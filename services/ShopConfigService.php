<?php

declare(strict_types=1);

final class ShopConfigService
{
    public const SETTING_KEY = 'shop.sale_config';

    public static function ensureSchema(): void
    {
        ShopUnitService::ensureSchema();
    }

    public static function defaults(): array
    {
        return [
            'version' => 1,
            'settings' => [
                'enabled' => true,
                'purchaseMode' => 'preview',
                'defaultRoleView' => 'slider',
                'shopBuyDefaults' => ShopUnitService::defaultBuyTheme(),
                'roleSeriesSettings' => [],
            ],
            'products' => [
                [
                    'id' => 'private-room-ticket',
                    'type' => 'system_ticket',
                    'name' => 'บัตรเปิดห้องส่วนตัว',
                    'shortName' => 'บัตรเปิดห้อง',
                    'itemCode' => 'private_room_ticket',
                    'image' => 'images/icon_3.png',
                    'badge' => 'ห้องส่วนตัว',
                    'effectType' => 'private_room_days',
                    'effectPayload' => ['scope' => 'private_room'],
                    'detailText' => 'บัตรสำหรับใช้เปิดห้องส่วนตัวตามจำนวนวันที่เลือก ระบบกระเป๋าจะเป็นผู้ถือสิทธิ์ไว้ใช้ภายหลัง',
                    'conditionText' => 'ซื้อแล้วจะเข้ากระเป๋าเมื่อเปิดระบบซื้อจริง ยังไม่ทำ action ทันทีในรอบนี้',
                    'purchaseOptions' => [
                        ['id' => 'room-7', 'label' => '7 วัน', 'days' => 7, 'prices' => ['coin' => 350, 'gem' => 0], 'active' => true, 'visible' => true],
                        ['id' => 'room-30', 'label' => '30 วัน', 'days' => 30, 'prices' => ['coin' => 1200, 'gem' => 12], 'active' => true, 'visible' => true],
                    ],
                    'active' => true,
                    'visible' => true,
                    'quotaMode' => 'unlimited',
                    'quotaTotal' => 0,
                    'soldOutMode' => 'show',
                    'sortOrder' => 10,
                ],
                [
                    'id' => 'unban-ticket',
                    'type' => 'system_ticket',
                    'name' => 'บัตรปลดแบน',
                    'shortName' => 'ปลดแบน',
                    'itemCode' => 'unban_ticket',
                    'image' => 'images/icon_4.png',
                    'badge' => 'ปลดแบน',
                    'effectType' => 'moderation_clearance',
                    'effectPayload' => ['clearance' => 'unban'],
                    'detailText' => 'บัตรคำขอปลดแบนสำหรับนำไปใช้ผ่านระบบกระเป๋าหรือ flow ตรวจสอบภายหลัง',
                    'conditionText' => 'สินค้าเป็นสิทธิ์ในกระเป๋า ยังไม่ปลดแบนอัตโนมัติในรอบนี้',
                    'purchaseOptions' => [
                        ['id' => 'unban-1', 'label' => '1 ใบ', 'days' => 0, 'prices' => ['coin' => 0, 'gem' => 30], 'active' => true, 'visible' => true],
                    ],
                    'active' => true,
                    'visible' => true,
                    'quotaMode' => 'manual',
                    'quotaTotal' => 10,
                    'soldOutMode' => 'show',
                    'sortOrder' => 20,
                ],
                [
                    'id' => 'yellow-card-clear-ticket',
                    'type' => 'system_ticket',
                    'name' => 'บัตรล้างใบเหลือง',
                    'shortName' => 'ล้างใบเหลือง',
                    'itemCode' => 'yellow_card_clear_ticket',
                    'image' => 'images/icon_5.png',
                    'badge' => 'ใบเหลือง',
                    'effectType' => 'moderation_clearance',
                    'effectPayload' => ['clearance' => 'yellow_card'],
                    'detailText' => 'บัตรสำหรับใช้ล้างสถานะใบเหลืองในระบบ moderation ภายหลัง',
                    'conditionText' => '',
                    'purchaseOptions' => [
                        ['id' => 'yellow-1', 'label' => '1 ใบ', 'days' => 0, 'prices' => ['coin' => 600, 'gem' => 0], 'active' => true, 'visible' => true],
                    ],
                    'active' => true,
                    'visible' => true,
                    'quotaMode' => 'unlimited',
                    'quotaTotal' => 0,
                    'soldOutMode' => 'show',
                    'sortOrder' => 30,
                ],
                [
                    'id' => 'red-card-clear-ticket',
                    'type' => 'system_ticket',
                    'name' => 'บัตรล้างใบแดง',
                    'shortName' => 'ล้างใบแดง',
                    'itemCode' => 'red_card_clear_ticket',
                    'image' => 'images/icon_2.png',
                    'badge' => 'ใบแดง',
                    'effectType' => 'moderation_clearance',
                    'effectPayload' => ['clearance' => 'red_card'],
                    'detailText' => 'บัตรสำหรับใช้ล้างสถานะใบแดงในระบบ moderation ภายหลัง',
                    'conditionText' => '',
                    'purchaseOptions' => [
                        ['id' => 'red-1', 'label' => '1 ใบ', 'days' => 0, 'prices' => ['coin' => 0, 'gem' => 18], 'active' => true, 'visible' => true],
                    ],
                    'active' => true,
                    'visible' => true,
                    'quotaMode' => 'manual',
                    'quotaTotal' => 20,
                    'soldOutMode' => 'show',
                    'sortOrder' => 40,
                ],
            ],
            'templates' => [
                ['id' => 'shop-role-detail', 'name' => 'รายละเอียดยศ', 'type' => 'detail', 'body' => 'เลือกอายุการใช้งานที่ต้องการ แล้วซื้อ/เช่ายศนี้ได้จากหน้าร้านค้า'],
                ['id' => 'shop-general-condition', 'name' => 'เงื่อนไขร้านค้า', 'type' => 'condition', 'body' => 'สินค้าอยู่ภายใต้เงื่อนไขของเซิร์ฟเวอร์และอาจถูกจำกัดจำนวนสิทธิ์'],
            ],
        ];
    }

