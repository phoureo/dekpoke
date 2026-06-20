<?php

declare(strict_types=1);

final class DiscordSyncService
{
    public function syncAll(): array
    {
        $guildId = $this->guildId();
        return [
            'guild' => $this->syncGuild(),
            'roles' => $this->syncRoles(),
            'channels' => $this->syncChannels(),
            'members' => $this->syncMembers(),
            'guildId' => $guildId,
        ];
    }

    public function syncGuild(): array
    {
        $guildId = $this->guildId();
        $response = $this->client()->request('GET', '/guilds/' . rawurlencode($guildId) . '?with_counts=true');
        $this->assertOk($response, 'Failed to sync guild.');
        $guild = is_array($response['body'] ?? null) ? $response['body'] : [];
        $this->upsertGuild($guild + ['id' => $guildId]);

        return ['guildId' => $guildId, 'guildName' => (string) ($guild['name'] ?? $guildId)];
    }

    public function syncRoles(): array
    {
        $guildId = $this->guildId();
        $response = $this->client()->request('GET', '/guilds/' . rawurlencode($guildId) . '/roles');
        $this->assertOk($response, 'Failed to sync roles.');

        $roleIds = [];
        foreach (is_array($response['body'] ?? null) ? $response['body'] : [] as $role) {
            if (!is_array($role) || empty($role['id'])) {
                continue;
            }
            $roleIds[] = (string) $role['id'];
            $before = Database::fetch('SELECT * FROM tbl_role WHERE roleId = :roleId', ['roleId' => (string) $role['id']]);
            $this->upsertRole($guildId, $role);
            if ($before) {
                Database::insert('tbl_role_revision', [
                    'roleId' => (string) $role['id'],
                    'guildId' => $guildId,
                    'beforeJson' => $this->json($before),
                    'afterJson' => $this->json($role),
                    'sourceType' => 'sync',
                ]);
            }
        }

        $this->markMissingInactive('tbl_role', 'roleId', $roleIds, 'guildId = :guildId', ['guildId' => $guildId]);
        LiveUpdateService::markTopic('roles', ['scope' => 'role_sync', 'synced' => count($roleIds)], 'guild', $guildId, $guildId);
        return ['synced' => count($roleIds)];
    }

    public function syncChannels(): array
    {
        $guildId = $this->guildId();
        $response = $this->client()->request('GET', '/guilds/' . rawurlencode($guildId) . '/channels');
        $this->assertOk($response, 'Failed to sync channels.');

        $channelIds = [];
        foreach (is_array($response['body'] ?? null) ? $response['body'] : [] as $channel) {
            if (!is_array($channel) || empty($channel['id'])) {
                continue;
            }
            $channelIds[] = (string) $channel['id'];
            $this->upsertChannel($guildId, $channel);
        }

        $this->markMissingInactive('tbl_channel', 'channelId', $channelIds, 'guildId = :guildId', ['guildId' => $guildId]);
        LiveUpdateService::markTopic('activity', ['scope' => 'channel_sync', 'synced' => count($channelIds)], 'guild', $guildId, $guildId);
        return ['synced' => count($channelIds)];
    }

    public function syncMembers(): array
    {
        $guildId = $this->guildId();
        $after = '0';
        $limit = (int) Bootstrap::config('discord.syncPageLimit', 1000);
        $syncDate = $this->now();
        $count = 0;

        do {
            $response = $this->client()->request('GET', '/guilds/' . rawurlencode($guildId) . '/members?limit=' . $limit . '&after=' . rawurlencode($after));
            $this->assertOk($response, 'Failed to sync members. Enable GUILD_MEMBERS intent.');
            $members = is_array($response['body'] ?? null) ? $response['body'] : [];

            foreach ($members as $member) {
                if (!is_array($member)) {
                    continue;
                }
                $this->upsertMember($guildId, $member, $syncDate);
                $count++;
            }

            $last = end($members);
            $after = is_array($last) ? (string) ($last['user']['id'] ?? $after) : $after;
        } while (count($members) === $limit);

        Database::execute(
            'UPDATE tbl_member
             SET isActive = 0, isDelete = 1, deleteDate = :deleteDate, updateDate = :updateDate
             WHERE guildId = :guildId AND (lastSyncDate IS NULL OR lastSyncDate < :syncDate) AND isActive = 1',
            ['guildId' => $guildId, 'syncDate' => $syncDate, 'deleteDate' => $syncDate, 'updateDate' => $syncDate]
        );

        LiveUpdateService::markTopic('activity', ['scope' => 'member_sync', 'synced' => $count], 'guild', $guildId, $guildId);
        return ['synced' => $count];
    }

