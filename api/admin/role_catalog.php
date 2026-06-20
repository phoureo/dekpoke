<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
Auth::requirePermission('admin.view');

$payload = Input::json();
$config = is_array($payload['config'] ?? null) ? $payload['config'] : null;
if ($config === null) {
    Response::error('config is required.', 422);
}

$before = RoleCatalogService::load();
$adminActionId = AuditLogger::start(
    'role_catalog_save',
    'setting',
    RoleCatalogService::SETTING_KEY,
    (string) Bootstrap::config('discord.guildId', ''),
    $payload,
    $before
);

try {
    $saved = RoleCatalogService::save($config);
    AuditLogger::finish($adminActionId, 'success', ['ok' => true], $saved);
    LiveUpdateService::mark(['roles', 'gacha_prize'], 'role_catalog_save', 'setting', RoleCatalogService::SETTING_KEY, [
        'series' => count($saved['series'] ?? []),
        'roleMeta' => count($saved['roles'] ?? []),
    ]);
    try {
        PublicPageCacheService::clear('gacha-shop');
        PublicPageCacheService::clear('gacha-prizes');
    } catch (Throwable) {
        // Cache invalidation must never block a successful role catalog save.
    }

    Response::json([
        'ok' => true,
        'config' => $saved,
        'tierOptions' => RoleCatalogService::tierOptions(),
    ]);
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    Response::error($exception->getMessage(), 500);
}
