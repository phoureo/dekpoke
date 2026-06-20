<?php

declare(strict_types=1);

final class ActivityEventPresenterService
{
    public static function enrichRows(array $rows, string $guildId, ?string $asOf = null): array
    {
        return array_map(static function (array $row): array {
            $payload = json_decode((string) ($row['eventPayloadJson'] ?? '{}'), true);
            $context = json_decode((string) ($row['contextJson'] ?? '{}'), true);
            $payload = is_array($payload) ? $payload : [];
            $context = is_array($context) ? $context : [];

            $row['payload'] = $payload;
            $row['context'] = $context;
            $row['sourceTitle'] = (string) ($context['sourceTitle'] ?? '');
            $row['sourceDescription'] = (string) ($context['sourceDescription'] ?? '');
            $row['sourceFooterText'] = (string) ($context['sourceFooterText'] ?? '');
            if (!empty($row['joinInviteAttributionId'])) {
                $row['inviteAttribution'] = [
                    'inviteType' => (string) ($row['inviteType'] ?? ''),
                    'inviterUserId' => (string) ($row['inviterUserId'] ?? ''),
                    'inviterName' => (string) ($row['inviterName'] ?? ''),
                    'inviteCount' => $row['inviteCount'] !== null ? (int) $row['inviteCount'] : null,
                    'matchStatus' => (string) ($row['inviteMatchStatus'] ?? ''),
                    'sourceMessageId' => (string) ($row['inviteSourceMessageId'] ?? ''),
                    'sourceMessageDate' => (string) ($row['inviteSourceMessageDate'] ?? ''),
                ];
            }
            $row['metadataPreview'] = self::preview($context ?: $payload);
            $row['detailFields'] = self::detailFields($row, $payload, $context);
            $row['detailSummary'] = self::detailSummary($row, $payload, $context);
            $row['ref1'] = (string) ($row['targetId'] ?? '');
            $row['ref1Label'] = (string) ($row['targetType'] ?? 'target');
            $row['ref2'] = (string) ($row['sourceName'] ?? '');
            $row['ref2Label'] = 'source';
            return $row;
        }, $rows);
    }

    private static function detailFields(array $row, array $payload, array $context): array
    {
        $fields = [];
        if (!empty($row['joinInviteAttributionId'])) {
            foreach ([
                'invite_type' => $row['inviteType'] ?? '',
                'inviter_user_id' => $row['inviterUserId'] ?? '',
                'inviter_name' => $row['inviterName'] ?? '',
                'invite_count' => $row['inviteCount'] ?? '',
                'invite_match' => $row['inviteMatchStatus'] ?? '',
            ] as $label => $value) {
                if ($value !== null && $value !== '') {
                    $fields[] = ['label' => $label, 'value' => (string) $value];
                }
            }
        }
        foreach (['users_count', 'user_limit', 'previous_users_count', 'previous_user_limit', 'action'] as $key) {
            if (array_key_exists($key, $context) && $context[$key] !== null && $context[$key] !== '') {
                $fields[] = ['label' => $key, 'value' => (string) $context[$key]];
            }
        }
        foreach (['old_channel_id', 'channel_id', 'role_id', 'code'] as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null && $payload[$key] !== '') {
                $fields[] = ['label' => $key, 'value' => (string) $payload[$key]];
            }
        }
        return array_slice($fields, 0, 8);
    }

    private static function detailSummary(array $row, array $payload, array $context): string
    {
        if (!empty($row['joinInviteAttributionId'])) {
            return 'invite attribution attached to canonical GUILD_MEMBER_ADD';
        }
        if (!empty($context['policy'])) {
            return (string) $context['policy'];
        }
        if (!empty($payload['workspace_context']) && is_array($payload['workspace_context'])) {
            return self::preview($payload['workspace_context']);
        }
        return (string) ($row['sourceName'] ?? '');
    }

    private static function preview(array $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return '';
        }
        return mb_strlen($json) > 220 ? mb_substr($json, 0, 220) . '...' : $json;
    }
}
