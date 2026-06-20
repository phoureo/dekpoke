<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireUser();
AuditLogger::access('role_list_read', 'page', 'roles');

$guildId = (string) Bootstrap::config('discord.guildId', '');
$roles = Database::fetchAll(
    'SELECT r.*, COUNT(mr.memberRoleId) AS memberCount
     FROM tbl_role r
     LEFT JOIN tbl_member_role mr ON mr.roleId = r.roleId AND mr.isActive = 1
     WHERE r.guildId = :guildId AND r.deleteDate IS NULL
     GROUP BY r.roleId
     ORDER BY r.rolePosition DESC, r.roleName ASC',
    ['guildId' => $guildId]
);
$roleCatalog = class_exists('RoleCatalogService') ? RoleCatalogService::load() : ['series' => [], 'roles' => []];
$roles = class_exists('RoleCatalogService') ? RoleCatalogService::decorateRoles($roles, $roleCatalog) : $roles;
$permissionDefaults = RolePermissionDescriptionService::defaults();
$permissionCatalog = RolePermissionDescriptionService::catalog();
$defaultMap = is_array($permissionDefaults['permissions'] ?? null) ? $permissionDefaults['permissions'] : [];

$botPosition = highestRolePosition((string) Bootstrap::config('discord.botUserId', ''));
$admin = Auth::currentUser();
$adminPosition = highestRolePosition((string) ($admin['discordUserId'] ?? ''));

Response::json([
    'ok' => true,
    'roles' => array_map(static function (array $role): array {
        $role['permissionsDecoded'] = DiscordPermissions::decode($role['permissions'] ?? null);
        $role['permissionCount'] = count($role['permissionsDecoded']);
        $role['roleIconUrl'] = DiscordAssets::roleIcon($role['roleId'], $role['iconHash'] ?? null, 64);
        return $role;
    }, $roles),
    'roleCatalog' => $roleCatalog,
    'tierOptions' => class_exists('RoleCatalogService') ? RoleCatalogService::tierOptions() : ['S', 'A', 'B', 'C'],
    'permissionDescriptions' => [
        'permissions' => $permissionCatalog,
        'defaults' => array_values(array_map(static function (array $entry) use ($defaultMap): array {
            $default = is_array($defaultMap[$entry['code']] ?? null) ? $defaultMap[$entry['code']] : [];
            return [
                'code' => (string) ($entry['code'] ?? ''),
                'bit' => (int) ($entry['bit'] ?? 0),
                'discordLabel' => (string) ($entry['discordLabel'] ?? ''),
                'label' => (string) ($default['label'] ?? $entry['discordLabel'] ?? ''),
                'badge' => (string) ($default['badge'] ?? ''),
                'description' => (string) ($default['description'] ?? ''),
            ];
        }, $permissionCatalog)),
        'metrics' => RolePermissionDescriptionService::metrics(false),
    ],
    'hierarchy' => [
        'botHighestPosition' => $botPosition,
        'adminHighestPosition' => $adminPosition,
        'isOwner' => ($admin['roleName'] ?? '') === 'Owner',
    ],
]);

function highestRolePosition(string $userId): int
{
    if ($userId === '') {
        return 0;
    }

    $row = Database::fetch(
        'SELECT COALESCE(MAX(r.rolePosition), 0) AS position
         FROM tbl_member_role mr
         INNER JOIN tbl_role r ON r.roleId = mr.roleId
         WHERE mr.guildId = :guildId AND mr.userId = :userId AND mr.isActive = 1',
        ['guildId' => Bootstrap::config('discord.guildId', ''), 'userId' => $userId]
    );

    return (int) ($row['position'] ?? 0);
}
