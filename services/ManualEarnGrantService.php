<?php

declare(strict_types=1);

final class ManualEarnGrantService
{
    private const RULE_CODE = 'earn_manual_grant';

    /** @return array<string, mixed> */
    public function payload(string $guildId, int $limit = 40): array
    {
        $this->ensureRule();
        $units = class_exists('ShopUnitService') ? ShopUnitService::units(true) : [];
        $recent = Database::fetchAll(
            'SELECT
                re.*,
                rr.ruleName,
                u.userName,
                u.globalName,
                u.avatarHash,
                COALESCE(m.nickName, u.globalName, u.userName, re.userId) AS displayName
             FROM tbl_reward_event re
             INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
             LEFT JOIN tbl_user u ON u.userId = re.userId
             LEFT JOIN tbl_member m ON m.guildId = re.guildId AND m.userId = re.userId
             WHERE re.guildId = :guildId
               AND rr.ruleCode = :ruleCode
             ORDER BY re.rewardEventId DESC
             LIMIT ' . max(10, min(100, $limit)),
            ['guildId' => $guildId, 'ruleCode' => self::RULE_CODE]
        );

        return [
            'units' => array_map(static fn (array $unit): array => [
                'unitCode' => (string) ($unit['unitCode'] ?? ''),
                'displayName' => (string) ($unit['displayName'] ?? $unit['unitCode'] ?? ''),
                'shortName' => (string) ($unit['shortName'] ?? $unit['unitCode'] ?? ''),
                'icon' => (string) ($unit['icon'] ?? ''),
            ], $units),
            'roles' => $this->roles($guildId),
            'memberCount' => $this->activeMemberCount($guildId),
            'recent' => array_map(static function (array $row): array {
                $metadata = json_decode((string) ($row['metadataJson'] ?? '{}'), true);
                $metadata = is_array($metadata) ? $metadata : [];
                $manualGrant = is_array($metadata['manualGrant'] ?? null) ? $metadata['manualGrant'] : [];
                $reward = is_array($metadata['reward'] ?? null) ? $metadata['reward'] : [];
                $unitRewards = is_array($reward['unitRewards'] ?? null) ? $reward['unitRewards'] : [];
                $freeSpins = max(0, (int) ($reward['gachaFreeSpin'] ?? 0));
                if ($freeSpins > 0) {
                    $unitRewards['freeSpin'] = ($unitRewards['freeSpin'] ?? 0) + $freeSpins;
                }
                $row['metadata'] = $metadata;
                $row['manualGrant'] = $manualGrant;
                $row['unitRewards'] = $unitRewards;
                $row['reason'] = (string) ($metadata['reason'] ?? '');
                $row['grantedBy'] = (string) ($metadata['grantedBy']['displayName'] ?? $metadata['grantedBy']['discordUserId'] ?? '');
                $row['targetType'] = (string) ($manualGrant['targetType'] ?? 'user');
                $row['targetLabel'] = (string) ($manualGrant['targetLabel'] ?? '');
                $row['recipientCount'] = max(1, (int) ($manualGrant['recipientCount'] ?? 1));
                $row['batchId'] = (string) ($manualGrant['batchId'] ?? $row['sourceId'] ?? '');
                $row['avatarUrl'] = DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64);
                return $row;
            }, $recent),
        ];
    }

    /** @return array<int, array<string, string>> */
    public function searchMembers(string $guildId, string $q, int $limit = 12): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $params = [
            'guildId' => $guildId,
            'qExact' => $q,
            'qNickName' => '%' . $q . '%',
            'qGlobalName' => '%' . $q . '%',
            'qUserName' => '%' . $q . '%',
        ];

        return array_map(static function (array $row): array {
            return [
                'userId' => (string) ($row['userId'] ?? ''),
                'displayName' => (string) ($row['displayName'] ?? $row['userId'] ?? ''),
                'userName' => (string) ($row['userName'] ?? ''),
                'globalName' => (string) ($row['globalName'] ?? ''),
                'avatarUrl' => DiscordAssets::avatar((string) ($row['userId'] ?? ''), $row['avatarHash'] ?? null, 64),
            ];
        }, Database::fetchAll(
            'SELECT
                m.userId,
                COALESCE(m.nickName, u.globalName, u.userName, m.userId) AS displayName,
                u.userName,
                u.globalName,
                u.avatarHash
             FROM tbl_member m
             LEFT JOIN tbl_user u ON u.userId = m.userId
             WHERE m.guildId = :guildId
               AND m.isActive = 1
               AND m.isDelete = 0
               AND m.deleteDate IS NULL
               AND COALESCE(u.isBot, 0) = 0
               AND (
                    m.userId = :qExact
                    OR COALESCE(m.nickName, "") LIKE :qNickName
                    OR COALESCE(u.globalName, "") LIKE :qGlobalName
                    OR COALESCE(u.userName, "") LIKE :qUserName
               )
             ORDER BY m.isActive DESC, displayName ASC
             LIMIT ' . max(5, min(30, $limit)),
            $params
        ));
    }

    /** @return array<string, mixed> */
    public function grantSelection(string $guildId, array $payload, array $adminUser, int $adminActionId): array
    {
        $guildId = trim($guildId);
        $targetType = $this->normalizeTargetType((string) ($payload['targetType'] ?? 'user'));
        $targetUserId = preg_replace('/[^0-9]/', '', (string) ($payload['targetUserId'] ?? $payload['userId'] ?? '')) ?? '';
        $targetRoleId = preg_replace('/[^0-9]/', '', (string) ($payload['targetRoleId'] ?? '')) ?? '';
        $reason = trim((string) ($payload['reason'] ?? ''));
        $rewardType = $this->normalizeRewardType((string) ($payload['rewardType'] ?? $payload['unitCode'] ?? ''));
        $amount = max(0, (int) ($payload['amount'] ?? 0));

        if ($guildId === '') {
            throw new InvalidArgumentException('Guild is required.');
        }
        if ($rewardType === '') {
            throw new InvalidArgumentException('ต้องเลือกสิ่งที่จะแจก');
        }
        if ($amount <= 0) {
            throw new InvalidArgumentException('ต้องใส่จำนวนมากกว่า 0');
        }

        $grantContext = $this->resolveGrantContext($guildId, $targetType, $targetUserId, $targetRoleId);
        $batchId = $this->batchId();
        $rewardTemplate = $this->rewardTemplate($rewardType);
        $eventCount = 0;
        $recipientResults = [];

        foreach ($grantContext['recipients'] as $recipient) {
            if ($rewardType === 'freeSpin') {
                for ($index = 1; $index <= $amount; $index += 1) {
                    $recipientResults[] = $this->grantToRecipient(
                        $guildId,
                        $recipient,
                        $rewardTemplate,
                        $reason,
                        $adminUser,
                        $adminActionId,
                        [
                            'batchId' => $batchId,
                            'eventSourceId' => $batchId . ':' . (string) ($recipient['userId'] ?? '') . ':' . $index,
                            'targetType' => $grantContext['targetType'],
                            'targetId' => $grantContext['targetId'],
                            'targetLabel' => $grantContext['targetLabel'],
                            'recipientCount' => $grantContext['recipientCount'],
                            'rewardType' => $rewardType,
                            'amount' => $amount,
                            'freeSpinIndex' => $index,
                            'freeSpinTotal' => $amount,
                        ]
                    );
                    $eventCount += 1;
                }
                continue;
            }

            $recipientResults[] = $this->grantToRecipient(
                $guildId,
                $recipient,
                array_merge($rewardTemplate, ['unitRewards' => [$rewardType => $amount]]),
                $reason,
                $adminUser,
                $adminActionId,
                [
                    'batchId' => $batchId,
                    'targetType' => $grantContext['targetType'],
                    'targetId' => $grantContext['targetId'],
                    'targetLabel' => $grantContext['targetLabel'],
                    'recipientCount' => $grantContext['recipientCount'],
                    'rewardType' => $rewardType,
                    'amount' => $amount,
                ]
            );
            $eventCount += 1;
        }

        LiveUpdateService::markTopic('earn_manual', [
            'scope' => 'earn_manual_batch',
            'batchId' => $batchId,
            'targetType' => $grantContext['targetType'],
            'recipientCount' => $grantContext['recipientCount'],
            'rewardType' => $rewardType,
            'amount' => $amount,
        ]);

        return [
            'batchId' => $batchId,
            'targetType' => $grantContext['targetType'],
            'targetId' => $grantContext['targetId'],
            'targetLabel' => $grantContext['targetLabel'],
            'recipientCount' => $grantContext['recipientCount'],
            'rewardType' => $rewardType,
            'amount' => $amount,
            'unitRewards' => $rewardType === 'freeSpin' ? ['freeSpin' => $amount] : [$rewardType => $amount],
            'eventCount' => $eventCount,
            'sampleRecipients' => array_slice(array_map(static fn (array $row): array => [
                'userId' => (string) ($row['userId'] ?? ''),
                'displayName' => (string) ($row['displayName'] ?? $row['userId'] ?? ''),
                'rewardEventId' => (int) ($row['rewardEventId'] ?? 0),
            ], $recipientResults), 0, 8),
        ];
    }

    /** @param array<string, int> $unitRewards */
    public function grant(string $guildId, string $userId, array $unitRewards, string $reason, array $adminUser, int $adminActionId): array
    {
        $amounts = $this->normalizeUnitRewards($unitRewards);
        if (!$amounts) {
            throw new InvalidArgumentException('ต้องใส่จำนวนอย่างน้อย 1 หน่วย');
        }
        $unitCode = array_key_first($amounts);
        return $this->grantSelection($guildId, [
            'targetType' => 'user',
            'targetUserId' => $userId,
            'rewardType' => $unitCode,
            'amount' => (int) ($amounts[$unitCode] ?? 0),
            'reason' => $reason,
        ], $adminUser, $adminActionId);
    }

    private function ensureRule(): int
    {
        Database::execute(
            'INSERT INTO tbl_reward_rule (ruleCode, ruleName, triggerType, conditionJson, rewardJson, isActive, updateDate)
             VALUES (:ruleCode, :ruleName, :triggerType, :conditionJson, :rewardJson, 1, :updateDate)
             ON DUPLICATE KEY UPDATE updateDate = updateDate',
            [
                'ruleCode' => self::RULE_CODE,
                'ruleName' => 'Manual Earn Grant',
                'triggerType' => 'earn_manual',
                'conditionJson' => json_encode(['manual' => true], JSON_UNESCAPED_SLASHES),
                'rewardJson' => json_encode(['unitRewards' => [], 'gachaFreeSpin' => 0], JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
        $row = Database::fetch('SELECT rewardRuleId FROM tbl_reward_rule WHERE ruleCode = :ruleCode', ['ruleCode' => self::RULE_CODE]);
        return (int) ($row['rewardRuleId'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    private function roles(string $guildId): array
    {
        return array_map(static function (array $row): array {
            return [
                'roleId' => (string) ($row['roleId'] ?? ''),
                'roleName' => (string) ($row['roleName'] ?? ''),
                'rolePosition' => (int) ($row['rolePosition'] ?? 0),
                'memberCount' => (int) ($row['memberCount'] ?? 0),
                'unicodeEmoji' => (string) ($row['unicodeEmoji'] ?? ''),
                'roleIconUrl' => DiscordAssets::roleIcon((string) ($row['roleId'] ?? ''), $row['iconHash'] ?? null, 64),
            ];
        }, Database::fetchAll(
            'SELECT
                r.roleId,
                r.roleName,
                r.rolePosition,
                r.iconHash,
                r.unicodeEmoji,
                COUNT(DISTINCT CASE WHEN m.userId IS NOT NULL AND COALESCE(u.isBot, 0) = 0 THEN mr.userId END) AS memberCount
             FROM tbl_role r
             LEFT JOIN tbl_member_role mr
               ON mr.guildId = r.guildId
              AND mr.roleId = r.roleId
              AND mr.isActive = 1
              AND mr.deleteDate IS NULL
             LEFT JOIN tbl_member m
               ON m.guildId = mr.guildId
              AND m.userId = mr.userId
              AND m.isActive = 1
              AND m.isDelete = 0
              AND m.deleteDate IS NULL
             LEFT JOIN tbl_user u
               ON u.userId = mr.userId
             WHERE r.guildId = :guildId
               AND r.deleteDate IS NULL
             GROUP BY r.roleId, r.roleName, r.rolePosition, r.iconHash, r.unicodeEmoji
             ORDER BY r.rolePosition DESC, r.roleName ASC',
            ['guildId' => $guildId]
        ));
    }

    private function activeMemberCount(string $guildId): int
    {
        return (int) (Database::fetch(
            'SELECT COUNT(*) AS total
               FROM tbl_member m
               LEFT JOIN tbl_user u ON u.userId = m.userId
              WHERE m.guildId = :guildId
                AND m.isActive = 1
                AND m.isDelete = 0
                AND m.deleteDate IS NULL
                AND COALESCE(u.isBot, 0) = 0',
            ['guildId' => $guildId]
        )['total'] ?? 0);
    }

    private function normalizeTargetType(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['user', 'role', 'server'], true) ? $value : 'user';
    }

    private function normalizeRewardType(string $value): string
    {
        $value = $this->normalizeCode($value);
        if ($value === 'free_spin') {
            return 'freeSpin';
        }
        if ($value === 'freespin') {
            return 'freeSpin';
        }
        if ($value === 'freeSpin') {
            return 'freeSpin';
        }

        $unitCodes = [];
        foreach (ShopUnitService::units(true) as $unit) {
            $code = $this->normalizeCode((string) ($unit['unitCode'] ?? ''));
            if ($code !== '') {
                $unitCodes[$code] = true;
            }
        }

        return isset($unitCodes[$value]) ? $value : '';
    }

    /** @return array<string, mixed> */
    private function resolveGrantContext(string $guildId, string $targetType, string $targetUserId, string $targetRoleId): array
    {
        if ($targetType === 'user') {
            if ($targetUserId === '') {
                throw new InvalidArgumentException('ต้องเลือกผู้ใช้');
            }
            $member = $this->memberById($guildId, $targetUserId);
            if (!$member) {
                throw new InvalidArgumentException('ไม่พบผู้ใช้คนนี้ในฐานข้อมูลสมาชิก');
            }
            return [
                'targetType' => 'user',
                'targetId' => (string) ($member['userId'] ?? ''),
                'targetLabel' => (string) ($member['displayName'] ?? $targetUserId),
                'recipientCount' => 1,
                'recipients' => [$member],
            ];
        }

        if ($targetType === 'role') {
            if ($targetRoleId === '') {
                throw new InvalidArgumentException('ต้องเลือกยศ');
            }
            $role = Database::fetch(
                'SELECT roleId, roleName
                   FROM tbl_role
                  WHERE guildId = :guildId
                    AND roleId = :roleId
                    AND deleteDate IS NULL
                  LIMIT 1',
                ['guildId' => $guildId, 'roleId' => $targetRoleId]
            );
            if (!$role) {
                throw new InvalidArgumentException('ไม่พบยศนี้ในระบบ');
            }
            $recipients = Database::fetchAll(
                'SELECT
                    m.userId,
                    COALESCE(m.nickName, u.globalName, u.userName, m.userId) AS displayName
                 FROM tbl_member_role mr
                 INNER JOIN tbl_member m
                    ON m.guildId = mr.guildId
                   AND m.userId = mr.userId
                 LEFT JOIN tbl_user u ON u.userId = m.userId
                 WHERE mr.guildId = :guildId
                   AND mr.roleId = :roleId
                   AND mr.isActive = 1
                   AND mr.deleteDate IS NULL
                   AND m.isActive = 1
                   AND m.isDelete = 0
                   AND m.deleteDate IS NULL
                   AND COALESCE(u.isBot, 0) = 0
                 ORDER BY displayName ASC',
                ['guildId' => $guildId, 'roleId' => $targetRoleId]
            );
            if (!$recipients) {
                throw new InvalidArgumentException('ยศนี้ยังไม่มีสมาชิกที่ active');
            }
            return [
                'targetType' => 'role',
                'targetId' => (string) ($role['roleId'] ?? ''),
                'targetLabel' => (string) ($role['roleName'] ?? $targetRoleId),
                'recipientCount' => count($recipients),
                'recipients' => $recipients,
            ];
        }

        $recipients = Database::fetchAll(
            'SELECT
                m.userId,
                COALESCE(m.nickName, u.globalName, u.userName, m.userId) AS displayName
             FROM tbl_member m
             LEFT JOIN tbl_user u ON u.userId = m.userId
             WHERE m.guildId = :guildId
               AND m.isActive = 1
               AND m.isDelete = 0
               AND m.deleteDate IS NULL
               AND COALESCE(u.isBot, 0) = 0
             ORDER BY displayName ASC',
            ['guildId' => $guildId]
        );
        if (!$recipients) {
            throw new InvalidArgumentException('ไม่พบสมาชิก active ในเซิร์ฟเวอร์');
        }
        return [
            'targetType' => 'server',
            'targetId' => 'server',
            'targetLabel' => 'ทั้งเซิร์ฟเวอร์',
            'recipientCount' => count($recipients),
            'recipients' => $recipients,
        ];
    }

    /** @return array<string, mixed> */
    private function rewardTemplate(string $rewardType): array
    {
        if ($rewardType === 'freeSpin') {
            return [
                'unitRewards' => [],
                'coin' => 0,
                'gachaTicket' => 0,
                'gachaFreeSpin' => 1,
            ];
        }

        return [
            'unitRewards' => [],
            'coin' => $rewardType === 'coin' ? 1 : 0,
            'gachaTicket' => $rewardType === 'ticket' ? 1 : 0,
            'gachaFreeSpin' => 0,
        ];
    }

    /** @param array<string, mixed> $recipient */
    /** @param array<string, mixed> $reward */
    /** @param array<string, mixed> $grantContext */
    private function grantToRecipient(
        string $guildId,
        array $recipient,
        array $reward,
        string $reason,
        array $adminUser,
        int $adminActionId,
        array $grantContext
    ): array {
        $userId = preg_replace('/[^0-9]/', '', (string) ($recipient['userId'] ?? '')) ?? '';
        if ($userId === '') {
            throw new InvalidArgumentException('Recipient userId is required.');
        }

        TransactionTraceService::ensureSchema();
        $ruleId = $this->ensureRule();
        $unitRewards = $this->normalizeUnitRewards($reward['unitRewards'] ?? []);
        $traceId = TransactionTraceService::generateTraceId('earn_manual');
        $createDate = date('Y-m-d H:i:s');
        $rewardPayload = [
            'unitRewards' => $unitRewards,
            'coin' => (int) ($unitRewards['coin'] ?? 0),
            'gachaTicket' => (int) ($unitRewards['ticket'] ?? 0),
            'gachaFreeSpin' => max(0, (int) ($reward['gachaFreeSpin'] ?? 0)),
        ];

        $metadata = [
            'rule' => self::RULE_CODE,
            'reward' => $rewardPayload,
            'reason' => trim($reason),
            'manualAdminActionId' => $adminActionId,
            'grantedBy' => [
                'adminUserId' => (int) ($adminUser['adminUserId'] ?? 0),
                'discordUserId' => (string) ($adminUser['discordUserId'] ?? ''),
                'displayName' => (string) ($adminUser['displayName'] ?? ''),
            ],
            'manualGrant' => [
                'batchId' => (string) ($grantContext['batchId'] ?? ''),
                'targetType' => (string) ($grantContext['targetType'] ?? 'user'),
                'targetId' => (string) ($grantContext['targetId'] ?? ''),
                'targetLabel' => (string) ($grantContext['targetLabel'] ?? ''),
                'recipientCount' => max(1, (int) ($grantContext['recipientCount'] ?? 1)),
                'rewardType' => (string) ($grantContext['rewardType'] ?? ''),
                'amount' => max(1, (int) ($grantContext['amount'] ?? 1)),
                'freeSpinIndex' => max(0, (int) ($grantContext['freeSpinIndex'] ?? 0)),
                'freeSpinTotal' => max(0, (int) ($grantContext['freeSpinTotal'] ?? 0)),
            ],
            'recipient' => [
                'userId' => $userId,
                'displayName' => (string) ($recipient['displayName'] ?? $userId),
            ],
        ];

        $rewardEventId = Database::insert('tbl_reward_event', [
            'rewardRuleId' => $ruleId,
            'guildId' => $guildId,
            'userId' => $userId,
            'sourceType' => 'earn_manual',
            'sourceId' => (string) ($grantContext['eventSourceId'] ?? $grantContext['batchId'] ?? ''),
            'transactionGroupId' => $traceId,
            'rewardStatus' => 'granted',
            'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'createDate' => $createDate,
        ]);

        $walletRows = [];
        foreach ($unitRewards as $unitCode => $amount) {
            $walletRows[] = ShopUnitService::adjustWalletBalance(
                $guildId,
                $userId,
                $unitCode,
                $amount,
                'credit',
                'earn_manual',
                (string) $rewardEventId,
                [
                    'rule' => self::RULE_CODE,
                    'rewardEventId' => $rewardEventId,
                    'manualAdminActionId' => $adminActionId,
                    'reason' => trim($reason),
                    'manualGrant' => $metadata['manualGrant'],
                ],
                [
                    'transactionGroupId' => $traceId,
                    'actorUserId' => (string) ($adminUser['discordUserId'] ?? ''),
                    'targetUserId' => $userId,
                    'createDate' => $createDate,
                ]
            );
        }

        return [
            'rewardEventId' => $rewardEventId,
            'userId' => $userId,
            'displayName' => (string) ($recipient['displayName'] ?? $userId),
            'unitRewards' => $rewardPayload['gachaFreeSpin'] > 0
                ? ($unitRewards + ['freeSpin' => (int) $rewardPayload['gachaFreeSpin']])
                : $unitRewards,
            'walletRows' => $walletRows,
        ];
    }

    /** @return array<string, mixed>|null */
    private function memberById(string $guildId, string $userId): ?array
    {
        $row = Database::fetch(
            'SELECT
                m.userId,
                COALESCE(m.nickName, u.globalName, u.userName, m.userId) AS displayName
             FROM tbl_member m
             LEFT JOIN tbl_user u ON u.userId = m.userId
             WHERE m.guildId = :guildId
               AND m.userId = :userId
               AND m.isActive = 1
               AND m.isDelete = 0
               AND m.deleteDate IS NULL
               AND COALESCE(u.isBot, 0) = 0
             LIMIT 1',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        return $row ?: null;
    }

    private function batchId(): string
    {
        return 'manual_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(6)), 0, 12);
    }

    /** @return array<string, int> */
    private function normalizeUnitRewards(array $raw): array
    {
        $unitCodes = [];
        foreach (ShopUnitService::units(true) as $unit) {
            $code = $this->normalizeCode((string) ($unit['unitCode'] ?? ''));
            if ($code !== '') {
                $unitCodes[$code] = true;
            }
        }

        $out = [];
        foreach ($raw as $unitCode => $amount) {
            $unitCode = $this->normalizeCode((string) $unitCode);
            if ($unitCode === '' || !isset($unitCodes[$unitCode])) {
                continue;
            }
            $amount = max(0, (int) $amount);
            if ($amount > 0) {
                $out[$unitCode] = $amount;
            }
        }
        return $out;
    }

    private function normalizeCode(string $value): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9_\-]/', '', trim($value)) ?? '');
    }
}
