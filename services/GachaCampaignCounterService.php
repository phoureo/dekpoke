<?php

declare(strict_types=1);

final class GachaCampaignCounterService
{
    public const DEFAULT_CAMPAIGN_CODE = 'special_5000';

    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_campaign_counter (
                campaignCounterId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                campaignCode varchar(120) NOT NULL,
                `currentValue` int unsigned NOT NULL DEFAULT 0,
                `minValue` int unsigned NOT NULL DEFAULT 0,
                `maxValue` int unsigned NOT NULL DEFAULT 5000,
                isEnabled tinyint(1) NOT NULL DEFAULT 1,
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (campaignCounterId),
                UNIQUE KEY uq_tbl_gacha_campaign_counter_code (guildId, campaignCode),
                KEY idx_tbl_gacha_campaign_counter_enabled (guildId, isEnabled)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_campaign_counter_ledger (
                campaignCounterLedgerId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                campaignCode varchar(120) NOT NULL,
                drawId varchar(64) NOT NULL,
                amountDelta int NOT NULL DEFAULT 0,
                `valueBefore` int unsigned NOT NULL DEFAULT 0,
                `valueAfter` int unsigned NOT NULL DEFAULT 0,
                sourceType varchar(80) NOT NULL DEFAULT "gacha_spin",
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (campaignCounterLedgerId),
                UNIQUE KEY uq_tbl_gacha_campaign_counter_ledger_draw (guildId, campaignCode, drawId),
                KEY idx_tbl_gacha_campaign_counter_ledger_counter (guildId, campaignCode, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    public static function status(string $guildId, string $campaignCode = self::DEFAULT_CAMPAIGN_CODE): array
    {
        self::ensureSchema();
        self::ensureDefaultRow($guildId, $campaignCode);

        $row = Database::fetch(
            'SELECT * FROM tbl_gacha_campaign_counter WHERE guildId = :guildId AND campaignCode = :campaignCode',
            ['guildId' => self::cleanGuildId($guildId), 'campaignCode' => self::cleanCampaignCode($campaignCode)]
        );

        return self::payload($row ?: []);
    }

    public static function incrementForSpin(
        string $guildId,
        string $drawId,
        int $amount,
        array $metadata = [],
        string $campaignCode = self::DEFAULT_CAMPAIGN_CODE
    ): array {
        self::ensureSchema();

        $guildId = self::cleanGuildId($guildId);
        $campaignCode = self::cleanCampaignCode($campaignCode);
        $drawId = trim($drawId);
        $amount = max(0, $amount);

        if ($drawId === '') {
            throw new RuntimeException('Missing gacha draw id for campaign counter.');
        }

        self::ensureDefaultRow($guildId, $campaignCode);

        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $existing = Database::fetch(
                'SELECT amountDelta, `valueBefore`, `valueAfter`
                   FROM tbl_gacha_campaign_counter_ledger
                  WHERE guildId = :guildId AND campaignCode = :campaignCode AND drawId = :drawId
                  FOR UPDATE',
                ['guildId' => $guildId, 'campaignCode' => $campaignCode, 'drawId' => $drawId]
            );

            if ($existing) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return [
                    'enabled' => true,
                    'before' => (int) $existing['valueBefore'],
                    'after' => (int) $existing['valueAfter'],
                    'delta' => (int) $existing['amountDelta'],
                    'current' => (int) $existing['valueAfter'],
                    'max' => 5000,
                    'displayValue' => self::displayValue((int) $existing['valueAfter']),
                ];
            }

            $row = Database::fetch(
                'SELECT * FROM tbl_gacha_campaign_counter
                  WHERE guildId = :guildId AND campaignCode = :campaignCode
                  FOR UPDATE',
                ['guildId' => $guildId, 'campaignCode' => $campaignCode]
            );

            if (!$row) {
                throw new RuntimeException('Campaign counter row was not created.');
            }

            $before = max((int) $row['minValue'], (int) $row['currentValue']);
            $max = max($before, (int) $row['maxValue']);
            $enabled = !empty($row['isEnabled']);

            if (!$enabled || $amount <= 0 || $before >= $max) {
                $after = $before;
                $delta = 0;
            } else {
                $after = min($max, $before + $amount);
                $delta = $after - $before;
            }

            Database::execute(
                'UPDATE tbl_gacha_campaign_counter
                    SET `currentValue` = :currentValue, updateDate = :updateDate
                  WHERE campaignCounterId = :campaignCounterId',
                [
                    'currentValue' => $after,
                    'updateDate' => date('Y-m-d H:i:s'),
                    'campaignCounterId' => (int) $row['campaignCounterId'],
                ]
            );

            Database::execute(
                'INSERT INTO tbl_gacha_campaign_counter_ledger
                    (guildId, campaignCode, drawId, amountDelta, `valueBefore`, `valueAfter`, sourceType, metadataJson)
                 VALUES
                    (:guildId, :campaignCode, :drawId, :amountDelta, :valueBefore, :valueAfter, :sourceType, :metadataJson)',
                [
                    'guildId' => $guildId,
                    'campaignCode' => $campaignCode,
                    'drawId' => $drawId,
                    'amountDelta' => $delta,
                    'valueBefore' => $before,
                    'valueAfter' => $after,
                    'sourceType' => 'gacha_spin',
                    'metadataJson' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'enabled' => $enabled,
                'before' => $before,
                'after' => $after,
                'delta' => $delta,
                'current' => $after,
                'max' => $max,
                'displayValue' => self::displayValue($after),
            ];
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    public static function rollbackSpin(
        string $guildId,
        string $drawId,
        string $campaignCode = self::DEFAULT_CAMPAIGN_CODE
    ): array {
        self::ensureSchema();

        $guildId = self::cleanGuildId($guildId);
        $campaignCode = self::cleanCampaignCode($campaignCode);
        $drawId = trim($drawId);

        if ($drawId === '') {
            throw new RuntimeException('Missing gacha draw id for campaign counter rollback.');
        }

        self::ensureDefaultRow($guildId, $campaignCode);

        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $ledger = Database::fetch(
                'SELECT amountDelta
                   FROM tbl_gacha_campaign_counter_ledger
                  WHERE guildId = :guildId AND campaignCode = :campaignCode AND drawId = :drawId
                  FOR UPDATE',
                ['guildId' => $guildId, 'campaignCode' => $campaignCode, 'drawId' => $drawId]
            );

            if (!$ledger) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return self::status($guildId, $campaignCode);
            }

            $row = Database::fetch(
                'SELECT * FROM tbl_gacha_campaign_counter
                  WHERE guildId = :guildId AND campaignCode = :campaignCode
                  FOR UPDATE',
                ['guildId' => $guildId, 'campaignCode' => $campaignCode]
            );

            if (!$row) {
                throw new RuntimeException('Campaign counter row was not created.');
            }

            $delta = max(0, (int) $ledger['amountDelta']);
            $current = max((int) $row['minValue'], (int) $row['currentValue']);
            $next = max((int) $row['minValue'], $current - $delta);

            Database::execute(
                'UPDATE tbl_gacha_campaign_counter
                    SET `currentValue` = :currentValue, updateDate = :updateDate
                  WHERE campaignCounterId = :campaignCounterId',
                [
                    'currentValue' => $next,
                    'updateDate' => date('Y-m-d H:i:s'),
                    'campaignCounterId' => (int) $row['campaignCounterId'],
                ]
            );

            Database::execute(
                'DELETE FROM tbl_gacha_campaign_counter_ledger
                  WHERE guildId = :guildId AND campaignCode = :campaignCode AND drawId = :drawId',
                ['guildId' => $guildId, 'campaignCode' => $campaignCode, 'drawId' => $drawId]
            );

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'enabled' => !empty($row['isEnabled']),
                'before' => $current,
                'after' => $next,
                'delta' => -$delta,
                'current' => $next,
                'max' => max($next, (int) $row['maxValue']),
                'displayValue' => self::displayValue($next),
            ];
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    private static function ensureDefaultRow(string $guildId, string $campaignCode): void
    {
        Database::execute(
            'INSERT INTO tbl_gacha_campaign_counter
                (guildId, campaignCode, `currentValue`, `minValue`, `maxValue`, isEnabled, metadataJson, updateDate)
             VALUES
                (:guildId, :campaignCode, 0, 0, 5000, 1, :metadataJson, :updateDate)
             ON DUPLICATE KEY UPDATE campaignCode = campaignCode',
            [
                'guildId' => self::cleanGuildId($guildId),
                'campaignCode' => self::cleanCampaignCode($campaignCode),
                'metadataJson' => json_encode(['label' => 'Special 5000 Counter'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private static function payload(array $row): array
    {
        $current = max((int) ($row['minValue'] ?? 0), (int) ($row['currentValue'] ?? 0));
        $max = max($current, (int) ($row['maxValue'] ?? 5000));
        $current = min($current, $max);

        return [
            'enabled' => !empty($row['isEnabled']),
            'campaignCode' => (string) ($row['campaignCode'] ?? self::DEFAULT_CAMPAIGN_CODE),
            'current' => $current,
            'min' => (int) ($row['minValue'] ?? 0),
            'max' => $max,
            'displayValue' => self::displayValue($current),
        ];
    }

    private static function displayValue(int $value): string
    {
        return str_pad((string) max(0, min(5000, $value)), 4, '0', STR_PAD_LEFT);
    }

    private static function cleanGuildId(string $guildId): string
    {
        $guildId = trim($guildId);
        return $guildId !== '' ? $guildId : 'local';
    }

    private static function cleanCampaignCode(string $campaignCode): string
    {
        $campaignCode = preg_replace('/[^a-z0-9_\\-]/i', '', trim($campaignCode)) ?: self::DEFAULT_CAMPAIGN_CODE;
        return substr($campaignCode, 0, 120);
    }
}
