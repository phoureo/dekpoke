<?php

declare(strict_types=1);

final class MileageReportService
{
    /** @return array<string, mixed> */
    public static function report(string $guildId, array $filters = []): array
    {
        GachaMileageService::ensureSchema();

        $guildId = trim($guildId);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = max(25, min(200, (int) ($filters['pageSize'] ?? 50)));
        $normalizedFilters = [
            'page' => $page,
            'pageSize' => $pageSize,
            'q' => trim((string) ($filters['q'] ?? '')),
            'boardCode' => trim((string) ($filters['boardCode'] ?? '')),
            'status' => trim((string) ($filters['status'] ?? '')),
            'dateFrom' => trim((string) ($filters['dateFrom'] ?? '')),
            'dateTo' => trim((string) ($filters['dateTo'] ?? '')),
            'sort' => trim((string) ($filters['sort'] ?? 'lastActivityDate')),
            'dir' => strtolower(trim((string) ($filters['dir'] ?? 'desc'))) === 'asc' ? 'asc' : 'desc',
        ];

        $params = ['guildId' => $guildId];
        $where = ['p.guildId = :guildId'];

        if ($normalizedFilters['q'] !== '') {
            $params['q'] = '%' . $normalizedFilters['q'] . '%';
            $where[] = '(
                p.userId LIKE :q
                OR p.boardCode LIKE :q
                OR COALESCE(u.userName, "") LIKE :q
                OR COALESCE(u.globalName, "") LIKE :q
                OR COALESCE(m.nickName, "") LIKE :q
            )';
        }

        if ($normalizedFilters['boardCode'] !== '') {
            $params['boardCode'] = $normalizedFilters['boardCode'];
            $where[] = 'p.boardCode = :boardCode';
        }

        if ($normalizedFilters['dateFrom'] !== '') {
            $params['dateFrom'] = $normalizedFilters['dateFrom'];
            $where[] = 'COALESCE(p.updateDate, p.createDate) >= :dateFrom';
        }
        if ($normalizedFilters['dateTo'] !== '') {
            $params['dateTo'] = $normalizedFilters['dateTo'];
            $where[] = 'COALESCE(p.updateDate, p.createDate) <= :dateTo';
        }

        $rows = Database::fetchAll(
            'SELECT
                p.userId,
                p.boardCode,
                p.lifetimeSteps,
                p.positionStep,
                p.lastAnimatedStep,
                p.isFinished,
                p.createDate,
                COALESCE(p.updateDate, p.createDate) AS lastActivityDate,
                COALESCE(spinAgg.spinCount, 0) AS spinCount,
                spinAgg.lastSpinDate,
                COALESCE(claimAgg.claimedRewardCount, 0) AS claimedRewardCount,
                claimAgg.lastClaimDate,
                u.userName,
                u.globalName,
                u.avatarHash,
                m.nickName
             FROM tbl_gacha_mileage_progress p
        LEFT JOIN (
                SELECT guildId, userId, boardCode, COUNT(*) AS spinCount, MAX(createDate) AS lastSpinDate
                  FROM tbl_gacha_mileage_spin_ledger
              GROUP BY guildId, userId, boardCode
            ) spinAgg
               ON spinAgg.guildId = p.guildId
              AND spinAgg.userId = p.userId
              AND spinAgg.boardCode = p.boardCode
        LEFT JOIN (
                SELECT guildId, userId, boardCode, COUNT(*) AS claimedRewardCount, MAX(createDate) AS lastClaimDate
                  FROM tbl_gacha_mileage_reward_claim
                 WHERE claimStatus = "granted"
              GROUP BY guildId, userId, boardCode
            ) claimAgg
               ON claimAgg.guildId = p.guildId
              AND claimAgg.userId = p.userId
              AND claimAgg.boardCode = p.boardCode
        LEFT JOIN tbl_user u
               ON u.userId = p.userId
        LEFT JOIN tbl_member m
               ON m.guildId = p.guildId
              AND m.userId = p.userId
            WHERE ' . implode(' AND ', $where),
            $params
        );

