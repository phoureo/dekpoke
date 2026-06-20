<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

$user = Auth::requireUser();
Csrf::assertValid();
AuditLogger::access('logout', 'auth', 'session');
LiveUpdateService::closeByAdminUser((int) $user['adminUserId']);
PlayerAuth::logout();
Auth::logout();

Response::json(['ok' => true]);
