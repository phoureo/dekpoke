<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';
if (!$code || !$state || !hash_equals((string) ($_SESSION['oauthState'] ?? ''), (string) $state)) {
    AuditLogger::access('oauth_callback_failed', 'auth', 'discord', ['reason' => 'Invalid OAuth state.'], 'sensitive');
    Response::error('Invalid OAuth state.', 400);
}
unset($_SESSION['oauthState']);
$flow = strtolower(trim((string) ($_SESSION['oauthFlow'] ?? 'admin')));
$returnTo = DiscordOAuth::sanitizeReturnTo($_SESSION['oauthReturnTo'] ?? null, $flow);
unset($_SESSION['oauthFlow'], $_SESSION['oauthReturnTo']);

try {
    $token = oauthRequest('https://discord.com/api/oauth2/token', [
        'client_id' => Bootstrap::config('discord.clientId', ''),
        'client_secret' => Bootstrap::config('discord.clientSecret', ''),
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => DiscordOAuth::redirectUri(),
    ]);

    $user = discordUser((string) $token['access_token']);

    if ($flow === 'gacha') {
        $player = PlayerAuth::createSession($user);
        AuditLogger::access('gacha_login_success', 'player_user', (string) ($player['userId'] ?? $user['id'] ?? 'unknown'), ['discordUserId' => $user['id'] ?? null]);
        header('Location: ' . $returnTo);
        exit;
    }

    $admin = Auth::upsertDiscordUser($user);
    if (!$admin) {
        AuditLogger::access('oauth_login_rejected', 'admin_user', (string) ($user['id'] ?? ''), ['reason' => 'Not allowed as dashboard admin.'], 'sensitive');
        Response::error('Your Discord account is not allowed to access this console.', 403);
    }

    Auth::createSession((int) $admin['adminUserId']);
    AuditLogger::access('login_success', 'admin_user', (string) $admin['adminUserId'], ['discordUserId' => $admin['discordUserId']]);
    header('Location: ' . $returnTo);
    exit;
} catch (Throwable $exception) {
    AuditLogger::access('oauth_callback_failed', 'auth', 'discord', ['error' => $exception->getMessage()], 'sensitive');
    Response::error('Discord OAuth failed.', 500, ['message' => $exception->getMessage()]);
}

function oauthRequest(string $url, array $payload): array
{
    $ch = curl_init($url);
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
        throw new RuntimeException($error ?: 'OAuth token request failed with HTTP ' . $status);
    }

    return $body;
}

function discordUser(string $accessToken): array
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
    if ($raw === false || $status < 200 || $status >= 300 || !is_array($body)) {
        throw new RuntimeException($error ?: 'Discord user request failed with HTTP ' . $status);
    }

    return $body;
}
