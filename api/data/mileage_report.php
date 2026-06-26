<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('mileage_report'));
AuditLogger::access('mileage_report_read', 'page', 'mileage_report');

$filters = [
    'page' => max(1, Input::int('page', 1)),
    'pageSize' => max(25, min(200, Input::int('pageSize', 50))),
    'q' => trim((string) Input::str('q', '')),
    'boardCode' => trim((string) Input::str('boardCode', '')),
    'status' => trim((string) Input::str('status', '')),
    'dateFrom' => trim((string) Input::str('dateFrom', '')),
    'dateTo' => trim((string) Input::str('dateTo', '')),
    'sort' => trim((string) Input::str('sort', 'lastActivityDate')),
    'dir' => strtolower(trim((string) Input::str('dir', 'desc'))) === 'asc' ? 'asc' : 'desc',
];

try {
    $report = MileageReportService::report((string) Bootstrap::config('discord.guildId', ''), $filters);
    Response::json(['ok' => true] + $report);
} catch (Throwable $exception) {
    Response::error($exception->getMessage(), 500);
}
