<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireUser();
AuditLogger::access('message_archive_read', 'page', 'messages', AuditLogger::requestPayload(), 'sensitive');

$guildId = (string) Bootstrap::config('discord.guildId', '');
$page = max(1, Input::int('page', 1));
$pageSize = max(10, min(100, Input::int('pageSize', 50)));
$offset = ($page - 1) * $pageSize;
$sort = Input::str('sort', 'messageCreateDate') ?? 'messageCreateDate';
$dir = strtolower(Input::str('dir', 'desc') ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$sortMap = [
    'messageCreateDate' => 'm.messageCreateDate',
    'channelName' => 'c.channelName',
    'authorName' => 'displayName',
    'contentText' => 'm.contentText',
    'isDelete' => 'm.isDelete',
    'attachmentCount' => 'm.attachmentCount',
    'reactionCount' => 'm.reactionCount',
    'deleteDate' => 'm.deleteDate',
];
$orderBy = $sortMap[$sort] ?? 'm.messageCreateDate';

$where = ['m.guildId = :guildId'];
$params = ['guildId' => $guildId];

foreach (['channelId', 'userId'] as $key) {
    $value = Input::str($key);
    if ($value) {
        $column = $key === 'userId' ? 'm.authorUserId' : 'm.channelId';
        $where[] = $column . ' = :' . $key;
        $params[$key] = $value;
    }
}

$authorKind = Input::str('authorKind', 'human') ?? 'human';
if ($authorKind === 'human') {
    $where[] = 'COALESCE(u.isBot, 0) = 0';
} elseif ($authorKind === 'bot') {
    $where[] = 'u.isBot = 1';
}

$q = Input::str('q');
if ($q) {
    $q = trim($q);
    if (preg_match('/^\d{8,}$/', $q)) {
        $where[] = '(m.messageId = :qMessageId OR m.authorUserId = :qAuthorUserId OR m.parentMessageId = :qParentMessageId OR c.channelId = :qChannelId)';
        $params['qMessageId'] = $q;
        $params['qAuthorUserId'] = $q;
        $params['qParentMessageId'] = $q;
        $params['qChannelId'] = $q;
    } else {
        $where[] = '(
            MATCH(m.contentText) AGAINST(:qFulltext IN BOOLEAN MODE)
            OR m.contentText LIKE :qContentLike
            OR c.channelName LIKE :qChannelLike
            OR COALESCE(u.globalName, u.userName, "") LIKE :qAuthorLike
            OR m.authorUserId LIKE :qUserIdLike
        )';
        $params['qFulltext'] = $q . '*';
        $params['qContentLike'] = '%' . $q . '%';
        $params['qChannelLike'] = '%' . $q . '%';
        $params['qAuthorLike'] = '%' . $q . '%';
        $params['qUserIdLike'] = '%' . $q . '%';
    }
}

$dateFrom = Input::str('dateFrom');
if ($dateFrom) {
    $where[] = 'm.messageCreateDate >= :dateFrom';
    $params['dateFrom'] = $dateFrom . ' 00:00:00';
}
$dateTo = Input::str('dateTo');
if ($dateTo) {
    $where[] = 'm.messageCreateDate <= :dateTo';
    $params['dateTo'] = $dateTo . ' 23:59:59';
}

$flagMap = [
    'deleted' => 'm.isDelete = 1',
    'edited' => 'm.isEdited = 1',
    'attachment' => 'm.attachmentCount > 0',
    'link' => 'm.contentText REGEXP "https?://"',
    'reply' => 'm.parentMessageId IS NOT NULL',
    'reaction' => 'EXISTS (SELECT 1 FROM tbl_message_reaction mr WHERE mr.messageId = m.messageId)',
    'poll' => 'EXISTS (SELECT 1 FROM tbl_poll_vote pv WHERE pv.messageId = m.messageId)',
];
foreach ($flagMap as $key => $sql) {
    if (Input::bool($key)) {
        $where[] = $sql;
    }
}

