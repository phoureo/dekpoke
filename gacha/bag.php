<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

$isEmbed = isset($_GET['embed']);
$hideChrome = $isEmbed && (string) ($_GET['chrome'] ?? '') === '0';
$lang = strtolower((string) ($_GET['lang'] ?? 'th')) === 'en' ? 'en' : 'th';
$t = [
    'th' => [
        'bag' => 'กระเป๋า',
        'items' => 'ไอเทม',
        'roles' => 'ยศ',
        'history' => 'ประวัติ',
        'all' => 'ทั้งหมด',
        'income' => 'ได้รับ',
        'spend' => 'ใช้ไป',
        'movement' => 'สถานะ/อื่น ๆ',
        'history_filter_hint' => 'ได้รับ = ของหรือยอดเข้า · ใช้ไป = รายการหักออก · สถานะ/อื่น ๆ = สถานะยศหรือรายการที่ไม่เพิ่ม/หักยอด',
        'empty_items' => 'ยังไม่มีไอเทมในกระเป๋า',
        'empty_roles' => 'ยังไม่มียศที่กำลังถืออยู่',
        'empty_history' => 'ยังไม่มีความเคลื่อนไหว',
        'permanent' => 'ถาวร',
        'expired' => 'สิ้นสุดแล้ว',
        'loading_player' => '',
        'source_gacha_spin' => 'ใช้หมุนกาชา',
        'source_gacha_spin_refund' => 'คืนเครดิตกาชา',
        'source_gacha_reset_mock_credit' => 'รีเซ็ตเครดิต',
        'source_reward_event' => 'รางวัลอัตโนมัติ',
        'source_earn_rule' => 'รางวัลอัตโนมัติ',
        'source_earn_manual' => 'แอดมินเพิ่มให้',
        'source_earn_text_active_daily' => 'ข้อความ active รายวัน',
        'source_earn_voice_hourly' => 'สะสมชั่วโมงเสียง',
        'source_earn_voice_10min_free_spin' => 'เข้าห้องสนทนาครบ 10 นาที',
        'source_earn_member_first_join' => 'เข้าเซิร์ฟครั้งแรก',
        'source_earn_invite_member' => 'ชวนสมาชิกเข้าเซิร์ฟ',
        'source_wallet' => 'wallet',
        'source_expiration' => 'หมดอายุอัตโนมัติ',
        'source_role_status' => 'สถานะยศ',
        'free_spin' => 'สุ่มฟรี',
        'role_received_at' => 'ได้รับ',
        'role_starts_at' => 'เริ่ม',
        'role_ends_at' => 'หมดอายุ',
        'role_state_permanent' => 'ใช้งานถาวร',
        'role_state_active' => 'กำลังใช้งาน',
        'role_state_pending' => 'รอระบบแจกยศ',
        'role_state_queued' => 'รอต่อเวลา',
        'role_state_covered_by_permanent' => 'มีถาวรแล้ว',
        'role_state_expired_covered' => 'ถูกใช้ต่อแล้ว',
        'role_state_grant_failed' => 'แจกยศไม่สำเร็จ',
        'role_state_ended' => 'สิ้นสุดแล้ว',
        'remaining_days' => 'เหลือ {count} วัน',
        'remaining_hours' => 'เหลือ {count} ชั่วโมง',
        'remaining_minutes' => 'เหลือ {count} นาที',
        'days_name' => '{count} วัน',
        'qty' => 'x{count}',
        'slot' => 'ช่อง {count}',
        'role_badges' => 'แบดยศ',
        'role_badges_hint' => 'กดใช้กับตัวเองหรือเลือกให้เพื่อนในเซิร์ฟได้',
        'empty_role_badges' => 'ยังไม่มีแบดยศจากร้านค้า',
        'active_roles' => 'ยศที่กำลังถืออยู่',
        'active_roles_hint' => 'ยศที่ใช้งานอยู่หรือรอต่อเวลา จากกาชาและการใช้ badge',
        'apply_self' => 'ใส่ให้ฉัน',
        'apply_friend' => 'ให้เพื่อน',
        'search_friend' => 'ค้นหาชื่อเพื่อนในเซิร์ฟ',
        'search_friend_hint' => 'พิมพ์ชื่อเล่น ชื่อ Discord หรือ user id แล้วเลือกคนที่จะใส่ยศให้',
        'search_placeholder' => 'ค้นหาชื่อเพื่อน...',
        'search_empty' => 'ไม่พบสมาชิกที่ตรงคำค้น',
        'search_loading' => 'กำลังค้นหา...',
        'search_submit_hint' => 'พิมพ์อย่างน้อย 1 ตัวอักษร',
        'role_badge_qty' => 'มี {count} ชิ้น',
        'role_badge_qty_label' => 'จำนวน',
        'role_badge_applied' => 'ใส่ยศให้ {name} แล้ว',
        'role_badge_applied_remaining' => 'ใส่ยศให้ {name} แล้ว เหลือ {count} ชิ้น',
        'role_badge_applied_used_up' => 'ใส่ยศให้ {name} แล้ว ใช้ badge หมดแล้ว',
        'role_badge_using' => 'กำลังใส่ยศ...',
        'role_badge_modal_title' => 'เลือกเพื่อนที่จะใส่ยศนี้',
        'role_badge_modal_close' => 'ปิด',
        'role_badge_duration' => 'ระยะเวลา',
        'role_badge_series' => 'เซ็ต',
        'select_member' => 'เลือก',
        'source_shop_role_badge_purchase' => 'ซื้อแบดยศจากร้าน',
        'source_shop_role_badge_gift' => 'ซื้อแบดยศส่งให้เพื่อน',
        'use_item' => 'ใช้',
        'open_item' => 'เปิด',
        'item_used' => 'ใช้ไอเทมสำเร็จ',
        'item_opened' => 'เปิดไอเทมสำเร็จ',
        'stored_balls' => 'ลูกกาชาปิด',
        'stored_balls_hint' => 'รอบที่ออกจากหน้าก่อนรับรางวัล เก็บผลเดิมไว้ เปิดแล้วได้รางวัลเดิม',
        'stored_ball_name' => 'ลูกกาชาปิด',
        'open_stored_ball' => 'เปิดลูก',
        'stored_ball_opened' => 'เปิดลูกกาชาแล้ว',
        'hold_to_open' => 'กดค้างเพื่อดูเมนู',
        'stored_ball_menu_title' => 'ลูกกาชาปิด',
        'stored_ball_menu_open' => 'เปิด',
        'stored_ball_menu_details' => 'ดูรายละเอียด',
        'stored_ball_details_title' => 'รายละเอียดลูกกาชา',
        'stored_ball_details_hint' => 'รางวัลยังถูกปิดไว้จนกว่าจะกดเปิด',
        'stored_ball_detail_stored_at' => 'ส่งเข้ากระเป๋า',
        'stored_ball_detail_spin_at' => 'เริ่มหมุนเมื่อ',
        'stored_ball_detail_tier' => 'ลูกกาชา',
        'stored_ball_detail_spin_by' => 'หมุนโดย',
        'stored_ball_detail_count' => 'จำนวนรอบ',
        'stored_ball_detail_draw_id' => 'เลขรอบ',
        'stored_ball_detail_free_spin' => 'Free spin',
        'stored_ball_detail_unknown' => 'ไม่ทราบ',
        'stored_ball_detail_button' => 'ปุ่ม {buttonId}',
        'stored_ball_reward_title' => 'รางวัลจากลูกกาชา',
        'stored_ball_reward_close' => 'รับรางวัล',
        'stored_ball_reward_item_note' => 'ของรางวัลถูกส่งเข้ากระเป๋าแล้ว',
        'stored_ball_reward_item_qty_note' => 'ของรางวัลถูกส่งเข้ากระเป๋าแล้ว x{count}',
        'stored_ball_reward_role_note' => 'ระบบรับรางวัลแล้ว ดูสถานะยศต่อได้ในแท็บยศ',
        'stored_ball_reward_currency_note' => 'รางวัลถูกเติมเข้าบัญชีแล้ว',
        'stored_ball_reward_currency_qty_note' => 'รับ {count} {unit} เข้าบัญชีแล้ว',
        'stored_ball_reward_fallback_name' => 'รางวัลกาชา',
    ],
    'en' => [
        'bag' => 'Bag',
        'items' => 'Items',
        'roles' => 'Roles',
        'history' => 'History',
        'all' => 'All',
        'income' => 'Received',
        'spend' => 'Spent',
        'movement' => 'Status/Other',
        'history_filter_hint' => 'Received = items or balance added · Spent = deductions · Status/Other = role states or entries that do not change balances',
        'empty_items' => 'No items in your bag yet.',
        'empty_roles' => 'No active roles yet.',
        'empty_history' => 'No movement history yet.',
        'permanent' => 'Permanent',
        'expired' => 'Ended',
        'loading_player' => '',
        'source_gacha_spin' => 'Gacha spin',
        'source_gacha_spin_refund' => 'Gacha refund',
        'source_gacha_reset_mock_credit' => 'Credit reset',
        'source_reward_event' => 'Automatic reward',
        'source_earn_rule' => 'Automatic reward',
        'source_earn_manual' => 'Manual admin grant',
        'source_earn_text_active_daily' => 'Text active daily',
        'source_earn_voice_hourly' => 'Voice hourly',
        'source_earn_voice_10min_free_spin' => 'Voice 10-minute free spin',
        'source_earn_member_first_join' => 'First server join',
        'source_earn_invite_member' => 'Invite member',
        'source_wallet' => 'wallet',
        'source_expiration' => 'Automatic expiration',
        'source_role_status' => 'Role status',
        'free_spin' => 'Free spin',
        'role_received_at' => 'Received',
        'role_starts_at' => 'Starts',
        'role_ends_at' => 'Ends',
        'role_state_permanent' => 'Permanent access',
        'role_state_active' => 'Active now',
        'role_state_pending' => 'Pending grant',
        'role_state_queued' => 'Queued next',
        'role_state_covered_by_permanent' => 'Covered by permanent access',
        'role_state_expired_covered' => 'Consumed by a later extension',
        'role_state_grant_failed' => 'Grant failed',
        'role_state_ended' => 'Ended',
        'remaining_days' => '{count} days left',
        'remaining_hours' => '{count} hours left',
        'remaining_minutes' => '{count} min left',
        'days_name' => '{count} days',
        'qty' => 'x{count}',
        'slot' => 'Slot {count}',
        'role_badges' => 'Role badges',
        'role_badges_hint' => 'Use on yourself or assign to a friend in the server.',
        'empty_role_badges' => 'No role badges from the shop yet.',
        'active_roles' => 'Active roles',
        'active_roles_hint' => 'Roles currently active or queued from gacha and badge usage.',
        'apply_self' => 'Apply to me',
        'apply_friend' => 'Give to friend',
        'search_friend' => 'Search a friend in this server',
        'search_friend_hint' => 'Type nickname, Discord name, or user id, then choose who should receive this role.',
        'search_placeholder' => 'Search member...',
        'search_empty' => 'No matching members found.',
        'search_loading' => 'Searching...',
        'search_submit_hint' => 'Type at least 1 character',
        'role_badge_qty' => '{count} owned',
        'role_badge_qty_label' => 'Quantity',
        'role_badge_applied' => 'Applied the role to {name}.',
        'role_badge_applied_remaining' => 'Applied the role to {name}. {count} badge(s) left.',
        'role_badge_applied_used_up' => 'Applied the role to {name}. The badge was fully used up.',
        'role_badge_using' => 'Applying role...',
        'role_badge_modal_title' => 'Choose a friend for this role',
        'role_badge_modal_close' => 'Close',
        'role_badge_duration' => 'Duration',
        'role_badge_series' => 'Series',
        'select_member' => 'Select',
        'source_shop_role_badge_purchase' => 'Shop role badge purchase',
        'source_shop_role_badge_gift' => 'Gifted shop role badge purchase',
        'use_item' => 'Use',
        'open_item' => 'Open',
        'item_used' => 'Item used successfully.',
        'item_opened' => 'Item opened successfully.',
        'stored_balls' => 'Closed gacha balls',
        'stored_balls_hint' => 'Rounds left before claiming are saved here with their original result.',
        'stored_ball_name' => 'Closed ball',
        'open_stored_ball' => 'Open',
        'stored_ball_opened' => 'Closed ball opened.',
        'hold_to_open' => 'Hold for menu',
        'stored_ball_menu_title' => 'Closed gacha ball',
        'stored_ball_menu_open' => 'Open',
        'stored_ball_menu_details' => 'Details',
        'stored_ball_details_title' => 'Ball details',
        'stored_ball_details_hint' => 'The reward stays hidden until you open the ball.',
        'stored_ball_detail_stored_at' => 'Sent to bag',
        'stored_ball_detail_spin_at' => 'Spin started',
        'stored_ball_detail_tier' => 'Gacha ball',
        'stored_ball_detail_spin_by' => 'Spent',
        'stored_ball_detail_count' => 'Rounds',
        'stored_ball_detail_draw_id' => 'Draw ID',
        'stored_ball_detail_free_spin' => 'Free spin',
        'stored_ball_detail_unknown' => 'Unknown',
        'stored_ball_detail_button' => 'Button {buttonId}',
        'stored_ball_reward_title' => 'Reward from stored ball',
        'stored_ball_reward_close' => 'Claim reward',
        'stored_ball_reward_item_note' => 'The reward was added to your bag.',
        'stored_ball_reward_item_qty_note' => 'The reward was added to your bag x{count}.',
        'stored_ball_reward_role_note' => 'The reward was accepted. You can track the role in the Roles tab.',
        'stored_ball_reward_currency_note' => 'The reward was added to your balance.',
        'stored_ball_reward_currency_qty_note' => '{count} {unit} was added to your balance.',
        'stored_ball_reward_fallback_name' => 'Gacha reward',
    ],
][$lang];

$guildId = (string) Bootstrap::config('discord.guildId', '');
$baseUrl = rtrim((string) Bootstrap::config('app.baseUrl', '/discord'), '/');
$player = PlayerAuth::currentUser();
$userId = is_array($player) ? (string) ($player['userId'] ?? '') : '';
$initialSection = strtolower((string) ($_GET['section'] ?? ''));
$requestedTab = strtolower((string) ($_GET['tab'] ?? ''));
$initialTab = in_array($requestedTab, ['roles', 'history'], true) ? $requestedTab : 'items';
if (in_array($initialSection, ['role-badges', 'role-badge-items'], true)) {
    $initialTab = 'items';
}
if ($userId === '' && isset($_GET['player_token'])) {
    $tokenPlayer = PlayerAuth::resumeSessionFromToken((string) $_GET['player_token']);
    if (is_array($tokenPlayer)) {
        $userId = (string) ($tokenPlayer['userId'] ?? '');
    }
}
$items = [];
$storedBalls = [];
$bagMaxSlots = 70;
$bagMinVisibleSlots = 42;
$gridSlots = array_fill(0, $bagMaxSlots, null);
$visibleSlotCount = $bagMinVisibleSlots;
$roles = [];
$roleGroups = [];
$history = [];

