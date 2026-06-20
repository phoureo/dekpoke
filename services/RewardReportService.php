<?php

declare(strict_types=1);

final class RewardReportService
{
    public static function report(string $guildId, array $filters = []): array
    {
        TransactionTraceService::ensureSchema();
        $guildId = trim($guildId);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = max(25, min(200, (int) ($filters['pageSize'] ?? 50)));
        $offset = ($page - 1) * $pageSize;

        $params = ['guildId' => $guildId];
        $whereSql = self::reportWhereSql($filters, $params);
        $coinSql = self::coinRewardSql();
        $ticketSql = self::ticketRewardSql();
        $freeSpinSql = self::freeSpinRewardSql();
        $baseJoins = '
            INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
            LEFT JOIN tbl_user u ON u.userId = re.userId
            LEFT JOIN tbl_member m ON m.guildId = re.guildId AND m.userId = re.userId
        ';
        $rowJoins = $baseJoins . '
            LEFT JOIN ' . self::rewardWalletJoinSql() . ' rw
                   ON rw.guildId = re.guildId
                  AND rw.userId = re.userId
                  AND rw.rewardEventId = CONCAT("", re.rewardEventId)
        ';

        $totalRow = Database::fetch(
            'SELECT COUNT(*) AS total
               FROM tbl_reward_event re
               ' . $baseJoins . '
              WHERE ' . $whereSql,
            $params
        ) ?: [];

        $metricRow = Database::fetch(
            'SELECT
                COUNT(*) AS totalEvents,
                SUM(CASE WHEN re.rewardStatus = "granted" THEN 1 ELSE 0 END) AS grantedEvents,
                SUM(' . $coinSql . ') AS coinGranted,
                SUM(' . $ticketSql . ') AS ticketGranted,
                SUM(' . $freeSpinSql . ') AS freeSpinGranted,
                SUM(CASE WHEN rr.triggerType = "earn_member_first_join" THEN 1 ELSE 0 END) AS firstJoinRewards
             FROM tbl_reward_event re
             ' . $baseJoins . '
             WHERE ' . $whereSql,
            $params
        ) ?: [];

        $rewardRows = Database::fetchAll(
            'SELECT
                re.*,
                re.transactionGroupId,
                rr.ruleCode,
                rr.ruleName,
                rr.triggerType,
                rr.conditionJson,
                rr.rewardJson,
                u.userName,
                u.globalName,
                u.avatarHash,
                COALESCE(m.nickName, u.globalName, u.userName, re.userId) AS displayName,
                NULL AS activityEventDate,
                NULL AS activityEventType,
                rw.walletLedgerCount,
                rw.walletLedgerId,
                rw.walletLedgerDate,
                rw.walletDeltaSummary,
                rw.walletBalanceBeforeSummary,
                rw.walletBalanceAfterSummary,
                ' . $coinSql . ' AS rewardCoin,
                ' . $ticketSql . ' AS rewardTicket,
                ' . $freeSpinSql . ' AS rewardFreeSpin
             FROM tbl_reward_event re
             ' . $rowJoins . '
             WHERE ' . $whereSql . '
             ORDER BY re.createDate DESC, re.rewardEventId DESC
             LIMIT 1000',
            $params
        );

        $rewardRows = array_map(static function (array $row): array {
            $metadata = json_decode((string) ($row['metadataJson'] ?? ''), true);
            $condition = json_decode((string) ($row['conditionJson'] ?? ''), true);
            $rewardConfig = json_decode((string) ($row['rewardJson'] ?? ''), true);
            $row = TransactionTraceService::decorateRowTraceMeta($row, 'reward_event', (int) ($row['rewardEventId'] ?? 0));
            $row['historyKind'] = 'reward_event';
            $row['historyId'] = (int) ($row['rewardEventId'] ?? 0);
            $row['historySortId'] = (int) ($row['rewardEventId'] ?? 0);
            $row['movementDirection'] = 'in';
            $row['metadata'] = is_array($metadata) ? $metadata : [];
            $row['condition'] = is_array($condition) ? $condition : [];
            $row['rewardConfig'] = is_array($rewardConfig) ? $rewardConfig : [];
            $row['metadataPretty'] = json_encode($row['metadata'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);
            $row['rewardCoin'] = (int) ($row['rewardCoin'] ?? 0);
            $row['rewardTicket'] = (int) ($row['rewardTicket'] ?? 0);
            $row['rewardFreeSpin'] = (int) ($row['rewardFreeSpin'] ?? 0);
            $row['walletDeltaMap'] = self::parseUnitSummary((string) ($row['walletDeltaSummary'] ?? ''));
            $row['walletBalanceBeforeMap'] = self::parseUnitSummary((string) ($row['walletBalanceBeforeSummary'] ?? ''));
            $row['walletBalanceAfterMap'] = self::parseUnitSummary((string) ($row['walletBalanceAfterSummary'] ?? ''));
            $row['walletAmountDelta'] = array_sum($row['walletDeltaMap']);
            $row['hasWalletLedger'] = !empty($row['walletLedgerId']);
            return self::decorateRow($row);
        }, $rewardRows);

        $walletRows = self::walletMovementRows($guildId, $filters);
        $rows = array_values(array_merge($rewardRows, $walletRows));
        usort($rows, static function (array $left, array $right): int {
            return strcmp((string) ($right['createDate'] ?? ''), (string) ($left['createDate'] ?? ''))
                ?: ((int) ($right['historySortId'] ?? 0) <=> (int) ($left['historySortId'] ?? 0));
        });
        $rows = self::sortRows($rows, $filters);
        $pageRows = array_slice($rows, $offset, $pageSize);

        $walletIncome = 0;
        $walletSpent = 0;
        $unitMetrics = self::unitMetricSkeleton();
        foreach ($rows as $row) {
            $deltaMap = is_array($row['walletDeltaMap'] ?? null) ? $row['walletDeltaMap'] : [];
            foreach ($deltaMap as $unitCode => $delta) {
                $unitCode = trim((string) $unitCode);
                if ($unitCode === '') {
                    continue;
                }
                $delta = (int) $delta;
                if (!isset($unitMetrics[$unitCode])) {
                    $unitMetrics[$unitCode] = [
                        'unitCode' => $unitCode,
                        'label' => $unitCode,
                        'income' => 0,
                        'spent' => 0,
                        'net' => 0,
                    ];
                }
                if ($delta > 0) {
                    $unitMetrics[$unitCode]['income'] += $delta;
                    $walletIncome += $delta;
                } elseif ($delta < 0) {
                    $unitMetrics[$unitCode]['spent'] += abs($delta);
                    $walletSpent += abs($delta);
                }
                $unitMetrics[$unitCode]['net'] += $delta;
            }
        }
        $unitMetricRows = array_values($unitMetrics);

        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => count($rows),
            'metrics' => [
                'totalEvents' => (int) ($metricRow['totalEvents'] ?? 0),
                'grantedEvents' => (int) ($metricRow['grantedEvents'] ?? 0),
                'coinGranted' => (int) ($metricRow['coinGranted'] ?? 0),
                'ticketGranted' => (int) ($metricRow['ticketGranted'] ?? 0),
                'freeSpinGranted' => (int) ($metricRow['freeSpinGranted'] ?? 0),
                'walletIncome' => $walletIncome,
                'walletSpent' => $walletSpent,
                'firstJoinRewards' => (int) ($metricRow['firstJoinRewards'] ?? 0),
                'unitMetrics' => $unitMetricRows,
            ],
            'rows' => $pageRows,
        ];
    }

