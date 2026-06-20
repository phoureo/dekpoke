<?php

declare(strict_types=1);

final class GachaFreeSpinService
{
    public function payload(string $guildId, string $userId, bool $sync = true): array
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            return $this->emptyPayload();
        }

        if ($sync) {
            $this->syncEarned($guildId, $userId);
        }

        $count = $this->availableCount($guildId, $userId);
        $next = $this->availableRows($guildId, $userId)[0] ?? null;
        $metadata = $next ? (json_decode((string) ($next['metadataJson'] ?? '{}'), true) ?: []) : [];

        return [
            'available' => $count,
            'canUse' => $count > 0,
            'label' => $count > 1 ? ('สุ่มฟรี ' . number_format($count) . ' ครั้ง') : ($count > 0 ? 'สุ่มฟรี 1 ครั้ง' : ''),
            'source' => (string) ($next['ruleCode'] ?? ''),
            'sourceName' => (string) ($next['ruleName'] ?? ''),
            'earnedAt' => (string) ($next['createDate'] ?? ''),
            'expiresAt' => date('Y-m-d 23:59:59'),
            'progress' => [
                'date' => (string) ($metadata['date'] ?? date('Y-m-d')),
                'voiceSeconds' => max(0, (int) ($metadata['voiceSeconds'] ?? 0)),
                'segment' => max(0, (int) ($metadata['segment'] ?? 0)),
            ],
        ];
    }

    public function syncEarned(string $guildId, string $userId): array
    {
        return (new EarnService())->syncUser($guildId, $userId, date('Y-m-d'), ['earn_voice_10min_free_spin']);
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

        $pdo = Database::pdo();
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $row = $this->availableRows($guildId, $userId, true)[0] ?? null;
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

        $rows = Database::fetchAll(
            'SELECT re.rewardEventId, re.metadataJson
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
              WHERE re.guildId = :guildId
                AND re.userId = :userId
                AND re.rewardStatus = "consumed"
                AND rr.ruleCode LIKE "earn_%"
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

    /** @return array<int, array<string, mixed>> */
    private function availableRows(string $guildId, string $userId, bool $forUpdate = false): array
    {
        $sql = 'SELECT re.*, rr.ruleCode, rr.ruleName
                  FROM tbl_reward_event re
            INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
                 WHERE re.guildId = :guildId
                   AND re.userId = :userId
                   AND re.rewardStatus = "granted"
                   AND rr.isActive = 1
                   AND rr.ruleCode LIKE "earn_%"
                   AND DATE(re.createDate) = CURDATE()
                   AND ' . self::freeSpinSql('re', 'rr') . ' > 0
              ORDER BY re.createDate ASC, re.rewardEventId ASC
                 LIMIT 1';
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
                AND rr.ruleCode LIKE "earn_%"
                AND DATE(re.createDate) = CURDATE()
                AND ' . self::freeSpinSql('re', 'rr') . ' > 0',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return max(0, (int) ($row['total'] ?? 0));
    }

    private static function freeSpinSql(string $eventAlias, string $ruleAlias): string
    {
        return 'CAST(COALESCE(
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(' . $eventAlias . '.metadataJson, "$.reward.gachaFreeSpin")), ""),
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(' . $ruleAlias . '.rewardJson, "$.gachaFreeSpin")), ""),
            "0"
        ) AS SIGNED)';
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
