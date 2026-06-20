<?php

declare(strict_types=1);

final class PlayerAuth
{
    private const SESSION_KEY = 'gachaPlayerUserId';
    private const TOKEN_HEADER_KEY = 'HTTP_X_GACHA_PLAYER_TOKEN';
    private const TOKEN_COOKIE_KEY = 'gachaPlayerToken';
    private const TOKEN_TTL = 2592000;

    public static function currentUser(): ?array
    {
        $userId = self::currentUserId();
        if ($userId === '') {
            return null;
        }

        return Database::fetch(
            'SELECT u.userId,
                    u.userName,
                    u.globalName,
                    u.discriminator,
                    u.avatarHash,
                    u.bannerHash,
                    u.accentColor,
                    u.isBot,
                    m.nickName,
                    m.guildAvatarHash,
                    m.isActive AS guildMemberActive
               FROM tbl_user u
          LEFT JOIN tbl_member m
                 ON m.guildId = :guildId
                AND m.userId = u.userId
              WHERE u.userId = :userId
              LIMIT 1',
            [
                'guildId' => (string) Bootstrap::config('discord.guildId', ''),
                'userId' => $userId,
            ]
        );
    }

    public static function createSession(array $discordUser): array
    {
        $userId = trim((string) ($discordUser['id'] ?? ''));
        if ($userId === '') {
            throw new RuntimeException('Discord user id is required.');
        }

        Database::execute(
            'INSERT INTO tbl_user
                    (userId, userName, globalName, discriminator, avatarHash, bannerHash, accentColor, isBot, metadataJson, updateDate)
             VALUES (:userId, :userName, :globalName, :discriminator, :avatarHash, :bannerHash, :accentColor, :isBot, :metadataJson, :updateDate)
             ON DUPLICATE KEY UPDATE
                    userName = VALUES(userName),
                    globalName = VALUES(globalName),
                    discriminator = VALUES(discriminator),
                    avatarHash = VALUES(avatarHash),
                    bannerHash = VALUES(bannerHash),
                    accentColor = VALUES(accentColor),
                    isBot = VALUES(isBot),
                    metadataJson = VALUES(metadataJson),
                    updateDate = VALUES(updateDate)',
            [
                'userId' => $userId,
                'userName' => trim((string) ($discordUser['username'] ?? $userId)) ?: $userId,
                'globalName' => self::nullableTrim($discordUser['global_name'] ?? null),
                'discriminator' => self::nullableTrim($discordUser['discriminator'] ?? null),
                'avatarHash' => self::nullableTrim($discordUser['avatar'] ?? null),
                'bannerHash' => self::nullableTrim($discordUser['banner'] ?? null),
                'accentColor' => isset($discordUser['accent_color']) && $discordUser['accent_color'] !== null ? (int) $discordUser['accent_color'] : null,
                'isBot' => !empty($discordUser['bot']) ? 1 : 0,
                'metadataJson' => json_encode($discordUser, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $userId;

        $user = self::currentUser();
        if (!$user) {
            throw new RuntimeException('Unable to create player session.');
        }

        return $user;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::clearTokenCookie();
    }

    public static function resumeSessionFromToken(string $token): ?array
    {
        $payload = self::resumeTokenPayloadFromToken($token);
        $tokenUserId = trim((string) ($payload['userId'] ?? ''));
        if ($tokenUserId === '') {
            return null;
        }

        $_SESSION[self::SESSION_KEY] = $tokenUserId;
        return self::currentUser();
    }

    public static function sessionPayload(): array
    {
        $user = self::currentUser();
        if (!$user) {
            return ['isAuthenticated' => false];
        }

        $displayName = trim((string) ($user['nickName'] ?? ''))
            ?: trim((string) ($user['globalName'] ?? ''))
            ?: trim((string) ($user['userName'] ?? ''))
            ?: 'Discord Player';

        $avatarUrl = DiscordAssets::avatar((string) $user['userId'], (string) ($user['avatarHash'] ?? null), 256);
        $guildAvatarHash = trim((string) ($user['guildAvatarHash'] ?? ''));
        if ($guildAvatarHash !== '') {
            $avatarUrl = DiscordAssets::guildAvatar(
                (string) Bootstrap::config('discord.guildId', ''),
                (string) $user['userId'],
                $guildAvatarHash,
                256
            ) ?: $avatarUrl;
        }

        return [
            'isAuthenticated' => true,
            'user' => [
                'discordUserId' => (string) $user['userId'],
                'displayName' => $displayName,
                'discordUserName' => (string) ($user['userName'] ?? ''),
                'avatarUrl' => $avatarUrl,
                'roleName' => !empty($user['guildMemberActive']) ? 'สมาชิกเซิร์ฟเวอร์' : 'Discord Player',
                'isGuildMember' => !empty($user['guildMemberActive']),
            ],
            'playerAuthToken' => self::issueResumeToken((string) $user['userId']),
            'playerAuthTokenExpiresAt' => time() + self::TOKEN_TTL,
        ];
    }

    private static function currentUserId(): string
    {
        $userId = trim((string) ($_SESSION[self::SESSION_KEY] ?? ''));
        if ($userId !== '') {
            return $userId;
        }

        $payload = self::resumeTokenPayload();
        $tokenUserId = trim((string) ($payload['userId'] ?? ''));
        if ($tokenUserId === '') {
            return '';
        }

        $_SESSION[self::SESSION_KEY] = $tokenUserId;
        return $tokenUserId;
    }

    private static function resumeTokenPayload(): ?array
    {
        $token = trim((string) ($_SERVER[self::TOKEN_HEADER_KEY] ?? $_COOKIE[self::TOKEN_COOKIE_KEY] ?? ''));
        return self::resumeTokenPayloadFromToken($token);
    }

    private static function resumeTokenPayloadFromToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 4096) {
            return null;
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $encodedSignature] = $parts;
        if (!hash_equals(self::signResumeToken($encodedPayload), $encodedSignature)) {
            return null;
        }

        $decodedPayload = self::base64UrlDecode($encodedPayload);
        if ($decodedPayload === false) {
            return null;
        }

        $payload = json_decode($decodedPayload, true);
        if (!is_array($payload)) {
            return null;
        }

        $expiresAt = (int) ($payload['expiresAt'] ?? 0);
        if ($expiresAt <= time()) {
            return null;
        }

        return $payload;
    }

    private static function issueResumeToken(string $userId): string
    {
        $issuedAt = time();
        $payload = self::base64UrlEncode(json_encode([
            'userId' => $userId,
            'issuedAt' => $issuedAt,
            'expiresAt' => $issuedAt + self::TOKEN_TTL,
            'version' => 1,
        ], JSON_UNESCAPED_SLASHES));

        return $payload . '.' . self::signResumeToken($payload);
    }

    private static function signResumeToken(string $encodedPayload): string
    {
        return self::base64UrlEncode(
            hash_hmac('sha256', $encodedPayload, self::resumeTokenSecret(), true)
        );
    }

    private static function resumeTokenSecret(): string
    {
        $configured = trim((string) Bootstrap::config('auth.playerTokenSecret', ''));
        if ($configured !== '') {
            return $configured;
        }

        return implode('|', [
            'dekpoke-gacha-player-token',
            (string) Bootstrap::config('discord.clientSecret', ''),
            (string) Bootstrap::config('discord.clientId', ''),
            (string) Bootstrap::config('discord.guildId', ''),
            (string) Bootstrap::config('auth.sessionName', 'dekpoke_orbit_session'),
        ]);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string|false
    {
        $padded = strtr($value, '-_', '+/');
        $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
        return base64_decode($padded, true);
    }

    private static function clearTokenCookie(): void
    {
        setcookie(self::TOKEN_COOKIE_KEY, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }

    private static function nullableTrim(mixed $value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }
}
