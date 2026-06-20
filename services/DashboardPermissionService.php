<?php

declare(strict_types=1);

final class DashboardPermissionService
{
    private const SETTING_KEY = 'dashboard.permission_tiers';

    /** @return array<string, array<int, string>> */
    public static function pagePermissionMap(): array
    {
        return [
            'roles' => ['admin.view'],
            'messages' => ['admin.view'],
            'activity' => ['admin.view'],

            'earn_settings' => ['rewards.earn_settings', 'rewards.manage'],
            'earn_manual' => ['rewards.earn_manual', 'rewards.manage'],
            'reward_report' => ['rewards.reward_report', 'rewards.manage'],
            'shop_report' => ['rewards.shop_report', 'rewards.manage'],
            'shop_member_bags' => ['rewards.shop_member_bags', 'rewards.manage'],
            'bag_transaction_report' => ['rewards.bag_transaction_report', 'rewards.manage'],
            'gacha_shop' => ['rewards.shop_setting', 'rewards.manage'],

            'gacha_prize' => ['gacha.manage'],
            'gacha_campaign' => ['gacha.manage'],
            'gacha_report' => ['gacha.manage'],

            'backfill' => ['ops.backfill'],
            'admin' => ['ops.admin', 'ops.backfill'],
            'logs' => ['logs.view'],
            'permission' => ['permission.manage'],
        ];
    }

    public function payload(): array
    {
        $tiers = $this->tiers();
        return [
            'tiers' => $this->decorateTiers($tiers),
            'permissionCatalog' => $this->permissionCatalog(),
            'adminUsers' => Database::fetchAll(
                'SELECT adminUserId, discordUserId, discordUserName, displayName, roleName, permissionsJson, isActive, updateDate
                   FROM tbl_admin_user
                  ORDER BY isActive DESC, roleName ASC, displayName ASC'
            ),
        ];
    }