    public function upsertGuild(array $guild): void
    {
        $guildId = (string) ($guild['id'] ?? $this->guildId());
        Database::execute(
            'INSERT INTO tbl_guild
                (guildId, guildName, iconHash, ownerUserId, approximateMemberCount, approximatePresenceCount, verificationLevel, premiumTier, isActive, metadataJson, updateDate, deleteDate)
             VALUES
                (:guildId, :guildName, :iconHash, :ownerUserId, :approximateMemberCount, :approximatePresenceCount, :verificationLevel, :premiumTier, 1, :metadataJson, :updateDate, NULL)
             ON DUPLICATE KEY UPDATE
                guildName = VALUES(guildName),
                iconHash = VALUES(iconHash),
                ownerUserId = VALUES(ownerUserId),
                approximateMemberCount = VALUES(approximateMemberCount),
                approximatePresenceCount = VALUES(approximatePresenceCount),
                verificationLevel = VALUES(verificationLevel),
                premiumTier = VALUES(premiumTier),
                isActive = 1,
                metadataJson = VALUES(metadataJson),
                updateDate = VALUES(updateDate),
                deleteDate = NULL',
            [
                'guildId' => $guildId,
                'guildName' => (string) ($guild['name'] ?? $guildId),
                'iconHash' => $guild['icon'] ?? null,
                'ownerUserId' => $guild['owner_id'] ?? null,
                'approximateMemberCount' => $guild['approximate_member_count'] ?? null,
                'approximatePresenceCount' => $guild['approximate_presence_count'] ?? null,
                'verificationLevel' => $guild['verification_level'] ?? null,
                'premiumTier' => $guild['premium_tier'] ?? null,
                'metadataJson' => $this->json($guild),
                'updateDate' => $this->now(),
            ]
        );
    }

    public function upsertRole(string $guildId, array $role): void
    {
        $roleId = trim((string) ($role['id'] ?? $role['role_id'] ?? ''));
        if ($guildId === '' || $roleId === '') {
            return;
        }

        Database::execute(
            'INSERT INTO tbl_role
                (roleId, guildId, roleName, roleColor, rolePosition, permissions, iconHash, unicodeEmoji, isManaged, isMentionable, isHoist, metadataJson, updateDate, deleteDate)
             VALUES
                (:roleId, :guildId, :roleName, :roleColor, :rolePosition, :permissions, :iconHash, :unicodeEmoji, :isManaged, :isMentionable, :isHoist, :metadataJson, :updateDate, NULL)
             ON DUPLICATE KEY UPDATE
                guildId = VALUES(guildId),
                roleName = VALUES(roleName),
                roleColor = VALUES(roleColor),
                rolePosition = VALUES(rolePosition),
                permissions = VALUES(permissions),
                iconHash = VALUES(iconHash),
                unicodeEmoji = VALUES(unicodeEmoji),
                isManaged = VALUES(isManaged),
                isMentionable = VALUES(isMentionable),
                isHoist = VALUES(isHoist),
                metadataJson = VALUES(metadataJson),
                updateDate = VALUES(updateDate),
                deleteDate = NULL',
            [
                'roleId' => $roleId,
                'guildId' => $guildId,
                'roleName' => (string) ($role['name'] ?? $role['roleName'] ?? $roleId),
                'roleColor' => $role['color'] ?? $role['roleColor'] ?? null,
                'rolePosition' => $role['position'] ?? $role['rolePosition'] ?? 0,
                'permissions' => $role['permissions'] ?? null,
                'iconHash' => $role['icon'] ?? null,
                'unicodeEmoji' => $role['unicode_emoji'] ?? null,
                'isManaged' => !empty($role['managed']) ? 1 : 0,
                'isMentionable' => !empty($role['mentionable']) ? 1 : 0,
                'isHoist' => !empty($role['hoist']) ? 1 : 0,
                'metadataJson' => $this->json($role),
                'updateDate' => $this->now(),
            ]
        );
        LiveUpdateService::markTopic('roles', ['scope' => 'role_upsert'], 'role', $roleId, $guildId);
    }

