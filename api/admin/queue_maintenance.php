<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('ops.manage');
Csrf::assertValid();

$payload = Input::json() + $_POST;
$staleMinutes = max(5, min(1440, (int) ($payload['staleMinutes'] ?? 60)));
$adminActionId = AuditLogger::start('queue_maintenance', 'sync_job', 'maintenance', Bootstrap::config('discord.guildId', ''), $payload);

try {
    $result = JobRunner::recoverQueue($staleMinutes, JobRunner::BACKGROUND_JOB_TYPES);
    AuditLogger::finish($adminActionId, 'success', $result);
    Response::json(['ok' => true, 'adminActionId' => $adminActionId, 'result' => $result]);
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    Response::error($exception->getMessage(), 500, ['adminActionId' => $adminActionId]);
}
