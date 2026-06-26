<?php

declare(strict_types=1);

final class GachaFreeSpinService
{
    private static bool $schemaReady = false;

    public function payload(string $guildId, string $userId, bool $sync = true): array
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            return $this->emptyPayload();
        }

        self::ensureSchema();

        if ($sync) {
            $this->syncEarned($guildId, $userId);
        }

        $this->expireStaleGrantedEvents($guildId, $userId);
        $this->applyDebt($guildId, $userId);

        $count = $this->availableCount($guildId, $userId);
        $next = $this->availableEventRows($guildId, $userId)[0] ?? null;
        $metadata = $next ? (json_decode((string) ($next['metadataJson'] ?? '{}'), true) ?: []) : [];
        $rewardDate = trim((string) ($metadata['date'] ?? ''));
        if ($rewardDate === '') {
            $rewardDate = date('Y-m-d');
        }

        return [
            'available' => $count,
            'canUse' => $count > 0,
            'label' => $count > 1 ? ('สุ่มฟรี ' . number_format($count) . ' ครั้ง') : ($count > 0 ? 'สุ่มฟรี 1 ครั้ง' : ''),
            'source' => (string) ($next['ruleCode'] ?? ''),
            'sourceName' => (string) ($next['ruleName'] ?? ''),
            'earnedAt' => (string) ($next['createDate'] ?? ''),
            'expiresAt' => $rewardDate . ' 23:59:59',
            'progress' => [
                'date' => $rewardDate,
                'voiceSeconds' => max(0, (int) ($metadata['voiceSeconds'] ?? 0)),
                'segment' => max(0, (int) ($metadata['segment'] ?? 0)),
            ],
        ];
    }

    public function syncEarned(string $guildId, string $userId): array
    {
        $earnService = new EarnService();
        $dates = array_values(array_unique([
            date('Y-m-d', strtotime('-1 day')),
            date('Y-m-d'),
        ]));

        $combined = [
            'date' => $dates[count($dates) - 1] ?? date('Y-m-d'),
            'userId' => $userId,
            'rules' => 0,
            'granted' => 0,
            'skipped' => 0,
            'byRule' => [],
        ];

        foreach ($dates as $date) {
            $result = $earnService->syncUser($guildId, $userId, $date, ['earn_voice_10min_free_spin']);
            $combined['rules'] = max($combined['rules'], (int) ($result['rules'] ?? 0));
            $combined['granted'] += (int) ($result['granted'] ?? 0);
            $combined['skipped'] += (int) ($result['skipped'] ?? 0);
            foreach ((array) ($result['byRule'] ?? []) as $ruleCode => $count) {
                $combined['byRule'][(string) $ruleCode] = (int) ($combined['byRule'][(string) $ruleCode] ?? 0) + (int) $count;
            }
        }

        return $combined;
    }

    public function canUseForButton(array $button, int $count): bool
    {
        return $count === 1 && strtolower(trim((string) ($button['currency'] ?? ''))) === 'ticket';
    }

    public function consume(string $guildId, string $userId, string $drawId, int $buttonId): ?array
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        $drawId = trim($drawId);
        if ($guildId === '' || $userId === '' || $drawId === '') {
            return null;
        }

        self::ensureSchema();
        $this->syncEarned($guildId, $userId);
        $this->expireStaleGrantedEvents($guildId, $userId);

        $pdo = Database::pdo();
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $this->applyDebt($guildId, $userId);

            $row = $this->availableEventRows($guildId, $userId, 1, true)[0] ?? null;
            if (!$row) {
                if ($startedTransaction) {
                    $pdo->commit();
                }
                return null;
            }

            $metadata = json_decode((string) ($row['metadataJson'] ?? '{}'), true) ?: [];
            $metadata['consumedAt'] = date('Y-m-d H:i:s');
            $metadata['consumedDrawId'] = $drawId;
            $metadata['consumedButtonId'] = $buttonId;
            $metadata['freeSpinStatus'] = 'consumed';

            Database::execute(
                'UPDATE tbl_reward_event
                    SET rewardStatus = "consumed",
                        metadataJson = :metadataJson
                  WHERE rewardEventId = :rewardEventId
                    AND rewardStatus = "granted"',
                [
                    'rewardEventId' => (int) $row['rewardEventId'],
                    'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]
            );

            if ($startedTransaction) {
                $pdo->commit();
            }

            return [
                'rewardEventId' => (int) $row['rewardEventId'],
                'ruleCode' => (string) ($row['ruleCode'] ?? ''),
                'ruleName' => (string) ($row['ruleName'] ?? ''),
                'metadata' => $metadata,
            ];
        } catch (Throwable $exception) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function restoreForDraw(string $guildId, string $userId, string $drawId): bool
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        $drawId = trim($drawId);
        if ($guildId === '' || $userId === '' || $drawId === '') {
            return false;
        }

        self::ensureSchema();

        $rows = Database::fetchAll(
            'SELECT re.rewardEventId, re.metadataJson
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
              WHERE re.guildId = :guildId
                AND re.userId = :userId
                AND re.rewardStatus = "consumed"
                AND ' . self::freeSpinSql('re', 'rr') . ' > 0
              ORDER BY re.rewardEventId DESC
              LIMIT 20',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        $restored = false;
        foreach ($rows as $row) {
            $metadata = json_decode((string) ($row['metadataJson'] ?? '{}'), true) ?: [];
            if ((string) ($metadata['consumedDrawId'] ?? '') !== $drawId) {
                continue;
            }
            $metadata['freeSpinStatus'] = 'restored';
            $metadata['restoredAt'] = date('Y-m-d H:i:s');
            unset($metadata['consumedDrawId'], $metadata['consumedAt'], $metadata['consumedButtonId']);
            Database::execute(
                'UPDATE tbl_reward_event
                    SET rewardStatus = "granted",
                        metadataJson = :metadataJson
                  WHERE rewardEventId = :rewardEventId',
                [
                    'rewardEventId' => (int) $row['rewardEventId'],
                    'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]
            );
            $restored = true;
        }

        return $restored;
    }

    public function addDebt(string $guildId, string $userId, int $amount, string $sourceType, string $sourceId, array $metadata = []): int
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        $sourceType = trim($sourceType);
        $sourceId = trim($sourceId);
        $amount = max(0, $amount);
        if ($guildId === '' || $userId === '' || $amount <= 0) {
            return 0;
        }

        self::ensureSchema();

        $debtId = Database::insert('tbl_gacha_free_spin_debt', [
            'guildId' => $guildId,
            'userId' => $userId,
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
            'amountOriginal' => $amount,
            'amountRemaining' => $amount,
            'debtStatus' => 'open',
            'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'createDate' => date('Y-m-d H:i:s'),
            'updateDate' => date('Y-m-d H:i:s'),
        ]);

        $this->applyDebt($guildId, $userId);

        return $debtId;
    }

    private static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_free_spin_debt (
                freeSpinDebtId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                sourceType varchar(64) NOT NULL DEFAULT "",
                sourceId varchar(128) NOT NULL DEFAULT "",
                amountOriginal int NOT NULL DEFAULT 0,
                amountRemaining int NOT NULL DEFAULT 0,
                debtStatus varchar(32) NOT NULL DEFAULT "open",
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (freeSpinDebtId),
                KEY idx_tbl_gacha_free_spin_debt_user (guildId, userId, debtStatus),
                KEY idx_tbl_gacha_free_spin_debt_source (sourceType, sourceId)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    /** @return array<int, array<string, mixed>> */
    private function availableEventRows(string $guildId, string $userId, int $limit = 1, bool $forUpdate = false): array
    {
        $limit = max(1, min(200, $limit));
        $sql = 'SELECT re.*, rr.ruleCode, rr.ruleName
                  FROM tbl_reward_event re
            INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
                 WHERE re.guildId = :guildId
                   AND re.userId = :userId
                   AND re.rewardStatus = "granted"
                   AND rr.isActive = 1
                   AND ' . $this->availabilitySql() . '
                   AND ' . self::freeSpinSql('re', 'rr') . ' > 0
              ORDER BY re.createDate ASC, re.rewardEventId ASC
                 LIMIT ' . $limit;
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        return Database::fetchAll($sql, ['guildId' => $guildId, 'userId' => $userId]);
    }

    private function availableCount(string $guildId, string $userId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS total
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
              WHERE re.guildId = :guildId
                AND re.userId = :userId
                AND re.rewardStatus = "granted"
                AND rr.isActive = 1
                AND ' . $this->availabilitySql() . '
                AND ' . self::freeSpinSql('re', 'rr') . ' > 0',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return max(0, (int) ($row['total'] ?? 0));
    }

    private function applyDebt(string $guildId, string $userId): void
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            return;
        }

        self::ensureSchema();

        $pdo = Database::pdo();
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $debts = Database::fetchAll(
                'SELECT freeSpinDebtId, amountRemaining, metadataJson
                   FROM tbl_gacha_free_spin_debt
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND debtStatus = "open"
                    AND amountRemaining > 0
                  ORDER BY freeSpinDebtId ASC
                  FOR UPDATE',
                ['guildId' => $guildId, 'userId' => $userId]
            );

            if (!$debts) {
                if ($startedTransaction) {
                    $pdo->commit();
                }
                return;
            }

            $available = $this->availableEventRows($guildId, $userId, 200, true);
            $availableIndex = 0;
            $appliedAt = date('Y-m-d H:i:s');

            foreach ($debts as $debt) {
                $remaining = max(0, (int) ($debt['amountRemaining'] ?? 0));
                while ($remaining > 0 && isset($available[$availableIndex])) {
                    $row = $available[$availableIndex];
                    $availableIndex += 1;

                    $metadata = json_decode((string) ($row['metadataJson'] ?? '{}'), true);
                    $metadata = is_array($metadata) ? $metadata : [];
                    $metadata['freeSpinStatus'] = 'debt_offset';
                    $metadata['freeSpinDebtId'] = (int) ($debt['freeSpinDebtId'] ?? 0);
                    $metadata['debtAppliedAt'] = $appliedAt;

                    Database::execute(
                        'UPDATE tbl_reward_event
                            SET rewardStatus = "consumed",
                                metadataJson = :metadataJson
                          WHERE rewardEventId = :rewardEventId
                            AND rewardStatus = "granted"',
                        [
                            'rewardEventId' => (int) $row['rewardEventId'],
                            'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        ]
                    );

                    $remaining -= 1;
                }

                Database::execute(
                    'UPDATE tbl_gacha_free_spin_debt
                        SET amountRemaining = :amountRemaining,
                            debtStatus = :debtStatus,
                            updateDate = :updateDate
                      WHERE freeSpinDebtId = :freeSpinDebtId',
                    [
                        'freeSpinDebtId' => (int) ($debt['freeSpinDebtId'] ?? 0),
                        'amountRemaining' => $remaining,
                        'debtStatus' => $remaining > 0 ? 'open' : 'settled',
                        'updateDate' => $appliedAt,
                    ]
                );
            }

            if ($startedTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function expireStaleGrantedEvents(string $guildId, string $userId): void
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            return;
        }

        $rows = Database::fetchAll(
            'SELECT re.rewardEventId, re.metadataJson
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
              WHERE re.guildId = :guildId
                AND re.userId = :userId
                AND re.rewardStatus = "granted"
                AND rr.isActive = 1
                AND re.sourceType <> "earn_manual"
                AND rr.ruleCode LIKE "earn_%"
                AND ' . self::freeSpinSql('re', 'rr') . ' > 0
                AND ' . self::rewardDateSql('re') . ' < CURDATE()',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        if (!$rows) {
            return;
        }

        $expiredAt = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $metadata = json_decode((string) ($row['metadataJson'] ?? '{}'), true);
            $metadata = is_array($metadata) ? $metadata : [];
            $metadata['freeSpinStatus'] = 'expired';
            $metadata['expiredAt'] = $expiredAt;

            Database::execute(
                'UPDATE tbl_reward_event
                    SET rewardStatus = "expired",
                        metadataJson = :metadataJson
                  WHERE rewardEventId = :rewardEventId
                    AND rewardStatus = "granted"',
                [
                    'rewardEventId' => (int) ($row['rewardEventId'] ?? 0),
                    'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]
            );
        }
    }

    private function availabilitySql(): string
    {
        return '(
            (rr.ruleCode LIKE "earn_%" AND ' . self::rewardDateSql('re') . ' = CURDATE())
            OR re.sourceType = "earn_manual"
        )';
    }

    private static function freeSpinSql(string $eventAlias, string $ruleAlias): string
    {
        return 'CAST(COALESCE(
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(' . $eventAlias . '.metadataJson, "$.reward.gachaFreeSpin")), ""),
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(' . $ruleAlias . '.rewardJson, "$.gachaFreeSpin")), ""),
            "0"
        ) AS SIGNED)';
    }

    private static function rewardDateSql(string $eventAlias): string
    {
        return 'COALESCE(
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(' . $eventAlias . '.metadataJson, "$.date")), ""),
            DATE(' . $eventAlias . '.createDate)
        )';
    }

    private function emptyPayload(): array
    {
        return [
            'available' => 0,
            'canUse' => false,
            'label' => '',
            'source' => '',
            'sourceName' => '',
            'earnedAt' => '',
            'expiresAt' => '',
            'progress' => [
                'date' => date('Y-m-d'),
                'voiceSeconds' => 0,
                'segment' => 0,
            ],
        ];
    }
}
