<?php

declare(strict_types=1);

require __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init(false);

$loops = in_array('--loop', $argv ?? [], true) ? 0 : 1;
$done = 0;
$jobTypes = ['server_sync', 'bot_log_archive', 'message_backfill', 'canonical_bot_logs', 'download_attachments', 'earn_worker', 'backfill_all'];

do {
    JobRunner::heartbeat('sync_worker', ['loop' => $done]);
    $job = JobRunner::claim($jobTypes);
    if (!$job) {
        if ($loops === 1) {
            echo "No queued jobs.\n";
            break;
        }
        sleep(5);
        continue;
    }

    try {
        $payload = json_decode((string) ($job['payloadJson'] ?? '{}'), true) ?: [];
        $result = handleJob((string) $job['jobType'], $payload);
        JobRunner::complete((int) $job['syncJobId'], $result);
        echo 'Completed job #' . $job['syncJobId'] . ' ' . $job['jobType'] . "\n";
    } catch (Throwable $exception) {
        JobRunner::fail((int) $job['syncJobId'], $exception->getMessage());
        echo 'Failed job #' . $job['syncJobId'] . ': ' . $exception->getMessage() . "\n";
    }

    $done++;
} while ($loops === 0 || $done < $loops);

function handleJob(string $jobType, array $payload): array
{
    return match ($jobType) {
        'server_sync' => (new DiscordSyncService())->syncAll(),
        'bot_log_archive', 'message_backfill' => (new BackfillService())->run('bot_log_archive', $payload),
        'canonical_bot_logs' => (new BackfillService())->run('canonical_bot_logs', $payload),
        'download_attachments' => (new AttachmentStorageService())->downloadQueued((int) ($payload['limit'] ?? 30)),
        'earn_worker' => (new BackfillService())->run('earn_summary', $payload),
        'backfill_all' => (new BackfillService())->run('all', $payload),
        default => throw new RuntimeException('Unsupported job type: ' . $jobType),
    };
}
