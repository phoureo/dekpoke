<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

function checkin_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function checkin_input(): array
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

$input = checkin_input();
$action = strtolower(trim((string) ($input['action'] ?? 'status')));
$guildId = (string) Bootstrap::config('discord.guildId', '');
$player = PlayerAuth::currentUser();
$userId = is_array($player) ? (string) ($player['userId'] ?? '') : '';
$config = GachaConfigService::load();

try {
    if ($action === 'claim') {
        if ($userId === '') {
            checkin_json([
                'ok' => false,
                'code' => 'AUTH_REQUIRED',
                'message' => 'login required',
                'requiresLogin' => true,
            ], 401);
        }
        $type = strtolower(trim((string) ($input['type'] ?? 'day')));
        $value = max(1, (int) ($input['value'] ?? $input['day'] ?? $input['days'] ?? 0));
        checkin_json(GachaDailyCheckinService::claim($guildId, $userId, $type, $value, $config));
    }

    checkin_json(GachaDailyCheckinService::status($guildId, $userId, $config));
} catch (Throwable $exception) {
    $code = $exception->getMessage();
    $status = in_array($code, ['AUTH_REQUIRED'], true) ? 401 : 422;
    checkin_json([
        'ok' => false,
        'code' => $code ?: 'CHECKIN_ERROR',
        'message' => $code ?: 'check-in failed',
    ], $status);
}
