<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requirePermission('gacha.manage');
AuditLogger::access('gacha_prize_read', 'page', 'gacha_prize');

$config = GachaConfigService::load();
$roles = Database::fetchAll(
    'SELECT roleId, roleName, rolePosition, roleColor, iconHash, unicodeEmoji, isManaged
     FROM tbl_role
     WHERE guildId = :guildId AND deleteDate IS NULL
     ORDER BY rolePosition DESC, roleName ASC',
    ['guildId' => (string) Bootstrap::config('discord.guildId', '')]
);

$activePrizes = array_values(array_filter($config['prizes'], static fn (array $prize): bool => !empty($prize['active'])));
$visiblePrizes = array_values(array_filter($config['prizes'], static fn (array $prize): bool => !empty($prize['visible'])));
$rolePrizes = array_values(array_filter($config['prizes'], static fn (array $prize): bool => ($prize['type'] ?? '') === 'role'));
$itemPrizes = array_values(array_filter($config['prizes'], static fn (array $prize): bool => ($prize['type'] ?? 'item') === 'item'));

Response::json([
    'ok' => true,
    'config' => $config,
    'assets' => gachaPrizeAssetLibrary(),
    'roles' => array_map(static function (array $role): array {
        $role['roleIconUrl'] = DiscordAssets::roleIcon($role['roleId'], $role['iconHash'] ?? null, 64);
        return $role;
    }, class_exists('RoleCatalogService') ? RoleCatalogService::decorateRoles($roles) : $roles),
    'metrics' => [
        'tiers' => count($config['tiers']),
        'prizes' => count($config['prizes']),
        'itemPrizes' => count($itemPrizes),
        'activePrizes' => count($activePrizes),
        'visiblePrizes' => count($visiblePrizes),
        'rolePrizes' => count($rolePrizes),
        'templates' => count($config['templates'] ?? []),
        'internalRateTotal' => array_sum(array_map(static fn (array $tier): float => (float) ($tier['rate'] ?? 0), $config['tiers'])),
    ],
    'timeline' => GachaConfigService::timeline($config),
]);

function gachaPrizeAssetLibrary(): array
{
    $roots = [
        ['dir' => Bootstrap::rootPath('gacha/images'), 'prefix' => 'images'],
        ['dir' => Bootstrap::rootPath('gacha/uploads/prizes'), 'prefix' => 'uploads/prizes'],
    ];
    $assets = [];
    foreach ($roots as $root) {
        $dir = $root['dir'];
        if (!is_dir($dir)) {
            continue;
        }
        foreach (scandir($dir) ?: [] as $fileName) {
            if ($fileName === '.' || $fileName === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $fileName;
            if (!is_file($path) || !preg_match('/\.(png|jpe?g|webp|gif)$/i', $fileName)) {
                continue;
            }
            $assetPath = $root['prefix'] . '/' . $fileName;
            $modifiedAt = filemtime($path) ?: 0;
            $isUploaded = str_starts_with($assetPath, 'uploads/');
            $assets[] = [
                'path' => $assetPath,
                'name' => $fileName,
                'url' => rtrim((string) Bootstrap::config('app.baseUrl', '/discord'), '/') . '/gacha/' . $assetPath,
                'kind' => str_starts_with($fileName, 'ICO') || str_contains(strtolower($fileName), 'icon') ? 'icon' : 'image',
                'modifiedAt' => $modifiedAt,
                'isUploaded' => $isUploaded,
            ];
        }
    }

    usort($assets, static function (array $left, array $right): int {
        $uploadedCompare = ((int) !empty($right['isUploaded'])) <=> ((int) !empty($left['isUploaded']));
        if ($uploadedCompare !== 0) {
            return $uploadedCompare;
        }
        $modifiedCompare = ((int) ($right['modifiedAt'] ?? 0)) <=> ((int) ($left['modifiedAt'] ?? 0));
        if ($modifiedCompare !== 0) {
            return $modifiedCompare;
        }
        return strcmp((string) ($left['name'] ?? $left['path'] ?? ''), (string) ($right['name'] ?? $right['path'] ?? ''));
    });
    return $assets;
}
