<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

function mileage_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function mileage_input(): array
{
    $input = $_POST;
    if (!$input) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
    return array_merge($_GET, $input);
}

function mileage_save_allowed(): bool
{
    $ip = trim(Http::clientIp());
    if (
        $ip === '127.0.0.1'
        || $ip === '::1'
        || $ip === '::ffff:127.0.0.1'
        || str_starts_with($ip, '192.168.')
        || str_starts_with($ip, '10.')
    ) {
        return true;
    }

    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
    $host = trim($host, '[]');
    $host = preg_replace('/:\d+$/', '', $host) ?: '';
    if (
        in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        || str_starts_with($host, '192.168.')
        || str_starts_with($host, '10.')
    ) {
        return true;
    }

    try {
        return Auth::currentUser() !== null;
    } catch (Throwable $exception) {
        if (mileage_db_is_unavailable($exception)) {
            return false;
        }
        throw $exception;
    }
}

function mileage_player_token_from_input(array $input): string
{
    return trim((string) (
        $input['player_token']
        ?? $_SERVER['HTTP_X_GACHA_PLAYER_TOKEN']
        ?? $_COOKIE['gachaPlayerToken']
        ?? ''
    ));
}

function mileage_db_is_unavailable(Throwable $exception): bool
{
    $message = strtolower(trim($exception->getMessage()));
    if ($message === '') {
        return false;
    }

    return str_contains($message, 'sqlstate[hy000] [2002]')
        || str_contains($message, 'network is unreachable')
        || str_contains($message, 'operation timed out')
        || str_contains($message, 'connection refused')
        || str_contains($message, 'no route to host');
}

function mileage_base64_url_decode(string $value): string|false
{
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($value, '-_', '+/'), true);
}

function mileage_base64_url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function mileage_resume_token_secret(): string
{
    $configured = trim((string) Bootstrap::config('auth.playerTokenSecret', ''));
    if ($configured !== '') {
        return $configured;
    }

    return implode('|', [
        'dekpoke-gacha-player-token',
        (string) Bootstrap::config('discord.clientSecret', ''),
        (string) Bootstrap::config('discord.clientId', ''),
        (string) Bootstrap::config('discord.guildId', ''),
        (string) Bootstrap::config('auth.sessionName', 'dekpoke_orbit_session'),
    ]);
}

function mileage_resume_token_payload(string $token): ?array
{
    $token = trim($token);
    if ($token === '' || strlen($token) > 4096) {
        return null;
    }

    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        return null;
    }

    [$encodedPayload, $encodedSignature] = $parts;
    $expectedSignature = mileage_base64_url_encode(
        hash_hmac('sha256', $encodedPayload, mileage_resume_token_secret(), true)
    );
    if (!hash_equals($expectedSignature, $encodedSignature)) {
        return null;
    }

    $decodedPayload = mileage_base64_url_decode($encodedPayload);
    if ($decodedPayload === false) {
        return null;
    }

    $payload = json_decode($decodedPayload, true);
    if (!is_array($payload)) {
        return null;
    }

    $expiresAt = (int) ($payload['expiresAt'] ?? 0);
    if ($expiresAt <= time()) {
        return null;
    }

    return $payload;
}

function mileage_resolved_user_id(array $input): string
{
    $sessionUserId = trim((string) ($_SESSION['gachaPlayerUserId'] ?? ''));
    if ($sessionUserId !== '') {
        return $sessionUserId;
    }

    $payload = mileage_resume_token_payload(mileage_player_token_from_input($input));
    return trim((string) ($payload['userId'] ?? ''));
}

function mileage_degraded_summary(string $boardCode): array
{
    $summary = GachaMileageService::summary('', '', $boardCode);
    $summary['requiresLogin'] = false;
    return $summary;
}

function mileage_degraded_bootstrap(string $boardCode): array
{
    $summary = mileage_degraded_summary($boardCode);
    $positionStep = (int) ($summary['positionStep'] ?? -1);
    $lastAnimatedStep = (int) ($summary['lastAnimatedStep'] ?? -1);

    return [
        'ok' => true,
        'requiresLogin' => false,
        'serviceUnavailable' => true,
        'serviceMessage' => 'player database unavailable',
        'board' => GachaMileageService::boardDefinition($boardCode),
        'summary' => $summary,
        'progress' => [
            'lifetimeSteps' => (int) ($summary['lifetimeSteps'] ?? 0),
            'positionStep' => $positionStep,
            'lastAnimatedStep' => $lastAnimatedStep,
            'finished' => !empty($summary['finished']),
        ],
        'pending' => [
            'startStepIndex' => null,
            'endStepIndex' => null,
            'previewRewards' => [],
        ],
        'players' => [],
        'self' => null,
        'leaderboard' => [
            'all' => [],
            'weekly' => [],
        ],
    ];
}

