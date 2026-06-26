<?php

declare(strict_types=1);

final class ShopUnitService
{
    private static bool $schemaReady = false;

    /** @return array<string, float|string> */
    public static function defaultBuyTheme(): array
    {
        return [
            'holdSeconds' => 1.0,
            'buttonBgStart' => '#4b3161',
            'buttonBgEnd' => '#7657b7',
            'buttonTextColor' => '#ffffff',
            'holdSweepStart' => '#f8b86f',
            'holdSweepEnd' => '#ffe590',
        ];
    }

    /** @return array<string, float|string> */
    public static function normalizeBuyThemeDefaults(mixed $theme, ?array $fallback = null): array
    {
        $resolvedFallback = self::defaultBuyTheme();
        if (is_array($fallback)) {
            $resolvedFallback = [
                'holdSeconds' => self::normalizeHoldSeconds($fallback['holdSeconds'] ?? null, (float) $resolvedFallback['holdSeconds']),
                'buttonBgStart' => self::normalizeHexColor($fallback['buttonBgStart'] ?? null, (string) $resolvedFallback['buttonBgStart']),
                'buttonBgEnd' => self::normalizeHexColor($fallback['buttonBgEnd'] ?? null, (string) $resolvedFallback['buttonBgEnd']),
                'buttonTextColor' => self::normalizeHexColor($fallback['buttonTextColor'] ?? null, (string) $resolvedFallback['buttonTextColor']),
                'holdSweepStart' => self::normalizeHexColor($fallback['holdSweepStart'] ?? null, (string) $resolvedFallback['holdSweepStart']),
                'holdSweepEnd' => self::normalizeHexColor($fallback['holdSweepEnd'] ?? null, (string) $resolvedFallback['holdSweepEnd']),
            ];
        }

        $theme = is_array($theme) ? $theme : [];
        return [
            'holdSeconds' => self::normalizeHoldSeconds($theme['holdSeconds'] ?? null, (float) $resolvedFallback['holdSeconds']),
            'buttonBgStart' => self::normalizeHexColor($theme['buttonBgStart'] ?? null, (string) $resolvedFallback['buttonBgStart']),
            'buttonBgEnd' => self::normalizeHexColor($theme['buttonBgEnd'] ?? null, (string) $resolvedFallback['buttonBgEnd']),
            'buttonTextColor' => self::normalizeHexColor($theme['buttonTextColor'] ?? null, (string) $resolvedFallback['buttonTextColor']),
            'holdSweepStart' => self::normalizeHexColor($theme['holdSweepStart'] ?? null, (string) $resolvedFallback['holdSweepStart']),
            'holdSweepEnd' => self::normalizeHexColor($theme['holdSweepEnd'] ?? null, (string) $resolvedFallback['holdSweepEnd']),
        ];
    }

    /** @return array<string, bool|float|string> */
    public static function normalizeBuyThemeOverride(mixed $theme): array
    {
        $theme = is_array($theme) ? $theme : [];
        return [
            'overrideEnabled' => !empty($theme['overrideEnabled']),
            'holdSeconds' => self::normalizeOptionalHoldSeconds($theme['holdSeconds'] ?? null),
            'buttonBgStart' => self::normalizeOptionalHexColor($theme['buttonBgStart'] ?? null),
            'buttonBgEnd' => self::normalizeOptionalHexColor($theme['buttonBgEnd'] ?? null),
            'buttonTextColor' => self::normalizeOptionalHexColor($theme['buttonTextColor'] ?? null),
            'holdSweepStart' => self::normalizeOptionalHexColor($theme['holdSweepStart'] ?? null),
            'holdSweepEnd' => self::normalizeOptionalHexColor($theme['holdSweepEnd'] ?? null),
        ];
    }

