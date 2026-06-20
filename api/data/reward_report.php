<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('reward_report'));
AuditLogger::access('reward_report_read', 'page', 'reward_report');

$filters = [
    'page' => max(1, Input::int('page', 1)),
    'pageSize' => max(25, min(200, Input::int('pageSize', 50))),
    'q' => trim((string) Input::str('q', '')),
    'ruleCode' => trim((string) Input::str('ruleCode', '')),
    'triggerType' => trim((string) Input::str('triggerType', '')),
    'sourceType' => trim((string) Input::str('sourceType', '')),
    'movementType' => trim((string) Input::str('movementType', '')),
    'unitCode' => trim((string) Input::str('unitCode', '')),
    'status' => trim((string) Input::str('status', '')),
    'rewardKind' => trim((string) Input::str('rewardKind', '')),
    'dateFrom' => trim((string) Input::str('dateFrom', '')),
    'dateTo' => trim((string) Input::str('dateTo', '')),
    'sort' => trim((string) Input::str('sort', 'createDate')),
    'dir' => strtolower(trim((string) Input::str('dir', 'desc'))) === 'asc' ? 'asc' : 'desc',
];

$rules = (new EarnService())->rules();
$report = RewardReportService::report((string) Bootstrap::config('discord.guildId', ''), $filters);
$units = class_exists('ShopUnitService') ? ShopUnitService::units(true) : [];

$ruleOptions = array_map(
    static function (array $rule): array {
        return [
            'ruleCode' => (string) ($rule['ruleCode'] ?? ''),
            'ruleName' => (string) ($rule['ruleName'] ?? $rule['ruleCode'] ?? ''),
            'triggerType' => (string) ($rule['triggerType'] ?? ''),
        ];
    },
    array_values(array_filter($rules, static fn (array $rule): bool => str_starts_with((string) ($rule['ruleCode'] ?? ''), 'earn_')))
);

$triggerMap = [];
foreach ($ruleOptions as $ruleOption) {
    $triggerType = (string) ($ruleOption['triggerType'] ?? '');
    if ($triggerType === '') {
        continue;
    }
    $triggerMap[$triggerType] = $triggerType;
}

Response::json([
    'ok' => true,
    'page' => $report['page'],
    'pageSize' => $report['pageSize'],
    'total' => $report['total'],
    'metrics' => $report['metrics'],
    'rows' => $report['rows'],
    'filters' => $filters,
    'filterOptions' => [
        'rules' => $ruleOptions,
        'triggerTypes' => array_values($triggerMap),
        'units' => array_map(static fn (array $unit): array => [
            'unitCode' => (string) ($unit['unitCode'] ?? ''),
            'label' => (string) ($unit['shortName'] ?? $unit['displayName'] ?? $unit['unitCode'] ?? ''),
        ], $units),
    ],
]);
