<?php

declare(strict_types=1);

require __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init(false);

$type = $argv[1] ?? 'all';
$options = [];
foreach (array_slice($argv, 2) as $arg) {
    if ($arg === '--force') {
        $options['force'] = true;
    } elseif (str_starts_with($arg, '--channel=')) {
        $options['channelId'] = substr($arg, 10);
    } elseif (str_starts_with($arg, '--pages=')) {
        $options['pagesPerChannel'] = (int) substr($arg, 8);
        $options['pages'] = (int) substr($arg, 8);
    }
}

JobRunner::heartbeat('backfill_worker', ['type' => $type]);
$result = (new BackfillService())->run((string) $type, $options);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
