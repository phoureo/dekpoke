<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
Auth::requirePermission('admin.view');

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    Response::error('image is required.', 422);
}

$file = $_FILES['image'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    Response::error('upload failed.', 422, ['error' => $file['error'] ?? null]);
}

$tmp = (string) ($file['tmp_name'] ?? '');
$size = (int) ($file['size'] ?? 0);
if ($tmp === '' || !is_uploaded_file($tmp)) {
    Response::error('invalid upload.', 422);
}
if ($size <= 0 || $size > 3 * 1024 * 1024) {
    Response::error('image must be less than 3MB.', 422);
}

$mime = mime_content_type($tmp) ?: '';
$extensions = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];
if (!isset($extensions[$mime])) {
    Response::error('only png, jpg, webp, and gif are allowed.', 422, ['mime' => $mime]);
}

$uploadDir = Bootstrap::rootPath('gacha/uploads/prizes');
$uploadRoot = dirname($uploadDir);
if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0777, true) && !is_dir($uploadRoot)) {
    Response::error('cannot create upload directory.', 500, ['path' => $uploadRoot]);
}
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
    Response::error('cannot create upload directory.', 500, ['path' => $uploadDir]);
}
@chmod($uploadRoot, 0777);
@chmod($uploadDir, 0777);
clearstatcache(true, $uploadRoot);
clearstatcache(true, $uploadDir);
if (!is_writable($uploadDir)) {
    Response::error('upload directory is not writable.', 500, [
        'path' => $uploadDir,
        'permissions' => substr(sprintf('%o', fileperms($uploadDir) ?: 0), -4),
    ]);
}

$name = 'role_series_icon_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $extensions[$mime];
$target = $uploadDir . DIRECTORY_SEPARATOR . $name;
if (!move_uploaded_file($tmp, $target)) {
    Response::error('cannot move uploaded file.', 500);
}
@chmod($target, 0666);

$publicPath = 'uploads/prizes/' . $name;
AuditLogger::access('role_catalog_upload', 'file', $publicPath);
try {
    PublicPageCacheService::clear('gacha-shop');
    PublicPageCacheService::clear('gacha-prizes');
} catch (Throwable) {
    // Cache invalidation must never block a successful upload.
}

Response::json([
    'ok' => true,
    'path' => $publicPath,
    'url' => Bootstrap::config('app.baseUrl', '/discord') . '/gacha/' . $publicPath,
    'kind' => 'image',
]);
