<?php

declare(strict_types=1);

final class GachaOfflineRewardSummaryService
{
    private static bool $schemaReady = false;

    private const ROLE_EVENT_SQL = 'COALESCE(grantedAt, updateDate, createDate)';
    private const SPIN_EVENT_SQL = 'COALESCE(completedAt, prizeResolvedAt, ballSeenAt, revealedAt, startedAt, createDate)';
    /** @return array<string, mixed> */
    public function payload(string $guildId, string $userId): array
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            return [
                'enabled' => false,
                'initialized' => false,
                'hasSummary' => false,
                'count' => 0,
                'snapshot' => self::emptySnapshot(),
                'entries' => [],
                'totals' => self::emptyTotals(),
            ];
        }

        self::ensureSchema();

        $state = $this->stateSnapshot($guildId, $userId);
        $snapshot = $this->currentSnapshot($guildId, $userId);

        if ($state === null) {
            $this->storeSnapshot($guildId, $userId, $snapshot);

            return [
                'enabled' => true,
                'initialized' => true,
                'hasSummary' => false,
                'count' => 0,
                'snapshot' => $snapshot,
                'entries' => [],
                'totals' => self::emptyTotals(),
            ];
        }

        $entries = array_merge(
            $this->rewardEntriesSince($guildId, $userId, $state['reward']),
            $this->itemEntriesSince($guildId, $userId, $state['item']),
            $this->roleEntriesSince($guildId, $userId, $state['role'])
        );

        usort($entries, static function (array $left, array $right): int {
            $dateCompare = strcmp((string) ($right['date'] ?? ''), (string) ($left['date'] ?? ''));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return ((int) ($right['sortId'] ?? 0)) <=> ((int) ($left['sortId'] ?? 0));
        });

        $totals = $this->totals($entries);

        return [
            'enabled' => true,
            'initialized' => false,
            'hasSummary' => count($entries) > 0,
            'count' => count($entries),
            'snapshot' => $snapshot,
            'entries' => $entries,
            'totals' => $totals,
        ];
    }

    /** @param array<string, mixed> $snapshot */
    public function acknowledge(string $guildId, string $userId, array $snapshot): array
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            return self::emptySnapshot();
        }

        self::ensureSchema();

        $normalized = self::normalizeSnapshot($snapshot);
        if (self::snapshotIsEmpty($normalized)) {
            $normalized = $this->currentSnapshot($guildId, $userId);
        }

        $this->storeSnapshot($guildId, $userId, $normalized);
        return $normalized;
    }

    private static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        if (class_exists('ShopUnitService')) {
            ShopUnitService::ensureSchema();
        }
        if (class_exists('GachaRoleGrantService')) {
            GachaRoleGrantService::ensureSchema();
        }
        if (class_exists('GachaSpinHistoryService')) {
            GachaSpinHistoryService::ensureSchema();
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_player_state (
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                lastRewardEventDate datetime DEFAULT NULL,
                lastRewardEventId bigint unsigned NOT NULL DEFAULT 0,
                lastItemLedgerDate datetime DEFAULT NULL,
                lastItemLedgerId bigint unsigned NOT NULL DEFAULT 0,
                lastRoleGrantDate datetime DEFAULT NULL,
                lastRoleGrantId bigint unsigned NOT NULL DEFAULT 0,
                lastSpinHistoryDate datetime DEFAULT NULL,
                lastSpinHistoryId bigint unsigned NOT NULL DEFAULT 0,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (guildId, userId),
                KEY idx_tbl_gacha_player_state_update (updateDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    /** @return array<string, array{date: string, id: int}>|null */
    private function stateSnapshot(string $guildId, string $userId): ?array
    {
        $row = Database::fetch(
            'SELECT lastRewardEventDate,
                    lastRewardEventId,
                    lastItemLedgerDate,
                    lastItemLedgerId,
                    lastRoleGrantDate,
                    lastRoleGrantId,
                    lastSpinHistoryDate,
                    lastSpinHistoryId
               FROM tbl_gacha_player_state
              WHERE guildId = :guildId
                AND userId = :userId
              LIMIT 1',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        if (!$row) {
            return null;
        }

        return [
            'reward' => self::snapshotSegment($row['lastRewardEventDate'] ?? null, $row['lastRewardEventId'] ?? 0),
            'item' => self::snapshotSegment($row['lastItemLedgerDate'] ?? null, $row['lastItemLedgerId'] ?? 0),
            'role' => self::snapshotSegment($row['lastRoleGrantDate'] ?? null, $row['lastRoleGrantId'] ?? 0),
            'spin' => self::snapshotSegment($row['lastSpinHistoryDate'] ?? null, $row['lastSpinHistoryId'] ?? 0),
        ];
    }

    /** @param array<string, array{date: string, id: int}> $snapshot */
    private function storeSnapshot(string $guildId, string $userId, array $snapshot): void
    {
        Database::execute(
            'INSERT INTO tbl_gacha_player_state (
                guildId,
                userId,
                lastRewardEventDate,
                lastRewardEventId,
                lastItemLedgerDate,
                lastItemLedgerId,
                lastRoleGrantDate,
                lastRoleGrantId,
                lastSpinHistoryDate,
                lastSpinHistoryId,
                updateDate
            ) VALUES (
                :guildId,
                :userId,
                :lastRewardEventDate,
                :lastRewardEventId,
                :lastItemLedgerDate,
                :lastItemLedgerId,
                :lastRoleGrantDate,
                :lastRoleGrantId,
                :lastSpinHistoryDate,
                :lastSpinHistoryId,
                :updateDate
            )
            ON DUPLICATE KEY UPDATE
                lastRewardEventDate = VALUES(lastRewardEventDate),
                lastRewardEventId = VALUES(lastRewardEventId),
                lastItemLedgerDate = VALUES(lastItemLedgerDate),
                lastItemLedgerId = VALUES(lastItemLedgerId),
                lastRoleGrantDate = VALUES(lastRoleGrantDate),
                lastRoleGrantId = VALUES(lastRoleGrantId),
                lastSpinHistoryDate = VALUES(lastSpinHistoryDate),
                lastSpinHistoryId = VALUES(lastSpinHistoryId),
                updateDate = VALUES(updateDate)',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'lastRewardEventDate' => self::nullableDate($snapshot['reward']['date'] ?? ''),
                'lastRewardEventId' => max(0, (int) ($snapshot['reward']['id'] ?? 0)),
                'lastItemLedgerDate' => self::nullableDate($snapshot['item']['date'] ?? ''),
                'lastItemLedgerId' => max(0, (int) ($snapshot['item']['id'] ?? 0)),
                'lastRoleGrantDate' => self::nullableDate($snapshot['role']['date'] ?? ''),
                'lastRoleGrantId' => max(0, (int) ($snapshot['role']['id'] ?? 0)),
                'lastSpinHistoryDate' => self::nullableDate($snapshot['spin']['date'] ?? ''),
                'lastSpinHistoryId' => max(0, (int) ($snapshot['spin']['id'] ?? 0)),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /** @return array<string, array{date: string, id: int}> */
    private function currentSnapshot(string $guildId, string $userId): array
    {
        return [
            'reward' => $this->latestRewardSnapshot($guildId, $userId),
            'item' => $this->latestItemSnapshot($guildId, $userId),
            'role' => $this->latestRoleSnapshot($guildId, $userId),
            'spin' => $this->latestSpinSnapshot($guildId, $userId),
        ];
    }

    /** @return array{date: string, id: int} */
    private function latestRewardSnapshot(string $guildId, string $userId): array
    {
        $row = Database::fetch(
            'SELECT rewardEventId AS eventId, createDate AS eventDate
               FROM tbl_reward_event
              WHERE guildId = :guildId
                AND userId = :userId
                AND rewardStatus NOT IN ("spent", "expired")
              ORDER BY createDate DESC, rewardEventId DESC
              LIMIT 1',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return self::snapshotSegment($row['eventDate'] ?? null, $row['eventId'] ?? 0);
    }

    /** @return array{date: string, id: int} */
    private function latestItemSnapshot(string $guildId, string $userId): array
    {
        $row = Database::fetch(
            'SELECT shopInventoryLedgerId AS eventId, createDate AS eventDate
               FROM tbl_shop_inventory_ledger
              WHERE guildId = :guildId
                AND userId = :userId
                AND quantityDelta > 0
              ORDER BY createDate DESC, shopInventoryLedgerId DESC
              LIMIT 1',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return self::snapshotSegment($row['eventDate'] ?? null, $row['eventId'] ?? 0);
    }

    /** @return array{date: string, id: int} */
    private function latestRoleSnapshot(string $guildId, string $userId): array
    {
        $row = Database::fetch(
            'SELECT gachaRoleGrantId AS eventId,
                    ' . self::ROLE_EVENT_SQL . ' AS eventDate
               FROM tbl_gacha_role_grant
              WHERE guildId = :guildId
                AND userId = :userId
                AND grantStatus = "granted"
              ORDER BY eventDate DESC, gachaRoleGrantId DESC
              LIMIT 1',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return self::snapshotSegment($row['eventDate'] ?? null, $row['eventId'] ?? 0);
    }

    /** @return array{date: string, id: int} */
    private function latestSpinSnapshot(string $guildId, string $userId): array
    {
        $row = Database::fetch(
            'SELECT gachaSpinHistoryId AS eventId,
                    ' . self::SPIN_EVENT_SQL . ' AS eventDate
               FROM tbl_gacha_spin_history
              WHERE guildId = :guildId
                AND userId = :userId
                AND drawStatus = "completed"
                AND COALESCE(prizeType, "item") <> "role"
              ORDER BY eventDate DESC, gachaSpinHistoryId DESC
              LIMIT 1',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return self::snapshotSegment($row['eventDate'] ?? null, $row['eventId'] ?? 0);
    }

    /** @param array{date: string, id: int} $after */
    private function rewardEntriesSince(string $guildId, string $userId, array $after): array
    {
        $params = ['guildId' => $guildId, 'userId' => $userId];
        $where = '';
        if ($after['date'] !== '' || $after['id'] > 0) {
            $where = ' AND (re.createDate > :eventDateAfter OR (re.createDate = :eventDateEqual AND re.rewardEventId > :eventIdAfter))';
            $params['eventDateAfter'] = $after['date'] !== '' ? $after['date'] : '1970-01-01 00:00:00';
            $params['eventDateEqual'] = $params['eventDateAfter'];
            $params['eventIdAfter'] = max(0, (int) $after['id']);
        }

        $rows = Database::fetchAll(
            'SELECT re.rewardEventId,
                    re.sourceType,
                    re.sourceId,
                    re.rewardStatus,
                    re.metadataJson,
                    re.createDate,
                    rr.ruleCode,
                    rr.ruleName
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
              WHERE re.guildId = :guildId
                AND re.userId = :userId
                AND re.rewardStatus NOT IN ("spent", "expired")' . $where . '
              ORDER BY re.createDate DESC, re.rewardEventId DESC',
            $params
        );

        $entries = [];
        foreach ($rows as $row) {
            $metadata = self::decodeJson($row['metadataJson'] ?? '');
            $unitRewards = self::rewardUnitRewards($metadata);
            if (!$unitRewards) {
                continue;
            }

            $entries[] = [
                'kind' => 'reward',
                'tag' => 'สะสม',
                'title' => $this->rewardSourceLabel($row, $metadata),
                'detail' => $this->formatUnitRewards($unitRewards),
                'context' => $this->rewardContextLabel($metadata),
                'date' => (string) ($row['createDate'] ?? ''),
                'sortId' => (int) ($row['rewardEventId'] ?? 0),
                'image' => '',
                'unitRewards' => $unitRewards,
            ];
        }

        return $entries;
    }

    /** @param array{date: string, id: int} $after */
    private function itemEntriesSince(string $guildId, string $userId, array $after): array
    {
        $params = ['guildId' => $guildId, 'userId' => $userId];
        $where = '';
        if ($after['date'] !== '' || $after['id'] > 0) {
            $where = ' AND (il.createDate > :eventDateAfter OR (il.createDate = :eventDateEqual AND il.shopInventoryLedgerId > :eventIdAfter))';
            $params['eventDateAfter'] = $after['date'] !== '' ? $after['date'] : '1970-01-01 00:00:00';
            $params['eventDateEqual'] = $params['eventDateAfter'];
            $params['eventIdAfter'] = max(0, (int) $after['id']);
        }

        $rows = Database::fetchAll(
            'SELECT il.shopInventoryLedgerId,
                    il.quantityDelta,
                    il.sourceType,
                    il.sourceId,
                    il.metadataJson,
                    il.createDate,
                    item.itemCode,
                    item.itemName,
                    item.image
               FROM tbl_shop_inventory_ledger il
          LEFT JOIN tbl_shop_item item ON item.shopItemId = il.shopItemId
              WHERE il.guildId = :guildId
                AND il.userId = :userId
                AND il.quantityDelta > 0' . $where . '
              ORDER BY il.createDate DESC, il.shopInventoryLedgerId DESC',
            $params
        );

        $entries = [];
        foreach ($rows as $row) {
            $metadata = self::decodeJson($row['metadataJson'] ?? '');
            $quantity = max(1, (int) ($row['quantityDelta'] ?? 0));
            $entries[] = [
                'kind' => 'item',
                'tag' => 'ไอเทม',
                'title' => trim((string) ($row['itemName'] ?? $row['itemCode'] ?? 'Item')) ?: 'Item',
                'detail' => 'x' . number_format($quantity) . ' เข้ากระเป๋าแล้ว',
                'context' => $this->itemSourceLabel($row, $metadata),
                'date' => (string) ($row['createDate'] ?? ''),
                'sortId' => (int) ($row['shopInventoryLedgerId'] ?? 0),
                'image' => trim((string) ($row['image'] ?? '')),
                'quantity' => $quantity,
                'itemCode' => (string) ($row['itemCode'] ?? ''),
            ];
        }

        return $entries;
    }

    /** @param array{date: string, id: int} $after */
    private function roleEntriesSince(string $guildId, string $userId, array $after): array
    {
        $params = ['guildId' => $guildId, 'userId' => $userId];
        $where = '';
        if ($after['date'] !== '' || $after['id'] > 0) {
            $where = ' AND (eventDate > :eventDateAfter OR (eventDate = :eventDateEqual AND gachaRoleGrantId > :eventIdAfter))';
            $params['eventDateAfter'] = $after['date'] !== '' ? $after['date'] : '1970-01-01 00:00:00';
            $params['eventDateEqual'] = $params['eventDateAfter'];
            $params['eventIdAfter'] = max(0, (int) $after['id']);
        }

        $rows = Database::fetchAll(
            'SELECT *
               FROM (
                    SELECT gachaRoleGrantId,
                           roleId,
                           roleName,
                           prizeName,
                           durationDays,
                           grantStatus,
                           grantedAt,
                           createDate,
                           ' . self::ROLE_EVENT_SQL . ' AS eventDate
                      FROM tbl_gacha_role_grant
                     WHERE guildId = :guildId
                       AND userId = :userId
                       AND grantStatus = "granted"
               ) grantedRows
              WHERE 1 = 1' . $where . '
              ORDER BY eventDate DESC, gachaRoleGrantId DESC',
            $params
        );

        $entries = [];
        foreach ($rows as $row) {
            $durationDays = max(0, (int) ($row['durationDays'] ?? 0));
            $entries[] = [
                'kind' => 'role',
                'tag' => 'ยศ',
                'title' => trim((string) ($row['roleName'] ?? $row['prizeName'] ?? $row['roleId'] ?? 'Role')) ?: 'Role',
                'detail' => $durationDays > 0 ? ($durationDays . ' วัน') : 'ถาวร',
                'context' => 'ได้รับเข้าคลังยศแล้ว',
                'date' => (string) ($row['eventDate'] ?? $row['grantedAt'] ?? $row['createDate'] ?? ''),
                'sortId' => (int) ($row['gachaRoleGrantId'] ?? 0),
                'image' => '',
                'durationDays' => $durationDays,
            ];
        }

        return $entries;
    }

    /** @param array{date: string, id: int} $after */
    private function spinEntriesSince(string $guildId, string $userId, array $after): array
    {
        $params = ['guildId' => $guildId, 'userId' => $userId];
        $where = '';
        if ($after['date'] !== '' || $after['id'] > 0) {
            $where = ' AND (eventDate > :eventDateAfter OR (eventDate = :eventDateEqual AND gachaSpinHistoryId > :eventIdAfter))';
            $params['eventDateAfter'] = $after['date'] !== '' ? $after['date'] : '1970-01-01 00:00:00';
            $params['eventDateEqual'] = $params['eventDateAfter'];
            $params['eventIdAfter'] = max(0, (int) $after['id']);
        }

        $rows = Database::fetchAll(
            'SELECT *
               FROM (
                    SELECT gachaSpinHistoryId,
                           `count`,
                           tierName,
                           prizeId,
                           prizeName,
                           prizeType,
                           snapshotJson,
                           ' . self::SPIN_EVENT_SQL . ' AS eventDate
                      FROM tbl_gacha_spin_history
                     WHERE guildId = :guildId
                       AND userId = :userId
                       AND drawStatus = "completed"
                       AND COALESCE(prizeType, "item") <> "role"
               ) spinRows
              WHERE 1 = 1' . $where . '
              ORDER BY eventDate DESC, gachaSpinHistoryId DESC',
            $params
        );

        $entries = [];
        foreach ($rows as $row) {
            $snapshot = self::decodeJson($row['snapshotJson'] ?? '');
            $prize = is_array($snapshot['prize'] ?? null) ? $snapshot['prize'] : [];
            $image = trim((string) ($prize['image'] ?? ''));
            $tierName = trim((string) ($row['tierName'] ?? ''));
            $entries[] = [
                'kind' => 'spin',
                'tag' => 'กาชา',
                'title' => trim((string) ($row['prizeName'] ?? $row['prizeId'] ?? 'ของรางวัลกาชา')) ?: 'ของรางวัลกาชา',
                'detail' => $tierName !== '' ? $tierName : 'ชนะจากการหมุน',
                'context' => max(1, (int) ($row['count'] ?? 1)) > 1
                    ? ('เปิด ' . number_format((int) ($row['count'] ?? 1)) . ' ครั้ง')
                    : 'กาชาปอง',
                'date' => (string) ($row['eventDate'] ?? ''),
                'sortId' => (int) ($row['gachaSpinHistoryId'] ?? 0),
                'image' => $image,
                'count' => max(1, (int) ($row['count'] ?? 1)),
                'prizeType' => (string) ($row['prizeType'] ?? ''),
            ];
        }

        return $entries;
    }

    /** @param array<int, array<string, mixed>> $entries */
    private function totals(array $entries): array
    {
        $totals = self::emptyTotals();

        foreach ($entries as $entry) {
            $kind = (string) ($entry['kind'] ?? '');
            if ($kind === 'reward') {
                foreach ((array) ($entry['unitRewards'] ?? []) as $unitCode => $amount) {
                    $amount = max(0, (int) $amount);
                    if ($amount <= 0) {
                        continue;
                    }
                    $totals['units'][(string) $unitCode] = max(0, (int) ($totals['units'][(string) $unitCode] ?? 0)) + $amount;
                }
                continue;
            }

            if ($kind === 'item') {
                $totals['items'] += max(1, (int) ($entry['quantity'] ?? 1));
                continue;
            }

            if ($kind === 'role') {
                $totals['roles'] += 1;
                continue;
            }

            if ($kind === 'spin') {
                $totals['spins'] += max(1, (int) ($entry['count'] ?? 1));
            }
        }

        return $totals;
    }

    /** @param array<string, mixed> $row */
    private function rewardSourceLabel(array $row, array $metadata): string
    {
        $ruleCode = trim((string) ($row['ruleCode'] ?? $metadata['rule'] ?? $row['sourceType'] ?? ''));
        $mapped = self::friendlySourceLabel($ruleCode);
        if ($mapped !== '') {
            return $mapped;
        }

        $ruleName = trim((string) ($row['ruleName'] ?? ''));
        if ($ruleName !== '') {
            return $ruleName;
        }

        return 'รางวัลอัตโนมัติ';
    }

    private function rewardContextLabel(array $metadata): string
    {
        $dailyLabel = trim((string) ($metadata['dailyCheckin']['label'] ?? ''));
        if ($dailyLabel !== '') {
            return $dailyLabel;
        }

        $reason = trim((string) ($metadata['reason'] ?? ''));
        if ($reason !== '') {
            return $reason;
        }

        $rewardLabel = trim((string) ($metadata['reward']['label'] ?? ''));
        if ($rewardLabel !== '') {
            return $rewardLabel;
        }

        $targetLabel = trim((string) ($metadata['manualGrant']['targetLabel'] ?? ''));
        if ($targetLabel !== '') {
            return $targetLabel;
        }

        return '';
    }

    /** @param array<string, mixed> $row */
    private function itemSourceLabel(array $row, array $metadata): string
    {
        $rewardLabel = trim((string) ($metadata['reward']['label'] ?? ''));
        if ($rewardLabel !== '') {
            return $rewardLabel;
        }

        $sourceType = trim((string) ($row['sourceType'] ?? ''));
        $mapped = self::friendlySourceLabel($sourceType);
        if ($mapped !== '') {
            return $mapped;
        }

        return 'เข้ากระเป๋าอัตโนมัติ';
    }

    /** @param array<string, int> $unitRewards */
    private function formatUnitRewards(array $unitRewards): string
    {
        $units = class_exists('ShopUnitService') ? ShopUnitService::unitIndex(true) : [];
        $parts = [];
        foreach ($unitRewards as $unitCode => $amount) {
            $amount = max(0, (int) $amount);
            if ($amount <= 0) {
                continue;
            }

            if ((string) $unitCode === 'freeSpin') {
                $parts[] = '+' . number_format($amount) . ' สุ่มฟรี';
                continue;
            }

            $displayName = trim((string) (($units[(string) $unitCode]['displayName'] ?? '') ?: ($units[(string) $unitCode]['shortName'] ?? '') ?: $unitCode));
            $parts[] = '+' . number_format($amount) . ' ' . $displayName;
        }

        return implode(' · ', $parts);
    }

    /** @return array<string, int> */
    private static function rewardUnitRewards(array $metadata): array
    {
        $reward = is_array($metadata['reward'] ?? null) ? $metadata['reward'] : [];
        $unitRewards = is_array($reward['unitRewards'] ?? null) ? $reward['unitRewards'] : [];

        if (!$unitRewards) {
            foreach (['coin', 'ticket', 'gem', 'potion'] as $unitCode) {
                $amount = max(0, (int) ($reward[$unitCode] ?? ($unitCode === 'ticket' ? ($reward['gachaTicket'] ?? 0) : 0)));
                if ($amount > 0) {
                    $unitRewards[$unitCode] = $amount;
                }
            }
        }

        if (!$unitRewards) {
            $kind = trim((string) ($reward['kind'] ?? ''));
            $amount = max(0, (int) ($reward['amount'] ?? 0));
            if ($amount > 0 && in_array($kind, ['coin', 'ticket', 'gem', 'potion'], true)) {
                $unitRewards[$kind] = $amount;
            }
        }

        $freeSpin = max(0, (int) ($reward['gachaFreeSpin'] ?? $reward['freeSpin'] ?? 0));
        if ($freeSpin > 0) {
            $unitRewards['freeSpin'] = max(0, (int) ($unitRewards['freeSpin'] ?? 0)) + $freeSpin;
        }

        $normalized = [];
        foreach ($unitRewards as $unitCode => $amount) {
            $amount = max(0, (int) $amount);
            if ($amount > 0) {
                $normalized[(string) $unitCode] = $amount;
            }
        }

        return $normalized;
    }

    private static function friendlySourceLabel(string $key): string
    {
        $map = [
            'gacha_mileage' => 'Mileage',
            'gacha_mileage_reward' => 'Mileage',
            'gacha_daily_checkin' => 'เช็คอินประจำวัน',
            'earn_daily_checkin' => 'เช็คอินประจำวัน',
            'earn_manual' => 'แอดมินเพิ่มให้',
            'earn_manual_grant' => 'แอดมินเพิ่มให้',
            'earn_manual_revoke' => 'แอดมินดึงคืน',
            'earn_text_active_daily' => 'ข้อความ active รายวัน',
            'earn_voice_hourly' => 'สะสมชั่วโมงเสียง',
            'earn_voice_10min_free_spin' => 'เข้าห้องครบ 10 นาที',
            'earn_member_first_join' => 'เข้าเซิร์ฟครั้งแรก',
            'earn_invite_member' => 'ชวนเพื่อนเข้าเซิร์ฟ',
            'shop_role_badge_purchase' => 'ร้านค้า',
            'shop_role_badge_gift' => 'ของขวัญจากร้านค้า',
        ];

        return $map[trim($key)] ?? '';
    }

    /** @return array<string, mixed> */
    private static function decodeJson(mixed $json): array
    {
        $decoded = json_decode((string) ($json ?? ''), true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, array{date: string, id: int}> */
    private static function normalizeSnapshot(array $snapshot): array
    {
        $normalized = self::emptySnapshot();
        foreach (['reward', 'item', 'role', 'spin'] as $key) {
            $segment = is_array($snapshot[$key] ?? null) ? $snapshot[$key] : [];
            $normalized[$key] = self::snapshotSegment($segment['date'] ?? null, $segment['id'] ?? 0);
        }

        return $normalized;
    }

    /** @return array<string, array{date: string, id: int}> */
    private static function emptySnapshot(): array
    {
        return [
            'reward' => self::snapshotSegment(null, 0),
            'item' => self::snapshotSegment(null, 0),
            'role' => self::snapshotSegment(null, 0),
            'spin' => self::snapshotSegment(null, 0),
        ];
    }

    /** @return array<string, int|array<string, int>> */
    private static function emptyTotals(): array
    {
        return [
            'units' => [],
            'items' => 0,
            'roles' => 0,
            'spins' => 0,
        ];
    }

    /** @return array{date: string, id: int} */
    private static function snapshotSegment(mixed $date, mixed $id): array
    {
        return [
            'date' => trim((string) ($date ?? '')),
            'id' => max(0, (int) ($id ?? 0)),
        ];
    }

    /** @param array<string, array{date: string, id: int}> $snapshot */
    private static function snapshotIsEmpty(array $snapshot): bool
    {
        foreach ($snapshot as $segment) {
            if ((int) ($segment['id'] ?? 0) > 0 || trim((string) ($segment['date'] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function nullableDate(string $date): ?string
    {
        $date = trim($date);
        return $date !== '' ? $date : null;
    }
}
