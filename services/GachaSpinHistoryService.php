<?php

declare(strict_types=1);

final class GachaSpinHistoryService
{
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_spin_history (
                gachaSpinHistoryId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                drawId varchar(64) NOT NULL,
                drawStatus varchar(40) NOT NULL DEFAULT "started",
                `count` int unsigned NOT NULL DEFAULT 1,
                buttonId int unsigned NOT NULL DEFAULT 0,
                currency varchar(80) NOT NULL DEFAULT "ticket",
                costPerSpin int unsigned NOT NULL DEFAULT 0,
                totalCost int unsigned NOT NULL DEFAULT 0,
                balanceBefore bigint NOT NULL DEFAULT 0,
                balanceAfter bigint NOT NULL DEFAULT 0,
                lockedType varchar(80) DEFAULT NULL,
                tierId varchar(120) DEFAULT NULL,
                tierName varchar(190) DEFAULT NULL,
                prizeId varchar(190) DEFAULT NULL,
                prizeName varchar(255) DEFAULT NULL,
                prizeType varchar(80) DEFAULT NULL,
                reusedPendingReward tinyint(1) NOT NULL DEFAULT 0,
                startedAt datetime DEFAULT NULL,
                revealedAt datetime DEFAULT NULL,
                ballIssuedAt datetime DEFAULT NULL,
                ballSeenAt datetime DEFAULT NULL,
                prizeResolvedAt datetime DEFAULT NULL,
                completedAt datetime DEFAULT NULL,
                refundedAt datetime DEFAULT NULL,
                snapshotJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaSpinHistoryId),
                UNIQUE KEY uq_tbl_gacha_spin_history_draw (guildId, drawId),
                KEY idx_tbl_gacha_spin_history_started (startedAt),
                KEY idx_tbl_gacha_spin_history_user_started (userId, startedAt),
                KEY idx_tbl_gacha_spin_history_status_started (drawStatus, startedAt),
                KEY idx_tbl_gacha_spin_history_prize (prizeId)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    public static function syncFromDraw(string $guildId, string $userId, array $draw): void
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $drawId = trim((string) ($draw['drawId'] ?? ''));
        if ($guildId === '' || $userId === '' || $drawId === '') {
            return;
        }

        $status = self::statusFromDraw($draw);
        $tier = is_array($draw['tier'] ?? null) ? $draw['tier'] : [];
        $prize = is_array($draw['prize'] ?? null) ? $draw['prize'] : [];
        $snapshot = self::snapshotFromDraw($draw, $status);

