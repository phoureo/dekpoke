<?php

declare(strict_types=1);

final class EarnService
{
    public const TICKET_ITEM_CODE = 'gacha_ticket';

    public static function currencies(): array
    {
        if (class_exists('ShopUnitService')) {
            try {
                return array_map(static fn (array $unit): array => [
                    'code' => (string) $unit['unitCode'],
                    'label' => (string) ($unit['displayName'] ?: $unit['unitCode']),
                    'shortName' => (string) ($unit['shortName'] ?: $unit['unitCode']),
                    'storage' => 'tbl_shop_wallet unitCode=' . (string) $unit['unitCode'],
                ], ShopUnitService::units(false));
            } catch (Throwable) {
                // Fall back to legacy labels while shop schema is being created.
            }
        }

        return [
            ['code' => 'coin', 'label' => 'Coin', 'storage' => 'tbl_shop_wallet unitCode=coin'],
            ['code' => 'ticket', 'label' => 'Ticket', 'storage' => 'tbl_shop_wallet unitCode=ticket'],
        ];
    }

    /** @return array<string, int> */
    private static function normalizeUnitRewards(mixed $value): array
    {
        $unitIndex = [];
        if (class_exists('ShopUnitService')) {
            try {
                foreach (ShopUnitService::units(false) as $unit) {
                    $code = self::normalizeUnitCode((string) ($unit['unitCode'] ?? ''));
                    if ($code !== '') {
                        $unitIndex[$code] = true;
                    }
                }
            } catch (Throwable) {
                $unitIndex = [];
            }
        }
        if (!$unitIndex) {
            $unitIndex = ['coin' => true, 'ticket' => true];
        }

        $raw = is_array($value) ? $value : [];
        $candidate = is_array($raw['unitRewards'] ?? null) ? $raw['unitRewards'] : $raw;
        $out = [];
        foreach ($candidate as $code => $amount) {
            if (is_array($amount)) {
                $code = (string) ($amount['unitCode'] ?? $amount['code'] ?? $code);
                $amount = $amount['amount'] ?? $amount['value'] ?? 0;
            }
            $unitCode = self::normalizeUnitCode((string) $code);
            if ($unitCode === '' || !isset($unitIndex[$unitCode])) {
                continue;
            }
            $out[$unitCode] = max(0, (int) $amount);
        }

        if (array_key_exists('coin', $raw)) {
            $out['coin'] = max(0, (int) $raw['coin']);
        }
        if (array_key_exists('gachaTicket', $raw)) {
            $out['ticket'] = max(0, (int) $raw['gachaTicket']);
        }

        return array_filter($out, static fn (int $amount): bool => $amount > 0);
    }

    private static function normalizeUnitCode(string $code): string
    {
        $code = strtolower(trim($code));
        $code = preg_replace('/[^a-z0-9_\-]/', '', $code) ?? '';
        return $code === 'gachaticket' || $code === 'gacha_ticket' ? 'ticket' : $code;
    }

    /** @return array<int, array<string, mixed>> */
    public function rules(): array
    {
        $this->ensureDefaults();
        return Database::fetchAll(
            'SELECT * FROM tbl_reward_rule
             WHERE ruleCode LIKE "earn_%"
             ORDER BY rewardRuleId ASC'
        );
    }

