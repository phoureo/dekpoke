<?php

declare(strict_types=1);

final class ManualEarnGrantService
{
    private const RULE_CODE_GRANT = 'earn_manual_grant';
    private const RULE_CODE_REVOKE = 'earn_manual_revoke';

    /** @return array<string, mixed> */
    public function payload(string $guildId, int $limit = 40): array
    {
        $this->ensureRules();

        $units = class_exists('ShopUnitService') ? ShopUnitService::units(true) : [];

        return [
            'units' => array_map(static fn (array $unit): array => [
                'unitCode' => (string) ($unit['unitCode'] ?? ''),
                'displayName' => (string) ($unit['displayName'] ?? $unit['unitCode'] ?? ''),
                'shortName' => (string) ($unit['shortName'] ?? $unit['unitCode'] ?? ''),
                'icon' => (string) ($unit['icon'] ?? ''),
            ], $units),
            'roles' => $this->roles($guildId),
            'memberCount' => $this->activeMemberCount($guildId),
            'recent' => $this->recentBatches($guildId, $limit),
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
    public function previewSelection(string $guildId, array $payload): array
    {
        return $this->selectionPreview($this->prepareSelection($guildId, $payload));
    }

    /** @return array<string, mixed> */
    public function grantSelection(string $guildId, array $payload, array $adminUser, int $adminActionId): array
    {
        $selection = $this->prepareSelection($guildId, $payload);
        $batchId = $this->batchId();
        $eventCount = 0;
        $recipientResults = [];

        foreach ($selection['recipients'] as $recipient) {
            if ($selection['rewardType'] === 'freeSpin') {
                for ($index = 1; $index <= $selection['amount']; $index += 1) {
                    $recipientResults[] = $this->grantToRecipient(
                        $guildId,
                        $recipient,
                        [
                            'unitRewards' => [],
                            'gachaFreeSpin' => 1,
                        ],
                        $selection['reason'],
                        $adminUser,
                        $adminActionId,
                        [
                            'batchId' => $batchId,
                            'eventSourceId' => $batchId . ':' . (string) ($recipient['userId'] ?? '') . ':' . $index,
                            'targetType' => $selection['targetType'],
                            'targetId' => $selection['targetId'],
                            'targetLabel' => $selection['targetLabel'],
                            'recipientCount' => $selection['recipientCount'],
                            'rewardType' => $selection['rewardType'],
                            'amount' => $selection['amount'],
                            'freeSpinIndex' => $index,
                            'freeSpinTotal' => $selection['amount'],
                        ]
                    );
                    $eventCount += 1;
                }
                continue;
            }

            $recipientResults[] = $this->grantToRecipient(
                $guildId,
                $recipient,
                [
                    'unitRewards' => $selection['unitRewardsEach'],
                    'gachaFreeSpin' => 0,
                ],
                $selection['reason'],
                $adminUser,
                $adminActionId,
                [
                    'batchId' => $batchId,
                    'targetType' => $selection['targetType'],
                    'targetId' => $selection['targetId'],
                    'targetLabel' => $selection['targetLabel'],
                    'recipientCount' => $selection['recipientCount'],
                    'rewardType' => $selection['rewardType'],
                    'amount' => $selection['amount'],
                ]
            );
            $eventCount += 1;
        }

        LiveUpdateService::markTopic('earn_manual', [
            'scope' => 'earn_manual_batch',
            'batchId' => $batchId,
            'targetType' => $selection['targetType'],
            'recipientCount' => $selection['recipientCount'],
            'rewardType' => $selection['rewardType'],
            'amount' => $selection['amount'],
        ]);

        return [
            'batchId' => $batchId,
            'targetType' => $selection['targetType'],
            'targetId' => $selection['targetId'],
            'targetLabel' => $selection['targetLabel'],
            'recipientCount' => $selection['recipientCount'],
            'rewardType' => $selection['rewardType'],
            'amount' => $selection['amount'],
            'reason' => $selection['reason'],
            'unitRewards' => $selection['unitRewardsEach'],
            'unitRewardsEach' => $selection['unitRewardsEach'],
            'unitRewardsTotal' => $selection['unitRewardsTotal'],
            'eventCount' => $eventCount,
            'sampleRecipients' => array_slice(array_map(static fn (array $row): array => [
                'userId' => (string) ($row['userId'] ?? ''),
                'displayName' => (string) ($row['displayName'] ?? $row['userId'] ?? ''),
                'rewardEventId' => (int) ($row['rewardEventId'] ?? 0),
            ], $recipientResults), 0, 8),
        ];
    }

    /** @return array<string, mixed> */
    public function revokeBatch(string $guildId, array $payload, array $adminUser, int $adminActionId): array
    {
        $this->ensureRules();

        $batchId = trim((string) ($payload['batchId'] ?? ''));
        $reason = trim((string) ($payload['reason'] ?? ''));
        if ($guildId === '' || $batchId === '') {
            throw new InvalidArgumentException('ต้องระบุ batch ที่จะยกเลิก');
        }
        if ($reason === '') {
            throw new InvalidArgumentException('ต้องระบุเหตุผลในการยกเลิก Manual Earn');
        }

        if (isset($this->revokeMapByBatchIds($guildId, [$batchId])[$batchId])) {
            throw new RuntimeException('รายการนี้ถูกยกเลิกไปแล้ว');
        }

        $grantRows = $this->findGrantRowsByBatch($guildId, $batchId);
        if (!$grantRows) {
            throw new RuntimeException('ไม่พบรายการแจกที่ต้องการยกเลิก');
        }

        TransactionTraceService::ensureSchema();
        $traceId = TransactionTraceService::generateTraceId('earn_manual_revoke');
        $ruleId = $this->ensureRule(self::RULE_CODE_REVOKE, 'Manual Earn Revoke', 'earn_manual_revoke');
        $createDate = date('Y-m-d H:i:s');
        $freeSpinService = new GachaFreeSpinService();
        $walletRows = [];
        $reversalCount = 0;
        $recipientMap = [];
        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            foreach ($grantRows as $row) {
                $metadata = $this->decodeJson($row['metadataJson'] ?? '');
                $reward = is_array($metadata['reward'] ?? null) ? $metadata['reward'] : [];
                $unitRewards = $this->normalizeUnitRewards($reward['unitRewards'] ?? []);
                $freeSpins = max(0, (int) ($reward['gachaFreeSpin'] ?? 0));
                $userId = (string) ($row['userId'] ?? '');
                $displayName = (string) ($metadata['recipient']['displayName'] ?? $userId);

                $recipientMap[$userId] = $displayName;

                $reversalMetadata = [
                    'rule' => self::RULE_CODE_REVOKE,
                    'reward' => [
                        'unitRewards' => [],
                        'gachaFreeSpin' => 0,
                    ],
                    'reason' => $reason !== '' ? $reason : (string) ($metadata['reason'] ?? ''),
                    'manualAdminActionId' => $adminActionId,
                    'grantedBy' => [
                        'adminUserId' => (int) ($adminUser['adminUserId'] ?? 0),
                        'discordUserId' => (string) ($adminUser['discordUserId'] ?? ''),
                        'displayName' => (string) ($adminUser['displayName'] ?? ''),
                    ],
                    'manualGrant' => is_array($metadata['manualGrant'] ?? null) ? $metadata['manualGrant'] : [],
                    'manualRevoke' => [
                        'batchId' => $batchId,
                        'originalRewardEventId' => (int) ($row['rewardEventId'] ?? 0),
                        'reason' => $reason,
                    ],
                    'recipient' => [
                        'userId' => $userId,
                        'displayName' => $displayName,
                    ],
                    'reversal' => [
                        'unitRewards' => $unitRewards,
                        'gachaFreeSpin' => $freeSpins,
                    ],
                ];

                $reversalEventId = Database::insert('tbl_reward_event', [
                    'rewardRuleId' => $ruleId,
                    'guildId' => $guildId,
                    'userId' => $userId,
                    'sourceType' => 'earn_manual_revoke',
                    'sourceId' => $batchId . ':' . (string) ($row['rewardEventId'] ?? ''),
                    'transactionGroupId' => $traceId,
                    'rewardStatus' => 'reversed',
                    'metadataJson' => json_encode($reversalMetadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'createDate' => $createDate,
                ]);
                $reversalCount += 1;

                foreach ($unitRewards as $unitCode => $amount) {
                    $walletRows[] = ShopUnitService::adjustWalletBalance(
                        $guildId,
                        $userId,
                        $unitCode,
                        -1 * $amount,
                        'debit',
                        'earn_manual_revoke',
                        (string) $reversalEventId,
                        [
                            'rule' => self::RULE_CODE_REVOKE,
                            'rewardEventId' => $reversalEventId,
                            'manualAdminActionId' => $adminActionId,
                            'reason' => $reversalMetadata['reason'],
                            'manualRevoke' => $reversalMetadata['manualRevoke'],
                        ],
                        [
                            'transactionGroupId' => $traceId,
                            'actorUserId' => (string) ($adminUser['discordUserId'] ?? ''),
                            'targetUserId' => $userId,
                            'createDate' => $createDate,
                            'allowNegative' => true,
                        ]
                    );
                }

                if ($freeSpins > 0) {
                    if ((string) ($row['rewardStatus'] ?? '') === 'granted') {
                        $originalMetadata = $metadata;
                        $originalMetadata['freeSpinStatus'] = 'revoked';
                        $originalMetadata['manualRevokeBatchId'] = $batchId;
                        $originalMetadata['revokedAt'] = $createDate;
                        Database::execute(
                            'UPDATE tbl_reward_event
                                SET rewardStatus = "consumed",
                                    metadataJson = :metadataJson
                              WHERE rewardEventId = :rewardEventId
                                AND rewardStatus = "granted"',
                            [
                                'rewardEventId' => (int) ($row['rewardEventId'] ?? 0),
                                'metadataJson' => json_encode($originalMetadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            ]
                        );
                    } else {
                        $freeSpinService->addDebt(
                            $guildId,
                            $userId,
                            $freeSpins,
                            'earn_manual_revoke',
                            (string) $reversalEventId,
                            [
                                'manualAdminActionId' => $adminActionId,
                                'batchId' => $batchId,
                                'originalRewardEventId' => (int) ($row['rewardEventId'] ?? 0),
                                'reason' => $reversalMetadata['reason'],
                            ]
                        );
                    }
                }
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        $summary = $this->batchSummaryFromRows($grantRows, [
            $batchId => [
                'revoked' => true,
                'revokedAt' => $createDate,
                'revokeReason' => $reason,
                'revokedBy' => (string) ($adminUser['displayName'] ?? ''),
                'reversalCount' => $reversalCount,
            ],
        ]);

        LiveUpdateService::markTopic('earn_manual', [
            'scope' => 'earn_manual_revoke',
            'batchId' => $batchId,
            'recipientCount' => (int) ($summary['recipientCount'] ?? 0),
        ]);

        return [
            'batchId' => $batchId,
            'reversalCount' => $reversalCount,
            'affectedRecipients' => count($recipientMap),
            'walletRowCount' => count($walletRows),
            'summary' => $summary,
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

    /** @return array<string, mixed> */
    private function prepareSelection(string $guildId, array $payload): array
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
        if ($reason === '') {
            throw new InvalidArgumentException('ต้องระบุเหตุผลในการแจก Manual Earn');
        }

        $grantContext = $this->resolveGrantContext($guildId, $targetType, $targetUserId, $targetRoleId);
        $unitRewardsEach = $rewardType === 'freeSpin' ? ['freeSpin' => $amount] : [$rewardType => $amount];
        $unitRewardsTotal = [];
        foreach ($unitRewardsEach as $unitCode => $unitAmount) {
            $unitRewardsTotal[$unitCode] = max(0, (int) $unitAmount) * max(1, (int) ($grantContext['recipientCount'] ?? 1));
        }

        return [
            'targetType' => $grantContext['targetType'],
            'targetId' => $grantContext['targetId'],
            'targetLabel' => $grantContext['targetLabel'],
            'recipientCount' => $grantContext['recipientCount'],
            'recipients' => $grantContext['recipients'],
            'rewardType' => $rewardType,
            'amount' => $amount,
            'reason' => $reason,
            'unitRewardsEach' => $unitRewardsEach,
            'unitRewardsTotal' => $unitRewardsTotal,
        ];
    }

    /** @param array<string, mixed> $selection */
    /** @return array<string, mixed> */
    private function selectionPreview(array $selection): array
    {
        $recipients = array_map(static fn (array $row): array => [
            'userId' => (string) ($row['userId'] ?? ''),
            'displayName' => (string) ($row['displayName'] ?? $row['userId'] ?? ''),
        ], $selection['recipients']);

        return [
            'targetType' => $selection['targetType'],
            'targetId' => $selection['targetId'],
            'targetLabel' => $selection['targetLabel'],
            'recipientCount' => $selection['recipientCount'],
            'rewardType' => $selection['rewardType'],
            'amount' => $selection['amount'],
            'reason' => $selection['reason'],
            'unitRewards' => $selection['unitRewardsEach'],
            'unitRewardsEach' => $selection['unitRewardsEach'],
            'unitRewardsTotal' => $selection['unitRewardsTotal'],
            'recipients' => $selection['targetType'] === 'server' ? [] : $recipients,
            'sampleRecipients' => array_slice($recipients, 0, 12),
            'listSuppressed' => $selection['targetType'] === 'server',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function recentBatches(string $guildId, int $limit): array
    {
        $rows = $this->recentGrantRows($guildId, $limit * 4);
        if (!$rows) {
            return [];
        }

        $batchIds = [];
        foreach ($rows as $row) {
            $batchId = (string) ($row['batchId'] ?? '');
            if ($batchId !== '') {
                $batchIds[$batchId] = true;
            }
        }

        $revokeMap = $this->revokeMapByBatchIds($guildId, array_keys($batchIds));
        $groups = [];
        foreach ($rows as $row) {
            $batchId = (string) ($row['batchId'] ?? '');
            if ($batchId === '') {
                $batchId = 'event_' . (string) ($row['rewardEventId'] ?? '');
            }
            $groups[$batchId][] = $row;
        }

        $result = [];
        foreach ($groups as $batchId => $groupRows) {
            $result[] = $this->batchSummaryFromRows($groupRows, $revokeMap);
        }

        usort($result, static function (array $left, array $right): int {
            $dateCompare = strcmp((string) ($right['createDate'] ?? ''), (string) ($left['createDate'] ?? ''));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp((string) ($right['batchId'] ?? ''), (string) ($left['batchId'] ?? ''));
        });

        return array_slice($result, 0, max(10, min(100, $limit)));
    }

    /** @return array<int, array<string, mixed>> */
    private function recentGrantRows(string $guildId, int $limit): array
    {
        $rows = Database::fetchAll(
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
             LIMIT ' . max(20, min(400, $limit)),
            ['guildId' => $guildId, 'ruleCode' => self::RULE_CODE_GRANT]
        );

        return array_map(fn (array $row): array => $this->decorateGrantRow($row), $rows);
    }

    /** @return array<int, array<string, mixed>> */
    private function findGrantRowsByBatch(string $guildId, string $batchId): array
    {
        $rows = Database::fetchAll(
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
               AND JSON_UNQUOTE(JSON_EXTRACT(re.metadataJson, "$.manualGrant.batchId")) = :batchId
             ORDER BY re.rewardEventId ASC',
            [
                'guildId' => $guildId,
                'ruleCode' => self::RULE_CODE_GRANT,
                'batchId' => $batchId,
            ]
        );

        return array_map(fn (array $row): array => $this->decorateGrantRow($row), $rows);
    }

    /** @param array<int, string> $batchIds */
    /** @return array<string, array<string, mixed>> */
    private function revokeMapByBatchIds(string $guildId, array $batchIds): array
    {
        $batchIds = array_values(array_filter(array_map('strval', $batchIds), static fn (string $value): bool => trim($value) !== ''));
        if (!$batchIds) {
            return [];
        }

        $params = ['guildId' => $guildId, 'ruleCode' => self::RULE_CODE_REVOKE];
        $placeholders = [];
        foreach ($batchIds as $index => $batchId) {
            $key = 'batch' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $batchId;
        }

        $rows = Database::fetchAll(
            'SELECT re.rewardEventId, re.createDate, re.metadataJson
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
              WHERE re.guildId = :guildId
                AND rr.ruleCode = :ruleCode
                AND JSON_UNQUOTE(JSON_EXTRACT(re.metadataJson, "$.manualRevoke.batchId")) IN (' . implode(', ', $placeholders) . ')
              ORDER BY re.rewardEventId DESC',
            $params
        );

        $map = [];
        foreach ($rows as $row) {
            $metadata = $this->decodeJson($row['metadataJson'] ?? '');
            $batchId = trim((string) ($metadata['manualRevoke']['batchId'] ?? ''));
            if ($batchId === '') {
                continue;
            }
            if (!isset($map[$batchId])) {
                $map[$batchId] = [
                    'revoked' => true,
                    'revokedAt' => (string) ($row['createDate'] ?? ''),
                    'revokeReason' => (string) ($metadata['reason'] ?? ''),
                    'revokedBy' => (string) ($metadata['grantedBy']['displayName'] ?? $metadata['grantedBy']['discordUserId'] ?? ''),
                    'reversalCount' => 0,
                ];
            }
            $map[$batchId]['reversalCount'] = max(0, (int) ($map[$batchId]['reversalCount'] ?? 0)) + 1;
        }

        return $map;
    }

    /** @param array<int, array<string, mixed>> $rows */
    /** @param array<string, array<string, mixed>> $revokeMap */
    /** @return array<string, mixed> */
    private function batchSummaryFromRows(array $rows, array $revokeMap): array
    {
        $first = $rows[0] ?? [];
        $batchId = (string) ($first['batchId'] ?? '');
        $recipientCount = max(1, (int) ($first['recipientCount'] ?? 1));
        $rewardType = (string) ($first['manualGrant']['rewardType'] ?? '');
        $amount = max(1, (int) ($first['manualGrant']['amount'] ?? 1));
        $unitRewardsEach = is_array($first['unitRewards'] ?? null) ? $first['unitRewards'] : [];
        if ($rewardType === 'freeSpin') {
            $unitRewardsEach = ['freeSpin' => $amount];
        } elseif ($rewardType !== '' && !isset($unitRewardsEach[$rewardType])) {
            $unitRewardsEach = [$rewardType => $amount];
        }
        $unitRewardsTotal = [];
        foreach ($unitRewardsEach as $unitCode => $amount) {
            $unitRewardsTotal[(string) $unitCode] = max(0, (int) $amount) * $recipientCount;
        }

        $sampleRecipients = [];
        foreach ($rows as $row) {
            $displayName = (string) ($row['displayName'] ?? $row['userId'] ?? '');
            if ($displayName === '' || in_array($displayName, $sampleRecipients, true)) {
                continue;
            }
            $sampleRecipients[] = $displayName;
            if (count($sampleRecipients) >= 8) {
                break;
            }
        }

        $revoke = $revokeMap[$batchId] ?? [
            'revoked' => false,
            'revokedAt' => '',
            'revokeReason' => '',
            'revokedBy' => '',
            'reversalCount' => 0,
        ];

        return [
            'batchId' => $batchId,
            'createDate' => (string) ($first['createDate'] ?? ''),
            'targetType' => (string) ($first['targetType'] ?? 'user'),
            'targetLabel' => (string) ($first['targetLabel'] ?? ''),
            'recipientCount' => $recipientCount,
            'rewardType' => $rewardType,
            'amount' => $amount,
            'unitRewards' => $unitRewardsEach,
            'unitRewardsEach' => $unitRewardsEach,
            'unitRewardsTotal' => $unitRewardsTotal,
            'reason' => (string) ($first['reason'] ?? ''),
            'grantedBy' => (string) ($first['grantedBy'] ?? ''),
            'sampleRecipients' => $sampleRecipients,
            'eventCount' => count($rows),
            'revoked' => (bool) ($revoke['revoked'] ?? false),
            'revokedAt' => (string) ($revoke['revokedAt'] ?? ''),
            'revokeReason' => (string) ($revoke['revokeReason'] ?? ''),
            'revokedBy' => (string) ($revoke['revokedBy'] ?? ''),
            'reversalCount' => max(0, (int) ($revoke['reversalCount'] ?? 0)),
        ];
    }

    /** @return array<string, mixed> */
    private function decorateGrantRow(array $row): array
    {
        $metadata = $this->decodeJson($row['metadataJson'] ?? '');
        $manualGrant = is_array($metadata['manualGrant'] ?? null) ? $metadata['manualGrant'] : [];
        $reward = is_array($metadata['reward'] ?? null) ? $metadata['reward'] : [];
        $unitRewards = is_array($reward['unitRewards'] ?? null) ? $reward['unitRewards'] : [];
        $freeSpins = max(0, (int) ($reward['gachaFreeSpin'] ?? 0));
        if ($freeSpins > 0) {
            $unitRewards['freeSpin'] = max(0, (int) ($unitRewards['freeSpin'] ?? 0)) + $freeSpins;
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
    }

    private function ensureRules(): void
    {
        $this->ensureRule(self::RULE_CODE_GRANT, 'Manual Earn Grant', 'earn_manual');
        $this->ensureRule(self::RULE_CODE_REVOKE, 'Manual Earn Revoke', 'earn_manual_revoke');
    }

    private function ensureRule(string $ruleCode, string $ruleName, string $triggerType): int
    {
        Database::execute(
            'INSERT INTO tbl_reward_rule (ruleCode, ruleName, triggerType, conditionJson, rewardJson, isActive, updateDate)
             VALUES (:ruleCode, :ruleName, :triggerType, :conditionJson, :rewardJson, 1, :updateDate)
             ON DUPLICATE KEY UPDATE updateDate = updateDate',
            [
                'ruleCode' => $ruleCode,
                'ruleName' => $ruleName,
                'triggerType' => $triggerType,
                'conditionJson' => json_encode(['manual' => true], JSON_UNESCAPED_SLASHES),
                'rewardJson' => json_encode(['unitRewards' => [], 'gachaFreeSpin' => 0], JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
        $row = Database::fetch('SELECT rewardRuleId FROM tbl_reward_rule WHERE ruleCode = :ruleCode', ['ruleCode' => $ruleCode]);
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
        if (in_array($value, ['free_spin', 'freespin', 'freeSpin'], true)) {
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
        $ruleId = $this->ensureRule(self::RULE_CODE_GRANT, 'Manual Earn Grant', 'earn_manual');
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
            'rule' => self::RULE_CODE_GRANT,
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
                    'rule' => self::RULE_CODE_GRANT,
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

    /** @return array<string, mixed> */
    private function decodeJson(mixed $json): array
    {
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeCode(string $value): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9_\-]/', '', trim($value)) ?? '');
    }
}
