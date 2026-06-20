<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$config = null;
$bootstrapReady = false;
$GLOBALS['bootstrapReady'] = false;

try {
    require __DIR__ . '/../core/Bootstrap.php';
    Bootstrap::init();
    $config = GachaConfigService::load();
    $bootstrapReady = true;
    $GLOBALS['bootstrapReady'] = true;
    sync_runtime_reset_session_marker();
} catch (Throwable) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $config = fallback_gacha_config();
}
$GLOBALS['config'] = $config;

function fallback_gacha_config(): array
{
    return [
        'settings' => [
            'enabled' => true,
            'tokenSecret' => 'local-gacha-token-secret-change-me',
            'defaultButtonId' => 1,
            'startingBalances' => ['ticket' => 0, 'coin' => 0],
            'buttons' => [
                '1' => ['label' => 'Coin Spin', 'currency' => 'coin', 'cost' => 10, 'enabled' => true],
                '2' => ['label' => 'Ticket Spin', 'currency' => 'ticket', 'cost' => 1, 'enabled' => true],
            ],
        ],
        'tiers' => [
            ['id' => 'common', 'tier' => 'Common', 'rate' => 54, 'displayRate' => 54, 'active' => true],
            ['id' => 'rare', 'tier' => 'Rare', 'rate' => 28, 'displayRate' => 28, 'active' => true],
            ['id' => 'epic', 'tier' => 'Epic', 'rate' => 12, 'displayRate' => 12, 'active' => true],
            ['id' => 'legendary', 'tier' => 'Legendary', 'rate' => 5, 'displayRate' => 5, 'active' => true],
            ['id' => 'mythic', 'tier' => 'Mythic', 'rate' => 1, 'displayRate' => 1, 'active' => true],
        ],
        'prizes' => [
            ['id' => 'item-1-common', 'tierId' => 'common', 'type' => 'item', 'name' => 'Leather Backpack', 'image' => 'images/item-1.png', 'badge' => '', 'internalWeight' => 100, 'active' => true],
            ['id' => 'item-1-rare', 'tierId' => 'rare', 'type' => 'item', 'name' => 'Aqua Leather Backpack', 'image' => 'images/item-1.png', 'badge' => '', 'internalWeight' => 100, 'active' => true],
            ['id' => 'item-1-epic', 'tierId' => 'epic', 'type' => 'item', 'name' => 'Prism Leather Backpack', 'image' => 'images/item-1.png', 'badge' => '', 'internalWeight' => 100, 'active' => true],
            ['id' => 'item-1-legendary', 'tierId' => 'legendary', 'type' => 'item', 'name' => 'Golden Leather Backpack', 'image' => 'images/item-1.png', 'badge' => '', 'internalWeight' => 100, 'active' => true],
            ['id' => 'item-1-mythic', 'tierId' => 'mythic', 'type' => 'item', 'name' => 'Rainbow Leather Backpack', 'image' => 'images/item-1.png', 'badge' => 'SSS', 'internalWeight' => 100, 'active' => true],
        ],
    ];
}

function gacha_runtime_reset_setting_key(): string
{
    return 'gacha.runtime_reset_stamp';
}

function read_runtime_reset_stamp(): string
{
    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('Database')) {
        return '';
    }

    try {
        $row = Database::fetch(
            'SELECT settingValueJson
               FROM tbl_setting
              WHERE settingKey = :settingKey
              LIMIT 1',
            ['settingKey' => gacha_runtime_reset_setting_key()]
        );
    } catch (Throwable) {
        return '';
    }

    $raw = trim((string) ($row['settingValueJson'] ?? ''));
    if ($raw === '') {
        return '';
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return trim((string) ($decoded['stamp'] ?? ''));
    }

    return $raw;
}

function sync_runtime_reset_session_marker(): void
{
    $stamp = read_runtime_reset_stamp();
    if ($stamp === '') {
        return;
    }

    $sessionStamp = trim((string) ($_SESSION['gacha_runtime_reset_stamp'] ?? ''));
    if ($sessionStamp === $stamp) {
        return;
    }

    unset(
        $_SESSION['gacha_draws'],
        $_SESSION['gacha_active_draw_id'],
        $_SESSION['gacha_balances'],
        $_SESSION['gacha_credit'],
        $_SESSION['gacha_spin_stats']
    );
    $_SESSION['gacha_runtime_reset_stamp'] = $stamp;
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function close_session_write_lock(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

function json_response_and_continue(array $payload, callable $afterResponse, int $status = 200): void
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        $json = '{"ok":false,"code":"JSON_ENCODE_FAILED","error":"json encode failed"}';
        $status = 500;
    }

    close_session_write_lock();
    ignore_user_abort(true);

    http_response_code($status);
    header('Content-Length: ' . strlen($json));
    header('Connection: close');
    echo $json;

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        flush();
    }

    try {
        $afterResponse();
    } catch (Throwable) {
        // Background follow-up must never break the user-visible response.
    }

    exit;
}

function base64url_encode_value(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64url_decode_value(string $value): string|false
{
    $padded = strtr($value, '-_', '+/');
    $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
    return base64_decode($padded, true);
}

function token_secret(array $config): string
{
    $secret = trim((string) ($config['settings']['tokenSecret'] ?? ''));
    return $secret !== '' ? $secret : 'local-gacha-token-secret-change-me';
}

function sign_token_payload(string $payload, array $config): string
{
    return base64url_encode_value(hash_hmac('sha256', $payload, token_secret($config), true));
}

function create_draw_token(string $drawId, string $nonce, array $config): string
{
    $payload = base64url_encode_value(json_encode([
        'drawId' => $drawId,
        'nonce' => $nonce,
        'issuedAt' => time(),
    ], JSON_UNESCAPED_SLASHES));

    return $payload . '.' . sign_token_payload($payload, $config);
}

function prize_envelope_salt(): string
{
    return 'gacha-prize-envelope-v1';
}

function fnv1a32(string $value): int
{
    $hash = 2166136261;
    $length = strlen($value);
    for ($index = 0; $index < $length; $index += 1) {
        $hash ^= ord($value[$index]);
        $hash = ($hash * 16777619) & 0xffffffff;
    }

    return $hash ?: 0x6d2b79f5;
}

function prize_envelope_seed(string $drawToken, int $visualSeed, string $lockedType): int
{
    return fnv1a32($drawToken . '|' . $visualSeed . '|' . $lockedType . '|' . prize_envelope_salt());
}

function prize_mask_next_state(int $state): int
{
    $state ^= ($state << 13) & 0xffffffff;
    $state ^= ($state >> 17);
    $state ^= ($state << 5) & 0xffffffff;
    return $state & 0xffffffff;
}

function prize_mask_apply(string $value, int $seed): string
{
    $state = $seed;
    $output = '';
    $length = strlen($value);
    for ($index = 0; $index < $length; $index += 1) {
        $state = prize_mask_next_state($state);
        $output .= chr(ord($value[$index]) ^ ($state & 0xff));
    }

    return $output;
}

function prize_envelope_payload(?array $prize, array $draw, string $drawToken): ?array
{
    if (!is_array($prize) || !$prize || $drawToken === '') {
        return null;
    }

    $lockedType = (string) ($draw['lockedType'] ?? ($prize['tierId'] ?? $prize['rarity'] ?? 'common'));
    $visualSeed = (int) ($draw['visualSeed'] ?? 0);
    $json = json_encode($prize, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return null;
    }

    return [
        'v' => 1,
        'alg' => 'xorshift32-json-mask',
        'data' => base64url_encode_value(prize_mask_apply($json, prize_envelope_seed($drawToken, $visualSeed, $lockedType))),
    ];
}

function parse_draw_token(string $token, array $config): ?array
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        return null;
    }

    [$payload, $signature] = $parts;
    if (!hash_equals(sign_token_payload($payload, $config), $signature)) {
        return null;
    }

    $decoded = base64url_decode_value($payload);
    if ($decoded === false) {
        return null;
    }

    $data = json_decode($decoded, true);
    return is_array($data) && !empty($data['drawId']) && !empty($data['nonce']) ? $data : null;
}

function read_input(): array
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

function ensure_draw_store(): void
{
    if (!isset($_SESSION['gacha_draws']) || !is_array($_SESSION['gacha_draws'])) {
        $_SESSION['gacha_draws'] = [];
    }
}

function draw_status(array $draw): string
{
    $status = strtolower(trim((string) ($draw['drawStatus'] ?? 'active')));
    return in_array($status, ['prepared', 'active'], true) ? $status : 'active';
}

function draw_is_prepared(array $draw): bool
{
    return draw_status($draw) === 'prepared';
}

function draw_is_active(array $draw): bool
{
    return draw_status($draw) === 'active';
}

function requested_type_from_input(array $input): string
{
    return strtolower(trim((string) ($input['type'] ?? '')));
}

function draw_signature_value(string $requestedType = '', ?string $conditionTierId = null): string
{
    return 'requested:' . $requestedType . '|condition:' . ($conditionTierId ?? '');
}

function draw_request_context(array $config, array $input, int $count = 1): array
{
    $requestedType = requested_type_from_input($input);
    $conditionTierId = $requestedType === '' ? forced_tier_from_conditions($config) : null;

    return [
        'count' => max(1, $count),
        'requestedType' => $requestedType,
        'conditionTierId' => $conditionTierId,
        'signature' => draw_signature_value($requestedType, $conditionTierId),
    ];
}

function draw_matches_signature(array $draw, string $signature): bool
{
    $drawSignature = (string) ($draw['signature'] ?? '');
    return $drawSignature !== '' && hash_equals($drawSignature, $signature);
}

function draw_matches_request_context(array $draw, array $context): bool
{
    $lockedType = trim((string) ($draw['lockedType'] ?? ($draw['tier']['id'] ?? '')));
    if ($lockedType === '') {
        return false;
    }

    $requestedType = trim((string) ($context['requestedType'] ?? ''));
    if ($requestedType !== '' && !hash_equals($lockedType, $requestedType)) {
        return false;
    }

    $conditionTierId = trim((string) ($context['conditionTierId'] ?? ''));
    if ($requestedType === '' && $conditionTierId !== '' && !hash_equals($lockedType, $conditionTierId)) {
        return false;
    }

    return true;
}

function active_pending_draw(array $config): ?array
{
    ensure_draw_store();
    $drawId = (string) ($_SESSION['gacha_active_draw_id'] ?? '');
    if ($drawId === '') {
        return null;
    }

    $draw = $_SESSION['gacha_draws'][$drawId] ?? null;
    if (!is_array($draw) || !draw_is_active($draw) || !empty($draw['completedAt'])) {
        unset($_SESSION['gacha_active_draw_id']);
        $draw = null;
    }

    if (is_array($draw)) {
        return $draw;
    }

    $persistentDraw = persistent_pending_draw();
    if (!$persistentDraw) {
        return null;
    }

    $_SESSION['gacha_draws'][(string) $persistentDraw['drawId']] = $persistentDraw;
    $_SESSION['gacha_active_draw_id'] = (string) $persistentDraw['drawId'];
    return $persistentDraw;
}

function pending_draw_payload(array $draw, array $config): array
{
    $drawId = (string) ($draw['drawId'] ?? '');
    $nonce = (string) ($draw['nonce'] ?? '');
    $drawToken = $drawId !== '' && $nonce !== '' ? create_draw_token($drawId, $nonce, $config) : '';
    $resumeMode = draw_requires_forced_open($draw) ? 'open_ball' : 'refund';
    $spinStartedAt = (int) ($draw['createdAt'] ?? 0);
    return [
        'drawToken' => $drawToken,
        'roundRef' => $drawToken,
        'count' => max(1, (int) ($draw['count'] ?? 1)),
        'buttonId' => (int) ($draw['buttonId'] ?? 0),
        'currency' => (string) ($draw['currency'] ?? 'ticket'),
        'charged' => true,
        'chargedCost' => (int) ($draw['cost'] ?? 0),
        'usedFreeSpin' => !empty($draw['usedFreeSpin']),
        'freeSpinRewardEventId' => (int) ($draw['freeSpinRewardEventId'] ?? 0),
        'costPerSpin' => (int) ($draw['costPerSpin'] ?? 1),
        'balanceBefore' => (int) ($draw['balanceBefore'] ?? 0),
        'balanceAfter' => (int) ($draw['balanceAfter'] ?? 0),
        'balancesAfter' => is_array($draw['balancesAfter'] ?? null) ? $draw['balancesAfter'] : [],
        'lockedType' => (string) ($draw['lockedType'] ?? 'common'),
        'prizeEnvelope' => prize_envelope_payload(is_array($draw['prize'] ?? null) ? $draw['prize'] : null, $draw, $drawToken),
        'campaignCounter' => is_array($draw['campaignCounter'] ?? null) ? $draw['campaignCounter'] : null,
        'visualSeed' => (int) ($draw['visualSeed'] ?? 0),
        'createdAt' => (int) ($draw['createdAt'] ?? 0),
        'spinStartedAt' => $spinStartedAt,
        'forceOpenAvailableAt' => $spinStartedAt > 0 ? $spinStartedAt + 10 : 0,
        'revealedAt' => $draw['revealedAt'] ?? null,
        'ballIssuedAt' => $draw['ballIssuedAt'] ?? null,
        'ballSeenAt' => $draw['ballSeenAt'] ?? null,
        'prizeResolvedAt' => $draw['prizeResolvedAt'] ?? null,
        'refundedAt' => $draw['refundedAt'] ?? null,
        'resumeMode' => $resumeMode,
        'needsRefund' => false,
        'requiresForcedOpen' => $resumeMode === 'open_ball',
    ];
}

