<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('earn_manual'));
AuditLogger::access('earn_manual_read', 'page', 'earn_manual');

$guildId = (string) Bootstrap::config('discord.guildId', '');
$q = trim((string) Input::str('q', ''));
$service = new ManualEarnGrantService();
$users = $q !== '' ? $service->searchMembers($guildId, $q, 20) : [];

Response::json(['ok' => true, 'users' => $users] + $service->payload($guildId));