function bagEsc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function bagJsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bagJsonAttr(array $payload): string
{
    return htmlspecialchars((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
}

function bagReadInput(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '', true);
    return is_array($decoded) ? array_merge($_POST, $decoded) : $_POST;
}

function bagAssetUrl(string $path): string
{
    global $baseUrl;
    $path = trim($path);
    if ($path === '') {
        return $baseUrl . '/gacha/images/item-1.png';
    }
    if (preg_match('/^https?:\/\//i', $path) || str_starts_with($path, '/')) {
        return $path;
    }
    return $baseUrl . '/gacha/' . ltrim($path, '/');
}

function bagStoredBallTierId(?string $tierId): string
{
    $normalized = strtolower(trim((string) $tierId));
    return in_array($normalized, ['common', 'rare', 'epic', 'legendary', 'mythic'], true)
        ? $normalized
        : 'common';
}

function bagStoredBallImage(string $tierId): string
{
    $normalized = bagStoredBallTierId($tierId);
    $path = match ($normalized) {
        'rare' => 'images/ball_2.png',
        'epic' => 'images/ball_3.png',
        'legendary' => 'images/ball_4.png',
        'mythic' => 'images/ball_5.png',
        default => 'images/ball_1.png',
    };
    return bagAssetUrl($path);
}

function bagDecodeJson(?string $json): array
{
    $decoded = json_decode((string) $json, true);
    return is_array($decoded) ? $decoded : [];
}

function bagFormatRemainingSeconds(int $seconds, array $t): string
{
    if ($seconds <= 0) {
        return $t['expired'];
    }
    $days = (int) ceil($seconds / 86400);
    if ($days >= 1) {
        return str_replace('{count}', (string) $days, $t['remaining_days']);
    }
    $hours = (int) ceil($seconds / 3600);
    if ($hours >= 1) {
        return str_replace('{count}', (string) $hours, $t['remaining_hours']);
    }
    $minutes = max(1, (int) ceil($seconds / 60));
    return str_replace('{count}', (string) $minutes, $t['remaining_minutes']);
}

function bagDurationName(int $days, array $t): string
{
    return $days > 0 ? str_replace('{count}', (string) $days, $t['days_name']) : $t['permanent'];
}

function bagInitialGlyph(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '#';
    }
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, 1, 'UTF-8');
    }
    return substr($text, 0, 1);
}

function bagRoleGroupSummary(array $group, array $t): string
{
    if (!empty($group['hasPermanent'])) {
        return $t['permanent'];
    }
    $seconds = (int) ($group['totalRemainingSeconds'] ?? 0);
    if ($seconds > 0) {
        return bagFormatRemainingSeconds($seconds, $t);
    }
    return $t['expired'];
}

function bagRoleEntryStateLabel(array $entry, array $t): string
{
    return match ((string) ($entry['state'] ?? 'ended')) {
        'permanent' => $t['role_state_permanent'],
        'active' => $t['role_state_active'],
        'queued' => $t['role_state_queued'],
        'covered_by_permanent' => $t['role_state_covered_by_permanent'],
        'expired_covered' => $t['role_state_expired_covered'],
        'grant_failed' => $t['role_state_grant_failed'],
        default => $t['role_state_ended'],
    };
}

function bagRoleEntryBadge(array $entry, array $t): string
{
    $durationDays = max(0, (int) ($entry['durationDays'] ?? 0));
    $state = (string) ($entry['state'] ?? '');
    $duration = bagDurationName((int) ($entry['durationDays'] ?? 0), $t);
    if ($durationDays <= 0) {
        return in_array($state, ['permanent', 'active', 'queued'], true)
            ? $t['permanent']
            : bagRoleEntryStateLabel($entry, $t);
    }
    if (in_array($state, ['active', 'queued'], true)) {
        $remaining = bagFormatRemainingSeconds((int) ($entry['segmentRemainingSeconds'] ?? $entry['remainingSeconds'] ?? 0), $t);
        return $duration . ' · ' . $remaining;
    }
    return $duration . ' · ' . bagRoleEntryStateLabel($entry, $t);
}

function bagRoleEntryIsActive(array $entry): bool
{
    return in_array((string) ($entry['state'] ?? ''), ['permanent', 'active', 'queued'], true);
}

function bagVisibleRoleEntries(array $group): array
{
    $entries = array_values(array_filter($group['entries'] ?? [], 'is_array'));
    $entries = array_values(array_filter($entries, 'bagRoleEntryIsActive'));
    if (!empty($group['hasPermanent'])) {
        $entries = array_values(array_filter($entries, static function (array $entry): bool {
            return !in_array((string) ($entry['state'] ?? ''), ['covered_by_permanent', 'expired_covered'], true);
        }));
    }
    return $entries;
}

function bagRoleGroupHasActiveEntries(array $group): bool
{
    return bagVisibleRoleEntries($group) !== [];
}

function bagSourceLabel(string $sourceType, array $t): string
{
    return match ($sourceType) {
        'gacha_spin' => $t['source_gacha_spin'],
        'gacha_spin_refund' => $t['source_gacha_spin_refund'],
        'gacha_reset_mock_credit' => $t['source_gacha_reset_mock_credit'],
        'reward_event' => $t['source_reward_event'],
        'earn_rule' => $t['source_earn_rule'],
        'earn_manual' => $t['source_earn_manual'],
        'earn_text_active_daily' => $t['source_earn_text_active_daily'],
        'earn_voice_hourly' => $t['source_earn_voice_hourly'],
        'earn_voice_10min_free_spin' => $t['source_earn_voice_10min_free_spin'],
        'earn_member_first_join' => $t['source_earn_member_first_join'],
        'earn_invite_member' => $t['source_earn_invite_member'],
        'shop_role_badge_purchase' => $t['source_shop_role_badge_purchase'],
        'shop_role_badge_gift' => $t['source_shop_role_badge_gift'],
        'expiration', 'expired', 'expire' => $t['source_expiration'],
        '' => $t['source_wallet'],
        default => $sourceType,
    };
}

function bagIsExpiryMovement(string $ledgerType, string $sourceType): bool
{
    $text = strtolower(trim($ledgerType . ' ' . $sourceType));
    return str_contains($text, 'expire') || str_contains($text, 'expiration') || str_contains($text, 'หมดอายุ');
}

function bagGrantStatusLabel(string $status, array $t): string
{
    return match ($status) {
        'granted' => $t['role_state_active'],
        'pending' => $t['role_state_pending'],
        'covered_by_permanent' => $t['role_state_covered_by_permanent'],
        'grant_failed' => $t['role_state_grant_failed'],
        'revoked', 'revoke_failed' => $t['role_state_ended'],
        default => $status !== '' ? $status : $t['income'],
    };
}

function bagUpdateInventorySlot(string $guildId, string $userId, int $inventoryId, int $slot): void
{
    $row = Database::fetch(
        'SELECT metadataJson
           FROM tbl_shop_inventory
          WHERE guildId = :guildId AND userId = :userId AND shopInventoryId = :shopInventoryId
          LIMIT 1',
        ['guildId' => $guildId, 'userId' => $userId, 'shopInventoryId' => $inventoryId]
    );
    if (!$row) {
        throw new RuntimeException('item not found');
    }
    $metadata = bagDecodeJson($row['metadataJson'] ?? null);
    $metadata['slot'] = $slot;
    Database::execute(
        'UPDATE tbl_shop_inventory
            SET metadataJson = :metadataJson, updateDate = :updateDate
          WHERE guildId = :guildId AND userId = :userId AND shopInventoryId = :shopInventoryId',
        [
            'metadataJson' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updateDate' => date('Y-m-d H:i:s'),
            'guildId' => $guildId,
            'userId' => $userId,
            'shopInventoryId' => $inventoryId,
        ]
    );
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $input = bagReadInput();
    if (($input['action'] ?? '') === 'move_item') {
        if ($userId === '') {
            bagJsonResponse(['ok' => false, 'message' => 'player required'], 401);
        }
        $inventoryId = max(0, (int) ($input['inventoryId'] ?? 0));
        $slot = max(0, min(69, (int) ($input['slot'] ?? 0)));
        try {
            bagUpdateInventorySlot($guildId, $userId, $inventoryId, $slot);
            bagJsonResponse(['ok' => true, 'slot' => $slot]);
        } catch (Throwable $exception) {
            bagJsonResponse(['ok' => false, 'message' => $exception->getMessage()], 404);
        }
    }
    if (($input['action'] ?? '') === 'search_members') {
        if ($userId === '') {
            bagJsonResponse(['ok' => false, 'message' => 'player required'], 401);
        }
        try {
            $members = class_exists('ShopRoleBadgeService')
                ? ShopRoleBadgeService::searchGuildMembers($guildId, trim((string) ($input['query'] ?? '')), 10)
                : [];
            bagJsonResponse(['ok' => true, 'members' => $members]);
        } catch (Throwable $exception) {
            bagJsonResponse(['ok' => false, 'message' => $exception->getMessage()], 400);
        }
    }
    if (($input['action'] ?? '') === 'apply_role_badge') {
        if ($userId === '') {
            bagJsonResponse(['ok' => false, 'message' => 'player required'], 401);
        }
        try {
            $result = class_exists('ShopRoleBadgeService')
                ? ShopRoleBadgeService::consumeBadge(
                    $guildId,
                    $userId,
                    max(0, (int) ($input['inventoryId'] ?? 0)),
                    trim((string) ($input['targetUserId'] ?? ''))
                )
                : ['ok' => false];
            bagJsonResponse($result);
        } catch (Throwable $exception) {
            $message = match ($exception->getMessage()) {
                'TARGET_NOT_FOUND' => 'ไม่พบสมาชิกคนนี้ในเซิร์ฟ',
                'TARGET_ALREADY_HAS_PERMANENT_ROLE' => 'คนนี้มียศถาวรนี้อยู่แล้ว จึงยังไม่ใช้ badge',
                'BADGE_NOT_FOUND' => 'ไม่พบ badge นี้ในกระเป๋า',
                'BADGE_OUT_OF_STOCK' => 'badge นี้ถูกใช้หมดแล้ว',
                'BADGE_ROLE_NOT_CONFIGURED' => 'badge นี้ยังตั้งค่า role ไม่ครบ',
                default => 'ใส่ยศไม่สำเร็จ ลองใหม่อีกครั้ง',
            };
            bagJsonResponse(['ok' => false, 'message' => $message], 400);
        }
    }
    if (($input['action'] ?? '') === 'open_stored_ball') {
        if ($userId === '') {
            bagJsonResponse(['ok' => false, 'message' => 'player required'], 401);
        }
        try {
            $result = class_exists('GachaRewardSettlementService')
                ? (new GachaRewardSettlementService())->openStoredBall(
                    $guildId,
                    $userId,
                    max(0, (int) ($input['storedBallId'] ?? 0))
                )
                : ['ok' => false];
            bagJsonResponse(['ok' => true, 'result' => $result]);
        } catch (Throwable $exception) {
            $message = match ($exception->getMessage()) {
                'STORED_BALL_NOT_FOUND' => 'ไม่พบลูกกาชานี้ในกระเป๋า',
                'STORED_BALL_DRAW_INVALID' => 'ข้อมูลลูกกาชาไม่สมบูรณ์',
                default => 'เปิดลูกกาชาไม่สำเร็จ ลองใหม่อีกครั้ง',
            };
            bagJsonResponse(['ok' => false, 'message' => $message], 400);
        }
    }
    if (($input['action'] ?? '') === 'use_item') {
        if ($userId === '') {
            bagJsonResponse(['ok' => false, 'message' => 'player required'], 401);
        }
        try {
            $result = class_exists('InventoryItemUseService')
                ? (new InventoryItemUseService())->useInventoryItem(
                    $guildId,
                    $userId,
                    max(0, (int) ($input['inventoryId'] ?? 0)),
                    [
                        'sourceType' => 'bag_use',
                        'sourceId' => 'bag:' . $userId,
                        'metadata' => [
                            'surface' => 'gacha_bag',
                        ],
                    ]
                )
                : ['ok' => false];
            bagJsonResponse(['ok' => true, 'result' => $result]);
        } catch (Throwable $exception) {
            $message = match ($exception->getMessage()) {
                'ITEM_NOT_USABLE' => 'ไอเทมนี้ยังใช้จากกระเป๋าไม่ได้',
                'ITEM_NOT_FOUND' => 'ไม่พบไอเทมนี้ในกระเป๋า',
                'ITEM_OUT_OF_STOCK' => 'ไอเทมนี้ถูกใช้หมดแล้ว',
                'ITEM_HANDLER_UNAVAILABLE' => 'ไอเทมนี้ยังไม่มี handler รองรับ',
                default => 'ใช้ไอเทมไม่สำเร็จ ลองใหม่อีกครั้ง',
            };
            bagJsonResponse(['ok' => false, 'message' => $message], 400);
        }
    }
}

