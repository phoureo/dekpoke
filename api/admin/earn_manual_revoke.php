<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
$adminUser = Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('earn_manual'));

$payload = Input::json();
$guildId = (string) Bootstrap::config('discord.guildId', '');
$batchId = trim((string) ($payload['batchId'] ?? ''));

$before = [
    'batchId' => $batchId,
    'payload' => $payload,
];
$adminActionId = AuditLogger::start('earn_manual_revoke', 'member', $batchId, $guildId, $payload, $before);

try {
    $service = new ManualEarnGrantService();
    $result = $service->revokeBatch($guildId, $payload, $adminUser, $adminActionId);
    AuditLogger::finish($adminActionId, 'success', $result, [
        'batchId' => $result['batchId'] ?? '',
        'reversalCount' => $result['reversalCount'] ?? 0,
        'affectedRecipients' => $result['affectedRecipients'] ?? 0,
        'reason' => trim((string) ($payload['reason'] ?? '')),
    ]);
    Response::json(['ok' => true] + $result + $service->payload($guildId));
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    Response::error($exception->getMessage(), 400);
}