function prepared_draw_payload(array $draw, array $config): array
{
    $drawId = (string) ($draw['drawId'] ?? '');
    $nonce = (string) ($draw['nonce'] ?? '');
    $drawToken = $drawId !== '' && $nonce !== '' ? create_draw_token($drawId, $nonce, $config) : '';
    return [
        'drawToken' => $drawToken,
        'roundRef' => $drawToken,
        'count' => max(1, (int) ($draw['candidateCount'] ?? $draw['count'] ?? 1)),
        'candidateCount' => max(1, (int) ($draw['candidateCount'] ?? $draw['count'] ?? 1)),
        'buttonId' => (int) ($draw['buttonId'] ?? 0),
        'candidateButtonId' => (int) ($draw['buttonId'] ?? 0),
        'currency' => (string) ($draw['currency'] ?? default_currency($config)),
        'lockedType' => (string) ($draw['lockedType'] ?? 'common'),
        'prizeEnvelope' => prize_envelope_payload(is_array($draw['prize'] ?? null) ? $draw['prize'] : null, $draw, $drawToken),
        'visualSeed' => (int) ($draw['visualSeed'] ?? 0),
        'prepareMode' => (string) ($draw['prepareMode'] ?? 'warm'),
        'requestedType' => (string) ($draw['requestedType'] ?? ''),
        'signature' => (string) ($draw['signature'] ?? ''),
        'condition' => is_array($draw['condition'] ?? null) ? $draw['condition'] : null,
        'preparedAt' => (int) ($draw['preparedAt'] ?? $draw['createdAt'] ?? time()),
        'createdAt' => (int) ($draw['createdAt'] ?? 0),
        'charged' => false,
    ];
}

function gacha_free_spin_payload(bool $sync = true): array
{
    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('GachaFreeSpinService')) {
        return [
            'available' => 0,
            'canUse' => false,
            'label' => '',
            'source' => '',
            'sourceName' => '',
            'earnedAt' => '',
            'expiresAt' => '',
            'progress' => ['date' => date('Y-m-d'), 'voiceSeconds' => 0, 'segment' => 0],
        ];
    }

    $owner = gacha_balance_owner();
    if (!$owner) {
        return (new GachaFreeSpinService())->payload('', '', false);
    }

    try {
        return (new GachaFreeSpinService())->payload(
            (string) ($owner['guildId'] ?? ''),
            (string) ($owner['userId'] ?? ''),
            $sync
        );
    } catch (Throwable) {
        return (new GachaFreeSpinService())->payload('', '', false);
    }
}

function draw_requires_forced_open(array $draw): bool
{
    if (!empty($draw['completedAt'])) {
        return false;
    }

    return empty($draw['refundedAt']);
}

function draw_token_from_input(array $input): string
{
    return trim((string) ($input['drawToken'] ?? $input['roundRef'] ?? ''));
}

function persist_active_draw(array $draw): array
{
    ensure_draw_store();
    $drawId = (string) ($draw['drawId'] ?? '');
    if ($drawId === '') {
        return $draw;
    }

    $draw['drawStatus'] = 'active';
    $_SESSION['gacha_draws'][$drawId] = $draw;
    $_SESSION['gacha_active_draw_id'] = $drawId;
    store_persistent_pending_draw($draw);
    sync_spin_history_for_draw($draw);
    return $draw;
}

function persist_prepared_draw(array $draw): array
{
    ensure_draw_store();
    $drawId = (string) ($draw['drawId'] ?? '');
    if ($drawId === '') {
        return $draw;
    }

    $draw['drawStatus'] = 'prepared';
    $_SESSION['gacha_draws'][$drawId] = $draw;
    if ((string) ($_SESSION['gacha_active_draw_id'] ?? '') === $drawId) {
        unset($_SESSION['gacha_active_draw_id']);
    }
    store_persistent_pending_draw($draw);
    return $draw;
}

function sync_spin_history_for_draw(array $draw): void
{
    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('GachaSpinHistoryService')) {
        return;
    }

    $owner = gacha_balance_owner();
    if (!$owner) {
        return;
    }

    try {
        GachaSpinHistoryService::syncFromDraw(
            (string) ($owner['guildId'] ?? ''),
            (string) ($owner['userId'] ?? ''),
            $draw
        );
    } catch (Throwable) {
        // History tracking must never block the game flow.
    }
}

function mark_gacha_report_live_update(string $updateType, string $drawId = '', array $metadata = []): void
{
    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('LiveUpdateService')) {
        return;
    }

    try {
        LiveUpdateService::mark(
            ['gacha_report'],
            $updateType,
            'gacha_draw',
            $drawId !== '' ? $drawId : null,
            $metadata,
            gacha_guild_id(),
            'gacha_spin',
            $drawId !== '' ? $drawId : null
        );
    } catch (Throwable) {
        // Live updates are best-effort only.
    }
}

function request_pending_draw(array $input, array $config, string $requiredStatus = 'active'): array
{
    $token = draw_token_from_input($input);
    $tokenData = parse_draw_token($token, $config);

    if (!$tokenData) {
        json_response([
            'ok' => false,
            'code' => 'INVALID_TOKEN',
            'error' => 'invalid draw token',
            'balance' => balances($config)[default_currency($config)] ?? 0,
        ], 400);
    }

    $drawId = (string) $tokenData['drawId'];
    $draw = $_SESSION['gacha_draws'][$drawId] ?? null;
    if (!is_array($draw)) {
        $persistentDraw = persistent_pending_draw($drawId, $requiredStatus);
        if ($persistentDraw) {
            $_SESSION['gacha_draws'][$drawId] = $persistentDraw;
            if ($requiredStatus === 'active') {
                $_SESSION['gacha_active_draw_id'] = $drawId;
            }
            $draw = $persistentDraw;
        }
    }

    $statusOk = $requiredStatus === 'prepared'
        ? (is_array($draw) && draw_is_prepared($draw))
        : (is_array($draw) && draw_is_active($draw));

    if (!$statusOk || !hash_equals((string) ($draw['nonce'] ?? ''), (string) $tokenData['nonce'])) {
        json_response([
            'ok' => false,
            'code' => 'DRAW_NOT_FOUND',
            'error' => 'draw not found',
            'balance' => balances($config)[default_currency($config)] ?? 0,
        ], 404);
    }

    return [$drawId, $draw];
}

function lookup_draw_by_token(array $input, array $config, array $allowedStatuses = ['active']): array
{
    $token = draw_token_from_input($input);
    $tokenData = parse_draw_token($token, $config);
    if (!$tokenData) {
        return [
            'ok' => false,
            'code' => 'INVALID_TOKEN',
            'error' => 'invalid draw token',
            'status' => 400,
        ];
    }

    ensure_draw_store();
    $drawId = (string) ($tokenData['drawId'] ?? '');
    $nonce = (string) ($tokenData['nonce'] ?? '');
    foreach ($allowedStatuses as $status) {
        $draw = $_SESSION['gacha_draws'][$drawId] ?? null;
        $statusOk = $status === 'prepared'
            ? (is_array($draw) && draw_is_prepared($draw))
            : (is_array($draw) && draw_is_active($draw));
        if (!$statusOk) {
            $draw = persistent_pending_draw($drawId, (string) $status);
            if ($draw) {
                $_SESSION['gacha_draws'][$drawId] = $draw;
                if ($status === 'active') {
                    $_SESSION['gacha_active_draw_id'] = $drawId;
                }
            }
        }

        $statusOk = $status === 'prepared'
            ? (is_array($draw) && draw_is_prepared($draw))
            : (is_array($draw) && draw_is_active($draw));
        if ($statusOk && hash_equals((string) ($draw['nonce'] ?? ''), $nonce)) {
            return [
                'ok' => true,
                'drawId' => $drawId,
                'draw' => $draw,
                'drawStatus' => (string) $status,
                'token' => $token,
                'tokenData' => $tokenData,
            ];
        }
    }

    return [
        'ok' => false,
        'code' => 'DRAW_NOT_FOUND',
        'error' => 'draw not found',
        'status' => 404,
    ];
}

function ensure_persistent_draw_schema(): bool
{
    static $ready = false;
    if ($ready) {
        return true;
    }
    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('Database')) {
        return false;
    }

    try {
        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_pending_draw (
                gachaPendingDrawId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                drawId varchar(64) NOT NULL,
                drawStatus varchar(40) NOT NULL DEFAULT "active",
                drawJson longtext NOT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaPendingDrawId),
                UNIQUE KEY uq_tbl_gacha_pending_draw_draw (drawId),
                KEY idx_tbl_gacha_pending_draw_user (guildId, userId, drawStatus, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $ready = true;
        return true;
    } catch (Throwable) {
        return false;
    }
}

function persistent_draw_owner(): ?array
{
    if (!ensure_persistent_draw_schema()) {
        return null;
    }

    return gacha_balance_owner();
}

