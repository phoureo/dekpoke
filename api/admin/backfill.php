<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('ops.backfill');
Csrf::assertValid();

$payload = Input::json() + $_POST;
$command = (string) ($payload['command'] ?? 'run');
$type = (string) ($payload['type'] ?? 'all');
$guildId = (string) Bootstrap::config('discord.guildId', '');
$adminActionId = AuditLogger::start('backfill_' . $command, 'backfill', $type, $guildId, $payload);

try {
    $service = new BackfillService();
    if ($command === 'reset_system') {
        $confirmText = strtoupper(trim((string) ($payload['confirmText'] ?? '')));
        if ($confirmText !== 'RESET BOT LOG DATA') {
            throw new RuntimeException('Type RESET BOT LOG DATA before resetting.');
        }
        $result = (new ActivitySystemResetService())->resetForBotLogRebuild();
    } elseif ($command === 'reset_rewards') {
        $confirmText = strtoupper(trim((string) ($payload['confirmText'] ?? '')));
        if ($confirmText !== 'RESET REWARDS') {
            throw new RuntimeException('Type RESET REWARDS before resetting.');
        }
        $result = (new RewardEconomyResetService())->resetRewardsAndWallets($guildId);
    } elseif ($command === 'reset_gachapon') {
        $confirmText = strtoupper(trim((string) ($payload['confirmText'] ?? '')));
        if ($confirmText !== 'RESET GACHAPON') {
            throw new RuntimeException('Type RESET GACHAPON before resetting.');
        }
        $result = (new RewardEconomyResetService())->resetGachaponHistory($guildId);
    } elseif ($command === 'reset') {
        $count = $service->resetCursor($type === 'all' ? null : $type, $payload['channelId'] ?? null);
        $result = ['resetCount' => $count, 'status' => $service->status()];
    } else {
        $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
        if ($command === 'force') {
            $options['force'] = true;
        }
        $result = $service->run($type, $options);
    }

    AuditLogger::finish($adminActionId, 'success', $result, null);
    LiveUpdateService::markTopic('backfill', ['command' => $command, 'type' => $type, 'status' => 'success'], 'backfill', $type, $guildId);
    Response::json(['ok' => true, 'adminActionId' => $adminActionId, 'result' => $result]);
} catch (Throwable $exception) {
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    LiveUpdateService::markTopic('backfill', ['command' => $command, 'type' => $type, 'status' => 'failed'], 'backfill', $type, $guildId);
    Response::error($exception->getMessage(), 500, ['adminActionId' => $adminActionId]);
}
