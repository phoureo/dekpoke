<?php

return [
    'app' => [
        'name' => 'Dekpoke Workspace Console',
        'baseUrl' => 'http://localhost/workspace',
        'timezone' => 'Asia/Bangkok',
        'env' => 'local',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'dekpoke_workspace',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'discord' => [
        'apiBase' => 'https://discord.com/api/v10',
        'gatewayBase' => 'wss://gateway.discord.gg/?v=10&encoding=json',
        'gatewayIntents' => 3243775 | (1 << 15),
        'clientId' => 'APPLICATION_ID',
        'clientSecret' => 'OAUTH2_CLIENT_SECRET',
        'redirectUri' => 'http://localhost/workspace/api/auth/callback.php',
        'botToken' => 'BOT_TOKEN',
        'botUserId' => 'BOT_USER_ID',
        'guildId' => 'SERVER_ID',
        'oauthScopes' => 'identify guilds',
        'syncPageLimit' => 1000,
        'messageBackfillLimit' => 100,
        'downloadAttachments' => true,
    ],
    'auth' => [
        'sessionName' => 'dekpoke_workspace_session',
        'allowFirstLoginAsOwner' => true,
        'ownerDiscordUserIds' => ['YOUR_DISCORD_USER_ID'],
        'playerTokenSecret' => 'change-me-workspace-player-token',
    ],
    'security' => [
        'csrfTokenBytes' => 32,
        'strictRoleHierarchy' => true,
    ],
    'audit' => [
        'sensitiveKeys' => [
            'authorization',
            'botToken',
            'clientSecret',
            'cookie',
            'csrf',
            'password',
            'secret',
            'token',
            'apiKey',
        ],
    ],
];
