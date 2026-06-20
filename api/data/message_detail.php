<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireUser();

$messageId = Input::str('messageId');
if (!$messageId) {
    Response::error('messageId is required.', 422);
}

$asOfInput = Input::str('asOf');
$asOf = null;
if ($asOfInput) {
    $asOfTs = strtotime($asOfInput);
    if ($asOfTs === false) {
        Response::error('Invalid asOf timestamp.', 422);
    }
    $asOf = date('Y-m-d H:i:s', $asOfTs);
}

$message = Database::fetch(
    'SELECT m.*, c.channelName, c.guildId AS channelGuildId, u.userName, u.globalName, u.avatarHash, m.guildId AS guildId,
            COALESCE(NULLIF(m.contentText, ""), latestRevision.contentText) AS archiveContentText,
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
      WHERE m.messageId = :messageId',
    ['messageId' => $messageId]
);
if (!$message) {
    Response::error('Message not found.', 404);
}

if ($asOf && !empty($message['messageCreateDate']) && strcmp((string) $message['messageCreateDate'], $asOf) > 0) {
    Response::error('Message is after selected timestamp.', 422, ['messageCreateDate' => $message['messageCreateDate'], 'asOf' => $asOf]);
}

AuditLogger::access(
    'message_detail_read',
    'message',
    $messageId,
    ['isDelete' => (int) $message['isDelete'], 'attachmentCount' => (int) $message['attachmentCount'], 'asOf' => $asOf],
    ((int) $message['isDelete'] === 1 || (int) $message['attachmentCount'] > 0) ? 'sensitive' : 'normal'
);

$message['guildAvatarHash'] = null;
$message['avatarUrl'] = DiscordAssets::avatar($message['authorUserId'], $message['avatarHash'] ?? null, 80);
$historicalContext = [
    'mode' => 'current',
    'asOf' => $asOf,
    'authorConfidence' => $asOf ? 'current_filtered' : 'current',
    'channelConfidence' => 'current',
];

$dateFilter = $asOf ? ['asOf' => $asOf] : [];
$revisions = Database::fetchAll(
    'SELECT *
       FROM tbl_message_revision
      WHERE messageId = :messageId' . ($asOf ? ' AND createDate <= :asOf' : '') . '
      ORDER BY messageRevisionId DESC',
    ['messageId' => $messageId] + $dateFilter
);
$attachments = Database::fetchAll(
    'SELECT *
       FROM tbl_message_attachment
      WHERE messageId = :messageId
      ORDER BY messageAttachmentId ASC',
    ['messageId' => $messageId]
);
$deletes = Database::fetchAll(
    'SELECT *
       FROM tbl_message_delete
      WHERE messageId = :messageId' . ($asOf ? ' AND createDate <= :asOf' : '') . '
      ORDER BY messageDeleteId DESC',
    ['messageId' => $messageId] + $dateFilter
);
$reactions = Database::fetchAll(
    'SELECT *
       FROM tbl_message_reaction
      WHERE messageId = :messageId' . ($asOf ? ' AND createDate <= :asOf' : '') . '
      ORDER BY createDate DESC
      LIMIT 100',
    ['messageId' => $messageId] + $dateFilter
);
$systemHistory = Database::fetchAll(
    'SELECT aa.*, au.displayName, au.discordUserName
       FROM tbl_admin_action aa
       LEFT JOIN tbl_admin_user au ON au.adminUserId = aa.adminUserId
      WHERE aa.targetType = "message" AND aa.targetId = :messageId' . ($asOf ? ' AND aa.createDate <= :asOf' : '') . '
      ORDER BY aa.adminActionId DESC
      LIMIT 40',
    ['messageId' => $messageId] + $dateFilter
);
$accessHistory = Database::fetchAll(
    'SELECT al.*, au.displayName, au.discordUserName
       FROM tbl_access_log al
       LEFT JOIN tbl_admin_user au ON au.adminUserId = al.adminUserId
      WHERE al.targetType = "message" AND al.targetId = :messageId' . ($asOf ? ' AND al.createDate <= :asOf' : '') . '
      ORDER BY al.accessLogId DESC
      LIMIT 40',
    ['messageId' => $messageId] + $dateFilter
);

Response::json([
    'ok' => true,
    'asOf' => $asOf,
    'message' => $message,
    'historicalContext' => $historicalContext,
    'revisions' => $revisions,
    'attachments' => $attachments,
    'deletes' => $deletes,
    'reactions' => $reactions,
    'systemHistory' => $systemHistory,
    'accessHistory' => $accessHistory,
]);
