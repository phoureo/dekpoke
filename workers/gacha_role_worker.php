<?php

declare(strict_types=1);

require __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init(false);

$loop = in_array('--loop', $argv ?? [], true);
$interval = 60;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--interval=')) {
        $interval = max(15, (int) substr($arg, 11));
    }
}

$run = 0;
do {
    JobRunner::heartbeat('gacha_role_worker', ['loop' => $run, 'intervalSeconds' => $interval]);
    try {
        $result = class_exists('GachaRoleGrantService')
            ? (static function (): array {
                $service = new GachaRoleGrantService();
                return [
                    'pending' => $service->processPending(20),
                    'expired' => $service->processExpired(50),
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
