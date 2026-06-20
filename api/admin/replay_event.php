<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('ops.replay');
Csrf::assertValid();

$payload = Input::json() + $_POST;
$rawEventId = (int) ($payload['rawEventId'] ?? 0);
if ($rawEventId <= 0) {
    Response::error('rawEventId is required.', 422);
}

$raw = Database::fetch('SELECT * FROM tbl_raw_event WHERE rawEventId = :id', ['id' => $rawEventId]);
if (!$raw) {
    Response::error('Raw event not found.', 404);
}

$adminActionId = AuditLogger::start('raw_event_replay', 'raw_event', (string) $rawEventId, $raw['guildId'], $payload, $raw);
try {
    $eventType = strtoupper(trim((string) $raw['eventType']));
    if (!GatewayEventIngestService::isOfficialEventType($eventType)) {
        throw new RuntimeException('Stored event type is not an official Discord gateway event.');
    }

    $eventPayload = json_decode((string) $raw['eventPayloadJson'], true);
    if (!is_array($eventPayload)) {
        throw new RuntimeException('Raw event payload is not valid JSON.');
    }

    $context = json_decode((string) ($raw['contextJson'] ?? '{}'), true);
    if (!is_array($context)) {
        $context = [];
    }

    (new GatewayEventIngestService())->transform(
        $eventType,
        $eventPayload,
        (int) $rawEventId,
        (string) ($raw['eventDate'] ?? date('Y-m-d H:i:s')),
        (string) ($raw['sourceName'] ?? 'gateway'),
        $context
    );
    Database::execute(
        'UPDATE tbl_raw_event SET processStatus = "success", errorMessage = NULL, processDate = :processDate WHERE rawEventId = :id',
        ['id' => $rawEventId, 'processDate' => date('Y-m-d H:i:s')]
    );
    AuditLogger::finish($adminActionId, 'success', ['rawEventId' => $rawEventId]);
    LiveUpdateService::markTopic('admin', ['rawEventId' => $rawEventId, 'status' => 'success'], 'raw_event', (string) $rawEventId, (string) ($raw['guildId'] ?? Bootstrap::config('discord.guildId', '')));
    Response::json(['ok' => true, 'adminActionId' => $adminActionId]);
} catch (Throwable $exception) {
    Database::execute(
        'UPDATE tbl_raw_event SET processStatus = "failed", errorMessage = :error, processDate = :processDate WHERE rawEventId = :id',
        ['id' => $rawEventId, 'error' => $exception->getMessage(), 'processDate' => date('Y-m-d H:i:s')]
    );
    AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
    LiveUpdateService::markTopic('admin', ['rawEventId' => $rawEventId, 'status' => 'failed'], 'raw_event', (string) $rawEventId, (string) ($raw['guildId'] ?? Bootstrap::config('discord.guildId', '')));
    Response::error($exception->getMessage(), 500, ['adminActionId' => $adminActionId]);
}
