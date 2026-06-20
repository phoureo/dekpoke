<?php

declare(strict_types=1);

final class ActivityMessageIngestService
{
    public const ACTION_LOG_CHANNEL_ID = '1482664581607194745';
    public const INVITE_LOG_CHANNEL_ID = '1481271035906097164';
    public const VOICE_OCCUPANCY_CHANNEL_ID = '1481581538356498625';
    public const VOICE_FLAGS_CHANNEL_ID = '1516184891136671816';

    public const WATCHED_CHANNEL_IDS = [
        self::ACTION_LOG_CHANNEL_ID,
        self::INVITE_LOG_CHANNEL_ID,
        self::VOICE_OCCUPANCY_CHANNEL_ID,
        self::VOICE_FLAGS_CHANNEL_ID,
    ];

    public function ingestDiscordMessage(array $message, bool $isBackfilled = false): array
    {
        $channelId = (string) ($message['channel_id'] ?? '');
        $messageId = (string) ($message['id'] ?? '');
        if ($messageId === '' || !in_array($channelId, self::WATCHED_CHANNEL_IDS, true)) {
            return ['skipped' => true, 'reason' => 'unwatched_channel'];
        }
        if ($this->alreadyProcessed($channelId, $messageId)) {
            return ['skipped' => true, 'reason' => 'duplicate'];
        }

        return match ($channelId) {
            self::ACTION_LOG_CHANNEL_ID => $this->ingestActionLog($message, $isBackfilled),
            self::INVITE_LOG_CHANNEL_ID => $this->ingestInviteLog($message, $isBackfilled),
            self::VOICE_OCCUPANCY_CHANNEL_ID => $this->ingestVoiceOccupancyLog($message, $isBackfilled),
            self::VOICE_FLAGS_CHANNEL_ID => $this->ingestVoiceFlagsLog($message, $isBackfilled),
            default => ['skipped' => true, 'reason' => 'unwatched_channel'],
        };
    }

    public function backfillArchivedMessages(?string $channelId = null, int $limit = 0): array
    {
        return $this->rebuildArchivedMessages($channelId, $limit);
    }

