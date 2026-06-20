<?php

declare(strict_types=1);

final class BackfillService
{
    private const EMPTY_PAGE_THRESHOLD = 3;

    public function catalog(): array
    {
        return [
            'all' => [
                'label' => 'Workspace Backfill',
                'scope' => 'Sync current state, archive approved bot-log channels, rebuild canonical raw events, voice sessions, and earn summaries.',
                'canRun' => true,
            ],
            'server_snapshot' => [
                'label' => 'Current State Sync',
                'scope' => 'Refresh guild, roles, channels, and members only.',
                'canRun' => true,
            ],
            'bot_log_archive' => [
                'label' => 'Approved Bot Log Archive',
                'scope' => 'Fetch 1482664581607194745, invite attribution channel 1481271035906097164, plus voice context enrich channels 1481581538356498625 and 1516184891136671816.',
                'canRun' => true,
            ],
            'canonical_bot_logs' => [
                'label' => 'Canonical Event Rebuild',
                'scope' => 'Parse archived bot-log messages into official Discord gateway event keywords only, with invite logs stored as join attribution context.',
                'canRun' => true,
            ],
            'voice_sessions' => [
                'label' => 'Voice Session Rebuild',
                'scope' => 'Rebuild tbl_voice_session from canonical VOICE_STATE_UPDATE rows.',
                'canRun' => true,
            ],
            'earn_summary' => [
                'label' => 'Earn Daily Summary',
                'scope' => 'Recalculate tbl_user_daily_summary from MESSAGE_CREATE archive and voice sessions.',
                'canRun' => true,
            ],
            'attachment_archive' => [
                'label' => 'Attachment Queue',
                'scope' => 'Download queued message attachments.',
                'canRun' => true,
            ],
        ];
    }

    public function run(string $type = 'all', array $options = []): array
    {
        return match ($type) {
            'all' => $this->runAll($options),
            'server_snapshot' => $this->runServerSnapshot(),
            'bot_log_archive', 'message_archive' => $this->runBotLogArchive($options),
            'canonical_bot_logs', 'activity_bot_logs' => $this->runCanonicalBotLogs($options),
            'voice_sessions' => $this->runVoiceSessions(),
            'earn_summary', 'earn_worker' => $this->runEarnSummary($options),
            'attachment_archive' => $this->runAttachmentArchive($options),
            default => throw new InvalidArgumentException('Unknown workspace backfill type: ' . $type),
        };
    }

    public function runAll(array $options = []): array
    {
        $order = $options['order'] ?? ['server_snapshot', 'bot_log_archive', 'canonical_bot_logs', 'voice_sessions', 'earn_summary', 'attachment_archive'];
        $result = [];
        foreach ($order as $type) {
            $baseOptions = is_array($options[$type] ?? null) ? $options[$type] : $options;
            $result[(string) $type] = $this->run((string) $type, $baseOptions);
        }

        return [
            'type' => 'all',
            'summary' => $this->summarize($result),
            'items' => $result,
        ];
    }

    public function resetCursor(?string $type = null, ?string $channelId = null): int
    {
        $guildId = (string) Bootstrap::config('discord.guildId', '');
        $params = ['guildId' => $guildId];
        $where = 'guildId = :guildId';
        if ($type) {
            $where .= ' AND cursorType = :cursorType';
            $params['cursorType'] = $type;
        }
        if ($channelId) {
            $where .= ' AND channelId = :channelId';
            $params['channelId'] = $channelId;
        }
        return Database::execute('DELETE FROM tbl_sync_cursor WHERE ' . $where, $params);
    }

    public function status(): array
    {
        $guildId = (string) Bootstrap::config('discord.guildId', '');
        $cursors = Database::fetchAll(
            'SELECT cursorType, channelId, cursorValue, metadataJson, updateDate
             FROM tbl_sync_cursor
             WHERE guildId = :guildId
             ORDER BY cursorType ASC, updateDate DESC',
            ['guildId' => $guildId]
        );

        return [
            'catalog' => $this->catalog(),
            'policy' => [
                'truth' => 'gateway_first_raw_event_store',
                'mainBackfillChannelId' => ActivityMessageIngestService::ACTION_LOG_CHANNEL_ID,
                'inviteAttributionChannelId' => ActivityMessageIngestService::INVITE_LOG_CHANNEL_ID,
                'voiceOccupancyChannelId' => ActivityMessageIngestService::VOICE_OCCUPANCY_CHANNEL_ID,
                'voiceFlagsChannelId' => ActivityMessageIngestService::VOICE_FLAGS_CHANNEL_ID,
            ],
            'cursors' => array_map(static function (array $row): array {
                $row['metadata'] = json_decode((string) ($row['metadataJson'] ?? '{}'), true) ?: [];
                unset($row['metadataJson']);
                return $row;
            }, $cursors),
        ];
    }

