<?php

declare(strict_types=1);

final class GatewayWorkerService
{
    private ?int $sequence = null;

    public function run(int $maxEvents = 0): array
    {
        $token = (string) Bootstrap::config('discord.botToken', '');
        if ($token === '') {
            throw new RuntimeException('discord.botToken is not configured.');
        }

        $this->enqueueRecoveryProtocol();
        $socket = $this->connect();
        $heartbeatIntervalMs = 45000;
        $nextHeartbeat = 0.0;
        $identified = false;
        $events = 0;
        $startedAt = time();

        while (!feof($socket)) {
            JobRunner::heartbeat('gateway_worker', [
                'events' => $events,
                'sequence' => $this->sequence,
                'uptimeSeconds' => time() - $startedAt,
            ]);

            if (microtime(true) * 1000 >= $nextHeartbeat && $identified) {
                $this->writeJson($socket, ['op' => 1, 'd' => $this->sequence]);
                $nextHeartbeat = microtime(true) * 1000 + $heartbeatIntervalMs;
            }

            $frame = $this->readFrame($socket);
            if ($frame === null) {
                continue;
            }

            $packet = json_decode($frame, true);
            if (!is_array($packet)) {
                continue;
            }

            $op = (int) ($packet['op'] ?? -1);
            if (isset($packet['s'])) {
                $this->sequence = (int) $packet['s'];
            }

            if ($op === 10) {
                $heartbeatIntervalMs = (int) ($packet['d']['heartbeat_interval'] ?? $heartbeatIntervalMs);
                $this->identify($socket, $token);
                $identified = true;
                $nextHeartbeat = microtime(true) * 1000 + $heartbeatIntervalMs;
                continue;
            }

            if ($op === 1) {
                $this->writeJson($socket, ['op' => 1, 'd' => $this->sequence]);
                continue;
            }

            if ($op === 7) {
                throw new RuntimeException('Discord Gateway requested reconnect.');
            }

            if ($op === 9) {
                usleep(random_int(1000, 5000) * 1000);
                $this->identify($socket, $token);
                continue;
            }

            if ($op !== 0) {
                continue;
            }

            $eventType = (string) ($packet['t'] ?? '');
            $payload = is_array($packet['d'] ?? null) ? $packet['d'] : [];
            if (!$this->isConfiguredGuildEvent($eventType, $payload)) {
                continue;
            }

            (new GatewayEventIngestService())->ingest($eventType, $payload, $this->sequence, 'gateway');
            $events++;
            if ($maxEvents > 0 && $events >= $maxEvents) {
                break;
            }
        }

        fclose($socket);
        return ['events' => $events, 'sequence' => $this->sequence];
    }

    private function enqueueRecoveryProtocol(): void
    {
        $payload = [
            'source' => 'gateway_start',
            'reason' => 'Gateway worker started or reconnected; reconcile current state and approved bot-log channels.',
            'bot_log_archive' => [
                'pagesPerChannel' => 2,
            ],
        ];

        JobRunner::enqueueOnceRecent('server_sync', $payload, 10, 180);
        JobRunner::enqueueOnceRecent('backfill_all', $payload, 20, 180);
        LiveUpdateService::markTopic('backfill', ['source' => 'gateway_start', 'status' => 'queued_recovery'], 'worker', 'gateway_worker');
    }

    private function connect()
    {
        $url = parse_url((string) Bootstrap::config('discord.gatewayBase'));
        $host = $url['host'] ?? 'gateway.discord.gg';
        $port = (int) ($url['port'] ?? 443);
        $path = ($url['path'] ?? '/') . (isset($url['query']) ? '?' . $url['query'] : '');
        $socket = stream_socket_client('ssl://' . $host . ':' . $port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            throw new RuntimeException('Gateway connection failed: ' . $errstr);
        }

        stream_set_timeout($socket, 2);
        $key = base64_encode(random_bytes(16));
        $request = "GET {$path} HTTP/1.1\r\n"
            . "Host: {$host}\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: {$key}\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        fwrite($socket, $request);

        $headers = '';
        while (!str_contains($headers, "\r\n\r\n")) {
            $chunk = fgets($socket);
            if ($chunk === false) {
                throw new RuntimeException('Gateway handshake failed.');
            }
            $headers .= $chunk;
        }

        if (!str_contains($headers, ' 101 ')) {
            throw new RuntimeException('Gateway handshake was not accepted.');
        }

        return $socket;
    }

