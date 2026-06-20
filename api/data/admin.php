<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('admin.view');
AuditLogger::access('admin_data_read', 'page', 'admin', AuditLogger::requestPayload(), 'sensitive');

$guildId = (string) Bootstrap::config('discord.guildId', '');
$logLimit = max(20, min(200, Input::int('limit', 80)));

$workers = Database::fetchAll('SELECT * FROM tbl_worker_heartbeat ORDER BY heartbeatDate DESC');
$jobs = Database::fetchAll('SELECT * FROM tbl_sync_job ORDER BY syncJobId DESC LIMIT 80');
$ingestErrors = Database::fetchAll('SELECT * FROM tbl_ingest_error WHERE isResolved = 0 ORDER BY ingestErrorId DESC LIMIT 80');
$rawEvents = Database::fetchAll(
    'SELECT rawEventId, guildId, eventType, eventSequence, processStatus, errorMessage, sourceName, channelId, userId, targetType, targetId, eventDate, createDate, processDate
     FROM tbl_raw_event
     WHERE guildId = :guildId OR guildId IS NULL
     ORDER BY rawEventId DESC
     LIMIT 100',
    ['guildId' => $guildId]
);
$staffLogs = Database::fetchAll(
    'SELECT aa.*, au.displayName, au.discordUserName
     FROM tbl_admin_action aa
     LEFT JOIN tbl_admin_user au ON au.adminUserId = aa.adminUserId
     ORDER BY aa.adminActionId DESC
     LIMIT ' . $logLimit
);
$accessLogs = Database::fetchAll(
    'SELECT al.*, au.displayName, au.discordUserName
     FROM tbl_access_log al
     LEFT JOIN tbl_admin_user au ON au.adminUserId = al.adminUserId
     ORDER BY al.accessLogId DESC
     LIMIT ' . $logLimit
);
$dashboardUsers = Database::fetchAll('SELECT adminUserId, discordUserId, discordUserName, displayName, roleName, isActive, createDate, updateDate FROM tbl_admin_user ORDER BY adminUserId ASC');
$rateLimits = Database::fetchAll('SELECT * FROM tbl_api_rate_limit ORDER BY updateDate DESC, createDate DESC LIMIT 40');

Response::json([
    'ok' => true,
    'system' => LiveUpdateService::systemState(),
    'metrics' => [
        'workers' => count($workers),
        'queuedJobs' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_sync_job WHERE jobStatus = "queued"')['total'] ?? 0),
        'runningJobs' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_sync_job WHERE jobStatus = "running"')['total'] ?? 0),
        'staleRunningJobs' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_sync_job WHERE jobStatus = "running" AND COALESCE(startDate, createDate) < DATE_SUB(NOW(), INTERVAL 60 MINUTE)')['total'] ?? 0),
        'rawEvents24h' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_raw_event WHERE createDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)')['total'] ?? 0),
        'errors' => count($ingestErrors),
        'activeViewers' => 0,
    ],
    'workers' => $workers,
    'jobs' => $jobs,
    'ingestErrors' => $ingestErrors,
    'rawEvents' => $rawEvents,
    'staffLogs' => $staffLogs,
    'accessLogs' => $accessLogs,
    'auditCompare' => [],
    'dashboardUsers' => $dashboardUsers,
    'viewers' => [],
    'rateLimits' => $rateLimits,
    'backfill' => (new BackfillService())->status(),
]);