    /** @return array<string, float|string> */
    public static function resolveUnitBuyTheme(array $unit, mixed $defaults = null): array
    {
        $resolved = self::normalizeBuyThemeDefaults(
            is_array($defaults) ? $defaults : [],
            self::defaultBuyTheme()
        );
        $override = self::normalizeBuyThemeOverride($unit['shopBuyTheme'] ?? (self::unitMetadata($unit)['shopBuyTheme'] ?? []));
        if (!$override['overrideEnabled']) {
            return $resolved;
        }

        if ($override['holdSeconds'] !== '') {
            $resolved['holdSeconds'] = self::normalizeHoldSeconds($override['holdSeconds'], (float) $resolved['holdSeconds']);
        }
        foreach (['buttonBgStart', 'buttonBgEnd', 'buttonTextColor', 'holdSweepStart', 'holdSweepEnd'] as $field) {
            if ($override[$field] !== '') {
                $resolved[$field] = self::normalizeHexColor($override[$field], (string) $resolved[$field]);
            }
        }

        return $resolved;
    }

    /** @return array<string, mixed> */
    public static function unitMetadata(array $unit): array
    {
        $decoded = json_decode((string) ($unit['metadataJson'] ?? ''), true);
        if (!is_array($decoded)) {
            $decoded = [];
        }
        if (isset($unit['metadata']) && is_array($unit['metadata'])) {
            $decoded = $unit['metadata'];
        }
        $decoded['shopBuyTheme'] = self::normalizeBuyThemeOverride($decoded['shopBuyTheme'] ?? []);
        return $decoded;
    }

