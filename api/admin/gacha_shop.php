<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('gacha_shop'));

$payload = Input::json();
$config = is_array($payload['config'] ?? null) ? $payload['config'] : null;
$units = is_array($payload['units'] ?? null) ? $payload['units'] : null;
if (!$config) {
    Response::error('config is required.', 422);
}

$before = [
    'config' => ShopConfigService::load(),
    'units' => ShopUnitService::units(false),
];
$auditSummary = static function (?array $config, ?array $units): array {
    $products = is_array($config['products'] ?? null) ? $config['products'] : [];
    $roleProducts = 0;
    $itemProducts = 0;
    $options = 0;
    foreach ($products as $product) {
        if (!is_array($product)) {
            continue;
        }
        if (($product['type'] ?? '') === 'role') {
            $roleProducts++;
        } else {
            $itemProducts++;
        }
        $options += count(array_filter($product['purchaseOptions'] ?? [], 'is_array'));
    }

    return [
        'settings' => [
            'enabled' => $config['settings']['enabled'] ?? null,
            'purchaseMode' => $config['settings']['purchaseMode'] ?? null,
        ],
        'counts' => [
            'units' => count($units ?? []),
            'products' => count($products),
            'roleProducts' => $roleProducts,
            'itemProducts' => $itemProducts,
            'purchaseOptions' => $options,
            'templates' => count(array_filter($config['templates'] ?? [], 'is_array')),
        ],
    ];
};

$adminActionId = 0;
try {
    $adminActionId = AuditLogger::start(
        'gacha_shop_save',
        'setting',
        ShopConfigService::SETTING_KEY,
        (string) Bootstrap::config('discord.guildId', ''),
        $auditSummary($config, $units),
        $auditSummary($before['config'], $before['units'])
    );
} catch (Throwable) {
    // A logging outage must not block shop settings from being saved.
}

try {
    $savedUnits = $units !== null ? ShopUnitService::saveUnits($units) : ShopUnitService::units(false);
    $savedConfig = ShopConfigService::save($config);
    if ($adminActionId > 0) {
        try {
            AuditLogger::finish($adminActionId, 'success', ['ok' => true], $auditSummary($savedConfig, $savedUnits));
        } catch (Throwable) {
            // Saving the shop is the source of truth; audit completion is best-effort.
        }
    }
    try {
        LiveUpdateService::mark(['gacha_shop'], 'gacha_shop_save', 'setting', ShopConfigService::SETTING_KEY, [
            'units' => count($savedUnits),
            'products' => count($savedConfig['products'] ?? []),
        ]);
    } catch (Throwable) {
        // A live-update heartbeat issue should not turn a successful save into a failure.
    }
    try {
        PublicPageCacheService::clear('gacha-shop');
    } catch (Throwable) {
        // Cache invalidation must never block a successful shop save.
    }

    Response::json([
        'ok' => true,
        'config' => $savedConfig,
        'units' => $savedUnits,
    ]);
} catch (Throwable $exception) {
    if ($adminActionId > 0) {
        try {
            AuditLogger::finish($adminActionId, 'failed', [], null, $exception->getMessage());
        } catch (Throwable) {
            // Keep the real save error visible instead of replacing it with an audit failure.
        }
    }
    Response::error($exception->getMessage(), 500);
}
