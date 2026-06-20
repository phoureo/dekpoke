<?php

declare(strict_types=1);

final class RateLimitStore
{
    public static function record(string $method, string $route, array $headers, int $status): void
    {
        Database::execute(
            'INSERT INTO tbl_api_rate_limit
                (providerName, routeKey, httpMethod, bucketKey, statusCode, remainingCount, resetAfterSeconds, resetDate, metadataJson, updateDate)
             VALUES
                ("discord", :routeKey, :httpMethod, :bucketKey, :statusCode, :remainingCount, :resetAfterSeconds, :resetDate, :metadataJson, :updateDate)
             ON DUPLICATE KEY UPDATE
                bucketKey = VALUES(bucketKey),
                statusCode = VALUES(statusCode),
                remainingCount = VALUES(remainingCount),
                resetAfterSeconds = VALUES(resetAfterSeconds),
                resetDate = VALUES(resetDate),
                metadataJson = VALUES(metadataJson),
                updateDate = VALUES(updateDate)',
            [
                'routeKey' => $route,
                'httpMethod' => strtoupper($method),
                'bucketKey' => $headers['x-ratelimit-bucket'] ?? null,
                'statusCode' => $status,
                'remainingCount' => isset($headers['x-ratelimit-remaining']) ? (int) $headers['x-ratelimit-remaining'] : null,
                'resetAfterSeconds' => isset($headers['x-ratelimit-reset-after']) ? (float) $headers['x-ratelimit-reset-after'] : null,
                'resetDate' => isset($headers['x-ratelimit-reset']) ? date('Y-m-d H:i:s', (int) $headers['x-ratelimit-reset']) : null,
                'metadataJson' => json_encode($headers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );
    }
}