    private function runServerSnapshot(): array
    {
        return ['type' => 'server_snapshot', 'status' => 'success', 'result' => (new DiscordSyncService())->syncAll()];
    }

    private function runBotLogArchive(array $options): array
    {
        $guildId = (string) Bootstrap::config('discord.guildId', '');
        $channelIds = $this->botLogChannelIds($options['channelId'] ?? null);
        $pagesPerChannel = max(1, min(200, (int) ($options['pagesPerChannel'] ?? 10)));
        $maxPagesPerChannel = !empty($options['untilComplete'])
            ? max($pagesPerChannel, min(3000, (int) ($options['maxPagesPerChannel'] ?? 1000)))
            : $pagesPerChannel;
        $client = new DiscordClient();
        $archive = new MessageArchiveService();

        $totals = [
            'channels' => count($channelIds),
            'pages' => 0,
            'messagesSeen' => 0,
            'newMessages' => 0,
            'changedMessages' => 0,
            'attachmentsQueued' => 0,
            'failed' => 0,
            'rateLimited' => 0,
        ];
        $items = [];

        foreach ($channelIds as $channelId) {
            $cursor = $this->cursor('bot_log_archive', $guildId, $channelId);
            $metadata = $this->cursorMetadata($cursor);
            $before = $this->validSnowflake($cursor['cursorValue'] ?? null) ? (string) $cursor['cursorValue'] : null;
            $emptyStreak = (int) ($metadata['emptyStreak'] ?? 0);
            $item = ['channelId' => $channelId, 'pages' => 0, 'messagesSeen' => 0, 'newMessages' => 0, 'changedMessages' => 0, 'status' => 'running', 'cursor' => $before];

            for ($page = 0; $page < $maxPagesPerChannel; $page++) {
                $path = '/channels/' . rawurlencode($channelId) . '/messages?limit=100';
                if ($before) {
                    $path .= '&before=' . rawurlencode($before);
                }
                $response = $client->request('GET', $path);
                if (($response['status'] ?? 0) === 429) {
                    $totals['rateLimited']++;
                    $item['status'] = 'rate_limited';
                    $this->saveCursor('bot_log_archive', $guildId, $channelId, $before, ['emptyStreak' => $emptyStreak, 'rateLimited' => true]);
                    break;
                }
                if (!($response['ok'] ?? false)) {
                    $totals['failed']++;
                    $item['status'] = 'failed';
                    $this->recordError('bot_log_archive', $response, ['channelId' => $channelId]);
                    break;
                }

                $messages = is_array($response['body'] ?? null) ? $response['body'] : [];
                $pageNew = 0;
                $pageChanged = 0;
                $pageAttachments = 0;
                foreach ($messages as $message) {
                    if (!is_array($message)) {
                        continue;
                    }
                    $state = $archive->upsertFromDiscord($message + ['guild_id' => $guildId], true);
                    $pageNew += !empty($state['isNew']) ? 1 : 0;
                    $pageChanged += !empty($state['isChanged']) ? 1 : 0;
                    $pageAttachments += (int) ($state['attachmentCount'] ?? 0);
                }

                $item['pages']++;
                $item['messagesSeen'] += count($messages);
                $item['newMessages'] += $pageNew;
                $item['changedMessages'] += $pageChanged;
                $totals['pages']++;
                $totals['messagesSeen'] += count($messages);
                $totals['newMessages'] += $pageNew;
                $totals['changedMessages'] += $pageChanged;
                $totals['attachmentsQueued'] += $pageAttachments;

                if ($messages === []) {
                    $item['status'] = 'oldest_reached';
                    $item['cursor'] = 'done';
                    $this->saveCursor('bot_log_archive', $guildId, $channelId, 'done', ['emptyStreak' => self::EMPTY_PAGE_THRESHOLD]);
                    break;
                }

                $oldest = end($messages);
                $before = is_array($oldest) && !empty($oldest['id']) ? (string) $oldest['id'] : $before;
                $found = ($pageNew + $pageChanged + $pageAttachments) > 0;
                $emptyStreak = $found ? 0 : $emptyStreak + 1;
                $item['cursor'] = $before;
                $item['status'] = 'partial';
                $this->saveCursor('bot_log_archive', $guildId, $channelId, $before, [
                    'emptyStreak' => $emptyStreak,
                    'lastMessageCount' => count($messages),
                    'lastFound' => ['new' => $pageNew, 'changed' => $pageChanged, 'attachments' => $pageAttachments],
                ]);

                if (empty($options['untilComplete']) && $page + 1 >= $pagesPerChannel) {
                    break;
                }
            }
            $items[] = $item;
        }

        return [
            'type' => 'bot_log_archive',
            'status' => $totals['failed'] > 0 ? 'partial' : 'success',
            'approvedChannels' => $channelIds,
            'totals' => $totals,
            'items' => $items,
        ];
    }