    public static function load(bool $seed = true): array
    {
        self::ensureSchema();
        $defaults = self::defaults();
        $row = Database::fetch(
            'SELECT settingValueJson FROM tbl_setting WHERE settingKey = :settingKey',
            ['settingKey' => self::SETTING_KEY]
        );
        if (!$row || trim((string) ($row['settingValueJson'] ?? '')) === '') {
            if ($seed) {
                self::save($defaults);
            }
            return self::normalize($defaults);
        }

        $decoded = json_decode((string) $row['settingValueJson'], true);
        if (!is_array($decoded)) {
            return self::normalize($defaults);
        }

        $normalized = self::normalize($decoded);
        if ($normalized !== $decoded) {
            try {
                self::save($normalized);
            } catch (Throwable) {
                // Keep using normalized data in memory.
            }
        }
        return $normalized;
    }

    public static function save(array $config): array
    {
        self::ensureSchema();
        $config = self::stabilizeItemVisibilityFlags($config);
        $normalized = self::normalize($config);
        Database::execute(
            'INSERT INTO tbl_setting (settingKey, settingValueJson, isSecret, updateDate)
             VALUES (:settingKey, :settingValueJson, 0, :updateDate)
             ON DUPLICATE KEY UPDATE settingValueJson = VALUES(settingValueJson), updateDate = VALUES(updateDate)',
            [
                'settingKey' => self::SETTING_KEY,
                'settingValueJson' => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updateDate' => date('Y-m-d H:i:s'),
            ]
        );

        self::syncConfiguredItems($normalized);
        return $normalized;
    }

