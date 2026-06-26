<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('shop_member_bags'));
AuditLogger::access('shop_member_bags_read', 'page', 'shop_member_bags');

$filters = [
    'page' => max(1, Input::int('page', 1)),
    'pageSize' => max(25, min(200, Input::int('pageSize', 50))),
    'q' => trim((string) Input::str('q', '')),
    'unitCode' => trim((string) Input::str('unitCode', '')),
    'itemType' => trim((string) Input::str('itemType', '')),
    'itemCode' => trim((string) Input::str('itemCode', '')),
    'hideInactive' => Input::bool('hideInactive', true),
    'onlyWithWallet' => Input::bool('onlyWithWallet', false),
    'onlyWithInventory' => Input::bool('onlyWithInventory', false),
    'sort' => trim((string) Input::str('sort', 'displayName')),
    'dir' => strtolower(trim((string) Input::str('dir', 'asc'))) === 'desc' ? 'desc' : 'asc',
];

try {
    ShopUnitService::ensureSchema();
    $report = ShopReportService::memberBagReport((string) Bootstrap::config('discord.guildId', ''), $filters);
    $units = ShopUnitService::units(true);
    $itemTypes = Database::fetchAll(
        'SELECT DISTINCT itemType
           FROM tbl_shop_item
          WHERE itemType IS NOT NULL AND itemType <> ""
          ORDER BY itemType ASC'
    );

    Response::json([
        'ok' => true,
        'page' => $report['page'],
        'pageSize' => $report['pageSize'],
        'total' => $report['total'],
        'metrics' => $report['metrics'],
        'rows' => $report['rows'],
        'walletColumns' => $report['walletColumns'] ?? [],
        'filters' => $filters,
        'filterOptions' => [
            'units' => array_map(static fn (array $unit): array => [
                'unitCode' => (string) ($unit['unitCode'] ?? ''),
                'label' => (string) ($unit['shortName'] ?? $unit['displayName'] ?? $unit['unitCode'] ?? ''),
            ], $units),
            'itemTypes' => array_values(array_map(static fn (array $row): string => (string) ($row['itemType'] ?? ''), $itemTypes)),
        ],
    ]);
} catch (Throwable $exception) {
    Response::error('โหลดกระเป๋าสมาชิกไม่สำเร็จ: ' . $exception->getMessage(), 500);
}
