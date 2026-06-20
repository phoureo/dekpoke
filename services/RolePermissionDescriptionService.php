<?php

declare(strict_types=1);

final class RolePermissionDescriptionService
{
    public const SETTING_KEY = 'reward.role_permission_descriptions';

    public static function defaults(): array
    {
        return [
            'version' => 2,
            'permissions' => [
                'CREATE_INSTANT_INVITE' => ['label' => 'สร้างลิงก์เชิญ', 'badge' => 'เชิญ', 'description' => 'สร้างและแชร์ลิงก์เชิญเข้าห้องหรือเซิร์ฟเวอร์ที่เปิดสิทธิ์ไว้'],
                'KICK_MEMBERS' => ['label' => 'เตะสมาชิก', 'badge' => 'เตะ', 'description' => 'นำสมาชิกออกจากเซิร์ฟเวอร์ได้ทันทีโดยไม่แบนถาวร'],
                'BAN_MEMBERS' => ['label' => 'แบนสมาชิก', 'badge' => 'แบน', 'description' => 'แบนสมาชิกและกันไม่ให้กลับเข้ามาใหม่จนกว่าจะปลดแบน'],
                'ADMINISTRATOR' => ['label' => 'ผู้ดูแลระบบเต็มรูปแบบ', 'badge' => 'แอดมิน', 'description' => 'ใช้สิทธิ์ทุกอย่างในเซิร์ฟเวอร์และข้ามการตั้งค่าสิทธิ์ย่อยของห้อง'],
                'MANAGE_CHANNELS' => ['label' => 'จัดการห้อง', 'badge' => 'ห้อง', 'description' => 'สร้าง แก้ไข ย้าย หรือลบห้องและหมวดหมู่ต่างๆ ได้'],
                'MANAGE_GUILD' => ['label' => 'จัดการเซิร์ฟเวอร์', 'badge' => 'เซิร์ฟ', 'description' => 'ปรับการตั้งค่าเซิร์ฟเวอร์หลัก เช่น ชื่อ ภาพ และโครงสร้างทั่วไป'],
                'ADD_REACTIONS' => ['label' => 'เพิ่มรีแอ็กชัน', 'badge' => 'รีแอ็กต์', 'description' => 'กดรีแอ็กชันบนข้อความในห้องที่เปิดให้ใช้งานได้'],
                'VIEW_AUDIT_LOG' => ['label' => 'ดูบันทึกการตรวจสอบ', 'badge' => 'Audit', 'description' => 'เปิดดู audit log และประวัติการจัดการของเซิร์ฟเวอร์ได้'],
                'PRIORITY_SPEAKER' => ['label' => 'สิทธิ์พูดก่อน', 'badge' => 'พูดก่อน', 'description' => 'ได้รับลำดับความสำคัญของเสียงเมื่อพูดในห้องเสียง'],
                'STREAM' => ['label' => 'แชร์หน้าจอและสตรีม', 'badge' => 'สตรีม', 'description' => 'เปิดวิดีโอหรือแชร์หน้าจอในห้องเสียงที่อนุญาตได้'],
                'VIEW_CHANNEL' => ['label' => 'ดูห้อง', 'badge' => 'เข้าห้อง', 'description' => 'มองเห็นและเข้าอ่านหรือเข้าฟังห้องที่เปิดสิทธิ์ให้ยศนี้'],
                'SEND_MESSAGES' => ['label' => 'ส่งข้อความ', 'badge' => 'แชต', 'description' => 'พิมพ์ข้อความตอบโต้ในห้องแชตได้ตามปกติ'],
                'SEND_TTS_MESSAGES' => ['label' => 'ส่งข้อความอ่านออกเสียง', 'badge' => 'TTS', 'description' => 'ส่งข้อความแบบ TTS ให้ระบบอ่านออกเสียงในห้องได้'],
                'MANAGE_MESSAGES' => ['label' => 'จัดการข้อความ', 'badge' => 'ลบข้อความ', 'description' => 'ลบ ปักหมุด หรือจัดการข้อความของผู้อื่นในห้องได้'],
                'EMBED_LINKS' => ['label' => 'ฝังลิงก์', 'badge' => 'ลิงก์', 'description' => 'ส่งลิงก์แบบมีการ์ดพรีวิวและข้อมูลประกอบอัตโนมัติได้'],
                'ATTACH_FILES' => ['label' => 'แนบไฟล์', 'badge' => 'ไฟล์', 'description' => 'อัปโหลดรูปภาพ ไฟล์ และสื่อประกอบลงในข้อความได้'],
                'READ_MESSAGE_HISTORY' => ['label' => 'อ่านประวัติข้อความ', 'badge' => 'ย้อนหลัง', 'description' => 'ย้อนดูข้อความเก่าในห้องที่เข้าถึงได้'],
                'MENTION_EVERYONE' => ['label' => 'แท็กทุกคน', 'badge' => '@everyone', 'description' => 'ใช้ @everyone @here หรือแท็กยศที่เปิดให้ mention ได้'],
                'USE_EXTERNAL_EMOJIS' => ['label' => 'ใช้อีโมจิภายนอก', 'badge' => 'อีโมจิ', 'description' => 'ใช้อีโมจิจากเซิร์ฟเวอร์อื่นในข้อความได้'],
                'VIEW_GUILD_INSIGHTS' => ['label' => 'ดูสถิติเซิร์ฟเวอร์', 'badge' => 'สถิติ', 'description' => 'เปิดดูหน้าสถิติและข้อมูลเชิงลึกของเซิร์ฟเวอร์ได้'],
                'CONNECT' => ['label' => 'เข้าห้องเสียง', 'badge' => 'เข้าเสียง', 'description' => 'เข้าร่วมห้องเสียงหรือห้องสเตจที่เปิดสิทธิ์ไว้ได้'],
                'SPEAK' => ['label' => 'พูดในห้องเสียง', 'badge' => 'พูด', 'description' => 'เปิดไมค์และพูดคุยในห้องเสียงได้'],
                'MUTE_MEMBERS' => ['label' => 'ปิดไมค์สมาชิก', 'badge' => 'ปิดไมค์', 'description' => 'สั่งปิดไมค์ของสมาชิกคนอื่นในห้องเสียงได้'],
                'DEAFEN_MEMBERS' => ['label' => 'ปิดการฟังของสมาชิก', 'badge' => 'ตัดเสียง', 'description' => 'ทำให้สมาชิกคนอื่นไม่ได้ยินเสียงในห้องเสียงได้'],
                'MOVE_MEMBERS' => ['label' => 'ย้ายสมาชิก', 'badge' => 'ย้าย', 'description' => 'ย้ายสมาชิกระหว่างห้องเสียงต่างๆ ได้'],
                'USE_VAD' => ['label' => 'ใช้การจับเสียงอัตโนมัติ', 'badge' => 'ตรวจเสียง', 'description' => 'เปิดไมค์ด้วยระบบตรวจจับเสียงโดยไม่ต้องกดปุ่มค้าง'],
                'CHANGE_NICKNAME' => ['label' => 'เปลี่ยนชื่อเล่นตนเอง', 'badge' => 'เปลี่ยนชื่อ', 'description' => 'เปลี่ยนชื่อเล่นของบัญชีตัวเองในเซิร์ฟเวอร์ได้'],
                'MANAGE_NICKNAMES' => ['label' => 'จัดการชื่อเล่น', 'badge' => 'จัดชื่อ', 'description' => 'เปลี่ยนหรือรีเซ็ตชื่อเล่นของสมาชิกคนอื่นได้'],
                'MANAGE_ROLES' => ['label' => 'จัดการยศ', 'badge' => 'ยศ', 'description' => 'สร้าง แก้ไข มอบ หรือจัดลำดับยศที่ระบบอนุญาตให้จัดการได้'],
                'MANAGE_WEBHOOKS' => ['label' => 'จัดการเว็บฮุก', 'badge' => 'Webhook', 'description' => 'สร้าง แก้ไข หรือถอด webhook ของห้องต่างๆ ได้'],
                'MANAGE_GUILD_EXPRESSIONS' => ['label' => 'จัดการอีโมจิและสติกเกอร์', 'badge' => 'สติ๊กเกอร์', 'description' => 'จัดการอีโมจิ สติกเกอร์ และเอฟเฟกต์สื่อของเซิร์ฟเวอร์ได้'],
                'USE_APPLICATION_COMMANDS' => ['label' => 'ใช้คำสั่งแอป', 'badge' => 'Slash', 'description' => 'ใช้ slash command และคำสั่งจากบอตหรือแอปต่างๆ ได้'],
                'REQUEST_TO_SPEAK' => ['label' => 'ขอขึ้นพูด', 'badge' => 'ขอพูด', 'description' => 'ยกมือขอพูดในห้องสเตจได้'],
                'MANAGE_EVENTS' => ['label' => 'จัดการอีเวนต์', 'badge' => 'อีเวนต์', 'description' => 'แก้ไข ดูแล และจัดการกิจกรรมตามกำหนดการของเซิร์ฟเวอร์ได้'],
                'MANAGE_THREADS' => ['label' => 'จัดการเธรด', 'badge' => 'เธรด', 'description' => 'ดูแล แก้ไข หรือลบเธรดในห้องที่เกี่ยวข้องได้'],
                'CREATE_PUBLIC_THREADS' => ['label' => 'สร้างเธรดสาธารณะ', 'badge' => 'เปิดเธรด', 'description' => 'เปิดเธรดสาธารณะจากข้อความในห้องที่รองรับได้'],
                'CREATE_PRIVATE_THREADS' => ['label' => 'สร้างเธรดส่วนตัว', 'badge' => 'เธรดลับ', 'description' => 'สร้างเธรดแบบส่วนตัวให้เห็นเฉพาะคนที่เกี่ยวข้องได้'],
                'USE_EXTERNAL_STICKERS' => ['label' => 'ใช้สติกเกอร์ภายนอก', 'badge' => 'สติกเกอร์', 'description' => 'ส่งสติกเกอร์จากเซิร์ฟเวอร์อื่นในข้อความได้'],
                'SEND_MESSAGES_IN_THREADS' => ['label' => 'ส่งข้อความในเธรด', 'badge' => 'แชตเธรด', 'description' => 'พิมพ์ข้อความภายในเธรดที่เข้าถึงได้'],
                'USE_EMBEDDED_ACTIVITIES' => ['label' => 'ใช้กิจกรรมร่วม', 'badge' => 'Activity', 'description' => 'เปิดกิจกรรมหรือมินิเกมร่วมกันในห้องเสียงได้'],
                'MODERATE_MEMBERS' => ['label' => 'ไทม์เอาต์สมาชิก', 'badge' => 'Timeout', 'description' => 'จำกัดการใช้งานสมาชิกชั่วคราวด้วยระบบ timeout ได้'],
                'VIEW_CREATOR_MONETIZATION_ANALYTICS' => ['label' => 'ดูสถิติรายได้ครีเอเตอร์', 'badge' => 'รายได้', 'description' => 'ดูข้อมูลวิเคราะห์ด้านการสร้างรายได้ของเซิร์ฟเวอร์ได้'],
                'USE_SOUNDBOARD' => ['label' => 'ใช้ซาวด์บอร์ด', 'badge' => 'Soundboard', 'description' => 'เล่นเสียงจาก soundboard ในห้องเสียงที่รองรับได้'],
                'CREATE_GUILD_EXPRESSIONS' => ['label' => 'สร้างอีโมจิและสติกเกอร์ใหม่', 'badge' => 'สร้างอีโมจิ', 'description' => 'อัปโหลดอีโมจิหรือสติกเกอร์ใหม่เข้าสู่เซิร์ฟเวอร์ได้'],
                'CREATE_EVENTS' => ['label' => 'สร้างอีเวนต์', 'badge' => 'สร้างงาน', 'description' => 'สร้างกิจกรรมตามกำหนดการใหม่ของเซิร์ฟเวอร์ได้'],
                'USE_EXTERNAL_SOUNDS' => ['label' => 'ใช้เสียงภายนอก', 'badge' => 'เสียงพิเศษ', 'description' => 'ใช้เสียงประกอบจากเซิร์ฟเวอร์อื่นใน soundboard ได้'],
                'SEND_VOICE_MESSAGES' => ['label' => 'ส่งข้อความเสียง', 'badge' => 'ข้อความเสียง', 'description' => 'ส่ง voice message ในห้องแชตที่อนุญาตได้'],
                'SEND_POLLS' => ['label' => 'ส่งโพล', 'badge' => 'โพล', 'description' => 'สร้างโพลเพื่อให้สมาชิกโหวตในห้องแชตได้'],
                'USE_EXTERNAL_APPS' => ['label' => 'ใช้แอปภายนอก', 'badge' => 'แอปนอก', 'description' => 'เรียกใช้แอปหรือเครื่องมือภายนอกที่ผูกกับ Discord ได้'],
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

        $normalized = self::normalize($decoded);
        if ($normalized !== $decoded) {
            try {
                self::save($normalized);
            } catch (Throwable) {
                // Keep using the normalized config in memory even if persisting fails.
            }
        }

        return $normalized;
    }

    public static function save(array $config): array
    {
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

        return $normalized;
    }

    public static function catalog(bool $seed = true): array
    {
        $config = self::load($seed);
        $defaults = self::defaults()['permissions'] ?? [];
        $current = is_array($config['permissions'] ?? null) ? $config['permissions'] : [];
        $out = [];

        foreach (DiscordPermissions::catalog() as $code => $item) {
            $default = is_array($defaults[$code] ?? null) ? $defaults[$code] : [];
            $entry = is_array($current[$code] ?? null) ? $current[$code] : [];
            $label = trim((string) ($entry['label'] ?? $default['label'] ?? $item['label']));
            $badge = trim((string) ($entry['badge'] ?? $default['badge'] ?? ''));
            $description = trim((string) ($entry['description'] ?? $default['description'] ?? ''));
            $defaultLabel = trim((string) ($default['label'] ?? $item['label']));
            $defaultBadge = trim((string) ($default['badge'] ?? ''));
            $defaultDescription = trim((string) ($default['description'] ?? ''));

            $out[] = [
                'code' => $code,
                'bit' => (int) ($item['bit'] ?? 0),
                'discordLabel' => (string) ($item['label'] ?? $code),
                'label' => $label !== '' ? $label : $defaultLabel,
                'badge' => $badge,
                'description' => $description,
                'defaultLabel' => $defaultLabel,
                'defaultBadge' => $defaultBadge,
                'defaultDescription' => $defaultDescription,
                'isCustomized' => $label !== $defaultLabel || $badge !== $defaultBadge || $description !== $defaultDescription,
            ];
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    public static function describeAllowedPermissions(?string $bitset, bool $seed = true): array
    {
        $catalog = array_column(self::catalog($seed), null, 'code');
        $decoded = DiscordPermissions::decode($bitset);

        return array_map(static function (array $permission) use ($catalog): array {
            $meta = $catalog[$permission['code']] ?? [];
            return [
                'code' => (string) ($permission['code'] ?? ''),
                'bit' => (int) ($permission['bit'] ?? 0),
                'discordLabel' => (string) ($permission['label'] ?? ($meta['discordLabel'] ?? '')),
                'label' => (string) ($meta['label'] ?? $permission['label'] ?? ''),
                'badge' => (string) ($meta['badge'] ?? ''),
                'description' => (string) ($meta['description'] ?? ''),
            ];
        }, $decoded);
    }

    public static function metrics(bool $seed = true): array
    {
        $catalog = self::catalog($seed);
        return [
            'total' => count($catalog),
            'customized' => count(array_filter($catalog, static fn (array $entry): bool => !empty($entry['isCustomized']))),
            'withDescription' => count(array_filter($catalog, static fn (array $entry): bool => trim((string) ($entry['description'] ?? '')) !== '')),
            'withBadge' => count(array_filter($catalog, static fn (array $entry): bool => trim((string) ($entry['badge'] ?? '')) !== '')),
        ];
    }

    private static function normalize(array $config): array
    {
        $defaults = self::defaults()['permissions'] ?? [];
        $current = [];

        if (isset($config['permissions']) && is_array($config['permissions'])) {
            foreach ($config['permissions'] as $code => $entry) {
                if (is_string($code) && is_array($entry)) {
                    $current[$code] = $entry;
                    continue;
                }
                if (is_array($entry) && !empty($entry['code'])) {
                    $current[(string) $entry['code']] = $entry;
                }
            }
        }

        $normalized = [
            'version' => 2,
            'permissions' => [],
        ];

        foreach (DiscordPermissions::catalog() as $code => $item) {
            $default = is_array($defaults[$code] ?? null) ? $defaults[$code] : [];
            $entry = is_array($current[$code] ?? null) ? $current[$code] : [];
            $normalized['permissions'][$code] = [
                'label' => trim((string) ($entry['label'] ?? $default['label'] ?? $item['label'])) ?: (string) ($default['label'] ?? $item['label']),
                'badge' => trim((string) ($entry['badge'] ?? $default['badge'] ?? '')),
                'description' => trim((string) ($entry['description'] ?? $default['description'] ?? '')),
            ];
        }

        return $normalized;
    }
}
