<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

$auth = DiscordOAuth::beginAuthorization($_GET['flow'] ?? 'admin', $_GET['return_to'] ?? null);
AuditLogger::access('oauth_login_start', 'auth', 'discord');
$responseMode = strtolower(trim((string) ($_GET['response'] ?? '')));
if ($responseMode === 'json') {
    Response::json([
        'ok' => true,
        'authorizeUrl' => $auth['authorizeUrl'],
        'redirectUri' => $auth['redirectUri'],
        'flow' => $auth['flow'],
    ]);
}

header('Location: ' . $auth['authorizeUrl']);
exit;
