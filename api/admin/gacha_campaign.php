<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
Auth::requirePermission('gacha.manage');

$payload = Input::json();
$config = GachaConfigService::load();
$before = $config['settings'] ?? [];
$config['settings']['campaignCounterVisible'] = !empty($payload['campaignCounterVisible']);
if (array_key_exists('dailyCheckin', $payload)) {
    $config['settings']['dailyCheckin'] = GachaDailyCheckinService::normalizeConfig($payload['dailyCheckin']);
}
if (array_key_exists('campaignBanners', $payload)) {
    $config['settings']['campaignBanners'] = GachaCampaignBannerService::normalizeConfig($payload['campaignBanners']);
}

$adminActionId = AuditLogger::start(
    'gacha_campaign_save',
    'setting',
    GachaConfigService::SETTING_KEY,
    (string) Bootstrap::config('discord.guildId', ''),
    $payload,
    $before
);

try {
    $saved = GachaConfigService::save($config);
    AuditLogger::finish($adminActionId, 'success', ['ok' => true], $saved['settings'] ?? []);
    LiveUpdateService::markTopic('gacha_campaign', [
        'campaignCounterVisible' => (bool) ($saved['settings']['campaignCounterVisible'] ?? true),
        'dailyCheckin' => GachaDailyCheckinService::config($saved),
        'campaignBanners' => GachaCampaignBannerService::config($saved),
    ]);

    Response::json([
        'ok' => true,
        'settings' => [
            'campaignCounterVisible' => (bool) ($saved['settings']['campaignCounterVisible'] ?? true),
            'dailyCheckin' => GachaDailyCheckinService::config($saved),
            'campaignBanners' => GachaCampaignBannerService::config($saved),
        ],
    ]);
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    Response::error($exception->getMessage(), 500);
}
