<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('gacha.manage');
AuditLogger::access('gacha_report_read', 'page', 'gacha_report');

$config = class_exists('GachaConfigService') ? GachaConfigService::load() : ['settings' => [], 'tiers' => []];
$filters = [
    'page' => max(1, Input::int('page', 1)),
    'pageSize' => max(25, min(200, Input::int('pageSize', 50))),
    'q' => trim((string) Input::str('q', '')),
    'drawId' => trim((string) Input::str('drawId', '')),
    'status' => trim((string) Input::str('status', '')),
    'currency' => trim((string) Input::str('currency', '')),
    'buttonId' => trim((string) Input::str('buttonId', '')),
    'tierId' => trim((string) Input::str('tierId', '')),
    'prizeType' => trim((string) Input::str('prizeType', '')),
    'durationKind' => trim((string) Input::str('durationKind', '')),
    'dateFrom' => trim((string) Input::str('dateFrom', '')),
    'dateTo' => trim((string) Input::str('dateTo', '')),
    'sort' => trim((string) Input::str('sort', 'startedAt')),
    'dir' => strtolower(trim((string) Input::str('dir', 'desc'))) === 'asc' ? 'asc' : 'desc',
];

$report = GachaSpinHistoryService::report((string) Bootstrap::config('discord.guildId', ''), $filters);
$buttons = [];
foreach (($config['settings']['buttons'] ?? []) as $buttonKey => $button) {
    if (!is_array($button)) {
        continue;
    }
    $buttonId = (int) ($button['buttonId'] ?? $buttonKey);
    $buttons[] = [
        'buttonId' => $buttonId,
        'label' => (string) ($button['label'] ?? ('Button ' . $buttonId)),
        'currency' => (string) ($button['currency'] ?? ''),
    ];
}

$tiers = array_map(
    static function (array $tier): array {
        return [
            'id' => (string) ($tier['id'] ?? ''),
            'name' => (string) ($tier['tier'] ?? $tier['name'] ?? $tier['id'] ?? ''),
        ];
    },
    array_values(array_filter($config['tiers'] ?? [], 'is_array'))
);

Response::json([
    'ok' => true,
    'page' => $report['page'],
    'pageSize' => $report['pageSize'],
    'total' => $report['total'],
    'metrics' => $report['metrics'],
    'rows' => $report['rows'],
    'filters' => $filters,
    'filterOptions' => [
        'buttons' => $buttons,
        'tiers' => $tiers,
    ],
]);
