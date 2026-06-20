<?php

declare(strict_types=1);

final class Http
{
    public static function isHttps(): bool
    {
        $https = strtolower(trim((string) ($_SERVER['HTTPS'] ?? '')));
        if ($https !== '' && $https !== 'off') {
            return true;
        }

        $requestScheme = strtolower(trim((string) ($_SERVER['REQUEST_SCHEME'] ?? '')));
        if ($requestScheme === 'https') {
            return true;
        }

        $forwardedSsl = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')));
        if ($forwardedSsl === 'on') {
            return true;
        }

        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        if ($forwardedProto !== '') {
            return trim(explode(',', $forwardedProto)[0]) === 'https';
        }

        return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }

    public static function origin(): string
    {
        return (self::isHttps() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public static function clientIp(): string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded) {
            return trim(explode(',', $forwarded)[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function sameOrigin(string $url): bool
    {
        $left = parse_url(self::origin());
        $right = parse_url($url);
        if (!$left || !$right) {
            return false;
        }

        $leftScheme = strtolower($left['scheme'] ?? 'http');
        $rightScheme = strtolower($right['scheme'] ?? 'http');
        $leftPort = (int) ($left['port'] ?? ($leftScheme === 'https' ? 443 : 80));
        $rightPort = (int) ($right['port'] ?? ($rightScheme === 'https' ? 443 : 80));

        return $leftScheme === $rightScheme
            && strtolower($left['host'] ?? '') === strtolower($right['host'] ?? '')
            && $leftPort === $rightPort;
    }

    public static function cookieOptions(int $expires = 0): array
    {
        return [
            'expires' => $expires,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}
