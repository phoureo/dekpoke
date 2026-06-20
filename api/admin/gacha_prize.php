<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
Auth::requirePermission('gacha.manage');

$payload = Input::json();
$config = is_array($payload['config'] ?? null) ? $payload['config'] : null;
if (!$config) {
    Response::error('config is required.', 422);
}

$before = GachaConfigService::load();
$adminActionId = AuditLogger::start('gacha_prize_save', 'setting', GachaConfigService::SETTING_KEY, (string) Bootstrap::config('discord.guildId', ''), $payload, $before);

try {
    $saved = GachaConfigService::save($config);
    AuditLogger::finish($adminActionId, 'success', ['ok' => true], $saved);
    LiveUpdateService::mark(['gacha_prize'], 'gacha_prize_save', 'setting', GachaConfigService::SETTING_KEY, [
        'tiers' => count($saved['tiers']),
        'prizes' => count($saved['prizes']),
    ]);
    try {
        PublicPageCacheService::clear('gacha-prizes');
        PublicPageCacheService::clear('gacha-shop');
    } catch (Throwable) {
        // Cache invalidation must never block a successful admin save.
    }

    Response::json([
        'ok' => true,
        'config' => $saved,
        'timeline' => GachaConfigService::timeline($saved),
    ]);
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    Response::error($exception->getMessage(), 500);
}
