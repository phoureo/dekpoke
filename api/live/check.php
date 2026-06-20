<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

$user = Auth::requireUser();

$pageKey = Input::str('pageKey');
if (!$pageKey) {
    Response::error('pageKey is required.', 422);
}

$viewerToken = Input::str('viewerToken');
$metadata = [
    'path' => Input::str('path'),
    'visibility' => Input::str('visibility'),
];

$lastSeenLiveUpdateId = max(0, Input::int('lastSeenLiveUpdateId', 0));
try {
    $state = $viewerToken
        ? LiveUpdateService::heartbeat($viewerToken, $pageKey, (int) $user['adminUserId'], $lastSeenLiveUpdateId, array_filter($metadata, static fn ($value): bool => $value !== null && $value !== ''))
        : LiveUpdateService::state($pageKey, $lastSeenLiveUpdateId);
} catch (InvalidArgumentException $exception) {
    Response::error($exception->getMessage(), 422);
}

Response::json([
    'ok' => true,
    'state' => $state,
    'system' => LiveUpdateService::systemState(),
]);
