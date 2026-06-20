<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

function gachaSessionResponse(?array $playerSession = null): void
{
    $playerSession ??= PlayerAuth::sessionPayload();
    if (!empty($playerSession['isAuthenticated'])) {
        Response::json(array_merge($playerSession, [
            'ok' => true,
            'csrfToken' => Csrf::token(),
            'appName' => Bootstrap::config('app.name'),
            'guildId' => Bootstrap::config('discord.guildId', ''),
            'discordClientId' => Bootstrap::config('discord.clientId', ''),
            'authFlow' => 'gacha',
        ]));
    }

    Response::json([
        'ok' => true,
        'isAuthenticated' => false,
        'csrfToken' => Csrf::token(),
        'appName' => Bootstrap::config('app.name'),
        'authFlow' => 'gacha',
        'discordClientId' => Bootstrap::config('discord.clientId', ''),
    ]);
}

$requestPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['REQUEST_URI'] ?? ''));
$isGachaRequest = str_contains($requestPath, '/gacha/')
    || str_contains($requestPath, '/gacha/api/auth/')
    || !empty($_SERVER['HTTP_X_GACHA_PLAYER_TOKEN'])
    || !empty($_COOKIE['gachaPlayerToken'])
    || isset($_GET['player_token'])
    || strtolower(trim((string) ($_GET['flow'] ?? ''))) === 'gacha';

$playerSession = PlayerAuth::sessionPayload();
if ($isGachaRequest) {
    gachaSessionResponse($playerSession);
}

$user = Auth::currentUser();
if (!$user) {
    gachaSessionResponse($playerSession);
}

Auth::touchSession((int) $user['adminUserId']);
Response::json([
    'ok' => true,
    'isAuthenticated' => true,
    'csrfToken' => Csrf::token(),
    'user' => [
        'adminUserId' => (int) $user['adminUserId'],
        'discordUserId' => $user['discordUserId'],
        'displayName' => $user['displayName'],
        'discordUserName' => $user['discordUserName'],
        'avatarUrl' => DiscordAssets::avatar($user['discordUserId'], $user['avatarHash'], 256),
        'roleName' => $user['roleName'],
        'permissions' => json_decode((string) ($user['permissionsJson'] ?? '[]'), true) ?: [],
    ],
    'guildId' => Bootstrap::config('discord.guildId', ''),
    'appName' => Bootstrap::config('app.name'),
    'discordClientId' => Bootstrap::config('discord.clientId', ''),
    'authFlow' => 'admin',
]);
