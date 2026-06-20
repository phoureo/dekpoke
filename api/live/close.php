<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

$user = Auth::requireUser();
Csrf::assertValid();

$viewerToken = Input::str('viewerToken', '', 'post');
if ($viewerToken === '') {
    Response::error('viewerToken is required.', 422);
}

LiveUpdateService::closeViewer($viewerToken, (int) $user['adminUserId']);

Response::json([
    'ok' => true,
]);
