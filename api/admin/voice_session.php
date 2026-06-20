<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('ops.backfill');
Csrf::assertValid();

$payload = Input::json() + $_POST;
$command = (string) ($payload['command'] ?? '');
$voiceSessionId = (int) ($payload['voiceSessionId'] ?? 0);
if ($command !== 'hide' || $voiceSessionId <= 0) {
    Response::error('Invalid voice session action.', 422);
}

$session = Database::fetch(
    'SELECT voiceSessionId, guildId, metadataJson
     FROM tbl_voice_session
     WHERE voiceSessionId = :voiceSessionId',
    ['voiceSessionId' => $voiceSessionId]
);
if (!$session) {
    Response::error('Voice session not found.', 404);
}

$metadata = json_decode((string) ($session['metadataJson'] ?? '{}'), true);
$metadata = is_array($metadata) ? $metadata : [];
$metadata['hiddenFromTimeMachine'] = true;
$metadata['excludedFromRewards'] = true;
$metadata['hiddenReason'] = (string) ($payload['reason'] ?? 'manual_hide_suspect_session');
$metadata['hiddenDate'] = date('Y-m-d H:i:s');
$metadata['hiddenByAdminUserId'] = (int) (Auth::currentUser()['adminUserId'] ?? 0);

Database::execute(
    'UPDATE tbl_voice_session
     SET metadataJson = :metadataJson,
         durationSeconds = 0,
         updateDate = :updateDate
     WHERE voiceSessionId = :voiceSessionId',
    [
        'voiceSessionId' => $voiceSessionId,
        'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'updateDate' => date('Y-m-d H:i:s'),
    ]
);

AuditLogger::access('voice_session_hide', 'voice_session', (string) $voiceSessionId, ['reason' => $metadata['hiddenReason']], 'sensitive');
LiveUpdateService::markTopic('voice', ['scope' => 'voice_session_hide', 'voiceSessionId' => $voiceSessionId], 'voice_session', (string) $voiceSessionId, (string) $session['guildId']);

Response::json([
    'ok' => true,
    'voiceSessionId' => $voiceSessionId,
    'metadata' => $metadata,
]);