    public function save(array $tiers): array
    {
        $normalized = $this->normalizeTiers($tiers);
        $this->assertUniqueUsers($normalized);
        Database::execute(
            'INSERT INTO tbl_setting (settingKey, settingValueJson, isSecret, updateDate)
             VALUES (:settingKey, :settingValueJson, 0, :updateDate)
             ON DUPLICATE KEY UPDATE settingValueJson = VALUES(settingValueJson), updateDate = VALUES(updateDate)',
            [
                'settingKey' => self::SETTING_KEY,
                'settingValueJson' => json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
        $this->applyTiersToAdminUsers($normalized);
        LiveUpdateService::markTopic('permission', ['scope' => 'permission_save']);
        return $this->payload();
    }

    public function canViewPage(?array $user, string $page): bool
    {
        if (!$user) {
            return false;
        }
        $permissions = $this->pagePermissions($page);
        return $permissions === [] || Auth::canAny($permissions, $user);
    }

    /** @return array<int, string> */
    public static function pagePermissionsFor(string $page): array
    {
        $page = strtolower(trim($page));
        $map = self::pagePermissionMap();
        return $map[$page] ?? ['admin.view'];
    }

    /** @return array<int, string> */
    private function pagePermissions(string $page): array
    {
        return self::pagePermissionsFor($page);
    }

    private function tiers(): array
    {
        $row = Database::fetch('SELECT settingValueJson FROM tbl_setting WHERE settingKey = :settingKey', ['settingKey' => self::SETTING_KEY]);
        $decoded = $row ? json_decode((string) ($row['settingValueJson'] ?? ''), true) : null;
        if (!is_array($decoded)) {
            $decoded = $this->defaultTiers();
        }
        return $this->normalizeTiers($decoded);
    }

    private function defaultTiers(): array
    {
        $ownerIds = array_values(array_filter(array_map('strval', Bootstrap::config('auth.ownerDiscordUserIds', []))));
        $ownerRows = Database::fetchAll('SELECT discordUserId FROM tbl_admin_user WHERE roleName = "Owner" OR permissionsJson LIKE "%*%"');
        foreach ($ownerRows as $row) {
            $ownerIds[] = (string) ($row['discordUserId'] ?? '');
        }
        $ownerIds = array_values(array_unique(array_filter($ownerIds)));

        return [
            ['id' => 'owner', 'name' => 'Owner', 'permissions' => ['*'], 'userIds' => $ownerIds],
            ['id' => 'admin', 'name' => 'Admin', 'permissions' => ['admin.view', 'rewards.manage', 'rewards.earn_settings', 'rewards.earn_manual', 'rewards.reward_report', 'rewards.shop_report', 'rewards.shop_member_bags', 'rewards.bag_transaction_report', 'rewards.shop_setting', 'gacha.manage', 'ops.admin', 'ops.backfill', 'logs.view'], 'userIds' => []],
            ['id' => 'reward', 'name' => 'Rewards', 'permissions' => ['admin.view', 'rewards.earn_settings', 'rewards.earn_manual', 'rewards.reward_report', 'rewards.shop_report', 'rewards.shop_member_bags', 'rewards.bag_transaction_report', 'rewards.shop_setting', 'gacha.manage'], 'userIds' => []],
            ['id' => 'viewer', 'name' => 'Viewer', 'permissions' => ['admin.view'], 'userIds' => []],
            ['id' => 'permission_admin', 'name' => 'Permission Admin', 'permissions' => ['admin.view', 'ops.admin', 'permission.manage', 'logs.view'], 'userIds' => []],
        ];
    }

    private function permissionCatalog(): array
    {
        return [
            ['code' => '*', 'label' => 'All permissions'],
            ['code' => 'admin.view', 'label' => 'View dashboard'],
            ['code' => 'rewards.manage', 'label' => 'Rewards all pages (legacy umbrella)'],
            ['code' => 'rewards.earn_settings', 'label' => 'Rewards: Earn Settings'],
            ['code' => 'rewards.earn_manual', 'label' => 'Rewards: Manual Earn'],
            ['code' => 'rewards.reward_report', 'label' => 'Rewards: Reward Report'],
            ['code' => 'rewards.shop_report', 'label' => 'Rewards: Shop Report'],
            ['code' => 'rewards.shop_member_bags', 'label' => 'Rewards: Member Bags'],
            ['code' => 'rewards.bag_transaction_report', 'label' => 'Rewards: Bag Transaction Report'],
            ['code' => 'rewards.shop_setting', 'label' => 'Rewards: Shop Setting'],
            ['code' => 'gacha.manage', 'label' => 'Gachapon settings and reports'],
            ['code' => 'ops.admin', 'label' => 'System status and worker tools'],
            ['code' => 'ops.backfill', 'label' => 'Backfill and danger resets'],
            ['code' => 'logs.view', 'label' => 'Staff/access logs'],
            ['code' => 'permission.manage', 'label' => 'Permission settings'],
        ];
    }

    private function normalizeTiers(array $tiers): array
    {
        $allowedPermissions = array_fill_keys(array_map(static fn (array $entry): string => (string) $entry['code'], $this->permissionCatalog()), true);
        $out = [];
        foreach ($tiers as $tier) {
            if (!is_array($tier)) {
                continue;
            }
            $id = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) ($tier['id'] ?? ''))) ?: ('tier_' . count($out));
            $name = trim((string) ($tier['name'] ?? $id)) ?: $id;
            $permissions = array_values(array_unique(array_filter(array_map('strval', is_array($tier['permissions'] ?? null) ? $tier['permissions'] : []), static fn (string $permission): bool => isset($allowedPermissions[$permission]))));
            $userIds = $this->normalizeUserIds($tier['userIds'] ?? []);
            if ($id === 'owner' && !in_array('*', $permissions, true)) {
                $permissions[] = '*';
            }
            $out[] = ['id' => $id, 'name' => $name, 'permissions' => $permissions, 'userIds' => $userIds];
        }
        return $out ?: $this->defaultTiers();
    }

    private function decorateTiers(array $tiers): array
    {
        $userIds = [];
        foreach ($tiers as $tier) {
            foreach ((array) ($tier['userIds'] ?? []) as $userId) {
                $userIds[] = (string) $userId;
            }
        }
        $profiles = $this->memberProfiles(array_values(array_unique($userIds)));

        return array_map(static function (array $tier) use ($profiles): array {
            $tier['users'] = array_values(array_filter(array_map(static function (string $userId) use ($profiles): ?array {
                return $profiles[$userId] ?? ['userId' => $userId, 'displayName' => $userId, 'userName' => '', 'avatarUrl' => ''];
            }, (array) ($tier['userIds'] ?? []))));
            return $tier;
        }, $tiers);
    }

    private function normalizeUserIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value) ?: [];
        }
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $id = preg_replace('/[^0-9]/', '', (string) $item);
            if ($id !== '' && !in_array($id, $out, true)) {
                $out[] = $id;
            }
        }
        return $out;
    }

    private function assertUniqueUsers(array $tiers): void
    {
        $seen = [];
        foreach ($tiers as $tier) {
            $tierId = (string) ($tier['id'] ?? '');
            foreach ((array) ($tier['userIds'] ?? []) as $userId) {
                if (isset($seen[$userId])) {
                    throw new InvalidArgumentException('ผู้ใช้ ' . $userId . ' อยู่ซ้ำใน group ' . $seen[$userId] . ' และ ' . $tierId);
                }
                $seen[$userId] = $tierId;
            }
        }
    }

    /** @return array<string, array<string, string>> */
    public function memberProfiles(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map(static fn (mixed $value): string => preg_replace('/[^0-9]/', '', (string) $value) ?? '', $userIds))));
        if ($userIds === []) {
            return [];
        }

        $params = ['guildId' => (string) Bootstrap::config('discord.guildId', '')];
        $placeholders = $this->bindList($params, 'profileUserId', $userIds);
        $rows = Database::fetchAll(
            'SELECT
                m.userId,
                COALESCE(m.nickName, u.globalName, u.userName, m.userId) AS displayName,
                COALESCE(u.userName, "") AS userName,
                u.avatarHash
             FROM tbl_member m
             LEFT JOIN tbl_user u ON u.userId = m.userId
             WHERE m.guildId = :guildId
               AND m.userId IN (' . implode(',', $placeholders) . ')',
            $params
        );

        $out = [];
        foreach ($rows as $row) {
            $userId = (string) ($row['userId'] ?? '');
            if ($userId === '') {
                continue;
            }
            $out[$userId] = [
                'userId' => $userId,
                'displayName' => (string) ($row['displayName'] ?? $userId),
                'userName' => (string) ($row['userName'] ?? ''),
                'avatarUrl' => DiscordAssets::avatar($userId, $row['avatarHash'] ?? null, 64),
            ];
        }

        return $out;
    }

    private function applyTiersToAdminUsers(array $tiers): void
    {
        $memberProfiles = $this->memberProfiles(array_merge(...array_map(static fn (array $tier): array => array_values((array) ($tier['userIds'] ?? [])), $tiers)));
        $tierNames = [];
        $activeUserIds = [];
        foreach ($tiers as $tier) {
            $roleName = (string) ($tier['name'] ?? $tier['id'] ?? 'Viewer');
            $tierNames[] = $roleName;
            $permissionsJson = json_encode(array_values($tier['permissions'] ?? []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            foreach ($tier['userIds'] ?? [] as $discordUserId) {
                $discordUserId = (string) $discordUserId;
                $activeUserIds[] = $discordUserId;
                $profile = $memberProfiles[$discordUserId] ?? null;
                Database::execute(
                    'INSERT INTO tbl_admin_user (discordUserId, discordUserName, displayName, roleName, permissionsJson, isActive, updateDate)
                     VALUES (:discordUserId, :discordUserName, :displayName, :roleName, :permissionsJson, 1, :updateDate)
                     ON DUPLICATE KEY UPDATE roleName = VALUES(roleName), permissionsJson = VALUES(permissionsJson), isActive = 1, updateDate = VALUES(updateDate)',
                    [
                        'discordUserId' => $discordUserId,
                        'discordUserName' => (string) ($profile['userName'] ?? $discordUserId),
                        'displayName' => (string) ($profile['displayName'] ?? $discordUserId),
                        'roleName' => $roleName,
                        'permissionsJson' => $permissionsJson,
                        'updateDate' => date('Y-m-d H:i:s'),
                    ]
                );
            }
        }

        $tierNames = array_values(array_unique(array_filter($tierNames, static fn (string $name): bool => $name !== 'Owner')));
        if (!$tierNames) {
            return;
        }
        $params = [];
        $rolePlaceholders = $this->bindList($params, 'roleName', $tierNames);
        $where = 'roleName IN (' . implode(',', $rolePlaceholders) . ')';
        if ($activeUserIds) {
            $userPlaceholders = $this->bindList($params, 'activeUserId', array_values(array_unique($activeUserIds)));
            $where .= ' AND discordUserId NOT IN (' . implode(',', $userPlaceholders) . ')';
        }
        Database::execute('UPDATE tbl_admin_user SET isActive = 0, updateDate = :updateDate WHERE ' . $where, $params + ['updateDate' => date('Y-m-d H:i:s')]);
    }

    private function bindList(array &$params, string $prefix, array $values): array
    {
        $placeholders = [];
        foreach (array_values($values) as $index => $value) {
            $key = $prefix . $index;
            $params[$key] = $value;
            $placeholders[] = ':' . $key;
        }
        return $placeholders;
    }
}
