<?php

declare(strict_types=1);

final class MessageArchiveService
{
    public function upsertFromDiscord(array $message, bool $isBackfilled = false): array
    {
        $guildId = (string) ($message['guild_id'] ?? Bootstrap::config('discord.guildId', ''));
        $messageId = (string) ($message['id'] ?? '');
        $channelId = (string) ($message['channel_id'] ?? '');
        if ($messageId === '' || $channelId === '') {
            return ['skipped' => true, 'isNew' => false, 'isChanged' => false, 'attachmentCount' => 0];
        }

        $existing = Database::fetch('SELECT * FROM tbl_message WHERE messageId = :messageId', ['messageId' => $messageId]);
        $existingMetadata = $this->decodeMetadata($existing['metadataJson'] ?? null);
        $storedMessage = $this->mergeMessageMetadata($existingMetadata, $message);

        $author = is_array($storedMessage['author'] ?? null) ? $storedMessage['author'] : null;
        if (is_array($author) && !empty($author['id'])) {
            (new DiscordSyncService())->upsertUser($author);
        }

        $contentText = $this->resolveContentText($message, $existing, $existingMetadata);
        $existingContent = $existing['contentText'] ?? ($existingMetadata['content'] ?? null);
        $isChanged = $existing !== null
            && array_key_exists('content', $message)
            && $existingContent !== $contentText;
        if ($isChanged) {
            Database::insert('tbl_message_revision', [
                'messageId' => $messageId,
                'revisionType' => 'before_update',
                'contentText' => $existing['contentText'],
                'metadataJson' => $existing['metadataJson'],
            ]);
        }

        $attachments = $this->resolveArrayField($message, $existingMetadata, 'attachments');
        $mentions = $this->resolveArrayField($message, $existingMetadata, 'mentions');
        $reactions = $this->resolveArrayField($message, $existingMetadata, 'reactions');
        $messageCreateDate = DiscordSyncService::dt($storedMessage['timestamp'] ?? ($message['timestamp'] ?? null))
            ?? ($existing['messageCreateDate'] ?? null)
            ?? $this->snowflakeDate($messageId);
        $messageUpdateDate = $this->resolveMessageUpdateDate($message, $storedMessage, $existing);
        $parentMessageId = $this->resolveParentMessageId($message, $storedMessage, $existing);
        $messageType = array_key_exists('type', $message)
            ? $message['type']
            : ($existing['messageType'] ?? ($storedMessage['type'] ?? null));
        $isPinned = array_key_exists('pinned', $message)
            ? (!empty($message['pinned']) ? 1 : 0)
            : (int) ($existing['isPinned'] ?? (!empty($storedMessage['pinned']) ? 1 : 0));
        $isEdited = (!empty($message['edited_timestamp']) || (int) ($existing['isEdited'] ?? 0) === 1) ? 1 : 0;

        Database::execute(
            'INSERT INTO tbl_message
                (messageId, guildId, channelId, authorUserId, parentMessageId, messageType, contentText, cleanContentText, attachmentCount, reactionCount, mentionCount, isPinned, isEdited, isDelete, isBackfilled, metadataJson, messageCreateDate, messageUpdateDate, updateDate)
             VALUES
                (:messageId, :guildId, :channelId, :authorUserId, :parentMessageId, :messageType, :contentText, :cleanContentText, :attachmentCount, :reactionCount, :mentionCount, :isPinned, :isEdited, 0, :isBackfilled, :metadataJson, :messageCreateDate, :messageUpdateDate, :updateDate)
             ON DUPLICATE KEY UPDATE
                channelId = VALUES(channelId),
                authorUserId = VALUES(authorUserId),
                parentMessageId = VALUES(parentMessageId),
                messageType = VALUES(messageType),
                contentText = VALUES(contentText),
                cleanContentText = VALUES(cleanContentText),
                attachmentCount = VALUES(attachmentCount),
                reactionCount = VALUES(reactionCount),
                mentionCount = VALUES(mentionCount),
                isPinned = VALUES(isPinned),
                isEdited = VALUES(isEdited),
                isDelete = 0,
                isBackfilled = GREATEST(isBackfilled, VALUES(isBackfilled)),
                metadataJson = VALUES(metadataJson),
                messageUpdateDate = VALUES(messageUpdateDate),
                updateDate = VALUES(updateDate)',
            [
                'messageId' => $messageId,
                'guildId' => $guildId,
                'channelId' => $channelId,
                'authorUserId' => $author['id'] ?? ($existing['authorUserId'] ?? null),
                'parentMessageId' => $parentMessageId,
                'messageType' => $messageType,
                'contentText' => $contentText,
                'cleanContentText' => $contentText,
                'attachmentCount' => count($attachments),
                'reactionCount' => array_sum(array_map(static fn (array $reaction): int => (int) ($reaction['count'] ?? 0), $reactions)),
                'mentionCount' => count($mentions),
                'isPinned' => $isPinned,
                'isEdited' => $isEdited,
                'isBackfilled' => $isBackfilled ? 1 : 0,
                'metadataJson' => json_encode($storedMessage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'messageCreateDate' => $messageCreateDate,
                'messageUpdateDate' => $messageUpdateDate,
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );

        $this->syncAttachments($messageId, $attachments);
        LiveUpdateService::markTopic('message', [
            'channelId' => $channelId,
            'authorUserId' => $author['id'] ?? null,
            'isBackfilled' => $isBackfilled,
        ], 'message', $messageId, $guildId);

        return [
            'skipped' => false,
            'isNew' => !$existing,
            'isChanged' => (bool) $isChanged,
            'attachmentCount' => count($attachments),
        ];
    }

    private function decodeMetadata(?string $metadataJson): array
    {
        if ($metadataJson === null || $metadataJson === '') {
            return [];
        }

        $decoded = json_decode($metadataJson, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function mergeMessageMetadata(array $existingMetadata, array $incomingMessage): array
    {
        $merged = $existingMetadata;
        foreach ($incomingMessage as $key => $value) {
            $merged[$key] = $value;
        }

        return $merged;
    }

    private function resolveContentText(array $incomingMessage, ?array $existingMessage, array $existingMetadata): ?string
    {
        if (array_key_exists('content', $incomingMessage)) {
            $content = $incomingMessage['content'];
            if ($content === null) {
                return null;
            }

            return (string) $content;
        }

        $fallback = $existingMessage['contentText'] ?? ($existingMetadata['content'] ?? null);
        return $fallback === null ? null : (string) $fallback;
    }

    private function resolveArrayField(array $incomingMessage, array $existingMetadata, string $field): array
    {
        if (array_key_exists($field, $incomingMessage)) {
            return is_array($incomingMessage[$field]) ? $incomingMessage[$field] : [];
        }

        $fallback = $existingMetadata[$field] ?? [];
        return is_array($fallback) ? $fallback : [];
    }

    private function resolveParentMessageId(array $incomingMessage, array $storedMessage, ?array $existingMessage): ?string
    {
        if (array_key_exists('message_reference', $incomingMessage) && is_array($incomingMessage['message_reference'])) {
            return isset($incomingMessage['message_reference']['message_id'])
                ? (string) $incomingMessage['message_reference']['message_id']
                : null;
        }

        if (is_array($storedMessage['message_reference'] ?? null) && isset($storedMessage['message_reference']['message_id'])) {
            return (string) $storedMessage['message_reference']['message_id'];
        }

        return isset($existingMessage['parentMessageId']) && $existingMessage['parentMessageId'] !== ''
            ? (string) $existingMessage['parentMessageId']
            : null;
    }

    private function resolveMessageUpdateDate(array $incomingMessage, array $storedMessage, ?array $existingMessage): ?string
    {
        if (array_key_exists('edited_timestamp', $incomingMessage)) {
            return DiscordSyncService::dt($incomingMessage['edited_timestamp'] ?? ($storedMessage['timestamp'] ?? ($incomingMessage['timestamp'] ?? null)));
        }

        if (!empty($existingMessage['messageUpdateDate'])) {
            return $existingMessage['messageUpdateDate'];
        }

        return DiscordSyncService::dt($storedMessage['edited_timestamp'] ?? ($storedMessage['timestamp'] ?? ($incomingMessage['timestamp'] ?? null)));
    }

    public function markDeleted(string $guildId, string $channelId, string $messageId, array $payload = [], bool $isBulk = false): void
    {
        $this->ensureMessageStub($guildId, $channelId, $messageId, $payload);
        Database::execute(
            'UPDATE tbl_message SET isDelete = 1, deleteDate = :deleteDate, updateDate = :updateDate WHERE messageId = :messageId',
            ['messageId' => $messageId, 'deleteDate' => date('Y-m-d H:i:s'), 'updateDate' => date('Y-m-d H:i:s')]
        );
        Database::execute(
            'INSERT INTO tbl_message_delete
                (messageId, guildId, channelId, deleteSource, isBulk, metadataJson)
             VALUES
                (:messageId, :guildId, :channelId, :deleteSource, :isBulk, :metadataJson)
             ON DUPLICATE KEY UPDATE metadataJson = VALUES(metadataJson)',
            [
                'messageId' => $messageId,
                'guildId' => $guildId,
                'channelId' => $channelId,
                'deleteSource' => 'gateway',
                'isBulk' => $isBulk ? 1 : 0,
                'metadataJson' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]
        );
        LiveUpdateService::markTopic('message', [
            'channelId' => $channelId,
            'isBulk' => $isBulk,
            'isDelete' => true,
        ], 'message', $messageId, $guildId);
    }

    public function reaction(string $eventType, array $payload): void
    {
        $messageId = (string) ($payload['message_id'] ?? '');
        $userId = (string) ($payload['user_id'] ?? '');
        if ($messageId === '' || $userId === '') {
            return;
        }

        $this->ensureMessageStub(
            (string) ($payload['guild_id'] ?? Bootstrap::config('discord.guildId', '')),
            (string) ($payload['channel_id'] ?? ''),
            $messageId,
            $payload
        );
        $emoji = $payload['emoji']['id'] ?? $payload['emoji']['name'] ?? 'unknown';
        $isActive = $eventType === 'MESSAGE_REACTION_ADD' ? 1 : 0;
        Database::execute(
            'INSERT INTO tbl_message_reaction (messageId, userId, emojiKey, isActive, deleteDate)
             VALUES (:messageId, :userId, :emojiKey, :isActive, :deleteDate)
             ON DUPLICATE KEY UPDATE isActive = VALUES(isActive), deleteDate = VALUES(deleteDate)',
            [
                'messageId' => $messageId,
                'userId' => $userId,
                'emojiKey' => (string) $emoji,
                'isActive' => $isActive,
                'deleteDate' => $isActive ? null : date('Y-m-d H:i:s'),
            ]
        );
        LiveUpdateService::markTopic('message', [
            'channelId' => $payload['channel_id'] ?? null,
            'reactionEvent' => $eventType,
            'userId' => $userId,
            'emojiKey' => (string) $emoji,
        ], 'message', $messageId, (string) ($payload['guild_id'] ?? Bootstrap::config('discord.guildId', '')));
    }

    private function ensureMessageStub(string $guildId, string $channelId, string $messageId, array $metadata = []): void
    {
        if ($messageId === '' || $channelId === '') {
            return;
        }

        Database::execute(
            'INSERT IGNORE INTO tbl_message
                (messageId, guildId, channelId, metadataJson, messageCreateDate, createDate)
             VALUES
                (:messageId, :guildId, :channelId, :metadataJson, :messageCreateDate, :createDate)',
            [
                'messageId' => $messageId,
                'guildId' => $guildId,
                'channelId' => $channelId,
                'metadataJson' => json_encode(['stub' => true, 'source' => $metadata], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'messageCreateDate' => $this->snowflakeDate($messageId),
                'createDate' => date('Y-m-d H:i:s'),
            ]
        );
    }

    private function syncAttachments(string $messageId, array $attachments): void
    {
        $hasAttachments = false;
        $attachmentRowIds = [];
        foreach ($attachments as $attachment) {
            if (empty($attachment['id'])) {
                continue;
            }
            $hasAttachments = true;
            Database::execute(
                'INSERT INTO tbl_message_attachment
                    (attachmentId, messageId, fileName, contentType, fileSize, sourceUrl, proxyUrl, downloadStatus, metadataJson, updateDate)
                 VALUES
                    (:attachmentId, :messageId, :fileName, :contentType, :fileSize, :sourceUrl, :proxyUrl, "queued", :metadataJson, :updateDate)
                 ON DUPLICATE KEY UPDATE
                    fileName = VALUES(fileName),
                    contentType = VALUES(contentType),
                    fileSize = VALUES(fileSize),
                    sourceUrl = VALUES(sourceUrl),
                    proxyUrl = VALUES(proxyUrl),
                    metadataJson = VALUES(metadataJson),
                    updateDate = VALUES(updateDate)',
                [
                    'attachmentId' => (string) $attachment['id'],
                    'messageId' => $messageId,
                    'fileName' => $attachment['filename'] ?? null,
                    'contentType' => $attachment['content_type'] ?? null,
                    'fileSize' => $attachment['size'] ?? null,
                    'sourceUrl' => $attachment['url'] ?? null,
                    'proxyUrl' => $attachment['proxy_url'] ?? null,
                    'metadataJson' => json_encode($attachment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'updateDate' => date('Y-m-d H:i:s'),
                ]
            );

            $storedAttachment = Database::fetch(
                'SELECT messageAttachmentId
                 FROM tbl_message_attachment
                 WHERE attachmentId = :attachmentId',
                ['attachmentId' => (string) $attachment['id']]
            );
            if ($storedAttachment && !empty($storedAttachment['messageAttachmentId'])) {
                $attachmentRowIds[] = (int) $storedAttachment['messageAttachmentId'];
            }
        }

        if ($attachmentRowIds !== []) {
            $storage = new AttachmentStorageService();
            foreach (array_values(array_unique($attachmentRowIds)) as $attachmentRowId) {
                try {
                    $storage->storeOneById($attachmentRowId);
                } catch (Throwable) {
                    // Fall back to the queued downloader when immediate archive is not possible.
                }
            }
        }

        if ($hasAttachments) {
            JobRunner::enqueueOnceQueued('download_attachments', ['limit' => 30], 25);
        }
    }

    private function snowflakeDate(string $snowflake): ?string
    {
        if (!ctype_digit($snowflake)) {
            return null;
        }

        $timestamp = ((int) ((int) $snowflake / 4194304) + 1420070400000) / 1000;
        return date('Y-m-d H:i:s', (int) $timestamp);
    }
}
