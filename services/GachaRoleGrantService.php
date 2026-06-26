<?php

declare(strict_types=1);

final class GachaRoleGrantService
{
    private const ACTIVE_GRANT_STATUSES = ['granted', 'revoke_failed'];

    private static bool $schemaReady = false;
    private array $memberRoleStateCache = [];

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_role_grant (
                gachaRoleGrantId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                drawId varchar(64) NOT NULL,
                transactionGroupId varchar(120) DEFAULT NULL,
                prizeId varchar(190) DEFAULT NULL,
                prizeName varchar(255) DEFAULT NULL,
                roleId varchar(32) DEFAULT NULL,
                roleName varchar(190) DEFAULT NULL,
                durationDays int unsigned NOT NULL DEFAULT 0,
                grantStatus varchar(40) NOT NULL DEFAULT "pending",
                grantedAt datetime DEFAULT NULL,
                expireAt datetime DEFAULT NULL,
                revokedAt datetime DEFAULT NULL,
                lastAttemptAt datetime DEFAULT NULL,
                lastError text DEFAULT NULL,
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaRoleGrantId),
                UNIQUE KEY uq_tbl_gacha_role_grant_draw (guildId, drawId),
                KEY idx_tbl_gacha_role_grant_user (guildId, userId, grantStatus),
                KEY idx_tbl_gacha_role_grant_expire (grantStatus, expireAt),
                KEY idx_tbl_gacha_role_grant_role (roleId),
                KEY idx_tbl_gacha_role_grant_trace (transactionGroupId, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    public function grantForDraw(string $guildId, string $userId, array $draw): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $drawId = trim((string) ($draw['drawId'] ?? ''));
        if ($guildId === '' || $userId === '' || $drawId === '') {
            return ['ok' => false, 'applied' => false, 'status' => 'invalid_draw', 'message' => 'draw context is incomplete'];
        }

        $prize = $this->resolvedPrize($draw);
        if (($prize['type'] ?? 'item') !== 'role') {
            return ['ok' => true, 'applied' => false, 'status' => 'not_role'];
        }

        $existing = $this->findByDrawId($guildId, $drawId);
        if (is_array($existing) && in_array((string) ($existing['grantStatus'] ?? ''), ['granted', 'revoked', 'covered_by_permanent'], true)) {
            return ['ok' => true, 'applied' => false, 'status' => (string) $existing['grantStatus'], 'grant' => $existing];
        }

        $now = date('Y-m-d H:i:s');
        $roleId = trim((string) ($prize['roleId'] ?? ''));
        $durationDays = max(0, (int) ($prize['roleDurationDays'] ?? 0));
        $baseRow = [
            'guildId' => $guildId,
            'userId' => $userId,
            'drawId' => $drawId,
            'transactionGroupId' => trim((string) ($draw['transactionGroupId'] ?? $drawId)),
            'prizeId' => (string) ($prize['id'] ?? ''),
            'prizeName' => (string) ($prize['name'] ?? ''),
            'roleId' => $roleId,
            'roleName' => (string) ($prize['roleName'] ?? $prize['name'] ?? $roleId),
            'durationDays' => $durationDays,
            'grantStatus' => 'pending',
            'grantedAt' => null,
            'expireAt' => null,
            'revokedAt' => null,
            'lastAttemptAt' => $now,
            'lastError' => null,
            'metadataJson' => json_encode([
                'drawId' => $drawId,
                'prize' => $prize,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updateDate' => $now,
        ];

        if ($roleId === '') {
            $baseRow['grantStatus'] = 'grant_failed';
            $baseRow['lastError'] = 'Prize role is not configured in prize setting.';
            $this->upsertGrantRow($baseRow);
            $this->markGrantUpdate($guildId, $drawId, 'grant_failed', ['message' => $baseRow['lastError']]);
            return ['ok' => false, 'applied' => false, 'status' => 'grant_failed', 'message' => $baseRow['lastError']];
        }

        $activePermanent = $this->findActivePermanentGrant($guildId, $userId, $roleId, $drawId);
        if ($durationDays > 0 && $activePermanent) {
            $coveredRow = array_replace($baseRow, [
                'grantStatus' => 'covered_by_permanent',
                'grantedAt' => $now,
                'lastError' => null,
                'metadataJson' => $this->encodeMetadata($baseRow['metadataJson'] ?? null, [
                    'stackRule' => 'covered_by_permanent',
                    'coveredByDrawId' => (string) ($activePermanent['drawId'] ?? ''),
                ]),
            ]);
            $this->upsertGrantRow($coveredRow);
            $this->markGrantUpdate($guildId, $drawId, 'covered_by_permanent', ['roleId' => $roleId]);
            return [
                'ok' => true,
                'applied' => false,
                'status' => 'covered_by_permanent',
                'grant' => $this->findByDrawId($guildId, $drawId),
            ];
        }

        if ($durationDays <= 0 && $activePermanent) {
            $coveredRow = array_replace($baseRow, [
                'grantStatus' => 'covered_by_permanent',
                'grantedAt' => $now,
                'lastError' => null,
                'metadataJson' => $this->encodeMetadata($baseRow['metadataJson'] ?? null, [
                    'stackRule' => 'duplicate_permanent_ignored',
                    'coveredByDrawId' => (string) ($activePermanent['drawId'] ?? ''),
                ]),
            ]);
            $this->upsertGrantRow($coveredRow);
            $this->markGrantUpdate($guildId, $drawId, 'covered_by_permanent', ['roleId' => $roleId]);
            return [
                'ok' => true,
                'applied' => false,
                'status' => 'covered_by_permanent',
                'grant' => $this->findByDrawId($guildId, $drawId),
            ];
        }

        if ($durationDays > 0) {
            $stackAnchorTs = $this->currentTempStackEndTs($guildId, $userId, $roleId, $drawId);
            $stackStartTs = max(time(), $stackAnchorTs ?? time());
            $baseRow['expireAt'] = date('Y-m-d H:i:s', $stackStartTs + ($durationDays * 86400));
            $baseRow['metadataJson'] = $this->encodeMetadata($baseRow['metadataJson'] ?? null, [
                'stackRule' => 'extend_existing_duration',
                'stackStartAt' => date('Y-m-d H:i:s', $stackStartTs),
            ]);
        }

        $this->upsertGrantRow($baseRow);

        $response = (new DiscordClient())->request(
            'PUT',
            '/guilds/' . rawurlencode($guildId) . '/members/' . rawurlencode($userId) . '/roles/' . rawurlencode($roleId),
            [],
            $this->auditReason('grant', $drawId, (string) ($prize['name'] ?? $roleId))
        );

        if (!$response['ok']) {
            $message = $this->discordMessage($response);
            $this->upsertGrantRow(array_replace($baseRow, [
                'grantStatus' => 'grant_failed',
                'lastError' => $message,
            ]));
            $this->markGrantUpdate($guildId, $drawId, 'grant_failed', ['message' => $message]);
            return ['ok' => false, 'applied' => true, 'status' => 'grant_failed', 'message' => $message];
        }

        $this->syncMember($guildId, $userId);
        $this->markMemberRoleActiveLocal($guildId, $userId, $roleId, $now);
        $grantedRow = array_replace($baseRow, [
            'grantStatus' => 'granted',
            'grantedAt' => $now,
            'lastError' => null,
        ]);
        $this->upsertGrantRow($grantedRow);
        if ($durationDays <= 0) {
            $this->coverActiveTemporaryRowsByPermanent($guildId, $userId, $roleId, $drawId, $now);
        }
        $this->markGrantUpdate($guildId, $drawId, 'granted', ['roleId' => $roleId, 'expireAt' => $grantedRow['expireAt'] ?? null]);

        return [
            'ok' => true,
            'applied' => true,
            'status' => 'granted',
            'grant' => $this->findByDrawId($guildId, $drawId),
        ];
    }

    public function queueForDraw(string $guildId, string $userId, array $draw): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        $drawId = trim((string) ($draw['drawId'] ?? ''));
        if ($guildId === '' || $userId === '' || $drawId === '') {
            return ['ok' => false, 'queued' => false, 'status' => 'invalid_draw', 'message' => 'draw context is incomplete'];
        }

        $prize = $this->resolvedPrize($draw);
        if (($prize['type'] ?? 'item') !== 'role') {
            return ['ok' => true, 'queued' => false, 'status' => 'not_role'];
        }

        $existing = $this->findByDrawId($guildId, $drawId);
        if (is_array($existing)) {
            return [
                'ok' => true,
                'queued' => (string) ($existing['grantStatus'] ?? '') === 'pending',
                'status' => (string) ($existing['grantStatus'] ?? 'pending'),
                'grant' => $existing,
            ];
        }

        $now = date('Y-m-d H:i:s');
        $roleId = trim((string) ($prize['roleId'] ?? ''));
        $row = [
            'guildId' => $guildId,
            'userId' => $userId,
            'drawId' => $drawId,
            'transactionGroupId' => trim((string) ($draw['transactionGroupId'] ?? $drawId)),
            'prizeId' => (string) ($prize['id'] ?? ''),
            'prizeName' => (string) ($prize['name'] ?? ''),
            'roleId' => $roleId,
            'roleName' => (string) ($prize['roleName'] ?? $prize['name'] ?? $roleId),
            'durationDays' => max(0, (int) ($prize['roleDurationDays'] ?? 0)),
            'grantStatus' => $roleId === '' ? 'grant_failed' : 'pending',
            'grantedAt' => null,
            'expireAt' => null,
            'revokedAt' => null,
            'lastAttemptAt' => null,
            'lastError' => $roleId === '' ? 'Prize role is not configured in prize setting.' : null,
            'metadataJson' => json_encode([
                'drawId' => $drawId,
                'prize' => $prize,
                'queuedBy' => 'gacha_complete',
                'queuedAt' => $now,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updateDate' => $now,
        ];

        $this->upsertGrantRow($row);
        $this->markGrantUpdate($guildId, $drawId, (string) $row['grantStatus'], [
            'roleId' => $roleId,
            'queued' => $roleId !== '',
        ]);

        return [
            'ok' => $roleId !== '',
            'queued' => $roleId !== '',
            'status' => (string) $row['grantStatus'],
            'grant' => $this->findByDrawId($guildId, $drawId),
        ];
    }

    public function processPending(int $limit = 20): array
    {
        self::ensureSchema();

        $limit = max(1, min(100, $limit));
        $rows = Database::fetchAll(
            'SELECT *
               FROM tbl_gacha_role_grant
              WHERE grantStatus = "pending"
              ORDER BY createDate ASC, gachaRoleGrantId ASC
              LIMIT ' . $limit
        );

        $result = ['checked' => count($rows), 'granted' => 0, 'covered' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $metadata = [];
            $decoded = json_decode((string) ($row['metadataJson'] ?? ''), true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }

            $draw = [
                'drawId' => (string) ($row['drawId'] ?? ''),
                'transactionGroupId' => (string) ($row['transactionGroupId'] ?? $row['drawId'] ?? ''),
                'prize' => is_array($metadata['prize'] ?? null) ? $metadata['prize'] : [
                    'id' => (string) ($row['prizeId'] ?? ''),
                    'name' => (string) ($row['prizeName'] ?? ''),
                    'type' => 'role',
                    'roleId' => (string) ($row['roleId'] ?? ''),
                    'roleName' => (string) ($row['roleName'] ?? ''),
                    'roleDurationDays' => max(0, (int) ($row['durationDays'] ?? 0)),
                ],
            ];

            try {
                $grant = $this->grantForDraw(
                    (string) ($row['guildId'] ?? ''),
                    (string) ($row['userId'] ?? ''),
                    $draw
                );
                $status = (string) ($grant['status'] ?? '');
                if ($status === 'granted') {
                    $result['granted']++;
                } elseif ($status === 'covered_by_permanent') {
                    $result['covered']++;
                } elseif ($status === 'not_role') {
                    $result['skipped']++;
                } else {
                    $result['failed']++;
                }
            } catch (Throwable $exception) {
                $result['failed']++;
                $this->upsertGrantRow(array_replace($row, [
                    'grantStatus' => 'grant_failed',
                    'lastAttemptAt' => date('Y-m-d H:i:s'),
                    'lastError' => $exception->getMessage(),
                    'updateDate' => date('Y-m-d H:i:s'),
                ]));
            }
        }

        return $result;
    }

    public function processExpired(int $limit = 20): array
    {
        self::ensureSchema();

        $limit = max(1, min(200, $limit));
        $now = date('Y-m-d H:i:s');
        $rows = Database::fetchAll(
            'SELECT *
               FROM tbl_gacha_role_grant
              WHERE grantStatus IN ("granted", "revoke_failed")
                AND expireAt IS NOT NULL
                AND expireAt <= :expireAt
              ORDER BY expireAt ASC, gachaRoleGrantId ASC
              LIMIT ' . $limit,
            ['expireAt' => $now]
        );

        $result = ['checked' => count($rows), 'revoked' => 0, 'covered' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $guildId = trim((string) ($row['guildId'] ?? ''));
            $userId = trim((string) ($row['userId'] ?? ''));
            $roleId = trim((string) ($row['roleId'] ?? ''));
            $drawId = trim((string) ($row['drawId'] ?? ''));
            $attemptAt = date('Y-m-d H:i:s');

            if ($guildId === '' || $userId === '' || $roleId === '') {
                $this->upsertGrantRow(array_replace($row, [
                    'grantStatus' => 'revoke_failed',
                    'lastAttemptAt' => $attemptAt,
                    'lastError' => 'Grant row is missing guildId/userId/roleId.',
                    'updateDate' => $attemptAt,
                ]));
                $this->markGrantUpdate($guildId, $drawId, 'revoke_failed', ['message' => 'Grant row is missing guildId/userId/roleId.']);
                $result['failed']++;
                continue;
            }

            if ($this->findActivePermanentGrant($guildId, $userId, $roleId, $drawId)) {
                $this->upsertGrantRow(array_replace($row, [
                    'grantStatus' => 'covered_by_permanent',
                    'lastAttemptAt' => $attemptAt,
                    'lastError' => null,
                    'updateDate' => $attemptAt,
                    'metadataJson' => $this->encodeMetadata($row['metadataJson'] ?? null, [
                        'resolvedByWorkerAt' => $attemptAt,
                        'stackRule' => 'covered_by_permanent',
                    ]),
                ]));
                $this->markGrantUpdate($guildId, $drawId, 'covered_by_permanent', ['roleId' => $roleId]);
                $result['covered']++;
                continue;
            }

            if ($this->hasFutureTemporaryCoverage($guildId, $userId, $roleId, $drawId, $attemptAt)) {
                $this->upsertGrantRow(array_replace($row, [
                    'grantStatus' => 'expired_covered',
                    'lastAttemptAt' => $attemptAt,
                    'lastError' => null,
                    'updateDate' => $attemptAt,
                    'metadataJson' => $this->encodeMetadata($row['metadataJson'] ?? null, [
                        'resolvedByWorkerAt' => $attemptAt,
                        'stackRule' => 'expired_covered',
                    ]),
                ]));
                $this->markGrantUpdate($guildId, $drawId, 'expired_covered', ['roleId' => $roleId]);
                $result['covered']++;
                continue;
            }

            $response = (new DiscordClient())->request(
                'DELETE',
                '/guilds/' . rawurlencode($guildId) . '/members/' . rawurlencode($userId) . '/roles/' . rawurlencode($roleId),
                [],
                $this->auditReason('revoke', $drawId, (string) ($row['roleName'] ?? $roleId))
            );

            if ($response['ok'] || (int) ($response['status'] ?? 0) === 404) {
                $this->syncMember($guildId, $userId);
                $this->markMemberRoleInactiveLocal($guildId, $userId, $roleId, $attemptAt);
                $this->upsertGrantRow(array_replace($row, [
                    'grantStatus' => 'revoked',
                    'revokedAt' => $attemptAt,
                    'lastAttemptAt' => $attemptAt,
                    'lastError' => null,
                    'updateDate' => $attemptAt,
                ]));
                $this->markGrantUpdate($guildId, $drawId, 'revoked', ['roleId' => $roleId]);
                $result['revoked']++;
                continue;
            }

            $message = $this->discordMessage($response);
            $this->upsertGrantRow(array_replace($row, [
                'grantStatus' => 'revoke_failed',
                'lastAttemptAt' => $attemptAt,
                'lastError' => $message,
                'updateDate' => $attemptAt,
            ]));
            $this->markGrantUpdate($guildId, $drawId, 'revoke_failed', ['message' => $message, 'roleId' => $roleId]);
            $result['failed']++;
        }

        return $result;
    }

    public function mapByDrawIds(string $guildId, array $drawIds): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $normalized = array_values(array_filter(array_map(static fn (mixed $drawId): string => trim((string) $drawId), $drawIds), static fn (string $drawId): bool => $drawId !== ''));
        if ($guildId === '' || $normalized === []) {
            return [];
        }

        $params = ['guildId' => $guildId];
        $placeholders = [];
        foreach ($normalized as $index => $drawId) {
            $key = 'drawId' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $drawId;
        }

        $rows = Database::fetchAll(
            'SELECT *
               FROM tbl_gacha_role_grant
              WHERE guildId = :guildId
                AND drawId IN (' . implode(',', $placeholders) . ')',
            $params
        );

        $map = [];
        foreach ($rows as $row) {
            $drawId = trim((string) ($row['drawId'] ?? ''));
            if ($drawId !== '') {
                $map[$drawId] = $row;
            }
        }
        return $map;
    }

    public function summarizeUserGrants(string $guildId, string $userId, int $limit = 100): array
    {
        self::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            return [];
        }

        $rows = Database::fetchAll(
            'SELECT gr.*, COALESCE(r.roleName, gr.roleName, gr.roleId) AS sourceRoleName
               FROM tbl_gacha_role_grant gr
          LEFT JOIN tbl_role r ON r.guildId = gr.guildId AND r.roleId = gr.roleId
              WHERE gr.guildId = :guildId AND gr.userId = :userId
              ORDER BY COALESCE(gr.grantedAt, gr.createDate) ASC, gr.gachaRoleGrantId ASC',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        $groups = [];
        foreach ($rows as $row) {
            $groupKey = trim((string) ($row['roleId'] ?? ''));
            if ($groupKey === '') {
                $groupKey = 'draw:' . trim((string) ($row['drawId'] ?? ''));
            }
            $groups[$groupKey][] = $row;
        }

        $out = [];
        foreach ($groups as $groupRows) {
            $out[] = $this->summarizeRoleGroup($groupRows);
        }

        usort($out, static function (array $left, array $right): int {
            return strcmp((string) ($right['latestDate'] ?? ''), (string) ($left['latestDate'] ?? ''));
        });

        return array_slice($out, 0, max(1, min(500, $limit)));
    }

    private function resolvedPrize(array $draw): array
    {
        $snapshotPrize = is_array($draw['prize'] ?? null) ? $draw['prize'] : [];
        $prizeId = trim((string) ($snapshotPrize['id'] ?? ''));
        $configPrize = $this->configPrizeById($prizeId);

        $payload = $snapshotPrize;
        if ($configPrize) {
            $tierId = trim((string) ($snapshotPrize['tierId'] ?? ''));
            $tier = $this->configTierById($tierId);
            if ($tier === [] && $tierId !== '') {
                $tier = ['id' => $tierId, 'tier' => $tierId];
            }
            if ($tier !== [] && class_exists('GachaConfigService')) {
                try {
                    $payload = GachaConfigService::publicPrizePayload(GachaConfigService::load(), $configPrize, $tier);
                } catch (Throwable) {
                    $payload = $snapshotPrize + $configPrize;
                }
            } else {
                $payload = $snapshotPrize + $configPrize;
            }
        }

        foreach (['roleDurationDays', 'roleDurationLabel', 'roleDurationOptions', 'roleDurationPoolLabel'] as $snapshotField) {
            if (array_key_exists($snapshotField, $snapshotPrize)) {
                $payload[$snapshotField] = $snapshotPrize[$snapshotField];
            }
        }

        $payload['roleId'] = trim((string) ($payload['roleId'] ?? ($configPrize['discordRoleId'] ?? '')));
        $payload['roleName'] = trim((string) ($payload['roleName'] ?? ''));
        if ($payload['roleName'] === '') {
            $payload['roleName'] = trim((string) ($payload['roleId'] ?? ($configPrize['discordRoleId'] ?? '')));
        }
        $payload['roleDurationDays'] = max(0, (int) ($payload['roleDurationDays'] ?? ($configPrize['roleDurationDays'] ?? 0)));
        $payload['type'] = (string) ($configPrize['type'] ?? ($payload['type'] ?? 'item'));

        return $payload;
    }

    private function configPrizeById(string $prizeId): array
    {
        if ($prizeId === '' || !class_exists('GachaConfigService')) {
            return [];
        }

        $config = GachaConfigService::load();
        foreach ($config['prizes'] ?? [] as $prize) {
            if (is_array($prize) && (string) ($prize['id'] ?? '') === $prizeId) {
                return $prize;
            }
        }
        return [];
    }

    private function configTierById(string $tierId): array
    {
        if ($tierId === '' || !class_exists('GachaConfigService')) {
            return [];
        }

        $config = GachaConfigService::load();
        foreach ($config['tiers'] ?? [] as $tier) {
            if (is_array($tier) && (string) ($tier['id'] ?? '') === $tierId) {
                return $tier;
            }
        }
        return [];
    }

    private function syncMember(string $guildId, string $userId): void
    {
        $response = (new DiscordClient())->request('GET', '/guilds/' . rawurlencode($guildId) . '/members/' . rawurlencode($userId));
        if (!$response['ok'] || !is_array($response['body'])) {
            return;
        }

        (new DiscordSyncService())->upsertMember($guildId, $response['body']);
    }

    private function findByDrawId(string $guildId, string $drawId): ?array
    {
        return Database::fetch(
            'SELECT *
               FROM tbl_gacha_role_grant
              WHERE guildId = :guildId AND drawId = :drawId
              LIMIT 1',
            ['guildId' => $guildId, 'drawId' => $drawId]
        );
    }

    private function summarizeRoleGroup(array $rows): array
    {
        usort($rows, static function (array $left, array $right): int {
            $leftDate = (string) ($left['grantedAt'] ?? $left['createDate'] ?? '');
            $rightDate = (string) ($right['grantedAt'] ?? $right['createDate'] ?? '');
            return strcmp($leftDate, $rightDate) ?: ((int) ($left['gachaRoleGrantId'] ?? 0) <=> (int) ($right['gachaRoleGrantId'] ?? 0));
        });

        $nowTs = time();
        $entries = [];
        $latestDate = '';
        $summaryName = '';
        $hasPermanent = false;
        $totalExpireTs = null;

        foreach ($rows as $row) {
            $status = trim((string) ($row['grantStatus'] ?? ''));
            $durationDays = max(0, (int) ($row['durationDays'] ?? 0));
            $grantedAt = (string) ($row['grantedAt'] ?? $row['createDate'] ?? '');
            $expireAt = (string) ($row['expireAt'] ?? '');
            $expireTs = $expireAt !== '' ? strtotime($expireAt) : false;
            $segmentStartTs = false;
            if ($durationDays > 0 && $expireTs !== false) {
                $segmentStartTs = $expireTs - ($durationDays * 86400);
            }

            $state = 'ended';
            if ($durationDays <= 0 && $this->isActiveStatus($status)) {
                $state = 'permanent';
                $hasPermanent = true;
            } elseif ($status === 'covered_by_permanent') {
                $state = 'covered_by_permanent';
            } elseif ($status === 'expired_covered') {
                $state = 'expired_covered';
            } elseif ($status === 'grant_failed') {
                $state = 'grant_failed';
            } elseif ($status === 'revoked') {
                $state = 'ended';
            } elseif ($this->isActiveStatus($status) && $expireTs !== false) {
                if ($expireTs <= $nowTs) {
                    $state = 'ended';
                } elseif ($segmentStartTs !== false && $segmentStartTs > $nowTs) {
                    $state = 'queued';
                } else {
                    $state = 'active';
                }
                $totalExpireTs = max((int) ($totalExpireTs ?? 0), $expireTs);
            }

            $displayName = trim((string) ($row['sourceRoleName'] ?? $row['roleName'] ?? $row['roleId'] ?? 'Role'));
            if ($displayName !== '') {
                $summaryName = $displayName;
            }
            if ($grantedAt !== '' && $grantedAt > $latestDate) {
                $latestDate = $grantedAt;
            }

            $segmentRemainingSeconds = 0;
            if ($state === 'active' && $expireTs !== false && $expireTs > $nowTs) {
                $segmentRemainingSeconds = $expireTs - $nowTs;
            } elseif ($state === 'queued') {
                $segmentRemainingSeconds = $durationDays * 86400;
            }

            $entries[] = [
                'drawId' => (string) ($row['drawId'] ?? ''),
                'roleId' => (string) ($row['roleId'] ?? ''),
                'roleName' => $displayName,
                'grantStatus' => $status,
                'state' => $state,
                'durationDays' => $durationDays,
                'grantedAt' => $grantedAt,
                'expireAt' => $expireAt !== '' ? $expireAt : null,
                'segmentStartAt' => $segmentStartTs !== false ? date('Y-m-d H:i:s', $segmentStartTs) : null,
                'remainingSeconds' => ($expireTs !== false && $expireTs > $nowTs) ? ($expireTs - $nowTs) : 0,
                'segmentRemainingSeconds' => $segmentRemainingSeconds,
                'sourceRoleName' => $displayName,
            ];
        }

        usort($entries, static function (array $left, array $right): int {
            return strcmp((string) ($right['grantedAt'] ?? ''), (string) ($left['grantedAt'] ?? ''));
        });

        $summaryState = 'expired';
        $totalRemainingSeconds = 0;
        if ($hasPermanent) {
            $summaryState = 'permanent';
        } elseif ($totalExpireTs !== null && $totalExpireTs > $nowTs) {
            $summaryState = 'stacked';
            $totalRemainingSeconds = $totalExpireTs - $nowTs;
        }

        return [
            'roleId' => (string) ($rows[count($rows) - 1]['roleId'] ?? ''),
            'roleName' => $summaryName !== '' ? $summaryName : 'Role',
            'latestDate' => $latestDate,
            'summaryState' => $summaryState,
            'totalExpireAt' => $totalExpireTs !== null ? date('Y-m-d H:i:s', $totalExpireTs) : null,
            'totalRemainingSeconds' => $totalRemainingSeconds,
            'hasPermanent' => $hasPermanent,
            'roundCount' => count($entries),
            'entries' => $entries,
        ];
    }

    private function findActivePermanentGrant(string $guildId, string $userId, string $roleId, ?string $excludeDrawId = null): ?array
    {
        $rows = $this->roleRows($guildId, $userId, $roleId, $excludeDrawId);
        foreach ($rows as $row) {
            $row = $this->normalizeActiveGrantRowIfStale($row);
            if ($this->isActiveStatus((string) ($row['grantStatus'] ?? '')) && max(0, (int) ($row['durationDays'] ?? 0)) <= 0) {
                return $row;
            }
        }
        return null;
    }

    private function currentTempStackEndTs(string $guildId, string $userId, string $roleId, ?string $excludeDrawId = null): ?int
    {
        $rows = $this->roleRows($guildId, $userId, $roleId, $excludeDrawId);
        $nowTs = time();
        $maxTs = null;
        foreach ($rows as $row) {
            $row = $this->normalizeActiveGrantRowIfStale($row);
            if (!$this->isActiveStatus((string) ($row['grantStatus'] ?? ''))) {
                continue;
            }
            if (max(0, (int) ($row['durationDays'] ?? 0)) <= 0) {
                continue;
            }
            $expireAt = trim((string) ($row['expireAt'] ?? ''));
            $expireTs = $expireAt !== '' ? strtotime($expireAt) : false;
            if ($expireTs === false || $expireTs <= $nowTs) {
                continue;
            }
            $maxTs = max((int) ($maxTs ?? 0), $expireTs);
        }
        return $maxTs;
    }

    private function hasFutureTemporaryCoverage(string $guildId, string $userId, string $roleId, string $drawId, string $now): bool
    {
        $rows = $this->roleRows($guildId, $userId, $roleId, $drawId);
        $nowTs = strtotime($now) ?: time();
        foreach ($rows as $row) {
            $row = $this->normalizeActiveGrantRowIfStale($row);
            if (!$this->isActiveStatus((string) ($row['grantStatus'] ?? ''))) {
                continue;
            }
            if (max(0, (int) ($row['durationDays'] ?? 0)) <= 0) {
                continue;
            }
            $expireAt = trim((string) ($row['expireAt'] ?? ''));
            $expireTs = $expireAt !== '' ? strtotime($expireAt) : false;
            if ($expireTs !== false && $expireTs > $nowTs) {
                return true;
            }
        }
        return false;
    }

    private function coverActiveTemporaryRowsByPermanent(string $guildId, string $userId, string $roleId, string $excludeDrawId, string $now): void
    {
        $rows = $this->roleRows($guildId, $userId, $roleId, $excludeDrawId);
        foreach ($rows as $row) {
            $row = $this->normalizeActiveGrantRowIfStale($row);
            if (!$this->isActiveStatus((string) ($row['grantStatus'] ?? ''))) {
                continue;
            }
            if (max(0, (int) ($row['durationDays'] ?? 0)) <= 0) {
                continue;
            }
            $this->upsertGrantRow(array_replace($row, [
                'grantStatus' => 'covered_by_permanent',
                'lastAttemptAt' => $now,
                'lastError' => null,
                'updateDate' => $now,
                'metadataJson' => $this->encodeMetadata($row['metadataJson'] ?? null, [
                    'coveredByPermanentAt' => $now,
                    'stackRule' => 'covered_by_permanent',
                ]),
            ]));
            $this->markGrantUpdate($guildId, (string) ($row['drawId'] ?? ''), 'covered_by_permanent', ['roleId' => $roleId]);
        }
    }

    private function roleRows(string $guildId, string $userId, string $roleId, ?string $excludeDrawId = null): array
    {
        $params = [
            'guildId' => $guildId,
            'userId' => $userId,
            'roleId' => $roleId,
        ];
        $sql = 'SELECT *
                  FROM tbl_gacha_role_grant
                 WHERE guildId = :guildId
                   AND userId = :userId
                   AND roleId = :roleId';
        if ($excludeDrawId !== null && $excludeDrawId !== '') {
            $sql .= ' AND drawId <> :excludeDrawId';
            $params['excludeDrawId'] = $excludeDrawId;
        }
        $sql .= ' ORDER BY COALESCE(grantedAt, createDate) ASC, gachaRoleGrantId ASC';
        return Database::fetchAll($sql, $params);
    }

    private function isActiveStatus(string $status): bool
    {
        return in_array($status, self::ACTIVE_GRANT_STATUSES, true);
    }

    private function normalizeActiveGrantRowIfStale(array $row): array
    {
        if (!$this->isActiveStatus((string) ($row['grantStatus'] ?? ''))) {
            return $row;
        }

        $guildId = trim((string) ($row['guildId'] ?? ''));
        $userId = trim((string) ($row['userId'] ?? ''));
        $roleId = trim((string) ($row['roleId'] ?? ''));
        if ($guildId === '' || $userId === '' || $roleId === '') {
            return $row;
        }

        $memberRole = $this->memberRoleState($guildId, $userId, $roleId);
        if (!empty($memberRole['isActive'])) {
            return $row;
        }

        $revokedAt = trim((string) ($memberRole['deleteDate'] ?? ''));
        if ($revokedAt === '') {
            $revokedAt = date('Y-m-d H:i:s');
        }
        $attemptAt = date('Y-m-d H:i:s');

        $updated = array_replace($row, [
            'grantStatus' => 'revoked',
            'revokedAt' => !empty($row['revokedAt']) ? $row['revokedAt'] : $revokedAt,
            'lastAttemptAt' => $attemptAt,
            'lastError' => null,
            'metadataJson' => $this->encodeMetadata($row['metadataJson'] ?? null, [
                'staleGrantRecoveredAt' => $attemptAt,
                'staleGrantReason' => 'member_role_inactive',
                'memberRoleDeleteDate' => $memberRole['deleteDate'] ?? null,
            ]),
            'updateDate' => $attemptAt,
        ]);

        $this->upsertGrantRow($updated);
        $this->markGrantUpdate(
            $guildId,
            (string) ($row['drawId'] ?? ''),
            'revoked',
            ['roleId' => $roleId, 'staleGrantRecovered' => true]
        );

        return $updated;
    }

    private function memberRoleState(string $guildId, string $userId, string $roleId): array
    {
        $cacheKey = $guildId . ':' . $userId . ':' . $roleId;
        if (array_key_exists($cacheKey, $this->memberRoleStateCache)) {
            return $this->memberRoleStateCache[$cacheKey];
        }

        $row = Database::fetch(
            'SELECT isActive, deleteDate
               FROM tbl_member_role
              WHERE guildId = :guildId
                AND userId = :userId
                AND roleId = :roleId
              LIMIT 1',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'roleId' => $roleId,
            ]
        );

        $state = is_array($row)
            ? [
                'isActive' => !empty($row['isActive']) ? 1 : 0,
                'deleteDate' => $row['deleteDate'] ?? null,
            ]
            : ['isActive' => 0, 'deleteDate' => null];

        $this->memberRoleStateCache[$cacheKey] = $state;
        return $state;
    }

    private function forgetMemberRoleState(string $guildId, string $userId, string $roleId): void
    {
        unset($this->memberRoleStateCache[$guildId . ':' . $userId . ':' . $roleId]);
    }

    private function markMemberRoleActiveLocal(string $guildId, string $userId, string $roleId, ?string $grantedAt = null): void
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        $roleId = trim($roleId);
        if ($guildId === '' || $userId === '' || $roleId === '') {
            return;
        }

        $grantedAt = trim((string) ($grantedAt ?? ''));
        if ($grantedAt === '') {
            $grantedAt = date('Y-m-d H:i:s');
        }

        Database::execute(
            'INSERT INTO tbl_member_role
                (guildId, userId, roleId, isActive, createDate, deleteDate)
             VALUES
                (:guildId, :userId, :roleId, 1, :grantedAt, NULL)
             ON DUPLICATE KEY UPDATE
                isActive = 1,
                deleteDate = NULL',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'roleId' => $roleId,
                'grantedAt' => $grantedAt,
            ]
        );

