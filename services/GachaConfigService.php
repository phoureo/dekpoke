<?php

declare(strict_types=1);

final class GachaConfigService
{
    public const SETTING_KEY = 'gacha.prize_config';

    public static function defaults(): array
    {
        return [
            'version' => 9,
            'settings' => [
                'enabled' => true,
                'tokenSecret' => 'local-gacha-token-secret-change-me',
                'defaultButtonId' => 1,
                'campaignCounterVisible' => true,
                'campaignBanners' => GachaCampaignBannerService::defaultConfig(),
                'defaultRoleIcon' => '',
                'defaultItemIcon' => '',
                'startingBalances' => [
                    'ticket' => 0,
                    'coin' => 0,
                ],
                'buttons' => [
                    '1' => ['label' => 'Coin Spin', 'currency' => 'coin', 'cost' => 10, 'enabled' => true],
                    '2' => ['label' => 'Ticket Spin', 'currency' => 'ticket', 'cost' => 1, 'enabled' => true],
                ],
            ],
            'tiers' => [
                ['id' => 'common', 'name' => 'สีเขียว', 'tier' => 'Common', 'rate' => 54, 'displayRate' => 54, 'ball' => 'ball_1.png', 'accent' => '#64d87c', 'soft' => '#e4ffe7', 'deep' => '#238447', 'active' => true, 'visible' => true, 'summary' => 'รางวัลพื้นฐานที่ออกง่าย เหมาะกับการสะสมรายวันและใช้เป็นวัตถุดิบอัปเกรด'],
                ['id' => 'rare', 'name' => 'สีฟ้า', 'tier' => 'Rare', 'rate' => 28, 'displayRate' => 28, 'ball' => 'ball_2.png', 'accent' => '#58bfff', 'soft' => '#e3f6ff', 'deep' => '#2479b8', 'active' => true, 'visible' => true, 'summary' => 'รางวัลระดับกลางที่เริ่มมีไอเท็มตกแต่งและโบนัสแบบเวลาจำกัด'],
                ['id' => 'epic', 'name' => 'สีม่วง', 'tier' => 'Epic', 'rate' => 12, 'displayRate' => 12, 'ball' => 'ball_3.png', 'accent' => '#a974ff', 'soft' => '#efe4ff', 'deep' => '#5d34ae', 'active' => true, 'visible' => true, 'summary' => 'รางวัลที่เริ่มมีเอฟเฟกต์พิเศษ เหมาะกับของแต่งโปรไฟล์และคอลเลกชันหายาก'],
                ['id' => 'legendary', 'name' => 'สีทอง', 'tier' => 'Legendary', 'rate' => 5, 'displayRate' => 5, 'ball' => 'ball_4.png', 'accent' => '#f5bd45', 'soft' => '#fff0c7', 'deep' => '#9a6420', 'active' => true, 'visible' => true, 'summary' => 'รางวัลหายากที่มีมูลค่าสูง ใช้โชว์สถานะและปลดล็อกของตกแต่งบางชุด'],
                ['id' => 'mythic', 'name' => 'สีรุ้ง', 'tier' => 'Mythic', 'rate' => 1, 'displayRate' => 1, 'ball' => 'ball_5.png', 'accent' => '#ff78cf', 'soft' => '#efffff', 'deep' => '#7b3bb3', 'active' => true, 'visible' => true, 'special' => true, 'summary' => 'รางวัลระดับสูงสุด มีเอฟเฟกต์เฉพาะและควรถูกนำเสนอให้เด่นที่สุดในเกม'],
            ],
            'prizes' => [
                ['id' => 'green-gamer-p', 'tierId' => 'common', 'type' => 'item', 'name' => 'Gamer P', 'shortName' => 'Gamer P', 'image' => 'images/item-1.png', 'description' => "เหรียญ Gamer P สำหรับแลกของในร้านค้า", 'detailTemplateId' => 'item-store-detail', 'detailText' => '', 'conditionTemplateId' => 'general-condition', 'conditionText' => '', 'badge' => 'Daily', 'statusIcon' => 'images/ICO1.png', 'pickDate' => self::futurePickDate(18800), 'expireAction' => 'ended', 'coinPrice' => 120, 'displayQuantityMode' => 'manual', 'displayQuantity' => 12765, 'internalWeight' => 60, 'displayWeight' => 60, 'active' => true, 'visible' => true, 'sortOrder' => 10],
                ['id' => 'green-drop', 'tierId' => 'common', 'type' => 'item', 'name' => 'Green Drop', 'shortName' => 'Green Drop', 'image' => 'images/ball_1.png', 'description' => 'ลูกกาชาสีเขียวสำหรับสะสมรายวัน', 'detailTemplateId' => 'item-basic-detail', 'detailText' => '', 'conditionTemplateId' => 'general-condition', 'conditionText' => '', 'badge' => '', 'statusIcon' => '', 'pickDate' => self::futurePickDate(24200), 'internalWeight' => 40, 'displayWeight' => 40, 'active' => true, 'visible' => true, 'sortOrder' => 20],
                ['id' => 'blue-sky-ball', 'tierId' => 'rare', 'type' => 'item', 'name' => 'Sky Ball', 'shortName' => 'Sky Ball', 'image' => 'images/ball_2.png', 'description' => 'ลูกกาชาสีฟ้าพร้อมโบนัสสะสม', 'detailTemplateId' => 'item-basic-detail', 'detailText' => '', 'conditionTemplateId' => 'general-condition', 'conditionText' => '', 'badge' => 'Rate Up', 'statusIcon' => '', 'pickDate' => self::futurePickDate(14200), 'internalWeight' => 45, 'displayWeight' => 45, 'active' => true, 'visible' => true, 'sortOrder' => 30],
                ['id' => 'blue-prize-pin', 'tierId' => 'rare', 'type' => 'role', 'groupId' => 'blue-prize-pin', 'name' => 'Prize Pin Role', 'shortName' => 'Prize Pin', 'image' => 'images/icon_2.png', 'description' => 'ยศรางวัลตัวอย่างแบบจำกัดเวลา', 'detailTemplateId' => 'role-basic-detail', 'detailText' => '', 'conditionTemplateId' => 'role-limited-condition', 'conditionText' => '', 'badge' => 'New', 'statusIcon' => 'images/ICO1.png', 'pickDate' => '', 'internalWeight' => 55, 'displayWeight' => 55, 'active' => true, 'visible' => true, 'sortOrder' => 40, 'discordRoleId' => '', 'roleDurationDays' => 7],
                ['id' => 'purple-prism', 'tierId' => 'epic', 'type' => 'item', 'name' => 'Prism Orb', 'shortName' => 'Prism Orb', 'image' => 'images/ball_3.png', 'description' => 'รางวัล Epic พร้อมประกายพิเศษ', 'detailTemplateId' => 'item-basic-detail', 'detailText' => '', 'conditionTemplateId' => 'general-condition', 'conditionText' => '', 'badge' => 'Epic', 'statusIcon' => '', 'pickDate' => self::futurePickDate(16400), 'internalWeight' => 100, 'displayWeight' => 100, 'active' => true, 'visible' => true, 'sortOrder' => 50],
                ['id' => 'gold-core', 'tierId' => 'legendary', 'type' => 'item', 'name' => 'Gold Core', 'shortName' => 'Gold Core', 'image' => 'images/ball_4.png', 'description' => 'แกนรางวัลสีทองสำหรับแลกบันเดิลพรีเมียม', 'detailTemplateId' => 'item-store-detail', 'detailText' => '', 'conditionTemplateId' => 'general-condition', 'conditionText' => '', 'badge' => 'Hot', 'statusIcon' => '', 'pickDate' => self::futurePickDate(9300), 'internalWeight' => 100, 'displayWeight' => 100, 'active' => true, 'visible' => true, 'sortOrder' => 60],
                ['id' => 'mythic-ball', 'tierId' => 'mythic', 'type' => 'item', 'name' => 'Mythic Ball', 'shortName' => 'Mythic Ball', 'image' => 'images/ball_5.png', 'description' => 'รางวัลสูงสุดพร้อมเอฟเฟกต์พิเศษ', 'detailTemplateId' => 'item-basic-detail', 'detailText' => '', 'conditionTemplateId' => 'general-condition', 'conditionText' => '', 'badge' => 'SSS', 'statusIcon' => 'images/ICO1.png', 'pickDate' => self::futurePickDate(7400), 'internalWeight' => 100, 'displayWeight' => 100, 'active' => true, 'visible' => true, 'sortOrder' => 70],
            ],
            'templates' => [
                ['id' => 'item-basic-detail', 'type' => 'detail', 'name' => 'รายละเอียดไอเทมทั่วไป', 'content' => 'ของรางวัลชิ้นนี้ใช้สำหรับสะสมหรือใช้งานในระบบเกมตามรายละเอียดที่กำหนดไว้'],
                ['id' => 'item-store-detail', 'type' => 'detail', 'name' => 'รายละเอียดไอเทมร้านค้า', 'content' => 'ของรางวัลชิ้นนี้สามารถนำไปใช้งานต่อในระบบร้านค้าและกิจกรรมที่รองรับ'],
                ['id' => 'role-basic-detail', 'type' => 'detail', 'name' => 'รายละเอียดรางวัลยศทั่วไป', 'content' => 'ยศรางวัลนี้จะผูกกับบัญชี Discord ที่ล็อกอินไว้ และปลดล็อกสิทธิ์ตามรายการด้านล่าง'],
                ['id' => 'general-condition', 'type' => 'condition', 'name' => 'เงื่อนไขทั่วไป', 'content' => 'เมื่อได้รับรางวัลแล้ว ระบบจะผูกข้อมูลเข้ากับบัญชีผู้เล่นที่ล็อกอิน Discord ไว้'],
                ['id' => 'role-limited-condition', 'type' => 'condition', 'name' => 'เงื่อนไขรางวัลยศจำกัดเวลา', 'content' => 'หากเป็นยศแบบมีอายุ ระบบจะนับเวลาตามจำนวนวันที่ระบุทันทีหลังรับรางวัลสำเร็จ'],
            ],
            'conditions' => [
                'pityEvery' => 0,
                'pityTierId' => 'mythic',
                'quotaWindowDays' => 1,
            ],
        ];
    }

