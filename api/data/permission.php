<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('permission.manage');
AuditLogger::access('permission_read', 'page', 'permission', AuditLogger::requestPayload(), 'sensitive');

Response::json(['ok' => true] + (new DashboardPermissionService())->payload());