$whereSql = implode(' AND ', $where);
$total = Database::fetch(
    'SELECT COUNT(*) AS total
     FROM tbl_message m
     LEFT JOIN tbl_channel c ON c.channelId = m.channelId
     LEFT JOIN tbl_user u ON u.userId = m.authorUserId
     WHERE ' . $whereSql,
    $params
);

$rows = Database::fetchAll(
    'SELECT m.*, c.channelName, u.userName, u.globalName, u.avatarHash,
            COALESCE(NULLIF(m.contentText, ""), latestRevision.contentText) AS archiveContentText,
            firstAttachment.messageAttachmentId AS firstAttachmentId,
            firstAttachment.fileName AS firstAttachmentFileName,
            firstAttachment.contentType AS firstAttachmentContentType,
            firstAttachment.downloadStatus AS firstAttachmentDownloadStatus,
            COALESCE(u.globalName, u.userName, m.authorUserId) AS displayName
     FROM tbl_message m
     LEFT JOIN tbl_channel c ON c.channelId = m.channelId
     LEFT JOIN tbl_user u ON u.userId = m.authorUserId
     LEFT JOIN (
        SELECT mr.messageId, mr.contentText
        FROM tbl_message_revision mr
        INNER JOIN (
            SELECT messageId, MAX(messageRevisionId) AS messageRevisionId
            FROM tbl_message_revision
            WHERE contentText IS NOT NULL AND contentText <> ""
            GROUP BY messageId
        ) latest ON latest.messageRevisionId = mr.messageRevisionId
     ) latestRevision ON latestRevision.messageId = m.messageId
     LEFT JOIN (
        SELECT ma.messageId, ma.messageAttachmentId, ma.fileName, ma.contentType, ma.downloadStatus
        FROM tbl_message_attachment ma
        INNER JOIN (
            SELECT messageId, MIN(messageAttachmentId) AS messageAttachmentId
            FROM tbl_message_attachment
            GROUP BY messageId
        ) firstAttachmentMeta ON firstAttachmentMeta.messageAttachmentId = ma.messageAttachmentId
     ) firstAttachment ON firstAttachment.messageId = m.messageId
     WHERE ' . $whereSql . '
     ORDER BY ' . $orderBy . ' ' . $dir . '
     LIMIT ' . $pageSize . ' OFFSET ' . $offset,
    $params
);

$channels = Database::fetchAll(
    'SELECT channelId, channelName, channelType
     FROM tbl_channel
     WHERE guildId = :guildId AND isActive = 1
     ORDER BY channelPosition ASC, channelName ASC',
    ['guildId' => $guildId]
);

$messagePayloadHealth = Database::fetch(
    'SELECT COUNT(*) AS total,
            SUM(
                CASE
                    WHEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(eventPayloadJson, "$.content")), "") = ""
                     AND COALESCE(JSON_LENGTH(JSON_EXTRACT(eventPayloadJson, "$.attachments")), 0) = 0
                    THEN 1 ELSE 0
                END
            ) AS emptyPayloadCount
     FROM tbl_raw_event
     WHERE guildId = :guildId
       AND eventType = "MESSAGE_CREATE"
       AND createDate >= DATE_SUB(NOW(), INTERVAL 20 MINUTE)',
    ['guildId' => $guildId]
);

$messagePayloadTotal = (int) ($messagePayloadHealth['total'] ?? 0);
$messagePayloadEmpty = (int) ($messagePayloadHealth['emptyPayloadCount'] ?? 0);

Response::json([
    'ok' => true,
    'page' => $page,
    'pageSize' => $pageSize,
    'total' => (int) ($total['total'] ?? 0),
    'channels' => $channels,
    'diagnostics' => [
        'recentMessageCreateCount' => $messagePayloadTotal,
        'recentEmptyPayloadCount' => $messagePayloadEmpty,
        'messageContentLikelyUnavailable' => $messagePayloadTotal >= 3 && $messagePayloadEmpty === $messagePayloadTotal,
    ],
    'rows' => array_map(static function (array $row): array {
        $row['avatarUrl'] = DiscordAssets::avatar($row['authorUserId'], $row['avatarHash'], 64);
        return $row;
    }, $rows),
]);
