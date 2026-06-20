<?php

declare(strict_types=1);

final class GachaCampaignBannerService
{
    private const RULE_CODE = 'gacha_campaign_banner';
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        Database::execute(
            'CREATE TABLE IF NOT EXISTS tbl_gacha_campaign_banner_claim (
                gachaCampaignBannerClaimId bigint unsigned NOT NULL AUTO_INCREMENT,
                guildId varchar(32) NOT NULL,
                userId varchar(32) NOT NULL,
                bannerId varchar(120) NOT NULL,
                rewardJson longtext DEFAULT NULL,
                status varchar(24) NOT NULL DEFAULT "claimed",
                createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updateDate datetime DEFAULT NULL,
                PRIMARY KEY (gachaCampaignBannerClaimId),
                UNIQUE KEY uq_gacha_campaign_banner_claim (guildId, userId, bannerId),
                KEY idx_gacha_campaign_banner_user (guildId, userId, createDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    public static function defaultConfig(): array
    {
        return [
            'enabled' => true,
            'title' => 'Campaign Board',
            'subtitle' => 'ดูข่าวกิจกรรมและรับของจากแบนเนอร์ที่ตั้งค่าไว้',
            'banners' => [
                [
                    'id' => 'welcome-campaign',
                    'enabled' => true,
                    'title' => 'Welcome Campaign',
                    'subtitle' => 'พื้นที่ประกาศกิจกรรมหน้ากาชา',
                    'body' => 'ตั้งค่าแบนเนอร์ รูปภาพ และรางวัลได้จาก Campaign Setting',
                    'image' => '',
                    'badge' => 'NEW',
                    'ctaLabel' => 'ดูแล้ว',
                    'startsAt' => '',
                    'endsAt' => '',
                    'sortOrder' => 10,
                    'reward' => self::normalizeReward([]),
                ],
            ],
        ];
    }

    public static function normalizeConfig(mixed $config): array
    {
        $defaults = self::defaultConfig();
        $config = is_array($config) ? $config : [];
        $rawBanners = is_array($config['banners'] ?? null) ? $config['banners'] : ($defaults['banners'] ?? []);
        $banners = [];
        foreach ($rawBanners as $index => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $banner = self::normalizeBanner($entry, $index);
            if ($banner['id'] !== '') {
                $banners[$banner['id']] = $banner;
            }
        }

        uasort($banners, static function (array $a, array $b): int {
            return ($a['sortOrder'] <=> $b['sortOrder']) ?: strcmp($a['id'], $b['id']);
        });

        return [
            'enabled' => array_key_exists('enabled', $config) ? (bool) $config['enabled'] : (bool) $defaults['enabled'],
            'title' => trim((string) ($config['title'] ?? $defaults['title'])) ?: $defaults['title'],
            'subtitle' => trim((string) ($config['subtitle'] ?? $defaults['subtitle'])) ?: $defaults['subtitle'],
            'banners' => array_values($banners),
        ];
    }

    public static function config(?array $gachaConfig = null): array
    {
        $gachaConfig ??= GachaConfigService::load();
        return self::normalizeConfig($gachaConfig['settings']['campaignBanners'] ?? []);
    }

    public static function status(string $guildId, string $userId = '', ?array $gachaConfig = null): array
    {
        self::ensureSchema();
        $config = self::config($gachaConfig);
        $claimed = $userId !== '' ? self::claimedMap($guildId, $userId) : [];
        $activeBanners = [];

        foreach ($config['banners'] as $banner) {
            if (!self::isBannerActive($banner)) {
                continue;
            }
            $reward = is_array($banner['reward'] ?? null) ? $banner['reward'] : self::normalizeReward([]);
            $hasReward = self::rewardHasValue($reward);
            $bannerId = (string) ($banner['id'] ?? '');
            $activeBanners[] = $banner + [
                'reward' => $reward,
                'hasReward' => $hasReward,
                'claimed' => isset($claimed[$bannerId]),
                'canClaim' => $config['enabled'] && $userId !== '' && $hasReward && !isset($claimed[$bannerId]),
            ];
        }

        return [
            'ok' => true,
            'requiresLogin' => $userId === '',
            'config' => [
                'enabled' => $config['enabled'],
                'title' => $config['title'],
                'subtitle' => $config['subtitle'],
            ],
            'banners' => $activeBanners,
            'unclaimedCount' => count(array_filter($activeBanners, static fn (array $banner): bool => !empty($banner['hasReward']) && empty($banner['claimed']))),
            'activeCount' => count($activeBanners),
        ];
    }

    public static function claim(string $guildId, string $userId, string $bannerId, ?array $gachaConfig = null): array
    {
        self::ensureSchema();
        $guildId = trim($guildId);
        $userId = preg_replace('/[^0-9]/', '', $userId) ?? '';
        $bannerId = self::normalizeId($bannerId, 'banner');
        if ($guildId === '' || $userId === '') {
            throw new RuntimeException('AUTH_REQUIRED');
        }

        $config = self::config($gachaConfig);
        if (!$config['enabled']) {
            throw new RuntimeException('CAMPAIGN_BANNER_DISABLED');
        }

        $banner = self::findBanner($config['banners'], $bannerId);
        if (!$banner || !self::isBannerActive($banner)) {
            throw new RuntimeException('CAMPAIGN_BANNER_NOT_FOUND');
        }

        $reward = self::normalizeReward($banner['reward'] ?? []);
        if (!self::rewardHasValue($reward)) {
            throw new RuntimeException('CAMPAIGN_BANNER_NO_REWARD');
        }

        $claimId = 0;
        try {
            $claimId = Database::insert('tbl_gacha_campaign_banner_claim', [
                'guildId' => $guildId,
                'userId' => $userId,
                'bannerId' => $bannerId,
                'rewardJson' => json_encode($reward, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'status' => 'claimed',
                'createDate' => date('Y-m-d H:i:s'),
                'updateDate' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            throw new RuntimeException('ALREADY_CLAIMED');
        }

        try {
            $granted = self::grantReward($guildId, $userId, $bannerId, $banner, $reward, $claimId);
        } catch (Throwable $exception) {
            Database::execute(
                'UPDATE tbl_gacha_campaign_banner_claim
                    SET status = "failed", updateDate = :updateDate
                  WHERE gachaCampaignBannerClaimId = :claimId',
                ['claimId' => $claimId, 'updateDate' => date('Y-m-d H:i:s')]
            );
            throw $exception;
        }

        $status = self::status($guildId, $userId, $gachaConfig);
        $balancesAfter = class_exists('ShopUnitService') ? ShopUnitService::walletBalances($guildId, $userId) : [];

        return $status + [
            'claimed' => [
                'bannerId' => $bannerId,
                'reward' => $reward,
                'granted' => $granted,
            ],
            'balancesAfter' => $balancesAfter,
        ];
    }

    private static function normalizeBanner(array $entry, int $index): array
    {
        $fallbackId = 'banner-' . ($index + 1);
        $id = self::normalizeId((string) ($entry['id'] ?? ''), $fallbackId);
        return [
            'id' => $id,
            'enabled' => array_key_exists('enabled', $entry) ? (bool) $entry['enabled'] : true,
            'title' => trim((string) ($entry['title'] ?? 'Campaign')),
            'subtitle' => trim((string) ($entry['subtitle'] ?? '')),
            'body' => trim((string) ($entry['body'] ?? '')),
            'image' => trim((string) ($entry['image'] ?? $entry['bannerImage'] ?? '')),
            'badge' => trim((string) ($entry['badge'] ?? 'NEW')),
            'ctaLabel' => trim((string) ($entry['ctaLabel'] ?? 'รับรางวัล')),
            'startsAt' => self::normalizeDateTime((string) ($entry['startsAt'] ?? '')),
            'endsAt' => self::normalizeDateTime((string) ($entry['endsAt'] ?? '')),
            'sortOrder' => max(0, min(999999, (int) ($entry['sortOrder'] ?? (($index + 1) * 10)))),
            'reward' => self::normalizeReward($entry['reward'] ?? $entry),
        ];
    }

    private static function normalizeReward(mixed $reward): array
    {
        $reward = is_array($reward) ? $reward : [];
        return [
            'coin' => max(0, (int) ($reward['coin'] ?? 0)),
            'gem' => max(0, (int) ($reward['gem'] ?? 0)),
            'ticket' => max(0, (int) ($reward['ticket'] ?? $reward['gachaTicket'] ?? 0)),
            'potion' => max(0, (int) ($reward['potion'] ?? 0)),
            'label' => trim((string) ($reward['label'] ?? '')),
        ];
    }

    private static function rewardHasValue(array $reward): bool
    {
        foreach (['coin', 'gem', 'ticket', 'potion'] as $unitCode) {
            if ((int) ($reward[$unitCode] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    private static function grantReward(string $guildId, string $userId, string $bannerId, array $banner, array $reward, int $claimId): array
    {
        $traceId = class_exists('TransactionTraceService') ? TransactionTraceService::generateTraceId('campaign_banner') : 'campaign_banner_' . bin2hex(random_bytes(6));
        $walletRows = [];
        foreach (['coin', 'gem', 'ticket', 'potion'] as $unitCode) {
            $amount = max(0, (int) ($reward[$unitCode] ?? 0));
            if ($amount <= 0) {
                continue;
            }
            $walletRows[] = ShopUnitService::adjustWalletBalance(
                $guildId,
                $userId,
                $unitCode,
                $amount,
                'credit',
                self::RULE_CODE,
                $bannerId,
                [
                    'rule' => self::RULE_CODE,
                    'claimId' => $claimId,
                    'bannerId' => $bannerId,
                    'bannerTitle' => (string) ($banner['title'] ?? ''),
                ],
                [
                    'transactionGroupId' => $traceId,
                    'targetUserId' => $userId,
                    'createDate' => date('Y-m-d H:i:s'),
                ]
            );
        }

        return [
            'walletRows' => $walletRows,
            'unitRewards' => array_filter([
                'coin' => max(0, (int) ($reward['coin'] ?? 0)),
                'gem' => max(0, (int) ($reward['gem'] ?? 0)),
                'ticket' => max(0, (int) ($reward['ticket'] ?? 0)),
                'potion' => max(0, (int) ($reward['potion'] ?? 0)),
            ]),
        ];
    }

    private static function claimedMap(string $guildId, string $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT bannerId
               FROM tbl_gacha_campaign_banner_claim
              WHERE guildId = :guildId
                AND userId = :userId
                AND status = "claimed"',
            ['guildId' => $guildId, 'userId' => $userId]
        );
        $claimed = [];
        foreach ($rows as $row) {
            $bannerId = (string) ($row['bannerId'] ?? '');
            if ($bannerId !== '') {
                $claimed[$bannerId] = true;
            }
        }
        return $claimed;
    }

    private static function findBanner(array $banners, string $bannerId): ?array
    {
        foreach ($banners as $banner) {
            if ((string) ($banner['id'] ?? '') === $bannerId) {
                return $banner;
            }
        }
        return null;
    }

    private static function isBannerActive(array $banner): bool
    {
        if (empty($banner['enabled'])) {
            return false;
        }
        $now = time();
        $startsAt = self::timestamp((string) ($banner['startsAt'] ?? ''));
        $endsAt = self::timestamp((string) ($banner['endsAt'] ?? ''));
        if ($startsAt > 0 && $now < $startsAt) {
            return false;
        }
        if ($endsAt > 0 && $now > $endsAt) {
            return false;
        }
        return true;
    }

    private static function normalizeId(string $value, string $fallback): string
    {
        $id = strtolower(trim($value));
        $id = preg_replace('/[^a-z0-9_-]+/', '-', $id) ?? '';
        $id = trim($id, '-_');
        if ($id === '') {
            $id = $fallback;
        }
        return substr($id, 0, 120);
    }

    private static function normalizeDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    private static function timestamp(string $value): int
    {
        $value = trim($value);
        return $value === '' ? 0 : (strtotime($value) ?: 0);
    }
}
