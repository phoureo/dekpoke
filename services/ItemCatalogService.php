<?php

declare(strict_types=1);

final class ItemCatalogService
{
    public static function ensureSchema(): void
    {
        ShopUnitService::ensureSchema();
        ShopInventoryLedgerService::ensureSchema();
    }

    /** @return array<string, mixed>|null */
    public static function findByCode(string $itemCode): ?array
    {
        self::ensureSchema();

        $row = Database::fetch(
            'SELECT *
               FROM tbl_shop_item
              WHERE itemCode = :itemCode
              LIMIT 1',
            ['itemCode' => self::normalizeItemCode($itemCode)]
        );

        return $row ? self::decorateItemRow($row) : null;
    }

    /** @return array<string, mixed>|null */
    public static function findById(int $shopItemId): ?array
    {
        self::ensureSchema();
        if ($shopItemId <= 0) {
            return null;
        }

        $row = Database::fetch(
            'SELECT *
               FROM tbl_shop_item
              WHERE shopItemId = :shopItemId
              LIMIT 1',
            ['shopItemId' => $shopItemId]
        );

        return $row ? self::decorateItemRow($row) : null;
    }

    /** @return array<string, mixed> */
    public static function upsertItem(array $item): array
    {
        self::ensureSchema();

        $itemCode = self::normalizeItemCode((string) ($item['itemCode'] ?? ''));
        if ($itemCode === '') {
            throw new InvalidArgumentException('ITEM_CODE_REQUIRED');
        }

        $itemName = trim((string) ($item['itemName'] ?? $itemCode)) ?: $itemCode;
        $itemType = self::normalizeItemType((string) ($item['itemType'] ?? 'item'));
        $updateDate = date('Y-m-d H:i:s');
        $effectPayload = $item['effectPayload'] ?? ($item['effectPayloadJson'] ?? []);
        if (!is_string($effectPayload)) {
            $effectPayload = json_encode(
                is_array($effectPayload) ? $effectPayload : [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
        $metadata = $item['metadata'] ?? ($item['metadataJson'] ?? []);
        if (!is_string($metadata)) {
            $metadata = json_encode(
                is_array($metadata) ? $metadata : [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        Database::execute(
            'INSERT INTO tbl_shop_item (
                itemCode,
                itemName,
                itemType,
                image,
                effectType,
                effectPayloadJson,
                metadataJson,
                isActive,
                updateDate
            ) VALUES (
                :itemCode,
                :itemName,
                :itemType,
                :image,
                :effectType,
                :effectPayloadJson,
                :metadataJson,
                :isActive,
                :updateDate
            )
            ON DUPLICATE KEY UPDATE
                itemName = VALUES(itemName),
                itemType = VALUES(itemType),
                image = VALUES(image),
                effectType = VALUES(effectType),
                effectPayloadJson = VALUES(effectPayloadJson),
                metadataJson = VALUES(metadataJson),
                isActive = VALUES(isActive),
                updateDate = VALUES(updateDate)',
            [
                'itemCode' => $itemCode,
                'itemName' => $itemName,
                'itemType' => $itemType,
                'image' => self::nullableString($item['image'] ?? null),
                'effectType' => self::nullableString($item['effectType'] ?? null),
                'effectPayloadJson' => $effectPayload,
                'metadataJson' => $metadata,
                'isActive' => !empty($item['isActive']) ? 1 : 0,
                'updateDate' => $updateDate,
            ]
        );

        return self::findByCode($itemCode) ?? [];
    }

    /** @return array<string, mixed>|null */
    public static function inventoryItemById(string $guildId, string $userId, int $shopInventoryId): ?array
    {
        self::ensureSchema();
        if ($shopInventoryId <= 0 || trim($guildId) === '' || trim($userId) === '') {
            return null;
        }

        $row = Database::fetch(
            'SELECT inv.*, item.itemCode, item.itemName, item.itemType, item.image, item.effectType, item.effectPayloadJson, item.metadataJson AS itemMetadataJson
               FROM tbl_shop_inventory inv
         INNER JOIN tbl_shop_item item ON item.shopItemId = inv.shopItemId
              WHERE inv.guildId = :guildId
                AND inv.userId = :userId
                AND inv.shopInventoryId = :shopInventoryId
              LIMIT 1',
            [
                'guildId' => trim($guildId),
                'userId' => trim($userId),
                'shopInventoryId' => $shopInventoryId,
            ]
        );

        return $row ? self::decorateInventoryRow($row) : null;
    }

    /** @return array<string, mixed> */
    public static function grantItem(
        string $guildId,
        string $userId,
        string $itemCode,
        int $quantity,
        array $itemData = [],
        ?string $sourceType = null,
        ?string $sourceId = null,
        array $metadata = [],
        array $context = []
    ): array {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $itemCode = self::normalizeItemCode($itemCode);
        $quantity = max(1, $quantity);
        if ($guildId === '' || $userId === '' || $itemCode === '') {
            throw new InvalidArgumentException('ITEM_GRANT_TARGET_REQUIRED');
        }

        $itemRow = self::upsertItem(['itemCode' => $itemCode] + $itemData + ['isActive' => $itemData['isActive'] ?? true]);
        $shopItemId = max(0, (int) ($itemRow['shopItemId'] ?? 0));
        if ($shopItemId <= 0) {
            throw new RuntimeException('ITEM_UPSERT_FAILED');
        }

        $createDate = self::timestamp($context['createDate'] ?? null);
        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $inventory = Database::fetch(
                'SELECT shopInventoryId, quantity
                   FROM tbl_shop_inventory
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND shopItemId = :shopItemId
                  LIMIT 1
                  FOR UPDATE',
                [
                    'guildId' => $guildId,
                    'userId' => $userId,
                    'shopItemId' => $shopItemId,
                ]
            );

            if (!$inventory) {
                Database::insert('tbl_shop_inventory', [
                    'guildId' => $guildId,
                    'userId' => $userId,
                    'shopItemId' => $shopItemId,
                    'quantity' => 0,
                    'metadataJson' => json_encode(['source' => $sourceType ?: 'item_catalog'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'createDate' => $createDate,
                    'updateDate' => $createDate,
                ]);
                $inventory = Database::fetch(
                    'SELECT shopInventoryId, quantity
                       FROM tbl_shop_inventory
                      WHERE guildId = :guildId
                        AND userId = :userId
                        AND shopItemId = :shopItemId
                      LIMIT 1
                      FOR UPDATE',
                    [
                        'guildId' => $guildId,
                        'userId' => $userId,
                        'shopItemId' => $shopItemId,
                    ]
                );
            }

            $inventoryId = max(0, (int) ($inventory['shopInventoryId'] ?? 0));
            $before = max(0, (int) ($inventory['quantity'] ?? 0));
            $after = $before + $quantity;

            Database::execute(
                'UPDATE tbl_shop_inventory
                    SET quantity = :quantity,
                        updateDate = :updateDate
                  WHERE shopInventoryId = :shopInventoryId',
                [
                    'quantity' => $after,
                    'updateDate' => $createDate,
                    'shopInventoryId' => $inventoryId,
                ]
            );

            $ledgerId = ShopInventoryLedgerService::record([
                'guildId' => $guildId,
                'userId' => $userId,
                'shopInventoryId' => $inventoryId,
                'shopItemId' => $shopItemId,
                'quantityDelta' => $quantity,
                'quantityBefore' => $before,
                'quantityAfter' => $after,
                'ledgerType' => (string) ($context['ledgerType'] ?? 'credit'),
                'sourceType' => $sourceType,
                'sourceId' => $sourceId,
                'transactionGroupId' => self::nullableString($context['transactionGroupId'] ?? null),
                'actorUserId' => self::nullableString($context['actorUserId'] ?? null),
                'targetUserId' => self::nullableString($context['targetUserId'] ?? $userId),
                'metadata' => $metadata,
                'createDate' => $createDate,
            ]);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'shopItemId' => $shopItemId,
                'shopInventoryId' => $inventoryId,
                'quantityBefore' => $before,
                'quantityAfter' => $after,
                'quantityDelta' => $quantity,
                'inventoryLedgerId' => $ledgerId,
                'itemCode' => $itemCode,
                'itemName' => (string) ($itemRow['itemName'] ?? $itemCode),
                'itemType' => (string) ($itemRow['itemType'] ?? 'item'),
                'effectType' => (string) ($itemRow['effectType'] ?? ''),
                'effectPayload' => $itemRow['effectPayload'] ?? [],
            ];
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    /** @return array<string, mixed> */
    public static function consumeInventoryItem(
        string $guildId,
        string $userId,
        int $shopInventoryId,
        int $quantity,
        ?string $sourceType = null,
        ?string $sourceId = null,
        array $metadata = [],
        array $context = []
    ): array {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $quantity = max(1, $quantity);
        if ($guildId === '' || $userId === '' || $shopInventoryId <= 0) {
            throw new InvalidArgumentException('ITEM_CONSUME_TARGET_REQUIRED');
        }

        $createDate = self::timestamp($context['createDate'] ?? null);
        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $inventory = Database::fetch(
                'SELECT inv.shopInventoryId,
                        inv.shopItemId,
                        inv.quantity,
                        item.itemCode,
                        item.itemName,
                        item.itemType,
                        item.effectType,
                        item.effectPayloadJson,
                        item.metadataJson AS itemMetadataJson
                   FROM tbl_shop_inventory inv
             INNER JOIN tbl_shop_item item ON item.shopItemId = inv.shopItemId
                  WHERE inv.guildId = :guildId
                    AND inv.userId = :userId
                    AND inv.shopInventoryId = :shopInventoryId
                  LIMIT 1
                  FOR UPDATE',
                [
                    'guildId' => $guildId,
                    'userId' => $userId,
                    'shopInventoryId' => $shopInventoryId,
                ]
            );

            if (!$inventory) {
                throw new RuntimeException('ITEM_NOT_FOUND');
            }

            $before = max(0, (int) ($inventory['quantity'] ?? 0));
            if ($before < $quantity) {
                throw new RuntimeException('ITEM_OUT_OF_STOCK');
            }
            $after = $before - $quantity;

            Database::execute(
                'UPDATE tbl_shop_inventory
                    SET quantity = :quantity,
                        updateDate = :updateDate
                  WHERE shopInventoryId = :shopInventoryId',
                [
                    'quantity' => $after,
                    'updateDate' => $createDate,
                    'shopInventoryId' => $shopInventoryId,
                ]
            );

            $ledgerId = ShopInventoryLedgerService::record([
                'guildId' => $guildId,
                'userId' => $userId,
                'shopInventoryId' => $shopInventoryId,
                'shopItemId' => (int) ($inventory['shopItemId'] ?? 0),
                'quantityDelta' => 0 - $quantity,
                'quantityBefore' => $before,
                'quantityAfter' => $after,
                'ledgerType' => (string) ($context['ledgerType'] ?? 'debit'),
                'sourceType' => $sourceType,
                'sourceId' => $sourceId,
                'transactionGroupId' => self::nullableString($context['transactionGroupId'] ?? null),
                'actorUserId' => self::nullableString($context['actorUserId'] ?? $userId),
                'targetUserId' => self::nullableString($context['targetUserId'] ?? $userId),
                'metadata' => $metadata,
                'createDate' => $createDate,
            ]);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'shopItemId' => (int) ($inventory['shopItemId'] ?? 0),
                'shopInventoryId' => $shopInventoryId,
                'quantityBefore' => $before,
                'quantityAfter' => $after,
                'quantityDelta' => 0 - $quantity,
                'inventoryLedgerId' => $ledgerId,
                'itemCode' => (string) ($inventory['itemCode'] ?? ''),
                'itemName' => (string) ($inventory['itemName'] ?? ''),
                'itemType' => (string) ($inventory['itemType'] ?? 'item'),
                'effectType' => (string) ($inventory['effectType'] ?? ''),
                'effectPayload' => self::decodeJson((string) ($inventory['effectPayloadJson'] ?? '')),
                'itemMetadata' => self::decodeJson((string) ($inventory['itemMetadataJson'] ?? '')),
            ];
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    /** @return array<string, mixed> */
    private static function decorateItemRow(array $row): array
    {
        $row['shopItemId'] = (int) ($row['shopItemId'] ?? 0);
        $row['itemCode'] = (string) ($row['itemCode'] ?? '');
        $row['itemName'] = (string) ($row['itemName'] ?? $row['itemCode'] ?? '');
        $row['itemType'] = (string) ($row['itemType'] ?? 'item');
        $row['effectType'] = (string) ($row['effectType'] ?? '');
        $row['effectPayload'] = self::decodeJson((string) ($row['effectPayloadJson'] ?? ''));
        $row['metadata'] = self::decodeJson((string) ($row['metadataJson'] ?? ''));
        return $row;
    }

    /** @return array<string, mixed> */
    private static function decorateInventoryRow(array $row): array
    {
        $row['shopInventoryId'] = (int) ($row['shopInventoryId'] ?? 0);
        $row['shopItemId'] = (int) ($row['shopItemId'] ?? 0);
        $row['quantity'] = (int) ($row['quantity'] ?? 0);
        $row['inventoryMetadata'] = self::decodeJson((string) ($row['metadataJson'] ?? ''));
        $row['itemMetadata'] = self::decodeJson((string) ($row['itemMetadataJson'] ?? ''));
        $row['effectPayload'] = self::decodeJson((string) ($row['effectPayloadJson'] ?? ''));
        return $row;
    }

    private static function normalizeItemCode(string $itemCode): string
    {
        $itemCode = preg_replace('/[^a-z0-9_]+/i', '_', strtolower(trim($itemCode))) ?? '';
        return trim($itemCode, '_');
    }

    private static function normalizeItemType(string $itemType): string
    {
        $itemType = preg_replace('/[^a-z0-9_\-]+/i', '_', strtolower(trim($itemType))) ?? '';
        return trim($itemType, '_') ?: 'item';
    }

    /** @return array<string, mixed> */
    private static function decodeJson(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : null;
    }

    private static function timestamp(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : date('Y-m-d H:i:s');
    }
}