function store_persistent_pending_draw(array $draw): void
{
    $owner = persistent_draw_owner();
    if (!$owner || empty($draw['drawId'])) {
        return;
    }

    $drawStatus = draw_status($draw);
    try {
        if ($drawStatus === 'active') {
            Database::execute(
                'DELETE FROM tbl_gacha_pending_draw
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND drawStatus = "active"
                    AND drawId <> :drawId',
                [
                    'guildId' => (string) $owner['guildId'],
                    'userId' => (string) $owner['userId'],
                    'drawId' => (string) $draw['drawId'],
                ]
            );
        } elseif ($drawStatus === 'prepared') {
            $rows = Database::fetchAll(
                'SELECT drawId, drawJson
                   FROM tbl_gacha_pending_draw
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND drawStatus = "prepared"',
                [
                    'guildId' => (string) $owner['guildId'],
                    'userId' => (string) $owner['userId'],
                ]
            );
            $signature = (string) ($draw['signature'] ?? '');
            foreach ($rows as $row) {
                $rowDrawId = (string) ($row['drawId'] ?? '');
                if ($rowDrawId === '' || $rowDrawId === (string) $draw['drawId']) {
                    continue;
                }
                $rowDraw = json_decode((string) ($row['drawJson'] ?? ''), true);
                if (!is_array($rowDraw) || !draw_is_prepared($rowDraw)) {
                    Database::execute(
                        'DELETE FROM tbl_gacha_pending_draw
                          WHERE guildId = :guildId
                            AND userId = :userId
                            AND drawId = :drawId',
                        [
                            'guildId' => (string) $owner['guildId'],
                            'userId' => (string) $owner['userId'],
                            'drawId' => $rowDrawId,
                        ]
                    );
                    continue;
                }
                if ($signature !== '' && draw_matches_signature($rowDraw, $signature)) {
                    Database::execute(
                        'DELETE FROM tbl_gacha_pending_draw
                          WHERE guildId = :guildId
                            AND userId = :userId
                            AND drawId = :drawId',
                        [
                            'guildId' => (string) $owner['guildId'],
                            'userId' => (string) $owner['userId'],
                            'drawId' => $rowDrawId,
                        ]
                    );
                }
            }
        }

        Database::execute(
            'INSERT INTO tbl_gacha_pending_draw (guildId, userId, drawId, drawStatus, drawJson, updateDate)
             VALUES (:guildId, :userId, :drawId, :drawStatus, :drawJson, :updateDate)
             ON DUPLICATE KEY UPDATE
                drawStatus = VALUES(drawStatus),
                drawJson = VALUES(drawJson),
                updateDate = VALUES(updateDate)',
            [
                'guildId' => (string) $owner['guildId'],
                'userId' => (string) $owner['userId'],
                'drawId' => (string) $draw['drawId'],
                'drawStatus' => $drawStatus,
                'drawJson' => json_encode($draw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
    } catch (Throwable) {
        // Session storage still protects the current browser if the DB write fails.
    }
}

function persistent_pending_draw(?string $drawId = null, string $drawStatus = 'active'): ?array
{
    $owner = persistent_draw_owner();
    if (!$owner) {
        return null;
    }

    try {
        if ($drawId !== null && $drawId !== '') {
            $row = Database::fetch(
                'SELECT drawJson
                   FROM tbl_gacha_pending_draw
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND drawId = :drawId
                    AND drawStatus = :drawStatus
                  LIMIT 1',
                [
                    'guildId' => (string) $owner['guildId'],
                    'userId' => (string) $owner['userId'],
                    'drawId' => $drawId,
                    'drawStatus' => $drawStatus,
                ]
            );
        } else {
            $row = Database::fetch(
                'SELECT drawJson
                   FROM tbl_gacha_pending_draw
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND drawStatus = :drawStatus
                  ORDER BY createDate DESC, gachaPendingDrawId DESC
                  LIMIT 1',
                [
                    'guildId' => (string) $owner['guildId'],
                    'userId' => (string) $owner['userId'],
                    'drawStatus' => $drawStatus,
                ]
            );
        }

        if (!$row) {
            return null;
        }
        $draw = json_decode((string) ($row['drawJson'] ?? ''), true);
        if (!is_array($draw) || !empty($draw['completedAt'])) {
            return null;
        }
        if ($drawStatus === 'prepared' && !draw_is_prepared($draw)) {
            return null;
        }
        if ($drawStatus === 'active' && !draw_is_active($draw)) {
            return null;
        }
        return $draw;
    } catch (Throwable) {
        return null;
    }
}

function persistent_prepared_draws(): array
{
    $owner = persistent_draw_owner();
    if (!$owner) {
        return [];
    }

    try {
        $rows = Database::fetchAll(
            'SELECT drawId, drawJson
               FROM tbl_gacha_pending_draw
              WHERE guildId = :guildId
                AND userId = :userId
                AND drawStatus = "prepared"
              ORDER BY createDate DESC, gachaPendingDrawId DESC',
            [
                'guildId' => (string) $owner['guildId'],
                'userId' => (string) $owner['userId'],
            ]
        );
    } catch (Throwable) {
        return [];
    }

    $draws = [];
    foreach ($rows as $row) {
        $draw = json_decode((string) ($row['drawJson'] ?? ''), true);
        if (is_array($draw) && draw_is_prepared($draw) && empty($draw['completedAt'])) {
            $draws[] = $draw;
        }
    }

    return $draws;
}

function forget_persistent_pending_draw(string $drawId = '', string $drawStatus = 'active'): void
{
    $owner = persistent_draw_owner();
    if (!$owner) {
        return;
    }

    try {
        if ($drawId !== '') {
            Database::execute(
                'DELETE FROM tbl_gacha_pending_draw
                  WHERE guildId = :guildId
                    AND userId = :userId
                    AND drawId = :drawId
                    AND drawStatus = :drawStatus',
                [
                    'guildId' => (string) $owner['guildId'],
                    'userId' => (string) $owner['userId'],
                    'drawId' => $drawId,
                    'drawStatus' => $drawStatus,
                ]
            );
            return;
        }

        Database::execute(
            'DELETE FROM tbl_gacha_pending_draw
              WHERE guildId = :guildId
                AND userId = :userId
                AND drawStatus = :drawStatus',
            [
                'guildId' => (string) $owner['guildId'],
                'userId' => (string) $owner['userId'],
                'drawStatus' => $drawStatus,
            ]
        );
    } catch (Throwable) {
        // Keep session cleanup independent from persistent cleanup.
    }
}

function forget_session_draw(string $drawId): void
{
    ensure_draw_store();
    unset($_SESSION['gacha_draws'][$drawId]);
    if ((string) ($_SESSION['gacha_active_draw_id'] ?? '') === $drawId) {
        unset($_SESSION['gacha_active_draw_id']);
    }
}

function forget_draw_everywhere(string $drawId, string $drawStatus = 'prepared'): void
{
    if ($drawId === '') {
        return;
    }

    forget_session_draw($drawId);
    forget_persistent_pending_draw($drawId, $drawStatus);
}

function config_tier_by_id(array $config, string $tierId): ?array
{
    foreach ($config['tiers'] ?? [] as $tier) {
        if ((string) ($tier['id'] ?? '') === $tierId) {
            return $tier;
        }
    }

    return null;
}

function config_prize_by_id(array $config, string $prizeId): ?array
{
    foreach ($config['prizes'] ?? [] as $prize) {
        if ((string) ($prize['id'] ?? '') === $prizeId) {
            return $prize;
        }
    }

    return null;
}

function prepared_draw_still_available(array $config, array $draw, ?string $expectedSignature = null): bool
{
    if (!draw_is_prepared($draw) || !empty($draw['completedAt'])) {
        return false;
    }

    if ($expectedSignature !== null && !draw_matches_signature($draw, $expectedSignature)) {
        return false;
    }

    $lockedType = trim((string) ($draw['lockedType'] ?? ($draw['tier']['id'] ?? '')));
    $prizeId = trim((string) (($draw['prize']['id'] ?? '')));
    if ($lockedType === '' || $prizeId === '') {
        return false;
    }

    $tier = config_tier_by_id($config, $lockedType);
    if (!$tier || empty($tier['active'])) {
        return false;
    }

    $prize = config_prize_by_id($config, $prizeId);
    if (!$prize) {
        return false;
    }

    if (empty($prize['active']) || (float) ($prize['internalWeight'] ?? 0) <= 0) {
        return false;
    }

    return (string) ($prize['tierId'] ?? '') === $lockedType;
}

function prepared_draw_sort_key(array $draw): int
{
    return max(0, (int) ($draw['preparedAt'] ?? $draw['createdAt'] ?? 0));
}

function prepared_next_draw(array $config, string $signature): ?array
{
    ensure_draw_store();
    $best = null;

    foreach ($_SESSION['gacha_draws'] as $drawId => $draw) {
        if (!is_array($draw) || !draw_is_prepared($draw) || !draw_matches_signature($draw, $signature)) {
            continue;
        }
        if (!prepared_draw_still_available($config, $draw, $signature)) {
            forget_draw_everywhere((string) $drawId, 'prepared');
            continue;
        }
        if (!$best || prepared_draw_sort_key($draw) >= prepared_draw_sort_key($best)) {
            $best = $draw;
        }
    }

    if ($best) {
        return $best;
    }

    foreach (persistent_prepared_draws() as $draw) {
        $drawId = (string) ($draw['drawId'] ?? '');
        if ($drawId === '' || !draw_matches_signature($draw, $signature)) {
            continue;
        }
        if (!prepared_draw_still_available($config, $draw, $signature)) {
            forget_draw_everywhere($drawId, 'prepared');
            continue;
        }
        $_SESSION['gacha_draws'][$drawId] = $draw;
        if (!$best || prepared_draw_sort_key($draw) >= prepared_draw_sort_key($best)) {
            $best = $draw;
        }
    }

    return $best;
}

function recycled_prepared_draw(array $draw, array $context, array $button, string $prepareMode): array
{
    $draw['drawStatus'] = 'prepared';
    $draw['prepareMode'] = $prepareMode;
    $draw['candidateCount'] = max(1, (int) ($context['count'] ?? 1));
    $draw['requestedType'] = (string) ($context['requestedType'] ?? '');
    $draw['signature'] = (string) ($context['signature'] ?? '');
    $draw['condition'] = !empty($context['conditionTierId'])
        ? ['type' => 'pity', 'tierId' => (string) $context['conditionTierId']]
        : null;
    $draw['buttonId'] = (int) ($button['buttonId'] ?? 0);
    $draw['currency'] = (string) ($button['currency'] ?? ($draw['currency'] ?? 'ticket'));
    $draw['count'] = max(1, (int) ($context['count'] ?? 1));
    $draw['preparedAt'] = time();
    $draw['reusedPendingReward'] = true;
    $draw['cost'] = 0;
    $draw['costPerSpin'] = 0;
    $draw['balanceBefore'] = 0;
    $draw['balanceAfter'] = 0;
    $draw['balancesAfter'] = [];
    $draw['usedFreeSpin'] = false;
    $draw['freeSpinRewardEventId'] = 0;
    $draw['freeSpinSource'] = '';
    $draw['campaignCounter'] = null;
    $draw['revealedAt'] = null;
    $draw['ballIssuedAt'] = null;
    $draw['ballSeenAt'] = null;
    $draw['prizeResolvedAt'] = null;
    $draw['completedAt'] = null;
    $draw['refundBlockedAt'] = null;
    $draw['refundBlockedReason'] = null;
    unset(
        $draw['refundedAt'],
        $draw['refundAmount'],
        $draw['refundCurrency'],
        $draw['refundBalanceBefore'],
        $draw['refundBalanceAfter']
    );

    return $draw;
}

function rollback_campaign_counter_for_spin(string $drawId): void
{
    if ($drawId === '' || !($GLOBALS['bootstrapReady'] ?? false) || !class_exists('GachaCampaignCounterService')) {
        return;
    }

    try {
        GachaCampaignCounterService::rollbackSpin(gacha_guild_id(), $drawId);
    } catch (Throwable) {
        // Refunds should not fail just because the optional campaign counter cannot roll back.
    }
}

function refund_pending_draw(array $draw, array $config): array
{
    $drawId = (string) ($draw['drawId'] ?? '');
    if ($drawId === '' || !empty($draw['completedAt'])) {
        return $draw;
    }

    ensure_draw_store();
    if (draw_requires_forced_open($draw)) {
        $draw['refundBlockedAt'] = time();
        $draw['refundBlockedReason'] = 'ball_already_seen';
        return persist_active_draw($draw);
    }

    if (!empty($draw['refundedAt'])) {
        return persist_active_draw($draw);
    }

    $currency = (string) ($draw['currency'] ?? 'ticket');
    $amount = max(0, (int) ($draw['cost'] ?? 0));
    $balancesBefore = balances($config);
    $balanceBefore = (int) ($balancesBefore[$currency] ?? 0);
    $balancesAfter = $balancesBefore;

    if ($amount > 0) {
        $balancesAfter = set_balance($currency, $balanceBefore + $amount, $config, [
            'ledgerType' => 'credit',
            'sourceType' => 'gacha_spin_refund',
            'sourceId' => $drawId,
            'currency' => $currency,
            'refundAmount' => $amount,
            'reason' => 'previous_gacha_spin_not_completed',
        ]);
    } elseif (!empty($draw['usedFreeSpin']) && class_exists('GachaFreeSpinService')) {
        $owner = gacha_balance_owner();
        if ($owner) {
            try {
                (new GachaFreeSpinService())->restoreForDraw(
                    (string) ($owner['guildId'] ?? ''),
                    (string) ($owner['userId'] ?? ''),
                    $drawId
                );
            } catch (Throwable) {
                // Restoring a free-spin marker should not block refund cleanup.
            }
        }
    }

    rollback_campaign_counter_for_spin($drawId);
    rollback_spin_stats((string) ($draw['lockedType'] ?? ''), max(1, (int) ($draw['count'] ?? 1)));

    $draw['refundedAt'] = time();
    $draw['refundAmount'] = $amount;
    $draw['refundCurrency'] = $currency;
    $draw['refundBalanceBefore'] = $balanceBefore;
    $draw['refundBalanceAfter'] = (int) ($balancesAfter[$currency] ?? $balanceBefore);
    $draw['balancesAfter'] = $balancesAfter;
    $draw['balanceAfter'] = (int) ($balancesAfter[$currency] ?? $balanceBefore);

    return persist_active_draw($draw);
}

function ensure_spin_stats(): void
{
    if (!isset($_SESSION['gacha_spin_stats']) || !is_array($_SESSION['gacha_spin_stats'])) {
        $_SESSION['gacha_spin_stats'] = [
            'totalDraws' => 0,
            'tierCounts' => [],
            'updatedAt' => time(),
        ];
    }
}

function spin_stats(): array
{
    ensure_spin_stats();
    return $_SESSION['gacha_spin_stats'];
}

function record_spin_stats(string $tierId, int $count = 1): void
{
    ensure_spin_stats();
    $_SESSION['gacha_spin_stats']['totalDraws'] = max(0, (int) ($_SESSION['gacha_spin_stats']['totalDraws'] ?? 0)) + $count;
    $_SESSION['gacha_spin_stats']['tierCounts'][$tierId] = max(0, (int) ($_SESSION['gacha_spin_stats']['tierCounts'][$tierId] ?? 0)) + $count;
    $_SESSION['gacha_spin_stats']['updatedAt'] = time();
}

function rollback_spin_stats(string $tierId, int $count = 1): void
{
    ensure_spin_stats();
    $count = max(0, $count);
    $_SESSION['gacha_spin_stats']['totalDraws'] = max(0, (int) ($_SESSION['gacha_spin_stats']['totalDraws'] ?? 0) - $count);
    if ($tierId !== '') {
        $_SESSION['gacha_spin_stats']['tierCounts'][$tierId] = max(0, (int) ($_SESSION['gacha_spin_stats']['tierCounts'][$tierId] ?? 0) - $count);
    }
    $_SESSION['gacha_spin_stats']['updatedAt'] = time();
}

function forced_tier_from_conditions(array $config): ?string
{
    $conditions = is_array($config['conditions'] ?? null) ? $config['conditions'] : [];
    $pityEvery = max(0, (int) ($conditions['pityEvery'] ?? 0));
    $pityTierId = trim((string) ($conditions['pityTierId'] ?? ''));
    if ($pityEvery <= 0 || $pityTierId === '') {
        return null;
    }

    $nextDraw = max(0, (int) (spin_stats()['totalDraws'] ?? 0)) + 1;
    if ($nextDraw % $pityEvery !== 0) {
        return null;
    }

    foreach ($config['tiers'] ?? [] as $tier) {
        if (($tier['id'] ?? '') === $pityTierId && !empty($tier['active'])) {
            return $pityTierId;
        }
    }

    return null;
}

function starting_balances(array $config): array
{
    return [
        'ticket' => max(0, (int) ($config['settings']['startingBalances']['ticket'] ?? 0)),
        'coin' => max(0, (int) ($config['settings']['startingBalances']['coin'] ?? 0)),
        'gem' => 0,
        'potion' => 0,
    ];
}

function normalize_balances(array $balances, array $config): array
{
    $normalized = starting_balances($config);
    foreach ($balances as $currency => $amount) {
        if (!is_scalar($amount)) {
            continue;
        }
        $normalized[(string) $currency] = max(0, (int) $amount);
    }

    return $normalized;
}

function sync_balance_session(array $balances, array $config): array
{
    $normalized = normalize_balances($balances, $config);
    $_SESSION['gacha_balances'] = $normalized;
    $_SESSION['gacha_credit'] = $normalized['ticket'] ?? 0;
    return $normalized;
}

function gacha_balance_owner(): ?array
{
    if (!($GLOBALS['bootstrapReady'] ?? false)) {
        return null;
    }

    if (class_exists('PlayerAuth')) {
        $player = PlayerAuth::currentUser();
        if (is_array($player) && !empty($player['userId'])) {
            return [
                'guildId' => gacha_guild_id(),
                'userId' => (string) $player['userId'],
                'source' => 'player',
            ];
        }
    }

    return null;
}

function gacha_is_authenticated(): bool
{
    return gacha_balance_owner() !== null;
}

function guest_balances(array $config): array
{
    $balances = starting_balances($config);
    foreach ($balances as $unitCode => $amount) {
        $balances[$unitCode] = 0;
    }

    return normalize_balances($balances, $config);
}

function balances(array $config): array
{
    $owner = gacha_balance_owner();
    if ($owner && ($GLOBALS['bootstrapReady'] ?? false) && class_exists('ShopUnitService')) {
        try {
            return sync_balance_session(
                ShopUnitService::walletBalances((string) $owner['guildId'], (string) $owner['userId']),
                $config
            );
        } catch (Throwable) {
            // Fall back to session cache when the wallet store is unavailable.
        }
    }

    if (!isset($_SESSION['gacha_balances']) || !is_array($_SESSION['gacha_balances'])) {
        $_SESSION['gacha_balances'] = starting_balances($config);
        if (isset($_SESSION['gacha_credit'])) {
            $_SESSION['gacha_balances']['ticket'] = max(0, (int) $_SESSION['gacha_credit']);
        }
    }

    return sync_balance_session($_SESSION['gacha_balances'], $config);
}

function set_balance(string $currency, int $value, array $config, array $ledgerContext = []): array
{
    $targetValue = max(0, $value);
    $owner = gacha_balance_owner();
    if ($owner && ($GLOBALS['bootstrapReady'] ?? false) && class_exists('ShopUnitService')) {
        $balances = balances($config);
        $currentValue = (int) ($balances[$currency] ?? 0);
        $delta = $targetValue - $currentValue;
        if ($delta !== 0) {
            ShopUnitService::adjustWalletBalance(
                (string) $owner['guildId'],
                (string) $owner['userId'],
                $currency,
                $delta,
                (string) ($ledgerContext['ledgerType'] ?? ($delta > 0 ? 'credit' : 'debit')),
                isset($ledgerContext['sourceType']) ? (string) $ledgerContext['sourceType'] : null,
                isset($ledgerContext['sourceId']) ? (string) $ledgerContext['sourceId'] : null,
                $ledgerContext
            );
        }
        return balances($config);
    }

    $balances = balances($config);
    $balances[$currency] = $targetValue;
    return sync_balance_session($balances, $config);
}

function debit_balance(string $currency, int $amount, array $config, array $ledgerContext = []): array
{
    $amount = max(0, $amount);
    $balances = balances($config);
    $balanceBefore = (int) ($balances[$currency] ?? 0);
    if ($balanceBefore < $amount) {
        return [
            'ok' => false,
            'balanceBefore' => $balanceBefore,
            'balancesAfter' => $balances,
        ];
    }

    $owner = gacha_balance_owner();
    if ($owner && ($GLOBALS['bootstrapReady'] ?? false) && class_exists('ShopUnitService')) {
        try {
            ShopUnitService::adjustWalletBalance(
                (string) $owner['guildId'],
                (string) $owner['userId'],
                $currency,
                -$amount,
                (string) ($ledgerContext['ledgerType'] ?? 'debit'),
                isset($ledgerContext['sourceType']) ? (string) $ledgerContext['sourceType'] : null,
                isset($ledgerContext['sourceId']) ? (string) $ledgerContext['sourceId'] : null,
                $ledgerContext
            );
        } catch (RuntimeException $error) {
            if ($error->getMessage() === 'INSUFFICIENT_BALANCE') {
                $freshBalances = balances($config);
                return [
                    'ok' => false,
                    'balanceBefore' => (int) ($freshBalances[$currency] ?? 0),
                    'balancesAfter' => $freshBalances,
                ];
            }

            throw $error;
        }

        $balancesAfter = balances($config);
        return [
            'ok' => true,
            'balanceBefore' => $balanceBefore,
            'balancesAfter' => $balancesAfter,
        ];
    }

    $balancesAfter = set_balance($currency, $balanceBefore - $amount, $config, $ledgerContext);
    return [
        'ok' => true,
        'balanceBefore' => $balanceBefore,
        'balancesAfter' => $balancesAfter,
    ];
}

function default_currency(array $config, int $buttonId = 0): string
{
    $button = spin_button($config, $buttonId);
    return (string) ($button['currency'] ?? 'ticket');
}

function spin_button(array $config, int $buttonId): array
{
    if (class_exists('GachaConfigService')) {
        return GachaConfigService::spinButton($config, $buttonId);
    }

    $buttonId = $buttonId > 0 ? $buttonId : (int) ($config['settings']['defaultButtonId'] ?? 1);
    $button = $config['settings']['buttons'][(string) $buttonId] ?? $config['settings']['buttons']['1'];
    $button['buttonId'] = $buttonId;
    return $button;
}

function shop_units_payload(): array
{
    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('ShopUnitService')) {
        return [
            'coin' => ['unitCode' => 'coin', 'displayName' => 'Coin', 'shortName' => 'Coin', 'icon' => 'fa-solid fa-coins'],
            'ticket' => ['unitCode' => 'ticket', 'displayName' => 'Ticket', 'shortName' => 'Ticket', 'icon' => 'fa-solid fa-ticket'],
        ];
    }

    try {
        $units = [];
        foreach (ShopUnitService::units(true) as $unit) {
            $units[(string) $unit['unitCode']] = [
                'unitCode' => (string) $unit['unitCode'],
                'displayName' => (string) $unit['displayName'],
                'shortName' => (string) $unit['shortName'],
                'icon' => (string) $unit['icon'],
            ];
        }
        return $units;
    } catch (Throwable) {
        return [];
    }
}

function gacha_guild_id(): string
{
    if (($GLOBALS['bootstrapReady'] ?? false) && class_exists('Bootstrap')) {
        return (string) Bootstrap::config('discord.guildId', 'local');
    }

    return 'local';
}

function gacha_process_expired_role_grants(): void
{
    static $processed = false;
    if ($processed) {
        return;
    }
    $processed = true;

    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('GachaRoleGrantService')) {
        return;
    }

    try {
        (new GachaRoleGrantService())->processExpired(10);
    } catch (Throwable) {
        // Do not block game flow when grant cleanup fails.
    }
}

function campaign_counter_payload(): array
{
    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('GachaCampaignCounterService')) {
        return [
            'enabled' => false,
            'campaignCode' => 'special_5000',
            'current' => 0,
            'min' => 0,
            'max' => 5000,
            'displayValue' => '0000',
            'unavailable' => true,
        ];
    }

    try {
        return GachaCampaignCounterService::status(gacha_guild_id());
    } catch (Throwable) {
        return [
            'enabled' => false,
            'campaignCode' => 'special_5000',
            'current' => 0,
            'min' => 0,
            'max' => 5000,
            'displayValue' => '0000',
            'unavailable' => true,
        ];
    }
}