    /** @return array<int, array<string, mixed>> */
    public static function assetLibrary(): array
    {
        $videoExtensions = ['mp4', 'webm', 'mov', 'm4v'];
        $imageExtensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
        $roots = [
            ['dir' => Bootstrap::rootPath('gacha/images'), 'prefix' => 'images'],
            ['dir' => Bootstrap::rootPath('gacha/uploads/prizes'), 'prefix' => 'uploads/prizes'],
        ];
        $assets = [];
        foreach ($roots as $root) {
            if (!is_dir($root['dir'])) {
                continue;
            }
            foreach (scandir($root['dir']) ?: [] as $fileName) {
                if ($fileName === '.' || $fileName === '..') {
                    continue;
                }
                $path = $root['dir'] . DIRECTORY_SEPARATOR . $fileName;
                $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
                if (!is_file($path) || (!in_array($extension, $imageExtensions, true) && !in_array($extension, $videoExtensions, true))) {
                    continue;
                }
                $assetPath = $root['prefix'] . '/' . $fileName;
                $kind = in_array($extension, $videoExtensions, true)
                    ? 'video'
                    : (str_starts_with(strtolower($fileName), 'icon') || str_contains(strtolower($fileName), 'ico') ? 'icon' : 'image');
                $assets[] = [
                    'path' => $assetPath,
                    'name' => $fileName,
                    'url' => rtrim((string) Bootstrap::config('app.baseUrl', '/discord'), '/') . '/gacha/' . $assetPath,
                    'kind' => $kind,
                    'modifiedAt' => filemtime($path) ?: 0,
                    'isUploaded' => str_starts_with($assetPath, 'uploads/'),
                ];
            }
        }
        usort($assets, static function (array $left, array $right): int {
            $uploaded = ((int) !empty($right['isUploaded'])) <=> ((int) !empty($left['isUploaded']));
            if ($uploaded !== 0) {
                return $uploaded;
            }
            $modified = ((int) ($right['modifiedAt'] ?? 0)) <=> ((int) ($left['modifiedAt'] ?? 0));
            return $modified !== 0 ? $modified : strcmp((string) $left['name'], (string) $right['name']);
        });
        return $assets;
    }

    /** @return array<int, array<string, mixed>> */
    public static function publicProducts(?array $config = null): array
    {
        $config ??= self::load();
        return array_values(array_filter($config['products'] ?? [], static function (array $product): bool {
            if (empty($product['active']) || empty($product['visible'])) {
                return false;
            }

            $type = (string) ($product['type'] ?? 'item');
            if ($type === 'item' && !self::hasVisiblePurchaseOptions($product['purchaseOptions'] ?? $product['durationOptions'] ?? [])) {
                return false;
            }

            return true;
        }));
    }

