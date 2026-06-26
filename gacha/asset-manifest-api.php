<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

function asset_manifest_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function asset_manifest_input(): array
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

function asset_manifest_save_allowed(): bool
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
    } catch (Throwable) {
        return false;
    }
}

$input = asset_manifest_input();
$action = strtolower(trim((string) ($input['action'] ?? 'bootstrap')));

try {
    if ($action === 'bootstrap') {
        asset_manifest_json([
            'ok' => true,
            'saveAllowed' => asset_manifest_save_allowed(),
        ] + GachaAssetManifestService::editorBootstrap());
    }

    if ($action === 'live_manifest') {
        asset_manifest_json([
            'ok' => true,
            'manifest' => GachaAssetManifestService::liveManifest(),
            'pageDefinitions' => GachaAssetManifestService::pageDefinitions(),
        ]);
    }

    if ($action === 'list_assets') {
        asset_manifest_json([
            'ok' => true,
        ] + GachaAssetManifestService::listAvailableAssets());
    }

    if ($action === 'save_draft') {
        if (!asset_manifest_save_allowed()) {
            asset_manifest_json([
                'ok' => false,
                'code' => 'FORBIDDEN',
                'message' => 'draft save is restricted',
            ], 403);
        }

        Csrf::assertValid();
        $payload = is_array($input['manifest'] ?? null) ? $input['manifest'] : [];
        $manifest = GachaAssetManifestService::saveDraft($payload);
        asset_manifest_json([
            'ok' => true,
            'saveAllowed' => true,
            'manifest' => $manifest,
            'versions' => GachaAssetManifestService::versionManifest(),
        ]);
    }

    if ($action === 'publish_draft') {
        if (!asset_manifest_save_allowed()) {
            asset_manifest_json([
                'ok' => false,
                'code' => 'FORBIDDEN',
                'message' => 'draft publish is restricted',
            ], 403);
        }

        Csrf::assertValid();
        $payload = is_array($input['manifest'] ?? null) ? $input['manifest'] : null;
        $result = GachaAssetManifestService::publishDraft($payload);
        asset_manifest_json(['ok' => true, 'saveAllowed' => true] + $result);
    }

    if ($action === 'rollback_version') {
        if (!asset_manifest_save_allowed()) {
            asset_manifest_json([
                'ok' => false,
                'code' => 'FORBIDDEN',
                'message' => 'version rollback is restricted',
            ], 403);
        }

        Csrf::assertValid();
        $versionId = trim((string) ($input['versionId'] ?? ''));
        $publish = filter_var($input['publish'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $result = GachaAssetManifestService::rollbackVersion($versionId, $publish);
        asset_manifest_json(['ok' => true, 'saveAllowed' => true] + $result);
    }

    asset_manifest_json([
        'ok' => false,
        'code' => 'UNKNOWN_ACTION',
        'message' => 'unknown action',
    ], 404);
} catch (Throwable $exception) {
    $message = trim($exception->getMessage());
    asset_manifest_json([
        'ok' => false,
        'code' => $message !== '' ? $message : 'ASSET_MANIFEST_ERROR',
        'message' => $message !== '' ? $message : 'asset manifest request failed',
    ], 500);
}
