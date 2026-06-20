<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('gacha.manage');
AuditLogger::access('gacha_campaign_read', 'page', 'gacha_campaign');

$config = GachaConfigService::load();
$counter = ['enabled' => false, 'current' => 0, 'max' => 5000, 'displayValue' => '0000'];
if (class_exists('GachaCampaignCounterService')) {
    try {
        $counter = GachaCampaignCounterService::status((string) Bootstrap::config('discord.guildId', ''));
    } catch (Throwable) {
        $counter['unavailable'] = true;
    }
}

Response::json([
    'ok' => true,
    'settings' => [
        'campaignCounterVisible' => (bool) ($config['settings']['campaignCounterVisible'] ?? true),
        'dailyCheckin' => GachaDailyCheckinService::config($config),
        'campaignBanners' => GachaCampaignBannerService::config($config),
    ],
    'counter' => $counter,
]);