function mileage_uploaded_image_meta(): array
{
    $file = $_FILES['image'] ?? null;
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('MILEAGE_UPLOAD_MISSING');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('MILEAGE_UPLOAD_INVALID');
    }

    $size = @getimagesize($tmp);
    if (!is_array($size)) {
        throw new RuntimeException('MILEAGE_UPLOAD_NOT_IMAGE');
    }

    $mime = strtolower((string) ($size['mime'] ?? ''));
    $extensions = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('MILEAGE_UPLOAD_UNSUPPORTED_TYPE');
    }

    if ((int) ($file['size'] ?? 0) > 30 * 1024 * 1024) {
        throw new RuntimeException('MILEAGE_UPLOAD_TOO_LARGE');
    }

    return [
        'tmp' => $tmp,
        'mime' => $mime,
        'extension' => $extensions[$mime],
        'width' => max(1, (int) ($size[0] ?? 1)),
        'height' => max(1, (int) ($size[1] ?? 1)),
        'originalName' => (string) ($file['name'] ?? 'image'),
    ];
}

function mileage_upload_asset(array $input): array
{
    if (!mileage_save_allowed()) {
        mileage_json([
            'ok' => false,
            'code' => 'FORBIDDEN',
            'message' => 'asset upload is restricted',
        ], 403);
    }

    $meta = mileage_uploaded_image_meta();
    $assetType = strtolower(trim((string) ($input['assetType'] ?? 'sprite')));
    $folders = [
        'sprite' => 'sprites',
        'segment' => 'mileage/segments',
        'reward_icon' => 'mileage/icons',
    ];
    if (!isset($folders[$assetType])) {
        throw new RuntimeException('MILEAGE_UPLOAD_BAD_ASSET_TYPE');
    }

    $uploadDir = Bootstrap::rootPath('gacha/images/uploads/' . $folders[$assetType]);
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('MILEAGE_UPLOAD_DIR_CREATE_FAILED');
    }
    @chmod(dirname($uploadDir), 0777);
    @chmod($uploadDir, 0777);
    if (!is_writable($uploadDir)) {
        throw new RuntimeException('MILEAGE_UPLOAD_DIR_NOT_WRITABLE');
    }

    $safeStem = preg_replace('/[^a-z0-9_-]+/i', '-', pathinfo($meta['originalName'], PATHINFO_FILENAME)) ?: $assetType;
    $safeStem = strtolower(trim($safeStem, '-_')) ?: $assetType;
    $name = sprintf(
        '%s_%s_%s.%s',
        $safeStem,
        date('Ymd_His'),
        bin2hex(random_bytes(4)),
        $meta['extension']
    );
    $target = $uploadDir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($meta['tmp'], $target)) {
        throw new RuntimeException('MILEAGE_UPLOAD_SAVE_FAILED');
    }
    @chmod($target, 0666);

    $publicPath = 'images/uploads/' . $folders[$assetType] . '/' . $name;
    try {
        AuditLogger::access('gacha_mileage_asset_upload', 'file', $publicPath);
    } catch (Throwable) {
    }

    return [
        'ok' => true,
        'assetType' => $assetType,
        'path' => $publicPath,
        'url' => $publicPath,
        'width' => $meta['width'],
        'height' => $meta['height'],
        'mime' => $meta['mime'],
    ];
}

function mileage_list_assets(): array
{
    $roots = [
        'images/uploads/sprites',
        'images/uploads/mileage',
        'images',
    ];
    $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
    $spriteManifestPath = Bootstrap::rootPath('gacha/images/uploads/sprites/manifest.json');
    $spriteManifest = [];
    if (is_file($spriteManifestPath)) {
        $decoded = json_decode((string) file_get_contents($spriteManifestPath), true);
        $spriteManifest = is_array($decoded['assets'] ?? null) ? $decoded['assets'] : [];
    }
    $assets = [];
    $seenPaths = [];
    foreach ($roots as $root) {
        $absolute = Bootstrap::rootPath('gacha/' . $root);
        if (!is_dir($absolute)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }
            $extension = strtolower($fileInfo->getExtension());
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($fileInfo->getPathname(), strlen(Bootstrap::rootPath('gacha')) + 1));
            if (isset($seenPaths[$relative])) {
                continue;
            }
            $seenPaths[$relative] = true;
            $size = @getimagesize($fileInfo->getPathname()) ?: [0, 0, 'mime' => ''];
            $assets[] = [
                'path' => $relative,
                'folder' => $root,
                'name' => $fileInfo->getFilename(),
                'width' => max(0, (int) ($size[0] ?? 0)),
                'height' => max(0, (int) ($size[1] ?? 0)),
                'mime' => (string) ($size['mime'] ?? ''),
                'updatedAt' => date(DateTimeInterface::ATOM, $fileInfo->getMTime()),
                'spriteMeta' => is_array($spriteManifest[$relative] ?? null) ? $spriteManifest[$relative] : null,
            ];
            if (count($assets) >= 500) {
                break 2;
            }
        }
    }

    usort($assets, static fn (array $a, array $b): int => strcmp((string) ($b['updatedAt'] ?? ''), (string) ($a['updatedAt'] ?? '')));

    return [
        'ok' => true,
        'roots' => $roots,
        'assets' => $assets,
    ];
}