function offline_reward_summary_payload(): array
{
    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('GachaOfflineRewardSummaryService')) {
        return [
            'enabled' => false,
            'initialized' => false,
            'hasSummary' => false,
            'count' => 0,
            'snapshot' => [
                'reward' => ['date' => '', 'id' => 0],
                'item' => ['date' => '', 'id' => 0],
                'role' => ['date' => '', 'id' => 0],
                'spin' => ['date' => '', 'id' => 0],
            ],
            'entries' => [],
            'totals' => [
                'units' => [],
                'items' => 0,
                'roles' => 0,
                'spins' => 0,
            ],
        ];
    }

    $owner = gacha_balance_owner();
    if (!$owner) {
        return [
            'enabled' => false,
            'initialized' => false,
            'hasSummary' => false,
            'count' => 0,
            'snapshot' => [
                'reward' => ['date' => '', 'id' => 0],
                'item' => ['date' => '', 'id' => 0],
                'role' => ['date' => '', 'id' => 0],
                'spin' => ['date' => '', 'id' => 0],
            ],
            'entries' => [],
            'totals' => [
                'units' => [],
                'items' => 0,
                'roles' => 0,
                'spins' => 0,
            ],
        ];
    }

    try {
        return (new GachaOfflineRewardSummaryService())->payload(
            (string) ($owner['guildId'] ?? gacha_guild_id()),
            (string) ($owner['userId'] ?? '')
        );
    } catch (Throwable) {
        return [
            'enabled' => false,
            'initialized' => false,
            'hasSummary' => false,
            'count' => 0,
            'snapshot' => [
                'reward' => ['date' => '', 'id' => 0],
                'item' => ['date' => '', 'id' => 0],
                'role' => ['date' => '', 'id' => 0],
                'spin' => ['date' => '', 'id' => 0],
            ],
            'entries' => [],
            'totals' => [
                'units' => [],
                'items' => 0,
                'roles' => 0,
                'spins' => 0,
            ],
        ];
    }
}

function campaign_counter_for_spin_or_fail(string $drawId, int $count, array $metadata): array
{
    if (!($GLOBALS['bootstrapReady'] ?? false) || !class_exists('GachaCampaignCounterService')) {
        json_response([
            'ok' => false,
            'code' => 'EVENT_COUNTER_UNAVAILABLE',
            'error' => 'campaign counter database is unavailable',
            'campaignCounter' => [
                'enabled' => false,
                'current' => 0,
                'max' => 5000,
                'displayValue' => '0000',
                'unavailable' => true,
            ],
        ], 503);
    }

    try {
        return GachaCampaignCounterService::incrementForSpin(gacha_guild_id(), $drawId, $count, $metadata);
    } catch (Throwable) {
        json_response([
            'ok' => false,
            'code' => 'EVENT_COUNTER_UNAVAILABLE',
            'error' => 'campaign counter database is unavailable',
            'campaignCounter' => [
                'enabled' => false,
                'current' => 0,
                'max' => 5000,
                'displayValue' => '0000',
                'unavailable' => true,
            ],
        ], 503);
    }
}

function pick_tier(array $config, ?string $forcedType = null): array
{
    if (class_exists('GachaConfigService')) {
        return GachaConfigService::pickTier($config, $forcedType);
    }

    $tiers = array_values(array_filter($config['tiers'], static fn (array $tier): bool => !empty($tier['active']) && (float) ($tier['rate'] ?? 0) > 0));
    if ($forcedType) {
        foreach ($config['tiers'] as $tier) {
            if (($tier['id'] ?? '') === $forcedType) {
                return $tier;
            }
        }
    }
    $total = array_sum(array_map(static fn (array $tier): float => (float) $tier['rate'], $tiers));
    $roll = random_int(1, 1000000) / 1000000 * max(1, $total);
    $cursor = 0.0;
    foreach ($tiers as $tier) {
        $cursor += (float) $tier['rate'];
        if ($roll <= $cursor) {
            return $tier;
        }
    }
    return $tiers[0] ?? $config['tiers'][0];
}

function pick_prize(array $config, string $tierId): array
{
    if (class_exists('GachaConfigService')) {
        return GachaConfigService::pickPrize($config, $tierId);
    }

    $pool = array_values(array_filter($config['prizes'], static fn (array $prize): bool => ($prize['tierId'] ?? '') === $tierId && !empty($prize['active'])));
    return $pool ? $pool[array_rand($pool)] : $config['prizes'][0];
}

function public_prize(array $prize, array $tier): array
{
    if (class_exists('GachaConfigService')) {
        return GachaConfigService::publicPrizePayload($GLOBALS['config'] ?? [], $prize, $tier);
    }

    return [
        'id' => $prize['id'] ?? '',
        'name' => $prize['name'] ?? 'Mystery Prize',
        'image' => $prize['image'] ?? 'images/item-1.png',
        'rarity' => $tier['id'] ?? 'common',
        'tierId' => $tier['id'] ?? 'common',
        'tierName' => $tier['tier'] ?? ucfirst((string) ($tier['id'] ?? 'common')),
        'type' => $prize['type'] ?? 'item',
        'badge' => $prize['badge'] ?? '',
        'description' => $prize['description'] ?? '',
        'roleDurationDays' => (int) ($prize['roleDurationDays'] ?? 0),
    ];
}

