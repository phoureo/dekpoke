<?php

declare(strict_types=1);

require __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init(false);

$loop = in_array('--loop', $argv ?? [], true);
$interval = 60;
$pendingLimit = 20;
$expiredLimit = 50;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--interval=')) {
        $interval = max(15, (int) substr($arg, 11));
    } elseif (str_starts_with($arg, '--pending-limit=')) {
        $pendingLimit = max(1, min(100, (int) substr($arg, 16)));
    } elseif (str_starts_with($arg, '--expired-limit=')) {
        $expiredLimit = max(1, min(200, (int) substr($arg, 16)));
    }
}

$run = 0;
do {
    JobRunner::heartbeat('gacha_role_worker', [
        'loop' => $run,
        'intervalSeconds' => $interval,
        'pendingLimit' => $pendingLimit,
        'expiredLimit' => $expiredLimit,
    ]);
    try {
        $result = class_exists('GachaRoleGrantService')
            ? (static function () use ($pendingLimit, $expiredLimit): array {
                $service = new GachaRoleGrantService();
                return [
                    'pending' => $service->processPending($pendingLimit),
                    'expired' => $service->processExpired($expiredLimit),
                ];
            })()
            : ['checked' => 0, 'revoked' => 0, 'failed' => 0, 'message' => 'GachaRoleGrantService unavailable'];
        echo json_encode(['ok' => true, 'result' => $result], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } catch (Throwable $exception) {
        echo json_encode(['ok' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }

    $run++;
    if (!$loop) {
        break;
    }
    sleep($interval);
} while (true);
