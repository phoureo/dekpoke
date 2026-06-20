<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
Auth::requirePermission('permission.manage');

$payload = Input::json();
$tiers = is_array($payload['tiers'] ?? null) ? $payload['tiers'] : [];
$before = (new DashboardPermissionService())->payload();
$adminActionId = AuditLogger::start('permission_save', 'setting', 'dashboard_permission_tiers', (string) Bootstrap::config('discord.guildId', ''), $payload, $before);

try {
    $saved = (new DashboardPermissionService())->save($tiers);
    AuditLogger::finish($adminActionId, 'success', ['tiers' => count($saved['tiers'] ?? [])], $saved);
    Response::json(['ok' => true] + $saved);
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    Response::error($exception->getMessage(), 500);
}