function commit_prepared_draw_for_complete(array $config, array $input, string $drawId, array $draw, int $buttonId, string $fallbackCurrency): array
{
    $count = max(1, min(1, (int) ($input['count'] ?? ($draw['candidateCount'] ?? $draw['count'] ?? 1))));
    $context = draw_request_context($config, $input, $count);
    $signature = (string) ($context['signature'] ?? '');
    $errorBase = static function (string $code, string $message, int $status, array $extra = []) use ($config, $fallbackCurrency): array {
        $balances = balances($config);
        return [
            'ok' => false,
            'status' => $status,
            'payload' => array_merge([
                'ok' => false,
                'code' => $code,
                'error' => $message,
                'balance' => $balances[$fallbackCurrency] ?? 0,
                'balances' => $balances,
                'currency' => $fallbackCurrency,
                'freeSpin' => gacha_free_spin_payload(true),
                'campaignCounter' => campaign_counter_payload(),
            ], $extra),
        ];
    };

    if (!draw_matches_signature($draw, $signature) || !draw_matches_request_context($draw, $context)) {
        return $errorBase('PREPARED_DRAW_MISMATCH', 'prepared draw does not match current request context', 409);
    }

    if (!prepared_draw_still_available($config, $draw, $signature)) {
        return $errorBase('PREPARED_DRAW_UNAVAILABLE', 'prepared draw prize is no longer available', 409);
    }

    if (empty($config['settings']['enabled'])) {
        return $errorBase('GACHA_DISABLED', 'gacha is disabled', 423);
    }

    $button = spin_button($config, $buttonId);
    if (empty($button['enabled'])) {
        return $errorBase('BUTTON_DISABLED', 'spin button disabled', 423, ['button' => $button]);
    }

    $currency = (string) ($button['currency'] ?? $fallbackCurrency);
    $costPerSpin = max(1, (int) ($button['cost'] ?? 1));
    $totalCost = $costPerSpin * $count;
    $freeSpinService = class_exists('GachaFreeSpinService') ? new GachaFreeSpinService() : null;
    $owner = gacha_balance_owner();
    $freeSpinPayload = ($freeSpinService && $owner)
        ? $freeSpinService->payload((string) ($owner['guildId'] ?? ''), (string) ($owner['userId'] ?? ''), true)
        : gacha_free_spin_payload(false);
    $freeSpinEligible = $freeSpinService
        && $owner
        && $freeSpinService->canUseForButton($button, $count)
        && !empty($freeSpinPayload['canUse'])
        && (int) ($freeSpinPayload['available'] ?? 0) > 0;
    $balances = balances($config);
    $balanceBefore = (int) ($balances[$currency] ?? 0);

    if ($balanceBefore < $totalCost && !$freeSpinEligible) {
        return [
            'ok' => false,
            'status' => 402,
            'payload' => [
                'ok' => false,
                'code' => 'INSUFFICIENT_CREDIT',
                'error' => 'not enough credit',
                'balance' => $balanceBefore,
                'balances' => $balances,
                'required' => $totalCost,
                'currency' => $currency,
                'button' => $button,
                'freeSpin' => $freeSpinPayload,
                'campaignCounter' => campaign_counter_payload(),
            ],
        ];
    }

    $campaignCounter = campaign_counter_for_spin_or_fail($drawId, $count, [
        'count' => $count,
        'buttonId' => (int) ($button['buttonId'] ?? $buttonId),
        'currency' => $currency,
        'costPerSpin' => $costPerSpin,
        'totalCost' => $totalCost,
        'usedFreeSpin' => $freeSpinEligible,
        'tierId' => (string) ($draw['lockedType'] ?? ''),
        'prizeId' => (string) (($draw['prize']['id'] ?? '')),
        'preparedDraw' => true,
        'commitAtComplete' => true,
    ]);

    $usingFreeSpin = false;
    $freeSpinEntitlement = null;
    if ($freeSpinEligible && $freeSpinService && $owner) {
        try {
            $freeSpinEntitlement = $freeSpinService->consume(
                (string) ($owner['guildId'] ?? ''),
                (string) ($owner['userId'] ?? ''),
                $drawId,
                (int) ($button['buttonId'] ?? $buttonId)
            );
            $usingFreeSpin = is_array($freeSpinEntitlement);
        } catch (Throwable) {
            $usingFreeSpin = false;
            $freeSpinEntitlement = null;
        }
    }

    if ($freeSpinEligible && !$usingFreeSpin) {
        rollback_campaign_counter_for_spin($drawId);
        return [
            'ok' => false,
            'status' => 409,
            'payload' => [
                'ok' => false,
                'code' => 'FREE_SPIN_UNAVAILABLE',
                'error' => 'free spin is no longer available',
                'balance' => $balanceBefore,
                'balances' => $balances,
                'required' => $totalCost,
                'currency' => $currency,
                'button' => $button,
                'freeSpin' => gacha_free_spin_payload(true),
                'campaignCounter' => campaign_counter_payload(),
            ],
        ];
    }

    try {
        if ($usingFreeSpin) {
            $charge = [
                'ok' => true,
                'balanceBefore' => $balanceBefore,
                'balancesAfter' => $balances,
            ];
        } else {
            $charge = debit_balance($currency, $totalCost, $config, [
                'ledgerType' => 'debit',
                'sourceType' => 'gacha_spin',
                'sourceId' => $drawId,
                'count' => $count,
                'buttonId' => (int) ($button['buttonId'] ?? $buttonId),
                'currency' => $currency,
                'costPerSpin' => $costPerSpin,
                'totalCost' => $totalCost,
                'tierId' => (string) ($draw['lockedType'] ?? ''),
                'prizeId' => (string) (($draw['prize']['id'] ?? '')),
                'preparedDraw' => true,
                'commitAtComplete' => true,
            ]);
        }
    } catch (Throwable) {
        if ($usingFreeSpin && $freeSpinService && $owner) {
            try {
                $freeSpinService->restoreForDraw((string) ($owner['guildId'] ?? ''), (string) ($owner['userId'] ?? ''), $drawId);
            } catch (Throwable) {
                // Keep complete failure deterministic.
            }
        }
        rollback_campaign_counter_for_spin($drawId);
        $freshBalances = balances($config);
        return [
            'ok' => false,
            'status' => 503,
            'payload' => [
                'ok' => false,
                'code' => 'CREDIT_STORE_UNAVAILABLE',
                'error' => 'credit store unavailable',
                'balance' => $freshBalances[$currency] ?? 0,
                'balances' => $freshBalances,
                'required' => $totalCost,
                'currency' => $currency,
                'button' => $button,
                'freeSpin' => gacha_free_spin_payload(true),
                'campaignCounter' => campaign_counter_payload(),
            ],
        ];
    }

    if (empty($charge['ok'])) {
        if ($usingFreeSpin && $freeSpinService && $owner) {
            try {
                $freeSpinService->restoreForDraw((string) ($owner['guildId'] ?? ''), (string) ($owner['userId'] ?? ''), $drawId);
            } catch (Throwable) {
                // Best-effort only.
            }
        }
        rollback_campaign_counter_for_spin($drawId);
        $balances = is_array($charge['balancesAfter'] ?? null) ? $charge['balancesAfter'] : balances($config);
        $balanceBefore = (int) ($charge['balanceBefore'] ?? ($balances[$currency] ?? 0));
        return [
            'ok' => false,
            'status' => 402,
            'payload' => [
                'ok' => false,
                'code' => 'INSUFFICIENT_CREDIT',
                'error' => 'not enough credit',
                'balance' => $balanceBefore,
                'balances' => $balances,
                'required' => $totalCost,
                'currency' => $currency,
                'button' => $button,
                'freeSpin' => gacha_free_spin_payload(true),
                'campaignCounter' => campaign_counter_payload(),
            ],
        ];
    }

    $now = time();
    $balanceBefore = (int) ($charge['balanceBefore'] ?? $balanceBefore);
    $balances = is_array($charge['balancesAfter'] ?? null) ? $charge['balancesAfter'] : balances($config);
    record_spin_stats((string) ($draw['lockedType'] ?? ''), $count);

    $draw['drawStatus'] = 'active';
    $draw['count'] = $count;
    $draw['candidateCount'] = $count;
    $draw['buttonId'] = (int) ($button['buttonId'] ?? $buttonId);
    $draw['currency'] = $currency;
    $draw['cost'] = $totalCost;
    $draw['costPerSpin'] = $costPerSpin;
    $draw['balanceBefore'] = $balanceBefore;
    $draw['balanceAfter'] = $balances[$currency] ?? 0;
    $draw['balancesAfter'] = $balances;
    $draw['usedFreeSpin'] = $usingFreeSpin;
    $draw['freeSpinRewardEventId'] = (int) ($freeSpinEntitlement['rewardEventId'] ?? 0);
    $draw['freeSpinSource'] = (string) ($freeSpinEntitlement['ruleCode'] ?? '');
    $draw['campaignCounter'] = $campaignCounter;
    $draw['requestedType'] = (string) ($context['requestedType'] ?? '');
    $draw['signature'] = $signature;
    $draw['condition'] = !empty($context['conditionTierId'])
        ? ['type' => 'pity', 'tierId' => (string) $context['conditionTierId']]
        : null;
    $draw['originalCreatedAt'] = (int) ($draw['originalCreatedAt'] ?? $draw['preparedAt'] ?? $draw['createdAt'] ?? $now);
    $draw['reusedPendingReward'] = !empty($draw['preparedAt']) || !empty($draw['reusedPendingReward']);
    $draw['prepareMode'] = (string) ($draw['prepareMode'] ?? 'warm');
    $draw['createdAt'] = (int) ($draw['createdAt'] ?? $now);
    $draw['spinCommittedAt'] = $now;
    $draw['revealedAt'] = $draw['revealedAt'] ?? $now;
    $draw['ballIssuedAt'] = $draw['ballIssuedAt'] ?? $now;
    $draw['ballSeenAt'] = $draw['ballSeenAt'] ?? $now;
    $draw['prizeResolvedAt'] = $draw['prizeResolvedAt'] ?? $now;
    $draw['refundBlockedAt'] = null;
    $draw['refundBlockedReason'] = null;
    unset(
        $draw['refundedAt'],
        $draw['refundAmount'],
        $draw['refundCurrency'],
        $draw['refundBalanceBefore'],
        $draw['refundBalanceAfter']
    );

    return [
        'ok' => true,
        'draw' => $draw,
        'balances' => $balances,
        'currency' => $currency,
        'campaignCounter' => $campaignCounter,
        'freeSpin' => gacha_free_spin_payload(false),
    ];
}

$input = read_input();
$action = strtolower((string) ($input['action'] ?? 'status'));
$buttonId = max(0, (int) ($input['buttonId'] ?? ($config['settings']['defaultButtonId'] ?? 1)));
$currency = default_currency($config, $buttonId);
if ($action === 'process_expired_role_grants') {
    gacha_process_expired_role_grants();
    json_response(['ok' => true, 'processed' => true]);
}

if ($action === 'status' || $action === 'claim_status') {
    $detail = strtolower(trim((string) ($input['detail'] ?? 'round')));
    if (!in_array($detail, ['round', 'full'], true)) {
        $detail = 'round';
    }
    $isAuthenticated = gacha_is_authenticated();
    $pendingDraw = $isAuthenticated ? active_pending_draw($config) : null;
    $blockingPendingDraw = ($pendingDraw && empty($pendingDraw['refundedAt'])) ? $pendingDraw : null;
    $preparedContext = draw_request_context($config, $input, 1);
    $includePrepared = (string) ($input['prepared'] ?? '1') !== '0';
    $preparedDraw = ($includePrepared && $isAuthenticated && !$blockingPendingDraw)
        ? prepared_next_draw($config, (string) ($preparedContext['signature'] ?? ''))
        : null;

    $payload = [
        'ok' => true,
        'currency' => $currency,
        'isAuthenticated' => $isAuthenticated,
        'requiresLogin' => !$isAuthenticated,
        'pendingDraw' => $blockingPendingDraw ? pending_draw_payload($blockingPendingDraw, $config) : null,
        'preparedDraw' => $preparedDraw ? prepared_draw_payload($preparedDraw, $config) : null,
        'dbReady' => $GLOBALS['bootstrapReady'] ?? false,
    ];

    if ($detail === 'full') {
        $balances = $isAuthenticated ? balances($config) : guest_balances($config);
        $payload += [
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'settings' => [
                'enabled' => (bool) ($config['settings']['enabled'] ?? true),
                'campaignCounterVisible' => (bool) ($config['settings']['campaignCounterVisible'] ?? true),
                'buttons' => $config['settings']['buttons'] ?? [],
                'defaultButtonId' => $config['settings']['defaultButtonId'] ?? 1,
            ],
            'units' => shop_units_payload(),
            'freeSpin' => $isAuthenticated ? gacha_free_spin_payload(false) : gacha_free_spin_payload(false),
            'campaignCounter' => campaign_counter_payload(),
            'offlineSummary' => $isAuthenticated ? offline_reward_summary_payload() : [
                'enabled' => false,
                'initialized' => false,
                'hasSummary' => false,
                'count' => 0,
                'snapshot' => [
                    'reward' => ['date' => '', 'id' => 0],
                    'item' => ['date' => '', 'id' => 0],
                    'role' => ['date' => '', 'id' => 0],
                    'spin' => ['date' => '', 'id' => 0],
                ],
                'entries' => [],
                'totals' => [
                    'units' => [],
                    'items' => 0,
                    'roles' => 0,
                    'spins' => 0,
                ],
            ],
        ];
    }

    json_response($payload);
}

if ($action === 'reset_mock_credit') {
    $adminUser = class_exists('Auth') ? Auth::currentUser() : null;
    if (!$adminUser || !Auth::can('gacha.manage', $adminUser)) {
        json_response([
            'ok' => false,
            'code' => 'ADMIN_PERMISSION_REQUIRED',
            'error' => 'admin permission required',
        ], 403);
    }

    foreach (starting_balances($config) as $unitCode => $amount) {
        set_balance($unitCode, $amount, $config, [
            'ledgerType' => 'reset',
            'sourceType' => 'gacha_reset_mock_credit',
            'sourceId' => 'reset_mock_credit',
            'unitCode' => $unitCode,
        ]);
    }
    $_SESSION['gacha_draws'] = [];
    unset($_SESSION['gacha_active_draw_id']);
    forget_persistent_pending_draw('', 'active');
    forget_persistent_pending_draw('', 'prepared');
    $_SESSION['gacha_spin_stats'] = [
        'totalDraws' => 0,
        'tierCounts' => [],
        'updatedAt' => time(),
    ];
    $balances = balances($config);
    json_response([
        'ok' => true,
        'balance' => $balances[$currency] ?? 0,
        'balances' => $balances,
        'currency' => $currency,
        'units' => shop_units_payload(),
        'freeSpin' => gacha_free_spin_payload(true),
        'campaignCounter' => campaign_counter_payload(),
    ]);
}

if (!in_array($action, ['prepare', 'confirm_start', 'discard_prepare', 'start', 'reveal', 'reveal_ball', 'mark_ball_seen', 'resolve_prize', 'complete', 'refund_pending', 'ack_offline_summary'], true)) {
    json_response([
        'ok' => false,
        'code' => 'UNKNOWN_ACTION',
        'error' => 'unknown action',
        'balance' => balances($config)[$currency] ?? 0,
    ], 404);
}

