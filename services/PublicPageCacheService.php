<?php

declare(strict_types=1);

final class PublicPageCacheService
{
    private const DIR = 'storage/tmp/page-cache';

    public static function get(string $key, int $ttlSeconds): string
    {
        $path = self::path($key);
        if (!is_file($path)) {
            return '';
        }
        if ($ttlSeconds <= 0 || time() - (filemtime($path) ?: 0) > $ttlSeconds) {
            @unlink($path);
            return '';
        }
        return (string) (file_get_contents($path) ?: '');
    }

    public static function put(string $key, string $html): void
    {
        if ($html === '') {
            return;
        }
        $dir = self::dir();
        if ($dir === '') {
            return;
        }
        @file_put_contents(self::path($key), $html, LOCK_EX);
    }

    public static function clear(string $prefix = ''): void
    {
        foreach (self::candidateDirs(false) as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.html') ?: [] as $path) {
                if ($prefix === '' || str_starts_with(basename($path), self::prefixHash($prefix))) {
                    @unlink($path);
                }
            }
        }
    }

    public static function key(string $prefix, array $parts = []): string
    {
        return self::prefixHash($prefix) . '-' . hash('sha256', json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }

    private static function path(string $key): string
    {
        $dir = self::dir();
        if ($dir === '') {
            $dir = sys_get_temp_dir();
        }
        return $dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_.-]+/i', '-', $key) . '.html';
    }

    private static function prefixHash(string $prefix): string
    {
        return substr(hash('sha256', $prefix), 0, 12);
    }

    private static function dir(): string
    {
        foreach (self::candidateDirs(true) as $dir) {
            if (self::ensureDir($dir)) {
                return $dir;
            }
        }
        return '';
    }

    /** @return array<int, string> */
    private static function candidateDirs(bool $includeFallback): array
    {
        $dirs = [Bootstrap::rootPath(self::DIR)];
        if ($includeFallback) {
            $dirs[] = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'dekpoke-page-cache-'
                . substr(hash('sha256', Bootstrap::rootPath()), 0, 12);
        }
        return $dirs;
    }

    private static function ensureDir(string $dir): bool
    {
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }
        @chmod($dir, 0777);
        return is_writable($dir);
    }
}
