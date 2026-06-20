<?php

declare(strict_types=1);

final class RoleCatalogService
{
    public const SETTING_KEY = 'reward.role_catalog';

    public static function defaults(): array
    {
        return [
            'version' => 1,
            'series' => [],
            'roles' => [],
        ];
    }

    public static function load(bool $seed = true): array
    {
        $defaults = self::defaults();

        try {
            $row = Database::fetch(
                'SELECT settingValueJson FROM tbl_setting WHERE settingKey = :settingKey',
                ['settingKey' => self::SETTING_KEY]
            );
        } catch (Throwable) {
            return $defaults;
        }

        if (!$row || trim((string) ($row['settingValueJson'] ?? '')) === '') {
            if ($seed) {
                self::save($defaults);
            }
            return $defaults;
        }

        $decoded = json_decode((string) $row['settingValueJson'], true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        $normalized = self::normalize($decoded);
        if ($normalized !== $decoded) {
            try {
                self::save($normalized);
            } catch (Throwable) {
                // Keep using normalized data in memory.
            }
        }

        return $normalized;
    }

    public static function save(array $config): array
    {
        $normalized = self::normalize($config);
        Database::execute(
            'INSERT INTO tbl_setting (settingKey, settingValueJson, isSecret, updateDate)
             VALUES (:settingKey, :settingValueJson, 0, :updateDate)
             ON DUPLICATE KEY UPDATE settingValueJson = VALUES(settingValueJson), updateDate = VALUES(updateDate)',
            [
                'settingKey' => self::SETTING_KEY,
                'settingValueJson' => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );

        return $normalized;
    }

    public static function tierOptions(): array
    {
        return ['S', 'A', 'B', 'C'];
    }

    /** @return array<string, array<string, mixed>> */
    public static function seriesIndex(bool $seed = true): array
    {
        return array_column(self::load($seed)['series'] ?? [], null, 'id');
    }

    /** @return array<string, array<string, mixed>> */
    public static function roleMetaIndex(bool $seed = true): array
    {
        $config = self::load($seed);
        $out = [];
        foreach (array_filter($config['roles'] ?? [], 'is_array') as $entry) {
            $roleId = (string) ($entry['roleId'] ?? '');
            if ($roleId === '') {
                continue;
            }
            $out[$roleId] = $entry;
        }
        return $out;
    }

    public static function decorateRole(array $role, ?array $config = null): array
    {
        $config ??= self::load();
        $seriesIndex = array_column($config['series'] ?? [], null, 'id');
        $roleIndex = [];
        foreach (array_filter($config['roles'] ?? [], 'is_array') as $entry) {
            $roleId = (string) ($entry['roleId'] ?? '');
            if ($roleId !== '') {
                $roleIndex[$roleId] = $entry;
            }
        }

        $roleId = (string) ($role['roleId'] ?? '');
        $meta = is_array($roleIndex[$roleId] ?? null) ? $roleIndex[$roleId] : [];
        $seriesId = (string) ($meta['seriesId'] ?? '');
        $series = is_array($seriesIndex[$seriesId] ?? null) ? $seriesIndex[$seriesId] : [];

        $role['roleTier'] = (string) ($meta['tier'] ?? '');
        $role['roleSeriesId'] = $seriesId;
        $role['roleSeriesName'] = (string) ($series['name'] ?? '');
        $role['roleSeriesIcon'] = (string) ($series['icon'] ?? '');
        $role['roleSeriesBackground'] = (string) ($series['background'] ?? '');
        $role['roleSeriesBadge'] = (string) ($series['badge'] ?? '');
        $role['roleSeriesDescription'] = (string) ($series['description'] ?? '');
        $role['roleSeriesSortOrder'] = (int) ($series['sortOrder'] ?? 9999);

        return $role;
    }

    /** @return array<int, array<string, mixed>> */
    public static function decorateRoles(array $roles, ?array $config = null): array
    {
        $config ??= self::load();
        return array_map(static fn (array $role): array => self::decorateRole($role, $config), $roles);
    }

    private static function normalize(array $config): array
    {
        $seriesInput = [];
        foreach (array_filter($config['series'] ?? [], 'is_array') as $entry) {
            $seriesInput[] = $entry;
        }

        $normalizedSeries = [];
        $usedIds = [];
        foreach ($seriesInput as $index => $entry) {
            $name = trim((string) ($entry['name'] ?? ''));
            $id = self::normalizeSeriesId((string) ($entry['id'] ?? ''), $name, $index + 1);
            if (isset($usedIds[$id])) {
                $id .= '-' . ($index + 1);
            }
            $usedIds[$id] = true;
            $normalizedSeries[] = [
                'id' => $id,
                'name' => $name !== '' ? $name : ('Series ' . ($index + 1)),
                'icon' => substr(trim((string) ($entry['icon'] ?? '')), 0, 190),
                'background' => substr(trim((string) ($entry['background'] ?? '')), 0, 240),
                'badge' => trim((string) ($entry['badge'] ?? '')),
                'description' => trim((string) ($entry['description'] ?? '')),
                'sortOrder' => (int) ($entry['sortOrder'] ?? (($index + 1) * 10)),
            ];
        }

        usort($normalizedSeries, static fn (array $left, array $right): int => ($left['sortOrder'] <=> $right['sortOrder']) ?: strcmp($left['name'], $right['name']));
        $seriesIds = array_column($normalizedSeries, 'id');

        $roleInput = [];
        foreach ($config['roles'] ?? [] as $roleId => $entry) {
            if (is_string($roleId) && is_array($entry)) {
                $entry['roleId'] = $entry['roleId'] ?? $roleId;
            }
            if (is_array($entry)) {
                $roleInput[] = $entry;
            }
        }

        $normalizedRoles = [];
        foreach ($roleInput as $entry) {
            $roleId = trim((string) ($entry['roleId'] ?? ''));
            if ($roleId === '') {
                continue;
            }

            $tier = strtoupper(trim((string) ($entry['tier'] ?? '')));
            if (!in_array($tier, self::tierOptions(), true)) {
                $tier = '';
            }

            $seriesId = trim((string) ($entry['seriesId'] ?? ''));
            if ($seriesId !== '' && !in_array($seriesId, $seriesIds, true)) {
                $seriesId = '';
            }

            $normalizedRoles[] = [
                'roleId' => $roleId,
                'tier' => $tier,
                'seriesId' => $seriesId,
            ];
        }

        usort($normalizedRoles, static fn (array $left, array $right): int => strcmp($left['roleId'], $right['roleId']));

        return [
            'version' => 1,
            'series' => $normalizedSeries,
            'roles' => $normalizedRoles,
        ];
    }

    private static function normalizeSeriesId(string $candidate, string $name, int $index): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            $candidate = $name;
        }
        $candidate = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $candidate) ?? '');
        $candidate = trim($candidate, '-');
        if ($candidate === '') {
            $candidate = 'series-' . $index;
        }
        return $candidate;
    }
}