ensure_draw_store();

if (!gacha_is_authenticated()) {
    $balances = guest_balances($config);
    json_response([
        'ok' => false,
        'code' => 'AUTH_REQUIRED',
        'error' => 'login required',
        'balance' => $balances[$currency] ?? 0,
        'balances' => $balances,
        'currency' => $currency,
        'requiresLogin' => true,
    ], 401);
}

if ($action === 'ack_offline_summary') {
    $owner = gacha_balance_owner();
    $snapshot = is_array($input['snapshot'] ?? null) ? $input['snapshot'] : [];
    $storedSnapshot = class_exists('GachaOfflineRewardSummaryService') && $owner
        ? (new GachaOfflineRewardSummaryService())->acknowledge(
            (string) ($owner['guildId'] ?? gacha_guild_id()),
            (string) ($owner['userId'] ?? ''),
            $snapshot
        )
        : [
            'reward' => ['date' => '', 'id' => 0],
            'item' => ['date' => '', 'id' => 0],
            'role' => ['date' => '', 'id' => 0],
            'spin' => ['date' => '', 'id' => 0],
        ];

    $balances = balances($config);
    json_response([
        'ok' => true,
        'acknowledged' => true,
        'snapshot' => $storedSnapshot,
        'balance' => $balances[$currency] ?? 0,
        'balances' => $balances,
        'currency' => $currency,
        'campaignCounter' => campaign_counter_payload(),
        'freeSpin' => gacha_free_spin_payload(false),
    ]);
}

if ($action === 'refund_pending') {
    $pendingDraw = active_pending_draw($config);
    if (!$pendingDraw) {
        $balances = balances($config);
        json_response([
            'ok' => true,
            'refunded' => false,
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
            'campaignCounter' => campaign_counter_payload(),
            'freeSpin' => gacha_free_spin_payload(true),
        ]);
    }

    if (empty($pendingDraw['refundedAt'])) {
        $pendingDraw['refundBlockedAt'] = time();
        $pendingDraw['refundBlockedReason'] = 'pending_round_must_be_opened';
        $pendingDraw = persist_active_draw($pendingDraw);
        $balances = balances($config);
        $drawCurrency = (string) ($pendingDraw['currency'] ?? $currency);
        json_response([
            'ok' => true,
            'refunded' => false,
            'code' => 'FORCED_OPEN_REQUIRED',
            'resumeMode' => 'open_ball',
            'pendingDraw' => pending_draw_payload($pendingDraw, $config),
            'balance' => $balances[$drawCurrency] ?? ($balances[$currency] ?? 0),
            'balances' => $balances,
            'currency' => $drawCurrency,
            'campaignCounter' => campaign_counter_payload(),
            'freeSpin' => gacha_free_spin_payload(true),
        ]);
    }

    $wasRefunded = !empty($pendingDraw['refundedAt']);
    $refundedDraw = refund_pending_draw($pendingDraw, $config);
    $refundCurrency = (string) ($refundedDraw['refundCurrency'] ?? $refundedDraw['currency'] ?? $currency);
    $refundAmount = max(0, (int) ($refundedDraw['refundAmount'] ?? $refundedDraw['cost'] ?? 0));
    $didRefund = !$wasRefunded && !empty($refundedDraw['refundedAt']);
    if ($didRefund) {
        mark_gacha_report_live_update(
            'gacha_spin_refund',
            (string) ($refundedDraw['drawId'] ?? ''),
            [
                'drawStatus' => 'refunded',
                'refundAmount' => $refundAmount,
                'currency' => $refundCurrency,
            ]
        );
    }
    $balances = balances($config);

    json_response([
        'ok' => true,
        'refunded' => $didRefund && $refundAmount > 0,
        'refundNotice' => [
            'amount' => $refundAmount,
            'currency' => $refundCurrency,
            'message' => 'คืนเครดิตจากรอบก่อนที่หมุนกาชาไม่สำเร็จแล้ว',
        ],
        'balance' => $balances[$refundCurrency] ?? ($balances[$currency] ?? 0),
        'balances' => $balances,
        'currency' => $refundCurrency,
        'campaignCounter' => campaign_counter_payload(),
        'freeSpin' => gacha_free_spin_payload(true),
    ]);
}

if ($action === 'complete') {
    $lookup = lookup_draw_by_token($input, $config, ['active', 'prepared']);
    if (empty($lookup['ok'])) {
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => (string) ($lookup['code'] ?? 'DRAW_NOT_FOUND'),
            'error' => (string) ($lookup['error'] ?? 'draw not found'),
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
        ], (int) ($lookup['status'] ?? 404));
    }

    $drawId = (string) ($lookup['drawId'] ?? '');
    $draw = is_array($lookup['draw'] ?? null) ? $lookup['draw'] : [];
    $sourceDrawStatus = (string) ($lookup['drawStatus'] ?? 'active');
    $completeButtonId = max(0, (int) ($input['buttonId'] ?? ($draw['buttonId'] ?? $buttonId)));

    if ($sourceDrawStatus === 'prepared') {
        $commit = commit_prepared_draw_for_complete($config, $input, $drawId, $draw, $completeButtonId, $currency);
        if (empty($commit['ok'])) {
            json_response(
                is_array($commit['payload'] ?? null) ? $commit['payload'] : [
                    'ok' => false,
                    'code' => 'COMPLETE_FAILED',
                    'error' => 'complete failed',
                ],
                (int) ($commit['status'] ?? 409)
            );
        }
        $draw = is_array($commit['draw'] ?? null) ? $commit['draw'] : $draw;
        mark_gacha_report_live_update('gacha_spin_start', $drawId, [
            'drawStatus' => 'started',
            'currency' => (string) ($draw['currency'] ?? $currency),
            'tierId' => (string) ($draw['lockedType'] ?? ''),
            'prizeId' => (string) (($draw['prize']['id'] ?? '')),
            'preparedDraw' => true,
            'commitAtComplete' => true,
        ]);
    }

    $draw['completedAt'] = time();
    $draw = persist_active_draw($draw);

    unset($_SESSION['gacha_draws'][$drawId]);
    if ((string) ($_SESSION['gacha_active_draw_id'] ?? '') === $drawId) {
        unset($_SESSION['gacha_active_draw_id']);
    }
    forget_persistent_pending_draw($drawId);
    mark_gacha_report_live_update('gacha_spin_complete', $drawId, ['drawStatus' => 'completed']);

    $owner = gacha_balance_owner();
    close_session_write_lock();

    $mileageSummary = null;
    if (
        is_array($owner)
        && !empty($owner['guildId'])
        && !empty($owner['userId'])
        && ($GLOBALS['bootstrapReady'] ?? false)
        && class_exists('GachaMileageService')
    ) {
        try {
            $mileageSummary = GachaMileageService::recordCompletedSpin(
                (string) $owner['guildId'],
                (string) $owner['userId'],
                $drawId,
                max(1, (int) ($draw['count'] ?? 1))
            );
        } catch (Throwable $exception) {
            $mileageSummary = null;
            error_log(sprintf(
                '[gacha mileage] complete failed guild=%s user=%s draw=%s error=%s',
                (string) $owner['guildId'],
                (string) $owner['userId'],
                $drawId,
                $exception->getMessage()
            ));
        }
    }

    $balances = balances($config);
    $drawCurrency = (string) ($draw['currency'] ?? $currency);
    $responsePayload = [
        'ok' => true,
        'completed' => true,
        'balance' => $balances[$drawCurrency] ?? 0,
        'balances' => $balances,
        'currency' => $drawCurrency,
        'campaignCounter' => is_array($draw['campaignCounter'] ?? null) ? $draw['campaignCounter'] : campaign_counter_payload(),
        'freeSpin' => gacha_free_spin_payload(true),
        'mileageSummary' => $mileageSummary,
    ];

    if (
        is_array($owner)
        && !empty($owner['guildId'])
        && !empty($owner['userId'])
        && ($GLOBALS['bootstrapReady'] ?? false)
        && class_exists('GachaRoleGrantService')
    ) {
        try {
            (new GachaRoleGrantService())->queueForDraw(
                (string) ($owner['guildId'] ?? ''),
                (string) ($owner['userId'] ?? ''),
                $draw
            );
        } catch (Throwable) {
            // Role grants are best-effort and must never block completing the gacha round.
        }
    }

    json_response($responsePayload);
}

if ($action === 'discard_prepare') {
    [$drawId, $draw] = request_pending_draw($input, $config, 'prepared');
    forget_draw_everywhere($drawId, 'prepared');
    $balances = balances($config);
    $drawCurrency = (string) ($draw['currency'] ?? $currency);
    json_response([
        'ok' => true,
        'discarded' => true,
        'balance' => $balances[$drawCurrency] ?? ($balances[$currency] ?? 0),
        'balances' => $balances,
        'currency' => $drawCurrency,
        'freeSpin' => gacha_free_spin_payload(true),
        'campaignCounter' => campaign_counter_payload(),
    ]);
}

if ($action === 'prepare') {
    $prepareMode = strtolower(trim((string) ($input['mode'] ?? 'press')));
    if (!in_array($prepareMode, ['warm', 'press'], true)) {
        $prepareMode = 'press';
    }

    $count = 1;
    $context = draw_request_context($config, $input, $count);
    $signature = (string) ($context['signature'] ?? '');
    $button = spin_button($config, $buttonId);
    $responseCurrency = (string) ($button['currency'] ?? $currency);

    $pendingDraw = active_pending_draw($config);
    if ($pendingDraw) {
        if (empty($pendingDraw['refundedAt'])) {
            $balances = balances($config);
            json_response([
                'ok' => false,
                'code' => 'FORCED_OPEN_REQUIRED',
                'error' => 'pending gacha ball must be opened first',
                'resumeMode' => 'open_ball',
                'pendingDraw' => pending_draw_payload($pendingDraw, $config),
                'balance' => $balances[$currency] ?? 0,
                'balances' => $balances,
                'currency' => (string) ($pendingDraw['currency'] ?? $currency),
            ], 409);
        }

        $recycledDraw = recycled_prepared_draw($pendingDraw, $context, $button, $prepareMode);
        if (draw_matches_request_context($recycledDraw, $context) && prepared_draw_still_available($config, $recycledDraw, $signature)) {
            $recycledDraw = persist_prepared_draw($recycledDraw);
            $payload = prepared_draw_payload($recycledDraw, $config);
            json_response(array_merge([
                'ok' => true,
                'preparedDraw' => $payload,
                'currency' => $responseCurrency,
            ], $payload));
        }

        forget_draw_everywhere((string) ($pendingDraw['drawId'] ?? ''), 'active');
    }

    $existingPreparedDraw = $signature !== '' ? prepared_next_draw($config, $signature) : null;
    if ($existingPreparedDraw) {
        $payload = prepared_draw_payload($existingPreparedDraw, $config);
        json_response(array_merge([
            'ok' => true,
            'preparedDraw' => $payload,
            'currency' => $responseCurrency,
        ], $payload));
    }

    if (empty($config['settings']['enabled'])) {
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'GACHA_DISABLED',
            'error' => 'gacha is disabled',
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
        ], 423);
    }

    if (empty($button['enabled'])) {
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'BUTTON_DISABLED',
            'error' => 'spin button disabled',
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
            'button' => $button,
        ], 423);
    }

    $tier = pick_tier($config, ($context['requestedType'] ?? '') !== '' ? (string) $context['requestedType'] : ($context['conditionTierId'] ?? null));
    $prize = pick_prize($config, (string) ($tier['id'] ?? 'common'));
    if (($GLOBALS['bootstrapReady'] ?? false) && class_exists('GachaConfigService')) {
        $prize = GachaConfigService::prizeWithRolledRoleDuration($prize);
    }
    $prizePayload = public_prize($prize, $tier);
    $now = time();
    $preparedDraw = [
        'drawId' => bin2hex(random_bytes(12)),
        'nonce' => bin2hex(random_bytes(16)),
        'drawStatus' => 'prepared',
        'prepareMode' => $prepareMode,
        'count' => $count,
        'candidateCount' => $count,
        'buttonId' => (int) ($button['buttonId'] ?? $buttonId),
        'currency' => (string) ($button['currency'] ?? default_currency($config, $buttonId)),
        'requestedType' => (string) ($context['requestedType'] ?? ''),
        'signature' => $signature,
        'condition' => !empty($context['conditionTierId'])
            ? ['type' => 'pity', 'tierId' => (string) $context['conditionTierId']]
            : null,
        'lockedType' => (string) ($tier['id'] ?? 'common'),
        'tier' => $tier,
        'prize' => $prizePayload,
        'preparedAt' => $now,
        'createdAt' => $now,
        'originalCreatedAt' => $now,
        'visualSeed' => random_int(100000, 999999999),
        'revealedAt' => null,
        'ballIssuedAt' => null,
        'ballSeenAt' => null,
        'prizeResolvedAt' => null,
        'completedAt' => null,
    ];
    $preparedDraw = persist_prepared_draw($preparedDraw);
    $payload = prepared_draw_payload($preparedDraw, $config);
    json_response(array_merge([
        'ok' => true,
        'preparedDraw' => $payload,
        'currency' => $responseCurrency,
    ], $payload));
}

