<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireUser();

$page = strtolower((string) ($_GET['page'] ?? 'activity'));
$allowed = ['roles', 'messages', 'activity', 'gacha_prize', 'gacha_campaign', 'gacha_shop', 'gacha_report', 'earn_settings', 'earn_manual', 'reward_report', 'shop_report', 'shop_member_bags', 'bag_transaction_report', 'admin', 'permission', 'backfill', 'logs'];
if (!in_array($page, $allowed, true)) {
    Response::error('Page not found.', 404);
}

$user = Auth::requireUser();
if (!(new DashboardPermissionService())->canViewPage($user, $page)) {
    AuditLogger::reject('page_permission_reject', 'Permission denied.', 'page', $page, AuditLogger::requestPayload());
    Response::error('Permission denied.', 403);
}

AuditLogger::access('page_load', 'page', $page);
$path = Bootstrap::rootPath('pages/' . $page . '.php');
if (!is_file($path)) {
    Response::error('Page partial missing.', 404);
}

ob_start();
require $path;
$html = ob_get_clean();

Response::json(['ok' => true, 'page' => $page, 'html' => $html]);
