<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireAnyPermission(DashboardPermissionService::pagePermissionsFor('messages'));
$id = Input::int('id', 0);
if ($id <= 0) {
    Response::error('Attachment id is required.', 422);
}

$storage = new AttachmentStorageService();
$resolved = $storage->resolveLocalPath($id);
$row = $resolved['row'] ?? $storage->resolveAttachment($id);
if (!$row) {
    Response::error('Attachment not found.', 404);
}

if (!$resolved) {
    try {
        $resolved = $storage->storeOneById($id);
    } catch (Throwable) {
        $resolved = null;
    }
}

AuditLogger::access('attachment_download', 'message_attachment', (string) $id, [
    'messageId' => $row['messageId'],
    'channelId' => $row['channelId'],
    'fileName' => $row['fileName'],
], 'sensitive');

$contentType = $row['contentType'] ?: 'application/octet-stream';
header('Content-Type: ' . $contentType);
header('Content-Disposition: inline; filename="' . addslashes((string) ($row['fileName'] ?: ('attachment-' . $row['attachmentId']))) . '"');

if ($resolved) {
    $path = $resolved['path'];
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

try {
    $body = $storage->fetchRemoteBody($row);
} catch (Throwable $exception) {
    Response::error('Attachment file is not stored or is missing.', 404, ['error' => $exception->getMessage()]);
}

header('Content-Length: ' . strlen($body));
echo $body;
exit;
