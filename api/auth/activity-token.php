<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    Response::error('Method not allowed. This endpoint accepts POST from Discord Activity only.', 405);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    Response::error('Invalid JSON body.', 400);
}

$code = trim((string) ($input['code'] ?? ''));
if ($code === '' || strlen($code) > 4096) {
    Response::error('Discord authorization code is required.', 400);
}

try {
    $token = activityOauthRequest([
        'client_id' => Bootstrap::config('discord.clientId', ''),
        'client_secret' => Bootstrap::config('discord.clientSecret', ''),
        'grant_type' => 'authorization_code',
        'code' => $code,
    ]);

    $accessToken = (string) ($token['access_token'] ?? '');
    if ($accessToken === '') {
        throw new RuntimeException('Discord token response did not include an access token.');
    }

    $user = activityDiscordUser($accessToken);
    $player = PlayerAuth::createSession($user);
    AuditLogger::access(
        'gacha_activity_login_success',
        'player_user',
        (string) ($player['userId'] ?? $user['id'] ?? 'unknown'),
        ['discordUserId' => $user['id'] ?? null]
    );

    Response::json([
        'ok' => true,
        'access_token' => $accessToken,
        'token_type' => $token['token_type'] ?? null,
        'expires_in' => isset($token['expires_in']) ? (int) $token['expires_in'] : null,
        'session' => PlayerAuth::sessionPayload(),
    ]);
} catch (Throwable $exception) {
    AuditLogger::access(
        'gacha_activity_login_failed',
        'auth',
        'discord',
        ['error' => $exception->getMessage()],
        'sensitive'
    );
    Response::error('Discord Activity auth failed.', 500, ['message' => $exception->getMessage()]);
}

function activityOauthRequest(array $payload): array
{
    $ch = curl_init('https://discord.com/api/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $body = json_decode((string) $raw, true);
    if ($raw === false || $status < 200 || $status >= 300 || !is_array($body)) {
        throw new RuntimeException($error ?: 'Discord token exchange failed with HTTP ' . $status . ': ' . (string) $raw);
    }

    return $body;
}

function activityDiscordUser(string $accessToken): array
{
    $ch = curl_init('https://discord.com/api/users/@me');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $body = json_decode((string) $raw, true);
    if ($raw === false || $status < 200 || $status >= 300 || !is_array($body) || empty($body['id'])) {
        throw new RuntimeException($error ?: 'Discord user request failed with HTTP ' . $status . ': ' . (string) $raw);
    }

    return $body;
}
