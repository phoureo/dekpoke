<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('earn_settings'));
AuditLogger::access('earn_settings_read', 'page', 'earn_settings');

Response::json(['ok' => true] + (new EarnService())->payload());
