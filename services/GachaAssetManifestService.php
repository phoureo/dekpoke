<?php

declare(strict_types=1);

final class GachaAssetManifestService
{
    private const MANIFEST_VERSION = 1;
    private const LIVE_PATH = 'gacha/data/assets/asset-manifest.json';
    private const DRAFT_PATH = 'gacha/data/assets/drafts/asset-manifest.draft.json';
    private const DRAFT_META_PATH = 'gacha/data/assets/drafts/asset-manifest.draft.meta.json';
    private const VERSION_DIR = 'gacha/data/assets/versions';
    private const VERSION_MANIFEST_PATH = 'gacha/data/assets/versions/manifest.json';

    public static function pageDefinitions(): array
    {
        return [
            [
                'id' => 'gacha-index',
                'label' => 'Gacha Index',
                'entry' => 'index.html',
                'description' => 'หน้าเครื่องกาชาหลักและ preloader',
            ],
            [
                'id' => 'mileage-runtime',
                'label' => 'Mileage Runtime',
                'entry' => 'mileage.php',
                'description' => 'หน้ากระดาน mileage และ canvas preload',
            ],
        ];
    }

    public static function editorBootstrap(): array
    {
        $live = self::liveManifest();
        $draft = self::draftManifest();

        return [
            'liveManifestExists' => is_file(self::livePath()),
            'liveManifest' => $live,
            'draftManifest' => $draft,
            'workingManifest' => $draft ?: $live,
            'hasDraft' => $draft !== null,
            'versions' => self::versionManifest(),
            'pageDefinitions' => self::pageDefinitions(),
            'assets' => self::listAvailableAssets(),
        ];
    }

    public static function liveManifest(): array
    {
        $path = self::livePath();
        if (!is_file($path)) {
            return self::defaultManifest();
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return self::defaultManifest();
        }

        return self::normalizeManifest($decoded, self::defaultManifest());
    }

    public static function draftManifest(): ?array
    {
        $path = self::draftPath();
        if (!is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return null;
        }

        return self::normalizeManifest($decoded, self::liveManifest());
    }

    public static function saveDraft(array $payload): array
    {
        $manifest = self::normalizeManifest($payload, self::liveManifest());
        $manifest['meta']['updatedAt'] = date(DateTimeInterface::ATOM);
        self::writeJsonFile(self::draftPath(), $manifest, 'ASSET_MANIFEST_DRAFT_SAVE_FAILED');
        self::writeDraftMeta([
            'savedAt' => date(DateTimeInterface::ATOM),
            'gameVersion' => $manifest['meta']['gameVersion'],
            'contentVersion' => $manifest['meta']['contentVersion'],
        ]);

        return $manifest;
    }

    public static function publishDraft(?array $payload = null): array
    {
        $draft = $payload !== null
            ? self::normalizeManifest($payload, self::liveManifest())
            : self::draftManifest();
        if (!$draft) {
            throw new RuntimeException('ASSET_MANIFEST_DRAFT_NOT_FOUND');
        }

        $live = self::liveManifest();
        $snapshot = self::snapshotVersion($live, [
            'source' => 'publish',
            'publishedAt' => date(DateTimeInterface::ATOM),
        ]);

        $draft['meta']['updatedAt'] = date(DateTimeInterface::ATOM);
        self::writeJsonFile(self::livePath(), $draft, 'ASSET_MANIFEST_LIVE_SAVE_FAILED');
        self::writeDraftMeta([
            'savedAt' => date(DateTimeInterface::ATOM),
            'publishedAt' => date(DateTimeInterface::ATOM),
            'publishedVersionBefore' => $snapshot['id'] ?? '',
            'gameVersion' => $draft['meta']['gameVersion'],
            'contentVersion' => $draft['meta']['contentVersion'],
        ]);

        return [
            'manifest' => $draft,
            'snapshot' => $snapshot,
            'versions' => self::versionManifest(),
        ];
    }

