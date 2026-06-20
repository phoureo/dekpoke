<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
Auth::requirePermission('admin.view');

$payload = Input::json();
$permissions = is_array($payload['permissions'] ?? null) ? $payload['permissions'] : null;
if ($permissions === null) {
    Response::error('permissions is required.', 422);
}

$before = RolePermissionDescriptionService::load();
$adminActionId = AuditLogger::start(
    'role_permission_descriptions_save',
    'setting',
    RolePermissionDescriptionService::SETTING_KEY,
    (string) Bootstrap::config('discord.guildId', ''),
    $payload,
    $before
);

try {
    $saved = RolePermissionDescriptionService::save(['permissions' => $permissions]);
    $catalog = RolePermissionDescriptionService::catalog();
    AuditLogger::finish($adminActionId, 'success', ['ok' => true], $saved);
    LiveUpdateService::mark(['roles', 'role_permission_descriptions', 'gacha_prize'], 'role_permission_descriptions_save', 'setting', RolePermissionDescriptionService::SETTING_KEY, [
        'permissions' => count($catalog),
        'customized' => RolePermissionDescriptionService::metrics(false)['customized'] ?? 0,
    ]);
    try {
        PublicPageCacheService::clear('gacha-shop');
        PublicPageCacheService::clear('gacha-prizes');
    } catch (Throwable) {
        // Cache invalidation must never block a successful permission description save.
    }

    Response::json([
        'ok' => true,
        'permissions' => $catalog,
        'metrics' => RolePermissionDescriptionService::metrics(false),
    ]);
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    Response::error($exception->getMessage(), 500);
}
