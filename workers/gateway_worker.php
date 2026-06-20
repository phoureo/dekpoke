<?php

declare(strict_types=1);

require __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init(false);

$maxEvents = 0;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--max-events=')) {
        $maxEvents = (int) substr($arg, 13);
    }
}
$once = $maxEvents > 0 || in_array('--once', $argv ?? [], true);
$attempt = 0;

do {
    $attempt++;
    try {
        JobRunner::heartbeat('gateway_worker', ['starting' => true, 'attempt' => $attempt]);
        $result = (new GatewayWorkerService())->run($maxEvents);
        JobRunner::heartbeat('gateway_worker', ['stopped' => true, 'attempt' => $attempt] + $result);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        if ($once) {
            break;
        }
    } catch (Throwable $exception) {
        JobRunner::heartbeat('gateway_worker', [
            'error' => $exception->getMessage(),
            'attempt' => $attempt,
            'reconnectInSeconds' => 5,
        ]);
        LiveUpdateService::markTopic('backfill', ['source' => 'gateway_worker', 'status' => 'reconnect_wait', 'error' => $exception->getMessage()], 'worker', 'gateway_worker');
        fwrite(STDERR, 'Gateway reconnect after error: ' . $exception->getMessage() . "\n");
        if ($once) {
            throw $exception;
        }
        sleep(5);
    }
} while (true);
