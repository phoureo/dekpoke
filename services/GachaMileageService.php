<?php

declare(strict_types=1);

final class GachaMileageService
{
    public const DEFAULT_BOARD_CODE = 'main';
    public const RULE_CODE = 'gacha_mileage_reward';

    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_mileage_progress (
                gachaMileageProgressId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                boardCode varchar(80) NOT NULL,
                lifetimeSteps int unsigned NOT NULL DEFAULT 0,
                positionStep int NOT NULL DEFAULT -1,
                lastAnimatedStep int NOT NULL DEFAULT -1,
                isFinished tinyint(1) NOT NULL DEFAULT 0,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaMileageProgressId),
                UNIQUE KEY uq_tbl_gacha_mileage_progress_user (guildId, userId, boardCode),
                KEY idx_tbl_gacha_mileage_progress_board (guildId, boardCode, positionStep)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_mileage_spin_ledger (
                gachaMileageSpinLedgerId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                boardCode varchar(80) NOT NULL,
                drawId varchar(64) NOT NULL,
                stepDelta int unsigned NOT NULL DEFAULT 0,
                lifetimeBefore int unsigned NOT NULL DEFAULT 0,
                lifetimeAfter int unsigned NOT NULL DEFAULT 0,
                positionBefore int NOT NULL DEFAULT -1,
                positionAfter int NOT NULL DEFAULT -1,
                isFinishedAfter tinyint(1) NOT NULL DEFAULT 0,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (gachaMileageSpinLedgerId),
                UNIQUE KEY uq_tbl_gacha_mileage_spin_draw (guildId, boardCode, drawId),
                KEY idx_tbl_gacha_mileage_spin_user (guildId, userId, boardCode, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_mileage_reward_claim (
                gachaMileageRewardClaimId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                boardCode varchar(80) NOT NULL,
                rewardId varchar(120) NOT NULL,
                stepIndex int DEFAULT NULL,
                rewardEventId bigint unsigned DEFAULT NULL,
                claimStatus varchar(24) NOT NULL DEFAULT "granted",
                rewardJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaMileageRewardClaimId),
                UNIQUE KEY uq_tbl_gacha_mileage_reward_claim_reward (guildId, userId, boardCode, rewardId),
                KEY idx_tbl_gacha_mileage_reward_claim_step (guildId, userId, boardCode, stepIndex)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    public static function boardDefinition(string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        return self::loadBoardDefinition($boardCode);
    }

    public static function saveBoardDefinition(array $payload, string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        $boardCode = self::normalizeBoardCode($payload['boardCode'] ?? $boardCode);
        $board = self::normalizeBoard($payload + ['boardCode' => $boardCode]);
        $path = self::boardPath($boardCode);
        self::writeJsonFile($path, $board, 'MILEAGE_BOARD_SAVE_FAILED');

        return $board;
    }

    public static function editorBootstrap(string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        $boardCode = self::normalizeBoardCode($boardCode);
        $live = self::loadBoardDefinition($boardCode);
        $draft = self::loadDraftDefinition($boardCode);

        return [
            'boardCode' => $boardCode,
            'liveBoard' => $live,
            'draftBoard' => $draft,
            'workingBoard' => $draft ?: $live,
            'hasDraft' => $draft !== null,
            'versions' => self::versionManifest($boardCode),
            'dataUrlBlocked' => self::containsDataUrl($draft ?: $live),
        ];
    }

    public static function draftBoardDefinition(string $boardCode = self::DEFAULT_BOARD_CODE): ?array
    {
        return self::loadDraftDefinition($boardCode);
    }

    public static function previewBoardDefinition(?array $payload = null, string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        $boardCode = self::normalizeBoardCode($payload['boardCode'] ?? $boardCode);
        if (is_array($payload) && $payload !== []) {
            return self::normalizeBoard($payload + ['boardCode' => $boardCode]);
        }

        return self::loadDraftDefinition($boardCode) ?: self::loadBoardDefinition($boardCode);
    }

    public static function saveDraftDefinition(array $payload, string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        $boardCode = self::normalizeBoardCode($payload['boardCode'] ?? $boardCode);
        $board = self::normalizeBoard($payload + ['boardCode' => $boardCode]);
        self::writeJsonFile(self::draftPath($boardCode), $board, 'MILEAGE_DRAFT_SAVE_FAILED');
        self::writeDraftMeta($boardCode, [
            'boardCode' => $boardCode,
            'savedAt' => date(DateTimeInterface::ATOM),
            'hasDataUrl' => self::containsDataUrl($board),
        ]);
        return $board;
    }

    public static function publishDraftDefinition(string $boardCode = self::DEFAULT_BOARD_CODE, ?array $payload = null): array
    {
        $boardCode = self::normalizeBoardCode($payload['boardCode'] ?? $boardCode);
        $draft = $payload !== null
            ? self::normalizeBoard($payload + ['boardCode' => $boardCode])
            : self::loadDraftDefinition($boardCode);
        if (!$draft) {
            throw new RuntimeException('MILEAGE_DRAFT_NOT_FOUND');
        }
        if (self::containsDataUrl($draft)) {
            throw new RuntimeException('MILEAGE_DATA_URL_ASSET_BLOCKED');
        }

        $live = self::loadBoardDefinition($boardCode);
        $version = self::snapshotVersion($boardCode, $live, [
            'source' => 'publish',
            'publishedAt' => date(DateTimeInterface::ATOM),
        ]);
        self::writeJsonFile(self::boardPath($boardCode), $draft, 'MILEAGE_BOARD_SAVE_FAILED');
        self::writeDraftMeta($boardCode, [
            'boardCode' => $boardCode,
            'savedAt' => date(DateTimeInterface::ATOM),
            'publishedAt' => date(DateTimeInterface::ATOM),
            'publishedVersionBefore' => $version['id'] ?? '',
            'hasDataUrl' => false,
        ]);

        return [
            'board' => $draft,
            'snapshot' => $version,
            'versions' => self::versionManifest($boardCode),
        ];
    }

    public static function rollbackVersion(string $boardCode, string $versionId, bool $publish = false): array
    {
        $boardCode = self::normalizeBoardCode($boardCode);
        $versionId = self::normalizeVersionId($versionId);
        $path = self::versionPath($boardCode, $versionId);
        if (!is_file($path)) {
            throw new RuntimeException('MILEAGE_VERSION_NOT_FOUND');
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        $board = self::normalizeBoard(is_array($decoded) ? $decoded : ['boardCode' => $boardCode]);
        if (self::containsDataUrl($board)) {
            throw new RuntimeException('MILEAGE_DATA_URL_ASSET_BLOCKED');
        }

        if ($publish) {
            self::snapshotVersion($boardCode, self::loadBoardDefinition($boardCode), [
                'source' => 'rollback',
                'rollbackFrom' => $versionId,
                'publishedAt' => date(DateTimeInterface::ATOM),
            ]);
            self::writeJsonFile(self::boardPath($boardCode), $board, 'MILEAGE_BOARD_SAVE_FAILED');
        }

        self::writeJsonFile(self::draftPath($boardCode), $board, 'MILEAGE_DRAFT_SAVE_FAILED');
        self::writeDraftMeta($boardCode, [
            'boardCode' => $boardCode,
            'savedAt' => date(DateTimeInterface::ATOM),
            'rollbackFrom' => $versionId,
            'publishedAt' => $publish ? date(DateTimeInterface::ATOM) : null,
            'hasDataUrl' => false,
        ]);

        return [
            'board' => $board,
            'published' => $publish,
            'versions' => self::versionManifest($boardCode),
        ];
    }

    public static function versionManifest(string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        $path = self::versionManifestPath($boardCode);
        if (!is_file($path)) {
            return [
                'boardCode' => self::normalizeBoardCode($boardCode),
                'versions' => [],
            ];
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [
                'boardCode' => self::normalizeBoardCode($boardCode),
                'versions' => [],
            ];
        }
        $decoded['versions'] = array_values(array_filter(
            is_array($decoded['versions'] ?? null) ? $decoded['versions'] : [],
            static fn (mixed $item): bool => is_array($item) && trim((string) ($item['id'] ?? '')) !== ''
        ));
        return $decoded + ['boardCode' => self::normalizeBoardCode($boardCode)];
    }

    public static function previewRewardDefinition(array $reward, string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        $board = self::loadBoardDefinition($boardCode);
        $rewardTemplateId = trim((string) ($reward['rewardTemplateId'] ?? ($reward['meta']['rewardTemplateId'] ?? '')));
        $resolvedBundle = null;
        if ($rewardTemplateId !== '' && class_exists('RewardTemplateService')) {
            $resolvedBundle = RewardTemplateService::resolveTemplate(
                $rewardTemplateId,
                isset($reward['stepIndex']) && $reward['stepIndex'] !== null ? (int) $reward['stepIndex'] : null
            );
        }

        return [
            'ok' => true,
            'boardCode' => (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE),
            'reward' => $reward,
            'rewardTemplateId' => $rewardTemplateId,
            'resolved' => is_array($resolvedBundle) ? $resolvedBundle : null,
            'simulationOnly' => true,
        ];
    }

    public static function summary(string $guildId, string $userId = '', string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        $board = self::loadBoardDefinition($boardCode);
        $mappedStepCount = count($board['steps']);
        $maxStepIndex = self::maxStepIndex($board);

        if ($guildId === '' || $userId === '') {
            return [
                'boardCode' => $board['boardCode'],
                'lifetimeSteps' => 0,
                'positionStep' => -1,
                'lastAnimatedStep' => -1,
                'pendingSteps' => 0,
                'pendingWalkCount' => 0,
                'badgeCount' => 0,
                'finished' => false,
                'claimableRewardCount' => 0,
                'claimedRewardIds' => [],
                'mappedStepCount' => $mappedStepCount,
                'unmappedOverflowSteps' => 0,
                'requiresLogin' => $userId === '',
                'maxReachableStep' => $maxStepIndex,
            ];
        }

        $row = self::reconciledProgressRow($guildId, $userId, $board);
        $positionStep = (int) ($row['positionStep'] ?? -1);
        $lastAnimatedStep = (int) ($row['lastAnimatedStep'] ?? -1);
        $pendingSteps = max(0, $positionStep - $lastAnimatedStep);
        $unmappedOverflowSteps = max(0, ((int) ($row['lifetimeSteps'] ?? 0)) - $mappedStepCount);
        $badgeCount = $pendingSteps + $unmappedOverflowSteps;

        return [
            'boardCode' => $board['boardCode'],
            'lifetimeSteps' => (int) ($row['lifetimeSteps'] ?? 0),
            'positionStep' => $positionStep,
            'lastAnimatedStep' => $lastAnimatedStep,
            'pendingSteps' => $pendingSteps,
            'pendingWalkCount' => $pendingSteps,
            'badgeCount' => $badgeCount,
            'finished' => !empty($row['isFinished']),
            'claimableRewardCount' => count(self::claimableRewardsInRange(
                $guildId,
                $userId,
                $board,
                0,
                $positionStep
            )),
            'claimedRewardIds' => self::claimedRewardIds($guildId, $userId, (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE)),
            'mappedStepCount' => $mappedStepCount,
            'unmappedOverflowSteps' => $unmappedOverflowSteps,
            'requiresLogin' => false,
            'maxReachableStep' => $maxStepIndex,
        ];
    }

    public static function bootstrap(string $guildId, string $userId = '', string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        $board = self::loadBoardDefinition($boardCode);
        $summary = self::summary($guildId, $userId, $boardCode);
        $progress = [
            'lifetimeSteps' => (int) ($summary['lifetimeSteps'] ?? 0),
            'positionStep' => (int) ($summary['positionStep'] ?? -1),
            'lastAnimatedStep' => (int) ($summary['lastAnimatedStep'] ?? -1),
            'finished' => !empty($summary['finished']),
        ];

        $startStep = (int) ($summary['lastAnimatedStep'] ?? -1) + 1;
        $endStep = (int) ($summary['positionStep'] ?? -1);

        return [
            'ok' => true,
            'requiresLogin' => $userId === '',
            'board' => $board,
            'summary' => $summary,
            'progress' => $progress,
            'pending' => [
                'startStepIndex' => $endStep >= $startStep ? $startStep : null,
                'endStepIndex' => $endStep >= $startStep ? $endStep : null,
                'previewRewards' => $userId !== '' && $endStep >= $startStep
                    ? self::claimableRewardsInRange($guildId, $userId, $board, $startStep, $endStep)
                    : [],
            ],
            'players' => $guildId !== '' ? self::visiblePlayers($guildId, $board) : [],
            'self' => $userId !== '' ? self::playerPayloadByUserId($guildId, $userId, $board) : null,
            'leaderboard' => $guildId !== '' ? self::leaderboard($guildId, $boardCode, 300, $userId) : ['all' => [], 'weekly' => []],
        ];
    }

    public static function leaderboard(string $guildId, string $boardCode = self::DEFAULT_BOARD_CODE, int $limit = 300, string $focusUserId = ''): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $focusUserId = trim($focusUserId);
        $board = self::loadBoardDefinition($boardCode);
        $boardCode = (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE);
        $limit = max(1, min(500, $limit));
        if ($guildId === '') {
            return ['all' => [], 'weekly' => []];
        }

        $allRows = Database::fetchAll(
            'SELECT p.userId,
                    p.lifetimeSteps,
                    p.positionStep,
                    p.lastAnimatedStep,
                    COALESCE(p.updateDate, p.createDate) AS rankDate,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    m.nickName,
                    m.guildAvatarHash
               FROM tbl_gacha_mileage_progress p
          LEFT JOIN tbl_user u
                 ON u.userId = p.userId
          LEFT JOIN tbl_member m
                 ON m.guildId = p.guildId
                AND m.userId = p.userId
              WHERE p.guildId = :guildId
                AND p.boardCode = :boardCode
                AND p.lifetimeSteps > 0
              ORDER BY p.lifetimeSteps DESC, rankDate ASC, p.gachaMileageProgressId ASC
              LIMIT ' . $limit,
            [
                'guildId' => $guildId,
                'boardCode' => $boardCode,
            ]
        );

        $weekStart = (new DateTimeImmutable('monday this week'))->setTime(0, 0, 0);
        $weeklyRows = Database::fetchAll(
            'SELECT l.userId,
                    SUM(l.stepDelta) AS weeklySteps,
                    MAX(l.createDate) AS rankDate,
                    COALESCE(p.lifetimeSteps, 0) AS lifetimeSteps,
                    COALESCE(p.positionStep, -1) AS positionStep,
                    COALESCE(p.lastAnimatedStep, -1) AS lastAnimatedStep,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    m.nickName,
                    m.guildAvatarHash
               FROM tbl_gacha_mileage_spin_ledger l
          LEFT JOIN tbl_gacha_mileage_progress p
                 ON p.guildId = l.guildId
                AND p.userId = l.userId
                AND p.boardCode = l.boardCode
          LEFT JOIN tbl_user u
                 ON u.userId = l.userId
          LEFT JOIN tbl_member m
                 ON m.guildId = l.guildId
                AND m.userId = l.userId
              WHERE l.guildId = :guildId
                AND l.boardCode = :boardCode
                AND l.createDate >= :weekStart
              GROUP BY l.userId,
                       p.lifetimeSteps,
                       p.positionStep,
                       p.lastAnimatedStep,
                       u.userName,
                       u.globalName,
                       u.avatarHash,
                       m.nickName,
                       m.guildAvatarHash
             HAVING weeklySteps > 0
              ORDER BY weeklySteps DESC, rankDate ASC
              LIMIT ' . $limit,
            [
                'guildId' => $guildId,
                'boardCode' => $boardCode,
                'weekStart' => $weekStart->format('Y-m-d H:i:s'),
            ]
        );

        if ($focusUserId !== '') {
            $focusAllRow = self::leaderboardFocusAllRow($guildId, $boardCode, $focusUserId);
            if ($focusAllRow && !self::rowsContainUser($allRows, $focusUserId)) {
                $allRows[] = $focusAllRow;
            }
            $focusWeeklyRow = self::leaderboardFocusWeeklyRow($guildId, $boardCode, $focusUserId, $weekStart);
            if ($focusWeeklyRow && !self::rowsContainUser($weeklyRows, $focusUserId)) {
                $weeklyRows[] = $focusWeeklyRow;
            }
        }

        return [
            'all' => self::leaderboardRowsPayload($guildId, $board, $allRows, 'lifetimeSteps'),
            'weekly' => self::leaderboardRowsPayload($guildId, $board, $weeklyRows, 'weeklySteps'),
            'weekStart' => $weekStart->format(DateTimeInterface::ATOM),
        ];
    }

    public static function stepPlayers(string $guildId, string $boardCode = self::DEFAULT_BOARD_CODE, int $stepIndex = -1, int $limit = 200): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $board = self::loadBoardDefinition($boardCode);
        $boardCode = (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE);
        $stepIndex = max(-1, min(self::maxStepIndex($board), $stepIndex));
        $limit = max(1, min(500, $limit));

        if ($guildId === '' || $stepIndex < 0) {
            return [];
        }

        $targetLifetime = $stepIndex >= self::maxStepIndex($board)
            ? self::maxStepIndex($board) + 1
            : $stepIndex + 1;
        $targetComparator = $stepIndex >= self::maxStepIndex($board) ? '>=' : '=';

        $rows = Database::fetchAll(
            'SELECT p.userId,
                    p.lifetimeSteps,
                    p.positionStep,
                    p.lastAnimatedStep,
                    COALESCE(p.updateDate, p.createDate) AS activityDate,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    m.nickName,
                    m.guildAvatarHash
               FROM tbl_gacha_mileage_progress p
          LEFT JOIN tbl_user u
                 ON u.userId = p.userId
          LEFT JOIN tbl_member m
                 ON m.guildId = p.guildId
                AND m.userId = p.userId
              WHERE p.guildId = :guildId
                AND p.boardCode = :boardCode
                AND p.lifetimeSteps ' . $targetComparator . ' :targetLifetime
              ORDER BY activityDate DESC, p.gachaMileageProgressId DESC
              LIMIT ' . $limit,
            [
                'guildId' => $guildId,
                'boardCode' => $boardCode,
                'targetLifetime' => $targetLifetime,
            ]
        );

        $players = [];
        foreach ($rows as $row) {
            $payload = self::playerPayloadFromProgressRow($guildId, $board, $row);
            if ((int) ($payload['positionStep'] ?? -1) !== $stepIndex) {
                continue;
            }
            $players[] = $payload;
        }

        return $players;
    }

    public static function recordCompletedSpin(
        string $guildId,
        string $userId,
        string $drawId,
        int $stepDelta = 1,
        string $boardCode = self::DEFAULT_BOARD_CODE
    ): array {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $drawId = trim($drawId);
        $stepDelta = max(0, $stepDelta);

        if ($guildId === '' || $userId === '' || $drawId === '' || $stepDelta <= 0) {
            return self::summary($guildId, $userId, $boardCode);
        }

        $board = self::loadBoardDefinition($boardCode);
        self::ensureProgressRow($guildId, $userId, $board['boardCode']);

        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $existing = Database::fetch(
                'SELECT gachaMileageSpinLedgerId
                   FROM tbl_gacha_mileage_spin_ledger
                  WHERE guildId = :guildId
                    AND boardCode = :boardCode
                    AND drawId = :drawId
                  LIMIT 1
                  FOR UPDATE',
                [
                    'guildId' => $guildId,
                    'boardCode' => $board['boardCode'],
                    'drawId' => $drawId,
                ]
            );

            if ($existing) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return self::summary($guildId, $userId, $board['boardCode']);
            }

            $row = Database::fetch(
                'SELECT *
                   FROM tbl_gacha_mileage_progress
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND boardCode = :boardCode
                  LIMIT 1
                  FOR UPDATE',
                [
                    'guildId' => $guildId,
                    'userId' => $userId,
                    'boardCode' => $board['boardCode'],
                ]
            ) ?: [];

            $beforeLifetime = max(0, (int) ($row['lifetimeSteps'] ?? 0));
            $beforePosition = self::positionFromLifetime($beforeLifetime, $board);
            $afterLifetime = $beforeLifetime + $stepDelta;
            $afterPosition = self::positionFromLifetime($afterLifetime, $board);
            $afterFinished = self::isFinishedPosition($afterPosition, $board);
            $lastAnimatedStep = min(
                max(-1, (int) ($row['lastAnimatedStep'] ?? -1)),
                $afterPosition
            );

            Database::execute(
                'UPDATE tbl_gacha_mileage_progress
                    SET lifetimeSteps = :lifetimeSteps,
                        positionStep = :positionStep,
                        lastAnimatedStep = :lastAnimatedStep,
                        isFinished = :isFinished,
                        updateDate = :updateDate
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND boardCode = :boardCode',
                [
                    'lifetimeSteps' => $afterLifetime,
                    'positionStep' => $afterPosition,
                    'lastAnimatedStep' => $lastAnimatedStep,
                    'isFinished' => $afterFinished ? 1 : 0,
                    'updateDate' => date('Y-m-d H:i:s'),
                    'guildId' => $guildId,
                    'userId' => $userId,
                    'boardCode' => $board['boardCode'],
                ]
            );

            Database::insert('tbl_gacha_mileage_spin_ledger', [
                'guildId' => $guildId,
                'userId' => $userId,
                'boardCode' => $board['boardCode'],
                'drawId' => $drawId,
                'stepDelta' => $stepDelta,
                'lifetimeBefore' => $beforeLifetime,
                'lifetimeAfter' => $afterLifetime,
                'positionBefore' => $beforePosition,
                'positionAfter' => $afterPosition,
                'isFinishedAfter' => $afterFinished ? 1 : 0,
                'createDate' => date('Y-m-d H:i:s'),
            ]);

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }

        return self::summary($guildId, $userId, $board['boardCode']);
    }

    public static function claimPending(string $guildId, string $userId, string $boardCode = self::DEFAULT_BOARD_CODE): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $board = self::loadBoardDefinition($boardCode);
        $summary = self::summary($guildId, $userId, $board['boardCode']);
        $startStep = 0;
        $endStep = (int) ($summary['positionStep'] ?? -1);

        if ($guildId === '' || $userId === '' || $endStep < $startStep) {
            return [
                'ok' => true,
                'summary' => $summary,
                'claimedRewards' => [],
            ];
        }

        $claimable = self::claimableRewardsInRange($guildId, $userId, $board, $startStep, $endStep);
        $claimed = [];
        foreach ($claimable as $reward) {
            $claimed[] = self::grantReward($guildId, $userId, $board, $reward);
        }

        Database::execute(
            'UPDATE tbl_gacha_mileage_progress
                SET lastAnimatedStep = :lastAnimatedStep,
                    updateDate = :updateDate
              WHERE guildId = :guildId
                AND userId = :userId
                AND boardCode = :boardCode',
            [
                'lastAnimatedStep' => $endStep,
                'updateDate' => date('Y-m-d H:i:s'),
                'guildId' => $guildId,
                'userId' => $userId,
                'boardCode' => $board['boardCode'],
            ]
        );

        return [
            'ok' => true,
            'summary' => self::summary($guildId, $userId, $board['boardCode']),
            'claimedRewards' => $claimed,
        ];
    }

    private static function loadBoardDefinition(string $boardCode): array
    {
        $boardCode = self::normalizeBoardCode($boardCode);
        $path = self::boardPath($boardCode);
        if (!is_file($path)) {
            return self::normalizeBoard([
                'boardCode' => $boardCode,
                'version' => 1,
                'entry' => self::defaultEntryPoint(),
                'image' => [
                    'width' => 1,
                    'height' => 1,
                    'source' => '',
                    'segments' => [],
                ],
                'steps' => [],
                'rewards' => [],
                'iconTemplates' => [],
                'rewardTemplates' => [],
                'rewardNodes' => [],
            ]);
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return self::normalizeBoard(is_array($decoded) ? $decoded : ['boardCode' => $boardCode]);
    }

    private static function normalizeBoard(array $board): array
    {
        $boardCode = self::normalizeBoardCode($board['boardCode'] ?? self::DEFAULT_BOARD_CODE);
        $image = is_array($board['image'] ?? null) ? $board['image'] : [];
        $imageWidth = max(1, (int) ($image['width'] ?? 1));
        $sourceHeight = max(1, (int) ($image['height'] ?? 1));
        $segments = self::normalizeSegments($image['segments'] ?? [], $image);
        $imageHeight = self::segmentsHeight($segments);
        $steps = self::normalizeSteps($board['steps'] ?? [], $segments, $imageWidth, $sourceHeight);
        $rewards = self::normalizeRewards($board['rewards'] ?? [], $steps, $segments, $imageWidth, $sourceHeight);
        $sprites = self::normalizeSprites($board['sprites'] ?? [], $segments, $imageWidth, $sourceHeight);
        $iconTemplates = self::normalizeIconTemplates($board['iconTemplates'] ?? []);
        $rewardTemplates = self::normalizeRewardTemplates($board['rewardTemplates'] ?? []);
        $rewardNodes = self::normalizeRewardNodes($board['rewardNodes'] ?? [], $steps, $iconTemplates, $rewardTemplates, $segments, $imageWidth, $sourceHeight);
        $entry = self::normalizeEntryPoint($board['entry'] ?? null, $steps, $segments, $imageWidth, $sourceHeight);

        return [
            'boardCode' => $boardCode,
            'version' => max(2, (int) ($board['version'] ?? 2)),
            'title' => trim((string) ($board['title'] ?? 'Mileage Board')) ?: 'Mileage Board',
            'entry' => $entry,
            'image' => [
                'width' => $imageWidth,
                'height' => $imageHeight,
                'source' => trim((string) ($image['source'] ?? '')),
                'segments' => $segments,
            ],
            'steps' => $steps,
            'rewards' => $rewards,
            'sprites' => $sprites,
            'iconTemplates' => $iconTemplates,
            'rewardTemplates' => $rewardTemplates,
            'rewardNodes' => $rewardNodes,
            'meta' => self::normalizeBoardMeta($board['meta'] ?? []),
        ];
    }

    private static function normalizeBoardMeta(mixed $meta): array
    {
        $out = is_array($meta) ? $meta : [];
        $ui = is_array($out['ui'] ?? null) ? $out['ui'] : [];
        $rewardMarker = is_array($ui['rewardMarker'] ?? null) ? $ui['rewardMarker'] : [];
        $currencyPickup = is_array($ui['currencyPickup'] ?? null) ? $ui['currencyPickup'] : [];

        $rewardSize = (int) round((float) ($rewardMarker['size'] ?? 44));
        $pickupScale = (float) ($currencyPickup['scale'] ?? 1.3);
        $pickupCountMultiplier = (float) ($currencyPickup['countMultiplier'] ?? 1.45);

        $out['ui'] = $ui;
        $out['ui']['rewardMarker'] = $rewardMarker;
        $out['ui']['rewardMarker']['size'] = max(26, min(96, $rewardSize));
        $out['ui']['currencyPickup'] = $currencyPickup;
        $out['ui']['currencyPickup']['scale'] = max(0.7, min(2.4, $pickupScale));
        $out['ui']['currencyPickup']['countMultiplier'] = max(0.7, min(3.2, $pickupCountMultiplier));
        $fx = is_array($out['fx'] ?? null) ? $out['fx'] : [];
        $out['fx'] = [
            'pathGlow' => max(0, min(2, (float) ($fx['pathGlow'] ?? 1))),
            'pathLine' => max(0, min(2, (float) ($fx['pathLine'] ?? 1))),
            'clouds' => max(0, min(2, (float) ($fx['clouds'] ?? 1))),
            'ambience' => max(0, min(2, (float) ($fx['ambience'] ?? 1))),
            'friendCount' => max(0, min(12, (int) ($fx['friendCount'] ?? 3))),
            'selfPulse' => max(0, min(2, (float) ($fx['selfPulse'] ?? 1))),
        ];
        $editor = is_array($out['editor'] ?? null) ? $out['editor'] : [];
        $out['editor'] = $editor + [
            'layerPolicy' => 'path-first',
        ];

        return $out;
    }

    // MILEAGE_SEGMENT_MODEL_V2: segments are the authoritative scene source and define board stacking.
    private static function normalizeSegments(mixed $segments, array $image = []): array
    {
        $out = [];
        foreach (is_array($segments) ? $segments : [] as $segment) {
            if (!is_array($segment)) {
                continue;
            }
            $out[] = [
                'id' => self::normalizeSegmentId($segment['id'] ?? ('segment_' . str_pad((string) (count($out) + 1), 3, '0', STR_PAD_LEFT))),
                'src' => trim((string) ($segment['src'] ?? '')),
                'h' => max(1, (int) ($segment['h'] ?? 1)),
                'y' => 0,
            ];
        }

        if (!$out) {
            $legacySource = trim((string) ($image['source'] ?? ''));
            if ($legacySource !== '') {
                $out[] = [
                    'id' => 'segment_001',
                    'src' => $legacySource,
                    'h' => max(1, (int) ($image['height'] ?? 1)),
                    'y' => 0,
                ];
            }
        }

        return self::finalizeSegments($out);
    }

    private static function finalizeSegments(array $segments): array
    {
        $cursor = 0;
        $out = [];
        foreach ($segments as $index => $segment) {
            if (!is_array($segment)) {
                continue;
            }
            $height = max(1, (int) ($segment['h'] ?? 1));
            $id = self::normalizeSegmentId($segment['id'] ?? ('segment_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)));
            $out[] = [
                'id' => $id,
                'src' => trim((string) ($segment['src'] ?? '')),
                'h' => $height,
                'y' => $cursor,
            ];
            $cursor += $height;
        }
        return $out;
    }

    private static function normalizeSteps(mixed $steps, array $segments, int $boardWidth, int $sourceHeight): array
    {
        $out = [];
        foreach (is_array($steps) ? $steps : [] as $index => $step) {
            if (!is_array($step)) {
                continue;
            }
            $point = self::normalizeBoardPointReference($step, $segments, $boardWidth, $sourceHeight);
            $out[] = [
                'i' => count($out),
                'segmentId' => $point['segmentId'],
                'localX' => $point['localX'],
                'localY' => $point['localY'],
                'x' => $point['x'],
                'y' => $point['y'],
                'label' => trim((string) ($step['label'] ?? '')),
                'visible' => ($step['visible'] ?? true) !== false,
                'locked' => ($step['locked'] ?? false) === true,
                'layerSlot' => self::normalizeLayerSlot($step['layerSlot'] ?? 'path'),
                'zIndex' => (int) ($step['zIndex'] ?? $index),
                'meta' => is_array($step['meta'] ?? null) ? $step['meta'] : [],
            ];
            unset($index);
        }
        return $out;
    }

    private static function normalizeRewards(mixed $rewards, array $steps, array $segments, int $boardWidth, int $sourceHeight): array
    {
        $out = [];
        foreach (is_array($rewards) ? $rewards : [] as $index => $reward) {
            if (!is_array($reward)) {
                continue;
            }

            $id = trim((string) ($reward['id'] ?? ''));
            if ($id === '') {
                $id = 'reward_' . str_pad((string) (count($out) + 1), 3, '0', STR_PAD_LEFT);
            }

            $stepIndex = null;
            if ($reward['stepIndex'] ?? null) {
                $stepIndex = max(0, (int) $reward['stepIndex']);
            } elseif (($reward['stepIndex'] ?? null) === 0 || ($reward['stepIndex'] ?? null) === '0') {
                $stepIndex = 0;
            }
            if ($stepIndex !== null && !isset($steps[$stepIndex])) {
                $stepIndex = null;
            }

            $fallbackPoint = $stepIndex !== null && isset($steps[$stepIndex]) ? $steps[$stepIndex] : null;
            $point = self::normalizeBoardPointReference($reward, $segments, $boardWidth, $sourceHeight, $fallbackPoint);

            $kind = self::normalizeRewardKind((string) ($reward['kind'] ?? 'coin'));
            $amount = max(0, (int) ($reward['amount'] ?? ($kind === 'item' ? 1 : 0)));
            if ($kind !== 'item' && $amount <= 0) {
                $amount = 1;
            }

            $out[] = [
                'id' => $id,
                'stepIndex' => $stepIndex,
                'segmentId' => $point['segmentId'],
                'localX' => $point['localX'],
                'localY' => $point['localY'],
                'x' => $point['x'],
                'y' => $point['y'],
                'kind' => $kind,
                'amount' => $amount,
                'itemCode' => trim((string) ($reward['itemCode'] ?? '')),
                'rewardTemplateId' => trim((string) ($reward['rewardTemplateId'] ?? '')),
                'iconTemplateId' => trim((string) ($reward['iconTemplateId'] ?? '')),
                'label' => trim((string) ($reward['label'] ?? '')),
                'visible' => ($reward['visible'] ?? true) !== false,
                'locked' => ($reward['locked'] ?? false) === true,
                'layerSlot' => self::normalizeLayerSlot($reward['layerSlot'] ?? 'reward'),
                'zIndex' => (int) ($reward['zIndex'] ?? $index),
                'meta' => is_array($reward['meta'] ?? null) ? $reward['meta'] : [],
            ];
            unset($index);
        }
        return $out;
    }

    private static function normalizeRewardKind(string $kind): string
    {
        $normalized = strtolower(trim($kind));
        return in_array($normalized, ['coin', 'ticket', 'gem', 'potion', 'item'], true)
            ? $normalized
            : 'coin';
    }

    private static function normalizeSprites(mixed $sprites, array $segments, int $boardWidth, int $sourceHeight): array
    {
        $out = [];
        foreach (is_array($sprites) ? $sprites : [] as $index => $sprite) {
            if (!is_array($sprite)) {
                continue;
            }

            $src = trim((string) ($sprite['src'] ?? ''));
            if ($src === '') {
                continue;
            }

            $columns = max(1, (int) ($sprite['columns'] ?? 1));
            $rows = max(1, (int) ($sprite['rows'] ?? 1));
            $maxFrames = max(1, $columns * $rows);
            $mode = strtolower(trim((string) ($sprite['mode'] ?? 'loop')));
            if (!in_array($mode, ['once', 'loop', 'pingpong', 'static'], true)) {
                $mode = 'loop';
            }
            $enabledStates = [];
            foreach (is_array($sprite['enabledStates'] ?? null) ? $sprite['enabledStates'] : ['idle'] as $stateName) {
                $normalizedStateName = trim((string) $stateName);
                if ($normalizedStateName === 'notready') {
                    $normalizedStateName = 'notReady';
                }
                if (in_array($normalizedStateName, ['idle', 'touch', 'notReady', 'ready', 'claimed'], true) && !in_array($normalizedStateName, $enabledStates, true)) {
                    $enabledStates[] = $normalizedStateName;
                }
            }
            if (!in_array('idle', $enabledStates, true)) {
                array_unshift($enabledStates, 'idle');
            }

            $id = trim((string) ($sprite['id'] ?? ''));
            if ($id === '') {
                $id = 'sprite_' . str_pad((string) (count($out) + 1), 3, '0', STR_PAD_LEFT);
            }

            $rawStepIndex = $sprite['stepIndex'] ?? (is_array($sprite['meta'] ?? null) ? ($sprite['meta']['stepIndex'] ?? -1) : -1);
            $point = self::normalizeBoardPointReference($sprite, $segments, $boardWidth, $sourceHeight);
            $out[] = [
                'id' => $id,
                'label' => trim((string) ($sprite['label'] ?? '')),
                'src' => $src,
                'segmentId' => $point['segmentId'],
                'localX' => $point['localX'],
                'localY' => $point['localY'],
                'x' => $point['x'],
                'y' => $point['y'],
                'width' => max(1, (int) ($sprite['width'] ?? 48)),
                'height' => max(1, (int) ($sprite['height'] ?? 48)),
                'columns' => $columns,
                'rows' => $rows,
                'frameWidth' => max(0, (int) ($sprite['frameWidth'] ?? 0)),
                'frameHeight' => max(0, (int) ($sprite['frameHeight'] ?? 0)),
                'frameCount' => max(1, min($maxFrames, (int) ($sprite['frameCount'] ?? $maxFrames))),
                'fps' => max(1, min(60, (float) ($sprite['fps'] ?? 12))),
                'mode' => $mode,
                'autoplay' => ($sprite['autoplay'] ?? true) !== false,
                'visible' => ($sprite['visible'] ?? true) !== false,
                'locked' => ($sprite['locked'] ?? false) === true,
                'layerSlot' => self::normalizeLayerSlot($sprite['layerSlot'] ?? 'decor-back'),
                'zIndex' => (int) ($sprite['zIndex'] ?? $index),
                'stepIndex' => max(-1, (int) $rawStepIndex),
                'enabledStates' => $enabledStates,
                'states' => self::normalizeAnimationStates($sprite['states'] ?? []),
                'meta' => is_array($sprite['meta'] ?? null) ? $sprite['meta'] : [],
            ];
            unset($index);
        }
        return $out;
    }

    private static function normalizeIconTemplates(mixed $templates): array
    {
        $out = [];
        foreach (is_array($templates) ? $templates : [] as $template) {
            if (!is_array($template)) {
                continue;
            }

            $templateId = trim((string) ($template['id'] ?? ''));
            if ($templateId === '') {
                $templateId = 'icon_' . str_pad((string) (count($out) + 1), 3, '0', STR_PAD_LEFT);
            }
            $columns = max(1, min(512, (int) ($template['columns'] ?? 1)));
            $rows = max(1, min(512, (int) ($template['rows'] ?? 1)));
            $maxFrames = max(1, $columns * $rows);
            $mode = strtolower(trim((string) ($template['mode'] ?? 'loop')));
            if (!in_array($mode, ['loop', 'once', 'pingpong'], true)) {
                $mode = 'loop';
            }

            $out[] = [
                'id' => $templateId,
                'label' => trim((string) ($template['label'] ?? '')),
                'src' => trim((string) ($template['src'] ?? '')),
                'frameX' => max(0, (int) ($template['frameX'] ?? 0)),
                'frameY' => max(0, (int) ($template['frameY'] ?? 0)),
                'frameWidth' => max(0, (int) ($template['frameWidth'] ?? 0)),
                'frameHeight' => max(0, (int) ($template['frameHeight'] ?? 0)),
                'columns' => $columns,
                'rows' => $rows,
                'frameCount' => max(1, min($maxFrames, (int) ($template['frameCount'] ?? 1))),
                'fps' => max(1, min(60, (float) ($template['fps'] ?? 12))),
                'mode' => $mode,
                'scale' => max(0.1, min(4, (float) ($template['scale'] ?? 1))),
                'anchorX' => max(0, min(1, (float) ($template['anchorX'] ?? 0.5))),
                'anchorY' => max(0, min(1, (float) ($template['anchorY'] ?? 0.5))),
                'offsetX' => (float) ($template['offsetX'] ?? 0),
                'offsetY' => (float) ($template['offsetY'] ?? 0),
                'states' => self::normalizeAnimationStates($template['states'] ?? []),
                'meta' => is_array($template['meta'] ?? null) ? $template['meta'] : [],
            ];
        }
        return $out;
    }

    private static function normalizeRewardTemplates(mixed $templates): array
    {
        $out = [];
        foreach (is_array($templates) ? $templates : [] as $template) {
            if (!is_array($template)) {
                continue;
            }

            $templateId = trim((string) ($template['id'] ?? ''));
            if ($templateId === '') {
                $templateId = 'reward_template_' . str_pad((string) (count($out) + 1), 3, '0', STR_PAD_LEFT);
            }

            $out[] = [
                'id' => $templateId,
                'label' => trim((string) ($template['label'] ?? '')),
                'rewardTemplateId' => trim((string) ($template['rewardTemplateId'] ?? '')),
                'mode' => strtolower(trim((string) ($template['mode'] ?? 'fixed'))) === 'random' ? 'random' : 'fixed',
                'meta' => is_array($template['meta'] ?? null) ? $template['meta'] : [],
            ];
        }
        return $out;
    }

    private static function normalizeRewardNodes(mixed $nodes, array $steps, array $iconTemplates, array $rewardTemplates, array $segments, int $boardWidth, int $sourceHeight): array
    {
        $iconTemplateIds = array_fill_keys(array_map(static fn (array $template): string => (string) ($template['id'] ?? ''), $iconTemplates), true);
        $rewardTemplateIds = array_fill_keys(array_map(static fn (array $template): string => (string) ($template['id'] ?? ''), $rewardTemplates), true);
        $out = [];

        foreach (is_array($nodes) ? $nodes : [] as $node) {
            if (!is_array($node)) {
                continue;
            }

            $nodeId = trim((string) ($node['id'] ?? ''));
            if ($nodeId === '') {
                $nodeId = 'reward_node_' . str_pad((string) (count($out) + 1), 3, '0', STR_PAD_LEFT);
            }

            $stepIndex = null;
            if ($node['stepIndex'] ?? null || ($node['stepIndex'] ?? null) === 0 || ($node['stepIndex'] ?? null) === '0') {
                $stepIndex = max(0, (int) $node['stepIndex']);
                if (!isset($steps[$stepIndex])) {
                    $stepIndex = null;
                }
            }

            $fallbackPoint = $stepIndex !== null && isset($steps[$stepIndex]) ? $steps[$stepIndex] : null;
            $point = self::normalizeBoardPointReference($node, $segments, $boardWidth, $sourceHeight, $fallbackPoint);

            $iconTemplateId = trim((string) ($node['iconTemplateId'] ?? ''));
            if ($iconTemplateId !== '' && !isset($iconTemplateIds[$iconTemplateId])) {
                $iconTemplateId = '';
            }

            $rewardTemplateId = trim((string) ($node['rewardTemplateId'] ?? ''));
            if ($rewardTemplateId !== '' && !isset($rewardTemplateIds[$rewardTemplateId])) {
                $rewardTemplateId = '';
            }

            $out[] = [
                'id' => $nodeId,
                'stepIndex' => $stepIndex,
                'segmentId' => $point['segmentId'],
                'localX' => $point['localX'],
                'localY' => $point['localY'],
                'x' => $point['x'],
                'y' => $point['y'],
                'iconTemplateId' => $iconTemplateId,
                'rewardTemplateId' => $rewardTemplateId,
                'label' => trim((string) ($node['label'] ?? '')),
                'visible' => ($node['visible'] ?? true) !== false,
                'locked' => ($node['locked'] ?? false) === true,
                'layerSlot' => self::normalizeLayerSlot($node['layerSlot'] ?? 'reward'),
                'zIndex' => (int) ($node['zIndex'] ?? count($out)),
                'meta' => is_array($node['meta'] ?? null) ? $node['meta'] : [],
            ];
        }

        return $out;
    }

    private static function normalizeLayerSlot(mixed $value): string
    {
        $slot = strtolower(trim((string) $value));
        return in_array($slot, ['background', 'decor-back', 'path', 'reward', 'decor-front', 'fx-front'], true)
            ? $slot
            : 'decor-back';
    }

    private static function normalizeAnimationStates(mixed $states): array
    {
        $out = [];
        foreach (is_array($states) ? $states : [] as $key => $state) {
            $name = strtolower(trim((string) $key));
            if (!in_array($name, ['idle', 'touch', 'notReady', 'notready', 'ready', 'claimed'], true) || !is_array($state)) {
                continue;
            }
            if ($name === 'notready') {
                $name = 'notReady';
            }
            $columns = max(1, min(512, (int) ($state['columns'] ?? 1)));
            $rows = max(1, min(512, (int) ($state['rows'] ?? 1)));
            $maxFrames = max(1, $columns * $rows);
            $mode = strtolower(trim((string) ($state['mode'] ?? 'loop')));
            if (!in_array($mode, ['loop', 'once', 'pingpong', 'static'], true)) {
                $mode = 'loop';
            }
            $out[$name] = [
                'label' => trim((string) ($state['label'] ?? '')),
                'frameX' => max(0, (int) ($state['frameX'] ?? 0)),
                'frameY' => max(0, (int) ($state['frameY'] ?? 0)),
                'frameWidth' => max(0, (int) ($state['frameWidth'] ?? 0)),
                'frameHeight' => max(0, (int) ($state['frameHeight'] ?? 0)),
                'columns' => $columns,
                'rows' => $rows,
                'frameCount' => max(1, min($maxFrames, (int) ($state['frameCount'] ?? 1))),
                'frameIndex' => max(0, min($maxFrames - 1, (int) ($state['frameIndex'] ?? 0))),
                'fps' => max(1, min(60, (float) ($state['fps'] ?? 12))),
                'mode' => $mode,
                'width' => max(0, (int) ($state['width'] ?? 0)),
                'height' => max(0, (int) ($state['height'] ?? 0)),
                'opacity' => max(0, min(1, (float) ($state['opacity'] ?? 1))),
            ];
        }
        return $out;
    }

    private static function normalizeBoardCode(mixed $value): string
    {
        $code = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim((string) ($value ?? '')))) ?? '';
        $code = trim($code, '-_');
        return $code !== '' ? $code : self::DEFAULT_BOARD_CODE;
    }

    private static function boardPath(string $boardCode): string
    {
        return Bootstrap::rootPath('gacha/data/mileage/board-' . self::normalizeBoardCode($boardCode) . '.v1.json');
    }

    private static function draftPath(string $boardCode): string
    {
        return Bootstrap::rootPath('gacha/data/mileage/drafts/board-' . self::normalizeBoardCode($boardCode) . '.draft.json');
    }

    private static function draftMetaPath(string $boardCode): string
    {
        return Bootstrap::rootPath('gacha/data/mileage/drafts/board-' . self::normalizeBoardCode($boardCode) . '.draft.meta.json');
    }

    private static function versionDir(string $boardCode): string
    {
        return Bootstrap::rootPath('gacha/data/mileage/versions/' . self::normalizeBoardCode($boardCode));
    }

    private static function versionManifestPath(string $boardCode): string
    {
        return self::versionDir($boardCode) . DIRECTORY_SEPARATOR . 'manifest.json';
    }

    private static function versionPath(string $boardCode, string $versionId): string
    {
        return self::versionDir($boardCode) . DIRECTORY_SEPARATOR . self::normalizeVersionId($versionId) . '.json';
    }

    private static function normalizeVersionId(string $versionId): string
    {
        $normalized = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($versionId))) ?? '';
        $normalized = trim($normalized, '-_');
        if ($normalized === '') {
            throw new RuntimeException('MILEAGE_VERSION_ID_REQUIRED');
        }
        return $normalized;
    }

    private static function writeJsonFile(string $path, array $payload, string $errorCode): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('MILEAGE_DIR_CREATE_FAILED');
        }

        $bytes = @file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            LOCK_EX
        );
        if ($bytes === false) {
            throw new RuntimeException($errorCode);
        }

        @chmod($dir, 0777);
        @chmod($path, 0666);
    }

    private static function loadDraftDefinition(string $boardCode): ?array
    {
        $path = self::draftPath($boardCode);
        if (!is_file($path)) {
            return null;
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return null;
        }
        return self::normalizeBoard($decoded + ['boardCode' => self::normalizeBoardCode($boardCode)]);
    }

    private static function writeDraftMeta(string $boardCode, array $meta): void
    {
        self::writeJsonFile(
            self::draftMetaPath($boardCode),
            array_filter($meta, static fn (mixed $value): bool => $value !== null),
            'MILEAGE_DRAFT_META_SAVE_FAILED'
        );
    }

    private static function snapshotVersion(string $boardCode, array $board, array $meta = []): array
    {
        $boardCode = self::normalizeBoardCode($boardCode);
        $hash = substr(hash('sha256', json_encode($board, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: random_bytes(8)), 0, 6);
        $id = strtolower(date('Ymd-His') . '-' . $hash);
        $path = self::versionPath($boardCode, $id);
        self::writeJsonFile($path, $board, 'MILEAGE_VERSION_SAVE_FAILED');

        $entry = [
            'id' => $id,
            'boardCode' => $boardCode,
            'createdAt' => date(DateTimeInterface::ATOM),
            'title' => trim((string) ($board['title'] ?? 'Mileage Board')) ?: 'Mileage Board',
            'stepCount' => count($board['steps'] ?? []),
            'rewardCount' => count($board['rewards'] ?? []),
            'rewardNodeCount' => count($board['rewardNodes'] ?? []),
            'spriteCount' => count($board['sprites'] ?? []),
            'hash' => $hash,
            'meta' => $meta,
        ];

        $manifest = self::versionManifest($boardCode);
        $versions = is_array($manifest['versions'] ?? null) ? $manifest['versions'] : [];
        array_unshift($versions, $entry);
        $seen = [];
        $versions = array_values(array_filter($versions, static function (array $item) use (&$seen): bool {
            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '' || isset($seen[$id])) {
                return false;
            }
            $seen[$id] = true;
            return true;
        }));
        $manifest = [
            'boardCode' => $boardCode,
            'updatedAt' => date(DateTimeInterface::ATOM),
            'versions' => $versions,
        ];
        self::writeJsonFile(self::versionManifestPath($boardCode), $manifest, 'MILEAGE_VERSION_MANIFEST_SAVE_FAILED');

        return $entry;
    }

    private static function containsDataUrl(mixed $value): bool
    {
        if (is_string($value)) {
            return str_starts_with(strtolower(trim($value)), 'data:');
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::containsDataUrl($item)) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function normalizeUnitFloat(mixed $value): float
    {
        return max(0, min(1, (float) $value));
    }

    private static function nullableUnitFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return self::normalizeUnitFloat($value);
    }

    private static function defaultEntryPoint(): array
    {
        return [
            'x' => 0.4123,
            'y' => 0.9721,
        ];
    }

    private static function normalizeEntryPoint(mixed $entry, array $steps, array $segments, int $boardWidth, int $sourceHeight): array
    {
        $fallback = $steps[0] ?? self::defaultEntryPoint();
        $source = is_array($entry) ? $entry : [];
        return self::normalizeBoardPointReference($source, $segments, $boardWidth, $sourceHeight, $fallback);
    }

    private static function normalizeSegmentId(mixed $value): string
    {
        $id = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim((string) ($value ?? '')))) ?? '';
        $id = trim($id, '-_');
        return $id !== '' ? $id : 'segment_001';
    }

    private static function segmentsHeight(array $segments): int
    {
        $height = 0;
        foreach ($segments as $segment) {
            $height += max(1, (int) ($segment['h'] ?? 1));
        }
        return max(1, $height);
    }

    private static function segmentMap(array $segments): array
    {
        $out = [];
        foreach ($segments as $segment) {
            if (!is_array($segment)) {
                continue;
            }
            $segmentId = self::normalizeSegmentId($segment['id'] ?? '');
            $out[$segmentId] = $segment + ['id' => $segmentId];
        }
        return $out;
    }

    private static function fallbackSegment(array $segments): array
    {
        return $segments[0] ?? [
            'id' => 'segment_001',
            'src' => '',
            'h' => 1,
            'y' => 0,
        ];
    }

    private static function segmentForBoardY(array $segments, float $boardY): array
    {
        $last = self::fallbackSegment($segments);
        foreach ($segments as $segment) {
            if (!is_array($segment)) {
                continue;
            }
            $last = $segment;
            $segmentY = (float) ($segment['y'] ?? 0);
            $segmentHeight = max(1.0, (float) ($segment['h'] ?? 1));
            if ($boardY >= $segmentY && $boardY <= ($segmentY + $segmentHeight)) {
                return $segment;
            }
        }
        return $last;
    }

    private static function buildBoardPointPayload(array $segment, float $localX, float $localY, int $boardWidth, array $segments): array
    {
        $normalizedX = self::normalizeUnitFloat($localX);
        $normalizedY = self::normalizeUnitFloat($localY);
        $segmentHeight = max(1.0, (float) ($segment['h'] ?? 1));
        $segmentY = max(0.0, (float) ($segment['y'] ?? 0));
        $boardHeight = self::segmentsHeight($segments);
        $boardX = $normalizedX * max(1, $boardWidth);
        $boardY = $segmentY + ($normalizedY * $segmentHeight);

        return [
            'segmentId' => self::normalizeSegmentId($segment['id'] ?? ''),
            'localX' => $normalizedX,
            'localY' => $normalizedY,
            'x' => self::normalizeUnitFloat($boardX / max(1, $boardWidth)),
            'y' => self::normalizeUnitFloat($boardY / $boardHeight),
        ];
    }

    // Legacy global points still load, but are canonicalized into segment-local coordinates here.
    private static function normalizeBoardPointReference(mixed $value, array $segments, int $boardWidth, int $sourceHeight, ?array $fallback = null): array
    {
        $source = is_array($value) ? $value : [];
        $segmentMap = self::segmentMap($segments);
        $segmentId = self::normalizeSegmentId($source['segmentId'] ?? '');
        if (isset($segmentMap[$segmentId]) && array_key_exists('localX', $source) && array_key_exists('localY', $source)) {
            return self::buildBoardPointPayload(
                $segmentMap[$segmentId],
                (float) $source['localX'],
                (float) $source['localY'],
                $boardWidth,
                $segments
            );
        }

        $x = self::nullableUnitFloat($source['x'] ?? null);
        $y = self::nullableUnitFloat($source['y'] ?? null);
        if ($x !== null && $y !== null) {
            $boardX = $x * max(1, $boardWidth);
            $boardY = $y * max(1, $sourceHeight);
            $segment = self::segmentForBoardY($segments, $boardY);
            $localY = ((float) $boardY - (float) ($segment['y'] ?? 0)) / max(1.0, (float) ($segment['h'] ?? 1));
            return self::buildBoardPointPayload($segment, $boardX / max(1, $boardWidth), $localY, $boardWidth, $segments);
        }

        if (is_array($fallback)) {
            return self::normalizeBoardPointReference($fallback, $segments, $boardWidth, $sourceHeight, null);
        }

        return self::buildBoardPointPayload(self::fallbackSegment($segments), 0, 0, $boardWidth, $segments);
    }

    private static function maxStepIndex(array $board): int
    {
        return count($board['steps'] ?? []) - 1;
    }

    private static function positionFromLifetime(int $lifetimeSteps, array $board): int
    {
        if ($lifetimeSteps <= 0) {
            return -1;
        }
        $maxStepIndex = self::maxStepIndex($board);
        if ($maxStepIndex < 0) {
            return -1;
        }
        return min($maxStepIndex, $lifetimeSteps - 1);
    }

    private static function isFinishedPosition(int $positionStep, array $board): bool
    {
        $maxStepIndex = self::maxStepIndex($board);
        return $maxStepIndex >= 0 && $positionStep >= $maxStepIndex;
    }

    private static function ensureProgressRow(string $guildId, string $userId, string $boardCode): void
    {
        self::ensureSchema();
        Database::execute(
            'INSERT INTO tbl_gacha_mileage_progress
                (guildId, userId, boardCode, lifetimeSteps, positionStep, lastAnimatedStep, isFinished, updateDate)
             VALUES
                (:guildId, :userId, :boardCode, 0, -1, -1, 0, :updateDate)
             ON DUPLICATE KEY UPDATE updateDate = updateDate',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'boardCode' => $boardCode,
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private static function reconciledProgressRow(string $guildId, string $userId, array $board): array
    {
        self::ensureProgressRow($guildId, $userId, $board['boardCode']);
        $row = Database::fetch(
            'SELECT *
               FROM tbl_gacha_mileage_progress
              WHERE guildId = :guildId
                AND userId = :userId
                AND boardCode = :boardCode
              LIMIT 1',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'boardCode' => $board['boardCode'],
            ]
        ) ?: [];

        $lifetimeSteps = max(0, (int) ($row['lifetimeSteps'] ?? 0));
        $positionStep = self::positionFromLifetime($lifetimeSteps, $board);
        $lastAnimatedStep = min(max(-1, (int) ($row['lastAnimatedStep'] ?? -1)), $positionStep);
        $isFinished = self::isFinishedPosition($positionStep, $board);

        if (
            (int) ($row['positionStep'] ?? -999) !== $positionStep
            || (int) ($row['lastAnimatedStep'] ?? -999) !== $lastAnimatedStep
            || (int) ($row['isFinished'] ?? -1) !== ($isFinished ? 1 : 0)
        ) {
            Database::execute(
                'UPDATE tbl_gacha_mileage_progress
                    SET positionStep = :positionStep,
                        lastAnimatedStep = :lastAnimatedStep,
                        isFinished = :isFinished,
                        updateDate = :updateDate
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND boardCode = :boardCode',
                [
                    'positionStep' => $positionStep,
                    'lastAnimatedStep' => $lastAnimatedStep,
                    'isFinished' => $isFinished ? 1 : 0,
                    'updateDate' => date('Y-m-d H:i:s'),
                    'guildId' => $guildId,
                    'userId' => $userId,
                    'boardCode' => $board['boardCode'],
                ]
            );
            $row['positionStep'] = $positionStep;
            $row['lastAnimatedStep'] = $lastAnimatedStep;
            $row['isFinished'] = $isFinished ? 1 : 0;
        }

        return $row + [
            'lifetimeSteps' => $lifetimeSteps,
            'positionStep' => $positionStep,
            'lastAnimatedStep' => $lastAnimatedStep,
            'isFinished' => $isFinished ? 1 : 0,
        ];
    }

    private static function claimableRewardsInRange(
        string $guildId,
        string $userId,
        array $board,
        int $startStep,
        int $endStep
    ): array {
        if ($endStep < $startStep) {
            return [];
        }

        $rewards = array_values(array_filter(
            self::boardRewardDefinitions($board),
            static fn (array $reward): bool => isset($reward['stepIndex'])
                && $reward['stepIndex'] !== null
                && (int) $reward['stepIndex'] >= $startStep
                && (int) $reward['stepIndex'] <= $endStep
        ));

        if (!$rewards) {
            return [];
        }

        $placeholders = [];
        $params = [
            'guildId' => $guildId,
            'userId' => $userId,
            'boardCode' => (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE),
        ];
        foreach ($rewards as $index => $reward) {
            $key = 'rewardId' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = (string) ($reward['id'] ?? '');
        }

        $claimedRows = Database::fetchAll(
            'SELECT rewardId
               FROM tbl_gacha_mileage_reward_claim
              WHERE guildId = :guildId
                AND userId = :userId
                AND boardCode = :boardCode
                AND rewardId IN (' . implode(', ', $placeholders) . ')',
            $params
        );
        $claimedMap = [];
        foreach ($claimedRows as $row) {
            $claimedMap[(string) ($row['rewardId'] ?? '')] = true;
        }

        return array_values(array_filter(
            $rewards,
            static fn (array $reward): bool => !isset($claimedMap[(string) ($reward['id'] ?? '')])
        ));
    }

    private static function claimedRewardIds(string $guildId, string $userId, string $boardCode): array
    {
        self::ensureSchema();

        if ($guildId === '' || $userId === '') {
            return [];
        }

        $rows = Database::fetchAll(
            'SELECT rewardId
               FROM tbl_gacha_mileage_reward_claim
              WHERE guildId = :guildId
                AND userId = :userId
                AND boardCode = :boardCode
              ORDER BY stepIndex ASC, rewardId ASC',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'boardCode' => $boardCode,
            ]
        );

        $ids = [];
        foreach ($rows as $row) {
            $rewardId = trim((string) ($row['rewardId'] ?? ''));
            if ($rewardId !== '') {
                $ids[] = $rewardId;
            }
        }

        return array_values(array_unique($ids));
    }

    private static function visiblePlayers(string $guildId, array $board): array
    {
        self::ensureSchema();
        $rows = Database::fetchAll(
            'SELECT p.userId,
                    p.lifetimeSteps,
                    p.positionStep,
                    p.lastAnimatedStep,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    m.nickName,
                    m.guildAvatarHash
               FROM tbl_gacha_mileage_progress p
          LEFT JOIN tbl_user u
                 ON u.userId = p.userId
          LEFT JOIN tbl_member m
                 ON m.guildId = p.guildId
                AND m.userId = p.userId
              WHERE p.guildId = :guildId
                AND p.boardCode = :boardCode
                AND p.lifetimeSteps > 0
              ORDER BY COALESCE(p.updateDate, p.createDate) DESC, p.gachaMileageProgressId DESC
              LIMIT 160',
            [
                'guildId' => $guildId,
                'boardCode' => (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE),
            ]
        );

        $players = [];
        foreach ($rows as $row) {
            $payload = self::playerPayloadFromProgressRow($guildId, $board, $row);
            $positionStep = (int) ($payload['positionStep'] ?? -1);
            if ($positionStep < 0) {
                continue;
            }
            $players[] = $payload;
        }

        return $players;
    }

    private static function rowsContainUser(array $rows, string $userId): bool
    {
        $targetUserId = trim($userId);
        foreach ($rows as $row) {
            if (trim((string) ($row['userId'] ?? '')) === $targetUserId) {
                return true;
            }
        }
        return false;
    }

    private static function leaderboardFocusAllRow(string $guildId, string $boardCode, string $userId): ?array
    {
        $row = Database::fetch(
            'SELECT p.gachaMileageProgressId,
                    p.userId,
                    p.lifetimeSteps,
                    p.positionStep,
                    p.lastAnimatedStep,
                    COALESCE(p.updateDate, p.createDate) AS rankDate,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    m.nickName,
                    m.guildAvatarHash
               FROM tbl_gacha_mileage_progress p
          LEFT JOIN tbl_user u
                 ON u.userId = p.userId
          LEFT JOIN tbl_member m
                 ON m.guildId = p.guildId
                AND m.userId = p.userId
              WHERE p.guildId = :guildId
                AND p.boardCode = :boardCode
                AND p.userId = :userId
                AND p.lifetimeSteps > 0
              LIMIT 1',
            [
                'guildId' => $guildId,
                'boardCode' => $boardCode,
                'userId' => $userId,
            ]
        );

        if (!$row) {
            return null;
        }

        $rank = Database::fetch(
            'SELECT COUNT(*) AS rank
               FROM tbl_gacha_mileage_progress p
              WHERE p.guildId = :guildId
                AND p.boardCode = :boardCode
                AND p.lifetimeSteps > 0
                AND (
                    p.lifetimeSteps > :lifetimeStepsGreater
                    OR (
                        p.lifetimeSteps = :lifetimeStepsEqual
                        AND (
                            COALESCE(p.updateDate, p.createDate) < :rankDateBefore
                            OR (
                                COALESCE(p.updateDate, p.createDate) = :rankDateEqual
                                AND p.gachaMileageProgressId <= :progressId
                            )
                        )
                    )
                )',
            [
                'guildId' => $guildId,
                'boardCode' => $boardCode,
                'lifetimeStepsGreater' => max(0, (int) ($row['lifetimeSteps'] ?? 0)),
                'lifetimeStepsEqual' => max(0, (int) ($row['lifetimeSteps'] ?? 0)),
                'rankDateBefore' => (string) ($row['rankDate'] ?? ''),
                'rankDateEqual' => (string) ($row['rankDate'] ?? ''),
                'progressId' => max(0, (int) ($row['gachaMileageProgressId'] ?? 0)),
            ]
        );

        $row['_rank'] = max(1, (int) ($rank['rank'] ?? 1));
        return $row;
    }

    private static function leaderboardFocusWeeklyRow(string $guildId, string $boardCode, string $userId, DateTimeImmutable $weekStart): ?array
    {
        $weekStartSql = $weekStart->format('Y-m-d H:i:s');
        $row = Database::fetch(
            'SELECT l.userId,
                    SUM(l.stepDelta) AS weeklySteps,
                    MAX(l.createDate) AS rankDate,
                    COALESCE(p.lifetimeSteps, 0) AS lifetimeSteps,
                    COALESCE(p.positionStep, -1) AS positionStep,
                    COALESCE(p.lastAnimatedStep, -1) AS lastAnimatedStep,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    m.nickName,
                    m.guildAvatarHash
               FROM tbl_gacha_mileage_spin_ledger l
          LEFT JOIN tbl_gacha_mileage_progress p
                 ON p.guildId = l.guildId
                AND p.userId = l.userId
                AND p.boardCode = l.boardCode
          LEFT JOIN tbl_user u
                 ON u.userId = l.userId
          LEFT JOIN tbl_member m
                 ON m.guildId = l.guildId
                AND m.userId = l.userId
              WHERE l.guildId = :guildId
                AND l.boardCode = :boardCode
                AND l.userId = :userId
                AND l.createDate >= :weekStart
              GROUP BY l.userId,
                       p.lifetimeSteps,
                       p.positionStep,
                       p.lastAnimatedStep,
                       u.userName,
                       u.globalName,
                       u.avatarHash,
                       m.nickName,
                       m.guildAvatarHash
             HAVING weeklySteps > 0
              LIMIT 1',
            [
                'guildId' => $guildId,
                'boardCode' => $boardCode,
                'userId' => $userId,
                'weekStart' => $weekStartSql,
            ]
        );

        if (!$row) {
            return null;
        }

        $rank = Database::fetch(
            'SELECT COUNT(*) AS rank
               FROM (
                    SELECT userId,
                           SUM(stepDelta) AS weeklySteps,
                           MAX(createDate) AS rankDate
                      FROM tbl_gacha_mileage_spin_ledger
                     WHERE guildId = :guildId
                       AND boardCode = :boardCode
                       AND createDate >= :weekStart
                  GROUP BY userId
                    HAVING weeklySteps > 0
               ) ranked
	              WHERE ranked.weeklySteps > :weeklyStepsGreater
	                 OR (
	                    ranked.weeklySteps = :weeklyStepsEqual
	                    AND ranked.rankDate <= :rankDate
	                 )',
            [
                'guildId' => $guildId,
                'boardCode' => $boardCode,
                'weekStart' => $weekStartSql,
                'weeklyStepsGreater' => max(0, (int) ($row['weeklySteps'] ?? 0)),
                'weeklyStepsEqual' => max(0, (int) ($row['weeklySteps'] ?? 0)),
                'rankDate' => (string) ($row['rankDate'] ?? ''),
            ]
        );

        $row['_rank'] = max(1, (int) ($rank['rank'] ?? 1));
        return $row;
    }

    private static function leaderboardRowsPayload(string $guildId, array $board, array $rows, string $scoreKey): array
    {
        $players = [];
        foreach ($rows as $index => $row) {
            $lifetimeSteps = max(0, (int) ($row['lifetimeSteps'] ?? 0));
            $score = max(0, (int) ($row[$scoreKey] ?? $lifetimeSteps));
            if ($score <= 0) {
                continue;
            }

            $positionStep = self::positionFromLifetime($lifetimeSteps, $board);
            if ($positionStep < 0 && isset($row['positionStep'])) {
                $positionStep = max(-1, (int) $row['positionStep']);
            }

            $players[] = [
                'rank' => max(1, (int) ($row['_rank'] ?? (count($players) + 1))),
                'userId' => (string) ($row['userId'] ?? ''),
                'displayName' => trim((string) ($row['nickName'] ?? $row['globalName'] ?? $row['userName'] ?? 'Player')) ?: 'Player',
                'avatarUrl' => self::rowAvatarUrl($guildId, $row),
                'positionStep' => $positionStep,
                'lifetimeSteps' => $lifetimeSteps,
                'weeklySteps' => max(0, (int) ($row['weeklySteps'] ?? 0)),
                'score' => $score,
            ];
            unset($index);
        }
        return $players;
    }

    private static function playerPayloadByUserId(string $guildId, string $userId, array $board): ?array
    {
        $row = Database::fetch(
            'SELECT p.userId,
                    p.lifetimeSteps,
                    p.positionStep,
                    p.lastAnimatedStep,
                    u.userName,
                    u.globalName,
                    u.avatarHash,
                    m.nickName,
                    m.guildAvatarHash
               FROM tbl_gacha_mileage_progress p
          LEFT JOIN tbl_user u
                 ON u.userId = p.userId
          LEFT JOIN tbl_member m
                 ON m.guildId = p.guildId
                AND m.userId = p.userId
              WHERE p.guildId = :guildId
                AND p.userId = :userId
                AND p.boardCode = :boardCode
              LIMIT 1',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'boardCode' => (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE),
            ]
        );

        if (!$row) {
            $player = PlayerAuth::currentUser();
            if (!$player) {
                return null;
            }
            return [
                'userId' => $userId,
                'displayName' => trim((string) ($player['nickName'] ?? $player['globalName'] ?? $player['userName'] ?? 'Player')) ?: 'Player',
                'avatarUrl' => self::rowAvatarUrl($guildId, $player),
                'positionStep' => self::positionFromLifetime(0, $board),
                'lifetimeSteps' => 0,
            ];
        }

        return self::playerPayloadFromProgressRow($guildId, $board, $row);
    }

    private static function rowAvatarUrl(string $guildId, array $row): string
    {
        $guildAvatarHash = trim((string) ($row['guildAvatarHash'] ?? ''));
        $userId = (string) ($row['userId'] ?? '');
        if ($guildAvatarHash !== '') {
            $guildAvatar = DiscordAssets::guildAvatar($guildId, $userId, $guildAvatarHash, 128);
            if ($guildAvatar) {
                return $guildAvatar;
            }
        }
        return DiscordAssets::avatar($userId, (string) ($row['avatarHash'] ?? ''), 128);
    }

    private static function playerPayloadFromProgressRow(string $guildId, array $board, array $row): array
    {
        $lifetimeSteps = max(0, (int) ($row['lifetimeSteps'] ?? 0));
        return [
            'userId' => (string) ($row['userId'] ?? ''),
            'displayName' => trim((string) ($row['nickName'] ?? $row['globalName'] ?? $row['userName'] ?? 'Player')) ?: 'Player',
            'avatarUrl' => self::rowAvatarUrl($guildId, $row),
            'positionStep' => self::positionFromLifetime($lifetimeSteps, $board),
            'lifetimeSteps' => $lifetimeSteps,
        ];
    }

    private static function grantReward(string $guildId, string $userId, array $board, array $reward): array
    {
        self::ensureSchema();
        ShopUnitService::ensureSchema();
        TransactionTraceService::ensureSchema();

        $rewardId = trim((string) ($reward['id'] ?? ''));
        if ($rewardId === '') {
            return $reward;
        }

        $existing = Database::fetch(
            'SELECT rewardEventId, claimStatus, rewardJson
               FROM tbl_gacha_mileage_reward_claim
              WHERE guildId = :guildId
                AND userId = :userId
                AND boardCode = :boardCode
                AND rewardId = :rewardId
              LIMIT 1',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'boardCode' => (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE),
                'rewardId' => $rewardId,
            ]
        );
        if ($existing) {
            return $reward + [
                'rewardEventId' => (int) ($existing['rewardEventId'] ?? 0),
                'alreadyClaimed' => true,
            ];
        }

        $traceId = TransactionTraceService::generateTraceId('gacha_mileage');
        $createDate = date('Y-m-d H:i:s');
        $rewardRuleId = self::ensureRewardRule();
        $rewardTemplateId = trim((string) ($reward['rewardTemplateId'] ?? ($reward['meta']['rewardTemplateId'] ?? '')));
        $resolvedBundle = null;
        if ($rewardTemplateId !== '' && class_exists('RewardTemplateService')) {
            $resolvedBundle = RewardTemplateService::resolveTemplate($rewardTemplateId, isset($reward['stepIndex']) ? (int) $reward['stepIndex'] : null);
        }
        $rewardEventPayload = $reward;
        if (is_array($resolvedBundle)) {
            $rewardEventPayload['rewardTemplateId'] = $rewardTemplateId;
            $rewardEventPayload['unitRewards'] = is_array($resolvedBundle['unitRewards'] ?? null) ? $resolvedBundle['unitRewards'] : [];
            $rewardEventPayload['resolvedEntries'] = array_values($resolvedBundle['resolvedEntries'] ?? []);
        }

        Database::insert('tbl_gacha_mileage_reward_claim', [
            'guildId' => $guildId,
            'userId' => $userId,
            'boardCode' => (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE),
            'rewardId' => $rewardId,
            'stepIndex' => isset($reward['stepIndex']) && $reward['stepIndex'] !== null ? (int) $reward['stepIndex'] : null,
            'rewardEventId' => null,
            'claimStatus' => 'pending',
            'rewardJson' => json_encode($reward, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'createDate' => $createDate,
            'updateDate' => $createDate,
        ]);

        $rewardEventId = $rewardRuleId > 0
            ? Database::insert('tbl_reward_event', [
                'rewardRuleId' => $rewardRuleId,
                'guildId' => $guildId,
                'userId' => $userId,
                'sourceType' => self::RULE_CODE,
                'sourceId' => $rewardId,
                'transactionGroupId' => $traceId,
                'rewardStatus' => 'granted',
                'metadataJson' => json_encode([
                    'rule' => self::RULE_CODE,
                    'boardCode' => (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE),
                    'reward' => $rewardEventPayload,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'createDate' => $createDate,
            ])
            : 0;

        $kind = (string) ($reward['kind'] ?? 'coin');
        $amount = max(1, (int) ($reward['amount'] ?? 1));
        $walletRows = [];
        $inventoryRow = null;
        if (is_array($resolvedBundle) && class_exists('RewardTemplateService')) {
            $bundleGrant = RewardTemplateService::grantRewardBundle(
                $guildId,
                $userId,
                $resolvedBundle,
                self::RULE_CODE,
                $rewardId,
                [
                    'transactionGroupId' => $traceId,
                    'createDate' => $createDate,
                    'targetUserId' => $userId,
                ]
            );
            $walletRows = array_values($bundleGrant['walletRows'] ?? []);
            $inventoryRow = $bundleGrant['itemRows'][0] ?? $bundleGrant['lootBoxRows'][0] ?? null;
        } elseif (in_array($kind, ['coin', 'ticket', 'gem', 'potion'], true)) {
            $walletRows[] = ShopUnitService::adjustWalletBalance(
                $guildId,
                $userId,
                $kind,
                $amount,
                'credit',
                self::RULE_CODE,
                $rewardId,
                [
                    'rule' => self::RULE_CODE,
                    'rewardEventId' => $rewardEventId,
                    'boardCode' => (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE),
                ],
                [
                    'transactionGroupId' => $traceId,
                    'targetUserId' => $userId,
                    'createDate' => $createDate,
                ]
            );
        } elseif ($kind === 'item') {
            $inventoryRow = self::grantInventoryItem($guildId, $userId, $reward, $traceId, $createDate);
        }

        Database::execute(
            'UPDATE tbl_gacha_mileage_reward_claim
                SET rewardEventId = :rewardEventId,
                    claimStatus = :claimStatus,
                    updateDate = :updateDate
              WHERE guildId = :guildId
                AND userId = :userId
                AND boardCode = :boardCode
                AND rewardId = :rewardId',
            [
                'rewardEventId' => $rewardEventId > 0 ? $rewardEventId : null,
                'claimStatus' => 'granted',
                'updateDate' => date('Y-m-d H:i:s'),
                'guildId' => $guildId,
                'userId' => $userId,
                'boardCode' => (string) ($board['boardCode'] ?? self::DEFAULT_BOARD_CODE),
                'rewardId' => $rewardId,
            ]
        );

        return $reward + [
            'rewardEventId' => $rewardEventId,
            'walletRows' => $walletRows,
            'inventory' => $inventoryRow,
            'alreadyClaimed' => false,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function boardRewardDefinitions(array $board): array
    {
        $legacyRewards = array_values(array_filter($board['rewards'] ?? [], static fn (mixed $reward): bool => is_array($reward)));
        $rewardNodes = [];

        foreach (is_array($board['rewardNodes'] ?? null) ? $board['rewardNodes'] : [] as $node) {
            if (!is_array($node)) {
                continue;
            }

            $rewardTemplateId = trim((string) ($node['rewardTemplateId'] ?? ''));
            $meta = is_array($node['meta'] ?? null) ? $node['meta'] : [];
            $stepIndex = $node['stepIndex'] ?? null;
            if ($rewardTemplateId === '' && trim((string) ($meta['kind'] ?? '')) === '') {
                continue;
            }

            $rewardNodes[] = [
                'id' => trim((string) ($node['id'] ?? '')) ?: ('reward_node_' . str_pad((string) (count($rewardNodes) + 1), 3, '0', STR_PAD_LEFT)),
                'stepIndex' => $stepIndex !== null ? (int) $stepIndex : null,
                'x' => $node['x'] ?? null,
                'y' => $node['y'] ?? null,
                'kind' => trim((string) ($meta['kind'] ?? 'coin')) ?: 'coin',
                'amount' => max(1, (int) ($meta['amount'] ?? 1)),
                'itemCode' => trim((string) ($meta['itemCode'] ?? '')),
                'rewardTemplateId' => $rewardTemplateId,
                'iconTemplateId' => trim((string) ($node['iconTemplateId'] ?? '')),
                'label' => trim((string) ($node['label'] ?? ($meta['label'] ?? ''))),
                'meta' => $meta,
            ];
        }

        return array_merge($legacyRewards, $rewardNodes);
    }

    private static function ensureRewardRule(): int
    {
        Database::execute(
            'INSERT INTO tbl_reward_rule (ruleCode, ruleName, triggerType, conditionJson, rewardJson, isActive, updateDate)
             VALUES (:ruleCode, :ruleName, :triggerType, :conditionJson, :rewardJson, 1, :updateDate)
             ON DUPLICATE KEY UPDATE updateDate = updateDate',
            [
                'ruleCode' => self::RULE_CODE,
                'ruleName' => 'Gacha Mileage',
                'triggerType' => self::RULE_CODE,
                'conditionJson' => json_encode(['gachaMileage' => true], JSON_UNESCAPED_SLASHES),
                'rewardJson' => json_encode(['unitRewards' => [], 'items' => []], JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
        $row = Database::fetch(
            'SELECT rewardRuleId
               FROM tbl_reward_rule
              WHERE ruleCode = :ruleCode
              LIMIT 1',
            ['ruleCode' => self::RULE_CODE]
        );
        return (int) ($row['rewardRuleId'] ?? 0);
    }

    private static function grantInventoryItem(
        string $guildId,
        string $userId,
        array $reward,
        string $traceId,
        string $createDate
    ): ?array {
        $itemCode = preg_replace('/[^a-z0-9_]+/i', '_', strtolower(trim((string) ($reward['itemCode'] ?? '')))) ?? '';
        $itemCode = trim($itemCode, '_');
        if ($itemCode === '') {
            return null;
        }

        ShopInventoryLedgerService::ensureSchema();
        $itemName = trim((string) ($reward['label'] ?? $itemCode)) ?: $itemCode;
        Database::execute(
            'INSERT INTO tbl_shop_item (itemCode, itemName, itemType, image, metadataJson, isActive, updateDate)
             VALUES (:itemCode, :itemName, "item", :image, :metadataJson, 1, :updateDate)
             ON DUPLICATE KEY UPDATE updateDate = updateDate',
            [
                'itemCode' => $itemCode,
                'itemName' => $itemName,
                'image' => null,
                'metadataJson' => json_encode(['source' => self::RULE_CODE, 'reward' => $reward], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updateDate' => $createDate,
            ]
        );

        $itemRow = Database::fetch(
            'SELECT shopItemId
               FROM tbl_shop_item
              WHERE itemCode = :itemCode
              LIMIT 1',
            ['itemCode' => $itemCode]
        );
        $shopItemId = (int) ($itemRow['shopItemId'] ?? 0);
        if ($shopItemId <= 0) {
            return null;
        }

        $quantityDelta = max(1, (int) ($reward['amount'] ?? 1));
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
                    'metadataJson' => json_encode(['source' => self::RULE_CODE], JSON_UNESCAPED_SLASHES),
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

            $inventoryId = (int) ($inventory['shopInventoryId'] ?? 0);
            $before = max(0, (int) ($inventory['quantity'] ?? 0));
            $after = $before + $quantityDelta;

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
                'quantityDelta' => $quantityDelta,
                'quantityBefore' => $before,
                'quantityAfter' => $after,
                'ledgerType' => 'credit',
                'sourceType' => self::RULE_CODE,
                'sourceId' => (string) ($reward['id'] ?? ''),
                'transactionGroupId' => $traceId,
                'targetUserId' => $userId,
                'metadata' => [
                    'rule' => self::RULE_CODE,
                    'reward' => $reward,
                ],
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
                'quantityDelta' => $quantityDelta,
                'inventoryLedgerId' => $ledgerId,
                'itemCode' => $itemCode,
                'itemName' => $itemName,
            ];
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }
}
