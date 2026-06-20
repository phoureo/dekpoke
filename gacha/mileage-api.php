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
    if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '::ffff:127.0.0.1') {
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

$input = mileage_input();
$action = strtolower(trim((string) ($input['action'] ?? 'summary')));
$boardCode = trim((string) ($input['boardCode'] ?? GachaMileageService::DEFAULT_BOARD_CODE));
$guildId = (string) Bootstrap::config('discord.guildId', '');

try {
    $userId = '';

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
            'leaderboard' => GachaMileageService::leaderboard($guildId, $boardCode),
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
