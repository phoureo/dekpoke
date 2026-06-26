<?php

declare(strict_types=1);

final class InventoryItemUseService
{
    /** @return array<string, mixed> */
    public static function useInventoryItem(string $guildId, string $userId, int $shopInventoryId, array $context = []): array
    {
        $inventory = ItemCatalogService::inventoryItemById($guildId, $userId, $shopInventoryId);
        if (!$inventory) {
            throw new RuntimeException('ITEM_NOT_FOUND');
        }

        $effectType = trim((string) ($inventory['effectType'] ?? ''));
        $effectPayload = is_array($inventory['effectPayload'] ?? null) ? $inventory['effectPayload'] : [];

        if ($effectType === 'loot_box') {
            return LootBoxService::openInventoryItem($guildId, $userId, $shopInventoryId, $context) + [
                'handler' => 'loot_box',
            ];
        }

        if ($effectType === 'currency_bundle') {
            $traceId = trim((string) ($context['transactionGroupId'] ?? ''));
            if ($traceId === '' && class_exists('TransactionTraceService')) {
                $traceId = TransactionTraceService::generateTraceId('inventory_use');
            }

            $pdo = Database::pdo();
            $ownsTransaction = !$pdo->inTransaction();
            if ($ownsTransaction) {
                $pdo->beginTransaction();
            }

            try {
                $consume = ItemCatalogService::consumeInventoryItem(
                    $guildId,
                    $userId,
                    $shopInventoryId,
                    1,
                    'inventory_use',
                    (string) ($inventory['itemCode'] ?? ''),
                    ['effectType' => $effectType, 'effectPayload' => $effectPayload],
                    [
                        'transactionGroupId' => $traceId,
                        'actorUserId' => $context['actorUserId'] ?? $userId,
                        'targetUserId' => $userId,
                        'ledgerType' => 'debit',
                    ]
                );

                $grant = RewardTemplateService::grantRewardBundle(
                    $guildId,
                    $userId,
                    [
                        'templateId' => '',
                        'templateName' => (string) ($inventory['itemName'] ?? $inventory['itemCode'] ?? 'currency_bundle'),
                        'unitRewards' => is_array($effectPayload['unitRewards'] ?? null) ? $effectPayload['unitRewards'] : [],
                        'itemRewards' => is_array($effectPayload['itemRewards'] ?? null) ? $effectPayload['itemRewards'] : [],
                        'lootBoxRewards' => is_array($effectPayload['lootBoxRewards'] ?? null) ? $effectPayload['lootBoxRewards'] : [],
                        'resolvedEntries' => [],
                    ],
                    'inventory_use',
                    (string) ($inventory['itemCode'] ?? ''),
                    [
                        'transactionGroupId' => $traceId,
                        'actorUserId' => $context['actorUserId'] ?? $userId,
                    ]
                );

                if ($ownsTransaction) {
                    $pdo->commit();
                }
            } catch (Throwable $exception) {
                if ($ownsTransaction && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }

            return [
                'handler' => 'currency_bundle',
                'consume' => $consume,
                'grant' => $grant,
                'transactionGroupId' => $traceId,
            ];
        }

        if ($effectType === '' || $effectType === 'none' || $effectType === 'event_fragment') {
            throw new RuntimeException('ITEM_NOT_USABLE');
        }

        throw new RuntimeException('ITEM_HANDLER_UNAVAILABLE');
    }
}