    public function upsertChannel(string $guildId, array $channel): void
    {
        $channelId = trim((string) ($channel['id'] ?? $channel['channel_id'] ?? ''));
        if ($guildId === '' || $channelId === '') {
            return;
        }

        Database::execute(
            'INSERT INTO tbl_channel
                (channelId, guildId, parentChannelId, channelName, channelType, channelPosition, topic, bitrate, userLimit, rateLimitPerUser, isNsfw, isActive, metadataJson, updateDate, deleteDate)
             VALUES
                (:channelId, :guildId, :parentChannelId, :channelName, :channelType, :channelPosition, :topic, :bitrate, :userLimit, :rateLimitPerUser, :isNsfw, 1, :metadataJson, :updateDate, NULL)
             ON DUPLICATE KEY UPDATE
                guildId = VALUES(guildId),
                parentChannelId = VALUES(parentChannelId),
                channelName = VALUES(channelName),
                channelType = VALUES(channelType),
                channelPosition = VALUES(channelPosition),
                topic = VALUES(topic),
                bitrate = VALUES(bitrate),
                userLimit = VALUES(userLimit),
                rateLimitPerUser = VALUES(rateLimitPerUser),
                isNsfw = VALUES(isNsfw),
                isActive = 1,
                metadataJson = VALUES(metadataJson),
                updateDate = VALUES(updateDate),
                deleteDate = NULL',
            [
                'channelId' => $channelId,
                'guildId' => $guildId,
                'parentChannelId' => $channel['parent_id'] ?? $channel['parentChannelId'] ?? null,
                'channelName' => (string) ($channel['name'] ?? $channel['channelName'] ?? $channelId),
                'channelType' => $channel['type'] ?? $channel['channelType'] ?? 0,
                'channelPosition' => $channel['position'] ?? $channel['channelPosition'] ?? 0,
                'topic' => $channel['topic'] ?? null,
                'bitrate' => $channel['bitrate'] ?? null,
                'userLimit' => $channel['user_limit'] ?? $channel['userLimit'] ?? null,
                'rateLimitPerUser' => $channel['rate_limit_per_user'] ?? null,
                'isNsfw' => !empty($channel['nsfw']) ? 1 : 0,
                'metadataJson' => $this->json($channel),
                'updateDate' => $this->now(),
            ]
        );

        $overwrites = is_array($channel['permission_overwrites'] ?? null) ? $channel['permission_overwrites'] : [];
        $this->syncChannelOverwrites($channelId, $overwrites);
        LiveUpdateService::markTopic('activity', ['scope' => 'channel_upsert'], 'channel', $channelId, $guildId);
    }

    public function upsertMember(string $guildId, array $member, ?string $syncDate = null): void
    {
        $syncDate ??= $this->now();
        $user = $member['user'] ?? null;
        if (!is_array($user) && !empty($member['user_id'])) {
            $user = ['id' => (string) $member['user_id'], 'username' => (string) $member['user_id']];
        }
        if (!is_array($user) || empty($user['id'])) {
            return;
        }

        $this->upsertUser($user, $syncDate);
        $userId = (string) $user['id'];
        $roles = array_values(array_filter(array_map('strval', is_array($member['roles'] ?? null) ? $member['roles'] : [])));

        Database::execute(
            'INSERT INTO tbl_member
                (guildId, userId, nickName, guildAvatarHash, joinedAt, premiumSince, communicationDisabledUntil, lastSyncDate, roleCount, isPending, isDelete, isActive, metadataJson, updateDate, deleteDate)
             VALUES
                (:guildId, :userId, :nickName, :guildAvatarHash, :joinedAt, :premiumSince, :communicationDisabledUntil, :lastSyncDate, :roleCount, :isPending, 0, 1, :metadataJson, :updateDate, NULL)
             ON DUPLICATE KEY UPDATE
                nickName = VALUES(nickName),
                guildAvatarHash = VALUES(guildAvatarHash),
                joinedAt = COALESCE(VALUES(joinedAt), joinedAt),
                premiumSince = VALUES(premiumSince),
                communicationDisabledUntil = VALUES(communicationDisabledUntil),
                lastSyncDate = VALUES(lastSyncDate),
                roleCount = VALUES(roleCount),
                isPending = VALUES(isPending),
                isDelete = 0,
                isActive = 1,
                metadataJson = VALUES(metadataJson),
                updateDate = VALUES(updateDate),
                deleteDate = NULL',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'nickName' => $member['nick'] ?? null,
                'guildAvatarHash' => $member['avatar'] ?? null,
                'joinedAt' => self::dt($member['joined_at'] ?? null),
                'premiumSince' => self::dt($member['premium_since'] ?? null),
                'communicationDisabledUntil' => self::dt($member['communication_disabled_until'] ?? null),
                'lastSyncDate' => $syncDate,
                'roleCount' => count($roles),
                'isPending' => !empty($member['pending']) ? 1 : 0,
                'metadataJson' => $this->json($member),
                'updateDate' => $syncDate,
            ]
        );

