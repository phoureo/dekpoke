<?php

declare(strict_types=1);

final class AuditLogger
{
    public static function access(string $eventType, ?string $targetType = null, ?string $targetId = null, array $metadata = [], string $sensitivity = 'normal'): int
    {
        $user = Auth::currentUser();

        return Database::insert('tbl_access_log', [
            'adminUserId' => $user['adminUserId'] ?? null,
            'discordUserId' => $user['discordUserId'] ?? null,
            'eventType' => $eventType,
            'targetType' => $targetType,
            'targetId' => $targetId,
            'sensitivityLevel' => $sensitivity,
            'requestPayloadJson' => self::json(self::mask(self::requestPayload())),
            'metadataJson' => self::json(self::mask($metadata)),
            'ipAddress' => Http::clientIp(),
            'userAgent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    public static function start(string $actionType, ?string $targetType = null, ?string $targetId = null, ?string $guildId = null, array $payload = [], mixed $before = null): int
    {
        return self::insertAction([
            'actionType' => $actionType,
            'targetType' => $targetType,
            'targetId' => $targetId,
            'guildId' => $guildId,
            'actionStage' => 'pending',
            'status' => 'pending',
            'requestPayloadJson' => self::json(self::mask($payload)),
            'beforeJson' => self::json(self::mask($before)),
        ]);
    }

    public static function finish(int $parentAdminActionId, string $status, array $response = [], mixed $after = null, ?string $error = null, ?int $httpStatus = null, ?string $discordReason = null): int
    {
        $parent = Database::fetch('SELECT * FROM tbl_admin_action WHERE adminActionId = :id', ['id' => $parentAdminActionId]);
        if (!$parent) {
            throw new RuntimeException('Parent admin action not found.');
        }

        return self::insertAction([
            'parentAdminActionId' => $parentAdminActionId,
            'adminUserId' => $parent['adminUserId'],
            'discordUserId' => $parent['discordUserId'],
            'guildId' => $parent['guildId'],
            'actionType' => $parent['actionType'],
            'targetType' => $parent['targetType'],
            'targetId' => $parent['targetId'],
            'actionStage' => 'completion',
            'status' => $status,
            'requestPayloadJson' => $parent['requestPayloadJson'],
            'responsePayloadJson' => self::json(self::mask($response)),
            'beforeJson' => $parent['beforeJson'],
            'afterJson' => self::json(self::mask($after)),
            'errorMessage' => $error,
            'httpStatus' => $httpStatus,
            'discordAuditReason' => $discordReason,
        ]);
    }

    public static function reject(string $actionType, string $reason, ?string $targetType = null, ?string $targetId = null, array $payload = [], ?string $guildId = null): int
    {
        return self::insertAction([
            'guildId' => $guildId,
            'actionType' => $actionType,
            'targetType' => $targetType,
            'targetId' => $targetId,
            'actionStage' => 'rejected',
            'status' => 'rejected',
            'requestPayloadJson' => self::json(self::mask($payload)),
            'errorMessage' => $reason,
        ]);
    }

    public static function target(int $adminActionId, string $targetType, string $targetId, array $metadata = []): void
    {
        Database::insert('tbl_admin_action_target', [
            'adminActionId' => $adminActionId,
            'targetType' => $targetType,
            'targetId' => $targetId,
            'metadataJson' => self::json(self::mask($metadata)),
        ]);
    }

    public static function discordReason(int $adminActionId, string $actionType, ?string $discordUserId = null): string
    {
        $discordUserId ??= Auth::currentUser()['discordUserId'] ?? 'unknown';

        return sprintf('Dekpoke Orbit Console | admin=%s | action=%d | type=%s', $discordUserId, $adminActionId, $actionType);
    }

    public static function requestPayload(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);

        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'path' => $_SERVER['REQUEST_URI'] ?? '',
            'query' => $_GET,
            'post' => $_POST,
            'json' => is_array($json) ? $json : null,
        ];
    }

    public static function mask(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = self::isSensitive((string) $key) ? '[masked]' : self::mask($item);
            }
            return $result;
        }

        if (is_object($value)) {
            return self::mask((array) $value);
        }

        return $value;
    }

    private static function insertAction(array $data): int
    {
        $user = Auth::currentUser();

        return Database::insert('tbl_admin_action', array_merge([
            'parentAdminActionId' => null,
            'adminUserId' => $user['adminUserId'] ?? null,
            'discordUserId' => $user['discordUserId'] ?? null,
            'guildId' => null,
            'actionType' => 'unknown',
            'targetType' => null,
            'targetId' => null,
            'actionStage' => 'event',
            'status' => 'pending',
            'permissionSnapshotJson' => self::json(Auth::snapshot($user)),
            'requestPayloadJson' => null,
            'responsePayloadJson' => null,
            'beforeJson' => null,
            'afterJson' => null,
            'errorMessage' => null,
            'discordAuditReason' => null,
            'httpStatus' => null,
            'ipAddress' => Http::clientIp(),
            'userAgent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ], $data));
    }

    private static function isSensitive(string $key): bool
    {
        foreach (Bootstrap::config('audit.sensitiveKeys', []) as $needle) {
            if (stripos($key, (string) $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function json(mixed $value): ?string
    {
        return $value === null ? null : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