    private function runCanonicalBotLogs(array $options): array
    {
        return (new ActivityMessageIngestService())->rebuildArchivedMessages(
            isset($options['channelId']) ? (string) $options['channelId'] : null,
            (int) ($options['limit'] ?? 0)
        );
    }

    private function runVoiceSessions(): array
    {
        $guildId = (string) Bootstrap::config('discord.guildId', '');
        return ['type' => 'voice_sessions', 'status' => 'success', 'result' => (new VoiceSessionRebuildService())->rebuildFromRawEvents($guildId)];
    }

    private function runEarnSummary(array $options): array
    {
        $summary = new EarnSummaryService();
        if (!empty($options['date'])) {
            $result = $summary->rebuild((string) $options['date']);
        } else {
            $result = $summary->rebuildRange((int) ($options['days'] ?? 14));
        }
        return ['type' => 'earn_summary', 'status' => 'success', 'result' => $result];
    }

    private function runAttachmentArchive(array $options): array
    {
        return [
            'type' => 'attachment_archive',
            'status' => 'success',
            'result' => (new AttachmentStorageService())->downloadQueued((int) ($options['limit'] ?? 50)),
        ];
    }

    /** @return array<int, string> */
    private function botLogChannelIds(mixed $channelId): array
    {
        $allowed = ActivityMessageIngestService::WATCHED_CHANNEL_IDS;
        $channelId = trim((string) ($channelId ?? ''));
        if ($channelId !== '' && in_array($channelId, $allowed, true)) {
            return [$channelId];
        }
        return $allowed;
    }

    private function cursor(string $type, string $guildId, string $channelId): ?array
    {
        return Database::fetch(
            'SELECT * FROM tbl_sync_cursor WHERE cursorType = :cursorType AND guildId = :guildId AND channelId = :channelId',
            ['cursorType' => $type, 'guildId' => $guildId, 'channelId' => $channelId]
        );
    }

    private function saveCursor(string $type, string $guildId, string $channelId, ?string $value, array $metadata = []): void
    {
        Database::execute(
            'INSERT INTO tbl_sync_cursor (cursorType, guildId, channelId, cursorValue, metadataJson, updateDate)
             VALUES (:cursorType, :guildId, :channelId, :cursorValue, :metadataJson, :updateDate)
             ON DUPLICATE KEY UPDATE cursorValue = VALUES(cursorValue), metadataJson = VALUES(metadataJson), updateDate = VALUES(updateDate)',
            [
                'cursorType' => $type,
                'guildId' => $guildId,
                'channelId' => $channelId,
                'cursorValue' => $value,
                'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private function cursorMetadata(?array $cursor): array
    {
        if (!$cursor || empty($cursor['metadataJson'])) {
            return [];
        }
        $metadata = json_decode((string) $cursor['metadataJson'], true);
        return is_array($metadata) ? $metadata : [];
    }

    private function validSnowflake(mixed $value): bool
    {
        $value = (string) ($value ?? '');
        return ctype_digit($value) && strlen($value) >= 8;
    }

    private function recordError(string $type, array $response, array $context): void
    {
        Database::insert('tbl_ingest_error', [
            'errorType' => $type,
            'errorMessage' => is_array($response['body'] ?? null) ? (string) ($response['body']['message'] ?? 'Discord API error') : (string) ($response['body'] ?? 'Discord API error'),
            'contextJson' => json_encode($context + ['status' => $response['status'] ?? null], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function summarize(array $result): array
    {
        $summary = ['success' => 0, 'partial' => 0, 'failed' => 0];
        foreach ($result as $item) {
            $status = (string) ($item['status'] ?? 'success');
            if (!isset($summary[$status])) {
                $summary[$status] = 0;
            }
            $summary[$status]++;
        }
        return $summary;
    }
}