$input = mileage_input();
$action = strtolower(trim((string) ($input['action'] ?? 'summary')));
$boardCode = trim((string) ($input['boardCode'] ?? GachaMileageService::DEFAULT_BOARD_CODE));
$guildId = (string) Bootstrap::config('discord.guildId', '');

try {
    $userId = '';

    if ($action === 'editor_bootstrap') {
        mileage_json([
            'ok' => true,
            'saveAllowed' => mileage_save_allowed(),
        ] + GachaMileageService::editorBootstrap($boardCode));
    }

    if ($action === 'preview_bootstrap') {
        $payload = is_array($input['board'] ?? null) ? $input['board'] : null;
        $board = GachaMileageService::previewBoardDefinition($payload, $boardCode);
        $maxStep = max(-1, count($board['steps'] ?? []) - 1);
        $step = max(-1, min($maxStep, (int) ($input['step'] ?? $maxStep)));
        mileage_json([
            'ok' => true,
            'previewOnly' => true,
            'requiresLogin' => false,
            'serviceUnavailable' => false,
            'board' => $board,
            'summary' => [
                'boardCode' => (string) ($board['boardCode'] ?? $boardCode),
                'lifetimeSteps' => max(0, $step + 1),
                'positionStep' => $step,
                'lastAnimatedStep' => $step,
                'pendingSteps' => 0,
                'pendingWalkCount' => 0,
                'badgeCount' => 0,
                'finished' => $maxStep >= 0 && $step >= $maxStep,
                'claimableRewardCount' => 0,
                'claimedRewardIds' => [],
                'requiresLogin' => false,
            ],
            'progress' => [
                'lifetimeSteps' => max(0, $step + 1),
                'positionStep' => $step,
                'lastAnimatedStep' => $step,
                'finished' => $maxStep >= 0 && $step >= $maxStep,
            ],
            'pending' => [
                'startStepIndex' => null,
                'endStepIndex' => null,
                'previewRewards' => [],
            ],
            'players' => [],
            'self' => [
                'userId' => 'preview-self',
                'displayName' => 'ME',
                'avatarUrl' => '',
                'lifetimeSteps' => max(0, $step + 1),
                'positionStep' => $step,
            ],
            'leaderboard' => [
                'all' => [],
                'weekly' => [],
            ],
        ]);
    }

    if ($action === 'save_draft') {
        if (!mileage_save_allowed()) {
            mileage_json([
                'ok' => false,
                'code' => 'FORBIDDEN',
                'message' => 'draft save is restricted',
            ], 403);
        }
        $payload = is_array($input['board'] ?? null) ? $input['board'] : [];
        $board = GachaMileageService::saveDraftDefinition($payload, $boardCode);
        mileage_json([
            'ok' => true,
            'saveAllowed' => true,
            'board' => $board,
            'hasDataUrl' => str_contains(json_encode($board, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', '"data:'),
            'versions' => GachaMileageService::versionManifest($boardCode),
        ]);
    }

    if ($action === 'publish_draft') {
        if (!mileage_save_allowed()) {
            mileage_json([
                'ok' => false,
                'code' => 'FORBIDDEN',
                'message' => 'draft publish is restricted',
            ], 403);
        }
        $payload = is_array($input['board'] ?? null) ? $input['board'] : null;
        $result = GachaMileageService::publishDraftDefinition($boardCode, $payload);
        mileage_json(['ok' => true, 'saveAllowed' => true] + $result);
    }

    if ($action === 'rollback_version') {
        if (!mileage_save_allowed()) {
            mileage_json([
                'ok' => false,
                'code' => 'FORBIDDEN',
                'message' => 'version rollback is restricted',
            ], 403);
        }
        $versionId = trim((string) ($input['versionId'] ?? ''));
        $publish = filter_var($input['publish'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $result = GachaMileageService::rollbackVersion($boardCode, $versionId, $publish);
        mileage_json(['ok' => true, 'saveAllowed' => true] + $result);
    }

    if ($action === 'upload_asset') {
        mileage_json(mileage_upload_asset($input));
    }

    if ($action === 'list_assets') {
        mileage_json(mileage_list_assets());
    }

    if ($action === 'preview_reward') {
        $reward = is_array($input['reward'] ?? null) ? $input['reward'] : [];
        mileage_json(GachaMileageService::previewRewardDefinition($reward, $boardCode));
    }

    if ($action === 'tool_bootstrap' || $action === 'board') {
        mileage_json([
            'ok' => true,
            'saveAllowed' => mileage_save_allowed(),
            'board' => GachaMileageService::boardDefinition($boardCode),
        ]);
    }

    if ($action === 'save_board') {
        if (!mileage_save_allowed()) {
            mileage_json([
                'ok' => false,
                'code' => 'FORBIDDEN',
                'message' => 'board save is restricted',
            ], 403);
        }

        $payload = $input['board'] ?? null;
        if (!is_array($payload)) {
            $payload = $input;
        }

        mileage_json([
            'ok' => true,
            'saveAllowed' => true,
            'board' => GachaMileageService::saveBoardDefinition(
                is_array($payload) ? $payload : [],
                $boardCode
            ),
        ]);
    }

    $userId = mileage_resolved_user_id($input);

    if ($action === 'bootstrap') {
        mileage_json(GachaMileageService::bootstrap($guildId, $userId, $boardCode));
    }

    if ($action === 'leaderboard') {
        mileage_json([
            'ok' => true,
            'leaderboard' => GachaMileageService::leaderboard($guildId, $boardCode, 300, $userId),
        ]);
    }

    if ($action === 'step_players') {
        $stepIndex = max(-1, (int) ($input['stepIndex'] ?? -1));
        mileage_json([
            'ok' => true,
            'stepIndex' => $stepIndex,
            'players' => GachaMileageService::stepPlayers($guildId, $boardCode, $stepIndex),
        ]);
    }

    if ($action === 'claim_pending') {
        if ($userId === '') {
            mileage_json([
                'ok' => false,
                'code' => 'AUTH_REQUIRED',
                'message' => 'login required',
                'requiresLogin' => true,
            ], 401);
        }

        mileage_json(GachaMileageService::claimPending($guildId, $userId, $boardCode));
    }

    mileage_json([
        'ok' => true,
        'summary' => GachaMileageService::summary($guildId, $userId, $boardCode),
    ]);
} catch (Throwable $exception) {
    if (mileage_db_is_unavailable($exception)) {
        error_log(sprintf(
            '[Mileage API degraded] action=%s boardCode=%s reason=%s',
            $action,
            $boardCode,
            $exception->getMessage()
        ));

        if ($action === 'bootstrap') {
            mileage_json(mileage_degraded_bootstrap($boardCode));
        }

        if ($action === 'leaderboard') {
            mileage_json([
                'ok' => true,
                'serviceUnavailable' => true,
                'serviceMessage' => 'player database unavailable',
                'leaderboard' => [
                    'all' => [],
                    'weekly' => [],
                ],
            ]);
        }

        if ($action === 'step_players') {
            mileage_json([
                'ok' => true,
                'serviceUnavailable' => true,
                'serviceMessage' => 'player database unavailable',
                'stepIndex' => max(-1, (int) ($input['stepIndex'] ?? -1)),
                'players' => [],
            ]);
        }

        if ($action === 'summary') {
            mileage_json([
                'ok' => true,
                'serviceUnavailable' => true,
                'serviceMessage' => 'player database unavailable',
                'summary' => mileage_degraded_summary($boardCode),
            ]);
        }

        if ($action === 'claim_pending') {
            mileage_json([
                'ok' => false,
                'code' => 'SERVICE_UNAVAILABLE',
                'message' => 'player database unavailable',
                'serviceUnavailable' => true,
            ], 503);
        }
    }

    $code = trim($exception->getMessage());
    $status = $code === 'AUTH_REQUIRED' ? 401 : ($code === 'FORBIDDEN' ? 403 : 422);
    mileage_json([
        'ok' => false,
        'code' => $code !== '' ? $code : 'MILEAGE_ERROR',
        'message' => $code !== '' ? $code : 'mileage request failed',
    ], $status);
}