if ($action === 'confirm_start') {
    $pendingDraw = active_pending_draw($config);
    if ($pendingDraw && empty($pendingDraw['refundedAt'])) {
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'FORCED_OPEN_REQUIRED',
            'error' => 'pending gacha ball must be opened first',
            'resumeMode' => 'open_ball',
            'pendingDraw' => pending_draw_payload($pendingDraw, $config),
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => (string) ($pendingDraw['currency'] ?? $currency),
        ], 409);
    }

    $token = draw_token_from_input($input);
    $tokenData = parse_draw_token($token, $config);
    if (!$tokenData) {
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'INVALID_TOKEN',
            'error' => 'invalid draw token',
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
        ], 400);
    }

    $drawId = (string) ($tokenData['drawId'] ?? '');
    $draw = $_SESSION['gacha_draws'][$drawId] ?? null;
    if (!is_array($draw) || !draw_is_prepared($draw)) {
        $draw = persistent_pending_draw($drawId, 'prepared');
        if ($draw) {
            $_SESSION['gacha_draws'][$drawId] = $draw;
        }
    }

    if (!is_array($draw) || !draw_is_prepared($draw) || !hash_equals((string) ($draw['nonce'] ?? ''), (string) ($tokenData['nonce'] ?? ''))) {
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'PREPARED_DRAW_STALE',
            'error' => 'prepared draw is no longer available',
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
            'freeSpin' => gacha_free_spin_payload(true),
            'campaignCounter' => campaign_counter_payload(),
        ], 409);
    }

    $count = max(1, min(1, (int) ($input['count'] ?? ($draw['candidateCount'] ?? 1))));
    $context = draw_request_context($config, $input, $count);
    $signature = (string) ($context['signature'] ?? '');
    if (!draw_matches_signature($draw, $signature) || !draw_matches_request_context($draw, $context)) {
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'PREPARED_DRAW_MISMATCH',
            'error' => 'prepared draw does not match current request context',
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
            'freeSpin' => gacha_free_spin_payload(true),
            'campaignCounter' => campaign_counter_payload(),
        ], 409);
    }

    if (!prepared_draw_still_available($config, $draw, $signature)) {
        forget_draw_everywhere($drawId, 'prepared');
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'PREPARED_DRAW_UNAVAILABLE',
            'error' => 'prepared draw prize is no longer available',
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
            'freeSpin' => gacha_free_spin_payload(true),
            'campaignCounter' => campaign_counter_payload(),
        ], 409);
    }

    if (empty($config['settings']['enabled'])) {
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'GACHA_DISABLED',
            'error' => 'gacha is disabled',
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
        ], 423);
    }

    $button = spin_button($config, $buttonId);
    if (empty($button['enabled'])) {
        $balances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'BUTTON_DISABLED',
            'error' => 'spin button disabled',
            'balance' => $balances[$currency] ?? 0,
            'balances' => $balances,
            'currency' => $currency,
            'button' => $button,
        ], 423);
    }

    $currency = (string) ($button['currency'] ?? 'ticket');
    $costPerSpin = max(1, (int) ($button['cost'] ?? 1));
    $totalCost = $costPerSpin * $count;
    $freeSpinService = class_exists('GachaFreeSpinService') ? new GachaFreeSpinService() : null;
    $owner = gacha_balance_owner();
    $freeSpinPayload = ($freeSpinService && $owner)
        ? $freeSpinService->payload((string) ($owner['guildId'] ?? ''), (string) ($owner['userId'] ?? ''), true)
        : gacha_free_spin_payload(false);
    $freeSpinEligible = $freeSpinService
        && $owner
        && $freeSpinService->canUseForButton($button, $count)
        && !empty($freeSpinPayload['canUse'])
        && (int) ($freeSpinPayload['available'] ?? 0) > 0;
    $balances = balances($config);
    $balanceBefore = (int) ($balances[$currency] ?? 0);

    if ($balanceBefore < $totalCost && !$freeSpinEligible) {
        json_response([
            'ok' => false,
            'code' => 'INSUFFICIENT_CREDIT',
            'error' => 'not enough credit',
            'balance' => $balanceBefore,
            'balances' => $balances,
            'required' => $totalCost,
            'currency' => $currency,
            'button' => $button,
            'freeSpin' => $freeSpinPayload,
        ], 402);
    }

    $campaignCounter = campaign_counter_for_spin_or_fail($drawId, $count, [
        'count' => $count,
        'buttonId' => (int) ($button['buttonId'] ?? $buttonId),
        'currency' => $currency,
        'costPerSpin' => $costPerSpin,
        'totalCost' => $totalCost,
        'usedFreeSpin' => $freeSpinEligible,
        'tierId' => (string) ($draw['lockedType'] ?? ''),
        'prizeId' => (string) (($draw['prize']['id'] ?? '')),
        'preparedDraw' => true,
    ]);

    $usingFreeSpin = false;
    $freeSpinEntitlement = null;
    if ($freeSpinEligible && $freeSpinService && $owner) {
        try {
            $freeSpinEntitlement = $freeSpinService->consume(
                (string) ($owner['guildId'] ?? ''),
                (string) ($owner['userId'] ?? ''),
                $drawId,
                (int) ($button['buttonId'] ?? $buttonId)
            );
            $usingFreeSpin = is_array($freeSpinEntitlement);
        } catch (Throwable) {
            $usingFreeSpin = false;
            $freeSpinEntitlement = null;
        }
    }

    if ($freeSpinEligible && !$usingFreeSpin) {
        rollback_campaign_counter_for_spin($drawId);
        json_response([
            'ok' => false,
            'code' => 'FREE_SPIN_UNAVAILABLE',
            'error' => 'free spin is no longer available',
            'balance' => $balanceBefore,
            'balances' => $balances,
            'required' => $totalCost,
            'currency' => $currency,
            'button' => $button,
            'freeSpin' => gacha_free_spin_payload(true),
        ], 409);
    }

    try {
        if ($usingFreeSpin) {
            $charge = [
                'ok' => true,
                'balanceBefore' => $balanceBefore,
                'balancesAfter' => $balances,
            ];
        } else {
            $charge = debit_balance($currency, $totalCost, $config, [
                'ledgerType' => 'debit',
                'sourceType' => 'gacha_spin',
                'sourceId' => $drawId,
                'count' => $count,
                'buttonId' => (int) ($button['buttonId'] ?? $buttonId),
                'currency' => $currency,
                'costPerSpin' => $costPerSpin,
                'totalCost' => $totalCost,
                'tierId' => (string) ($draw['lockedType'] ?? ''),
                'prizeId' => (string) (($draw['prize']['id'] ?? '')),
                'preparedDraw' => true,
            ]);
        }
    } catch (Throwable) {
        if ($usingFreeSpin && $freeSpinService && $owner) {
            try {
                $freeSpinService->restoreForDraw((string) ($owner['guildId'] ?? ''), (string) ($owner['userId'] ?? ''), $drawId);
            } catch (Throwable) {
                // Keep credit-store error path deterministic.
            }
        }
        rollback_campaign_counter_for_spin($drawId);
        $freshBalances = balances($config);
        json_response([
            'ok' => false,
            'code' => 'CREDIT_STORE_UNAVAILABLE',
            'error' => 'credit store unavailable',
            'balance' => $freshBalances[$currency] ?? 0,
            'balances' => $freshBalances,
            'required' => $totalCost,
            'currency' => $currency,
            'button' => $button,
            'freeSpin' => gacha_free_spin_payload(true),
        ], 503);
    }

    if (empty($charge['ok'])) {
        if ($usingFreeSpin && $freeSpinService && $owner) {
            try {
                $freeSpinService->restoreForDraw((string) ($owner['guildId'] ?? ''), (string) ($owner['userId'] ?? ''), $drawId);
            } catch (Throwable) {
                // Best-effort only.
            }
        }
        rollback_campaign_counter_for_spin($drawId);
        $balances = is_array($charge['balancesAfter'] ?? null) ? $charge['balancesAfter'] : balances($config);
        $balanceBefore = (int) ($charge['balanceBefore'] ?? ($balances[$currency] ?? 0));
        json_response([
            'ok' => false,
            'code' => 'INSUFFICIENT_CREDIT',
            'error' => 'not enough credit',
            'balance' => $balanceBefore,
            'balances' => $balances,
            'required' => $totalCost,
            'currency' => $currency,
            'button' => $button,
            'freeSpin' => gacha_free_spin_payload(true),
        ], 402);
    }

    $balanceBefore = (int) ($charge['balanceBefore'] ?? $balanceBefore);
    $balances = is_array($charge['balancesAfter'] ?? null) ? $charge['balancesAfter'] : balances($config);
    record_spin_stats((string) ($draw['lockedType'] ?? ''), $count);

    $draw['drawStatus'] = 'active';
    $draw['count'] = $count;
    $draw['candidateCount'] = $count;
    $draw['buttonId'] = (int) ($button['buttonId'] ?? $buttonId);
    $draw['currency'] = $currency;
    $draw['cost'] = $totalCost;
    $draw['costPerSpin'] = $costPerSpin;
    $draw['balanceBefore'] = $balanceBefore;
    $draw['balanceAfter'] = $balances[$currency] ?? 0;
    $draw['balancesAfter'] = $balances;
    $draw['usedFreeSpin'] = $usingFreeSpin;
    $draw['freeSpinRewardEventId'] = (int) ($freeSpinEntitlement['rewardEventId'] ?? 0);
    $draw['freeSpinSource'] = (string) ($freeSpinEntitlement['ruleCode'] ?? '');
    $draw['campaignCounter'] = $campaignCounter;
    $draw['requestedType'] = (string) ($context['requestedType'] ?? '');
    $draw['signature'] = $signature;
    $draw['condition'] = !empty($context['conditionTierId'])
        ? ['type' => 'pity', 'tierId' => (string) $context['conditionTierId']]
        : null;
    $draw['originalCreatedAt'] = (int) ($draw['originalCreatedAt'] ?? $draw['preparedAt'] ?? $draw['createdAt'] ?? time());
    $draw['reusedPendingReward'] = !empty($draw['preparedAt']) || !empty($draw['reusedPendingReward']);
    $draw['prepareMode'] = (string) ($draw['prepareMode'] ?? 'press');
    $draw['createdAt'] = time();
    $draw['revealedAt'] = null;
    $draw['ballIssuedAt'] = null;
    $draw['ballSeenAt'] = null;
    $draw['prizeResolvedAt'] = null;
    $draw['completedAt'] = null;
    $draw['refundBlockedAt'] = null;
    $draw['refundBlockedReason'] = null;
    unset(
        $draw['refundedAt'],
        $draw['refundAmount'],
        $draw['refundCurrency'],
        $draw['refundBalanceBefore'],
        $draw['refundBalanceAfter']
    );
    $draw = persist_active_draw($draw);
    mark_gacha_report_live_update('gacha_spin_start', $drawId, [
        'drawStatus' => 'started',
        'currency' => $currency,
        'tierId' => (string) ($draw['lockedType'] ?? ''),
        'prizeId' => (string) (($draw['prize']['id'] ?? '')),
        'preparedDraw' => true,
    ]);

    $drawToken = create_draw_token($drawId, (string) ($draw['nonce'] ?? ''), $config);
    json_response([
        'ok' => true,
        'drawToken' => $drawToken,
        'roundRef' => $drawToken,
        'charged' => true,
        'chargedCost' => $usingFreeSpin ? 0 : $totalCost,
        'usedFreeSpin' => $usingFreeSpin,
        'freeSpin' => gacha_free_spin_payload(false),
        'costPerSpin' => $costPerSpin,
        'balanceBefore' => $balanceBefore,
        'balanceAfter' => $balances[$currency] ?? 0,
        'balancesAfter' => $balances,
        'currency' => $currency,
        'button' => $button,
        'lockedType' => (string) ($draw['lockedType'] ?? 'common'),
        'prizeEnvelope' => prize_envelope_payload(is_array($draw['prize'] ?? null) ? $draw['prize'] : null, $draw, $drawToken),
        'campaignCounter' => $campaignCounter,
        'visualSeed' => (int) ($draw['visualSeed'] ?? 0),
        'reusedPendingReward' => !empty($draw['reusedPendingReward']),
    ]);
}

