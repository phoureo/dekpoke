<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

function campaign_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function campaign_input(): array
{
    $input = $_POST;
    if (!$input) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
    return array_merge($_GET, $input);
}

$input = campaign_input();
$action = strtolower(trim((string) ($input['action'] ?? 'status')));
$guildId = (string) Bootstrap::config('discord.guildId', '');
$player = PlayerAuth::currentUser();
$userId = is_array($player) ? (string) ($player['userId'] ?? '') : '';
$config = GachaConfigService::load();

try {
    if ($action === 'claim') {
        if ($userId === '') {
            campaign_json([
                'ok' => false,
                'code' => 'AUTH_REQUIRED',
                'message' => 'login required',
                'requiresLogin' => true,
            ], 401);
        }
        $bannerId = trim((string) ($input['bannerId'] ?? $input['id'] ?? ''));
        campaign_json(GachaCampaignBannerService::claim($guildId, $userId, $bannerId, $config));
    }

    campaign_json(GachaCampaignBannerService::status($guildId, $userId, $config));
} catch (Throwable $exception) {
    $code = $exception->getMessage();
    $status = in_array($code, ['AUTH_REQUIRED'], true) ? 401 : 422;
    campaign_json([
        'ok' => false,
        'code' => $code ?: 'CAMPAIGN_BANNER_ERROR',
        'message' => $code ?: 'campaign banner failed',
    ], $status);
}
