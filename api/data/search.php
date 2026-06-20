<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireUser();

$q = Input::str('q');
if (!$q || mb_strlen($q) < 2) {
    Response::json(['ok' => true, 'items' => []]);
}

$type = trim((string) Input::str('type', ''));
$type = in_array($type, ['member', 'message', 'role', 'channel'], true) ? $type : '';

AuditLogger::access('global_search', 'search', null, ['q' => $q, 'type' => $type], 'sensitive');
$guildId = (string) Bootstrap::config('discord.guildId', '');
$like = '%' . $q . '%';
$items = [];

if ($type === '' || $type === 'member') {
    $members = Database::fetchAll(
        'SELECT m.userId, COALESCE(m.nickName, u.globalName, u.userName) AS title, u.userName AS subtitle, u.avatarHash
         FROM tbl_member m
         INNER JOIN tbl_user u ON u.userId = m.userId
         WHERE m.guildId = :guildId AND (u.userName LIKE :memberUserName OR u.globalName LIKE :memberGlobalName OR m.nickName LIKE :memberNickName OR m.userId = :exact)
         ORDER BY m.isActive DESC, title ASC
         LIMIT 8',
        [
            'guildId' => $guildId,
            'memberUserName' => $like,
            'memberGlobalName' => $like,
            'memberNickName' => $like,
            'exact' => $q,
        ]
    );
    foreach ($members as $row) {
        $items[] = [
            'type' => 'member',
            'id' => $row['userId'],
            'title' => $row['title'],
            'subtitle' => '@' . $row['subtitle'],
            'avatarUrl' => DiscordAssets::avatar($row['userId'], $row['avatarHash'], 64),
        ];
    }
}

if ($type === '' || $type === 'message') {
    $messages = Database::fetchAll(
        'SELECT m.messageId, m.contentText, c.channelName, m.messageCreateDate
         FROM tbl_message m
         LEFT JOIN tbl_channel c ON c.channelId = m.channelId
         WHERE m.guildId = :guildId AND m.contentText LIKE :q
         ORDER BY m.messageCreateDate DESC
         LIMIT 8',
        ['guildId' => $guildId, 'q' => $like]
    );
    foreach ($messages as $row) {
        $items[] = [
            'type' => 'message',
            'id' => $row['messageId'],
            'title' => mb_substr((string) $row['contentText'], 0, 90),
            'subtitle' => '#' . ($row['channelName'] ?? 'unknown') . ' · ' . $row['messageCreateDate'],
        ];
    }
}

if ($type === '' || $type === 'role') {
    $roles = Database::fetchAll(
        'SELECT roleId, roleName, rolePosition
         FROM tbl_role
         WHERE guildId = :guildId AND roleName LIKE :q AND deleteDate IS NULL
         ORDER BY rolePosition DESC
         LIMIT 8',
        ['guildId' => $guildId, 'q' => $like]
    );
    foreach ($roles as $row) {
        $items[] = [
            'type' => 'role',
            'id' => $row['roleId'],
            'title' => $row['roleName'],
            'subtitle' => 'Role position ' . $row['rolePosition'],
        ];
    }
}

if ($type === '' || $type === 'channel') {
    $channels = Database::fetchAll(
        'SELECT c.channelId, c.channelName, c.channelType, parent.channelName AS categoryName
         FROM tbl_channel c
         LEFT JOIN tbl_channel parent ON parent.channelId = c.parentChannelId
         WHERE c.guildId = :guildId AND (c.channelName LIKE :channelNameLike OR c.channelId = :exact)
         ORDER BY c.channelPosition ASC
         LIMIT 8',
        ['guildId' => $guildId, 'channelNameLike' => $like, 'exact' => $q]
    );
    foreach ($channels as $row) {
        $items[] = [
            'type' => 'channel',
            'id' => $row['channelId'],
            'title' => $row['channelName'],
            'subtitle' => '#' . ($row['categoryName'] ?: 'uncategorized') . ' · type ' . $row['channelType'],
        ];
    }
}

Response::json(['ok' => true, 'items' => $items]);
