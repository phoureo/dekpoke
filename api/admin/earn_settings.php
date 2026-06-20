<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('earn_settings'));

$payload = Input::json();
$rules = is_array($payload['rules'] ?? null) ? $payload['rules'] : [];
$adminActionId = AuditLogger::start('earn_settings_save', 'setting', 'earn_rules', (string) Bootstrap::config('discord.guildId', ''), $payload);
try {
    $saved = (new EarnService())->saveRules($rules);
    AuditLogger::finish($adminActionId, 'success', ['rules' => count($saved['rules'] ?? [])], $saved);
    Response::json(['ok' => true] + $saved);
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    Response::error($exception->getMessage(), 500);
}