if ($userId !== '') {
    try {
        ShopUnitService::ensureSchema();
        $shopRows = Database::fetchAll(
            'SELECT inv.shopInventoryId, inv.quantity, inv.metadataJson AS inventoryMetadataJson,
                    item.itemCode, item.itemName, item.itemType, item.image, item.effectType, item.metadataJson AS itemMetadataJson
               FROM tbl_shop_inventory inv
         INNER JOIN tbl_shop_item item ON item.shopItemId = inv.shopItemId
              WHERE inv.guildId = :guildId
                AND inv.userId = :userId
                AND inv.quantity > 0
                AND item.isActive = 1
              ORDER BY inv.updateDate DESC, item.itemName ASC',
            ['guildId' => $guildId, 'userId' => $userId]
        );
        foreach ($shopRows as $row) {
            $inventoryMeta = bagDecodeJson($row['inventoryMetadataJson'] ?? null);
            $itemMeta = bagDecodeJson($row['itemMetadataJson'] ?? null);
            $itemType = (string) ($row['itemType'] ?? 'item');
            $roleBadge = null;
            if ($itemType === 'role_badge') {
                $roleName = trim((string) ($itemMeta['roleName'] ?? $row['itemName'] ?? 'Role Badge'));
                $roleBadge = [
                    'inventoryId' => (int) ($row['shopInventoryId'] ?? 0),
                    'name' => $roleName !== '' ? $roleName : (string) ($row['itemName'] ?? 'Role Badge'),
                    'itemName' => (string) ($row['itemName'] ?? $roleName ?: 'Role Badge'),
                    'image' => bagAssetUrl((string) ($row['image'] ?? '')),
                    'quantity' => (int) ($row['quantity'] ?? 0),
                    'roleColor' => (string) ($itemMeta['roleColor'] ?? ''),
                    'roleIconUrl' => (string) ($itemMeta['roleIconUrl'] ?? ''),
                    'roleTier' => (string) ($itemMeta['roleTier'] ?? ''),
                    'seriesName' => (string) ($itemMeta['roleSeriesName'] ?? ''),
                    'seriesBadge' => (string) ($itemMeta['roleSeriesBadge'] ?? ''),
                    'durationDays' => (int) ($itemMeta['durationDays'] ?? 0),
                    'durationLabel' => (string) ($itemMeta['durationLabel'] ?? ''),
                    'detailText' => (string) ($itemMeta['detailText'] ?? ''),
                    'conditionText' => (string) ($itemMeta['conditionText'] ?? ''),
                ];
            }
            $slot = isset($inventoryMeta['slot']) ? (int) $inventoryMeta['slot'] : null;
            $items[] = [
                'inventoryId' => (int) ($row['shopInventoryId'] ?? 0),
                'name' => (string) ($row['itemName'] ?? $row['itemCode'] ?? 'Item'),
                'code' => (string) ($row['itemCode'] ?? ''),
                'type' => $itemType,
                'image' => bagAssetUrl((string) ($row['image'] ?? '')),
                'effectType' => (string) ($row['effectType'] ?? ''),
                'isUsable' => in_array((string) ($row['effectType'] ?? ''), ['loot_box', 'currency_bundle'], true),
                'quantity' => (int) ($row['quantity'] ?? 0),
                'slot' => $slot !== null && $slot >= 0 && $slot < $bagMaxSlots ? $slot : null,
                'meta' => $itemMeta,
                'roleBadge' => $roleBadge,
            ];
        }
    } catch (Throwable) {
        $items = [];
    }

    try {
        $storedBalls = class_exists('GachaRewardSettlementService')
            ? (new GachaRewardSettlementService())->storedBalls($guildId, $userId, 50)
            : [];
    } catch (Throwable) {
        $storedBalls = [];
    }

    foreach ($storedBalls as $ball) {
        $storedBallId = (int) ($ball['storedBallId'] ?? 0);
        if ($storedBallId <= 0) {
            continue;
        }
        $tierId = bagStoredBallTierId((string) ($ball['tierId'] ?? ''));
        $tierName = trim((string) ($ball['tierName'] ?? ''));
        $count = max(1, (int) ($ball['count'] ?? 1));
        $storedBallDetails = [
            'storedBallId' => $storedBallId,
            'drawId' => (string) ($ball['drawId'] ?? ''),
            'tierId' => $tierId,
            'tierName' => $tierName,
            'count' => $count,
            'buttonId' => max(0, (int) ($ball['buttonId'] ?? 0)),
            'currency' => (string) ($ball['currency'] ?? ''),
            'cost' => max(0, (int) ($ball['cost'] ?? 0)),
            'costPerSpin' => max(0, (int) ($ball['costPerSpin'] ?? 0)),
            'usedFreeSpin' => !empty($ball['usedFreeSpin']),
            'freeSpinSource' => (string) ($ball['freeSpinSource'] ?? ''),
            'spinCommittedAt' => (int) ($ball['spinCommittedAt'] ?? 0),
            'storedAt' => (string) ($ball['storedAt'] ?? $ball['createdAt'] ?? ''),
            'storedReason' => (string) ($ball['storedReason'] ?? ''),
        ];
        $items[] = [
            'inventoryId' => 0,
            'storedBallId' => $storedBallId,
            'name' => $tierName !== '' ? $tierName : $t['stored_ball_name'],
            'code' => 'gacha_stored_ball_' . $storedBallId,
            'type' => 'stored_ball',
            'image' => bagStoredBallImage($tierId),
            'effectType' => 'stored_ball',
            'isUsable' => false,
            'quantity' => $count,
            'slot' => null,
            'meta' => [
                'tierId' => $tierId,
                'tierName' => $tierName,
                'holdLabel' => $t['hold_to_open'],
                'cardTitle' => trim($t['stored_ball_name'] . ($tierName !== '' ? ' · ' . $tierName : '')),
                'details' => $storedBallDetails,
            ],
            'roleBadge' => null,
        ];
    }

    $nextFreeSlot = 0;
    foreach ($items as $index => $item) {
        $slot = $item['slot'];
        if ($slot === null || $gridSlots[$slot] !== null) {
            while ($nextFreeSlot < $bagMaxSlots && $gridSlots[$nextFreeSlot] !== null) {
                $nextFreeSlot++;
            }
            $slot = $nextFreeSlot < $bagMaxSlots ? $nextFreeSlot : null;
        }
        if ($slot !== null) {
            $gridSlots[$slot] = $index;
        }
    }
    $highestSlot = -1;
    foreach ($gridSlots as $slotIndex => $itemIndex) {
        if ($itemIndex !== null) {
            $highestSlot = max($highestSlot, (int) $slotIndex);
        }
    }
    $visibleSlotCount = min($bagMaxSlots, max($bagMinVisibleSlots, (int) (ceil(($highestSlot + 1) / 7) * 7) + 7));

    try {
        $roles = Database::fetchAll(
            'SELECT gr.*, COALESCE(r.roleName, gr.roleName, gr.roleId) AS sourceRoleName
               FROM tbl_gacha_role_grant gr
          LEFT JOIN tbl_role r ON r.guildId = gr.guildId AND r.roleId = gr.roleId
              WHERE gr.guildId = :guildId AND gr.userId = :userId
              ORDER BY COALESCE(gr.grantedAt, gr.createDate) DESC, gr.gachaRoleGrantId DESC
              LIMIT 100',
            ['guildId' => $guildId, 'userId' => $userId]
        );
    } catch (Throwable) {
        $roles = [];
    }

    if (class_exists('GachaRoleGrantService')) {
        try {
            $roleGroups = (new GachaRoleGrantService())->summarizeUserGrants($guildId, $userId, 100);
            $roleGroups = array_values(array_filter($roleGroups, 'bagRoleGroupHasActiveEntries'));
            usort($roleGroups, static function (array $left, array $right): int {
                $leftPermanent = !empty($left['hasPermanent']) || (string) ($left['summaryState'] ?? '') === 'permanent';
                $rightPermanent = !empty($right['hasPermanent']) || (string) ($right['summaryState'] ?? '') === 'permanent';
                if ($leftPermanent !== $rightPermanent) {
                    return $leftPermanent <=> $rightPermanent;
                }
                $dateSort = strcmp((string) ($right['latestDate'] ?? ''), (string) ($left['latestDate'] ?? ''));
                if ($dateSort !== 0) {
                    return $dateSort;
                }
                return strcmp((string) ($left['roleName'] ?? ''), (string) ($right['roleName'] ?? ''));
            });
        } catch (Throwable) {
            $roleGroups = [];
        }
    }

    try {
        $spinRows = Database::fetchAll(
            'SELECT prizeName, prizeId, tierName, completedAt
               FROM tbl_gacha_spin_history
              WHERE guildId = :guildId
                AND userId = :userId
                AND drawStatus = "completed"
                AND COALESCE(prizeType, "item") <> "role"
              ORDER BY completedAt DESC, gachaSpinHistoryId DESC
              LIMIT 80',
            ['guildId' => $guildId, 'userId' => $userId]
        );
        foreach ($spinRows as $row) {
            $history[] = [
                'kind' => 'in',
                'title' => (string) ($row['prizeName'] ?? $row['prizeId'] ?? $t['income']),
                'detail' => $t['income'],
                'source' => 'กาชาปอง' . (!empty($row['tierName']) ? ' · ' . $row['tierName'] : ''),
                'date' => (string) ($row['completedAt'] ?? ''),
            ];
        }
    } catch (Throwable) {
        // Optional history table.
    }

    try {
        $walletRows = Database::fetchAll(
            'SELECT wl.*, sw.userId
               FROM tbl_shop_wallet_ledger wl
         INNER JOIN tbl_shop_wallet sw ON sw.shopWalletId = wl.shopWalletId
              WHERE sw.guildId = :guildId
                AND sw.userId = :userId
              ORDER BY wl.createDate DESC, wl.shopWalletLedgerId DESC
              LIMIT 120',
            ['guildId' => $guildId, 'userId' => $userId]
        );
        foreach ($walletRows as $row) {
            $delta = (int) ($row['amountDelta'] ?? 0);
            $isMovement = bagIsExpiryMovement((string) ($row['ledgerType'] ?? ''), (string) ($row['sourceType'] ?? ''));
            $history[] = [
                'kind' => $isMovement ? 'move' : ($delta < 0 ? 'out' : 'in'),
                'title' => $isMovement ? $t['movement'] : ($delta < 0 ? $t['spend'] : $t['income']),
                'detail' => (($delta > 0 ? '+' : '') . number_format($delta) . ' ' . ($row['unitCode'] ?? '')),
                'source' => $isMovement ? $t['source_expiration'] : bagSourceLabel(trim((string) ($row['sourceType'] ?? '')), $t),
                'date' => (string) ($row['createDate'] ?? ''),
            ];
        }
    } catch (Throwable) {
        // Keep reward history below even if wallet ledger is unavailable.
    }

    try {
        $inventoryLedgerRows = Database::fetchAll(
            'SELECT il.*, item.itemName, item.itemCode
               FROM tbl_shop_inventory_ledger il
          LEFT JOIN tbl_shop_item item ON item.shopItemId = il.shopItemId
              WHERE il.guildId = :guildId
                AND il.userId = :userId
              ORDER BY il.createDate DESC, il.shopInventoryLedgerId DESC
              LIMIT 80',
            ['guildId' => $guildId, 'userId' => $userId]
        );
        foreach ($inventoryLedgerRows as $row) {
            $delta = (int) ($row['quantityDelta'] ?? 0);
            $isMovement = bagIsExpiryMovement((string) ($row['ledgerType'] ?? ''), (string) ($row['sourceType'] ?? ''));
            $itemName = (string) ($row['itemName'] ?? $row['itemCode'] ?? $t['items']);
            $history[] = [
                'kind' => $isMovement ? 'move' : ($delta < 0 ? 'out' : 'in'),
                'title' => $itemName,
                'detail' => ($isMovement ? $t['expired'] . ' ' : '') . (($delta > 0 ? '+' : '') . number_format($delta)),
                'source' => $isMovement ? $t['source_expiration'] : bagSourceLabel(trim((string) ($row['sourceType'] ?? '')), $t),
                'date' => (string) ($row['createDate'] ?? ''),
            ];
        }
    } catch (Throwable) {
        // Inventory ledger is optional for older installs.
    }

    try {
        $rewardRows = Database::fetchAll(
            'SELECT re.*, rr.ruleName, rr.ruleCode
               FROM tbl_reward_event re
         INNER JOIN tbl_reward_rule rr ON rr.rewardRuleId = re.rewardRuleId
              WHERE re.guildId = :guildId AND re.userId = :userId
              ORDER BY re.createDate DESC, re.rewardEventId DESC
              LIMIT 80',
            ['guildId' => $guildId, 'userId' => $userId]
        );
        foreach ($rewardRows as $row) {
            $meta = bagDecodeJson($row['metadataJson'] ?? null);
            $reward = is_array($meta['reward'] ?? null) ? $meta['reward'] : [];
            $unitRewards = is_array($reward['unitRewards'] ?? null) ? $reward['unitRewards'] : [];
            $parts = [];
            foreach ($unitRewards as $unitCode => $amount) {
                $amount = (int) $amount;
                if ($amount > 0) {
                    $parts[] = '+' . number_format($amount) . ' ' . $unitCode;
                }
            }
            if (!$parts && (int) ($reward['coin'] ?? 0) > 0) $parts[] = '+' . number_format((int) $reward['coin']) . ' coin';
            if (!$parts && (int) ($reward['gachaTicket'] ?? 0) > 0) $parts[] = '+' . number_format((int) $reward['gachaTicket']) . ' ticket';
            if ((int) ($reward['gachaFreeSpin'] ?? 0) > 0) $parts[] = '+' . number_format((int) $reward['gachaFreeSpin']) . ' ' . $t['free_spin'];
            $history[] = [
                'kind' => 'in',
                'title' => (string) ($row['ruleName'] ?? $row['ruleCode'] ?? $t['income']),
                'detail' => implode(' + ', $parts) ?: $t['income'],
                'source' => bagSourceLabel((string) ($row['sourceType'] ?? 'reward_event'), $t),
                'date' => (string) ($row['createDate'] ?? ''),
            ];
        }
    } catch (Throwable) {
        // Reward rows are optional for the embedded bag view.
    }

    foreach ($roles as $role) {
        $durationText = bagDurationName((int) ($role['durationDays'] ?? 0), $t);
        $roleName = (string) ($role['sourceRoleName'] ?? $role['roleName'] ?? $role['roleId'] ?? 'Role');
        $status = (string) ($role['grantStatus'] ?? '');
        $expireAt = (string) ($role['expireAt'] ?? '');
        $expireTs = $expireAt !== '' ? strtotime($expireAt) : false;
        $durationDays = (int) ($role['durationDays'] ?? 0);
        $isExpiredActiveGrant = in_array($status, ['granted', 'revoke_failed'], true)
            && $durationDays > 0
            && $expireTs !== false
            && $expireTs <= time();
        $isMovement = $isExpiredActiveGrant
            || in_array($status, ['pending', 'revoked', 'revoke_failed', 'covered_by_permanent', 'expired_covered', 'grant_failed'], true);
        $history[] = [
            'kind' => $isMovement ? 'move' : 'in',
            'title' => trim($roleName . ' ' . $durationText),
            'detail' => $isExpiredActiveGrant ? $t['expired'] : bagGrantStatusLabel($status, $t),
            'source' => $isMovement ? $t['source_role_status'] : 'กาชาปอง',
            'date' => (string) (
                $isMovement
                    ? ($role['revokedAt'] ?? $role['expireAt'] ?? $role['updateDate'] ?? $role['grantedAt'] ?? $role['createDate'] ?? '')
                    : ($role['grantedAt'] ?? $role['createDate'] ?? '')
            ),
        ];
    }

    usort($history, static fn (array $a, array $b): int => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
    $history = array_slice($history, 0, 140);
}

$roleBadgeBaseIcon = bagAssetUrl('images/icon_roles_blank.png');
?>
<!doctype html>
<html lang="<?= bagEsc($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    @font-face { font-family: 'GachaUi'; src: url('fonts/FCVisionRounded-Regular.woff2') format('woff2'); font-display: swap; font-weight: 400; }
    @font-face { font-family: 'GachaUi'; src: url('fonts/FCVisionRounded-SemiBold.woff2') format('woff2'); font-display: swap; font-weight: 600; }
    @font-face { font-family: 'GachaUi'; src: url('fonts/FCVisionRounded-Bold.woff2') format('woff2'); font-display: swap; font-weight: 700; }
    :root {
      --bag-ink: #3f2b52;
      --bag-muted: rgba(63, 43, 82, .62);
      --bag-line: rgba(99, 68, 128, .16);
      --bag-paper: rgba(255, 255, 255, .9);
      --bag-paper-soft: rgba(255, 255, 255, .72);
      --bag-purple: #7b5bd6;
      --bag-pink: #ed78b6;
      --bag-cyan: #62c7d8;
    }
    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    html, body { margin: 0; width: 100%; height: 100%; background: transparent; color: var(--bag-ink); font-family: GachaUi, system-ui, sans-serif; }
    body { overflow: hidden; }
    button { font: inherit; }
    .bag-shell {
      height: 100vh;
      display: grid;
      grid-template-rows: auto minmax(0, 1fr);
      gap: 10px;
      padding: 10px 14px 12px;
    }
    .bag-shell.is-chrome-hidden {
      grid-template-rows: minmax(0, 1fr);
      padding: 4px 4px 8px;
    }
    .bag-head {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
      gap: 10px;
      align-items: center;
    }
    .bag-title { grid-column: 1; }
    .bag-title h1 {
      margin: 0;
      color: #3d2752;
      font-size: 27px;
      line-height: 1.05;
      font-weight: 800;
    }
    .bag-head.is-embed-head {
      grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    }
    .history-open {
      grid-column: 3;
      justify-self: end;
      min-height: 32px;
      padding: 0 12px;
      border: 1px solid rgba(255,255,255,.76);
      border-radius: 999px;
      background: rgba(255,255,255,.82);
      color: #56356e;
      font-size: 12px;
      font-weight: 800;
      box-shadow: 0 8px 14px rgba(73, 48, 95, .1);
    }
    .bag-tabs {
      grid-column: 2;
      justify-self: center;
      width: fit-content;
      display: inline-flex;
      gap: 6px;
      padding: 5px;
      border-radius: 999px;
      background: rgba(255,255,255,.72);
      border: 1px solid rgba(255,255,255,.78);
      box-shadow: 0 10px 18px rgba(73, 48, 95, .08);
    }
    .bag-tab {
      border: 1px solid var(--bag-line);
      border-radius: 999px;
      padding: 8px 18px;
      color: var(--bag-muted);
      background: rgba(255,255,255,.82);
      font-weight: 800;
    }
    .bag-tab.is-active {
      color: #fff;
      border-color: transparent;
      background: linear-gradient(135deg, var(--bag-pink), var(--bag-cyan));
    }
    .history-open.is-active {
      color: #fff;
      border-color: transparent;
      background: linear-gradient(135deg, var(--bag-pink), var(--bag-cyan));
      box-shadow: 0 10px 18px rgba(93, 135, 187, .18);
    }
    .bag-content {
      min-height: 0;
      overflow: hidden;
      border: 0;
      border-radius: 0;
      background: transparent;
      box-shadow: none;
    }
    .bag-panel {
      height: 100%;
      min-height: 0;
      overflow: auto;
      display: none;
      padding: 4px 4px 8px;
    }
    .bag-panel.is-active { display: block; }
    .bag-panel.is-history-panel {
      overflow: hidden;
    }
    .bag-panel.is-history-panel.is-active {
      display: grid;
      grid-template-rows: auto auto minmax(0, 1fr);
      gap: 10px;
    }
    .bag-grid {
      display: grid;
      grid-template-columns: repeat(7, minmax(0, 1fr));
      gap: 6px;
      min-width: 0;
    }
    .bag-slot {
      position: relative;
      aspect-ratio: 1;
      min-width: 0;
      border: 1px solid rgba(97, 67, 124, .13);
      border-radius: 10px;
      background:
        linear-gradient(180deg, rgba(255,255,255,.72), rgba(244,238,255,.52));
      box-shadow: inset 0 1px 0 rgba(255,255,255,.72);
    }
    .bag-slot.is-drop-target {
      border-color: rgba(237, 120, 182, .68);
      box-shadow: inset 0 0 0 2px rgba(237, 120, 182, .16), 0 0 14px rgba(237, 120, 182, .2);
    }
    .bag-item-card {
      position: absolute;
      inset: 3px;
      display: grid;
      place-items: center;
      border: 0;
      border-radius: 9px;
      background: rgba(255,255,255,.76);
      box-shadow: 0 6px 10px rgba(73, 48, 95, .12);
      cursor: grab;
    }
    .bag-item-card:active { cursor: grabbing; }
    .bag-item-card.is-role-badge {
      background:
        radial-gradient(circle at top, rgba(255,255,255,.94), rgba(244,238,255,.88)),
        linear-gradient(180deg, rgba(250,246,255,.94), rgba(240,247,255,.84));
      box-shadow: 0 9px 16px rgba(73, 48, 95, .16);
      cursor: pointer;
    }
    .bag-item-card.is-role-badge:active { cursor: pointer; }
    .bag-item-card[data-role-badge-item]:focus-visible {
      outline: 2px solid rgba(123, 91, 214, .52);
      outline-offset: 2px;
    }
    .bag-item-card img {
      width: 76%;
      height: 76%;
      object-fit: contain;
      filter: drop-shadow(0 6px 6px rgba(55,45,76,.14));
    }
    .bag-role-badge-visual {
      position: relative;
      width: 76%;
      height: 76%;
      display: grid;
      place-items: center;
    }
    .bag-role-badge-shell {
      width: 100%;
      height: 100%;
      object-fit: contain;
      filter: drop-shadow(0 8px 8px rgba(55,45,76,.14));
    }
    .bag-role-badge-icon {
      position: absolute;
      width: 44%;
      height: 44%;
      object-fit: contain;
      border-radius: 12px;
      filter: drop-shadow(0 8px 8px rgba(55,45,76,.14));
    }
    .bag-role-badge-fallback {
      position: absolute;
      width: 44%;
      height: 44%;
      display: grid;
      place-items: center;
      border-radius: 14px;
      background: linear-gradient(180deg, rgba(255,247,227,.98), rgba(255,236,180,.96));
      color: #6a447f;
      font-size: 18px;
      font-weight: 900;
      box-shadow: 0 8px 12px rgba(55,45,76,.12);
    }
    .bag-role-badge-dot {
      position: absolute;
      right: 17%;
      bottom: 16%;
      width: 12px;
      height: 12px;
      border-radius: 999px;
      border: 2px solid rgba(255,255,255,.94);
      background: #8b91a1;
      box-shadow: 0 3px 6px rgba(55,45,76,.18);
    }
    .bag-item-qty {
      position: absolute;
      right: 4px;
      top: 4px;
      min-width: 22px;
      min-height: 18px;
      display: grid;
      place-items: center;
      border-radius: 999px;
      background: #4b3161;
      color: #fff;
      font-size: 10px;
      font-weight: 900;
    }
    .bag-item-name {
      position: absolute;
      left: 3px;
      right: 3px;
      bottom: 3px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      border-radius: 7px;
      padding: 3px 4px;
      background: rgba(255,255,255,.84);
      color: #4b3161;
      font-size: 9px;
      font-weight: 800;
      text-align: center;
    }
    .bag-item-card.is-role-badge .bag-item-name {
      left: 5px;
      right: 5px;
      bottom: 5px;
      padding: 4px 6px;
      background: linear-gradient(180deg, rgba(255,250,235,.96), rgba(255,240,198,.94));
      color: #6a447f;
      font-weight: 900;
    }
    .bag-item-use {
      position: absolute;
      left: 6px;
      right: 6px;
      bottom: 22px;
      border: 0;
      border-radius: 999px;
      padding: 4px 6px;
      background: linear-gradient(180deg, rgba(123, 91, 214, .96), rgba(86, 65, 173, .96));
      color: #fff;
      font-size: 9px;
      font-weight: 900;
      letter-spacing: .04em;
      box-shadow: 0 5px 10px rgba(73, 48, 95, .18);
    }
    .bag-item-card.is-stored-ball {
      isolation: isolate;
      overflow: hidden;
      cursor: pointer;
      touch-action: manipulation;
      background:
        radial-gradient(circle at 50% 4%, rgba(255,255,255,.96), rgba(255,255,255,.58) 24%, transparent 46%),
        linear-gradient(180deg, rgba(255,255,255,.94), rgba(242,236,255,.9));
      box-shadow: 0 10px 18px rgba(73, 48, 95, .16);
      transition: transform .16s ease, box-shadow .16s ease, opacity .16s ease;
    }
    .bag-item-card.is-stored-ball:active {
      cursor: pointer;
    }
    .bag-item-card.is-stored-ball::before {
      content: "";
      position: absolute;
      inset: 8px;
      border-radius: 999px;
      background: radial-gradient(circle, var(--stored-ball-glow, rgba(114,196,255,.2)), transparent 72%);
      opacity: .72;
      z-index: 0;
      pointer-events: none;
    }
    .bag-item-card.is-stored-ball img {
      position: relative;
      z-index: 1;
      width: 84%;
      height: 84%;
      filter: drop-shadow(0 10px 12px rgba(55,45,76,.16));
    }
    .bag-item-card.is-stored-ball .bag-item-qty {
      background: rgba(61, 39, 82, .92);
      z-index: 2;
    }
    .bag-item-card.is-stored-ball .bag-item-name {
      left: 5px;
      right: 5px;
      bottom: 5px;
      padding: 4px 6px;
      background: rgba(255,255,255,.9);
      color: #4b3161;
      font-weight: 900;
      z-index: 2;
    }
    .bag-item-card.is-stored-ball.is-tier-common { --stored-ball-glow: rgba(114,196,255,.32); }
    .bag-item-card.is-stored-ball.is-tier-rare { --stored-ball-glow: rgba(255,95,159,.34); }
    .bag-item-card.is-stored-ball.is-tier-epic { --stored-ball-glow: rgba(186,124,255,.36); }
    .bag-item-card.is-stored-ball.is-tier-legendary { --stored-ball-glow: rgba(255,212,44,.38); }
    .bag-item-card.is-stored-ball.is-tier-mythic { --stored-ball-glow: rgba(255,240,168,.44); }
    .bag-item-card.is-stored-ball.is-holding {
      transform: scale(.97);
      box-shadow: 0 12px 22px rgba(73, 48, 95, .2);
    }
    .bag-item-card.is-stored-ball[data-opening="1"] {
      opacity: .72;
      pointer-events: none;
    }
    .bag-stored-ball-hold {
      position: absolute;
      left: 6px;
      right: 6px;
      bottom: 22px;
      display: grid;
      place-items: center;
      border-radius: 999px;
      padding: 4px 6px;
      background: linear-gradient(180deg, rgba(123, 91, 214, .98), rgba(86, 65, 173, .98));
      color: #fff;
      font-size: 9px;
      font-weight: 900;
      letter-spacing: .04em;
      box-shadow: 0 5px 10px rgba(73, 48, 95, .18);
      pointer-events: none;
      z-index: 2;
    }
    .bag-item-card.is-stored-ball.is-holding .bag-stored-ball-hold {
      background: linear-gradient(180deg, rgba(255, 127, 184, .98), rgba(128, 176, 255, .98));
    }
    .role-groups {
      display: grid;
      align-content: start;
      gap: 10px;
    }
    .role-card {
      display: grid;
      gap: 7px;
      padding: 12px;
      border: 1px solid var(--bag-line);
      border-radius: 15px;
      background: rgba(255,255,255,.84);
      box-shadow: 0 8px 13px rgba(73, 48, 95, .07);
    }
    .role-card-head {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 10px;
      align-items: center;
    }
    .role-card strong {
      min-width: 0;
      color: #3d2752;
      font-size: 17px;
      font-weight: 800;
    }
    .role-duration {
      margin-left: 5px;
      color: #7f4b1c;
      font-size: 13px;
      white-space: nowrap;
    }
    .pill {
      display: inline-flex;
      align-items: center;
      width: fit-content;
      min-height: 24px;
      border-radius: 999px;
      padding: 0 9px;
      background: #fff0c6;
      color: #7f4b1c;
      font-size: 12px;
      font-weight: 800;
      white-space: nowrap;
    }
    .role-entry-list {
      display: grid;
      gap: 5px;
      margin: 0;
      padding: 0;
      list-style: none;
    }
    .role-entry-list li {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 8px;
      align-items: center;
      padding: 7px 9px;
      border-radius: 10px;
      background: rgba(246, 240, 255, .78);
      color: var(--bag-muted);
      font-size: 12px;
      line-height: 1.35;
    }
    .role-entry-list b {
      color: #4b3161;
      font-weight: 800;
    }
    .role-section {
      display: grid;
      gap: 10px;
    }
    .role-section-head {
      display: grid;
      gap: 4px;
      padding: 0 2px;
    }
    .role-section-head strong {
      color: #3d2752;
      font-size: 17px;
      font-weight: 800;
    }
    .role-section-head span {
      color: var(--bag-muted);
      font-size: 12px;
      line-height: 1.5;
    }
    .role-badge-sheet-card {
      display: grid;
      gap: 12px;
      padding: 14px;
      border: 1px solid var(--bag-line);
      border-radius: 18px;
      background: rgba(255,255,255,.9);
      box-shadow: 0 10px 18px rgba(73, 48, 95, .08);
    }
    .role-badge-main {
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
      gap: 12px;
      align-items: center;
    }
    .role-badge-hero {
      width: 54px;
      height: 54px;
      display: grid;
      place-items: center;
      position: relative;
      border-radius: 16px;
      background: linear-gradient(180deg, rgba(248,243,255,.96), rgba(239,247,255,.84));
      box-shadow: inset 0 1px 0 rgba(255,255,255,.92);
      overflow: hidden;
    }
    .role-badge-hero img {
      width: 38px;
      height: 38px;
      object-fit: contain;
      filter: drop-shadow(0 8px 8px rgba(55,45,76,.12));
    }
    .role-badge-hero-shell {
      width: 100%;
      height: 100%;
      object-fit: contain;
      filter: drop-shadow(0 8px 8px rgba(55,45,76,.12));
    }
    .role-badge-hero-icon {
      position: absolute;
      width: 44%;
      height: 44%;
      object-fit: contain;
      border-radius: 14px;
      filter: drop-shadow(0 8px 8px rgba(55,45,76,.14));
    }
    .role-badge-hero-fallback {
      position: absolute;
      width: 44%;
      height: 44%;
      display: grid;
      place-items: center;
      border-radius: 14px;
      background: linear-gradient(180deg, rgba(255,247,227,.98), rgba(255,236,180,.96));
      color: #6a447f;
      font-size: 18px;
      font-weight: 900;
      box-shadow: 0 8px 12px rgba(55,45,76,.12);
    }
    .role-badge-dot {
      position: absolute;
      left: 6px;
      bottom: 6px;
      width: 12px;
      height: 12px;
      border-radius: 999px;
      background: #8b91a1;
      border: 2px solid rgba(255,255,255,.9);
    }
    .role-badge-copy {
      min-width: 0;
      display: grid;
      gap: 7px;
    }
    .role-badge-copy strong {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px 8px;
      color: #3d2752;
      font-size: 17px;
      font-weight: 800;
      line-height: 1.3;
    }
    .role-badge-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      align-items: center;
    }
    .role-badge-copy p {
      margin: 0;
      color: var(--bag-muted);
      font-size: 12px;
      line-height: 1.55;
    }
    .role-badge-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .role-action {
      border: 0;
      border-radius: 999px;
      min-height: 38px;
      padding: 0 14px;
      background: rgba(241, 235, 252, .9);
      color: #5a4080;
      font: inherit;
      font-size: 12px;
      font-weight: 800;
      cursor: pointer;
      white-space: nowrap;
    }
    .role-action.is-primary {
      background: linear-gradient(135deg, var(--bag-purple), var(--bag-pink));
      color: #fff;
    }
    .role-badge-drawer {
      position: fixed;
      inset: 0;
      z-index: 26;
      display: none;
      padding: 14px;
      background: rgba(22, 15, 37, .28);
      backdrop-filter: blur(8px);
    }
    .role-badge-drawer.is-open {
      display: grid;
    }
    .role-badge-sheet {
      min-height: 0;
      max-height: min(72svh, 540px);
      overflow: hidden;
      display: grid;
      grid-template-rows: auto minmax(0, 1fr) auto;
      gap: 12px;
      align-self: center;
      border-radius: 22px;
      padding: 14px;
      background: rgba(255,255,255,.98);
      box-shadow: 0 24px 60px rgba(20, 12, 34, .22);
    }
    .role-badge-sheet-head {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      align-items: center;
      gap: 10px;
    }
    .role-badge-sheet-head h2 {
      margin: 0;
      color: #3d2752;
      font-size: 22px;
      line-height: 1.2;
    }
    .role-badge-sheet-body {
      min-height: 0;
      overflow: auto;
      display: grid;
      align-content: start;
      gap: 12px;
    }
    .role-badge-sheet-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      align-items: center;
    }
    .role-badge-facts {
      display: grid;
      gap: 8px;
    }
    .role-badge-fact {
      display: grid;
      gap: 3px;
      padding: 10px 12px;
      border-radius: 14px;
      background: rgba(246,240,255,.74);
      border: 1px solid rgba(99, 68, 128, .1);
    }
    .role-badge-fact strong {
      color: #3d2752;
      font-size: 12px;
      font-weight: 800;
    }
    .role-badge-fact span {
      color: var(--bag-muted);
      font-size: 12px;
      line-height: 1.5;
    }
    .role-badge-description {
      display: grid;
      gap: 8px;
    }
    .role-badge-description p {
      margin: 0;
      padding: 10px 12px;
      border-radius: 14px;
      background: rgba(246,240,255,.52);
      color: var(--bag-muted);
      font-size: 12px;
      line-height: 1.6;
    }
    .role-badge-sheet-actions {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 8px;
    }
    .role-badge-sheet-actions .role-action {
      width: 100%;
      min-height: 42px;
      justify-content: center;
    }
    .role-search-drawer {
      position: fixed;
      inset: 0;
      z-index: 28;
      display: none;
      padding: 14px;
      background: rgba(22, 15, 37, .28);
      backdrop-filter: blur(8px);
    }
    .role-search-drawer.is-open {
      display: grid;
    }
    .role-search-card {
      min-height: 0;
      overflow: hidden;
      display: grid;
      grid-template-rows: auto auto auto minmax(0, 1fr);
      gap: 10px;
      align-self: center;
      border-radius: 22px;
      padding: 14px;
      background: rgba(255,255,255,.98);
      box-shadow: 0 24px 60px rgba(20, 12, 34, .22);
    }
    .role-search-head {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      align-items: center;
      gap: 10px;
    }
    .role-search-head h2 {
      margin: 0;
      color: #3d2752;
      font-size: 22px;
      line-height: 1.2;
    }
    .role-search-close {
      width: 36px;
      height: 36px;
      border: 0;
      border-radius: 999px;
      background: #f1eef7;
      color: #4b3161;
      font-size: 22px;
      cursor: pointer;
    }
    .role-search-target {
      display: grid;
      gap: 5px;
      padding: 12px;
      border-radius: 16px;
      background: rgba(246,240,255,.74);
      border: 1px solid rgba(99, 68, 128, .1);
    }
    .role-search-target strong {
      color: #3d2752;
      font-size: 15px;
    }
    .role-search-target span {
      color: var(--bag-muted);
      font-size: 12px;
      line-height: 1.5;
    }
    .role-search-input {
      width: 100%;
      border: 1px solid rgba(99, 68, 128, .18);
      border-radius: 16px;
      padding: 12px 14px;
      background: rgba(255,255,255,.96);
      color: #3d2752;
      font: inherit;
      font-size: 14px;
    }
    .role-search-status {
      color: var(--bag-muted);
      font-size: 12px;
      line-height: 1.5;
    }
    .role-search-results {
      min-height: 0;
      overflow: auto;
      display: grid;
      align-content: start;
      gap: 8px;
    }
    .role-search-result {
      display: grid;
      grid-template-columns: auto minmax(0, 1fr) auto;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 10px 11px;
      border: 1px solid rgba(99, 68, 128, .1);
      border-radius: 14px;
      background: rgba(247, 244, 255, .88);
      color: inherit;
      text-align: left;
      cursor: pointer;
    }
    .role-search-result img {
      width: 38px;
      height: 38px;
      border-radius: 999px;
      object-fit: cover;
      background: rgba(255,255,255,.72);
    }
    .role-search-result strong {
      display: block;
      color: #3d2752;
      font-size: 14px;
      line-height: 1.3;
    }
    .role-search-result span {
      display: block;
      color: var(--bag-muted);
      font-size: 11px;
      line-height: 1.45;
    }
    .bag-toast {
      position: fixed;
      left: 50%;
      bottom: calc(env(safe-area-inset-bottom, 0px) + 18px);
      transform: translateX(-50%);
      z-index: 32;
      max-width: min(92vw, 420px);
      min-height: 42px;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 0 16px;
      border-radius: 999px;
      background: rgba(55, 37, 72, .96);
      color: #fff;
      font-size: 13px;
      font-weight: 700;
      box-shadow: 0 18px 28px rgba(20, 12, 34, .22);
    }
    .bag-toast.is-visible {
      display: inline-flex;
    }
    body.is-stored-ball-reward-open,
    body.is-stored-ball-menu-open {
      overflow: hidden;
    }
    .stored-ball-menu-modal,
    .stored-ball-reward-modal {
      position: fixed;
      inset: 0;
      z-index: 36;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px 16px;
      background: rgba(24, 12, 38, .56);
      opacity: 0;
      pointer-events: none;
      transition: opacity .2s ease;
    }
    .stored-ball-menu-modal {
      z-index: 35;
      align-items: end;
      padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 16px);
    }
    .stored-ball-menu-modal.is-open,
    .stored-ball-reward-modal.is-open {
      opacity: 1;
      pointer-events: auto;
    }
    .stored-ball-menu-card,
    .stored-ball-reward-card {
      position: relative;
      width: min(92vw, 368px);
      padding: 26px 22px 22px;
      border-radius: 26px;
      border: 1px solid rgba(255,255,255,.48);
      background:
        radial-gradient(circle at top, rgba(255,255,255,.72), rgba(255,255,255,0) 44%),
        linear-gradient(180deg, rgba(255,248,255,.98), rgba(247,238,255,.96));
      box-shadow: 0 28px 52px rgba(32, 12, 52, .28);
      text-align: center;
    }
    .stored-ball-menu-card {
      width: min(92vw, 390px);
      padding: 22px;
      text-align: left;
    }
    .stored-ball-menu-close,
    .stored-ball-reward-close {
      position: absolute;
      top: 12px;
      right: 12px;
      width: 34px;
      height: 34px;
      border: 0;
      border-radius: 50%;
      background: rgba(88, 58, 118, .12);
      color: #5d3f79;
      font-size: 22px;
      line-height: 1;
      cursor: pointer;
    }
    .stored-ball-menu-kicker {
      margin: 0 0 6px;
      color: #a44f96;
      font-size: 12px;
      font-weight: 900;
      letter-spacing: .12em;
      text-transform: uppercase;
    }
    .stored-ball-menu-title {
      margin: 0;
      padding-right: 34px;
      color: #342045;
      font-size: 23px;
      line-height: 1.18;
      font-weight: 900;
    }
    .stored-ball-menu-subtitle {
      margin: 8px 0 0;
      color: #6f5a83;
      font-size: 13px;
      line-height: 1.45;
      font-weight: 750;
    }
    .stored-ball-detail-list {
      margin-top: 14px;
      display: grid;
      gap: 8px;
    }
    .stored-ball-detail-row {
      display: grid;
      grid-template-columns: minmax(88px, .72fr) 1fr;
      gap: 10px;
      align-items: start;
      padding: 10px 12px;
      border-radius: 14px;
      background: rgba(110, 76, 150, .08);
      color: #4f3a64;
      font-size: 12px;
      line-height: 1.35;
      font-weight: 800;
    }
    .stored-ball-detail-row strong {
      color: #8a4d96;
      font-weight: 900;
    }
    .stored-ball-detail-row span {
      min-width: 0;
      overflow-wrap: anywhere;
      text-align: right;
    }
    .stored-ball-menu-actions {
      margin-top: 16px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .stored-ball-menu-action {
      min-height: 46px;
      border: 0;
      border-radius: 16px;
      background: rgba(132, 90, 191, .12);
      color: #563573;
      font: inherit;
      font-size: 14px;
      font-weight: 900;
      cursor: pointer;
    }
    .stored-ball-menu-action.is-primary {
      background: linear-gradient(135deg, var(--bag-purple), var(--bag-pink));
      color: #fff;
      box-shadow: 0 18px 28px rgba(143, 72, 163, .2);
    }
    .stored-ball-menu-action:disabled {
      opacity: .58;
      cursor: wait;
    }
    .stored-ball-reward-visual {
      width: 122px;
      height: 122px;
      margin: 2px auto 16px;
      border-radius: 32px;
      display: grid;
      place-items: center;
      background:
        radial-gradient(circle at top, rgba(255,255,255,.92), rgba(255,255,255,.35) 36%, rgba(188,149,255,.16) 70%),
        linear-gradient(180deg, rgba(132,86,198,.18), rgba(255,129,203,.14));
      box-shadow:
        inset 0 1px 0 rgba(255,255,255,.8),
        0 18px 34px rgba(58, 28, 90, .16);
      overflow: hidden;
    }
    .stored-ball-reward-visual img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: cover;
    }
    .stored-ball-reward-fallback {
      width: 82px;
      height: 82px;
      border-radius: 24px;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, rgba(117,225,255,.92), rgba(188,126,255,.94));
      color: #fff;
      font-size: 34px;
      font-weight: 900;
      text-transform: uppercase;
      box-shadow: 0 14px 28px rgba(69, 48, 109, .18);
    }
    .stored-ball-reward-kicker {
      margin: 0 0 6px;
      color: #a44f96;
      font-size: 12px;
      font-weight: 900;
      letter-spacing: .14em;
      text-transform: uppercase;
    }
    .stored-ball-reward-title {
      margin: 0;
      color: #342045;
      font-size: 25px;
      line-height: 1.18;
      font-weight: 900;
      word-break: break-word;
    }
    .stored-ball-reward-badges {
      margin-top: 12px;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 8px;
    }
    .stored-ball-reward-badge {
      border-radius: 999px;
      padding: 7px 12px;
      background: rgba(132, 90, 191, .12);
      color: #5a3a80;
      font-size: 12px;
      font-weight: 800;
    }
    .stored-ball-reward-note {
      margin: 14px 0 0;
      color: #6a5480;
      font-size: 13px;
      line-height: 1.55;
      font-weight: 700;
    }
    .stored-ball-reward-action {
      width: 100%;
      min-height: 48px;
      margin-top: 18px;
      border: 0;
      border-radius: 16px;
      background: linear-gradient(135deg, var(--bag-purple), var(--bag-pink));
      color: #fff;
      font: inherit;
      font-size: 15px;
      font-weight: 900;
      cursor: pointer;
      box-shadow: 0 18px 28px rgba(143, 72, 163, .22);
    }
    .history-tabs {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
    }
    .history-filter {
      border: 1px solid var(--bag-line);
      border-radius: 999px;
      padding: 8px 6px;
      color: var(--bag-muted);
      background: rgba(246,240,255,.72);
      font: inherit;
      font-size: 13px;
      font-weight: 800;
    }
    .history-filter.is-active {
      color: #fff;
      background: linear-gradient(135deg, var(--bag-purple), var(--bag-pink));
    }
    .history-filter-note {
      margin: 1px 2px 0;
      color: var(--bag-muted);
      font-size: 11px;
      font-weight: 700;
      line-height: 1.45;
    }
    .history-list {
      min-height: 0;
      overflow: auto;
      display: grid;
      align-content: start;
      gap: 8px;
    }
    .history-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 10px;
      align-items: center;
      border-radius: 14px;
      padding: 10px 11px;
      background: rgba(247, 244, 255, .88);
      border: 1px solid rgba(99, 68, 128, .1);
    }
    .history-row strong {
      display: block;
      color: #3d2752;
      font-size: 14px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .history-row small,
    .history-source {
      display: block;
      color: var(--bag-muted);
      font-size: 11px;
    }
    .pill.is-in { background: rgba(135, 230, 183, .28); color: #1f6f4f; }
    .pill.is-out { background: rgba(255, 164, 184, .3); color: #8d2742; }
    .pill.is-move { background: rgba(255, 211, 128, .34); color: #8a5b12; }
    .empty {
      margin: 34px auto 0;
      max-width: 280px;
      text-align: center;
      color: var(--bag-muted);
      font-size: 17px;
      font-weight: 700;
    }
    @media (max-width: 480px) {
      .bag-shell { padding: 10px; }
      .bag-grid { gap: 5px; }
      .bag-item-name { display: none; }
      .role-card-head { grid-template-columns: 1fr; }
      .role-badge-main { grid-template-columns: 1fr; align-items: start; }
      .role-badge-hero { width: 50px; height: 50px; }
      .bag-head { gap: 6px; }
      .history-open { padding: 0 10px; font-size: 11px; }
      .bag-tabs { gap: 7px; padding: 6px; }
      .bag-tab { min-height: 38px; padding: 10px 18px; font-size: 14px; }
      .role-badge-sheet { align-self: stretch; max-height: none; }
      .role-badge-sheet-actions { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>
  <main class="bag-shell<?= $hideChrome ? ' is-chrome-hidden' : '' ?>">
    <?php if (!$hideChrome): ?>
    <header class="bag-head<?= $isEmbed ? ' is-embed-head' : '' ?>">
      <?php if (!$isEmbed): ?>
      <div class="bag-title">
        <h1><?= bagEsc($t['bag']) ?></h1>
      </div>
      <?php endif; ?>
      <nav class="bag-tabs" aria-label="Bag tabs">
        <button class="bag-tab<?= $initialTab === 'items' ? ' is-active' : '' ?>" type="button" data-tab="items"><?= bagEsc($t['items']) ?></button>
        <button class="bag-tab<?= $initialTab === 'roles' ? ' is-active' : '' ?>" type="button" data-tab="roles"><?= bagEsc($t['roles']) ?></button>
      </nav>
      <button class="history-open<?= $initialTab === 'history' ? ' is-active' : '' ?>" type="button" data-history-open data-tab="history" aria-pressed="<?= $initialTab === 'history' ? 'true' : 'false' ?>"><?= bagEsc($t['history']) ?></button>
    </header>
    <?php endif; ?>
    <section class="bag-content">
      <section class="bag-panel<?= $initialTab === 'items' ? ' is-active' : '' ?>" data-panel="items">
        <div class="bag-grid" data-bag-grid>
          <?php for ($slot = 0; $slot < $visibleSlotCount; $slot++): $itemIndex = $gridSlots[$slot]; $item = $itemIndex !== null ? ($items[$itemIndex] ?? null) : null; ?>
            <div class="bag-slot" data-slot="<?= bagEsc($slot) ?>">
              <?php if ($item): ?>
                <?php $roleBadge = is_array($item['roleBadge'] ?? null) ? $item['roleBadge'] : null; ?>
                <?php $storedBallItem = ($item['type'] ?? '') === 'stored_ball'; ?>
                <?php $storedBallMeta = $storedBallItem && is_array($item['meta'] ?? null) ? $item['meta'] : []; ?>
                <?php $storedBallId = $storedBallItem ? (int) ($item['storedBallId'] ?? 0) : 0; ?>
                <?php
                  $roleBadgeFallback = '';
                  if ($roleBadge) {
                      $roleBadgeFallback = trim((string) ($roleBadge['seriesBadge'] ?? ''));
                      if ($roleBadgeFallback === '') {
                          $roleBadgeFallback = bagInitialGlyph((string) ($roleBadge['name'] ?? $item['name'] ?? 'B'));
                      }
                  }
                  $cardClasses = 'bag-item-card';
                  if ($roleBadge) {
                      $cardClasses .= ' is-role-badge';
                  }
                  if ($storedBallItem) {
                      $cardClasses .= ' is-stored-ball is-tier-' . bagStoredBallTierId((string) ($storedBallMeta['tierId'] ?? ''));
                  }
                  $cardTitle = $storedBallItem
                      ? trim((string) ($storedBallMeta['cardTitle'] ?? $t['stored_ball_name']))
                      : (string) ($item['name'] ?? '');
                ?>
                <article
                  class="<?= bagEsc($cardClasses) ?>"
                  draggable="<?= $storedBallItem ? 'false' : 'true' ?>"
                  data-inventory-id="<?= bagEsc($item['inventoryId']) ?>"
                  data-item-type="<?= bagEsc($item['type']) ?>"
                  data-effect-type="<?= bagEsc($item['effectType'] ?? '') ?>"
                  <?= $roleBadge ? 'data-role-badge-item data-role-badge="' . bagJsonAttr($roleBadge) . '" role="button" tabindex="0" aria-label="' . bagEsc($roleBadge['name'] . ' · ' . $t['role_badges']) . '"' : '' ?>
                  <?= $storedBallItem ? 'data-stored-ball-card data-stored-ball-id="' . bagEsc($storedBallId) . '" data-stored-ball-details="' . bagJsonAttr(is_array($storedBallMeta['details'] ?? null) ? $storedBallMeta['details'] : []) . '" data-no-drag="1" role="button" tabindex="0" aria-label="' . bagEsc($cardTitle . ' · ' . $t['hold_to_open']) . '"' : '' ?>
                  title="<?= bagEsc($cardTitle) ?>"
                >
                  <?php if ($roleBadge): ?>
                    <div class="bag-role-badge-visual" aria-hidden="true">
                      <img class="bag-role-badge-shell" src="<?= bagEsc($roleBadgeBaseIcon) ?>" alt="">
                      <?php if (!empty($roleBadge['roleIconUrl'])): ?>
                        <img class="bag-role-badge-icon" src="<?= bagEsc($roleBadge['roleIconUrl']) ?>" alt="">
                      <?php else: ?>
                        <span class="bag-role-badge-fallback"><?= bagEsc($roleBadgeFallback) ?></span>
                      <?php endif; ?>
                      <span class="bag-role-badge-dot" style="<?= $roleBadge['roleColor'] ? 'background:' . bagEsc($roleBadge['roleColor']) : '' ?>"></span>
                    </div>
                  <?php else: ?>
                    <img src="<?= bagEsc($item['image']) ?>" alt="">
                  <?php endif; ?>
                  <?php if (!$storedBallItem || (int) ($item['quantity'] ?? 0) > 1): ?>
                    <span class="bag-item-qty"><?= bagEsc(str_replace('{count}', number_format($item['quantity']), $t['qty'])) ?></span>
                  <?php endif; ?>
                  <?php if ($storedBallItem): ?>
                    <span class="bag-stored-ball-hold"><?= bagEsc((string) ($storedBallMeta['holdLabel'] ?? $t['hold_to_open'])) ?></span>
                  <?php elseif (!empty($item['isUsable'])): ?>
                    <button class="bag-item-use" type="button" data-bag-use-item="<?= bagEsc($item['inventoryId']) ?>"><?= bagEsc(($item['effectType'] ?? '') === 'loot_box' ? $t['open_item'] : $t['use_item']) ?></button>
                  <?php endif; ?>
                  <span class="bag-item-name"><?= bagEsc($item['name']) ?></span>
                </article>
              <?php endif; ?>
            </div>
          <?php endfor; ?>
        </div>
      </section>
      <section class="bag-panel<?= $initialTab === 'roles' ? ' is-active' : '' ?>" data-panel="roles">
        <?php if ($userId !== '' && !$roleGroups): ?><div class="empty"><?= bagEsc($t['empty_roles']) ?></div><?php endif; ?>
        <?php if ($roleGroups): ?>
          <section class="role-section">
            <div class="role-section-head">
              <strong><?= bagEsc($t['active_roles']) ?></strong>
              <span><?= bagEsc($t['active_roles_hint']) ?></span>
            </div>
            <div class="role-groups">
              <?php foreach ($roleGroups as $group): $entries = bagVisibleRoleEntries($group); $summary = bagRoleGroupSummary($group, $t); $showEntries = !($summary === $t['permanent'] && count($entries) <= 1); ?>
                <article class="role-card">
                  <div class="role-card-head">
                    <strong><?= bagEsc($group['roleName'] ?? 'Role') ?><span class="role-duration"><?= bagEsc($summary) ?></span></strong>
                  </div>
                  <?php if ($entries && $showEntries): ?>
                    <ul class="role-entry-list">
                      <?php foreach ($entries as $index => $entry): ?>
                        <li>
                          <span>
                            <?php if (count($entries) > 1): ?><b><?= bagEsc(bagDurationName((int) ($entry['durationDays'] ?? 0), $t)) ?></b> · <?php endif; ?>
                            <?= bagEsc(bagRoleEntryStateLabel($entry, $t)) ?>
                            <?php if (!empty($entry['grantedAt'])): ?> · <?= bagEsc($t['role_received_at']) ?> <?= bagEsc((string) $entry['grantedAt']) ?><?php endif; ?>
                            <?php if (!empty($entry['segmentStartAt']) && ($entry['state'] ?? '') === 'queued'): ?> · <?= bagEsc($t['role_starts_at']) ?> <?= bagEsc((string) $entry['segmentStartAt']) ?><?php endif; ?>
                            <?php if (!empty($entry['expireAt'])): ?> · <?= bagEsc($t['role_ends_at']) ?> <?= bagEsc((string) $entry['expireAt']) ?><?php endif; ?>
                          </span>
                          <span class="pill"><?= bagEsc(bagRoleEntryBadge($entry, $t)) ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>
      </section>
      <section class="bag-panel is-history-panel<?= $initialTab === 'history' ? ' is-active' : '' ?>" data-panel="history">
        <div class="history-tabs" aria-label="History filters">
          <button class="history-filter is-active" data-history-filter="in" aria-pressed="true"><?= bagEsc($t['income']) ?></button>
          <button class="history-filter is-active" data-history-filter="out" aria-pressed="true"><?= bagEsc($t['spend']) ?></button>
          <button class="history-filter is-active" data-history-filter="move" aria-pressed="true"><?= bagEsc($t['movement']) ?></button>
        </div>
        <p class="history-filter-note"><?= bagEsc($t['history_filter_hint']) ?></p>
        <div class="history-list">
          <?php if (!$history): ?><div class="empty"><?= bagEsc($t['empty_history']) ?></div><?php endif; ?>
          <?php foreach ($history as $row): ?>
            <?php $historyKind = (string) ($row['kind'] ?? 'in'); ?>
            <article class="history-row" data-history-kind="<?= bagEsc($row['kind'] ?? 'in') ?>">
              <div>
                <strong><?= bagEsc($row['title']) ?></strong>
                <small><?= bagEsc($row['date']) ?></small>
                <span class="history-source"><?= bagEsc($row['source']) ?></span>
              </div>
              <span class="pill <?= $historyKind === 'out' ? 'is-out' : ($historyKind === 'move' ? 'is-move' : 'is-in') ?>"><?= bagEsc($row['detail']) ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </section>
  </main>

  <aside class="role-badge-drawer" data-role-badge-drawer aria-hidden="true">
    <section class="role-badge-sheet">
      <header class="role-badge-sheet-head">
        <h2><?= bagEsc($t['role_badges']) ?></h2>
        <button class="role-search-close" type="button" data-role-badge-close aria-label="<?= bagEsc($t['role_badge_modal_close']) ?>">×</button>
      </header>
      <div class="role-badge-sheet-body">
        <div class="role-badge-sheet-card">
          <div class="role-badge-main">
            <div class="role-badge-hero">
              <img class="role-badge-hero-shell" src="<?= bagEsc($roleBadgeBaseIcon) ?>" alt="">
              <img id="roleBadgeDrawerIcon" class="role-badge-hero-icon" src="" alt="" hidden>
              <span id="roleBadgeDrawerFallback" class="role-badge-hero-fallback" hidden></span>
              <span id="roleBadgeDrawerDot" class="role-badge-dot"></span>
            </div>
            <div class="role-badge-copy">
              <strong id="roleBadgeDrawerName">Role badge</strong>
              <div id="roleBadgeDrawerBadges" class="role-badge-sheet-badges"></div>
            </div>
          </div>
          <div id="roleBadgeDrawerFacts" class="role-badge-facts"></div>
          <div id="roleBadgeDrawerDescription" class="role-badge-description" hidden></div>
        </div>
      </div>
      <div class="role-badge-sheet-actions">
        <button class="role-action is-primary" type="button" data-role-badge-self><?= bagEsc($t['apply_self']) ?></button>
        <button class="role-action" type="button" data-role-badge-friend><?= bagEsc($t['apply_friend']) ?></button>
      </div>
    </section>
  </aside>

  <aside class="role-search-drawer" data-role-search-drawer aria-hidden="true">
    <section class="role-search-card">
      <header class="role-search-head">
        <h2><?= bagEsc($t['role_badge_modal_title']) ?></h2>
        <button class="role-search-close" type="button" data-role-search-close aria-label="<?= bagEsc($t['role_badge_modal_close']) ?>">×</button>
      </header>
      <div class="role-search-target">
        <strong id="roleSearchBadgeName">Role badge</strong>
        <span><?= bagEsc($t['search_friend_hint']) ?></span>
      </div>
      <input id="roleSearchInput" class="role-search-input" type="search" placeholder="<?= bagEsc($t['search_placeholder']) ?>" autocomplete="off">
      <div>
        <div id="roleSearchStatus" class="role-search-status"><?= bagEsc($t['search_submit_hint']) ?></div>
        <div id="roleSearchResults" class="role-search-results"></div>
      </div>
    </section>
  </aside>

  <aside class="stored-ball-menu-modal" data-stored-ball-menu aria-hidden="true">
    <section class="stored-ball-menu-card" role="dialog" aria-modal="true" aria-labelledby="storedBallMenuTitle">
      <button class="stored-ball-menu-close" type="button" data-stored-ball-menu-close aria-label="<?= bagEsc($t['role_badge_modal_close']) ?>">×</button>
      <p class="stored-ball-menu-kicker"><?= bagEsc($t['stored_ball_menu_title']) ?></p>
      <h2 id="storedBallMenuTitle" class="stored-ball-menu-title"><?= bagEsc($t['stored_ball_menu_title']) ?></h2>
      <p id="storedBallMenuSubtitle" class="stored-ball-menu-subtitle"><?= bagEsc($t['stored_ball_details_hint']) ?></p>
      <div id="storedBallDetailList" class="stored-ball-detail-list" hidden></div>
      <div class="stored-ball-menu-actions">
        <button class="stored-ball-menu-action is-primary" type="button" data-stored-ball-menu-open><?= bagEsc($t['stored_ball_menu_open']) ?></button>
        <button class="stored-ball-menu-action" type="button" data-stored-ball-menu-details><?= bagEsc($t['stored_ball_menu_details']) ?></button>
      </div>
    </section>
  </aside>

  <aside class="stored-ball-reward-modal" data-stored-ball-reward aria-hidden="true">
    <section class="stored-ball-reward-card" role="dialog" aria-modal="true" aria-labelledby="storedBallRewardTitle">
      <button class="stored-ball-reward-close" type="button" data-stored-ball-reward-close aria-label="<?= bagEsc($t['role_badge_modal_close']) ?>">×</button>
      <div class="stored-ball-reward-visual" aria-hidden="true">
        <img id="storedBallRewardImage" src="" alt="" hidden>
        <span id="storedBallRewardFallback" class="stored-ball-reward-fallback" hidden></span>
      </div>
      <p class="stored-ball-reward-kicker"><?= bagEsc($t['stored_ball_reward_title']) ?></p>
      <h2 id="storedBallRewardTitle" class="stored-ball-reward-title"><?= bagEsc($t['stored_ball_reward_fallback_name']) ?></h2>
      <div id="storedBallRewardBadges" class="stored-ball-reward-badges" hidden></div>
      <p id="storedBallRewardNote" class="stored-ball-reward-note"><?= bagEsc($t['stored_ball_reward_item_note']) ?></p>
      <button class="stored-ball-reward-action" type="button" data-stored-ball-reward-close><?= bagEsc($t['stored_ball_reward_close']) ?></button>
    </section>
  </aside>

  <div id="bagToast" class="bag-toast" aria-live="polite"></div>

  <script>
    const bagInitialTab = <?= json_encode($initialTab) ?>;
    const bagInitialSection = <?= json_encode($initialSection) ?>;
    const roleBadgeBaseIcon = <?= json_encode($roleBadgeBaseIcon) ?>;
    const permanentText = <?= json_encode($t['permanent']) ?>;
    const daysNameText = <?= json_encode($t['days_name']) ?>;
    const roleBadgeSeriesText = <?= json_encode($t['role_badge_series']) ?>;
    const roleBadgeDurationText = <?= json_encode($t['role_badge_duration']) ?>;
    const roleBadgeQtyText = <?= json_encode($t['role_badge_qty']) ?>;
    const roleBadgeQtyLabelText = <?= json_encode($t['role_badge_qty_label']) ?>;
    const selectMemberText = <?= json_encode($t['select_member']) ?>;
    const escHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;"
    }[char]));
    const compactText = (value) => String(value ?? "").trim();
    const formatDurationText = (badge) => {
      const label = compactText(badge?.durationLabel);
      if (label) return label;
      const days = Number(badge?.durationDays || 0);
      return days > 0 ? daysNameText.replace("{count}", days.toLocaleString()) : permanentText;
    };
    const renderFact = (label, value) => {
      const text = compactText(value);
      return text ? `<div class="role-badge-fact"><strong>${escHtml(label)}</strong><span>${escHtml(text)}</span></div>` : "";
    };
    const badgeFallbackText = (badge) => {
      const seriesBadge = compactText(badge?.seriesBadge);
      if (seriesBadge) return seriesBadge;
      const chars = Array.from(compactText(badge?.name || ""));
      return chars[0] || "B";
    };
    const normalizeBagTab = (tab) => tab === "roles" ? "roles" : (tab === "history" ? "history" : "items");
    window.bagSetTab = (tab) => {
      const normalized = normalizeBagTab(tab);
      document.querySelectorAll("[data-tab]").forEach((item) => {
        const active = item.dataset.tab === normalized;
        item.classList.toggle("is-active", active);
        item.setAttribute("aria-pressed", active ? "true" : "false");
      });
      document.querySelectorAll("[data-panel]").forEach((panel) => panel.classList.toggle("is-active", panel.dataset.panel === normalized));
    };
    document.querySelectorAll("[data-tab]").forEach((button) => {
      button.addEventListener("click", () => window.bagSetTab(button.dataset.tab));
    });
    window.bagSetTab(bagInitialTab);
    if (bagInitialTab === "items" && ["role-badges", "role-badge-items"].includes(bagInitialSection)) {
      window.requestAnimationFrame(() => {
        document.querySelector("[data-role-badge-item]")?.scrollIntoView({ block: "center", behavior: "smooth" });
      });
    }

    window.bagOpenHistory = () => window.bagSetTab("history");
    const applyHistoryFilters = () => {
      const activeFilters = new Set(Array.from(document.querySelectorAll("[data-history-filter].is-active")).map((button) => button.dataset.historyFilter || "in"));
      document.querySelectorAll("[data-history-filter]").forEach((button) => {
        button.setAttribute("aria-pressed", button.classList.contains("is-active") ? "true" : "false");
      });
      document.querySelectorAll("[data-history-kind]").forEach((row) => {
        const kind = row.dataset.historyKind || "in";
        row.style.display = activeFilters.has(kind) ? "" : "none";
      });
    };
    document.querySelectorAll("[data-history-filter]").forEach((button) => {
      button.addEventListener("click", () => {
        button.classList.toggle("is-active");
        applyHistoryFilters();
      });
    });
    applyHistoryFilters();

    const postMove = (inventoryId, slot) => fetch(window.location.href, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "move_item", inventoryId, slot })
    }).catch(() => null);
    const currentUserId = <?= json_encode($userId) ?>;
    const roleBadgeAppliedText = <?= json_encode($t['role_badge_applied']) ?>;
    const roleBadgeAppliedRemainingText = <?= json_encode($t['role_badge_applied_remaining']) ?>;
    const roleBadgeAppliedUsedUpText = <?= json_encode($t['role_badge_applied_used_up']) ?>;
    const roleBadgeUsingText = <?= json_encode($t['role_badge_using']) ?>;
    const searchLoadingText = <?= json_encode($t['search_loading']) ?>;
    const searchEmptyText = <?= json_encode($t['search_empty']) ?>;
    const searchHintText = <?= json_encode($t['search_submit_hint']) ?>;
    const itemUsedText = <?= json_encode($t['item_used']) ?>;
    const itemOpenedText = <?= json_encode($t['item_opened']) ?>;
    const storedBallOpenedText = <?= json_encode($t['stored_ball_opened']) ?>;
    const holdToOpenText = <?= json_encode($t['hold_to_open']) ?>;
    const storedBallMenuTitleText = <?= json_encode($t['stored_ball_menu_title']) ?>;
    const storedBallDetailsTitleText = <?= json_encode($t['stored_ball_details_title']) ?>;
    const storedBallDetailsHintText = <?= json_encode($t['stored_ball_details_hint']) ?>;
    const storedBallDetailStoredAtText = <?= json_encode($t['stored_ball_detail_stored_at']) ?>;
    const storedBallDetailSpinAtText = <?= json_encode($t['stored_ball_detail_spin_at']) ?>;
    const storedBallDetailTierText = <?= json_encode($t['stored_ball_detail_tier']) ?>;
    const storedBallDetailSpinByText = <?= json_encode($t['stored_ball_detail_spin_by']) ?>;
    const storedBallDetailCountText = <?= json_encode($t['stored_ball_detail_count']) ?>;
    const storedBallDetailDrawIdText = <?= json_encode($t['stored_ball_detail_draw_id']) ?>;
    const storedBallDetailFreeSpinText = <?= json_encode($t['stored_ball_detail_free_spin']) ?>;
    const storedBallDetailUnknownText = <?= json_encode($t['stored_ball_detail_unknown']) ?>;
    const storedBallDetailButtonText = <?= json_encode($t['stored_ball_detail_button']) ?>;
    const storedBallRewardItemNoteText = <?= json_encode($t['stored_ball_reward_item_note']) ?>;
    const storedBallRewardItemQtyNoteText = <?= json_encode($t['stored_ball_reward_item_qty_note']) ?>;
    const storedBallRewardRoleNoteText = <?= json_encode($t['stored_ball_reward_role_note']) ?>;
    const storedBallRewardCurrencyNoteText = <?= json_encode($t['stored_ball_reward_currency_note']) ?>;
    const storedBallRewardCurrencyQtyNoteText = <?= json_encode($t['stored_ball_reward_currency_qty_note']) ?>;
    const storedBallRewardFallbackNameText = <?= json_encode($t['stored_ball_reward_fallback_name']) ?>;
    const toast = document.getElementById("bagToast");
    const roleBadgeDrawer = document.querySelector("[data-role-badge-drawer]");
    const roleBadgeDrawerName = document.getElementById("roleBadgeDrawerName");
    const roleBadgeDrawerBadges = document.getElementById("roleBadgeDrawerBadges");
    const roleBadgeDrawerFacts = document.getElementById("roleBadgeDrawerFacts");
    const roleBadgeDrawerDescription = document.getElementById("roleBadgeDrawerDescription");
    const roleBadgeDrawerIcon = document.getElementById("roleBadgeDrawerIcon");
    const roleBadgeDrawerFallback = document.getElementById("roleBadgeDrawerFallback");
    const roleBadgeDrawerDot = document.getElementById("roleBadgeDrawerDot");
    const roleSearchDrawer = document.querySelector("[data-role-search-drawer]");
    const roleSearchInput = document.getElementById("roleSearchInput");
    const roleSearchStatus = document.getElementById("roleSearchStatus");
    const roleSearchResults = document.getElementById("roleSearchResults");
    const roleSearchBadgeName = document.getElementById("roleSearchBadgeName");
    const storedBallMenuModal = document.querySelector("[data-stored-ball-menu]");
    const storedBallMenuTitle = document.getElementById("storedBallMenuTitle");
    const storedBallMenuSubtitle = document.getElementById("storedBallMenuSubtitle");
    const storedBallDetailList = document.getElementById("storedBallDetailList");
    const storedBallMenuOpenButton = document.querySelector("[data-stored-ball-menu-open]");
    const storedBallMenuDetailsButton = document.querySelector("[data-stored-ball-menu-details]");
    const storedBallRewardModal = document.querySelector("[data-stored-ball-reward]");
    const storedBallRewardImage = document.getElementById("storedBallRewardImage");
    const storedBallRewardFallback = document.getElementById("storedBallRewardFallback");
    const storedBallRewardTitle = document.getElementById("storedBallRewardTitle");
    const storedBallRewardBadges = document.getElementById("storedBallRewardBadges");
    const storedBallRewardNote = document.getElementById("storedBallRewardNote");
    let activeRoleBadge = null;
    let activeStoredBallCard = null;
    let activeStoredBallDetails = {};
    let roleSearchTimer = 0;
    let storedBallRewardReloadOnClose = false;

    const showToast = (message) => {
      if (!toast) return;
      toast.textContent = message || "";
      toast.classList.add("is-visible");
      window.clearTimeout(showToast.timerId || 0);
      showToast.timerId = window.setTimeout(() => {
        toast.classList.remove("is-visible");
      }, 2200);
    };
    const hideToast = () => {
      if (!toast) return;
      window.clearTimeout(showToast.timerId || 0);
      toast.classList.remove("is-visible");
      toast.textContent = "";
    };

    const updateRoleBadgeCard = (inventoryId, remainingQuantity) => {
      const normalizedInventoryId = String(Number(inventoryId || 0));
      const card = document.querySelector(`[data-role-badge-item][data-inventory-id="${normalizedInventoryId}"]`);
      if (!card) return;

      if (remainingQuantity <= 0) {
        if (activeRoleBadge && Number(activeRoleBadge.inventoryId || 0) === Number(inventoryId || 0)) {
          closeRoleSearch();
          closeRoleBadgeDrawer();
        }
        card.remove();
        return;
      }

      const qtyText = <?= json_encode($t['qty']) ?>.replace("{count}", Number(remainingQuantity).toLocaleString());
      card.querySelector(".bag-item-qty")?.replaceChildren(document.createTextNode(qtyText));

      try {
        const badge = JSON.parse(card.dataset.roleBadge || "{}");
        badge.quantity = remainingQuantity;
        card.dataset.roleBadge = JSON.stringify(badge);
        if (activeRoleBadge && Number(activeRoleBadge.inventoryId || 0) === Number(inventoryId || 0)) {
          activeRoleBadge.quantity = remainingQuantity;
          roleBadgeDrawerFacts.innerHTML = [
            renderFact(roleBadgeSeriesText, activeRoleBadge?.seriesName || ""),
            renderFact(roleBadgeDurationText, formatDurationText(activeRoleBadge)),
            renderFact(roleBadgeQtyLabelText, roleBadgeQtyText.replace("{count}", Number(remainingQuantity).toLocaleString())),
          ].filter(Boolean).join("");
        }
      } catch (error) {
        // Ignore local UI sync issues and let the reload reconcile state.
      }
    };

    const closeRoleSearch = () => {
      if (!roleSearchDrawer) return;
      roleSearchDrawer.classList.remove("is-open");
      roleSearchDrawer.setAttribute("aria-hidden", "true");
      roleSearchResults.innerHTML = "";
      roleSearchStatus.textContent = searchHintText;
      roleSearchInput.value = "";
    };

    const closeRoleBadgeDrawer = () => {
      activeRoleBadge = null;
      if (!roleBadgeDrawer) return;
      roleBadgeDrawer.classList.remove("is-open");
      roleBadgeDrawer.setAttribute("aria-hidden", "true");
    };

    const openRoleBadgeDrawer = (badge) => {
      activeRoleBadge = badge;
      if (!roleBadgeDrawer) return;

      const badges = [];
      if (compactText(badge?.seriesBadge)) {
        badges.push(`<span class="pill">${escHtml(badge.seriesBadge)}</span>`);
      }
      if (compactText(badge?.roleTier)) {
        badges.push(`<span class="pill">Tier ${escHtml(badge.roleTier)}</span>`);
      }

      const durationText = formatDurationText(badge);
      const qtyText = roleBadgeQtyText.replace("{count}", Number(badge?.quantity || 0).toLocaleString());
      const details = [];
      if (compactText(badge?.detailText)) {
        details.push(`<p>${escHtml(badge.detailText)}</p>`);
      }
      if (compactText(badge?.conditionText)) {
        details.push(`<p>${escHtml(badge.conditionText)}</p>`);
      }

      roleBadgeDrawerName.textContent = badge?.name || badge?.itemName || "Role badge";
      roleBadgeDrawerBadges.innerHTML = badges.join("");
      roleBadgeDrawerFacts.innerHTML = [
        renderFact(roleBadgeSeriesText, badge?.seriesName || ""),
        renderFact(roleBadgeDurationText, durationText),
        renderFact(roleBadgeQtyLabelText, qtyText),
      ].filter(Boolean).join("");
      roleBadgeDrawerDescription.innerHTML = details.join("");
      roleBadgeDrawerDescription.hidden = details.length === 0;
      const iconUrl = compactText(badge?.roleIconUrl);
      roleBadgeDrawerIcon.hidden = iconUrl === "";
      if (iconUrl !== "") {
        roleBadgeDrawerIcon.src = iconUrl;
      } else {
        roleBadgeDrawerIcon.removeAttribute("src");
      }
      roleBadgeDrawerFallback.hidden = iconUrl !== "";
      roleBadgeDrawerFallback.textContent = badgeFallbackText(badge);
      roleBadgeDrawerDot.style.background = compactText(badge?.roleColor) || "#8b91a1";
      roleBadgeDrawer.classList.add("is-open");
      roleBadgeDrawer.setAttribute("aria-hidden", "false");
    };

    const openRoleSearch = (badge) => {
      activeRoleBadge = badge;
      if (!roleSearchDrawer) return;
      roleSearchBadgeName.textContent = badge?.name || "Role badge";
      roleSearchDrawer.classList.add("is-open");
      roleSearchDrawer.setAttribute("aria-hidden", "false");
      roleSearchStatus.textContent = searchHintText;
      roleSearchResults.innerHTML = "";
      roleSearchInput.value = "";
      roleSearchInput.focus();
    };

    const applyRoleBadge = async (inventoryId, targetUserId) => {
      showToast(roleBadgeUsingText);
      const response = await fetch(window.location.href, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ action: "apply_role_badge", inventoryId, targetUserId })
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || !payload?.ok) {
        throw new Error(payload?.message || "apply failed");
      }
      const name = payload?.target?.displayName || "";
      const remainingQuantity = Number(payload?.remainingQuantity ?? -1);
      updateRoleBadgeCard(inventoryId, remainingQuantity);
      if (remainingQuantity === 0) {
        showToast(roleBadgeAppliedUsedUpText.replace("{name}", name));
      } else if (remainingQuantity > 0) {
        showToast(
          roleBadgeAppliedRemainingText
            .replace("{name}", name)
            .replace("{count}", remainingQuantity.toLocaleString())
        );
      } else {
        showToast(roleBadgeAppliedText.replace("{name}", name));
      }
      window.setTimeout(() => window.location.reload(), 900);
    };

    const useBagItem = async (inventoryId, effectType = "") => {
      const response = await fetch(window.location.href, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ action: "use_item", inventoryId })
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || !payload?.ok) {
        throw new Error(payload?.message || "use item failed");
      }
      showToast(effectType === "loot_box" ? itemOpenedText : itemUsedText);
      window.setTimeout(() => window.location.reload(), 700);
    };

    const openStoredBall = async (storedBallId) => {
      const response = await fetch(window.location.href, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ action: "open_stored_ball", storedBallId })
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || !payload?.ok) {
        throw new Error(payload?.message || "open stored ball failed");
      }
      return payload?.result || null;
    };

    const parseStoredBallDetails = (card) => {
      try {
        const parsed = JSON.parse(card?.dataset?.storedBallDetails || "{}");
        return parsed && typeof parsed === "object" ? parsed : {};
      } catch (error) {
        return {};
      }
    };

    const formatStoredBallDate = (value) => {
      const raw = compactText(value);
      if (!raw) return storedBallDetailUnknownText;
      const numeric = Number(raw);
      const date = Number.isFinite(numeric) && numeric > 0
        ? new Date(numeric * 1000)
        : new Date(raw.replace(" ", "T"));
      if (Number.isNaN(date.getTime())) return raw;
      return date.toLocaleString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };

    const storedBallSpinSpendText = (details) => {
      if (details?.usedFreeSpin) {
        const source = compactText(details?.freeSpinSource);
        return source ? `${storedBallDetailFreeSpinText} · ${source}` : storedBallDetailFreeSpinText;
      }
      const cost = Number(details?.cost || 0);
      const costPerSpin = Number(details?.costPerSpin || 0);
      const currency = compactText(details?.currency);
      if (cost > 0 && currency) return `${cost.toLocaleString()} ${currency}`;
      if (costPerSpin > 0 && currency) return `${costPerSpin.toLocaleString()} ${currency}`;
      const buttonId = Number(details?.buttonId || 0);
      return buttonId > 0 ? storedBallDetailButtonText.replace("{buttonId}", buttonId.toLocaleString()) : storedBallDetailUnknownText;
    };

    const renderStoredBallDetails = (details) => {
      const tierName = compactText(details?.tierName || details?.tierId) || storedBallMenuTitleText;
      const rows = [
        [storedBallDetailStoredAtText, formatStoredBallDate(details?.storedAt || details?.createdAt)],
        [storedBallDetailSpinAtText, formatStoredBallDate(details?.spinCommittedAt)],
        [storedBallDetailTierText, tierName],
        [storedBallDetailSpinByText, storedBallSpinSpendText(details)],
        [storedBallDetailCountText, Math.max(1, Number(details?.count || 1)).toLocaleString()],
        [storedBallDetailDrawIdText, compactText(details?.drawId) || storedBallDetailUnknownText]
      ];
      return rows.map(([label, value]) => `
        <div class="stored-ball-detail-row">
          <strong>${escHtml(label)}</strong>
          <span>${escHtml(value)}</span>
        </div>
      `).join("");
    };

    const closeStoredBallMenu = () => {
      if (!storedBallMenuModal) return;
      storedBallMenuModal.classList.remove("is-open");
      storedBallMenuModal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("is-stored-ball-menu-open");
      if (storedBallMenuOpenButton) storedBallMenuOpenButton.disabled = false;
      activeStoredBallCard = null;
      activeStoredBallDetails = {};
    };

    const showStoredBallDetails = () => {
      if (!storedBallDetailList || !storedBallMenuTitle || !storedBallMenuSubtitle) return;
      storedBallMenuTitle.textContent = storedBallDetailsTitleText;
      storedBallMenuSubtitle.textContent = storedBallDetailsHintText;
      storedBallDetailList.hidden = false;
      storedBallDetailList.innerHTML = renderStoredBallDetails(activeStoredBallDetails);
    };

    const openStoredBallMenu = (card) => {
      if (!storedBallMenuModal || !card || card.dataset.opening === "1") return;
      hideToast();
      activeStoredBallCard = card;
      activeStoredBallDetails = parseStoredBallDetails(card);
      const title = compactText(card.getAttribute("title")) || storedBallMenuTitleText;
      if (storedBallMenuTitle) storedBallMenuTitle.textContent = title;
      if (storedBallMenuSubtitle) storedBallMenuSubtitle.textContent = storedBallDetailsHintText;
      if (storedBallDetailList) {
        storedBallDetailList.hidden = true;
        storedBallDetailList.innerHTML = "";
      }
      if (storedBallMenuOpenButton) storedBallMenuOpenButton.disabled = false;
      storedBallMenuModal.classList.add("is-open");
      storedBallMenuModal.setAttribute("aria-hidden", "false");
      document.body.classList.add("is-stored-ball-menu-open");
    };

    const openActiveStoredBallFromMenu = async () => {
      const card = activeStoredBallCard;
      const storedBallId = Number(card?.dataset?.storedBallId || 0);
      if (!card || !storedBallId || card.dataset.opening === "1") return;
      card.dataset.opening = "1";
      if (storedBallMenuOpenButton) storedBallMenuOpenButton.disabled = true;
      try {
        const result = await openStoredBall(storedBallId);
        card.remove();
        closeStoredBallMenu();
        openStoredBallRewardModal(result);
      } catch (error) {
        delete card.dataset.opening;
        if (storedBallMenuOpenButton) storedBallMenuOpenButton.disabled = false;
        showToast(error instanceof Error ? error.message : "open stored ball failed");
      }
    };

    const storedBallRewardDurationText = (prize) => {
      if (compactText(prize?.type) !== "role") return "";
      const label = compactText(prize?.roleDurationLabel);
      if (label) return label;
      const days = Number(prize?.roleDurationDays || 0);
      return days > 0 ? daysNameText.replace("{count}", days.toLocaleString()) : permanentText;
    };

    const storedBallRewardDisplayName = (prize) => {
      const baseName = compactText(prize?.name || prize?.shortName) || storedBallRewardFallbackNameText;
      const duration = storedBallRewardDurationText(prize);
      return duration ? `${baseName} ${duration}` : baseName;
    };

    const storedBallRewardBadgeList = (prize) => {
      const badges = [];
      if (compactText(prize?.tierName)) badges.push(compactText(prize.tierName));
      if (compactText(prize?.roleSeriesName)) badges.push(compactText(prize.roleSeriesName));
      if (compactText(prize?.roleTier)) badges.push(`Tier ${compactText(prize.roleTier)}`);
      if (compactText(prize?.type) === "role") {
        const duration = storedBallRewardDurationText(prize);
        if (duration) badges.push(duration);
      }
      return badges.slice(0, 4);
    };

    const storedBallRewardNoteText = (result, prize) => {
      const settlement = result?.settlement || {};
      if (settlement?.rewardType === "role") {
        return storedBallRewardRoleNoteText;
      }
      if (settlement?.rewardType === "currency") {
        const amount = Number(settlement?.reward?.wallet?.amount || prize?.amount || prize?.quantity || 0);
        const unit = compactText(prize?.unitCode || prize?.currency || "");
        if (amount > 0 && unit) {
          return storedBallRewardCurrencyQtyNoteText
            .replace("{count}", amount.toLocaleString())
            .replace("{unit}", unit);
        }
        return storedBallRewardCurrencyNoteText;
      }
      const quantity = Number(settlement?.reward?.item?.quantityDelta || prize?.quantity || 0);
      if (quantity > 1) {
        return storedBallRewardItemQtyNoteText.replace("{count}", quantity.toLocaleString());
      }
      return storedBallRewardItemNoteText;
    };

    const closeStoredBallRewardModal = ({ reload = storedBallRewardReloadOnClose } = {}) => {
      if (!storedBallRewardModal) return;
      const shouldReload = Boolean(reload);
      storedBallRewardReloadOnClose = false;
      storedBallRewardModal.classList.remove("is-open");
      storedBallRewardModal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("is-stored-ball-reward-open");
      if (shouldReload) {
        window.location.reload();
      }
    };

    const openStoredBallRewardModal = (result) => {
      hideToast();
      if (!storedBallRewardModal || !storedBallRewardTitle || !storedBallRewardNote) {
        showToast(storedBallOpenedText);
        window.setTimeout(() => window.location.reload(), 700);
        return;
      }

      const prize = result?.prize || {};
      const displayName = storedBallRewardDisplayName(prize);
      const fallbackLabel = badgeFallbackText({ name: displayName });
      const imageSrc = compactText(prize?.roleIconUrl || prize?.image);

      storedBallRewardTitle.textContent = displayName;
      storedBallRewardNote.textContent = storedBallRewardNoteText(result, prize);

      const badges = storedBallRewardBadgeList(prize);
      if (storedBallRewardBadges) {
        storedBallRewardBadges.hidden = badges.length === 0;
        storedBallRewardBadges.innerHTML = badges.map((badge) => (
          `<span class="stored-ball-reward-badge">${escHtml(badge)}</span>`
        )).join("");
      }

      if (storedBallRewardImage && storedBallRewardFallback) {
        storedBallRewardImage.hidden = !imageSrc;
        storedBallRewardImage.alt = displayName;
        storedBallRewardImage.src = imageSrc || "";
        storedBallRewardImage.onerror = () => {
          storedBallRewardImage.hidden = true;
          storedBallRewardFallback.hidden = false;
          storedBallRewardFallback.textContent = fallbackLabel;
        };
        storedBallRewardFallback.hidden = Boolean(imageSrc);
        storedBallRewardFallback.textContent = fallbackLabel;
      }

      storedBallRewardReloadOnClose = true;
      storedBallRewardModal.classList.add("is-open");
      storedBallRewardModal.setAttribute("aria-hidden", "false");
      document.body.classList.add("is-stored-ball-reward-open");
    };

    storedBallMenuModal?.addEventListener("click", (event) => {
      if (event.target === storedBallMenuModal) {
        closeStoredBallMenu();
      }
    });
    document.querySelectorAll("[data-stored-ball-menu-close]").forEach((button) => {
      button.addEventListener("click", closeStoredBallMenu);
    });
    storedBallMenuDetailsButton?.addEventListener("click", showStoredBallDetails);
    storedBallMenuOpenButton?.addEventListener("click", () => {
      void openActiveStoredBallFromMenu();
    });

    storedBallRewardModal?.addEventListener("click", (event) => {
      if (event.target === storedBallRewardModal) {
        closeStoredBallRewardModal();
      }
    });
    document.querySelectorAll("[data-stored-ball-reward-close]").forEach((button) => {
      button.addEventListener("click", () => {
        closeStoredBallRewardModal();
      });
    });
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && storedBallMenuModal?.classList.contains("is-open")) {
        event.preventDefault();
        closeStoredBallMenu();
        return;
      }
      if (event.key === "Escape" && storedBallRewardModal?.classList.contains("is-open")) {
        event.preventDefault();
        closeStoredBallRewardModal();
      }
    });

    const renderRoleSearchResults = (members) => {
      roleSearchResults.innerHTML = members.map((member) => `
        <button class="role-search-result" type="button" data-target-user-id="${escHtml(member.userId)}">
          <img src="${escHtml(member.avatarUrl || "")}" alt="">
          <span>
            <strong>${escHtml(member.displayName || member.userName || member.userId)}</strong>
            <span>${escHtml(member.userName ? `@${member.userName}` : member.userId)}</span>
          </span>
          <span class="pill">${escHtml(selectMemberText)}</span>
        </button>
      `).join("");
      roleSearchStatus.textContent = members.length ? "" : searchEmptyText;
    };

    const searchMembers = async (query) => {
      roleSearchStatus.textContent = searchLoadingText;
      roleSearchResults.innerHTML = "";
      const response = await fetch(window.location.href, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ action: "search_members", query })
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || !payload?.ok) {
        throw new Error(payload?.message || "search failed");
      }
      renderRoleSearchResults(Array.isArray(payload.members) ? payload.members : []);
    };

    let draggedCard = null;
    document.querySelectorAll('.bag-item-card[draggable="true"]').forEach((card) => {
      card.addEventListener("dragstart", (event) => {
        draggedCard = card;
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData("text/plain", card.dataset.inventoryId || "");
      });
      card.addEventListener("dragend", () => {
        draggedCard = null;
        card.dataset.justDragged = "1";
        window.setTimeout(() => card.removeAttribute("data-just-dragged"), 160);
        document.querySelectorAll(".bag-slot").forEach((slot) => slot.classList.remove("is-drop-target"));
      });
    });
    document.querySelectorAll(".bag-slot").forEach((slot) => {
      slot.addEventListener("dragover", (event) => {
        if (!draggedCard) return;
        const existing = slot.querySelector(".bag-item-card");
        if (existing && existing !== draggedCard && existing.dataset.noDrag === "1") return;
        event.preventDefault();
        slot.classList.add("is-drop-target");
      });
      slot.addEventListener("dragleave", () => slot.classList.remove("is-drop-target"));
      slot.addEventListener("drop", (event) => {
        event.preventDefault();
        slot.classList.remove("is-drop-target");
        if (!draggedCard) return;
        const fromSlot = draggedCard.closest(".bag-slot");
        const existing = slot.querySelector(".bag-item-card");
        if (existing && existing !== draggedCard && existing.dataset.noDrag === "1") return;
        if (existing && fromSlot) {
          fromSlot.appendChild(existing);
          postMove(existing.dataset.inventoryId || "", Number(fromSlot.dataset.slot || 0));
        }
        slot.appendChild(draggedCard);
        postMove(draggedCard.dataset.inventoryId || "", Number(slot.dataset.slot || 0));
      });
    });

    document.querySelectorAll("[data-role-badge-item]").forEach((card) => {
      let badge = null;
      try {
        badge = JSON.parse(card.dataset.roleBadge || "{}");
      } catch (error) {
        badge = null;
      }
      if (!badge) return;

      const openDrawer = () => {
        if (card.dataset.justDragged === "1") return;
        openRoleBadgeDrawer(badge);
      };

      card.addEventListener("click", openDrawer);
      card.addEventListener("keydown", (event) => {
        if (event.key !== "Enter" && event.key !== " ") return;
        event.preventDefault();
        openDrawer();
      });
    });

    document.querySelector("[data-role-badge-self]")?.addEventListener("click", async () => {
      if (!activeRoleBadge) return;
      try {
        await applyRoleBadge(activeRoleBadge.inventoryId, currentUserId);
      } catch (error) {
        showToast(error?.message || "apply failed");
      }
    });
    document.querySelectorAll("[data-bag-use-item]").forEach((button) => {
      button.addEventListener("click", async (event) => {
        event.preventDefault();
        event.stopPropagation();
        const card = button.closest(".bag-item-card");
        const inventoryId = Number(button.dataset.bagUseItem || 0);
        const effectType = String(card?.dataset.effectType || "");
        if (!inventoryId) return;
        button.disabled = true;
        try {
          await useBagItem(inventoryId, effectType);
        } catch (error) {
          showToast(error instanceof Error ? error.message : "use item failed");
          button.disabled = false;
        }
      });
    });
    document.querySelectorAll("[data-stored-ball-card]").forEach((card) => {
      const storedBallId = Number(card.dataset.storedBallId || 0);
      let holdTimer = 0;
      let holdTriggered = false;

      const resetHold = () => {
        if (holdTimer) {
          window.clearTimeout(holdTimer);
          holdTimer = 0;
        }
        card.classList.remove("is-holding");
      };

      const triggerMenu = () => {
        if (!storedBallId || card.dataset.opening === "1") return;
        holdTriggered = true;
        holdTimer = 0;
        card.classList.remove("is-holding");
        openStoredBallMenu(card);
      };

      card.addEventListener("pointerdown", (event) => {
        if (event.button !== undefined && event.button !== 0) return;
        if (card.dataset.opening === "1") return;
        holdTriggered = false;
        resetHold();
        card.classList.add("is-holding");
        holdTimer = window.setTimeout(() => {
          triggerMenu();
        }, 550);
      });

      ["pointerup", "pointerleave", "pointercancel"].forEach((eventName) => {
        card.addEventListener(eventName, () => {
          if (!holdTriggered) {
            resetHold();
          }
        });
      });

      card.addEventListener("contextmenu", (event) => {
        event.preventDefault();
        triggerMenu();
      });

      card.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (holdTriggered) {
          holdTriggered = false;
          return;
        }
        showToast(holdToOpenText);
      });

      card.addEventListener("keydown", async (event) => {
        if (event.key !== "Enter" && event.key !== " ") return;
        event.preventDefault();
        triggerMenu();
      });
    });

    document.querySelector("[data-role-badge-friend]")?.addEventListener("click", () => {
      if (!activeRoleBadge) return;
      openRoleSearch(activeRoleBadge);
    });

    document.querySelector("[data-role-badge-close]")?.addEventListener("click", closeRoleBadgeDrawer);
    roleBadgeDrawer?.addEventListener("click", (event) => {
      if (event.target === roleBadgeDrawer) {
        closeRoleBadgeDrawer();
      }
    });

    document.querySelector("[data-role-search-close]")?.addEventListener("click", closeRoleSearch);
    roleSearchDrawer?.addEventListener("click", (event) => {
      if (event.target === roleSearchDrawer) {
        closeRoleSearch();
      }
    });

    roleSearchInput?.addEventListener("input", () => {
      const query = roleSearchInput.value.trim();
      window.clearTimeout(roleSearchTimer);
      if (!query) {
        roleSearchStatus.textContent = searchHintText;
        roleSearchResults.innerHTML = "";
        return;
      }
      roleSearchTimer = window.setTimeout(async () => {
        try {
          await searchMembers(query);
        } catch (error) {
          roleSearchStatus.textContent = error?.message || searchEmptyText;
        }
      }, 180);
    });

    roleSearchResults?.addEventListener("click", async (event) => {
      const button = event.target.closest("[data-target-user-id]");
      if (!button || !activeRoleBadge) return;
      try {
        await applyRoleBadge(activeRoleBadge.inventoryId, button.dataset.targetUserId || "");
      } catch (error) {
        showToast(error?.message || "apply failed");
      } finally {
        closeRoleSearch();
      }
    });
  </script>
</body>
</html>