    private static function normalize(array $config): array
    {
        $defaults = self::defaults();
        $settings = is_array($config['settings'] ?? null) ? $config['settings'] : [];
        $normalized = [
            'version' => 1,
            'settings' => [
                'enabled' => array_key_exists('enabled', $settings) ? (bool) $settings['enabled'] : true,
                'purchaseMode' => 'preview',
                'defaultRoleView' => in_array(($settings['defaultRoleView'] ?? 'slider'), ['slider', 'list'], true) ? (string) $settings['defaultRoleView'] : 'slider',
                'shopBuyDefaults' => ShopUnitService::normalizeBuyThemeDefaults(
                    $settings['shopBuyDefaults'] ?? [],
                    $defaults['settings']['shopBuyDefaults'] ?? ShopUnitService::defaultBuyTheme()
                ),
                'roleSeriesSettings' => self::normalizeRoleSeriesSettings($settings['roleSeriesSettings'] ?? []),
            ],
            'products' => [],
            'templates' => [],
        ];

        $products = is_array($config['products'] ?? null) ? $config['products'] : ($defaults['products'] ?? []);
        $usedIds = [];
        foreach (array_values(array_filter($products, 'is_array')) as $index => $product) {
            $type = (string) ($product['type'] ?? 'item');
            if (!in_array($type, ['item', 'role', 'system_ticket'], true)) {
                $type = 'item';
            }
            $id = self::slug((string) ($product['id'] ?? ''), $type . '-' . ($index + 1));
            if (isset($usedIds[$id])) {
                $id .= '-' . ($index + 1);
            }
            $usedIds[$id] = true;

            $purchaseOptions = self::normalizePurchaseOptions($product['purchaseOptions'] ?? $product['durationOptions'] ?? [], $type);
            $effectPayload = $product['effectPayload'] ?? [];
            if (is_string($effectPayload)) {
                $decoded = json_decode($effectPayload, true);
                $effectPayload = is_array($decoded) ? $decoded : [];
            }
            $quotaMode = (string) ($product['quotaMode'] ?? 'unlimited');
            if (!in_array($quotaMode, ['unlimited', 'manual'], true)) {
                $quotaMode = 'unlimited';
            }
            $soldOutMode = (string) ($product['soldOutMode'] ?? 'show');
            if (!in_array($soldOutMode, ['show', 'hide'], true)) {
                $soldOutMode = 'show';
            }

            $normalized['products'][] = [
                'id' => $id,
                'type' => $type,
                'name' => trim((string) ($product['name'] ?? ($type === 'role' ? '<roleName>' : 'สินค้าใหม่'))) ?: ($type === 'role' ? '<roleName>' : 'สินค้าใหม่'),
                'shortName' => trim((string) ($product['shortName'] ?? '')),
                'itemCode' => self::slug((string) ($product['itemCode'] ?? $id), $id, '_'),
                'discordRoleId' => preg_replace('/[^0-9]/', '', (string) ($product['discordRoleId'] ?? '')) ?: '',
                'seriesSourceId' => self::slug((string) ($product['seriesSourceId'] ?? ''), ''),
                'groupId' => self::slug((string) ($product['groupId'] ?? ''), ''),
                'groupName' => trim((string) ($product['groupName'] ?? '')),
                'groupBadge' => trim((string) ($product['groupBadge'] ?? '')),
                'groupDescription' => trim((string) ($product['groupDescription'] ?? '')),
                'groupSortOrder' => (int) ($product['groupSortOrder'] ?? 0),
                'image' => trim((string) ($product['image'] ?? '')),
                'badge' => trim((string) ($product['badge'] ?? '')),
                'effectType' => trim((string) ($product['effectType'] ?? '')),
                'effectPayload' => is_array($effectPayload) ? $effectPayload : [],
                'detailTemplateId' => trim((string) ($product['detailTemplateId'] ?? '')),
                'detailText' => trim((string) ($product['detailText'] ?? $product['description'] ?? '')),
                'conditionTemplateId' => trim((string) ($product['conditionTemplateId'] ?? '')),
                'conditionText' => trim((string) ($product['conditionText'] ?? '')),
                'purchaseOptions' => $purchaseOptions,
                'durationOptions' => $type === 'role' ? $purchaseOptions : [],
                'active' => array_key_exists('active', $product) ? (bool) $product['active'] : true,
                'visible' => array_key_exists('visible', $product) ? (bool) $product['visible'] : true,
                'quotaMode' => $quotaMode,
                'quotaTotal' => max(0, (int) ($product['quotaTotal'] ?? 0)),
                'soldOutMode' => $soldOutMode,
                'sortOrder' => (int) ($product['sortOrder'] ?? (($index + 1) * 10)),
            ];
        }

        usort($normalized['products'], static fn (array $left, array $right): int => ((int) $left['sortOrder'] <=> (int) $right['sortOrder']) ?: strcmp((string) $left['name'], (string) $right['name']));

        $templates = is_array($config['templates'] ?? null) ? $config['templates'] : ($defaults['templates'] ?? []);
        foreach (array_values(array_filter($templates, 'is_array')) as $index => $template) {
            $type = (string) ($template['type'] ?? 'detail');
            $normalized['templates'][] = [
                'id' => self::slug((string) ($template['id'] ?? ''), 'template-' . ($index + 1)),
                'name' => trim((string) ($template['name'] ?? 'Template ' . ($index + 1))) ?: 'Template ' . ($index + 1),
                'type' => in_array($type, ['detail', 'condition'], true) ? $type : 'detail',
                'body' => trim((string) ($template['body'] ?? '')),
            ];
        }

        return $normalized;
    }

    private static function stabilizeItemVisibilityFlags(array $config): array
    {
        if (!isset($config['products']) || !is_array($config['products'])) {
            return $config;
        }

        foreach ($config['products'] as $index => $product) {
            if (!is_array($product)) {
                continue;
            }

            $type = (string) ($product['type'] ?? 'item');
            if ($type !== 'item') {
                continue;
            }

            if (!array_key_exists('active', $product) || !array_key_exists('visible', $product)) {
                continue;
            }

            if (!empty($product['active']) || !empty($product['visible'])) {
                continue;
            }

            if (!self::hasVisiblePurchaseOptions($product['purchaseOptions'] ?? $product['durationOptions'] ?? [])) {
                continue;
            }

            $config['products'][$index]['active'] = true;
            $config['products'][$index]['visible'] = true;
        }

        return $config;
    }

