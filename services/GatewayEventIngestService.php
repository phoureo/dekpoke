<?php

declare(strict_types=1);

final class GatewayEventIngestService
{
    /** @return array<int, string> */
    public static function officialEventTypes(): array
    {
        return [
            'APPLICATION_COMMAND_PERMISSIONS_UPDATE',
            'AUTO_MODERATION_ACTION_EXECUTION',
            'AUTO_MODERATION_RULE_CREATE',
            'AUTO_MODERATION_RULE_DELETE',
            'AUTO_MODERATION_RULE_UPDATE',
            'CHANNEL_CREATE',
            'CHANNEL_DELETE',
            'CHANNEL_PINS_UPDATE',
            'CHANNEL_UPDATE',
            'ENTITLEMENT_CREATE',
            'ENTITLEMENT_DELETE',
            'ENTITLEMENT_UPDATE',
            'GUILD_AUDIT_LOG_ENTRY_CREATE',
            'GUILD_BAN_ADD',
            'GUILD_BAN_REMOVE',
            'GUILD_CREATE',
            'GUILD_DELETE',
            'GUILD_EMOJIS_UPDATE',
            'GUILD_INTEGRATIONS_UPDATE',
            'GUILD_MEMBER_ADD',
            'GUILD_MEMBER_REMOVE',
            'GUILD_MEMBER_UPDATE',
            'GUILD_MEMBERS_CHUNK',
            'GUILD_ROLE_CREATE',
            'GUILD_ROLE_DELETE',
            'GUILD_ROLE_UPDATE',
            'GUILD_SCHEDULED_EVENT_CREATE',
            'GUILD_SCHEDULED_EVENT_DELETE',
            'GUILD_SCHEDULED_EVENT_UPDATE',
            'GUILD_SCHEDULED_EVENT_USER_ADD',
            'GUILD_SCHEDULED_EVENT_USER_REMOVE',
            'GUILD_SOUNDBOARD_SOUND_CREATE',
            'GUILD_SOUNDBOARD_SOUND_DELETE',
            'GUILD_SOUNDBOARD_SOUND_UPDATE',
            'GUILD_SOUNDBOARD_SOUNDS_UPDATE',
            'GUILD_STICKERS_UPDATE',
            'GUILD_UPDATE',
            'INTEGRATION_CREATE',
            'INTEGRATION_DELETE',
            'INTEGRATION_UPDATE',
            'INTERACTION_CREATE',
            'INVITE_CREATE',
            'INVITE_DELETE',
            'MESSAGE_CREATE',
            'MESSAGE_DELETE',
            'MESSAGE_DELETE_BULK',
            'MESSAGE_POLL_VOTE_ADD',
            'MESSAGE_POLL_VOTE_REMOVE',
            'MESSAGE_REACTION_ADD',
            'MESSAGE_REACTION_REMOVE',
            'MESSAGE_REACTION_REMOVE_ALL',
            'MESSAGE_REACTION_REMOVE_EMOJI',
            'MESSAGE_UPDATE',
            'PRESENCE_UPDATE',
            'STAGE_INSTANCE_CREATE',
            'STAGE_INSTANCE_DELETE',
            'STAGE_INSTANCE_UPDATE',
            'THREAD_CREATE',
            'THREAD_DELETE',
            'THREAD_LIST_SYNC',
            'THREAD_MEMBER_UPDATE',
            'THREAD_MEMBERS_UPDATE',
            'THREAD_UPDATE',
            'TYPING_START',
            'VOICE_CHANNEL_STATUS_UPDATE',
            'VOICE_SERVER_UPDATE',
            'VOICE_STATE_UPDATE',
            'WEBHOOKS_UPDATE',
        ];
    }

    public static function isOfficialEventType(string $eventType): bool
    {
        return in_array(strtoupper($eventType), self::officialEventTypes(), true);
    }