    public function rebuildArchivedMessages(?string $channelId = null, int $limit = 0): array
    {
        $channelIds = $channelId && in_array($channelId, self::WATCHED_CHANNEL_IDS, true)
            ? [$channelId]
            : self::WATCHED_CHANNEL_IDS;
        $rows = $this->archivedMessages($channelIds, $limit);

        $totals = [
            'seen' => count($rows),
            'processed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'byEventType' => [],
            'byChannel' => [],
        ];

        foreach ($rows as $row) {
            $channel = (string) $row['channelId'];
            $totals['byChannel'][$channel] ??= ['seen' => 0, 'processed' => 0, 'skipped' => 0, 'failed' => 0];
            $totals['byChannel'][$channel]['seen']++;
            try {
                $result = $this->ingestDiscordMessage($this->messageFromArchiveRow($row), true);
                if (!empty($result['skipped'])) {
                    $totals['skipped']++;
                    $totals['byChannel'][$channel]['skipped']++;
                    continue;
                }
                $eventType = (string) ($result['eventType'] ?? 'UNKNOWN');
                $totals['processed']++;
                $totals['byChannel'][$channel]['processed']++;
                $totals['byEventType'][$eventType] = ($totals['byEventType'][$eventType] ?? 0) + 1;
            } catch (Throwable $exception) {
                $totals['failed']++;
                $totals['byChannel'][$channel]['failed']++;
                Database::insert('tbl_ingest_error', [
                    'errorType' => 'bot_log_canonical_rebuild',
                    'errorMessage' => $exception->getMessage(),
                    'contextJson' => json_encode(['messageId' => $row['messageId'], 'channelId' => $channel], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);
            }
        }

        return [
            'type' => 'canonical_bot_logs',
            'status' => $totals['failed'] > 0 ? 'partial' : 'success',
            'source' => 'archived_messages',
            'watchedChannels' => $channelIds,
            'totals' => $totals,
        ];
    }

    private function ingestActionLog(array $message, bool $isBackfilled): array
    {
        $embed = $this->firstEmbed($message);
        $title = trim((string) ($embed['title'] ?? ''));
        $eventType = $this->eventTypeForActionTitle($title);
        if ($eventType === null) {
            return ['skipped' => true, 'reason' => 'unmapped_action_log_title', 'title' => $title];
        }

        $payload = $this->payloadForActionLog($eventType, $message, $embed);
        if ($payload === null) {
            return ['skipped' => true, 'reason' => 'missing_required_action_context', 'eventType' => $eventType, 'title' => $title];
        }

        $context = $this->baseContext($message, $embed, $isBackfilled) + [
            'policy' => 'action_log_maps_to_official_gateway_keyword_only',
            'title' => $title,
            'labels' => $this->labels((string) ($embed['description'] ?? '')),
        ];
        $sourceKey = 'botlog:' . self::ACTION_LOG_CHANNEL_ID . ':' . (string) ($message['id'] ?? '');
        $rawEventId = (new GatewayEventIngestService())->ingest($eventType, $payload, null, 'bot_log_action', $context + ['sourceKey' => $sourceKey]);
        if ($eventType === 'GUILD_MEMBER_ADD') {
            $this->reconcileInviteAttributions((string) ($payload['guild_id'] ?? ''), (string) ($payload['user']['id'] ?? $payload['user_id'] ?? ''), $rawEventId);
        }

        return ['skipped' => false, 'eventType' => $eventType];
    }

    private function ingestInviteLog(array $message, bool $isBackfilled): array
    {
        $this->ensureInviteAttributionSchema();

        $embed = $this->firstEmbed($message);
        $content = trim((string) ($message['content'] ?? ''));
        $description = trim((string) ($embed['description'] ?? ''));
        $text = trim($content . "\n" . $description);
        $messageId = (string) ($message['id'] ?? '');
        $guildId = (string) ($message['guild_id'] ?? Bootstrap::config('discord.guildId', ''));
        $eventDate = $this->eventDate($message, $embed) ?? date('Y-m-d H:i:s');
        $joinedUser = $this->extractUser($text, $embed);
        $joinedUserId = (string) ($joinedUser['id'] ?? '');
        $inviteType = $this->inviteAttributionType($text);
        $inviterName = $inviteType === 'invite' ? $this->extractInviteName($text) : null;
        $inviterUserId = $inviterName ? $this->resolveInviterUserId($guildId, $inviterName) : null;
        $inviteCount = $this->extractInviteCount($text);
        $join = $joinedUserId !== '' ? $this->matchJoinEvent($guildId, $joinedUserId, $eventDate) : null;

        $matchStatus = $joinedUserId === ''
            ? 'missing_join_user'
            : ($join ? 'matched' : 'pending_join');
        $confidence = $join ? 'nearest_join_event_2m' : 'pending';
        if ($inviteType === 'invite' && $inviterUserId === null) {
            $confidence = $join ? 'matched_unresolved_inviter' : 'pending_unresolved_inviter';
        }

        $metadata = $this->baseContext($message, $embed, $isBackfilled) + [
            'policy' => 'invite_log_attribution_only_no_custom_raw_event',
            'joinedUser' => $joinedUser,
            'invite' => [
                'type' => $inviteType,
                'inviter_name' => $inviterName,
                'inviter_user_id' => $inviterUserId,
                'invite_count' => $inviteCount,
                'raw_text' => $text,
            ],
        ];

        Database::execute(
            'INSERT INTO tbl_member_join_invite_attribution
                (guildId, joinedUserId, rawEventId, joinEventDate, sourceMessageId, sourceChannelId, sourceMessageDate, inviteType, inviterUserId, inviterName, inviteCount, matchStatus, confidence, metadataJson, updateDate)
             VALUES
                (:guildId, :joinedUserId, :rawEventId, :joinEventDate, :sourceMessageId, :sourceChannelId, :sourceMessageDate, :inviteType, :inviterUserId, :inviterName, :inviteCount, :matchStatus, :confidence, :metadataJson, :updateDate)
             ON DUPLICATE KEY UPDATE
                guildId = VALUES(guildId),
                joinedUserId = VALUES(joinedUserId),
                rawEventId = VALUES(rawEventId),
                joinEventDate = VALUES(joinEventDate),
                sourceChannelId = VALUES(sourceChannelId),
                sourceMessageDate = VALUES(sourceMessageDate),
                inviteType = VALUES(inviteType),
                inviterUserId = VALUES(inviterUserId),
                inviterName = VALUES(inviterName),
                inviteCount = VALUES(inviteCount),
                matchStatus = VALUES(matchStatus),
                confidence = VALUES(confidence),
                metadataJson = VALUES(metadataJson),
                updateDate = VALUES(updateDate)',
            [
                'guildId' => $guildId,
                'joinedUserId' => $joinedUserId !== '' ? $joinedUserId : null,
                'rawEventId' => $join ? (int) $join['rawEventId'] : null,
                'joinEventDate' => $join['eventDate'] ?? null,
                'sourceMessageId' => $messageId,
                'sourceChannelId' => self::INVITE_LOG_CHANNEL_ID,
                'sourceMessageDate' => $eventDate,
                'inviteType' => $inviteType,
                'inviterUserId' => $inviterUserId,
                'inviterName' => $inviterName,
                'inviteCount' => $inviteCount,
                'matchStatus' => $matchStatus,
                'confidence' => $confidence,
                'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );

        return ['skipped' => false, 'eventType' => 'GUILD_MEMBER_ADD', 'derived' => 'member_join_invite_attribution', 'inviteType' => $inviteType];
    }

    private function ingestVoiceOccupancyLog(array $message, bool $isBackfilled): array
    {
        $embed = $this->firstEmbed($message);
        $title = strtolower((string) ($embed['title'] ?? ''));
        $description = (string) ($embed['description'] ?? $message['content'] ?? '');
        if (!str_contains($title, 'joined') && !str_contains($title, 'left') && !str_contains($title, 'switched')) {
            return ['skipped' => true, 'reason' => 'voice_occupancy_context_without_member_transition'];
        }

        $user = $this->extractUser($description, $embed);
        $channelId = $this->extractLabeledChannelId($description, 'Channel') ?? $this->firstChannelMention($description);
        $previousChannelId = $this->extractLabeledChannelId($description, 'Previous channel');
        if (($user['id'] ?? '') === '' || ($channelId === null && !str_contains($title, 'left'))) {
            return ['skipped' => true, 'reason' => 'voice_occupancy_missing_user_or_channel'];
        }

        $current = $this->extractOccupancy($description, 'Users');
        $previous = $this->extractOccupancy($description, 'Previous users');
        $payload = [
            'guild_id' => (string) ($message['guild_id'] ?? Bootstrap::config('discord.guildId', '')),
            'user_id' => (string) $user['id'],
            'channel_id' => str_contains($title, 'left') ? null : $channelId,
            'old_channel_id' => str_contains($title, 'switched') || str_contains($title, 'left') ? ($previousChannelId ?? $channelId) : null,
            'member' => ['user' => $user],
            'timestamp' => $this->eventDate($message, $embed),
            'workspace_context' => [
                'sourceChannelId' => self::VOICE_OCCUPANCY_CHANNEL_ID,
                'users_count' => $current['users'],
                'user_limit' => $current['limit'],
                'previous_users_count' => $previous['users'],
                'previous_user_limit' => $previous['limit'],
            ],
        ];

        $context = $this->baseContext($message, $embed, $isBackfilled) + [
            'policy' => 'voice_occupancy_context_only',
            'action' => str_contains($title, 'switched') ? 'switched' : (str_contains($title, 'left') ? 'left' : 'joined'),
            'users_count' => $current['users'],
            'user_limit' => $current['limit'],
            'previous_users_count' => $previous['users'],
            'previous_user_limit' => $previous['limit'],
        ];
        $sourceKey = 'botlog:' . self::VOICE_OCCUPANCY_CHANNEL_ID . ':' . (string) ($message['id'] ?? '');
        (new GatewayEventIngestService())->ingest('VOICE_STATE_UPDATE', $payload, null, 'bot_log_voice_occupancy', $context + ['sourceKey' => $sourceKey]);

        return ['skipped' => false, 'eventType' => 'VOICE_STATE_UPDATE'];
    }

    private function ingestVoiceFlagsLog(array $message, bool $isBackfilled): array
    {
        $embed = $this->firstEmbed($message);
        $title = strtolower((string) ($embed['title'] ?? ''));
        $description = (string) ($embed['description'] ?? $message['content'] ?? '');
        $userId = $this->firstUserMention($description) ?? $this->footerUserId($embed);
        $channelId = $this->firstChannelMention($description);
        if ($userId === null || $channelId === null) {
            return ['skipped' => true, 'reason' => 'voice_flags_missing_user_or_channel'];
        }

        $action = null;
        $payload = [
            'guild_id' => (string) ($message['guild_id'] ?? Bootstrap::config('discord.guildId', '')),
            'user_id' => $userId,
            'channel_id' => $channelId,
            'timestamp' => $this->eventDate($message, $embed),
            'workspace_context' => [
                'sourceChannelId' => self::VOICE_FLAGS_CHANNEL_ID,
                'self_mute_context' => str_contains($title, 'selfmuted'),
                'self_deaf_context' => str_contains($title, 'selfdeafened'),
            ],
        ];

        if (str_contains($title, 'stream started')) {
            $action = 'stream_started';
            $payload['self_stream'] = true;
        } elseif (str_contains($title, 'stream ended')) {
            $action = 'stream_ended';
            $payload['self_stream'] = false;
        } elseif (str_contains($title, 'video started')) {
            $action = 'video_started';
            $payload['self_video'] = true;
        } elseif (str_contains($title, 'video stopped')) {
            $action = 'video_stopped';
            $payload['self_video'] = false;
        } elseif (str_contains($title, 'joined channel')) {
            $action = 'join_context';
        } elseif (str_contains($title, 'left channel')) {
            $action = 'leave_context';
            $payload['channel_id'] = null;
            $payload['old_channel_id'] = $channelId;
        } elseif (str_contains($title, 'changed channel') || str_contains($title, 'moved')) {
            $action = 'change_context';
            $previous = $this->firstChannelMentionBeforeText($description, ' to ');
            if ($previous !== null) {
                $payload['old_channel_id'] = $previous;
            }
        }

        if ($action === null) {
            return ['skipped' => true, 'reason' => 'voice_flags_unmapped_context'];
        }

        $context = $this->baseContext($message, $embed, $isBackfilled) + [
            'policy' => 'stream_video_context_only_mute_deaf_are_not_transitions',
            'action' => $action,
            'self_mute_context' => str_contains($title, 'selfmuted'),
            'self_deaf_context' => str_contains($title, 'selfdeafened'),
        ];
        $sourceKey = 'botlog:' . self::VOICE_FLAGS_CHANNEL_ID . ':' . (string) ($message['id'] ?? '');
        (new GatewayEventIngestService())->ingest('VOICE_STATE_UPDATE', $payload, null, 'bot_log_voice_flags', $context + ['sourceKey' => $sourceKey]);

        return ['skipped' => false, 'eventType' => 'VOICE_STATE_UPDATE'];
    }

    private function payloadForActionLog(string $eventType, array $message, array $embed): ?array
    {
        $description = (string) ($embed['description'] ?? $message['content'] ?? '');
        $labels = $this->labels($description);
        $guildId = (string) ($message['guild_id'] ?? Bootstrap::config('discord.guildId', ''));
        $timestamp = $this->eventDate($message, $embed);
        $user = $this->extractUser($description, $embed);
        $channelId = $this->extractLabeledChannelId($description, 'Channel') ?? $this->extractLabeledChannelId($description, 'Name');
        $messageId = $this->extractLabeledSnowflake($description, 'Message ID') ?? $this->extractLabeledSnowflake($description, 'Message');
        $roleId = $this->firstRoleMention($description) ?? $this->extractLabeledSnowflake($description, 'ID');
        $targetId = $this->extractLabeledSnowflake($description, 'ID');

        $base = ['guild_id' => $guildId, 'timestamp' => $timestamp, 'workspace_partial' => true];
        return match ($eventType) {
            'GUILD_MEMBER_ADD' => $user['id'] !== ''
                ? $base + ['user' => $user, 'roles' => $this->roleMentions($description), 'joined_at' => $timestamp, 'workspace_partial' => false]
                : null,
            'GUILD_MEMBER_REMOVE' => $user['id'] !== ''
                ? $base + ['user' => $user]
                : null,
            'GUILD_MEMBER_UPDATE' => $user['id'] !== ''
                ? $base + ['user' => $user, 'nick' => $labels['User nickname'] ?? $labels['Nickname'] ?? null]
                : null,
            'GUILD_BAN_ADD', 'GUILD_BAN_REMOVE' => $user['id'] !== ''
                ? $base + ['user' => $user]
                : null,
            'GUILD_ROLE_CREATE', 'GUILD_ROLE_UPDATE' => ($roleId ?? '') !== ''
                ? $base + ['role' => ['id' => $roleId, 'name' => $this->cleanLabel($labels['Name'] ?? $labels['Role'] ?? $roleId)]]
                : null,
            'GUILD_ROLE_DELETE' => ($roleId ?? '') !== ''
                ? $base + ['role_id' => $roleId]
                : null,
            'CHANNEL_CREATE', 'CHANNEL_UPDATE' => ($targetId ?? $channelId ?? '') !== ''
                ? $base + [
                    'id' => (string) ($targetId ?? $channelId),
                    'name' => $this->cleanChannelName((string) ($labels['Name'] ?? $labels['Channel'] ?? ($targetId ?? $channelId))),
                    'type' => $this->channelTypeForTitle((string) ($embed['title'] ?? '')),
                    'parent_id' => $this->extractLabeledSnowflake($description, 'Category'),
                    'user_limit' => isset($labels['User limit']) ? $this->intOrNull($labels['User limit']) : null,
                ]
                : null,
            'CHANNEL_DELETE' => ($targetId ?? $channelId ?? '') !== ''
                ? $base + ['id' => (string) ($targetId ?? $channelId)]
                : null,
            'MESSAGE_DELETE' => ($messageId ?? '') !== '' && ($channelId ?? '') !== ''
                ? $base + ['id' => $messageId, 'channel_id' => $channelId]
                : null,
            'MESSAGE_DELETE_BULK' => ($channelId ?? '') !== ''
                ? $base + ['ids' => [], 'channel_id' => $channelId]
                : null,
            'MESSAGE_UPDATE' => ($messageId ?? '') !== '' && ($channelId ?? '') !== ''
                ? $base + ['id' => $messageId, 'channel_id' => $channelId, 'edited_timestamp' => $timestamp]
                : null,
            'INVITE_CREATE', 'INVITE_DELETE' => ($this->inviteCode($description) ?? '') !== ''
                ? $base + ['code' => $this->inviteCode($description), 'channel_id' => $channelId, 'inviter' => $user['id'] !== '' ? $user : null]
                : null,
            'GUILD_UPDATE' => $base + ['id' => $guildId, 'name' => $labels['Name'] ?? null],
            'GUILD_EMOJIS_UPDATE' => $base + ['guild_id' => $guildId, 'emojis' => []],
            'WEBHOOKS_UPDATE' => ($channelId ?? '') !== ''
                ? $base + ['channel_id' => $channelId]
                : null,
            'GUILD_AUDIT_LOG_ENTRY_CREATE' => $base + [
                'id' => (string) ($message['id'] ?? ''),
                'guild_id' => $guildId,
                'target_id' => $targetId,
                'user_id' => $user['id'] !== '' ? $user['id'] : null,
                'action_type' => (string) ($embed['title'] ?? ''),
                'changes' => $labels,
            ],
            default => null,
        };
    }

    private function eventTypeForActionTitle(string $title): ?string
    {
        $value = strtolower(trim($title));
        if ($value === 'user joined') return 'GUILD_MEMBER_ADD';
        if ($value === 'user left') return 'GUILD_MEMBER_REMOVE';
        if (str_starts_with($value, 'user roles') || str_contains($value, 'nickname') || str_contains($value, 'avatar') || str_contains($value, 'timed out')) return 'GUILD_MEMBER_UPDATE';
        if ($value === 'user banned') return 'GUILD_BAN_ADD';
        if ($value === 'user unbanned') return 'GUILD_BAN_REMOVE';
        if ($value === 'role created') return 'GUILD_ROLE_CREATE';
        if ($value === 'role deleted') return 'GUILD_ROLE_DELETE';
        if (str_starts_with($value, 'role ')) return 'GUILD_ROLE_UPDATE';
        if (str_contains($value, 'channel created')) return 'CHANNEL_CREATE';
        if (str_contains($value, 'channel deleted')) return 'CHANNEL_DELETE';
        if (str_contains($value, 'channel ')) return 'CHANNEL_UPDATE';
        if ($value === 'message deleted') return 'MESSAGE_DELETE';
        if (preg_match('/^\d+\s+messages\s+deleted$/', $value)) return 'MESSAGE_DELETE_BULK';
        if ($value === 'message edited' || $value === 'message pinned') return 'MESSAGE_UPDATE';
        if ($value === 'invite created') return 'INVITE_CREATE';
        if ($value === 'invite deleted') return 'INVITE_DELETE';
        if (str_starts_with($value, 'emoji ')) return 'GUILD_EMOJIS_UPDATE';
        if (str_starts_with($value, 'webhook ')) return 'WEBHOOKS_UPDATE';
        if (str_contains($value, 'server ') || str_contains($value, 'features') || str_contains($value, 'vanity') || str_contains($value, 'boost progress')) return 'GUILD_UPDATE';
        if (str_contains($value, 'application ')) return 'GUILD_AUDIT_LOG_ENTRY_CREATE';
        return null;
    }

    private function alreadyProcessed(string $channelId, string $messageId): bool
    {
        if ($channelId === self::INVITE_LOG_CHANNEL_ID) {
            return false;
        }
        $row = Database::fetch(
            'SELECT rawEventId FROM tbl_raw_event WHERE sourceKey = :sourceKey LIMIT 1',
            ['sourceKey' => 'botlog:' . $channelId . ':' . $messageId]
        );
        return $row !== null;
    }

    public function reconcileInviteAttributions(?string $guildId = null, ?string $userId = null, ?int $rawEventId = null): array
    {
        $this->ensureInviteAttributionSchema();

        $params = [];
        $where = ['a.matchStatus <> "matched"', 'a.joinedUserId IS NOT NULL'];
        if ($guildId !== null && trim($guildId) !== '') {
            $params['guildId'] = trim($guildId);
            $where[] = 'a.guildId = :guildId';
        }
        if ($userId !== null && trim($userId) !== '') {
            $params['userId'] = trim($userId);
            $where[] = 'a.joinedUserId = :userId';
        }
        if ($rawEventId !== null && $rawEventId > 0) {
            $params['rawEventId'] = $rawEventId;
            $where[] = 'EXISTS (SELECT 1 FROM tbl_raw_event re WHERE re.rawEventId = :rawEventId AND re.guildId = a.guildId AND re.userId = a.joinedUserId)';
        }

        $rows = Database::fetchAll(
            'SELECT a.*
               FROM tbl_member_join_invite_attribution a
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY a.sourceMessageDate ASC, a.joinInviteAttributionId ASC
              LIMIT 1000',
            $params
        );

        $matched = 0;
        foreach ($rows as $row) {
            $join = $this->matchJoinEvent((string) $row['guildId'], (string) $row['joinedUserId'], (string) ($row['sourceMessageDate'] ?? ''));
            if (!$join) {
                continue;
            }
            Database::execute(
                'UPDATE tbl_member_join_invite_attribution
                    SET rawEventId = :rawEventId,
                        joinEventDate = :joinEventDate,
                        matchStatus = "matched",
                        confidence = CASE
                            WHEN inviteType = "invite" AND inviterUserId IS NULL THEN "matched_unresolved_inviter"
                            ELSE "nearest_join_event_2m"
                        END,
                        updateDate = :updateDate
                  WHERE joinInviteAttributionId = :joinInviteAttributionId',
                [
                    'rawEventId' => (int) $join['rawEventId'],
                    'joinEventDate' => (string) $join['eventDate'],
                    'updateDate' => date('Y-m-d H:i:s'),
                    'joinInviteAttributionId' => (int) $row['joinInviteAttributionId'],
                ]
            );
            $matched++;
        }

        return ['seen' => count($rows), 'matched' => $matched];
    }

    private function matchJoinEvent(string $guildId, string $userId, string $eventDate): ?array
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        $eventDate = trim($eventDate);
        if ($guildId === '' || $userId === '' || $eventDate === '') {
            return null;
        }

        $row = Database::fetch(
            'SELECT rawEventId, eventDate
               FROM tbl_raw_event
              WHERE guildId = :guildId
                AND userId = :userId
                AND eventType = "GUILD_MEMBER_ADD"
                AND eventDate BETWEEN DATE_SUB(:eventDateFrom, INTERVAL 2 MINUTE) AND DATE_ADD(:eventDateTo, INTERVAL 2 MINUTE)
              ORDER BY ABS(TIMESTAMPDIFF(SECOND, eventDate, :eventDateOrder)) ASC,
                       CASE WHEN sourceName = "gateway" THEN 0 ELSE 1 END ASC,
                       rawEventId ASC
              LIMIT 1',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'eventDateFrom' => $eventDate,
                'eventDateTo' => $eventDate,
                'eventDateOrder' => $eventDate,
            ]
        );

        return $row ?: null;
    }

    /** @param array<int, string> $channelIds */
    private function archivedMessages(array $channelIds, int $limit): array
    {
        $params = [];
        $placeholders = [];
        foreach ($channelIds as $index => $channelId) {
            $key = 'channelId' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $channelId;
        }
        $sql = 'SELECT * FROM tbl_message WHERE channelId IN (' . implode(',', $placeholders) . ') ORDER BY messageCreateDate ASC, messageId ASC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, min(20000, $limit));
        }
        return Database::fetchAll($sql, $params);
    }

    private function messageFromArchiveRow(array $row): array
    {
        $metadata = json_decode((string) ($row['metadataJson'] ?? '{}'), true);
        $metadata = is_array($metadata) ? $metadata : [];
        return $metadata + [
            'id' => (string) $row['messageId'],
            'guild_id' => (string) $row['guildId'],
            'channel_id' => (string) $row['channelId'],
            'content' => (string) ($row['contentText'] ?? ''),
            'timestamp' => $row['messageCreateDate'] ?? null,
        ];
    }

    private function firstEmbed(array $message): array
    {
        $embeds = $message['embeds'] ?? [];
        if (!is_array($embeds) && !empty($message['metadataJson'])) {
            $metadata = json_decode((string) $message['metadataJson'], true);
            $embeds = is_array($metadata['embeds'] ?? null) ? $metadata['embeds'] : [];
        }
        $embed = is_array($embeds) ? ($embeds[0] ?? []) : [];
        return is_array($embed) ? $embed : [];
    }

    private function baseContext(array $message, array $embed, bool $isBackfilled): array
    {
        return [
            'isBackfilled' => $isBackfilled,
            'sourceMessageId' => (string) ($message['id'] ?? ''),
            'sourceChannelId' => (string) ($message['channel_id'] ?? ''),
            'sourceTitle' => (string) ($embed['title'] ?? ''),
            'sourceDescription' => (string) ($embed['description'] ?? ($message['content'] ?? '')),
            'sourceFooterText' => (string) ($embed['footer']['text'] ?? ''),
        ];
    }

    private function eventDate(array $message, array $embed): ?string
    {
        return DiscordSyncService::dt($embed['timestamp'] ?? $message['timestamp'] ?? null)
            ?? DiscordSyncService::snowflakeDate((string) ($message['id'] ?? ''));
    }

    private function extractUser(string $text, array $embed): array
    {
        $userId = $this->firstUserMention($text) ?? $this->footerUserId($embed) ?? '';
        $name = $userId;
        if (preg_match('/\*\*User:\*\*\s*([^(\n]+)\s*\(<@!?' . preg_quote($userId, '/') . '>\)/u', $text, $matches)) {
            $name = trim($matches[1]);
        }
        return ['id' => $userId, 'username' => $name !== '' ? $name : $userId];
    }

    private function firstUserMention(string $text): ?string
    {
        return preg_match('/<@!?(\d{17,20})>/', $text, $matches) ? $matches[1] : null;
    }

    private function footerUserId(array $embed): ?string
    {
        $footer = (string) ($embed['footer']['text'] ?? '');
        return preg_match('/ID:\s*(\d{17,20})/', $footer, $matches) ? $matches[1] : null;
    }

    private function firstChannelMention(string $text): ?string
    {
        return preg_match('/<#(\d{17,20})>/', $text, $matches) ? $matches[1] : null;
    }

    private function firstChannelMentionBeforeText(string $text, string $needle): ?string
    {
        $pos = strpos($text, $needle);
        if ($pos === false) {
            return null;
        }
        $before = substr($text, 0, $pos);
        preg_match_all('/<#(\d{17,20})>/', $before, $matches);
        return $matches[1] ? end($matches[1]) : null;
    }

    private function extractLabeledChannelId(string $text, string $label): ?string
    {
        $value = $this->labels($text)[$label] ?? null;
        if ($value === null) {
            return null;
        }
        return $this->snowflakeFromText($value);
    }

    private function extractLabeledSnowflake(string $text, string $label): ?string
    {
        $value = $this->labels($text)[$label] ?? null;
        return $value === null ? null : $this->snowflakeFromText($value);
    }

    private function snowflakeFromText(string $text): ?string
    {
        return preg_match('/(\d{17,20})/', $text, $matches) ? $matches[1] : null;
    }

    private function firstRoleMention(string $text): ?string
    {
        return preg_match('/<@&(\d{17,20})>/', $text, $matches) ? $matches[1] : null;
    }

    /** @return array<int, string> */
    private function roleMentions(string $text): array
    {
        preg_match_all('/<@&(\d{17,20})>/', $text, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    /** @return array<string, string> */
    private function labels(string $text): array
    {
        $labels = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            if (preg_match('/^\s*>?\s*\*\*([^:*]+):\*\*\s*(.*?)\s*$/u', $line, $matches)) {
                $labels[trim($matches[1])] = trim($matches[2]);
            }
        }
        return $labels;
    }

    private function inviteCode(string $text): ?string
    {
        $labels = $this->labels($text);
        $value = $labels['Code'] ?? $labels['Codes'] ?? null;
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        return preg_match('/([A-Za-z0-9_-]{3,})/', $value, $matches) ? $matches[1] : null;
    }

    private function inviteAttributionType(string $text): string
    {
        $value = mb_strtolower($text);
        if (str_contains($value, 'vanity invite')) {
            return 'vanity';
        }
        if (str_contains($value, 'joined using oauth') || str_contains($value, 'using oauth')) {
            return 'oauth';
        }
        if (str_contains($value, 'ถูกเชิญโดย') || str_contains($value, 'joined using invite') || str_contains($value, 'invited by')) {
            return 'invite';
        }
        return 'unknown';
    }

    private function extractInviteName(string $text): ?string
    {
        if (preg_match('/ถูกเชิญโดย\**\s*:\s*(.+?)\s*(?:<:|\*ตอนนี้|ตอนนี้คุณมี|\R|$)/u', $text, $matches)) {
            return $this->cleanDiscordName($matches[1]);
        }
        if (preg_match('/invited by\s*:?\s*(.+?)\s*(?:\R|$)/iu', $text, $matches)) {
            return $this->cleanDiscordName($matches[1]);
        }
        if (preg_match('/joined using invite(?:\s+from|\s+by)?\s+(.+?)\s*(?:\R|$)/iu', $text, $matches)) {
            return $this->cleanDiscordName($matches[1]);
        }
        return null;
    }

    private function extractInviteCount(string $text): ?int
    {
        if (preg_match('/ตอนนี้คุณมี\**\s*:\s*([0-9,]+)/u', $text, $matches)) {
            return (int) str_replace(',', '', $matches[1]);
        }
        if (preg_match('/(?:invites?|uses?)\s*[:=]\s*([0-9,]+)/iu', $text, $matches)) {
            return (int) str_replace(',', '', $matches[1]);
        }
        return null;
    }

    private function resolveInviterUserId(string $guildId, string $name): ?string
    {
        $name = $this->cleanDiscordName($name);
        if ($name === '' || strtolower($name) === 'vanity invite') {
            return null;
        }

        $rows = Database::fetchAll(
            'SELECT u.userId
               FROM tbl_user u
          LEFT JOIN tbl_member m ON m.guildId = :guildId AND m.userId = u.userId
              WHERE u.userName = :nameUser
                 OR u.globalName = :nameGlobal
                 OR m.nickName = :nameNick
              ORDER BY m.isActive DESC, u.updateDate DESC
              LIMIT 2',
            [
                'guildId' => $guildId,
                'nameUser' => $name,
                'nameGlobal' => $name,
                'nameNick' => $name,
            ]
        );

        return count($rows) === 1 ? (string) $rows[0]['userId'] : null;
    }

    private function cleanDiscordName(string $name): string
    {
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = preg_replace('/<a?:[^:>]+:\d+>/', '', $name) ?? $name;
        $name = preg_replace('/<[@#!&]?\d{17,20}>/', '', $name) ?? $name;
        $name = str_replace(['`', '*', '_', '~', '\\'], '', $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        return trim($name, " \t\n\r\0\x0B:,-");
    }

    private function extractOccupancy(string $text, string $label): array
    {
        $value = $this->labels($text)[$label] ?? '';
        if (preg_match('/(\d+)\s*\/\s*(\d+|∞|inf|infinity)/iu', $value, $matches)) {
            return [
                'users' => (int) $matches[1],
                'limit' => ctype_digit($matches[2]) ? (int) $matches[2] : null,
            ];
        }
        return ['users' => null, 'limit' => null];
    }

    private function channelTypeForTitle(string $title): int
    {
        $value = strtolower($title);
        if (str_contains($value, 'category')) return 4;
        if (str_contains($value, 'voice')) return 2;
        return 0;
    }

    private function cleanLabel(string $value): string
    {
        $value = preg_replace('/<[@#&!]?\d{17,20}>/', '', $value) ?? $value;
        $value = str_replace(['`', '*', '>'], '', $value);
        return trim($value);
    }

    private function cleanChannelName(string $value): string
    {
        $value = preg_replace('/\(<#\d{17,20}>\)/', '', $value) ?? $value;
        $value = preg_replace('/<#\d{17,20}>/', '', $value) ?? $value;
        return trim(str_replace(['`', '*', '>'], '', $value));
    }

    private function intOrNull(string $value): ?int
    {
        return preg_match('/\d+/', $value, $matches) ? (int) $matches[0] : null;
    }

    private function ensureInviteAttributionSchema(): void
    {
        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_member_join_invite_attribution (
                joinInviteAttributionId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                joinedUserId varchar(32) DEFAULT NULL,
                rawEventId bigint unsigned DEFAULT NULL,
                joinEventDate datetime DEFAULT NULL,
                sourceMessageId varchar(32) NOT NULL,
                sourceChannelId varchar(32) NOT NULL,
                sourceMessageDate datetime DEFAULT NULL,
                inviteType varchar(40) NOT NULL DEFAULT "unknown",
                inviterUserId varchar(32) DEFAULT NULL,
                inviterName varchar(190) DEFAULT NULL,
                inviteCount int DEFAULT NULL,
                matchStatus varchar(40) NOT NULL DEFAULT "pending_join",
                confidence varchar(40) NOT NULL DEFAULT "pending",
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (joinInviteAttributionId),
                UNIQUE KEY uq_tbl_member_join_invite_source (sourceMessageId),
                KEY idx_tbl_member_join_invite_joined (guildId, joinedUserId, sourceMessageDate),
                KEY idx_tbl_member_join_invite_raw (rawEventId),
                KEY idx_tbl_member_join_invite_inviter (guildId, inviterUserId, sourceMessageDate),
                KEY idx_tbl_member_join_invite_status (matchStatus, inviteType)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}
