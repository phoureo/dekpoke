<?php

declare(strict_types=1);

require __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init(false);

$loop = in_array('--loop', $argv ?? [], true);
$interval = 300;
$dateOverride = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--interval=')) {
        $interval = max(30, (int) substr($arg, 11));
    } elseif (str_starts_with($arg, '--date=')) {
        $candidateDate = substr($arg, 7);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $candidateDate)) {
            $dateOverride = $candidateDate;
        }
    }
}

$run = 0;
do {
    $date = $dateOverride ?: date('Y-m-d');
    JobRunner::heartbeat('earn_worker', ['loop' => $run, 'date' => $date, 'intervalSeconds' => $interval]);
    try {
        $summary = (new EarnSummaryService())->rebuild($date);
        $earn = (new EarnService())->processDue($date);
        echo json_encode(['ok' => true, 'date' => $date, 'summary' => $summary, 'earn' => $earn], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } catch (Throwable $exception) {
        echo json_encode(['ok' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }
    $run++;
    if (!$loop) {
        break;
    }
    sleep($interval);
} while (true);
