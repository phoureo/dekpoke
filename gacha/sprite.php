<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

const SPRITE_MAX_UPLOAD_BYTES = 26214400;
const SPRITE_MAX_OUTPUT_PIXELS = 48000000;
const SPRITE_MANIFEST_RELATIVE = 'gacha/images/uploads/sprites/manifest.json';

function sprite_int(string $key, int $default, int $min, int $max): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    if ($value === false || $value === null) {
        $value = $default;
    }
    return max($min, min($max, (int) $value));
}

function sprite_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return trim(is_string($value) ? $value : $default);
}

function sprite_canvas(int $width, int $height): GdImage
{
    $image = imagecreatetruecolor($width, $height);
    if (!$image instanceof GdImage) {
        Response::error('สร้าง canvas สำหรับส่งออกไม่ได้', 500);
    }
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefilledrectangle($image, 0, 0, $width, $height, $transparent);
    return $image;
}

function sprite_export_allowed(): bool
{
    $ip = trim(Http::clientIp());
    if (
        $ip === '127.0.0.1'
        || $ip === '::1'
        || $ip === '::ffff:127.0.0.1'
        || str_starts_with($ip, '192.168.')
        || str_starts_with($ip, '10.')
    ) {
        return true;
    }

    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
    $host = trim($host, '[]');
    $host = preg_replace('/:\d+$/', '', $host) ?: '';
    if (
        in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        || str_starts_with($host, '192.168.')
        || str_starts_with($host, '10.')
    ) {
        return true;
    }

    return Auth::can('gacha.manage');
}

function sprite_require_export_allowed(): void
{
    if (!sprite_export_allowed()) {
        Response::error('ไม่มีสิทธิ์ส่งออก sprite', 403);
    }
}

function sprite_load_image(string $path, string $mime): GdImage
{
    $image = match ($mime) {
        'image/png' => imagecreatefrompng($path),
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
        default => false,
    };

    if (!$image instanceof GdImage) {
        Response::error('อ่านรูปนี้ไม่ได้ กรุณาใช้ PNG, JPG หรือ WebP', 422);
    }

    if (!imageistruecolor($image)) {
        imagepalettetotruecolor($image);
    }
    imagealphablending($image, false);
    imagesavealpha($image, true);

    return $image;
}

function sprite_upload_meta(): array
{
    if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
        Response::error('กรุณาเลือกรูปก่อน', 422);
    }

    $file = $_FILES['image'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        Response::error('อัปโหลดไม่สำเร็จ', 422, ['error' => $file['error'] ?? null]);
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        Response::error('ไฟล์อัปโหลดไม่ถูกต้อง', 422);
    }
    if ($size <= 0 || $size > SPRITE_MAX_UPLOAD_BYTES) {
        Response::error('รูปต้องมีขนาดน้อยกว่า 25MB', 422);
    }

    $mime = mime_content_type($tmp) ?: '';
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        Response::error('รองรับเฉพาะ PNG, JPG และ WebP', 422, ['mime' => $mime]);
    }

    return [$tmp, $mime, $allowed[$mime]];
}

function sprite_frame_bounds(GdImage $source, int $frameWidth, int $frameHeight, int $columns, int $frameCount, int $alphaThreshold, int $padding): array
{
    $minX = $frameWidth;
    $minY = $frameHeight;
    $maxX = -1;
    $maxY = -1;

    for ($frame = 0; $frame < $frameCount; $frame++) {
        $sourceX = ($frame % $columns) * $frameWidth;
        $sourceY = intdiv($frame, $columns) * $frameHeight;
        for ($y = 0; $y < $frameHeight; $y++) {
            for ($x = 0; $x < $frameWidth; $x++) {
                $rgba = imagecolorat($source, $sourceX + $x, $sourceY + $y);
                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha >= $alphaThreshold) {
                    continue;
                }
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }
    }

    if ($maxX < $minX || $maxY < $minY) {
        return ['x' => 0, 'y' => 0, 'width' => $frameWidth, 'height' => $frameHeight];
    }

    $minX = max(0, $minX - $padding);
    $minY = max(0, $minY - $padding);
    $maxX = min($frameWidth - 1, $maxX + $padding);
    $maxY = min($frameHeight - 1, $maxY + $padding);

    return [
        'x' => $minX,
        'y' => $minY,
        'width' => ($maxX - $minX) + 1,
        'height' => ($maxY - $minY) + 1,
    ];
}

