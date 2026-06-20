<?php

declare(strict_types=1);

final class LiveUpdateService
{
    public static function allowedPageKeys(): array
    {
        return [
            'roles',
            'messages',
            'activity',
            'gacha_prize',
            'gacha_campaign',
            'gacha_report',
            'gacha_shop',
            'earn_settings',
            'earn_manual',
            'reward_report',
            'shop_report',
            'shop_member_bags',
            'bag_transaction_report',
            'permission',
            'admin',
            'backfill',
            'logs',
        ];
    }

    public static function isAllowedPageKey(string $pageKey): bool
    {
        return in_array($pageKey, self::allowedPageKeys(), true);
    }

    public static function mark(array $pageKeys, string $updateType, ?string $entityType = null, ?string $entityId = null, array $metadata = [], ?string $guildId = null, ?string $sourceType = null, ?string $sourceId = null): void
    {
        // Canonical workspace keeps no live-update event tables.
    }

    public static function markTopic(string $topic, array $metadata = [], ?string $entityType = null, ?string $entityId = null, ?string $guildId = null): void
    {
        // Canonical workspace keeps no live-update event tables.
    }

    public static function state(string $pageKey, int $lastSeenLiveUpdateId = 0): array
    {
        if (!self::isAllowedPageKey($pageKey)) {
            throw new InvalidArgumentException('Invalid page key.');
        }

        return [
            'pageKey' => $pageKey,
            'hasUpdate' => false,
            'currentLiveUpdateId' => max(0, $lastSeenLiveUpdateId),
            'lastUpdateType' => null,
            'lastEntityType' => null,
            'lastEntityId' => null,
            'updateDate' => null,
        ];
    }

    public static function heartbeat(string $viewerToken, string $pageKey, int $adminUserId, int $lastSeenLiveUpdateId = 0, array $metadata = []): array
    {
        return self::state($pageKey, $lastSeenLiveUpdateId) + [
            'viewerToken' => $viewerToken,
            'viewerCount' => 0,
        ];
    }

    public static function systemState(): array
    {
        $gateway = self::workerState('gateway_worker', 20);
        $sync = self::workerState('sync_worker', 40);
        $backfill = self::workerState('backfill_worker', 90);

        return [
            'mode' => $gateway['isLive'] ? 'live' : 'degraded',
            'isDegraded' => !$gateway['isLive'],
            'gateway' => $gateway,
            'sync' => $sync,
            'backfill' => $backfill,
        ];
    }

    public static function closeViewer(string $viewerToken, ?int $adminUserId = null): void
    {
        // No viewer persistence in the lean workspace.
    }

    public static function closeByAdminUser(int $adminUserId): void
    {
        // No viewer persistence in the lean workspace.
    }

    private static function workerState(string $workerName, int $liveSeconds): array
    {
        $row = Database::fetch(
            'SELECT workerName, heartbeatDate, metadataJson
               FROM tbl_worker_heartbeat
              WHERE workerName = :workerName',
            ['workerName' => $workerName]
        );

        $lastSeen = $row['heartbeatDate'] ?? null;
        $ageSeconds = $lastSeen ? max(0, time() - strtotime((string) $lastSeen)) : null;
        $metadata = json_decode((string) ($row['metadataJson'] ?? '{}'), true);
        $metadata = is_array($metadata) ? $metadata : [];
        $hasBlockingState = !empty($metadata['error']) || !empty($metadata['reconnectInSeconds']) || !empty($metadata['stopped']);

        return [
            'workerName' => $workerName,
            'isLive' => $ageSeconds !== null && $ageSeconds <= $liveSeconds && !$hasBlockingState,
            'lastSeenDate' => $lastSeen,
            'ageSeconds' => $ageSeconds,
            'metadata' => $metadata,
            'liveThresholdSeconds' => $liveSeconds,
        ];
    }
}
