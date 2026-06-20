<?php

declare(strict_types=1);

final class JobRunner
{
    public const BACKGROUND_JOB_TYPES = ['server_sync', 'bot_log_archive', 'message_backfill', 'canonical_bot_logs', 'download_attachments', 'earn_worker', 'backfill_all'];

    public static function enqueue(string $jobType, array $payload = [], int $priority = 100): int
    {
        $syncJobId = Database::insert('tbl_sync_job', [
            'jobType' => $jobType,
            'jobStatus' => 'queued',
            'priority' => $priority,
            'payloadJson' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        self::markJobUpdate($jobType, 'queued', $syncJobId);
        return $syncJobId;
    }

    public static function enqueueOnceRecent(string $jobType, array $payload = [], int $priority = 100, int $windowSeconds = 180): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $existing = Database::fetch(
            'SELECT syncJobId
             FROM tbl_sync_job
             WHERE jobType = :jobType
               AND jobStatus IN ("queued", "running")
               AND createDate >= :cutoff
             ORDER BY syncJobId DESC
             LIMIT 1',
            ['jobType' => $jobType, 'cutoff' => $cutoff]
        );
        if ($existing) {
            return (int) $existing['syncJobId'];
        }

        return self::enqueue($jobType, $payload, $priority);
    }

    public static function enqueueOnceOpen(string $jobType, array $payload = [], int $priority = 100): array
    {
        self::recoverStaleRunning(60, [$jobType]);

        $existing = Database::fetch(
            'SELECT syncJobId
             FROM tbl_sync_job
             WHERE jobType = :jobType
               AND jobStatus IN ("queued", "running")
             ORDER BY FIELD(jobStatus, "running", "queued"), priority ASC, syncJobId DESC
             LIMIT 1',
            ['jobType' => $jobType]
        );
        if ($existing) {
            return ['syncJobId' => (int) $existing['syncJobId'], 'reused' => true];
        }

        return ['syncJobId' => self::enqueue($jobType, $payload, $priority), 'reused' => false];
    }

    public static function enqueueOnceQueued(string $jobType, array $payload = [], int $priority = 100): array
    {
        $existing = Database::fetch(
            'SELECT syncJobId
             FROM tbl_sync_job
             WHERE jobType = :jobType
               AND jobStatus = "queued"
             ORDER BY priority ASC, syncJobId DESC
             LIMIT 1',
            ['jobType' => $jobType]
        );
        if ($existing) {
            return ['syncJobId' => (int) $existing['syncJobId'], 'reused' => true];
        }

        return ['syncJobId' => self::enqueue($jobType, $payload, $priority), 'reused' => false];
    }

    public static function claim(?array $jobTypes = null): ?array
    {
        $where = 'jobStatus = "queued"';
        $params = [];
        if ($jobTypes) {
            $placeholders = [];
            foreach ($jobTypes as $index => $jobType) {
                $key = 'jobType' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $jobType;
            }
            $where .= ' AND jobType IN (' . implode(',', $placeholders) . ')';
        }

        $job = Database::fetch(
            'SELECT * FROM tbl_sync_job WHERE ' . $where . ' ORDER BY priority ASC, syncJobId ASC LIMIT 1',
            $params
        );
        if (!$job) {
            return null;
        }

        Database::execute(
            'UPDATE tbl_sync_job SET jobStatus = "running", startDate = :startDate, attemptCount = attemptCount + 1 WHERE syncJobId = :syncJobId AND jobStatus = "queued"',
            ['syncJobId' => $job['syncJobId'], 'startDate' => date('Y-m-d H:i:s')]
        );

        return Database::fetch('SELECT * FROM tbl_sync_job WHERE syncJobId = :syncJobId', ['syncJobId' => $job['syncJobId']]);
    }

    public static function complete(int $syncJobId, array $result = []): void
    {
        Database::execute(
            'UPDATE tbl_sync_job SET jobStatus = "success", resultJson = :resultJson, finishDate = :finishDate WHERE syncJobId = :syncJobId',
            [
                'syncJobId' => $syncJobId,
                'resultJson' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'finishDate' => date('Y-m-d H:i:s'),
            ]
        );
        self::markJobUpdate(self::jobType($syncJobId), 'success', $syncJobId);
    }

    public static function fail(int $syncJobId, string $error): void
    {
        Database::execute(
            'UPDATE tbl_sync_job SET jobStatus = "failed", errorMessage = :errorMessage, finishDate = :finishDate WHERE syncJobId = :syncJobId',
            [
                'syncJobId' => $syncJobId,
                'errorMessage' => $error,
                'finishDate' => date('Y-m-d H:i:s'),
            ]
        );
        self::markJobUpdate(self::jobType($syncJobId), 'failed', $syncJobId);
    }

    public static function heartbeat(string $workerName, array $metadata = []): void
    {
        Database::execute(
            'INSERT INTO tbl_worker_heartbeat (workerName, heartbeatDate, metadataJson)
             VALUES (:workerName, :heartbeatDate, :metadataJson)
             ON DUPLICATE KEY UPDATE heartbeatDate = VALUES(heartbeatDate), metadataJson = VALUES(metadataJson)',
            [
                'workerName' => $workerName,
                'heartbeatDate' => date('Y-m-d H:i:s'),
                'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]
        );
    }

    public static function recoverQueue(int $staleMinutes = 60, ?array $jobTypes = null): array
    {
        $jobTypes ??= self::BACKGROUND_JOB_TYPES;
        $staleRunning = self::recoverStaleRunning($staleMinutes, $jobTypes);
        $duplicateQueued = self::cancelDuplicateQueued($jobTypes);

        if ($staleRunning > 0 || $duplicateQueued > 0) {
            LiveUpdateService::markTopic('backfill', [
                'scope' => 'queue_maintenance',
                'staleRunning' => $staleRunning,
                'duplicateQueued' => $duplicateQueued,
            ], 'sync_job', 'maintenance');
        }

        return [
            'staleRunningFailed' => $staleRunning,
            'duplicateQueuedCancelled' => $duplicateQueued,
            'queuedJobs' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_sync_job WHERE jobStatus = "queued"')['total'] ?? 0),
            'runningJobs' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_sync_job WHERE jobStatus = "running"')['total'] ?? 0),
        ];
    }

    public static function recoverStaleRunning(int $staleMinutes = 60, ?array $jobTypes = null): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - max(1, $staleMinutes) * 60);
        $where = 'jobStatus = "running" AND COALESCE(startDate, createDate) < :cutoff';
        $params = ['cutoff' => $cutoff];
        if ($jobTypes) {
            $where .= ' AND jobType IN (' . self::placeholders($jobTypes, $params, 'staleType') . ')';
        }

        return Database::execute(
            'UPDATE tbl_sync_job
             SET jobStatus = "failed",
                 errorMessage = :errorMessage,
                 finishDate = :finishDate
             WHERE ' . $where,
            $params + [
                'errorMessage' => 'Queue maintenance: stale running job was marked failed because no worker heartbeat/process was active.',
                'finishDate' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public static function cancelDuplicateQueued(?array $jobTypes = null): int
    {
        $params = [];
        $where = 'jobStatus = "queued"';
        if ($jobTypes) {
            $where .= ' AND jobType IN (' . self::placeholders($jobTypes, $params, 'dupType') . ')';
        }

        $rows = Database::fetchAll(
            'SELECT syncJobId, jobType
             FROM tbl_sync_job
             WHERE ' . $where . '
             ORDER BY jobType ASC, priority ASC, syncJobId DESC',
            $params
        );

        $keptByType = [];
        $cancelled = 0;
        foreach ($rows as $row) {
            $jobType = (string) $row['jobType'];
            $syncJobId = (int) $row['syncJobId'];
            if (!isset($keptByType[$jobType])) {
                $keptByType[$jobType] = $syncJobId;
                continue;
            }

            $cancelled += Database::execute(
                'UPDATE tbl_sync_job
                 SET jobStatus = "cancelled",
                     errorMessage = :errorMessage,
                     finishDate = :finishDate
                 WHERE syncJobId = :syncJobId
                   AND jobStatus = "queued"',
                [
                    'syncJobId' => $syncJobId,
                    'errorMessage' => 'Queue maintenance: duplicate queued job was cancelled; kept job #' . $keptByType[$jobType] . '.',
                    'finishDate' => date('Y-m-d H:i:s'),
                ]
            );
        }

        return $cancelled;
    }

    private static function markJobUpdate(string $jobType, string $status, int $syncJobId): void
    {
        $topic = in_array($jobType, ['backfill_all', 'bot_log_archive', 'message_backfill', 'canonical_bot_logs', 'download_attachments', 'earn_worker'], true) ? 'backfill' : 'admin';
        LiveUpdateService::markTopic($topic, ['jobType' => $jobType, 'jobStatus' => $status], 'sync_job', (string) $syncJobId);
    }

    private static function jobType(int $syncJobId): string
    {
        $job = Database::fetch('SELECT jobType FROM tbl_sync_job WHERE syncJobId = :syncJobId', ['syncJobId' => $syncJobId]);
        return (string) ($job['jobType'] ?? '');
    }

    private static function placeholders(array $values, array &$params, string $prefix): string
    {
        $placeholders = [];
        foreach (array_values($values) as $index => $value) {
            $key = $prefix . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = (string) $value;
        }
        return implode(',', $placeholders);
    }
}
