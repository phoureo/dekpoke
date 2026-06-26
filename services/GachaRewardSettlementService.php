<?php

declare(strict_types=1);

final class GachaRewardSettlementService
{
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        self::ensurePendingDrawSchema();

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_reward_settlement (
                gachaRewardSettlementId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                drawId varchar(64) NOT NULL,
                settlementStatus varchar(40) NOT NULL DEFAULT "pending",
                sourceType varchar(80) DEFAULT NULL,
                sourceId varchar(120) DEFAULT NULL,
                rewardType varchar(80) DEFAULT NULL,
                drawJson longtext DEFAULT NULL,
                resultJson longtext DEFAULT NULL,
                mileageSummaryJson longtext DEFAULT NULL,
                lastError text DEFAULT NULL,
                settledAt datetime DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaRewardSettlementId),
                UNIQUE KEY uq_tbl_gacha_reward_settlement_draw (guildId, drawId),
                KEY idx_tbl_gacha_reward_settlement_status (settlementStatus, updateDate),
                KEY idx_tbl_gacha_reward_settlement_user (guildId, userId, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_stored_ball (
                gachaStoredBallId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                drawId varchar(64) NOT NULL,
                ballStatus varchar(40) NOT NULL DEFAULT "stored",
                drawJson longtext NOT NULL,
                resultJson longtext DEFAULT NULL,
                metadataJson longtext DEFAULT NULL,
                openedAt datetime DEFAULT NULL,
                settledAt datetime DEFAULT NULL,
                lastError text DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaStoredBallId),
                UNIQUE KEY uq_tbl_gacha_stored_ball_draw (guildId, drawId),
                KEY idx_tbl_gacha_stored_ball_user_status (guildId, userId, ballStatus, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    /** @return array<int, array<string, mixed>> */
    public function storedBalls(string $guildId, string $userId, int $limit = 50): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $rows = Database::fetchAll(
            'SELECT *
               FROM tbl_gacha_stored_ball
              WHERE guildId = :guildId
                AND userId = :userId
                AND ballStatus IN ("stored", "opened", "failed")
              ORDER BY createDate DESC, gachaStoredBallId DESC
              LIMIT ' . $limit,
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return array_values(array_map([$this, 'decorateStoredBallRow'], $rows));
    }

    /** @return array<string, mixed> */
    public function storeAbandonedDraw(string $guildId, string $userId, array $draw, string $reason = 'abandoned'): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $drawId = trim((string) ($draw['drawId'] ?? ''));
        if ($guildId === '' || $userId === '' || $drawId === '') {
            throw new InvalidArgumentException('STORED_BALL_TARGET_REQUIRED');
        }

        if (!empty($draw['completedAt']) || !empty($draw['refundedAt'])) {
            throw new RuntimeException('DRAW_ALREADY_FINISHED');
        }

        $nowTs = time();
        $now = date('Y-m-d H:i:s', $nowTs);
        $storedDraw = $draw;
        $storedDraw['storedBallAt'] = $storedDraw['storedBallAt'] ?? $nowTs;
        $storedDraw['storedBallReason'] = $reason;
        $storedDraw['drawStatus'] = 'active';
        $drawJson = self::encodeJson($storedDraw);
        $metadataJson = self::encodeJson([
            'reason' => $reason,
            'storedAt' => $now,
        ]);

        Database::execute(
            'INSERT INTO tbl_gacha_stored_ball
                (guildId, userId, drawId, ballStatus, drawJson, metadataJson, updateDate)
             VALUES
                (:guildId, :userId, :drawId, "stored", :drawJson, :metadataJson, :updateDate)
             ON DUPLICATE KEY UPDATE
                userId = VALUES(userId),
                drawJson = IF(ballStatus IN ("stored", "failed"), VALUES(drawJson), drawJson),
                metadataJson = IF(ballStatus IN ("stored", "failed"), VALUES(metadataJson), metadataJson),
                lastError = IF(ballStatus = "failed", NULL, lastError),
                ballStatus = IF(ballStatus = "failed", "stored", ballStatus),
                updateDate = VALUES(updateDate)',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'drawId' => $drawId,
                'drawJson' => $drawJson,
                'metadataJson' => $metadataJson,
                'updateDate' => $now,
            ]
        );

        $this->deletePendingDraw($guildId, $userId, $drawId, 'active');
        $this->syncHistory($guildId, $userId, $storedDraw);
        $this->markLiveUpdate($guildId, $drawId, 'gacha_stored_ball_created', ['reason' => $reason]);

        $row = $this->storedBallRowByDraw($guildId, $drawId);
        return $this->decorateStoredBallRow($row ?: []);
    }

