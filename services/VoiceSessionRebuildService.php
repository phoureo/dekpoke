<?php

declare(strict_types=1);

final class VoiceSessionRebuildService
{
    public function rebuildFromRawEvents(string $guildId): array
    {
        $before = (int) (Database::fetch(
            'SELECT COUNT(*) AS total FROM tbl_voice_session WHERE guildId = :guildId',
            ['guildId' => $guildId]
        )['total'] ?? 0);

        Database::execute('DELETE FROM tbl_voice_session WHERE guildId = :guildId', ['guildId' => $guildId]);

        $events = Database::fetchAll(
            'SELECT rawEventId, guildId, eventType, eventPayloadJson, sourceName, contextJson, eventDate
             FROM tbl_raw_event
             WHERE guildId = :guildId
               AND eventType = "VOICE_STATE_UPDATE"
               AND processStatus IN ("queued", "success")
             ORDER BY eventDate ASC, rawEventId ASC',
            ['guildId' => $guildId]
        );

        $openByUser = [];
        $stats = [
            'before' => $before,
            'events' => count($events),
            'opened' => 0,
            'closed' => 0,
            'flagContext' => 0,
            'uncertainClosed' => 0,
            'open' => 0,
            'after' => 0,
        ];

        foreach ($events as $event) {
            $payload = json_decode((string) ($event['eventPayloadJson'] ?? '{}'), true);
            $context = json_decode((string) ($event['contextJson'] ?? '{}'), true);
            $payload = is_array($payload) ? $payload : [];
            $context = is_array($context) ? $context : [];
            $sourceName = (string) ($event['sourceName'] ?? '');
            $userId = (string) ($payload['user_id'] ?? ($payload['member']['user']['id'] ?? ''));
            if ($userId === '') {
                continue;
            }

            $eventDate = (string) ($event['eventDate'] ?? date('Y-m-d H:i:s'));
            $channelId = isset($payload['channel_id']) && $payload['channel_id'] !== null ? (string) $payload['channel_id'] : null;
            $previousChannelId = isset($payload['old_channel_id']) && $payload['old_channel_id'] !== null ? (string) $payload['old_channel_id'] : null;

            if ($sourceName === 'bot_log_voice_flags') {
                if (isset($openByUser[$userId])) {
                    if (array_key_exists('self_stream', $payload)) {
                        $openByUser[$userId]['isStreaming'] = !empty($payload['self_stream']) ? 1 : 0;
                    }
                    if (array_key_exists('self_video', $payload)) {
                        $openByUser[$userId]['isVideo'] = !empty($payload['self_video']) ? 1 : 0;
                    }
                    $openByUser[$userId]['metadata']['flagContext'][] = [
                        'rawEventId' => (int) $event['rawEventId'],
                        'eventDate' => $eventDate,
                        'action' => $context['action'] ?? null,
                        'self_mute_context' => $context['self_mute_context'] ?? null,
                        'self_deaf_context' => $context['self_deaf_context'] ?? null,
                    ];
                }
                $stats['flagContext']++;
                continue;
            }

            if ($channelId === null) {
                if (isset($openByUser[$userId])) {
                    $this->insertSession($openByUser[$userId], $eventDate, true, [
                        'closedByRawEventId' => (int) $event['rawEventId'],
                        'previousChannelId' => $previousChannelId,
                    ]);
                    unset($openByUser[$userId]);
                    $stats['closed']++;
                    continue;
                }
                $stats['uncertainClosed']++;
                continue;
            }

            if (isset($openByUser[$userId]) && (string) $openByUser[$userId]['channelId'] !== $channelId) {
                $this->insertSession($openByUser[$userId], $eventDate, true, [
                    'closedByRawEventId' => (int) $event['rawEventId'],
                    'previousChannelId' => $previousChannelId,
                ]);
                unset($openByUser[$userId]);
                $stats['closed']++;
            }

            if (!isset($openByUser[$userId])) {
                $openByUser[$userId] = $this->sessionSeed($event, $payload, $context, $channelId);
                $stats['opened']++;
                continue;
            }

            $openByUser[$userId]['metadata']['refreshContext'][] = [
                'rawEventId' => (int) $event['rawEventId'],
                'eventDate' => $eventDate,
                'context' => $context,
            ];
        }

        foreach ($openByUser as $seed) {
            $this->insertSession($seed, null, false, ['currentSource' => 'latest_voice_state_update']);
            $stats['open']++;
        }

        $stats['after'] = (int) (Database::fetch(
            'SELECT COUNT(*) AS total FROM tbl_voice_session WHERE guildId = :guildId',
            ['guildId' => $guildId]
        )['total'] ?? 0);

        LiveUpdateService::markTopic('activity', ['scope' => 'voice_session_rebuild'] + $stats, 'guild', $guildId, $guildId);
        return $stats;
    }

    public function rebuildFromActivity(string $guildId): array
    {
        return $this->rebuildFromRawEvents($guildId);
    }

    private function sessionSeed(array $event, array $payload, array $context, string $channelId): array
    {
        return [
            'guildId' => (string) $event['guildId'],
            'userId' => (string) ($payload['user_id'] ?? ($payload['member']['user']['id'] ?? '')),
            'channelId' => $channelId,
            'startDate' => (string) $event['eventDate'],
            'sourceRawEventId' => (int) $event['rawEventId'],
            'isMuted' => !empty($payload['self_mute']) || !empty($payload['mute']) ? 1 : 0,
            'isDeafened' => !empty($payload['self_deaf']) || !empty($payload['deaf']) ? 1 : 0,
            'isStreaming' => !empty($payload['self_stream']) ? 1 : 0,
            'isVideo' => !empty($payload['self_video']) ? 1 : 0,
            'metadata' => [
                'sourceRawEventId' => (int) $event['rawEventId'],
                'sourceName' => (string) ($event['sourceName'] ?? ''),
                'context' => $context,
            ],
        ];
    }

    private function insertSession(array $seed, ?string $endDate, bool $isClosed, array $extraMetadata): void
    {
        $metadata = array_merge($seed['metadata'], $extraMetadata);
        $durationSeconds = $isClosed
            ? max(0, strtotime((string) $endDate) - strtotime((string) $seed['startDate']))
            : 0;

        Database::insert('tbl_voice_session', [
            'guildId' => (string) $seed['guildId'],
            'userId' => (string) $seed['userId'],
            'channelId' => (string) $seed['channelId'],
            'startDate' => (string) $seed['startDate'],
            'endDate' => $endDate,
            'durationSeconds' => $durationSeconds,
            'isMuted' => (int) ($seed['isMuted'] ?? 0),
            'isDeafened' => (int) ($seed['isDeafened'] ?? 0),
            'isStreaming' => (int) ($seed['isStreaming'] ?? 0),
            'isVideo' => (int) ($seed['isVideo'] ?? 0),
            'isClosed' => $isClosed ? 1 : 0,
            'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updateDate' => date('Y-m-d H:i:s'),
        ]);
    }
}