    private static function sortRows(array $rows, array $filters): array
    {
        $allowed = [
            'createDate', 'displayName', 'historyKind', 'ruleName', 'sourceType',
            'sourceSegment', 'rewardCoin', 'walletBalanceAfter', 'rewardStatus', 'walletLedgerId',
        ];
        $sort = trim((string) ($filters['sort'] ?? 'createDate'));
        if (!in_array($sort, $allowed, true)) {
            return $rows;
        }
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

    /** @return array<string, array{unitCode:string,label:string,income:int,spent:int,net:int}> */
    private static function unitMetricSkeleton(): array
    {
        if (!class_exists('ShopUnitService')) {
            return [];
        }

        try {
            $units = ShopUnitService::units(true);
        } catch (Throwable) {
            return [];
        }

        $out = [];
        foreach ($units as $unit) {
            $unitCode = trim((string) ($unit['unitCode'] ?? $unit['code'] ?? ''));
            if ($unitCode === '') {
                continue;
            }
            $out[$unitCode] = [
                'unitCode' => $unitCode,
                'label' => trim((string) ($unit['displayName'] ?? $unit['shortName'] ?? $unitCode)) ?: $unitCode,
                'income' => 0,
                'spent' => 0,
                'net' => 0,
            ];
        }

        return $out;
    }

    private static function walletMovementRows(string $guildId, array $filters): array
    {
        if (!class_exists('ShopUnitService')) {
            return [];
        }

        try {
            ShopUnitService::ensureSchema();
        } catch (Throwable) {
            return [];
        }

        $params = ['guildIdWallet' => $guildId];
        $where = [
            'sw.guildId = :guildIdWallet',
            '(wl.amountDelta < 0 OR COALESCE(wl.sourceType, "") NOT IN ("earn_rule", "earn_manual"))',
        ];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $params['walletQ'] = '%' . $q . '%';
            $where[] = '(
                sw.userId LIKE :walletQ
                OR COALESCE(u.userName, "") LIKE :walletQ
                OR COALESCE(u.globalName, "") LIKE :walletQ
                OR COALESCE(m.nickName, "") LIKE :walletQ
                OR COALESCE(wl.sourceType, "") LIKE :walletQ
                OR COALESCE(wl.sourceId, "") LIKE :walletQ
                OR wl.unitCode LIKE :walletQ
            )';
        }