    private function identify($socket, string $token): void
    {
        $this->writeJson($socket, [
            'op' => 2,
            'd' => [
                'token' => $token,
                'intents' => (int) Bootstrap::config('discord.gatewayIntents', 3243775),
                'properties' => [
                    'os' => PHP_OS_FAMILY,
                    'browser' => 'Dekpoke Workspace Console',
                    'device' => 'Dekpoke Workspace Console',
                ],
            ],
        ]);
    }

    private function isConfiguredGuildEvent(string $eventType, array $payload): bool
    {
        if ($eventType === 'READY') {
            return false;
        }

        $guildId = (string) Bootstrap::config('discord.guildId', '');
        $payloadGuildId = (string) ($payload['guild_id'] ?? $payload['id'] ?? '');
        if ($payloadGuildId === '') {
            return true;
        }

        return $payloadGuildId === $guildId;
    }

    private function readFrame($socket): ?string
    {
        $header = $this->readBytes($socket, 2);
        if ($header === null) {
            return null;
        }

        $first = ord($header[0]);
        $second = ord($header[1]);
        $opcode = $first & 0x0f;
        if ($opcode === 8) {
            throw new RuntimeException('Gateway socket closed.');
        }

        $isMasked = ($second & 0x80) === 0x80;
        $length = $second & 0x7f;
        if ($length === 126) {
            $extended = $this->readBytes($socket, 2);
            if ($extended === null) {
                return null;
            }
            $length = unpack('n', $extended)[1];
        } elseif ($length === 127) {
            $extended = $this->readBytes($socket, 8);
            if ($extended === null) {
                return null;
            }
            $parts = unpack('Nhigh/Nlow', $extended);
            $length = $parts['high'] * 4294967296 + $parts['low'];
        }

        $mask = $isMasked ? $this->readBytes($socket, 4) : null;
        $payload = $this->readBytes($socket, (int) $length);
        if ($payload === null) {
            return null;
        }

        if ($mask !== null) {
            $decoded = '';
            for ($i = 0; $i < strlen($payload); $i++) {
                $decoded .= $payload[$i] ^ $mask[$i % 4];
            }
            return $decoded;
        }

        return $payload;
    }

    private function writeJson($socket, array $payload): void
    {
        $data = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($data === false) {
            throw new RuntimeException('Cannot encode gateway payload.');
        }
        fwrite($socket, $this->frame($data));
    }

    private function frame(string $payload): string
    {
        $length = strlen($payload);
        $mask = random_bytes(4);
        if ($length < 126) {
            $header = chr(0x81) . chr(0x80 | $length);
        } elseif ($length <= 65535) {
            $header = chr(0x81) . chr(0x80 | 126) . pack('n', $length);
        } else {
            $header = chr(0x81) . chr(0x80 | 127) . pack('NN', intdiv($length, 4294967296), $length % 4294967296);
        }

        $masked = '';
        for ($i = 0; $i < $length; $i++) {
            $masked .= $payload[$i] ^ $mask[$i % 4];
        }

        return $header . $mask . $masked;
    }

    private function readBytes($socket, int $length): ?string
    {
        if ($length === 0) {
            return '';
        }

        $buffer = '';
        while (strlen($buffer) < $length) {
            $chunk = @fread($socket, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($socket);
                if (!empty($meta['timed_out'])) {
                    return null;
                }
                if (feof($socket)) {
                    throw new RuntimeException('Gateway socket reached EOF.');
                }
                throw new RuntimeException('Gateway socket read failed; reconnect required.');
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }
}
