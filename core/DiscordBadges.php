<?php

declare(strict_types=1);

final class DiscordBadges
{
    public static function fromUserMetadata(?string $metadataJson): array
    {
        $metadata = json_decode((string) ($metadataJson ?? '{}'), true) ?: [];
        $flags = (int) ($metadata['public_flags'] ?? $metadata['flags'] ?? 0);

        $catalog = [
            0 => ['code' => 'staff', 'label' => 'Discord Staff', 'icon' => 'fa-solid fa-certificate'],
            1 => ['code' => 'partner', 'label' => 'Partnered Server Owner', 'icon' => 'fa-solid fa-handshake'],
            2 => ['code' => 'hypesquad', 'label' => 'HypeSquad Events', 'icon' => 'fa-solid fa-bolt'],
            3 => ['code' => 'bug_hunter_1', 'label' => 'Bug Hunter', 'icon' => 'fa-solid fa-bug'],
            6 => ['code' => 'bravery', 'label' => 'House Bravery', 'icon' => 'fa-solid fa-shield-heart'],
            7 => ['code' => 'brilliance', 'label' => 'House Brilliance', 'icon' => 'fa-solid fa-gem'],
            8 => ['code' => 'balance', 'label' => 'House Balance', 'icon' => 'fa-solid fa-scale-balanced'],
            9 => ['code' => 'early_supporter', 'label' => 'Early Supporter', 'icon' => 'fa-solid fa-heart'],
            14 => ['code' => 'bug_hunter_2', 'label' => 'Bug Hunter Gold', 'icon' => 'fa-solid fa-bug-slash'],
            16 => ['code' => 'verified_bot', 'label' => 'Verified Bot', 'icon' => 'fa-solid fa-robot'],
            17 => ['code' => 'early_verified_bot_developer', 'label' => 'Early Verified Bot Developer', 'icon' => 'fa-solid fa-code'],
            18 => ['code' => 'moderator_programs_alumni', 'label' => 'Moderator Programs Alumni', 'icon' => 'fa-solid fa-shield'],
            22 => ['code' => 'active_developer', 'label' => 'Active Developer', 'icon' => 'fa-solid fa-terminal'],
        ];

        $badges = [];
        foreach ($catalog as $bit => $badge) {
            $mask = 1 << $bit;
            if (($flags & $mask) === $mask) {
                $badges[] = $badge + ['bit' => $bit];
            }
        }

        if (!empty($metadata['bot'])) {
            $badges[] = ['code' => 'bot', 'label' => 'Bot', 'icon' => 'fa-solid fa-robot', 'bit' => null];
        }

        return $badges;
    }
}
