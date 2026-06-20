<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

$permissions = array_values(array_unique(array_merge(
    DashboardPermissionService::pagePermissionsFor('bag_transaction_report'),
    DashboardPermissionService::pagePermissionsFor('shop_member_bags'),
    DashboardPermissionService::pagePermissionsFor('shop_report'),
    DashboardPermissionService::pagePermissionsFor('reward_report')
)));

Auth::requireAnyPermission($permissions);
AuditLogger::access('bag_transaction_trace_read', 'page', 'bag_transaction_report');

$transactionGroupId = trim((string) Input::str('transactionGroupId', ''));
$historyKind = trim((string) Input::str('historyKind', ''));
$historyId = max(0, Input::int('historyId', 0));

if ($transactionGroupId === '' && ($historyKind === '' || $historyId <= 0)) {
    Response::json(['ok' => false, 'message' => 'transactionGroupId or history seed required'], 400);
}

try {
    $trace = TransactionTraceService::resolveTrace((string) Bootstrap::config('discord.guildId', ''), [
        'transactionGroupId' => $transactionGroupId,
        'historyKind' => $historyKind,
        'historyId' => $historyId,
    ]);
} catch (RuntimeException $exception) {
    $message = trim($exception->getMessage());
    if ($message === 'TRACE_NOT_FOUND') {
        Response::json(['ok' => false, 'message' => 'ไม่พบ transaction นี้'], 404);
    }
    Response::json(['ok' => false, 'message' => $message !== '' ? $message : 'trace lookup failed'], 400);
}

Response::json([
    'ok' => true,
    'trace' => $trace['trace'] ?? [],
    'legs' => $trace['legs'] ?? [],
]);
