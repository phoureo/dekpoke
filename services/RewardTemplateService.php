<?php

declare(strict_types=1);

final class RewardTemplateService
{
    private const SETTING_KEY = 'reward.templates';

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        $row = Database::fetch(
            'SELECT settingValueJson
               FROM tbl_setting
              WHERE settingKey = :settingKey
              LIMIT 1',
            ['settingKey' => self::SETTING_KEY]
        );
        $decoded = $row ? json_decode((string) ($row['settingValueJson'] ?? '[]'), true) : [];
        return self::normalizeTemplates(is_array($decoded) ? $decoded : []);
    }

    /** @return array<string, mixed>|null */
    public static function find(string $templateId): ?array
    {
        $templateId = trim($templateId);
        if ($templateId === '') {
            return null;
        }

        foreach (self::all() as $template) {
            if ((string) ($template['id'] ?? '') === $templateId) {
                return $template;
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function saveTemplates(array $templates): array
    {
        $normalized = self::normalizeTemplates($templates);
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

    /** @return array<string, mixed> */
    public static function resolveTemplate(string $templateId, ?int $seed = null): array
    {
        $template = self::find($templateId);
        if (!$template) {
            throw new RuntimeException('REWARD_TEMPLATE_NOT_FOUND');
        }

        $mode = (string) ($template['mode'] ?? 'fixed');
        $entries = is_array($template['entries'] ?? null) ? $template['entries'] : [];
        $selectedEntries = $mode === 'random'
            ? array_filter([self::pickWeightedEntry($entries, $seed)])
            : $entries;

        $bundle = [
            'templateId' => (string) ($template['id'] ?? ''),
            'templateName' => (string) ($template['name'] ?? ''),
            'unitRewards' => [],
            'itemRewards' => [],
            'lootBoxRewards' => [],
            'resolvedEntries' => [],
        ];

        foreach ($selectedEntries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $type = (string) ($entry['type'] ?? 'unit');
            $amount = max(1, (int) ($entry['amount'] ?? 1));
            $code = trim((string) ($entry['code'] ?? ''));
            $bundle['resolvedEntries'][] = $entry;

            if ($type === 'item') {
                $bundle['itemRewards'][] = [
                    'itemCode' => $code,
                    'amount' => $amount,
                    'itemData' => is_array($entry['itemData'] ?? null) ? $entry['itemData'] : [],
                ];
                continue;
            }

            if ($type === 'loot_box') {
                $bundle['lootBoxRewards'][] = [
                    'itemCode' => $code,
                    'amount' => $amount,
                    'rewardTemplateId' => trim((string) ($entry['rewardTemplateId'] ?? '')),
                    'itemData' => is_array($entry['itemData'] ?? null) ? $entry['itemData'] : [],
                ];
                continue;
            }

            if ($code !== '') {
                $bundle['unitRewards'][$code] = max(0, (int) ($bundle['unitRewards'][$code] ?? 0)) + $amount;
            }
        }

        return $bundle;
    }

    /** @return array<string, mixed> */
    public static function grantTemplateRewards(
        string $guildId,
        string $userId,
        string $templateId,
        ?string $sourceType = null,
        ?string $sourceId = null,
        array $context = []
    ): array {
        $bundle = self::resolveTemplate($templateId, isset($context['seed']) ? (int) $context['seed'] : null);
        return self::grantRewardBundle($guildId, $userId, $bundle, $sourceType, $sourceId, $context);
    }

    /** @return array<string, mixed> */
    public static function grantRewardBundle(
        string $guildId,
        string $userId,
        array $bundle,
        ?string $sourceType = null,
        ?string $sourceId = null,
        array $context = []
    ): array {
        ItemCatalogService::ensureSchema();

        $guildId = trim($guildId);
        $userId = trim($userId);
        if ($guildId === '' || $userId === '') {
            throw new InvalidArgumentException('REWARD_BUNDLE_TARGET_REQUIRED');
        }

        $transactionGroupId = trim((string) ($context['transactionGroupId'] ?? ''));
        $createDate = trim((string) ($context['createDate'] ?? '')) ?: date('Y-m-d H:i:s');
        $walletRows = [];
        $itemRows = [];
        $lootBoxRows = [];

        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            foreach ((array) ($bundle['unitRewards'] ?? []) as $unitCode => $amount) {
                $amount = max(0, (int) $amount);
                $unitCode = trim((string) $unitCode);
                if ($unitCode === '' || $amount <= 0) {
                    continue;
                }
                $walletRows[] = ShopUnitService::adjustWalletBalance(
                    $guildId,
                    $userId,
                    $unitCode,
                    $amount,
                    'credit',
                    $sourceType,
                    $sourceId,
                    [
                        'rewardTemplateId' => (string) ($bundle['templateId'] ?? ''),
                        'rewardTemplateName' => (string) ($bundle['templateName'] ?? ''),
                        'resolvedEntries' => array_values($bundle['resolvedEntries'] ?? []),
                    ],
                    [
                        'transactionGroupId' => $transactionGroupId,
                        'actorUserId' => $context['actorUserId'] ?? null,
                        'targetUserId' => $userId,
                        'createDate' => $createDate,
                    ]
                );
            }

            foreach ((array) ($bundle['itemRewards'] ?? []) as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $itemCode = trim((string) ($entry['itemCode'] ?? ''));
                if ($itemCode === '') {
                    continue;
                }
                $itemData = is_array($entry['itemData'] ?? null) ? $entry['itemData'] : [];
                $itemRows[] = ItemCatalogService::grantItem(
                    $guildId,
                    $userId,
                    $itemCode,
                    max(1, (int) ($entry['amount'] ?? 1)),
                    $itemData,
                    $sourceType,
                    $sourceId,
                    [
                        'rewardTemplateId' => (string) ($bundle['templateId'] ?? ''),
                        'rewardTemplateName' => (string) ($bundle['templateName'] ?? ''),
                        'resolvedEntry' => $entry,
                    ],
                    [
                        'transactionGroupId' => $transactionGroupId,
                        'actorUserId' => $context['actorUserId'] ?? null,
                        'targetUserId' => $userId,
                        'createDate' => $createDate,
                        'ledgerType' => 'credit',
                    ]
                );
            }

            foreach ((array) ($bundle['lootBoxRewards'] ?? []) as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $itemCode = trim((string) ($entry['itemCode'] ?? ''));
                if ($itemCode === '') {
                    continue;
                }
                $itemData = is_array($entry['itemData'] ?? null) ? $entry['itemData'] : [];
                $itemData['effectType'] = 'loot_box';
                $effectPayload = is_array($itemData['effectPayload'] ?? null) ? $itemData['effectPayload'] : [];
                if (!isset($effectPayload['rewardTemplateId'])) {
                    $effectPayload['rewardTemplateId'] = (string) ($entry['rewardTemplateId'] ?? $itemCode);
                }
                $itemData['effectPayload'] = $effectPayload;
                $lootBoxRows[] = ItemCatalogService::grantItem(
                    $guildId,
                    $userId,
                    $itemCode,
                    max(1, (int) ($entry['amount'] ?? 1)),
                    $itemData,
                    $sourceType,
                    $sourceId,
                    [
                        'rewardTemplateId' => (string) ($bundle['templateId'] ?? ''),
                        'rewardTemplateName' => (string) ($bundle['templateName'] ?? ''),
                        'resolvedEntry' => $entry,
                    ],
                    [
                        'transactionGroupId' => $transactionGroupId,
                        'actorUserId' => $context['actorUserId'] ?? null,
                        'targetUserId' => $userId,
                        'createDate' => $createDate,
                        'ledgerType' => 'credit',
                    ]
                );
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        return [
            'templateId' => (string) ($bundle['templateId'] ?? ''),
            'templateName' => (string) ($bundle['templateName'] ?? ''),
            'walletRows' => $walletRows,
            'itemRows' => $itemRows,
            'lootBoxRows' => $lootBoxRows,
            'resolvedEntries' => array_values($bundle['resolvedEntries'] ?? []),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function normalizeTemplates(array $templates): array
    {
        $out = [];
        foreach ($templates as $index => $template) {
            if (!is_array($template)) {
                continue;
            }
            $id = trim((string) ($template['id'] ?? ''));
            if ($id === '') {
                $id = 'reward_template_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            }
            $mode = strtolower(trim((string) ($template['mode'] ?? 'fixed')));
            if (!in_array($mode, ['fixed', 'random'], true)) {
                $mode = 'fixed';
            }
            $entries = [];
            foreach ((array) ($template['entries'] ?? []) as $entryIndex => $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $type = strtolower(trim((string) ($entry['type'] ?? 'unit')));
                if (!in_array($type, ['unit', 'item', 'loot_box'], true)) {
                    $type = 'unit';
                }
                $entries[] = [
                    'id' => trim((string) ($entry['id'] ?? '')) ?: ($id . '_entry_' . ($entryIndex + 1)),
                    'type' => $type,
                    'code' => trim((string) ($entry['code'] ?? '')),
                    'amount' => max(1, (int) ($entry['amount'] ?? 1)),
                    'weight' => max(1, (int) ($entry['weight'] ?? 1)),
                    'label' => trim((string) ($entry['label'] ?? '')),
                    'rewardTemplateId' => trim((string) ($entry['rewardTemplateId'] ?? '')),
                    'itemData' => is_array($entry['itemData'] ?? null) ? $entry['itemData'] : [],
                ];
            }
            $out[] = [
                'id' => $id,
                'name' => trim((string) ($template['name'] ?? $id)) ?: $id,
                'mode' => $mode,
                'iconTemplateId' => trim((string) ($template['iconTemplateId'] ?? '')),
                'entries' => $entries,
                'meta' => is_array($template['meta'] ?? null) ? $template['meta'] : [],
            ];
        }
        return $out;
    }

    /** @return array<string, mixed>|null */
    private static function pickWeightedEntry(array $entries, ?int $seed = null): ?array
    {
        $weighted = array_values(array_filter($entries, static fn (mixed $entry): bool => is_array($entry)));
        if ($weighted === []) {
            return null;
        }

        $totalWeight = 0;
        foreach ($weighted as $entry) {
            $totalWeight += max(1, (int) ($entry['weight'] ?? 1));
        }
        if ($totalWeight <= 0) {
            return $weighted[0];
        }

        $ticket = $seed !== null
            ? abs(crc32((string) $seed)) % $totalWeight
            : random_int(0, $totalWeight - 1);

        $cursor = 0;
        foreach ($weighted as $entry) {
            $cursor += max(1, (int) ($entry['weight'] ?? 1));
            if ($ticket < $cursor) {
                return $entry;
            }
        }

        return end($weighted) ?: null;
    }
}