    public static function rollbackVersion(string $versionId, bool $publish = false): array
    {
        $normalizedVersionId = self::normalizeVersionId($versionId);
        $path = self::versionPath($normalizedVersionId);
        if (!is_file($path)) {
            throw new RuntimeException('ASSET_MANIFEST_VERSION_NOT_FOUND');
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        $manifest = self::normalizeManifest(is_array($decoded) ? $decoded : [], self::liveManifest());
        $manifest['meta']['updatedAt'] = date(DateTimeInterface::ATOM);

        if ($publish) {
            self::snapshotVersion(self::liveManifest(), [
                'source' => 'rollback',
                'rollbackFrom' => $normalizedVersionId,
                'publishedAt' => date(DateTimeInterface::ATOM),
            ]);
            self::writeJsonFile(self::livePath(), $manifest, 'ASSET_MANIFEST_LIVE_SAVE_FAILED');
        }

        self::writeJsonFile(self::draftPath(), $manifest, 'ASSET_MANIFEST_DRAFT_SAVE_FAILED');
        self::writeDraftMeta([
            'savedAt' => date(DateTimeInterface::ATOM),
            'rollbackFrom' => $normalizedVersionId,
            'publishedAt' => $publish ? date(DateTimeInterface::ATOM) : null,
            'gameVersion' => $manifest['meta']['gameVersion'],
            'contentVersion' => $manifest['meta']['contentVersion'],
        ]);

        return [
            'manifest' => $manifest,
            'published' => $publish,
            'versions' => self::versionManifest(),
        ];
    }

    public static function versionManifest(): array
    {
        $path = self::versionManifestPath();
        if (!is_file($path)) {
            return [
                'kind' => 'asset-manifest',
                'updatedAt' => '',
                'versions' => [],
            ];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [
                'kind' => 'asset-manifest',
                'updatedAt' => '',
                'versions' => [],
            ];
        }

        $decoded['versions'] = array_values(array_filter(
            is_array($decoded['versions'] ?? null) ? $decoded['versions'] : [],
            static fn (mixed $item): bool => is_array($item) && trim((string) ($item['id'] ?? '')) !== ''
        ));

        return $decoded + [
            'kind' => 'asset-manifest',
            'updatedAt' => '',
        ];
    }

    public static function listAvailableAssets(): array
    {
        $roots = [
            'images/uploads/sprites',
            'images/uploads/mileage',
            'images/mileage',
            'images',
        ];
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
        $spriteManifestPath = Bootstrap::rootPath('gacha/images/uploads/sprites/manifest.json');
        $spriteManifest = [];
        if (is_file($spriteManifestPath)) {
            $decoded = json_decode((string) file_get_contents($spriteManifestPath), true);
            $spriteManifest = is_array($decoded['assets'] ?? null) ? $decoded['assets'] : [];
        }

        $assets = [];
        $seenPaths = [];
        foreach ($roots as $root) {
            $absolute = Bootstrap::rootPath('gacha/' . $root);
            if (!is_dir($absolute)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                    continue;
                }

                $extension = strtolower($fileInfo->getExtension());
                if (!in_array($extension, $allowedExtensions, true)) {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($fileInfo->getPathname(), strlen(Bootstrap::rootPath('gacha')) + 1));
                if ($relative === '' || isset($seenPaths[$relative])) {
                    continue;
                }
                $seenPaths[$relative] = true;

                $size = @getimagesize($fileInfo->getPathname()) ?: [0, 0, 'mime' => ''];
                $assets[] = [
                    'path' => $relative,
                    'folder' => $root,
                    'name' => $fileInfo->getFilename(),
                    'width' => max(0, (int) ($size[0] ?? 0)),
                    'height' => max(0, (int) ($size[1] ?? 0)),
                    'mime' => (string) ($size['mime'] ?? ''),
                    'updatedAt' => date(DateTimeInterface::ATOM, $fileInfo->getMTime()),
                    'spriteMeta' => is_array($spriteManifest[$relative] ?? null) ? $spriteManifest[$relative] : null,
                ];

                if (count($assets) >= 1500) {
                    break 2;
                }
            }
        }

        usort(
            $assets,
            static fn (array $a, array $b): int => strcmp((string) ($b['updatedAt'] ?? ''), (string) ($a['updatedAt'] ?? ''))
        );

        return [
            'roots' => $roots,
            'assets' => $assets,
        ];
    }

    private static function defaultManifest(): array
    {
        return self::normalizeManifest([
            'version' => self::MANIFEST_VERSION,
            'meta' => [
                'gameVersion' => 'legacy-runtime',
                'contentVersion' => 'legacy-assets',
                'updatedAt' => self::defaultUpdatedAt(),
                'notes' => 'Generated from current runtime defaults until the first publish.',
            ],
            'groups' => self::defaultGroups(),
            'assets' => array_merge(
                self::defaultGachaAssets(),
                self::defaultMileageAssets()
            ),
        ]);
    }

