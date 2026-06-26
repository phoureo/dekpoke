<?php

declare(strict_types=1);

final class LootBoxService
{
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        ItemCatalogService::ensureSchema();

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_loot_box_open (
                lootBoxOpenId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                shopInventoryId bigint unsigned NOT NULL,
                shopItemId bigint unsigned NOT NULL,
                prepareToken varchar(96) NOT NULL,
                rewardTemplateId varchar(120) NOT NULL,
                openStatus varchar(24) NOT NULL DEFAULT "prepared",
                resultJson longtext DEFAULT NULL,
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (lootBoxOpenId),
                UNIQUE KEY uq_tbl_loot_box_open_token (prepareToken),
                KEY idx_tbl_loot_box_open_inventory (guildId, userId, shopInventoryId, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    /** @return array<string, mixed> */
    public static function prepareOpen(string $guildId, string $userId, int $shopInventoryId, array $context = []): array
    {
        self::ensureSchema();

        $inventory = ItemCatalogService::inventoryItemById($guildId, $userId, $shopInventoryId);
        if (!$inventory) {
            throw new RuntimeException('ITEM_NOT_FOUND');
        }

        $effectType = trim((string) ($inventory['effectType'] ?? ''));
        if ($effectType !== 'loot_box') {
            throw new RuntimeException('ITEM_NOT_LOOT_BOX');
        }
        if (max(0, (int) ($inventory['quantity'] ?? 0)) <= 0) {
            throw new RuntimeException('ITEM_OUT_OF_STOCK');
        }

        $effectPayload = is_array($inventory['effectPayload'] ?? null) ? $inventory['effectPayload'] : [];
        $rewardTemplateId = trim((string) ($effectPayload['rewardTemplateId'] ?? ''));
        if ($rewardTemplateId === '') {
            throw new RuntimeException('LOOT_BOX_TEMPLATE_REQUIRED');
        }

        $resultBundle = RewardTemplateService::resolveTemplate($rewardTemplateId);
        $token = self::prepareToken();
        $now = date('Y-m-d H:i:s');
        $metadata = [
            'itemCode' => (string) ($inventory['itemCode'] ?? ''),
            'itemName' => (string) ($inventory['itemName'] ?? ''),
            'effectPayload' => $effectPayload,
            'actorUserId' => trim((string) ($context['actorUserId'] ?? $userId)),
        ];

        Database::insert('tbl_loot_box_open', [
            'guildId' => trim($guildId),
            'userId' => trim($userId),
            'shopInventoryId' => $shopInventoryId,
            'shopItemId' => (int) ($inventory['shopItemId'] ?? 0),
            'prepareToken' => $token,
            'rewardTemplateId' => $rewardTemplateId,
            'openStatus' => 'prepared',
            'resultJson' => json_encode($resultBundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'metadataJson' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'createDate' => $now,
            'updateDate' => $now,
        ]);

        return [
            'prepareToken' => $token,
            'rewardTemplateId' => $rewardTemplateId,
            'bundle' => $resultBundle,
            'inventory' => $inventory,
        ];
    }

    /** @return array<string, mixed> */
    public static function completeOpen(string $guildId, string $userId, string $prepareToken, array $context = []): array
    {
        self::ensureSchema();

        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $row = Database::fetch(
                'SELECT *
                   FROM tbl_loot_box_open
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND prepareToken = :prepareToken
                  LIMIT 1
                  FOR UPDATE',
                [
                    'guildId' => trim($guildId),
                    'userId' => trim($userId),
                    'prepareToken' => trim($prepareToken),
                ]
            );

            if (!$row) {
                throw new RuntimeException('LOOT_BOX_PREPARE_NOT_FOUND');
            }

            $resultBundle = json_decode((string) ($row['resultJson'] ?? '{}'), true);
            $resultBundle = is_array($resultBundle) ? $resultBundle : [];
            if ((string) ($row['openStatus'] ?? '') === 'completed') {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return [
                    'prepareToken' => trim((string) ($row['prepareToken'] ?? '')),
                    'alreadyOpened' => true,
                    'bundle' => $resultBundle,
                ];
            }

            $traceId = trim((string) ($context['transactionGroupId'] ?? ''));
            if ($traceId === '' && class_exists('TransactionTraceService')) {
                $traceId = TransactionTraceService::generateTraceId('loot_box');
            }

            $consumeRow = ItemCatalogService::consumeInventoryItem(
                trim($guildId),
                trim($userId),
                (int) ($row['shopInventoryId'] ?? 0),
                1,
                'loot_box_open',
                trim((string) ($row['prepareToken'] ?? '')),
                [
                    'rewardTemplateId' => (string) ($row['rewardTemplateId'] ?? ''),
                ],
                [
                    'transactionGroupId' => $traceId,
                    'actorUserId' => $context['actorUserId'] ?? $userId,
                    'targetUserId' => $userId,
                    'ledgerType' => 'debit',
                ]
            );

            $grantRows = RewardTemplateService::grantRewardBundle(
                trim($guildId),
                trim($userId),
                $resultBundle,
                'loot_box_open',
                trim((string) ($row['prepareToken'] ?? '')),
                [
                    'transactionGroupId' => $traceId,
                    'actorUserId' => $context['actorUserId'] ?? $userId,
                    'createDate' => date('Y-m-d H:i:s'),
                ]
            );

            Database::execute(
                'UPDATE tbl_loot_box_open
                    SET openStatus = "completed",
                        updateDate = :updateDate
                  WHERE lootBoxOpenId = :lootBoxOpenId',
                [
                    'lootBoxOpenId' => (int) ($row['lootBoxOpenId'] ?? 0),
                    'updateDate' => date('Y-m-d H:i:s'),
                ]
            );

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'prepareToken' => trim((string) ($row['prepareToken'] ?? '')),
                'alreadyOpened' => false,
                'bundle' => $resultBundle,
                'consume' => $consumeRow,
                'grants' => $grantRows,
                'transactionGroupId' => $traceId,
            ];
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public static function openInventoryItem(string $guildId, string $userId, int $shopInventoryId, array $context = []): array
    {
        $prepared = self::prepareOpen($guildId, $userId, $shopInventoryId, $context);
        return self::completeOpen($guildId, $userId, (string) ($prepared['prepareToken'] ?? ''), $context);
    }

    private static function prepareToken(): string
    {
        return 'lootbox_' . bin2hex(random_bytes(16));
    }
}
