<?php

declare(strict_types=1);

final class GachaDailyCheckinService
{
    private const RULE_CODE = 'earn_daily_checkin';
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_daily_checkin_claim (
                gachaDailyCheckinClaimId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                campaignMonth char(7) NOT NULL,
                claimType varchar(24) NOT NULL,
                claimKey varchar(32) NOT NULL,
                rewardJson longtext DEFAULT NULL,
                status varchar(24) NOT NULL DEFAULT "claimed",
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaDailyCheckinClaimId),
                UNIQUE KEY uq_gacha_daily_checkin_claim (guildId, userId, campaignMonth, claimType, claimKey),
                KEY idx_gacha_daily_checkin_user (guildId, userId, campaignMonth)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    public static function normalizeConfig(mixed $config): array
    {
        $config = is_array($config) ? $config : [];
        $month = self::normalizeMonth((string) ($config['campaignMonth'] ?? date('Y-m')));
        $daysInMonth = self::daysInMonth($month);
        $dailyByDay = [];
        foreach (array_filter($config['dailyRewards'] ?? [], 'is_array') as $entry) {
            $day = max(1, min($daysInMonth, (int) ($entry['day'] ?? 0)));
            if ($day > 0) {
                $dailyByDay[$day] = $entry;
            }
        }

        $dailyRewards = [];
        for ($day = 1; $day <= $daysInMonth; $day += 1) {
            $dailyRewards[] = self::normalizeReward($dailyByDay[$day] ?? ['day' => $day, 'coin' => 5], $day);
        }

        $milestoneByDays = [];
        foreach (array_filter($config['milestones'] ?? [], 'is_array') as $entry) {
            $days = max(1, min($daysInMonth, (int) ($entry['days'] ?? 0)));
            if (in_array($days, [3, 7, 14, 28], true)) {
                $milestoneByDays[$days] = $entry;
            }
        }
        $milestones = [];
        foreach ([3, 7, 14, 28] as $days) {
            if ($days > $daysInMonth) {
                continue;
            }
            $milestones[] = self::normalizeReward($milestoneByDays[$days] ?? [
                'days' => $days,
                'label' => 'ครบ ' . $days . ' วัน',
                'ticket' => $days >= 7 ? 1 : 0,
                'coin' => $days * 5,
            ], null, $days);
        }

        return [
            'enabled' => array_key_exists('enabled', $config) ? (bool) $config['enabled'] : true,
            'campaignMonth' => $month,
            'daysInMonth' => $daysInMonth,
            'title' => trim((string) ($config['title'] ?? 'เช็คอินประจำวัน')) ?: 'เช็คอินประจำวัน',
            'subtitle' => trim((string) ($config['subtitle'] ?? 'รับของเล็กๆทุกวัน และสะสมครบเพื่อรับโบนัสพิเศษ')) ?: 'รับของเล็กๆทุกวัน และสะสมครบเพื่อรับโบนัสพิเศษ',
            'dailyRewards' => $dailyRewards,
            'milestones' => $milestones,
        ];
    }

    public static function config(?array $gachaConfig = null): array
    {
        $gachaConfig ??= GachaConfigService::load();
        return self::normalizeConfig($gachaConfig['settings']['dailyCheckin'] ?? []);
    }

    public static function status(string $guildId, string $userId = '', ?array $gachaConfig = null): array
    {
        self::ensureSchema();
        $config = self::config($gachaConfig);
        $month = $config['campaignMonth'];
        $today = date('Y-m-d');
        $todayMonth = substr($today, 0, 7);
        $todayDay = (int) date('j');
        $claims = $userId !== '' ? self::claims($guildId, $userId, $month) : [];
        $dayClaims = $claims['day'] ?? [];
        $milestoneClaims = $claims['milestone'] ?? [];
        $claimedDays = count($dayClaims);

        $dailyRewards = array_map(static function (array $reward) use ($dayClaims, $todayMonth, $month, $todayDay, $config): array {
            $day = (int) ($reward['day'] ?? 0);
            $claimed = isset($dayClaims[(string) $day]);
            return $reward + [
                'claimed' => $claimed,
                'isToday' => $todayMonth === $month && $day === $todayDay,
                'canClaim' => $config['enabled'] && !$claimed && $todayMonth === $month && $day === $todayDay,
            ];
        }, $config['dailyRewards']);

        $milestones = array_map(static function (array $reward) use ($milestoneClaims, $claimedDays, $config): array {
            $days = (int) ($reward['days'] ?? 0);
            $claimed = isset($milestoneClaims[(string) $days]);
            return $reward + [
                'claimed' => $claimed,
                'progress' => min($claimedDays, $days),
                'canClaim' => $config['enabled'] && !$claimed && $claimedDays >= $days,
            ];
        }, $config['milestones']);

        return [
            'ok' => true,
            'requiresLogin' => $userId === '',
            'config' => [
                'enabled' => $config['enabled'],
                'campaignMonth' => $config['campaignMonth'],
                'daysInMonth' => $config['daysInMonth'],
                'title' => $config['title'],
                'subtitle' => $config['subtitle'],
            ],
            'today' => $today,
            'claimedDays' => $claimedDays,
            'dailyRewards' => $dailyRewards,
            'milestones' => $milestones,
        ];
    }