        $movementType = trim((string) ($filters['movementType'] ?? ''));
        if ($movementType === 'in') {
            $where[] = 'wl.amountDelta > 0';
        } elseif ($movementType === 'out') {
            $where[] = 'wl.amountDelta < 0';
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status === 'spent') {
            $where[] = 'wl.amountDelta < 0';
        } elseif ($status === 'received') {
            $where[] = 'wl.amountDelta > 0';
        } elseif ($status !== '') {
            $where[] = '1 = 0';
        }

        $unitCode = trim((string) ($filters['unitCode'] ?? ''));
        if ($unitCode !== '') {
            $params['walletUnitCode'] = $unitCode;
            $where[] = 'wl.unitCode = :walletUnitCode';
        }

        $rewardKind = trim((string) ($filters['rewardKind'] ?? ''));
        if ($rewardKind === 'freeSpin') {
            $where[] = '1 = 0';
        } elseif ($rewardKind !== '' && $rewardKind !== 'mixed') {
            $params['walletRewardKind'] = $rewardKind;
            $where[] = 'wl.unitCode = :walletRewardKind';
        }

        $dateFrom = self::normalizeDateInput($filters['dateFrom'] ?? null);
        if ($dateFrom !== null) {
            $params['walletDateFrom'] = $dateFrom;
            $where[] = 'wl.createDate >= :walletDateFrom';
        }

        $dateTo = self::normalizeDateInput($filters['dateTo'] ?? null);
        if ($dateTo !== null) {
            $params['walletDateTo'] = $dateTo;
            $where[] = 'wl.createDate <= :walletDateTo';
        }

        $sourceType = trim((string) ($filters['sourceType'] ?? ''));
        if ($sourceType !== '') {
            $params['walletSourceType'] = $sourceType;
            $where[] = 'wl.sourceType = :walletSourceType';
        }

        $rows = Database::fetchAll(
            'SELECT
                wl.shopWalletLedgerId AS walletLedgerId,
                wl.unitCode,
                wl.amountDelta AS walletAmountDelta,
                wl.transactionGroupId,
                wl.actorUserId,
                wl.targetUserId,
                wl.walletBalanceBefore,
                wl.walletBalanceAfter,
                wl.resolvedWalletBalanceBefore,
                wl.resolvedWalletBalanceAfter,
                wl.ledgerType,
                wl.sourceType,
                wl.sourceId,
                wl.metadataJson,
                wl.createDate,
                sw.guildId,
                sw.userId,
                u.userName,
                u.globalName,
                u.avatarHash,
                COALESCE(m.nickName, u.globalName, u.userName, sw.userId) AS displayName,
                COALESCE(actorMember.nickName, actorUser.globalName, actorUser.userName, wl.actorUserId) AS actorDisplayName,
                COALESCE(targetMember.nickName, targetUser.globalName, targetUser.userName, wl.targetUserId) AS targetDisplayName,
                h.drawId,
                h.drawStatus,
                h.prizeId,
                h.prizeName,
                h.prizeType,
                h.tierName
             FROM ' . self::shopWalletLedgerProjectionSql() . ' wl
             INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = wl.shopWalletId
             LEFT JOIN tbl_user u ON u.userId = sw.userId
             LEFT JOIN tbl_member m ON m.guildId = sw.guildId AND m.userId = sw.userId
             LEFT JOIN tbl_user actorUser ON actorUser.userId = wl.actorUserId
             LEFT JOIN tbl_member actorMember ON actorMember.guildId = sw.guildId AND actorMember.userId = wl.actorUserId
             LEFT JOIN tbl_user targetUser ON targetUser.userId = wl.targetUserId
             LEFT JOIN tbl_member targetMember ON targetMember.guildId = sw.guildId AND targetMember.userId = wl.targetUserId
             LEFT JOIN tbl_gacha_spin_history h ON h.guildId = sw.guildId
                AND h.drawId = wl.sourceId
                AND wl.sourceType IN ("gacha_spin", "gacha_spin_refund")
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY wl.createDate DESC, wl.shopWalletLedgerId DESC
             LIMIT 1000',
            $params
        );

