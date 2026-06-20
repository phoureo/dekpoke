<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Csrf::assertValid();
Auth::requirePermission('gacha.manage');

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    Response::error('file is required.', 422);
}

$usage = trim((string) ($_POST['usage'] ?? ''));
$isRoleSeriesBanner = $usage === 'shop-role-series-banner';
$maxBytes = $isRoleSeriesBanner ? 25 * 1024 * 1024 : 3 * 1024 * 1024;
$allowedKinds = $isRoleSeriesBanner ? ['image', 'video'] : ['image'];
$mimeMap = [
    'image/png' => ['extension' => 'png', 'kind' => 'image'],
    'image/jpeg' => ['extension' => 'jpg', 'kind' => 'image'],
    'image/webp' => ['extension' => 'webp', 'kind' => 'image'],
    'image/gif' => ['extension' => 'gif', 'kind' => 'image'],
    'video/mp4' => ['extension' => 'mp4', 'kind' => 'video'],
    'video/webm' => ['extension' => 'webm', 'kind' => 'video'],
    'video/quicktime' => ['extension' => 'mov', 'kind' => 'video'],
    'video/x-m4v' => ['extension' => 'm4v', 'kind' => 'video'],
];
$extensionMap = [
    'png' => ['extension' => 'png', 'kind' => 'image'],
    'jpg' => ['extension' => 'jpg', 'kind' => 'image'],
    'jpeg' => ['extension' => 'jpg', 'kind' => 'image'],
    'webp' => ['extension' => 'webp', 'kind' => 'image'],
    'gif' => ['extension' => 'gif', 'kind' => 'image'],
    'mp4' => ['extension' => 'mp4', 'kind' => 'video'],
    'webm' => ['extension' => 'webm', 'kind' => 'video'],
    'mov' => ['extension' => 'mov', 'kind' => 'video'],
    'm4v' => ['extension' => 'm4v', 'kind' => 'video'],
];

$file = $_FILES['image'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    Response::error('upload failed.', 422, ['error' => $file['error'] ?? null]);
}

$tmp = (string) ($file['tmp_name'] ?? '');
$size = (int) ($file['size'] ?? 0);
$originalName = (string) ($file['name'] ?? '');
$originalExtension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
if ($tmp === '' || !is_uploaded_file($tmp)) {
    Response::error('invalid upload.', 422);
}
if ($size <= 0 || $size > $maxBytes) {
    $limitLabel = $isRoleSeriesBanner ? '25MB' : '3MB';
    $subjectLabel = $isRoleSeriesBanner ? 'media' : 'image';
    Response::error($subjectLabel . ' must be less than ' . $limitLabel . '.', 422);
}

$mime = mime_content_type($tmp) ?: '';
$detected = $mimeMap[$mime] ?? null;
if ($detected === null && isset($extensionMap[$originalExtension])) {
    $detected = $extensionMap[$originalExtension];
}
if ($detected === null || !in_array($detected['kind'], $allowedKinds, true)) {
    $allowedLabel = $isRoleSeriesBanner
        ? 'only png, jpg, webp, gif, mp4, webm, mov, and m4v are allowed.'
        : 'only png, jpg, webp, and gif are allowed.';
    Response::error($allowedLabel, 422, ['mime' => $mime, 'extension' => $originalExtension]);
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

$prefix = $isRoleSeriesBanner ? 'series_banner_' : 'prize_';
$name = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $detected['extension'];
$target = $uploadDir . DIRECTORY_SEPARATOR . $name;
if (!move_uploaded_file($tmp, $target)) {
    Response::error('cannot move uploaded file.', 500);
}
@chmod($target, 0666);

$publicPath = 'uploads/prizes/' . $name;
AuditLogger::access('gacha_prize_upload', 'file', $publicPath);
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
    'kind' => $detected['kind'],
    'mime' => $mime,
    'usage' => $usage,
]);
