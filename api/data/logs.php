<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('logs.view');
AuditLogger::access('logs_data_read', 'page', 'logs', AuditLogger::requestPayload(), 'sensitive');

$limit = max(20, min(250, Input::int('limit', 80)));
$q = Input::str('q');
$status = Input::str('status');

$staffWhere = [];
$staffParams = [];
if ($status) {
    $staffWhere[] = 'aa.status = :status';
    $staffParams['status'] = $status;
}
if ($q) {
    $staffWhere[] = '(aa.actionType LIKE :staffActionType OR aa.targetType LIKE :staffTargetType OR aa.targetId LIKE :staffTargetId OR aa.ipAddress LIKE :staffIpAddress OR au.displayName LIKE :staffDisplayName OR au.discordUserName LIKE :staffDiscordUserName)';
    $staffLike = '%' . $q . '%';
    $staffParams['staffActionType'] = $staffLike;
    $staffParams['staffTargetType'] = $staffLike;
    $staffParams['staffTargetId'] = $staffLike;
    $staffParams['staffIpAddress'] = $staffLike;
    $staffParams['staffDisplayName'] = $staffLike;
    $staffParams['staffDiscordUserName'] = $staffLike;
}
$staffSql = $staffWhere ? ' WHERE ' . implode(' AND ', $staffWhere) : '';

$staffLogs = Database::fetchAll(
    'SELECT aa.*, au.displayName, au.discordUserName
     FROM tbl_admin_action aa
     LEFT JOIN tbl_admin_user au ON au.adminUserId = aa.adminUserId
     ' . $staffSql . '
     ORDER BY aa.adminActionId DESC
     LIMIT ' . $limit,
    $staffParams
);

$accessWhere = [];
$accessParams = [];
if ($q) {
    $accessWhere[] = '(al.eventType LIKE :accessEventType OR al.targetType LIKE :accessTargetType OR al.targetId LIKE :accessTargetId OR al.ipAddress LIKE :accessIpAddress OR au.displayName LIKE :accessDisplayName OR au.discordUserName LIKE :accessDiscordUserName)';
    $accessLike = '%' . $q . '%';
    $accessParams['accessEventType'] = $accessLike;
    $accessParams['accessTargetType'] = $accessLike;
    $accessParams['accessTargetId'] = $accessLike;
    $accessParams['accessIpAddress'] = $accessLike;
    $accessParams['accessDisplayName'] = $accessLike;
    $accessParams['accessDiscordUserName'] = $accessLike;
}
$accessSql = $accessWhere ? ' WHERE ' . implode(' AND ', $accessWhere) : '';

$accessLogs = Database::fetchAll(
    'SELECT al.*, au.displayName, au.discordUserName
     FROM tbl_access_log al
     LEFT JOIN tbl_admin_user au ON au.adminUserId = al.adminUserId
     ' . $accessSql . '
     ORDER BY al.accessLogId DESC
     LIMIT ' . $limit,
    $accessParams
);

Response::json([
    'ok' => true,
    'system' => LiveUpdateService::systemState(),
    'staffLogs' => $staffLogs,
    'accessLogs' => $accessLogs,
    'auditCompare' => [],
    'metrics' => [
        'staffLogs' => count($staffLogs),
        'accessLogs' => count($accessLogs),
        'auditCompare' => 0,
        'rejected24h' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM tbl_admin_action WHERE status = "rejected" AND createDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)')['total'] ?? 0),
    ],
]);
