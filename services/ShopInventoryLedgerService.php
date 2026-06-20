<?php

declare(strict_types=1);

final class ShopInventoryLedgerService
{
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_shop_inventory_ledger (
                shopInventoryLedgerId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                shopInventoryId bigint unsigned NOT NULL,
                shopItemId bigint unsigned NOT NULL,
                quantityDelta int NOT NULL,
                quantityBefore int DEFAULT NULL,
                quantityAfter int DEFAULT NULL,
                ledgerType varchar(80) NOT NULL,
                sourceType varchar(80) DEFAULT NULL,
                sourceId varchar(120) DEFAULT NULL,
                transactionGroupId varchar(120) DEFAULT NULL,
                actorUserId varchar(32) DEFAULT NULL,
                targetUserId varchar(32) DEFAULT NULL,
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (shopInventoryLedgerId),
                KEY idx_tbl_shop_inventory_ledger_user (guildId, userId, createDate),
                KEY idx_tbl_shop_inventory_ledger_item (shopItemId, createDate),
                KEY idx_tbl_shop_inventory_ledger_inventory (shopInventoryId, createDate),
                KEY idx_tbl_shop_inventory_ledger_trace (transactionGroupId, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    public static function record(array $entry): int
    {
        self::ensureSchema();

        $guildId = trim((string) ($entry['guildId'] ?? ''));
        $userId = trim((string) ($entry['userId'] ?? ''));
        $shopInventoryId = max(0, (int) ($entry['shopInventoryId'] ?? 0));
        $shopItemId = max(0, (int) ($entry['shopItemId'] ?? 0));
        $quantityDelta = (int) ($entry['quantityDelta'] ?? 0);
        $ledgerType = trim((string) ($entry['ledgerType'] ?? ''));

        if ($guildId === '' || $userId === '' || $shopInventoryId <= 0 || $shopItemId <= 0 || $quantityDelta === 0 || $ledgerType === '') {
            return 0;
        }

        $metadata = $entry['metadata'] ?? ($entry['metadataJson'] ?? null);
        if (!is_string($metadata)) {
            $metadata = json_encode(
                is_array($metadata) ? $metadata : [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        $quantityBefore = self::nullableInt($entry['quantityBefore'] ?? null);
        $quantityAfter = self::nullableInt($entry['quantityAfter'] ?? null);
        if ($quantityBefore !== null && $quantityAfter === null) {
            $quantityAfter = $quantityBefore + $quantityDelta;
        }

        return Database::insert('tbl_shop_inventory_ledger', [
            'guildId' => $guildId,
            'userId' => $userId,
            'shopInventoryId' => $shopInventoryId,
            'shopItemId' => $shopItemId,
            'quantityDelta' => $quantityDelta,
            'quantityBefore' => $quantityBefore,
            'quantityAfter' => $quantityAfter,
            'ledgerType' => $ledgerType,
            'sourceType' => self::nullableString($entry['sourceType'] ?? null),
            'sourceId' => self::nullableString($entry['sourceId'] ?? null),
            'transactionGroupId' => self::nullableString($entry['transactionGroupId'] ?? null),
            'actorUserId' => self::nullableString($entry['actorUserId'] ?? null),
            'targetUserId' => self::nullableString($entry['targetUserId'] ?? null),
            'metadataJson' => $metadata,
            'createDate' => self::timestamp($entry['createDate'] ?? null),
        ]);
    }

    public static function firstLedgerDate(string $guildId = ''): ?string
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $params = [];
        $sql = 'SELECT MIN(createDate) AS firstDate FROM tbl_shop_inventory_ledger';
        if ($guildId !== '') {
            $sql .= ' WHERE guildId = :guildId';
            $params['guildId'] = $guildId;
        }

        $row = Database::fetch($sql, $params);
        $firstDate = trim((string) ($row['firstDate'] ?? ''));

        return $firstDate !== '' ? $firstDate : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : null;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private static function timestamp(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : date('Y-m-d H:i:s');
    }
}
