<?php

declare(strict_types=1);

$isEmbed = isset($_GET['embed']);
$gachaPrizeTiers = [];

try {
    require_once __DIR__ . '/../core/Bootstrap.php';
    Bootstrap::init();
    $gachaPrizePageCacheKey = '';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_GET['nocache'])) {
        $gachaPrizePageCacheKey = PublicPageCacheService::key('gacha-prizes', [
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'file' => filemtime(__FILE__) ?: 0,
        ]);
        $cachedGachaPrizePage = PublicPageCacheService::get($gachaPrizePageCacheKey, 90);
        if ($cachedGachaPrizePage !== '') {
            echo $cachedGachaPrizePage;
            exit;
        }
        ob_start();
    }
    if (class_exists('GachaConfigService')) {
        $gachaPrizeTiers = GachaConfigService::buildGachaPrizePageTiers(GachaConfigService::load());
    }
} catch (Throwable) {
    // Fall back to defaults or local mock data below.
}

if (!$gachaPrizeTiers && class_exists('GachaConfigService')) {
    try {
        $gachaPrizeTiers = GachaConfigService::buildGachaPrizePageTiers(GachaConfigService::defaults());
    } catch (Throwable) {
        $gachaPrizeTiers = [];
    }
}

if (!$gachaPrizeTiers) {
    $gachaPrizeTiers = [
        [
            'id' => 'tier-mythic',
            'name' => 'สีรุ้ง',
            'tier' => 'Mythic',
            'rate' => '1.00%',
            'ball' => 'ball_5.png',
            'accent' => '#ff78cf',
            'soft' => '#efffff',
            'deep' => '#7b3bb3',
            'special' => true,
            'summary' => 'รางวัลระดับสูงสุดของกาชา พร้อมของพิเศษและยศจำกัดเวลา',
            'itemShelves' => [
                ['icon' => 'ball_5.png', 'name' => 'Mythic Ball', 'type' => 'item', 'typeLabel' => 'Prize Item', 'badge' => 'SSS', 'statusIcon' => 'ICO1.png', 'pickDate' => '2026-06-30 23:59:00', 'pickDateLabel' => '30/06/2026 23:59', 'description' => 'ลูกกาชาพิเศษพร้อมเอฟเฟกต์โชว์รางวัล'],
                ['icon' => 'item-1.png', 'name' => 'Gamer P SSS', 'type' => 'item', 'typeLabel' => 'Prize Item', 'badge' => 'Jackpot', 'statusIcon' => '', 'pickDate' => '', 'pickDateLabel' => '', 'description' => 'เหรียญพรีเมียมใช้แลกของในร้าน'],
            ],
            'itemList' => [
                ['icon' => 'ball_5.png', 'name' => 'Mythic Ball', 'line' => 'Mythic Ball · ลูกกาชาพิเศษพร้อมเอฟเฟกต์โชว์รางวัล', 'type' => 'item', 'typeLabel' => 'Prize Item', 'badge' => 'SSS', 'statusIcon' => 'ICO1.png', 'pickDate' => '2026-06-30 23:59:00', 'pickDateLabel' => '30/06/2026 23:59', 'description' => 'ลูกกาชาพิเศษพร้อมเอฟเฟกต์โชว์รางวัล'],
                ['icon' => 'item-1.png', 'name' => 'Gamer P SSS', 'line' => 'Gamer P SSS · เหรียญพรีเมียมใช้แลกของในร้าน', 'type' => 'item', 'typeLabel' => 'Prize Item', 'badge' => 'Jackpot', 'statusIcon' => '', 'pickDate' => '', 'pickDateLabel' => '', 'description' => 'เหรียญพรีเมียมใช้แลกของในร้าน'],
            ],
            'roleList' => [
                [
                    'icon' => 'icon_3.png',
                    'name' => 'Aurora Role',
                    'line' => 'Aurora Role · 30 วัน',
                    'type' => 'role',
                    'typeLabel' => 'Discord Role',
                    'badge' => 'Event',
                    'statusIcon' => '',
                    'pickDate' => '2026-06-30 23:59:00',
                    'pickDateLabel' => '30/06/2026 23:59',
                    'description' => 'ยศพิเศษสำหรับผู้เล่นที่ดวงดีที่สุดในตู้',
                    'roleDurationDays' => 30,
                    'roleDurationLabel' => '30 วัน',
                    'roleColor' => '#c86eff',
                    'roleId' => 'mock-role-1',
                    'rolePosition' => 12,
                    'permissionCount' => 4,
                    'permissionDetails' => [
                        ['code' => 'VIEW_CHANNEL', 'label' => 'ดูห้อง', 'description' => 'มองเห็นและเข้าอ่านห้องที่เปิดสิทธิ์ให้ยศนี้'],
                        ['code' => 'SEND_MESSAGES', 'label' => 'ส่งข้อความ', 'description' => 'พิมพ์ข้อความตอบโต้ในห้องแชตได้ตามปกติ'],
                        ['code' => 'EMBED_LINKS', 'label' => 'ฝังลิงก์', 'description' => 'ส่งลิงก์แบบมีการ์ดพรีวิวและข้อมูลประกอบอัตโนมัติได้'],
                        ['code' => 'USE_EMBEDDED_ACTIVITIES', 'label' => 'ใช้กิจกรรมร่วม', 'description' => 'เปิดกิจกรรมหรือมินิเกมร่วมกันในห้องเสียงได้'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'tier-gold',
            'name' => 'สีทอง',
            'tier' => 'Legendary',
            'rate' => '5.00%',
            'ball' => 'ball_4.png',
            'accent' => '#f5bd45',
            'soft' => '#fff0c7',
            'deep' => '#9a6420',
            'special' => false,
            'summary' => 'โซนของรางวัลหายากที่มีทั้งไอเทมโชว์และยศแจกแบบจำกัดเวลา',
            'itemShelves' => [
                ['icon' => 'ball_4.png', 'name' => 'Gold Core', 'type' => 'item', 'typeLabel' => 'Prize Item', 'badge' => 'Hot', 'statusIcon' => '', 'pickDate' => '2026-06-24 18:00:00', 'pickDateLabel' => '24/06/2026 18:00', 'description' => 'แกนรางวัลสีทองสำหรับแลกของพรีเมียม'],
            ],
            'itemList' => [
                ['icon' => 'ball_4.png', 'name' => 'Gold Core', 'line' => 'Gold Core · แกนรางวัลสีทองสำหรับแลกของพรีเมียม', 'type' => 'item', 'typeLabel' => 'Prize Item', 'badge' => 'Hot', 'statusIcon' => '', 'pickDate' => '2026-06-24 18:00:00', 'pickDateLabel' => '24/06/2026 18:00', 'description' => 'แกนรางวัลสีทองสำหรับแลกของพรีเมียม'],
            ],
            'roleList' => [],
        ],
    ];
}

$gachaPrizeSwiperCss = '';
$gachaPrizeSwiperJs = '';
$gachaPrizeSwiperCssPath = __DIR__ . '/vendor/swiper/swiper-bundle.min.css';
$gachaPrizeSwiperJsPath = __DIR__ . '/vendor/swiper/swiper-bundle.min.js';

if (is_file($gachaPrizeSwiperCssPath)) {
    $gachaPrizeSwiperCss = (string) file_get_contents($gachaPrizeSwiperCssPath);
}

if (is_file($gachaPrizeSwiperJsPath)) {
    $gachaPrizeSwiperJs = str_replace('</script>', '<\/script>', (string) file_get_contents($gachaPrizeSwiperJsPath));
}

$gachaPrizeTierRank = [
    'Mythic' => 1,
    'Legendary' => 2,
    'Epic' => 3,
    'Rare' => 4,
    'Common' => 5,
];

usort($gachaPrizeTiers, static function (array $left, array $right) use ($gachaPrizeTierRank): int {
    return ($gachaPrizeTierRank[$left['tier']] ?? 99) <=> ($gachaPrizeTierRank[$right['tier']] ?? 99);
});

function gachaPrizeEsc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function gachaPrizeAssetUrl(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return 'images/item-1.png';
    }
    if (preg_match('/^https?:\/\//', $path) || str_starts_with($path, '/')) {
        return $path;
    }
    if (str_starts_with($path, 'images/') || str_starts_with($path, 'uploads/')) {
        return $path;
    }
    return 'images/' . $path;
}

function gachaPrizePayload(array $tier, array $prize): array
{
    $statusIcon = trim((string) ($prize['statusIcon'] ?? ''));

    return [
        'tierId' => (string) ($tier['id'] ?? ''),
        'tier' => (string) ($tier['tier'] ?? ''),
        'tierName' => (string) ($tier['name'] ?? ''),
        'rate' => (string) ($tier['rate'] ?? ''),
        'accent' => (string) ($tier['accent'] ?? '#8bd6ff'),
        'soft' => (string) ($tier['soft'] ?? '#eef8ff'),
        'deep' => (string) ($tier['deep'] ?? '#3d5a88'),
        'special' => !empty($tier['special']),
        'summary' => (string) ($tier['summary'] ?? ''),
        'name' => (string) ($prize['name'] ?? 'Mystery Prize'),
        'shortName' => (string) ($prize['shortName'] ?? ($prize['name'] ?? 'Mystery Prize')),
        'line' => (string) ($prize['line'] ?? ''),
        'type' => (string) ($prize['type'] ?? 'item'),
        'typeLabel' => (string) ($prize['typeLabel'] ?? (($prize['type'] ?? 'item') === 'role' ? 'ยศ' : 'ไอเทม')),
        'image' => gachaPrizeAssetUrl((string) ($prize['icon'] ?? '')),
        'badge' => (string) ($prize['badge'] ?? ''),
        'statusIcon' => $statusIcon !== '' ? gachaPrizeAssetUrl($statusIcon) : '',
        'pickDate' => (string) ($prize['pickDate'] ?? ''),
        'pickDateLabel' => (string) ($prize['pickDateLabel'] ?? ''),
        'isExpired' => !empty($prize['isExpired']),
        'coinPrice' => (int) ($prize['coinPrice'] ?? 0),
        'displayQuantityLabel' => (string) ($prize['displayQuantityLabel'] ?? ''),
        'description' => (string) ($prize['description'] ?? ''),
        'conditionText' => (string) ($prize['conditionText'] ?? ''),
        'roleDurationLabel' => (string) ($prize['roleDurationLabel'] ?? ''),
        'roleDurationPoolLabel' => (string) ($prize['roleDurationPoolLabel'] ?? ($prize['roleDurationLabel'] ?? '')),
        'roleColor' => (string) ($prize['roleColor'] ?? ''),
        'roleIconUrl' => (string) ($prize['roleIconUrl'] ?? ''),
        'roleId' => (string) ($prize['roleId'] ?? ''),
        'roleTier' => (string) ($prize['roleTier'] ?? ''),
        'roleSeriesId' => (string) ($prize['roleSeriesId'] ?? ''),
        'roleSeriesName' => (string) ($prize['roleSeriesName'] ?? ''),
        'roleSeriesBadge' => (string) ($prize['roleSeriesBadge'] ?? ''),
        'permissionBadges' => array_values(array_filter($prize['permissionBadges'] ?? [], 'is_string')),
        'permissionCount' => (int) ($prize['permissionCount'] ?? 0),
        'permissionDetails' => array_values(array_filter($prize['permissionDetails'] ?? [], 'is_array')),
    ];
}

function gachaPrizeJsonAttr(array $payload): string
{
    return htmlspecialchars((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>Gachapon Prize Guide</title>
  <?php if ($gachaPrizeSwiperCss !== ''): ?>
  <style><?= $gachaPrizeSwiperCss ?></style>
  <?php else: ?>
  <link rel="stylesheet" href="vendor/swiper/swiper-bundle.min.css" />
  <?php endif; ?>
  <style>
    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-Light.woff2") format("woff2");
      font-weight: 300;
      font-style: normal;
      font-display: swap;
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
      src: url("fonts/FCVisionRounded-Medium.woff2") format("woff2");
      font-weight: 500;
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

    :root {
      --gacha-prize-font: "FC Vision Rounded", sans-serif;
      --gacha-prize-ink: #48315c;
      --gacha-prize-muted: rgba(72, 49, 92, 0.68);
      --gacha-prize-line: rgba(123, 94, 155, 0.16);
      --gacha-prize-paper: rgba(255, 255, 255, 0.84);
      --gacha-prize-paper-strong: rgba(255, 255, 255, 0.93);
      --gacha-prize-shadow: rgba(98, 71, 117, 0.12);
      --gacha-prize-shell-clearance: 132px;
      --gacha-prize-top-offset: 102px;
      --gacha-prize-anchor-offset: 204px;
      --gacha-prize-bottom-clearance: 420px;
      --gacha-prize-dock-background: transparent;
      --gacha-prize-dock-overlay-opacity: 0;
      scroll-behavior: smooth;
      scroll-padding-top: var(--gacha-prize-anchor-offset);
    }

    * {
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
    }

    html,
    body {
      margin: 0;
      min-height: 100%;
      font-family: var(--gacha-prize-font);
      color: var(--gacha-prize-ink);
      background:
        radial-gradient(circle at top, rgba(255, 237, 244, 0.94), rgba(255, 255, 255, 0) 32%),
        linear-gradient(180deg, rgba(255, 247, 251, 0.98) 0%, rgba(247, 245, 255, 0.98) 48%, rgba(238, 248, 255, 0.99) 100%),
        repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.26) 0 18px, rgba(255, 226, 237, 0.16) 18px 36px);
    }

    body {
      overflow-x: hidden;
    }

    button,
    a {
      font: inherit;
    }

    img {
      display: block;
      max-width: 100%;
    }

    .gacha-prize-page {
      min-height: 100svh;
      padding: calc(env(safe-area-inset-top, 0px) + 124px) 16px calc(env(safe-area-inset-bottom, 0px) + var(--gacha-prize-bottom-clearance));
      position: relative;
      isolation: isolate;
    }

    .gacha-prize-page.is-embed {
      padding-top: calc(env(safe-area-inset-top, 0px) + var(--gacha-prize-shell-clearance));
    }

    .gacha-prize-intro {
      position: relative;
      z-index: 1;
      display: grid;
      gap: 12px;
      margin-bottom: 18px;
      padding: 18px 18px 17px;
      border: 1px solid var(--gacha-prize-line);
      border-radius: 28px;
      background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(255, 247, 234, 0.86)),
        linear-gradient(90deg, rgba(255, 215, 229, 0.14), rgba(201, 238, 255, 0.14));
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.92),
        0 18px 30px rgba(98, 71, 117, 0.09);
    }

    .gacha-prize-page.is-embed .gacha-prize-intro {
      background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.97), rgba(255, 247, 234, 0.95)),
        linear-gradient(90deg, rgba(255, 215, 229, 0.08), rgba(201, 238, 255, 0.08));
    }

    .gacha-prize-kicker {
      width: fit-content;
      padding: 7px 11px;
      border-radius: 999px;
      background: rgba(255, 236, 202, 0.94);
      color: #92622e;
      font-size: 12px;
      font-weight: 500;
      letter-spacing: 0.01em;
    }

    .gacha-prize-intro h1 {
      margin: 0;
      font-size: 27px;
      line-height: 1.14;
      font-weight: 600;
      color: #4b3161;
    }

    .gacha-prize-intro p {
      margin: 0;
      color: var(--gacha-prize-muted);
      font-size: 13px;
      line-height: 1.65;
      font-weight: 400;
    }

    .gacha-prize-tier-dock {
      position: sticky;
      top: calc(env(safe-area-inset-top, 0px) + var(--gacha-prize-top-offset));
      z-index: 20;
      margin: 0 -2px 16px;
      padding: 5px 6px 7px;
      border-radius: 0 0 20px 20px;
      border: 1px solid rgba(255, 255, 255, 0.72);
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(255, 252, 255, 0.84)),
        linear-gradient(90deg, rgba(255, 221, 235, 0.16), rgba(216, 245, 255, 0.16));
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.88),
        0 10px 18px rgba(98, 71, 117, 0.08);
      overflow: hidden;
      isolation: isolate;
    }

    .gacha-prize-tier-dock::before {
      content: "";
      position: absolute;
      inset: 0;
      background: var(--gacha-prize-dock-background);
      opacity: var(--gacha-prize-dock-overlay-opacity);
      transition: opacity 360ms ease;
      pointer-events: none;
      z-index: 0;
    }

    .gacha-prize-tier-dock-wipe {
      position: absolute;
      inset: 0;
      z-index: 0;
      opacity: 0;
      pointer-events: none;
      background: var(--gacha-prize-dock-background);
      filter: blur(12px) saturate(1.05);
      transform-origin: left center;
    }

    @keyframes gachaPrizeDockWipe {
      0% {
        opacity: 0;
        clip-path: inset(0 100% 0 0 round 0 0 20px 20px);
        transform: translateX(-12%) scaleX(0.74);
      }

      24% {
        opacity: 0.28;
      }

      72% {
        opacity: 0.16;
        transform: translateX(-2%) scaleX(0.98);
      }

      100% {
        opacity: 0;
        clip-path: inset(0 0 0 0 round 0 0 20px 20px);
        transform: translateX(0) scaleX(1);
      }
    }

    .gacha-prize-tier-rail {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 6px;
    }

    .gacha-prize-tier-link {
      position: relative;
      display: grid;
      justify-items: center;
      align-content: center;
      gap: 3px;
      min-height: 62px;
      padding: 8px 4px 7px;
      border: 1px solid rgba(120, 84, 145, 0.12);
      border-radius: 15px;
      background: rgba(255, 255, 255, 0.72);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.84),
        0 6px 12px rgba(92, 58, 111, 0.05);
      color: var(--gacha-prize-ink);
      text-decoration: none;
      transition:
        transform 240ms ease,
        border-color 240ms ease,
        box-shadow 240ms ease,
        background 240ms ease;
    }

    .gacha-prize-tier-link::after {
      content: "";
      position: absolute;
      left: 12px;
      right: 12px;
      bottom: 4px;
      height: 3px;
      border-radius: 999px;
      background: rgba(221, 220, 230, 0.98);
      box-shadow: 0 0 3px rgba(184, 178, 198, 0.1);
    }

    .gacha-prize-tier-link.is-current {
      transform: translateY(-1px);
      border-color: color-mix(in srgb, var(--tier-accent) 36%, rgba(120, 84, 145, 0.16));
      background: linear-gradient(180deg, color-mix(in srgb, var(--tier-soft) 40%, white), rgba(255, 255, 255, 0.88));
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 8px 14px rgba(92, 58, 111, 0.08);
    }

    .gacha-prize-tier-link.is-current::after {
      background: var(--tier-accent);
      box-shadow:
        0 0 12px color-mix(in srgb, var(--tier-accent) 36%, transparent),
        0 0 5px color-mix(in srgb, var(--tier-accent) 18%, white);
    }

    .gacha-prize-tier-link.is-special {
      border-color: transparent;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(255, 255, 255, 0.76)) padding-box,
        conic-gradient(from 22deg, #ff86d8, #ffe26d, #78efb3, #78d9ff, #b88dff, #ff86d8) border-box;
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 7px 13px rgba(106, 76, 144, 0.1);
    }

    .gacha-prize-tier-link img {
      width: 22px;
      height: 22px;
      object-fit: contain;
      filter: drop-shadow(0 5px 6px rgba(63, 44, 82, 0.14));
    }

    .gacha-prize-tier-name {
      font-size: 11px;
      font-weight: 500;
      color: #4a305f;
      white-space: nowrap;
    }

    .gacha-prize-tier-rate {
      font-size: 10px;
      font-weight: 400;
      color: color-mix(in srgb, var(--tier-deep) 82%, #5a4f68);
      white-space: nowrap;
    }

    .gacha-prize-tier-stack {
      display: grid;
      gap: 44px;
    }

    .gacha-prize-tier-section {
      scroll-margin-top: var(--gacha-prize-anchor-offset);
      --tier-accent: #8bd6ff;
      --tier-soft: #eef8ff;
      --tier-deep: #335f9a;
    }

    .gacha-prize-tier-panel {
      position: relative;
      overflow: hidden;
      border: 1px solid var(--gacha-prize-line);
      border-radius: 30px;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.8), rgba(255, 250, 255, 0.5)),
        linear-gradient(135deg, var(--tier-soft), rgba(255, 255, 255, 0.22));
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 18px 32px rgba(98, 71, 117, 0.1);
    }

    .gacha-prize-tier-section.is-special .gacha-prize-tier-panel {
      border: 2px solid transparent;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(255, 252, 255, 0.54)) padding-box,
        conic-gradient(from 35deg, #ff84d6, #ffe36d, #7ef0b6, #76d8ff, #b98cff, #ff84d6) border-box;
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.92),
        0 22px 40px rgba(98, 71, 117, 0.14),
        0 0 24px rgba(255, 219, 122, 0.16);
    }

    .gacha-prize-tier-panel::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        linear-gradient(90deg, color-mix(in srgb, var(--tier-accent) 14%, transparent), transparent 56%),
        radial-gradient(circle at 84% 12%, color-mix(in srgb, var(--tier-accent) 18%, transparent), transparent 30%);
      pointer-events: none;
    }

    .gacha-prize-tier-head {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: 92px minmax(0, 1fr);
      gap: 15px;
      align-items: center;
      padding: 18px 16px 13px;
    }

    .gacha-prize-tier-ball {
      display: grid;
      place-items: center;
      width: 92px;
      height: 92px;
      border-radius: 28px;
      background:
        radial-gradient(circle at 48% 34%, rgba(255, 255, 255, 0.96), var(--tier-soft) 64%, color-mix(in srgb, var(--tier-accent) 34%, white) 100%);
      box-shadow:
        inset 0 2px 0 rgba(255, 255, 255, 0.88),
        0 15px 22px rgba(64, 45, 80, 0.13);
    }

    .gacha-prize-tier-ball img {
      width: 74px;
      height: 74px;
      object-fit: contain;
      filter: drop-shadow(0 10px 10px rgba(64, 45, 80, 0.16));
    }

    .gacha-prize-tier-copy {
      min-width: 0;
      display: grid;
      gap: 8px;
    }

    .gacha-prize-tier-copy h2 {
      margin: 0;
      font-size: 24px;
      line-height: 1.14;
      font-weight: 600;
      color: #4c3261;
    }

    .gacha-prize-tier-copy p {
      margin: 0;
      color: var(--gacha-prize-muted);
      font-size: 13px;
      line-height: 1.62;
      font-weight: 400;
    }

    .gacha-prize-tier-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .gacha-prize-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      min-height: 29px;
      padding: 0 10px;
      border: 1px solid rgba(255, 255, 255, 0.76);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.74);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.86),
        0 7px 12px rgba(84, 54, 110, 0.08);
      font-size: 12px;
      font-weight: 500;
      white-space: nowrap;
      color: var(--tier-deep);
    }

    .gacha-prize-pill.is-accent {
      background:
        linear-gradient(
          180deg,
          color-mix(in srgb, var(--tier-soft, rgba(255, 248, 222, 0.96)) 84%, white),
          color-mix(in srgb, var(--tier-accent, #f5bd45) 22%, white)
        );
      border-color: color-mix(in srgb, var(--tier-accent, #f5bd45) 36%, rgba(255, 255, 255, 0.94));
      color: var(--tier-deep);
    }

    .gacha-prize-section {
      position: relative;
      z-index: 1;
      display: grid;
      gap: 14px;
      padding: 0 14px 20px;
    }

    .gacha-prize-section-title {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      align-items: end;
      gap: 12px;
      padding: 0 2px;
    }

    .gacha-prize-section-title h3 {
      margin: 0;
      font-size: 18px;
      line-height: 1.16;
      font-weight: 500;
      color: #49305f;
    }

    .gacha-prize-role-title-row {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      min-width: 0;
    }

    .gacha-prize-role-title-row h3 {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .gacha-prize-role-view-toggle {
      width: 26px;
      height: 26px;
      display: inline-grid;
      place-items: center;
      border: 0;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.72);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.94),
        0 7px 10px rgba(98, 71, 117, 0.08);
      color: rgba(74, 49, 95, 0.72);
      font-size: 11px;
      cursor: pointer;
      transition: transform 160ms ease, color 160ms ease, box-shadow 160ms ease, background 160ms ease;
    }

    .gacha-prize-role-view-toggle svg {
      width: 13px;
      height: 13px;
      display: block;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      vector-effect: non-scaling-stroke;
    }

    .gacha-prize-role-view-toggle:hover {
      color: #4a315f;
      transform: translateY(-1px);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.94),
        0 9px 12px rgba(98, 71, 117, 0.1);
    }

    .gacha-prize-role-viewer {
      display: grid;
      gap: 14px;
    }

    .gacha-prize-role-viewer[data-view="slider"] [data-gacha-role-list] {
      display: none;
    }

    .gacha-prize-role-viewer[data-view="list"] [data-gacha-role-slider] {
      display: none;
    }

    .gacha-prize-role-slider-shell {
      position: relative;
      min-width: 0;
      padding: 2px 12px 6px;
    }

    .gacha-prize-role-slider-shell::before,
    .gacha-prize-role-slider-shell::after {
      position: absolute;
      top: 50%;
      z-index: 3;
      transform: translateY(-50%);
      width: 14px;
      height: 14px;
      display: grid;
      place-items: center;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.88);
      box-shadow: 0 5px 10px rgba(98, 71, 117, 0.1);
      color: rgba(74, 49, 95, 0.52);
      font-size: 8px;
      line-height: 1;
      opacity: 0;
      pointer-events: none;
      transition: opacity 160ms ease;
    }

    .gacha-prize-role-slider-shell.is-scrollable::before,
    .gacha-prize-role-slider-shell.is-scrollable::after {
      opacity: 1;
    }

    .gacha-prize-role-slider-shell::before {
      content: "‹";
      left: -9px;
    }

    .gacha-prize-role-slider-shell::after {
      content: "›";
      right: -9px;
    }

    .gacha-prize-role-swiper {
      overflow-x: auto;
      overflow-y: visible;
      padding-bottom: 3px;
      scroll-behavior: smooth;
      scroll-snap-type: x proximity;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      -ms-overflow-style: none;
    }

    .gacha-prize-role-swiper::-webkit-scrollbar {
      display: none;
    }

    .gacha-prize-role-swiper .swiper-wrapper {
      display: flex;
      align-items: stretch;
      width: max-content;
      min-width: 100%;
    }

    .gacha-prize-role-swiper .swiper-slide {
      flex: 0 0 auto;
      width: min(236px, calc(100vw - 72px));
      height: auto;
      scroll-snap-align: start;
    }

    .gacha-prize-role-swiper .swiper-slide.is-role-group-start {
      margin-left: 8px;
    }

    .gacha-prize-role-card {
      width: 100%;
      min-height: 64px;
      display: grid;
      grid-template-columns: 38px minmax(0, 1fr);
      align-items: center;
      gap: 8px;
      padding: 7px 9px;
      border: 1px solid rgba(132, 98, 162, 0.14);
      border-radius: 18px;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(255, 249, 255, 0.92));
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.92),
        0 12px 20px rgba(98, 71, 117, 0.08);
      color: inherit;
      text-align: left;
      cursor: pointer;
      transition: box-shadow 180ms ease, border-color 180ms ease;
      touch-action: pan-y;
    }

    .gacha-prize-role-card:hover {
      border-color: color-mix(in srgb, var(--tier-accent, #f5bd45) 24%, rgba(132, 98, 162, 0.16));
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.94),
        0 13px 18px rgba(98, 71, 117, 0.1);
    }

    .gacha-prize-role-card-hero {
      position: relative;
      display: grid;
      place-items: center;
      width: 38px;
      min-height: 38px;
      padding: 0;
      background: transparent;
      overflow: visible;
    }

    .gacha-prize-role-card-hero img {
      width: 100%;
      height: 38px;
      object-fit: contain;
      filter: drop-shadow(0 5px 8px rgba(95, 65, 110, 0.14));
    }

    .gacha-prize-role-card-status,
    .gacha-prize-role-card-coin {
      position: absolute;
      bottom: 6px;
      z-index: 2;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 22px;
      border-radius: 999px;
      box-shadow: 0 8px 12px rgba(90, 62, 104, 0.12);
      pointer-events: none;
    }

    .gacha-prize-role-card-status {
      right: -1px;
      width: 17px;
      height: 17px;
      background: rgba(255, 245, 214, 0.94);
    }

    .gacha-prize-role-card-status img {
      width: 10px;
      height: 10px;
      object-fit: contain;
    }

    .gacha-prize-role-card-coin {
      left: -1px;
      gap: 4px;
      padding: 0 6px;
      background: linear-gradient(180deg, rgba(255, 236, 168, 0.98), rgba(240, 176, 67, 0.96));
      color: #744719;
      font-size: 9px;
      font-weight: 600;
    }

    .gacha-prize-role-card-coin::before {
      content: "P";
      display: grid;
      place-items: center;
      width: 14px;
      height: 14px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.56);
      font-size: 8px;
      font-weight: 700;
    }

    .gacha-prize-role-card-copy {
      min-width: 0;
      display: grid;
      gap: 3px;
      align-content: center;
    }

    .gacha-prize-role-card-copy strong {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      min-width: 0;
      font-size: 12px;
      line-height: 1.16;
      font-weight: 600;
      color: #4a315f;
    }

    .gacha-prize-role-card-line {
      display: none;
      font-size: 9px;
      line-height: 1.24;
      color: rgba(74, 49, 95, 0.66);
      overflow-wrap: anywhere;
    }

    .gacha-prize-role-name-text {
      min-width: 0;
      font-family: var(--gacha-prize-font);
      font-size: 1.16em;
      line-height: 1.08;
      font-weight: 700;
      font-style: normal;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .gacha-prize-role-card-tags,
    .gacha-prize-role-card-permissions {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      column-gap: 6px;
      row-gap: 4px;
      min-width: 0;
    }

    .gacha-prize-section-title em {
      max-width: 360px;
      font-size: 12px;
      line-height: 1.3;
      color: rgba(73, 48, 95, 0.62);
      font-style: italic;
      text-align: right;
    }

    .gacha-prize-shelf-stack {
      display: grid;
      gap: 22px;
    }

    .gacha-prize-shelf {
      position: relative;
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
      align-items: end;
      min-height: 232px;
      padding: 14px 6px 20px;
    }

    .gacha-prize-shelf::before {
      content: "";
      position: absolute;
      left: 2px;
      right: 2px;
      bottom: 24px;
      height: 30px;
      border-radius: 999px;
      background:
        linear-gradient(180deg, rgba(255, 230, 178, 0.92), rgba(221, 151, 91, 0.96));
      box-shadow:
        inset 0 2px 0 rgba(255, 255, 255, 0.54),
        inset 0 -5px 0 rgba(151, 88, 56, 0.16),
        0 14px 18px rgba(109, 63, 65, 0.18);
    }

    .gacha-prize-shelf::after {
      content: "";
      position: absolute;
      left: 18px;
      right: 18px;
      bottom: 12px;
      height: 14px;
      border-radius: 999px;
      background: rgba(114, 63, 67, 0.15);
      filter: blur(8px);
    }

    .gacha-prize-shelf-item {
      position: relative;
      display: grid;
      justify-items: center;
      align-content: end;
      gap: 0;
      min-height: 188px;
      padding: 0 4px 8px;
      border: 0;
      background: transparent;
      cursor: pointer;
    }

    .gacha-prize-shelf-item:focus-visible,
    .gacha-prize-list-row:focus-visible,
    .gacha-prize-modal-close:focus-visible {
      outline: 2px solid color-mix(in srgb, var(--tier-accent) 76%, white);
      outline-offset: 2px;
    }

    .gacha-prize-shelf-visual {
      display: grid;
      place-items: end center;
      min-height: 148px;
      width: 100%;
      padding-bottom: 4px;
    }

    .gacha-prize-shelf-figure {
      position: relative;
      display: grid;
      place-items: end center;
      width: min(100%, 124px);
      height: 140px;
      min-height: 140px;
      align-self: end;
    }

    .gacha-prize-shelf-figure > img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      filter: drop-shadow(0 14px 12px rgba(75, 43, 94, 0.16));
      z-index: 1;
    }

    .gacha-prize-status-icon {
      position: absolute;
      right: 2px;
      bottom: 36px;
      width: 32px;
      height: 32px;
      border-radius: 999px;
      display: grid;
      place-items: center;
      background: rgba(255, 250, 255, 0.92);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.92),
        0 8px 10px rgba(88, 62, 105, 0.1);
      z-index: 4;
    }

    .gacha-prize-status-icon img {
      width: 24px;
      height: 24px;
      object-fit: contain;
    }

    .gacha-prize-coin-price {
      position: absolute;
      right: -2px;
      bottom: -2px;
      z-index: 5;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      min-width: 42px;
      min-height: 26px;
      padding: 3px 8px 3px 4px;
      border-radius: 999px;
      background: linear-gradient(180deg, rgba(255, 236, 168, 0.98), rgba(240, 176, 67, 0.96));
      border: 1px solid rgba(255, 255, 255, 0.78);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.74),
        0 8px 13px rgba(105, 70, 26, 0.16);
      color: #744719;
      font-size: 10px;
      font-weight: 600;
      line-height: 1;
      white-space: nowrap;
      pointer-events: none;
    }

    .gacha-prize-coin-price::before {
      content: "P";
      display: grid;
      place-items: center;
      width: 18px;
      height: 18px;
      border-radius: 999px;
      background: linear-gradient(180deg, #ffe894, #db8f27);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.65),
        inset 0 -2px 0 rgba(122, 72, 18, 0.18);
      color: #8a501c;
      font-size: 10px;
      font-weight: 700;
    }

    .gacha-prize-badge-stack {
      position: absolute;
      top: -4px;
      left: 50%;
      display: grid;
      gap: 4px;
      justify-items: center;
      transform: translate(-50%, -62%);
      width: min(100%, 154px);
      pointer-events: none;
      z-index: 3;
    }

    .gacha-prize-tag {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 24px;
      max-width: 100%;
      padding: 2px 8px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.88);
      border: 1px solid rgba(255, 255, 255, 0.92);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 8px 14px rgba(87, 59, 106, 0.08);
      font-size: 11px;
      font-weight: 500;
      color: #61437a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .gacha-prize-tag.is-tier,
    .gacha-prize-inline-badge.is-tier {
      background:
        linear-gradient(
          180deg,
          var(--gacha-badge-soft, rgba(255, 235, 194, 0.94)),
          color-mix(in srgb, var(--gacha-badge-accent, #f5bd45) 22%, rgba(255, 255, 255, 0.95))
        );
      color: var(--gacha-badge-deep, #8b5c26);
      border-color: color-mix(in srgb, var(--gacha-badge-accent, #f5bd45) 34%, rgba(255, 255, 255, 0.96));
    }

    .gacha-prize-tag.is-warm {
      background: linear-gradient(180deg, rgba(255, 236, 193, 0.98), rgba(255, 213, 134, 0.95));
      color: #8c5b26;
    }

    .gacha-prize-tag.is-countdown {
      background: linear-gradient(180deg, rgba(255, 234, 188, 0.98), rgba(255, 202, 118, 0.95));
      color: #87531f;
    }

    .gacha-prize-tag.is-quantity {
      background: rgba(255, 255, 255, 0.84);
      color: rgba(74, 49, 95, 0.7);
    }

    .gacha-prize-tag.is-neutral {
      background: rgba(125, 115, 145, 0.12);
      color: rgba(74, 49, 95, 0.74);
    }

    .gacha-prize-shelf-name {
      position: relative;
      z-index: 2;
      width: min(100%, 138px);
      margin-top: -7px;
      display: grid;
      align-items: center;
      justify-content: center;
      min-height: 34px;
      padding: 4px 12px 5px;
      border-radius: 17px;
      border: 1px solid rgba(255, 255, 255, 0.74);
      background:
        linear-gradient(180deg, rgba(255, 240, 214, 0.98), rgba(244, 202, 127, 0.98));
      box-shadow:
        inset 0 2px 0 rgba(255, 250, 236, 0.76),
        0 8px 12px rgba(133, 82, 51, 0.12);
      color: #694323;
      font-size: 12px;
      font-weight: 500;
      line-height: 1.18;
      text-align: center;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .gacha-prize-list-frame {
      border: 1px solid rgba(132, 98, 162, 0.14);
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.76);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 14px 24px rgba(98, 71, 117, 0.08);
      overflow: hidden;
    }

    .gacha-prize-role-series-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 13px 15px 10px;
      border-bottom: 1px solid rgba(132, 98, 162, 0.12);
      background: rgba(255, 255, 255, 0.34);
    }

    .gacha-prize-role-series-head strong {
      font-size: 13px;
      color: #4a315f;
    }

    .gacha-prize-list-row {
      position: relative;
      width: 100%;
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
      gap: 12px;
      align-items: center;
      padding: 13px 15px;
      border: 0;
      border-top: 1px solid rgba(132, 98, 162, 0.12);
      background: transparent;
      color: inherit;
      text-align: left;
      cursor: pointer;
    }

    .gacha-prize-list-row:first-child {
      border-top: 0;
    }

    .gacha-prize-list-row:hover {
      background: rgba(255, 255, 255, 0.28);
    }

    .gacha-prize-list-row.is-role-group-start {
      margin-top: 10px;
      padding-top: 18px;
    }

    .gacha-prize-list-row.is-role-group-start::before {
      content: "";
      position: absolute;
      left: 15px;
      right: 15px;
      top: 0;
      border-top: 1px dashed rgba(132, 98, 162, 0.18);
      pointer-events: none;
    }

    .gacha-prize-list-icon {
      position: relative;
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      flex: 0 0 auto;
    }

    .gacha-prize-list-icon img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      filter: drop-shadow(0 8px 10px rgba(95, 65, 110, 0.12));
    }

    .gacha-prize-list-copy {
      min-width: 0;
      display: grid;
      gap: 4px;
      align-content: center;
    }

    .gacha-prize-list-copy strong {
      min-width: 0;
      font-size: 13px;
      line-height: 1.3;
      font-weight: 500;
      color: #4a315f;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px;
    }

    .gacha-prize-list-copy span {
      min-width: 0;
      font-size: 11px;
      line-height: 1.35;
      font-weight: 400;
      color: rgba(74, 49, 95, 0.7);
      font-style: normal;
    }

    .gacha-prize-list-copy span:empty {
      display: none;
    }

    .gacha-prize-inline-badge-row {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .gacha-prize-inline-badge {
      display: inline-flex;
      align-items: center;
      min-height: 20px;
      padding: 0 7px;
      border-radius: 999px;
      font-size: 10px;
      line-height: 1;
      font-style: normal;
      white-space: nowrap;
    }

    .gacha-prize-tag.is-duration,
    .gacha-prize-inline-badge.is-duration {
      background:
        linear-gradient(
          180deg,
          rgba(255, 248, 252, 0.98),
          rgba(255, 218, 238, 0.97) 54%,
          rgba(255, 224, 184, 0.95)
        );
      border: 1px solid rgba(255, 255, 255, 0.92);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 8px 12px rgba(118, 76, 126, 0.08);
      color: #8a4976;
      font-weight: 600;
    }

    .gacha-prize-inline-badge.is-warm {
      background: linear-gradient(180deg, rgba(255, 236, 193, 0.98), rgba(255, 213, 134, 0.95));
      color: #8c5b26;
    }

    .gacha-prize-inline-badge.is-quantity {
      background: rgba(255, 255, 255, 0.84);
      color: rgba(74, 49, 95, 0.72);
    }

    .gacha-prize-inline-badge.is-series {
      background: rgba(125, 115, 145, 0.12);
      color: rgba(74, 49, 95, 0.8);
    }

    .gacha-prize-inline-badge.is-neutral {
      background: rgba(125, 115, 145, 0.12);
      color: rgba(74, 49, 95, 0.74);
    }

    .gacha-prize-inline-badge.is-permission-after-meta {
      margin-left: 4px;
    }

    .gacha-prize-empty {
      padding: 18px;
      border: 1px dashed rgba(133, 102, 160, 0.18);
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.52);
      color: rgba(74, 49, 95, 0.58);
      font-size: 13px;
      font-style: italic;
      text-align: center;
    }

    .gacha-prize-modal {
      position: fixed;
      inset: 0;
      z-index: 50;
      display: grid;
      place-items: center;
      padding: 22px;
    }

    .gacha-prize-modal.is-hidden {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }

    .gacha-prize-modal-backdrop {
      position: absolute;
      inset: 0;
      border: 0;
      background: rgba(255, 255, 255, 0.72);
      cursor: pointer;
    }

    .gacha-prize-modal-card {
      position: relative;
      z-index: 1;
      width: min(468px, calc(100vw - 26px));
      max-height: min(70svh, 620px);
      display: grid;
      grid-template-rows: auto minmax(0, 1fr);
      border: 1px solid rgba(136, 102, 168, 0.18);
      border-radius: 24px;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(255, 249, 255, 0.95));
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.94),
        0 24px 48px rgba(98, 71, 117, 0.16);
      overflow: hidden;
    }

    .gacha-prize-modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 18px 18px 12px;
      border-bottom: 1px solid rgba(134, 100, 165, 0.12);
    }

    .gacha-prize-modal-head strong {
      font-size: 20px;
      line-height: 1.2;
      font-weight: 500;
      color: #4a315f;
    }

    .gacha-prize-modal-close {
      width: 42px;
      height: 42px;
      border: 0;
      border-radius: 16px;
      background: linear-gradient(180deg, rgba(255, 236, 242, 0.96), rgba(244, 200, 218, 0.96));
      box-shadow:
        inset 0 2px 0 rgba(255, 255, 255, 0.76),
        0 10px 16px rgba(112, 76, 120, 0.12);
      color: #8b4d69;
      font-size: 22px;
      line-height: 1;
      cursor: pointer;
    }

    .gacha-prize-modal-body {
      overflow: auto;
      padding: 14px 16px 16px;
      display: grid;
      gap: 12px;
    }

    .gacha-prize-modal-hero {
      position: relative;
      display: grid;
      place-items: center;
      min-height: 148px;
      padding: 6px 12px 8px;
      overflow: visible;
    }

    .gacha-prize-modal-hero img {
      width: min(152px, 48vw);
      height: 132px;
      object-fit: contain;
      filter: drop-shadow(0 16px 14px rgba(90, 62, 104, 0.16));
    }

    .gacha-prize-modal-hero.is-role-preview > img {
      display: none;
    }

    .gacha-prize-modal-role-preview {
      --gacha-prize-role-preview-height: 56px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      min-width: 0;
      max-width: 100%;
      min-height: var(--gacha-prize-role-preview-height);
      padding: 0 16px;
      border-radius: 12px;
      background: #f5f7fa;
    }

    .gacha-prize-modal-role-preview[hidden] {
      display: none;
    }

    .gacha-prize-modal-role-preview-dot {
      width: calc(var(--gacha-prize-role-preview-height) * 0.54);
      height: calc(var(--gacha-prize-role-preview-height) * 0.54);
      border-radius: 999px;
      background: #8b91a1;
      flex: 0 0 auto;
    }

    .gacha-prize-modal-role-preview-icon {
      width: 28px;
      height: 28px;
      display: inline-grid;
      place-items: center;
      flex: 0 0 auto;
    }

    .gacha-prize-modal-role-preview-icon[hidden] {
      display: none;
    }

    .gacha-prize-modal-role-preview-icon img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      filter: drop-shadow(0 8px 10px rgba(90, 62, 104, 0.16));
    }

    .gacha-prize-modal-role-preview-name {
      min-width: 0;
      font-family: var(--gacha-prize-font);
      font-size: clamp(24px, 3.4vw, 30px);
      line-height: 1.04;
      font-weight: 700;
      color: #111111;
      text-align: center;
      overflow-wrap: anywhere;
    }

    .gacha-prize-modal-status {
      position: absolute;
      right: 8px;
      bottom: 8px;
      display: none;
      align-items: center;
      justify-content: center;
      width: 42px;
      height: 42px;
      border-radius: 999px;
      background: rgba(255, 245, 214, 0.94);
      box-shadow: 0 12px 20px rgba(90, 62, 104, 0.14);
    }

    .gacha-prize-modal-status.is-visible {
      display: inline-flex;
    }

    .gacha-prize-modal-status img {
      width: 28px;
      height: 28px;
      object-fit: contain;
    }

    .gacha-prize-modal-coin {
      position: absolute;
      left: 8px;
      bottom: 8px;
      display: none;
      align-items: center;
      gap: 6px;
      min-height: 30px;
      padding: 0 10px;
      border-radius: 999px;
      background: linear-gradient(180deg, rgba(255, 236, 168, 0.98), rgba(240, 176, 67, 0.96));
      box-shadow: 0 10px 16px rgba(105, 70, 26, 0.16);
      color: #744719;
      font-size: 12px;
      font-weight: 600;
    }

    .gacha-prize-modal-coin.is-visible {
      display: inline-flex;
    }

    .gacha-prize-modal-coin span {
      display: grid;
      place-items: center;
      width: 18px;
      height: 18px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.56);
      font-size: 10px;
    }

    .gacha-prize-modal-title {
      display: grid;
      gap: 4px;
    }

    .gacha-prize-modal-title strong {
      font-size: 35px;
      line-height: 1.1;
      font-weight: 700;
      color: #111111;
    }

    .gacha-prize-detail-name-text {
      display: inline-block;
      font-family: var(--gacha-prize-font);
      font-style: normal;
    }

    .gacha-prize-detail-name-text.is-gradient {
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      -webkit-text-fill-color: transparent;
    }

    .gacha-prize-modal-title span {
      font-size: 13px;
      line-height: 1.45;
      color: rgba(74, 49, 95, 0.72);
      font-style: italic;
    }

    .gacha-prize-modal-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .gacha-prize-modal-facts {
      display: grid;
      gap: 8px;
    }

    .gacha-prize-modal-fact {
      display: grid;
      grid-template-columns: 118px minmax(0, 1fr);
      gap: 10px;
      padding: 8px 10px;
      border-bottom: 1px solid rgba(134, 100, 165, 0.12);
      color: rgba(74, 49, 95, 0.82);
      font-size: 12px;
      line-height: 1.45;
    }

    .gacha-prize-modal-fact strong {
      font-weight: 500;
      color: #4a315f;
    }

    .gacha-prize-modal-desc {
      color: rgba(70, 48, 90, 0.86);
      font-size: 13px;
      line-height: 1.7;
    }

    .gacha-prize-modal-desc p {
      margin: 0 0 8px;
    }

    .gacha-prize-modal-desc p:last-child {
      margin-bottom: 0;
    }

    .gacha-prize-modal-block {
      display: grid;
      gap: 10px;
      padding: 12px;
      border: 1px solid rgba(134, 100, 165, 0.12);
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.7);
    }

    .gacha-prize-modal-block-title {
      font-size: 13px;
      font-weight: 600;
      color: #4a315f;
    }

    .gacha-prize-modal-ability-list {
      display: grid;
      gap: 0;
    }

    .gacha-prize-modal-ability-item {
      display: grid;
      gap: 5px;
      padding: 10px 0;
      border-top: 1px solid rgba(134, 100, 165, 0.12);
    }

    .gacha-prize-modal-ability-item:first-child {
      padding-top: 0;
      border-top: 0;
    }

    .gacha-prize-modal-ability-item strong {
      font-size: 13px;
      line-height: 1.35;
      font-weight: 600;
      color: #4a315f;
    }

    .gacha-prize-modal-ability-item span {
      color: rgba(74, 49, 95, 0.72);
      font-size: 12px;
      line-height: 1.6;
    }

    @media (max-width: 720px) {
      :root {
        --gacha-prize-shell-clearance: 124px;
        --gacha-prize-top-offset: 94px;
        --gacha-prize-anchor-offset: 206px;
        --gacha-prize-bottom-clearance: 480px;
      }

      .gacha-prize-page {
        padding-left: 12px;
        padding-right: 12px;
      }

      .gacha-prize-page.is-embed {
        padding-top: calc(env(safe-area-inset-top, 0px) + var(--gacha-prize-shell-clearance));
      }

      .gacha-prize-tier-head {
        grid-template-columns: 76px minmax(0, 1fr);
      }

      .gacha-prize-tier-ball {
        width: 76px;
        height: 76px;
        border-radius: 24px;
      }

      .gacha-prize-tier-ball img {
        width: 62px;
        height: 62px;
      }

      .gacha-prize-tier-copy h2 {
        font-size: 21px;
      }

      .gacha-prize-shelf {
        gap: 8px;
        min-height: 220px;
        padding-left: 6px;
        padding-right: 6px;
        padding-bottom: 20px;
      }

      .gacha-prize-shelf-figure > img {
        width: 100%;
        height: 100%;
        max-width: 100%;
        max-height: 100%;
      }

      .gacha-prize-shelf-name {
        width: min(100%, 118px);
        min-height: 32px;
        font-size: 10.5px;
      }

      .gacha-prize-section-title {
        grid-template-columns: 1fr;
        gap: 6px;
      }

      .gacha-prize-section-title em {
        max-width: none;
        text-align: left;
      }

      .gacha-prize-modal {
        padding: 14px;
      }

      .gacha-prize-modal-card {
        width: min(100vw - 12px, 468px);
        max-height: min(68svh, 560px);
      }

      .gacha-prize-modal-hero {
        min-height: 138px;
        padding-bottom: 6px;
      }

      .gacha-prize-modal-hero img {
        width: min(144px, 64vw);
        height: 124px;
      }

      .gacha-prize-modal-fact {
        width: 100%;
        grid-template-columns: 96px minmax(0, 1fr);
      }
    }
  </style>
