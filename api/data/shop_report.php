<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('shop_report'));
AuditLogger::access('shop_report_read', 'page', 'shop_report');

$filters = [
    'page' => max(1, Input::int('page', 1)),
    'pageSize' => max(25, min(200, Input::int('pageSize', 50))),
    'q' => trim((string) Input::str('q', '')),
    'sourceType' => trim((string) Input::str('sourceType', '')),
    'movementType' => trim((string) Input::str('movementType', '')),
    'unitCode' => trim((string) Input::str('unitCode', '')),
    'dateFrom' => trim((string) Input::str('dateFrom', '')),
    'dateTo' => trim((string) Input::str('dateTo', '')),
    'sort' => trim((string) Input::str('sort', 'createDate')),
    'dir' => strtolower(trim((string) Input::str('dir', 'desc'))) === 'asc' ? 'asc' : 'desc',
];

$report = ShopReportService::purchaseReport((string) Bootstrap::config('discord.guildId', ''), $filters);
$units = ShopUnitService::units(true);

Response::json([
    'ok' => true,
    'page' => $report['page'],
    'pageSize' => $report['pageSize'],
    'total' => $report['total'],
    'metrics' => $report['metrics'],
    'rows' => $report['rows'],
    'filters' => $filters,
    'filterOptions' => [
        'units' => array_map(static fn (array $unit): array => [
            'unitCode' => (string) ($unit['unitCode'] ?? ''),
            'label' => (string) ($unit['shortName'] ?? $unit['displayName'] ?? $unit['unitCode'] ?? ''),
        ], $units),
        'sourceTypes' => ['shop_role_badge_purchase', 'shop_role_badge_gift'],
    ],
]);
