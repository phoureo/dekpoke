<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireUser();

$channelId = Input::str('channelId');
if (!$channelId) {
    Response::error('channelId is required.', 422);
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

AuditLogger::access('channel_detail_read', 'channel', $channelId, ['view' => 'channel_drawer', 'asOf' => $asOf], 'sensitive');

$channel = Database::fetch(
    'SELECT c.*, parent.channelName AS categoryName
       FROM tbl_channel c
       LEFT JOIN tbl_channel parent ON parent.channelId = c.parentChannelId
      WHERE c.channelId = :channelId',
    ['channelId' => $channelId]
);
if (!$channel) {
    Response::error('Channel not found.', 404);
}

$channel['historicalContext'] = [
    'mode' => 'current',
    'asOf' => $asOf,
    'channelConfidence' => $asOf ? 'current_filtered' : 'current',
    'categoryConfidence' => 'current',
    'sourceType' => 'current',
    'sourceDate' => $channel['updateDate'] ?? null,
];

$dateParams = $asOf ? ['asOf' => $asOf] : [];
$windowSql = $asOf
    ? 'BETWEEN DATE_SUB(:asOf, INTERVAL 12 HOUR) AND :asOf'
    : 'BETWEEN DATE_SUB(NOW(), INTERVAL 12 HOUR) AND NOW()';

$metrics = [
    'messagesTotal' => (int) (Database::fetch(
        'SELECT COUNT(*) AS total
           FROM tbl_message
          WHERE channelId = :channelId' . ($asOf ? ' AND messageCreateDate <= :asOf' : ''),
        ['channelId' => $channelId] + $dateParams
    )['total'] ?? 0),
    'messages12h' => (int) (Database::fetch(
        'SELECT COUNT(*) AS total
           FROM tbl_message
          WHERE channelId = :channelId AND messageCreateDate ' . $windowSql,
        ['channelId' => $channelId] + $dateParams
    )['total'] ?? 0),
    'activeUsers12h' => (int) (Database::fetch(
        'SELECT COUNT(DISTINCT authorUserId) AS total
           FROM tbl_message
          WHERE channelId = :channelId AND messageCreateDate ' . $windowSql,
        ['channelId' => $channelId] + $dateParams
    )['total'] ?? 0),
    'events12h' => (int) (Database::fetch(
        'SELECT COUNT(*) AS total
           FROM tbl_raw_event
          WHERE channelId = :channelId AND eventDate ' . $windowSql,
        ['channelId' => $channelId] + $dateParams
    )['total'] ?? 0),
    'lastEventDate' => Database::fetch(
        'SELECT MAX(eventDate) AS value
           FROM tbl_raw_event
          WHERE channelId = :channelId' . ($asOf ? ' AND eventDate <= :asOf' : ''),
        ['channelId' => $channelId] + $dateParams
    )['value'] ?? null,
    'overwriteCount' => (int) (Database::fetch(
        'SELECT COUNT(*) AS total FROM tbl_channel_overwrite WHERE channelId = :channelId',
        ['channelId' => $channelId]
    )['total'] ?? 0),
];

$recentMessages = Database::fetchAll(
    'SELECT m.messageId,
            COALESCE(NULLIF(m.contentText, ""), latestRevision.contentText) AS archiveContentText,
            m.contentText, m.attachmentCount, m.isDelete, m.messageCreateDate,
            u.userId, u.userName, u.globalName, u.avatarHash,
            COALESCE(u.globalName, u.userName, m.authorUserId) AS displayName
       FROM tbl_message m
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
      WHERE m.channelId = :channelId' . ($asOf ? ' AND m.messageCreateDate <= :asOf' : '') . '
      ORDER BY m.messageCreateDate DESC
      LIMIT 40',
    ['channelId' => $channelId] + $dateParams
);

$recentActivity = Database::fetchAll(
    'SELECT re.*, u.userName, u.globalName, u.avatarHash,
            COALESCE(u.globalName, u.userName, re.userId) AS displayName
       FROM tbl_raw_event re
       LEFT JOIN tbl_user u ON u.userId = re.userId
      WHERE re.channelId = :channelId' . ($asOf ? ' AND re.eventDate <= :asOf' : '') . '
      ORDER BY re.eventDate DESC, re.rawEventId DESC
      LIMIT 60',
    ['channelId' => $channelId] + $dateParams
);

$voiceNow = Database::fetchAll(
    'SELECT vs.*, u.userName, u.globalName, u.avatarHash,
            m.nickName, m.guildAvatarHash,
            ' . ($asOf ? 'GREATEST(0, TIMESTAMPDIFF(SECOND, vs.startDate, :asOf))' : 'GREATEST(0, TIMESTAMPDIFF(SECOND, vs.startDate, NOW()))') . ' AS asOfDurationSeconds,
            COALESCE(m.nickName, u.globalName, u.userName, vs.userId) AS displayName
       FROM tbl_voice_session vs
       LEFT JOIN tbl_member m ON m.guildId = vs.guildId AND m.userId = vs.userId
       LEFT JOIN tbl_user u ON u.userId = vs.userId
      WHERE vs.channelId = :channelId
        AND ' . ($asOf ? 'vs.startDate <= :asOf AND (vs.endDate IS NULL OR vs.endDate > :asOf)' : 'vs.isClosed = 0') . '
      ORDER BY vs.startDate DESC',
    ['channelId' => $channelId] + $dateParams
);

$overwrites = Database::fetchAll(
    'SELECT co.*, r.roleName, u.userName, u.globalName
       FROM tbl_channel_overwrite co
       LEFT JOIN tbl_role r ON r.roleId = co.overwriteId
       LEFT JOIN tbl_user u ON u.userId = co.overwriteId
      WHERE co.channelId = :channelId
      ORDER BY co.channelOverwriteId ASC',
    ['channelId' => $channelId]
);

$children = Database::fetchAll(
    'SELECT channelId, channelName, channelType, channelPosition, isActive
       FROM tbl_channel
      WHERE parentChannelId = :channelId
      ORDER BY channelPosition ASC, channelName ASC',
    ['channelId' => $channelId]
);

$guildId = (string) ($channel['guildId'] ?? Bootstrap::config('discord.guildId', ''));
$decorateUser = static function (array $row, string $guildId, string $userKey = 'userId'): array {
    $userId = (string) ($row[$userKey] ?? '');
    $row['avatarUrl'] = DiscordAssets::guildAvatar($guildId, $userId, $row['guildAvatarHash'] ?? null, 64)
        ?: DiscordAssets::avatar($userId, $row['avatarHash'] ?? null, 64);
    $row['contextConfidence'] = 'current';
    return $row;
};
$decorateVoice = static function (array $row) use ($guildId, $decorateUser): array {
    $voiceMetadata = json_decode((string) ($row['metadataJson'] ?? '{}'), true);
    $voiceMetadata = is_array($voiceMetadata) ? $voiceMetadata : [];
    $row['durationIsTrusted'] = empty($voiceMetadata['isUncertain']) && empty($voiceMetadata['excludedFromRewards']);
    $row['uncertaintyReason'] = (string) ($voiceMetadata['uncertaintyReason'] ?? '');
    return $decorateUser($row, $guildId);
};

Response::json([
    'ok' => true,
    'channel' => $channel,
    'metrics' => $metrics,
    'recentMessages' => array_map(static fn (array $row): array => $decorateUser($row, $guildId), $recentMessages),
    'recentActivity' => array_map(static fn (array $row): array => $decorateUser($row, $guildId), $recentActivity),
    'voiceNow' => array_map($decorateVoice, $voiceNow),
    'typingNow' => [],
    'overwrites' => array_map(static function (array $overwrite): array {
        $overwrite['allowDecoded'] = DiscordPermissions::decode($overwrite['allowPermissions'] ?? null);
        $overwrite['denyDecoded'] = DiscordPermissions::decode($overwrite['denyPermissions'] ?? null);
        return $overwrite;
    }, $overwrites),
    'children' => $children,
    'asOf' => $asOf,
    'historicalContext' => $channel['historicalContext'],
]);
