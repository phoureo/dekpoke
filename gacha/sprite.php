<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

const SPRITE_MAX_UPLOAD_BYTES = 26214400;
const SPRITE_MAX_OUTPUT_PIXELS = 48000000;

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
        Response::error('Cannot create output canvas.', 500);
    }
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefilledrectangle($image, 0, 0, $width, $height, $transparent);
    return $image;
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
        Response::error('Cannot read this image. Please use PNG, JPG, or WebP.', 422);
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
        Response::error('Image file is required.', 422);
    }

    $file = $_FILES['image'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        Response::error('Upload failed.', 422, ['error' => $file['error'] ?? null]);
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        Response::error('Invalid upload.', 422);
    }
    if ($size <= 0 || $size > SPRITE_MAX_UPLOAD_BYTES) {
        Response::error('Image must be less than 25MB.', 422);
    }

    $mime = mime_content_type($tmp) ?: '';
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        Response::error('Only PNG, JPG, and WebP are allowed.', 422, ['mime' => $mime]);
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

function sprite_export(): never
{
    Csrf::assertValid();
    Auth::requirePermission('gacha.manage');

    [$tmp, $mime] = sprite_upload_meta();
    $source = sprite_load_image($tmp, $mime);
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    if (($sourceWidth * $sourceHeight) > SPRITE_MAX_OUTPUT_PIXELS) {
        Response::error('Source image is too large. Reduce image dimensions before processing.', 422, [
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
        Response::error('Grid is bigger than the image.', 422);
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

    $outputColumns = sprite_int('outputColumns', $frameCount, 1, $frameCount);
    $outputRows = (int) ceil($frameCount / $outputColumns);
    $outputWidth = $crop['width'] * $outputColumns;
    $outputHeight = $crop['height'] * $outputRows;

    if (($outputWidth * $outputHeight) > SPRITE_MAX_OUTPUT_PIXELS) {
        Response::error('Output is too large. Reduce frame count, crop size, or output columns.', 422, [
            'width' => $outputWidth,
            'height' => $outputHeight,
        ]);
    }

    $output = sprite_canvas($outputWidth, $outputHeight);
    for ($frame = 0; $frame < $frameCount; $frame++) {
        $sourceX = (($frame % $columns) * $frameWidth) + $crop['x'];
        $sourceY = (intdiv($frame, $columns) * $frameHeight) + $crop['y'];
        $targetX = ($frame % $outputColumns) * $crop['width'];
        $targetY = intdiv($frame, $outputColumns) * $crop['height'];
        imagecopy($output, $source, $targetX, $targetY, $sourceX, $sourceY, $crop['width'], $crop['height']);
    }

    $uploadDir = Bootstrap::rootPath('gacha/uploads/sprites');
    $uploadRoot = dirname($uploadDir);
    if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0777, true) && !is_dir($uploadRoot)) {
        Response::error('Cannot create upload directory.', 500, ['path' => $uploadRoot]);
    }
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        Response::error('Cannot create sprite upload directory.', 500, ['path' => $uploadDir]);
    }
    @chmod($uploadRoot, 0777);
    @chmod($uploadDir, 0777);
    if (!is_writable($uploadDir)) {
        Response::error('Sprite upload directory is not writable.', 500, ['path' => $uploadDir]);
    }

    $name = 'sprite_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.png';
    $target = $uploadDir . DIRECTORY_SEPARATOR . $name;
    if (!imagepng($output, $target, 6)) {
        Response::error('Cannot save sprite PNG.', 500);
    }
    @chmod($target, 0666);

    imagedestroy($source);
    imagedestroy($output);

    $publicPath = 'uploads/sprites/' . $name;
    try {
        AuditLogger::access('gacha_sprite_export', 'file', $publicPath);
    } catch (Throwable) {
    }

    $baseUrl = rtrim((string) Bootstrap::config('app.baseUrl', ''), '/');
    $url = $baseUrl !== '' ? $baseUrl . '/gacha/' . $publicPath : $publicPath;

    Response::json([
        'ok' => true,
        'path' => $publicPath,
        'url' => $url,
        'width' => $outputWidth,
        'height' => $outputHeight,
        'columns' => $outputColumns,
        'rows' => $outputRows,
        'frameWidth' => $crop['width'],
        'frameHeight' => $crop['height'],
        'frameCount' => $frameCount,
        'crop' => $crop,
        'message' => 'Exported sprite sheet.',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'export') {
    sprite_export();
}

$csrfToken = Csrf::token();
$canExport = Auth::can('gacha.manage');
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>Sprite Sheet Cleaner</title>
  <style>
    :root {
      --bg: #07101b;
      --panel: rgba(12, 22, 36, 0.92);
      --panel-soft: rgba(17, 31, 48, 0.78);
      --line: rgba(139, 226, 219, 0.18);
      --ink: #edf7f5;
      --muted: rgba(221, 239, 236, 0.72);
      --accent: #8fe8d9;
      --gold: #ffd97a;
      --danger: #ff8b9a;
      --good: #8effbd;
      --font: "FC Vision Rounded", system-ui, sans-serif;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-Regular.woff2") format("woff2");
      font-weight: 400;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-SemiBold.woff2") format("woff2");
      font-weight: 600;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-Bold.woff2") format("woff2");
      font-weight: 700;
      font-style: normal;
      font-display: swap;
    }

    * {
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
    }

    html,
    body {
      width: 100%;
      min-height: 100%;
      margin: 0;
      background:
        radial-gradient(circle at 18% 0%, rgba(117, 232, 190, 0.14), transparent 34%),
        radial-gradient(circle at 86% 12%, rgba(255, 217, 122, 0.11), transparent 28%),
        linear-gradient(180deg, #081421, #040910 72%);
      color: var(--ink);
      font-family: var(--font);
      font-size: 16px;
    }

    body {
      min-height: 100vh;
      display: grid;
      grid-template-columns: minmax(300px, 360px) minmax(420px, 1fr) minmax(300px, 360px);
      gap: 0;
      overflow: hidden;
    }

    button,
    input,
    select,
    textarea {
      font: inherit;
    }

    button {
      min-height: 40px;
      border: 1px solid rgba(143, 232, 217, 0.18);
      border-radius: 8px;
      padding: 9px 12px;
      background: rgba(21, 37, 58, 0.86);
      color: var(--ink);
      font-weight: 700;
      cursor: pointer;
      transition: transform 140ms ease, border-color 140ms ease, background 140ms ease;
    }

    button:hover {
      border-color: rgba(143, 232, 217, 0.42);
      background: rgba(26, 48, 72, 0.94);
    }

    button:active {
      transform: translateY(1px);
    }

    button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    .primary {
      background: linear-gradient(135deg, rgba(143, 232, 217, 0.96), rgba(255, 217, 122, 0.92));
      border-color: transparent;
      color: #10202a;
    }

    .danger {
      color: #ffe8eb;
      border-color: rgba(255, 139, 154, 0.34);
      background: rgba(83, 27, 39, 0.72);
    }

    .sidebar,
    .inspector {
      height: 100vh;
      min-height: 0;
      overflow: auto;
      overscroll-behavior: contain;
      padding: 16px;
      background: linear-gradient(180deg, rgba(7, 16, 27, 0.96), rgba(7, 16, 27, 0.86));
    }

    .sidebar {
      border-right: 1px solid rgba(143, 232, 217, 0.12);
    }

    .inspector {
      border-left: 1px solid rgba(143, 232, 217, 0.12);
    }

    .workspace {
      min-width: 0;
      min-height: 0;
      height: 100vh;
      display: grid;
      grid-template-rows: auto minmax(0, 1fr);
      padding: 16px;
      overflow: hidden;
    }

    .panel {
      border: 1px solid var(--line);
      border-radius: 8px;
      background: var(--panel);
      box-shadow: 0 18px 46px rgba(0, 0, 0, 0.24);
      padding: 14px;
      margin-bottom: 12px;
    }

    .panel h1,
    .panel h2,
    .panel h3 {
      margin: 0;
      line-height: 1.15;
    }

    .panel h1 {
      font-size: 22px;
    }

    .panel h2 {
      font-size: 15px;
      margin-bottom: 10px;
    }

    .panel h3 {
      font-size: 13px;
      color: var(--muted);
    }

    .note,
    label,
    small {
      color: var(--muted);
      font-size: 12px;
      line-height: 1.45;
    }

    .drop-zone {
      min-height: 148px;
      display: grid;
      place-items: center;
      gap: 8px;
      text-align: center;
      border: 1.5px dashed rgba(143, 232, 217, 0.34);
      border-radius: 8px;
      background:
        linear-gradient(45deg, rgba(255, 255, 255, 0.025) 25%, transparent 25% 50%, rgba(255, 255, 255, 0.025) 50% 75%, transparent 75%),
        rgba(12, 24, 37, 0.76);
      background-size: 18px 18px;
      cursor: pointer;
    }

    .drop-zone.is-dragover {
      border-color: var(--gold);
      background-color: rgba(255, 217, 122, 0.08);
    }

    .drop-zone strong {
      display: block;
      font-size: 15px;
    }

    .drop-zone span {
      color: var(--muted);
      font-size: 12px;
    }

    .grid-2,
    .grid-3 {
      display: grid;
      gap: 8px;
    }

    .grid-2 {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid-3 {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .field {
      display: grid;
      gap: 5px;
    }

    input,
    select,
    textarea {
      width: 100%;
      min-height: 38px;
      border: 1px solid rgba(223, 239, 236, 0.12);
      border-radius: 8px;
      background: rgba(5, 10, 18, 0.58);
      color: var(--ink);
      padding: 8px 10px;
      outline: none;
    }

    textarea {
      min-height: 128px;
      resize: vertical;
      font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      font-size: 12px;
      line-height: 1.5;
    }

    input:focus,
    select:focus,
    textarea:focus {
      border-color: rgba(143, 232, 217, 0.5);
      box-shadow: 0 0 0 3px rgba(143, 232, 217, 0.1);
    }

    .segmented {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
    }

    .segmented button.is-active {
      background: rgba(143, 232, 217, 0.18);
      border-color: rgba(143, 232, 217, 0.52);
      color: #eafffb;
    }

    .button-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .button-row button {
      flex: 1 1 auto;
    }

    .status {
      min-height: 34px;
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 10px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.045);
      color: var(--muted);
      font-size: 12px;
    }

    .status.is-good {
      color: var(--good);
    }

    .status.is-bad {
      color: var(--danger);
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }

    .meta-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      justify-content: flex-end;
    }

    .pill {
      min-height: 28px;
      display: inline-flex;
      align-items: center;
      padding: 0 9px;
      border-radius: 999px;
      border: 1px solid rgba(143, 232, 217, 0.16);
      background: rgba(10, 19, 31, 0.72);
      color: var(--muted);
      font-size: 12px;
      white-space: nowrap;
    }

    .canvas-stage {
      min-height: 0;
      display: grid;
      grid-template-rows: minmax(0, 1fr) auto;
      gap: 10px;
      padding: 12px;
      border: 1px solid rgba(143, 232, 217, 0.14);
      border-radius: 8px;
      background:
        linear-gradient(45deg, rgba(255, 255, 255, 0.04) 25%, transparent 25% 50%, rgba(255, 255, 255, 0.04) 50% 75%, transparent 75%),
        rgba(3, 7, 13, 0.56);
      background-size: 24px 24px;
      overflow: hidden;
    }

    .source-wrap {
      position: relative;
      min-height: 0;
      display: grid;
      place-items: center;
      overflow: auto;
      border-radius: 8px;
      overscroll-behavior: contain;
    }

    canvas {
      max-width: 100%;
      height: auto;
      display: block;
      image-rendering: auto;
    }

    #sourceCanvas {
      cursor: crosshair;
    }

    .empty-state {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      text-align: center;
      color: var(--muted);
      pointer-events: none;
    }

    .empty-state.is-hidden {
      display: none;
    }

    .frame-bar {
      display: grid;
      grid-template-columns: auto minmax(120px, 1fr) auto;
      gap: 10px;
      align-items: center;
    }

    input[type="range"] {
      padding: 0;
      accent-color: var(--accent);
    }

    .preview-box {
      display: grid;
      gap: 10px;
    }

    .preview-canvas {
      min-height: 180px;
      display: grid;
      place-items: center;
      border-radius: 8px;
      border: 1px solid rgba(223, 239, 236, 0.12);
      background:
        linear-gradient(45deg, rgba(255, 255, 255, 0.06) 25%, transparent 25% 50%, rgba(255, 255, 255, 0.06) 50% 75%, transparent 75%),
        rgba(2, 6, 11, 0.52);
      background-size: 18px 18px;
      overflow: hidden;
    }

    .preview-canvas canvas {
      max-width: min(100%, 260px);
      max-height: 220px;
    }

    .result-image {
      width: 100%;
      min-height: 150px;
      display: grid;
      place-items: center;
      border: 1px solid rgba(223, 239, 236, 0.12);
      border-radius: 8px;
      background:
        linear-gradient(45deg, rgba(255, 255, 255, 0.05) 25%, transparent 25% 50%, rgba(255, 255, 255, 0.05) 50% 75%, transparent 75%),
        rgba(2, 6, 11, 0.52);
      background-size: 18px 18px;
      overflow: auto;
    }

    .result-image img {
      max-width: 100%;
      height: auto;
      display: block;
    }

    .hidden {
      display: none !important;
    }

    @media (max-width: 1180px) {
      body {
        grid-template-columns: 320px minmax(420px, 1fr);
      }

      .inspector {
        position: fixed;
        z-index: 8;
        right: 0;
        top: 0;
        bottom: 0;
        width: min(360px, 92vw);
        box-shadow: -24px 0 60px rgba(0, 0, 0, 0.34);
      }
    }

    @media (max-width: 760px) {
      body {
        display: block;
        overflow: auto;
      }

      .sidebar,
      .workspace,
      .inspector {
        height: auto;
        min-height: 0;
        overflow: visible;
        border: 0;
      }

      .workspace {
        display: block;
      }

      .canvas-stage {
        min-height: 420px;
      }

      .grid-3 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }
  </style>
</head>
<body data-csrf="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" data-can-export="<?= $canExport ? '1' : '0' ?>">
  <aside class="sidebar">
    <section class="panel">
      <h1>Sprite Sheet Cleaner</h1>
      <p class="note">ตัดเฟรมและประกอบ spritesheet ใหม่โดยไม่ยืดภาพ ใช้กับ PNG/WebP/JPG แล้ว export เป็น PNG พร้อม path สำหรับ mileage</p>
    </section>

    <section class="panel">
      <h2>ไฟล์ต้นฉบับ</h2>
      <label id="dropZone" class="drop-zone" for="fileInput">
        <input id="fileInput" class="hidden" type="file" accept="image/png,image/jpeg,image/webp" />
        <strong>ลากไฟล์มาวาง หรือคลิกเลือกภาพ</strong>
        <span>PNG, WebP, JPG ไม่เกิน 25MB</span>
      </label>
      <div id="fileStatus" class="status">ยังไม่ได้เลือกไฟล์</div>
    </section>

    <section class="panel">
      <h2>โครง sprite</h2>
      <div class="grid-3">
        <label class="field">Columns
          <input id="columnsInput" type="number" min="1" step="1" value="1" />
        </label>
        <label class="field">Rows
          <input id="rowsInput" type="number" min="1" step="1" value="1" />
        </label>
        <label class="field">Frames
          <input id="frameCountInput" type="number" min="1" step="1" value="1" />
        </label>
      </div>
      <div class="grid-2" style="margin-top:8px">
        <label class="field">Output columns
          <input id="outputColumnsInput" type="number" min="1" step="1" value="1" />
        </label>
        <label class="field">Preview FPS
          <input id="fpsInput" type="number" min="1" max="60" step="1" value="12" />
        </label>
      </div>
      <p class="note">เปลี่ยน `Columns` หรือ `Rows` แล้ว `Frames` จะเติมให้เต็ม grid อัตโนมัติ จากนั้น animation preview จะเริ่มวิ่งเองถ้ามีมากกว่า 1 เฟรม</p>
    </section>

    <section class="panel">
      <h2>Crop</h2>
      <div class="segmented" role="group" aria-label="Crop mode">
        <button id="manualModeButton" type="button" class="is-active">Manual</button>
        <button id="autoModeButton" type="button">Auto alpha</button>
      </div>
      <div class="grid-2" style="margin-top:10px">
        <label class="field">X
          <input id="cropXInput" type="number" min="0" step="1" value="0" />
        </label>
        <label class="field">Y
          <input id="cropYInput" type="number" min="0" step="1" value="0" />
        </label>
        <label class="field">Width
          <input id="cropWidthInput" type="number" min="1" step="1" value="1" />
        </label>
        <label class="field">Height
          <input id="cropHeightInput" type="number" min="1" step="1" value="1" />
        </label>
      </div>
      <div class="grid-2" style="margin-top:8px">
        <label class="field">Alpha threshold
          <input id="alphaThresholdInput" type="number" min="1" max="127" step="1" value="120" />
        </label>
        <label class="field">Padding
          <input id="paddingInput" type="number" min="0" max="512" step="1" value="0" />
        </label>
      </div>
      <div class="button-row">
        <button id="autoTrimButton" type="button">Auto trim</button>
        <button id="resetCropButton" type="button">Reset crop</button>
      </div>
    </section>

    <section class="panel">
      <h2>Export</h2>
      <div class="button-row">
        <button id="exportButton" class="primary" type="button" <?= $canExport ? '' : 'disabled' ?>>Export PNG</button>
      </div>
      <div id="exportStatus" class="status<?= $canExport ? '' : ' is-bad' ?>"><?= $canExport ? 'พร้อม export' : 'ต้องมีสิทธิ์ gacha.manage เพื่อ export ไฟล์' ?></div>
    </section>
  </aside>

  <main class="workspace">
    <header class="topbar">
      <div>
        <h2 style="margin:0;font-size:18px">Preview</h2>
        <small>ลากกรอบ crop บนเฟรมที่เลือก หรือกรอก pixel จากแถบซ้าย</small>
      </div>
      <div class="meta-pills">
        <span id="imageSizePill" class="pill">image: -</span>
        <span id="frameSizePill" class="pill">frame: -</span>
        <span id="cropSizePill" class="pill">crop: -</span>
      </div>
    </header>
    <section class="canvas-stage">
      <div class="source-wrap">
        <canvas id="sourceCanvas"></canvas>
        <div id="emptyState" class="empty-state">เลือก spritesheet เพื่อเริ่มจัดเฟรม</div>
      </div>
      <div class="frame-bar">
        <button id="prevFrameButton" type="button" disabled>Prev</button>
        <input id="frameSlider" type="range" min="0" max="0" value="0" disabled />
        <button id="nextFrameButton" type="button" disabled>Next</button>
      </div>
    </section>
  </main>

  <aside class="inspector">
    <section class="panel preview-box">
      <h2>เฟรมหลัง crop</h2>
      <div class="preview-canvas">
        <canvas id="frameCanvas"></canvas>
      </div>
      <div id="frameInfo" class="status">ยังไม่มี preview</div>
    </section>

    <section class="panel preview-box">
      <h2>Animation preview</h2>
      <div class="preview-canvas">
        <canvas id="animationCanvas"></canvas>
      </div>
      <div id="animationInfo" class="status">ยังไม่มี animation preview</div>
      <div class="button-row">
        <button id="playButton" type="button" disabled>Play</button>
      </div>
    </section>

    <section class="panel">
      <h2>ผลลัพธ์ export</h2>
      <div id="resultImage" class="result-image"><span class="note">ยังไม่ได้ export</span></div>
      <label class="field" style="margin-top:10px">Sprite source/path
        <input id="pathOutput" type="text" readonly placeholder="uploads/sprites/..." />
      </label>
      <label class="field" style="margin-top:8px">Config สำหรับ mileage
        <textarea id="configOutput" readonly placeholder="export แล้วค่าจะขึ้นตรงนี้"></textarea>
      </label>
      <div class="button-row">
        <button id="copyPathButton" type="button">Copy path</button>
        <button id="copyConfigButton" type="button">Copy config</button>
      </div>
    </section>
  </aside>

  <script>
    (() => {
      const body = document.body;
      const csrfToken = body.dataset.csrf || "";
      const canExport = body.dataset.canExport === "1";

      const fileInput = document.getElementById("fileInput");
      const dropZone = document.getElementById("dropZone");
      const fileStatus = document.getElementById("fileStatus");
      const columnsInput = document.getElementById("columnsInput");
      const rowsInput = document.getElementById("rowsInput");
      const frameCountInput = document.getElementById("frameCountInput");
      const outputColumnsInput = document.getElementById("outputColumnsInput");
      const fpsInput = document.getElementById("fpsInput");
      const cropXInput = document.getElementById("cropXInput");
      const cropYInput = document.getElementById("cropYInput");
      const cropWidthInput = document.getElementById("cropWidthInput");
      const cropHeightInput = document.getElementById("cropHeightInput");
      const alphaThresholdInput = document.getElementById("alphaThresholdInput");
      const paddingInput = document.getElementById("paddingInput");
      const manualModeButton = document.getElementById("manualModeButton");
      const autoModeButton = document.getElementById("autoModeButton");
      const autoTrimButton = document.getElementById("autoTrimButton");
      const resetCropButton = document.getElementById("resetCropButton");
      const exportButton = document.getElementById("exportButton");
      const exportStatus = document.getElementById("exportStatus");
      const sourceCanvas = document.getElementById("sourceCanvas");
      const frameCanvas = document.getElementById("frameCanvas");
      const animationCanvas = document.getElementById("animationCanvas");
      const emptyState = document.getElementById("emptyState");
      const frameSlider = document.getElementById("frameSlider");
      const prevFrameButton = document.getElementById("prevFrameButton");
      const nextFrameButton = document.getElementById("nextFrameButton");
      const imageSizePill = document.getElementById("imageSizePill");
      const frameSizePill = document.getElementById("frameSizePill");
      const cropSizePill = document.getElementById("cropSizePill");
      const frameInfo = document.getElementById("frameInfo");
      const animationInfo = document.getElementById("animationInfo");
      const playButton = document.getElementById("playButton");
      const resultImage = document.getElementById("resultImage");
      const pathOutput = document.getElementById("pathOutput");
      const configOutput = document.getElementById("configOutput");
      const copyPathButton = document.getElementById("copyPathButton");
      const copyConfigButton = document.getElementById("copyConfigButton");

      const state = {
        file: null,
        image: null,
        imageWidth: 0,
        imageHeight: 0,
        cropMode: "manual",
        selectedFrame: 0,
        crop: { x: 0, y: 0, width: 1, height: 1 },
        dragging: null,
        playing: true,
        lastAnimAt: 0,
        animFrame: 0,
        result: null,
        frameCountTouched: false,
        outputColumnsTouched: false
      };

      function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
      }

      function intValue(input, fallback = 1) {
        const value = Number.parseInt(input.value, 10);
        return Number.isFinite(value) ? value : fallback;
      }

      function settings() {
        const columns = clamp(intValue(columnsInput, 1), 1, 512);
        const rows = clamp(intValue(rowsInput, 1), 1, 512);
        const maxFrames = Math.max(1, columns * rows);
        const frameCount = clamp(intValue(frameCountInput, maxFrames), 1, maxFrames);
        const outputColumns = clamp(intValue(outputColumnsInput, frameCount), 1, frameCount);
        const frameWidth = state.imageWidth > 0 ? Math.floor(state.imageWidth / columns) : 1;
        const frameHeight = state.imageHeight > 0 ? Math.floor(state.imageHeight / rows) : 1;
        return {
          columns,
          rows,
          maxFrames,
          frameCount,
          outputColumns,
          outputRows: Math.ceil(frameCount / outputColumns),
          frameWidth: Math.max(1, frameWidth),
          frameHeight: Math.max(1, frameHeight),
          fps: clamp(intValue(fpsInput, 12), 1, 60),
          alphaThreshold: clamp(intValue(alphaThresholdInput, 120), 1, 127),
          padding: clamp(intValue(paddingInput, 0), 0, 512)
        };
      }

      function updateDerivedInputs(reason = "general") {
        const columns = clamp(intValue(columnsInput, 1), 1, 512);
        const rows = clamp(intValue(rowsInput, 1), 1, 512);
        const maxFrames = Math.max(1, columns * rows);
        const currentFrames = clamp(intValue(frameCountInput, maxFrames), 1, maxFrames);

        columnsInput.value = String(columns);
        rowsInput.value = String(rows);

        if (!state.frameCountTouched || reason === "grid" || currentFrames > maxFrames) {
          frameCountInput.value = String(maxFrames);
        } else {
          frameCountInput.value = String(currentFrames);
        }

        const frameCount = clamp(intValue(frameCountInput, maxFrames), 1, maxFrames);
        const currentOutputColumns = clamp(intValue(outputColumnsInput, frameCount), 1, frameCount);
        if (!state.outputColumnsTouched || reason === "grid" || currentOutputColumns > frameCount) {
          outputColumnsInput.value = String(frameCount);
        } else {
          outputColumnsInput.value = String(currentOutputColumns);
        }
      }

      function frameRect(frameIndex = state.selectedFrame) {
        const s = settings();
        return {
          x: (frameIndex % s.columns) * s.frameWidth,
          y: Math.floor(frameIndex / s.columns) * s.frameHeight,
          width: s.frameWidth,
          height: s.frameHeight
        };
      }

      function normalizeCrop(crop = state.crop) {
        const s = settings();
        const x = clamp(Math.round(Number(crop.x || 0)), 0, Math.max(0, s.frameWidth - 1));
        const y = clamp(Math.round(Number(crop.y || 0)), 0, Math.max(0, s.frameHeight - 1));
        const width = clamp(Math.round(Number(crop.width || s.frameWidth)), 1, Math.max(1, s.frameWidth - x));
        const height = clamp(Math.round(Number(crop.height || s.frameHeight)), 1, Math.max(1, s.frameHeight - y));
        return { x, y, width, height };
      }

      function syncInputsFromCrop() {
        const crop = normalizeCrop();
        state.crop = crop;
        cropXInput.value = String(crop.x);
        cropYInput.value = String(crop.y);
        cropWidthInput.value = String(crop.width);
        cropHeightInput.value = String(crop.height);
      }

      function syncCropFromInputs() {
        state.crop = normalizeCrop({
          x: intValue(cropXInput, 0),
          y: intValue(cropYInput, 0),
          width: intValue(cropWidthInput, 1),
          height: intValue(cropHeightInput, 1)
        });
        syncInputsFromCrop();
        renderAll();
      }

      function setStatus(node, message, kind = "") {
        node.textContent = message;
        node.classList.toggle("is-good", kind === "good");
        node.classList.toggle("is-bad", kind === "bad");
      }

      function setCropMode(mode) {
        state.cropMode = mode === "auto-alpha" ? "auto-alpha" : "manual";
        manualModeButton.classList.toggle("is-active", state.cropMode === "manual");
        autoModeButton.classList.toggle("is-active", state.cropMode === "auto-alpha");
      }

      function updateUiAvailability() {
        const hasImage = !!state.image;
        const s = settings();
        const multiFrame = hasImage && s.frameCount > 1;

        playButton.disabled = !multiFrame;
        prevFrameButton.disabled = !multiFrame;
        nextFrameButton.disabled = !multiFrame;
        frameSlider.disabled = !multiFrame;
        autoTrimButton.disabled = !hasImage;
        resetCropButton.disabled = !hasImage;
        manualModeButton.disabled = !hasImage;
        autoModeButton.disabled = !hasImage;

        if (!hasImage) {
          playButton.textContent = "Play";
          animationInfo.textContent = "ยังไม่มี animation preview";
        } else if (!multiFrame) {
          playButton.textContent = "Play";
          animationInfo.textContent = "ตอนนี้มี 1 เฟรมอยู่ preview จะโชว์ภาพนิ่ง";
        } else {
          playButton.textContent = state.playing ? "Pause" : "Play";
          animationInfo.textContent = state.playing
            ? `กำลัง preview ${s.frameCount} เฟรม ที่ ${s.fps} FPS`
            : `หยุด preview ไว้ที่เฟรม ${state.animFrame + 1}/${s.frameCount}`;
        }
      }

      async function loadFile(file) {
        if (!file) return;
        if (!/^image\/(png|jpeg|webp)$/.test(file.type)) {
          setStatus(fileStatus, "รองรับเฉพาะ PNG, JPG, WebP", "bad");
          return;
        }
        if (file.size > 26214400) {
          setStatus(fileStatus, "ไฟล์ใหญ่เกิน 25MB", "bad");
          return;
        }

        const url = URL.createObjectURL(file);
        const image = new Image();
        image.decoding = "async";
        image.onload = () => {
          URL.revokeObjectURL(url);
          if ((image.naturalWidth * image.naturalHeight) > 48000000) {
            setStatus(fileStatus, "ภาพใหญ่เกินไป ลดขนาดภาพก่อนใช้งาน", "bad");
            return;
          }
          state.file = file;
          state.image = image;
          state.imageWidth = image.naturalWidth;
          state.imageHeight = image.naturalHeight;
          state.selectedFrame = 0;
          state.animFrame = 0;
          state.result = null;
          state.playing = true;
          state.frameCountTouched = false;
          state.outputColumnsTouched = false;
          pathOutput.value = "";
          configOutput.value = "";
          resultImage.innerHTML = '<span class="note">ยังไม่ได้ export</span>';
          updateDerivedInputs("grid");
          resetCrop();
          setStatus(fileStatus, `${file.name} (${image.naturalWidth}x${image.naturalHeight})`, "good");
          if (canExport) {
            setStatus(exportStatus, "ตั้ง Columns / Rows ให้ตรงกับ spritesheet แล้ว preview จะวิ่งเอง", "");
          }
          renderAll();
        };
        image.onerror = () => {
          URL.revokeObjectURL(url);
          setStatus(fileStatus, "อ่านรูปไม่สำเร็จ", "bad");
        };
        image.src = url;
      }

      function resetCrop() {
        const s = settings();
        state.crop = { x: 0, y: 0, width: s.frameWidth, height: s.frameHeight };
        syncInputsFromCrop();
      }

      function autoTrim() {
        if (!state.image) return;
        const s = settings();
        const scanCanvas = document.createElement("canvas");
        scanCanvas.width = state.imageWidth;
        scanCanvas.height = state.imageHeight;
        const ctx = scanCanvas.getContext("2d", { willReadFrequently: true });
        ctx.clearRect(0, 0, scanCanvas.width, scanCanvas.height);
        ctx.drawImage(state.image, 0, 0);

        let minX = s.frameWidth;
        let minY = s.frameHeight;
        let maxX = -1;
        let maxY = -1;

        for (let frame = 0; frame < s.frameCount; frame++) {
          const rect = frameRect(frame);
          const data = ctx.getImageData(rect.x, rect.y, rect.width, rect.height).data;
          for (let y = 0; y < rect.height; y++) {
            for (let x = 0; x < rect.width; x++) {
              const alpha = data[((y * rect.width + x) * 4) + 3];
              const gdAlpha = Math.round(127 - (alpha / 255) * 127);
              if (gdAlpha >= s.alphaThreshold) continue;
              minX = Math.min(minX, x);
              minY = Math.min(minY, y);
              maxX = Math.max(maxX, x);
              maxY = Math.max(maxY, y);
            }
          }
        }

        if (maxX < minX || maxY < minY) {
          resetCrop();
          setStatus(exportStatus, "ไม่เจอ pixel ที่ไม่โปร่งใส เลย reset เป็นเต็มเฟรม", "bad");
          renderAll();
          return;
        }

        minX = clamp(minX - s.padding, 0, s.frameWidth - 1);
        minY = clamp(minY - s.padding, 0, s.frameHeight - 1);
        maxX = clamp(maxX + s.padding, 0, s.frameWidth - 1);
        maxY = clamp(maxY + s.padding, 0, s.frameHeight - 1);
        state.crop = {
          x: minX,
          y: minY,
          width: (maxX - minX) + 1,
          height: (maxY - minY) + 1
        };
        setCropMode("auto-alpha");
        syncInputsFromCrop();
        setStatus(exportStatus, "Auto trim คำนวณจากทุกเฟรมแล้ว", "good");
        renderAll();
      }

      function resizeCanvasToImage(canvas, width, height) {
        canvas.width = Math.max(1, Math.round(width));
        canvas.height = Math.max(1, Math.round(height));
        canvas.style.aspectRatio = `${canvas.width} / ${canvas.height}`;
      }

      function renderSource() {
        const ctx = sourceCanvas.getContext("2d");
        if (!state.image) {
          resizeCanvasToImage(sourceCanvas, 900, 540);
          ctx.clearRect(0, 0, sourceCanvas.width, sourceCanvas.height);
          emptyState.classList.remove("is-hidden");
          return;
        }

        emptyState.classList.add("is-hidden");
        resizeCanvasToImage(sourceCanvas, state.imageWidth, state.imageHeight);
        ctx.clearRect(0, 0, state.imageWidth, state.imageHeight);
        ctx.drawImage(state.image, 0, 0);

        const s = settings();
        const selected = frameRect();
        const crop = normalizeCrop();
        ctx.save();
        ctx.lineWidth = Math.max(1, Math.min(s.frameWidth, s.frameHeight) * 0.006);
        ctx.strokeStyle = "rgba(143, 232, 217, 0.62)";
        ctx.beginPath();
        for (let col = 1; col < s.columns; col++) {
          const x = col * s.frameWidth;
          ctx.moveTo(x, 0);
          ctx.lineTo(x, s.frameHeight * s.rows);
        }
        for (let row = 1; row < s.rows; row++) {
          const y = row * s.frameHeight;
          ctx.moveTo(0, y);
          ctx.lineTo(s.frameWidth * s.columns, y);
        }
        ctx.stroke();

        ctx.fillStyle = "rgba(255, 217, 122, 0.15)";
        ctx.strokeStyle = "rgba(255, 217, 122, 0.95)";
        ctx.lineWidth = Math.max(2, Math.min(s.frameWidth, s.frameHeight) * 0.012);
        ctx.fillRect(selected.x, selected.y, selected.width, selected.height);
        ctx.strokeRect(selected.x + 1, selected.y + 1, selected.width - 2, selected.height - 2);

        const cropX = selected.x + crop.x;
        const cropY = selected.y + crop.y;
        ctx.fillStyle = "rgba(143, 232, 217, 0.16)";
        ctx.strokeStyle = "rgba(143, 232, 217, 1)";
        ctx.lineWidth = Math.max(2, Math.min(crop.width, crop.height) * 0.018);
        ctx.fillRect(cropX, cropY, crop.width, crop.height);
        ctx.strokeRect(cropX + 1, cropY + 1, crop.width - 2, crop.height - 2);
        ctx.restore();
      }

      function renderFramePreview() {
        const ctx = frameCanvas.getContext("2d");
        if (!state.image) {
          resizeCanvasToImage(frameCanvas, 1, 1);
          ctx.clearRect(0, 0, 1, 1);
          frameInfo.textContent = "ยังไม่มี preview";
          return;
        }
        const rect = frameRect();
        const crop = normalizeCrop();
        resizeCanvasToImage(frameCanvas, crop.width, crop.height);
        ctx.clearRect(0, 0, crop.width, crop.height);
        ctx.drawImage(
          state.image,
          rect.x + crop.x,
          rect.y + crop.y,
          crop.width,
          crop.height,
          0,
          0,
          crop.width,
          crop.height
        );
        const totalFrames = settings().frameCount;
        frameInfo.textContent = totalFrames > 1
          ? `frame ${state.selectedFrame + 1}/${totalFrames} | ${crop.width}x${crop.height}`
          : `ตอนนี้มี 1 เฟรมอยู่ ลองเพิ่ม Columns / Rows ให้ครบก่อน`;
      }

      function drawAnimationPreview(frameIndex = state.animFrame) {
        const ctx = animationCanvas.getContext("2d");
        if (!state.image) {
          resizeCanvasToImage(animationCanvas, 1, 1);
          ctx.clearRect(0, 0, 1, 1);
          return;
        }
        const s = settings();
        const crop = normalizeCrop();
        const safeFrameIndex = clamp(Math.round(Number(frameIndex || 0)), 0, Math.max(0, s.frameCount - 1));
        if (animationCanvas.width !== crop.width || animationCanvas.height !== crop.height) {
          resizeCanvasToImage(animationCanvas, crop.width, crop.height);
        }
        const rect = frameRect(safeFrameIndex);
        ctx.clearRect(0, 0, crop.width, crop.height);
        ctx.drawImage(
          state.image,
          rect.x + crop.x,
          rect.y + crop.y,
          crop.width,
          crop.height,
          0,
          0,
          crop.width,
          crop.height
        );
      }

      function renderAnimationFrame(ts = performance.now()) {
        if (!state.image) {
          drawAnimationPreview(0);
          return;
        }
        const s = settings();
        state.animFrame = clamp(state.animFrame, 0, Math.max(0, s.frameCount - 1));
        if (state.playing && ts - state.lastAnimAt >= 1000 / s.fps) {
          state.animFrame = (state.animFrame + 1) % s.frameCount;
          state.lastAnimAt = ts;
          updateUiAvailability();
        }
        drawAnimationPreview(state.animFrame);
        requestAnimationFrame(renderAnimationFrame);
      }

      function updateMeta() {
        const s = settings();
        const crop = normalizeCrop();
        imageSizePill.textContent = state.image ? `image: ${state.imageWidth}x${state.imageHeight}` : "image: -";
        frameSizePill.textContent = state.image ? `frame: ${s.frameWidth}x${s.frameHeight}` : "frame: -";
        cropSizePill.textContent = state.image ? `crop: ${crop.width}x${crop.height}` : "crop: -";
        frameSlider.max = String(Math.max(0, s.frameCount - 1));
        frameSlider.value = String(clamp(state.selectedFrame, 0, s.frameCount - 1));
        updateUiAvailability();
      }

      function renderAll() {
        state.crop = normalizeCrop();
        updateMeta();
        renderSource();
        renderFramePreview();
        state.animFrame = clamp(state.animFrame, 0, Math.max(0, settings().frameCount - 1));
        drawAnimationPreview(state.animFrame);
      }

      function sourcePointFromEvent(event) {
        const rect = sourceCanvas.getBoundingClientRect();
        return {
          x: clamp(((event.clientX - rect.left) / rect.width) * state.imageWidth, 0, state.imageWidth),
          y: clamp(((event.clientY - rect.top) / rect.height) * state.imageHeight, 0, state.imageHeight)
        };
      }

      function startCropDrag(event) {
        if (!state.image) return;
        const frame = frameRect();
        const point = sourcePointFromEvent(event);
        if (point.x < frame.x || point.y < frame.y || point.x > frame.x + frame.width || point.y > frame.y + frame.height) {
          return;
        }
        sourceCanvas.setPointerCapture(event.pointerId);
        state.dragging = {
          startX: clamp(Math.round(point.x - frame.x), 0, frame.width - 1),
          startY: clamp(Math.round(point.y - frame.y), 0, frame.height - 1)
        };
        state.crop = { x: state.dragging.startX, y: state.dragging.startY, width: 1, height: 1 };
        setCropMode("manual");
        syncInputsFromCrop();
        renderAll();
      }

      function moveCropDrag(event) {
        if (!state.image || !state.dragging) return;
        const frame = frameRect();
        const point = sourcePointFromEvent(event);
        const currentX = clamp(Math.round(point.x - frame.x), 0, frame.width - 1);
        const currentY = clamp(Math.round(point.y - frame.y), 0, frame.height - 1);
        const left = Math.min(state.dragging.startX, currentX);
        const top = Math.min(state.dragging.startY, currentY);
        const right = Math.max(state.dragging.startX, currentX);
        const bottom = Math.max(state.dragging.startY, currentY);
        state.crop = { x: left, y: top, width: (right - left) + 1, height: (bottom - top) + 1 };
        syncInputsFromCrop();
        renderAll();
      }

      function stopCropDrag(event) {
        if (!state.dragging) return;
        try {
          sourceCanvas.releasePointerCapture(event.pointerId);
        } catch (error) {
        }
        state.dragging = null;
      }

      async function copyText(value) {
        const text = String(value || "");
        if (!text) return;
        if (navigator.clipboard?.writeText) {
          await navigator.clipboard.writeText(text);
          return;
        }
        const input = document.createElement("textarea");
        input.value = text;
        document.body.append(input);
        input.select();
        document.execCommand("copy");
        input.remove();
      }

      async function exportSprite() {
        if (!state.file || !state.image || !canExport) return;
        const crop = normalizeCrop();
        const s = settings();
        const form = new FormData();
        form.append("image", state.file);
        form.append("columns", String(s.columns));
        form.append("rows", String(s.rows));
        form.append("frameCount", String(s.frameCount));
        form.append("outputColumns", String(s.outputColumns));
        form.append("cropMode", state.cropMode);
        form.append("cropX", String(crop.x));
        form.append("cropY", String(crop.y));
        form.append("cropWidth", String(crop.width));
        form.append("cropHeight", String(crop.height));
        form.append("alphaThreshold", String(s.alphaThreshold));
        form.append("padding", String(s.padding));

        exportButton.disabled = true;
        setStatus(exportStatus, "กำลัง export...", "");
        try {
          const response = await fetch("sprite.php?action=export", {
            method: "POST",
            headers: { "X-CSRF-Token": csrfToken },
            body: form
          });
          const data = await response.json();
          if (!response.ok || !data.ok) {
            throw new Error(data.message || "Export failed");
          }
          state.result = data;
          const previewUrl = `${data.path}?v=${Date.now()}`;
          resultImage.innerHTML = `<img src="${previewUrl}" alt="Exported sprite sheet" />`;
          pathOutput.value = data.path || "";
          configOutput.value = JSON.stringify({
            src: data.path,
            columns: data.columns,
            rows: data.rows,
            frameWidth: data.frameWidth,
            frameHeight: data.frameHeight,
            frameCount: data.frameCount
          }, null, 2);
          setStatus(exportStatus, `export แล้ว: ${data.width}x${data.height}`, "good");
        } catch (error) {
          setStatus(exportStatus, error.message || "Export failed", "bad");
        } finally {
          exportButton.disabled = !canExport;
        }
      }

      fileInput.addEventListener("change", () => loadFile(fileInput.files?.[0] || null));
      dropZone.addEventListener("dragover", (event) => {
        event.preventDefault();
        dropZone.classList.add("is-dragover");
      });
      dropZone.addEventListener("dragleave", () => dropZone.classList.remove("is-dragover"));
      dropZone.addEventListener("drop", (event) => {
        event.preventDefault();
        dropZone.classList.remove("is-dragover");
        loadFile(event.dataTransfer?.files?.[0] || null);
      });

      columnsInput.addEventListener("input", () => {
        updateDerivedInputs("grid");
        const s = settings();
        state.selectedFrame = clamp(state.selectedFrame, 0, s.frameCount - 1);
        state.animFrame = clamp(state.animFrame, 0, s.frameCount - 1);
        syncInputsFromCrop();
        renderAll();
      });
      rowsInput.addEventListener("input", () => {
        updateDerivedInputs("grid");
        const s = settings();
        state.selectedFrame = clamp(state.selectedFrame, 0, s.frameCount - 1);
        state.animFrame = clamp(state.animFrame, 0, s.frameCount - 1);
        syncInputsFromCrop();
        renderAll();
      });
      frameCountInput.addEventListener("input", () => {
        state.frameCountTouched = true;
        updateDerivedInputs("frameCount");
        const s = settings();
        state.selectedFrame = clamp(state.selectedFrame, 0, s.frameCount - 1);
        state.animFrame = clamp(state.animFrame, 0, s.frameCount - 1);
        renderAll();
      });
      outputColumnsInput.addEventListener("input", () => {
        state.outputColumnsTouched = true;
        updateDerivedInputs("outputColumns");
        renderAll();
      });
      for (const input of [fpsInput, alphaThresholdInput, paddingInput]) {
        input.addEventListener("input", () => {
          renderAll();
        });
      }
      for (const input of [cropXInput, cropYInput, cropWidthInput, cropHeightInput]) {
        input.addEventListener("input", () => {
          setCropMode("manual");
          syncCropFromInputs();
        });
      }

      manualModeButton.addEventListener("click", () => setCropMode("manual"));
      autoModeButton.addEventListener("click", () => {
        setCropMode("auto-alpha");
        autoTrim();
      });
      autoTrimButton.addEventListener("click", autoTrim);
      resetCropButton.addEventListener("click", () => {
        setCropMode("manual");
        resetCrop();
        renderAll();
      });
      exportButton.addEventListener("click", exportSprite);
      sourceCanvas.addEventListener("pointerdown", startCropDrag);
      sourceCanvas.addEventListener("pointermove", moveCropDrag);
      sourceCanvas.addEventListener("pointerup", stopCropDrag);
      sourceCanvas.addEventListener("pointercancel", stopCropDrag);

      frameSlider.addEventListener("input", () => {
        state.selectedFrame = clamp(intValue(frameSlider, 0), 0, settings().frameCount - 1);
        renderAll();
      });
      prevFrameButton.addEventListener("click", () => {
        state.selectedFrame = clamp(state.selectedFrame - 1, 0, settings().frameCount - 1);
        renderAll();
      });
      nextFrameButton.addEventListener("click", () => {
        state.selectedFrame = clamp(state.selectedFrame + 1, 0, settings().frameCount - 1);
        renderAll();
      });
      playButton.addEventListener("click", () => {
        if (playButton.disabled) return;
        state.playing = !state.playing;
        state.lastAnimAt = performance.now();
        updateUiAvailability();
        drawAnimationPreview(state.animFrame);
        playButton.textContent = state.playing ? "Pause" : "Play";
      });
      copyPathButton.addEventListener("click", () => copyText(pathOutput.value));
      copyConfigButton.addEventListener("click", () => copyText(configOutput.value));

      updateDerivedInputs("grid");
      resetCrop();
      renderAll();
      requestAnimationFrame(renderAnimationFrame);
    })();
  </script>
</body>
</html>