function sprite_manifest_path(): string
{
    return Bootstrap::rootPath(SPRITE_MANIFEST_RELATIVE);
}

function sprite_manifest_read(): array
{
    $path = sprite_manifest_path();
    if (!is_file($path)) {
        return ['version' => 1, 'assets' => []];
    }
    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        return ['version' => 1, 'assets' => []];
    }
    $decoded['assets'] = is_array($decoded['assets'] ?? null) ? $decoded['assets'] : [];
    $decoded['version'] = 1;
    return $decoded;
}

function sprite_manifest_write_asset(string $publicPath, array $metadata): void
{
    $path = sprite_manifest_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        Response::error('สร้าง manifest sprite ไม่ได้', 500, ['path' => $dir]);
    }
    @chmod($dir, 0777);
    $manifest = sprite_manifest_read();
    $manifest['assets'][$publicPath] = $metadata + [
        'src' => $publicPath,
        'updatedAt' => date(DateTimeInterface::ATOM),
    ];
    $json = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
        Response::error('บันทึก manifest sprite ไม่ได้', 500, ['path' => $path]);
    }
    @chmod($path, 0666);
}

function sprite_export(): never
{
    Csrf::assertValid();
    sprite_require_export_allowed();

    [$tmp, $mime] = sprite_upload_meta();
    $source = sprite_load_image($tmp, $mime);
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    if (($sourceWidth * $sourceHeight) > SPRITE_MAX_OUTPUT_PIXELS) {
        Response::error('รูปต้นฉบับใหญ่เกินไป กรุณาลดขนาดรูปก่อนประมวลผล', 422, [
            'width' => $sourceWidth,
            'height' => $sourceHeight,
        ]);
    }

    $columns = sprite_int('columns', 1, 1, 512);
    $rows = sprite_int('rows', 1, 1, 512);
    $maxFrames = max(1, $columns * $rows);
    $frameCount = sprite_int('frameCount', $maxFrames, 1, $maxFrames);
    $frameWidth = intdiv($sourceWidth, $columns);
    $frameHeight = intdiv($sourceHeight, $rows);

    if ($frameWidth < 1 || $frameHeight < 1) {
        Response::error('ตารางใหญ่กว่าขนาดรูป', 422);
    }

    $cropMode = sprite_string('cropMode', 'manual');
    $alphaThreshold = sprite_int('alphaThreshold', 120, 1, 127);
    $padding = sprite_int('padding', 0, 0, 512);

    if ($cropMode === 'auto-alpha') {
        $crop = sprite_frame_bounds($source, $frameWidth, $frameHeight, $columns, $frameCount, $alphaThreshold, $padding);
    } else {
        $cropX = sprite_int('cropX', 0, 0, max(0, $frameWidth - 1));
        $cropY = sprite_int('cropY', 0, 0, max(0, $frameHeight - 1));
        $cropWidth = sprite_int('cropWidth', $frameWidth - $cropX, 1, max(1, $frameWidth - $cropX));
        $cropHeight = sprite_int('cropHeight', $frameHeight - $cropY, 1, max(1, $frameHeight - $cropY));
        $crop = ['x' => $cropX, 'y' => $cropY, 'width' => $cropWidth, 'height' => $cropHeight];
    }

    $exportMode = sprite_string('exportMode', 'sheet');
    if (!in_array($exportMode, ['sheet', 'single'], true)) {
        $exportMode = 'sheet';
    }
    $selectedFrame = sprite_int('selectedFrame', 0, 0, max(0, $frameCount - 1));
    $exportFrameCount = $exportMode === 'single' ? 1 : $frameCount;
    $outputColumns = $exportMode === 'single' ? 1 : sprite_int('outputColumns', $frameCount, 1, $frameCount);
    $outputRows = (int) ceil($frameCount / $outputColumns);
    if ($exportMode === 'single') {
        $outputRows = 1;
    }
    $outputWidth = $crop['width'] * $outputColumns;
    $outputHeight = $crop['height'] * $outputRows;

    if (($outputWidth * $outputHeight) > SPRITE_MAX_OUTPUT_PIXELS) {
        Response::error('ไฟล์ขาออกใหญ่เกินไป กรุณาลดจำนวนเฟรม ขนาดกรอบตัด หรือคอลัมน์ขาออก', 422, [
            'width' => $outputWidth,
            'height' => $outputHeight,
        ]);
    }

    $mode = sprite_string('mode', 'loop');
    if (!in_array($mode, ['loop', 'once', 'pingpong'], true)) {
        $mode = 'loop';
    }
    $fps = max(1, min(60, (float) ($_POST['fps'] ?? 12)));
    $displayWidth = sprite_int('displayWidth', $crop['width'], 1, 4096);
    $displayHeight = sprite_int('displayHeight', $crop['height'], 1, 4096);

    $output = sprite_canvas($outputWidth, $outputHeight);
    for ($frame = 0; $frame < $exportFrameCount; $frame++) {
        $sourceFrame = $exportMode === 'single' ? $selectedFrame : $frame;
        $sourceX = (($sourceFrame % $columns) * $frameWidth) + $crop['x'];
        $sourceY = (intdiv($sourceFrame, $columns) * $frameHeight) + $crop['y'];
        $targetX = ($frame % $outputColumns) * $crop['width'];
        $targetY = intdiv($frame, $outputColumns) * $crop['height'];
        imagecopy($output, $source, $targetX, $targetY, $sourceX, $sourceY, $crop['width'], $crop['height']);
    }

    $uploadDir = Bootstrap::rootPath('gacha/images/uploads/sprites');
    $uploadRoot = dirname($uploadDir);
    if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0777, true) && !is_dir($uploadRoot)) {
        Response::error('สร้างโฟลเดอร์อัปโหลดไม่ได้', 500, ['path' => $uploadRoot]);
    }
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        Response::error('สร้างโฟลเดอร์ sprite ไม่ได้', 500, ['path' => $uploadDir]);
    }
    @chmod($uploadRoot, 0777);
    @chmod($uploadDir, 0777);
    if (!is_writable($uploadDir)) {
        Response::error('โฟลเดอร์ sprite เขียนไฟล์ไม่ได้', 500, ['path' => $uploadDir]);
    }

    $namePrefix = $exportMode === 'single' ? 'sprite_frame_' : 'sprite_';
    $name = $namePrefix . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.png';
    $target = $uploadDir . DIRECTORY_SEPARATOR . $name;
    if (!imagepng($output, $target, 6)) {
        Response::error('บันทึกไฟล์ sprite PNG ไม่ได้', 500);
    }
    @chmod($target, 0666);

    imagedestroy($source);
    imagedestroy($output);

    $publicPath = 'images/uploads/sprites/' . $name;
    try {
        AuditLogger::access('gacha_sprite_export', 'file', $publicPath);
    } catch (Throwable) {
    }

    $baseUrl = rtrim((string) Bootstrap::config('app.baseUrl', ''), '/');
    $url = $baseUrl !== '' ? $baseUrl . '/gacha/' . $publicPath : $publicPath;
    $mileageConfig = [
        'src' => $publicPath,
        'columns' => $outputColumns,
        'rows' => $outputRows,
        'frameWidth' => $crop['width'],
        'frameHeight' => $crop['height'],
        'frameCount' => $exportFrameCount,
        'fps' => $fps,
        'mode' => $exportMode === 'single' ? 'static' : $mode,
        'width' => $displayWidth,
        'height' => $displayHeight,
    ];
    $assetMeta = $mileageConfig + [
        'type' => $exportMode === 'single' ? 'single-frame' : 'sprite-sheet',
        'exportMode' => $exportMode,
        'selectedFrame' => $selectedFrame,
        'sheetWidth' => $outputWidth,
        'sheetHeight' => $outputHeight,
        'sourceColumns' => $columns,
        'sourceRows' => $rows,
        'sourceFrameCount' => $frameCount,
    ];
    sprite_manifest_write_asset($publicPath, $assetMeta);

    Response::json([
        'ok' => true,
        'exportMode' => $exportMode,
        'path' => $publicPath,
        'url' => $url,
        'width' => $displayWidth,
        'height' => $displayHeight,
        'sheetWidth' => $outputWidth,
        'sheetHeight' => $outputHeight,
        'columns' => $outputColumns,
        'rows' => $outputRows,
        'frameWidth' => $crop['width'],
        'frameHeight' => $crop['height'],
        'frameCount' => $exportFrameCount,
        'fps' => $fps,
        'mode' => $exportMode === 'single' ? 'static' : $mode,
        'crop' => $crop,
        'mileageConfig' => $mileageConfig,
        'assetMeta' => $assetMeta,
        'message' => $exportMode === 'single' ? 'ส่งออกเฟรมเดียวแล้ว' : 'ส่งออก sprite sheet แล้ว',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'export') {
    sprite_export();
}

$csrfToken = Csrf::token();
$canExport = sprite_export_allowed();
$assetVersion = (string) (@filemtime(__DIR__ . '/assets/js/sprite-editor.js') ?: time());
$cssVersion = (string) (@filemtime(__DIR__ . '/assets/css/editor.css') ?: time());
?>
<!doctype html>
<html lang="th" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>ตัวแก้ไข Sprite Sheet</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/editor.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8') ?>">
  <style>
    .sprite-editor-shell {}
    .sprite-stage-empty {
      position: absolute;
      inset: 18px;
      display: grid;
      place-items: center;
      pointer-events: none;
      color: var(--editor-muted);
      border: 1px dashed rgba(238, 243, 251, 0.16);
      border-radius: 8px;
    }
    .sprite-drop-zone {
      min-height: 108px;
      display: grid;
      place-items: center;
      gap: 6px;
      border: 1px dashed rgba(142, 161, 255, 0.42);
      border-radius: 8px;
      background: rgba(8, 12, 20, 0.62);
      cursor: pointer;
      text-align: center;
    }
    .sprite-drop-zone.is-dragover {
      border-color: var(--editor-accent);
      background: rgba(142, 161, 255, 0.12);
    }
    .sprite-preview-canvas {
      width: 100%;
      aspect-ratio: 1;
      display: block;
      border: 1px solid rgba(238, 243, 251, 0.12);
      border-radius: 8px;
      background: rgba(5, 10, 18, 0.86);
    }
    .sprite-result-preview {
      min-height: 98px;
      display: grid;
      place-items: center;
      overflow: auto;
      border: 1px solid rgba(238, 243, 251, 0.12);
      border-radius: 8px;
      background: rgba(5, 10, 18, 0.7);
    }
    .sprite-result-preview img {
      max-width: 100%;
      image-rendering: pixelated;
    }
    .sprite-anchor-picker {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-top: 10px;
    }
    .sprite-anchor-label {
      min-width: 44px;
      padding-top: 6px;
      color: rgba(211, 220, 236, 0.82);
      font-size: 12px;
      font-weight: 700;
    }
    .sprite-anchor-grid {
      display: grid;
      grid-template-columns: repeat(3, 40px);
      border: 1px solid rgba(238, 243, 251, 0.16);
      background: rgba(5, 10, 18, 0.7);
    }
    .sprite-anchor-button {
      width: 40px;
      height: 40px;
      border: 0;
      border-right: 1px solid rgba(238, 243, 251, 0.16);
      border-bottom: 1px solid rgba(238, 243, 251, 0.16);
      background: transparent;
      color: rgba(238, 243, 251, 0.72);
      font-size: 18px;
      cursor: pointer;
      transition: background 0.16s ease, color 0.16s ease;
    }
    .sprite-anchor-button:nth-child(3n) {
      border-right: 0;
    }
    .sprite-anchor-button:nth-last-child(-n + 3) {
      border-bottom: 0;
    }
    .sprite-anchor-button:hover {
      background: rgba(142, 161, 255, 0.14);
      color: #f8fbff;
    }
    .sprite-anchor-button.is-active {
      background: rgba(142, 161, 255, 0.22);
      color: #f8fbff;
    }
  </style>
</head>
<body class="editor-body">
  <div class="editor-shell sprite-editor-shell is-three-column" id="spriteEditorRoot">
    <header class="editor-topbar">
      <div class="editor-brand"><i class="fa-solid fa-wand-magic-sparkles"></i><span>ตัวแก้ไข Sprite Sheet</span></div>
      <span class="editor-pill" id="spriteStatusPill">กำลังโหลด</span>
      <span class="editor-pill">เครื่องมือ <strong id="activeSpriteToolLabel">เลือก</strong></span>
      <div class="editor-topbar-spacer"></div>
      <button class="ui tiny button" id="copySpritePathButton" type="button" data-tip="คัดลอก path ไฟล์ที่ส่งออก"><i class="fa-solid fa-link"></i> คัดลอก Path</button>
      <button class="ui tiny button" id="copyMileageConfigButton" type="button" data-tip="คัดลอก config ที่นำไปใช้ใน Mileage ได้ทันที"><i class="fa-solid fa-copy"></i> คัดลอก Config</button>
      <button class="ui tiny button" id="copySingleFrameConfigButton" type="button" data-tip="ส่งออกเฟรมที่เลือกเป็น PNG เดี่ยวแล้วคัดลอก config"><i class="fa-regular fa-square"></i> Config เฟรมเดียว</button>
      <button class="ui tiny button" id="sendMileageConfigButton" type="button" data-tip="ส่ง config ล่าสุดไปให้หน้า Mileage ใช้ตอนวาง sprite"><i class="fa-solid fa-paper-plane"></i> ส่งไป Mileage</button>
      <button class="ui tiny button" id="sendSingleFrameConfigButton" type="button" data-tip="ส่งออกเฟรมที่เลือกเป็น PNG เดี่ยวแล้วส่ง config ไป Mileage"><i class="fa-solid fa-paper-plane"></i> ส่งเฟรมเดียว</button>
      <button class="ui tiny primary button" id="spriteExportButton" type="button" data-tip="ตัดและส่งออกเป็น sprite sheet ใหม่"><i class="fa-solid fa-file-export"></i> ส่งออก</button>
    </header>

    <aside class="editor-toolbar" aria-label="เครื่องมือ sprite">
      <button class="ui icon button is-active" type="button" data-sprite-tool="select" data-short="V" data-tip="เลือกเฟรมและขยับกรอบตัด"><i class="fa-solid fa-arrow-pointer"></i></button>
      <button class="ui icon button" type="button" data-sprite-tool="pan" data-short="H" data-tip="เลื่อนมุมมอง กด Space ค้างก็ใช้ได้"><i class="fa-solid fa-hand"></i></button>
      <button class="ui icon button" type="button" id="spriteFitButton" data-short="F" data-tip="ปรับมุมมองให้เห็นรูปทั้งหมด"><i class="fa-solid fa-expand"></i></button>
      <button class="ui icon button" type="button" id="spriteResetCropButton" data-short="C" data-tip="รีเซ็ตกรอบตัดเท่าขนาดเฟรม"><i class="fa-solid fa-crop-simple"></i></button>
      <button class="ui icon button" type="button" id="spriteAutoTrimButton" data-short="T" data-tip="ตัดขอบโปร่งใสจากทุกเฟรมแบบใช้กรอบเดียวกัน"><i class="fa-solid fa-scissors"></i></button>
      <button class="ui icon button" type="button" id="spritePlayButton" data-short="P" data-tip="เล่น/หยุด preview animation"><i class="fa-solid fa-pause"></i></button>
    </aside>

    <main class="editor-workspace">
      <div id="spriteStage" class="editor-stage" aria-label="Sprite sheet canvas">
        <div class="sprite-stage-empty"><i class="fa-regular fa-image"></i></div>
      </div>
    </main>

    <aside class="editor-inspector">
      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-upload"></i> ไฟล์ต้นฉบับ</span></div>
        <div class="editor-panel-body">
          <input id="spriteFileInput" class="editor-hidden" type="file" accept="image/png,image/jpeg,image/webp">
          <div id="spriteDropZone" class="sprite-drop-zone">
            <div><i class="fa-regular fa-image"></i></div>
            <strong>ลากไฟล์มาวางหรือเลือก Sprite Sheet</strong>
            <span class="editor-muted">PNG, JPG, WebP</span>
          </div>
          <div class="editor-actions" style="margin-top:8px">
            <button class="ui tiny button" id="spriteAssetButton" type="button" data-tip="เลือกไฟล์รูปจาก uploads หรือ images ที่ระบบอนุญาต"><i class="fa-solid fa-folder-open"></i> เลือกจากคลัง</button>
          </div>
          <div class="editor-asset-toolbar" style="margin-top:8px">
            <label class="editor-field">
              <input id="spriteAssetSearchInput" type="search" placeholder="ค้นหาชื่อไฟล์ / path">
            </label>
            <div class="editor-mode-tabs" aria-label="รูปแบบการดูคลังไฟล์">
              <button class="ui mini icon button is-active" type="button" data-sprite-asset-view="grid" data-tip="ดูแบบตารางรูป"><i class="fa-solid fa-grip"></i></button>
              <button class="ui mini icon button" type="button" data-sprite-asset-view="list" data-tip="ดูแบบรายการ"><i class="fa-solid fa-list"></i></button>
            </div>
          </div>
          <div id="spriteAssetPreview" class="editor-asset-preview"><div class="editor-note">คลิกไฟล์เพื่อดูตัวอย่าง แล้วกดใช้รูปนี้เมื่อต้องการโหลดเข้า editor</div></div>
          <div id="spriteAssetList" class="editor-list editor-asset-list is-grid"></div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-table-cells"></i> ตารางเฟรม</span><span id="spriteFrameSizeLabel">-</span></div>
        <div class="editor-panel-body">
          <div class="editor-grid-2">
            <label class="editor-field">คอลัมน์ <input id="spriteColumnsInput" type="number" min="1" max="512" step="1" value="1"></label>
            <label class="editor-field">แถว <input id="spriteRowsInput" type="number" min="1" max="512" step="1" value="1"></label>
            <label class="editor-field">จำนวนเฟรม <input id="spriteFrameCountInput" type="number" min="1" max="512" step="1" value="1"></label>
            <label class="editor-field">คอลัมน์ขาออก <input id="spriteOutputColumnsInput" type="number" min="1" max="512" step="1" value="1"></label>
            <label class="editor-field">เฟรมที่ดู <input id="spriteSelectedFrameInput" type="number" min="0" step="1" value="0"></label>
            <label class="editor-field">ซูม <span id="spriteZoomLabel">100%</span></label>
          </div>
          <div class="editor-actions">
            <button class="ui tiny button" id="spriteAutoGridButton" type="button" data-tip="คำนวณ layout ขาออกให้ไม่ยาวเป็นแถวเดียวถ้าแบ่งลงตัว"><i class="fa-solid fa-border-all"></i> Auto Grid</button>
          </div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-crop-simple"></i> กรอบตัด</span><span id="spriteCropSizeLabel">-</span></div>
        <div class="editor-panel-body">
          <div class="editor-grid-2">
            <label class="editor-field">X <input id="spriteCropXInput" type="number" min="0" step="1" value="0"></label>
            <label class="editor-field">Y <input id="spriteCropYInput" type="number" min="0" step="1" value="0"></label>
            <label class="editor-field">W <input id="spriteCropWidthInput" type="number" min="1" step="1" value="1"></label>
            <label class="editor-field">H <input id="spriteCropHeightInput" type="number" min="1" step="1" value="1"></label>
            <label class="editor-field">Alpha <input id="spriteAlphaThresholdInput" type="number" min="1" max="127" step="1" value="120"></label>
            <label class="editor-field">Padding <input id="spritePaddingInput" type="number" min="0" max="512" step="1" value="0"></label>
          </div>
          <div class="sprite-anchor-picker">
            <div class="sprite-anchor-label">จุดยึด</div>
            <div class="sprite-anchor-grid" id="spriteCropAnchorGrid" aria-label="เลือกจุดยึดกรอบตัด">
              <button class="sprite-anchor-button" type="button" data-crop-anchor="top-left" data-tip="ยึดมุมบนซ้าย">↖</button>
              <button class="sprite-anchor-button" type="button" data-crop-anchor="top-center" data-tip="ยึดกึ่งกลางด้านบน">↑</button>
              <button class="sprite-anchor-button" type="button" data-crop-anchor="top-right" data-tip="ยึดมุมบนขวา">↗</button>
              <button class="sprite-anchor-button" type="button" data-crop-anchor="middle-left" data-tip="ยึดกึ่งกลางด้านซ้าย">←</button>
              <button class="sprite-anchor-button is-active" type="button" data-crop-anchor="center" data-tip="ยึดกึ่งกลาง">●</button>
              <button class="sprite-anchor-button" type="button" data-crop-anchor="middle-right" data-tip="ยึดกึ่งกลางด้านขวา">→</button>
              <button class="sprite-anchor-button" type="button" data-crop-anchor="bottom-left" data-tip="ยึดมุมล่างซ้าย">↙</button>
              <button class="sprite-anchor-button" type="button" data-crop-anchor="bottom-center" data-tip="ยึดกึ่งกลางด้านล่าง">↓</button>
              <button class="sprite-anchor-button" type="button" data-crop-anchor="bottom-right" data-tip="ยึดมุมล่างขวา">↘</button>
            </div>
          </div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-play"></i> Config สำหรับ Mileage</span></div>
        <div class="editor-panel-body">
          <div class="editor-grid-2">
            <label class="editor-field">FPS <input id="spriteFpsInput" type="number" min="1" max="60" step="1" value="12"></label>
            <label class="editor-field">ค่าเริ่มต้นแอนิเมชัน
              <select id="spriteModeInput">
                <option value="loop">loop</option>
                <option value="once">once</option>
                <option value="pingpong">pingpong</option>
              </select>
            </label>
            <label class="editor-field">Show W <input id="spriteDisplayWidthInput" type="number" min="1" max="4096" step="1" value="48"></label>
            <label class="editor-field">Show H <input id="spriteDisplayHeightInput" type="number" min="1" max="4096" step="1" value="48"></label>
          </div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-regular fa-eye"></i> พรีวิว</span><span id="spriteResultLabel">-</span></div>
        <div class="editor-panel-body">
          <label class="editor-field">สีพื้นหลังพรีวิว
            <select id="spritePreviewBgInput">
              <option value="dark">เข้ม</option>
              <option value="transparent">โปร่งใส</option>
              <option value="white">ขาว</option>
              <option value="black">ดำ</option>
              <option value="checker">ตาราง</option>
            </select>
          </label>
          <canvas id="animationPreviewCanvas" class="sprite-preview-canvas"></canvas>
          <div class="editor-muted" style="margin-top:8px">พรีวิวผลลัพธ์ที่ส่งออก</div>
          <canvas id="spriteResultAnimationCanvas" class="sprite-preview-canvas"></canvas>
          <div id="spriteResultPreview" class="sprite-result-preview" style="margin-top:8px"></div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-code"></i> ผลลัพธ์</span></div>
        <div class="editor-panel-body">
          <label class="editor-field">Path <input id="spritePathOutput" type="text" readonly></label>
          <textarea id="spriteConfigOutput" class="editor-textarea" readonly spellcheck="false"></textarea>
        </div>
      </section>
    </aside>

    <footer class="editor-statusbar">
      <span>ลากกรอบเส้นประเพื่อขยับ หรือลากมุม/ขอบเพื่อปรับขนาด</span>
      <span>คลิกเฟรมเพื่อดูเฟรมนั้น กด Space ค้างเพื่อแพน, กด Shift เพื่อล็อกสัดส่วน, กด Ctrl/Cmd ค้างเพื่อย่อจากกึ่งกลาง และใช้จุดยึด 3x3 ตอนพิมพ์ W/H ให้ละเอียดขึ้น</span>
    </footer>
  </div>

  <script>
    window.SPRITE_EDITOR_BOOT = {
      csrfToken: <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES) ?>,
      canExport: <?= $canExport ? 'true' : 'false' ?>
    };
  </script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
  <script src="vendor/konva/konva.min.js"></script>
  <script src="assets/js/editor-common.js?v=<?= htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="assets/js/sprite-editor.js?v=<?= htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
