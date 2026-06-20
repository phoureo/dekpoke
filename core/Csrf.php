<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrfToken'])) {
            $_SESSION['csrfToken'] = bin2hex(random_bytes((int) Bootstrap::config('security.csrfTokenBytes', 32)));
        }

        return $_SESSION['csrfToken'];
    }

    public static function assertValid(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
        if (!is_string($token) || !hash_equals(self::token(), $token)) {
            AuditLogger::reject('csrf_reject', 'CSRF validation failed.', 'security', 'csrf', AuditLogger::requestPayload());
            Response::error('CSRF validation failed.', 419);
        }
    }
}
