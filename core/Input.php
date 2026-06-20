<?php

declare(strict_types=1);

final class Input
{
    public static function json(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        $json = self::json();
        return $json[$key] ?? $_POST[$key] ?? $default;
    }

    public static function str(string $key, ?string $default = null, string $source = 'get'): ?string
    {
        $value = $source === 'post' ? self::post($key, $default) : self::get($key, $default);
        if ($value === null) {
            return $default;
        }

        $value = trim((string) $value);
        return $value === '' ? $default : $value;
    }

    public static function int(string $key, int $default = 0, string $source = 'get'): int
    {
        $value = $source === 'post' ? self::post($key, $default) : self::get($key, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function bool(string $key, bool $default = false, string $source = 'get'): bool
    {
        $value = $source === 'post' ? self::post($key, $default) : self::get($key, $default);
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
