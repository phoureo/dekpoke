<?php

declare(strict_types=1);

final class AttachmentStorageService
{
    public function resolveAttachment(int $messageAttachmentId): ?array
    {
        return Database::fetch(
            'SELECT ma.*, m.guildId, m.channelId, m.authorUserId
             FROM tbl_message_attachment ma
             INNER JOIN tbl_message m ON m.messageId = ma.messageId
             WHERE ma.messageAttachmentId = :id',
            ['id' => $messageAttachmentId]
        );
    }

    public function downloadQueued(int $limit = 20): array
    {
        if (!Bootstrap::config('discord.downloadAttachments', true)) {
            return ['downloaded' => 0, 'skipped' => true, 'reason' => 'Attachment download is disabled.'];
        }

        $effectiveLimit = max(1, min(100, $limit));

        $items = Database::fetchAll(
            'SELECT ma.*, m.guildId, m.channelId
             FROM tbl_message_attachment ma
             INNER JOIN tbl_message m ON m.messageId = ma.messageId
             WHERE ma.downloadStatus IN ("queued", "retry")
             ORDER BY ma.messageAttachmentId ASC
             LIMIT ' . $effectiveLimit
        );

        $downloaded = 0;
        $failed = 0;
        foreach ($items as $item) {
            try {
                $this->downloadOne($item);
                $downloaded++;
            } catch (Throwable $exception) {
                $failed++;
                Database::execute(
                    'UPDATE tbl_message_attachment
                     SET downloadStatus = "retry", metadataJson = :metadataJson, updateDate = :updateDate
                     WHERE messageAttachmentId = :messageAttachmentId',
                    [
                        'messageAttachmentId' => $item['messageAttachmentId'],
                        'metadataJson' => $this->mergeMetadata($item['metadataJson'] ?? null, ['downloadError' => $exception->getMessage()]),
                        'updateDate' => date('Y-m-d H:i:s'),
                    ]
                );
            }
        }

        $remaining = (int) (Database::fetch(
            'SELECT COUNT(*) AS total
             FROM tbl_message_attachment
             WHERE downloadStatus IN ("queued", "retry")'
        )['total'] ?? 0);

        $followUp = null;
        if ($remaining > 0) {
            $followUp = JobRunner::enqueueOnceQueued('download_attachments', ['limit' => $effectiveLimit], 25);
        }

        return [
            'downloaded' => $downloaded,
            'failed' => $failed,
            'scanned' => count($items),
            'remaining' => $remaining,
            'followUpQueued' => $followUp ? !$followUp['reused'] : false,
            'followUpJobId' => $followUp['syncJobId'] ?? null,
        ];
    }

    public function resolveLocalPath(int $messageAttachmentId): ?array
    {
        $row = $this->resolveAttachment($messageAttachmentId);

        if (!$row || !$row['localPath']) {
            return null;
        }

        $path = Bootstrap::rootPath($row['localPath']);
        if (!is_file($path)) {
            return null;
        }

        return ['path' => $path, 'row' => $row];
    }

    public function storeOneById(int $messageAttachmentId): ?array
    {
        $row = $this->resolveAttachment($messageAttachmentId);
        if (!$row) {
            return null;
        }

        $resolved = $this->resolveLocalPath($messageAttachmentId);
        if ($resolved) {
            return $resolved;
        }

        $this->downloadOne($row);
        return $this->resolveLocalPath($messageAttachmentId);
    }

    public function fetchRemoteBody(array $item): string
    {
        $url = (string) ($item['sourceUrl'] ?: $item['proxyUrl']);
        if ($url === '') {
            throw new RuntimeException('Attachment URL is empty.');
        }

        return $this->httpGet($url);
    }

    private function downloadOne(array $item): void
    {
        $url = (string) ($item['sourceUrl'] ?: $item['proxyUrl']);
        if ($url === '') {
            throw new RuntimeException('Attachment URL is empty.');
        }

        $safeFileName = $this->safeFileName((string) ($item['fileName'] ?: ('attachment-' . $item['attachmentId'])));
        $relativeDir = sprintf(
            'storage/private/attachments/%s/%s/%s/%s',
            date('Y/m'),
            $this->safeSegment((string) $item['guildId']),
            $this->safeSegment((string) $item['channelId']),
            $this->safeSegment((string) $item['messageId'])
        );
        $absoluteDir = Bootstrap::rootPath($relativeDir);
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('Cannot create attachment directory.');
        }

        $relativePath = $relativeDir . '/' . $this->safeSegment((string) $item['attachmentId']) . '-' . $safeFileName;
        $absolutePath = Bootstrap::rootPath($relativePath);
        $bytes = $this->httpGet($url);
        if ($bytes === '') {
            throw new RuntimeException('Attachment download returned empty body.');
        }

        if (file_put_contents($absolutePath, $bytes, LOCK_EX) === false) {
            throw new RuntimeException('Cannot write attachment file.');
        }

        $hash = hash_file('sha256', $absolutePath) ?: null;
        $fileSize = filesize($absolutePath) ?: strlen($bytes);
        Database::execute(
            'UPDATE tbl_message_attachment
             SET localPath = :localPath, downloadStatus = "stored", fileSize = :fileSize, updateDate = :updateDate
             WHERE messageAttachmentId = :messageAttachmentId',
            [
                'messageAttachmentId' => $item['messageAttachmentId'],
                'localPath' => $relativePath,
                'fileSize' => $fileSize,
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );

        Database::insert('tbl_file_blob', [
            'sourceType' => 'message_attachment',
            'sourceId' => (string) $item['messageAttachmentId'],
            'fileName' => $item['fileName'],
            'contentType' => $item['contentType'],
            'fileSize' => $fileSize,
            'localPath' => $relativePath,
            'sha256Hash' => $hash,
            'metadataJson' => $item['metadataJson'],
        ]);
    }

    private function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_USERAGENT => 'Dekpoke Orbit Console attachment archiver',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException($error ?: 'HTTP ' . $status);
        }

        return (string) $body;
    }

    private function mergeMetadata(?string $json, array $extra): string
    {
        $data = json_decode((string) $json, true);
        if (!is_array($data)) {
            $data = [];
        }

        return json_encode(array_merge($data, $extra), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function safeFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $fileName) ?: 'attachment.bin';
        return trim($fileName, '.-') ?: 'attachment.bin';
    }

    private function safeSegment(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_-]+/', '-', $value) ?: 'unknown';
    }
}
