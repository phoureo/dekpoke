<?php

declare(strict_types=1);

final class DiscordAssets
{
    public static function avatar(string|int|null $userId, ?string $avatarHash, int $size = 80): string
    {
        $userId = self::idString($userId);
        if ($userId && $avatarHash) {
            $extension = str_starts_with($avatarHash, 'a_') ? 'gif' : 'png';
            return sprintf('https://cdn.discordapp.com/avatars/%s/%s.%s?size=%d', rawurlencode($userId), rawurlencode($avatarHash), $extension, $size);
        }

        $index = $userId && ctype_digit($userId) ? ((int) ((int) $userId >> 22) % 6) : 0;
        return sprintf('https://cdn.discordapp.com/embed/avatars/%d.png', $index);
    }

    public static function guildIcon(string|int|null $guildId, ?string $iconHash, int $size = 96): ?string
    {
        $guildId = self::idString($guildId);
        if (!$guildId || !$iconHash) {
            return null;
        }

        $extension = str_starts_with($iconHash, 'a_') ? 'gif' : 'png';
        return sprintf('https://cdn.discordapp.com/icons/%s/%s.%s?size=%d', rawurlencode($guildId), rawurlencode($iconHash), $extension, $size);
    }

    public static function guildBanner(string|int|null $guildId, ?string $bannerHash, int $size = 600): ?string
    {
        $guildId = self::idString($guildId);
        if (!$guildId || !$bannerHash) {
            return null;
        }

        $extension = str_starts_with($bannerHash, 'a_') ? 'gif' : 'png';
        return sprintf('https://cdn.discordapp.com/banners/%s/%s.%s?size=%d', rawurlencode($guildId), rawurlencode($bannerHash), $extension, $size);
    }

    public static function banner(string|int|null $userId, ?string $bannerHash, int $size = 512): ?string
    {
        $userId = self::idString($userId);
        if (!$userId || !$bannerHash) {
            return null;
        }

        $extension = str_starts_with($bannerHash, 'a_') ? 'gif' : 'png';
        return sprintf('https://cdn.discordapp.com/banners/%s/%s.%s?size=%d', rawurlencode($userId), rawurlencode($bannerHash), $extension, $size);
    }

    public static function guildAvatar(string|int|null $guildId, string|int|null $userId, ?string $avatarHash, int $size = 128): ?string
    {
        $guildId = self::idString($guildId);
        $userId = self::idString($userId);
        if (!$guildId || !$userId || !$avatarHash) {
            return null;
        }

        $extension = str_starts_with($avatarHash, 'a_') ? 'gif' : 'png';
        return sprintf('https://cdn.discordapp.com/guilds/%s/users/%s/avatars/%s.%s?size=%d', rawurlencode($guildId), rawurlencode($userId), rawurlencode($avatarHash), $extension, $size);
    }

    public static function roleIcon(string|int|null $roleId, ?string $iconHash, int $size = 64): ?string
    {
        $roleId = self::idString($roleId);
        if (!$roleId || !$iconHash) {
            return null;
        }

        $extension = str_starts_with($iconHash, 'a_') ? 'gif' : 'png';
        return sprintf('https://cdn.discordapp.com/role-icons/%s/%s.%s?size=%d', rawurlencode($roleId), rawurlencode($iconHash), $extension, $size);
    }

    private static function idString(string|int|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
