<?php

declare(strict_types=1);

final class DiscordOAuth
{
    public static function normalizeFlow(mixed $flow): string
    {
        $flow = strtolower(trim((string) $flow));
        return in_array($flow, ['admin', 'gacha'], true) ? $flow : 'admin';
    }

    public static function beginAuthorization(string $flow = 'admin', ?string $returnTo = null): array
    {
        $clientId = (string) Bootstrap::config('discord.clientId', '');
        $redirectUri = self::redirectUri();
        if ($clientId === '' || $redirectUri === '') {
            Response::error('Discord OAuth is not configured.', 500);
        }

        $flow = self::normalizeFlow($flow);
        $state = bin2hex(random_bytes(24));
        $_SESSION['oauthState'] = $state;
        $_SESSION['oauthFlow'] = $flow;
        $_SESSION['oauthReturnTo'] = self::sanitizeReturnTo($returnTo, $flow);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => (string) Bootstrap::config('discord.oauthScopes', 'identify guilds'),
            'state' => $state,
        ]);

        return [
            'authorizeUrl' => 'https://discord.com/oauth2/authorize?' . $params,
            'redirectUri' => $redirectUri,
            'flow' => $flow,
            'returnTo' => $_SESSION['oauthReturnTo'],
        ];
    }

    public static function redirectUri(): string
    {
        $configured = trim((string) Bootstrap::config('discord.redirectUri', ''));
        $basePath = self::basePath();
        $dynamic = rtrim(Http::origin(), '/') . $basePath . '/api/auth/callback.php';

        if ($configured === '') {
            return $dynamic;
        }

        $configuredPath = (string) parse_url($configured, PHP_URL_PATH);
        if ($configuredPath === '') {
            return $dynamic;
        }

        return rtrim(Http::origin(), '/') . $configuredPath;
    }

    public static function basePath(): string
    {
        $basePath = trim((string) parse_url((string) Bootstrap::config('app.baseUrl', '/discord'), PHP_URL_PATH), '/');
        return '/' . ($basePath !== '' ? $basePath : 'discord');
    }

    public static function defaultReturnTo(string $flow = 'admin'): string
    {
        $path = $flow === 'gacha' ? self::basePath() . '/gacha/' : self::basePath() . '/index.php';
        return rtrim(Http::origin(), '/') . $path;
    }

    public static function sanitizeReturnTo(?string $returnTo, string $flow = 'admin'): string
    {
        $default = self::defaultReturnTo($flow);
        $returnTo = trim((string) $returnTo);
        if ($returnTo === '') {
            return $default;
        }

        if (preg_match('#^https?://#i', $returnTo)) {
            return Http::sameOrigin($returnTo) ? $returnTo : $default;
        }

        if (!str_starts_with($returnTo, '/')) {
            $returnTo = '/' . ltrim($returnTo, '/');
        }

        return rtrim(Http::origin(), '/') . $returnTo;
    }
}
