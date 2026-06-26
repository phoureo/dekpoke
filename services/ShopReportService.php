<?php

declare(strict_types=1);

final class ShopReportService
{
    public static function purchaseReport(string $guildId, array $filters = []): array
    {
        ShopUnitService::ensureSchema();
        TransactionTraceService::ensureSchema();

        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = max(25, min(200, (int) ($filters['pageSize'] ?? 50)));
        $offset = ($page - 1) * $pageSize;
        $params = ['guildId' => $guildId];
        $where = ['sw.guildId = :guildId', 'COALESCE(wl.sourceType, "") LIKE "shop_%"'];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            self::appendLikeGroup(
                $where,
                $params,
                [
                    'sw.userId',
                    'COALESCE(u.userName, "")',
                    'COALESCE(u.globalName, "")',
                    'COALESCE(m.nickName, "")',
                    'COALESCE(wl.sourceType, "")',
                    'COALESCE(wl.sourceId, "")',
                    'COALESCE(wl.metadataJson, "")',
                ],
                '%' . $q . '%',
                'purchaseQ'
            );
        }

        foreach (['unitCode' => 'wl.unitCode', 'sourceType' => 'wl.sourceType'] as $filterKey => $column) {
            $value = trim((string) ($filters[$filterKey] ?? ''));
            if ($value === '') {
                continue;
            }
            $params[$filterKey] = $value;
            $where[] = $column . ' = :' . $filterKey;
        }

        $movementType = trim((string) ($filters['movementType'] ?? ''));
        if ($movementType === 'in') {
            $where[] = 'wl.amountDelta > 0';
        } elseif ($movementType === 'out') {
            $where[] = 'wl.amountDelta < 0';
        }

        self::applyDateFilters($where, $params, 'wl.createDate', $filters);

        $whereSql = implode(' AND ', $where);
        $sortMap = [
            'createDate' => 'wl.createDate',
            'displayName' => 'displayName',
            'sourceType' => 'wl.sourceType',
            'unitCode' => 'wl.unitCode',
            'amountDelta' => 'wl.amountDelta',
            'sourceId' => 'wl.sourceId',
        ];
        $sort = (string) ($filters['sort'] ?? 'createDate');
        $dir = strtolower((string) ($filters['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orderBy = $sortMap[$sort] ?? 'wl.createDate';

        $total = Database::fetch(
            'SELECT COUNT(*) AS total
               FROM tbl_shop_wallet_ledger wl
         INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = wl.shopWalletId
          LEFT JOIN tbl_user u ON u.userId = sw.userId
          LEFT JOIN tbl_member m ON m.guildId = sw.guildId AND m.userId = sw.userId
              WHERE ' . $whereSql,
            $params
        ) ?: [];

        $metrics = Database::fetch(
            'SELECT COUNT(*) AS totalPurchases,
                    SUM(CASE WHEN wl.amountDelta < 0 THEN ABS(wl.amountDelta) ELSE 0 END) AS totalSpent,
                    SUM(CASE WHEN wl.sourceType = "shop_role_badge_gift" THEN 1 ELSE 0 END) AS gifts,
                    SUM(CASE WHEN wl.sourceType = "shop_role_badge_purchase" THEN 1 ELSE 0 END) AS selfPurchases
               FROM tbl_shop_wallet_ledger wl
         INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = wl.shopWalletId
          LEFT JOIN tbl_user u ON u.userId = sw.userId
          LEFT JOIN tbl_member m ON m.guildId = sw.guildId AND m.userId = sw.userId
              WHERE ' . $whereSql,
            $params
        ) ?: [];

        $unitMetrics = Database::fetchAll(
            'SELECT wl.unitCode,
                    COALESCE(unit.displayName, wl.unitCode) AS unitLabel,
                    SUM(CASE WHEN wl.amountDelta < 0 THEN ABS(wl.amountDelta) ELSE 0 END) AS spent,
                    COUNT(*) AS countRows
               FROM tbl_shop_wallet_ledger wl
         INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = wl.shopWalletId
          LEFT JOIN tbl_shop_unit unit ON unit.unitCode = wl.unitCode
          LEFT JOIN tbl_user u ON u.userId = sw.userId
          LEFT JOIN tbl_member m ON m.guildId = sw.guildId AND m.userId = sw.userId
              WHERE ' . $whereSql . '
           GROUP BY wl.unitCode, unit.displayName
           ORDER BY spent DESC, wl.unitCode ASC',
            $params
        );

        $rows = Database::fetchAll(
            'SELECT wl.shopWalletLedgerId,
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
                    sw.userId,
                    COALESCE(m.nickName, u.globalName, u.userName, sw.userId) AS displayName,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    COALESCE(unit.displayName, wl.unitCode) AS unitLabel,
                    COALESCE(unit.shortName, wl.unitCode) AS unitShortName
               FROM tbl_shop_wallet_ledger wl
         INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = wl.shopWalletId
          LEFT JOIN tbl_user u ON u.userId = sw.userId
          LEFT JOIN tbl_member m ON m.guildId = sw.guildId AND m.userId = sw.userId
          LEFT JOIN tbl_shop_unit unit ON unit.unitCode = wl.unitCode
              WHERE ' . $whereSql . '
           ORDER BY ' . $orderBy . ' ' . $dir . ', wl.shopWalletLedgerId ' . $dir . '
              LIMIT ' . $pageSize . ' OFFSET ' . $offset,
            $params
        );

        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => (int) ($total['total'] ?? 0),
            'metrics' => [
                'totalPurchases' => (int) ($metrics['totalPurchases'] ?? 0),
                'totalSpent' => (int) ($metrics['totalSpent'] ?? 0),
                'gifts' => (int) ($metrics['gifts'] ?? 0),
                'selfPurchases' => (int) ($metrics['selfPurchases'] ?? 0),
                'unitMetrics' => array_map(static fn (array $row): array => [
                    'unitCode' => (string) ($row['unitCode'] ?? ''),
                    'label' => (string) ($row['unitLabel'] ?? $row['unitCode'] ?? ''),
                    'spent' => (int) ($row['spent'] ?? 0),
                    'countRows' => (int) ($row['countRows'] ?? 0),
                ], $unitMetrics),
            ],
            'rows' => array_map([self::class, 'decoratePurchaseRow'], $rows),
        ];
    }

    public static function memberBagReport(string $guildId, array $filters = []): array
    {
        ShopUnitService::ensureSchema();
        TransactionTraceService::ensureSchema();
        $walletColumns = array_values(ShopUnitService::units(true));

        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = max(25, min(200, (int) ($filters['pageSize'] ?? 50)));
        $offset = ($page - 1) * $pageSize;
        $params = ['guildId' => $guildId];
        $where = ['m.guildId = :guildId'];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            self::appendLikeGroup(
                $where,
                $params,
                [
                    'm.userId',
                    'COALESCE(u.userName, "")',
                    'COALESCE(u.globalName, "")',
                    'COALESCE(m.nickName, "")',
                    'COALESCE(inv.itemSummary, "")',
                ],
                '%' . $q . '%',
                'memberBagQ'
            );
        }

        if (!empty($filters['hideInactive'])) {
            $where[] = 'm.isActive = 1';
        }

        $unitCode = trim((string) ($filters['unitCode'] ?? ''));
        if ($unitCode !== '') {
            $params['unitCode'] = $unitCode;
            $where[] = 'EXISTS (
                SELECT 1 FROM tbl_shop_wallet fw
                 WHERE fw.guildId = m.guildId AND fw.userId = m.userId AND fw.unitCode = :unitCode
            )';
        }