        $this->forgetMemberRoleState($guildId, $userId, $roleId);
    }

    private function markMemberRoleInactiveLocal(string $guildId, string $userId, string $roleId, ?string $revokedAt = null): void
    {
        $guildId = trim($guildId);
        $userId = trim($userId);
        $roleId = trim($roleId);
        if ($guildId === '' || $userId === '' || $roleId === '') {
            return;
        }

        $revokedAt = trim((string) ($revokedAt ?? ''));
        if ($revokedAt === '') {
            $revokedAt = date('Y-m-d H:i:s');
        }

        Database::execute(
            'INSERT INTO tbl_member_role
                (guildId, userId, roleId, isActive, createDate, deleteDate)
             VALUES
                (:guildId, :userId, :roleId, 0, :revokedAt, :revokedAt)
             ON DUPLICATE KEY UPDATE
                isActive = 0,
                deleteDate = VALUES(deleteDate)',
            [
                'guildId' => $guildId,
                'userId' => $userId,
                'roleId' => $roleId,
                'revokedAt' => $revokedAt,
            ]
        );

        $this->forgetMemberRoleState($guildId, $userId, $roleId);
    }

    private function encodeMetadata(?string $existingJson, array $patch): string
    {
        $data = [];
        if (is_string($existingJson) && $existingJson !== '') {
            $decoded = json_decode($existingJson, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        foreach ($patch as $key => $value) {
            $data[$key] = $value;
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function upsertGrantRow(array $row): void
    {
        Database::execute(
            'INSERT INTO tbl_gacha_role_grant
                (guildId, userId, drawId, transactionGroupId, prizeId, prizeName, roleId, roleName, durationDays, grantStatus, grantedAt, expireAt, revokedAt, lastAttemptAt, lastError, metadataJson, updateDate)
             VALUES
                (:guildId, :userId, :drawId, :transactionGroupId, :prizeId, :prizeName, :roleId, :roleName, :durationDays, :grantStatus, :grantedAt, :expireAt, :revokedAt, :lastAttemptAt, :lastError, :metadataJson, :updateDate)
             ON DUPLICATE KEY UPDATE
                userId = VALUES(userId),
                transactionGroupId = VALUES(transactionGroupId),
                prizeId = VALUES(prizeId),
                prizeName = VALUES(prizeName),
                roleId = VALUES(roleId),
                roleName = VALUES(roleName),
                durationDays = VALUES(durationDays),
                grantStatus = VALUES(grantStatus),
                grantedAt = COALESCE(VALUES(grantedAt), grantedAt),
                expireAt = VALUES(expireAt),
                revokedAt = VALUES(revokedAt),
                lastAttemptAt = VALUES(lastAttemptAt),
                lastError = VALUES(lastError),
                metadataJson = VALUES(metadataJson),
                updateDate = VALUES(updateDate)',
            [
                'guildId' => (string) ($row['guildId'] ?? ''),
                'userId' => (string) ($row['userId'] ?? ''),
                'drawId' => (string) ($row['drawId'] ?? ''),
                'transactionGroupId' => trim((string) ($row['transactionGroupId'] ?? '')),
                'prizeId' => (string) ($row['prizeId'] ?? ''),
                'prizeName' => (string) ($row['prizeName'] ?? ''),
                'roleId' => (string) ($row['roleId'] ?? ''),
                'roleName' => (string) ($row['roleName'] ?? ''),
                'durationDays' => max(0, (int) ($row['durationDays'] ?? 0)),
                'grantStatus' => (string) ($row['grantStatus'] ?? 'pending'),
                'grantedAt' => $row['grantedAt'] ?? null,
                'expireAt' => $row['expireAt'] ?? null,
                'revokedAt' => $row['revokedAt'] ?? null,
                'lastAttemptAt' => $row['lastAttemptAt'] ?? null,
                'lastError' => $row['lastError'] ?? null,
                'metadataJson' => $row['metadataJson'] ?? null,
                'updateDate' => $row['updateDate'] ?? date('Y-m-d H:i:s'),
            ]
        );
    }

    private function auditReason(string $action, string $drawId, string $label): string
    {
        return 'Dekpoke gacha ' . $action . ' draw ' . $drawId . ' · ' . trim($label);
    }

    private function discordMessage(array $response): string
    {
        $body = $response['body'] ?? null;
        if (is_array($body)) {
            return trim((string) ($body['message'] ?? 'Discord API error')) ?: 'Discord API error';
        }
        return trim((string) $body) ?: 'Discord API error';
    }

    private function markGrantUpdate(string $guildId, string $drawId, string $grantStatus, array $metadata = []): void
    {
        LiveUpdateService::markTopic(
            'gacha_report',
            ['scope' => 'gacha_role_grant', 'grantStatus' => $grantStatus] + $metadata,
            'draw',
            $drawId,
            $guildId
        );
    }
}