        Database::execute(
            'INSERT INTO tbl_gacha_spin_history
                (guildId, userId, drawId, drawStatus, `count`, buttonId, currency, costPerSpin, totalCost, balanceBefore, balanceAfter,
                 lockedType, tierId, tierName, prizeId, prizeName, prizeType, reusedPendingReward,
                 startedAt, revealedAt, ballIssuedAt, ballSeenAt, prizeResolvedAt, completedAt, refundedAt, snapshotJson, updateDate)
             VALUES
                (:guildId, :userId, :drawId, :drawStatus, :count, :buttonId, :currency, :costPerSpin, :totalCost, :balanceBefore, :balanceAfter,
                 :lockedType, :tierId, :tierName, :prizeId, :prizeName, :prizeType, :reusedPendingReward,
                 :startedAt, :revealedAt, :ballIssuedAt, :ballSeenAt, :prizeResolvedAt, :completedAt, :refundedAt, :snapshotJson, :updateDate)
             ON DUPLICATE KEY UPDATE
                userId = VALUES(userId),
                drawStatus = VALUES(drawStatus),
                `count` = VALUES(`count`),
                buttonId = VALUES(buttonId),
                currency = VALUES(currency),
                costPerSpin = VALUES(costPerSpin),
                totalCost = VALUES(totalCost),
                balanceBefore = VALUES(balanceBefore),
                balanceAfter = VALUES(balanceAfter),
                lockedType = VALUES(lockedType),
                tierId = VALUES(tierId),
                tierName = VALUES(tierName),
                prizeId = VALUES(prizeId),
                prizeName = VALUES(prizeName),
                prizeType = VALUES(prizeType),
                reusedPendingReward = VALUES(reusedPendingReward),
                startedAt = VALUES(startedAt),
                revealedAt = VALUES(revealedAt),
                ballIssuedAt = VALUES(ballIssuedAt),
                ballSeenAt = VALUES(ballSeenAt),
                prizeResolvedAt = VALUES(prizeResolvedAt),
                completedAt = VALUES(completedAt),
                refundedAt = VALUES(refundedAt),
                snapshotJson = VALUES(snapshotJson),
                updateDate = VALUES(updateDate)',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'drawId' => $drawId,
                'drawStatus' => $status,
                'count' => max(1, (int) ($draw['count'] ?? 1)),
                'buttonId' => max(0, (int) ($draw['buttonId'] ?? 0)),
                'currency' => (string) ($draw['currency'] ?? 'ticket'),
                'costPerSpin' => max(0, (int) ($draw['costPerSpin'] ?? 0)),
                'totalCost' => max(0, (int) ($draw['cost'] ?? 0)),
                'balanceBefore' => (int) ($draw['balanceBefore'] ?? 0),
                'balanceAfter' => (int) ($draw['balanceAfter'] ?? 0),
                'lockedType' => (string) ($draw['lockedType'] ?? ''),
                'tierId' => (string) ($tier['id'] ?? ($draw['lockedType'] ?? '')),
                'tierName' => (string) ($tier['tier'] ?? $tier['name'] ?? $draw['lockedType'] ?? ''),
                'prizeId' => (string) ($prize['id'] ?? ''),
                'prizeName' => (string) ($prize['name'] ?? ''),
                'prizeType' => (string) ($prize['type'] ?? ''),
                'reusedPendingReward' => !empty($draw['reusedPendingReward']) ? 1 : 0,
                'startedAt' => self::sqlDate($draw['createdAt'] ?? null),
                'revealedAt' => self::sqlDate($draw['revealedAt'] ?? null),
                'ballIssuedAt' => self::sqlDate($draw['ballIssuedAt'] ?? null),
                'ballSeenAt' => self::sqlDate($draw['ballSeenAt'] ?? null),
                'prizeResolvedAt' => self::sqlDate($draw['prizeResolvedAt'] ?? null),
                'completedAt' => self::sqlDate($draw['completedAt'] ?? null),
                'refundedAt' => self::sqlDate($draw['refundedAt'] ?? null),
                'snapshotJson' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public static function report(string $guildId, array $filters = []): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = max(25, min(200, (int) ($filters['pageSize'] ?? 50)));
        $offset = ($page - 1) * $pageSize;

        $params = ['guildId' => $guildId];
        $whereSql = self::reportWhereSql($filters, $params);

        $joins = '
            LEFT JOIN tbl_user u ON u.userId = h.userId
            LEFT JOIN tbl_member m ON m.guildId = h.guildId AND m.userId = h.userId
        ';

        $config = class_exists('GachaConfigService') ? GachaConfigService::load() : ['prizes' => [], 'tiers' => []];
        $prizeDirectory = self::prizeDirectory($config);
        $roleGrantMap = [];

        $rows = Database::fetchAll(
            'SELECT h.*,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    COALESCE(m.nickName, u.globalName, u.userName, h.userId) AS displayName
               FROM tbl_gacha_spin_history h
               ' . $joins . '
              WHERE ' . $whereSql . '
              ORDER BY h.startedAt DESC, h.gachaSpinHistoryId DESC',
            $params
        );

        if (class_exists('GachaRoleGrantService')) {
            try {
                $roleGrantMap = (new GachaRoleGrantService())->mapByDrawIds($guildId, array_map(
                    static fn (array $row): string => (string) ($row['drawId'] ?? ''),
                    $rows
                ));
            } catch (Throwable) {
                $roleGrantMap = [];
            }
        }

        $rows = array_values(array_filter(array_map(
            static fn (array $row): array => self::decorateReportRow($row, $prizeDirectory, $roleGrantMap),
            $rows
        ), static fn (array $row): bool => self::matchesDerivedFilters($row, $filters)));
        $rows = self::sortReportRows($rows, $filters);

        $tierRows = self::tierRowsFromReportRows($rows);
        $tierMeta = self::highestTierMeta($tierRows);
        $metricRow = self::metricRowFromReportRows($rows);
        $pageRows = array_slice($rows, $offset, $pageSize);

        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => count($rows),
            'metrics' => [
                'totalSpins' => (int) ($metricRow['totalSpins'] ?? 0),
                'completedSpins' => (int) ($metricRow['completedSpins'] ?? 0),
                'refundedSpins' => (int) ($metricRow['refundedSpins'] ?? 0),
                'ticketSpent' => (int) ($metricRow['ticketSpent'] ?? 0),
                'coinSpent' => (int) ($metricRow['coinSpent'] ?? 0),
                'highestTierHits' => (int) ($tierMeta['count'] ?? 0),
                'highestTierId' => (string) ($tierMeta['tierId'] ?? ''),
                'highestTierName' => (string) ($tierMeta['tierName'] ?? ''),
            ],
            'rows' => $pageRows,
        ];
    }

    private static function sortReportRows(array $rows, array $filters): array
    {
        $allowed = [
            'startedAt', 'displayName', 'drawStatus', 'buttonId', 'totalCost',
            'tierName', 'prizeName', 'rewardDurationLabel', 'roleGrantStatus',
            'counterCurrentValue', 'finishedAt',
        ];
        $sort = trim((string) ($filters['sort'] ?? 'startedAt'));
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
                ?: strcmp((string) ($right['startedAt'] ?? ''), (string) ($left['startedAt'] ?? ''))
                ?: ((int) ($right['gachaSpinHistoryId'] ?? 0) <=> (int) ($left['gachaSpinHistoryId'] ?? 0));
        });
        return $rows;
    }

    private static function reportWhereSql(array $filters, array &$params): string
    {
        $where = ['h.guildId = :guildId'];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $qLike = '%' . $q . '%';
            $params['qUserId'] = $qLike;
            $params['qDrawId'] = $qLike;
            $params['qPrizeName'] = $qLike;
            $params['qPrizeId'] = $qLike;
            $params['qUserName'] = $qLike;
            $params['qGlobalName'] = $qLike;
            $params['qNickName'] = $qLike;
            $where[] = '(
                h.userId LIKE :qUserId
                OR h.drawId LIKE :qDrawId
                OR COALESCE(h.prizeName, "") LIKE :qPrizeName
                OR COALESCE(h.prizeId, "") LIKE :qPrizeId
                OR COALESCE(u.userName, "") LIKE :qUserName
                OR COALESCE(u.globalName, "") LIKE :qGlobalName
                OR COALESCE(m.nickName, "") LIKE :qNickName
            )';
        }

        $drawId = trim((string) ($filters['drawId'] ?? ''));
        if ($drawId !== '') {
            $params['drawId'] = '%' . $drawId . '%';
            $where[] = 'h.drawId LIKE :drawId';
        }

        foreach ([
            'drawStatus' => 'status',
            'currency' => 'currency',
            'buttonId' => 'buttonId',
            'tierId' => 'tierId',
            'prizeType' => 'prizeType',
        ] as $column => $filterKey) {
            $value = trim((string) ($filters[$filterKey] ?? ''));
            if ($value === '') {
                continue;
            }
            $params[$filterKey] = $value;
            $where[] = 'h.' . $column . ' = :' . $filterKey;
        }

        $dateFrom = self::normalizeDateInput($filters['dateFrom'] ?? null);
        if ($dateFrom !== null) {
            $params['dateFrom'] = $dateFrom;
            $where[] = 'h.startedAt >= :dateFrom';
        }

        $dateTo = self::normalizeDateInput($filters['dateTo'] ?? null);
        if ($dateTo !== null) {
            $params['dateTo'] = $dateTo;
            $where[] = 'h.startedAt <= :dateTo';
        }

        return implode(' AND ', $where);
    }

    private static function highestTierMeta(array $tierRows): array
    {
        $counts = [];
        foreach ($tierRows as $row) {
            $tierId = (string) ($row['tierId'] ?? '');
            if ($tierId === '') {
                continue;
            }
            $counts[$tierId] = (int) ($row['total'] ?? 0);
        }

        $config = class_exists('GachaConfigService') ? GachaConfigService::load() : ['tiers' => []];
        $tiers = is_array($config['tiers'] ?? null) ? $config['tiers'] : [];
        for ($index = count($tiers) - 1; $index >= 0; $index--) {
            $tier = is_array($tiers[$index] ?? null) ? $tiers[$index] : [];
            $tierId = (string) ($tier['id'] ?? '');
            if ($tierId === '' || empty($counts[$tierId])) {
                continue;
            }
            return [
                'tierId' => $tierId,
                'tierName' => (string) ($tier['tier'] ?? $tier['name'] ?? $tierId),
                'count' => (int) $counts[$tierId],
            ];
        }

        if ($counts === []) {
            return ['tierId' => '', 'tierName' => '', 'count' => 0];
        }

        arsort($counts);
        $fallbackTierId = (string) array_key_first($counts);
        return [
            'tierId' => $fallbackTierId,
            'tierName' => $fallbackTierId,
            'count' => (int) ($counts[$fallbackTierId] ?? 0),
        ];
    }

    private static function statusFromDraw(array $draw): string
    {
        if (!empty($draw['completedAt'])) {
            return 'completed';
        }
        if (!empty($draw['refundedAt'])) {
            return 'refunded';
        }
        if (!empty($draw['prizeResolvedAt'])) {
            return 'resolved';
        }
        if (!empty($draw['ballSeenAt'])) {
            return 'ball_seen';
        }
        if (!empty($draw['revealedAt']) || !empty($draw['ballIssuedAt'])) {
            return 'revealed';
        }
        return 'started';
    }

    private static function snapshotFromDraw(array $draw, string $status): array
    {
        return [
            'drawId' => (string) ($draw['drawId'] ?? ''),
            'drawStatus' => $status,
            'count' => max(1, (int) ($draw['count'] ?? 1)),
            'buttonId' => max(0, (int) ($draw['buttonId'] ?? 0)),
            'currency' => (string) ($draw['currency'] ?? 'ticket'),
            'cost' => max(0, (int) ($draw['cost'] ?? 0)),
            'costPerSpin' => max(0, (int) ($draw['costPerSpin'] ?? 0)),
            'balanceBefore' => (int) ($draw['balanceBefore'] ?? 0),
            'balanceAfter' => (int) ($draw['balanceAfter'] ?? 0),
            'balancesAfter' => is_array($draw['balancesAfter'] ?? null) ? $draw['balancesAfter'] : [],
            'lockedType' => (string) ($draw['lockedType'] ?? ''),
            'tier' => is_array($draw['tier'] ?? null) ? $draw['tier'] : null,
            'prize' => is_array($draw['prize'] ?? null) ? $draw['prize'] : null,
            'campaignCounter' => is_array($draw['campaignCounter'] ?? null) ? $draw['campaignCounter'] : null,
            'condition' => is_array($draw['condition'] ?? null) ? $draw['condition'] : null,
            'reusedPendingReward' => !empty($draw['reusedPendingReward']),
            'refund' => [
                'amount' => (int) ($draw['refundAmount'] ?? 0),
                'currency' => (string) ($draw['refundCurrency'] ?? ''),
                'balanceBefore' => (int) ($draw['refundBalanceBefore'] ?? 0),
                'balanceAfter' => (int) ($draw['refundBalanceAfter'] ?? 0),
            ],
            'timeline' => [
                'createdAt' => (int) ($draw['createdAt'] ?? 0),
                'revealedAt' => (int) ($draw['revealedAt'] ?? 0),
                'ballIssuedAt' => (int) ($draw['ballIssuedAt'] ?? 0),
                'ballSeenAt' => (int) ($draw['ballSeenAt'] ?? 0),
                'prizeResolvedAt' => (int) ($draw['prizeResolvedAt'] ?? 0),
                'completedAt' => (int) ($draw['completedAt'] ?? 0),
                'refundedAt' => (int) ($draw['refundedAt'] ?? 0),
            ],
        ];
    }

    private static function sqlDate(mixed $value): ?string
    {
        if (!is_numeric($value)) {
            return null;
        }

        $timestamp = (int) $value;
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    private static function normalizeDateInput(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) ? $value : null;
    }

    private static function prizeDirectory(array $config): array
    {
        $directory = [];
        foreach ($config['prizes'] ?? [] as $prize) {
            if (!is_array($prize)) {
                continue;
            }
            $prizeId = trim((string) ($prize['id'] ?? ''));
            if ($prizeId === '') {
                continue;
            }
            if (($prize['type'] ?? '') === 'role' && class_exists('GachaConfigService')) {
                try {
                    $resolved = GachaConfigService::publicPrizePayload($config, $prize, [
                        'id' => (string) ($prize['tierId'] ?? 'role'),
                        'tier' => (string) ($prize['tierId'] ?? 'Role'),
                    ]);
                    $prize['_resolvedRoleName'] = trim((string) ($resolved['roleName'] ?? ''));
                    $prize['_resolvedName'] = trim((string) ($resolved['name'] ?? ''));
                } catch (Throwable) {
                    $prize['_resolvedRoleName'] = '';
                    $prize['_resolvedName'] = '';
                }
            }
            $directory[$prizeId] = $prize;
        }
        return $directory;
    }

    private static function decorateReportRow(array $row, array $prizeDirectory, array $roleGrantMap): array
    {
        $snapshot = json_decode((string) ($row['snapshotJson'] ?? ''), true);
        $row['snapshot'] = is_array($snapshot) ? $snapshot : [];
        $row['snapshotPretty'] = json_encode($row['snapshot'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);
        $row['counterDisplayValue'] = (string) (
            $row['snapshot']['campaignCounter']['displayValue']
            ?? $row['snapshot']['campaignCounter']['current']
            ?? ''
        );
        $row['counterCurrentValue'] = (int) (
            $row['snapshot']['campaignCounter']['current']
            ?? $row['snapshot']['campaignCounter']['after']
            ?? 0
        );
        $row['finishedAt'] = $row['completedAt'] ?: ($row['refundedAt'] ?: null);

        $snapshotPrize = is_array($row['snapshot']['prize'] ?? null) ? $row['snapshot']['prize'] : [];
        $prizeId = trim((string) ($row['prizeId'] ?? ($snapshotPrize['id'] ?? '')));
        $configPrize = is_array($prizeDirectory[$prizeId] ?? null) ? $prizeDirectory[$prizeId] : [];
        $prizeType = trim((string) ($row['prizeType'] ?? ($snapshotPrize['type'] ?? ($configPrize['type'] ?? ''))));
        $displayPrizeName = trim((string) ($row['prizeName'] ?? ($snapshotPrize['name'] ?? ($configPrize['_resolvedName'] ?? ($configPrize['name'] ?? '')))));

        if ($prizeId !== '') {
            $row['prizeId'] = $prizeId;
        }
        if ($prizeType !== '') {
            $row['prizeType'] = $prizeType;
        }
        if ($displayPrizeName !== '') {
            $row['prizeName'] = $displayPrizeName;
        }

        $durationDays = $prizeType === 'role'
            ? max(0, (int) ($configPrize['roleDurationDays'] ?? ($snapshotPrize['roleDurationDays'] ?? 0)))
            : 0;
        $roleId = trim((string) ($snapshotPrize['roleId'] ?? ($configPrize['discordRoleId'] ?? '')));
        $rewardGrantedAt = $row['completedAt'] ?: ($row['prizeResolvedAt'] ?: ($row['ballSeenAt'] ?: ($row['revealedAt'] ?: ($row['startedAt'] ?? null))));
        $rewardRemoveAt = null;
        if ($prizeType === 'role' && $durationDays > 0 && is_string($rewardGrantedAt) && $rewardGrantedAt !== '') {
            $grantedTs = strtotime($rewardGrantedAt);
            if ($grantedTs !== false) {
                $rewardRemoveAt = date('Y-m-d H:i:s', $grantedTs + ($durationDays * 86400));
            }
        }

        $row['roleId'] = $roleId;
        $row['rewardGrantedAt'] = $rewardGrantedAt;
        $row['rewardDurationDays'] = $durationDays;
        $row['isTemporaryRole'] = $prizeType === 'role' && $durationDays > 0;
        $row['isPermanentReward'] = !$row['isTemporaryRole'];
        $row['rewardDurationLabel'] = $row['isTemporaryRole'] ? ($durationDays . ' วัน') : 'ถาวร';
        $row['rewardRemoveAt'] = $rewardRemoveAt;
        $row['rewardRemoveLabel'] = $rewardRemoveAt ?: 'ถาวร';
        $row['rewardRemoveRelativeLabel'] = self::rewardRemoveRelativeLabel($rewardRemoveAt);

        $drawId = trim((string) ($row['drawId'] ?? ''));
        $grant = is_array($roleGrantMap[$drawId] ?? null) ? $roleGrantMap[$drawId] : null;
        $row['roleGrantStatus'] = (string) ($grant['grantStatus'] ?? '');
        $row['roleGrantGrantedAt'] = $grant['grantedAt'] ?? null;
        $row['roleGrantExpireAt'] = $grant['expireAt'] ?? null;
        $row['roleGrantRevokedAt'] = $grant['revokedAt'] ?? null;
        $row['roleGrantLastError'] = $grant['lastError'] ?? null;
        if ($grant) {
            if ($row['roleId'] === '') {
                $row['roleId'] = trim((string) ($grant['roleId'] ?? ''));
            }
            $row['rewardGrantedAt'] = $grant['grantedAt'] ?: $row['rewardGrantedAt'];
            $row['rewardRemoveAt'] = $grant['expireAt'] ?: $row['rewardRemoveAt'];
            $row['rewardRemoveLabel'] = $row['rewardRemoveAt'] ?: ($row['rewardRemoveLabel'] ?? 'ถาวร');
            $row['rewardRemoveRelativeLabel'] = self::rewardRemoveRelativeLabel($row['rewardRemoveAt']);
        }

        $sourceRoleName = '';
        if ($prizeType === 'role') {
            $sourceRoleName = trim((string) (
                $snapshotPrize['roleName']
                ?? ($grant['roleName'] ?? ($configPrize['_resolvedRoleName'] ?? ''))
            ));
            if ($displayPrizeName === '' || $displayPrizeName === '<roleName>') {
                $displayPrizeName = $sourceRoleName !== '' ? $sourceRoleName : ($row['prizeId'] ?? '');
                $row['prizeName'] = $displayPrizeName;
            }
        }

        $row['displayPrizeName'] = $displayPrizeName !== '' ? $displayPrizeName : ($row['prizeId'] ?? '');
        $row['sourceRoleName'] = $sourceRoleName;
        $row['roleName'] = $sourceRoleName;
        $row['reportPrizeName'] = $prizeType === 'role'
            ? ($sourceRoleName !== '' ? $sourceRoleName : $row['displayPrizeName'])
            : $row['displayPrizeName'];

        return $row;
    }

    private static function rewardRemoveRelativeLabel(?string $rewardRemoveAt): string
    {
        if (!$rewardRemoveAt) {
            return 'ไม่กำหนด';
        }

        $expireTs = strtotime($rewardRemoveAt);
        if ($expireTs === false) {
            return '-';
        }

        $seconds = $expireTs - time();
        if ($seconds <= 0) {
            return 'ครบกำหนดแล้ว';
        }

        return 'อีก ' . max(1, (int) ceil($seconds / 86400)) . ' วัน';
    }

    private static function matchesDerivedFilters(array $row, array $filters): bool
    {
        $durationKind = trim((string) ($filters['durationKind'] ?? ''));
        if ($durationKind === 'temporary_role') {
            return !empty($row['isTemporaryRole']);
        }
        if ($durationKind === 'permanent') {
            return !empty($row['isPermanentReward']);
        }
        return true;
    }

    private static function tierRowsFromReportRows(array $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $tierId = trim((string) ($row['tierId'] ?? ''));
            if ($tierId === '') {
                continue;
            }
            $counts[$tierId] = ($counts[$tierId] ?? 0) + 1;
        }

        $tierRows = [];
        foreach ($counts as $tierId => $total) {
            $tierRows[] = ['tierId' => $tierId, 'total' => $total];
        }
        return $tierRows;
    }

    private static function metricRowFromReportRows(array $rows): array
    {
        $metrics = [
            'totalSpins' => 0,
            'completedSpins' => 0,
            'refundedSpins' => 0,
            'ticketSpent' => 0,
            'coinSpent' => 0,
        ];

        foreach ($rows as $row) {
            $metrics['totalSpins']++;
            if (($row['drawStatus'] ?? '') === 'completed') {
                $metrics['completedSpins']++;
            }
            if (($row['drawStatus'] ?? '') === 'refunded') {
                $metrics['refundedSpins']++;
            }
            if (($row['currency'] ?? '') === 'ticket') {
                $metrics['ticketSpent'] += (int) ($row['totalCost'] ?? 0);
            }
            if (($row['currency'] ?? '') === 'coin') {
                $metrics['coinSpent'] += (int) ($row['totalCost'] ?? 0);
            }
        }

        return $metrics;
    }
}