        $itemType = trim((string) ($filters['itemType'] ?? ''));
        if ($itemType !== '') {
            $params['itemType'] = $itemType;
            $where[] = 'EXISTS (
                SELECT 1 FROM tbl_shop_inventory fi
                INNER JOIN tbl_shop_item fitem ON fitem.shopItemId = fi.shopItemId
                 WHERE fi.guildId = m.guildId AND fi.userId = m.userId AND fi.quantity > 0 AND fitem.itemType = :itemType
            )';
        }

        $itemCode = trim((string) ($filters['itemCode'] ?? ''));
        if ($itemCode !== '') {
            $itemCodeValue = '%' . $itemCode . '%';
            $itemCodeConditions = [];
            foreach (['fcitem.itemCode', 'fcitem.itemName'] as $index => $column) {
                $placeholder = 'itemCodeLike' . $index;
                $params[$placeholder] = $itemCodeValue;
                $itemCodeConditions[] = $column . ' LIKE :' . $placeholder;
            }
            $where[] = 'EXISTS (
                SELECT 1 FROM tbl_shop_inventory fci
                INNER JOIN tbl_shop_item fcitem ON fcitem.shopItemId = fci.shopItemId
                 WHERE fci.guildId = m.guildId AND fci.userId = m.userId AND fci.quantity > 0
                   AND (' . implode(' OR ', $itemCodeConditions) . ')
            )';
        }

        if (!empty($filters['onlyWithWallet'])) {
            $where[] = 'COALESCE(wallet.walletTotal, 0) <> 0';
        }
        if (!empty($filters['onlyWithInventory'])) {
            $where[] = 'COALESCE(inv.itemQuantity, 0) > 0';
        }

        $whereSql = implode(' AND ', $where);
        $walletSql = self::walletSummarySql();
        $inventorySql = self::inventorySummarySql();
        $walletColumnSelects = [];
        $walletColumnJoins = [];
        $walletColumnMap = [];
        foreach ($walletColumns as $index => $unit) {
            $unitCode = (string) ($unit['unitCode'] ?? '');
            if ($unitCode === '') {
                continue;
            }
            $alias = 'walletUnit' . $index;
            $field = 'walletUnitValue' . $index;
            $walletColumnSelects[] = 'COALESCE(' . $alias . '.balanceAmount, 0) AS ' . $field;
            $walletColumnJoins[] = 'LEFT JOIN tbl_shop_wallet ' . $alias . ' ON ' . $alias . '.guildId = m.guildId AND ' . $alias . '.userId = m.userId AND ' . $alias . '.unitCode = ' . Database::pdo()->quote($unitCode);
            $walletColumnMap[$unitCode] = ['field' => $field, 'orderBy' => 'COALESCE(' . $alias . '.balanceAmount, 0)'];
        }
        $sortMap = [
            'displayName' => 'displayName',
            'walletTotal' => 'wallet.walletTotal',
            'itemQuantity' => 'inv.itemQuantity',
            'updateDate' => 'lastActivityDate',
            'isActive' => 'm.isActive',
        ];
        foreach ($walletColumnMap as $unitCode => $column) {
            $sortMap['wallet:' . $unitCode] = $column['orderBy'];
        }
        $sort = (string) ($filters['sort'] ?? 'displayName');
        $dir = strtolower((string) ($filters['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderBy = $sortMap[$sort] ?? 'displayName';

        $total = Database::fetch(
            'SELECT COUNT(*) AS total
               FROM tbl_member m
          LEFT JOIN tbl_user u ON u.userId = m.userId
          LEFT JOIN ' . $walletSql . ' wallet ON wallet.guildId = m.guildId AND wallet.userId = m.userId
          LEFT JOIN ' . $inventorySql . ' inv ON inv.guildId = m.guildId AND inv.userId = m.userId
              WHERE ' . $whereSql,
            $params
        ) ?: [];

        $metrics = Database::fetch(
            'SELECT COUNT(*) AS memberCount,
                    SUM(CASE WHEN COALESCE(wallet.walletTotal, 0) <> 0 THEN 1 ELSE 0 END) AS membersWithWallet,
                    SUM(CASE WHEN COALESCE(inv.itemQuantity, 0) > 0 THEN 1 ELSE 0 END) AS membersWithItems,
                    SUM(COALESCE(inv.itemQuantity, 0)) AS totalItemQuantity
               FROM tbl_member m
          LEFT JOIN tbl_user u ON u.userId = m.userId
          LEFT JOIN ' . $walletSql . ' wallet ON wallet.guildId = m.guildId AND wallet.userId = m.userId
          LEFT JOIN ' . $inventorySql . ' inv ON inv.guildId = m.guildId AND inv.userId = m.userId
              WHERE ' . $whereSql,
            $params
        ) ?: [];

        $rows = Database::fetchAll(
            'SELECT m.userId,
                    m.isActive,
                    COALESCE(m.nickName, u.globalName, u.userName, m.userId) AS displayName,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    COALESCE(wallet.walletTotal, 0) AS walletTotal,
                    COALESCE(wallet.walletSummary, "") AS walletSummary,
                    COALESCE(inv.itemQuantity, 0) AS itemQuantity,
                    COALESCE(inv.itemSummary, "") AS itemSummary,
                    GREATEST(COALESCE(wallet.walletUpdateDate, "1970-01-01 00:00:00"), COALESCE(inv.inventoryUpdateDate, "1970-01-01 00:00:00")) AS lastActivityDate
                    ' . ($walletColumnSelects ? ', ' . implode(', ', $walletColumnSelects) : '') . '
               FROM tbl_member m
          LEFT JOIN tbl_user u ON u.userId = m.userId
          LEFT JOIN ' . $walletSql . ' wallet ON wallet.guildId = m.guildId AND wallet.userId = m.userId
          LEFT JOIN ' . $inventorySql . ' inv ON inv.guildId = m.guildId AND inv.userId = m.userId
          ' . implode("\n          ", $walletColumnJoins) . '
              WHERE ' . $whereSql . '
           ORDER BY ' . $orderBy . ' ' . $dir . ', displayName ASC
              LIMIT ' . $pageSize . ' OFFSET ' . $offset,
            $params
        );

        $decoratedRows = array_map([self::class, 'decorateMemberBagRow'], $rows);
        foreach ($decoratedRows as &$row) {
            $walletMap = [];
            foreach ($walletColumnMap as $unitCode => $column) {
                $field = (string) $column['field'];
                $walletMap[$unitCode] = (int) ($row[$field] ?? 0);
                unset($row[$field]);
            }
            $row['walletMap'] = $walletMap;
        }
        unset($row);

        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => (int) ($total['total'] ?? 0),
            'metrics' => [
                'memberCount' => (int) ($metrics['memberCount'] ?? 0),
                'membersWithWallet' => (int) ($metrics['membersWithWallet'] ?? 0),
                'membersWithItems' => (int) ($metrics['membersWithItems'] ?? 0),
                'totalItemQuantity' => (int) ($metrics['totalItemQuantity'] ?? 0),
            ],
            'walletColumns' => array_map(static fn (array $unit): array => [
                'unitCode' => (string) ($unit['unitCode'] ?? ''),
                'label' => (string) ($unit['shortName'] ?? $unit['displayName'] ?? $unit['unitCode'] ?? ''),
            ], $walletColumns),
            'rows' => $decoratedRows,
        ];
    }

    public static function memberBagDetail(string $guildId, string $userId, array $filters = []): array
    {
        ShopUnitService::ensureSchema();
        TransactionTraceService::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            throw new RuntimeException('MEMBER_NOT_FOUND');
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = max(10, min(200, (int) ($filters['pageSize'] ?? 25)));
        $offset = ($page - 1) * $pageSize;
        $normalizedFilters = [
            'page' => $page,
            'pageSize' => $pageSize,
            'historyKind' => trim((string) ($filters['historyKind'] ?? '')),
            'direction' => trim((string) ($filters['direction'] ?? '')),
            'sourceType' => trim((string) ($filters['sourceType'] ?? '')),
            'dateFrom' => trim((string) ($filters['dateFrom'] ?? '')),
            'dateTo' => trim((string) ($filters['dateTo'] ?? '')),
        ];

        $member = self::memberBagSummaryRow($guildId, $userId);
        if (!$member) {
            throw new RuntimeException('MEMBER_NOT_FOUND');
        }

        $wallets = self::memberBagWalletRows($guildId, $userId);
        $items = self::memberBagInventoryRows($guildId, $userId);

        $baseHistoryRows = array_merge(
            self::memberBagItemLedgerRows($guildId, $userId, $normalizedFilters),
            self::memberBagWalletHistoryRows($guildId, $userId, $normalizedFilters),
            self::memberBagRewardHistoryRows($guildId, $userId, $normalizedFilters)
        );

        usort($baseHistoryRows, static function (array $left, array $right): int {
            return strcmp((string) ($right['createDate'] ?? ''), (string) ($left['createDate'] ?? ''))
                ?: ((int) ($right['historySortId'] ?? 0) <=> (int) ($left['historySortId'] ?? 0));
        });

        $sourceTypes = [];
        $historyKinds = [];
        foreach ($baseHistoryRows as $row) {
            $sourceType = trim((string) ($row['sourceType'] ?? ''));
            if ($sourceType !== '') {
                $sourceTypes[$sourceType] = $sourceType;
            }
            $historyKind = trim((string) ($row['historyKind'] ?? ''));
            if ($historyKind !== '') {
                $historyKinds[$historyKind] = $historyKind;
            }
        }

        $rows = array_values(array_filter($baseHistoryRows, static function (array $row) use ($normalizedFilters): bool {
            if ($normalizedFilters['historyKind'] !== '' && (string) ($row['historyKind'] ?? '') !== $normalizedFilters['historyKind']) {
                return false;
            }
            if ($normalizedFilters['direction'] !== '' && (string) ($row['movementDirection'] ?? '') !== $normalizedFilters['direction']) {
                return false;
            }
            if ($normalizedFilters['sourceType'] !== '' && (string) ($row['sourceType'] ?? '') !== $normalizedFilters['sourceType']) {
                return false;
            }
            return true;
        }));

        $pageRows = array_slice($rows, $offset, $pageSize);

        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => count($rows),
            'member' => $member,
            'wallets' => $wallets,
            'items' => $items,
            'metrics' => [
                'historyRows' => count($rows),
                'itemMoves' => self::historyCount($rows, 'item_ledger'),
                'walletMoves' => self::historyCount($rows, 'wallet_ledger'),
                'rewardEvents' => self::historyCount($rows, 'reward_event'),
                'currentWalletUnits' => count($wallets),
                'currentItems' => count($items),
                'currentItemQuantity' => array_sum(array_map(static fn (array $item): int => (int) ($item['quantity'] ?? 0), $items)),
            ],
            'rows' => $pageRows,
            'filters' => $normalizedFilters,
            'filterOptions' => [
                'historyKinds' => array_values($historyKinds),
                'directions' => ['in', 'out'],
                'sourceTypes' => array_values($sourceTypes),
            ],
            'historyNotice' => self::memberBagHistoryNotice($guildId),
        ];
    }

    public static function transactionReport(string $guildId, array $filters = []): array
    {
        ShopUnitService::ensureSchema();
        TransactionTraceService::ensureSchema();

        $guildId = trim($guildId);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = max(25, min(200, (int) ($filters['pageSize'] ?? 50)));
        $offset = ($page - 1) * $pageSize;
        $normalizedFilters = [
            'page' => $page,
            'pageSize' => $pageSize,
            'q' => trim((string) ($filters['q'] ?? '')),
            'historyKind' => trim((string) ($filters['historyKind'] ?? '')),
            'direction' => trim((string) ($filters['direction'] ?? '')),
            'sourceType' => trim((string) ($filters['sourceType'] ?? '')),
            'unitCode' => trim((string) ($filters['unitCode'] ?? '')),
            'itemType' => trim((string) ($filters['itemType'] ?? '')),
            'itemCode' => trim((string) ($filters['itemCode'] ?? '')),
            'dateFrom' => trim((string) ($filters['dateFrom'] ?? '')),
            'dateTo' => trim((string) ($filters['dateTo'] ?? '')),
            'sort' => trim((string) ($filters['sort'] ?? 'createDate')),
            'dir' => strtolower(trim((string) ($filters['dir'] ?? 'desc'))) === 'asc' ? 'asc' : 'desc',
        ];

        $baseRows = [];
        if (self::transactionReportShouldFetch($normalizedFilters, 'item_ledger')) {
            $baseRows = array_merge($baseRows, self::transactionItemLedgerRows($guildId, $normalizedFilters));
        }
        if (self::transactionReportShouldFetch($normalizedFilters, 'wallet_ledger')) {
            $baseRows = array_merge($baseRows, self::transactionWalletHistoryRows($guildId, $normalizedFilters));
        }
        if (self::transactionReportShouldFetch($normalizedFilters, 'reward_event')) {
            $baseRows = array_merge($baseRows, self::transactionRewardHistoryRows($guildId, $normalizedFilters));
        }

        $sourceTypes = [];
        foreach ($baseRows as $row) {
            $sourceType = trim((string) ($row['sourceType'] ?? ''));
            if ($sourceType !== '') {
                $sourceTypes[$sourceType] = $sourceType;
            }
        }

        $rows = array_values(array_filter(
            $baseRows,
            static fn (array $row): bool => self::transactionRowMatchesFilters($row, $normalizedFilters)
        ));
        $rows = self::sortTransactionRows($rows, $normalizedFilters);
        $pageRows = array_slice($rows, $offset, $pageSize);

        $memberIds = [];
        foreach ($rows as $row) {
            $userId = trim((string) ($row['userId'] ?? ''));
            if ($userId !== '') {
                $memberIds[$userId] = true;
            }
        }

        $itemTypes = Database::fetchAll(
            'SELECT DISTINCT itemType
               FROM tbl_shop_item
              WHERE itemType IS NOT NULL AND itemType <> ""
              ORDER BY itemType ASC'
        );
        $units = ShopUnitService::units(true);

        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => count($rows),
            'metrics' => [
                'historyRows' => count($rows),
                'itemMoves' => self::historyCount($rows, 'item_ledger'),
                'walletMoves' => self::historyCount($rows, 'wallet_ledger'),
                'rewardEvents' => self::historyCount($rows, 'reward_event'),
                'members' => count($memberIds),
                'inflowRows' => count(array_filter($rows, static fn (array $row): bool => (string) ($row['movementDirection'] ?? '') === 'in')),
                'outflowRows' => count(array_filter($rows, static fn (array $row): bool => (string) ($row['movementDirection'] ?? '') === 'out')),
            ],
            'rows' => $pageRows,
            'filters' => $normalizedFilters,
            'filterOptions' => [
                'units' => array_map(static fn (array $unit): array => [
                    'unitCode' => (string) ($unit['unitCode'] ?? ''),
                    'label' => (string) ($unit['shortName'] ?? $unit['displayName'] ?? $unit['unitCode'] ?? ''),
                ], $units),
                'itemTypes' => array_values(array_map(static fn (array $row): string => (string) ($row['itemType'] ?? ''), $itemTypes)),
                'historyKinds' => ['item_ledger', 'wallet_ledger', 'reward_event'],
                'sourceTypes' => array_values($sourceTypes),
            ],
            'historyNotice' => self::memberBagHistoryNotice($guildId),
        ];
    }

    private static function decoratePurchaseRow(array $row): array
    {
        $metadata = json_decode((string) ($row['metadataJson'] ?? ''), true);
        $metadata = is_array($metadata) ? $metadata : [];
        $row = TransactionTraceService::decorateRowTraceMeta($row, 'wallet_ledger', (int) ($row['shopWalletLedgerId'] ?? 0));
        $row['historyKind'] = 'wallet_ledger';
        $row['historySortId'] = (int) ($row['shopWalletLedgerId'] ?? 0);
        $row['metadata'] = $metadata;
        $row['metadataPretty'] = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row['productId'] = (string) ($metadata['productId'] ?? $row['sourceId'] ?? '');
        $row['productName'] = (string) ($metadata['productName'] ?? $row['sourceId'] ?? '');
        $row['optionLabel'] = (string) ($metadata['optionLabel'] ?? '');
        $row['roleName'] = (string) ($metadata['roleName'] ?? '');
        $row['purchaseKind'] = (string) ($metadata['purchaseKind'] ?? '');
        $row['targetUserId'] = (string) ($metadata['targetUserId'] ?? '');
        $row['targetDisplayName'] = (string) ($metadata['targetDisplayName'] ?? '');
        $row['walletBalanceBefore'] = self::nullableInt($row['walletBalanceBefore'] ?? null);
        $row['walletBalanceAfter'] = self::nullableInt($row['walletBalanceAfter'] ?? null);
        $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);
        return $row;
    }

    private static function decorateMemberBagRow(array $row): array
    {
        $row['wallets'] = self::parseSummary((string) ($row['walletSummary'] ?? ''), ':');
        $row['items'] = self::parseSummary((string) ($row['itemSummary'] ?? ''), ' x ');
        $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);
        return $row;
    }

    private static function memberBagSummaryRow(string $guildId, string $userId): ?array
    {
        $walletSql = self::walletSummarySql();
        $inventorySql = self::inventorySummarySql();
        $row = Database::fetch(
            'SELECT m.userId,
                    m.isActive,
                    COALESCE(m.nickName, u.globalName, u.userName, m.userId) AS displayName,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    COALESCE(wallet.walletTotal, 0) AS walletTotal,
                    COALESCE(wallet.walletSummary, "") AS walletSummary,
                    COALESCE(inv.itemQuantity, 0) AS itemQuantity,
                    COALESCE(inv.itemSummary, "") AS itemSummary,
                    GREATEST(COALESCE(wallet.walletUpdateDate, "1970-01-01 00:00:00"), COALESCE(inv.inventoryUpdateDate, "1970-01-01 00:00:00")) AS lastActivityDate
               FROM tbl_member m
          LEFT JOIN tbl_user u ON u.userId = m.userId
          LEFT JOIN ' . $walletSql . ' wallet ON wallet.guildId = m.guildId AND wallet.userId = m.userId
          LEFT JOIN ' . $inventorySql . ' inv ON inv.guildId = m.guildId AND inv.userId = m.userId
              WHERE m.guildId = :guildId AND m.userId = :userId
              LIMIT 1',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return $row ? self::decorateMemberBagRow($row) : null;
    }

    /** @return array<int, array<string, mixed>> */
    private static function memberBagWalletRows(string $guildId, string $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT sw.shopWalletId,
                    sw.unitCode,
                    sw.balanceAmount,
                    sw.updateDate,
                    COALESCE(unit.displayName, sw.unitCode) AS unitLabel,
                    COALESCE(unit.shortName, sw.unitCode) AS unitShortName,
                    unit.icon
               FROM tbl_shop_wallet sw
          LEFT JOIN tbl_shop_unit unit ON unit.unitCode = sw.unitCode
              WHERE sw.guildId = :guildId
                AND sw.userId = :userId
                AND sw.balanceAmount <> 0
           ORDER BY COALESCE(unit.sortOrder, 9999) ASC, sw.unitCode ASC',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return array_map(static function (array $row): array {
            $row['label'] = (string) ($row['unitLabel'] ?? $row['unitCode'] ?? '');
            $row['value'] = (int) ($row['balanceAmount'] ?? 0);
            return $row;
        }, $rows);
    }

    /** @return array<int, array<string, mixed>> */
    private static function memberBagInventoryRows(string $guildId, string $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT inv.shopInventoryId,
                    inv.shopItemId,
                    inv.quantity,
                    inv.metadataJson AS inventoryMetadataJson,
                    inv.updateDate,
                    item.itemCode,
                    item.itemName,
                    item.itemType,
                    item.image,
                    item.metadataJson AS itemMetadataJson
               FROM tbl_shop_inventory inv
         INNER JOIN tbl_shop_item item ON item.shopItemId = inv.shopItemId
              WHERE inv.guildId = :guildId
                AND inv.userId = :userId
                AND inv.quantity > 0
           ORDER BY item.itemType ASC, item.itemName ASC',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return array_map(static function (array $row): array {
            $row['quantity'] = (int) ($row['quantity'] ?? 0);
            $row['inventoryMetadata'] = self::decodeJson((string) ($row['inventoryMetadataJson'] ?? ''));
            $row['itemMetadata'] = self::decodeJson((string) ($row['itemMetadataJson'] ?? ''));
            return $row;
        }, $rows);
    }

    /** @return array<int, array<string, mixed>> */
    private static function memberBagItemLedgerRows(string $guildId, string $userId, array $filters): array
    {
        $params = ['guildId' => $guildId, 'userId' => $userId];
        $where = ['il.guildId = :guildId', 'il.userId = :userId'];
        self::applyDateFilters($where, $params, 'il.createDate', $filters);

        $rows = Database::fetchAll(
            'SELECT il.shopInventoryLedgerId,
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
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, il.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, il.targetUserId) AS targetDisplayName
               FROM tbl_shop_inventory_ledger il
         INNER JOIN tbl_shop_item item ON item.shopItemId = il.shopItemId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = il.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = il.guildId AND actorMember.userId = il.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = il.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = il.guildId AND targetMember.userId = il.targetUserId
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY il.createDate DESC, il.shopInventoryLedgerId DESC',
            $params
        );

        return array_map([self::class, 'decorateMemberBagItemLedgerRow'], $rows);
    }

    /** @return array<int, array<string, mixed>> */
    private static function memberBagWalletHistoryRows(string $guildId, string $userId, array $filters): array
    {
        $params = ['guildId' => $guildId, 'userId' => $userId];
        $where = ['sw.guildId = :guildId', 'sw.userId = :userId'];
        self::applyDateFilters($where, $params, 'wl.createDate', $filters);

        $rows = Database::fetchAll(
            'SELECT wl.shopWalletLedgerId,
                    sw.userId,
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
                    COALESCE(unit.displayName, wl.unitCode) AS unitLabel,
                    COALESCE(unit.shortName, wl.unitCode) AS unitShortName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, wl.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, wl.targetUserId) AS targetDisplayName
               FROM tbl_shop_wallet sw
         INNER JOIN tbl_shop_wallet_ledger wl ON wl.shopWalletId = sw.shopWalletId
          LEFT JOIN tbl_shop_unit unit ON unit.unitCode = wl.unitCode
          LEFT JOIN tbl_user actorUser ON actorUser.userId = wl.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = sw.guildId AND actorMember.userId = wl.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = wl.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = sw.guildId AND targetMember.userId = wl.targetUserId
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY wl.createDate DESC, wl.shopWalletLedgerId DESC',
            $params
        );

        return array_map([self::class, 'decorateMemberBagWalletHistoryRow'], $rows);
    }

    /** @return array<int, array<string, mixed>> */
    private static function memberBagRewardHistoryRows(string $guildId, string $userId, array $filters): array
    {
        $params = ['guildId' => $guildId, 'userId' => $userId];
        $where = ['re.guildId = :guildId', 're.userId = :userId'];
        self::applyDateFilters($where, $params, 're.createDate', $filters);

        $rows = Database::fetchAll(
            'SELECT re.rewardEventId,
                    re.sourceType,
                    re.sourceId,
                    re.transactionGroupId,
                    re.rewardStatus,
                    re.metadataJson,
                    re.createDate,
                    rr.ruleCode,
                    rr.ruleName,
                    rr.triggerType
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY re.createDate DESC, re.rewardEventId DESC',
            $params
        );

        return array_map([self::class, 'decorateMemberBagRewardHistoryRow'], $rows);
    }

    private static function decorateMemberBagItemLedgerRow(array $row): array
    {
        $metadata = self::decodeJson((string) ($row['metadataJson'] ?? ''));
        $quantityDelta = (int) ($row['quantityDelta'] ?? 0);
        $ownerUserId = trim((string) ($row['userId'] ?? ''));
        $row = TransactionTraceService::decorateRowTraceMeta($row, 'item_ledger', (int) ($row['shopInventoryLedgerId'] ?? 0));
        [$counterpartyLabel, $counterpartyDirection, $counterpartyUserId] = self::memberBagItemCounterparty($row, $ownerUserId);

        $row['historyKind'] = 'item_ledger';
        $row['historySortId'] = (int) ($row['shopInventoryLedgerId'] ?? 0);
        $row['movementDirection'] = $quantityDelta < 0 ? 'out' : 'in';
        $row['metadata'] = $metadata;
        $row['metadataPretty'] = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row['itemLabel'] = (string) ($row['itemName'] ?? $row['itemCode'] ?? '');
        $row['itemCode'] = (string) ($row['itemCode'] ?? '');
        $row['itemType'] = (string) ($row['itemType'] ?? '');
        $row['quantityText'] = ($quantityDelta > 0 ? '+' : '') . $quantityDelta;
        $row['quantityBefore'] = self::nullableInt($row['quantityBefore'] ?? null);
        $row['quantityAfter'] = self::nullableInt($row['quantityAfter'] ?? null);
        $row['counterpartyLabel'] = $counterpartyLabel;
        $row['counterpartyDirection'] = $counterpartyDirection;
        $row['counterpartyUserId'] = $counterpartyUserId;
        $row['note'] = self::memberBagItemLedgerNote($row, $metadata);
        return $row;
    }

    private static function decorateMemberBagWalletHistoryRow(array $row): array
    {
        $metadata = self::decodeJson((string) ($row['metadataJson'] ?? ''));
        $amountDelta = (int) ($row['amountDelta'] ?? 0);
        $ownerUserId = trim((string) ($row['userId'] ?? ''));
        $row = TransactionTraceService::decorateRowTraceMeta($row, 'wallet_ledger', (int) ($row['shopWalletLedgerId'] ?? 0));
        [$counterpartyLabel, $counterpartyDirection, $counterpartyUserId] = self::memberBagWalletCounterparty($row, $metadata, $ownerUserId);

        $row['historyKind'] = 'wallet_ledger';
        $row['historySortId'] = (int) ($row['shopWalletLedgerId'] ?? 0);
        $row['movementDirection'] = $amountDelta < 0 ? 'out' : 'in';
        $row['metadata'] = $metadata;
        $row['metadataPretty'] = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row['unitLabel'] = (string) ($row['unitLabel'] ?? $row['unitCode'] ?? '');
        $row['unitShortName'] = (string) ($row['unitShortName'] ?? $row['unitLabel'] ?? $row['unitCode'] ?? '');
        $row['amountText'] = ($amountDelta > 0 ? '+' : '') . number_format($amountDelta) . ' ' . trim((string) ($row['unitShortName'] ?? $row['unitLabel'] ?? $row['unitCode'] ?? ''));
        $row['walletBalanceBefore'] = self::nullableInt($row['walletBalanceBefore'] ?? null);
        $row['walletBalanceAfter'] = self::nullableInt($row['walletBalanceAfter'] ?? null);
        $row['counterpartyLabel'] = $counterpartyLabel;
        $row['counterpartyDirection'] = $counterpartyDirection;
        $row['counterpartyUserId'] = $counterpartyUserId;
        $row['note'] = self::memberBagWalletLedgerNote($row, $metadata);
        return $row;
    }

    private static function decorateMemberBagRewardHistoryRow(array $row): array
    {
        $metadata = self::decodeJson((string) ($row['metadataJson'] ?? ''));
        $rewardStatus = strtolower(trim((string) ($row['rewardStatus'] ?? 'granted')));
        $outStatuses = ['consumed', 'spent', 'expired'];
        $unitRewards = self::rewardUnitRewards($metadata);
        $row = TransactionTraceService::decorateRowTraceMeta($row, 'reward_event', (int) ($row['rewardEventId'] ?? 0));

        $row['historyKind'] = 'reward_event';
        $row['historySortId'] = (int) ($row['rewardEventId'] ?? 0);
        $row['movementDirection'] = in_array($rewardStatus, $outStatuses, true) ? 'out' : 'in';
        $row['metadata'] = $metadata;
        $row['metadataPretty'] = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row['rewardLabel'] = (string) ($row['ruleName'] ?? $row['ruleCode'] ?? $row['sourceType'] ?? '');
        $row['rewardSummary'] = self::formatRewardUnitRewards($unitRewards, $row['movementDirection'] === 'out');
        $row['note'] = self::memberBagRewardNote($row, $metadata);
        $row['counterpartyLabel'] = '';
        $row['counterpartyDirection'] = '';
        $row['counterpartyUserId'] = '';
        return $row;
    }

    /** @return array{0:string,1:string,2:string} */
    private static function memberBagItemCounterparty(array $row, string $ownerUserId): array
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
    private static function memberBagWalletCounterparty(array $row, array $metadata, string $ownerUserId): array
    {
        $targetUserId = trim((string) ($row['targetUserId'] ?? ''));
        $targetDisplayName = trim((string) ($row['targetDisplayName'] ?? ''));
        if (($targetUserId !== '' || $targetDisplayName !== '') && $targetUserId !== $ownerUserId) {
            return [$targetDisplayName !== '' ? $targetDisplayName : $targetUserId, 'to', $targetUserId];
        }

        $targetUserId = trim((string) ($metadata['targetUserId'] ?? ''));
        $targetDisplayName = trim((string) ($metadata['targetDisplayName'] ?? ''));
        if (($targetUserId !== '' || $targetDisplayName !== '') && $targetUserId !== $ownerUserId) {
            return [$targetDisplayName !== '' ? $targetDisplayName : $targetUserId, 'to', $targetUserId];
        }

        $inventoryOwnerUserId = trim((string) ($row['actorUserId'] ?? ''));
        $inventoryOwnerDisplayName = trim((string) ($row['actorDisplayName'] ?? ''));
        if (($inventoryOwnerUserId !== '' || $inventoryOwnerDisplayName !== '') && $inventoryOwnerUserId !== $ownerUserId) {
            return [$inventoryOwnerDisplayName !== '' ? $inventoryOwnerDisplayName : $inventoryOwnerUserId, 'from', $inventoryOwnerUserId];
        }

        $inventoryOwnerUserId = trim((string) ($metadata['inventoryOwnerUserId'] ?? ''));
        $inventoryOwnerDisplayName = trim((string) ($metadata['inventoryOwnerDisplayName'] ?? ''));
        if (($inventoryOwnerUserId !== '' || $inventoryOwnerDisplayName !== '') && $inventoryOwnerUserId !== $ownerUserId) {
            return [$inventoryOwnerDisplayName !== '' ? $inventoryOwnerDisplayName : $inventoryOwnerUserId, 'for', $inventoryOwnerUserId];
        }

        return ['', '', ''];
    }

    private static function memberBagItemLedgerNote(array $row, array $metadata): string
    {
        $parts = [];
        $productName = trim((string) ($metadata['productName'] ?? ''));
        $optionLabel = trim((string) ($metadata['optionLabel'] ?? ''));
        $roleName = trim((string) ($metadata['roleName'] ?? ''));
        $paymentAmount = (int) ($metadata['paymentAmount'] ?? 0);
        $paymentUnitCode = trim((string) ($metadata['paymentUnitCode'] ?? ''));

        if ($productName !== '') {
            $parts[] = $productName;
        }
        if ($optionLabel !== '') {
            $parts[] = $optionLabel;
        }
        if ($roleName !== '' && !in_array($roleName, $parts, true)) {
            $parts[] = $roleName;
        }
        if ($paymentAmount > 0 && $paymentUnitCode !== '') {
            $parts[] = number_format($paymentAmount) . ' ' . $paymentUnitCode;
        }
        if ($parts === []) {
            $parts[] = (string) ($row['itemName'] ?? $row['itemCode'] ?? '-');
        }

        return implode(' · ', $parts);
    }

    private static function memberBagWalletLedgerNote(array $row, array $metadata): string
    {
        $parts = [];
        $productName = trim((string) ($metadata['productName'] ?? ''));
        $optionLabel = trim((string) ($metadata['optionLabel'] ?? ''));
        $roleName = trim((string) ($metadata['roleName'] ?? ''));
        $paymentAmount = (int) ($metadata['paymentAmount'] ?? 0);

        if ($productName !== '') {
            $parts[] = $productName;
        }
        if ($optionLabel !== '') {
            $parts[] = $optionLabel;
        }
        if ($roleName !== '' && !in_array($roleName, $parts, true)) {
            $parts[] = $roleName;
        }
        if ($paymentAmount > 0 && empty($parts)) {
            $parts[] = number_format($paymentAmount);
        }

        return implode(' · ', $parts);
    }

    private static function memberBagRewardNote(array $row, array $metadata): string
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
            $reason = trim((string) ($metadata['manualGrant']['reason'] ?? ''));
            if ($targetLabel !== '') {
                $parts[] = $targetLabel;
            }
            if ($reason !== '') {
                $parts[] = $reason;
            }
        }

        return implode(' · ', $parts);
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

    private static function historyCount(array $rows, string $historyKind): int
    {
        return count(array_filter(
            $rows,
            static fn (array $row): bool => (string) ($row['historyKind'] ?? '') === $historyKind
        ));
    }

    private static function transactionReportShouldFetch(array $filters, string $historyKind): bool
    {
        $requested = trim((string) ($filters['historyKind'] ?? ''));
        return $requested === '' || $requested === $historyKind;
    }

    /** @return array<int, array<string, mixed>> */
    private static function transactionItemLedgerRows(string $guildId, array $filters): array
    {
        $params = ['guildId' => $guildId];
        $where = ['il.guildId = :guildId'];
        self::applyDateFilters($where, $params, 'il.createDate', $filters);

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
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY il.createDate DESC, il.shopInventoryLedgerId DESC',
            $params
        );

        return array_map(static function (array $row): array {
            $row = self::decorateMemberBagItemLedgerRow($row);
            $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);
            $row['assetLabel'] = (string) ($row['itemLabel'] ?? $row['itemCode'] ?? '');
            $row['assetSortLabel'] = strtolower((string) ($row['assetLabel'] ?? ''));
            $row['deltaSortValue'] = (int) ($row['quantityDelta'] ?? 0);
            $row['unitCodes'] = [];
            return $row;
        }, $rows);
    }

    /** @return array<int, array<string, mixed>> */
    private static function transactionWalletHistoryRows(string $guildId, array $filters): array
    {
        $params = ['guildId' => $guildId];
        $where = ['sw.guildId = :guildId'];
        self::applyDateFilters($where, $params, 'wl.createDate', $filters);

        $rows = Database::fetchAll(
            'SELECT wl.shopWalletLedgerId,
                    sw.guildId,
                    sw.userId,
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
                    COALESCE(unit.displayName, wl.unitCode) AS unitLabel,
                    COALESCE(unit.shortName, wl.unitCode) AS unitShortName,
                    ownerUser.userName,
                    ownerUser.globalName,
                    ownerUser.avatarHash,
                    COALESCE(ownerMember.nickName, ownerUser.globalName, ownerUser.userName, sw.userId) AS displayName,
                    COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, wl.actorUserId) AS actorDisplayName,
                    COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, wl.targetUserId) AS targetDisplayName
               FROM tbl_shop_wallet sw
         INNER JOIN tbl_shop_wallet_ledger wl ON wl.shopWalletId = sw.shopWalletId
          LEFT JOIN tbl_shop_unit unit ON unit.unitCode = wl.unitCode
          LEFT JOIN tbl_user ownerUser ON ownerUser.userId = sw.userId
          LEFT JOIN tbl_member ownerMember ON ownerMember.guildId = sw.guildId AND ownerMember.userId = sw.userId
          LEFT JOIN tbl_user actorUser ON actorUser.userId = wl.actorUserId
          LEFT JOIN tbl_member actorMember ON actorMember.guildId = sw.guildId AND actorMember.userId = wl.actorUserId
          LEFT JOIN tbl_user targetUser ON targetUser.userId = wl.targetUserId
          LEFT JOIN tbl_member targetMember ON targetMember.guildId = sw.guildId AND targetMember.userId = wl.targetUserId
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY wl.createDate DESC, wl.shopWalletLedgerId DESC',
            $params
        );

        return array_map(static function (array $row): array {
            $row = self::decorateMemberBagWalletHistoryRow($row);
            $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);
            $row['assetLabel'] = (string) ($row['unitLabel'] ?? $row['unitCode'] ?? '');
            $row['assetSortLabel'] = strtolower((string) ($row['assetLabel'] ?? ''));
            $row['deltaSortValue'] = (int) ($row['amountDelta'] ?? 0);
            $row['unitCodes'] = [trim((string) ($row['unitCode'] ?? ''))];
            return $row;
        }, $rows);
    }

    /** @return array<int, array<string, mixed>> */
    private static function transactionRewardHistoryRows(string $guildId, array $filters): array
    {
        $params = ['guildId' => $guildId];
        $where = ['re.guildId = :guildId'];
        self::applyDateFilters($where, $params, 're.createDate', $filters);

        $rows = Database::fetchAll(
            'SELECT re.rewardEventId,
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
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY re.createDate DESC, re.rewardEventId DESC',
            $params
        );

        return array_map(static function (array $row): array {
            $row = self::decorateMemberBagRewardHistoryRow($row);
            $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);
            $rewardUnits = self::rewardUnitRewards($row['metadata'] ?? []);
            $row['assetLabel'] = (string) ($row['rewardLabel'] ?? $row['ruleCode'] ?? $row['sourceType'] ?? '');
            $row['assetSortLabel'] = strtolower((string) ($row['assetLabel'] ?? ''));
            $row['deltaSortValue'] = array_sum(array_map('abs', $rewardUnits));
            $row['unitCodes'] = array_values(array_map('strval', array_keys($rewardUnits)));
            return $row;
        }, $rows);
    }

    private static function transactionRowMatchesFilters(array $row, array $filters): bool
    {
        $direction = trim((string) ($filters['direction'] ?? ''));
        if ($direction !== '' && (string) ($row['movementDirection'] ?? '') !== $direction) {
            return false;
        }

        $sourceType = trim((string) ($filters['sourceType'] ?? ''));
        if ($sourceType !== '' && (string) ($row['sourceType'] ?? '') !== $sourceType) {
            return false;
        }

        $unitCode = trim((string) ($filters['unitCode'] ?? ''));
        if ($unitCode !== '') {
            $unitCodes = array_values(array_filter(array_map('strval', is_array($row['unitCodes'] ?? null) ? $row['unitCodes'] : [])));
            if (!in_array($unitCode, $unitCodes, true)) {
                return false;
            }
        }

        $itemType = trim((string) ($filters['itemType'] ?? ''));
        if ($itemType !== '' && (string) ($row['itemType'] ?? '') !== $itemType) {
            return false;
        }

        $itemCode = trim((string) ($filters['itemCode'] ?? ''));
        if ($itemCode !== '') {
            $haystack = implode(' ', [
                (string) ($row['itemCode'] ?? ''),
                (string) ($row['itemLabel'] ?? ''),
                (string) ($row['assetLabel'] ?? ''),
            ]);
            if (stripos($haystack, $itemCode) === false) {
                return false;
            }
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $values = [
                (string) ($row['displayName'] ?? ''),
                (string) ($row['userName'] ?? ''),
                (string) ($row['globalName'] ?? ''),
                (string) ($row['userId'] ?? ''),
                (string) ($row['historyKind'] ?? ''),
                (string) ($row['sourceType'] ?? ''),
                (string) ($row['sourceId'] ?? ''),
                (string) ($row['assetLabel'] ?? ''),
                (string) ($row['itemCode'] ?? ''),
                (string) ($row['unitCode'] ?? ''),
                (string) ($row['counterpartyLabel'] ?? ''),
                (string) ($row['note'] ?? ''),
                (string) ($row['rewardLabel'] ?? ''),
            ];
            $matched = false;
            foreach ($values as $value) {
                if ($value !== '' && stripos($value, $q) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, array<string, mixed>> */
    private static function sortTransactionRows(array $rows, array $filters): array
    {
        $sortMap = [
            'createDate' => 'createDate',
            'displayName' => 'displayName',
            'historyKind' => 'historyKind',
            'movementDirection' => 'movementDirection',
            'sourceType' => 'sourceType',
            'assetLabel' => 'assetSortLabel',
            'delta' => 'deltaSortValue',
            'counterpartyLabel' => 'counterpartyLabel',
            'note' => 'note',
        ];
        $sort = $sortMap[(string) ($filters['sort'] ?? 'createDate')] ?? 'createDate';
        $dir = strtolower((string) ($filters['dir'] ?? 'desc')) === 'asc' ? 1 : -1;

        usort($rows, static function (array $left, array $right) use ($sort, $dir): int {
            $leftValue = $left[$sort] ?? '';
            $rightValue = $right[$sort] ?? '';
            if (is_numeric($leftValue) && is_numeric($rightValue)) {
                $cmp = (float) $leftValue <=> (float) $rightValue;
            } else {
                $cmp = strcmp((string) $leftValue, (string) $rightValue);
            }

            return ($cmp * $dir)
                ?: strcmp((string) ($right['createDate'] ?? ''), (string) ($left['createDate'] ?? ''))
                ?: ((int) ($right['historySortId'] ?? 0) <=> (int) ($left['historySortId'] ?? 0));
        });

        return $rows;
    }

    /** @return array<string, string|null> */
    private static function memberBagHistoryNotice(string $guildId): array
    {
        $firstLedgerDate = ShopInventoryLedgerService::firstLedgerDate($guildId);
        if ($firstLedgerDate !== null) {
            return [
                'title' => 'Item history starts from new ledger',
                'body' => 'Current balances include older stock, but item inflow/outflow history is authoritative from ' . $firstLedgerDate . ' onward only.',
                'sinceDate' => $firstLedgerDate,
            ];
        }

        return [
            'title' => 'Item history has not started yet',
            'body' => 'Current balances are available now, but item movement history will appear after the first post-deploy inventory change is recorded.',
            'sinceDate' => null,
        ];
    }

    /** @return array<string, mixed> */
    private static function decodeJson(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private static function parseSummary(string $summary, string $separator): array
    {
        $out = [];
        foreach (explode('||', $summary) as $part) {
            $part = trim($part);
            if ($part === '' || !str_contains($part, $separator)) {
                continue;
            }
            [$label, $value] = explode($separator, $part, 2);
            $out[] = ['label' => $label, 'value' => (int) $value];
        }
        return $out;
    }

    private static function applyDateFilters(array &$where, array &$params, string $column, array $filters): void
    {
        $dateFrom = self::normalizeDateInput($filters['dateFrom'] ?? null);
        if ($dateFrom !== null) {
            $params['dateFrom'] = $dateFrom;
            $where[] = $column . ' >= :dateFrom';
        }
        $dateTo = self::normalizeDateInput($filters['dateTo'] ?? null);
        if ($dateTo !== null) {
            $params['dateTo'] = $dateTo;
            $where[] = $column . ' <= :dateTo';
        }
    }

    private static function appendLikeGroup(array &$where, array &$params, array $columns, string $value, string $paramPrefix): void
    {
        $clauses = [];
        foreach (array_values($columns) as $index => $column) {
            $placeholder = $paramPrefix . $index;
            $params[$placeholder] = $value;
            $clauses[] = $column . ' LIKE :' . $placeholder;
        }

        if ($clauses !== []) {
            $where[] = '(' . implode(' OR ', $clauses) . ')';
        }
    }

    private static function walletSummarySql(): string
    {
        return '(
            SELECT guildId,
                   userId,
                   SUM(balanceAmount) AS walletTotal,
                   GROUP_CONCAT(CONCAT(unitCode, ":", balanceAmount) ORDER BY unitCode ASC SEPARATOR "||") AS walletSummary,
                   MAX(updateDate) AS walletUpdateDate
              FROM tbl_shop_wallet
          GROUP BY guildId, userId
        )';
    }

    private static function inventorySummarySql(): string
    {
        return '(
            SELECT inv.guildId,
                   inv.userId,
                   SUM(inv.quantity) AS itemQuantity,
                   GROUP_CONCAT(CONCAT(item.itemName, " x ", inv.quantity) ORDER BY item.itemName ASC SEPARATOR "||") AS itemSummary,
                   MAX(inv.updateDate) AS inventoryUpdateDate
              FROM tbl_shop_inventory inv
        INNER JOIN tbl_shop_item item ON item.shopItemId = inv.shopItemId
             WHERE inv.quantity > 0
          GROUP BY inv.guildId, inv.userId
        )';
    }

    private static function normalizeDateInput(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }
        $text = str_replace('T', ' ', $text);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
            return $text . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $text)) {
            return $text . ':00';
        }
        return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $text) ? $text : null;
    }
}