    public function ingest(string $eventType, array $payload, ?int $sequence = null, string $source = 'gateway', array $context = []): int
    {
        $eventType = strtoupper(trim($eventType));
        if (!self::isOfficialEventType($eventType)) {
            throw new InvalidArgumentException('Non-official gateway event type rejected: ' . $eventType);
        }

        $receivedAt = $this->eventDate($payload) ?? date('Y-m-d H:i:s');
        $guildId = (string) ($payload['guild_id'] ?? Bootstrap::config('discord.guildId', ''));
        $sourceKey = $context['sourceKey'] ?? $this->sourceKey($eventType, $payload, $source, $sequence);
        $rawEventId = $this->upsertRawEvent($eventType, $payload, $sequence, $source, $context, $guildId, $receivedAt, $sourceKey);

        try {
            $this->transform($eventType, $payload, $rawEventId, $receivedAt, $source, $context);
            Database::execute(
                'UPDATE tbl_raw_event SET processStatus = "success", processDate = :processDate, errorMessage = NULL WHERE rawEventId = :rawEventId',
                ['rawEventId' => $rawEventId, 'processDate' => date('Y-m-d H:i:s')]
            );
        } catch (Throwable $exception) {
            Database::execute(
                'UPDATE tbl_raw_event SET processStatus = "failed", errorMessage = :errorMessage, processDate = :processDate WHERE rawEventId = :rawEventId',
                ['rawEventId' => $rawEventId, 'errorMessage' => $exception->getMessage(), 'processDate' => date('Y-m-d H:i:s')]
            );
            Database::insert('tbl_ingest_error', [
                'rawEventId' => $rawEventId,
                'errorType' => $eventType,
                'errorMessage' => $exception->getMessage(),
                'contextJson' => json_encode(['payload' => $payload, 'context' => $context], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
        }

        return $rawEventId;
    }

    public function transform(string $eventType, array $payload, ?int $rawEventId = null, ?string $eventDate = null, string $source = 'gateway', array $context = []): void
    {
        $guildId = (string) ($payload['guild_id'] ?? Bootstrap::config('discord.guildId', ''));
        $eventDate ??= date('Y-m-d H:i:s');
        $sync = new DiscordSyncService();
        $archive = new MessageArchiveService();

        match ($eventType) {
            'GUILD_CREATE' => $this->guildCreate($sync, $payload, $guildId),
            'GUILD_UPDATE' => $sync->upsertGuild($payload + ['id' => $guildId]),
            'GUILD_MEMBER_ADD', 'GUILD_MEMBER_UPDATE' => $this->memberUpsert($sync, $guildId, $payload),
            'GUILD_MEMBER_REMOVE' => $this->memberRemove($payload, $eventDate),
            'GUILD_ROLE_CREATE', 'GUILD_ROLE_UPDATE' => $sync->upsertRole($guildId, is_array($payload['role'] ?? null) ? $payload['role'] : $payload),
            'GUILD_ROLE_DELETE' => $this->roleDelete($guildId, (string) ($payload['role_id'] ?? $payload['id'] ?? ''), $eventDate),
            'CHANNEL_CREATE', 'CHANNEL_UPDATE', 'THREAD_CREATE', 'THREAD_UPDATE' => $sync->upsertChannel($guildId, $payload),
            'CHANNEL_DELETE', 'THREAD_DELETE' => $this->channelDelete((string) ($payload['id'] ?? ''), $eventDate),
            'MESSAGE_CREATE', 'MESSAGE_UPDATE' => $archive->upsertFromDiscord($payload, $source !== 'gateway'),
            'MESSAGE_DELETE' => $archive->markDeleted($guildId, (string) ($payload['channel_id'] ?? ''), (string) ($payload['id'] ?? ''), $payload, false),
            'MESSAGE_DELETE_BULK' => $this->bulkDelete($archive, $payload),
            'MESSAGE_REACTION_ADD', 'MESSAGE_REACTION_REMOVE' => $archive->reaction($eventType, $payload),
            'VOICE_STATE_UPDATE' => $this->voiceState($sync, $payload, $eventDate, $source, $context),
            default => null,
        };
    }

    private function upsertRawEvent(string $eventType, array $payload, ?int $sequence, string $source, array $context, string $guildId, string $eventDate, string $sourceKey): int
    {
        $data = [
            'guildId' => $guildId !== '' ? $guildId : null,
            'eventType' => $eventType,
            'eventSequence' => $sequence,
            'eventPayloadJson' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'channelId' => $this->channelId($eventType, $payload),
            'userId' => $this->userId($payload),
            'targetType' => $this->targetType($eventType),
            'targetId' => $this->targetId($eventType, $payload),
            'sourceKey' => $sourceKey,
            'sourceName' => $source,
            'contextJson' => $context === [] ? null : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'eventDate' => $eventDate,
            'createDate' => date('Y-m-d H:i:s'),
        ];

        Database::execute(
            'INSERT INTO tbl_raw_event
                (guildId, eventType, eventSequence, eventPayloadJson, channelId, userId, targetType, targetId, sourceKey, sourceName, contextJson, eventDate, createDate)
             VALUES
                (:guildId, :eventType, :eventSequence, :eventPayloadJson, :channelId, :userId, :targetType, :targetId, :sourceKey, :sourceName, :contextJson, :eventDate, :createDate)
             ON DUPLICATE KEY UPDATE
                guildId = VALUES(guildId),
                eventType = VALUES(eventType),
                eventSequence = VALUES(eventSequence),
                eventPayloadJson = VALUES(eventPayloadJson),
                channelId = VALUES(channelId),
                userId = VALUES(userId),
                targetType = VALUES(targetType),
                targetId = VALUES(targetId),
                sourceName = VALUES(sourceName),
                contextJson = VALUES(contextJson),
                eventDate = VALUES(eventDate)',
            $data
        );

        $row = Database::fetch('SELECT rawEventId FROM tbl_raw_event WHERE sourceKey = :sourceKey', ['sourceKey' => $sourceKey]);
        return (int) ($row['rawEventId'] ?? Database::pdo()->lastInsertId());
    }

    private function guildCreate(DiscordSyncService $sync, array $payload, string $guildId): void
    {
        $sync->upsertGuild($payload + ['id' => $guildId]);
        foreach (is_array($payload['roles'] ?? null) ? $payload['roles'] : [] as $role) {
            if (is_array($role)) {
                $sync->upsertRole($guildId, $role);
            }
        }
        foreach (is_array($payload['channels'] ?? null) ? $payload['channels'] : [] as $channel) {
            if (is_array($channel)) {
                $sync->upsertChannel($guildId, $channel);
            }
        }
        foreach (is_array($payload['members'] ?? null) ? $payload['members'] : [] as $member) {
            if (is_array($member)) {
                $sync->upsertMember($guildId, $member);
            }
        }
    }

    private function bulkDelete(MessageArchiveService $archive, array $payload): void
    {
        $guildId = (string) ($payload['guild_id'] ?? Bootstrap::config('discord.guildId', ''));
        foreach (is_array($payload['ids'] ?? null) ? $payload['ids'] : [] as $messageId) {
            $archive->markDeleted($guildId, (string) ($payload['channel_id'] ?? ''), (string) $messageId, $payload, true);
        }
    }

    private function memberRemove(array $payload, string $eventDate): void
    {
        $guildId = (string) ($payload['guild_id'] ?? Bootstrap::config('discord.guildId', ''));
        $user = is_array($payload['user'] ?? null) ? $payload['user'] : [];
        $userId = (string) ($user['id'] ?? $payload['user_id'] ?? '');
        if ($user !== []) {
            (new DiscordSyncService())->upsertUser($user, $eventDate);
        }
        if ($guildId === '' || $userId === '') {
            return;
        }
        Database::execute(
            'UPDATE tbl_member SET isActive = 0, isDelete = 1, deleteDate = :deleteDate, updateDate = :updateDate WHERE guildId = :guildId AND userId = :userId',
            ['guildId' => $guildId, 'userId' => $userId, 'deleteDate' => $eventDate, 'updateDate' => $eventDate]
        );
    }

    private function memberUpsert(DiscordSyncService $sync, string $guildId, array $payload): void
    {
        if (!empty($payload['workspace_partial'])) {
            if (is_array($payload['user'] ?? null)) {
                $sync->upsertUser($payload['user']);
            }
            return;
        }
        $sync->upsertMember($guildId, $payload);
    }

    private function roleDelete(string $guildId, string $roleId, string $eventDate): void
    {
        if ($guildId === '' || $roleId === '') {
            return;
        }
        Database::execute(
            'UPDATE tbl_role SET deleteDate = :deleteDate, updateDate = :updateDate WHERE guildId = :guildId AND roleId = :roleId',
            ['guildId' => $guildId, 'roleId' => $roleId, 'deleteDate' => $eventDate, 'updateDate' => $eventDate]
        );
    }

    private function channelDelete(string $channelId, string $eventDate): void
    {
        if ($channelId === '') {
            return;
        }
        Database::execute(
            'UPDATE tbl_channel SET isActive = 0, deleteDate = :deleteDate, updateDate = :updateDate WHERE channelId = :channelId',
            ['channelId' => $channelId, 'deleteDate' => $eventDate, 'updateDate' => $eventDate]
        );
    }

    private function voiceState(DiscordSyncService $sync, array $payload, string $eventDate, string $source, array $context): void
    {
        $guildId = (string) ($payload['guild_id'] ?? Bootstrap::config('discord.guildId', ''));
        $userId = (string) ($payload['user_id'] ?? ($payload['member']['user']['id'] ?? ''));
        if ($guildId === '' || $userId === '') {
            return;
        }

        if (is_array($payload['member'] ?? null)) {
            $sync->upsertMember($guildId, $payload['member']);
        } elseif (is_array($payload['user'] ?? null)) {
            $sync->upsertUser($payload['user']);
        }

        $channelId = isset($payload['channel_id']) && $payload['channel_id'] !== null ? (string) $payload['channel_id'] : null;
        $previousChannelId = isset($payload['old_channel_id']) && $payload['old_channel_id'] !== null ? (string) $payload['old_channel_id'] : null;
        $isFlagsOnly = $source === 'bot_log_voice_flags';

        $open = Database::fetch(
            'SELECT * FROM tbl_voice_session WHERE guildId = :guildId AND userId = :userId AND isClosed = 0 ORDER BY voiceSessionId DESC LIMIT 1',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        if ($isFlagsOnly) {
            if (!$open) {
                return;
            }
            $previousMetadata = json_decode((string) ($open['metadataJson'] ?? '{}'), true);
            Database::execute(
                'UPDATE tbl_voice_session
                 SET isStreaming = :isStreaming,
                     isVideo = :isVideo,
                     metadataJson = :metadataJson,
                     updateDate = :updateDate
                 WHERE voiceSessionId = :voiceSessionId',
                [
                    'voiceSessionId' => (int) ($open['voiceSessionId'] ?? 0),
                    'isStreaming' => array_key_exists('self_stream', $payload) ? (!empty($payload['self_stream']) ? 1 : 0) : (int) ($open['isStreaming'] ?? 0),
                    'isVideo' => array_key_exists('self_video', $payload) ? (!empty($payload['self_video']) ? 1 : 0) : (int) ($open['isVideo'] ?? 0),
                    'metadataJson' => json_encode([
                        'source' => $source,
                        'context' => $context,
                        'policy' => 'voice_flags_enrich_only_no_session_transition',
                        'previous' => is_array($previousMetadata) ? $previousMetadata : [],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'updateDate' => $eventDate,
                ]
            );
            return;
        }

        if ($channelId === null) {
            $this->closeVoiceSession($guildId, $userId, $eventDate, $context);
            return;
        }

        if ($open && (string) $open['channelId'] !== $channelId) {
            $this->closeVoiceSession($guildId, $userId, $eventDate, $context + ['previousChannelId' => $previousChannelId]);
            $open = null;
        }

        if (!$open) {
            Database::insert('tbl_voice_session', [
                'guildId' => $guildId,
                'userId' => $userId,
                'channelId' => $channelId,
                'startDate' => $eventDate,
                'isMuted' => !empty($payload['self_mute']) || !empty($payload['mute']) ? 1 : 0,
                'isDeafened' => !empty($payload['self_deaf']) || !empty($payload['deaf']) ? 1 : 0,
                'isStreaming' => !empty($payload['self_stream']) ? 1 : 0,
                'isVideo' => !empty($payload['self_video']) ? 1 : 0,
                'isClosed' => 0,
                'metadataJson' => json_encode(['source' => $source, 'context' => $context], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updateDate' => $eventDate,
            ]);
            return;
        }

        Database::execute(
            'UPDATE tbl_voice_session
             SET isMuted = :isMuted,
                 isDeafened = :isDeafened,
                 isStreaming = :isStreaming,
                 isVideo = :isVideo,
                 metadataJson = :metadataJson,
                 updateDate = :updateDate
             WHERE voiceSessionId = :voiceSessionId',
            [
                'voiceSessionId' => (int) ($open['voiceSessionId'] ?? 0),
                'isMuted' => !empty($payload['self_mute']) || !empty($payload['mute']) ? 1 : (int) ($open['isMuted'] ?? 0),
                'isDeafened' => !empty($payload['self_deaf']) || !empty($payload['deaf']) ? 1 : (int) ($open['isDeafened'] ?? 0),
                'isStreaming' => array_key_exists('self_stream', $payload) ? (!empty($payload['self_stream']) ? 1 : 0) : (int) ($open['isStreaming'] ?? 0),
                'isVideo' => array_key_exists('self_video', $payload) ? (!empty($payload['self_video']) ? 1 : 0) : (int) ($open['isVideo'] ?? 0),
                'metadataJson' => json_encode(['source' => $source, 'context' => $context, 'previous' => json_decode((string) ($open['metadataJson'] ?? '{}'), true) ?: []], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updateDate' => $eventDate,
            ]
        );
    }

    private function closeVoiceSession(string $guildId, string $userId, string $eventDate, array $context = []): void
    {
        Database::execute(
            'UPDATE tbl_voice_session
             SET endDate = :endDate,
                 durationSeconds = GREATEST(0, TIMESTAMPDIFF(SECOND, startDate, :durationEndDate)),
                 isClosed = 1,
                 metadataJson = :metadataJson,
                 updateDate = :updateDate
             WHERE guildId = :guildId AND userId = :userId AND isClosed = 0',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'endDate' => $eventDate,
                'durationEndDate' => $eventDate,
                'metadataJson' => json_encode(['closeContext' => $context], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updateDate' => $eventDate,
            ]
        );
    }

    private function sourceKey(string $eventType, array $payload, string $source, ?int $sequence = null): string
    {
        $id = (string) ($payload['id'] ?? $payload['message_id'] ?? $payload['role_id'] ?? $payload['code'] ?? '');
        $userId = (string) ($payload['user_id'] ?? ($payload['user']['id'] ?? ''));
        $channelId = (string) ($payload['channel_id'] ?? '');
        $time = $this->eventDate($payload) ?? date('Y-m-d H:i:s');
        $fallback = $sequence !== null ? 'seq:' . $sequence : $userId . ':' . $channelId . ':' . $time;
        return substr($source . ':' . $eventType . ':' . ($id ?: $fallback), 0, 190);
    }

    private function eventDate(array $payload): ?string
    {
        return DiscordSyncService::dt($payload['timestamp'] ?? $payload['edited_timestamp'] ?? null)
            ?? (!empty($payload['id']) ? DiscordSyncService::snowflakeDate((string) $payload['id']) : null);
    }

    private function channelId(string $eventType, array $payload): ?string
    {
        if (isset($payload['channel_id']) && $payload['channel_id'] !== null) {
            return (string) $payload['channel_id'];
        }
        if (str_starts_with($eventType, 'CHANNEL_') || str_starts_with($eventType, 'THREAD_')) {
            return (string) ($payload['id'] ?? '');
        }
        return null;
    }

    private function userId(array $payload): ?string
    {
        $userId = $payload['user_id'] ?? $payload['author']['id'] ?? $payload['member']['user']['id'] ?? $payload['user']['id'] ?? null;
        return $userId === null || $userId === '' ? null : (string) $userId;
    }

    private function targetType(string $eventType): ?string
    {
        if (str_starts_with($eventType, 'MESSAGE_')) {
            return 'message';
        }
        if (str_starts_with($eventType, 'GUILD_MEMBER_') || str_starts_with($eventType, 'GUILD_BAN_') || $eventType === 'VOICE_STATE_UPDATE') {
            return 'user';
        }
        if (str_starts_with($eventType, 'GUILD_ROLE_')) {
            return 'role';
        }
        if (str_starts_with($eventType, 'CHANNEL_') || str_starts_with($eventType, 'THREAD_')) {
            return 'channel';
        }
        if (str_starts_with($eventType, 'INVITE_')) {
            return 'invite';
        }
        return null;
    }

    private function targetId(string $eventType, array $payload): ?string
    {
        if (str_starts_with($eventType, 'MESSAGE_')) {
            return (string) ($payload['id'] ?? $payload['message_id'] ?? '');
        }
        if (str_starts_with($eventType, 'GUILD_MEMBER_') || str_starts_with($eventType, 'GUILD_BAN_') || $eventType === 'VOICE_STATE_UPDATE') {
            return (string) ($payload['user_id'] ?? ($payload['user']['id'] ?? ($payload['member']['user']['id'] ?? '')));
        }
        if (str_starts_with($eventType, 'GUILD_ROLE_')) {
            return (string) ($payload['role_id'] ?? ($payload['role']['id'] ?? ($payload['id'] ?? '')));
        }
        if (str_starts_with($eventType, 'CHANNEL_') || str_starts_with($eventType, 'THREAD_')) {
            return (string) ($payload['id'] ?? $payload['channel_id'] ?? '');
        }
        if (str_starts_with($eventType, 'INVITE_')) {
            return (string) ($payload['code'] ?? '');
        }
        return null;
    }
}
