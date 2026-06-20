<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

$player = PlayerAuth::currentUser();

$isEmbed = isset($_GET['embed']);
$baseUrl = rtrim((string) Bootstrap::config('app.baseUrl', '/discord'), '/');
$guildId = (string) Bootstrap::config('discord.guildId', '');
$shopPageCacheKey = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_GET['nocache'])) {
    $shopPageCacheKey = PublicPageCacheService::key('gacha-shop', [
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'user' => (string) ($player['userId'] ?? 'guest'),
        'file' => filemtime(__FILE__) ?: 0,
    ]);
    $cachedShopPage = PublicPageCacheService::get($shopPageCacheKey, $player ? 12 : 45);
    if ($cachedShopPage !== '') {
        echo $cachedShopPage;
        exit;
    }
    ob_start();
}
$config = ShopConfigService::load();
$shopSwiperCssHref = is_file(__DIR__ . '/vendor/swiper/swiper-bundle.min.css') ? 'vendor/swiper/swiper-bundle.min.css' : '';
$shopSwiperJsPath = __DIR__ . '/vendor/swiper/swiper-bundle.min.js';
$shopSwiperJs = is_file($shopSwiperJsPath) ? str_replace('</script>', '<\/script>', (string) file_get_contents($shopSwiperJsPath)) : '';
$shopSwiperJsHref = $shopSwiperJs === '' && is_file($shopSwiperJsPath) ? 'vendor/swiper/swiper-bundle.min.js' : '';
$units = ShopUnitService::units(true);
$unitIndex = ShopUnitService::unitIndex(true);
$products = ShopConfigService::publicProducts($config);
$roleProducts = array_values(array_filter($products, static fn (array $product): bool => ($product['type'] ?? '') === 'role'));
$itemProducts = array_values(array_filter($products, static fn (array $product): bool => ($product['type'] ?? '') !== 'role'));

$roles = Database::fetchAll(
    'SELECT roleId, roleName, rolePosition, roleColor, permissions, iconHash, unicodeEmoji, isManaged, metadataJson
     FROM tbl_role
     WHERE guildId = :guildId AND deleteDate IS NULL
     ORDER BY rolePosition DESC, roleName ASC',
    ['guildId' => $guildId]
);
$roleIndex = [];
foreach (RoleCatalogService::decorateRoles($roles) as $role) {
    $role['roleIconUrl'] = DiscordAssets::roleIcon((string) $role['roleId'], $role['iconHash'] ?? null, 96);
    $role['roleColorHex'] = !empty($role['roleColor'])
        ? '#' . str_pad(strtolower(dechex((int) $role['roleColor'])), 6, '0', STR_PAD_LEFT)
        : '';
    $role['permissionDetails'] = class_exists('RolePermissionDescriptionService')
        ? RolePermissionDescriptionService::describeAllowedPermissions($role['permissions'] ?? null)
        : [];
    $roleIndex[(string) $role['roleId']] = $role;
}

$itemGroups = shopItemGroups($itemProducts);
$roleSeriesSettings = [];
foreach (array_filter($config['settings']['roleSeriesSettings'] ?? [], 'is_array') as $entry) {
    $seriesId = trim((string) ($entry['seriesId'] ?? ''));
    if ($seriesId === '') {
        continue;
    }
    $roleSeriesSettings[$seriesId] = $entry;
}
$shopRoleDefaultView = in_array(($config['settings']['defaultRoleView'] ?? 'slider'), ['slider', 'list'], true)
    ? (string) $config['settings']['defaultRoleView']
    : 'slider';
$roleGroups = shopRoleGroups($roleProducts, $roleIndex, $roleSeriesSettings);
$shopBuyDefaults = ShopUnitService::normalizeBuyThemeDefaults(
    $config['settings']['shopBuyDefaults'] ?? [],
    ShopUnitService::defaultBuyTheme()
);

function shopEsc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function shopAssetUrl(string $path): string
{
    global $baseUrl;
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    return $baseUrl . '/gacha/' . ltrim($path, '/');
}

function shopIsVideoAsset(string $path): bool
{
    $path = trim($path);
    if ($path === '') {
        return false;
    }
    $resolvedPath = (string) (parse_url($path, PHP_URL_PATH) ?: $path);
    return (bool) preg_match('/\.(mp4|webm|mov|m4v)$/i', $resolvedPath);
}

function shopRoleGroupBannerHtml(string $path, string $label = ''): string
{
    $url = shopAssetUrl($path);
    if ($url === '') {
        return '';
    }
    if (shopIsVideoAsset($path)) {
        return '<video class="shop-role-group-banner" src="' . shopEsc($url) . '" autoplay muted loop playsinline webkit-playsinline preload="metadata" disablepictureinpicture aria-label="' . shopEsc($label !== '' ? $label : 'Role series') . '"></video>';
    }
    return '<img class="shop-role-group-banner" src="' . shopEsc($url) . '" alt="' . shopEsc($label !== '' ? $label : 'Role series') . '">';
}

function shopCssBackgroundStyle(string $background): string
{
    $background = trim($background);
    if ($background === '' || strlen($background) > 240) {
        return '';
    }
    $isAllowed = (bool) preg_match('/^(#[0-9a-f]{3,8}|rgba?\([0-9.,\s%]+\)|hsla?\([0-9.,\s%]+\)|(linear|radial|conic)-gradient\([#a-z0-9.,\s%()\-]+\))$/i', $background);
    return $isAllowed ? ' style="background:' . shopEsc($background) . '"' : '';
}

function shopUnitGlyph(array $unit): string
{
    $code = strtolower(trim((string) ($unit['unitCode'] ?? $unit['code'] ?? '')));
    return match ($code) {
        'coin' => 'P',
        'ticket', 'gachaticket' => 'T',
        'gem', 'star' => 'S',
        'free_spin', 'freespin' => 'F',
        default => strtoupper(substr((string) ($unit['shortName'] ?? $unit['displayName'] ?? $code ?: 'U'), 0, 1)),
    };
}

function shopPresetUnitIconPath(array $unit): string
{
    $candidates = [
        strtolower(trim((string) ($unit['unitCode'] ?? ''))),
        strtolower(trim((string) ($unit['code'] ?? ''))),
        strtolower(trim((string) ($unit['shortName'] ?? ''))),
        strtolower(trim((string) ($unit['displayName'] ?? ''))),
        strtolower(trim((string) ($unit['label'] ?? ''))),
    ];

    foreach ($candidates as $candidate) {
        if ($candidate === '') {
            continue;
        }
        return match ($candidate) {
            'coin', 'เหรียญ' => 'images/icon_coin.png',
            'gem', 'เพชร' => 'images/icon_gem.png',
            'ticket', 'ตั๋ว', 'gachaticket', 'gacha_ticket' => 'images/icon_ticket.png',
            'potion', 'gelato', 'โพชั่น' => 'images/icon_gelato.png',
            default => '',
        };
    }

    return '';
}

function shopUnitIconIsImage(string $icon): bool
{
    return $icon !== '' && (
        preg_match('/^https?:\/\//i', $icon)
        || str_starts_with($icon, '/')
        || str_starts_with($icon, 'images/')
        || str_starts_with($icon, 'uploads/')
    );
}

function shopUnitIcon(array $unit): string
{
    $preset = shopPresetUnitIconPath($unit);
    if ($preset !== '') {
        return '<span class="shop-unit-icon is-image"><img src="' . shopEsc(shopAssetUrl($preset)) . '" alt=""></span>';
    }

    $icon = trim((string) ($unit['icon'] ?? ''));
    if (shopUnitIconIsImage($icon)) {
        return '<span class="shop-unit-icon is-image"><img src="' . shopEsc(shopAssetUrl($icon)) . '" alt=""></span>';
    }
    return '<span class="shop-unit-icon">' . shopEsc(shopUnitGlyph($unit)) . '</span>';
}

function shopMoneyIcon(array $unit): string
{
    $preset = shopPresetUnitIconPath($unit);
    if ($preset !== '') {
        return '<span class="shop-money-icon is-image"><img src="' . shopEsc(shopAssetUrl($preset)) . '" alt=""></span>';
    }

    $icon = trim((string) ($unit['icon'] ?? ''));
    if (shopUnitIconIsImage($icon)) {
        return '<span class="shop-money-icon is-image"><img src="' . shopEsc(shopAssetUrl($icon)) . '" alt=""></span>';
    }

    return '<span class="shop-money-icon is-glyph">' . shopEsc(shopUnitGlyph($unit)) . '</span>';
}

function shopDisplayName(array $product, ?array $role = null): string
{
    $roleName = trim((string) ($role['roleName'] ?? 'Discord Role'));
    $name = trim((string) ($product['name'] ?? '')) ?: trim((string) ($product['shortName'] ?? 'สินค้า'));
    return str_replace('<roleName>', $roleName, $name);
}

function shopNormalizedLabel(string $value): string
{
    $value = preg_replace('/\s+/u', '', trim($value)) ?? '';
    return mb_strtolower($value);
}

function shopResolvedShortName(array $product, ?array $role = null): string
{
    $roleName = trim((string) ($role['roleName'] ?? 'Discord Role'));
    $shortName = trim((string) ($product['shortName'] ?? ''));
    return $shortName !== '' ? str_replace('<roleName>', $roleName, $shortName) : '';
}

function shopShelfName(array $product, ?array $role = null): string
{
    $shortName = shopResolvedShortName($product, $role);
    return $shortName !== '' ? $shortName : shopDisplayName($product, $role);
}

function shopQuotaBadge(array $product, ?array $option = null): string
{
    $mode = (string) ($option['quotaMode'] ?? 'inherit');
    if ($mode === 'inherit') {
        $mode = (string) ($product['quotaMode'] ?? 'unlimited');
    }
    $total = (int) ($option['quotaTotal'] ?? 0);
    if ($total <= 0) {
        $total = (int) ($product['quotaTotal'] ?? 0);
    }
    if ($mode === 'manual' && $total > 0) {
        return number_format($total) . ' สิทธิ์';
    }
    return '';
}

function shopProductBadgeLabels(array $product, ?array $option = null, ?array $role = null, ?array $group = null): array
{
    $title = shopDisplayName($product, $role);
    $titleKey = shopNormalizedLabel($title);
    $shelfKey = shopNormalizedLabel(shopShelfName($product, $role));
    $shortKey = shopNormalizedLabel(shopResolvedShortName($product, $role));
    $labels = [
        ['label' => shopQuotaBadge($product, $option), 'kind' => 'quota'],
        ['label' => trim((string) ($group['badge'] ?? '')), 'kind' => 'group'],
        ['label' => trim((string) ($product['badge'] ?? '')), 'kind' => 'badge'],
        ['label' => shopResolvedShortName($product, $role), 'kind' => 'short'],
    ];
    if ($role && !empty($role['roleTier'])) {
        $labels[] = ['label' => 'Tier ' . (string) $role['roleTier'], 'kind' => 'tier'];
    }

    $seen = array_filter([
        $titleKey => $titleKey !== '',
        $shelfKey => $shelfKey !== '',
    ]);
    $out = [];
    foreach ($labels as $entry) {
        $label = trim((string) ($entry['label'] ?? ''));
        $kind = (string) ($entry['kind'] ?? '');
        if ($label === '') {
            continue;
        }
        $key = shopNormalizedLabel($label);
        if ($key === '' || isset($seen[$key])) {
            continue;
        }
        if ($kind === 'short' && ($titleKey !== '') && (str_contains($titleKey, $key) || str_contains($key, $titleKey))) {
            continue;
        }
        if ($kind === 'badge' && $shortKey !== '' && $key === $shortKey && str_contains($titleKey, $key)) {
            continue;
        }
        $seen[$key] = true;
        $out[] = $label;
    }
    return $out;
}

function shopFullName(array $product, ?array $role = null, ?array $option = null): string
{
    $name = shopDisplayName($product, $role);
    if (($product['type'] ?? '') === 'role') {
        $prefix = str_starts_with($name, 'ยศ ') ? '' : 'ยศ ';
        $days = (int) ($option['days'] ?? 0);
        return trim($prefix . $name . ($days > 0 ? ' ' . $days . ' วัน' : ' ถาวร'));
    }
    return $name;
}

function shopRoleColor(?array $role): string
{
    if (!$role) {
        return '#503268';
    }
    $color = (int) ($role['roleColor'] ?? 0);
    return $color > 0 ? '#' . str_pad(dechex($color), 6, '0', STR_PAD_LEFT) : '#503268';
}

function shopProductImage(array $product, ?array $role = null): string
{
    $image = trim((string) ($product['image'] ?? ''));
    if ($image !== '') {
        return shopAssetUrl($image);
    }
    if ($role && !empty($role['roleIconUrl'])) {
        return (string) $role['roleIconUrl'];
    }
    if (($product['type'] ?? '') === 'role') {
        return shopAssetUrl('images/icon_roles_blank.png');
    }
    return shopAssetUrl('images/item-1.png');
}

