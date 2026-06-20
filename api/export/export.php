<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('export.data');
$type = Input::str('type', 'messages') ?? 'messages';
$guildId = (string) Bootstrap::config('discord.guildId', '');

$exports = [
    'members' => [
        'file' => 'members.csv',
        'sql' => 'SELECT m.userId,
                         COALESCE(m.nickName, u.globalName, u.userName) AS displayName,
                         u.userName,
                         m.joinedAt,
                         m.roleCount,
                         m.isActive,
                         COALESCE(MAX(CASE WHEN sw.unitCode = "coin" THEN sw.balanceAmount END), 0) AS coin,
                         COALESCE(MAX(CASE WHEN sw.unitCode = "gem" THEN sw.balanceAmount END), 0) AS gem,
                         COALESCE(MAX(CASE WHEN sw.unitCode = "ticket" THEN sw.balanceAmount END), 0) AS ticket,
                         COALESCE(MAX(CASE WHEN sw.unitCode = "potion" THEN sw.balanceAmount END), 0) AS potion
                  FROM tbl_member m
                  INNER JOIN tbl_user u ON u.userId = m.userId
                  LEFT JOIN tbl_shop_wallet sw ON sw.guildId = m.guildId AND sw.userId = m.userId
                  WHERE m.guildId = :guildId
                  GROUP BY m.memberId
                  ORDER BY displayName ASC
                  LIMIT 10000',
    ],
    'messages' => [
        'file' => 'messages.csv',
        'sql' => 'SELECT m.messageId, m.channelId, c.channelName, m.authorUserId, u.userName, m.contentText, m.attachmentCount, m.isEdited, m.isDelete, m.messageCreateDate, m.deleteDate
                  FROM tbl_message m LEFT JOIN tbl_channel c ON c.channelId = m.channelId LEFT JOIN tbl_user u ON u.userId = m.authorUserId
                  WHERE m.guildId = :guildId ORDER BY m.messageCreateDate DESC LIMIT 10000',
    ],
    'staff_logs' => [
        'file' => 'staff_logs.csv',
        'sql' => 'SELECT aa.adminActionId, aa.adminUserId, au.displayName, aa.actionType, aa.targetType, aa.targetId, aa.actionStage, aa.status, aa.errorMessage, aa.ipAddress, aa.createDate
                  FROM tbl_admin_action aa LEFT JOIN tbl_admin_user au ON au.adminUserId = aa.adminUserId
                  ORDER BY aa.adminActionId DESC LIMIT 10000',
    ],
];

if (!isset($exports[$type])) {
    Response::error('Unsupported export type.', 422);
}

AuditLogger::access('export_csv', 'export', $type, AuditLogger::requestPayload(), 'sensitive');
$rows = Database::fetchAll($exports[$type]['sql'], str_contains($exports[$type]['sql'], ':guildId') ? ['guildId' => $guildId] : []);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $exports[$type]['file'] . '"');
$out = fopen('php://output', 'w');
if ($rows) {
    fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
}
fclose($out);
exit;