    public function saveRules(array $rules): array
    {
        $this->ensureDefaults();
        $allowed = array_column($this->rules(), null, 'ruleCode');
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $code = (string) ($rule['ruleCode'] ?? '');
            if (!isset($allowed[$code])) {
                continue;
            }
            $current = $allowed[$code];
            $condition = json_decode((string) ($current['conditionJson'] ?? '{}'), true) ?: [];
            $reward = json_decode((string) ($current['rewardJson'] ?? '{}'), true) ?: [];
            $condition['minMinutes'] = max(0, (int) ($rule['minMinutes'] ?? ($condition['minMinutes'] ?? 0)));
            $condition['minSeconds'] = max(0, (int) ($rule['minSeconds'] ?? ($condition['minSeconds'] ?? 0)));
            $condition['dailyLimit'] = max(0, (int) ($rule['dailyLimit'] ?? ($condition['dailyLimit'] ?? 1)));
            $condition['requireResolvedInviter'] = (bool) ($rule['requireResolvedInviter'] ?? ($condition['requireResolvedInviter'] ?? false));
            $condition['minMessages'] = max(0, (int) ($rule['minMessages'] ?? ($condition['minMessages'] ?? 0)));
            $condition['minUniqueChannels'] = max(0, (int) ($rule['minUniqueChannels'] ?? ($condition['minUniqueChannels'] ?? 0)));
            $condition['channelMode'] = in_array(($rule['channelMode'] ?? ($condition['channelMode'] ?? 'all')), ['all', 'allow', 'deny'], true)
                ? (string) ($rule['channelMode'] ?? ($condition['channelMode'] ?? 'all'))
                : 'all';
            $condition['channelIds'] = self::normalizeIdList($rule['channelIds'] ?? ($condition['channelIds'] ?? []));
            $condition['categoryIds'] = self::normalizeIdList($rule['categoryIds'] ?? ($condition['categoryIds'] ?? []));
            $condition['timeStart'] = self::normalizeTimeValue($rule['timeStart'] ?? ($condition['timeStart'] ?? ''));
            $condition['timeEnd'] = self::normalizeTimeValue($rule['timeEnd'] ?? ($condition['timeEnd'] ?? ''));
            $unitRewards = self::normalizeUnitRewards($rule['unitRewards'] ?? $reward['unitRewards'] ?? $reward);
            if (array_key_exists('coin', $rule)) {
                $unitRewards['coin'] = max(0, (int) ($rule['coin'] ?? 0));
            }
            if (array_key_exists('gachaTicket', $rule)) {
                $unitRewards['ticket'] = max(0, (int) ($rule['gachaTicket'] ?? 0));
            }
            $reward['unitRewards'] = $unitRewards;
            $reward['coin'] = (int) ($unitRewards['coin'] ?? 0);
            $reward['gachaTicket'] = (int) ($unitRewards['ticket'] ?? 0);
            $reward['gachaFreeSpin'] = max(0, min(1, (int) ($rule['gachaFreeSpin'] ?? ($reward['gachaFreeSpin'] ?? 0))));

            Database::execute(
                'UPDATE tbl_reward_rule
                 SET ruleName = :ruleName,
                     conditionJson = :conditionJson,
                     rewardJson = :rewardJson,
                     isActive = :isActive,
                     updateDate = :updateDate
                 WHERE ruleCode = :ruleCode',
                [
                    'ruleCode' => $code,
                    'ruleName' => trim((string) ($rule['ruleName'] ?? $current['ruleName'])) ?: (string) $current['ruleName'],
                    'conditionJson' => json_encode($condition, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'rewardJson' => json_encode($reward, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'isActive' => !empty($rule['isActive']) ? 1 : 0,
                    'updateDate' => date('Y-m-d H:i:s'),
                ]
            );
        }

        LiveUpdateService::markTopic('earn_settings', ['scope' => 'earn_rule_save']);
        LiveUpdateService::markTopic('reward_report', ['scope' => 'earn_rule_save']);
        return $this->payload();
    }

    public function payload(): array
    {
        $rules = array_map(static function (array $rule): array {
            $condition = json_decode((string) ($rule['conditionJson'] ?? '{}'), true) ?: [];
            $reward = json_decode((string) ($rule['rewardJson'] ?? '{}'), true) ?: [];
            $unitRewards = self::normalizeUnitRewards($reward['unitRewards'] ?? $reward);
            return $rule + [
                'condition' => $condition,
                'reward' => $reward,
                'unitRewards' => $unitRewards,
                'minMinutes' => (int) ($condition['minMinutes'] ?? 0),
                'minSeconds' => (int) ($condition['minSeconds'] ?? 0),
                'dailyLimit' => (int) ($condition['dailyLimit'] ?? 1),
                'minMessages' => (int) ($condition['minMessages'] ?? 0),
                'minUniqueChannels' => (int) ($condition['minUniqueChannels'] ?? 0),
                'requireResolvedInviter' => (bool) ($condition['requireResolvedInviter'] ?? false),
                'channelMode' => (string) ($condition['channelMode'] ?? 'all'),
                'channelIds' => self::normalizeIdList($condition['channelIds'] ?? []),
                'categoryIds' => self::normalizeIdList($condition['categoryIds'] ?? []),
                'timeStart' => (string) ($condition['timeStart'] ?? ''),
                'timeEnd' => (string) ($condition['timeEnd'] ?? ''),
                'coin' => (int) ($unitRewards['coin'] ?? $reward['coin'] ?? 0),
                'gachaTicket' => (int) ($unitRewards['ticket'] ?? $reward['gachaTicket'] ?? 0),
                'gachaFreeSpin' => max(0, min(1, (int) ($reward['gachaFreeSpin'] ?? 0))),
            ];
        }, $this->rules());

        $today = date('Y-m-d');
        $todayEvents = Database::fetchAll(
            'SELECT re.rewardRuleId, COUNT(*) AS total
             FROM tbl_reward_event re
             INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
             WHERE rr.ruleCode LIKE "earn_%" AND DATE(re.createDate) = :today
             GROUP BY re.rewardRuleId',
            ['today' => $today]
        );
        $counts = [];
        foreach ($todayEvents as $row) {
            $counts[(int) $row['rewardRuleId']] = (int) $row['total'];
        }

        return [
            'rules' => array_map(static function (array $rule) use ($counts): array {
                $rule['grantedToday'] = $counts[(int) $rule['rewardRuleId']] ?? 0;
                return $rule;
            }, $rules),
            'currencies' => self::currencies(),
        ];
    }

    public function syncUser(string $guildId, string $userId, ?string $date = null, ?array $triggerTypes = null): array
    {
        $this->ensureDefaults();
        $date ??= date('Y-m-d');
        $guildId = trim($guildId);
        $userId = trim($userId);
        $allowedTriggers = $triggerTypes ? array_fill_keys(array_map('strval', $triggerTypes), true) : null;
        $stats = ['date' => $date, 'userId' => $userId, 'rules' => 0, 'granted' => 0, 'skipped' => 0, 'byRule' => []];

        if ($guildId === '' || $userId === '') {
            return $stats;
        }

        foreach ($this->rules() as $rule) {
            if ((int) ($rule['isActive'] ?? 0) !== 1) {
                continue;
            }
            $trigger = (string) ($rule['triggerType'] ?? '');
            $ruleCode = (string) ($rule['ruleCode'] ?? '');
            if ($allowedTriggers !== null && !isset($allowedTriggers[$trigger]) && !isset($allowedTriggers[$ruleCode])) {
                continue;
            }
            $stats['rules']++;
            $condition = json_decode((string) ($rule['conditionJson'] ?? '{}'), true) ?: [];
            $reward = json_decode((string) ($rule['rewardJson'] ?? '{}'), true) ?: [];
            $before = $stats['granted'];
            if ($trigger === 'earn_voice_hourly') {
                $this->grantVoiceHourlyForUser($guildId, $userId, $rule, $condition, $reward, $date, $stats);
            } elseif ($trigger === 'earn_text_active_daily') {
                $this->grantTextActiveForUser($guildId, $userId, $rule, $condition, $reward, $date, $stats);
            }
            $stats['byRule'][(string) ($rule['ruleCode'] ?? 'rule')] = $stats['granted'] - $before;
        }

        if ($stats['granted'] > 0) {
            LiveUpdateService::markTopic('earn_settings', ['scope' => 'earn_user_sync'] + $stats);
            LiveUpdateService::markTopic('reward_report', ['scope' => 'earn_user_sync'] + $stats);
        }

        return $stats;
    }

    public function processDue(?string $date = null): array
    {
        $this->ensureDefaults();
        $date ??= date('Y-m-d');
        $guildId = (string) Bootstrap::config('discord.guildId', '');
        $rules = $this->rules();
        $stats = ['date' => $date, 'rules' => 0, 'granted' => 0, 'skipped' => 0, 'byRule' => []];
        foreach ($rules as $rule) {
            if ((int) $rule['isActive'] !== 1) {
                continue;
            }
            $stats['rules']++;
            $condition = json_decode((string) ($rule['conditionJson'] ?? '{}'), true) ?: [];
            $reward = json_decode((string) ($rule['rewardJson'] ?? '{}'), true) ?: [];
            $before = $stats['granted'];
            $trigger = (string) $rule['triggerType'];
            if ($trigger === 'earn_text_active_daily') {
                $this->grantTextActive($guildId, $rule, $condition, $reward, $date, $stats);
            } elseif ($trigger === 'earn_voice_hourly') {
                $this->grantVoiceHourly($guildId, $rule, $condition, $reward, $date, $stats);
            } elseif ($trigger === 'earn_member_first_join') {
                $this->grantMemberFirstJoin($guildId, $rule, $condition, $reward, $date, $stats);
            } elseif ($trigger === 'earn_invite_member') {
                $this->grantInviteMember($guildId, $rule, $condition, $reward, $date, $stats);
            }
            $stats['byRule'][$rule['ruleCode']] = $stats['granted'] - $before;
        }
        if ($stats['granted'] > 0) {
            LiveUpdateService::markTopic('earn_settings', ['scope' => 'earn_grants'] + $stats);
            LiveUpdateService::markTopic('reward_report', ['scope' => 'earn_grants'] + $stats);
        }
        return $stats;
    }

    private function grantTextActive(string $guildId, array $rule, array $condition, array $reward, string $date, array &$stats): void
    {
        $minMinutes = max(1, (int) ($condition['minMinutes'] ?? 10));
        $minMessages = max(0, (int) ($condition['minMessages'] ?? 0));
        $minUniqueChannels = max(0, (int) ($condition['minUniqueChannels'] ?? 0));
        $dailyLimit = max(0, (int) ($condition['dailyLimit'] ?? 1));
        if ($dailyLimit === 0) return;
        $where = [
            'm.guildId = :guildId',
            'm.authorUserId IS NOT NULL',
            'COALESCE(u.isBot, 0) = 0',
            'c.channelType IN (0, 5, 10, 11, 12, 15, 16)',
            'm.messageCreateDate BETWEEN :dayStart AND :dayEnd',
        ];
        $params = ['guildId' => $guildId, 'dayStart' => $date . ' 00:00:00', 'dayEnd' => $date . ' 23:59:59'];
        $channelIds = self::normalizeIdList($condition['channelIds'] ?? []);
        $categoryIds = self::normalizeIdList($condition['categoryIds'] ?? []);
        $channelMode = (string) ($condition['channelMode'] ?? 'all');
        if ($channelIds && in_array($channelMode, ['allow', 'deny'], true)) {
            $placeholders = self::bindList($params, 'channelId', $channelIds);
            $where[] = 'm.channelId ' . ($channelMode === 'allow' ? 'IN' : 'NOT IN') . ' (' . implode(',', $placeholders) . ')';
        }
        if ($categoryIds) {
            $placeholders = self::bindList($params, 'categoryId', $categoryIds);
            $where[] = 'c.parentChannelId IN (' . implode(',', $placeholders) . ')';
        }
        $timeStart = self::normalizeTimeValue($condition['timeStart'] ?? '');
        $timeEnd = self::normalizeTimeValue($condition['timeEnd'] ?? '');
        if ($timeStart !== '' && $timeEnd !== '') {
            $params['timeStart'] = $timeStart . ':00';
            $params['timeEnd'] = $timeEnd . ':59';
            $where[] = $timeStart <= $timeEnd
                ? 'TIME(m.messageCreateDate) BETWEEN :timeStart AND :timeEnd'
                : '(TIME(m.messageCreateDate) >= :timeStart OR TIME(m.messageCreateDate) <= :timeEnd)';
        }
        $having = ['activeMinutes >= :minMinutes'];
        $params['minMinutes'] = $minMinutes;
        if ($minMessages > 0) {
            $having[] = 'messageCount >= :minMessages';
            $params['minMessages'] = $minMessages;
        }
        if ($minUniqueChannels > 0) {
            $having[] = 'uniqueChannels >= :minUniqueChannels';
            $params['minUniqueChannels'] = $minUniqueChannels;
        }
        $rows = Database::fetchAll(
            'SELECT m.guildId, m.authorUserId AS userId,
                    COUNT(DISTINCT DATE_FORMAT(m.messageCreateDate, "%Y-%m-%d %H:%i")) AS activeMinutes,
                    COUNT(*) AS messageCount,
                    COUNT(DISTINCT m.channelId) AS uniqueChannels
             FROM tbl_message m
             LEFT JOIN tbl_user u ON u.userId = m.authorUserId
             LEFT JOIN tbl_channel c ON c.channelId = m.channelId
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY m.guildId, m.authorUserId
             HAVING ' . implode(' AND ', $having),
            $params
        );
        foreach ($rows as $row) {
            $earned = min($dailyLimit, intdiv((int) $row['activeMinutes'], $minMinutes));
            for ($i = 1; $i <= $earned; $i++) {
                $this->grantOnce($rule, (string) $row['guildId'], (string) $row['userId'], 'earn_text_active_daily', $date . ':' . $i, $reward, ['date' => $date, 'activeMinutes' => (int) $row['activeMinutes'], 'segment' => $i], $stats);
            }
        }
    }

    private function grantTextActiveForUser(string $guildId, string $userId, array $rule, array $condition, array $reward, string $date, array &$stats): void
    {
        $minMinutes = max(1, (int) ($condition['minMinutes'] ?? 10));
        $minMessages = max(0, (int) ($condition['minMessages'] ?? 0));
        $minUniqueChannels = max(0, (int) ($condition['minUniqueChannels'] ?? 0));
        $dailyLimit = max(0, (int) ($condition['dailyLimit'] ?? 1));
        if ($dailyLimit === 0) {
            return;
        }

        $where = [
            'm.guildId = :guildId',
            'm.authorUserId = :userId',
            'COALESCE(u.isBot, 0) = 0',
            'c.channelType IN (0, 5, 10, 11, 12, 15, 16)',
            'm.messageCreateDate BETWEEN :dayStart AND :dayEnd',
        ];
        $params = [
            'guildId' => $guildId,
            'userId' => $userId,
            'dayStart' => $date . ' 00:00:00',
            'dayEnd' => $date . ' 23:59:59',
        ];
        $channelIds = self::normalizeIdList($condition['channelIds'] ?? []);
        $categoryIds = self::normalizeIdList($condition['categoryIds'] ?? []);
        $channelMode = (string) ($condition['channelMode'] ?? 'all');
        if ($channelIds && in_array($channelMode, ['allow', 'deny'], true)) {
            $placeholders = self::bindList($params, 'channelId', $channelIds);
            $where[] = 'm.channelId ' . ($channelMode === 'allow' ? 'IN' : 'NOT IN') . ' (' . implode(',', $placeholders) . ')';
        }
        if ($categoryIds) {
            $placeholders = self::bindList($params, 'categoryId', $categoryIds);
            $where[] = 'c.parentChannelId IN (' . implode(',', $placeholders) . ')';
        }
        $timeStart = self::normalizeTimeValue($condition['timeStart'] ?? '');
        $timeEnd = self::normalizeTimeValue($condition['timeEnd'] ?? '');
        if ($timeStart !== '' && $timeEnd !== '') {
            $params['timeStart'] = $timeStart . ':00';
            $params['timeEnd'] = $timeEnd . ':59';
            $where[] = $timeStart <= $timeEnd
                ? 'TIME(m.messageCreateDate) BETWEEN :timeStart AND :timeEnd'
                : '(TIME(m.messageCreateDate) >= :timeStart OR TIME(m.messageCreateDate) <= :timeEnd)';
        }
        $having = ['activeMinutes >= :minMinutes'];
        $params['minMinutes'] = $minMinutes;
        if ($minMessages > 0) {
            $having[] = 'messageCount >= :minMessages';
            $params['minMessages'] = $minMessages;
        }
        if ($minUniqueChannels > 0) {
            $having[] = 'uniqueChannels >= :minUniqueChannels';
            $params['minUniqueChannels'] = $minUniqueChannels;
        }

        $row = Database::fetch(
            'SELECT m.guildId, m.authorUserId AS userId,
                    COUNT(DISTINCT DATE_FORMAT(m.messageCreateDate, "%Y-%m-%d %H:%i")) AS activeMinutes,
                    COUNT(*) AS messageCount,
                    COUNT(DISTINCT m.channelId) AS uniqueChannels
             FROM tbl_message m
             LEFT JOIN tbl_user u ON u.userId = m.authorUserId
             LEFT JOIN tbl_channel c ON c.channelId = m.channelId
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY m.guildId, m.authorUserId
             HAVING ' . implode(' AND ', $having) . '
             LIMIT 1',
            $params
        );
        if (!$row) {
            return;
        }

        $earned = min($dailyLimit, intdiv((int) ($row['activeMinutes'] ?? 0), $minMinutes));
        for ($i = 1; $i <= $earned; $i++) {
            $this->grantOnce(
                $rule,
                (string) ($row['guildId'] ?? $guildId),
                (string) ($row['userId'] ?? $userId),
                'earn_text_active_daily',
                $date . ':' . $i,
                $reward,
                ['date' => $date, 'activeMinutes' => (int) ($row['activeMinutes'] ?? 0), 'segment' => $i],
                $stats
            );
        }
    }

    private function grantVoiceHourly(string $guildId, array $rule, array $condition, array $reward, string $date, array &$stats): void
    {
        $minSeconds = max(60, (int) ($condition['minSeconds'] ?? 3600));
        $dailyLimit = max(0, (int) ($condition['dailyLimit'] ?? 24));
        if ($dailyLimit === 0) return;
        $sourceType = $this->sourceTypeForRule($rule, 'earn_voice_hourly');
        $rows = Database::fetchAll(
            'SELECT guildId, userId, voiceSeconds
             FROM tbl_user_daily_summary
             WHERE guildId = :guildId AND summaryDate = :summaryDate AND voiceSeconds >= :minSeconds',
            ['guildId' => $guildId, 'summaryDate' => $date, 'minSeconds' => $minSeconds]
        );
        foreach ($rows as $row) {
            $earned = min($dailyLimit, intdiv((int) $row['voiceSeconds'], $minSeconds));
            for ($i = 1; $i <= $earned; $i++) {
                $this->grantOnce($rule, (string) $row['guildId'], (string) $row['userId'], $sourceType, $date . ':' . $i, $reward, ['date' => $date, 'voiceSeconds' => (int) $row['voiceSeconds'], 'segment' => $i], $stats);
            }
        }
    }

    private function grantVoiceHourlyForUser(string $guildId, string $userId, array $rule, array $condition, array $reward, string $date, array &$stats): void
    {
        $minSeconds = max(60, (int) ($condition['minSeconds'] ?? 3600));
        $dailyLimit = max(0, (int) ($condition['dailyLimit'] ?? 24));
        if ($dailyLimit === 0) {
            return;
        }

        $sourceType = $this->sourceTypeForRule($rule, 'earn_voice_hourly');
        $voiceSeconds = $this->voiceSecondsForUserDate($guildId, $userId, $date);
        if ($voiceSeconds < $minSeconds) {
            return;
        }

        $earned = min($dailyLimit, intdiv($voiceSeconds, $minSeconds));
        for ($i = 1; $i <= $earned; $i++) {
            $this->grantOnce(
                $rule,
                $guildId,
                $userId,
                $sourceType,
                $date . ':' . $i,
                $reward,
                ['date' => $date, 'voiceSeconds' => $voiceSeconds, 'segment' => $i],
                $stats
            );
        }
    }

    private function sourceTypeForRule(array $rule, string $fallback): string
    {
        $ruleCode = (string) ($rule['ruleCode'] ?? '');
        return $ruleCode === 'earn_voice_10min_free_spin' ? $ruleCode : $fallback;
    }

    private function grantMemberFirstJoin(string $guildId, array $rule, array $condition, array $reward, string $date, array &$stats): void
    {
        $dailyLimit = max(0, (int) ($condition['dailyLimit'] ?? 1));
        if ($dailyLimit === 0) return;
        $events = Database::fetchAll(
            'SELECT re.*
             FROM tbl_raw_event re
             WHERE re.guildId = :guildId
               AND re.eventType = "GUILD_MEMBER_ADD"
               AND re.userId IS NOT NULL
               AND re.eventDate BETWEEN :dayStart AND :dayEnd
               AND re.rawEventId = (
                    SELECT re2.rawEventId
                    FROM tbl_raw_event re2
                    WHERE re2.guildId = re.guildId
                      AND re2.userId = re.userId
                      AND re2.eventType = "GUILD_MEMBER_ADD"
                      AND re2.userId IS NOT NULL
                    ORDER BY re2.eventDate ASC, re2.rawEventId ASC
                    LIMIT 1
               )',
            ['guildId' => $guildId, 'dayStart' => $date . ' 00:00:00', 'dayEnd' => $date . ' 23:59:59']
        );
        foreach ($events as $event) {
            $this->grantOnce($rule, $guildId, (string) $event['userId'], 'earn_member_first_join', (string) $event['rawEventId'], $reward, ['eventDate' => $event['eventDate']], $stats);
        }
    }

    private function grantInviteMember(string $guildId, array $rule, array $condition, array $reward, string $date, array &$stats): void
    {
        $dailyLimit = max(0, (int) ($condition['dailyLimit'] ?? 20));
        if ($dailyLimit === 0) {
            return;
        }

        if (class_exists('ActivityMessageIngestService')) {
            (new ActivityMessageIngestService())->reconcileInviteAttributions($guildId);
        }

        $events = Database::fetchAll(
            'SELECT a.*
               FROM tbl_member_join_invite_attribution a
              WHERE a.guildId = :guildId
                AND a.inviteType = "invite"
                AND a.matchStatus = "matched"
                AND a.inviterUserId IS NOT NULL
                AND a.inviterUserId <> ""
                AND COALESCE(a.joinEventDate, a.sourceMessageDate) BETWEEN :dayStart AND :dayEnd
              ORDER BY COALESCE(a.joinEventDate, a.sourceMessageDate) ASC, a.joinInviteAttributionId ASC',
            ['guildId' => $guildId, 'dayStart' => $date . ' 00:00:00', 'dayEnd' => $date . ' 23:59:59']
        );

        $countByInviter = [];
        foreach ($events as $event) {
            $inviterUserId = (string) ($event['inviterUserId'] ?? '');
            $joinedUserId = (string) ($event['joinedUserId'] ?? '');
            if ($inviterUserId === '' || $inviterUserId === $joinedUserId) {
                $stats['skipped']++;
                continue;
            }
            $countByInviter[$inviterUserId] = ($countByInviter[$inviterUserId] ?? 0) + 1;
            if ($countByInviter[$inviterUserId] > $dailyLimit) {
                $stats['skipped']++;
                continue;
            }

            $this->grantOnce(
                $rule,
                $guildId,
                $inviterUserId,
                'earn_invite_member',
                (string) $event['joinInviteAttributionId'],
                $reward,
                [
                    'joinedUserId' => $joinedUserId,
                    'rawEventId' => (int) ($event['rawEventId'] ?? 0),
                    'sourceMessageId' => (string) ($event['sourceMessageId'] ?? ''),
                    'sourceChannelId' => (string) ($event['sourceChannelId'] ?? ''),
                    'eventDate' => (string) ($event['joinEventDate'] ?? $event['sourceMessageDate'] ?? ''),
                    'invite' => [
                        'inviter_name' => (string) ($event['inviterName'] ?? ''),
                        'inviter_user_id' => $inviterUserId,
                        'invite_count' => isset($event['inviteCount']) ? (int) $event['inviteCount'] : null,
                    ],
                ],
                $stats
            );
        }
    }

    private function grantOnce(array $rule, string $guildId, string $userId, string $sourceType, string $sourceId, array $reward, array $metadata, array &$stats): void
    {
        if ($userId === '') {
            $stats['skipped']++;
            return;
        }
        TransactionTraceService::ensureSchema();
        $unitRewards = self::normalizeUnitRewards($reward['unitRewards'] ?? $reward);
        $reward['unitRewards'] = $unitRewards;
        $reward['coin'] = (int) ($unitRewards['coin'] ?? $reward['coin'] ?? 0);
        $reward['gachaTicket'] = (int) ($unitRewards['ticket'] ?? $reward['gachaTicket'] ?? 0);
        $traceId = TransactionTraceService::generateTraceId('earn_rule');
        $createDate = date('Y-m-d H:i:s');
        try {
            $rewardEventId = Database::insert('tbl_reward_event', [
                'rewardRuleId' => (int) $rule['rewardRuleId'],
                'guildId' => $guildId,
                'userId' => $userId,
                'sourceType' => $sourceType,
                'sourceId' => $sourceId,
                'transactionGroupId' => $traceId,
                'rewardStatus' => 'granted',
                'metadataJson' => json_encode(['rule' => $rule['ruleCode'], 'reward' => $reward] + $metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'createDate' => $createDate,
            ]);
        } catch (Throwable) {
            $stats['skipped']++;
            return;
        }

        $freeSpins = max(0, min(1, (int) ($reward['gachaFreeSpin'] ?? 0)));
        $walletMetadata = ['rule' => $rule['ruleCode'], 'rewardEventId' => $rewardEventId, 'sourceType' => $sourceType, 'sourceId' => $sourceId];
        if (class_exists('ShopUnitService')) {
            foreach ($unitRewards as $unitCode => $amount) {
                $amount = max(0, (int) $amount);
                if ($unitCode === '' || $amount <= 0) {
                    continue;
                }
                ShopUnitService::adjustWalletBalance(
                    $guildId,
                    $userId,
                    $unitCode,
                    $amount,
                    'credit',
                    'earn_rule',
                    (string) $rewardEventId,
                    $walletMetadata,
                    [
                        'transactionGroupId' => $traceId,
                        'actorUserId' => null,
                        'targetUserId' => null,
                        'createDate' => $createDate,
                    ]
                );
            }
        }
        if ($freeSpins > 0) {
            Database::execute(
                'UPDATE tbl_reward_event
                    SET metadataJson = :metadataJson
                  WHERE rewardEventId = :rewardEventId',
                [
                    'rewardEventId' => $rewardEventId,
                    'metadataJson' => json_encode(
                        ['rule' => $rule['ruleCode'], 'reward' => $reward] + $metadata,
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    ),
                ]
            );
        }
        $stats['granted']++;
    }

    /** @return array<int, string> */
    private static function normalizeIdList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value) ?: [];
        }
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $id = preg_replace('/[^0-9]/', '', (string) $item);
            if ($id !== '' && !in_array($id, $out, true)) {
                $out[] = $id;
            }
        }
        return $out;
    }

    private static function normalizeTimeValue(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $raw, $matches)) {
            return '';
        }
        return $matches[1] . ':' . $matches[2];
    }

    /** @return array<int, string> */
    private static function bindList(array &$params, string $prefix, array $values): array
    {
        $placeholders = [];
        foreach (array_values($values) as $index => $value) {
            $key = $prefix . $index;
            $params[$key] = $value;
            $placeholders[] = ':' . $key;
        }
        return $placeholders;
    }

    private function ensureDefaults(): void
    {
        $defaults = [
            ['earn_text_daily_ticket', 'Text active 10 minutes daily', 'earn_text_active_daily', ['minMinutes' => 10, 'dailyLimit' => 1], ['coin' => 0, 'gachaTicket' => 1, 'unitRewards' => ['ticket' => 1]]],
            ['earn_voice_hourly_coin', 'Voice 1 hour repeat', 'earn_voice_hourly', ['minSeconds' => 3600, 'dailyLimit' => 24], ['coin' => 10, 'gachaTicket' => 0, 'unitRewards' => ['coin' => 10]]],
            ['earn_voice_10min_free_spin', 'Voice 10 minutes daily free spin', 'earn_voice_hourly', ['minSeconds' => 600, 'dailyLimit' => 1], ['coin' => 0, 'gachaTicket' => 0, 'gachaFreeSpin' => 1, 'unitRewards' => []]],
            ['earn_member_first_join', 'First server join', 'earn_member_first_join', ['dailyLimit' => 1], ['coin' => 10, 'gachaTicket' => 0, 'unitRewards' => ['coin' => 10]]],
            ['earn_invite_member', 'Invite member join', 'earn_invite_member', ['dailyLimit' => 20, 'requireResolvedInviter' => true], ['coin' => 5, 'gachaTicket' => 0, 'unitRewards' => ['coin' => 5]]],
        ];
        foreach ($defaults as [$code, $name, $trigger, $condition, $reward]) {
            Database::execute(
                'INSERT INTO tbl_reward_rule (ruleCode, ruleName, triggerType, conditionJson, rewardJson, isActive, updateDate)
                 VALUES (:ruleCode, :ruleName, :triggerType, :conditionJson, :rewardJson, 1, :updateDate)
                 ON DUPLICATE KEY UPDATE updateDate = updateDate',
                [
                    'ruleCode' => $code,
                    'ruleName' => $name,
                    'triggerType' => $trigger,
                    'conditionJson' => json_encode($condition, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'rewardJson' => json_encode($reward, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'updateDate' => date('Y-m-d H:i:s'),
                ]
            );
        }
    }

    private function voiceSecondsForUserDate(string $guildId, string $userId, string $date): int
    {
        [$dayStart, $dayEnd] = $this->dayBounds($date);
        $row = Database::fetch(
            'SELECT SUM(CASE
                    WHEN isClosed = 1 AND COALESCE(durationSeconds, 0) = 0 THEN 0
                    ELSE GREATEST(0, TIMESTAMPDIFF(
                        SECOND,
                        GREATEST(startDate, :dayStart),
                        LEAST(COALESCE(endDate, NOW()), :dayEnd)
                    ))
                END) AS voiceSeconds
             FROM tbl_voice_session
             WHERE guildId = :guildId
               AND userId = :userId
               AND startDate < :whereEnd
               AND COALESCE(endDate, NOW()) > :whereStart
               AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadataJson, "$.excludedFromRewards")), "false") <> "true"
               AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadataJson, "$.hiddenFromTimeMachine")), "false") <> "true"',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'dayStart' => $dayStart,
                'dayEnd' => $dayEnd,
                'whereStart' => $dayStart,
                'whereEnd' => $dayEnd,
            ]
        );

        return max(0, (int) ($row['voiceSeconds'] ?? 0));
    }

    /** @return array{0:string,1:string} */
    private function dayBounds(string $date): array
    {
        return [$date . ' 00:00:00', $date . ' 23:59:59'];
    }
}