        $this->syncMemberRoles($guildId, $userId, $roles);
        LiveUpdateService::markTopic('activity', ['scope' => 'member_upsert'], 'user', $userId, $guildId);
    }

    public function upsertUser(array $user, ?string $updateDate = null): void
    {
        $userId = trim((string) ($user['id'] ?? ''));
        if ($userId === '') {
            return;
        }

        $updateDate ??= $this->now();
        Database::execute(
            'INSERT INTO tbl_user
                (userId, userName, globalName, discriminator, avatarHash, bannerHash, accentColor, isBot, metadataJson, updateDate)
             VALUES
                (:userId, :userName, :globalName, :discriminator, :avatarHash, :bannerHash, :accentColor, :isBot, :metadataJson, :updateDate)
             ON DUPLICATE KEY UPDATE
                userName = CASE
                    WHEN VALUES(userName) = VALUES(userId) AND userName <> userId THEN userName
                    ELSE VALUES(userName)
                END,
                globalName = COALESCE(VALUES(globalName), globalName),
                discriminator = COALESCE(VALUES(discriminator), discriminator),
                avatarHash = COALESCE(VALUES(avatarHash), avatarHash),
                bannerHash = COALESCE(VALUES(bannerHash), bannerHash),
                accentColor = COALESCE(VALUES(accentColor), accentColor),
                isBot = VALUES(isBot),
                metadataJson = VALUES(metadataJson),
                updateDate = VALUES(updateDate)',
            [
                'userId' => $userId,
                'userName' => (string) ($user['username'] ?? $user['global_name'] ?? $userId),
                'globalName' => $user['global_name'] ?? null,
                'discriminator' => $user['discriminator'] ?? null,
                'avatarHash' => $user['avatar'] ?? null,
                'bannerHash' => $user['banner'] ?? null,
                'accentColor' => $user['accent_color'] ?? null,
                'isBot' => !empty($user['bot']) ? 1 : 0,
                'metadataJson' => $this->json($user),
                'updateDate' => $updateDate,
            ]
        );
    }

    private function syncChannelOverwrites(string $channelId, array $overwrites): void
    {
        $normalized = [];
        foreach ($overwrites as $overwrite) {
            if (!is_array($overwrite)) {
                continue;
            }
            $overwriteId = trim((string) ($overwrite['id'] ?? ''));
            if ($overwriteId === '') {
                continue;
            }
            $overwriteType = (int) ($overwrite['type'] ?? 0);
            $normalized[$overwriteId . ':' . $overwriteType] = [
                'channelId' => $channelId,
                'overwriteId' => $overwriteId,
                'overwriteType' => $overwriteType,
                'allowPermissions' => $overwrite['allow'] ?? null,
                'denyPermissions' => $overwrite['deny'] ?? null,
                'updateDate' => $this->now(),
            ];
        }

        if ($normalized === []) {
            Database::execute('DELETE FROM tbl_channel_overwrite WHERE channelId = :channelId', ['channelId' => $channelId]);
            return;
        }

        foreach ($normalized as $row) {
            Database::execute(
                'INSERT INTO tbl_channel_overwrite
                    (channelId, overwriteId, overwriteType, allowPermissions, denyPermissions, updateDate)
                 VALUES
                    (:channelId, :overwriteId, :overwriteType, :allowPermissions, :denyPermissions, :updateDate)
                 ON DUPLICATE KEY UPDATE
                    allowPermissions = VALUES(allowPermissions),
                    denyPermissions = VALUES(denyPermissions),
                    updateDate = VALUES(updateDate)',
                $row
            );
        }
    }

    private function syncMemberRoles(string $guildId, string $userId, array $roles): void
    {
        $roles = array_values(array_unique(array_filter($roles)));
        foreach ($roles as $roleId) {
            Database::execute(
                'INSERT IGNORE INTO tbl_role (roleId, guildId, roleName, metadataJson, updateDate)
                 VALUES (:roleId, :guildId, :roleName, :metadataJson, :updateDate)',
                [
                    'roleId' => $roleId,
                    'guildId' => $guildId,
                    'roleName' => $roleId,
                    'metadataJson' => $this->json(['stub' => true, 'source' => 'member_roles']),
                    'updateDate' => $this->now(),
                ]
            );
            Database::execute(
                'INSERT INTO tbl_member_role (guildId, userId, roleId, isActive, deleteDate)
                 VALUES (:guildId, :userId, :roleId, 1, NULL)
                 ON DUPLICATE KEY UPDATE isActive = 1, deleteDate = NULL',
                ['guildId' => $guildId, 'userId' => $userId, 'roleId' => $roleId]
            );
        }

        $params = ['guildId' => $guildId, 'userId' => $userId, 'deleteDate' => $this->now()];
        if ($roles === []) {
            Database::execute(
                'UPDATE tbl_member_role SET isActive = 0, deleteDate = :deleteDate WHERE guildId = :guildId AND userId = :userId AND isActive = 1',
                $params
            );
            return;
        }

        $placeholders = [];
        foreach ($roles as $index => $roleId) {
            $key = 'roleId' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $roleId;
        }
        Database::execute(
            'UPDATE tbl_member_role
             SET isActive = 0, deleteDate = :deleteDate
             WHERE guildId = :guildId AND userId = :userId AND isActive = 1 AND roleId NOT IN (' . implode(',', $placeholders) . ')',
            $params
        );
    }

    private function markMissingInactive(string $table, string $idColumn, array $ids, string $scopeSql, array $scopeParams): void
    {
        if ($ids === []) {
            return;
        }

        $placeholders = [];
        $params = array_merge($scopeParams, ['deleteDate' => $this->now()]);
        foreach (array_values($ids) as $index => $id) {
            $key = 'id' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $activeSql = $table === 'tbl_channel' ? ', isActive = 0' : '';
        Database::execute(
            'UPDATE ' . $table . ' SET deleteDate = :deleteDate' . $activeSql . ' WHERE ' . $scopeSql . ' AND ' . $idColumn . ' NOT IN (' . implode(',', $placeholders) . ')',
            $params
        );
    }

    private function assertOk(array $response, string $message): void
    {
        if (!($response['ok'] ?? false)) {
            $bodyMessage = is_array($response['body'] ?? null) ? (string) ($response['body']['message'] ?? '') : (string) ($response['body'] ?? '');
            throw new RuntimeException(trim($message . ' ' . $bodyMessage));
        }
    }

    private function guildId(): string
    {
        $guildId = (string) Bootstrap::config('discord.guildId', '');
        if ($guildId === '') {
            throw new RuntimeException('discord.guildId is not configured.');
        }
        return $guildId;
    }

    private function client(): DiscordClient
    {
        return new DiscordClient();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function json(mixed $value): ?string
    {
        return $value === null ? null : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function snowflakeDate(string $snowflake): ?string
    {
        if (!ctype_digit($snowflake)) {
            return null;
        }
        $timestamp = ((int) ((int) $snowflake / 4194304) + 1420070400000) / 1000;
        return date('Y-m-d H:i:s', (int) $timestamp);
    }

    public static function dt(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable((string) $value))
                ->setTimezone(new DateTimeZone(date_default_timezone_get()))
                ->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }
}