    public static function load(bool $seed = true): array
    {
        $defaults = self::defaults();

        try {
            $row = Database::fetch(
                'SELECT settingValueJson FROM tbl_setting WHERE settingKey = :settingKey',
                ['settingKey' => self::SETTING_KEY]
            );
        } catch (Throwable) {
            return $defaults;
        }

        if (!$row || trim((string) ($row['settingValueJson'] ?? '')) === '') {
            if ($seed) {
                self::save($defaults);
            }
            return $defaults;
        }

        $decoded = json_decode((string) $row['settingValueJson'], true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        $needsMigration = self::requiresCountdownMigration($decoded) || self::requiresPrizeFieldMigration($decoded);
        if ($needsMigration) {
            $decoded = self::migratePrizeFields($decoded);
        }

        $normalized = self::normalize(self::mergeWithDefaults($decoded));
        if ($needsMigration) {
            try {
                self::save($normalized);
            } catch (Throwable) {
                // Keep the normalized config in memory even if the migration save fails.
            }
        }

        return $normalized;
    }

    public static function save(array $config): array
    {
        $normalized = self::normalize(self::mergeWithDefaults($config));
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

        return $normalized;
    }

    private static function mergeWithDefaults(array $config): array
    {
        $defaults = self::defaults();
        $merged = $defaults;
        foreach (['version', 'settings', 'conditions'] as $key) {
            if (array_key_exists($key, $config)) {
                $merged[$key] = is_array($config[$key] ?? null) && is_array($defaults[$key] ?? null)
                    ? array_replace_recursive($defaults[$key], $config[$key])
                    : $config[$key];
            }
        }
        foreach (['tiers', 'prizes', 'templates'] as $key) {
            if (isset($config[$key]) && is_array($config[$key])) {
                $merged[$key] = $config[$key];
            }
        }
        return $merged;
    }

    public static function normalize(array $config): array
    {
        $config['version'] = max(9, (int) ($config['version'] ?? 9));
        $config['settings'] = is_array($config['settings'] ?? null) ? $config['settings'] : [];
        $settings = &$config['settings'];
        $settings['enabled'] = self::boolValue($settings['enabled'] ?? true);
        $settings['campaignCounterVisible'] = self::boolValue($settings['campaignCounterVisible'] ?? true);
        $settings['defaultRoleIcon'] = trim((string) ($settings['defaultRoleIcon'] ?? ''));
        $settings['defaultItemIcon'] = trim((string) ($settings['defaultItemIcon'] ?? ''));
        $settings['defaultButtonId'] = 1;
        $settings['startingBalances'] = is_array($settings['startingBalances'] ?? null) ? $settings['startingBalances'] : [];
        foreach (['ticket' => 0, 'coin' => 0] as $currency => $default) {
            $settings['startingBalances'][$currency] = max(0, (int) ($settings['startingBalances'][$currency] ?? $default));
        }
        $settings['buttons'] = is_array($settings['buttons'] ?? null) ? $settings['buttons'] : [];
        $button1 = is_array($settings['buttons']['1'] ?? null) ? $settings['buttons']['1'] : [];
        $button2 = is_array($settings['buttons']['2'] ?? null) ? $settings['buttons']['2'] : [];
        if (($button1['currency'] ?? '') === 'ticket' && ($button2['currency'] ?? '') === 'coin') {
            [$button1, $button2] = [$button2, $button1];
        }
        $settings['buttons']['1'] = $button1;
        $settings['buttons']['2'] = $button2;
        foreach (['1', '2'] as $buttonId) {
            $button = is_array($settings['buttons'][$buttonId] ?? null) ? $settings['buttons'][$buttonId] : [];
            $fixedCurrency = $buttonId === '1' ? 'coin' : 'ticket';
            $fixedLabel = $buttonId === '1' ? 'Coin Spin' : 'Ticket Spin';
            $fixedCost = $buttonId === '1' ? 10 : 1;
            $settings['buttons'][$buttonId] = [
                'label' => trim((string) ($button['label'] ?? '')) ?: $fixedLabel,
                'currency' => $fixedCurrency,
                'cost' => max(1, min(999999, (int) ($button['cost'] ?? $fixedCost))),
                'enabled' => self::boolValue($button['enabled'] ?? true),
            ];
        }

        $config['tiers'] = array_values(array_map(static function (array $tier): array {
            $id = preg_replace('/[^a-z0-9_-]/i', '', (string) ($tier['id'] ?? 'common')) ?: 'common';
            $defaultPrize = self::defaultPrizeById($id);
            return [
                'id' => $id,
                'name' => trim((string) ($tier['name'] ?? $id)),
                'tier' => trim((string) ($tier['tier'] ?? ucfirst($id))),
                'rate' => max(0, (float) ($tier['rate'] ?? 0)),
                'displayRate' => max(0, (float) ($tier['displayRate'] ?? ($tier['rate'] ?? 0))),
                'ball' => trim((string) ($tier['ball'] ?? 'ball_1.png')),
                'accent' => trim((string) ($tier['accent'] ?? '#64d87c')),
                'soft' => trim((string) ($tier['soft'] ?? '#e4ffe7')),
                'deep' => trim((string) ($tier['deep'] ?? '#238447')),
                'summary' => trim((string) ($tier['summary'] ?? '')),
                'active' => self::boolValue($tier['active'] ?? true),
                'visible' => self::boolValue($tier['visible'] ?? true),
                'special' => self::boolValue($tier['special'] ?? false),
            ];
        }, array_filter($config['tiers'] ?? [], 'is_array')));

        $config['templates'] = array_values(array_map(static function (array $template): array {
            $id = preg_replace('/[^a-z0-9_-]/i', '-', trim((string) ($template['id'] ?? '')));
            if ($id === '' || $id === '-') {
                $id = 'template-' . substr(sha1(json_encode($template)), 0, 10);
            }
            return [
                'id' => $id,
                'type' => in_array(($template['type'] ?? 'detail'), ['detail', 'condition'], true) ? (string) $template['type'] : 'detail',
                'name' => trim((string) ($template['name'] ?? 'Template')) ?: 'Template',
                'content' => trim((string) ($template['content'] ?? '')),
            ];
        }, array_filter($config['templates'] ?? [], 'is_array')));

        usort($config['templates'], static fn (array $a, array $b): int => strcmp($a['type'] . '|' . $a['name'], $b['type'] . '|' . $b['name']));
        $detailTemplateIds = [];
        $conditionTemplateIds = [];
        foreach ($config['templates'] as $template) {
            if (($template['type'] ?? '') === 'detail') {
                $detailTemplateIds[] = (string) $template['id'];
            } elseif (($template['type'] ?? '') === 'condition') {
                $conditionTemplateIds[] = (string) $template['id'];
            }
        }

        $tierIds = array_column($config['tiers'], 'id');
        $fallbackTierId = $tierIds[0] ?? 'common';
        $config['prizes'] = array_values(array_map(static function (array $prize) use ($tierIds, $fallbackTierId, $detailTemplateIds, $conditionTemplateIds): array {
            $id = trim((string) ($prize['id'] ?? ''));
            if ($id === '') {
                $id = 'prize-' . substr(sha1(json_encode($prize)), 0, 10);
            }
            $defaultPrize = self::defaultPrizeById($id);
            $type = in_array(($prize['type'] ?? 'item'), ['item', 'role'], true) ? (string) $prize['type'] : 'item';
            $tierId = (string) ($prize['tierId'] ?? $fallbackTierId);
            if (!in_array($tierId, $tierIds, true)) {
                $tierId = $fallbackTierId;
            }
            $duration = (int) ($prize['roleDurationDays'] ?? 0);
            if (!in_array($duration, self::allowedRoleDurations(), true)) {
                $duration = 0;
            }
            $durationOptions = self::normalizeRoleDurationOptions($prize['roleDurationOptions'] ?? null, $duration);
            $defaultImage = $type === 'item' ? 'images/item-1.png' : '';
            $image = trim((string) ($prize['image'] ?? $defaultImage));
            if ($type === 'role' && in_array($image, ['images/item-1.png', 'item-1.png'], true)) {
                $image = '';
            }
            $countdownSeconds = max(0, (int) ($prize['countdownSeconds'] ?? 0));
            $pickDate = self::normalizePickDate($prize['pickDate'] ?? '');
            if ($pickDate === '' && $countdownSeconds > 0) {
                $pickDate = self::futurePickDate($countdownSeconds);
            }
            $groupId = trim((string) ($prize['groupId'] ?? ''));
            if ($type === 'role' && $groupId === '') {
                $groupId = preg_replace('/[^a-z0-9_-]/i', '-', $id) ?: ('group-' . bin2hex(random_bytes(4)));
            }
            $normalizedPrize = [
                'id' => preg_replace('/[^a-z0-9_-]/i', '-', $id) ?: ('prize-' . bin2hex(random_bytes(4))),
                'tierId' => $tierId,
                'type' => $type,
                'groupId' => $type === 'role' ? $groupId : '',
                'name' => trim((string) ($prize['name'] ?? 'Mystery Prize')),
                'shortName' => trim((string) ($prize['shortName'] ?? '')),
                'image' => $image !== '' ? $image : $defaultImage,
                'description' => trim((string) ($prize['description'] ?? '')),
                'detailTemplateId' => in_array((string) ($prize['detailTemplateId'] ?? ''), $detailTemplateIds, true) ? (string) $prize['detailTemplateId'] : '',
                'detailText' => trim((string) ($prize['detailText'] ?? '')),
                'conditionTemplateId' => in_array((string) ($prize['conditionTemplateId'] ?? ''), $conditionTemplateIds, true) ? (string) $prize['conditionTemplateId'] : '',
                'conditionText' => trim((string) ($prize['conditionText'] ?? '')),
                'badge' => trim((string) ($prize['badge'] ?? '')),
                'statusIcon' => trim((string) ($prize['statusIcon'] ?? '')),
                'pickDate' => $pickDate,
                'expireAction' => in_array(($prize['expireAction'] ?? ($defaultPrize['expireAction'] ?? 'ended')), ['ended', 'hide'], true) ? ($prize['expireAction'] ?? ($defaultPrize['expireAction'] ?? 'ended')) : 'ended',
                'coinPrice' => max(0, (int) ($prize['coinPrice'] ?? ($defaultPrize['coinPrice'] ?? 0))),
                'displayQuantityMode' => in_array(($prize['displayQuantityMode'] ?? ($defaultPrize['displayQuantityMode'] ?? 'none')), ['none', 'manual', 'quota'], true) ? ($prize['displayQuantityMode'] ?? ($defaultPrize['displayQuantityMode'] ?? 'none')) : 'none',
                'displayQuantity' => max(0, (int) ($prize['displayQuantity'] ?? ($defaultPrize['displayQuantity'] ?? 0))),
                'internalWeight' => max(0, (float) ($prize['internalWeight'] ?? 1)),
                'displayWeight' => max(0, (float) ($prize['displayWeight'] ?? ($prize['internalWeight'] ?? 1))),
                'active' => self::boolValue($prize['active'] ?? true),
                'visible' => self::boolValue($prize['visible'] ?? true),
                'sortOrder' => (int) ($prize['sortOrder'] ?? 100),
                'discordRoleId' => trim((string) ($prize['discordRoleId'] ?? '')),
                'roleDurationDays' => $duration,
                'roleDurationOptions' => $durationOptions,
                'roleImageOverride' => '',
            ];

            if ($type !== 'role') {
                unset($normalizedPrize['roleImageOverride']);
                return $normalizedPrize;
            }

            $roleGroupConfig = self::normalizeRoleGroupConfig(
                $prize['roleGroupConfig'] ?? null,
                $normalizedPrize,
                $tierIds,
                $fallbackTierId,
                $detailTemplateIds,
                $conditionTemplateIds
            );
            $roleChildConfig = self::normalizeRoleChildConfig($prize['roleChildConfig'] ?? null, $roleGroupConfig);
            $roleImageOverride = trim((string) ($prize['roleImageOverride'] ?? ''));
            if ($roleImageOverride === '') {
                $rawImage = trim((string) ($prize['image'] ?? ''));
                $sharedImage = trim((string) ($roleGroupConfig['image'] ?? ''));
                if ($rawImage !== '' && $rawImage !== $sharedImage) {
                    $roleImageOverride = $rawImage;
                }
            }
            $normalizedPrize['roleImageOverride'] = $roleImageOverride;

            return self::applyRoleConfigToPrize($normalizedPrize, $roleGroupConfig, $roleChildConfig);
        }, array_filter($config['prizes'] ?? [], 'is_array')));

        usort($config['prizes'], static fn (array $a, array $b): int => ($a['sortOrder'] <=> $b['sortOrder']) ?: strcmp($a['name'], $b['name']));
        self::normalizeTierPercentPools($config['tiers']);
        self::normalizePrizePercentPools($config['prizes'], $tierIds);

        return $config;
    }

    private static function normalizeTierPercentPools(array &$tiers): void
    {
        self::normalizePercentPool($tiers, 'rate', static fn (array $tier): bool => !empty($tier['active']));
        self::normalizePercentPool($tiers, 'displayRate', static fn (array $tier): bool => !empty($tier['visible']));
    }

    private static function normalizePrizePercentPools(array &$prizes, array $tierIds): void
    {
        foreach ($tierIds as $tierId) {
            self::normalizePercentPool(
                $prizes,
                'internalWeight',
                static fn (array $prize): bool => ($prize['tierId'] ?? '') === $tierId && !empty($prize['active'])
            );
            self::normalizePercentPool(
                $prizes,
                'displayWeight',
                static fn (array $prize): bool => ($prize['tierId'] ?? '') === $tierId && !empty($prize['visible'])
            );
        }
    }

    private static function normalizePercentPool(array &$rows, string $field, callable $filter): void
    {
        $eligibleIndexes = [];
        $total = 0.0;

        foreach ($rows as $index => $row) {
            if (!$filter($row)) {
                continue;
            }
            $value = max(0.0, (float) ($row[$field] ?? 0));
            if ($value <= 0) {
                continue;
            }
            $eligibleIndexes[] = $index;
            $total += $value;
        }

        if ($eligibleIndexes === [] || $total <= 0) {
            return;
        }

        if (count($eligibleIndexes) === 1) {
            $rows[$eligibleIndexes[0]][$field] = 100.0;
            return;
        }

        $lastIndex = array_pop($eligibleIndexes);
        $runningTotal = 0.0;

        foreach ($eligibleIndexes as $index) {
            $normalizedValue = self::roundPercent(((float) $rows[$index][$field] / $total) * 100);
            $rows[$index][$field] = $normalizedValue;
            $runningTotal += $normalizedValue;
        }

        $rows[$lastIndex][$field] = self::roundPercent(max(0.0, 100 - $runningTotal));
    }

    private static function roundPercent(float $value, int $precision = 6): float
    {
        return round($value, $precision);
    }

    public static function spinButton(array $config, int $buttonId): array
    {
        $buttonId = $buttonId > 0 ? $buttonId : (int) ($config['settings']['defaultButtonId'] ?? 1);
        $button = $config['settings']['buttons'][(string) $buttonId] ?? $config['settings']['buttons']['1'] ?? ['currency' => 'coin', 'cost' => 10, 'enabled' => true];
        $button['buttonId'] = $buttonId;
        return $button;
    }

    public static function pickTier(array $config, ?string $forcedType = null): array
    {
        $tiers = array_values(array_filter($config['tiers'], static fn (array $tier): bool => $tier['active'] && $tier['rate'] > 0));
        if ($forcedType) {
            foreach ($config['tiers'] as $tier) {
                if ($tier['id'] === $forcedType) {
                    return $tier;
                }
            }
        }
        if (!$tiers) {
            return $config['tiers'][0] ?? self::defaults()['tiers'][0];
        }
        return self::pickWeighted($tiers, 'rate');
    }

    public static function pickPrize(array $config, string $tierId): array
    {
        $pool = array_values(array_filter($config['prizes'], static fn (array $prize): bool => $prize['tierId'] === $tierId && $prize['active'] && $prize['internalWeight'] > 0));
        if (!$pool) {
            $pool = array_values(array_filter($config['prizes'], static fn (array $prize): bool => $prize['active'] && $prize['internalWeight'] > 0));
        }
        if (!$pool) {
            $pool = self::defaults()['prizes'];
        }

        return self::pickWeighted($pool, 'internalWeight');
    }

    public static function prizeWithRolledRoleDuration(array $prize): array
    {
        if (($prize['type'] ?? 'item') !== 'role') {
            return $prize;
        }

        $durationDays = max(0, (int) ($prize['roleDurationDays'] ?? 0));
        $prize['roleDurationDays'] = $durationDays;
        $prize['roleDurationLabel'] = self::roleDurationLabel($durationDays);
        $prize['roleDurationOptions'] = [$durationDays];
        $prize['roleDurationPoolLabel'] = $prize['roleDurationLabel'];
        return $prize;
    }

    public static function buildGachaPrizePageTiers(array $config): array
    {
        $out = [];
        $templates = array_column($config['templates'] ?? [], null, 'id');
        $roleDirectory = self::loadGachaRoleDirectory();
        foreach ($config['tiers'] as $tier) {
            if (!$tier['visible']) {
                continue;
            }
            $itemShelves = [];
            $itemList = [];
            $roleList = [];
            foreach ($config['prizes'] as $prize) {
                if ($prize['tierId'] !== $tier['id'] || !$prize['visible']) {
                    continue;
                }
                $fallbackIcon = ($prize['type'] ?? 'item') === 'role'
                    ? (string) ($config['settings']['defaultRoleIcon'] ?? '')
                    : (string) ($config['settings']['defaultItemIcon'] ?? '');
                $image = self::imageFileForGachaPrizePage(trim((string) ($prize['image'] ?? '')) !== '' ? $prize['image'] : $fallbackIcon);
                $statusIcon = self::imageFileForGachaPrizePage($prize['statusIcon']);
                $pickDate = self::normalizePickDate($prize['pickDate'] ?? '');
                $pickDateLabel = self::displayPickDate($pickDate);
                $isExpired = self::isExpiredPickDate($pickDate);
                if ($isExpired && ($prize['expireAction'] ?? 'ended') === 'hide') {
                    continue;
                }
                $detailDescription = self::resolvePrizeText($prize, $templates, 'detail', '');
                $conditionText = self::resolvePrizeText($prize, $templates, 'condition', '');
                $displayQuantityLabel = self::displayQuantityLabel((string) ($prize['displayQuantityMode'] ?? 'none'), (int) ($prize['displayQuantity'] ?? 0));
                $shortName = trim((string) ($prize['shortName'] ?? '')) ?: trim((string) ($prize['name'] ?? '')) ?: 'Mystery Prize';

                if (($prize['type'] ?? 'item') === 'role') {
                    $role = $roleDirectory[(string) ($prize['discordRoleId'] ?? '')] ?? null;
                    $discordRoleName = trim((string) ($role['roleName'] ?? ''));
                    $roleName = self::resolveRoleNameTemplate((string) ($prize['name'] ?? ''), $discordRoleName);
                    $permissionDetails = array_values(array_filter($role['permissionDetails'] ?? [], 'is_array'));
                    $roleDurationDays = (int) ($prize['roleDurationDays'] ?? 0);
                    $roleDurationLabel = self::roleDurationLabel($roleDurationDays);
                    $roleDurationOptions = [$roleDurationDays];
                    $shortName = trim((string) ($prize['shortName'] ?? '')) !== ''
                        ? self::resolveRoleNameTemplate((string) $prize['shortName'], $discordRoleName)
                        : $roleName;
                    $roleLine = [];
                    if ($roleName !== '' && mb_strtolower($roleName) !== mb_strtolower($shortName)) {
                        $roleLine[] = $roleName;
                    }
                    if (!empty($role['roleSeriesName'])) {
                        $roleLine[] = (string) $role['roleSeriesName'];
                    }
                    $roleList[] = [
                        'icon' => self::rolePrizeImage((string) ($role['roleIconUrl'] ?? ''), $image),
                        'name' => $roleName,
                        'shortName' => $shortName !== '' ? $shortName : $roleName,
                        'line' => implode(' · ', array_values(array_filter($roleLine, static fn (mixed $value): bool => trim((string) $value) !== ''))),
                        'type' => 'role',
                        'typeLabel' => 'ยศ',
                        'badge' => $prize['badge'],
                        'statusIcon' => $statusIcon,
                        'pickDate' => $pickDate,
                        'pickDateLabel' => $pickDateLabel,
                        'isExpired' => $isExpired,
                        'expireAction' => (string) ($prize['expireAction'] ?? 'ended'),
                        'coinPrice' => (int) ($prize['coinPrice'] ?? 0),
                        'displayQuantityLabel' => $displayQuantityLabel,
                        'description' => $detailDescription,
                        'conditionText' => $conditionText,
                        'roleDurationDays' => $roleDurationDays,
                        'roleDurationLabel' => $roleDurationLabel,
                        'roleDurationOptions' => $roleDurationOptions,
                        'roleDurationPoolLabel' => self::roleDurationLabel($roleDurationDays),
                        'roleColor' => $role['roleColorHex'] ?? '',
                        'roleId' => (string) ($prize['discordRoleId'] ?? ''),
                        'roleTier' => (string) ($role['roleTier'] ?? ''),
                        'roleSeriesId' => (string) ($role['roleSeriesId'] ?? ''),
                        'roleSeriesName' => (string) ($role['roleSeriesName'] ?? ''),
                        'roleSeriesBadge' => (string) ($role['roleSeriesBadge'] ?? ''),
                        'roleSeriesDescription' => (string) ($role['roleSeriesDescription'] ?? ''),
                        'roleSeriesSortOrder' => (int) ($role['roleSeriesSortOrder'] ?? 9999),
                        'roleConfigGroupId' => (string) ($prize['groupId'] ?? ''),
                        'permissionCount' => count($permissionDetails),
                        'permissionDetails' => $permissionDetails,
                        'permissionBadges' => self::permissionBadgeList($permissionDetails),
                    ];
                    continue;
                }

                $itemShelves[] = [
                    'icon' => $image,
                    'name' => $prize['name'],
                    'shortName' => $shortName,
                    'type' => 'item',
                    'typeLabel' => 'ไอเทม',
                    'badge' => $prize['badge'],
                    'statusIcon' => $statusIcon,
                    'pickDate' => $pickDate,
                    'pickDateLabel' => $pickDateLabel,
                    'isExpired' => $isExpired,
                    'expireAction' => (string) ($prize['expireAction'] ?? 'ended'),
                    'coinPrice' => (int) ($prize['coinPrice'] ?? 0),
                    'displayQuantityLabel' => $displayQuantityLabel,
                    'description' => $detailDescription,
                    'conditionText' => $conditionText,
                ];
                $itemList[] = [
                    'icon' => $image,
                    'name' => $prize['name'] ?: 'Mystery Prize',
                    'shortName' => $shortName,
                    'line' => implode(' · ', array_values(array_filter([$shortName, $detailDescription], static fn (mixed $value): bool => trim((string) $value) !== ''))),
                    'type' => 'item',
                    'typeLabel' => 'ไอเทม',
                    'badge' => $prize['badge'],
                    'statusIcon' => $statusIcon,
                    'pickDate' => $pickDate,
                    'pickDateLabel' => $pickDateLabel,
                    'isExpired' => $isExpired,
                    'expireAction' => (string) ($prize['expireAction'] ?? 'ended'),
                    'coinPrice' => (int) ($prize['coinPrice'] ?? 0),
                    'displayQuantityLabel' => $displayQuantityLabel,
                    'description' => $detailDescription,
                    'conditionText' => $conditionText,
                ];
            }
            $out[] = [
                'id' => 'tier-' . $tier['id'],
                'name' => $tier['name'],
                'tier' => $tier['tier'],
                'rate' => number_format((float) $tier['displayRate'], 2) . '%',
                'ball' => $tier['ball'],
                'accent' => $tier['accent'],
                'soft' => $tier['soft'],
                'deep' => $tier['deep'],
                'special' => $tier['special'] ?? '',
                'summary' => $tier['summary'],
                'itemShelves' => $itemShelves,
                'itemList' => $itemList,
                'roleList' => $roleList,
                'roleGroups' => self::groupRolePrizeSeries($roleList),
            ];
        }

        $rank = ['Mythic' => 1, 'Legendary' => 2, 'Epic' => 3, 'Rare' => 4, 'Common' => 5];
        usort($out, static fn (array $a, array $b): int => ($rank[$a['tier']] ?? 99) <=> ($rank[$b['tier']] ?? 99));
        return $out;
    }

    public static function publicPrizePayload(array $config, array $prize, array $tier): array
    {
        $templates = array_column($config['templates'] ?? [], null, 'id');
        $roleDirectory = self::loadGachaRoleDirectory();
        $type = (string) ($prize['type'] ?? 'item');
        $pickDate = self::normalizePickDate($prize['pickDate'] ?? '');
        $image = trim((string) ($prize['image'] ?? ''));
        if ($type === 'role' && in_array($image, ['images/item-1.png', 'item-1.png'], true)) {
            $image = '';
        }
        if ($image === '') {
            $image = $type === 'role'
                ? trim((string) ($config['settings']['defaultRoleIcon'] ?? ''))
                : trim((string) ($config['settings']['defaultItemIcon'] ?? ''));
        }
        $shortName = trim((string) ($prize['shortName'] ?? '')) ?: trim((string) ($prize['name'] ?? '')) ?: 'Mystery Prize';
        $payload = [
            'id' => (string) ($prize['id'] ?? ''),
            'name' => trim((string) ($prize['name'] ?? 'Mystery Prize')) ?: 'Mystery Prize',
            'shortName' => $shortName,
            'image' => $image !== '' ? $image : 'images/item-1.png',
            'rarity' => (string) ($tier['id'] ?? 'common'),
            'tierId' => (string) ($tier['id'] ?? 'common'),
            'tierName' => (string) ($tier['tier'] ?? ucfirst((string) ($tier['id'] ?? 'common'))),
            'type' => $type,
            'badge' => (string) ($prize['badge'] ?? ''),
            'statusIcon' => trim((string) ($prize['statusIcon'] ?? '')),
            'description' => self::resolvePrizeText($prize, $templates, 'detail', ''),
            'conditionText' => self::resolvePrizeText($prize, $templates, 'condition', ''),
            'pickDate' => $pickDate,
            'pickDateLabel' => self::displayPickDate($pickDate),
            'displayQuantityLabel' => self::displayQuantityLabel((string) ($prize['displayQuantityMode'] ?? 'none'), (int) ($prize['displayQuantity'] ?? 0)),
            'coinPrice' => (int) ($prize['coinPrice'] ?? 0),
            'roleDurationDays' => (int) ($prize['roleDurationDays'] ?? 0),
            'roleDurationLabel' => self::roleDurationLabel((int) ($prize['roleDurationDays'] ?? 0)),
            'roleDurationOptions' => [(int) ($prize['roleDurationDays'] ?? 0)],
            'roleDurationPoolLabel' => self::roleDurationLabel((int) ($prize['roleDurationDays'] ?? 0)),
        ];

        if ($type !== 'role') {
            return $payload;
        }

        $role = $roleDirectory[(string) ($prize['discordRoleId'] ?? '')] ?? [];
        $discordRoleName = trim((string) ($role['roleName'] ?? ''));
        $payload['name'] = self::resolveRoleNameTemplate((string) ($prize['name'] ?? ''), $discordRoleName);
        $payload['shortName'] = trim((string) ($prize['shortName'] ?? '')) !== ''
            ? self::resolveRoleNameTemplate((string) ($prize['shortName'] ?? ''), $discordRoleName)
            : $payload['name'];
        $payload['image'] = trim((string) ($prize['image'] ?? '')) !== ''
            ? trim((string) ($prize['image'] ?? ''))
            : (trim((string) ($role['roleIconUrl'] ?? '')) !== '' ? (string) $role['roleIconUrl'] : 'images/icon_roles_blank.png');
        $payload['roleIconUrl'] = trim((string) ($role['roleIconUrl'] ?? ''));
        $payload['roleColor'] = (string) ($role['roleColorHex'] ?? '');
        $payload['roleId'] = (string) ($prize['discordRoleId'] ?? '');
        $payload['roleName'] = $discordRoleName;
        $payload['roleTier'] = (string) ($role['roleTier'] ?? '');
        $payload['roleSeriesId'] = (string) ($role['roleSeriesId'] ?? '');
        $payload['roleSeriesName'] = (string) ($role['roleSeriesName'] ?? '');
        $payload['roleConfigGroupId'] = (string) ($prize['groupId'] ?? '');

        return $payload;
    }

    public static function timeline(array $config): array
    {
        $events = [];
        foreach ($config['tiers'] as $tier) {
            $events[] = [
                'label' => $tier['tier'] . ' rate',
                'scope' => $tier['id'],
                'detail' => ($tier['active'] ? 'Active' : 'Paused') . ' · ' . number_format((float) $tier['rate'], 2) . '% internal / ' . number_format((float) $tier['displayRate'], 2) . '% public',
                'status' => $tier['active'] ? 'active' : 'paused',
            ];
        }
        $pityEvery = (int) ($config['conditions']['pityEvery'] ?? 0);
        if ($pityEvery > 0) {
            $events[] = [
                'label' => 'Guarantee window',
                'scope' => $config['conditions']['pityTierId'] ?? 'mythic',
                'detail' => 'Force this tier every ' . $pityEvery . ' draws if no higher prize appears first.',
                'status' => 'condition',
            ];
        }

        return $events;
    }

    private static function pickWeighted(array $rows, string $weightKey): array
    {
        $total = array_sum(array_map(static fn (array $row): float => max(0, (float) ($row[$weightKey] ?? 0)), $rows));
        if ($total <= 0) {
            return $rows[array_rand($rows)];
        }
        $roll = random_int(1, 1000000) / 1000000 * $total;
        $cursor = 0.0;
        foreach ($rows as $row) {
            $cursor += max(0, (float) ($row[$weightKey] ?? 0));
            if ($roll <= $cursor) {
                return $row;
            }
        }
        return $rows[array_key_last($rows)];
    }

    private static function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private static function imageFileForGachaPrizePage(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'images/')) {
            return substr($path, 7);
        }
        if (str_starts_with($path, '/discord/gacha/images/')) {
            return substr($path, strlen('/discord/gacha/images/'));
        }
        return $path;
    }

    private static function futurePickDate(int $seconds): string
    {
        return $seconds > 0 ? date('Y-m-d H:i:s', time() + $seconds) : '';
    }

    private static function defaultPrizeById(string $id): array
    {
        foreach (self::defaults()['prizes'] as $defaultPrize) {
            if (($defaultPrize['id'] ?? '') === $id) {
                return $defaultPrize;
            }
        }

        return [];
    }

    private static function normalizePickDate(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        $raw = str_replace('T', ' ', $raw);
        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private static function pickDateTimestamp(string $pickDate): int
    {
        if ($pickDate === '') {
            return 0;
        }

        $timestamp = strtotime($pickDate);
        return $timestamp === false ? 0 : $timestamp;
    }

    private static function displayPickDate(string $pickDate): string
    {
        $timestamp = self::pickDateTimestamp($pickDate);
        return $timestamp > 0 ? date('d/m/Y H:i', $timestamp) : '';
    }

    private static function isExpiredPickDate(string $pickDate): bool
    {
        $timestamp = self::pickDateTimestamp($pickDate);
        return $timestamp > 0 && $timestamp <= time();
    }

    private static function displayQuantityLabel(string $mode, int $quantity): string
    {
        if ($mode === 'none') {
            return '';
        }
        if ($mode === 'quota') {
            return $quantity > 0 ? number_format($quantity) . ' สิทธิ์' : 'ตามโควต้าจริง';
        }
        return $quantity > 0 ? number_format($quantity) . ' สิทธิ์' : '';
    }

    private static function roleDurationLabel(int $days): string
    {
        return $days > 0 ? $days . ' วัน' : 'ถาวร';
    }

    private static function roleDurationPoolLabel(array $days): string
    {
        $normalized = self::normalizeRoleDurationOptions($days, 0);
        if (count($normalized) === 1) {
            return self::roleDurationLabel((int) $normalized[0]);
        }
        return 'สุ่ม ' . implode(' / ', array_map(static fn (int $day): string => self::roleDurationLabel($day), $normalized));
    }

    private static function allowedRoleDurations(): array
    {
        return [0, 3, 7, 14, 30, 60, 90];
    }

    private static function normalizeRoleDurationOptions(mixed $value, int $fallbackDays): array
    {
        $allowed = self::allowedRoleDurations();
        $rawValues = is_array($value) ? $value : [$fallbackDays];
        $out = [];
        foreach ($rawValues as $raw) {
            $day = max(0, (int) $raw);
            if (!in_array($day, $allowed, true) || in_array($day, $out, true)) {
                continue;
            }
            $out[] = $day;
        }
        if (!$out) {
            $fallback = in_array($fallbackDays, $allowed, true) ? $fallbackDays : 0;
            $out[] = $fallback;
        }
        usort($out, static fn (int $left, int $right): int => $left <=> $right);
        return $out;
    }

    private static function loadGachaRoleDirectory(): array
    {
        if (!class_exists('Database') || !class_exists('Bootstrap')) {
            return [];
        }

        try {
            $roles = Database::fetchAll(
                'SELECT roleId, roleName, roleColor, rolePosition, permissions, iconHash
                 FROM tbl_role
                 WHERE guildId = :guildId AND deleteDate IS NULL',
                ['guildId' => (string) Bootstrap::config('discord.guildId', '')]
            );
        } catch (Throwable) {
            return [];
        }

        $roleCatalog = class_exists('RoleCatalogService') ? RoleCatalogService::load(false) : null;
        $decoratedRoles = (class_exists('RoleCatalogService') && is_array($roleCatalog))
            ? RoleCatalogService::decorateRoles($roles, $roleCatalog)
            : $roles;

        $directory = [];
        foreach ($decoratedRoles as $role) {
            $roleId = (string) ($role['roleId'] ?? '');
            if ($roleId === '') {
                continue;
            }

            $directory[$roleId] = [
                'roleName' => trim((string) ($role['roleName'] ?? '')),
                'roleColorHex' => self::roleColorHex((int) ($role['roleColor'] ?? 0)),
                'rolePosition' => (int) ($role['rolePosition'] ?? 0),
                'roleIconUrl' => class_exists('DiscordAssets') ? DiscordAssets::roleIcon($roleId, $role['iconHash'] ?? null, 64) : '',
                'roleTier' => trim((string) ($role['roleTier'] ?? '')),
                'roleSeriesId' => trim((string) ($role['roleSeriesId'] ?? '')),
                'roleSeriesName' => trim((string) ($role['roleSeriesName'] ?? '')),
                'roleSeriesBadge' => trim((string) ($role['roleSeriesBadge'] ?? '')),
                'roleSeriesDescription' => trim((string) ($role['roleSeriesDescription'] ?? '')),
                'roleSeriesSortOrder' => (int) ($role['roleSeriesSortOrder'] ?? 9999),
                'permissionDetails' => class_exists('RolePermissionDescriptionService')
                    ? RolePermissionDescriptionService::describeAllowedPermissions($role['permissions'] ?? null)
                    : DiscordPermissions::decode($role['permissions'] ?? null),
            ];
        }

        return $directory;
    }

    private static function roleColorHex(int $roleColor): string
    {
        if ($roleColor <= 0) {
            return '';
        }

        return '#' . str_pad(strtolower(dechex($roleColor)), 6, '0', STR_PAD_LEFT);
    }

    private static function resolvePrizeText(array $prize, array $templates, string $field, string $fallback = ''): string
    {
        $templateIdField = $field === 'condition' ? 'conditionTemplateId' : 'detailTemplateId';
        $textField = $field === 'condition' ? 'conditionText' : 'detailText';
        $legacyField = $field === 'condition' ? '' : 'description';

        $override = trim((string) ($prize[$textField] ?? ''));
        if ($override !== '') {
            return $override;
        }

        $templateId = trim((string) ($prize[$templateIdField] ?? ''));
        if ($templateId !== '' && is_array($templates[$templateId] ?? null)) {
            $templateText = trim((string) ($templates[$templateId]['content'] ?? ''));
            if ($templateText !== '') {
                return $templateText;
            }
        }

        if ($legacyField !== '') {
            $legacy = trim((string) ($prize[$legacyField] ?? ''));
            if ($legacy !== '') {
                return $legacy;
            }
        }

        return trim($fallback);
    }

    private static function resolveRoleNameTemplate(string $template, string $roleName): string
    {
        $template = trim($template);
        $roleName = trim($roleName);
        if ($template === '' || in_array($template, ['New Gachapon Role Prize', 'Prize Pin Role'], true)) {
            return $roleName !== '' ? $roleName : 'Mystery Prize';
        }

        $resolved = str_replace('<roleName>', $roleName !== '' ? $roleName : 'Role', $template);
        return trim($resolved) !== '' ? trim($resolved) : ($roleName !== '' ? $roleName : 'Mystery Prize');
    }

    private static function permissionBadgeList(array $permissionDetails): array
    {
        $badges = [];
        foreach ($permissionDetails as $permission) {
            $badge = trim((string) ($permission['badge'] ?? ''));
            if ($badge === '' || in_array($badge, $badges, true)) {
                continue;
            }
            $badges[] = $badge;
        }
        return $badges;
    }

    private static function rolePrizeImage(string $roleIconUrl, string $fallbackImage): string
    {
        $fallbackImage = trim($fallbackImage);
        if ($fallbackImage !== '') {
            return $fallbackImage;
        }

        $roleIconUrl = trim($roleIconUrl);
        if ($roleIconUrl !== '') {
            return $roleIconUrl;
        }

        return 'images/icon_roles_blank.png';
    }

    /** @return array<int, array<string, mixed>> */
    private static function groupRolePrizeSeries(array $roleList): array
    {
        $groups = [];
        foreach ($roleList as $role) {
            $seriesId = trim((string) ($role['roleSeriesId'] ?? ''));
            $key = $seriesId !== '' ? 'series:' . $seriesId : 'ungrouped';
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'id' => $seriesId !== '' ? $seriesId : 'ungrouped',
                    'name' => (string) ($role['roleSeriesName'] ?? ''),
                    'badge' => (string) ($role['roleSeriesBadge'] ?? ''),
                    'description' => (string) ($role['roleSeriesDescription'] ?? ''),
                    'sortOrder' => (int) ($role['roleSeriesSortOrder'] ?? 9999),
                    'items' => [],
                ];
            }
            $groups[$key]['items'][] = $role;
        }

        $out = array_values($groups);
        usort($out, static function (array $left, array $right): int {
            $leftNamed = trim((string) ($left['name'] ?? '')) !== '';
            $rightNamed = trim((string) ($right['name'] ?? '')) !== '';
            if ($leftNamed !== $rightNamed) {
                return $leftNamed ? -1 : 1;
            }
            return ($left['sortOrder'] <=> $right['sortOrder']) ?: strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return $out;
    }

    private static function requiresCountdownMigration(array $decoded): bool
    {
        foreach (array_filter($decoded['prizes'] ?? [], 'is_array') as $prize) {
            if (array_key_exists('countdownSeconds', $prize) && trim((string) ($prize['pickDate'] ?? '')) === '') {
                return true;
            }
        }

        return false;
    }

    private static function requiresPrizeFieldMigration(array $decoded): bool
    {
        if ((int) ($decoded['version'] ?? 0) < 8) {
            return true;
        }

        if (!isset($decoded['templates']) || !is_array($decoded['templates'])) {
            return true;
        }

        foreach (array_filter($decoded['prizes'] ?? [], 'is_array') as $prize) {
            foreach (['groupId', 'expireAction', 'coinPrice', 'displayQuantityMode', 'displayQuantity', 'shortName', 'detailTemplateId', 'detailText', 'conditionTemplateId', 'conditionText'] as $field) {
                if (!array_key_exists($field, $prize)) {
                    return true;
                }
            }
            if (($prize['type'] ?? 'item') === 'role') {
                foreach (['roleGroupConfig', 'roleChildConfig', 'roleImageOverride'] as $field) {
                    if (!array_key_exists($field, $prize)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private static function migratePrizeFields(array $decoded): array
    {
        if (!isset($decoded['prizes']) || !is_array($decoded['prizes'])) {
            return $decoded;
        }

        foreach ($decoded['prizes'] as &$prize) {
            if (!is_array($prize)) {
                continue;
            }

            $id = (string) ($prize['id'] ?? '');
            $defaultPrize = self::defaultPrizeById($id);
            foreach (['groupId', 'expireAction', 'coinPrice', 'displayQuantityMode', 'displayQuantity', 'shortName', 'detailTemplateId', 'detailText', 'conditionTemplateId', 'conditionText'] as $field) {
                if (!array_key_exists($field, $prize) && array_key_exists($field, $defaultPrize)) {
                    $prize[$field] = $defaultPrize[$field];
                }
            }

            if (($prize['type'] ?? 'item') === 'role' && trim((string) ($prize['groupId'] ?? '')) === '') {
                $prize['groupId'] = (string) ($prize['id'] ?? '');
            }
            if (($prize['type'] ?? 'item') === 'role') {
                if (!array_key_exists('roleGroupConfig', $prize) || !is_array($prize['roleGroupConfig'] ?? null)) {
                    $prize['roleGroupConfig'] = [];
                }
                if (!array_key_exists('roleChildConfig', $prize) || !is_array($prize['roleChildConfig'] ?? null)) {
                    $prize['roleChildConfig'] = self::defaultRoleChildConfig();
                }
                if (!array_key_exists('image', $prize['roleGroupConfig']) || !is_string($prize['roleGroupConfig']['image'] ?? null)) {
                    $prize['roleGroupConfig']['image'] = '';
                }
                if (!array_key_exists('roleImageOverride', $prize)) {
                    $prize['roleImageOverride'] = trim((string) ($prize['image'] ?? ''));
                }
            }

            if ($id === 'green-gamer-p') {
                if ((int) ($prize['coinPrice'] ?? 0) <= 0) {
                    $prize['coinPrice'] = 120;
                }
                if (($prize['displayQuantityMode'] ?? 'none') === 'none') {
                    $prize['displayQuantityMode'] = 'manual';
                }
                if ((int) ($prize['displayQuantity'] ?? 0) <= 0) {
                    $prize['displayQuantity'] = 12765;
                }
            }
        }
        unset($prize);

        if (!isset($decoded['templates']) || !is_array($decoded['templates'])) {
            $decoded['templates'] = self::defaults()['templates'];
        }

        $decoded['version'] = 8;
        return $decoded;
    }

    private static function normalizeRoleGroupConfig(
        mixed $rawConfig,
        array $basePrize,
        array $tierIds,
        string $fallbackTierId,
        array $detailTemplateIds,
        array $conditionTemplateIds
    ): array {
        $raw = is_array($rawConfig) ? $rawConfig : [];
        $tierId = (string) ($raw['tierId'] ?? $basePrize['tierId'] ?? $fallbackTierId);
        if (!in_array($tierId, $tierIds, true)) {
            $tierId = $fallbackTierId;
        }

        $duration = (int) ($raw['roleDurationDays'] ?? $basePrize['roleDurationDays'] ?? 0);
        if (!in_array($duration, self::allowedRoleDurations(), true)) {
            $duration = 0;
        }
        $durationOptions = [$duration];

        $pickDate = self::normalizePickDate($raw['pickDate'] ?? ($basePrize['pickDate'] ?? ''));
        $detailTemplateId = trim((string) ($raw['detailTemplateId'] ?? ($basePrize['detailTemplateId'] ?? '')));
        $conditionTemplateId = trim((string) ($raw['conditionTemplateId'] ?? ($basePrize['conditionTemplateId'] ?? '')));
        $internalWeight = max(0, (float) ($raw['internalWeight'] ?? ($basePrize['internalWeight'] ?? 1)));

        return [
            'tierId' => $tierId,
            'badge' => trim((string) ($raw['badge'] ?? ($basePrize['badge'] ?? ''))),
            'roleDurationDays' => $duration,
            'roleDurationOptions' => $durationOptions,
            'image' => trim((string) ($raw['image'] ?? '')),
            'detailTemplateId' => in_array($detailTemplateId, $detailTemplateIds, true) ? $detailTemplateId : '',
            'detailText' => trim((string) ($raw['detailText'] ?? ($basePrize['detailText'] ?? ''))),
            'description' => trim((string) ($raw['description'] ?? ($basePrize['description'] ?? ''))),
            'conditionTemplateId' => in_array($conditionTemplateId, $conditionTemplateIds, true) ? $conditionTemplateId : '',
            'conditionText' => trim((string) ($raw['conditionText'] ?? ($basePrize['conditionText'] ?? ''))),
            'statusIcon' => trim((string) ($raw['statusIcon'] ?? ($basePrize['statusIcon'] ?? ''))),
            'pickDate' => $pickDate,
            'expireAction' => in_array(($raw['expireAction'] ?? ($basePrize['expireAction'] ?? 'ended')), ['ended', 'hide'], true)
                ? (string) ($raw['expireAction'] ?? ($basePrize['expireAction'] ?? 'ended'))
                : 'ended',
            'coinPrice' => max(0, (int) ($raw['coinPrice'] ?? ($basePrize['coinPrice'] ?? 0))),
            'displayQuantityMode' => in_array(($raw['displayQuantityMode'] ?? ($basePrize['displayQuantityMode'] ?? 'none')), ['none', 'manual', 'quota'], true)
                ? (string) ($raw['displayQuantityMode'] ?? ($basePrize['displayQuantityMode'] ?? 'none'))
                : 'none',
            'displayQuantity' => max(0, (int) ($raw['displayQuantity'] ?? ($basePrize['displayQuantity'] ?? 0))),
            'internalWeight' => $internalWeight,
            'displayWeight' => max(0, (float) ($raw['displayWeight'] ?? ($basePrize['displayWeight'] ?? $internalWeight))),
            'active' => self::boolValue($raw['active'] ?? ($basePrize['active'] ?? true)),
            'visible' => self::boolValue($raw['visible'] ?? ($basePrize['visible'] ?? true)),
        ];
    }

    private static function defaultRoleChildConfig(): array
    {
        $defaults = [];
        foreach (self::roleChildOverrideDefaults() as $field => $value) {
            $defaults[$field] = [
                'enabled' => false,
                'value' => $value,
            ];
        }

        return [
            'enabled' => false,
            'overrides' => $defaults,
        ];
    }

    private static function normalizeRoleChildConfig(mixed $rawConfig, array $shared): array
    {
        $defaults = self::defaultRoleChildConfig();
        if (!is_array($rawConfig)) {
            return $defaults;
        }

        $normalized = [
            'enabled' => self::boolValue($rawConfig['enabled'] ?? false),
            'overrides' => $defaults['overrides'],
        ];
        $rawOverrides = is_array($rawConfig['overrides'] ?? null) ? $rawConfig['overrides'] : [];

        foreach ($defaults['overrides'] as $field => $default) {
            $fieldRaw = is_array($rawOverrides[$field] ?? null) ? $rawOverrides[$field] : [];
            $normalized['overrides'][$field] = [
                'enabled' => self::boolValue($fieldRaw['enabled'] ?? false),
                'value' => self::normalizeRoleChildOverrideValue($field, $fieldRaw['value'] ?? ($shared[$field] ?? $default['value']), $shared),
            ];
        }

        return $normalized;
    }

    private static function normalizeRoleChildOverrideValue(string $field, mixed $value, array $shared): mixed
    {
        return match ($field) {
            'active', 'visible' => self::boolValue($value),
            'displayQuantityMode' => in_array((string) $value, ['none', 'manual', 'quota'], true)
                ? (string) $value
                : (string) ($shared['displayQuantityMode'] ?? 'none'),
            'displayQuantity', 'coinPrice' => max(0, (int) $value),
            'internalWeight', 'displayWeight' => max(0, (float) $value),
            default => $value,
        };
    }

    private static function applyRoleConfigToPrize(array $prize, array $roleGroupConfig, array $roleChildConfig): array
    {
        foreach ([
            'tierId',
            'badge',
            'roleDurationDays',
            'roleDurationOptions',
            'detailTemplateId',
            'detailText',
            'description',
            'conditionTemplateId',
            'conditionText',
            'statusIcon',
            'pickDate',
            'expireAction',
            'coinPrice',
            'displayQuantityMode',
            'displayQuantity',
            'internalWeight',
            'displayWeight',
            'active',
            'visible',
        ] as $field) {
            if (array_key_exists($field, $roleGroupConfig)) {
                $prize[$field] = $roleGroupConfig[$field];
            }
        }

        $sharedImage = trim((string) ($roleGroupConfig['image'] ?? ''));
        $childImage = trim((string) ($prize['roleImageOverride'] ?? ''));
        $prize['image'] = $childImage !== '' ? $childImage : $sharedImage;

        if (self::boolValue($roleChildConfig['enabled'] ?? false)) {
            foreach (array_filter($roleChildConfig['overrides'] ?? [], 'is_array') as $field => $override) {
                if (self::boolValue($override['enabled'] ?? false) && array_key_exists('value', $override)) {
                    $prize[$field] = $override['value'];
                }
            }
        }

        $prize['roleGroupConfig'] = $roleGroupConfig;
        $prize['roleChildConfig'] = $roleChildConfig;

        return $prize;
    }

    private static function roleChildOverrideDefaults(): array
    {
        return [
            'active' => true,
            'visible' => true,
            'displayQuantityMode' => 'none',
            'displayQuantity' => 0,
            'coinPrice' => 0,
            'internalWeight' => 1,
            'displayWeight' => 1,
        ];
    }
}
