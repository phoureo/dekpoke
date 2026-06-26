<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('earn_manual'));
AuditLogger::access('earn_manual_preview', 'page', 'earn_manual');

$payload = Input::json();
$guildId = (string) Bootstrap::config('discord.guildId', '');

try {
    Response::json([
        'ok' => true,
        'preview' => (new ManualEarnGrantService())->previewSelection($guildId, $payload),
    ]);
} catch (Throwable $exception) {
    Response::error($exception->getMessage(), 400);
}