function shopPriceHtml(array $option, array $unitIndex): string
{
    $parts = [];
    foreach (($option['prices'] ?? []) as $unitCode => $amount) {
        $amount = (int) $amount;
        if ($amount <= 0) {
            continue;
        }
        $unit = $unitIndex[(string) $unitCode] ?? ['unitCode' => $unitCode, 'shortName' => $unitCode, 'displayName' => $unitCode, 'icon' => ''];
        $parts[] = '<span class="shop-price-chip">' . shopMoneyInlineHtml($unit, $amount) . '</span>';
    }
    return $parts ? implode('', $parts) : '<span class="shop-price-chip is-free"><strong>ฟรี</strong></span>';
}

function shopVisibleOptions(array $product): array
{
    return array_values(array_filter($product['purchaseOptions'] ?? [], static fn (array $option): bool => !empty($option['active']) && !empty($option['visible'])));
}

function shopDurationLabel(?array $option): string
{
    if (!$option) {
        return '';
    }
    $label = trim((string) ($option['label'] ?? ''));
    if ($label !== '') {
        return $label;
    }
    $days = (int) ($option['days'] ?? 0);
    return $days > 0 ? $days . ' วัน' : 'ถาวร';
}

function shopItemGroupMeta(array $product): array
{
    $groupName = trim((string) ($product['groupName'] ?? ''));
    $groupBadge = trim((string) ($product['groupBadge'] ?? ''));
    $groupDescription = trim((string) ($product['groupDescription'] ?? ''));
    $groupId = trim((string) ($product['groupId'] ?? ''));
    $groupSortOrder = (int) ($product['groupSortOrder'] ?? 0);

    if ($groupName !== '') {
        return [
            'id' => $groupId !== '' ? $groupId : strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $groupName)),
            'name' => $groupName,
            'badge' => $groupBadge,
            'description' => $groupDescription,
            'sortOrder' => $groupSortOrder > 0 ? $groupSortOrder : (int) ($product['sortOrder'] ?? 999),
        ];
    }

    $effectType = trim((string) ($product['effectType'] ?? ''));
    if (str_contains($effectType, 'private_room')) {
        return [
            'id' => 'private-room',
            'name' => 'ห้องส่วนตัว',
            'badge' => '',
            'description' => '',
            'sortOrder' => $groupSortOrder > 0 ? $groupSortOrder : (int) ($product['sortOrder'] ?? 999),
        ];
    }
    if (str_contains($effectType, 'moderation')) {
        return [
            'id' => 'moderation-ticket',
            'name' => 'บัตรสถานะ',
            'badge' => '',
            'description' => '',
            'sortOrder' => $groupSortOrder > 0 ? $groupSortOrder : (int) ($product['sortOrder'] ?? 999),
        ];
    }
    if (($product['type'] ?? '') === 'system_ticket') {
        return [
            'id' => 'system-ticket',
            'name' => 'บัตรระบบ',
            'badge' => '',
            'description' => '',
            'sortOrder' => $groupSortOrder > 0 ? $groupSortOrder : (int) ($product['sortOrder'] ?? 999),
        ];
    }
    $badge = trim((string) ($product['badge'] ?? ''));
    return [
        'id' => $badge !== '' ? strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $badge)) : 'general-item',
        'name' => $badge !== '' ? $badge : 'ไอเทมทั่วไป',
        'badge' => '',
        'description' => '',
        'sortOrder' => $groupSortOrder > 0 ? $groupSortOrder : (int) ($product['sortOrder'] ?? 999),
    ];
}

function shopItemGroups(array $itemProducts): array
{
    $groups = [];
    foreach ($itemProducts as $product) {
        $meta = shopItemGroupMeta($product);
        $name = (string) ($meta['name'] ?? 'ไอเทมทั่วไป');
        $key = (string) ($meta['id'] ?? '');
        if ($key === '') {
            $key = strtolower((string) (preg_replace('/[^a-z0-9]+/i', '-', $name) ?: $name));
        }
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'name' => $name,
                'badge' => (string) ($meta['badge'] ?? ''),
                'description' => (string) ($meta['description'] ?? ''),
                'products' => [],
                'sortOrder' => (int) ($meta['sortOrder'] ?? ($product['sortOrder'] ?? 999)),
            ];
        }
        $groups[$key]['products'][] = $product;
        $groups[$key]['sortOrder'] = min((int) $groups[$key]['sortOrder'], (int) ($meta['sortOrder'] ?? ($product['sortOrder'] ?? 999)));
    }
    $out = array_values($groups);
    usort($out, static fn (array $left, array $right): int => ((int) $left['sortOrder'] <=> (int) $right['sortOrder']) ?: strcmp((string) $left['name'], (string) $right['name']));
    return $out;
}

function shopRoleGroups(array $roleProducts, array $roleIndex, array $roleSeriesSettings = []): array
{
    $groups = [];
    foreach ($roleProducts as $product) {
        $role = $roleIndex[(string) ($product['discordRoleId'] ?? '')] ?? null;
        $seriesId = (string) ($role['roleSeriesId'] ?? '');
        $key = $seriesId !== '' ? $seriesId : '__none';
        $seriesSetting = is_array($roleSeriesSettings[$seriesId] ?? null) ? $roleSeriesSettings[$seriesId] : [];
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'id' => $key,
                'name' => $role && !empty($role['roleSeriesName']) ? (string) $role['roleSeriesName'] : 'ยศทั่วไป',
                'icon' => $role ? trim((string) ($role['roleSeriesIcon'] ?? '')) : '',
                'background' => $role ? trim((string) ($role['roleSeriesBackground'] ?? '')) : '',
                'badge' => $role && !empty($role['roleSeriesBadge']) ? (string) $role['roleSeriesBadge'] : '',
                'description' => $role && !empty($role['roleSeriesDescription']) ? (string) $role['roleSeriesDescription'] : '',
                'headerImage' => trim((string) ($seriesSetting['headerImage'] ?? '')),
                'sortOrder' => (int) ($role['roleSeriesSortOrder'] ?? 9999),
                'products' => [],
            ];
        }
        $groups[$key]['products'][] = $product;
    }
    usort($groups, static fn (array $left, array $right): int => ((int) $left['sortOrder'] <=> (int) $right['sortOrder']) ?: strcmp((string) $left['name'], (string) $right['name']));
    return $groups;
}

function shopRoleSummary(?array $role, array $product): string
{
    $detail = trim((string) ($product['detailText'] ?? ''));
    if ($detail !== '') {
        return mb_strimwidth($detail, 0, 86, '...');
    }
    $seriesDescription = trim((string) ($role['roleSeriesDescription'] ?? ''));
    if ($seriesDescription !== '') {
        return mb_strimwidth($seriesDescription, 0, 82, '...');
    }
    return '';
}

function shopUnitDisplayLabel(array $unit): string
{
    return trim((string) ($unit['displayName'] ?? ''))
        ?: trim((string) ($unit['shortName'] ?? ''))
        ?: trim((string) ($unit['unitCode'] ?? $unit['code'] ?? ''));
}

/** @return array<int, string> */
function shopRoleAbilityBadges(?array $role, int $limit = 2): array
{
    $labels = [];
    foreach (array_values(array_filter($role['permissionDetails'] ?? [], 'is_array')) as $permission) {
        $label = trim((string) ($permission['badge'] ?? $permission['label'] ?? ''));
        if ($label === '' || in_array($label, $labels, true)) {
            continue;
        }
        $labels[] = $label;
        if (count($labels) >= $limit) {
            break;
        }
    }
    return $labels;
}

function shopMoneyInlineHtml(array $unit, int $amount): string
{
    $label = trim(shopUnitDisplayLabel($unit)) ?: 'หน่วย';
    return '<span class="shop-money-inline" aria-label="' . shopEsc(number_format($amount) . ' ' . $label) . '">'
        . '<span class="shop-money-amount">' . shopEsc(number_format($amount)) . '</span>'
        . shopMoneyIcon($unit)
        . '<span class="shop-visually-hidden">' . shopEsc($label) . '</span>'
        . '</span>';
}

/** @return array<int, string> */
function shopDurationLabels(array $options): array
{
    $labels = [];
    foreach ($options as $option) {
        $label = trim(shopDurationLabel($option));
        if ($label === '' || in_array($label, $labels, true)) {
            continue;
        }
        $labels[] = $label;
    }
    return $labels;
}

function shopDurationSummaryText(array $options): string
{
    return implode(' | ', shopDurationLabels($options));
}

/** @return array<int, array<string, mixed>> */
function shopPaymentChoicesFromPrices(array $prices, array $unitIndex, array $buyDefaults, ?array $enabledUnits = null): array
{
    $choices = [];
    foreach ($prices as $unitCode => $amount) {
        $unitCode = (string) $unitCode;
        if (is_array($enabledUnits) && empty($enabledUnits[$unitCode])) {
            continue;
        }
        $amount = (int) $amount;
        if ($amount <= 0) {
            continue;
        }
        $unit = $unitIndex[(string) $unitCode] ?? [
            'unitCode' => (string) $unitCode,
            'shortName' => (string) $unitCode,
            'displayName' => (string) $unitCode,
            'icon' => '',
            'shopBuyTheme' => [],
        ];
        $choices[] = [
            'unitCode' => (string) ($unit['unitCode'] ?? $unitCode),
            'amount' => $amount,
            'displayName' => shopUnitDisplayLabel($unit),
            'iconHtml' => shopMoneyIcon($unit),
            'moneyHtml' => shopMoneyInlineHtml($unit, $amount),
            'resolvedTheme' => ShopUnitService::resolveUnitBuyTheme($unit, $buyDefaults),
        ];
    }
    return $choices;
}

/** @return array<int, array<string, mixed>> */
function shopPaymentChoices(array $option, array $unitIndex, array $buyDefaults): array
{
    return shopPaymentChoicesFromPrices(
        is_array($option['prices'] ?? null) ? $option['prices'] : [],
        $unitIndex,
        $buyDefaults
    );
}

/** @return array<int, array<string, mixed>> */
function shopGiftPaymentChoices(array $option, array $unitIndex, array $buyDefaults): array
{
    $gift = is_array($option['gift'] ?? null) ? $option['gift'] : [];
    if (empty($gift['enabled'])) {
        return [];
    }
    $enabledUnits = is_array($gift['enabledUnits'] ?? null) ? $gift['enabledUnits'] : [];
    $prices = !empty($gift['useCustomPrices']) && is_array($gift['prices'] ?? null)
        ? $gift['prices']
        : (is_array($option['prices'] ?? null) ? $option['prices'] : []);

    return shopPaymentChoicesFromPrices($prices, $unitIndex, $buyDefaults, $enabledUnits);
}

/** @return array<string, mixed> */
function shopRoleListCta(array $options, array $unitIndex, array $buyDefaults): array
{
    $firstOption = $options[0] ?? null;
    if ($firstOption && count($options) === 1) {
        $choices = shopPaymentChoices($firstOption, $unitIndex, $buyDefaults);
        if ($choices && count($choices) <= 2) {
            return [
                'mode' => 'prices',
                'optionId' => (string) ($firstOption['id'] ?? ''),
                'choices' => $choices,
            ];
        }
    }

    return [
        'mode' => 'buy',
        'optionId' => (string) ($firstOption['id'] ?? ''),
        'choices' => [],
    ];
}

function shopRoleListCtaHtml(array $cta): string
{
    if (($cta['mode'] ?? '') === 'prices') {
        $lines = [];
        foreach (($cta['choices'] ?? []) as $choice) {
            $lines[] = '<span class="shop-role-cta-line" data-shop-open-option="' . shopEsc((string) ($cta['optionId'] ?? '')) . '" data-shop-open-payment="' . shopEsc((string) ($choice['unitCode'] ?? '')) . '">' . ($choice['moneyHtml'] ?? '') . '</span>';
        }
        return '<span class="shop-role-cta is-price-stack" aria-label="เลือกราคาซื้อ">' . implode('', $lines) . '</span>';
    }

    return '<span class="shop-role-cta is-buy" aria-label="ดูรายละเอียดและซื้อ">ซื้อ</span>';
}

