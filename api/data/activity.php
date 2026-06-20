<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireUser();
AuditLogger::access('activity_read', 'page', 'activity');

$guildId = (string) Bootstrap::config('discord.guildId', '');
$selectedGroups = Input::get('type', []);
if (!is_array($selectedGroups)) {
    $selectedGroups = $selectedGroups === null || $selectedGroups === '' ? [] : [$selectedGroups];
}
$selectedEventTypes = Input::get('eventType', []);
if (!is_array($selectedEventTypes)) {
    $selectedEventTypes = $selectedEventTypes === null || $selectedEventTypes === '' ? [] : [$selectedEventTypes];
}

$selectedGroups = array_values(array_unique(array_filter(array_map(
    static fn (mixed $value): string => strtolower(trim((string) $value)),
    $selectedGroups
), static fn (string $value): bool => $value !== '')));
$selectedEventTypes = array_values(array_unique(array_filter(array_map(
    static fn (mixed $value): string => strtoupper(trim((string) $value)),
    $selectedEventTypes
), static fn (string $value): bool => $value !== '' && GatewayEventIngestService::isOfficialEventType($value))));

$allowedGroups = ['voice', 'member', 'role', 'channel', 'guild', 'audit', 'invite', 'message'];
$selectedGroups = array_values(array_intersect($allowedGroups, $selectedGroups));
$page = max(1, Input::int('page', 1));
$pageSize = max(20, min(300, Input::int('pageSize', 100)));
$offset = ($page - 1) * $pageSize;
$q = trim((string) Input::str('q'));

$groupFilters = [
    'voice' => 're.eventType = "VOICE_STATE_UPDATE"',
    'member' => '(re.eventType LIKE "GUILD_MEMBER_%" OR re.eventType LIKE "GUILD_BAN_%")',
    'role' => 're.eventType LIKE "GUILD_ROLE_%"',
    'channel' => '(re.eventType LIKE "CHANNEL_%" OR re.eventType LIKE "THREAD_%" OR re.eventType LIKE "STAGE_INSTANCE_%")',
    'guild' => '(re.eventType LIKE "GUILD_%" AND re.eventType NOT LIKE "GUILD_MEMBER_%" AND re.eventType NOT LIKE "GUILD_ROLE_%" AND re.eventType <> "GUILD_AUDIT_LOG_ENTRY_CREATE" AND re.eventType NOT LIKE "GUILD_BAN_%")',
    'audit' => 're.eventType = "GUILD_AUDIT_LOG_ENTRY_CREATE"',
    'invite' => '(re.eventType LIKE "INVITE_%" OR inviteAttr.joinInviteAttributionId IS NOT NULL)',
    'message' => 're.eventType LIKE "MESSAGE_%"',
];

$where = ['(re.guildId = :guildId OR re.guildId IS NULL)'];
$params = ['guildId' => $guildId];

if ($selectedGroups !== []) {
    $where[] = '(' . implode(' OR ', array_map(static fn (string $group): string => $groupFilters[$group], $selectedGroups)) . ')';
}
if ($selectedEventTypes !== []) {
    $placeholders = [];
    foreach ($selectedEventTypes as $index => $eventType) {
        $key = 'eventType' . $index;
        $placeholders[] = ':' . $key;
        $params[$key] = $eventType;
    }
    $where[] = 're.eventType IN (' . implode(',', $placeholders) . ')';
}
if ($q !== '') {
    $qLike = '%' . $q . '%';
    $where[] = '(
        re.eventType LIKE :q
        OR re.sourceName LIKE :q
        OR COALESCE(c.channelName, "") LIKE :q
        OR COALESCE(u.userName, "") LIKE :q
        OR COALESCE(u.globalName, "") LIKE :q
        OR COALESCE(m.nickName, "") LIKE :q
        OR COALESCE(inviteAttr.inviterName, "") LIKE :q
        OR COALESCE(inviteAttr.inviteType, "") LIKE :q
        OR COALESCE(re.targetId, "") LIKE :q
        OR COALESCE(re.contextJson, "") LIKE :q
        OR COALESCE(re.eventPayloadJson, "") LIKE :q
    )';
    $params['q'] = $qLike;
}

