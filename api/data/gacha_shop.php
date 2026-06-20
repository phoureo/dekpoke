<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('gacha_shop'));
AuditLogger::access('gacha_shop_read', 'page', 'gacha_shop');

$guildId = (string) Bootstrap::config('discord.guildId', '');
$config = ShopConfigService::load();
$units = ShopUnitService::units(false);

$roles = Database::fetchAll(
    'SELECT roleId, roleName, rolePosition, roleColor, iconHash, unicodeEmoji, isManaged, metadataJson
     FROM tbl_role
     WHERE guildId = :guildId AND deleteDate IS NULL
     ORDER BY rolePosition DESC, roleName ASC',
    ['guildId' => $guildId]
);
$roles = array_map(static function (array $role): array {
    $role['roleIconUrl'] = DiscordAssets::roleIcon($role['roleId'], $role['iconHash'] ?? null, 64);
    return $role;
}, RoleCatalogService::decorateRoles($roles));

$products = $config['products'] ?? [];
$roleProducts = array_values(array_filter($products, static fn (array $product): bool => ($product['type'] ?? '') === 'role'));
$itemProducts = array_values(array_filter($products, static fn (array $product): bool => ($product['type'] ?? '') !== 'role'));
$activeProducts = array_values(array_filter($products, static fn (array $product): bool => !empty($product['active'])));
$visibleProducts = array_values(array_filter($products, static fn (array $product): bool => !empty($product['visible'])));

Response::json([
    'ok' => true,
    'config' => $config,
    'units' => $units,
    'roles' => $roles,
    'roleSeries' => array_values(RoleCatalogService::load()['series'] ?? []),
    'assets' => ShopConfigService::assetLibrary(),
    'metrics' => [
        'units' => count($units),
        'enabledUnits' => count(array_filter($units, static fn (array $unit): bool => !empty($unit['isEnabled']))),
        'products' => count($products),
        'roleProducts' => count($roleProducts),
        'itemProducts' => count($itemProducts),
        'activeProducts' => count($activeProducts),
        'visibleProducts' => count($visibleProducts),
        'templates' => count($config['templates'] ?? []),
    ],
]);
