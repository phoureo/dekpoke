<?php

declare(strict_types=1);

final class ShopRoleBadgeService
{
    public static function purchaseBadge(string $guildId, string $buyerUserId, string $productId, string $optionId, string $paymentUnitCode): array
    {
        return self::purchaseBadgeForOwner(
            $guildId,
            $buyerUserId,
            $buyerUserId,
            $productId,
            $optionId,
            $paymentUnitCode,
            'self',
            null
        );
    }

    public static function giftBadge(string $guildId, string $buyerUserId, string $productId, string $optionId, string $paymentUnitCode, string $targetUserId): array
    {
        $targetUserId = trim($targetUserId);
        if ($targetUserId === '') {
            throw new RuntimeException('TARGET_NOT_FOUND');
        }
        if ($targetUserId === $buyerUserId) {
            throw new RuntimeException('TARGET_IS_SELF');
        }

        $target = self::findActiveMember($guildId, $targetUserId);
        if (!$target) {
            throw new RuntimeException('TARGET_NOT_FOUND');
        }

        return self::purchaseBadgeForOwner(
            $guildId,
            $buyerUserId,
            $targetUserId,
            $productId,
            $optionId,
            $paymentUnitCode,
            'gift',
            $target
        );
    }

    private static function purchaseBadgeForOwner(
        string $guildId,
        string $payerUserId,
        string $inventoryOwnerUserId,
        string $productId,
        string $optionId,
        string $paymentUnitCode,
        string $purchaseKind,
        ?array $target
    ): array
    {
        ShopUnitService::ensureSchema();

        [$product, $option, $role] = self::resolveRoleProductOption($guildId, $productId, $optionId);
        $shopItem = self::ensureRoleBadgeItem($product, $option, $role);
        $paymentUnitCode = self::slug($paymentUnitCode, '', '_');
        $paymentAmount = self::paymentAmountForOption($option, $paymentUnitCode, $purchaseKind);
        $transactionGroupId = TransactionTraceService::generateTraceId($purchaseKind === 'gift' ? 'shop_gift' : 'shop_purchase');
        $pdo = Database::pdo();
        $inventoryId = 0;

        try {
            $pdo->beginTransaction();

            $walletId = ShopUnitService::ensureWallet($guildId, $payerUserId, $paymentUnitCode);
            if ($walletId <= 0) {
                throw new RuntimeException('WALLET_UNAVAILABLE');
            }

            $walletRow = Database::fetch(
                'SELECT balanceAmount
                   FROM tbl_shop_wallet
                  WHERE shopWalletId = :shopWalletId
                  FOR UPDATE',
                ['shopWalletId' => $walletId]
            );
            $currentBalance = (int) ($walletRow['balanceAmount'] ?? 0);
            if ($currentBalance < $paymentAmount) {
                throw new RuntimeException('INSUFFICIENT_BALANCE:' . $paymentUnitCode);
            }

            $now = date('Y-m-d H:i:s');
            $nextBalance = $currentBalance - $paymentAmount;
            Database::execute(
                'UPDATE tbl_shop_wallet
                    SET balanceAmount = :balanceAmount,
                        updateDate = :updateDate
                  WHERE shopWalletId = :shopWalletId',
                [
                    'shopWalletId' => $walletId,
                    'balanceAmount' => $nextBalance,
                    'updateDate' => $now,
                ]
            );

            ShopUnitService::appendWalletLedger([
                'shopWalletId' => $walletId,
                'unitCode' => $paymentUnitCode,
                'amountDelta' => 0 - $paymentAmount,
                'ledgerType' => 'debit',
                'sourceType' => $purchaseKind === 'gift' ? 'shop_role_badge_gift' : 'shop_role_badge_purchase',
                'sourceId' => (string) ($product['id'] ?? ''),
                'transactionGroupId' => $transactionGroupId,
                'actorUserId' => $payerUserId,
                'targetUserId' => $purchaseKind === 'gift' ? $inventoryOwnerUserId : null,
                'walletBalanceBefore' => $currentBalance,
                'walletBalanceAfter' => $nextBalance,
                'createDate' => $now,
                'metadata' => [
                    'productId' => (string) ($product['id'] ?? ''),
                    'productName' => (string) ($product['name'] ?? ''),
                    'optionId' => (string) ($option['id'] ?? ''),
                    'optionLabel' => (string) ($option['label'] ?? ''),
                    'roleId' => (string) ($role['roleId'] ?? ''),
                    'roleName' => (string) ($role['roleName'] ?? ''),
                    'purchaseKind' => $purchaseKind,
                    'paidByUserId' => $payerUserId,
                    'inventoryOwnerUserId' => $inventoryOwnerUserId,
                    'inventoryOwnerDisplayName' => (string) ($target['displayName'] ?? ''),
                    'targetUserId' => (string) ($target['userId'] ?? ''),
                    'targetDisplayName' => (string) ($target['displayName'] ?? ''),
                    'paymentUnitCode' => $paymentUnitCode,
                    'paymentAmount' => $paymentAmount,
                ],
            ]);

            $inventoryRow = Database::fetch(
                'SELECT shopInventoryId, quantity, metadataJson
                   FROM tbl_shop_inventory
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND shopItemId = :shopItemId
                  FOR UPDATE',
                [
                    'guildId' => $guildId,
                    'userId' => $inventoryOwnerUserId,
                    'shopItemId' => (int) ($shopItem['shopItemId'] ?? 0),
                ]
            );

            $inventoryMeta = self::decodeJson($inventoryRow['metadataJson'] ?? null);
            $inventoryMeta['badgeType'] = 'role';
            $inventoryMeta['lastPurchasedAt'] = $now;
            $inventoryMeta['shopProductId'] = (string) ($product['id'] ?? '');
            $inventoryMeta['purchaseOptionId'] = (string) ($option['id'] ?? '');
            $inventoryMeta['durationDays'] = (int) ($option['days'] ?? 0);
            $inventoryMeta['lastPaidUnitCode'] = $paymentUnitCode;
            $inventoryMeta['lastPaidAmount'] = $paymentAmount;
            $inventoryMeta['lastPurchaseKind'] = $purchaseKind;
            $inventoryMeta['lastPurchasedByUserId'] = $payerUserId;
            if ($purchaseKind === 'gift') {
                $inventoryMeta['lastGiftedByUserId'] = $payerUserId;
                $inventoryMeta['lastGiftedToUserId'] = $inventoryOwnerUserId;
                $inventoryMeta['lastGiftTargetName'] = (string) ($target['displayName'] ?? '');
            }

            if ($inventoryRow) {
                $inventoryId = (int) ($inventoryRow['shopInventoryId'] ?? 0);
                $quantityBefore = (int) ($inventoryRow['quantity'] ?? 0);
                $quantityAfter = $quantityBefore + 1;
                Database::execute(
                    'UPDATE tbl_shop_inventory
                        SET quantity = :quantity,
                            metadataJson = :metadataJson,
                            updateDate = :updateDate
                      WHERE shopInventoryId = :shopInventoryId',
                    [
                        'quantity' => $quantityAfter,
                        'metadataJson' => json_encode($inventoryMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updateDate' => $now,
                        'shopInventoryId' => $inventoryId,
                    ]
                );
            } else {
                $quantityBefore = 0;
                $quantityAfter = 1;
                $inventoryId = Database::insert('tbl_shop_inventory', [
                    'guildId' => $guildId,
                    'userId' => $inventoryOwnerUserId,
                    'shopItemId' => (int) ($shopItem['shopItemId'] ?? 0),
                    'quantity' => $quantityAfter,
                    'metadataJson' => json_encode($inventoryMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updateDate' => $now,
                ]);
            }

            ShopInventoryLedgerService::record([
                'guildId' => $guildId,
                'userId' => $inventoryOwnerUserId,
                'shopInventoryId' => $inventoryId,
                'shopItemId' => (int) ($shopItem['shopItemId'] ?? 0),
                'quantityDelta' => 1,
                'ledgerType' => 'credit',
                'sourceType' => $purchaseKind === 'gift' ? 'shop_role_badge_gift' : 'shop_role_badge_purchase',
                'sourceId' => (string) ($product['id'] ?? ''),
                'transactionGroupId' => $transactionGroupId,
                'actorUserId' => $payerUserId,
                'targetUserId' => $purchaseKind === 'gift' ? $inventoryOwnerUserId : '',
                'quantityBefore' => $quantityBefore,
                'quantityAfter' => $quantityAfter,
                'createDate' => $now,
                'metadata' => [
                    'productId' => (string) ($product['id'] ?? ''),
                    'productName' => (string) ($product['name'] ?? ''),
                    'optionId' => (string) ($option['id'] ?? ''),
                    'optionLabel' => (string) ($option['label'] ?? ''),
                    'purchaseKind' => $purchaseKind,
                    'inventoryOwnerUserId' => $inventoryOwnerUserId,
                    'paidByUserId' => $payerUserId,
                    'targetUserId' => (string) ($target['userId'] ?? ''),
                    'targetDisplayName' => (string) ($target['displayName'] ?? ''),
                    'paymentUnitCode' => $paymentUnitCode,
                    'paymentAmount' => $paymentAmount,
                    'roleId' => (string) ($role['roleId'] ?? ''),
                    'roleName' => (string) ($role['roleName'] ?? ''),
                ],
            ]);

            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }

        try {
            LiveUpdateService::markTopic('reward_report', [
                'scope' => $purchaseKind === 'gift' ? 'shop_role_badge_gift' : 'shop_role_badge_purchase',
                'purchaseKind' => $purchaseKind,
                'payerUserId' => $payerUserId,
                'inventoryOwnerUserId' => $inventoryOwnerUserId,
                'targetUserId' => (string) ($target['userId'] ?? ''),
                'productId' => (string) ($product['id'] ?? ''),
                'optionId' => (string) ($option['id'] ?? ''),
                'paymentUnitCode' => $paymentUnitCode,
                'paymentAmount' => $paymentAmount,
                'inventoryId' => $inventoryId,
            ], 'user', $inventoryOwnerUserId, $guildId);
        } catch (Throwable $_) {
        }

        return [
            'ok' => true,
            'inventoryId' => $inventoryId,
            'balances' => ShopUnitService::walletBalances($guildId, $payerUserId),
            'product' => [
                'id' => (string) ($product['id'] ?? ''),
                'name' => self::displayName($product, $role),
            ],
            'option' => [
                'id' => (string) ($option['id'] ?? ''),
                'label' => self::durationLabel($option),
                'priceText' => self::priceText($option),
            ],
            'payment' => [
                'unitCode' => $paymentUnitCode,
                'amount' => $paymentAmount,
            ],
            'purchaseKind' => $purchaseKind,
            'target' => $target,
        ];
    }

    public static function consumeBadge(string $guildId, string $ownerUserId, int $inventoryId, string $targetUserId): array
    {
        ShopUnitService::ensureSchema();
        GachaRoleGrantService::ensureSchema();

        $target = self::findActiveMember($guildId, $targetUserId);
        if (!$target) {
            throw new RuntimeException('TARGET_NOT_FOUND');
        }

        $badge = self::loadBadgeInventoryRow($guildId, $ownerUserId, $inventoryId, true);
        if (!$badge) {
            throw new RuntimeException('BADGE_NOT_FOUND');
        }

        $itemMeta = self::decodeJson($badge['itemMetadataJson'] ?? null);
        $inventoryMeta = self::decodeJson($badge['inventoryMetadataJson'] ?? null);
        $roleId = trim((string) ($itemMeta['roleId'] ?? $inventoryMeta['roleId'] ?? ''));
        $roleName = trim((string) ($itemMeta['roleName'] ?? $badge['itemName'] ?? 'Badge Role'));
        $durationDays = max(0, (int) ($itemMeta['durationDays'] ?? $inventoryMeta['durationDays'] ?? 0));
        if ($roleId === '') {
            throw new RuntimeException('BADGE_ROLE_NOT_CONFIGURED');
        }

        $drawId = 'shop-role-badge:' . $inventoryId . ':' . $targetUserId . ':' . bin2hex(random_bytes(6));
        $transactionGroupId = $drawId;
        $quantityChange = self::decrementBadgeQuantity($inventoryId);
        $grantResult = null;

        try {
            $grantResult = (new GachaRoleGrantService())->grantForDraw($guildId, $targetUserId, [
                'drawId' => $drawId,
                'transactionGroupId' => $transactionGroupId,
                'prize' => [
                    'id' => 'shop-role-badge:' . (string) ($itemMeta['shopProductId'] ?? $badge['itemCode'] ?? $inventoryId),
                    'type' => 'role',
                    'name' => $roleName,
                    'roleId' => $roleId,
                    'roleName' => $roleName,
                    'roleDurationDays' => $durationDays,
                    'roleDurationLabel' => (string) ($itemMeta['durationLabel'] ?? self::durationLabel(['days' => $durationDays])),
                    'roleColor' => (string) ($itemMeta['roleColor'] ?? ''),
                    'roleIconUrl' => (string) ($itemMeta['roleIconUrl'] ?? ''),
                    'roleTier' => (string) ($itemMeta['roleTier'] ?? ''),
                    'roleSeriesName' => (string) ($itemMeta['roleSeriesName'] ?? ''),
                    'roleSeriesBadge' => (string) ($itemMeta['roleSeriesBadge'] ?? ''),
                ],
            ]);
        } catch (Throwable $error) {
            self::restoreBadgeQuantity($inventoryId);
            throw $error;
        }

        $status = (string) ($grantResult['status'] ?? '');
        if (!($grantResult['ok'] ?? false) || !in_array($status, ['granted'], true)) {
            self::restoreBadgeQuantity($inventoryId);
            if ($status === 'covered_by_permanent') {
                throw new RuntimeException('TARGET_ALREADY_HAS_PERMANENT_ROLE');
            }
            throw new RuntimeException(trim((string) ($grantResult['message'] ?? 'ROLE_GRANT_FAILED')) ?: 'ROLE_GRANT_FAILED');
        }

        ShopInventoryLedgerService::record([
            'guildId' => $guildId,
            'userId' => $ownerUserId,
            'shopInventoryId' => $inventoryId,
            'shopItemId' => (int) ($badge['shopItemId'] ?? 0),
            'quantityDelta' => -1,
            'ledgerType' => 'debit',
            'sourceType' => 'shop_role_badge_consume',
            'sourceId' => (string) ($grantResult['grant']['drawId'] ?? $drawId),
            'transactionGroupId' => $transactionGroupId,
            'actorUserId' => $ownerUserId,
            'targetUserId' => $targetUserId,
            'quantityBefore' => (int) ($quantityChange['quantityBefore'] ?? 0),
            'quantityAfter' => (int) ($quantityChange['quantityAfter'] ?? 0),
            'metadata' => [
                'inventoryId' => $inventoryId,
                'drawId' => $drawId,
                'grantId' => (int) ($grantResult['grant']['gachaRoleGrantId'] ?? 0),
                'grantStatus' => (string) ($grantResult['status'] ?? ''),
                'targetUserId' => $targetUserId,
                'targetDisplayName' => (string) ($target['displayName'] ?? ''),
                'roleId' => $roleId,
                'roleName' => $roleName,
                'durationDays' => $durationDays,
                'itemCode' => (string) ($badge['itemCode'] ?? ''),
                'itemName' => (string) ($badge['itemName'] ?? ''),
            ],
        ]);

        try {
            LiveUpdateService::markTopic('reward_report', [
                'scope' => 'shop_role_badge_consume',
                'ownerUserId' => $ownerUserId,
                'targetUserId' => $targetUserId,
                'inventoryId' => $inventoryId,
                'drawId' => $drawId,
                'grantId' => (int) ($grantResult['grant']['gachaRoleGrantId'] ?? 0),
                'shopItemId' => (int) ($badge['shopItemId'] ?? 0),
                'itemCode' => (string) ($badge['itemCode'] ?? ''),
                'itemName' => (string) ($badge['itemName'] ?? ''),
                'roleId' => $roleId,
                'roleName' => $roleName,
            ], 'user', $ownerUserId, $guildId);
        } catch (Throwable $_) {
        }

        return [
            'ok' => true,
            'remainingQuantity' => (int) ($quantityChange['quantityAfter'] ?? 0),
            'target' => $target,
            'grant' => $grantResult['grant'] ?? null,
            'roleName' => $roleName,
        ];
    }

    public static function searchGuildMembers(string $guildId, string $query, int $limit = 8): array
    {
        $guildId = trim($guildId);
        $query = trim($query);
        if ($guildId === '' || $query === '') {
            return [];
        }

        $limit = max(1, min(20, $limit));
        $rows = Database::fetchAll(
            'SELECT m.userId, m.nickName, m.guildAvatarHash, u.userName, u.globalName, u.avatarHash
               FROM tbl_member m
         INNER JOIN tbl_user u ON u.userId = m.userId
              WHERE m.guildId = :guildId
                AND m.isActive = 1
                AND u.isBot = 0
                AND (
                    u.userId = :exactUserId
                    OR u.userName LIKE :qUserName
                    OR u.globalName LIKE :qGlobalName
                    OR m.nickName LIKE :qNickName
                )
              ORDER BY COALESCE(m.nickName, u.globalName, u.userName) ASC
              LIMIT ' . $limit,
            [
                'guildId' => $guildId,
                'exactUserId' => $query,
                'qUserName' => '%' . $query . '%',
                'qGlobalName' => '%' . $query . '%',
                'qNickName' => '%' . $query . '%',
            ]
        );

        return array_map(static function (array $row) use ($guildId): array {
            $userId = (string) ($row['userId'] ?? '');
            $displayName = trim((string) ($row['nickName'] ?? ''))
                ?: trim((string) ($row['globalName'] ?? ''))
                ?: trim((string) ($row['userName'] ?? ''))
                ?: $userId;
            $avatarUrl = DiscordAssets::guildAvatar($guildId, $userId, $row['guildAvatarHash'] ?? null, 96)
                ?: DiscordAssets::avatar($userId, $row['avatarHash'] ?? null, 96);

            return [
                'userId' => $userId,
                'displayName' => $displayName,
                'userName' => (string) ($row['userName'] ?? ''),
                'avatarUrl' => $avatarUrl,
            ];
        }, $rows);
    }

    private static function resolveRoleProductOption(string $guildId, string $productId, string $optionId): array
    {
        $productId = trim($productId);
        $optionId = trim($optionId);
        if ($productId === '' || $optionId === '') {
            throw new RuntimeException('INVALID_PRODUCT_OPTION');
        }

        $config = ShopConfigService::load();
        $product = null;
        foreach (ShopConfigService::publicProducts($config) as $candidate) {
            if (($candidate['id'] ?? '') === $productId) {
                $product = $candidate;
                break;
            }
        }
        if (!$product || ($product['type'] ?? '') !== 'role') {
            throw new RuntimeException('ROLE_PRODUCT_NOT_FOUND');
        }

        $option = null;
        foreach (($product['purchaseOptions'] ?? []) as $candidate) {
            if (($candidate['id'] ?? '') === $optionId && !empty($candidate['active']) && !empty($candidate['visible'])) {
                $option = $candidate;
                break;
            }
        }
        if (!$option) {
            throw new RuntimeException('ROLE_OPTION_NOT_FOUND');
        }

        $roleId = trim((string) ($product['discordRoleId'] ?? ''));
        if ($roleId === '') {
            throw new RuntimeException('ROLE_NOT_CONFIGURED');
        }

        $role = self::loadRoleRecord($guildId, $roleId);
        if (!$role) {
            throw new RuntimeException('ROLE_NOT_FOUND');
        }

        return [$product, $option, $role];
    }

    private static function ensureRoleBadgeItem(array $product, array $option, array $role): array
    {
        $itemCode = self::slug((string) ($product['itemCode'] ?? $product['id'] ?? 'role_badge'), 'role_badge', '_')
            . '__'
            . self::slug((string) ($option['id'] ?? 'option'), 'option', '_');
        $roleName = trim((string) ($role['roleName'] ?? 'Role Badge'));
        $displayName = self::displayName($product, $role);
        $durationLabel = self::durationLabel($option);
        $prices = self::normalizedPrices($option['prices'] ?? []);

        Database::execute(
            'INSERT INTO tbl_shop_item (itemCode, itemName, itemType, image, effectType, effectPayloadJson, metadataJson, isActive, updateDate)
             VALUES (:itemCode, :itemName, :itemType, :image, :effectType, :effectPayloadJson, :metadataJson, :isActive, :updateDate)
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
                'itemName' => $displayName . ' · ' . $durationLabel,
                'itemType' => 'role_badge',
                'image' => trim((string) ($product['image'] ?? '')) !== '' ? (string) $product['image'] : (string) ($role['roleIconUrl'] ?? 'images/icon_roles_blank.png'),
                'effectType' => 'role_badge_apply',
                'effectPayloadJson' => json_encode([
                    'discordRoleId' => (string) ($role['roleId'] ?? ''),
                    'durationDays' => (int) ($option['days'] ?? 0),
                    'purchaseOptionId' => (string) ($option['id'] ?? ''),
                    'shopProductId' => (string) ($product['id'] ?? ''),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'metadataJson' => json_encode([
                    'badgeType' => 'role',
                    'shopProductId' => (string) ($product['id'] ?? ''),
                    'purchaseOptionId' => (string) ($option['id'] ?? ''),
                    'roleId' => (string) ($role['roleId'] ?? ''),
                    'roleName' => $roleName !== '' ? $roleName : $displayName,
                    'roleColor' => (string) ($role['roleColorHex'] ?? ''),
                    'roleIconUrl' => (string) ($role['roleIconUrl'] ?? ''),
                    'roleTier' => (string) ($role['roleTier'] ?? ''),
                    'roleSeriesName' => (string) ($role['roleSeriesName'] ?? ''),
                    'roleSeriesBadge' => (string) ($role['roleSeriesBadge'] ?? ''),
                    'permissionDetails' => array_values(array_filter($role['permissionDetails'] ?? [], 'is_array')),
                    'productName' => $displayName,
                    'productBadge' => (string) ($product['badge'] ?? ''),
                    'detailText' => (string) ($product['detailText'] ?? ''),
                    'conditionText' => (string) ($product['conditionText'] ?? ''),
                    'durationDays' => (int) ($option['days'] ?? 0),
                    'durationLabel' => $durationLabel,
                    'prices' => $prices,
                    'priceText' => self::priceText($option),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'isActive' => !empty($product['active']) ? 1 : 0,
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );

        $shopItem = Database::fetch(
            'SELECT shopItemId, itemCode, itemName, itemType, image, effectType, metadataJson
               FROM tbl_shop_item
              WHERE itemCode = :itemCode
              LIMIT 1',
            ['itemCode' => $itemCode]
        );

        if (!$shopItem) {
            throw new RuntimeException('ROLE_BADGE_ITEM_UNAVAILABLE');
        }

        return $shopItem;
    }

    private static function loadRoleRecord(string $guildId, string $roleId): ?array
    {
        $row = Database::fetch(
            'SELECT roleId, roleName, roleColor, rolePosition, permissions, iconHash
               FROM tbl_role
              WHERE guildId = :guildId
                AND roleId = :roleId
                AND deleteDate IS NULL
              LIMIT 1',
            ['guildId' => $guildId, 'roleId' => $roleId]
        );
        if (!$row) {
            return null;
        }

        if (class_exists('RoleCatalogService')) {
            $row = RoleCatalogService::decorateRole($row);
        }

        $row['roleColorHex'] = self::roleColorHex((int) ($row['roleColor'] ?? 0));
        $row['roleIconUrl'] = DiscordAssets::roleIcon((string) ($row['roleId'] ?? ''), $row['iconHash'] ?? null, 96) ?: '';
        $row['permissionDetails'] = class_exists('RolePermissionDescriptionService')
            ? RolePermissionDescriptionService::describeAllowedPermissions($row['permissions'] ?? null)
            : [];

        return $row;
    }

    private static function loadBadgeInventoryRow(string $guildId, string $ownerUserId, int $inventoryId, bool $forUpdate = false): ?array
    {
        return Database::fetch(
            'SELECT inv.shopInventoryId, inv.quantity, inv.metadataJson AS inventoryMetadataJson,
                    item.shopItemId, item.itemCode, item.itemName, item.itemType, item.metadataJson AS itemMetadataJson
               FROM tbl_shop_inventory inv
         INNER JOIN tbl_shop_item item ON item.shopItemId = inv.shopItemId
              WHERE inv.guildId = :guildId
                AND inv.userId = :userId
                AND inv.shopInventoryId = :shopInventoryId
                AND inv.quantity > 0
                AND item.itemType = "role_badge"
              LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : ''),
            [
                'guildId' => $guildId,
                'userId' => $ownerUserId,
                'shopInventoryId' => $inventoryId,
            ]
        );
    }

    /** @return array{quantityBefore:int,quantityAfter:int} */
    private static function decrementBadgeQuantity(int $inventoryId): array
    {
        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();
            $row = Database::fetch(
                'SELECT quantity
                   FROM tbl_shop_inventory
                  WHERE shopInventoryId = :shopInventoryId
                  FOR UPDATE',
                ['shopInventoryId' => $inventoryId]
            );
            $quantity = (int) ($row['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new RuntimeException('BADGE_OUT_OF_STOCK');
            }
            $remaining = $quantity - 1;
            Database::execute(
                'UPDATE tbl_shop_inventory
                    SET quantity = :quantity,
                        updateDate = :updateDate
                  WHERE shopInventoryId = :shopInventoryId',
                [
                    'quantity' => $remaining,
                    'updateDate' => date('Y-m-d H:i:s'),
                    'shopInventoryId' => $inventoryId,
                ]
            );
            $pdo->commit();
            return [
                'quantityBefore' => $quantity,
                'quantityAfter' => $remaining,
            ];
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    private static function restoreBadgeQuantity(int $inventoryId): void
    {
        Database::execute(
            'UPDATE tbl_shop_inventory
                SET quantity = quantity + 1,
                    updateDate = :updateDate
              WHERE shopInventoryId = :shopInventoryId',
            [
                'updateDate' => date('Y-m-d H:i:s'),
                'shopInventoryId' => $inventoryId,
            ]
        );
    }

    private static function findActiveMember(string $guildId, string $targetUserId): ?array
    {
        $row = Database::fetch(
            'SELECT m.userId, m.nickName, m.guildAvatarHash, u.userName, u.globalName, u.avatarHash
               FROM tbl_member m
         INNER JOIN tbl_user u ON u.userId = m.userId
              WHERE m.guildId = :guildId
                AND m.userId = :userId
                AND m.isActive = 1
              LIMIT 1',
            ['guildId' => $guildId, 'userId' => $targetUserId]
        );
        if (!$row) {
            return null;
        }

        $displayName = trim((string) ($row['nickName'] ?? ''))
            ?: trim((string) ($row['globalName'] ?? ''))
            ?: trim((string) ($row['userName'] ?? ''))
            ?: (string) ($row['userId'] ?? '');

        return [
            'userId' => (string) ($row['userId'] ?? ''),
            'displayName' => $displayName,
            'userName' => (string) ($row['userName'] ?? ''),
            'avatarUrl' => DiscordAssets::guildAvatar($guildId, $row['userId'] ?? '', $row['guildAvatarHash'] ?? null, 96)
                ?: DiscordAssets::avatar($row['userId'] ?? '', $row['avatarHash'] ?? null, 96),
        ];
    }

    private static function displayName(array $product, array $role): string
    {
        $roleName = trim((string) ($role['roleName'] ?? 'Discord Role'));
        $name = trim((string) ($product['name'] ?? '')) ?: trim((string) ($product['shortName'] ?? 'สินค้า'));
        return str_replace('<roleName>', $roleName, $name);
    }

    private static function durationLabel(array $option): string
    {
        $label = trim((string) ($option['label'] ?? ''));
        if ($label !== '') {
            return $label;
        }
        $days = max(0, (int) ($option['days'] ?? 0));
        return $days > 0 ? $days . ' วัน' : 'ถาวร';
    }

    private static function priceText(array $option): string
    {
        $prices = self::normalizedPrices($option['prices'] ?? []);
        $unitIndex = ShopUnitService::unitIndex(true);
        $parts = [];
        foreach ($prices as $unitCode => $amount) {
            if ($amount <= 0) {
                continue;
            }
            $unit = $unitIndex[$unitCode] ?? ['shortName' => $unitCode, 'displayName' => $unitCode];
            $parts[] = number_format($amount) . ' ' . ((string) ($unit['shortName'] ?? '') ?: (string) ($unit['displayName'] ?? $unitCode));
        }
        return $parts ? implode(' / ', $parts) : 'ฟรี';
    }

    private static function paymentAmountForOption(array $option, string $paymentUnitCode, string $purchaseKind): int
    {
        $paymentUnitCode = self::slug($paymentUnitCode, '', '_');
        if ($paymentUnitCode === '') {
            throw new RuntimeException($purchaseKind === 'gift' ? 'INVALID_GIFT_PAYMENT_UNIT' : 'INVALID_PAYMENT_UNIT');
        }

        $basePrices = self::normalizedPrices($option['prices'] ?? []);
        if ($purchaseKind === 'gift') {
            $gift = self::normalizedGiftSettings($option['gift'] ?? [], $basePrices);
            if (empty($gift['enabled']) || empty($gift['enabledUnits'][$paymentUnitCode])) {
                throw new RuntimeException('INVALID_GIFT_PAYMENT_UNIT');
            }
            $prices = !empty($gift['useCustomPrices'])
                ? self::normalizedPrices($gift['prices'] ?? [])
                : $basePrices;
            $amount = (int) ($prices[$paymentUnitCode] ?? 0);
            if ($amount <= 0) {
                throw new RuntimeException('INVALID_GIFT_PAYMENT_UNIT');
            }
            return $amount;
        }

        $amount = (int) ($basePrices[$paymentUnitCode] ?? 0);
        if ($amount <= 0) {
            throw new RuntimeException('INVALID_PAYMENT_UNIT');
        }
        return $amount;
    }

    /** @return array{enabled: bool, useCustomPrices: bool, enabledUnits: array<string, bool>, prices: array<string, int>} */
    private static function normalizedGiftSettings(mixed $gift, array $basePrices): array
    {
        $gift = is_array($gift) ? $gift : [];
        $enabledUnitsRaw = is_array($gift['enabledUnits'] ?? null) ? $gift['enabledUnits'] : [];
        $giftPricesRaw = is_array($gift['prices'] ?? null) ? $gift['prices'] : [];
        $unitCodes = array_unique(array_merge(array_keys($basePrices), array_keys($enabledUnitsRaw), array_keys($giftPricesRaw)));
        $enabledUnits = [];
        $giftPrices = [];
        foreach ($unitCodes as $unitCode) {
            $unitCode = self::slug((string) $unitCode, '', '_');
            if ($unitCode === '') {
                continue;
            }
            $enabledUnits[$unitCode] = !empty($enabledUnitsRaw[$unitCode]);
            $giftPrices[$unitCode] = max(0, (int) ($giftPricesRaw[$unitCode] ?? ($basePrices[$unitCode] ?? 0)));
        }

        return [
            'enabled' => !empty($gift['enabled']),
            'useCustomPrices' => !empty($gift['useCustomPrices']),
            'enabledUnits' => $enabledUnits,
            'prices' => $giftPrices,
        ];
    }

    private static function normalizedPrices(mixed $prices): array
    {
        if (!is_array($prices)) {
            return [];
        }

        $out = [];
        foreach ($prices as $unitCode => $amount) {
            $normalizedUnit = self::slug((string) $unitCode, '', '_');
            if ($normalizedUnit === '') {
                continue;
            }
            $out[$normalizedUnit] = max(0, (int) $amount);
        }
        return $out;
    }

    private static function roleColorHex(int $roleColor): string
    {
        if ($roleColor <= 0) {
            return '';
        }
        return '#' . str_pad(strtolower(dechex($roleColor)), 6, '0', STR_PAD_LEFT);
    }

    private static function slug(string $value, string $fallback, string $separator = '-'): string
    {
        $value = trim($value);
        if ($value === '') {
            $value = $fallback;
        }
        $pattern = $separator === '_' ? '/[^a-z0-9_]+/i' : '/[^a-z0-9]+/i';
        $value = strtolower(preg_replace($pattern, $separator, $value) ?? '');
        $value = trim($value, $separator);
        return $value !== '' ? $value : trim($fallback, $separator);
    }

    private static function decodeJson(?string $json): array
    {
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
