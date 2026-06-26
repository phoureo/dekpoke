<?php

declare(strict_types=1);

require __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init(false);

$loop = in_array('--loop', $argv ?? [], true);
$interval = 30;
$staleActiveMinutes = 5;
$limit = 100;

foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--interval=')) {
        $interval = max(5, (int) substr($arg, 11));
    } elseif (str_starts_with($arg, '--stale-active-minutes=')) {
        $staleActiveMinutes = max(1, (int) substr($arg, 23));
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = max(1, min(500, (int) substr($arg, 8)));
    }
}

$run = 0;
do {
    JobRunner::heartbeat('gacha_reward_worker', [
        'loop' => $run,
        'intervalSeconds' => $interval,
        'staleActiveMinutes' => $staleActiveMinutes,
        'limit' => $limit,
    ]);

    try {
        $service = new GachaRewardSettlementService();
        $result = [
            'staleActive' => $service->processStaleActiveDraws($staleActiveMinutes, $limit),
            'pendingSettlements' => $service->processPendingSettlements($limit),
            'failedSettlements' => $service->retryFailedSettlements($limit),
        ];
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