    /** @return array<int, array<string, mixed>> */
    public static function defaultUnits(): array
    {
        return [
            ['unitCode' => 'coin', 'displayName' => 'เหรียญ', 'shortName' => 'Coin', 'icon' => 'fa-solid fa-coins', 'isEnabled' => 1, 'sortOrder' => 10],
            ['unitCode' => 'gem', 'displayName' => 'เพชร', 'shortName' => 'Gem', 'icon' => 'fa-solid fa-gem', 'isEnabled' => 1, 'sortOrder' => 20],
            ['unitCode' => 'ticket', 'displayName' => 'ตั๋ว', 'shortName' => 'Ticket', 'icon' => 'fa-solid fa-ticket', 'isEnabled' => 1, 'sortOrder' => 30],
            ['unitCode' => 'potion', 'displayName' => 'โพชั่น', 'shortName' => 'Potion', 'icon' => 'fa-solid fa-flask', 'isEnabled' => 1, 'sortOrder' => 40],
        ];
    }

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_shop_unit (
                unitCode varchar(80) NOT NULL,
                displayName varchar(120) NOT NULL,
                shortName varchar(40) NOT NULL,
                icon varchar(190) DEFAULT NULL,
                isEnabled tinyint(1) NOT NULL DEFAULT 1,
                sortOrder int NOT NULL DEFAULT 0,
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (unitCode),
                KEY idx_tbl_shop_unit_sort (isEnabled, sortOrder)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_shop_wallet (
                shopWalletId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                unitCode varchar(80) NOT NULL,
                balanceAmount bigint NOT NULL DEFAULT 0,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (shopWalletId),
                UNIQUE KEY uq_tbl_shop_wallet_user_unit (guildId, userId, unitCode),
                KEY idx_tbl_shop_wallet_unit (unitCode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_shop_wallet_ledger (
                shopWalletLedgerId bigint unsigned NOT NULL AUTO_INCREMENT,
                shopWalletId bigint unsigned NOT NULL,
                unitCode varchar(80) NOT NULL,
                amountDelta bigint NOT NULL,
                ledgerType varchar(80) NOT NULL,
                sourceType varchar(80) DEFAULT NULL,
                sourceId varchar(120) DEFAULT NULL,
                transactionGroupId varchar(120) DEFAULT NULL,
                actorUserId varchar(32) DEFAULT NULL,
                targetUserId varchar(32) DEFAULT NULL,
                walletBalanceBefore bigint DEFAULT NULL,
                walletBalanceAfter bigint DEFAULT NULL,
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (shopWalletLedgerId),
                KEY idx_tbl_shop_wallet_ledger_wallet (shopWalletId, createDate),
                KEY idx_tbl_shop_wallet_ledger_unit (unitCode, createDate),
                KEY idx_tbl_shop_wallet_ledger_trace (transactionGroupId, createDate),
                KEY idx_tbl_shop_wallet_ledger_source (sourceType, sourceId, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_shop_item (
                shopItemId bigint unsigned NOT NULL AUTO_INCREMENT,
                itemCode varchar(120) NOT NULL,
                itemName varchar(190) NOT NULL,
                itemType varchar(80) NOT NULL DEFAULT "item",
                image varchar(255) DEFAULT NULL,
                effectType varchar(120) DEFAULT NULL,
                effectPayloadJson longtext DEFAULT NULL,
                metadataJson longtext DEFAULT NULL,
                isActive tinyint(1) NOT NULL DEFAULT 1,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (shopItemId),
                UNIQUE KEY uq_tbl_shop_item_code (itemCode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_shop_inventory (
                shopInventoryId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                shopItemId bigint unsigned NOT NULL,
                quantity int NOT NULL DEFAULT 0,
                metadataJson longtext DEFAULT NULL,
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (shopInventoryId),
                UNIQUE KEY uq_tbl_shop_inventory_item (guildId, userId, shopItemId),
                KEY idx_tbl_shop_inventory_user (guildId, userId)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        ShopInventoryLedgerService::ensureSchema();
        TransactionTraceService::ensureSchema();

        self::$schemaReady = true;
        self::seedDefaults();
    }

    public static function seedDefaults(): void
    {
        foreach (self::defaultUnits() as $unit) {
            Database::execute(
                'INSERT INTO tbl_shop_unit (unitCode, displayName, shortName, icon, isEnabled, sortOrder, metadataJson, updateDate)
                 VALUES (:unitCode, :displayName, :shortName, :icon, :isEnabled, :sortOrder, :metadataJson, :updateDate)
                 ON DUPLICATE KEY UPDATE unitCode = unitCode',
                [
                    'unitCode' => $unit['unitCode'],
                    'displayName' => $unit['displayName'],
                    'shortName' => $unit['shortName'],
                    'icon' => $unit['icon'],
                    'isEnabled' => (int) $unit['isEnabled'],
                    'sortOrder' => (int) $unit['sortOrder'],
                    'metadataJson' => json_encode([
                        'seed' => 'shop.default_unit',
                        'shopBuyTheme' => self::normalizeBuyThemeOverride([]),
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'updateDate' => date('Y-m-d H:i:s'),
                ]
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function units(bool $enabledOnly = false): array
    {
        self::ensureSchema();
        $where = $enabledOnly ? 'WHERE isEnabled = 1' : '';
        return array_map(
            static fn (array $unit): array => self::normalizeUnitRow($unit),
            Database::fetchAll(
                'SELECT unitCode, displayName, shortName, icon, isEnabled, sortOrder, metadataJson, updateDate
                 FROM tbl_shop_unit
                 ' . $where . '
                 ORDER BY sortOrder ASC, unitCode ASC'
            )
        );
    }

    /** @return array<string, array<string, mixed>> */
    public static function unitIndex(bool $enabledOnly = false): array
    {
        $index = [];
        foreach (self::units($enabledOnly) as $unit) {
            $index[(string) $unit['unitCode']] = $unit;
        }
        return $index;
    }

    /** @return array<int, array<string, mixed>> */
    public static function saveUnits(array $units): array
    {
        self::ensureSchema();
        $seen = [];
        foreach ($units as $index => $unit) {
            if (!is_array($unit)) {
                continue;
            }
            $code = self::normalizeUnitCode((string) ($unit['unitCode'] ?? $unit['code'] ?? ''));
            if ($code === '' || isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;
            $displayName = trim((string) ($unit['displayName'] ?? $unit['label'] ?? $code));
            $shortName = trim((string) ($unit['shortName'] ?? $unit['abbr'] ?? $displayName));
            $metadata = self::normalizeUnitMetadata($unit);
            Database::execute(
                'INSERT INTO tbl_shop_unit (unitCode, displayName, shortName, icon, isEnabled, sortOrder, metadataJson, updateDate)
                 VALUES (:unitCode, :displayName, :shortName, :icon, :isEnabled, :sortOrder, :metadataJson, :updateDate)
                 ON DUPLICATE KEY UPDATE
                    displayName = VALUES(displayName),
                    shortName = VALUES(shortName),
                    icon = VALUES(icon),
                    isEnabled = VALUES(isEnabled),
                    sortOrder = VALUES(sortOrder),
                    metadataJson = VALUES(metadataJson),
                    updateDate = VALUES(updateDate)',
                [
                    'unitCode' => $code,
                    'displayName' => substr($displayName !== '' ? $displayName : $code, 0, 120),
                    'shortName' => substr($shortName !== '' ? $shortName : $code, 0, 40),
                    'icon' => substr(trim((string) ($unit['icon'] ?? '')), 0, 190) ?: null,
                    'isEnabled' => !empty($unit['isEnabled']) || !empty($unit['enabled']) ? 1 : 0,
                    'sortOrder' => (int) ($unit['sortOrder'] ?? (($index + 1) * 10)),
                    'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'updateDate' => date('Y-m-d H:i:s'),
                ]
            );
        }

        return self::units(false);
    }

    public static function ensureWallet(string $guildId, string $userId, string $unitCode): int
    {
        self::ensureSchema();
        $unitCode = self::normalizeUnitCode($unitCode);
        if ($guildId === '' || $userId === '' || $unitCode === '') {
            return 0;
        }

        Database::execute(
            'INSERT INTO tbl_shop_wallet (guildId, userId, unitCode, balanceAmount, updateDate)
             VALUES (:guildId, :userId, :unitCode, 0, :updateDate)
             ON DUPLICATE KEY UPDATE updateDate = updateDate',
            ['guildId' => $guildId, 'userId' => $userId, 'unitCode' => $unitCode, 'updateDate' => date('Y-m-d H:i:s')]
        );

        $wallet = Database::fetch(
            'SELECT shopWalletId FROM tbl_shop_wallet WHERE guildId = :guildId AND userId = :userId AND unitCode = :unitCode',
            ['guildId' => $guildId, 'userId' => $userId, 'unitCode' => $unitCode]
        );

        return (int) ($wallet['shopWalletId'] ?? 0);
    }

    /** @return array<string, int> */
    public static function walletBalances(string $guildId, string $userId, bool $withLegacyBackfill = false): array
    {
        self::ensureSchema();

        foreach (self::units(false) as $unit) {
            self::ensureWallet($guildId, $userId, (string) $unit['unitCode']);
        }

        $rows = Database::fetchAll(
            'SELECT unitCode, balanceAmount
             FROM tbl_shop_wallet
             WHERE guildId = :guildId AND userId = :userId',
            ['guildId' => $guildId, 'userId' => $userId]
        );

        $balances = [];
        foreach ($rows as $row) {
            $balances[(string) $row['unitCode']] = (int) $row['balanceAmount'];
        }
        return $balances;
    }

    /** @return array<string, array<string, int>> */
    public static function walletBalancesForUsers(string $guildId, array $userIds, bool $enabledOnly = true): array
    {
        self::ensureSchema();
        $guildId = trim($guildId);
        $userIds = array_values(array_filter(array_map(static fn (mixed $value): string => trim((string) $value), $userIds)));
        if ($guildId === '' || $userIds === []) {
            return [];
        }

        $unitCodes = array_map(
            static fn (array $unit): string => (string) ($unit['unitCode'] ?? ''),
            self::units($enabledOnly)
        );
        $unitCodes = array_values(array_filter($unitCodes, static fn (string $code): bool => $code !== ''));

        $balances = [];
        foreach ($userIds as $userId) {
            $balances[$userId] = [];
            foreach ($unitCodes as $unitCode) {
                $balances[$userId][$unitCode] = 0;
            }
        }

        $params = ['guildId' => $guildId];
        $placeholders = [];
        foreach ($userIds as $index => $userId) {
            $paramKey = 'userId' . $index;
            $placeholders[] = ':' . $paramKey;
            $params[$paramKey] = $userId;
        }

        $rows = Database::fetchAll(
            'SELECT userId, unitCode, balanceAmount
             FROM tbl_shop_wallet
             WHERE guildId = :guildId
               AND userId IN (' . implode(',', $placeholders) . ')',
            $params
        );

        foreach ($rows as $row) {
            $userId = (string) ($row['userId'] ?? '');
            $unitCode = (string) ($row['unitCode'] ?? '');
            if ($userId === '' || $unitCode === '') {
                continue;
            }
            if (!isset($balances[$userId])) {
                $balances[$userId] = [];
            }
            $balances[$userId][$unitCode] = (int) ($row['balanceAmount'] ?? 0);
        }

        return $balances;
    }

    public static function appendWalletLedger(array $entry): int
    {
        self::ensureSchema();

        $shopWalletId = max(0, (int) ($entry['shopWalletId'] ?? 0));
        $unitCode = self::normalizeUnitCode((string) ($entry['unitCode'] ?? ''));
        $amountDelta = (int) ($entry['amountDelta'] ?? 0);
        $ledgerType = trim((string) ($entry['ledgerType'] ?? ''));
        if ($shopWalletId <= 0 || $unitCode === '' || $amountDelta === 0 || $ledgerType === '') {
            return 0;
        }

        $metadata = $entry['metadata'] ?? ($entry['metadataJson'] ?? null);
        if (!is_string($metadata)) {
            $metadata = json_encode(
                is_array($metadata) ? $metadata : [],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        }

        return Database::insert('tbl_shop_wallet_ledger', [
            'shopWalletId' => $shopWalletId,
            'unitCode' => $unitCode,
            'amountDelta' => $amountDelta,
            'ledgerType' => $ledgerType,
            'sourceType' => self::nullableString($entry['sourceType'] ?? null),
            'sourceId' => self::nullableString($entry['sourceId'] ?? null),
            'transactionGroupId' => self::nullableString($entry['transactionGroupId'] ?? null),
            'actorUserId' => self::nullableString($entry['actorUserId'] ?? null),
            'targetUserId' => self::nullableString($entry['targetUserId'] ?? null),
            'walletBalanceBefore' => self::nullableInt($entry['walletBalanceBefore'] ?? null),
            'walletBalanceAfter' => self::nullableInt($entry['walletBalanceAfter'] ?? null),
            'metadataJson' => $metadata,
            'createDate' => self::timestamp($entry['createDate'] ?? null),
        ]);
    }

    /** @return array{walletId:int,walletLedgerId:int,balanceAmount:int,amountDelta:int,unitCode:string,walletBalanceBefore:int,walletBalanceAfter:int,transactionGroupId:string} */
    public static function adjustWalletBalance(
        string $guildId,
        string $userId,
        string $unitCode,
        int $amountDelta,
        string $ledgerType,
        ?string $sourceType = null,
        ?string $sourceId = null,
        array $metadata = [],
        array $context = []
    ): array {
        self::ensureSchema();

        $unitCode = self::normalizeUnitCode($unitCode);
        if ($guildId === '' || $userId === '' || $unitCode === '') {
            throw new InvalidArgumentException('Wallet target is required.');
        }

        $pdo = Database::pdo();
        $walletId = 0;
        $walletLedgerId = 0;
        $currentBalance = 0;
        $nextBalance = 0;
        $allowNegative = !empty($context['allowNegative']);

        $ownsTransaction = !$pdo->inTransaction();

        try {
            if ($ownsTransaction) {
                $pdo->beginTransaction();
            }

            $walletId = self::ensureWallet($guildId, $userId, $unitCode);
            if ($walletId <= 0) {
                throw new RuntimeException('WALLET_UNAVAILABLE');
            }

            $row = Database::fetch(
                'SELECT balanceAmount
                   FROM tbl_shop_wallet
                  WHERE shopWalletId = :shopWalletId
                  FOR UPDATE',
                ['shopWalletId' => $walletId]
            );

            $currentBalance = (int) ($row['balanceAmount'] ?? 0);
            $nextBalance = $currentBalance + $amountDelta;
            if ($nextBalance < 0 && !$allowNegative) {
                throw new RuntimeException('INSUFFICIENT_BALANCE');
            }

            if ($amountDelta !== 0) {
                $updateDate = self::timestamp($context['createDate'] ?? null);
                Database::execute(
                    'UPDATE tbl_shop_wallet
                        SET balanceAmount = :balanceAmount,
                            updateDate = :updateDate
                      WHERE shopWalletId = :shopWalletId',
                    [
                        'shopWalletId' => $walletId,
                        'balanceAmount' => $nextBalance,
                        'updateDate' => $updateDate,
                    ]
                );

                $walletLedgerId = self::appendWalletLedger([
                    'shopWalletId' => $walletId,
                    'unitCode' => $unitCode,
                    'amountDelta' => $amountDelta,
                    'ledgerType' => $ledgerType,
                    'sourceType' => $sourceType,
                    'sourceId' => $sourceId,
                    'transactionGroupId' => $context['transactionGroupId'] ?? null,
                    'actorUserId' => $context['actorUserId'] ?? null,
                    'targetUserId' => $context['targetUserId'] ?? null,
                    'walletBalanceBefore' => $currentBalance,
                    'walletBalanceAfter' => $nextBalance,
                    'createDate' => $updateDate,
                    'metadata' => $metadata,
                ]);
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }

        return [
            'walletId' => $walletId,
            'walletLedgerId' => $walletLedgerId,
            'balanceAmount' => $nextBalance,
            'amountDelta' => $amountDelta,
            'unitCode' => $unitCode,
            'walletBalanceBefore' => $currentBalance,
            'walletBalanceAfter' => $nextBalance,
            'transactionGroupId' => trim((string) ($context['transactionGroupId'] ?? '')),
        ];
    }

    public static function backfillLegacyBalances(string $guildId, ?string $userId = null): void
    {
        self::ensureSchema();
        unset($guildId, $userId);
    }

    private static function normalizeUnitCode(string $code): string
    {
        $code = strtolower(trim($code));
        $code = preg_replace('/[^a-z0-9_]+/', '_', $code) ?? '';
        return trim($code, '_');
    }

    /** @return array<string, mixed> */
    private static function normalizeUnitMetadata(array $unit): array
    {
        $metadata = [];
        if (isset($unit['metadata']) && is_array($unit['metadata'])) {
            $metadata = $unit['metadata'];
        } elseif (is_array($unit['metadataJson'] ?? null)) {
            $metadata = $unit['metadataJson'];
        } else {
            $decoded = json_decode((string) ($unit['metadataJson'] ?? ''), true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $metadata['updatedFrom'] = 'shop_setting';
        $metadata['shopBuyTheme'] = self::normalizeBuyThemeOverride($unit['shopBuyTheme'] ?? ($metadata['shopBuyTheme'] ?? []));
        return $metadata;
    }

    private static function normalizeUnitRow(array $unit): array
    {
        $metadata = self::unitMetadata($unit);
        return [
            'unitCode' => (string) ($unit['unitCode'] ?? ''),
            'code' => (string) ($unit['unitCode'] ?? ''),
            'displayName' => (string) ($unit['displayName'] ?? ''),
            'label' => (string) ($unit['displayName'] ?? ''),
            'shortName' => (string) ($unit['shortName'] ?? ''),
            'icon' => (string) ($unit['icon'] ?? ''),
            'isEnabled' => (int) ($unit['isEnabled'] ?? 0),
            'enabled' => !empty($unit['isEnabled']),
            'sortOrder' => (int) ($unit['sortOrder'] ?? 0),
            'metadata' => $metadata,
            'shopBuyTheme' => $metadata['shopBuyTheme'] ?? self::normalizeBuyThemeOverride([]),
            'metadataJson' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updateDate' => $unit['updateDate'] ?? null,
        ];
    }

    private static function normalizeHoldSeconds(mixed $value, float $fallback): float
    {
        if (!is_numeric($value)) {
            return $fallback;
        }
        $numeric = (float) $value;
        if ($numeric < 0.2 || $numeric > 10) {
            return $fallback;
        }
        return round($numeric, 2);
    }

    private static function normalizeOptionalHoldSeconds(mixed $value): float|string
    {
        if ($value === null) {
            return '';
        }
        $text = trim((string) $value);
        if ($text === '' || !is_numeric($text)) {
            return '';
        }
        $numeric = (float) $text;
        if ($numeric < 0.2 || $numeric > 10) {
            return '';
        }
        return round($numeric, 2);
    }

    private static function normalizeHexColor(mixed $value, string $fallback): string
    {
        $normalized = self::normalizeOptionalHexColor($value);
        return $normalized !== '' ? $normalized : $fallback;
    }

    private static function normalizeOptionalHexColor(mixed $value): string
    {
        $text = strtolower(trim((string) $value));
        if ($text === '') {
            return '';
        }
        if (!preg_match('/^#?[0-9a-f]{6}$/', $text)) {
            return '';
        }
        if ($text[0] !== '#') {
            $text = '#' . $text;
        }
        return $text;
    }

    private static function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : null;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private static function timestamp(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : date('Y-m-d H:i:s');
    }
}
