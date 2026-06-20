<?php

declare(strict_types=1);

final class Response
{
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($json === false) {
            http_response_code(500);
            $json = json_encode(
                [
                    'ok' => false,
                    'message' => 'Failed to encode JSON response.',
                    'context' => ['jsonError' => json_last_error_msg()],
                ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            ) ?: '{"ok":false,"message":"Failed to encode JSON response.","context":{"jsonError":"unknown"}}';
        }

        echo $json;
        exit;
    }

    public static function error(string $message, int $status = 400, array $context = []): never
    {
        self::json(['ok' => false, 'message' => $message, 'context' => $context], $status);
    }
}
