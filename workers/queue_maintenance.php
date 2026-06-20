<?php

declare(strict_types=1);

require __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init(false);

$staleMinutes = 60;
foreach (array_slice($argv ?? [], 1) as $arg) {
    if (str_starts_with($arg, '--stale-minutes=')) {
        $staleMinutes = max(5, min(1440, (int) substr($arg, 16)));
    }
}

$result = JobRunner::recoverQueue($staleMinutes, JobRunner::BACKGROUND_JOB_TYPES);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