    /** @return array<int, array<string, mixed>> */
    private static function normalizePurchaseOptions(mixed $options, string $productType, bool $allowEmpty = false): array
    {
        if (!is_array($options) || !$options) {
            if ($allowEmpty) {
                return [];
            }
            $options = $productType === 'role'
                ? [['id' => 'role-7', 'label' => '7 วัน', 'days' => 7, 'prices' => ['coin' => 100, 'gem' => 0], 'active' => true, 'visible' => true]]
                : [['id' => 'item-1', 'label' => '1 ชิ้น', 'days' => 0, 'prices' => ['coin' => 100, 'gem' => 0], 'active' => true, 'visible' => true]];
        }

        $normalized = [];
        $usedIds = [];
        foreach (array_values(array_filter($options, 'is_array')) as $index => $option) {
            $id = self::slug((string) ($option['id'] ?? ''), 'option-' . ($index + 1));
            if (isset($usedIds[$id])) {
                $id .= '-' . ($index + 1);
            }
            $usedIds[$id] = true;
            $days = max(0, (int) ($option['days'] ?? $option['durationDays'] ?? 0));
            $prices = is_array($option['prices'] ?? null) ? $option['prices'] : [];
            if (array_key_exists('coinPrice', $option)) {
                $prices['coin'] = $option['coinPrice'];
            }
            if (array_key_exists('gemPrice', $option)) {
                $prices['gem'] = $option['gemPrice'];
            }
            $normalizedPrices = [];
            foreach ($prices as $unitCode => $amount) {
                $unitCode = self::slug((string) $unitCode, '', '_');
                if ($unitCode === '') {
                    continue;
                }
                $normalizedPrices[$unitCode] = max(0, (int) $amount);
            }
            if (!array_key_exists('coin', $normalizedPrices)) {
                $normalizedPrices['coin'] = 0;
            }
            if (!array_key_exists('gem', $normalizedPrices)) {
                $normalizedPrices['gem'] = 0;
            }

            $quotaMode = (string) ($option['quotaMode'] ?? 'inherit');
            if (!in_array($quotaMode, ['inherit', 'unlimited', 'manual'], true)) {
                $quotaMode = 'inherit';
            }

            $normalized[] = [
                'id' => $id,
                'label' => trim((string) ($option['label'] ?? ($days > 0 ? $days . ' วัน' : ($productType === 'role' ? 'ถาวร' : '1 ชิ้น')))),
                'days' => $days,
                'prices' => $normalizedPrices,
                'gift' => self::normalizeGiftSettings($option['gift'] ?? [], $normalizedPrices),
                'active' => array_key_exists('active', $option) ? (bool) $option['active'] : true,
                'visible' => array_key_exists('visible', $option) ? (bool) $option['visible'] : true,
                'quotaMode' => $quotaMode,
                'quotaTotal' => max(0, (int) ($option['quotaTotal'] ?? 0)),
                'sortOrder' => (int) ($option['sortOrder'] ?? (($index + 1) * 10)),
            ];
        }

        usort($normalized, static fn (array $left, array $right): int => ((int) $left['sortOrder'] <=> (int) $right['sortOrder']) ?: ((int) $left['days'] <=> (int) $right['days']));
        return $normalized;
    }

    /** @return array<int, array{seriesId: string, headerImage: string, purchaseOptions: array<int, array<string, mixed>>}> */
    private static function normalizeRoleSeriesSettings(mixed $settings): array
    {
        if (!is_array($settings)) {
            return [];
        }

        $normalized = [];
        $usedSeriesIds = [];
        foreach ($settings as $index => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $seriesId = self::slug(
                (string) ($entry['seriesId'] ?? (is_string($index) ? $index : '')),
                ''
            );
            if ($seriesId === '' || isset($usedSeriesIds[$seriesId])) {
                continue;
            }

            $headerImage = trim((string) ($entry['headerImage'] ?? $entry['bannerImage'] ?? ''));
            $purchaseOptions = self::normalizePurchaseOptions($entry['purchaseOptions'] ?? [], 'role', true);
            if ($headerImage === '' && !$purchaseOptions) {
                continue;
            }

            $usedSeriesIds[$seriesId] = true;
            $normalized[] = [
                'seriesId' => $seriesId,
                'headerImage' => $headerImage,
                'purchaseOptions' => $purchaseOptions,
            ];
        }

        usort($normalized, static fn (array $left, array $right): int => strcmp((string) $left['seriesId'], (string) $right['seriesId']));
        return $normalized;
    }

