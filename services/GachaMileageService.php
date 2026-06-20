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
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException('MILEAGE_BOARD_DIR_CREATE_FAILED');
            }
        }

        $bytes = @file_put_contents(
            $path,
            json_encode($board, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
        if ($bytes === false) {
            throw new RuntimeException('MILEAGE_BOARD_SAVE_FAILED');
        }

        @chmod($dir, 0777);
        @chmod($path, 0666);

        return $board;
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
            'leaderboard' => $guildId !== '' ? self::leaderboard($guildId, $boardCode) : ['all' => [], 'weekly' => []],
        ];
    }

    public static function leaderboard(string $guildId, string $boardCode = self::DEFAULT_BOARD_CODE, int $limit = 300): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
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

        return [
            'all' => self::leaderboardRowsPayload($guildId, $board, $allRows, 'lifetimeSteps'),
            'weekly' => self::leaderboardRowsPayload($guildId, $board, $weeklyRows, 'weeklySteps'),
            'weekStart' => $weekStart->format(DateTimeInterface::ATOM),
        ];
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
            ]);
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return self::normalizeBoard(is_array($decoded) ? $decoded : ['boardCode' => $boardCode]);
    }

    private static function normalizeBoard(array $board): array
    {
        $boardCode = self::normalizeBoardCode($board['boardCode'] ?? self::DEFAULT_BOARD_CODE);
        $image = is_array($board['image'] ?? null) ? $board['image'] : [];
        $steps = self::normalizeSteps($board['steps'] ?? []);
        $rewards = self::normalizeRewards($board['rewards'] ?? [], $steps);
        $sprites = self::normalizeSprites($board['sprites'] ?? []);
        $entry = self::normalizeEntryPoint($board['entry'] ?? null, $steps);

        return [
            'boardCode' => $boardCode,
            'version' => max(1, (int) ($board['version'] ?? 1)),
            'title' => trim((string) ($board['title'] ?? 'Mileage Board')) ?: 'Mileage Board',
            'entry' => $entry,
            'image' => [
                'width' => max(1, (int) ($image['width'] ?? 1)),
                'height' => max(1, (int) ($image['height'] ?? 1)),
                'source' => trim((string) ($image['source'] ?? '')),
                'segments' => self::normalizeSegments($image['segments'] ?? []),
            ],
            'steps' => $steps,
            'rewards' => $rewards,
            'sprites' => $sprites,
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

        return $out;
    }

    private static function normalizeSegments(mixed $segments): array
    {
        $out = [];
        foreach (is_array($segments) ? $segments : [] as $segment) {
            if (!is_array($segment)) {
                continue;
            }
            $src = trim((string) ($segment['src'] ?? ''));
            if ($src === '') {
                continue;
            }
            $out[] = [
                'src' => $src,
                'y' => max(0, (int) ($segment['y'] ?? 0)),
                'h' => max(1, (int) ($segment['h'] ?? 1)),
            ];
        }
        return $out;
    }

    private static function normalizeSteps(mixed $steps): array
    {
        $out = [];
        foreach (is_array($steps) ? $steps : [] as $index => $step) {
            if (!is_array($step)) {
                continue;
            }
            $out[] = [
                'i' => count($out),
                'x' => self::normalizeUnitFloat($step['x'] ?? 0),
                'y' => self::normalizeUnitFloat($step['y'] ?? 0),
                'label' => trim((string) ($step['label'] ?? '')),
                'meta' => is_array($step['meta'] ?? null) ? $step['meta'] : [],
            ];
            unset($index);
        }
        return $out;
    }

    private static function normalizeRewards(mixed $rewards, array $steps): array
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

            $x = self::nullableUnitFloat($reward['x'] ?? null);
            $y = self::nullableUnitFloat($reward['y'] ?? null);
            if (($x === null || $y === null) && $stepIndex !== null && isset($steps[$stepIndex])) {
                $x = $steps[$stepIndex]['x'];
                $y = $steps[$stepIndex]['y'];
            }

            $kind = self::normalizeRewardKind((string) ($reward['kind'] ?? 'coin'));
            $amount = max(0, (int) ($reward['amount'] ?? ($kind === 'item' ? 1 : 0)));
            if ($kind !== 'item' && $amount <= 0) {
                $amount = 1;
            }

            $out[] = [
                'id' => $id,
                'stepIndex' => $stepIndex,
                'x' => $x,
                'y' => $y,
                'kind' => $kind,
                'amount' => $amount,
                'itemCode' => trim((string) ($reward['itemCode'] ?? '')),
                'label' => trim((string) ($reward['label'] ?? '')),
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

    private static function normalizeSprites(mixed $sprites): array
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
            if (!in_array($mode, ['once', 'loop', 'pingpong'], true)) {
                $mode = 'loop';
            }

            $id = trim((string) ($sprite['id'] ?? ''));
            if ($id === '') {
                $id = 'sprite_' . str_pad((string) (count($out) + 1), 3, '0', STR_PAD_LEFT);
            }

            $out[] = [
                'id' => $id,
                'label' => trim((string) ($sprite['label'] ?? '')),
                'src' => $src,
                'x' => self::normalizeUnitFloat($sprite['x'] ?? 0),
                'y' => self::normalizeUnitFloat($sprite['y'] ?? 0),
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
                'meta' => is_array($sprite['meta'] ?? null) ? $sprite['meta'] : [],
            ];
            unset($index);
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

    private static function normalizeEntryPoint(mixed $entry, array $steps): array
    {
        $fallback = $steps[0] ?? self::defaultEntryPoint();
        $source = is_array($entry) ? $entry : [];

        return [
            'x' => self::normalizeUnitFloat($source['x'] ?? ($fallback['x'] ?? 0.4123)),
            'y' => self::normalizeUnitFloat($source['y'] ?? ($fallback['y'] ?? 0.9721)),
        ];
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
            $board['rewards'] ?? [],
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
            $lifetimeSteps = max(0, (int) ($row['lifetimeSteps'] ?? 0));
            $positionStep = self::positionFromLifetime($lifetimeSteps, $board);
            if ($positionStep < 0) {
                continue;
            }

            $players[] = [
                'userId' => (string) ($row['userId'] ?? ''),
                'displayName' => trim((string) ($row['nickName'] ?? $row['globalName'] ?? $row['userName'] ?? 'Player')) ?: 'Player',
                'avatarUrl' => self::rowAvatarUrl($guildId, $row),
                'positionStep' => $positionStep,
                'lifetimeSteps' => $lifetimeSteps,
            ];
        }

        return $players;
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
                'rank' => count($players) + 1,
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

        return [
            'userId' => (string) ($row['userId'] ?? ''),
            'displayName' => trim((string) ($row['nickName'] ?? $row['globalName'] ?? $row['userName'] ?? 'Player')) ?: 'Player',
            'avatarUrl' => self::rowAvatarUrl($guildId, $row),
            'positionStep' => self::positionFromLifetime(max(0, (int) ($row['lifetimeSteps'] ?? 0)), $board),
            'lifetimeSteps' => max(0, (int) ($row['lifetimeSteps'] ?? 0)),
        ];
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
                    'reward' => $reward,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'createDate' => $createDate,
            ])
            : 0;

        $kind = (string) ($reward['kind'] ?? 'coin');
        $amount = max(1, (int) ($reward['amount'] ?? 1));
        $walletRows = [];
        $inventoryRow = null;
        if (in_array($kind, ['coin', 'ticket', 'gem', 'potion'], true)) {
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
