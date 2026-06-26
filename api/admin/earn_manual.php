<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
$adminUser = Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('earn_manual'));

$payload = Input::json();
$guildId = (string) Bootstrap::config('discord.guildId', '');
$targetType = trim((string) ($payload['targetType'] ?? 'user'));
$targetId = $targetType === 'role'
    ? trim((string) ($payload['targetRoleId'] ?? ''))
    : trim((string) ($payload['targetUserId'] ?? $payload['userId'] ?? ($targetType === 'server' ? 'server' : '')));

$before = [
    'targetType' => $targetType,
    'targetId' => $targetId,
    'payload' => $payload,
];
$adminActionId = AuditLogger::start('earn_manual_grant', 'member', $targetId, $guildId, $payload, $before);

try {
    $result = (new ManualEarnGrantService())->grantSelection($guildId, $payload, $adminUser, $adminActionId);
    $after = [
        'batchId' => $result['batchId'] ?? '',
        'targetType' => $result['targetType'] ?? '',
        'targetLabel' => $result['targetLabel'] ?? '',
        'recipientCount' => $result['recipientCount'] ?? 0,
        'unitRewards' => $result['unitRewards'],
        'reason' => $result['reason'] ?? '',
    ];
    AuditLogger::finish($adminActionId, 'success', $result, $after);
    Response::json(['ok' => true] + $result + (new ManualEarnGrantService())->payload($guildId));
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    Response::error($exception->getMessage(), 400);
}
