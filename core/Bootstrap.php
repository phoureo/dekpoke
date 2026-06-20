<?php

declare(strict_types=1);

spl_autoload_register(static function (string $className): void {
    foreach ([__DIR__, __DIR__ . '/../services'] as $basePath) {
        $path = $basePath . '/' . $className . '.php';
        if (is_file($path)) {
            require $path;
            return;
        }
    }
});

final class Bootstrap
{
    private static ?array $config = null;

    public static function init(bool $withSession = true): array
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/config.php';
            date_default_timezone_set(self::$config['app']['timezone'] ?? 'UTC');
        }

        if ($withSession && PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
            session_name(self::$config['auth']['sessionName'] ?? 'dekpoke_orbit_session');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => Http::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        return self::$config;
    }

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        $config = self::init(PHP_SAPI !== 'cli');
        if ($key === null) {
            return $config;
        }

        $value = $config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function rootPath(string $path = ''): string
    {
        return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }
}
