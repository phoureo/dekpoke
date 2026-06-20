<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('ops.backfill');
AuditLogger::access('backfill_data_read', 'page', 'backfill', AuditLogger::requestPayload(), 'sensitive');

$guildId = (string) Bootstrap::config('discord.guildId', '');
$status = (new BackfillService())->status();
$jobs = Database::fetchAll(
    'SELECT *
     FROM tbl_sync_job
     WHERE jobType IN ("server_sync", "bot_log_archive", "message_backfill", "canonical_bot_logs", "download_attachments", "earn_worker", "backfill_all")
     ORDER BY syncJobId DESC
     LIMIT 80'
);
$workers = Database::fetchAll(
    'SELECT *
     FROM tbl_worker_heartbeat
     WHERE workerName IN ("gateway_worker", "sync_worker", "backfill_worker", "earn_worker")
     ORDER BY heartbeatDate DESC'
);

$cursorCount = (int) (Database::fetch(
    'SELECT COUNT(*) AS total FROM tbl_sync_cursor WHERE guildId = :guildId',
    ['guildId' => $guildId]
)['total'] ?? 0);

Response::json([
    'ok' => true,
    'system' => LiveUpdateService::systemState(),
    'backfill' => $status,
    'workers' => $workers,
    'jobs' => $jobs,
    'metrics' => [
        'catalogItems' => count($status['catalog'] ?? []),
        'cursorCount' => $cursorCount,
        'queuedJobs' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_sync_job WHERE jobStatus = "queued"')['total'] ?? 0),
        'runningJobs' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_sync_job WHERE jobStatus = "running"')['total'] ?? 0),
        'staleRunningJobs' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_sync_job WHERE jobStatus = "running" AND COALESCE(startDate, createDate) < DATE_SUB(NOW(), INTERVAL 60 MINUTE)')['total'] ?? 0),
        'ingestErrors' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_ingest_error WHERE isResolved = 0')['total'] ?? 0),
        'queuedAttachments' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_message_attachment WHERE downloadStatus IN ("queued", "retry")')['total'] ?? 0),
    ],
]);