    private static function hasVisiblePurchaseOptions(mixed $options): bool
    {
        if (!is_array($options)) {
            return false;
        }

        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }
            if (!empty($option['active']) && !empty($option['visible'])) {
                return true;
            }
        }

        return false;
    }

    /** @return array{enabled: bool, useCustomPrices: bool, enabledUnits: array<string, bool>, prices: array<string, int>} */
    private static function normalizeGiftSettings(mixed $gift, array $basePrices): array
    {
        $gift = is_array($gift) ? $gift : [];
        $enabledUnitsRaw = is_array($gift['enabledUnits'] ?? null) ? $gift['enabledUnits'] : [];
        if (!$enabledUnitsRaw && is_array($gift['allowedUnits'] ?? null)) {
            $enabledUnitsRaw = array_fill_keys(array_values($gift['allowedUnits']), true);
        }

        $giftPricesRaw = is_array($gift['prices'] ?? null) ? $gift['prices'] : [];
        $unitCodes = array_unique(array_merge(
            array_keys($basePrices),
            array_keys($enabledUnitsRaw),
            array_keys($giftPricesRaw)
        ));

        $enabledUnits = [];
        $giftPrices = [];
        foreach ($unitCodes as $unitCode) {
            $unitCode = self::slug((string) $unitCode, '', '_');
            if ($unitCode === '') {
                continue;
            }
            $enabledUnits[$unitCode] = !empty($enabledUnitsRaw[$unitCode]);
            $giftPrices[$unitCode] = max(0, (int) ($giftPricesRaw[$unitCode] ?? ($basePrices[$unitCode] ?? 0)));
        }

        if (!array_key_exists('coin', $enabledUnits)) {
            $enabledUnits['coin'] = false;
            $giftPrices['coin'] = max(0, (int) ($giftPrices['coin'] ?? ($basePrices['coin'] ?? 0)));
        }
        if (!array_key_exists('gem', $enabledUnits)) {
            $enabledUnits['gem'] = false;
            $giftPrices['gem'] = max(0, (int) ($giftPrices['gem'] ?? ($basePrices['gem'] ?? 0)));
        }

        return [
            'enabled' => !empty($gift['enabled']),
            'useCustomPrices' => !empty($gift['useCustomPrices']),
            'enabledUnits' => $enabledUnits,
            'prices' => $giftPrices,
        ];
    }

    private static function syncConfiguredItems(array $config): void
    {
        foreach ($config['products'] ?? [] as $product) {
            if (!in_array(($product['type'] ?? ''), ['item', 'system_ticket'], true)) {
                continue;
            }
            Database::execute(
                'INSERT INTO tbl_shop_item (itemCode, itemName, itemType, image, effectType, effectPayloadJson, metadataJson, isActive, updateDate)
                 VALUES (:itemCode, :itemName, :itemType, :image, :effectType, :effectPayloadJson, :metadataJson, :isActive, :updateDate)
                 ON DUPLICATE KEY UPDATE
                    itemName = VALUES(itemName),
                    itemType = VALUES(itemType),
                    image = VALUES(image),
                    effectType = VALUES(effectType),
                    effectPayloadJson = VALUES(effectPayloadJson),
                    metadataJson = VALUES(metadataJson),
                    isActive = VALUES(isActive),
                    updateDate = VALUES(updateDate)',
                [
                    'itemCode' => (string) $product['itemCode'],
                    'itemName' => (string) $product['name'],
                    'itemType' => (string) $product['type'],
                    'image' => (string) ($product['image'] ?? ''),
                    'effectType' => (string) ($product['effectType'] ?? ''),
                    'effectPayloadJson' => json_encode($product['effectPayload'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'metadataJson' => json_encode(['shopProductId' => $product['id'], 'purchaseOptions' => $product['purchaseOptions'] ?? []], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'isActive' => !empty($product['active']) ? 1 : 0,
                    'updateDate' => date('Y-m-d H:i:s'),
                ]
            );
        }
    }

    private static function slug(string $value, string $fallback, string $separator = '-'): string
    {
        $value = trim($value);
        if ($value === '') {
            $value = $fallback;
        }
        $pattern = $separator === '_' ? '/[^a-z0-9_]+/i' : '/[^a-z0-9]+/i';
        $value = strtolower(preg_replace($pattern, $separator, $value) ?? '');
        $value = trim($value, $separator);
        return $value !== '' ? $value : trim($fallback, $separator);
    }
}