    private static function defaultGroups(): array
    {
        return [
            ['id' => 'shell', 'label' => 'Shell', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading shell', 'order' => 10],
            ['id' => 'room', 'label' => 'Room', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading room', 'order' => 20],
            ['id' => 'machine', 'label' => 'Machine', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading machine', 'order' => 30],
            ['id' => 'balls', 'label' => 'Balls', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading balls', 'order' => 40],
            ['id' => 'effects', 'label' => 'Effects', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading effects', 'order' => 50],
            ['id' => 'ui', 'label' => 'UI', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading UI', 'order' => 60],
            ['id' => 'reward-ball', 'label' => 'Reward Ball', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading reward ball', 'order' => 70],
            ['id' => 'rewards', 'label' => 'Rewards', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading rewards', 'order' => 80],
            ['id' => 'buttons', 'label' => 'Buttons', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading buttons', 'order' => 90],
            ['id' => 'counter', 'label' => 'Counter', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading counter', 'order' => 100],
            ['id' => 'switch', 'label' => 'Switch', 'pages' => ['gacha-index'], 'preloadLabel' => 'Loading switch', 'order' => 110],
            ['id' => 'mileage-board', 'label' => 'Mileage Board', 'pages' => ['mileage-runtime'], 'preloadLabel' => 'Loading mileage board', 'order' => 200],
            ['id' => 'mileage-sprites', 'label' => 'Mileage Sprites', 'pages' => ['mileage-runtime'], 'preloadLabel' => 'Loading mileage sprites', 'order' => 210],
            ['id' => 'mileage-icons', 'label' => 'Mileage Icons', 'pages' => ['mileage-runtime'], 'preloadLabel' => 'Loading mileage icons', 'order' => 220],
            ['id' => 'mileage-decor', 'label' => 'Mileage Decor', 'pages' => ['mileage-runtime'], 'preloadLabel' => 'Loading mileage decor', 'order' => 230],
        ];
    }

    private static function defaultGachaAssets(): array
    {
        return [
            self::asset('images/source_background_2-2.jpg', ['gacha-index'], 'shell', 'no-store', 'none', true, true, 'auto'),
            self::asset('images/source_background_2.jpg', ['gacha-index'], 'shell', 'no-store', 'none', true, true, 'auto'),
            self::asset('images/img_Asset 8-2.png', ['gacha-index'], 'machine', 'no-store'),
            self::asset('images/img_Asset 8-1.png', ['gacha-index'], 'machine'),
            self::asset('images/img_Asset 10.png', ['gacha-index'], 'machine'),
            self::asset('images/img_Asset 19.png', ['gacha-index'], 'room'),
            self::asset('images/img_Asset 20.png', ['gacha-index'], 'room'),
            self::asset('images/ball_1.png', ['gacha-index'], 'balls', 'no-store'),
            self::asset('images/ball_2.png', ['gacha-index'], 'balls', 'no-store'),
            self::asset('images/ball_3.png', ['gacha-index'], 'balls', 'no-store'),
            self::asset('images/ball_4.png', ['gacha-index'], 'balls', 'no-store'),
            self::asset('images/ball_5.png', ['gacha-index'], 'balls', 'no-store'),
            self::asset('images/img_Asset 4.png', ['gacha-index'], 'effects'),
            self::asset('images/img_Asset 7.png', ['gacha-index'], 'effects'),
            self::asset('images/img_Asset 13.png', ['gacha-index'], 'effects'),
            self::asset('images/img_Asset 14.png', ['gacha-index'], 'effects'),
            self::asset('images/ui_2.png', ['gacha-index'], 'ui'),
            self::asset('images/icon_1.png', ['gacha-index'], 'ui'),
            self::asset('images/icon_2.png', ['gacha-index'], 'ui'),
            self::asset('images/icon_3.png', ['gacha-index'], 'ui'),
            self::asset('images/icon_4.png', ['gacha-index'], 'ui'),
            self::asset('images/icon_5.png', ['gacha-index'], 'ui'),
            self::asset('images/ball_1-1.png', ['gacha-index'], 'reward-ball', 'no-store'),
            self::asset('images/ball_1-2.png', ['gacha-index'], 'reward-ball', 'no-store'),
            self::asset('images/ball_1-3.png', ['gacha-index'], 'reward-ball', 'no-store'),
            self::asset('images/ball_1-4.png', ['gacha-index'], 'reward-ball', 'no-store'),
            self::asset('images/item-1.png', ['gacha-index'], 'rewards'),
            self::asset('images/ui_5.png', ['gacha-index'], 'buttons'),
            self::asset('images/btn_3.png', ['gacha-index'], 'buttons'),
            self::asset('images/btn_4.png', ['gacha-index'], 'buttons'),
            self::asset('images/btn_4-1.png', ['gacha-index'], 'buttons'),
            self::asset('images/btn_5.png', ['gacha-index'], 'buttons'),
            self::asset('images/btn_5-1.png', ['gacha-index'], 'buttons'),
            self::asset('images/btn_6.png', ['gacha-index'], 'buttons'),
            self::asset('images/pic_numberic.png', ['gacha-index'], 'counter'),
            self::asset('images/pic_switch_on.png', ['gacha-index'], 'switch', 'no-store'),
            self::asset('images/pic_switch-off.png', ['gacha-index'], 'switch', 'no-store'),
        ];
    }

    private static function defaultMileageAssets(): array
    {
        $board = GachaMileageService::boardDefinition(GachaMileageService::DEFAULT_BOARD_CODE);
        $assets = [];
        $push = static function (array &$target, string $path, string $groupId): void {
            $key = self::canonicalAssetPath($path);
            if ($key === '') {
                return;
            }
            $target[$key] = self::asset($key, ['mileage-runtime'], $groupId);
        };

        if (is_array($board['image']['segments'] ?? null)) {
            foreach ($board['image']['segments'] as $segment) {
                if (is_array($segment)) {
                    $push($assets, (string) ($segment['src'] ?? ''), 'mileage-board');
                }
            }
        }

        $push($assets, (string) ($board['image']['source'] ?? ''), 'mileage-board');

        if (is_array($board['sprites'] ?? null)) {
            foreach ($board['sprites'] as $sprite) {
                if (is_array($sprite)) {
                    $push($assets, (string) ($sprite['src'] ?? ''), 'mileage-sprites');
                }
            }
        }

        if (is_array($board['iconTemplates'] ?? null)) {
            foreach ($board['iconTemplates'] as $template) {
                if (is_array($template)) {
                    $push($assets, (string) ($template['src'] ?? ''), 'mileage-icons');
                }
            }
        }

        $push($assets, 'images/pic_element_mileage_1.png', 'mileage-decor');

        return array_values($assets);
    }

    private static function asset(
        string $path,
        array $pages,
        string $groupId,
        string $cachePolicy = 'default',
        string $versionMode = 'none',
        bool $preload = true,
        bool $enabled = true,
        string $mimeType = '',
        string $customVersion = '',
        string $notes = ''
    ): array {
        return [
            'path' => self::canonicalAssetPath($path),
            'pages' => $pages,
            'groupId' => $groupId,
            'cachePolicy' => $cachePolicy,
            'versionMode' => $versionMode,
            'customVersion' => $customVersion,
            'mimeType' => $mimeType,
            'enabled' => $enabled,
            'preload' => $preload,
            'notes' => $notes,
        ];
    }

    private static function normalizeManifest(array $payload, ?array $baseManifest = null): array
    {
        $base = is_array($baseManifest) ? $baseManifest : [
            'meta' => [
                'gameVersion' => 'legacy-runtime',
                'contentVersion' => 'legacy-assets',
                'updatedAt' => self::defaultUpdatedAt(),
                'notes' => '',
            ],
            'groups' => self::defaultGroups(),
            'assets' => [],
        ];

        $groupSource = self::arrayValues($payload['groups'] ?? $base['groups'] ?? []);
        $groups = self::normalizeGroups($groupSource);
        $groupMap = [];
        foreach ($groups as $group) {
            $groupMap[$group['id']] = $group;
        }

        $assetSource = self::arrayValues($payload['assets'] ?? $base['assets'] ?? []);
        $assets = self::normalizeAssets($assetSource, $groupMap);

        $baseMeta = is_array($base['meta'] ?? null) ? $base['meta'] : [];
        $metaSource = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

        return [
            'version' => self::MANIFEST_VERSION,
            'meta' => [
                'gameVersion' => trim((string) ($metaSource['gameVersion'] ?? $baseMeta['gameVersion'] ?? 'legacy-runtime')) ?: 'legacy-runtime',
                'contentVersion' => trim((string) ($metaSource['contentVersion'] ?? $baseMeta['contentVersion'] ?? 'legacy-assets')) ?: 'legacy-assets',
                'updatedAt' => trim((string) ($metaSource['updatedAt'] ?? $baseMeta['updatedAt'] ?? self::defaultUpdatedAt())) ?: self::defaultUpdatedAt(),
                'notes' => trim((string) ($metaSource['notes'] ?? $baseMeta['notes'] ?? '')),
            ],
            'groups' => $groups,
            'assets' => $assets,
        ];
    }

    private static function normalizeGroups(array $groups): array
    {
        $allowedPages = array_map(
            static fn (array $page): string => (string) ($page['id'] ?? ''),
            self::pageDefinitions()
        );
        $seen = [];
        $normalized = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $id = self::normalizeIdentifier($group['id'] ?? '');
            if ($id === '' || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $pages = array_values(array_filter(
                array_map(static fn (mixed $pageId): string => trim((string) $pageId), self::arrayValues($group['pages'] ?? [])),
                static fn (string $pageId): bool => $pageId !== '' && in_array($pageId, $allowedPages, true)
            ));

            $normalized[] = [
                'id' => $id,
                'label' => trim((string) ($group['label'] ?? '')) ?: ucwords(str_replace(['-', '_'], ' ', $id)),
                'pages' => $pages,
                'preloadLabel' => trim((string) ($group['preloadLabel'] ?? '')),
                'order' => (int) ($group['order'] ?? count($normalized) * 10),
            ];
        }

        usort($normalized, static function (array $a, array $b): int {
            $order = ((int) $a['order']) <=> ((int) $b['order']);
            if ($order !== 0) {
                return $order;
            }
            return strcmp((string) $a['label'], (string) $b['label']);
        });

        return $normalized;
    }

    private static function normalizeAssets(array $assets, array $groupMap): array
    {
        $allowedPages = array_map(
            static fn (array $page): string => (string) ($page['id'] ?? ''),
            self::pageDefinitions()
        );
        $seen = [];
        $normalized = [];
        foreach ($assets as $asset) {
            if (!is_array($asset)) {
                continue;
            }

            $path = self::canonicalAssetPath((string) ($asset['path'] ?? $asset['src'] ?? ''));
            if ($path === '' || isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;

            $groupId = self::normalizeIdentifier($asset['groupId'] ?? '');
            if ($groupId !== '' && !isset($groupMap[$groupId])) {
                $groupId = '';
            }

            $pages = array_values(array_filter(
                array_map(static fn (mixed $pageId): string => trim((string) $pageId), self::arrayValues($asset['pages'] ?? [])),
                static fn (string $pageId): bool => $pageId !== '' && in_array($pageId, $allowedPages, true)
            ));
            if ($pages === [] && $groupId !== '' && is_array($groupMap[$groupId]['pages'] ?? null)) {
                $pages = array_values(array_filter(
                    array_map(static fn (mixed $pageId): string => trim((string) $pageId), $groupMap[$groupId]['pages']),
                    static fn (string $pageId): bool => $pageId !== ''
                ));
            }

            $cachePolicy = strtolower(trim((string) ($asset['cachePolicy'] ?? 'default')));
            if (!in_array($cachePolicy, ['default', 'no-store'], true)) {
                $cachePolicy = 'default';
            }

            $versionMode = strtolower(trim((string) ($asset['versionMode'] ?? 'none')));
            if (!in_array($versionMode, ['none', 'inherit-content-version', 'custom'], true)) {
                $versionMode = 'none';
            }

            $mimeType = trim((string) ($asset['mimeType'] ?? ''));
            if ($mimeType !== '' && $mimeType !== 'auto' && !str_starts_with($mimeType, 'image/')) {
                $mimeType = '';
            }

            $normalized[] = [
                'path' => $path,
                'pages' => $pages,
                'groupId' => $groupId,
                'cachePolicy' => $cachePolicy,
                'versionMode' => $versionMode,
                'customVersion' => trim((string) ($asset['customVersion'] ?? '')),
                'mimeType' => $mimeType,
                'enabled' => !array_key_exists('enabled', $asset) || filter_var($asset['enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false,
                'preload' => !array_key_exists('preload', $asset) || filter_var($asset['preload'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false,
                'notes' => trim((string) ($asset['notes'] ?? '')),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

        return $normalized;
    }

    private static function arrayValues(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        return array_values($value);
    }

    private static function normalizeIdentifier(mixed $value): string
    {
        $normalized = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim((string) $value))) ?? '';
        return trim($normalized, '-_');
    }

    private static function canonicalAssetPath(string $path): string
    {
        $value = trim($path);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[#?].*$/', '', $value) ?: '';
        $value = str_replace('\\', '/', $value);

        if (preg_match('#^https?://#i', $value)) {
            $parts = parse_url($value);
            $value = isset($parts['path']) ? (string) $parts['path'] : '';
        }

        $value = preg_replace('#^https?://[^/]+/#i', '', $value) ?: $value;
        $value = preg_replace('#^/?workspace/gacha/#i', '', $value) ?: $value;
        $value = preg_replace('#^/?gacha/#i', '', $value) ?: $value;
        $value = preg_replace('#^\./#', '', $value) ?: $value;

        return trim($value, '/');
    }

    private static function livePath(): string
    {
        return Bootstrap::rootPath(self::LIVE_PATH);
    }

    private static function draftPath(): string
    {
        return Bootstrap::rootPath(self::DRAFT_PATH);
    }

    private static function draftMetaPath(): string
    {
        return Bootstrap::rootPath(self::DRAFT_META_PATH);
    }

    private static function versionDir(): string
    {
        return Bootstrap::rootPath(self::VERSION_DIR);
    }

    private static function versionManifestPath(): string
    {
        return Bootstrap::rootPath(self::VERSION_MANIFEST_PATH);
    }

    private static function versionPath(string $versionId): string
    {
        return self::versionDir() . DIRECTORY_SEPARATOR . self::normalizeVersionId($versionId) . '.json';
    }

    private static function normalizeVersionId(string $versionId): string
    {
        $normalized = self::normalizeIdentifier($versionId);
        if ($normalized === '') {
            throw new RuntimeException('ASSET_MANIFEST_VERSION_ID_REQUIRED');
        }
        return $normalized;
    }

    private static function writeJsonFile(string $path, array $payload, string $errorCode): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('ASSET_MANIFEST_DIR_CREATE_FAILED');
        }

        $bytes = @file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            LOCK_EX
        );
        if ($bytes === false) {
            throw new RuntimeException($errorCode);
        }

        @chmod($dir, 0777);
        @chmod($path, 0666);
    }

    private static function writeDraftMeta(array $meta): void
    {
        self::writeJsonFile(
            self::draftMetaPath(),
            array_filter($meta, static fn (mixed $value): bool => $value !== null),
            'ASSET_MANIFEST_DRAFT_META_SAVE_FAILED'
        );
    }

    private static function snapshotVersion(array $manifest, array $meta = []): array
    {
        $normalizedManifest = self::normalizeManifest($manifest, self::defaultManifest());
        $hash = substr(hash('sha256', json_encode($normalizedManifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: random_bytes(8)), 0, 6);
        $id = strtolower(date('Ymd-His') . '-' . $hash);
        self::writeJsonFile(self::versionPath($id), $normalizedManifest, 'ASSET_MANIFEST_VERSION_SAVE_FAILED');

        $entry = [
            'id' => $id,
            'createdAt' => date(DateTimeInterface::ATOM),
            'gameVersion' => (string) ($normalizedManifest['meta']['gameVersion'] ?? ''),
            'contentVersion' => (string) ($normalizedManifest['meta']['contentVersion'] ?? ''),
            'groupCount' => count($normalizedManifest['groups'] ?? []),
            'assetCount' => count($normalizedManifest['assets'] ?? []),
            'hash' => $hash,
            'meta' => $meta,
        ];

        $manifestIndex = self::versionManifest();
        $versions = is_array($manifestIndex['versions'] ?? null) ? $manifestIndex['versions'] : [];
        array_unshift($versions, $entry);
        $seen = [];
        $versions = array_values(array_filter($versions, static function (array $item) use (&$seen): bool {
            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '' || isset($seen[$id])) {
                return false;
            }
            $seen[$id] = true;
            return true;
        }));

        self::writeJsonFile(self::versionManifestPath(), [
            'kind' => 'asset-manifest',
            'updatedAt' => date(DateTimeInterface::ATOM),
            'versions' => $versions,
        ], 'ASSET_MANIFEST_VERSION_MANIFEST_SAVE_FAILED');

        return $entry;
    }

    private static function defaultUpdatedAt(): string
    {
        $candidates = [
            Bootstrap::rootPath('gacha/index.html'),
            Bootstrap::rootPath('gacha/mileage.php'),
            Bootstrap::rootPath('gacha/data/mileage/board-main.v1.json'),
        ];
        $timestamp = 0;
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $timestamp = max($timestamp, (int) filemtime($path));
            }
        }

        if ($timestamp <= 0) {
            $timestamp = time();
        }

        return date(DateTimeInterface::ATOM, $timestamp);
    }
}
