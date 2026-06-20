<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('shop_member_bags'));
AuditLogger::access('shop_member_bag_detail_read', 'page', 'shop_member_bags');

$userId = trim((string) Input::str('userId', ''));
if ($userId === '') {
    Response::json(['ok' => false, 'message' => 'member required'], 400);
}

$filters = [
    'page' => max(1, Input::int('page', 1)),
    'pageSize' => max(10, min(200, Input::int('pageSize', 25))),
    'historyKind' => trim((string) Input::str('historyKind', '')),
    'direction' => trim((string) Input::str('direction', '')),
    'sourceType' => trim((string) Input::str('sourceType', '')),
    'dateFrom' => trim((string) Input::str('dateFrom', '')),
    'dateTo' => trim((string) Input::str('dateTo', '')),
];

try {
    $report = ShopReportService::memberBagDetail((string) Bootstrap::config('discord.guildId', ''), $userId, $filters);
} catch (RuntimeException $exception) {
    $status = $exception->getMessage() === 'MEMBER_NOT_FOUND' ? 404 : 400;
    Response::json(['ok' => false, 'message' => $status === 404 ? 'ไม่พบสมาชิกคนนี้' : $exception->getMessage()], $status);
}

Response::json([
    'ok' => true,
    'page' => $report['page'],
    'pageSize' => $report['pageSize'],
    'total' => $report['total'],
    'member' => $report['member'],
    'wallets' => $report['wallets'],
    'items' => $report['items'],
    'metrics' => $report['metrics'],
    'rows' => $report['rows'],
    'filters' => $report['filters'],
    'filterOptions' => $report['filterOptions'],
    'historyNotice' => $report['historyNotice'],
]);
