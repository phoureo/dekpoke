<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireUser();

$guildId = (string) Bootstrap::config('discord.guildId', '');
$roleId = Input::str('roleId');
if (!$roleId) {
    Response::error('roleId is required.', 422);
}

$asOfInput = Input::str('asOf');
$asOf = null;
if ($asOfInput) {
    $asOfTs = strtotime($asOfInput);
    if ($asOfTs === false) {
        Response::error('Invalid asOf timestamp.', 422);
    }
    $asOf = date('Y-m-d H:i:s', $asOfTs);
}

AuditLogger::access('role_detail_read', 'role', $roleId, ['view' => 'role_drawer', 'asOf' => $asOf], 'sensitive');

$role = Database::fetch(
    'SELECT r.*, COUNT(mr.memberRoleId) AS memberCount
       FROM tbl_role r
       LEFT JOIN tbl_member_role mr ON mr.guildId = r.guildId AND mr.roleId = r.roleId AND mr.isActive = 1
      WHERE r.guildId = :guildId AND r.roleId = :roleId
      GROUP BY r.roleId',
    ['guildId' => $guildId, 'roleId' => $roleId]
);

if (!$role) {
    Response::error('Role not found. Run Server Snapshot first.', 404);
}

$role['historicalContext'] = [
    'mode' => 'current',
    'asOf' => $asOf,
    'roleConfidence' => $asOf ? 'current_filtered' : 'current',
    'memberConfidence' => 'current',
    'sourceType' => 'current',
    'sourceDate' => $role['updateDate'] ?? null,
];

$members = Database::fetchAll(
    'SELECT m.userId, m.nickName, m.joinedAt, m.isActive, m.guildAvatarHash,
            u.userName, u.globalName, u.avatarHash, u.isBot,
            COALESCE(m.nickName, u.globalName, u.userName, m.userId) AS displayName
       FROM tbl_member_role mr
       INNER JOIN tbl_member m ON m.guildId = mr.guildId AND m.userId = mr.userId
       INNER JOIN tbl_user u ON u.userId = m.userId
      WHERE mr.guildId = :guildId AND mr.roleId = :roleId AND mr.isActive = 1
      ORDER BY displayName ASC
      LIMIT 1001',
    ['guildId' => $guildId, 'roleId' => $roleId]
);
$membersTruncated = count($members) > 1000;
if ($membersTruncated) {
    $members = array_slice($members, 0, 1000);
}

$overwrites = Database::fetchAll(
    'SELECT co.*, c.channelName, c.channelType, c.parentChannelId, parent.channelName AS categoryName
       FROM tbl_channel_overwrite co
       INNER JOIN tbl_channel c ON c.channelId = co.channelId
       LEFT JOIN tbl_channel parent ON parent.channelId = c.parentChannelId
      WHERE co.overwriteId = :roleId AND co.overwriteType = 0
      ORDER BY COALESCE(parent.channelPosition, c.channelPosition), c.channelPosition ASC, c.channelName ASC',
    ['roleId' => $roleId]
);

$revisions = Database::fetchAll(
    'SELECT *
       FROM tbl_role_revision
      WHERE guildId = :guildId AND roleId = :roleId' . ($asOf ? ' AND createDate <= :asOf' : '') . '
      ORDER BY roleRevisionId DESC
      LIMIT 80',
    ['guildId' => $guildId, 'roleId' => $roleId] + ($asOf ? ['asOf' => $asOf] : [])
);

$systemHistory = Database::fetchAll(
    'SELECT aa.*, au.displayName, au.discordUserName
       FROM tbl_admin_action aa
       LEFT JOIN tbl_admin_user au ON au.adminUserId = aa.adminUserId
      WHERE aa.targetType = "role" AND aa.targetId = :roleId' . ($asOf ? ' AND aa.createDate <= :asOf' : '') . '
      ORDER BY aa.adminActionId DESC
      LIMIT 60',
    ['roleId' => $roleId] + ($asOf ? ['asOf' => $asOf] : [])
);

$role['permissionsDecoded'] = DiscordPermissions::decode($role['permissions'] ?? null);
$role['permissionCount'] = count($role['permissionsDecoded']);
$role['roleIconUrl'] = DiscordAssets::roleIcon($role['roleId'], $role['iconHash'] ?? null, 96);
$role = class_exists('RoleCatalogService') ? RoleCatalogService::decorateRole($role) : $role;
$role['contextConfidence'] = $role['historicalContext']['roleConfidence'];
$roleMetadata = json_decode((string) ($role['metadataJson'] ?? ''), true);
$rawColors = is_array($roleMetadata) && isset($roleMetadata['colors']) && is_array($roleMetadata['colors'])
    ? $roleMetadata['colors']
    : [
        'primary_color' => $role['roleColor'] ?? 0,
        'secondary_color' => null,
        'tertiary_color' => null,
    ];
$toHex = static function ($value): ?string {
    if ($value === null || $value === '') {
        return null;
    }
    return '#' . str_pad(dechex((int) $value), 6, '0', STR_PAD_LEFT);
};
$role['roleColors'] = [
    'primary' => [
        'value' => $rawColors['primary_color'] ?? ($role['roleColor'] ?? 0),
        'hex' => $toHex($rawColors['primary_color'] ?? ($role['roleColor'] ?? 0)),
    ],
    'secondary' => [
        'value' => $rawColors['secondary_color'] ?? null,
        'hex' => $toHex($rawColors['secondary_color'] ?? null),
    ],
    'tertiary' => [
        'value' => $rawColors['tertiary_color'] ?? null,
        'hex' => $toHex($rawColors['tertiary_color'] ?? null),
    ],
];

$allowedPermissions = array_column($role['permissionsDecoded'], 'code');
$permissionMetaIndex = class_exists('RolePermissionDescriptionService')
    ? array_column(RolePermissionDescriptionService::catalog(), null, 'code')
    : [];
$permissionCatalog = [];
foreach (DiscordPermissions::catalog() as $code => $item) {
    $meta = is_array($permissionMetaIndex[$code] ?? null) ? $permissionMetaIndex[$code] : [];
    $permissionCatalog[] = [
        'code' => $code,
        'label' => (string) ($meta['label'] ?? $item['label']),
        'badge' => (string) ($meta['badge'] ?? ''),
        'description' => (string) ($meta['description'] ?? ''),
        'discordLabel' => (string) ($item['label'] ?? $code),
        'bit' => $item['bit'],
        'isAllowed' => in_array($code, $allowedPermissions, true) ? 1 : 0,
    ];
}

Response::json([
    'ok' => true,
    'asOf' => $asOf,
    'role' => $role,
    'historicalContext' => $role['historicalContext'],
    'permissionCatalog' => $permissionCatalog,
    'membersTruncated' => $membersTruncated,
    'members' => array_map(static function (array $member) use ($guildId): array {
        $member['avatarUrl'] = DiscordAssets::guildAvatar($guildId, $member['userId'], $member['guildAvatarHash'] ?? null, 64)
            ?: DiscordAssets::avatar($member['userId'], $member['avatarHash'] ?? null, 64);
        return $member;
    }, $members),
    'overwrites' => array_map(static function (array $overwrite): array {
        $overwrite['allowDecoded'] = DiscordPermissions::decode($overwrite['allowPermissions'] ?? null);
        $overwrite['denyDecoded'] = DiscordPermissions::decode($overwrite['denyPermissions'] ?? null);
        return $overwrite;
    }, $overwrites),
    'revisions' => $revisions,
    'systemHistory' => $systemHistory,
    'auditHistory' => [],
]);