    public static function claim(string $guildId, string $userId, string $claimType, int $value, ?array $gachaConfig = null): array
    {
        self::ensureSchema();
        $guildId = trim($guildId);
        $userId = preg_replace('/[^0-9]/', '', $userId) ?? '';
        if ($guildId === '' || $userId === '') {
            throw new RuntimeException('AUTH_REQUIRED');
        }

        $config = self::config($gachaConfig);
        if (!$config['enabled']) {
            throw new RuntimeException('CHECKIN_DISABLED');
        }

        $month = $config['campaignMonth'];
        if (date('Y-m') !== $month) {
            throw new RuntimeException('CHECKIN_NOT_ACTIVE_MONTH');
        }

        $claimType = $claimType === 'milestone' ? 'milestone' : 'day';
        $reward = null;
        if ($claimType === 'day') {
            $todayDay = (int) date('j');
            if ($value !== $todayDay) {
                throw new RuntimeException('CHECKIN_DAY_LOCKED');
            }
            $reward = self::rewardByKey($config['dailyRewards'], 'day', $value);
        } else {
            $reward = self::rewardByKey($config['milestones'], 'days', $value);
            $claimedDays = count(self::claims($guildId, $userId, $month)['day'] ?? []);
            if ($claimedDays < $value) {
                throw new RuntimeException('MILESTONE_NOT_READY');
            }
        }
        if (!$reward) {
            throw new RuntimeException('REWARD_NOT_FOUND');
        }

        $claimKey = (string) $value;
        $claimId = 0;
        try {
            $claimId = Database::insert('tbl_gacha_daily_checkin_claim', [
                'guildId' => $guildId,
                'userId' => $userId,
                'campaignMonth' => $month,
                'claimType' => $claimType,
                'claimKey' => $claimKey,
                'rewardJson' => json_encode($reward, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'status' => 'claimed',
                'createDate' => date('Y-m-d H:i:s'),
                'updateDate' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            throw new RuntimeException('ALREADY_CLAIMED');
        }

        try {
            $granted = self::grantReward($guildId, $userId, $claimType, $claimKey, $reward, $claimId);
        } catch (Throwable $exception) {
            Database::execute(
                'UPDATE tbl_gacha_daily_checkin_claim
                    SET status = "failed", updateDate = :updateDate
                  WHERE gachaDailyCheckinClaimId = :claimId',
                ['claimId' => $claimId, 'updateDate' => date('Y-m-d H:i:s')]
            );
            throw $exception;
        }

        return self::status($guildId, $userId, $gachaConfig) + [
            'claimed' => [
                'type' => $claimType,
                'key' => $claimKey,
                'reward' => $reward,
                'granted' => $granted,
            ],
        ];
    }

    private static function claims(string $guildId, string $userId, string $month): array
    {
        $rows = Database::fetchAll(
            'SELECT claimType, claimKey
               FROM tbl_gacha_daily_checkin_claim
              WHERE guildId = :guildId
                AND userId = :userId
                AND campaignMonth = :campaignMonth
                AND status = "claimed"',
            ['guildId' => $guildId, 'userId' => $userId, 'campaignMonth' => $month]
        );
        $claims = ['day' => [], 'milestone' => []];
        foreach ($rows as $row) {
            $type = (string) ($row['claimType'] ?? '');
            $key = (string) ($row['claimKey'] ?? '');
            if (isset($claims[$type]) && $key !== '') {
                $claims[$type][$key] = true;
            }
        }
        return $claims;
    }

    private static function grantReward(string $guildId, string $userId, string $claimType, string $claimKey, array $reward, int $claimId): array
    {
        $unitRewards = self::unitRewards($reward);
        $freeSpin = max(0, (int) ($reward['freeSpin'] ?? $reward['gachaFreeSpin'] ?? 0));
        $traceId = class_exists('TransactionTraceService') ? TransactionTraceService::generateTraceId('daily_checkin') : 'daily_checkin_' . bin2hex(random_bytes(6));
        $createDate = date('Y-m-d H:i:s');
        $rewardEventId = 0;
        if (class_exists('TransactionTraceService')) {
            TransactionTraceService::ensureSchema();
        }

        if ($unitRewards || $freeSpin > 0) {
            $rewardEventId = self::ensureRewardEvent($guildId, $userId, $claimType, $claimKey, $reward, $unitRewards, $freeSpin, $traceId, $claimId, $createDate);
        }

        $walletRows = [];
        foreach ($unitRewards as $unitCode => $amount) {
            $walletRows[] = ShopUnitService::adjustWalletBalance(
                $guildId,
                $userId,
                $unitCode,
                $amount,
                'credit',
                'earn_rule',
                (string) $rewardEventId,
                [
                    'rule' => self::RULE_CODE,
                    'rewardEventId' => $rewardEventId,
                    'dailyCheckinClaimId' => $claimId,
                    'claimType' => $claimType,
                    'claimKey' => $claimKey,
                ],
                [
                    'transactionGroupId' => $traceId,
                    'targetUserId' => $userId,
                    'createDate' => $createDate,
                ]
            );
        }

        return [
            'rewardEventId' => $rewardEventId,
            'unitRewards' => $unitRewards,
            'freeSpin' => $freeSpin,
            'walletRows' => $walletRows,
        ];
    }

    private static function ensureRewardEvent(
        string $guildId,
        string $userId,
        string $claimType,
        string $claimKey,
        array $reward,
        array $unitRewards,
        int $freeSpin,
        string $traceId,
        int $claimId,
        string $createDate
    ): int {
        $ruleId = self::ensureRewardRule();
        if ($ruleId <= 0) {
            return 0;
        }
        $rewardPayload = [
            'unitRewards' => $unitRewards,
            'coin' => (int) ($unitRewards['coin'] ?? 0),
            'gachaTicket' => (int) ($unitRewards['ticket'] ?? 0),
            'gachaFreeSpin' => $freeSpin,
        ];
        return Database::insert('tbl_reward_event', [
            'rewardRuleId' => $ruleId,
            'guildId' => $guildId,
            'userId' => $userId,
            'sourceType' => self::RULE_CODE,
            'sourceId' => $claimType . ':' . $claimKey,
            'transactionGroupId' => $traceId,
            'rewardStatus' => 'granted',
            'metadataJson' => json_encode([
                'rule' => self::RULE_CODE,
                'reward' => $rewardPayload,
                'dailyCheckin' => [
                    'claimId' => $claimId,
                    'claimType' => $claimType,
                    'claimKey' => $claimKey,
                    'label' => (string) ($reward['label'] ?? ''),
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'createDate' => $createDate,
        ]);
    }

    private static function ensureRewardRule(): int
    {
        Database::execute(
            'INSERT INTO tbl_reward_rule (ruleCode, ruleName, triggerType, conditionJson, rewardJson, isActive, updateDate)
             VALUES (:ruleCode, :ruleName, :triggerType, :conditionJson, :rewardJson, 1, :updateDate)
             ON DUPLICATE KEY UPDATE updateDate = updateDate',
            [
                'ruleCode' => self::RULE_CODE,
                'ruleName' => 'Daily Check-in',
                'triggerType' => self::RULE_CODE,
                'conditionJson' => json_encode(['dailyCheckin' => true], JSON_UNESCAPED_SLASHES),
                'rewardJson' => json_encode(['unitRewards' => [], 'gachaFreeSpin' => 0], JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
        $row = Database::fetch('SELECT rewardRuleId FROM tbl_reward_rule WHERE ruleCode = :ruleCode', ['ruleCode' => self::RULE_CODE]);
        return (int) ($row['rewardRuleId'] ?? 0);
    }

    private static function unitRewards(array $reward): array
    {
        $out = [];
        foreach (['coin', 'ticket', 'gem', 'potion'] as $unitCode) {
            $amount = max(0, (int) ($reward[$unitCode] ?? 0));
            if ($amount > 0) {
                $out[$unitCode] = $amount;
            }
        }
        if (isset($reward['unitRewards']) && is_array($reward['unitRewards'])) {
            foreach ($reward['unitRewards'] as $unitCode => $amount) {
                $code = preg_replace('/[^a-z0-9_]+/i', '', strtolower((string) $unitCode)) ?: '';
                $amount = max(0, (int) $amount);
                if ($code !== '' && $amount > 0) {
                    $out[$code] = ($out[$code] ?? 0) + $amount;
                }
            }
        }
        return $out;
    }

    private static function normalizeReward(array $reward, ?int $day = null, ?int $days = null): array
    {
        $out = [
            'label' => trim((string) ($reward['label'] ?? '')),
            'note' => trim((string) ($reward['note'] ?? '')),
            'icon' => trim((string) ($reward['icon'] ?? $reward['image'] ?? '')),
            'coin' => max(0, (int) ($reward['coin'] ?? 0)),
            'gem' => max(0, (int) ($reward['gem'] ?? 0)),
            'ticket' => max(0, (int) ($reward['ticket'] ?? $reward['gachaTicket'] ?? 0)),
            'potion' => max(0, (int) ($reward['potion'] ?? 0)),
            'freeSpin' => max(0, (int) ($reward['freeSpin'] ?? $reward['gachaFreeSpin'] ?? 0)),
        ];
        if ($day !== null) {
            $out['day'] = $day;
            $out['label'] = $out['label'] !== '' ? $out['label'] : 'วันที่ ' . $day;
        }
        if ($days !== null) {
            $out['days'] = $days;
            $out['label'] = $out['label'] !== '' ? $out['label'] : 'ครบ ' . $days . ' วัน';
        }
        return $out;
    }

    private static function rewardByKey(array $rewards, string $key, int $value): ?array
    {
        foreach ($rewards as $reward) {
            if ((int) ($reward[$key] ?? 0) === $value) {
                return $reward;
            }
        }
        return null;
    }

    private static function normalizeMonth(string $month): string
    {
        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
    }

    private static function daysInMonth(string $month): int
    {
        $timestamp = strtotime($month . '-01 00:00:00') ?: time();
        return (int) date('t', $timestamp);
    }
}