</head>
<body>
  <main class="gacha-prize-page<?= $isEmbed ? ' is-embed' : '' ?>">
    <nav id="gachaPrizeTierDock" class="gacha-prize-tier-dock" aria-label="เลือก tier กาชา">
      <div class="gacha-prize-tier-dock-wipe" aria-hidden="true"></div>
      <div class="gacha-prize-tier-rail">
        <?php foreach ($gachaPrizeTiers as $gachaPrizeTier): ?>
          <?php
            $gachaPrizeBackground = !empty($gachaPrizeTier['special'])
              ? 'linear-gradient(90deg, rgba(255, 145, 220, 0.22), rgba(255, 226, 111, 0.2), rgba(126, 240, 182, 0.2), rgba(118, 216, 255, 0.2), rgba(185, 140, 255, 0.2))'
              : 'linear-gradient(90deg, color-mix(in srgb, ' . ($gachaPrizeTier['soft'] ?? '#eef8ff') . ' 84%, white), rgba(255,255,255,0.42))';
          ?>
          <a
            class="gacha-prize-tier-link<?= !empty($gachaPrizeTier['special']) ? ' is-special' : '' ?>"
            href="#<?= gachaPrizeEsc($gachaPrizeTier['id']) ?>"
            data-gacha-prize-tier-link
            data-tier-id="<?= gachaPrizeEsc($gachaPrizeTier['id']) ?>"
            data-tier-background="<?= gachaPrizeEsc($gachaPrizeBackground) ?>"
            style="--tier-accent:<?= gachaPrizeEsc($gachaPrizeTier['accent'] ?? '#8bd6ff') ?>;--tier-soft:<?= gachaPrizeEsc($gachaPrizeTier['soft'] ?? '#eef8ff') ?>;--tier-deep:<?= gachaPrizeEsc($gachaPrizeTier['deep'] ?? '#335f9a') ?>;"
          >
            <img src="<?= gachaPrizeEsc(gachaPrizeAssetUrl((string) ($gachaPrizeTier['ball'] ?? 'ball_1.png'))) ?>" alt="" />
            <span class="gacha-prize-tier-name"><?= gachaPrizeEsc($gachaPrizeTier['tier'] ?? '') ?></span>
            <span class="gacha-prize-tier-rate"><?= gachaPrizeEsc($gachaPrizeTier['rate'] ?? '') ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </nav>

    <div class="gacha-prize-tier-stack">
      <?php foreach ($gachaPrizeTiers as $gachaPrizeTier): ?>
        <?php
          $gachaPrizeItems = array_values(array_filter($gachaPrizeTier['itemShelves'] ?? [], 'is_array'));
          $gachaPrizeItemList = array_values(array_filter($gachaPrizeTier['itemList'] ?? [], 'is_array'));
          $gachaPrizeRoleList = array_values(array_filter($gachaPrizeTier['roleList'] ?? [], 'is_array'));
        ?>
        <section
          id="<?= gachaPrizeEsc($gachaPrizeTier['id']) ?>"
          class="gacha-prize-tier-section<?= !empty($gachaPrizeTier['special']) ? ' is-special' : '' ?>"
          data-gacha-prize-tier-section
          data-tier-id="<?= gachaPrizeEsc($gachaPrizeTier['id']) ?>"
          data-tier-background="<?= gachaPrizeEsc(!empty($gachaPrizeTier['special']) ? 'linear-gradient(90deg, rgba(255, 145, 220, 0.22), rgba(255, 226, 111, 0.2), rgba(126, 240, 182, 0.2), rgba(118, 216, 255, 0.2), rgba(185, 140, 255, 0.2))' : 'linear-gradient(90deg, color-mix(in srgb, ' . ($gachaPrizeTier['soft'] ?? '#eef8ff') . ' 84%, white), rgba(255,255,255,0.42))') ?>"
          style="--tier-accent:<?= gachaPrizeEsc($gachaPrizeTier['accent'] ?? '#8bd6ff') ?>;--tier-soft:<?= gachaPrizeEsc($gachaPrizeTier['soft'] ?? '#eef8ff') ?>;--tier-deep:<?= gachaPrizeEsc($gachaPrizeTier['deep'] ?? '#335f9a') ?>;"
        >
          <article class="gacha-prize-tier-panel">
            <header class="gacha-prize-tier-head">
              <div class="gacha-prize-tier-ball">
                <img src="<?= gachaPrizeEsc(gachaPrizeAssetUrl((string) ($gachaPrizeTier['ball'] ?? 'ball_1.png'))) ?>" alt="" />
              </div>
              <div class="gacha-prize-tier-copy">
                <h2><?= gachaPrizeEsc(($gachaPrizeTier['tier'] ?? '') . ' · ' . ($gachaPrizeTier['name'] ?? '')) ?></h2>
                <div class="gacha-prize-tier-meta">
                  <span class="gacha-prize-pill is-accent">อัตราออก <?= gachaPrizeEsc($gachaPrizeTier['rate'] ?? '') ?></span>
                  <?php if (!empty($gachaPrizeTier['special'])): ?>
                    <span class="gacha-prize-pill">Mythic Highlight</span>
                  <?php endif; ?>
                </div>
                <p><?= gachaPrizeEsc($gachaPrizeTier['summary'] ?? '') ?></p>
              </div>
            </header>

            <?php if ($gachaPrizeItems): ?>
              <section class="gacha-prize-section" aria-label="Prize Items">
                <div class="gacha-prize-shelf-stack">
                  <?php foreach (array_chunk($gachaPrizeItems, 3) as $gachaPrizeShelfGroup): ?>
                    <div class="gacha-prize-shelf">
                      <?php foreach ($gachaPrizeShelfGroup as $gachaPrizeItem): ?>
                        <?php $gachaPrizePayload = gachaPrizePayload($gachaPrizeTier, $gachaPrizeItem); ?>
                        <button
                          class="gacha-prize-shelf-item"
                          type="button"
                          data-gacha-prize-open
                          data-gacha-prize="<?= gachaPrizeJsonAttr($gachaPrizePayload) ?>"
                        >
                          <span class="gacha-prize-shelf-visual">
                            <span class="gacha-prize-shelf-figure">
                              <span class="gacha-prize-badge-stack">
                                <?php if (!empty($gachaPrizeItem['badge'])): ?>
                                  <span class="gacha-prize-tag is-warm"><?= gachaPrizeEsc($gachaPrizeItem['badge']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($gachaPrizeItem['displayQuantityLabel'])): ?>
                                  <span class="gacha-prize-tag is-quantity"><?= gachaPrizeEsc($gachaPrizeItem['displayQuantityLabel']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($gachaPrizeItem['pickDate'])): ?>
                                  <span
                                    class="gacha-prize-tag is-countdown"
                                    data-gacha-prize-countdown
                                    data-pick-date="<?= gachaPrizeEsc((string) $gachaPrizeItem['pickDate']) ?>"
                                    data-pick-date-label="<?= gachaPrizeEsc((string) ($gachaPrizeItem['pickDateLabel'] ?? '')) ?>"
                                  ></span>
                                <?php endif; ?>
                              </span>
                              <img src="<?= gachaPrizeEsc(gachaPrizeAssetUrl((string) ($gachaPrizeItem['icon'] ?? 'item-1.png'))) ?>" alt="<?= gachaPrizeEsc($gachaPrizeItem['name'] ?? '') ?>" />
                              <?php if (!empty($gachaPrizeItem['statusIcon'])): ?>
                                <span class="gacha-prize-status-icon"><img src="<?= gachaPrizeEsc(gachaPrizeAssetUrl((string) $gachaPrizeItem['statusIcon'])) ?>" alt="" /></span>
                              <?php endif; ?>
                              <?php if ((int) ($gachaPrizeItem['coinPrice'] ?? 0) > 0): ?>
                                <span class="gacha-prize-coin-price"><?= gachaPrizeEsc(number_format((int) $gachaPrizeItem['coinPrice'])) ?></span>
                              <?php endif; ?>
                            </span>
                          </span>
                          <span class="gacha-prize-shelf-name"><?= gachaPrizeEsc($gachaPrizeItem['shortName'] ?? ($gachaPrizeItem['name'] ?? '')) ?></span>
                        </button>
                      <?php endforeach; ?>
                    </div>
                  <?php endforeach; ?>
                </div>

                <?php if ($gachaPrizeItemList): ?>
                  <div class="gacha-prize-list-frame">
                    <?php foreach ($gachaPrizeItemList as $gachaPrizeListRow): ?>
                      <?php $gachaPrizePayload = gachaPrizePayload($gachaPrizeTier, $gachaPrizeListRow); ?>
                      <button
                        class="gacha-prize-list-row"
                        type="button"
                        data-gacha-prize-open
                        data-gacha-prize="<?= gachaPrizeJsonAttr($gachaPrizePayload) ?>"
                      >
                        <span class="gacha-prize-list-icon">
                          <img src="<?= gachaPrizeEsc(gachaPrizeAssetUrl((string) ($gachaPrizeListRow['icon'] ?? 'item-1.png'))) ?>" alt="" />
                        </span>
                        <span class="gacha-prize-list-copy">
                          <strong><?= gachaPrizeEsc($gachaPrizeListRow['shortName'] ?? ($gachaPrizeListRow['name'] ?? '')) ?></strong>
                          <?php if (!empty($gachaPrizeListRow['line'])): ?>
                            <span><?= gachaPrizeEsc($gachaPrizeListRow['line']) ?></span>
                          <?php endif; ?>
                          <?php if (!empty($gachaPrizeListRow['badge']) || !empty($gachaPrizeListRow['displayQuantityLabel'])): ?>
                            <span class="gacha-prize-inline-badge-row">
                              <?php if (!empty($gachaPrizeListRow['badge'])): ?>
                                <span class="gacha-prize-inline-badge is-warm"><?= gachaPrizeEsc($gachaPrizeListRow['badge']) ?></span>
                              <?php endif; ?>
                              <?php if (!empty($gachaPrizeListRow['displayQuantityLabel'])): ?>
                                <span class="gacha-prize-inline-badge is-quantity"><?= gachaPrizeEsc($gachaPrizeListRow['displayQuantityLabel']) ?></span>
                              <?php endif; ?>
                            </span>
                          <?php endif; ?>
                        </span>
                      </button>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </section>
            <?php endif; ?>

            <?php if ($gachaPrizeRoleList): ?>
              <section class="gacha-prize-section" aria-labelledby="<?= gachaPrizeEsc($gachaPrizeTier['id']) ?>-roles-title">
                <div class="gacha-prize-role-viewer" data-gacha-role-viewer data-view="slider" data-default-view="slider">
                  <div class="gacha-prize-section-title">
                    <div class="gacha-prize-role-title-row">
                      <h3 id="<?= gachaPrizeEsc($gachaPrizeTier['id']) ?>-roles-title">รางวัลยศ</h3>
                      <button class="gacha-prize-role-view-toggle" type="button" data-gacha-role-view-toggle aria-label="สลับเป็นรายการ" title="สลับรูปแบบการแสดงรางวัลยศ">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                          <path d="M8 6h12"></path>
                          <path d="M8 12h12"></path>
                          <path d="M8 18h12"></path>
                          <path d="M4 6h.01"></path>
                          <path d="M4 12h.01"></path>
                          <path d="M4 18h.01"></path>
                        </svg>
                      </button>
                    </div>
                  </div>

                  <div class="gacha-prize-role-slider-shell" data-gacha-role-slider>
                    <div class="swiper gacha-prize-role-swiper">
                      <div class="swiper-wrapper">
                        <?php $gachaPrizePreviousRoleGroupId = null; ?>
                        <?php foreach ($gachaPrizeRoleList as $gachaPrizeRoleRow): ?>
                          <?php $gachaPrizePayload = gachaPrizePayload($gachaPrizeTier, $gachaPrizeRoleRow); ?>
                          <?php
                            $gachaPrizeRoleGroupId = (string) ($gachaPrizeRoleRow['roleConfigGroupId'] ?? '');
                            $gachaPrizeIsRoleGroupStart = $gachaPrizePreviousRoleGroupId !== null && $gachaPrizeRoleGroupId !== '' && $gachaPrizeRoleGroupId !== $gachaPrizePreviousRoleGroupId;
                            $gachaPrizePreviousRoleGroupId = $gachaPrizeRoleGroupId;
                          ?>
                          <div class="swiper-slide<?= $gachaPrizeIsRoleGroupStart ? ' is-role-group-start' : '' ?>">
                            <button
                              class="gacha-prize-role-card"
                              type="button"
                              data-gacha-prize-open
                              data-gacha-prize="<?= gachaPrizeJsonAttr($gachaPrizePayload) ?>"
                            >
                              <span class="gacha-prize-role-card-hero">
                                <img src="<?= gachaPrizeEsc(gachaPrizeAssetUrl((string) ($gachaPrizeRoleRow['icon'] ?? 'item-1.png'))) ?>" alt="<?= gachaPrizeEsc($gachaPrizeRoleRow['name'] ?? '') ?>" />
                                <?php if (!empty($gachaPrizeRoleRow['statusIcon'])): ?>
                                  <span class="gacha-prize-role-card-status"><img src="<?= gachaPrizeEsc(gachaPrizeAssetUrl((string) $gachaPrizeRoleRow['statusIcon'])) ?>" alt="" /></span>
                                <?php endif; ?>
                                <?php if ((int) ($gachaPrizeRoleRow['coinPrice'] ?? 0) > 0): ?>
                                  <span class="gacha-prize-role-card-coin"><?= gachaPrizeEsc(number_format((int) $gachaPrizeRoleRow['coinPrice'])) ?></span>
                                <?php endif; ?>
                              </span>
                              <span class="gacha-prize-role-card-copy">
                                <strong>
                                  <span
                                    class="gacha-prize-role-name-text"
                                    <?php if (!empty($gachaPrizeRoleRow['roleColor'])): ?>
                                      style="color: <?= gachaPrizeEsc((string) $gachaPrizeRoleRow['roleColor']) ?>"
                                    <?php endif; ?>
                                  ><?= gachaPrizeEsc($gachaPrizeRoleRow['shortName'] ?? ($gachaPrizeRoleRow['name'] ?? '')) ?></span>
                                  <?php $gachaPrizeDurationDisplay = (string) ($gachaPrizeRoleRow['roleDurationPoolLabel'] ?? $gachaPrizeRoleRow['roleDurationLabel'] ?? ''); ?>
                                  <?php if ($gachaPrizeDurationDisplay !== ''): ?>
                                    <span class="gacha-prize-inline-badge is-duration"><?= gachaPrizeEsc($gachaPrizeDurationDisplay) ?></span>
                                  <?php endif; ?>
                                </strong>
                                <?php if (!empty($gachaPrizeRoleRow['line'])): ?>
                                  <span class="gacha-prize-role-card-line"><?= gachaPrizeEsc($gachaPrizeRoleRow['line']) ?></span>
                                <?php endif; ?>
                                <?php
                                  $gachaPrizeRoleHasCompactMeta = !empty($gachaPrizeRoleRow['roleSeriesBadge']) || !empty($gachaPrizeRoleRow['roleSeriesName']);
                                  $gachaPrizeRoleCompactPermissionLimit = $gachaPrizeRoleHasCompactMeta ? 1 : 2;
                                ?>
                                <?php if ($gachaPrizeRoleHasCompactMeta || !empty($gachaPrizeRoleRow['permissionBadges'])): ?>
                                  <span class="gacha-prize-role-card-tags">
                                    <?php if (!empty($gachaPrizeRoleRow['roleSeriesBadge']) || !empty($gachaPrizeRoleRow['roleSeriesName'])): ?>
                                      <span class="gacha-prize-inline-badge is-series"><?= gachaPrizeEsc($gachaPrizeRoleRow['roleSeriesBadge'] ?: $gachaPrizeRoleRow['roleSeriesName']) ?></span>
                                    <?php endif; ?>
                                    <?php foreach (array_slice($gachaPrizeRoleRow['permissionBadges'] ?? [], 0, $gachaPrizeRoleCompactPermissionLimit) as $gachaPrizePermissionIndex => $gachaPrizePermissionBadge): ?>
                                      <span class="gacha-prize-inline-badge is-neutral<?= ($gachaPrizePermissionIndex === 0 && $gachaPrizeRoleHasCompactMeta) ? ' is-permission-after-meta' : '' ?>"><?= gachaPrizeEsc($gachaPrizePermissionBadge) ?></span>
                                    <?php endforeach; ?>
                                  </span>
                                <?php endif; ?>
                              </span>
                            </button>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>

                  <div class="gacha-prize-list-frame" data-gacha-role-list>
                    <?php $gachaPrizePreviousRoleGroupId = null; ?>
                    <?php foreach ($gachaPrizeRoleList as $gachaPrizeRoleRow): ?>
                      <?php $gachaPrizePayload = gachaPrizePayload($gachaPrizeTier, $gachaPrizeRoleRow); ?>
                      <?php
                        $gachaPrizeRoleGroupId = (string) ($gachaPrizeRoleRow['roleConfigGroupId'] ?? '');
                        $gachaPrizeIsRoleGroupStart = $gachaPrizePreviousRoleGroupId !== null && $gachaPrizeRoleGroupId !== '' && $gachaPrizeRoleGroupId !== $gachaPrizePreviousRoleGroupId;
                        $gachaPrizePreviousRoleGroupId = $gachaPrizeRoleGroupId;
                      ?>
                      <button
                        class="gacha-prize-list-row<?= $gachaPrizeIsRoleGroupStart ? ' is-role-group-start' : '' ?>"
                        type="button"
                        data-gacha-prize-open
                        data-gacha-prize="<?= gachaPrizeJsonAttr($gachaPrizePayload) ?>"
                      >
                        <span class="gacha-prize-list-icon">
                          <img src="<?= gachaPrizeEsc(gachaPrizeAssetUrl((string) ($gachaPrizeRoleRow['icon'] ?? 'item-1.png'))) ?>" alt="" />
                        </span>
                        <span class="gacha-prize-list-copy">
                          <strong>
                            <span
                              class="gacha-prize-role-name-text"
                              <?php if (!empty($gachaPrizeRoleRow['roleColor'])): ?>
                                style="color: <?= gachaPrizeEsc((string) $gachaPrizeRoleRow['roleColor']) ?>"
                              <?php endif; ?>
                            ><?= gachaPrizeEsc($gachaPrizeRoleRow['shortName'] ?? ($gachaPrizeRoleRow['name'] ?? '')) ?></span>
                            <?php $gachaPrizeDurationDisplay = (string) ($gachaPrizeRoleRow['roleDurationPoolLabel'] ?? $gachaPrizeRoleRow['roleDurationLabel'] ?? ''); ?>
                            <?php if ($gachaPrizeDurationDisplay !== ''): ?>
                              <span class="gacha-prize-inline-badge is-duration"><?= gachaPrizeEsc($gachaPrizeDurationDisplay) ?></span>
                            <?php endif; ?>
                          </strong>
                          <?php if (!empty($gachaPrizeRoleRow['line'])): ?>
                            <span><?= gachaPrizeEsc($gachaPrizeRoleRow['line']) ?></span>
                          <?php endif; ?>
                          <?php
                            $gachaPrizeRoleHasCompactMeta = !empty($gachaPrizeRoleRow['roleSeriesBadge']) || !empty($gachaPrizeRoleRow['roleSeriesName']);
                            $gachaPrizeRoleCompactPermissionLimit = $gachaPrizeRoleHasCompactMeta ? 1 : 2;
                          ?>
                          <?php if ($gachaPrizeRoleHasCompactMeta || (!empty($gachaPrizeRoleRow['permissionBadges']) && is_array($gachaPrizeRoleRow['permissionBadges']))): ?>
                            <span class="gacha-prize-inline-badge-row">
                              <?php if (!empty($gachaPrizeRoleRow['roleSeriesBadge']) || !empty($gachaPrizeRoleRow['roleSeriesName'])): ?>
                                <span class="gacha-prize-inline-badge is-series"><?= gachaPrizeEsc($gachaPrizeRoleRow['roleSeriesBadge'] ?: $gachaPrizeRoleRow['roleSeriesName']) ?></span>
                              <?php endif; ?>
                              <?php foreach (array_slice($gachaPrizeRoleRow['permissionBadges'] ?? [], 0, $gachaPrizeRoleCompactPermissionLimit) as $gachaPrizePermissionIndex => $gachaPrizePermissionBadge): ?>
                                <span class="gacha-prize-inline-badge is-neutral<?= ($gachaPrizePermissionIndex === 0 && $gachaPrizeRoleHasCompactMeta) ? ' is-permission-after-meta' : '' ?>"><?= gachaPrizeEsc($gachaPrizePermissionBadge) ?></span>
                              <?php endforeach; ?>
                            </span>
                          <?php endif; ?>
                        </span>
                      </button>
                    <?php endforeach; ?>
                  </div>
                </div>
              </section>
            <?php endif; ?>

            <?php if (!$gachaPrizeItems && !$gachaPrizeRoleList): ?>
              <div class="gacha-prize-section">
                <div class="gacha-prize-empty">ไม่มีรางวัล</div>
              </div>
            <?php endif; ?>
          </article>
        </section>
      <?php endforeach; ?>
    </div>
  </main>

  <section id="gachaPrizeDetailModal" class="gacha-prize-modal is-hidden" aria-hidden="true">
    <button class="gacha-prize-modal-backdrop" type="button" data-gacha-prize-close aria-label="ปิดรายละเอียด"></button>
    <article class="gacha-prize-modal-card" role="dialog" aria-modal="true" aria-labelledby="gachaPrizeDetailTitle">
      <header class="gacha-prize-modal-head">
        <strong id="gachaPrizeDetailTitle">รายละเอียด</strong>
        <button class="gacha-prize-modal-close" type="button" data-gacha-prize-close aria-label="ปิดรายละเอียด">×</button>
      </header>
      <div class="gacha-prize-modal-body">
        <div class="gacha-prize-modal-hero" id="gachaPrizeDetailHero">
          <img id="gachaPrizeDetailImage" src="images/item-1.png" alt="" />
          <div id="gachaPrizeDetailRolePreview" class="gacha-prize-modal-role-preview" hidden>
            <span id="gachaPrizeDetailRolePreviewDot" class="gacha-prize-modal-role-preview-dot"></span>
            <span id="gachaPrizeDetailRolePreviewIconWrap" class="gacha-prize-modal-role-preview-icon" hidden>
              <img id="gachaPrizeDetailRolePreviewIcon" src="" alt="" />
            </span>
            <strong id="gachaPrizeDetailRolePreviewName" class="gacha-prize-modal-role-preview-name"></strong>
          </div>
          <span id="gachaPrizeDetailStatus" class="gacha-prize-modal-status"><img id="gachaPrizeDetailStatusImage" src="images/ICO1.png" alt="" /></span>
          <span id="gachaPrizeDetailCoin" class="gacha-prize-modal-coin"><span>เหรียญ</span></span>
        </div>
        <div id="gachaPrizeDetailTitleWrap" class="gacha-prize-modal-title">
          <strong id="gachaPrizeDetailName">Mystery Prize</strong>
          <span id="gachaPrizeDetailSubtitle" hidden></span>
        </div>
        <div id="gachaPrizeDetailBadges" class="gacha-prize-modal-badges"></div>
        <div id="gachaPrizeDetailFacts" class="gacha-prize-modal-facts"></div>
        <section id="gachaPrizeDetailPermissionsWrap" class="gacha-prize-modal-block" hidden>
          <div class="gacha-prize-modal-block-title">ความสามารถยศ</div>
          <div id="gachaPrizeDetailPermissions" class="gacha-prize-modal-ability-list"></div>
        </section>
        <section id="gachaPrizeDetailDescWrap" class="gacha-prize-modal-block" hidden>
          <div class="gacha-prize-modal-block-title">รายละเอียด</div>
          <div id="gachaPrizeDetailDesc" class="gacha-prize-modal-desc"></div>
        </section>
        <section id="gachaPrizeDetailConditionWrap" class="gacha-prize-modal-block" hidden>
          <div class="gacha-prize-modal-block-title">เงื่อนไข</div>
          <div id="gachaPrizeDetailCondition" class="gacha-prize-modal-desc"></div>
        </section>
      </div>
    </article>
  </section>

  <?php if ($gachaPrizeSwiperJs !== ''): ?>
  <script><?= $gachaPrizeSwiperJs ?>;if(typeof Swiper!=="undefined"&&!window.Swiper){window.Swiper=Swiper;}</script>
  <?php endif; ?>
  <script>
    let gachaPrizeSwiperLoadPromise = null;

    function ensureGachaPrizeSwiperReady() {
      if (window.Swiper) {
        return Promise.resolve(window.Swiper);
      }

      if (gachaPrizeSwiperLoadPromise) {
        return gachaPrizeSwiperLoadPromise;
      }

      gachaPrizeSwiperLoadPromise = new Promise((resolve, reject) => {
        const existing = document.querySelector('script[data-gacha-swiper-loader="true"]');
        if (existing) {
          existing.addEventListener("load", () => resolve(window.Swiper), { once: true });
          existing.addEventListener("error", () => reject(new Error("Failed to load local Swiper script.")), { once: true });
          return;
        }

        const script = document.createElement("script");
        script.src = "vendor/swiper/swiper-bundle.min.js";
        script.async = false;
        script.dataset.gachaSwiperLoader = "true";
        script.addEventListener("load", () => {
          if (window.Swiper) {
            resolve(window.Swiper);
            return;
          }
          reject(new Error("Swiper loaded but global was not initialized."));
        }, { once: true });
        script.addEventListener("error", () => reject(new Error("Failed to load local Swiper script.")), { once: true });
        document.head.appendChild(script);
      });

      return gachaPrizeSwiperLoadPromise;
    }

    const gachaPrizeTierDock = document.getElementById("gachaPrizeTierDock");
    const gachaPrizeTierDockWipe = gachaPrizeTierDock?.querySelector(".gacha-prize-tier-dock-wipe") || null;
    const gachaPrizeTierLinks = Array.from(document.querySelectorAll("[data-gacha-prize-tier-link]"));
    const gachaPrizeTierSections = Array.from(document.querySelectorAll("[data-gacha-prize-tier-section]"));
    const gachaPrizeDetailModal = document.getElementById("gachaPrizeDetailModal");
    const gachaPrizeDetailBody = gachaPrizeDetailModal?.querySelector(".gacha-prize-modal-body") || null;
    const gachaPrizeDetailHero = document.getElementById("gachaPrizeDetailHero");
    const gachaPrizeDetailImage = document.getElementById("gachaPrizeDetailImage");
    const gachaPrizeDetailRolePreview = document.getElementById("gachaPrizeDetailRolePreview");
    const gachaPrizeDetailRolePreviewDot = document.getElementById("gachaPrizeDetailRolePreviewDot");
    const gachaPrizeDetailRolePreviewIconWrap = document.getElementById("gachaPrizeDetailRolePreviewIconWrap");
    const gachaPrizeDetailRolePreviewIcon = document.getElementById("gachaPrizeDetailRolePreviewIcon");
    const gachaPrizeDetailRolePreviewName = document.getElementById("gachaPrizeDetailRolePreviewName");
    const gachaPrizeDetailTitleWrap = document.getElementById("gachaPrizeDetailTitleWrap");
    const gachaPrizeDetailName = document.getElementById("gachaPrizeDetailName");
    const gachaPrizeDetailSubtitle = document.getElementById("gachaPrizeDetailSubtitle");
    const gachaPrizeDetailBadges = document.getElementById("gachaPrizeDetailBadges");
    const gachaPrizeDetailFacts = document.getElementById("gachaPrizeDetailFacts");
    const gachaPrizeDetailDesc = document.getElementById("gachaPrizeDetailDesc");
    const gachaPrizeDetailPermissions = document.getElementById("gachaPrizeDetailPermissions");
    const gachaPrizeDetailPermissionsWrap = document.getElementById("gachaPrizeDetailPermissionsWrap");
    const gachaPrizeDetailDescWrap = document.getElementById("gachaPrizeDetailDescWrap");
    const gachaPrizeDetailConditionWrap = document.getElementById("gachaPrizeDetailConditionWrap");
    const gachaPrizeDetailCondition = document.getElementById("gachaPrizeDetailCondition");
    const gachaPrizeDetailStatus = document.getElementById("gachaPrizeDetailStatus");
    const gachaPrizeDetailStatusImage = document.getElementById("gachaPrizeDetailStatusImage");
    const gachaPrizeDetailCoin = document.getElementById("gachaPrizeDetailCoin");
    const gachaPrizeRoleViewers = Array.from(document.querySelectorAll("[data-gacha-role-viewer]"));
    const gachaPrizeRoleViewToggles = Array.from(document.querySelectorAll("[data-gacha-role-view-toggle]"));
    const gachaPrizeRoleSwipers = [];
    let activeGachaPrizeTierId = "";
    let activeGachaPrizeRoleView = "slider";

    function escapeHtml(value) {
      return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");
    }

    function activateGachaPrizeTier(tierId, animate = true) {
      if (!gachaPrizeTierDock || !tierId) return;
      const target = gachaPrizeTierLinks.find((link) => link.dataset.tierId === tierId);
      if (!target) return;
      const nextBackground = target.dataset.tierBackground || "transparent";
      gachaPrizeTierDock.style.setProperty("--gacha-prize-dock-background", nextBackground);
      gachaPrizeTierDock.style.setProperty("--gacha-prize-dock-overlay-opacity", "1");
      gachaPrizeTierLinks.forEach((link) => {
        link.classList.toggle("is-current", link.dataset.tierId === tierId);
      });
      if (animate && gachaPrizeTierDockWipe && activeGachaPrizeTierId && activeGachaPrizeTierId !== tierId) {
        gachaPrizeTierDockWipe.style.background = nextBackground;
        gachaPrizeTierDockWipe.style.animation = "none";
        void gachaPrizeTierDockWipe.offsetWidth;
        gachaPrizeTierDockWipe.style.animation = "gachaPrizeDockWipe 560ms cubic-bezier(0.22, 1, 0.36, 1)";
      }
      activeGachaPrizeTierId = tierId;
    }

    function detectActiveGachaPrizeTier() {
      if (!gachaPrizeTierSections.length) return;
      const focusLine = Math.max(132, window.innerHeight * 0.34);
      let bestSection = gachaPrizeTierSections[0];
      let bestDistance = Number.POSITIVE_INFINITY;

      gachaPrizeTierSections.forEach((section) => {
        const rect = section.getBoundingClientRect();
        const distance = Math.abs(rect.top - focusLine);
        const intersectsFocus = rect.top <= focusLine && rect.bottom >= focusLine;
        if (intersectsFocus && distance < bestDistance) {
          bestSection = section;
          bestDistance = distance;
          return;
        }
        if (bestDistance === Number.POSITIVE_INFINITY || distance < bestDistance) {
          bestSection = section;
          bestDistance = distance;
        }
      });

      activateGachaPrizeTier(bestSection.dataset.tierId || "", true);
    }

    function parseGachaPrizeDate(value) {
      const match = String(value || "").trim().match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
      if (!match) return NaN;
      const [, year, month, day, hour, minute, second = "0"] = match;
      return new Date(
        Number(year),
        Number(month) - 1,
        Number(day),
        Number(hour),
        Number(minute),
        Number(second)
      ).getTime();
    }

    function formatGachaPrizeCountdown(pickDate) {
      const targetTime = parseGachaPrizeDate(pickDate);
      if (!Number.isFinite(targetTime)) return "";
      const remainingMs = targetTime - Date.now();
      if (remainingMs <= 0) return "สิ้นสุดเวลา";
      const totalSeconds = Math.floor(remainingMs / 1000);
      const days = Math.floor(totalSeconds / 86400);
      const hours = Math.floor((totalSeconds % 86400) / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;
      const time = [hours, minutes, seconds].map((part) => String(part).padStart(2, "0")).join(":");
      return days > 0 ? `เหลือ ${days} วัน ${time}` : `เหลือ ${time}`;
    }

    function updateGachaPrizeCountdowns(scope = document) {
      scope.querySelectorAll("[data-gacha-prize-countdown]").forEach((node) => {
        const label = formatGachaPrizeCountdown(node.dataset.pickDate || "");
        node.textContent = label || (node.dataset.pickDateLabel ? `ถึง ${node.dataset.pickDateLabel}` : "");
        if (node.dataset.pickDateLabel) {
          node.title = `สิ้นสุด ${node.dataset.pickDateLabel}`;
        }
      });
    }

    function renderGachaPrizeBadge(value, className = "", style = "") {
      if (!value) return "";
      const classes = ["gacha-prize-tag"];
      if (className) classes.push(className);
      return `<span class="${classes.join(" ")}"${style ? ` style="${style}"` : ""}>${escapeHtml(value)}</span>`;
    }

    function renderGachaPrizeFact(label, value) {
      if (!value) return "";
      return `<div class="gacha-prize-modal-fact"><strong>${escapeHtml(label)}</strong><span>${escapeHtml(value)}</span></div>`;
    }

    function renderGachaPrizePermissionItem(permission) {
      const label = permission?.label || permission?.discordLabel || permission?.code || "สิทธิ์ยศ";
      const description = permission?.description || "ยังไม่ได้ตั้งคำอธิบายสิทธิ์นี้ไว้";
      return `
        <div class="gacha-prize-modal-ability-item">
          <strong>${escapeHtml(label)}</strong>
          <span>${escapeHtml(description)}</span>
        </div>
      `;
    }

    function renderGachaPrizeRichText(text) {
      const lines = String(text || "").split(/\n+/).map((line) => line.trim()).filter(Boolean);
      return lines.length ? lines.map((line) => `<p>${escapeHtml(line)}</p>`).join("") : "";
    }

    function hasRenderableGachaPrizeContent(text) {
      return renderGachaPrizeRichText(text) !== "";
    }

    function gachaPrizeTierBadgeStyle(payload) {
      return [
        `--gacha-badge-accent:${escapeHtml(payload?.accent || "#f5bd45")}`,
        `--gacha-badge-soft:${escapeHtml(payload?.soft || "#fff0d6")}`,
        `--gacha-badge-deep:${escapeHtml(payload?.deep || "#8b5c26")}`,
      ].join(";");
    }

    function applyGachaPrizeDetailNameStyle(payload) {
      const textNode = gachaPrizeDetailName.querySelector(".gacha-prize-detail-name-text");
      if (!textNode) return;
      textNode.classList.remove("is-gradient");
      textNode.style.backgroundImage = "";
      textNode.style.color = "#111111";
      textNode.style.webkitTextFillColor = "";

      if (payload?.type === "role" && String(payload?.roleColor || "").trim()) {
        textNode.style.color = String(payload.roleColor).trim();
      }
    }

    function syncGachaPrizeEmbedOffset() {
      if (!document.querySelector(".gacha-prize-page.is-embed")) return;
      try {
        if (!window.parent || window.parent === window) return;
        const parentOverlay = window.parent.document.getElementById("topUiOverlay");
        if (!parentOverlay) return;
        const overlayRect = parentOverlay.getBoundingClientRect();
        const overlayBottom = Math.ceil(overlayRect.bottom);
        const shellClearance = Math.max(overlayBottom + 6, 90);
        const dockTop = Math.max(overlayBottom + 2, 84);
        document.documentElement.style.setProperty("--gacha-prize-shell-clearance", `${shellClearance}px`);
        document.documentElement.style.setProperty("--gacha-prize-top-offset", `${dockTop}px`);
        requestAnimationFrame(() => {
          const dockHeight = gachaPrizeTierDock?.offsetHeight || 84;
          document.documentElement.style.setProperty("--gacha-prize-anchor-offset", `${dockTop + dockHeight + 8}px`);
        });
      } catch (error) {
        console.warn("Failed to sync gacha prize embed offset", error);
      }
    }

    function syncGachaPrizeRoleToggleButtons() {
      const nextView = activeGachaPrizeRoleView === "slider" ? "list" : "slider";
      const nextLabel = nextView === "list" ? "สลับเป็นรายการ" : "สลับเป็นการ์ด";
      gachaPrizeRoleViewToggles.forEach((button) => {
        button.setAttribute("aria-label", nextLabel);
        button.setAttribute("title", nextLabel);
        button.setAttribute("aria-pressed", activeGachaPrizeRoleView === "list" ? "true" : "false");
        button.innerHTML = nextView === "list"
          ? `
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M8 6h12"></path>
              <path d="M8 12h12"></path>
              <path d="M8 18h12"></path>
              <path d="M4 6h.01"></path>
              <path d="M4 12h.01"></path>
              <path d="M4 18h.01"></path>
            </svg>
          `
          : `
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
              <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
              <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
              <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
            </svg>
          `;
      });
    }

    function applyGachaPrizeRoleView(requestedView) {
      activeGachaPrizeRoleView = requestedView === "list" ? "list" : "slider";
      gachaPrizeRoleViewers.forEach((viewer) => {
        viewer.dataset.view = activeGachaPrizeRoleView;
      });
      syncGachaPrizeRoleToggleButtons();
      if (activeGachaPrizeRoleView === "slider") {
        window.requestAnimationFrame(() => {
          gachaPrizeRoleSwipers.forEach((entry) => {
            entry?.swiper?.update?.();
            updateGachaPrizeRoleSliderShell(entry);
          });
        });
      }
    }

    function updateGachaPrizeRoleSliderShell(entry) {
      if (!entry?.shell || !entry?.slider) return;
      const wrapper = entry.swiper?.wrapperEl || entry.slider.querySelector(".swiper-wrapper");
      const sliderWidth = entry.slider.clientWidth || 0;
      const wrapperWidth = wrapper?.scrollWidth || 0;
      entry.shell.classList.toggle("is-scrollable", wrapperWidth - sliderWidth > 12);
    }

    function initializeGachaPrizeRoleViewers() {
      gachaPrizeRoleViewers.forEach((viewer) => {
        const slider = viewer.querySelector(".gacha-prize-role-swiper");
        const shell = viewer.querySelector("[data-gacha-role-slider]");

        if (slider && window.Swiper) {
          const swiper = new window.Swiper(slider, {
            slidesPerView: "auto",
            spaceBetween: 8,
            grabCursor: true,
            freeMode: {
              enabled: true,
              sticky: false,
              momentum: true,
              momentumBounce: true,
            },
            watchOverflow: true,
            observer: true,
            observeParents: true,
            resistanceRatio: 0.86,
            breakpoints: {
              768: {
                spaceBetween: 10,
              },
              1200: {
                spaceBetween: 12,
              },
            },
          });
          const entry = { swiper, slider, shell };
          gachaPrizeRoleSwipers.push(entry);
          window.requestAnimationFrame(() => updateGachaPrizeRoleSliderShell(entry));
        }
      });

      gachaPrizeRoleViewToggles.forEach((button) => {
        button.addEventListener("click", () => {
          applyGachaPrizeRoleView(activeGachaPrizeRoleView === "slider" ? "list" : "slider");
        });
      });

      applyGachaPrizeRoleView(gachaPrizeRoleViewers[0]?.dataset.defaultView || gachaPrizeRoleViewers[0]?.dataset.view || "slider");
    }

    function openGachaPrizeDetail(payload) {
      if (!payload || !gachaPrizeDetailModal) return;
      const permissionDetails = Array.isArray(payload.permissionDetails) ? payload.permissionDetails : [];
      const isRolePrize = payload.type === "role";
      const tierBadgeStyle = gachaPrizeTierBadgeStyle(payload);
      const roleFullName = isRolePrize
        ? ["ยศ", payload.name || "Mystery Prize", payload.roleDurationPoolLabel || payload.roleDurationLabel || ""].filter(Boolean).join(" ")
        : (payload.name || "Mystery Prize");
      gachaPrizeDetailName.innerHTML = `<span class="gacha-prize-detail-name-text">${escapeHtml(payload.name || "Mystery Prize")}</span>`;
      gachaPrizeDetailSubtitle.textContent = "";
      gachaPrizeDetailSubtitle.hidden = true;
      applyGachaPrizeDetailNameStyle(payload);
      gachaPrizeDetailImage.src = payload.image || "images/item-1.png";
      gachaPrizeDetailImage.alt = payload.name || "Prize image";
      if (gachaPrizeDetailRolePreview && gachaPrizeDetailRolePreviewName && gachaPrizeDetailRolePreviewIconWrap && gachaPrizeDetailRolePreviewIcon && gachaPrizeDetailTitleWrap) {
        if (isRolePrize) {
          const roleIconUrl = String(payload.roleIconUrl || "").trim();
          gachaPrizeDetailRolePreviewName.textContent = payload.name || "Mystery Prize";
          gachaPrizeDetailRolePreviewName.style.color = String(payload.roleColor || "").trim() || "#111111";
          if (gachaPrizeDetailRolePreviewDot) {
            gachaPrizeDetailRolePreviewDot.style.background = String(payload.roleColor || "").trim() || "#8b91a1";
          }
          if (roleIconUrl) {
            gachaPrizeDetailRolePreviewIcon.src = roleIconUrl;
            gachaPrizeDetailRolePreviewIcon.alt = payload.name || "Role icon";
            gachaPrizeDetailRolePreviewIconWrap.hidden = false;
          } else {
            gachaPrizeDetailRolePreviewIconWrap.hidden = true;
            gachaPrizeDetailRolePreviewIcon.removeAttribute("src");
            gachaPrizeDetailRolePreviewIcon.alt = "";
          }
          gachaPrizeDetailRolePreview.hidden = false;
          gachaPrizeDetailImage.hidden = true;
          gachaPrizeDetailHero.classList.add("is-role-preview");
          gachaPrizeDetailTitleWrap.hidden = true;
        } else {
          gachaPrizeDetailRolePreview.hidden = true;
          gachaPrizeDetailRolePreviewIconWrap.hidden = true;
          gachaPrizeDetailRolePreviewName.textContent = "";
          gachaPrizeDetailRolePreviewName.style.color = "";
          if (gachaPrizeDetailRolePreviewDot) {
            gachaPrizeDetailRolePreviewDot.style.background = "";
          }
          gachaPrizeDetailImage.hidden = false;
          gachaPrizeDetailHero.classList.remove("is-role-preview");
          gachaPrizeDetailTitleWrap.hidden = false;
        }
      }
      gachaPrizeDetailHero.style.setProperty("--gacha-prize-modal-accent", payload.accent || "#8bd6ff");
      gachaPrizeDetailHero.style.setProperty("--gacha-prize-modal-soft", payload.soft || "#eef8ff");

      if (payload.statusIcon) {
        gachaPrizeDetailStatusImage.src = payload.statusIcon;
        gachaPrizeDetailStatus.classList.add("is-visible");
      } else {
        gachaPrizeDetailStatus.classList.remove("is-visible");
      }

      if (Number(payload.coinPrice || 0) > 0) {
        gachaPrizeDetailCoin.innerHTML = `<span>P</span> ${escapeHtml(Number(payload.coinPrice || 0).toLocaleString())}`;
        gachaPrizeDetailCoin.classList.add("is-visible");
      } else {
        gachaPrizeDetailCoin.classList.remove("is-visible");
      }

      gachaPrizeDetailBadges.innerHTML = [
        renderGachaPrizeBadge(
          [payload.tier, payload.tierName].filter(Boolean).join(" · "),
          "is-tier",
          tierBadgeStyle
        ),
        renderGachaPrizeBadge(payload.rate ? `อัตราออก ${payload.rate}` : ""),
      ].filter(Boolean).join("");
      gachaPrizeDetailFacts.innerHTML = [
        renderGachaPrizeFact("ชื่อ", roleFullName),
        renderGachaPrizeFact("ประเภท", !isRolePrize ? (payload.typeLabel || "ไอเทม") : ""),
        renderGachaPrizeFact("เธียร์ยศ", isRolePrize ? (payload.roleTier || "") : ""),
        renderGachaPrizeFact("อายุการใช้งาน", isRolePrize ? (payload.roleDurationPoolLabel || payload.roleDurationLabel || "ถาวร") : ""),
        renderGachaPrizeFact("จำนวนสิทธิ์", payload.displayQuantityLabel || ""),
        renderGachaPrizeFact("สิ้นสุด", payload.pickDateLabel || ""),
        renderGachaPrizeFact("ราคาเหรียญ", payload.coinPrice > 0 ? `${Number(payload.coinPrice).toLocaleString()} P` : ""),
        renderGachaPrizeFact("ซีรีส์", payload.roleSeriesName || ""),
      ].filter(Boolean).join("");
      const descriptionHtml = renderGachaPrizeRichText(payload.description || "");
      const conditionHtml = renderGachaPrizeRichText(payload.conditionText || "");
      gachaPrizeDetailDesc.innerHTML = descriptionHtml;
      gachaPrizeDetailCondition.innerHTML = conditionHtml;
      gachaPrizeDetailDescWrap.hidden = !hasRenderableGachaPrizeContent(payload.description || "");
      gachaPrizeDetailConditionWrap.hidden = !hasRenderableGachaPrizeContent(payload.conditionText || "");
      gachaPrizeDetailPermissions.innerHTML = permissionDetails.length
        ? permissionDetails.map(renderGachaPrizePermissionItem).join("")
        : "";
      gachaPrizeDetailPermissionsWrap.hidden = !(isRolePrize && permissionDetails.length);
      if (gachaPrizeDetailBody) {
        gachaPrizeDetailBody.scrollTop = 0;
      }
      updateGachaPrizeCountdowns(gachaPrizeDetailModal);
      gachaPrizeDetailModal.classList.remove("is-hidden");
      gachaPrizeDetailModal.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
    }

    function closeGachaPrizeDetail() {
      if (!gachaPrizeDetailModal) return;
      gachaPrizeDetailModal.classList.add("is-hidden");
      gachaPrizeDetailModal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
    }

    document.addEventListener("click", (event) => {
      const tierLink = event.target.closest("[data-gacha-prize-tier-link]");
      if (tierLink) {
        event.preventDefault();
        const target = document.querySelector(tierLink.getAttribute("href") || "");
        if (target) {
          const styles = getComputedStyle(document.documentElement);
          const offset = parseFloat(styles.getPropertyValue("--gacha-prize-anchor-offset")) || 120;
          const targetTop = target.getBoundingClientRect().top + window.scrollY;
          window.scrollTo({
            top: Math.max(0, targetTop - offset),
            behavior: "smooth",
          });
          activateGachaPrizeTier(tierLink.dataset.tierId || "", true);
        }
        return;
      }

      const detailTrigger = event.target.closest("[data-gacha-prize-open]");
      if (detailTrigger) {
        try {
          const payload = JSON.parse(detailTrigger.dataset.gachaPrize || "{}");
          openGachaPrizeDetail(payload);
        } catch (error) {
          console.error("Failed to open gacha prize detail", error);
        }
        return;
      }

      if (event.target.closest("[data-gacha-prize-close]")) {
        closeGachaPrizeDetail();
        return;
      }

    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeGachaPrizeDetail();
      }
    });

    function bootGachaPrizePage() {
      syncGachaPrizeEmbedOffset();
      initializeGachaPrizeRoleViewers();
      updateGachaPrizeCountdowns();
      window.addEventListener("scroll", detectActiveGachaPrizeTier, { passive: true });
      window.addEventListener("resize", () => {
        syncGachaPrizeEmbedOffset();
        detectActiveGachaPrizeTier();
        gachaPrizeRoleSwipers.forEach((entry) => {
          entry?.swiper?.update?.();
          updateGachaPrizeRoleSliderShell(entry);
        });
      });
      window.setInterval(() => updateGachaPrizeCountdowns(), 1000);
      activateGachaPrizeTier(gachaPrizeTierSections[0]?.dataset.tierId || gachaPrizeTierLinks[0]?.dataset.tierId || "", false);
      detectActiveGachaPrizeTier();
    }

    ensureGachaPrizeSwiperReady()
      .catch((error) => {
        console.warn("Swiper bootstrap failed:", error);
      })
      .finally(() => {
        bootGachaPrizePage();
      });
  </script>
</body>
</html>
<?php
if (!empty($gachaPrizePageCacheKey) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $gachaPrizePageHtml = (string) ob_get_clean();
    if (http_response_code() < 400) {
        PublicPageCacheService::put($gachaPrizePageCacheKey, $gachaPrizePageHtml);
    }
    echo $gachaPrizePageHtml;
}
?>
