<?php

declare(strict_types=1);

final class DiscordClient
{
    public function request(string $method, string $path, array $payload = [], ?string $auditReason = null): array
    {
        $token = Bootstrap::config('discord.botToken', '');
        if (!$token) {
            throw new RuntimeException('Discord bot token is not configured.');
        }

        $url = rtrim(Bootstrap::config('discord.apiBase'), '/') . '/' . ltrim($path, '/');
        $headers = [
            'Authorization: Bot ' . $token,
            'Content-Type: application/json',
            'User-Agent: Dekpoke Orbit Console (single-guild production console)',
        ];

        if ($auditReason) {
            $headers[] = 'X-Audit-Log-Reason: ' . rawurlencode($auditReason);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 45,
        ]);

        if (!in_array(strtoupper($method), ['GET', 'DELETE'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException($error);
        }

        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headersText = substr($raw, 0, $headerSize);
        $bodyText = substr($raw, $headerSize);
        curl_close($ch);

        $headers = $this->headers($headersText);
        $body = json_decode($bodyText, true);
        if (isset($headers['x-ratelimit-bucket'])) {
            RateLimitStore::record($method, $path, $headers, $status);
        }

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'headers' => $headers,
            'body' => is_array($body) ? $body : $bodyText,
        ];
    }

    private function headers(string $headersText): array
    {
        $headers = [];
        foreach (explode("\r\n", $headersText) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        return $headers;
    }
}
