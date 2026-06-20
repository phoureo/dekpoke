<?php

declare(strict_types=1);

final class Auth
{
    public static function currentUser(): ?array
    {
        $adminUserId = $_SESSION['adminUserId'] ?? null;
        if (!$adminUserId) {
            return null;
        }

        return Database::fetch(
            'SELECT * FROM tbl_admin_user WHERE adminUserId = :adminUserId AND isActive = 1',
            ['adminUserId' => $adminUserId]
        );
    }

    public static function requireUser(): array
    {
        $user = self::currentUser();
        if (!$user) {
            Response::error('Authentication required.', 401);
        }

        self::touchSession((int) $user['adminUserId']);
        return $user;
    }

    public static function can(string $permission, ?array $user = null): bool
    {
        $user ??= self::currentUser();
        if (!$user) {
            return false;
        }

        if (($user['roleName'] ?? '') === 'Owner') {
            return true;
        }

        $permissions = json_decode((string) ($user['permissionsJson'] ?? '[]'), true);
        return is_array($permissions) && (in_array('*', $permissions, true) || in_array($permission, $permissions, true));
    }

    public static function canAny(array $permissions, ?array $user = null): bool
    {
        $user ??= self::currentUser();
        if (!$user) {
            return false;
        }

        foreach ($permissions as $permission) {
            $permission = (string) $permission;
            if ($permission !== '' && self::can($permission, $user)) {
                return true;
            }
        }

        return false;
    }

    public static function requirePermission(string $permission): array
    {
        $user = self::requireUser();
        if (!self::can($permission, $user)) {
            AuditLogger::reject('permission_reject', 'Permission denied.', 'permission', $permission, AuditLogger::requestPayload());
            Response::error('Permission denied.', 403);
        }

        return $user;
    }

    public static function requireAnyPermission(array $permissions): array
    {
        $user = self::requireUser();
        if (!self::canAny($permissions, $user)) {
            AuditLogger::reject('permission_reject', 'Permission denied.', 'permission', implode(',', array_map('strval', $permissions)), AuditLogger::requestPayload());
            Response::error('Permission denied.', 403);
        }

        return $user;
    }

    public static function snapshot(?array $user = null): array
    {
        $user ??= self::currentUser();
        if (!$user) {
            return ['isAuthenticated' => false, 'permissions' => []];
        }

        $permissions = json_decode((string) ($user['permissionsJson'] ?? '[]'), true);

        return [
            'isAuthenticated' => true,
            'adminUserId' => (int) $user['adminUserId'],
            'discordUserId' => $user['discordUserId'],
            'roleName' => $user['roleName'],
            'permissions' => is_array($permissions) ? $permissions : [],
        ];
    }

    public static function createSession(int $adminUserId): void
    {
        session_regenerate_id(true);
        $_SESSION['adminUserId'] = $adminUserId;
        Csrf::token();

        Database::insert('tbl_admin_session', [
            'adminUserId' => $adminUserId,
            'sessionHash' => hash('sha256', session_id()),
            'deviceLabel' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device', 0, 190),
            'ipAddress' => Http::clientIp(),
            'userAgent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'lastSeenDate' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function touchSession(int $adminUserId): void
    {
        Database::execute(
            'UPDATE tbl_admin_session
             SET lastSeenDate = :lastSeenDate
             WHERE adminUserId = :adminUserId AND sessionHash = :sessionHash AND isRevoked = 0',
            [
                'adminUserId' => $adminUserId,
                'sessionHash' => hash('sha256', session_id()),
                'lastSeenDate' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public static function logout(): void
    {
        $adminUserId = $_SESSION['adminUserId'] ?? null;
        if ($adminUserId) {
            Database::execute(
                'UPDATE tbl_admin_session
                 SET isRevoked = 1, revokeDate = :revokeDate
                 WHERE adminUserId = :adminUserId AND sessionHash = :sessionHash',
                [
                    'adminUserId' => $adminUserId,
                    'sessionHash' => hash('sha256', session_id()),
                    'revokeDate' => date('Y-m-d H:i:s'),
                ]
            );
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
    }

    public static function upsertDiscordUser(array $discordUser): ?array
    {
        $discordUserId = (string) $discordUser['id'];
        $existing = Database::fetch(
            'SELECT * FROM tbl_admin_user WHERE discordUserId = :discordUserId',
            ['discordUserId' => $discordUserId]
        );

        $displayName = $discordUser['global_name'] ?? $discordUser['username'] ?? $discordUserId;
        $username = $discordUser['username'] ?? $discordUserId;
        $avatarHash = $discordUser['avatar'] ?? null;

        if ($existing) {
            Database::execute(
                'UPDATE tbl_admin_user
                 SET discordUserName = :discordUserName, displayName = :displayName, avatarHash = :avatarHash, updateDate = :updateDate
                 WHERE adminUserId = :adminUserId',
                [
                    'discordUserName' => $username,
                    'displayName' => $displayName,
                    'avatarHash' => $avatarHash,
                    'updateDate' => date('Y-m-d H:i:s'),
                    'adminUserId' => $existing['adminUserId'],
                ]
            );

            return Database::fetch('SELECT * FROM tbl_admin_user WHERE adminUserId = :adminUserId', ['adminUserId' => $existing['adminUserId']]);
        }

        $ownerIds = Bootstrap::config('auth.ownerDiscordUserIds', []);
        $adminCount = Database::fetch('SELECT COUNT(*) AS total FROM tbl_admin_user');
        $isFirstOwner = Bootstrap::config('auth.allowFirstLoginAsOwner', true) && (int) ($adminCount['total'] ?? 0) === 0;
        $isConfiguredOwner = in_array($discordUserId, $ownerIds, true);

        if (!$isFirstOwner && !$isConfiguredOwner) {
            return null;
        }

        $adminUserId = Database::insert('tbl_admin_user', [
            'discordUserId' => $discordUserId,
            'discordUserName' => $username,
            'displayName' => $displayName,
            'avatarHash' => $avatarHash,
            'roleName' => 'Owner',
            'permissionsJson' => json_encode(['*'], JSON_UNESCAPED_SLASHES),
            'isActive' => 1,
        ]);

        return Database::fetch('SELECT * FROM tbl_admin_user WHERE adminUserId = :adminUserId', ['adminUserId' => $adminUserId]);
    }
}
