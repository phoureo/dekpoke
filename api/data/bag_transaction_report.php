<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('bag_transaction_report'));
AuditLogger::access('bag_transaction_report_read', 'page', 'bag_transaction_report');

$filters = [
    'page' => max(1, Input::int('page', 1)),
    'pageSize' => max(25, min(200, Input::int('pageSize', 50))),
    'q' => trim((string) Input::str('q', '')),
    'historyKind' => trim((string) Input::str('historyKind', '')),
    'direction' => trim((string) Input::str('direction', '')),
    'sourceType' => trim((string) Input::str('sourceType', '')),
    'unitCode' => trim((string) Input::str('unitCode', '')),
    'itemType' => trim((string) Input::str('itemType', '')),
    'itemCode' => trim((string) Input::str('itemCode', '')),
    'dateFrom' => trim((string) Input::str('dateFrom', '')),
    'dateTo' => trim((string) Input::str('dateTo', '')),
    'sort' => trim((string) Input::str('sort', 'createDate')),
    'dir' => strtolower(trim((string) Input::str('dir', 'desc'))) === 'asc' ? 'asc' : 'desc',
];

$report = ShopReportService::transactionReport((string) Bootstrap::config('discord.guildId', ''), $filters);

Response::json([
    'ok' => true,
    'page' => $report['page'],
    'pageSize' => $report['pageSize'],
    'total' => $report['total'],
    'metrics' => $report['metrics'],
    'rows' => $report['rows'],
    'filters' => $report['filters'],
    'filterOptions' => $report['filterOptions'],
    'historyNotice' => $report['historyNotice'],
]);
