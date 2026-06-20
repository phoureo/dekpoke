<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('ops.manage');
Csrf::assertValid();

$payload = Input::json() + $_POST;
$jobType = (string) ($payload['jobType'] ?? '');
if ($jobType === '') {
    Response::error('jobType is required.', 422);
}

$allowed = ['server_sync', 'bot_log_archive', 'message_backfill', 'canonical_bot_logs', 'download_attachments', 'earn_worker', 'backfill_all'];
if (!in_array($jobType, $allowed, true)) {
    AuditLogger::reject('job_enqueue', 'Unsupported job type.', 'sync_job', $jobType, $payload, Bootstrap::config('discord.guildId', ''));
    Response::error('Unsupported job type.', 422);
}

$defaultPriority = [
    'server_sync' => 10,
    'bot_log_archive' => 20,
    'message_backfill' => 20,
    'canonical_bot_logs' => 22,
    'backfill_all' => 20,
    'download_attachments' => 25,
    'earn_worker' => 40,
][$jobType] ?? 100;

$adminActionId = AuditLogger::start('job_enqueue', 'sync_job', $jobType, Bootstrap::config('discord.guildId', ''), $payload);
$enqueue = JobRunner::enqueueOnceOpen(
    $jobType,
    is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
    (int) ($payload['priority'] ?? $defaultPriority)
);
$syncJobId = (int) $enqueue['syncJobId'];
AuditLogger::target($adminActionId, 'sync_job', (string) $syncJobId);
AuditLogger::finish($adminActionId, 'success', ['syncJobId' => $syncJobId, 'reused' => (bool) $enqueue['reused']]);

Response::json(['ok' => true, 'syncJobId' => $syncJobId, 'reused' => (bool) $enqueue['reused'], 'adminActionId' => $adminActionId]);