function shopProductPayload(array $product, ?array $role, array $unitIndex, array $buyDefaults, ?array $group = null): string
{
    $options = shopVisibleOptions($product);
    $firstOption = $options[0] ?? null;
    $payload = [
        'id' => $product['id'] ?? '',
        'type' => $product['type'] ?? 'item',
        'name' => shopDisplayName($product, $role),
        'roleName' => $role['roleName'] ?? '',
        'roleTier' => $role['roleTier'] ?? '',
        'series' => $role['roleSeriesName'] ?? '',
        'seriesBadge' => $role['roleSeriesBadge'] ?? '',
        'image' => shopProductImage($product, $role),
        'badge' => $product['badge'] ?? '',
        'roleColor' => $role['roleColorHex'] ?? shopRoleColor($role),
        'roleIconUrl' => $role['roleIconUrl'] ?? '',
        'badges' => shopProductBadgeLabels($product, $firstOption, $role, $group),
        'groupName' => $group['name'] ?? '',
        'groupBadge' => $group['badge'] ?? '',
        'durationSummaryText' => shopDurationSummaryText($options),
        'detailText' => $product['detailText'] ?? '',
        'conditionText' => $product['conditionText'] ?? '',
        'effectType' => $product['effectType'] ?? '',
        'permissionDetails' => array_values(array_filter($role['permissionDetails'] ?? [], 'is_array')),
        'options' => array_map(static function (array $option) use ($unitIndex, $buyDefaults): array {
            $choices = shopPaymentChoices($option, $unitIndex, $buyDefaults);
            $giftChoices = shopGiftPaymentChoices($option, $unitIndex, $buyDefaults);
            return [
                'id' => $option['id'] ?? '',
                'label' => shopDurationLabel($option),
                'days' => (int) ($option['days'] ?? 0),
                'prices' => $option['prices'] ?? [],
                'paymentChoices' => $choices,
                'gift' => $option['gift'] ?? ['enabled' => false, 'useCustomPrices' => false, 'enabledUnits' => [], 'prices' => []],
                'giftPaymentChoices' => $giftChoices,
                'priceText' => $choices ? implode(' / ', array_map(static fn (array $choice): string => strip_tags((string) ($choice['moneyHtml'] ?? '')), $choices)) : 'ฟรี',
                'giftPriceText' => $giftChoices ? implode(' / ', array_map(static fn (array $choice): string => strip_tags((string) ($choice['moneyHtml'] ?? '')), $giftChoices)) : '',
            ];
        }, $options),
    ];
    return htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}', ENT_QUOTES, 'UTF-8');
}

function shopJsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function shopReadInput(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '', true);
    return is_array($decoded) ? array_merge($_POST, $decoded) : $_POST;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = shopReadInput();
    if (($input['action'] ?? '') === 'purchase_role_badge') {
        if (!$player) {
            shopJsonResponse(['ok' => false, 'message' => 'login required'], 401);
        }

        try {
            $result = ShopRoleBadgeService::purchaseBadge(
                $guildId,
                (string) ($player['userId'] ?? ''),
                trim((string) ($input['productId'] ?? '')),
                trim((string) ($input['optionId'] ?? '')),
                trim((string) ($input['paymentUnitCode'] ?? ''))
            );
            shopJsonResponse($result);
        } catch (Throwable $error) {
            $message = match (true) {
                str_starts_with($error->getMessage(), 'INSUFFICIENT_BALANCE:') => 'ยอดเงินไม่พอสำหรับซื้อยศนี้',
                $error->getMessage() === 'ROLE_PRODUCT_NOT_FOUND' => 'ไม่พบยศที่ต้องการซื้อ',
                $error->getMessage() === 'ROLE_OPTION_NOT_FOUND' => 'ไม่พบตัวเลือกการซื้อ',
                $error->getMessage() === 'INVALID_PAYMENT_UNIT' => 'ช่องทางจ่ายนี้ยังไม่พร้อมซื้อ',
                $error->getMessage() === 'ROLE_NOT_FOUND' => 'ไม่พบ role นี้ในเซิร์ฟเวอร์',
                default => 'ซื้อยศไม่สำเร็จ ลองใหม่อีกครั้ง',
            };
            shopJsonResponse(['ok' => false, 'message' => $message], 400);
        }
    }
    if (($input['action'] ?? '') === 'gift_role_badge') {
        if (!$player) {
            shopJsonResponse(['ok' => false, 'message' => 'login required'], 401);
        }

        try {
            $result = ShopRoleBadgeService::giftBadge(
                $guildId,
                (string) ($player['userId'] ?? ''),
                trim((string) ($input['productId'] ?? '')),
                trim((string) ($input['optionId'] ?? '')),
                trim((string) ($input['paymentUnitCode'] ?? '')),
                trim((string) ($input['targetUserId'] ?? ''))
            );
            shopJsonResponse($result);
        } catch (Throwable $error) {
            $message = match (true) {
                str_starts_with($error->getMessage(), 'INSUFFICIENT_BALANCE:') => 'ยอดเงินไม่พอสำหรับส่งของขวัญนี้',
                $error->getMessage() === 'INVALID_GIFT_PAYMENT_UNIT' => 'ช่องทางจ่ายนี้ยังไม่เปิดสำหรับส่งของขวัญ',
                $error->getMessage() === 'TARGET_IS_SELF' => 'ส่งให้ตัวเองไม่ได้ ใช้ปุ่มซื้อเข้ากระเป๋าแทน',
                $error->getMessage() === 'TARGET_NOT_FOUND' => 'ไม่พบเพื่อนคนนี้ในเซิร์ฟเวอร์',
                $error->getMessage() === 'ROLE_PRODUCT_NOT_FOUND' => 'ไม่พบยศที่ต้องการส่ง',
                $error->getMessage() === 'ROLE_OPTION_NOT_FOUND' => 'ไม่พบตัวเลือกการส่ง',
                $error->getMessage() === 'ROLE_NOT_FOUND' => 'ไม่พบ role นี้ในเซิร์ฟเวอร์',
                default => 'ส่งของขวัญไม่สำเร็จ ลองใหม่อีกครั้ง',
            };
            shopJsonResponse(['ok' => false, 'message' => $message], 400);
        }
    }
    if (($input['action'] ?? '') === 'search_gift_members') {
        if (!$player) {
            shopJsonResponse(['ok' => false, 'message' => 'login required'], 401);
        }
        $members = ShopRoleBadgeService::searchGuildMembers($guildId, trim((string) ($input['query'] ?? '')), 8);
        $currentUserId = (string) ($player['userId'] ?? '');
        $members = array_values(array_filter($members, static fn (array $member): bool => (string) ($member['userId'] ?? '') !== $currentUserId));
        shopJsonResponse(['ok' => true, 'members' => $members]);
    }
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>ร้านค้า</title>
  <?php if ($shopSwiperCssHref !== ''): ?><link rel="stylesheet" href="<?= shopEsc($shopSwiperCssHref) ?>"><?php endif; ?>
  <style>
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

    :root {
      --shop-font: "FC Vision Rounded", sans-serif;
      --shop-ink: #48315c;
      --shop-muted: rgba(72, 49, 92, .66);
      --shop-line: rgba(123, 94, 155, .16);
      --shop-paper: rgba(255, 255, 255, .86);
      --shop-paper-strong: rgba(255, 255, 255, .95);
      --shop-shadow: rgba(98, 71, 117, .12);
      --shop-pink: #f171a7;
      --shop-cyan: #65c9d2;
      --shop-gold: #e8ad3c;
      --shop-purple: #7e5ad8;
    }

    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    html, body { margin: 0; min-height: 100%; }
    body {
      color: var(--shop-ink);
      font-family: var(--shop-font);
      background:
        radial-gradient(circle at top, rgba(255, 238, 247, .94), rgba(255,255,255,0) 32%),
        linear-gradient(180deg, rgba(255,247,251,.98) 0%, rgba(247,245,255,.98) 48%, rgba(238,248,255,.99) 100%);
      overflow-x: hidden;
    }
    button { font: inherit; }
    img { display: block; max-width: 100%; }

    .shop-shell {
      min-height: 100svh;
      padding: calc(env(safe-area-inset-top, 0px) + <?= $isEmbed ? '40' : '32' ?>px) 16px calc(env(safe-area-inset-bottom, 0px) + 420px);
    }
    .shop-shell.is-embed {
      padding-top: calc(env(safe-area-inset-top, 0px) + 112px);
    }
    .shop-shell [hidden],
    .shop-modal [hidden] {
      display: none !important;
    }
    .shop-shell-inner {
      max-width: 980px;
      margin: 0 auto;
      display: grid;
      gap: 16px;
    }
    .shop-wallet-chip,
    .shop-price-chip,
    .shop-badge,
    .shop-tab {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      border: 1px solid var(--shop-line);
      background: var(--shop-paper-strong);
    }
    .shop-wallet-chip {
      min-height: 34px;
      padding: 0 11px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      box-shadow: 0 10px 22px rgba(80, 67, 96, .08);
    }
    .shop-unit-icon {
      width: 20px;
      height: 20px;
      display: inline-grid;
      place-items: center;
      border-radius: 999px;
      background: linear-gradient(180deg, #fff0c6, #eab75d);
      color: #79521a;
      font-size: 11px;
      font-weight: 900;
      box-shadow: inset 0 1px 0 rgba(255,255,255,.78);
    }
    .shop-unit-icon.is-image {
      padding: 0;
      overflow: hidden;
      background: rgba(255,255,255,.78);
    }
    .shop-unit-icon.is-image img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: cover;
    }
    .shop-money-inline {
      --shop-money-icon-size: 1.32em;
      --shop-money-image-scale: 1.14;
      display: inline-flex;
      align-items: center;
      gap: .28em;
      line-height: 1;
      vertical-align: middle;
      white-space: nowrap;
    }
    .shop-money-amount {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: var(--shop-money-icon-size);
      line-height: 1;
      font-variant-numeric: tabular-nums;
      min-height: 1em;
    }
    .shop-money-icon {
      width: var(--shop-money-icon-size);
      height: var(--shop-money-icon-size);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
      vertical-align: middle;
    }
    .shop-money-icon.is-image img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: contain;
      transform: scale(var(--shop-money-image-scale));
      transform-origin: center;
    }
    .shop-money-icon.is-glyph {
      border-radius: 999px;
      background: linear-gradient(180deg, #fff0c6, #eab75d);
      color: #79521a;
      font-size: .72em;
      font-weight: 900;
      box-shadow: inset 0 1px 0 rgba(255,255,255,.78);
    }
    .shop-visually-hidden {
      position: absolute;
      width: 1px;
      height: 1px;
      margin: -1px;
      padding: 0;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }
    .shop-tabs {
      position: sticky;
      top: calc(env(safe-area-inset-top, 0px) + 18px);
      z-index: 10;
      display: inline-flex;
      width: fit-content;
      gap: 6px;
      padding: 5px;
      border-radius: 999px;
      background: rgba(255, 255, 255, .78);
      border: 1px solid rgba(255,255,255,.78);
      box-shadow: 0 10px 18px rgba(98, 71, 117, .08);
    }
    .shop-shell.is-embed .shop-tabs {
      top: calc(env(safe-area-inset-top, 0px) + 88px);
    }
    .shop-tab {
      min-height: 36px;
      padding: 0 18px;
      border-radius: 999px;
      color: var(--shop-muted);
      cursor: pointer;
      font-weight: 800;
    }
    .shop-tab.is-active {
      color: #fff;
      border-color: transparent;
      background: linear-gradient(135deg, var(--shop-pink), var(--shop-cyan));
      box-shadow: 0 10px 18px rgba(88, 128, 184, .16);
    }
    .shop-panel { display: none; }
    .shop-panel.is-active { display: grid; gap: 22px; }
    .shop-section {
      display: grid;
      gap: 10px;
    }
    .shop-section-head {
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 10px;
      padding: 0 2px;
    }
    .shop-section-head h2 {
      margin: 0;
      font-size: 20px;
      line-height: 1.15;
      font-weight: 700;
      color: #49305f;
    }
    .shop-section-head span {
      color: var(--shop-muted);
      font-size: 12px;
      font-weight: 700;
    }
    .shop-section-head > div span {
      display: block;
      margin-top: 4px;
    }
    .shop-shelf-row {
      display: block;
      padding: 10px 12px 18px;
      border-radius: 24px;
      border: 1px solid var(--shop-line);
      background:
        linear-gradient(180deg, rgba(255,255,255,.64), rgba(255,255,255,.34)),
        linear-gradient(135deg, rgba(255,231,242,.48), rgba(231,248,255,.44));
      overflow: hidden;
      box-shadow: inset 0 1px 0 rgba(255,255,255,.86), 0 14px 24px rgba(98,71,117,.08);
    }
    .shop-shelf {
      position: relative;
      min-width: 0;
      min-height: 232px;
      padding: 14px 6px 20px;
    }
    .shop-shelf::before {
      content: "";
      position: absolute;
      left: 2px;
      right: 2px;
      bottom: 24px;
      height: 30px;
      border-radius: 999px;
      background:
        linear-gradient(180deg, rgba(255, 230, 178, .92), rgba(221, 151, 91, .96));
      box-shadow:
        inset 0 2px 0 rgba(255, 255, 255, .54),
        inset 0 -5px 0 rgba(151, 88, 56, .16),
        0 14px 18px rgba(109, 63, 65, .18);
    }
    .shop-shelf::after {
      content: "";
      position: absolute;
      left: 18px;
      right: 18px;
      bottom: 12px;
      height: 14px;
      border-radius: 999px;
      background: rgba(114, 63, 67, .15);
      filter: blur(8px);
    }
    .shop-shelf-rail {
      position: relative;
      z-index: 1;
      display: grid;
      grid-auto-flow: column;
      grid-auto-columns: minmax(168px, 196px);
      gap: 16px;
      align-items: end;
      overflow-x: auto;
      overscroll-behavior-x: contain;
      padding: 0 4px;
      scrollbar-width: thin;
    }
    .shop-product {
      position: relative;
      display: grid;
      justify-items: center;
      align-content: end;
      gap: 0;
      min-width: 0;
      min-height: 188px;
      padding: 0 4px 8px;
      border: 0;
      background: transparent;
      color: inherit;
      cursor: pointer;
      text-align: center;
    }
    .shop-product:focus-visible,
    .shop-modal-close:focus-visible {
      outline: 2px solid color-mix(in srgb, var(--shop-pink) 72%, white);
      outline-offset: 2px;
    }
    .shop-product-visual {
      display: grid;
      place-items: end center;
      min-height: 148px;
      width: 100%;
      padding-bottom: 4px;
    }
    .shop-product-figure {
      position: relative;
      display: grid;
      place-items: end center;
      width: min(100%, 124px);
      height: 140px;
      min-height: 140px;
      align-self: end;
    }
    .shop-product-media {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: end center;
      z-index: 1;
    }
    .shop-product-media img {
      width: 100%;
      height: 100%;
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      filter: drop-shadow(0 14px 12px rgba(75,43,94,.16));
    }
    .shop-product-nameplate {
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
      border: 1px solid rgba(255,255,255,.74);
      background:
        linear-gradient(180deg, rgba(255,240,214,.98), rgba(244,202,127,.98));
      box-shadow:
        inset 0 2px 0 rgba(255,250,236,.76),
        0 8px 12px rgba(133, 82, 51, .12);
    }
    .shop-product-nameplate strong,
    .shop-role-copy strong {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      font-weight: 800;
    }
    .shop-product-nameplate strong {
      max-width: 100%;
      color: #694323;
      font-size: 12px;
      font-weight: 500;
      line-height: 1.18;
    }
    .shop-product-badges {
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
    .shop-product-badges .shop-badge {
      min-height: 24px;
      max-width: 100%;
      padding: 2px 8px;
      justify-content: center;
      font-size: 11px;
      font-weight: 500;
      background: rgba(255,255,255,.88);
      border: 1px solid rgba(255,255,255,.92);
      color: #61437a;
      overflow: hidden;
      text-overflow: ellipsis;
      box-shadow:
        inset 0 1px 0 rgba(255,255,255,.9),
        0 8px 14px rgba(87,59,106,.08);
    }
    .shop-product-price-float {
      position: absolute;
      right: -2px;
      bottom: -2px;
      z-index: 5;
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 4px;
      max-width: 164px;
      pointer-events: none;
    }
    .shop-product-price-float .shop-price-chip {
      min-height: 26px;
      padding: 3px 8px;
      border-color: rgba(255,255,255,.86);
      background: rgba(255,255,255,.96);
      box-shadow:
        inset 0 1px 0 rgba(255,255,255,.78),
        0 8px 13px rgba(105,70,26,.16);
    }
    .shop-product-price-float .shop-money-inline {
      --shop-money-icon-size: 1.18em;
      --shop-money-image-scale: 1.08;
    }
    .shop-badge-row,
    .shop-option-row,
    .shop-price-row {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      align-items: center;
    }
    .shop-badge {
      min-height: 22px;
      padding: 0 8px;
      border-radius: 999px;
      color: var(--shop-muted);
      font-size: 11px;
      font-weight: 800;
      line-height: 1;
      white-space: nowrap;
    }
    .shop-badge.is-days {
      color: #8a4a12;
      background: #fff0c6;
      border-color: #ffd98a;
    }
    .shop-badge.is-series {
      color: #533580;
      background: #efe6ff;
      border-color: #d9c6ff;
    }
    .shop-price-chip {
      min-height: 24px;
      padding: 0 8px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 800;
      line-height: 1;
      background: #fff;
      border-color: rgba(230, 225, 236, .98);
      box-shadow: 0 8px 16px rgba(82, 61, 98, .1);
    }
    .shop-price-chip small {
      color: var(--shop-muted);
      font-size: 10px;
    }
    .shop-role-group {
      display: grid;
      gap: 12px;
      padding: 0 0 14px;
      border-radius: 26px;
      border: 1px solid rgba(132, 98, 162, .14);
      background: rgba(255,255,255,.9);
      overflow: hidden;
    }
    .shop-role-group-head {
      display: grid;
      gap: 0;
      padding: 0;
    }
    .shop-role-group-head-main {
      min-width: 0;
      display: grid;
      gap: 6px;
    }
    .shop-role-group-head > .shop-role-group-head-main {
      padding: 12px 16px 0;
    }
    .shop-role-group-title-row {
      min-width: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .shop-role-group-title-wrap {
      min-width: 0;
      flex: 1;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .shop-role-group-icon {
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      flex: none;
      overflow: hidden;
      border-radius: 999px;
      border: 1px solid rgba(132, 98, 162, .14);
      background: #fff;
    }
    .shop-role-group-icon img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: cover;
      object-position: center;
    }
    .shop-role-group-title-text {
      min-width: 0;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 6px 8px;
    }
    .shop-role-group-title-text h2 {
      margin: 0;
      color: #49305f;
      font-size: 18px;
      line-height: 1.16;
      font-weight: 800;
    }
    .shop-role-group-caption {
      color: var(--shop-muted);
      font-size: 12px;
      line-height: 1.5;
      font-weight: 600;
    }
    .shop-role-group-banner {
      width: 100%;
      display: block;
      object-fit: cover;
      object-position: center;
      pointer-events: none;
      border-radius: 20px;
      border: 1px solid rgba(132, 98, 162, .14);
    }
    .shop-role-group-banner::-webkit-media-controls,
    .shop-role-group-banner::-webkit-media-controls-enclosure {
      display: none !important;
    }
    .shop-role-group-head > .shop-role-group-banner {
      height: 96px;
      border-radius: 0;
      border: 0;
      box-shadow: none;
    }
    .shop-role-group-badge {
      min-height: 22px;
      display: inline-flex;
      align-items: center;
      padding: 0 8px;
      border-radius: 999px;
      border: 1px solid rgba(216, 205, 231, .95);
      background: rgba(236, 232, 244, .98);
      color: #745186 !important;
      font-size: 11px;
      font-weight: 700;
      white-space: nowrap;
    }
    .shop-role-viewer {
      display: grid;
      gap: 8px;
      padding: 0;
    }
    .shop-role-viewer[data-view="slider"] [data-shop-role-list] {
      display: none;
    }
    .shop-role-viewer[data-view="list"] [data-shop-role-slider-shell] {
      display: none;
    }
    .shop-role-view-toggle {
      width: 24px;
      height: 24px;
      padding: 0;
      display: inline-grid;
      place-items: center;
      border: 0;
      background: transparent;
      color: rgba(74, 49, 95, .74);
      cursor: pointer;
      transition: color .16s ease;
      flex: none;
    }
    .shop-role-view-toggle:hover {
      color: #4a315f;
    }
    .shop-role-view-toggle svg {
      width: 14px;
      height: 14px;
      display: block;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      vector-effect: non-scaling-stroke;
    }
    .shop-role-slider-shell {
      min-width: 0;
      padding: 0 16px 2px;
    }
    .shop-role-swiper {
      overflow: hidden;
      padding-bottom: 2px;
      scrollbar-width: none;
      -ms-overflow-style: none;
      cursor: grab;
      user-select: none;
      -webkit-user-select: none;
      touch-action: pan-y;
    }
    .shop-role-swiper::-webkit-scrollbar {
      display: none;
    }
    .shop-role-swiper:active {
      cursor: grabbing;
    }
    .shop-role-swiper .swiper-wrapper {
      align-items: stretch;
    }
    .shop-role-swiper .swiper-slide {
      width: clamp(238px, calc(100vw - 132px), 318px);
      height: auto;
    }
    .shop-role-card {
      width: 100%;
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
      gap: 10px;
      align-items: center;
      min-height: 70px;
      padding: 3px 12px;
      border: 1px solid rgba(132, 98, 162, .14);
      border-radius: 100px;
      background: rgba(255,255,255,.88);
      color: inherit;
      text-align: left;
      cursor: pointer;
      box-shadow: none;
      transition: border-color .18s ease, background .18s ease;
    }
    .shop-role-avatar {
      width: 54px;
      height: 54px;
      display: grid;
      place-items: center;
      border-radius: 999px;
      border: 0;
      background: transparent;
      overflow: hidden;
      box-shadow: none;
      flex: none;
    }
    .shop-role-avatar img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: cover;
      object-position: center;
      border-radius: inherit;
      -webkit-user-drag: none;
    }
    .shop-role-card-copy,
    .shop-role-row-copy {
      min-width: 0;
      display: grid;
      gap: 6px;
    }
    .shop-role-heading {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 5px 8px;
    }
    .shop-role-name {
      color: var(--role-color, var(--shop-ink));
      font-size: 18px;
      line-height: 1.15;
      letter-spacing: .01em;
    }
    .shop-role-inline-badges {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px;
    }
    .shop-role-inline-badge,
    .shop-role-days {
      min-height: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 8px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 700;
      line-height: 1;
      white-space: nowrap;
    }
    .shop-role-inline-badge {
      color: #6f6878;
      background: rgba(255,255,255,.32);
      border: 1px solid rgba(139, 132, 148, .42);
    }
    .shop-role-days {
      color: #6f6878;
      background: rgba(255,255,255,.32);
      border: 1px solid rgba(139, 132, 148, .42);
    }
    .shop-role-summary {
      color: rgba(94, 74, 122, .78);
      font-size: 12px;
      line-height: 1.45;
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 1;
      overflow: hidden;
    }
    .shop-role-list {
      display: grid;
      gap: 0;
      padding: 0 16px;
    }
    .shop-role-list-row {
      width: 100%;
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
      gap: 12px;
      align-items: center;
      padding: 12px 20px;
      border: 0;
      border-top: 1px solid rgba(123, 94, 155, .14);
      background: transparent;
      color: inherit;
      text-align: left;
      cursor: pointer;
    }
    .shop-role-list-row:first-child {
      border-top: 0;
    }
    .shop-role-list-row .shop-role-avatar {
      width: 48px;
      height: 48px;
    }
    .shop-role-list-row .shop-role-name {
      font-size: 16px;
    }
    .shop-role-list-row .shop-role-summary {
      font-size: 11px;
      -webkit-line-clamp: 1;
    }
    .shop-role-list-row .shop-role-inline-badge,
    .shop-role-list-row .shop-role-days {
      min-height: 22px;
      padding: 0 9px;
      font-size: 10.5px;
    }
    .shop-role-card:focus-visible,
    .shop-role-list-row:focus-visible,
    .shop-role-view-toggle:focus-visible {
      outline: 2px solid color-mix(in srgb, #7657b7 76%, white);
      outline-offset: 2px;
    }
    .shop-empty {
      padding: 24px 14px;
      border: 1px dashed var(--shop-line);
      border-radius: 16px;
      background: rgba(255,255,255,.68);
      color: var(--shop-muted);
      text-align: center;
      font-weight: 800;
    }
    .shop-modal {
      position: fixed;
      inset: 0;
      z-index: 30;
      display: none;
      place-items: center;
      padding: 18px;
      background: rgba(18, 16, 24, .26);
      backdrop-filter: blur(8px);
    }
    .shop-modal.is-open { display: grid; }
    .shop-modal-card {
      width: min(100%, 468px);
      max-height: min(70svh, 620px);
      display: grid;
      grid-template-rows: auto minmax(0, 1fr);
      border-radius: 26px;
      border: 1px solid rgba(136, 102, 168, .18);
      background:
        linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(255, 248, 255, .96));
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, .94),
        0 24px 70px rgba(25, 22, 33, .22);
      overflow: hidden;
    }
    .shop-modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 18px 18px 12px;
      border-bottom: 1px solid rgba(134, 100, 165, .12);
    }
    .shop-modal-head-actions {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .shop-modal-head h3 {
      margin: 0;
      font-size: 21px;
      line-height: 1.16;
      font-weight: 800;
      color: #4b3161;
    }
    .shop-modal-close {
      width: 42px;
      height: 42px;
      border: 0;
      border-radius: 16px;
      background: linear-gradient(180deg, rgba(255, 236, 242, .96), rgba(244, 200, 218, .96));
      color: #8b4d69;
      cursor: pointer;
      font-size: 22px;
      line-height: 1;
      box-shadow:
        inset 0 2px 0 rgba(255,255,255,.76),
        0 10px 16px rgba(112, 76, 120, .12);
    }
    .shop-modal-gift-toggle {
      width: 42px;
      height: 42px;
      border: 0;
      border-radius: 16px;
      background: linear-gradient(180deg, rgba(231, 251, 255, .98), rgba(193, 229, 244, .96));
      color: #356b7b;
      cursor: pointer;
      font-size: 17px;
      line-height: 1;
      box-shadow:
        inset 0 2px 0 rgba(255,255,255,.76),
        0 10px 16px rgba(76, 112, 120, .1);
    }
    .shop-modal-gift-toggle[hidden] {
      display: none;
    }
    .shop-modal-gift-toggle.is-active {
      background: linear-gradient(180deg, rgba(255, 241, 247, .98), rgba(255, 213, 236, .96));
      color: #9b4e78;
    }
    .shop-modal-body {
      overflow: auto;
      display: grid;
      gap: 12px;
      padding: 14px 16px 16px;
    }
    .shop-modal-hero {
      position: relative;
      display: grid;
      place-items: center;
      min-height: 164px;
      padding: 8px 12px 10px;
      border-radius: 22px;
      background:
        radial-gradient(circle at top, rgba(255,255,255,.92), rgba(255,255,255,0) 62%),
        linear-gradient(180deg, rgba(255,244,250,.84), rgba(245,249,255,.72));
    }
    .shop-modal-hero img {
      width: min(162px, 52vw);
      height: 140px;
      object-fit: contain;
      filter: drop-shadow(0 16px 14px rgba(90, 62, 104, .16));
    }
    .shop-modal-hero.is-role-preview > img {
      display: none;
    }
    .shop-modal-role-preview {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      min-height: 62px;
      max-width: 100%;
      padding: 0 18px;
      border-radius: 14px;
      background: #f5f7fa;
      box-shadow:
        inset 0 1px 0 rgba(255,255,255,.88),
        0 12px 18px rgba(89, 62, 104, .08);
    }
    .shop-modal-role-preview[hidden] {
      display: none;
    }
    .shop-modal-role-dot {
      width: 28px;
      height: 28px;
      flex: 0 0 auto;
      border-radius: 999px;
      background: #8b91a1;
    }
    .shop-modal-role-icon {
      width: 30px;
      height: 30px;
      display: inline-grid;
      place-items: center;
      flex: 0 0 auto;
    }
    .shop-modal-role-icon[hidden] {
      display: none;
    }
    .shop-modal-role-icon img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      filter: drop-shadow(0 8px 10px rgba(90, 62, 104, .16));
    }
    .shop-modal-role-name {
      min-width: 0;
      font-size: clamp(24px, 3.4vw, 30px);
      line-height: 1.04;
      font-weight: 700;
      text-align: center;
      overflow-wrap: anywhere;
    }
    .shop-modal-title-wrap {
      display: grid;
      gap: 4px;
    }
    .shop-modal-title-wrap h4 {
      margin: 0;
      font-size: 34px;
      line-height: 1.08;
      font-weight: 800;
      color: #111111;
    }
    .shop-modal-subtitle {
      margin: 0;
      font-size: 13px;
      line-height: 1.5;
      color: rgba(74, 49, 95, .72);
      font-style: italic;
    }
    .shop-modal-facts {
      display: grid;
      gap: 8px;
    }
    .shop-modal-fact {
      display: grid;
      grid-template-columns: 118px minmax(0, 1fr);
      gap: 10px;
      padding: 8px 10px;
      border-bottom: 1px solid rgba(134, 100, 165, .12);
      color: rgba(74, 49, 95, .82);
      font-size: 12px;
      line-height: 1.45;
    }
    .shop-modal-fact strong {
      font-weight: 700;
      color: #4a315f;
    }
    .shop-modal-section {
      display: grid;
      gap: 10px;
      padding: 12px;
      border: 1px solid rgba(134, 100, 165, .12);
      border-radius: 18px;
      background: rgba(255,255,255,.7);
    }
    .shop-modal-section h4 {
      margin: 0;
      font-size: 13px;
      color: #4a315f;
      font-weight: 800;
    }
    .shop-modal-desc {
      color: rgba(70, 48, 90, .86);
      font-size: 13px;
      line-height: 1.7;
    }
    .shop-modal-desc p {
      margin: 0 0 8px;
    }
    .shop-modal-desc p:last-child {
      margin-bottom: 0;
    }
    .shop-modal-ability-list {
      display: grid;
      gap: 0;
    }
    .shop-modal-ability-item {
      display: grid;
      gap: 5px;
      padding: 10px 0;
      border-top: 1px solid rgba(134, 100, 165, .12);
    }
    .shop-modal-ability-item:first-child {
      padding-top: 0;
      border-top: 0;
    }
    .shop-modal-ability-item strong {
      font-size: 13px;
      line-height: 1.35;
      font-weight: 700;
      color: #4a315f;
    }
    .shop-modal-ability-item span {
      color: rgba(74, 49, 95, .72);
      font-size: 12px;
      line-height: 1.6;
    }
    .shop-purchase-option-list {
      display: grid;
      gap: 8px;
    }
    .shop-purchase-option {
      width: 100%;
      border: 1px solid rgba(134, 100, 165, .12);
      border-radius: 16px;
      padding: 12px 14px;
      background: rgba(248, 244, 255, .88);
      color: inherit;
      text-align: left;
      cursor: pointer;
      transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }
    .shop-purchase-option.is-active {
      border-color: rgba(122, 91, 214, .42);
      background:
        linear-gradient(180deg, rgba(255, 247, 252, .98), rgba(245, 238, 255, .96));
      box-shadow: 0 12px 18px rgba(98, 71, 117, .1);
      transform: translateY(-1px);
    }
    .shop-purchase-option strong {
      display: block;
      margin-bottom: 3px;
      font-size: 14px;
      color: #463058;
    }
    .shop-purchase-option span {
      display: block;
      color: var(--shop-muted);
      font-size: 12px;
      line-height: 1.45;
    }
    .shop-modal-status {
      min-height: 20px;
      color: #6b4f8f;
      font-size: 12px;
      font-weight: 700;
      text-align: center;
    }
    .shop-modal-status.is-error {
      color: #a33e5c;
    }
    .shop-payment-wrap {
      display: grid;
      gap: 8px;
    }
    .shop-payment-row {
      display: flex;
      flex-wrap: nowrap;
      gap: 8px;
      overflow-x: auto;
      overscroll-behavior-x: contain;
      padding: 2px 1px 8px;
      scrollbar-width: thin;
    }
    .shop-payment-empty {
      padding: 12px;
      border: 1px dashed rgba(134, 100, 165, .18);
      border-radius: 14px;
      background: rgba(255,255,255,.62);
      color: var(--shop-muted);
      font-size: 12px;
      font-weight: 800;
      text-align: center;
    }
    .shop-gift-panel {
      display: grid;
      gap: 8px;
      padding: 10px;
      border-radius: 16px;
      background: rgba(239, 249, 255, .72);
      border: 1px solid rgba(119, 171, 194, .16);
    }
    .shop-gift-panel[hidden] {
      display: none;
    }
    .shop-gift-input {
      width: 100%;
      min-height: 42px;
      border: 1px solid rgba(134, 100, 165, .16);
      border-radius: 13px;
      padding: 0 12px;
      background: rgba(255,255,255,.92);
      color: #4a315f;
      font: inherit;
      font-size: 13px;
      outline: none;
    }
    .shop-gift-target,
    .shop-gift-status {
      color: var(--shop-muted);
      font-size: 12px;
      font-weight: 800;
    }
    .shop-gift-target strong {
      color: #4a315f;
    }
    .shop-gift-results {
      display: grid;
      gap: 6px;
    }
    .shop-gift-result {
      display: grid;
      grid-template-columns: 34px minmax(0, 1fr);
      gap: 9px;
      align-items: center;
      width: 100%;
      min-height: 44px;
      padding: 5px 8px;
      border: 0;
      border-radius: 12px;
      background: rgba(255,255,255,.78);
      color: inherit;
      cursor: pointer;
      text-align: left;
    }
    .shop-gift-result img {
      width: 34px;
      height: 34px;
      border-radius: 999px;
      object-fit: cover;
    }
    .shop-gift-result strong,
    .shop-gift-result span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      display: block;
    }
    .shop-gift-result strong {
      font-size: 13px;
      color: #4a315f;
    }
    .shop-gift-result span {
      font-size: 11px;
      color: var(--shop-muted);
    }
    .shop-hold-hint {
      min-height: 18px;
      color: #8d5b77;
      font-size: 12px;
      font-weight: 800;
      text-align: center;
      opacity: 0;
      transform: translateY(-2px);
      transition: opacity .16s ease, transform .16s ease;
    }
    .shop-hold-hint.is-visible {
      opacity: 1;
      transform: translateY(0);
    }
    .shop-buy-button {
      position: relative;
      isolation: isolate;
      flex: 1 1 0;
      min-width: 0;
      width: 100%;
      min-height: 48px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      overflow: hidden;
      border: 0;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--shop-buy-bg-start, #4b3161), var(--shop-buy-bg-end, #7657b7));
      color: var(--shop-buy-text, #fff);
      font-weight: 800;
      cursor: pointer;
      box-shadow: 0 16px 24px rgba(75, 49, 97, .18);
      transition: opacity .18s ease, transform .18s ease;
      white-space: nowrap;
      touch-action: manipulation;
      user-select: none;
    }
    .shop-buy-button::before {
      content: "";
      position: absolute;
      inset: 0;
      z-index: -1;
      background: linear-gradient(90deg, var(--shop-hold-start, #f8b86f), var(--shop-hold-end, #ffe590));
      transform: scaleX(var(--shop-hold-progress, 0));
      transform-origin: left center;
      opacity: .92;
      transition: transform .08s linear;
    }
    .shop-buy-button.is-holding::before {
      transition: transform var(--shop-hold-seconds, 1s) linear;
      transform: scaleX(1);
    }
    .shop-buy-button .shop-money-inline {
      --shop-money-icon-size: 1.42em;
      --shop-money-image-scale: 1.2;
      font-size: 14px;
    }
    .shop-success-actions {
      display: flex;
      flex-wrap: nowrap;
      gap: 8px;
      overflow-x: auto;
    }
    .shop-success-actions[hidden] {
      display: none;
    }
    .shop-success-button {
      flex: 1 0 0;
      min-width: 132px;
      min-height: 46px;
      border: 0;
      border-radius: 14px;
      background: rgba(236, 232, 244, .98);
      color: #705c8b;
      font-weight: 900;
      cursor: pointer;
    }
    .shop-success-button.is-primary {
      background: linear-gradient(135deg, #4b3161, #7657b7);
      color: #fff;
    }
    .shop-buy-button:disabled {
      opacity: .6;
      cursor: not-allowed;
      box-shadow: none;
    }
    .shop-buy-button:not(:disabled):active {
      transform: translateY(1px) scale(.996);
    }
    @media (max-width: 640px) {
      .shop-shell { padding-left: 12px; padding-right: 12px; }
      .shop-shelf-rail { grid-auto-columns: minmax(152px, 46vw); }
      .shop-shelf-row { padding-left: 8px; padding-right: 8px; }
      .shop-shelf { min-height: 220px; padding-left: 6px; padding-right: 6px; padding-bottom: 20px; }
      .shop-product { min-height: 180px; }
      .shop-product-nameplate { width: min(100%, 118px); min-height: 32px; }
      .shop-product-nameplate strong { font-size: 10.5px; }
      .shop-role-group { border-radius: 24px; }
      .shop-role-group-head > .shop-role-group-head-main { padding: 10px 14px 0; }
      .shop-role-group-icon { width: 34px; height: 34px; }
      .shop-role-group-title-text h2 { font-size: 17px; }
      .shop-role-group-head > .shop-role-group-banner { height: 74px; }
      .shop-role-slider-shell { padding: 0 14px 2px; }
      .shop-role-swiper .swiper-slide { width: clamp(214px, calc(100vw - 142px), 280px); }
      .shop-role-card { min-height: 70px; padding: 3px 12px; gap: 10px; }
      .shop-role-avatar { width: 48px; height: 48px; }
      .shop-role-name { font-size: 16px; }
      .shop-role-inline-badge,
      .shop-role-days { min-height: 20px; padding: 0 8px; font-size: 10px; }
      .shop-role-list-row { padding: 11px 14px; gap: 10px; }
      .shop-role-list-row .shop-role-avatar { width: 42px; height: 42px; }
      .shop-role-list-row .shop-role-name { font-size: 15px; }
      .shop-modal-card { max-height: min(78svh, 620px); }
      .shop-modal-title-wrap h4 { font-size: 28px; }
      .shop-modal-fact { grid-template-columns: 96px minmax(0, 1fr); }
      .shop-buy-button { min-width: 0; }
    }
  </style>
</head>
  <body>
  <main class="shop-shell<?= $isEmbed ? ' is-embed' : '' ?>">
    <div class="shop-shell-inner">
      <nav class="shop-tabs" aria-label="Shop tabs">
        <button class="shop-tab is-active" type="button" data-shop-tab="items">ไอเทม</button>
        <button class="shop-tab" type="button" data-shop-tab="roles">ยศ</button>
      </nav>

      <section class="shop-panel is-active" data-shop-panel="items">
        <?php if (!$itemGroups): ?>
          <div class="shop-empty">ยังไม่มีสินค้าไอเทมที่เปิดโชว์</div>
        <?php else: ?>
          <?php foreach ($itemGroups as $group): ?>
            <?php $groupHasBanner = !empty($group['headerImage']); ?>
            <section class="shop-section">
              <div class="shop-section-head">
                <div class="shop-role-group-head-main">
                  <?php if ($groupHasBanner): ?><?= shopRoleGroupBannerHtml((string) $group['headerImage'], (string) ($group['name'] ?: 'Role series')) ?><?php endif; ?>
                  <h2><?= shopEsc($group['name']) ?></h2>
                  <?php if (!empty($group['description'])): ?><span><?= shopEsc($group['description']) ?></span><?php endif; ?>
                </div>
                <span><?= !empty($group['badge']) ? shopEsc($group['badge']) . ' · ' : '' ?><?= shopEsc(count($group['products'])) ?> รายการ</span>
              </div>
              <div class="shop-shelf-row">
                <div class="shop-shelf">
                  <div class="shop-shelf-rail">
                    <?php foreach ($group['products'] as $product): $options = shopVisibleOptions($product); $firstOption = $options[0] ?? null; $badgeLabels = shopProductBadgeLabels($product, $firstOption, null, $group); ?>
                        <button class="shop-product" type="button" data-shop-product="<?= shopProductPayload($product, null, $unitIndex, $shopBuyDefaults, $group) ?>">
                          <span class="shop-product-visual">
                            <span class="shop-product-figure">
                              <?php if ($badgeLabels): ?>
                                <span class="shop-product-badges">
                                  <?php foreach ($badgeLabels as $badgeLabel): ?><span class="shop-badge"><?= shopEsc($badgeLabel) ?></span><?php endforeach; ?>
                                </span>
                              <?php endif; ?>
                              <span class="shop-product-media"><img src="<?= shopEsc(shopProductImage($product)) ?>" alt=""></span>
                              <span class="shop-product-price-float"><?= $firstOption ? shopPriceHtml($firstOption, $unitIndex) : '<span class="shop-badge">ยังไม่ตั้งราคา</span>' ?></span>
                            </span>
                          </span>
                          <span class="shop-product-nameplate">
                            <strong><?= shopEsc(shopShelfName($product)) ?></strong>
                          </span>
                        </button>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </section>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <section class="shop-panel" data-shop-panel="roles">
        <?php if (!$roleGroups): ?>
          <div class="shop-empty">ยังไม่มียศที่เปิดขาย</div>
        <?php else: ?>
          <?php foreach ($roleGroups as $group): ?>
            <?php $groupHasBanner = !empty($group['headerImage']); ?>
            <section class="shop-role-group"<?= shopCssBackgroundStyle((string) ($group['background'] ?? '')) ?>>
              <div class="shop-role-group-head<?= $groupHasBanner ? ' has-banner' : '' ?>">
                <?php if ($groupHasBanner): ?><?= shopRoleGroupBannerHtml((string) $group['headerImage'], (string) ($group['name'] ?: 'Role series')) ?><?php endif; ?>
                <div class="shop-role-group-head-main">
                  <div class="shop-role-group-title-row">
                    <div class="shop-role-group-title-wrap">
                      <?php if (!empty($group['icon'])): ?><span class="shop-role-group-icon"><img src="<?= shopEsc(shopAssetUrl((string) $group['icon'])) ?>" alt=""></span><?php endif; ?>
                      <div class="shop-role-group-title-text">
                        <h2><?= shopEsc($group['name'] ?: 'ยศทั่วไป') ?></h2>
                        <?php if (!empty($group['badge'])): ?><span class="shop-role-group-badge"><?= shopEsc($group['badge']) ?></span><?php endif; ?>
                      </div>
                    </div>
                    <button class="shop-role-view-toggle" type="button" data-shop-role-view-toggle aria-label="สลับมุมมองรายการยศ" title="สลับมุมมองรายการยศ">
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
                  <?php if (!empty($group['description'])): ?><span class="shop-role-group-caption"><?= shopEsc($group['description']) ?></span><?php endif; ?>
                </div>
              </div>
              <div class="shop-role-viewer" data-shop-role-viewer data-view="<?= shopEsc($shopRoleDefaultView) ?>" data-default-view="<?= shopEsc($shopRoleDefaultView) ?>">
                <div class="shop-role-slider-shell" data-shop-role-slider-shell>
                  <div class="swiper shop-role-swiper">
                    <div class="swiper-wrapper">
                <?php foreach ($group['products'] as $product): $role = $roleIndex[(string) ($product['discordRoleId'] ?? '')] ?? null; $options = shopVisibleOptions($product); $durationSummary = shopDurationSummaryText($options); $abilityBadges = shopRoleAbilityBadges($role, 3); $summary = shopRoleSummary($role, $product); if ($summary === (string) ($group['description'] ?? '')) { $summary = ''; } ?>
                  <div class="swiper-slide">
                    <button class="shop-role-card" type="button" style="--role-color:<?= shopEsc(shopRoleColor($role)) ?>" data-shop-product="<?= shopProductPayload($product, $role, $unitIndex, $shopBuyDefaults, $group) ?>">
                      <span class="shop-role-avatar"><img src="<?= shopEsc(shopProductImage($product, $role)) ?>" alt=""></span>
                      <span class="shop-role-card-copy">
                        <span class="shop-role-heading">
                          <strong class="shop-role-name"><?= shopEsc(shopDisplayName($product, $role)) ?></strong>
                          <?php if ($durationSummary !== ''): ?><span class="shop-role-days"><?= shopEsc($durationSummary) ?></span><?php endif; ?>
                        </span>
                        <?php if ($abilityBadges): ?>
                          <span class="shop-role-inline-badges">
                            <?php foreach ($abilityBadges as $abilityBadge): ?><span class="shop-role-inline-badge"><?= shopEsc($abilityBadge) ?></span><?php endforeach; ?>
                          </span>
                        <?php endif; ?>
                        <?php if ($summary !== ''): ?><span class="shop-role-summary"><?= shopEsc($summary) ?></span><?php endif; ?>
                      </span>
                    </button>
                  </div>
                <?php endforeach; ?>
                    </div>
                  </div>
                </div>
                <div class="shop-role-list" data-shop-role-list>
                  <?php foreach ($group['products'] as $product): $role = $roleIndex[(string) ($product['discordRoleId'] ?? '')] ?? null; $options = shopVisibleOptions($product); $durationSummary = shopDurationSummaryText($options); $abilityBadges = shopRoleAbilityBadges($role, 3); $summary = shopRoleSummary($role, $product); if ($summary === (string) ($group['description'] ?? '')) { $summary = ''; } ?>
                    <button class="shop-role-list-row" type="button" style="--role-color:<?= shopEsc(shopRoleColor($role)) ?>" data-shop-product="<?= shopProductPayload($product, $role, $unitIndex, $shopBuyDefaults, $group) ?>">
                      <span class="shop-role-avatar"><img src="<?= shopEsc(shopProductImage($product, $role)) ?>" alt=""></span>
                      <span class="shop-role-row-copy">
                        <span class="shop-role-heading">
                          <strong class="shop-role-name"><?= shopEsc(shopDisplayName($product, $role)) ?></strong>
                          <?php if ($durationSummary !== ''): ?><span class="shop-role-days"><?= shopEsc($durationSummary) ?></span><?php endif; ?>
                        </span>
                        <?php if ($abilityBadges): ?>
                          <span class="shop-role-inline-badges">
                            <?php foreach ($abilityBadges as $abilityBadge): ?><span class="shop-role-inline-badge"><?= shopEsc($abilityBadge) ?></span><?php endforeach; ?>
                          </span>
                        <?php endif; ?>
                        <?php if ($summary !== ''): ?><span class="shop-role-summary"><?= shopEsc($summary) ?></span><?php endif; ?>
                      </span>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            </section>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </div>
  </main>

  <div class="shop-modal" id="shopModal" aria-hidden="true">
    <article class="shop-modal-card">
      <div class="shop-modal-head">
        <h3>รายละเอียดสินค้า</h3>
        <div class="shop-modal-head-actions">
          <button class="shop-modal-gift-toggle" type="button" id="shopGiftToggle" aria-label="ส่งเป็นของขวัญ" hidden>🎁</button>
          <button class="shop-modal-close" type="button" id="shopModalClose" aria-label="Close">×</button>
        </div>
      </div>
      <div class="shop-modal-body">
        <div id="shopModalHero" class="shop-modal-hero">
          <img id="shopModalImage" src="" alt="">
          <div id="shopModalRolePreview" class="shop-modal-role-preview" hidden>
            <span id="shopModalRoleDot" class="shop-modal-role-dot"></span>
            <span id="shopModalRoleIconWrap" class="shop-modal-role-icon" hidden>
              <img id="shopModalRoleIcon" src="" alt="">
            </span>
            <strong id="shopModalRoleName" class="shop-modal-role-name"></strong>
          </div>
        </div>
        <div class="shop-modal-title-wrap">
          <h4 id="shopModalName">รายละเอียด</h4>
          <p id="shopModalSubtitle" class="shop-modal-subtitle"></p>
        </div>
        <div class="shop-badge-row" id="shopModalBadges"></div>
        <div class="shop-modal-facts" id="shopModalFacts"></div>
        <div class="shop-modal-section" id="shopModalOptionsWrap">
          <h4>ตัวเลือกการซื้อ</h4>
          <div id="shopModalPurchaseOptions" class="shop-purchase-option-list"></div>
        </div>
        <div class="shop-modal-section" id="shopModalPermissionWrap" hidden>
          <h4>ความสามารถยศ</h4>
          <div id="shopModalPermissions" class="shop-modal-ability-list"></div>
        </div>
        <div class="shop-modal-section" id="shopModalDetailWrap" hidden>
          <h4>รายละเอียด</h4>
          <div id="shopModalDetail" class="shop-modal-desc"></div>
        </div>
        <div class="shop-modal-section" id="shopModalConditionWrap" hidden>
          <h4>เงื่อนไข</h4>
          <div id="shopModalCondition" class="shop-modal-desc"></div>
        </div>
        <div id="shopModalStatus" class="shop-modal-status"></div>
        <div id="shopGiftPanel" class="shop-gift-panel" hidden>
          <div id="shopGiftTarget" class="shop-gift-target">ยังไม่ได้เลือกเพื่อน</div>
          <input id="shopGiftSearchInput" class="shop-gift-input" type="search" placeholder="ค้นหาชื่อเพื่อนในเซิร์ฟ" autocomplete="off">
          <div id="shopGiftStatus" class="shop-gift-status">พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อค้นหา</div>
          <div id="shopGiftResults" class="shop-gift-results"></div>
        </div>
        <div id="shopPaymentWrap" class="shop-payment-wrap">
          <div id="shopPaymentRow" class="shop-payment-row" aria-label="ช่องทางชำระเงิน"></div>
          <div id="shopHoldHint" class="shop-hold-hint">กดค้างเพื่อยืนยันการซื้อ</div>
        </div>
        <div id="shopSuccessActions" class="shop-success-actions" hidden>
          <button class="shop-success-button is-primary" type="button" id="shopGoBagButton">ไปกระเป๋า</button>
          <button class="shop-success-button" type="button" id="shopSuccessCloseButton">ปิด</button>
        </div>
      </div>
    </article>
  </div>

  <?php if ($shopSwiperJs !== ''): ?><script><?= $shopSwiperJs ?>;if(typeof Swiper!=="undefined"&&!window.Swiper){window.Swiper=Swiper;}</script><?php elseif ($shopSwiperJsHref !== ''): ?><script src="<?= shopEsc($shopSwiperJsHref) ?>"></script><?php endif; ?>
  <script>
    (() => {
      const shopIsAuthenticated = <?= json_encode((bool) $player) ?>;
      const shopBaseUrl = <?= json_encode($baseUrl) ?>;
      const shopIsEmbed = <?= json_encode((bool) $isEmbed) ?>;
      const tabs = document.querySelectorAll("[data-shop-tab]");
      const panels = document.querySelectorAll("[data-shop-panel]");
      tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
          const target = tab.dataset.shopTab;
          tabs.forEach((node) => node.classList.toggle("is-active", node === tab));
          panels.forEach((panel) => panel.classList.toggle("is-active", panel.dataset.shopPanel === target));
          if (target === "roles") {
            window.requestAnimationFrame(() => {
              shopRoleSwipers.forEach((entry) => {
                entry.swiper?.update?.();
                updateShopRoleSliderShell(entry);
              });
            });
          }
        });
      });

      const shopRoleViewers = Array.from(document.querySelectorAll("[data-shop-role-viewer]"));
      const shopRoleSwipers = [];
      const shopRoleListIcon = `
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M8 6h12"></path>
          <path d="M8 12h12"></path>
          <path d="M8 18h12"></path>
          <path d="M4 6h.01"></path>
          <path d="M4 12h.01"></path>
          <path d="M4 18h.01"></path>
        </svg>
      `;
      const shopRoleSliderIcon = `
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
          <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
          <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
          <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
        </svg>
      `;
      const syncShopRoleViewButtons = () => {
        shopRoleViewers.forEach((viewer) => {
          const button = viewer.closest(".shop-role-group")?.querySelector("[data-shop-role-view-toggle]");
          if (!button) return;
          const isListView = viewer.dataset.view === "list";
          button.innerHTML = isListView ? shopRoleSliderIcon : shopRoleListIcon;
          button.setAttribute("aria-label", isListView ? "สลับเป็นสไลด์ยศ" : "สลับเป็นลิสต์ยศ");
          button.title = isListView ? "สลับเป็นสไลด์ยศ" : "สลับเป็นลิสต์ยศ";
        });
      };
      const updateShopRoleSliderShell = (entry) => {
        if (!entry?.shell || !entry?.slider) return;
        const swiper = entry.swiper;
        if (swiper) {
          entry.shell.classList.toggle("is-scrollable", !swiper.isLocked);
          return;
        }
        const wrapper = entry.slider.querySelector(".swiper-wrapper");
        const sliderWidth = entry.slider.clientWidth || 0;
        const wrapperWidth = wrapper?.scrollWidth || 0;
        entry.shell.classList.toggle("is-scrollable", wrapperWidth - sliderWidth > 12);
      };
      const initializeShopRoleViewers = () => {
        shopRoleViewers.forEach((viewer) => {
          const slider = viewer.querySelector(".shop-role-swiper");
          const shell = viewer.querySelector("[data-shop-role-slider-shell]");
          const button = viewer.closest(".shop-role-group")?.querySelector("[data-shop-role-view-toggle]");

          if (slider && shell) {
            const swiper = window.Swiper ? new window.Swiper(slider, {
              slidesPerView: "auto",
              spaceBetween: 10,
              watchOverflow: true,
              grabCursor: true,
              preventClicks: false,
              preventClicksPropagation: false,
              observer: true,
              observeParents: true,
              breakpoints: {
                768: { spaceBetween: 10 },
                1200: { spaceBetween: 12 },
              },
            }) : null;
            const entry = { viewer, slider, shell, swiper };
            shopRoleSwipers.push(entry);
            window.requestAnimationFrame(() => updateShopRoleSliderShell(entry));
          }

          viewer.dataset.view = viewer.dataset.defaultView === "list" ? "list" : "slider";
          if (button) {
            button.addEventListener("click", () => {
              viewer.dataset.view = viewer.dataset.view === "slider" ? "list" : "slider";
              syncShopRoleViewButtons();
              if (viewer.dataset.view === "slider") {
                window.requestAnimationFrame(() => {
                  shopRoleSwipers
                    .filter((entry) => entry.viewer === viewer)
                    .forEach((entry) => {
                      entry.swiper?.update?.();
                      updateShopRoleSliderShell(entry);
                    });
                });
              }
            });
          }
        });
        syncShopRoleViewButtons();
        window.addEventListener("resize", () => {
          shopRoleSwipers.forEach((entry) => {
            entry.swiper?.update?.();
            updateShopRoleSliderShell(entry);
          });
        });
      };
      initializeShopRoleViewers();

      const modal = document.getElementById("shopModal");
      const closeButton = document.getElementById("shopModalClose");
      const modalHero = document.getElementById("shopModalHero");
      const modalImage = document.getElementById("shopModalImage");
      const modalName = document.getElementById("shopModalName");
      const modalSubtitle = document.getElementById("shopModalSubtitle");
      const modalBadges = document.getElementById("shopModalBadges");
      const modalFacts = document.getElementById("shopModalFacts");
      const modalPurchaseOptions = document.getElementById("shopModalPurchaseOptions");
      const modalOptionsWrap = document.getElementById("shopModalOptionsWrap");
      const modalPermissionWrap = document.getElementById("shopModalPermissionWrap");
      const modalPermissions = document.getElementById("shopModalPermissions");
      const modalDetailWrap = document.getElementById("shopModalDetailWrap");
      const modalDetail = document.getElementById("shopModalDetail");
      const modalConditionWrap = document.getElementById("shopModalConditionWrap");
      const modalCondition = document.getElementById("shopModalCondition");
      const modalStatus = document.getElementById("shopModalStatus");
      const modalRolePreview = document.getElementById("shopModalRolePreview");
      const modalRoleDot = document.getElementById("shopModalRoleDot");
      const modalRoleIconWrap = document.getElementById("shopModalRoleIconWrap");
      const modalRoleIcon = document.getElementById("shopModalRoleIcon");
      const modalRoleName = document.getElementById("shopModalRoleName");
      const giftToggle = document.getElementById("shopGiftToggle");
      const giftPanel = document.getElementById("shopGiftPanel");
      const giftTarget = document.getElementById("shopGiftTarget");
      const giftSearchInput = document.getElementById("shopGiftSearchInput");
      const giftStatus = document.getElementById("shopGiftStatus");
      const giftResults = document.getElementById("shopGiftResults");
      const paymentWrap = document.getElementById("shopPaymentWrap");
      const paymentRow = document.getElementById("shopPaymentRow");
      const holdHint = document.getElementById("shopHoldHint");
      const successActions = document.getElementById("shopSuccessActions");
      const goBagButton = document.getElementById("shopGoBagButton");
      const successCloseButton = document.getElementById("shopSuccessCloseButton");
      const walletChipMap = new Map(Array.from(document.querySelectorAll("[data-wallet-unit]")).map((node) => [node.dataset.walletUnit || "", node]));
      let activeShopPayload = null;
      let activeShopOptionId = "";
      let activeMode = "self";
      let activePaymentUnitCode = "";
      let selectedGiftTarget = null;
      let giftSearchTimer = 0;
      let giftSearchSeq = 0;
      let holdState = null;
      let paymentLocked = false;

      const escHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
      }[char]));
      const normalizeLabel = (value) => String(value ?? "").trim().replace(/\s+/g, "").toLowerCase();

      const renderRichText = (value) => {
        const lines = String(value || "").split(/\n+/).map((line) => line.trim()).filter(Boolean);
        return lines.length ? lines.map((line) => `<p>${escHtml(line)}</p>`).join("") : "";
      };
      const hasRichText = (value) => renderRichText(value) !== "";
      const renderFact = (label, value) => value ? `<div class="shop-modal-fact"><strong>${escHtml(label)}</strong><span>${escHtml(value)}</span></div>` : "";
      const renderPermission = (permission) => {
        const label = permission?.label || permission?.discordLabel || permission?.code || "สิทธิ์ยศ";
        const description = permission?.description || "ยังไม่ได้ตั้งคำอธิบายสิทธิ์นี้ไว้";
        return `
          <div class="shop-modal-ability-item">
            <strong>${escHtml(label)}</strong>
            <span>${escHtml(description)}</span>
          </div>
        `;
      };
      const selectedOption = () => (activeShopPayload?.options || []).find((option) => option.id === activeShopOptionId) || (activeShopPayload?.options || [])[0] || null;
      const hasGiftChoices = (payload = activeShopPayload) => (payload?.options || []).some((option) => (option.giftPaymentChoices || []).length > 0);
      const setStatus = (message = "", isError = false) => {
        modalStatus.textContent = message;
        modalStatus.classList.toggle("is-error", Boolean(message && isError));
      };
      const updateWalletBalances = (balances = {}) => {
        Object.entries(balances || {}).forEach(([unitCode, amount]) => {
          const chip = walletChipMap.get(unitCode);
          const label = chip?.querySelector("[data-wallet-balance]");
          if (label) {
            label.textContent = Number(amount || 0).toLocaleString();
          }
        });
      };
      const bagUrl = () => `${shopBaseUrl}/gacha/bag.php?tab=items&section=role-badge-items${shopIsEmbed ? "&embed=1" : ""}`;
      const hideHoldHint = () => holdHint.classList.remove("is-visible");
      const showHoldHint = (text) => {
        holdHint.textContent = text || (activeMode === "gift" ? "กดค้างเพื่อยืนยันการส่งของขวัญ" : "กดค้างเพื่อยืนยันการซื้อ");
        holdHint.classList.add("is-visible");
        window.clearTimeout(showHoldHint.timer);
        showHoldHint.timer = window.setTimeout(hideHoldHint, 1800);
      };
      const setPaymentLock = (locked, activeButton = null) => {
        paymentLocked = locked;
        paymentRow.querySelectorAll(".shop-buy-button").forEach((button) => {
          button.disabled = locked && button !== activeButton;
        });
      };
      const clearHold = (showHint = false) => {
        if (!holdState) return;
        window.clearTimeout(holdState.timer);
        holdState.button.classList.remove("is-holding");
        holdState.button.style.setProperty("--shop-hold-progress", "0");
        setPaymentLock(false);
        const shouldHint = showHint && !holdState.completed && !holdState.button.disabled;
        holdState = null;
        if (shouldHint) {
          showHoldHint();
        }
      };
      const choiceThemeStyle = (choice) => {
        const theme = choice?.resolvedTheme || {};
        const holdSeconds = Number(theme.holdSeconds || 1);
        return [
          `--shop-buy-bg-start:${escHtml(theme.buttonBgStart || "#4b3161")}`,
          `--shop-buy-bg-end:${escHtml(theme.buttonBgEnd || "#7657b7")}`,
          `--shop-buy-text:${escHtml(theme.buttonTextColor || "#ffffff")}`,
          `--shop-hold-start:${escHtml(theme.holdSweepStart || "#f8b86f")}`,
          `--shop-hold-end:${escHtml(theme.holdSweepEnd || "#ffe590")}`,
          `--shop-hold-seconds:${Number.isFinite(holdSeconds) ? holdSeconds : 1}s`,
        ].join(";");
      };
      const renderOptionButtons = () => {
        const options = activeShopPayload?.options || [];
        modalPurchaseOptions.innerHTML = options.map((option) => `
          <button
            type="button"
            class="shop-purchase-option${option.id === activeShopOptionId ? " is-active" : ""}"
            data-shop-option-id="${escHtml(option.id || "")}"
          >
            <strong>${escHtml(option.label || "ตัวเลือก")}</strong>
            <span>${activeMode === "gift" ? "ส่งเป็นของขวัญ" : "ซื้อเข้ากระเป๋า"}</span>
          </button>
        `).join("") || '<div class="shop-empty">ยังไม่พบตัวเลือกการซื้อ</div>';
        modalOptionsWrap.hidden = options.length === 0;
      };
      const renderFacts = () => {
        modalFacts.innerHTML = [
          renderFact("ชื่อ", activeShopPayload?.name || "รายละเอียด"),
          renderFact("ประเภท", activeShopPayload?.type === "role" ? "Role Badge" : (activeShopPayload?.type || "")),
          renderFact("ซีรีส์", activeShopPayload?.series || ""),
          renderFact("เธียร์ยศ", activeShopPayload?.roleTier || ""),
          renderFact("ระยะเวลายศ", activeShopPayload?.durationSummaryText || ""),
        ].filter(Boolean).join("");
      };
      const renderGiftTarget = () => {
        if (!selectedGiftTarget) {
          giftTarget.textContent = "ยังไม่ได้เลือกเพื่อน";
          return;
        }
        giftTarget.innerHTML = `ส่งให้ <strong>${escHtml(selectedGiftTarget.displayName || selectedGiftTarget.userName || selectedGiftTarget.userId)}</strong>`;
      };
      const renderPaymentButtons = () => {
        clearHold(false);
        hideHoldHint();
        const option = selectedOption();
        const isRole = activeShopPayload?.type === "role";
        const choices = activeMode === "gift"
          ? (option?.giftPaymentChoices || [])
          : (option?.paymentChoices || []);
        const needsGiftTarget = activeMode === "gift";

        paymentWrap.hidden = false;
        successActions.hidden = true;
        giftPanel.hidden = !(activeMode === "gift");
        giftToggle.classList.toggle("is-active", activeMode === "gift");
        giftToggle.setAttribute("aria-pressed", activeMode === "gift" ? "true" : "false");
        renderGiftTarget();

        if (!activeShopPayload) {
          paymentRow.innerHTML = '<div class="shop-payment-empty">ยังไม่เปิดซื้อสินค้านี้</div>';
          return;
        }
        if (!isRole) {
          paymentRow.innerHTML = '<div class="shop-payment-empty">สินค้านี้ยังเป็นโหมดดูรายละเอียด</div>';
          return;
        }
        if (!shopIsAuthenticated) {
          paymentRow.innerHTML = '<div class="shop-payment-empty">เข้าสู่ระบบก่อนซื้อสินค้า</div>';
          return;
        }
        if (!option) {
          paymentRow.innerHTML = '<div class="shop-payment-empty">ยังไม่พบตัวเลือกการซื้อ</div>';
          return;
        }
        if (!choices.length) {
          paymentRow.innerHTML = `<div class="shop-payment-empty">${activeMode === "gift" ? "ไม่มีรูปแบบการชำระสำหรับส่งของขวัญ" : "ไม่มีรูปแบบการชำระสำหรับตัวเลือกนี้"}</div>`;
          return;
        }

        paymentRow.innerHTML = choices.map((choice) => {
          const disabled = needsGiftTarget && !selectedGiftTarget;
          const label = activeMode === "gift" ? "ส่งของขวัญ" : "ซื้อ";
          return `
            <button
              class="shop-buy-button${choice.unitCode === activePaymentUnitCode ? " is-preselected" : ""}"
              type="button"
              data-shop-payment-unit="${escHtml(choice.unitCode || "")}"
              data-shop-hold-seconds="${escHtml(choice.resolvedTheme?.holdSeconds || 1)}"
              style="${choiceThemeStyle(choice)}"
              ${disabled ? "disabled" : ""}
            >
              <span>${label}</span>
              ${choice.moneyHtml || ""}
            </button>
          `;
        }).join("");
      };
      const renderMode = () => {
        const isGift = activeMode === "gift";
        modalSubtitle.textContent = activeShopPayload?.type === "role"
          ? (isGift ? "ส่ง role badge เข้ากระเป๋าเพื่อน แล้วเพื่อนค่อยกดใช้ได้เอง" : "ซื้อเป็น role badge เข้ากระเป๋า แล้วค่อยกดใช้กับตัวเองหรือเพื่อนในเซิร์ฟ")
          : "โหมดดูรายละเอียดสินค้า";
        renderOptionButtons();
        renderPaymentButtons();
      };
      const showSuccess = (payload, mode) => {
        paymentWrap.hidden = true;
        giftPanel.hidden = true;
        successActions.hidden = false;
        const productName = payload?.product?.name || activeShopPayload?.name || "สินค้า";
        if (mode === "gift") {
          const targetName = payload?.target?.displayName || selectedGiftTarget?.displayName || "เพื่อน";
          setStatus(`ส่ง ${productName} ให้ ${targetName} สำเร็จแล้ว`);
          goBagButton.textContent = "ส่งอีกครั้ง";
          goBagButton.onclick = () => {
            setStatus("");
            successActions.hidden = true;
            paymentWrap.hidden = false;
            giftPanel.hidden = false;
            renderPaymentButtons();
          };
        } else {
          setStatus(`ซื้อสำเร็จแล้ว เพิ่ม ${productName} เข้ากระเป๋าเรียบร้อย`);
          goBagButton.textContent = "ไปกระเป๋า";
          goBagButton.onclick = () => { window.location.href = bagUrl(); };
        }
      };
      const openModal = (payload, preset = {}) => {
        activeShopPayload = payload || {};
        activeShopOptionId = String(preset.optionId || (payload?.options || [])[0]?.id || "");
        activeMode = "self";
        activePaymentUnitCode = String(preset.paymentUnitCode || "");
        selectedGiftTarget = null;
        giftSearchInput.value = "";
        giftResults.innerHTML = "";
        giftStatus.textContent = "พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อค้นหา";
        successActions.hidden = true;
        paymentWrap.hidden = false;
        modalImage.src = payload.image || "";
        modalImage.alt = payload.name || "Product image";
        modalName.textContent = payload.name || "รายละเอียด";
        modalSubtitle.textContent = payload.type === "role"
          ? "ซื้อเป็น role badge เข้ากระเป๋า แล้วค่อยกดใช้กับตัวเองหรือเพื่อนในเซิร์ฟ"
          : "โหมดดูรายละเอียดสินค้า";
        const badges = [];
        const seenBadges = new Set([normalizeLabel(payload?.name)]);
        const pushBadge = (label, extraClass = "") => {
          const normalized = normalizeLabel(label);
          if (!normalized || seenBadges.has(normalized)) return;
          seenBadges.add(normalized);
          badges.push(`<span class="shop-badge${extraClass ? ` ${extraClass}` : ""}">${escHtml(label)}</span>`);
        };
        (payload.badges || []).forEach((label) => pushBadge(label));
        if (payload.seriesBadge) pushBadge(payload.seriesBadge, "is-series");
        if (payload.roleTier) pushBadge(`Tier ${payload.roleTier}`);
        modalBadges.innerHTML = badges.join("");

        const isRole = payload.type === "role";
        if (isRole) {
          modalRolePreview.hidden = false;
          modalHero.classList.add("is-role-preview");
          modalRoleName.textContent = payload.name || "Role";
          modalRoleName.style.color = String(payload.roleColor || "").trim() || "#111111";
          modalRoleDot.style.background = String(payload.roleColor || "").trim() || "#8b91a1";
          if (String(payload.roleIconUrl || "").trim()) {
            modalRoleIcon.src = payload.roleIconUrl;
            modalRoleIcon.alt = payload.name || "Role icon";
            modalRoleIconWrap.hidden = false;
          } else {
            modalRoleIconWrap.hidden = true;
            modalRoleIcon.removeAttribute("src");
            modalRoleIcon.alt = "";
          }
        } else {
          modalRolePreview.hidden = true;
          modalHero.classList.remove("is-role-preview");
          modalRoleIconWrap.hidden = true;
        }

        renderOptionButtons();
        renderFacts();

        const permissionDetails = Array.isArray(payload.permissionDetails) ? payload.permissionDetails : [];
        modalPermissions.innerHTML = permissionDetails.map(renderPermission).join("");
        modalPermissionWrap.hidden = !(isRole && permissionDetails.length);

        modalDetail.innerHTML = renderRichText(payload.detailText || "");
        modalCondition.innerHTML = renderRichText(payload.conditionText || "");
        modalDetailWrap.hidden = !hasRichText(payload.detailText || "");
        modalConditionWrap.hidden = !hasRichText(payload.conditionText || "");
        setStatus("");
        giftToggle.hidden = !(payload.type === "role" && hasGiftChoices(payload));
        renderMode();
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        const body = modal.querySelector(".shop-modal-body");
        body?.scrollTo({ top: 0 });
        if (preset.scrollToPayments) {
          window.setTimeout(() => paymentWrap.scrollIntoView({ behavior: "smooth", block: "nearest" }), 40);
        }
      };
      document.querySelectorAll("[data-shop-product]").forEach((button) => {
        button.addEventListener("click", (event) => {
          const cta = event.target.closest("[data-shop-open-option]");
          const preset = cta ? {
            optionId: cta.dataset.shopOpenOption || "",
            paymentUnitCode: cta.dataset.shopOpenPayment || "",
            scrollToPayments: true,
          } : {};
          try { openModal(JSON.parse(button.dataset.shopProduct || "{}"), preset); } catch (_) { openModal({}, preset); }
        });
      });
      const closeModal = () => {
        clearHold(false);
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        activeShopPayload = null;
        activeShopOptionId = "";
        activePaymentUnitCode = "";
        activeMode = "self";
        selectedGiftTarget = null;
      };
      closeButton.addEventListener("click", closeModal);
      successCloseButton.addEventListener("click", closeModal);
      modal.addEventListener("click", (event) => {
        if (event.target === modal) closeModal();
      });
      giftToggle.addEventListener("click", () => {
        if (!activeShopPayload || giftToggle.hidden) return;
        activeMode = activeMode === "gift" ? "self" : "gift";
        activePaymentUnitCode = "";
        setStatus("");
        renderMode();
        if (activeMode === "gift") {
          window.setTimeout(() => giftSearchInput.focus(), 40);
        }
      });
      modalPurchaseOptions.addEventListener("click", (event) => {
        const optionButton = event.target.closest("[data-shop-option-id]");
        if (!optionButton) return;
        activeShopOptionId = optionButton.dataset.shopOptionId || "";
        activePaymentUnitCode = "";
        renderOptionButtons();
        renderFacts();
        renderPaymentButtons();
      });
      const submitPayment = async (button) => {
        const option = selectedOption();
        const paymentUnitCode = button?.dataset?.shopPaymentUnit || "";
        if (!activeShopPayload || activeShopPayload.type !== "role" || !option || !paymentUnitCode) return;
        if (activeMode === "gift" && !selectedGiftTarget) {
          setStatus("เลือกเพื่อนก่อนส่งของขวัญ", true);
          return;
        }
        button.disabled = true;
        paymentLocked = true;
        setStatus(activeMode === "gift" ? "กำลังส่งของขวัญ..." : "กำลังซื้อเข้ากระเป๋า...");
        let succeeded = false;
        try {
          const response = await fetch(window.location.href, {
            method: "POST",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify({
              action: activeMode === "gift" ? "gift_role_badge" : "purchase_role_badge",
              productId: activeShopPayload.id || "",
              optionId: option.id || "",
              paymentUnitCode,
              targetUserId: activeMode === "gift" ? (selectedGiftTarget?.userId || "") : "",
            }),
          });
          const payload = await response.json().catch(() => ({}));
          if (!response.ok || !payload?.ok) {
            throw new Error(payload?.message || "ซื้อไม่สำเร็จ");
          }
          updateWalletBalances(payload.balances || {});
          succeeded = true;
          showSuccess(payload, activeMode);
        } catch (error) {
          setStatus(error?.message || "ซื้อไม่สำเร็จ", true);
        } finally {
          paymentLocked = false;
          if (!succeeded) {
            renderPaymentButtons();
          }
        }
      };
      paymentRow.addEventListener("pointerdown", (event) => {
        const button = event.target.closest(".shop-buy-button");
        if (!button || button.disabled || paymentLocked) return;
        event.preventDefault();
        hideHoldHint();
        activePaymentUnitCode = button.dataset.shopPaymentUnit || "";
        const holdSeconds = Math.max(.2, Number(button.dataset.shopHoldSeconds || 1));
        setPaymentLock(true, button);
        button.classList.add("is-holding");
        button.style.setProperty("--shop-hold-progress", "1");
        holdState = {
          button,
          completed: false,
          timer: window.setTimeout(() => {
            if (!holdState || holdState.button !== button) return;
            holdState.completed = true;
            button.classList.remove("is-holding");
            submitPayment(button);
            holdState = null;
          }, holdSeconds * 1000),
        };
        try { button.setPointerCapture(event.pointerId); } catch (_) {}
      });
      ["pointerup", "pointercancel", "pointerleave"].forEach((eventName) => {
        paymentRow.addEventListener(eventName, () => clearHold(eventName === "pointerup"));
      });
      const renderGiftResults = (members) => {
        giftResults.innerHTML = (members || []).map((member) => `
          <button class="shop-gift-result" type="button" data-gift-user-id="${escHtml(member.userId || "")}" data-gift-user-name="${escHtml(member.displayName || member.userName || member.userId || "")}">
            <img src="${escHtml(member.avatarUrl || "")}" alt="">
            <span>
              <strong>${escHtml(member.displayName || member.userName || member.userId)}</strong>
              <span>${escHtml(member.userName ? `@${member.userName}` : member.userId || "")}</span>
            </span>
          </button>
        `).join("");
      };
      const searchGiftMembers = () => {
        const query = giftSearchInput.value.trim();
        selectedGiftTarget = null;
        renderGiftTarget();
        renderPaymentButtons();
        if (query.length < 2) {
          giftStatus.textContent = "พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อค้นหา";
          giftResults.innerHTML = "";
          return;
        }
        const seq = ++giftSearchSeq;
        giftStatus.textContent = "กำลังค้นหา...";
        fetch(window.location.href, {
          method: "POST",
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify({ action: "search_gift_members", query }),
        })
          .then((response) => response.json())
          .then((payload) => {
            if (seq !== giftSearchSeq) return;
            const members = payload?.members || [];
            giftStatus.textContent = members.length ? "เลือกเพื่อนที่ต้องการส่งให้" : "ไม่พบสมาชิกที่ค้นหา";
            renderGiftResults(members);
          })
          .catch(() => {
            if (seq !== giftSearchSeq) return;
            giftStatus.textContent = "ค้นหาไม่สำเร็จ ลองใหม่อีกครั้ง";
            giftResults.innerHTML = "";
          });
      };
      giftSearchInput.addEventListener("input", () => {
        window.clearTimeout(giftSearchTimer);
        giftSearchTimer = window.setTimeout(searchGiftMembers, 260);
      });
      giftResults.addEventListener("click", (event) => {
        const button = event.target.closest("[data-gift-user-id]");
        if (!button) return;
        selectedGiftTarget = {
          userId: button.dataset.giftUserId || "",
          displayName: button.dataset.giftUserName || button.dataset.giftUserId || "",
        };
        giftSearchInput.value = selectedGiftTarget.displayName;
        giftResults.innerHTML = "";
        giftStatus.textContent = "เลือกเพื่อนแล้ว";
        renderGiftTarget();
        renderPaymentButtons();
      });
      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") closeModal();
      });
    })();
  </script>
</body>
</html>
<?php
if ($shopPageCacheKey !== '' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $shopPageHtml = (string) ob_get_clean();
    if (http_response_code() < 400) {
        PublicPageCacheService::put($shopPageCacheKey, $shopPageHtml);
    }
    echo $shopPageHtml;
}
?>