        return array_map(static function (array $row): array {
            $metadata = json_decode((string) ($row['metadataJson'] ?? ''), true);
            $metadata = is_array($metadata) ? $metadata : [];
            $delta = (int) ($row['walletAmountDelta'] ?? 0);
            $unitCode = (string) ($row['unitCode'] ?? '');
            $row = TransactionTraceService::decorateRowTraceMeta($row, 'wallet_movement', (int) ($row['walletLedgerId'] ?? 0));
            $row['historyKind'] = 'wallet_movement';
            $row['historyId'] = (int) ($row['walletLedgerId'] ?? 0);
            $row['historySortId'] = (int) ($row['walletLedgerId'] ?? 0);
            $row['movementDirection'] = $delta < 0 ? 'out' : 'in';
            $row['rewardEventId'] = null;
            $row['rewardStatus'] = $delta < 0 ? 'spent' : 'received';
            $row['ruleCode'] = '';
            $row['ruleName'] = self::walletSourceLabel((string) ($row['sourceType'] ?? ''), $metadata, $row);
            $row['triggerType'] = (string) ($row['sourceType'] ?? '');
            $row['rewardCoin'] = $delta > 0 && $unitCode === 'coin' ? $delta : 0;
            $row['rewardTicket'] = $delta > 0 && $unitCode === 'ticket' ? $delta : 0;
            $row['rewardFreeSpin'] = 0;
            $row['metadata'] = $metadata;
            $row['condition'] = [];
            $row['rewardConfig'] = [];
            $row['walletDeltaMap'] = [$unitCode => $delta];
            $row['walletBalanceBefore'] = (int) ($row['walletBalanceBefore'] ?? $row['resolvedWalletBalanceBefore'] ?? 0);
            $row['walletBalanceAfter'] = (int) ($row['walletBalanceAfter'] ?? $row['resolvedWalletBalanceAfter'] ?? 0);
            $row['walletBalanceBeforeMap'] = [$unitCode => (int) ($row['walletBalanceBefore'] ?? 0)];
            $row['walletBalanceAfterMap'] = [$unitCode => (int) ($row['walletBalanceAfter'] ?? 0)];
            $row['metadataPretty'] = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);
            $row['sourceDate'] = (string) ($row['createDate'] ?? '');
            $row['sourceSegment'] = 0;
            $row['sourceVoiceSeconds'] = 0;
            $row['sourceActiveMinutes'] = 0;
            $row['sourceWindowStartSeconds'] = 0;
            $row['sourceWindowEndSeconds'] = 0;
            $row['sourceWindowStartMinutes'] = 0;
            $row['sourceWindowEndMinutes'] = 0;
            return $row;
        }, $rows);
    }

    private static function walletSourceLabel(string $sourceType, array $metadata, array $row): string
    {
        return match ($sourceType) {
            'earn_rule' => 'รับจากระบบ Earn',
            'gacha_spin' => 'ใช้หมุนกาชาปอง',
            'gacha_spin_refund' => 'คืนเครดิตกาชาปอง',
            'gacha_reset_mock_credit' => 'รีเซ็ตเครดิต',
            default => trim((string) ($metadata['label'] ?? $metadata['rule'] ?? $sourceType)) ?: 'Wallet movement',
        };
    }

    private static function reportWhereSql(array $filters, array &$params): string
    {
        $where = ['re.guildId = :guildId', 'rr.ruleCode LIKE "earn_%"'];

        $movementType = trim((string) ($filters['movementType'] ?? ''));
        if ($movementType === 'out') {
            $where[] = '1 = 0';
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $qLike = '%' . $q . '%';
            $params['qUserId'] = $qLike;
            $params['qRuleCode'] = $qLike;
            $params['qRuleName'] = $qLike;
            $params['qSourceType'] = $qLike;
            $params['qSourceId'] = $qLike;
            $params['qUserName'] = $qLike;
            $params['qGlobalName'] = $qLike;
            $params['qNickName'] = $qLike;
            $where[] = '(
                re.userId LIKE :qUserId
                OR rr.ruleCode LIKE :qRuleCode
                OR rr.ruleName LIKE :qRuleName
                OR COALESCE(re.sourceType, "") LIKE :qSourceType
                OR COALESCE(re.sourceId, "") LIKE :qSourceId
                OR COALESCE(u.userName, "") LIKE :qUserName
                OR COALESCE(u.globalName, "") LIKE :qGlobalName
                OR COALESCE(m.nickName, "") LIKE :qNickName
            )';
        }

        foreach ([
            'ruleCode' => 'ruleCode',
            'triggerType' => 'triggerType',
            'rewardStatus' => 'status',
            'sourceType' => 'sourceType',
        ] as $column => $filterKey) {
            $value = trim((string) ($filters[$filterKey] ?? ''));
            if ($value === '') {
                continue;
            }
            $params[$filterKey] = $value;
            $where[] = ($column === 'ruleCode' || $column === 'triggerType' ? 'rr.' : 're.') . $column . ' = :' . $filterKey;
        }

        $rewardKind = trim((string) ($filters['rewardKind'] ?? ''));
        $unitCode = trim((string) ($filters['unitCode'] ?? ''));
        if ($unitCode !== '') {
            $rewardKind = $unitCode;
        }
        if ($rewardKind === 'coin') {
            $where[] = self::rewardUnitExistsSql('rewardUnitCodeCoin');
            $params['rewardUnitCodeCoin'] = 'coin';
        } elseif ($rewardKind === 'ticket') {
            $where[] = self::rewardUnitExistsSql('rewardUnitCodeTicket');
            $params['rewardUnitCodeTicket'] = 'ticket';
        } elseif ($rewardKind === 'mixed') {
            $where[] = self::rewardUnitExistsSql('rewardUnitCodeMixedCoin');
            $where[] = self::rewardUnitExistsSql('rewardUnitCodeMixedTicket');
            $params['rewardUnitCodeMixedCoin'] = 'coin';
            $params['rewardUnitCodeMixedTicket'] = 'ticket';
        } elseif ($rewardKind === 'freeSpin') {
            $where[] = self::freeSpinRewardSql() . ' > 0';
        } elseif ($rewardKind !== '') {
            $params['rewardUnitCode'] = $rewardKind;
            $where[] = self::rewardUnitExistsSql('rewardUnitCode');
        }

        $dateFrom = self::normalizeDateInput($filters['dateFrom'] ?? null);
        if ($dateFrom !== null) {
            $params['dateFrom'] = $dateFrom;
            $where[] = 're.createDate >= :dateFrom';
        }

        $dateTo = self::normalizeDateInput($filters['dateTo'] ?? null);
        if ($dateTo !== null) {
            $params['dateTo'] = $dateTo;
            $where[] = 're.createDate <= :dateTo';
        }

        return implode(' AND ', $where);
    }

    private static function coinRewardSql(): string
    {
        return 'CAST(COALESCE(
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.metadataJson, "$.reward.coin")), ""),
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.metadataJson, "$.coins")), ""),
            "0"
        ) AS SIGNED)';
    }

    private static function ticketRewardSql(): string
    {
        return 'CAST(COALESCE(
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.metadataJson, "$.reward.gachaTicket")), ""),
            "0"
        ) AS SIGNED)';
    }

    private static function freeSpinRewardSql(): string
    {
        return 'CAST(COALESCE(
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(re.metadataJson, "$.reward.gachaFreeSpin")), ""),
            "0"
        ) AS SIGNED)';
    }

    private static function rewardUnitExistsSql(string $paramName): string
    {
        return 'EXISTS (
            SELECT 1
              FROM tbl_shop_wallet_ledger wl_filter
              INNER JOIN tbl_shop_wallet sw_filter ON sw_filter.shopWalletId = wl_filter.shopWalletId
             WHERE sw_filter.guildId = re.guildId
               AND sw_filter.userId = re.userId
               AND wl_filter.sourceType IN ("earn_rule", "earn_manual")
               AND wl_filter.sourceId = CONCAT("", re.rewardEventId)
               AND wl_filter.unitCode = :' . $paramName . '
        )';
    }

    private static function shopWalletLedgerProjectionSql(): string
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

    private static function rewardWalletJoinSql(): string
    {
        return '(
            SELECT sw.guildId,
                   sw.userId,
                   wl.sourceId AS rewardEventId,
                   COUNT(*) AS walletLedgerCount,
                   MAX(wl.shopWalletLedgerId) AS walletLedgerId,
                   MAX(wl.createDate) AS walletLedgerDate,
                   GROUP_CONCAT(CONCAT(wl.unitCode, ":", wl.amountDelta) ORDER BY wl.shopWalletLedgerId SEPARATOR "|") AS walletDeltaSummary,
                   GROUP_CONCAT(CONCAT(wl.unitCode, ":", wl.resolvedWalletBalanceBefore) ORDER BY wl.shopWalletLedgerId SEPARATOR "|") AS walletBalanceBeforeSummary,
                   GROUP_CONCAT(CONCAT(wl.unitCode, ":", wl.resolvedWalletBalanceAfter) ORDER BY wl.shopWalletLedgerId SEPARATOR "|") AS walletBalanceAfterSummary
              FROM ' . self::shopWalletLedgerProjectionSql() . ' wl
        INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = wl.shopWalletId
             WHERE wl.sourceType IN ("earn_rule", "earn_manual")
          GROUP BY sw.guildId, sw.userId, wl.sourceId
        )';
    }

    private static function normalizeDateInput(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }
        $timestamp = strtotime($raw);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private static function decorateRow(array $row): array
    {
        $metadata = is_array($row['metadata'] ?? null) ? $row['metadata'] : [];
        $condition = is_array($row['condition'] ?? null) ? $row['condition'] : [];
        $sourceType = (string) ($row['sourceType'] ?? $row['triggerType'] ?? '');
        $segment = self::extractSegment($row['sourceId'] ?? '', $metadata['segment'] ?? null);

        $row['sourceDate'] = self::sourceDate($metadata, $row);
        $row['sourceSegment'] = $segment;
        $row['sourceVoiceSeconds'] = max(0, (int) ($metadata['voiceSeconds'] ?? 0));
        $row['sourceActiveMinutes'] = max(0, (int) ($metadata['activeMinutes'] ?? 0));
        $row['sourceJoinedUserId'] = trim((string) ($metadata['joinedUserId'] ?? ''));

        $row['sourceWindowStartSeconds'] = 0;
        $row['sourceWindowEndSeconds'] = 0;
        $row['sourceWindowStartMinutes'] = 0;
        $row['sourceWindowEndMinutes'] = 0;

        if ($sourceType === 'earn_voice_hourly') {
            $minSeconds = max(60, (int) ($condition['minSeconds'] ?? 3600));
            if ($segment > 0) {
                $row['sourceWindowStartSeconds'] = max(0, ($segment - 1) * $minSeconds);
                $row['sourceWindowEndSeconds'] = $segment * $minSeconds;
            }
        } elseif ($sourceType === 'earn_text_active_daily') {
            $minMinutes = max(1, (int) ($condition['minMinutes'] ?? 10));
            if ($segment > 0) {
                $row['sourceWindowStartMinutes'] = max(0, ($segment - 1) * $minMinutes);
                $row['sourceWindowEndMinutes'] = $segment * $minMinutes;
            }
        }

        return $row;
    }

    private static function sourceDate(array $metadata, array $row): string
    {
        foreach ([
            $metadata['eventDate'] ?? null,
            $row['activityEventDate'] ?? null,
            isset($metadata['date']) ? ((string) $metadata['date'] !== '' ? (string) $metadata['date'] . ' 00:00:00' : null) : null,
        ] as $value) {
            $value = trim((string) ($value ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function extractSegment(mixed $sourceId, mixed $segment): int
    {
        $segmentValue = (int) ($segment ?? 0);
        if ($segmentValue > 0) {
            return $segmentValue;
        }

        $sourceId = trim((string) $sourceId);
        if (preg_match('/:(\d+)$/', $sourceId, $matches)) {
            return max(0, (int) $matches[1]);
        }

        return 0;
    }

    /** @return array<string, int> */
    private static function parseUnitSummary(string $summary): array
    {
        $summary = trim($summary);
        if ($summary === '') {
            return [];
        }

        $out = [];
        foreach (explode('|', $summary) as $pair) {
            [$unitCode, $amount] = array_pad(explode(':', $pair, 2), 2, '');
            $unitCode = trim((string) $unitCode);
            if ($unitCode === '') {
                continue;
            }
            $out[$unitCode] = (int) $amount;
        }

        return $out;
    }
}