if ($action === 'start') {
    $pendingDraw = active_pending_draw($config);
    $reusePendingDraw = null;
    if ($pendingDraw) {
        if (empty($pendingDraw['refundedAt'])) {
            json_response([
                'ok' => false,
                'code' => 'FORCED_OPEN_REQUIRED',
                'error' => 'pending gacha ball must be opened first',
                'resumeMode' => 'open_ball',
                'pendingDraw' => pending_draw_payload($pendingDraw, $config),
                'balance' => balances($config)[$currency] ?? 0,
                'balances' => balances($config),
                'currency' => (string) ($pendingDraw['currency'] ?? $currency),
            ], 409);
        }
        $reusePendingDraw = empty($pendingDraw['refundedAt'])
            ? refund_pending_draw($pendingDraw, $config)
            : $pendingDraw;
    }

    if (empty($config['settings']['enabled'])) {
        json_response([
            'ok' => false,
            'code' => 'GACHA_DISABLED',
            'error' => 'gacha is disabled',
            'balance' => balances($config)[$currency] ?? 0,
        ], 423);
    }

    $count = max(1, min(10, (int) ($input['count'] ?? 1)));
    $button = spin_button($config, $buttonId);
    if (empty($button['enabled'])) {
        json_response([
            'ok' => false,
            'code' => 'BUTTON_DISABLED',
            'error' => 'spin button disabled',
            'balance' => balances($config)[$currency] ?? 0,
        ], 423);
    }

    $currency = (string) ($button['currency'] ?? 'ticket');
    $costPerSpin = max(1, (int) ($button['cost'] ?? 1));
    $totalCost = $costPerSpin * $count;
    $freeSpinService = class_exists('GachaFreeSpinService') ? new GachaFreeSpinService() : null;
    $owner = gacha_balance_owner();
    $freeSpinPayload = ($freeSpinService && $owner)
        ? $freeSpinService->payload((string) ($owner['guildId'] ?? ''), (string) ($owner['userId'] ?? ''), true)
        : gacha_free_spin_payload(false);
    $freeSpinEligible = $freeSpinService
        && $owner
        && $freeSpinService->canUseForButton($button, $count)
        && !empty($freeSpinPayload['canUse'])
        && (int) ($freeSpinPayload['available'] ?? 0) > 0
        && !$reusePendingDraw;
    $balances = balances($config);
    $balanceBefore = (int) ($balances[$currency] ?? 0);

    if ($balanceBefore < $totalCost && !$freeSpinEligible) {
        json_response([
            'ok' => false,
            'code' => 'INSUFFICIENT_CREDIT',
            'error' => 'not enough credit',
            'balance' => $balanceBefore,
            'balances' => $balances,
            'required' => $totalCost,
            'currency' => $currency,
            'button' => $button,
            'freeSpin' => $freeSpinPayload,
        ], 402);
    }

    $requestedType = strtolower(trim((string) ($input['type'] ?? '')));
    $conditionTierId = $requestedType === '' ? forced_tier_from_conditions($config) : null;
    $signature = draw_signature_value($requestedType, $conditionTierId);
    if ($reusePendingDraw) {
        $tier = is_array($reusePendingDraw['tier'] ?? null)
            ? $reusePendingDraw['tier']
            : pick_tier($config, (string) ($reusePendingDraw['lockedType'] ?? ''));
        $prizePayload = is_array($reusePendingDraw['prize'] ?? null)
            ? $reusePendingDraw['prize']
            : public_prize(pick_prize($config, (string) ($tier['id'] ?? 'common')), $tier);
        $drawId = (string) $reusePendingDraw['drawId'];
        $conditionTierId = is_array($reusePendingDraw['condition'] ?? null)
            ? (string) ($reusePendingDraw['condition']['tierId'] ?? $conditionTierId)
            : $conditionTierId;
    } else {
        $tier = pick_tier($config, $requestedType !== '' ? $requestedType : $conditionTierId);
        $prize = pick_prize($config, (string) $tier['id']);
        if (($GLOBALS['bootstrapReady'] ?? false) && class_exists('GachaConfigService')) {
            $prize = GachaConfigService::prizeWithRolledRoleDuration($prize);
        }
        $prizePayload = public_prize($prize, $tier);
        $drawId = bin2hex(random_bytes(12));
    }
    $campaignCounter = campaign_counter_for_spin_or_fail($drawId, $count, [
        'count' => $count,
        'buttonId' => (int) ($button['buttonId'] ?? $buttonId),
        'currency' => $currency,
        'costPerSpin' => $costPerSpin,
        'totalCost' => $totalCost,
        'usedFreeSpin' => $freeSpinEligible,
        'tierId' => (string) ($tier['id'] ?? ''),
        'prizeId' => (string) ($prizePayload['id'] ?? ''),
        'reusedPendingReward' => (bool) $reusePendingDraw,
    ]);

    $usingFreeSpin = false;
    $freeSpinEntitlement = null;
    if ($freeSpinEligible && $freeSpinService && $owner) {
        try {
            $freeSpinEntitlement = $freeSpinService->consume(
                (string) ($owner['guildId'] ?? ''),
                (string) ($owner['userId'] ?? ''),
                $drawId,
                (int) ($button['buttonId'] ?? $buttonId)
            );
            $usingFreeSpin = is_array($freeSpinEntitlement);
        } catch (Throwable) {
            $usingFreeSpin = false;
            $freeSpinEntitlement = null;
        }
    }

    if ($freeSpinEligible && !$usingFreeSpin) {
        rollback_campaign_counter_for_spin($drawId);
        json_response([
            'ok' => false,
            'code' => 'FREE_SPIN_UNAVAILABLE',
            'error' => 'free spin is no longer available',
            'balance' => $balanceBefore,
            'balances' => $balances,
            'required' => $totalCost,
            'currency' => $currency,
            'button' => $button,
            'freeSpin' => gacha_free_spin_payload(true),
        ], 409);
    }

    try {
        if ($usingFreeSpin) {
            $charge = [
                'ok' => true,
                'balanceBefore' => $balanceBefore,
                'balancesAfter' => $balances,
            ];
        } else {
        $charge = debit_balance($currency, $totalCost, $config, [
            'ledgerType' => 'debit',
            'sourceType' => 'gacha_spin',
            'sourceId' => $drawId,
            'count' => $count,
            'buttonId' => (int) ($button['buttonId'] ?? $buttonId),
            'currency' => $currency,
            'costPerSpin' => $costPerSpin,
            'totalCost' => $totalCost,
            'tierId' => (string) ($tier['id'] ?? ''),
            'prizeId' => (string) ($prizePayload['id'] ?? ''),
            'reusedPendingReward' => (bool) $reusePendingDraw,
        ]);
        }
    } catch (Throwable) {
        if ($usingFreeSpin && $freeSpinService && $owner) {
            try {
                $freeSpinService->restoreForDraw((string) ($owner['guildId'] ?? ''), (string) ($owner['userId'] ?? ''), $drawId);
            } catch (Throwable) {
                // Keep credit-store error path deterministic.
            }
        }
        rollback_campaign_counter_for_spin($drawId);
        json_response([
            'ok' => false,
            'code' => 'CREDIT_STORE_UNAVAILABLE',
            'error' => 'credit store unavailable',
            'balance' => balances($config)[$currency] ?? 0,
            'balances' => balances($config),
            'required' => $totalCost,
            'currency' => $currency,
            'button' => $button,
        ], 503);
    }

    if (empty($charge['ok'])) {
        if ($usingFreeSpin && $freeSpinService && $owner) {
            try {
                $freeSpinService->restoreForDraw((string) ($owner['guildId'] ?? ''), (string) ($owner['userId'] ?? ''), $drawId);
            } catch (Throwable) {
                // Best-effort only.
            }
        }
        rollback_campaign_counter_for_spin($drawId);
        $balances = is_array($charge['balancesAfter'] ?? null) ? $charge['balancesAfter'] : balances($config);
        $balanceBefore = (int) ($charge['balanceBefore'] ?? ($balances[$currency] ?? 0));
        json_response([
            'ok' => false,
            'code' => 'INSUFFICIENT_CREDIT',
            'error' => 'not enough credit',
            'balance' => $balanceBefore,
            'balances' => $balances,
            'required' => $totalCost,
            'currency' => $currency,
            'button' => $button,
        ], 402);
    }

    $balanceBefore = (int) ($charge['balanceBefore'] ?? $balanceBefore);
    $balances = is_array($charge['balancesAfter'] ?? null) ? $charge['balancesAfter'] : balances($config);
    record_spin_stats((string) $tier['id'], $count);

    $nonce = bin2hex(random_bytes(16));
    $draw = [
        'drawId' => $drawId,
        'nonce' => $nonce,
        'count' => $count,
        'candidateCount' => $count,
        'buttonId' => (int) ($button['buttonId'] ?? $buttonId),
        'currency' => $currency,
        'cost' => $totalCost,
        'costPerSpin' => $costPerSpin,
        'balanceBefore' => $balanceBefore,
        'balanceAfter' => $balances[$currency] ?? 0,
        'balancesAfter' => $balances,
        'usedFreeSpin' => $usingFreeSpin,
        'freeSpinRewardEventId' => (int) ($freeSpinEntitlement['rewardEventId'] ?? 0),
        'freeSpinSource' => (string) ($freeSpinEntitlement['ruleCode'] ?? ''),
        'lockedType' => $tier['id'],
        'tier' => $tier,
        'prize' => $prizePayload,
        'campaignCounter' => $campaignCounter,
        'requestedType' => $requestedType,
        'signature' => $signature,
        'condition' => $conditionTierId ? ['type' => 'pity', 'tierId' => $conditionTierId] : null,
        'originalCreatedAt' => (int) ($reusePendingDraw['originalCreatedAt'] ?? $reusePendingDraw['createdAt'] ?? time()),
        'reusedPendingReward' => (bool) $reusePendingDraw,
        'createdAt' => time(),
        'revealedAt' => null,
        'ballIssuedAt' => null,
        'ballSeenAt' => null,
        'prizeResolvedAt' => null,
        'completedAt' => null,
        'refundBlockedAt' => null,
        'refundBlockedReason' => null,
        'visualSeed' => random_int(100000, 999999999),
    ];
    $draw = persist_active_draw($draw);
    mark_gacha_report_live_update('gacha_spin_start', $drawId, [
        'drawStatus' => 'started',
        'currency' => $currency,
        'tierId' => (string) ($tier['id'] ?? ''),
        'prizeId' => (string) ($prizePayload['id'] ?? ''),
    ]);

    $drawToken = create_draw_token($drawId, $nonce, $config);
    json_response([
        'ok' => true,
        'drawToken' => $drawToken,
        'roundRef' => $drawToken,
        'charged' => true,
        'chargedCost' => $usingFreeSpin ? 0 : $totalCost,
        'usedFreeSpin' => $usingFreeSpin,
        'freeSpin' => gacha_free_spin_payload(false),
        'costPerSpin' => $costPerSpin,
        'balanceBefore' => $balanceBefore,
        'balanceAfter' => $balances[$currency] ?? 0,
        'balancesAfter' => $balances,
        'currency' => $currency,
        'button' => $button,
        'lockedType' => (string) ($tier['id'] ?? 'common'),
        'prizeEnvelope' => prize_envelope_payload($prizePayload, $draw, $drawToken),
        'campaignCounter' => $campaignCounter,
        'visualSeed' => (int) ($draw['visualSeed'] ?? 0),
        'reusedPendingReward' => (bool) $reusePendingDraw,
    ]);
}

[$drawId, $draw] = request_pending_draw($input, $config);

if ($action === 'reveal' || $action === 'reveal_ball') {
    $draw['revealedAt'] = time();
    $draw['ballIssuedAt'] = $draw['ballIssuedAt'] ?? time();
    $draw['ballViewSeed'] = random_int(100000, 999999999);
    $draw = persist_active_draw($draw);

    json_response([
        'ok' => true,
        'phase' => 'ball',
        'ballType' => (string) ($draw['lockedType'] ?? 'common'),
        'chargedCost' => !empty($draw['usedFreeSpin']) ? 0 : (int) ($draw['cost'] ?? 0),
        'usedFreeSpin' => !empty($draw['usedFreeSpin']),
        'costPerSpin' => (int) ($draw['costPerSpin'] ?? 1),
        'balanceBefore' => (int) ($draw['balanceBefore'] ?? 0),
        'balanceAfter' => (int) ($draw['balanceAfter'] ?? 0),
        'balancesAfter' => is_array($draw['balancesAfter'] ?? null) ? $draw['balancesAfter'] : [],
        'currency' => (string) ($draw['currency'] ?? $currency),
        'buttonId' => (int) ($draw['buttonId'] ?? 0),
        'visualSeed' => (int) ($draw['ballViewSeed'] ?? $draw['visualSeed'] ?? 0),
        'resumeMode' => draw_requires_forced_open($draw) ? 'open_ball' : 'refund',
    ]);
}

if ($action === 'mark_ball_seen') {
    if (empty($draw['ballSeenAt'])) {
        $draw['ballSeenAt'] = time();
    }
    $draw['ballIssuedAt'] = $draw['ballIssuedAt'] ?? $draw['ballSeenAt'];
    $draw = persist_active_draw($draw);

    json_response([
        'ok' => true,
        'phase' => 'ball_seen',
        'resumeMode' => 'open_ball',
        'pendingDraw' => pending_draw_payload($draw, $config),
    ]);
}

if ($action === 'resolve_prize') {
    if (empty($draw['ballSeenAt'])) {
        $draw['ballSeenAt'] = time();
    }
    $draw['prizeResolvedAt'] = time();
    $draw = persist_active_draw($draw);
    mark_gacha_report_live_update('gacha_spin_resolve', $drawId, [
        'drawStatus' => 'resolved',
        'tierId' => (string) ($draw['lockedType'] ?? ''),
        'prizeId' => (string) (($draw['prize']['id'] ?? '')),
    ]);
    $prize = is_array($draw['prize'] ?? null) ? $draw['prize'] : [];

    json_response([
        'ok' => true,
        'phase' => 'prize',
        'ballType' => (string) ($draw['lockedType'] ?? 'common'),
        'prize' => $prize,
        'chargedCost' => !empty($draw['usedFreeSpin']) ? 0 : (int) ($draw['cost'] ?? 0),
        'usedFreeSpin' => !empty($draw['usedFreeSpin']),
        'costPerSpin' => (int) ($draw['costPerSpin'] ?? 1),
        'balanceBefore' => (int) ($draw['balanceBefore'] ?? 0),
        'balanceAfter' => (int) ($draw['balanceAfter'] ?? 0),
        'balancesAfter' => is_array($draw['balancesAfter'] ?? null) ? $draw['balancesAfter'] : [],
        'currency' => (string) ($draw['currency'] ?? $currency),
        'buttonId' => (int) ($draw['buttonId'] ?? 0),
        'visualSeed' => random_int(100000, 999999999),
        'campaignCounter' => is_array($draw['campaignCounter'] ?? null) ? $draw['campaignCounter'] : null,
    ]);
}

json_response([
    'ok' => false,
    'code' => 'UNKNOWN_ACTION',
    'error' => 'unknown action',
], 404);