        $boardCache = [];
        $decorated = array_map(static function (array $row) use (&$boardCache, $guildId): array {
            $boardCode = (string) ($row['boardCode'] ?? GachaMileageService::DEFAULT_BOARD_CODE);
            if (!isset($boardCache[$boardCode])) {
                $boardCache[$boardCode] = GachaMileageService::boardDefinition($boardCode);
            }

            $summary = GachaMileageService::summary($guildId, (string) ($row['userId'] ?? ''), $boardCode);
            $claimableRewardCount = max(0, (int) ($summary['claimableRewardCount'] ?? 0));
            $claimedRewardCount = max(0, (int) ($row['claimedRewardCount'] ?? 0));
            $finished = !empty($row['isFinished']);
            $status = $claimableRewardCount > 0
                ? 'claimable'
                : ($finished ? 'finished' : ($claimedRewardCount > 0 ? 'claimed' : 'active'));

            $row['displayName'] = (string) (($row['nickName'] ?? '') ?: ($row['globalName'] ?? '') ?: ($row['userName'] ?? '') ?: ($row['userId'] ?? ''));
            $row['boardTitle'] = (string) ($boardCache[$boardCode]['title'] ?? $boardCode);
            $row['claimableRewardCount'] = $claimableRewardCount;
            $row['claimedRewardCount'] = $claimedRewardCount;
            $row['finished'] = $finished;
            $row['progressStatus'] = $status;
            $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);

            return $row;
        }, $rows);

        if ($normalizedFilters['status'] !== '') {
            $decorated = array_values(array_filter($decorated, static fn (array $row): bool => (string) ($row['progressStatus'] ?? '') === $normalizedFilters['status']));
        }

        self::sortRows($decorated, $normalizedFilters['sort'], $normalizedFilters['dir']);

        $total = count($decorated);
        $offset = ($page - 1) * $pageSize;
        $pageRows = array_slice($decorated, $offset, $pageSize);

        $boardOptions = [];
        foreach ($decorated as $row) {
            $boardCode = (string) ($row['boardCode'] ?? '');
            if ($boardCode === '' || isset($boardOptions[$boardCode])) {
                continue;
            }
            $boardOptions[$boardCode] = [
                'boardCode' => $boardCode,
                'title' => (string) ($row['boardTitle'] ?? $boardCode),
            ];
        }

        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'metrics' => [
                'players' => $total,
                'activePlayers' => count(array_filter($decorated, static fn (array $row): bool => max(0, (int) ($row['lifetimeSteps'] ?? 0)) > 0)),
                'totalSteps' => array_sum(array_map(static fn (array $row): int => max(0, (int) ($row['lifetimeSteps'] ?? 0)), $decorated)),
                'totalSpins' => array_sum(array_map(static fn (array $row): int => max(0, (int) ($row['spinCount'] ?? 0)), $decorated)),
                'claimedRewards' => array_sum(array_map(static fn (array $row): int => max(0, (int) ($row['claimedRewardCount'] ?? 0)), $decorated)),
                'claimablePlayers' => count(array_filter($decorated, static fn (array $row): bool => max(0, (int) ($row['claimableRewardCount'] ?? 0)) > 0)),
            ],
            'rows' => $pageRows,
            'filters' => $normalizedFilters,
            'filterOptions' => [
                'boards' => array_values($boardOptions),
                'statuses' => ['active', 'claimable', 'claimed', 'finished'],
            ],
        ];
    }

    /** @param array<int, array<string, mixed>> $rows */
    private static function sortRows(array &$rows, string $sort, string $dir): void
    {
        $dirFactor = strtolower($dir) === 'asc' ? 1 : -1;
        usort($rows, static function (array $left, array $right) use ($sort, $dirFactor): int {
            $compare = match ($sort) {
                'displayName' => strcmp((string) ($left['displayName'] ?? ''), (string) ($right['displayName'] ?? '')),
                'boardCode' => strcmp((string) ($left['boardCode'] ?? ''), (string) ($right['boardCode'] ?? '')),
                'lifetimeSteps' => ((int) ($left['lifetimeSteps'] ?? 0)) <=> ((int) ($right['lifetimeSteps'] ?? 0)),
                'spinCount' => ((int) ($left['spinCount'] ?? 0)) <=> ((int) ($right['spinCount'] ?? 0)),
                'claimedRewardCount' => ((int) ($left['claimedRewardCount'] ?? 0)) <=> ((int) ($right['claimedRewardCount'] ?? 0)),
                'claimableRewardCount' => ((int) ($left['claimableRewardCount'] ?? 0)) <=> ((int) ($right['claimableRewardCount'] ?? 0)),
                default => strcmp((string) ($left['lastActivityDate'] ?? ''), (string) ($right['lastActivityDate'] ?? '')),
            };

            if ($compare === 0) {
                $compare = strcmp((string) ($left['displayName'] ?? ''), (string) ($right['displayName'] ?? ''));
            }

            return $compare * $dirFactor;
        });
    }
}
