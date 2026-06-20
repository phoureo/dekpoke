<?php

declare(strict_types=1);

final class DiscordPermissions
{
    public static function catalog(): array
    {
        return [
            'CREATE_INSTANT_INVITE' => ['bit' => 0, 'label' => 'Create Invite'],
            'KICK_MEMBERS' => ['bit' => 1, 'label' => 'Kick Members'],
            'BAN_MEMBERS' => ['bit' => 2, 'label' => 'Ban Members'],
            'ADMINISTRATOR' => ['bit' => 3, 'label' => 'Administrator'],
            'MANAGE_CHANNELS' => ['bit' => 4, 'label' => 'Manage Channels'],
            'MANAGE_GUILD' => ['bit' => 5, 'label' => 'Manage Server'],
            'ADD_REACTIONS' => ['bit' => 6, 'label' => 'Add Reactions'],
            'VIEW_AUDIT_LOG' => ['bit' => 7, 'label' => 'View Audit Log'],
            'PRIORITY_SPEAKER' => ['bit' => 8, 'label' => 'Priority Speaker'],
            'STREAM' => ['bit' => 9, 'label' => 'Video / Stream'],
            'VIEW_CHANNEL' => ['bit' => 10, 'label' => 'View Channel'],
            'SEND_MESSAGES' => ['bit' => 11, 'label' => 'Send Messages'],
            'SEND_TTS_MESSAGES' => ['bit' => 12, 'label' => 'Send TTS Messages'],
            'MANAGE_MESSAGES' => ['bit' => 13, 'label' => 'Manage Messages'],
            'EMBED_LINKS' => ['bit' => 14, 'label' => 'Embed Links'],
            'ATTACH_FILES' => ['bit' => 15, 'label' => 'Attach Files'],
            'READ_MESSAGE_HISTORY' => ['bit' => 16, 'label' => 'Read Message History'],
            'MENTION_EVERYONE' => ['bit' => 17, 'label' => 'Mention Everyone'],
            'USE_EXTERNAL_EMOJIS' => ['bit' => 18, 'label' => 'Use External Emojis'],
            'VIEW_GUILD_INSIGHTS' => ['bit' => 19, 'label' => 'View Server Insights'],
            'CONNECT' => ['bit' => 20, 'label' => 'Connect Voice'],
            'SPEAK' => ['bit' => 21, 'label' => 'Speak'],
            'MUTE_MEMBERS' => ['bit' => 22, 'label' => 'Mute Members'],
            'DEAFEN_MEMBERS' => ['bit' => 23, 'label' => 'Deafen Members'],
            'MOVE_MEMBERS' => ['bit' => 24, 'label' => 'Move Members'],
            'USE_VAD' => ['bit' => 25, 'label' => 'Use Voice Activity'],
            'CHANGE_NICKNAME' => ['bit' => 26, 'label' => 'Change Nickname'],
            'MANAGE_NICKNAMES' => ['bit' => 27, 'label' => 'Manage Nicknames'],
            'MANAGE_ROLES' => ['bit' => 28, 'label' => 'Manage Roles'],
            'MANAGE_WEBHOOKS' => ['bit' => 29, 'label' => 'Manage Webhooks'],
            'MANAGE_GUILD_EXPRESSIONS' => ['bit' => 30, 'label' => 'Manage Expressions'],
            'USE_APPLICATION_COMMANDS' => ['bit' => 31, 'label' => 'Use App Commands'],
            'REQUEST_TO_SPEAK' => ['bit' => 32, 'label' => 'Request To Speak'],
            'MANAGE_EVENTS' => ['bit' => 33, 'label' => 'Manage Events'],
            'MANAGE_THREADS' => ['bit' => 34, 'label' => 'Manage Threads'],
            'CREATE_PUBLIC_THREADS' => ['bit' => 35, 'label' => 'Create Public Threads'],
            'CREATE_PRIVATE_THREADS' => ['bit' => 36, 'label' => 'Create Private Threads'],
            'USE_EXTERNAL_STICKERS' => ['bit' => 37, 'label' => 'Use External Stickers'],
            'SEND_MESSAGES_IN_THREADS' => ['bit' => 38, 'label' => 'Send In Threads'],
            'USE_EMBEDDED_ACTIVITIES' => ['bit' => 39, 'label' => 'Use Activities'],
            'MODERATE_MEMBERS' => ['bit' => 40, 'label' => 'Timeout Members'],
            'VIEW_CREATOR_MONETIZATION_ANALYTICS' => ['bit' => 41, 'label' => 'Creator Analytics'],
            'USE_SOUNDBOARD' => ['bit' => 42, 'label' => 'Use Soundboard'],
            'CREATE_GUILD_EXPRESSIONS' => ['bit' => 43, 'label' => 'Create Expressions'],
            'CREATE_EVENTS' => ['bit' => 44, 'label' => 'Create Events'],
            'USE_EXTERNAL_SOUNDS' => ['bit' => 45, 'label' => 'Use External Sounds'],
            'SEND_VOICE_MESSAGES' => ['bit' => 46, 'label' => 'Send Voice Messages'],
            'SEND_POLLS' => ['bit' => 49, 'label' => 'Send Polls'],
            'USE_EXTERNAL_APPS' => ['bit' => 50, 'label' => 'Use External Apps'],
        ];
    }

    public static function decode(?string $bitset): array
    {
        if ($bitset === null || $bitset === '' || !is_numeric($bitset)) {
            return [];
        }

        $value = (int) $bitset;
        $out = [];
        foreach (self::catalog() as $code => $item) {
            $mask = 1 << (int) $item['bit'];
            if (($value & $mask) === $mask) {
                $out[] = [
                    'code' => $code,
                    'label' => $item['label'],
                    'bit' => $item['bit'],
                ];
            }
        }

        return $out;
    }
}
