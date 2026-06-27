<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

$rawRoleIds = $_GET['roleIds'] ?? $_GET['roleId'] ?? '';
if (is_array($rawRoleIds)) {
    $rawRoleIds = implode(',', $rawRoleIds);
}

$roleIds = array_values(array_unique(array_filter(
    array_map('trim', explode(',', (string) $rawRoleIds)),
    static fn (string $roleId): bool => preg_match('/^\d{5,32}$/', $roleId) === 1
)));

$roleIds = array_slice($roleIds, 0, 25);

if ($roleIds === []) {
    Response::json([
        'ok' => true,
        'roles' => [],
    ]);
}

$roles = [];
foreach ($roleIds as $roleId) {
    $roles[$roleId] = [
        'roleId' => $roleId,
        'roleName' => null,
        'count' => 0,
        'members' => [],
    ];
}

$placeholders = [];
$params = [];
foreach ($roleIds as $index => $roleId) {
    $key = ':role' . $index;
    $placeholders[] = $key;
    $params[$key] = $roleId;
}

try {
    $rows = Database::fetchAll(
        'SELECT
            r.roleId,
            r.roleName,
            mr.userId,
            m.nickName,
            u.globalName,
            u.userName,
            u.isBot
        FROM tbl_role r
        LEFT JOIN tbl_member_role mr
            ON mr.roleId = r.roleId
            AND mr.guildId = r.guildId
            AND mr.isActive = 1
            AND mr.deleteDate IS NULL
        LEFT JOIN tbl_member m
            ON m.guildId = mr.guildId
            AND m.userId = mr.userId
            AND m.isActive = 1
            AND m.isDelete = 0
        LEFT JOIN tbl_user u
            ON u.userId = mr.userId
        WHERE r.roleId IN (' . implode(', ', $placeholders) . ')
            AND r.deleteDate IS NULL
        ORDER BY
            r.rolePosition DESC,
            COALESCE(NULLIF(m.nickName, \'\'), NULLIF(u.globalName, \'\'), NULLIF(u.userName, \'\'), mr.userId) ASC',
        $params
    );

    foreach ($rows as $row) {
        $roleId = (string) $row['roleId'];
        if (!isset($roles[$roleId])) {
            continue;
        }

        $roles[$roleId]['roleName'] = $row['roleName'] ?? $roles[$roleId]['roleName'];

        $userId = (string) ($row['userId'] ?? '');
        if ($userId === '') {
            continue;
        }

        $displayName = trim((string) ($row['nickName'] ?? ''));
        if ($displayName === '') {
            $displayName = trim((string) ($row['globalName'] ?? ''));
        }
        if ($displayName === '') {
            $displayName = trim((string) ($row['userName'] ?? ''));
        }
        if ($displayName === '') {
            $displayName = $userId;
        }

        $roles[$roleId]['members'][] = [
            'userId' => $userId,
            'displayName' => $displayName,
            'userName' => $row['userName'] ?? null,
            'globalName' => $row['globalName'] ?? null,
            'nickName' => $row['nickName'] ?? null,
            'isBot' => (bool) ($row['isBot'] ?? false),
        ];
    }

    foreach ($roles as $roleId => $role) {
        $roles[$roleId]['count'] = count($role['members']);
    }

    Response::json([
        'ok' => true,
        'roles' => $roles,
    ]);
} catch (Throwable $exception) {
    Response::error('Failed to load role members.', 500, [
        'message' => $exception->getMessage(),
    ]);
}
