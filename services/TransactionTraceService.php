<?php

declare(strict_types=1);

final class TransactionTraceService
{
    public const CONFIDENCE_AUTHORITATIVE = 'authoritative';
    public const CONFIDENCE_LINKED_EXISTING = 'linked_existing';
    public const CONFIDENCE_SINGLE_LEG_LEGACY = 'single_leg_legacy';

    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        self::ensureShopWalletLedgerSchema();
        self::ensureShopInventoryLedgerSchema();
        self::ensureRewardEventSchema();
        self::ensureRoleGrantSchema();

        self::$schemaReady = true;
    }

    public static function generateTraceId(string $prefix = 'tx'): string
    {
        $prefix = preg_replace('/[^a-z0-9_]+/i', '_', strtolower(trim($prefix))) ?? '';
        $prefix = trim($prefix, '_') ?: 'tx';
        return self::normalizeTransactionGroupId($prefix . '_' . bin2hex(random_bytes(12)));
    }

    public static function normalizeTransactionGroupId(mixed $value): string
    {
        return substr(trim((string) ($value ?? '')), 0, 120);
    }

    public static function canonicalHistoryKind(string $historyKind): string
    {
        $normalized = trim($historyKind);
        if ($normalized === 'wallet_movement') {
            return 'wallet_ledger';
        }
        return $normalized;
    }

    public static function decorateRowTraceMeta(array $row, string $historyKind, int $historyId): array
    {
        $row['transactionGroupId'] = self::normalizeTransactionGroupId($row['transactionGroupId'] ?? '');
        $row['historyId'] = $historyId;
        $row['traceHistoryKind'] = self::canonicalHistoryKind($historyKind);
        $row['traceConfidence'] = self::traceConfidenceForRow($row + ['historyKind' => $historyKind]);
        return $row;
    }

    public static function traceConfidenceForRow(array $row): string
    {
        $transactionGroupId = self::normalizeTransactionGroupId($row['transactionGroupId'] ?? '');
        if ($transactionGroupId !== '') {
            return self::CONFIDENCE_AUTHORITATIVE;
        }

        $historyKind = self::canonicalHistoryKind((string) ($row['traceHistoryKind'] ?? $row['historyKind'] ?? ''));
        $sourceType = trim((string) ($row['sourceType'] ?? ''));
        $sourceId = trim((string) ($row['sourceId'] ?? ''));

        if (in_array($sourceType, ['earn_rule', 'earn_manual'], true)) {
            return self::CONFIDENCE_LINKED_EXISTING;
        }
        if ($historyKind === 'reward_event' && str_starts_with($sourceType, 'earn_')) {
            return self::CONFIDENCE_LINKED_EXISTING;
        }
        if (in_array($sourceType, ['shop_role_badge_purchase', 'shop_role_badge_gift'], true)) {
            return self::CONFIDENCE_LINKED_EXISTING;
        }
        if ($historyKind === 'item_ledger' && $sourceType === 'shop_role_badge_consume' && $sourceId !== '') {
            return self::CONFIDENCE_LINKED_EXISTING;
        }
        if ($historyKind === 'role_grant' && trim((string) ($row['drawId'] ?? '')) !== '') {
            return self::CONFIDENCE_LINKED_EXISTING;
        }

        return self::CONFIDENCE_SINGLE_LEG_LEGACY;
    }

    public static function resolveTrace(string $guildId, array $filters): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $transactionGroupId = self::normalizeTransactionGroupId($filters['transactionGroupId'] ?? '');
        $historyKind = self::canonicalHistoryKind((string) ($filters['historyKind'] ?? ''));
        $historyId = max(0, (int) ($filters['historyId'] ?? 0));

        if ($transactionGroupId !== '') {
            $trace = self::traceByTransactionGroupId($guildId, $transactionGroupId);
            if ($trace !== null) {
                return $trace;
            }
        }

        $seed = self::resolveSeedLeg($guildId, $historyKind, $historyId);
        if ($seed === null) {
            throw new RuntimeException('TRACE_NOT_FOUND');
        }

        $seedTransactionGroupId = self::normalizeTransactionGroupId($seed['transactionGroupId'] ?? '');
        if ($seedTransactionGroupId !== '') {
            $trace = self::traceByTransactionGroupId($guildId, $seedTransactionGroupId);
            if ($trace !== null) {
                return $trace;
            }
        }

        $trace = self::legacyTraceForSeed($guildId, $seed);
        if ($trace !== null) {
            return $trace;
        }

        return self::buildTracePayload(
            [(string) ($seed['traceKey'] ?? '') => $seed],
            self::CONFIDENCE_SINGLE_LEG_LEGACY,
            '',
            'legacy:' . self::canonicalHistoryKind((string) ($seed['historyKind'] ?? '')) . ':' . (string) ($seed['historyId'] ?? 0)
        );
    }

    private static function traceByTransactionGroupId(string $guildId, string $transactionGroupId): ?array
    {
        $legs = [];
        foreach (
            array_merge(
                self::walletLegsByTransactionGroupId($guildId, $transactionGroupId),
                self::itemLegsByTransactionGroupId($guildId, $transactionGroupId),
                self::rewardLegsByTransactionGroupId($guildId, $transactionGroupId),
                self::roleGrantLegsByTransactionGroupId($guildId, $transactionGroupId)
            ) as $leg
        ) {
            $legs[(string) ($leg['traceKey'] ?? uniqid('leg_', true))] = $leg;
        }

        if ($legs === []) {
            return null;
        }

        return self::buildTracePayload($legs, self::CONFIDENCE_AUTHORITATIVE, $transactionGroupId, $transactionGroupId);
    }

    private static function resolveSeedLeg(string $guildId, string $historyKind, int $historyId): ?array
    {
        return match ($historyKind) {
            'wallet_ledger' => self::walletLegById($guildId, $historyId),
            'item_ledger' => self::itemLegById($guildId, $historyId),
            'reward_event' => self::rewardLegById($guildId, $historyId),
            'role_grant' => self::roleGrantLegById($guildId, $historyId),
            default => null,
        };
    }

    private static function legacyTraceForSeed(string $guildId, array $seed): ?array
    {
        $historyKind = self::canonicalHistoryKind((string) ($seed['historyKind'] ?? ''));
        $sourceType = trim((string) ($seed['sourceType'] ?? ''));

        if ($historyKind === 'reward_event') {
            if (str_starts_with($sourceType, 'earn_')) {
                return self::legacyRewardTraceFromRewardLeg($guildId, $seed);
            }
            return null;
        }

        if ($historyKind === 'wallet_ledger') {
            if (in_array($sourceType, ['earn_rule', 'earn_manual'], true)) {
                return self::legacyRewardTraceFromWalletLeg($guildId, $seed);
            }
            if (in_array($sourceType, ['shop_role_badge_purchase', 'shop_role_badge_gift'], true)) {
                return self::legacyShopPurchaseTraceFromWalletLeg($guildId, $seed);
            }
            return null;
        }

        if ($historyKind === 'item_ledger') {
            if ($sourceType === 'shop_role_badge_consume') {
                return self::legacyConsumeTraceFromItemLeg($guildId, $seed);
            }
            if (in_array($sourceType, ['shop_role_badge_purchase', 'shop_role_badge_gift'], true)) {
                return self::legacyShopPurchaseTraceFromItemLeg($guildId, $seed);
            }
            return null;
        }

        if ($historyKind === 'role_grant') {
            return self::legacyConsumeTraceFromRoleGrantLeg($guildId, $seed);
        }

        return null;
    }

    private static function legacyRewardTraceFromRewardLeg(string $guildId, array $rewardLeg): ?array
    {
        $rewardEventId = max(0, (int) ($rewardLeg['historyId'] ?? $rewardLeg['rewardEventId'] ?? 0));
        if ($rewardEventId <= 0) {
            return null;
        }

        $legs = [(string) $rewardLeg['traceKey'] => $rewardLeg];
        foreach (self::walletLegsByRewardEventId($guildId, (string) ($rewardLeg['userId'] ?? ''), $rewardEventId) as $walletLeg) {
            $legs[(string) $walletLeg['traceKey']] = $walletLeg;
        }

        $confidence = count($legs) > 1 ? self::CONFIDENCE_LINKED_EXISTING : self::CONFIDENCE_SINGLE_LEG_LEGACY;
        return self::buildTracePayload($legs, $confidence, '', 'legacy:reward_event:' . $rewardEventId);
    }

    private static function legacyRewardTraceFromWalletLeg(string $guildId, array $walletLeg): ?array
    {
        $rewardEventId = max(0, (int) ($walletLeg['sourceId'] ?? 0));
        if ($rewardEventId <= 0) {
            return null;
        }

        $rewardLeg = self::rewardLegById($guildId, $rewardEventId);
        if ($rewardLeg === null) {
            return null;
        }

        $legs = [(string) $rewardLeg['traceKey'] => $rewardLeg];
        foreach (self::walletLegsByRewardEventId($guildId, (string) ($walletLeg['userId'] ?? ''), $rewardEventId) as $candidate) {
            $legs[(string) $candidate['traceKey']] = $candidate;
        }

        return self::buildTracePayload($legs, self::CONFIDENCE_LINKED_EXISTING, '', 'legacy:reward_event:' . $rewardEventId);
    }

    private static function legacyConsumeTraceFromItemLeg(string $guildId, array $itemLeg): ?array
    {
        $drawId = trim((string) ($itemLeg['sourceId'] ?? ''));
        if ($drawId === '') {
            return null;
        }

        $grantLeg = self::roleGrantLegByDrawId($guildId, $drawId);
        if ($grantLeg === null) {
            return null;
        }

        return self::buildTracePayload(
            [
                (string) $itemLeg['traceKey'] => $itemLeg,
                (string) $grantLeg['traceKey'] => $grantLeg,
            ],
            self::CONFIDENCE_LINKED_EXISTING,
            '',
            'legacy:item_ledger:' . (string) ($itemLeg['historyId'] ?? 0)
        );
    }

    private static function legacyConsumeTraceFromRoleGrantLeg(string $guildId, array $grantLeg): ?array
    {
        $drawId = trim((string) ($grantLeg['drawId'] ?? $grantLeg['sourceId'] ?? ''));
        if ($drawId === '') {
            return null;
        }

        $itemLegs = self::itemLegsByConsumeDrawId($guildId, $drawId);
        if ($itemLegs === []) {
            return null;
        }

        $legs = [(string) $grantLeg['traceKey'] => $grantLeg];
        foreach ($itemLegs as $itemLeg) {
            $legs[(string) $itemLeg['traceKey']] = $itemLeg;
        }

        return self::buildTracePayload(
            $legs,
            self::CONFIDENCE_LINKED_EXISTING,
            '',
            'legacy:role_grant:' . (string) ($grantLeg['historyId'] ?? 0)
        );
    }

    private static function legacyShopPurchaseTraceFromWalletLeg(string $guildId, array $walletLeg): ?array
    {
        $candidates = self::filterLegacyShopItemCandidates(
            $walletLeg,
            self::itemLegCandidatesByPurchaseSeed($guildId, $walletLeg)
        );
        if (count($candidates) !== 1) {
            return null;
        }

        $itemLeg = $candidates[0];
        return self::buildTracePayload(
            [
                (string) $walletLeg['traceKey'] => $walletLeg,
                (string) $itemLeg['traceKey'] => $itemLeg,
            ],
            self::CONFIDENCE_LINKED_EXISTING,
            '',
            'legacy:wallet_ledger:' . (string) ($walletLeg['historyId'] ?? 0)
        );
    }

    private static function legacyShopPurchaseTraceFromItemLeg(string $guildId, array $itemLeg): ?array
    {
        $candidates = self::filterLegacyShopWalletCandidates(
            $itemLeg,
            self::walletLegCandidatesByPurchaseSeed($guildId, $itemLeg)
        );
        if (count($candidates) !== 1) {
            return null;
        }

        $walletLeg = $candidates[0];
        return self::buildTracePayload(
            [
                (string) $walletLeg['traceKey'] => $walletLeg,
                (string) $itemLeg['traceKey'] => $itemLeg,
            ],
            self::CONFIDENCE_LINKED_EXISTING,
            '',
            'legacy:item_ledger:' . (string) ($itemLeg['historyId'] ?? 0)
        );
    }

    /** @return array<int, array<string, mixed>> */
    private static function walletLegsByRewardEventId(string $guildId, string $userId, int $rewardEventId): array
    {
        if ($rewardEventId <= 0) {
            return [];
        }
        $params = [
            'guildId' => $guildId,
            'userId' => $userId,
            'sourceId' => (string) $rewardEventId,
        ];
        $rows = Database::fetchAll(
            'SELECT proj.*,
                    sw.guildId,
                    sw.userId,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, sw.userId) AS displayName,
                    COALESCE(unit.displayName, proj.unitCode) AS unitLabel,
                    COALESCE(unit.shortName, proj.unitCode) AS unitShortName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, proj.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, proj.targetUserId) AS targetDisplayName
               FROM ' . self::walletLedgerProjectionSql() . ' proj
         INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = proj.shopWalletId
          LEFT JOIN tbl_shop_unit unit ON unit.unitCode = proj.unitCode
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = sw.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = sw.guildId AND ownerMember.userId = sw.userId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = proj.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = sw.guildId AND actorMember.userId = proj.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = proj.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = sw.guildId AND targetMember.userId = proj.targetUserId
              WHERE sw.guildId = :guildId
                AND sw.userId = :userId
                AND proj.sourceType IN ("earn_rule", "earn_manual")
                AND proj.sourceId = :sourceId
           ORDER BY proj.createDate ASC, proj.shopWalletLedgerId ASC',
            $params
        );

        return array_map(
            static fn (array $row): array => self::normalizeWalletLeg($row, self::CONFIDENCE_LINKED_EXISTING),
            $rows
        );
    }

    /** @return array<int, array<string, mixed>> */
    private static function itemLegCandidatesByPurchaseSeed(string $guildId, array $walletLeg): array
    {
        $sourceType = trim((string) ($walletLeg['sourceType'] ?? ''));
        $sourceId = trim((string) ($walletLeg['sourceId'] ?? ''));
        $createDate = trim((string) ($walletLeg['createDate'] ?? ''));
        if ($sourceType === '' || $sourceId === '' || $createDate === '') {
            return [];
        }

        $rows = Database::fetchAll(
            'SELECT il.shopInventoryLedgerId,
                    il.guildId,
                    il.userId,
                    il.shopInventoryId,
                    il.shopItemId,
                    il.quantityDelta,
                    il.quantityBefore,
                    il.quantityAfter,
                    il.ledgerType,
                    il.sourceType,
                    il.sourceId,
                    il.transactionGroupId,
                    il.actorUserId,
                    il.targetUserId,
                    il.metadataJson,
                    il.createDate,
                    item.itemCode,
                    item.itemName,
                    item.itemType,
                    item.image,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, il.userId) AS displayName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, il.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, il.targetUserId) AS targetDisplayName
               FROM tbl_shop_inventory_ledger il
         INNER JOIN tbl_shop_item item ON item.shopItemId = il.shopItemId
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = il.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = il.guildId AND ownerMember.userId = il.userId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = il.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = il.guildId AND actorMember.userId = il.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = il.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = il.guildId AND targetMember.userId = il.targetUserId
              WHERE il.guildId = :guildId
                AND il.sourceType = :sourceType
                AND il.sourceId = :sourceId
                AND il.createDate = :createDate
           ORDER BY il.shopInventoryLedgerId ASC',
            [
                'guildId' => $guildId,
                'sourceType' => $sourceType,
                'sourceId' => $sourceId,
                'createDate' => $createDate,
            ]
        );

        return array_map(
            static fn (array $row): array => self::normalizeItemLeg($row, self::CONFIDENCE_LINKED_EXISTING),
            $rows
        );
    }

    /** @return array<int, array<string, mixed>> */
    private static function walletLegCandidatesByPurchaseSeed(string $guildId, array $itemLeg): array
    {
        $sourceType = trim((string) ($itemLeg['sourceType'] ?? ''));
        $sourceId = trim((string) ($itemLeg['sourceId'] ?? ''));
        $createDate = trim((string) ($itemLeg['createDate'] ?? ''));
        if ($sourceType === '' || $sourceId === '' || $createDate === '') {
            return [];
        }

        $rows = Database::fetchAll(
            'SELECT proj.*,
                    sw.guildId,
                    sw.userId,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, sw.userId) AS displayName,
                    COALESCE(unit.displayName, proj.unitCode) AS unitLabel,
                    COALESCE(unit.shortName, proj.unitCode) AS unitShortName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, proj.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, proj.targetUserId) AS targetDisplayName
               FROM ' . self::walletLedgerProjectionSql() . ' proj
         INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = proj.shopWalletId
          LEFT JOIN tbl_shop_unit unit ON unit.unitCode = proj.unitCode
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = sw.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = sw.guildId AND ownerMember.userId = sw.userId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = proj.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = sw.guildId AND actorMember.userId = proj.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = proj.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = sw.guildId AND targetMember.userId = proj.targetUserId
              WHERE sw.guildId = :guildId
                AND proj.sourceType = :sourceType
                AND proj.sourceId = :sourceId
                AND proj.createDate = :createDate
           ORDER BY proj.shopWalletLedgerId ASC',
            [
                'guildId' => $guildId,
                'sourceType' => $sourceType,
                'sourceId' => $sourceId,
                'createDate' => $createDate,
            ]
        );

        return array_map(
            static fn (array $row): array => self::normalizeWalletLeg($row, self::CONFIDENCE_LINKED_EXISTING),
            $rows
        );
    }

    /** @return array<int, array<string, mixed>> */
    private static function itemLegsByConsumeDrawId(string $guildId, string $drawId): array
    {
        if ($drawId === '') {
            return [];
        }

        $rows = Database::fetchAll(
            'SELECT il.shopInventoryLedgerId,
                    il.guildId,
                    il.userId,
                    il.shopInventoryId,
                    il.shopItemId,
                    il.quantityDelta,
                    il.quantityBefore,
                    il.quantityAfter,
                    il.ledgerType,
                    il.sourceType,
                    il.sourceId,
                    il.transactionGroupId,
                    il.actorUserId,
                    il.targetUserId,
                    il.metadataJson,
                    il.createDate,
                    item.itemCode,
                    item.itemName,
                    item.itemType,
                    item.image,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, il.userId) AS displayName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, il.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, il.targetUserId) AS targetDisplayName
               FROM tbl_shop_inventory_ledger il
         INNER JOIN tbl_shop_item item ON item.shopItemId = il.shopItemId
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = il.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = il.guildId AND ownerMember.userId = il.userId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = il.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = il.guildId AND actorMember.userId = il.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = il.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = il.guildId AND targetMember.userId = il.targetUserId
              WHERE il.guildId = :guildId
                AND il.sourceType = "shop_role_badge_consume"
                AND il.sourceId = :drawId
           ORDER BY il.createDate ASC, il.shopInventoryLedgerId ASC',
            ['guildId' => $guildId, 'drawId' => $drawId]
        );

        return array_map(
            static fn (array $row): array => self::normalizeItemLeg($row, self::CONFIDENCE_LINKED_EXISTING),
            $rows
        );
    }

    /** @return array<int, array<string, mixed>> */
    private static function filterLegacyShopItemCandidates(array $walletLeg, array $itemLegs): array
    {
        $walletMeta = is_array($walletLeg['metadata'] ?? null) ? $walletLeg['metadata'] : self::decodeJson($walletLeg['metadataJson'] ?? '');
        $sourceType = trim((string) ($walletLeg['sourceType'] ?? ''));
        $sourceId = trim((string) ($walletLeg['sourceId'] ?? ''));
        $payerUserId = trim((string) ($walletLeg['userId'] ?? ''));
        $inventoryOwnerUserId = trim((string) ($walletMeta['inventoryOwnerUserId'] ?? ''));
        $targetUserId = trim((string) ($walletLeg['targetUserId'] ?? $walletMeta['targetUserId'] ?? ''));
        $optionId = trim((string) ($walletMeta['optionId'] ?? ''));
        $paymentUnitCode = trim((string) ($walletMeta['paymentUnitCode'] ?? ''));
        $paymentAmount = (int) ($walletMeta['paymentAmount'] ?? 0);

        return array_values(array_filter($itemLegs, static function (array $itemLeg) use ($sourceType, $sourceId, $payerUserId, $inventoryOwnerUserId, $targetUserId, $optionId, $paymentUnitCode, $paymentAmount): bool {
            if (trim((string) ($itemLeg['sourceType'] ?? '')) !== $sourceType || trim((string) ($itemLeg['sourceId'] ?? '')) !== $sourceId) {
                return false;
            }

            $itemMeta = is_array($itemLeg['metadata'] ?? null) ? $itemLeg['metadata'] : self::decodeJson($itemLeg['metadataJson'] ?? '');
            if ($optionId !== '' && trim((string) ($itemMeta['optionId'] ?? '')) !== $optionId) {
                return false;
            }
            if ($paymentUnitCode !== '' && trim((string) ($itemMeta['paymentUnitCode'] ?? '')) !== $paymentUnitCode) {
                return false;
            }
            if ($paymentAmount > 0 && (int) ($itemMeta['paymentAmount'] ?? 0) !== $paymentAmount) {
                return false;
            }
            if ($payerUserId !== '' && trim((string) ($itemLeg['actorUserId'] ?? '')) !== '' && trim((string) ($itemLeg['actorUserId'] ?? '')) !== $payerUserId) {
                return false;
            }
            if ($inventoryOwnerUserId !== '' && trim((string) ($itemLeg['userId'] ?? '')) !== $inventoryOwnerUserId) {
                return false;
            }
            if ($targetUserId !== '') {
                $candidateTarget = trim((string) ($itemLeg['targetUserId'] ?? ''));
                $candidateOwner = trim((string) ($itemLeg['userId'] ?? ''));
                if ($candidateTarget !== '' && $candidateTarget !== $targetUserId) {
                    return false;
                }
                if ($candidateTarget === '' && $candidateOwner !== $targetUserId) {
                    return false;
                }
            }

            return true;
        }));
    }

    /** @return array<int, array<string, mixed>> */
    private static function filterLegacyShopWalletCandidates(array $itemLeg, array $walletLegs): array
    {
        $itemMeta = is_array($itemLeg['metadata'] ?? null) ? $itemLeg['metadata'] : self::decodeJson($itemLeg['metadataJson'] ?? '');
        $sourceType = trim((string) ($itemLeg['sourceType'] ?? ''));
        $sourceId = trim((string) ($itemLeg['sourceId'] ?? ''));
        $ownerUserId = trim((string) ($itemLeg['userId'] ?? ''));
        $actorUserId = trim((string) ($itemLeg['actorUserId'] ?? ''));
        $targetUserId = trim((string) ($itemLeg['targetUserId'] ?? ''));
        $optionId = trim((string) ($itemMeta['optionId'] ?? ''));
        $paymentUnitCode = trim((string) ($itemMeta['paymentUnitCode'] ?? ''));
        $paymentAmount = (int) ($itemMeta['paymentAmount'] ?? 0);

        return array_values(array_filter($walletLegs, static function (array $walletLeg) use ($sourceType, $sourceId, $ownerUserId, $actorUserId, $targetUserId, $optionId, $paymentUnitCode, $paymentAmount): bool {
            if (trim((string) ($walletLeg['sourceType'] ?? '')) !== $sourceType || trim((string) ($walletLeg['sourceId'] ?? '')) !== $sourceId) {
                return false;
            }

            $walletMeta = is_array($walletLeg['metadata'] ?? null) ? $walletLeg['metadata'] : self::decodeJson($walletLeg['metadataJson'] ?? '');
            if ($optionId !== '' && trim((string) ($walletMeta['optionId'] ?? '')) !== $optionId) {
                return false;
            }
            if ($paymentUnitCode !== '' && trim((string) ($walletMeta['paymentUnitCode'] ?? '')) !== $paymentUnitCode) {
                return false;
            }
            if ($paymentAmount > 0 && (int) ($walletMeta['paymentAmount'] ?? 0) !== $paymentAmount) {
                return false;
            }

            $walletUserId = trim((string) ($walletLeg['userId'] ?? ''));
            if ($actorUserId !== '' && $walletUserId !== $actorUserId) {
                return false;
            }

            $inventoryOwnerUserId = trim((string) ($walletMeta['inventoryOwnerUserId'] ?? ''));
            if ($ownerUserId !== '' && $inventoryOwnerUserId !== '' && $inventoryOwnerUserId !== $ownerUserId) {
                return false;
            }

            $walletTargetUserId = trim((string) ($walletLeg['targetUserId'] ?? $walletMeta['targetUserId'] ?? ''));
            if ($targetUserId !== '' && $walletTargetUserId !== '' && $walletTargetUserId !== $targetUserId) {
                return false;
            }

            return true;
        }));
    }

    /** @return array<string, mixed>|null */
    private static function walletLegById(string $guildId, int $historyId): ?array
    {
        if ($historyId <= 0) {
            return null;
        }

        $row = Database::fetch(
            'SELECT proj.*,
                    sw.guildId,
                    sw.userId,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, sw.userId) AS displayName,
                    COALESCE(unit.displayName, proj.unitCode) AS unitLabel,
                    COALESCE(unit.shortName, proj.unitCode) AS unitShortName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, proj.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, proj.targetUserId) AS targetDisplayName
               FROM ' . self::walletLedgerProjectionSql() . ' proj
         INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = proj.shopWalletId
          LEFT JOIN tbl_shop_unit unit ON unit.unitCode = proj.unitCode
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = sw.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = sw.guildId AND ownerMember.userId = sw.userId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = proj.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = sw.guildId AND actorMember.userId = proj.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = proj.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = sw.guildId AND targetMember.userId = proj.targetUserId
              WHERE sw.guildId = :guildId
                AND proj.shopWalletLedgerId = :historyId
              LIMIT 1',
            ['guildId' => $guildId, 'historyId' => $historyId]
        );

        return $row ? self::normalizeWalletLeg($row, self::traceConfidenceForRow($row + ['historyKind' => 'wallet_ledger'])) : null;
    }

    /** @return array<int, array<string, mixed>> */
    private static function walletLegsByTransactionGroupId(string $guildId, string $transactionGroupId): array
    {
        $rows = Database::fetchAll(
            'SELECT proj.*,
                    sw.guildId,
                    sw.userId,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, sw.userId) AS displayName,
                    COALESCE(unit.displayName, proj.unitCode) AS unitLabel,
                    COALESCE(unit.shortName, proj.unitCode) AS unitShortName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, proj.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, proj.targetUserId) AS targetDisplayName
               FROM ' . self::walletLedgerProjectionSql() . ' proj
         INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = proj.shopWalletId
          LEFT JOIN tbl_shop_unit unit ON unit.unitCode = proj.unitCode
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = sw.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = sw.guildId AND ownerMember.userId = sw.userId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = proj.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = sw.guildId AND actorMember.userId = proj.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = proj.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = sw.guildId AND targetMember.userId = proj.targetUserId
              WHERE sw.guildId = :guildId
                AND proj.transactionGroupId = :transactionGroupId
           ORDER BY proj.createDate ASC, proj.shopWalletLedgerId ASC',
            ['guildId' => $guildId, 'transactionGroupId' => $transactionGroupId]
        );

        return array_map(
            static fn (array $row): array => self::normalizeWalletLeg($row, self::CONFIDENCE_AUTHORITATIVE),
            $rows
        );
    }

    /** @return array<string, mixed>|null */
    private static function itemLegById(string $guildId, int $historyId): ?array
    {
        if ($historyId <= 0) {
            return null;
        }

        $row = Database::fetch(
            'SELECT il.shopInventoryLedgerId,
                    il.guildId,
                    il.userId,
                    il.shopInventoryId,
                    il.shopItemId,
                    il.quantityDelta,
                    il.quantityBefore,
                    il.quantityAfter,
                    il.ledgerType,
                    il.sourceType,
                    il.sourceId,
                    il.transactionGroupId,
                    il.actorUserId,
                    il.targetUserId,
                    il.metadataJson,
                    il.createDate,
                    item.itemCode,
                    item.itemName,
                    item.itemType,
                    item.image,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, il.userId) AS displayName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, il.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, il.targetUserId) AS targetDisplayName
               FROM tbl_shop_inventory_ledger il
         INNER JOIN tbl_shop_item item ON item.shopItemId = il.shopItemId
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = il.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = il.guildId AND ownerMember.userId = il.userId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = il.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = il.guildId AND actorMember.userId = il.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = il.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = il.guildId AND targetMember.userId = il.targetUserId
              WHERE il.guildId = :guildId
                AND il.shopInventoryLedgerId = :historyId
              LIMIT 1',
            ['guildId' => $guildId, 'historyId' => $historyId]
        );

        return $row ? self::normalizeItemLeg($row, self::traceConfidenceForRow($row + ['historyKind' => 'item_ledger'])) : null;
    }

    /** @return array<int, array<string, mixed>> */
    private static function itemLegsByTransactionGroupId(string $guildId, string $transactionGroupId): array
    {
        $rows = Database::fetchAll(
            'SELECT il.shopInventoryLedgerId,
                    il.guildId,
                    il.userId,
                    il.shopInventoryId,
                    il.shopItemId,
                    il.quantityDelta,
                    il.quantityBefore,
                    il.quantityAfter,
                    il.ledgerType,
                    il.sourceType,
                    il.sourceId,
                    il.transactionGroupId,
                    il.actorUserId,
                    il.targetUserId,
                    il.metadataJson,
                    il.createDate,
                    item.itemCode,
                    item.itemName,
                    item.itemType,
                    item.image,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, il.userId) AS displayName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, il.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, il.targetUserId) AS targetDisplayName
               FROM tbl_shop_inventory_ledger il
         INNER JOIN tbl_shop_item item ON item.shopItemId = il.shopItemId
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = il.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = il.guildId AND ownerMember.userId = il.userId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = il.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = il.guildId AND actorMember.userId = il.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = il.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = il.guildId AND targetMember.userId = il.targetUserId
              WHERE il.guildId = :guildId
                AND il.transactionGroupId = :transactionGroupId
           ORDER BY il.createDate ASC, il.shopInventoryLedgerId ASC',
            ['guildId' => $guildId, 'transactionGroupId' => $transactionGroupId]
        );

        return array_map(
            static fn (array $row): array => self::normalizeItemLeg($row, self::CONFIDENCE_AUTHORITATIVE),
            $rows
        );
    }

    /** @return array<string, mixed>|null */
    private static function rewardLegById(string $guildId, int $historyId): ?array
    {
        if ($historyId <= 0) {
            return null;
        }

        $row = Database::fetch(
            'SELECT re.rewardEventId,
                    re.rewardRuleId,
                    re.guildId,
                    re.userId,
                    re.sourceType,
                    re.sourceId,
                    re.transactionGroupId,
                    re.rewardStatus,
                    re.metadataJson,
                    re.createDate,
                    rr.ruleCode,
                    rr.ruleName,
                    rr.triggerType,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, re.userId) AS displayName
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = re.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = re.guildId AND ownerMember.userId = re.userId
              WHERE re.guildId = :guildId
                AND re.rewardEventId = :historyId
              LIMIT 1',
            ['guildId' => $guildId, 'historyId' => $historyId]
        );

        return $row ? self::normalizeRewardLeg($row, self::traceConfidenceForRow($row + ['historyKind' => 'reward_event'])) : null;
    }

    /** @return array<int, array<string, mixed>> */
    private static function rewardLegsByTransactionGroupId(string $guildId, string $transactionGroupId): array
    {
        $rows = Database::fetchAll(
            'SELECT re.rewardEventId,
                    re.rewardRuleId,
                    re.guildId,
                    re.userId,
                    re.sourceType,
                    re.sourceId,
                    re.transactionGroupId,
                    re.rewardStatus,
                    re.metadataJson,
                    re.createDate,
                    rr.ruleCode,
                    rr.ruleName,
                    rr.triggerType,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, re.userId) AS displayName
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = re.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = re.guildId AND ownerMember.userId = re.userId
              WHERE re.guildId = :guildId
                AND re.transactionGroupId = :transactionGroupId
           ORDER BY re.createDate ASC, re.rewardEventId ASC',
            ['guildId' => $guildId, 'transactionGroupId' => $transactionGroupId]
        );

        return array_map(
            static fn (array $row): array => self::normalizeRewardLeg($row, self::CONFIDENCE_AUTHORITATIVE),
            $rows
        );
    }

    /** @return array<string, mixed>|null */
    private static function roleGrantLegById(string $guildId, int $historyId): ?array
    {
        if ($historyId <= 0) {
            return null;
        }

        $row = Database::fetch(
            'SELECT gr.*,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, gr.userId) AS displayName
               FROM tbl_gacha_role_grant gr
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = gr.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = gr.guildId AND ownerMember.userId = gr.userId
              WHERE gr.guildId = :guildId
                AND gr.gachaRoleGrantId = :historyId
              LIMIT 1',
            ['guildId' => $guildId, 'historyId' => $historyId]
        );

        return $row ? self::normalizeRoleGrantLeg($row, self::traceConfidenceForRow($row + ['historyKind' => 'role_grant'])) : null;
    }

    /** @return array<string, mixed>|null */
    private static function roleGrantLegByDrawId(string $guildId, string $drawId): ?array
    {
        if ($drawId === '') {
            return null;
        }

        $row = Database::fetch(
            'SELECT gr.*,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, gr.userId) AS displayName
               FROM tbl_gacha_role_grant gr
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = gr.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = gr.guildId AND ownerMember.userId = gr.userId
              WHERE gr.guildId = :guildId
                AND gr.drawId = :drawId
              LIMIT 1',
            ['guildId' => $guildId, 'drawId' => $drawId]
        );

        return $row ? self::normalizeRoleGrantLeg($row, self::CONFIDENCE_LINKED_EXISTING) : null;
    }

    /** @return array<int, array<string, mixed>> */
    private static function roleGrantLegsByTransactionGroupId(string $guildId, string $transactionGroupId): array
    {
        $rows = Database::fetchAll(
            'SELECT gr.*,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, gr.userId) AS displayName
               FROM tbl_gacha_role_grant gr
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = gr.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = gr.guildId AND ownerMember.userId = gr.userId
              WHERE gr.guildId = :guildId
                AND gr.transactionGroupId = :transactionGroupId
           ORDER BY COALESCE(gr.grantedAt, gr.createDate) ASC, gr.gachaRoleGrantId ASC',
            ['guildId' => $guildId, 'transactionGroupId' => $transactionGroupId]
        );

        return array_map(
            static fn (array $row): array => self::normalizeRoleGrantLeg($row, self::CONFIDENCE_AUTHORITATIVE),
            $rows
        );
    }

    /**
     * @param array<string, array<string, mixed>> $legs
     * @return array<string, mixed>
     */
    private static function buildTracePayload(array $legs, string $confidence, string $transactionGroupId, string $traceKey): array
    {
        $orderedLegs = array_values($legs);
        usort($orderedLegs, static function (array $left, array $right): int {
            return strcmp((string) ($left['createDate'] ?? ''), (string) ($right['createDate'] ?? ''))
                ?: ((int) ($left['historySortId'] ?? 0) <=> (int) ($right['historySortId'] ?? 0));
        });

        $sourceTypes = [];
        $historyKinds = [];
        $users = [];
        foreach ($orderedLegs as $leg) {
            $sourceType = trim((string) ($leg['sourceType'] ?? ''));
            if ($sourceType !== '') {
                $sourceTypes[$sourceType] = $sourceType;
            }
            $historyKind = trim((string) ($leg['historyKind'] ?? ''));
            if ($historyKind !== '') {
                $historyKinds[$historyKind] = $historyKind;
            }
            $userId = trim((string) ($leg['userId'] ?? ''));
            if ($userId !== '' && !isset($users[$userId])) {
                $users[$userId] = [
                    'userId' => $userId,
                    'displayName' => (string) ($leg['displayName'] ?? $leg['userName'] ?? $userId),
                    'userName' => (string) ($leg['userName'] ?? ''),
                    'avatarUrl' => (string) ($leg['avatarUrl'] ?? ''),
                ];
            }
        }

        $title = self::traceTitle($orderedLegs, array_values($sourceTypes));

        return [
            'trace' => [
                'transactionGroupId' => $transactionGroupId !== '' ? $transactionGroupId : null,
                'traceKey' => $traceKey,
                'confidence' => $confidence,
                'title' => $title,
                'legCount' => count($orderedLegs),
                'createDateStart' => (string) ($orderedLegs[0]['createDate'] ?? ''),
                'createDateEnd' => (string) ($orderedLegs[count($orderedLegs) - 1]['createDate'] ?? ''),
                'sourceTypes' => array_values($sourceTypes),
                'historyKinds' => array_values($historyKinds),
                'users' => array_values($users),
            ],
            'legs' => array_map(static function (array $leg) use ($confidence): array {
                $leg['traceConfidence'] = $leg['traceConfidence'] ?? $confidence;
                return $leg;
            }, $orderedLegs),
        ];
    }

    /** @param array<int, array<string, mixed>> $legs */
    private static function traceTitle(array $legs, array $sourceTypes): string
    {
        $sourceType = (string) ($sourceTypes[0] ?? '');
        if ($sourceType === 'shop_role_badge_gift') {
            return 'Shop gift trace';
        }
        if ($sourceType === 'shop_role_badge_purchase') {
            return 'Shop purchase trace';
        }
        if ($sourceType === 'shop_role_badge_consume') {
            return 'Role badge consume trace';
        }
        if ($sourceType === 'earn_manual') {
            return 'Manual earn trace';
        }
        if ($sourceType === 'earn_rule') {
            return 'Earn reward trace';
        }
        foreach ($legs as $leg) {
            if ((string) ($leg['historyKind'] ?? '') === 'role_grant') {
                return 'Role grant trace';
            }
        }
        return 'Transaction trace';
    }

    /** @return array<string, mixed> */
    private static function normalizeWalletLeg(array $row, string $confidence): array
    {
        $metadata = self::decodeJson($row['metadataJson'] ?? '');
        $amountDelta = (int) ($row['amountDelta'] ?? 0);
        $userId = trim((string) ($row['userId'] ?? ''));
        $unitLabel = (string) ($row['unitLabel'] ?? $row['unitCode'] ?? 'Wallet');
        $unitShortName = (string) ($row['unitShortName'] ?? $unitLabel);
        [$counterpartyLabel, $counterpartyDirection, $counterpartyUserId] = self::walletCounterparty($row, $metadata);

        return [
            'traceKey' => 'wallet:' . (string) ($row['shopWalletLedgerId'] ?? 0),
            'legKind' => 'wallet_ledger',
            'historyKind' => 'wallet_ledger',
            'historyId' => (int) ($row['shopWalletLedgerId'] ?? 0),
            'historySortId' => (int) ($row['shopWalletLedgerId'] ?? 0),
            'createDate' => (string) ($row['createDate'] ?? ''),
            'userId' => $userId,
            'displayName' => (string) ($row['displayName'] ?? $row['userName'] ?? $userId),
            'userName' => (string) ($row['userName'] ?? ''),
            'avatarUrl' => (string) ($row['avatarUrl'] ?? DiscordAssets::avatar($userId, $row['avatarHash'] ?? null, 64)),
            'movementDirection' => $amountDelta < 0 ? 'out' : 'in',
            'sourceType' => (string) ($row['sourceType'] ?? ''),
            'sourceId' => (string) ($row['sourceId'] ?? ''),
            'transactionGroupId' => self::normalizeTransactionGroupId($row['transactionGroupId'] ?? ''),
            'traceConfidence' => $confidence,
            'actorUserId' => trim((string) ($row['actorUserId'] ?? '')),
            'actorDisplayName' => (string) ($row['actorDisplayName'] ?? ''),
            'targetUserId' => trim((string) ($row['targetUserId'] ?? '')),
            'targetDisplayName' => (string) ($row['targetDisplayName'] ?? ''),
            'counterpartyLabel' => $counterpartyLabel,
            'counterpartyDirection' => $counterpartyDirection,
            'counterpartyUserId' => $counterpartyUserId,
            'assetLabel' => $unitLabel,
            'assetCode' => (string) ($row['unitCode'] ?? ''),
            'deltaValue' => $amountDelta,
            'deltaText' => ($amountDelta > 0 ? '+' : '') . number_format($amountDelta) . ' ' . $unitShortName,
            'walletBalanceBefore' => self::nullableInt($row['walletBalanceBefore'] ?? $row['resolvedWalletBalanceBefore'] ?? null),
            'walletBalanceAfter' => self::nullableInt($row['walletBalanceAfter'] ?? $row['resolvedWalletBalanceAfter'] ?? null),
            'balanceBeforeText' => self::nullableInt($row['walletBalanceBefore'] ?? $row['resolvedWalletBalanceBefore'] ?? null) !== null
                ? number_format((int) ($row['walletBalanceBefore'] ?? $row['resolvedWalletBalanceBefore'])) . ' ' . $unitShortName
                : '-',
            'balanceAfterText' => self::nullableInt($row['walletBalanceAfter'] ?? $row['resolvedWalletBalanceAfter'] ?? null) !== null
                ? number_format((int) ($row['walletBalanceAfter'] ?? $row['resolvedWalletBalanceAfter'])) . ' ' . $unitShortName
                : '-',
            'note' => self::walletNote($metadata),
            'metadata' => $metadata,
            'metadataPretty' => json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /** @return array<string, mixed> */
    private static function normalizeItemLeg(array $row, string $confidence): array
    {
        $metadata = self::decodeJson($row['metadataJson'] ?? '');
        $quantityDelta = (int) ($row['quantityDelta'] ?? 0);
        $ownerUserId = trim((string) ($row['userId'] ?? ''));
        [$counterpartyLabel, $counterpartyDirection, $counterpartyUserId] = self::itemCounterparty($row, $ownerUserId);

        return [
            'traceKey' => 'item:' . (string) ($row['shopInventoryLedgerId'] ?? 0),
            'legKind' => 'item_ledger',
            'historyKind' => 'item_ledger',
            'historyId' => (int) ($row['shopInventoryLedgerId'] ?? 0),
            'historySortId' => (int) ($row['shopInventoryLedgerId'] ?? 0),
            'createDate' => (string) ($row['createDate'] ?? ''),
            'userId' => $ownerUserId,
            'displayName' => (string) ($row['displayName'] ?? $row['userName'] ?? $ownerUserId),
            'userName' => (string) ($row['userName'] ?? ''),
            'avatarUrl' => (string) ($row['avatarUrl'] ?? DiscordAssets::avatar($ownerUserId, $row['avatarHash'] ?? null, 64)),
            'movementDirection' => $quantityDelta < 0 ? 'out' : 'in',
            'sourceType' => (string) ($row['sourceType'] ?? ''),
            'sourceId' => (string) ($row['sourceId'] ?? ''),
            'transactionGroupId' => self::normalizeTransactionGroupId($row['transactionGroupId'] ?? ''),
            'traceConfidence' => $confidence,
            'actorUserId' => trim((string) ($row['actorUserId'] ?? '')),
            'actorDisplayName' => (string) ($row['actorDisplayName'] ?? ''),
            'targetUserId' => trim((string) ($row['targetUserId'] ?? '')),
            'targetDisplayName' => (string) ($row['targetDisplayName'] ?? ''),
            'counterpartyLabel' => $counterpartyLabel,
            'counterpartyDirection' => $counterpartyDirection,
            'counterpartyUserId' => $counterpartyUserId,
            'assetLabel' => (string) ($row['itemName'] ?? $row['itemCode'] ?? 'Item'),
            'assetCode' => (string) ($row['itemCode'] ?? ''),
            'itemType' => (string) ($row['itemType'] ?? ''),
            'deltaValue' => $quantityDelta,
            'deltaText' => ($quantityDelta > 0 ? '+' : '') . $quantityDelta,
            'quantityBefore' => self::nullableInt($row['quantityBefore'] ?? null),
            'quantityAfter' => self::nullableInt($row['quantityAfter'] ?? null),
            'balanceBeforeText' => self::nullableInt($row['quantityBefore'] ?? null) !== null ? (string) ((int) $row['quantityBefore']) : '-',
            'balanceAfterText' => self::nullableInt($row['quantityAfter'] ?? null) !== null ? (string) ((int) $row['quantityAfter']) : '-',
            'note' => self::itemNote($row, $metadata),
            'metadata' => $metadata,
            'metadataPretty' => json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /** @return array<string, mixed> */
    private static function normalizeRewardLeg(array $row, string $confidence): array
    {
        $metadata = self::decodeJson($row['metadataJson'] ?? '');
        $rewardStatus = strtolower(trim((string) ($row['rewardStatus'] ?? 'granted')));
        $outStatuses = ['consumed', 'spent', 'expired'];
        $unitRewards = self::rewardUnitRewards($metadata);
        $userId = trim((string) ($row['userId'] ?? ''));

        return [
            'traceKey' => 'reward:' . (string) ($row['rewardEventId'] ?? 0),
            'legKind' => 'reward_event',
            'historyKind' => 'reward_event',
            'historyId' => (int) ($row['rewardEventId'] ?? 0),
            'historySortId' => (int) ($row['rewardEventId'] ?? 0),
            'createDate' => (string) ($row['createDate'] ?? ''),
            'userId' => $userId,
            'displayName' => (string) ($row['displayName'] ?? $row['userName'] ?? $userId),
            'userName' => (string) ($row['userName'] ?? ''),
            'avatarUrl' => (string) ($row['avatarUrl'] ?? DiscordAssets::avatar($userId, $row['avatarHash'] ?? null, 64)),
            'movementDirection' => in_array($rewardStatus, $outStatuses, true) ? 'out' : 'in',
            'sourceType' => (string) ($row['sourceType'] ?? ''),
            'sourceId' => (string) ($row['sourceId'] ?? ''),
            'transactionGroupId' => self::normalizeTransactionGroupId($row['transactionGroupId'] ?? ''),
            'traceConfidence' => $confidence,
            'counterpartyLabel' => '',
            'counterpartyDirection' => '',
            'counterpartyUserId' => '',
            'assetLabel' => (string) ($row['ruleName'] ?? $row['ruleCode'] ?? $row['sourceType'] ?? 'Reward'),
            'assetCode' => (string) ($row['ruleCode'] ?? ''),
            'deltaValue' => array_sum(array_map('abs', $unitRewards)),
            'deltaText' => self::formatRewardUnitRewards($unitRewards, in_array($rewardStatus, $outStatuses, true)),
            'rewardStatus' => $rewardStatus,
            'ruleCode' => (string) ($row['ruleCode'] ?? ''),
            'ruleName' => (string) ($row['ruleName'] ?? ''),
            'triggerType' => (string) ($row['triggerType'] ?? ''),
            'note' => self::rewardNote($row, $metadata),
            'metadata' => $metadata,
            'metadataPretty' => json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /** @return array<string, mixed> */
    private static function normalizeRoleGrantLeg(array $row, string $confidence): array
    {
        $metadata = self::decodeJson($row['metadataJson'] ?? '');
        $userId = trim((string) ($row['userId'] ?? ''));
        $durationDays = max(0, (int) ($row['durationDays'] ?? 0));
        $grantStatus = trim((string) ($row['grantStatus'] ?? 'pending'));
        $createDate = trim((string) ($row['grantedAt'] ?? $row['createDate'] ?? ''));

        return [
            'traceKey' => 'grant:' . (string) ($row['gachaRoleGrantId'] ?? 0),
            'legKind' => 'role_grant',
            'historyKind' => 'role_grant',
            'historyId' => (int) ($row['gachaRoleGrantId'] ?? 0),
            'historySortId' => (int) ($row['gachaRoleGrantId'] ?? 0),
            'createDate' => $createDate,
            'userId' => $userId,
            'displayName' => (string) ($row['displayName'] ?? $row['userName'] ?? $userId),
            'userName' => (string) ($row['userName'] ?? ''),
            'avatarUrl' => (string) ($row['avatarUrl'] ?? DiscordAssets::avatar($userId, $row['avatarHash'] ?? null, 64)),
            'movementDirection' => 'grant',
            'sourceType' => 'gacha_role_grant',
            'sourceId' => (string) ($row['drawId'] ?? ''),
            'transactionGroupId' => self::normalizeTransactionGroupId($row['transactionGroupId'] ?? ''),
            'traceConfidence' => $confidence,
            'counterpartyLabel' => '',
            'counterpartyDirection' => '',
            'counterpartyUserId' => '',
            'assetLabel' => (string) ($row['roleName'] ?? $row['prizeName'] ?? $row['roleId'] ?? 'Role grant'),
            'assetCode' => (string) ($row['roleId'] ?? ''),
            'deltaValue' => $durationDays,
            'deltaText' => $durationDays > 0 ? $durationDays . ' days' : 'permanent',
            'drawId' => (string) ($row['drawId'] ?? ''),
            'grantStatus' => $grantStatus,
            'expireAt' => (string) ($row['expireAt'] ?? ''),
            'note' => self::roleGrantNote($row),
            'metadata' => $metadata,
            'metadataPretty' => json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /** @return array{0:string,1:string,2:string} */
    private static function itemCounterparty(array $row, string $ownerUserId): array
    {
        $targetUserId = trim((string) ($row['targetUserId'] ?? ''));
        if ($targetUserId !== '' && $targetUserId !== $ownerUserId) {
            return [
                trim((string) ($row['targetDisplayName'] ?? $targetUserId)),
                'to',
                $targetUserId,
            ];
        }

        $actorUserId = trim((string) ($row['actorUserId'] ?? ''));
        if ($actorUserId !== '' && $actorUserId !== $ownerUserId) {
            return [
                trim((string) ($row['actorDisplayName'] ?? $actorUserId)),
                'from',
                $actorUserId,
            ];
        }

        return ['', '', ''];
    }

    /** @return array{0:string,1:string,2:string} */
    private static function walletCounterparty(array $row, array $metadata): array
    {
        $ownerUserId = trim((string) ($row['userId'] ?? ''));

        $targetUserId = trim((string) ($row['targetUserId'] ?? $metadata['targetUserId'] ?? ''));
        $targetDisplayName = trim((string) ($row['targetDisplayName'] ?? $metadata['targetDisplayName'] ?? ''));
        if (($targetUserId !== '' || $targetDisplayName !== '') && $targetUserId !== $ownerUserId) {
            return [$targetDisplayName !== '' ? $targetDisplayName : $targetUserId, 'to', $targetUserId];
        }

        $inventoryOwnerUserId = trim((string) ($metadata['inventoryOwnerUserId'] ?? ''));
        $inventoryOwnerDisplayName = trim((string) ($metadata['inventoryOwnerDisplayName'] ?? ''));
        if (($inventoryOwnerUserId !== '' || $inventoryOwnerDisplayName !== '') && $inventoryOwnerUserId !== $ownerUserId) {
            return [$inventoryOwnerDisplayName !== '' ? $inventoryOwnerDisplayName : $inventoryOwnerUserId, 'for', $inventoryOwnerUserId];
        }

        $actorUserId = trim((string) ($row['actorUserId'] ?? ''));
        $actorDisplayName = trim((string) ($row['actorDisplayName'] ?? ''));
        if (($actorUserId !== '' || $actorDisplayName !== '') && $actorUserId !== $ownerUserId) {
            return [$actorDisplayName !== '' ? $actorDisplayName : $actorUserId, 'from', $actorUserId];
        }

        return ['', '', ''];
    }

    private static function itemNote(array $row, array $metadata): string
    {
        $parts = [];
        foreach (['productName', 'optionLabel', 'roleName'] as $key) {
            $value = trim((string) ($metadata[$key] ?? ''));
            if ($value !== '' && !in_array($value, $parts, true)) {
                $parts[] = $value;
            }
        }
        $paymentAmount = (int) ($metadata['paymentAmount'] ?? 0);
        $paymentUnitCode = trim((string) ($metadata['paymentUnitCode'] ?? ''));
        if ($paymentAmount > 0 && $paymentUnitCode !== '') {
            $parts[] = number_format($paymentAmount) . ' ' . $paymentUnitCode;
        }
        if ($parts === []) {
            $parts[] = (string) ($row['itemName'] ?? $row['itemCode'] ?? 'Item');
        }
        return implode(' · ', $parts);
    }

    private static function walletNote(array $metadata): string
    {
        $parts = [];
        foreach (['productName', 'optionLabel', 'roleName'] as $key) {
            $value = trim((string) ($metadata[$key] ?? ''));
            if ($value !== '' && !in_array($value, $parts, true)) {
                $parts[] = $value;
            }
        }
        $rule = trim((string) ($metadata['rule'] ?? ''));
        if ($rule !== '' && $parts === []) {
            $parts[] = $rule;
        }
        $reason = trim((string) ($metadata['reason'] ?? ''));
        if ($reason !== '' && !in_array($reason, $parts, true)) {
            $parts[] = $reason;
        }
        return $parts ? implode(' · ', $parts) : '-';
    }

    private static function rewardNote(array $row, array $metadata): string
    {
        $parts = [];
        $ruleCode = trim((string) ($metadata['rule'] ?? $row['ruleCode'] ?? ''));
        $sourceId = trim((string) ($row['sourceId'] ?? ''));
        if ($ruleCode !== '') {
            $parts[] = $ruleCode;
        }
        if ($sourceId !== '') {
            $parts[] = $sourceId;
        }
        if (!empty($metadata['manualGrant']) && is_array($metadata['manualGrant'])) {
            $targetLabel = trim((string) ($metadata['manualGrant']['targetLabel'] ?? ''));
            $reason = trim((string) ($metadata['reason'] ?? $metadata['manualGrant']['reason'] ?? ''));
            if ($targetLabel !== '') {
                $parts[] = $targetLabel;
            }
            if ($reason !== '') {
                $parts[] = $reason;
            }
        }
        return $parts ? implode(' · ', $parts) : '-';
    }

    private static function roleGrantNote(array $row): string
    {
        $parts = [];
        $grantStatus = trim((string) ($row['grantStatus'] ?? ''));
        if ($grantStatus !== '') {
            $parts[] = $grantStatus;
        }
        $prizeName = trim((string) ($row['prizeName'] ?? ''));
        if ($prizeName !== '') {
            $parts[] = $prizeName;
        }
        $expireAt = trim((string) ($row['expireAt'] ?? ''));
        if ($expireAt !== '') {
            $parts[] = 'expire ' . $expireAt;
        }
        return $parts ? implode(' · ', $parts) : '-';
    }

    /** @return array<string, int> */
    private static function rewardUnitRewards(array $metadata): array
    {
        $reward = is_array($metadata['reward'] ?? null) ? $metadata['reward'] : [];
        $unitRewards = is_array($reward['unitRewards'] ?? null) ? $reward['unitRewards'] : [];

        foreach (['coin' => 'coin', 'gachaTicket' => 'ticket', 'ticket' => 'ticket'] as $sourceKey => $unitCode) {
            $amount = (int) ($reward[$sourceKey] ?? 0);
            if ($amount > 0) {
                $unitRewards[$unitCode] = max((int) ($unitRewards[$unitCode] ?? 0), $amount);
            }
        }

        $freeSpin = (int) ($reward['gachaFreeSpin'] ?? 0);
        if ($freeSpin > 0) {
            $unitRewards['freeSpin'] = max((int) ($unitRewards['freeSpin'] ?? 0), $freeSpin);
        }

        return array_map(static fn (mixed $amount): int => (int) $amount, $unitRewards);
    }

    private static function formatRewardUnitRewards(array $unitRewards, bool $asOutgoing = false): string
    {
        $parts = [];
        foreach ($unitRewards as $unitCode => $amount) {
            $amount = (int) $amount;
            if ($amount === 0) {
                continue;
            }
            $sign = $asOutgoing ? '-' : '+';
            $parts[] = $sign . number_format(abs($amount)) . ' ' . $unitCode;
        }
        return $parts ? implode(' · ', $parts) : '-';
    }

    /** @return array<string, mixed> */
    private static function decodeJson(mixed $json): array
    {
        $decoded = json_decode((string) ($json ?? ''), true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private static function walletLedgerProjectionSql(): string
    {
        $runningBalanceSql = 'SUM(wl.amountDelta) OVER (
            PARTITION BY wl.shopWalletId
            ORDER BY wl.createDate ASC, wl.shopWalletLedgerId ASC
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
        )';

        return '(
            SELECT wl.shopWalletLedgerId,
                   wl.shopWalletId,
                   wl.unitCode,
                   wl.amountDelta,
                   wl.ledgerType,
                   wl.sourceType,
                   wl.sourceId,
                   wl.transactionGroupId,
                   wl.actorUserId,
                   wl.targetUserId,
                   wl.walletBalanceBefore,
                   wl.walletBalanceAfter,
                   wl.metadataJson,
                   wl.createDate,
                   COALESCE(wl.walletBalanceAfter, ' . $runningBalanceSql . ') AS resolvedWalletBalanceAfter,
                   COALESCE(
                        wl.walletBalanceBefore,
                        COALESCE(wl.walletBalanceAfter, ' . $runningBalanceSql . ') - wl.amountDelta
                   ) AS resolvedWalletBalanceBefore
              FROM tbl_shop_wallet_ledger wl
        )';
    }

    private static function ensureShopWalletLedgerSchema(): void
    {
        if (!self::tableExists('tbl_shop_wallet_ledger')) {
            return;
        }

        self::addColumnIfMissing('tbl_shop_wallet_ledger', 'transactionGroupId', 'ALTER TABLE tbl_shop_wallet_ledger ADD COLUMN transactionGroupId varchar(120) DEFAULT NULL AFTER sourceId');
        self::addColumnIfMissing('tbl_shop_wallet_ledger', 'actorUserId', 'ALTER TABLE tbl_shop_wallet_ledger ADD COLUMN actorUserId varchar(32) DEFAULT NULL AFTER transactionGroupId');
        self::addColumnIfMissing('tbl_shop_wallet_ledger', 'targetUserId', 'ALTER TABLE tbl_shop_wallet_ledger ADD COLUMN targetUserId varchar(32) DEFAULT NULL AFTER actorUserId');
        self::addColumnIfMissing('tbl_shop_wallet_ledger', 'walletBalanceBefore', 'ALTER TABLE tbl_shop_wallet_ledger ADD COLUMN walletBalanceBefore bigint DEFAULT NULL AFTER targetUserId');
        self::addColumnIfMissing('tbl_shop_wallet_ledger', 'walletBalanceAfter', 'ALTER TABLE tbl_shop_wallet_ledger ADD COLUMN walletBalanceAfter bigint DEFAULT NULL AFTER walletBalanceBefore');
        self::addIndexIfMissing('tbl_shop_wallet_ledger', 'idx_tbl_shop_wallet_ledger_trace', 'ALTER TABLE tbl_shop_wallet_ledger ADD KEY idx_tbl_shop_wallet_ledger_trace (transactionGroupId, createDate)');
        self::addIndexIfMissing('tbl_shop_wallet_ledger', 'idx_tbl_shop_wallet_ledger_source', 'ALTER TABLE tbl_shop_wallet_ledger ADD KEY idx_tbl_shop_wallet_ledger_source (sourceType, sourceId, createDate)');
    }

    private static function ensureShopInventoryLedgerSchema(): void
    {
        if (!self::tableExists('tbl_shop_inventory_ledger')) {
            return;
        }

        self::addColumnIfMissing('tbl_shop_inventory_ledger', 'transactionGroupId', 'ALTER TABLE tbl_shop_inventory_ledger ADD COLUMN transactionGroupId varchar(120) DEFAULT NULL AFTER sourceId');
        self::addColumnIfMissing('tbl_shop_inventory_ledger', 'quantityBefore', 'ALTER TABLE tbl_shop_inventory_ledger ADD COLUMN quantityBefore int DEFAULT NULL AFTER quantityDelta');
        self::addColumnIfMissing('tbl_shop_inventory_ledger', 'quantityAfter', 'ALTER TABLE tbl_shop_inventory_ledger ADD COLUMN quantityAfter int DEFAULT NULL AFTER quantityBefore');
        self::addIndexIfMissing('tbl_shop_inventory_ledger', 'idx_tbl_shop_inventory_ledger_trace', 'ALTER TABLE tbl_shop_inventory_ledger ADD KEY idx_tbl_shop_inventory_ledger_trace (transactionGroupId, createDate)');
    }

    private static function ensureRewardEventSchema(): void
    {
        if (!self::tableExists('tbl_reward_event')) {
            return;
        }

        self::addColumnIfMissing('tbl_reward_event', 'transactionGroupId', 'ALTER TABLE tbl_reward_event ADD COLUMN transactionGroupId varchar(120) DEFAULT NULL AFTER sourceId');
        self::addIndexIfMissing('tbl_reward_event', 'idx_tbl_reward_event_trace', 'ALTER TABLE tbl_reward_event ADD KEY idx_tbl_reward_event_trace (transactionGroupId, createDate)');
    }

    private static function ensureRoleGrantSchema(): void
    {
        if (!self::tableExists('tbl_gacha_role_grant')) {
            return;
        }

        self::addColumnIfMissing('tbl_gacha_role_grant', 'transactionGroupId', 'ALTER TABLE tbl_gacha_role_grant ADD COLUMN transactionGroupId varchar(120) DEFAULT NULL AFTER drawId');
        self::addIndexIfMissing('tbl_gacha_role_grant', 'idx_tbl_gacha_role_grant_trace', 'ALTER TABLE tbl_gacha_role_grant ADD KEY idx_tbl_gacha_role_grant_trace (transactionGroupId, createDate)');
    }

    private static function addColumnIfMissing(string $table, string $column, string $sql): void
    {
        if (!self::columnExists($table, $column)) {
            Database::execute($sql);
        }
    }

    private static function addIndexIfMissing(string $table, string $index, string $sql): void
    {
        if (!self::indexExists($table, $index)) {
            Database::execute($sql);
        }
    }

    private static function tableExists(string $table): bool
    {
        return (bool) Database::fetch(
            'SELECT TABLE_NAME
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tableName
             LIMIT 1',
            ['tableName' => $table]
        );
    }

    private static function columnExists(string $table, string $column): bool
    {
        return (bool) Database::fetch(
            'SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tableName
               AND COLUMN_NAME = :columnName
             LIMIT 1',
            ['tableName' => $table, 'columnName' => $column]
        );
    }

    private static function indexExists(string $table, string $index): bool
    {
        return (bool) Database::fetch(
            'SELECT INDEX_NAME
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tableName
               AND INDEX_NAME = :indexName
             LIMIT 1',
            ['tableName' => $table, 'indexName' => $index]
        );
    }
}