$whereSql = implode(' AND ', $where);
$total = Database::fetch(
    'SELECT COUNT(*) AS total
     FROM tbl_raw_event re
     LEFT JOIN tbl_channel c ON c.channelId = COALESCE(re.channelId, JSON_UNQUOTE(JSON_EXTRACT(re.eventPayloadJson, "$.old_channel_id")))
     LEFT JOIN tbl_user u ON u.userId = re.userId
     LEFT JOIN tbl_member m ON m.guildId = re.guildId AND m.userId = re.userId
     LEFT JOIN tbl_member_join_invite_attribution inviteAttr ON inviteAttr.rawEventId = re.rawEventId
     WHERE ' . $whereSql,
    $params
);

$timeline = Database::fetchAll(
    'SELECT re.*,
            COALESCE(c.channelName, previousChannel.channelName) AS channelName,
            u.userName,
            u.globalName,
            u.avatarHash,
            COALESCE(m.nickName, u.globalName, u.userName, re.userId) AS displayName,
            sourceMessage.contentText AS sourceMessageContentText,
            sourceMessage.metadataJson AS sourceMessageMetadataJson,
            sourceMessage.messageCreateDate AS sourceMessageCreateDate,
            inviteAttr.joinInviteAttributionId,
            inviteAttr.inviteType,
            inviteAttr.inviterUserId,
            inviteAttr.inviterName,
            inviteAttr.inviteCount,
            inviteAttr.matchStatus AS inviteMatchStatus,
            inviteAttr.sourceMessageId AS inviteSourceMessageId,
            inviteAttr.sourceMessageDate AS inviteSourceMessageDate
     FROM tbl_raw_event re
     LEFT JOIN tbl_channel c ON c.channelId = re.channelId
     LEFT JOIN tbl_channel previousChannel ON previousChannel.channelId = JSON_UNQUOTE(JSON_EXTRACT(re.eventPayloadJson, "$.old_channel_id"))
     LEFT JOIN tbl_user u ON u.userId = re.userId
     LEFT JOIN tbl_member m ON m.guildId = re.guildId AND m.userId = re.userId
     LEFT JOIN tbl_message sourceMessage ON sourceMessage.messageId = JSON_UNQUOTE(JSON_EXTRACT(re.contextJson, "$.sourceMessageId"))
     LEFT JOIN tbl_member_join_invite_attribution inviteAttr ON inviteAttr.rawEventId = re.rawEventId
     WHERE ' . $whereSql . '
     ORDER BY re.eventDate DESC, re.rawEventId DESC
     LIMIT ' . $pageSize . ' OFFSET ' . $offset,
    $params
);

$events = Database::fetchAll(
    'SELECT re.eventType, COUNT(*) AS total
     FROM tbl_raw_event re
     WHERE (re.guildId = :guildId OR re.guildId IS NULL)
       AND re.eventDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
     GROUP BY re.eventType
     ORDER BY total DESC, re.eventType ASC
     LIMIT 40',
    ['guildId' => $guildId]
);

$eventTypes = Database::fetchAll(
    'SELECT eventType, COUNT(*) AS total
     FROM tbl_raw_event
     WHERE guildId = :guildId OR guildId IS NULL
     GROUP BY eventType
     ORDER BY eventType ASC',
    ['guildId' => $guildId]
);

$decorate = static function (array $row): array {
    $row['avatarUrl'] = DiscordAssets::avatar($row['userId'] ?? null, $row['avatarHash'] ?? null, 64);
    return $row;
};

Response::json([
    'ok' => true,
    'page' => $page,
    'pageSize' => $pageSize,
    'total' => (int) ($total['total'] ?? 0),
    'selectedGroups' => $selectedGroups,
    'selectedEventTypes' => $selectedEventTypes,
    'timeline' => ActivityEventPresenterService::enrichRows(array_map($decorate, $timeline), $guildId, null),
    'presence' => [],
    'events' => $events,
    'eventTypes' => $eventTypes,
]);