    /** @return array<string, mixed> */
    public function openStoredBall(string $guildId, string $userId, int $storedBallId, string $sourceType = 'stored_ball_open'): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '' || $storedBallId <= 0) {
            throw new InvalidArgumentException('STORED_BALL_REQUIRED');
        }

        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $row = Database::fetch(
                'SELECT *
                   FROM tbl_gacha_stored_ball
                  WHERE gachaStoredBallId = :storedBallId
                    AND guildId = :guildId
                    AND userId = :userId
                  LIMIT 1
                  FOR UPDATE',
                ['storedBallId' => $storedBallId, 'guildId' => $guildId, 'userId' => $userId]
            );

            if (!$row) {
                throw new RuntimeException('STORED_BALL_NOT_FOUND');
            }

            $draw = self::decodeJson((string) ($row['drawJson'] ?? ''));
            if (!$draw) {
                throw new RuntimeException('STORED_BALL_DRAW_INVALID');
            }

            $draw['completedAt'] = $draw['completedAt'] ?? time();
            $draw['openedStoredBallAt'] = time();
            $draw['storedBallId'] = (int) ($row['gachaStoredBallId'] ?? 0);

            $settlement = $this->settleCompletedDraw($guildId, $userId, $draw, $sourceType, 'stored-ball:' . $storedBallId);
            $now = date('Y-m-d H:i:s');
            Database::execute(
                'UPDATE tbl_gacha_stored_ball
                    SET ballStatus = "settled",
                        drawJson = :drawJson,
                        resultJson = :resultJson,
                        openedAt = COALESCE(openedAt, :openedAt),
                        settledAt = :settledAt,
                        lastError = NULL,
                        updateDate = :updateDate
                  WHERE gachaStoredBallId = :storedBallId',
                [
                    'drawJson' => self::encodeJson($draw),
                    'resultJson' => self::encodeJson($settlement),
                    'openedAt' => $now,
                    'settledAt' => $now,
                    'updateDate' => $now,
                    'storedBallId' => $storedBallId,
                ]
            );

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'ok' => true,
                'storedBallId' => $storedBallId,
                'drawId' => (string) ($draw['drawId'] ?? ''),
                'settlement' => $settlement,
                'prize' => is_array($draw['prize'] ?? null) ? $draw['prize'] : null,
            ];
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->markStoredBallFailed($storedBallId, $exception->getMessage());
            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function settleCompletedDraw(
        string $guildId,
        string $userId,
        array $draw,
        string $sourceType = 'gacha_complete',
        ?string $sourceId = null
    ): array {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $drawId = trim((string) ($draw['drawId'] ?? ''));
        if ($guildId === '' || $userId === '' || $drawId === '') {
            throw new InvalidArgumentException('DRAW_SETTLEMENT_TARGET_REQUIRED');
        }

        $sourceType = trim($sourceType) !== '' ? trim($sourceType) : 'gacha_complete';
        $sourceId = trim((string) ($sourceId ?? $drawId)) ?: $drawId;
        $draw['completedAt'] = $draw['completedAt'] ?? time();
        $drawJson = self::encodeJson($draw);
        $prize = is_array($draw['prize'] ?? null) ? $draw['prize'] : [];
        $rewardType = trim((string) ($prize['type'] ?? 'item')) ?: 'item';

        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $row = Database::fetch(
                'SELECT *
                   FROM tbl_gacha_reward_settlement
                  WHERE guildId = :guildId
                    AND drawId = :drawId
                  LIMIT 1
                  FOR UPDATE',
                ['guildId' => $guildId, 'drawId' => $drawId]
            );

            if ($row && (string) ($row['settlementStatus'] ?? '') === 'settled') {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                $result = self::decodeJson((string) ($row['resultJson'] ?? ''));
                return $result + [
                    'ok' => true,
                    'drawId' => $drawId,
                    'alreadySettled' => true,
                ];
            }

            if (!$row) {
                Database::execute(
                    'INSERT INTO tbl_gacha_reward_settlement
                        (guildId, userId, drawId, settlementStatus, sourceType, sourceId, rewardType, drawJson, updateDate)
                     VALUES
                        (:guildId, :userId, :drawId, "pending", :sourceType, :sourceId, :rewardType, :drawJson, :updateDate)',
                    [
                        'guildId' => $guildId,
                        'userId' => $userId,
                        'drawId' => $drawId,
                        'sourceType' => $sourceType,
                        'sourceId' => $sourceId,
                        'rewardType' => $rewardType,
                        'drawJson' => $drawJson,
                        'updateDate' => date('Y-m-d H:i:s'),
                    ]
                );
            } else {
                Database::execute(
                    'UPDATE tbl_gacha_reward_settlement
                        SET userId = :userId,
                            settlementStatus = "pending",
                            sourceType = :sourceType,
                            sourceId = :sourceId,
                            rewardType = :rewardType,
                            drawJson = :drawJson,
                            lastError = NULL,
                            updateDate = :updateDate
                      WHERE gachaRewardSettlementId = :settlementId',
                    [
                        'userId' => $userId,
                        'sourceType' => $sourceType,
                        'sourceId' => $sourceId,
                        'rewardType' => $rewardType,
                        'drawJson' => $drawJson,
                        'updateDate' => date('Y-m-d H:i:s'),
                        'settlementId' => (int) ($row['gachaRewardSettlementId'] ?? 0),
                    ]
                );
            }

            $rewardResult = $this->grantReward($guildId, $userId, $draw, $sourceType, $sourceId);
            $mileageSummary = $this->recordMileage($guildId, $userId, $draw);
            $result = [
                'ok' => true,
                'drawId' => $drawId,
                'rewardType' => $rewardType,
                'reward' => $rewardResult,
                'mileageSummary' => $mileageSummary,
                'settledAt' => date(DateTimeInterface::ATOM),
            ];

            Database::execute(
                'UPDATE tbl_gacha_reward_settlement
                    SET settlementStatus = "settled",
                        resultJson = :resultJson,
                        mileageSummaryJson = :mileageSummaryJson,
                        settledAt = :settledAt,
                        lastError = NULL,
                        updateDate = :updateDate
                  WHERE guildId = :guildId
                    AND drawId = :drawId',
                [
                    'resultJson' => self::encodeJson($result),
                    'mileageSummaryJson' => self::encodeJson($mileageSummary),
                    'settledAt' => date('Y-m-d H:i:s'),
                    'updateDate' => date('Y-m-d H:i:s'),
                    'guildId' => $guildId,
                    'drawId' => $drawId,
                ]
            );

            $this->syncHistory($guildId, $userId, $draw);
            $this->markLiveUpdate($guildId, $drawId, 'gacha_reward_settled', ['rewardType' => $rewardType]);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->markSettlementFailed($guildId, $userId, $drawId, $draw, $sourceType, $sourceId, $rewardType, $exception->getMessage());
            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function enqueueCompletedDraw(
        string $guildId,
        string $userId,
        array $draw,
        string $sourceType = 'gacha_complete',
        ?string $sourceId = null
    ): array {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $drawId = trim((string) ($draw['drawId'] ?? ''));
        if ($guildId === '' || $userId === '' || $drawId === '') {
            throw new InvalidArgumentException('DRAW_SETTLEMENT_TARGET_REQUIRED');
        }

        $sourceType = trim($sourceType) !== '' ? trim($sourceType) : 'gacha_complete';
        $sourceId = trim((string) ($sourceId ?? $drawId)) ?: $drawId;
        $prize = is_array($draw['prize'] ?? null) ? $draw['prize'] : [];
        $rewardType = trim((string) ($prize['type'] ?? 'item')) ?: 'item';
        Database::execute(
            'INSERT INTO tbl_gacha_reward_settlement
                (guildId, userId, drawId, settlementStatus, sourceType, sourceId, rewardType, drawJson, updateDate)
             VALUES
                (:guildId, :userId, :drawId, "pending", :sourceType, :sourceId, :rewardType, :drawJson, :updateDate)
             ON DUPLICATE KEY UPDATE
                userId = VALUES(userId),
                settlementStatus = IF(settlementStatus = "settled", settlementStatus, "pending"),
                sourceType = IF(settlementStatus = "settled", sourceType, VALUES(sourceType)),
                sourceId = IF(settlementStatus = "settled", sourceId, VALUES(sourceId)),
                rewardType = IF(settlementStatus = "settled", rewardType, VALUES(rewardType)),
                drawJson = IF(settlementStatus = "settled", drawJson, VALUES(drawJson)),
                updateDate = VALUES(updateDate)',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'drawId' => $drawId,
                'sourceType' => $sourceType,
                'sourceId' => $sourceId,
                'rewardType' => $rewardType,
                'drawJson' => self::encodeJson($draw),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );

        return $this->settlementForDraw($guildId, $drawId) ?? ['drawId' => $drawId, 'status' => 'pending'];
    }

    /** @return array<string, int> */
    public function processStaleActiveDraws(int $staleActiveMinutes = 5, int $limit = 100): array
    {
        self::ensureSchema();

        $staleActiveMinutes = max(1, $staleActiveMinutes);
        $limit = max(1, min(500, $limit));
        $cutoff = date('Y-m-d H:i:s', time() - ($staleActiveMinutes * 60));
        $rows = Database::fetchAll(
            'SELECT *
               FROM tbl_gacha_pending_draw
              WHERE drawStatus = "active"
                AND COALESCE(updateDate, createDate) <= :cutoff
              ORDER BY COALESCE(updateDate, createDate) ASC, gachaPendingDrawId ASC
              LIMIT ' . $limit,
            ['cutoff' => $cutoff]
        );

        $result = ['checked' => count($rows), 'stored' => 0, 'skipped' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $draw = self::decodeJson((string) ($row['drawJson'] ?? ''));
            if (!$draw || !empty($draw['completedAt']) || !empty($draw['refundedAt'])) {
                $result['skipped']++;
                continue;
            }

            try {
                $this->storeAbandonedDraw(
                    (string) ($row['guildId'] ?? ''),
                    (string) ($row['userId'] ?? ''),
                    $draw,
                    'stale_active_sweep'
                );
                $result['stored']++;
            } catch (Throwable) {
                $result['failed']++;
            }
        }

        return $result;
    }

    /** @return array<string, int> */
    public function retryFailedSettlements(int $limit = 100): array
    {
        self::ensureSchema();

        $limit = max(1, min(500, $limit));
        $rows = Database::fetchAll(
            'SELECT *
               FROM tbl_gacha_reward_settlement
              WHERE settlementStatus = "failed"
              ORDER BY updateDate ASC, gachaRewardSettlementId ASC
              LIMIT ' . $limit
        );

        $result = ['checked' => count($rows), 'settled' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $draw = self::decodeJson((string) ($row['drawJson'] ?? ''));
            if (!$draw) {
                $result['skipped']++;
                continue;
            }

            try {
                $this->settleCompletedDraw(
                    (string) ($row['guildId'] ?? ''),
                    (string) ($row['userId'] ?? ''),
                    $draw,
                    'gacha_reward_retry',
                    (string) ($row['drawId'] ?? '')
                );
                $result['settled']++;
            } catch (Throwable) {
                $result['failed']++;
            }
        }

        return $result;
    }

    /** @return array<string, int> */
    public function processPendingSettlements(int $limit = 100): array
    {
        self::ensureSchema();

        $limit = max(1, min(500, $limit));
        $rows = Database::fetchAll(
            'SELECT *
               FROM tbl_gacha_reward_settlement
              WHERE settlementStatus = "pending"
              ORDER BY updateDate ASC, gachaRewardSettlementId ASC
              LIMIT ' . $limit
        );

        $result = ['checked' => count($rows), 'settled' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $draw = self::decodeJson((string) ($row['drawJson'] ?? ''));
            if (!$draw) {
                $result['skipped']++;
                continue;
            }

            try {
                $this->settleCompletedDraw(
                    (string) ($row['guildId'] ?? ''),
                    (string) ($row['userId'] ?? ''),
                    $draw,
                    (string) ($row['sourceType'] ?? 'gacha_complete'),
                    (string) ($row['sourceId'] ?? $row['drawId'] ?? '')
                );
                $result['settled']++;
            } catch (Throwable) {
                $result['failed']++;
            }
        }

        return $result;
    }

    /** @return array<string, mixed>|null */
    public function settlementForDraw(string $guildId, string $drawId): ?array
    {
        self::ensureSchema();

        $row = Database::fetch(
            'SELECT *
               FROM tbl_gacha_reward_settlement
              WHERE guildId = :guildId
                AND drawId = :drawId
              LIMIT 1',
            ['guildId' => trim($guildId), 'drawId' => trim($drawId)]
        );
        if (!$row) {
            return null;
        }

        return [
            'drawId' => (string) ($row['drawId'] ?? ''),
            'status' => (string) ($row['settlementStatus'] ?? ''),
            'result' => self::decodeJson((string) ($row['resultJson'] ?? '')),
            'settledAt' => (string) ($row['settledAt'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    private function grantReward(string $guildId, string $userId, array $draw, string $sourceType, string $sourceId): array
    {
        $prize = is_array($draw['prize'] ?? null) ? $draw['prize'] : [];
        $type = trim((string) ($prize['type'] ?? 'item')) ?: 'item';
        $drawId = trim((string) ($draw['drawId'] ?? ''));
        $transactionGroupId = trim((string) ($draw['transactionGroupId'] ?? $drawId)) ?: $drawId;

        if ($type === 'role') {
            $grant = class_exists('GachaRoleGrantService')
                ? (new GachaRoleGrantService())->queueForDraw($guildId, $userId, $draw)
                : ['ok' => false, 'status' => 'role_service_unavailable'];
            return ['type' => 'role', 'grant' => $grant];
        }

        if (in_array($type, ['currency', 'wallet', 'unit'], true)) {
            $unitCode = trim((string) ($prize['unitCode'] ?? $prize['currency'] ?? 'coin')) ?: 'coin';
            $amount = max(1, (int) ($prize['amount'] ?? $prize['quantity'] ?? 1));
            $wallet = ShopUnitService::adjustWalletBalance(
                $guildId,
                $userId,
                $unitCode,
                $amount,
                'credit',
                $sourceType,
                $sourceId,
                ['drawId' => $drawId, 'prize' => $prize],
                ['transactionGroupId' => $transactionGroupId, 'targetUserId' => $userId]
            );
            return ['type' => 'currency', 'wallet' => $wallet];
        }

        $itemCode = $this->itemCodeForPrize($prize);
        $quantity = max(1, (int) ($prize['quantity'] ?? 1));
        $item = ItemCatalogService::grantItem(
            $guildId,
            $userId,
            $itemCode,
            $quantity,
            [
                'itemName' => trim((string) ($prize['name'] ?? 'Gacha Prize')) ?: 'Gacha Prize',
                'itemType' => 'gacha_prize',
                'image' => trim((string) ($prize['image'] ?? '')),
                'effectType' => null,
                'metadata' => [
                    'source' => 'gacha',
                    'drawId' => $drawId,
                    'tierId' => (string) ($prize['tierId'] ?? $draw['lockedType'] ?? ''),
                    'prizeId' => (string) ($prize['id'] ?? ''),
                ],
            ],
            $sourceType,
            $sourceId,
            ['drawId' => $drawId, 'prize' => $prize],
            ['transactionGroupId' => $transactionGroupId, 'targetUserId' => $userId]
        );

        return ['type' => 'item', 'item' => $item];
    }

    /** @return array<string, mixed>|null */
    private function recordMileage(string $guildId, string $userId, array $draw): ?array
    {
        if (!class_exists('GachaMileageService')) {
            return null;
        }

        return GachaMileageService::recordCompletedSpin(
            $guildId,
            $userId,
            (string) ($draw['drawId'] ?? ''),
            max(1, (int) ($draw['count'] ?? 1))
        );
    }

    private function itemCodeForPrize(array $prize): string
    {
        $raw = trim((string) ($prize['itemCode'] ?? $prize['id'] ?? $prize['name'] ?? 'gacha_prize'));
        $code = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($raw)) ?? 'gacha_prize';
        $code = trim($code, '_') ?: 'gacha_prize';
        return str_starts_with($code, 'gacha_') ? $code : 'gacha_' . $code;
    }

    /** @return array<string, mixed> */
    private function decorateStoredBallRow(array $row): array
    {
        $draw = self::decodeJson((string) ($row['drawJson'] ?? ''));
        $metadata = self::decodeJson((string) ($row['metadataJson'] ?? ''));
        $tier = is_array($draw['tier'] ?? null) ? $draw['tier'] : [];
        $storedAt = (string) ($metadata['storedAt'] ?? $row['createDate'] ?? '');

        return [
            'storedBallId' => (int) ($row['gachaStoredBallId'] ?? 0),
            'drawId' => (string) ($row['drawId'] ?? ''),
            'status' => (string) ($row['ballStatus'] ?? 'stored'),
            'tierId' => (string) ($draw['lockedType'] ?? $tier['id'] ?? ''),
            'tierName' => (string) ($tier['tier'] ?? $tier['name'] ?? $draw['lockedType'] ?? 'Mystery'),
            'count' => max(1, (int) ($draw['count'] ?? 1)),
            'buttonId' => max(0, (int) ($draw['buttonId'] ?? 0)),
            'currency' => (string) ($draw['currency'] ?? ''),
            'cost' => max(0, (int) ($draw['cost'] ?? 0)),
            'costPerSpin' => max(0, (int) ($draw['costPerSpin'] ?? 0)),
            'usedFreeSpin' => !empty($draw['usedFreeSpin']),
            'freeSpinSource' => (string) ($draw['freeSpinSource'] ?? ''),
            'spinCommittedAt' => (int) ($draw['spinCommittedAt'] ?? $draw['createdAt'] ?? 0),
            'storedAt' => $storedAt,
            'storedReason' => (string) ($metadata['reason'] ?? $draw['storedBallReason'] ?? ''),
            'createdAt' => (string) ($row['createDate'] ?? ''),
            'openedAt' => (string) ($row['openedAt'] ?? ''),
            'settledAt' => (string) ($row['settledAt'] ?? ''),
        ];
    }

    /** @return array<string, mixed>|null */
    private function storedBallRowByDraw(string $guildId, string $drawId): ?array
    {
        return Database::fetch(
            'SELECT *
               FROM tbl_gacha_stored_ball
              WHERE guildId = :guildId
                AND drawId = :drawId
              LIMIT 1',
            ['guildId' => $guildId, 'drawId' => $drawId]
        );
    }

    private function deletePendingDraw(string $guildId, string $userId, string $drawId, string $status): void
    {
        Database::execute(
            'DELETE FROM tbl_gacha_pending_draw
              WHERE guildId = :guildId
                AND userId = :userId
                AND drawId = :drawId
                AND drawStatus = :drawStatus',
            ['guildId' => $guildId, 'userId' => $userId, 'drawId' => $drawId, 'drawStatus' => $status]
        );
    }

    private function markStoredBallFailed(int $storedBallId, string $message): void
    {
        if ($storedBallId <= 0) {
            return;
        }

        try {
            Database::execute(
                'UPDATE tbl_gacha_stored_ball
                    SET ballStatus = "failed",
                        lastError = :lastError,
                        updateDate = :updateDate
                  WHERE gachaStoredBallId = :storedBallId
                    AND ballStatus <> "settled"',
                ['lastError' => $message, 'updateDate' => date('Y-m-d H:i:s'), 'storedBallId' => $storedBallId]
            );
        } catch (Throwable) {
            // Best-effort failure marker.
        }
    }

    private function markSettlementFailed(
        string $guildId,
        string $userId,
        string $drawId,
        array $draw,
        string $sourceType,
        string $sourceId,
        string $rewardType,
        string $message
    ): void {
        try {
            Database::execute(
                'INSERT INTO tbl_gacha_reward_settlement
                    (guildId, userId, drawId, settlementStatus, sourceType, sourceId, rewardType, drawJson, lastError, updateDate)
                 VALUES
                    (:guildId, :userId, :drawId, "failed", :sourceType, :sourceId, :rewardType, :drawJson, :lastError, :updateDate)
                 ON DUPLICATE KEY UPDATE
                    userId = VALUES(userId),
                    settlementStatus = IF(settlementStatus = "settled", settlementStatus, "failed"),
                    sourceType = VALUES(sourceType),
                    sourceId = VALUES(sourceId),
                    rewardType = VALUES(rewardType),
                    drawJson = VALUES(drawJson),
                    lastError = IF(settlementStatus = "settled", lastError, VALUES(lastError)),
                    updateDate = VALUES(updateDate)',
                [
                    'guildId' => $guildId,
                    'userId' => $userId,
                    'drawId' => $drawId,
                    'sourceType' => $sourceType,
                    'sourceId' => $sourceId,
                    'rewardType' => $rewardType,
                    'drawJson' => self::encodeJson($draw),
                    'lastError' => $message,
                    'updateDate' => date('Y-m-d H:i:s'),
                ]
            );
        } catch (Throwable) {
            // Settlement failure state must not hide the original exception.
        }
    }

    private function syncHistory(string $guildId, string $userId, array $draw): void
    {
        if (!class_exists('GachaSpinHistoryService')) {
            return;
        }

        try {
            GachaSpinHistoryService::syncFromDraw($guildId, $userId, $draw);
        } catch (Throwable) {
            // History is diagnostic; settlement should remain authoritative.
        }
    }

    private function markLiveUpdate(string $guildId, string $drawId, string $type, array $metadata = []): void
    {
        if (!class_exists('LiveUpdateService')) {
            return;
        }

        try {
            LiveUpdateService::mark(
                ['gacha_report'],
                $type,
                'gacha_draw',
                $drawId !== '' ? $drawId : null,
                $metadata,
                $guildId,
                'gacha_reward',
                $drawId !== '' ? $drawId : null
            );
        } catch (Throwable) {
            // Live updates are best-effort.
        }
    }

    private static function ensurePendingDrawSchema(): void
    {
        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_pending_draw (
                gachaPendingDrawId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                drawId varchar(64) NOT NULL,
                drawStatus varchar(40) NOT NULL DEFAULT "active",
                drawJson longtext NOT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaPendingDrawId),
                UNIQUE KEY uq_tbl_gacha_pending_draw_draw (drawId),
                KEY idx_tbl_gacha_pending_draw_user (guildId, userId, drawStatus, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return array<string, mixed> */
    private static function decodeJson(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function encodeJson(mixed $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '{}';
    }
}
