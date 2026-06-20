<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

Auth::requireUser();

$guildId = (string) Bootstrap::config('discord.guildId', '');
$userId = Input::str('userId');
if (!$userId) {
    Response::error('userId is required.', 422);
}

$asOfInput = Input::str('asOf');
$asOf = null;
if ($asOfInput) {
    $asOfTs = strtotime($asOfInput);
    if ($asOfTs === false) {
        Response::error('Invalid asOf timestamp.', 422);
    }
    $asOf = date('Y-m-d H:i:s', $asOfTs);
}

AuditLogger::access('member_detail_read', 'user', $userId, ['view' => 'profile_drawer', 'asOf' => $asOf], 'sensitive');

$dateFilter = $asOf ? ['asOf' => $asOf] : [];
$decodeDate = static function (mixed $value): ?string {
    $text = trim((string) ($value ?? ''));
    if ($text === '') {
        return null;
    }
    $ts = strtotime($text);
    return $ts === false ? null : date('Y-m-d H:i:s', $ts);
};
$decorateRole = static function (array $role): array {
    $role['roleIconUrl'] = DiscordAssets::roleIcon($role['roleId'], $role['iconHash'] ?? null, 64);
    $role['permissionsDecoded'] = DiscordPermissions::decode($role['permissions'] ?? null);
    $role['contextConfidence'] = 'current';
    $role['contextSourceDate'] = null;
    return $role;
};

$member = Database::fetch(
    'SELECT m.*, u.userName, u.globalName, u.discriminator, u.avatarHash, u.bannerHash, u.accentColor, u.isBot,
            u.metadataJson AS userMetadataJson, m.metadataJson AS memberMetadataJson,
            COALESCE(m.nickName, u.globalName, u.userName, m.userId) AS displayName
       FROM tbl_member m
       INNER JOIN tbl_user u ON u.userId = m.userId
      WHERE m.guildId = :guildId AND m.userId = :userId',
    ['guildId' => $guildId, 'userId' => $userId]
);

if (!$member) {
    $member = Database::fetch(
        'SELECT NULL AS memberId,
                :guildId AS guildId,
                u.userId,
                NULL AS nickName,
                NULL AS guildAvatarHash,
                NULL AS joinedAt,
                NULL AS premiumSince,
                NULL AS communicationDisabledUntil,
                NULL AS lastSyncDate,
                0 AS roleCount,
                0 AS isPending,
                0 AS isDelete,
                0 AS isActive,
                NULL AS memberMetadataJson,
                u.userName, u.globalName, u.discriminator, u.avatarHash, u.bannerHash, u.accentColor, u.isBot,
                u.metadataJson AS userMetadataJson,
                COALESCE(u.globalName, u.userName, u.userId) AS displayName
           FROM tbl_user u
          WHERE u.userId = :userId',
        ['guildId' => $guildId, 'userId' => $userId]
    );
}

if (!$member) {
    Response::error('Member not found. Run Server Snapshot first.', 404);
}

$walletUnits = class_exists('ShopUnitService') ? ShopUnitService::units(true) : [];
$walletBalances = class_exists('ShopUnitService') ? ShopUnitService::walletBalances($guildId, $userId, false) : [];
foreach ($walletUnits as &$walletUnit) {
    $unitCode = (string) ($walletUnit['unitCode'] ?? '');
    $walletBalances[$unitCode] = (int) ($walletBalances[$unitCode] ?? 0);
    $walletUnit['balanceAmount'] = $walletBalances[$unitCode];
}
unset($walletUnit);

$roles = Database::fetchAll(
    'SELECT r.*
       FROM tbl_member_role mr
       INNER JOIN tbl_role r ON r.roleId = mr.roleId
      WHERE mr.guildId = :guildId AND mr.userId = :userId AND mr.isActive = 1
      ORDER BY r.rolePosition DESC, r.roleName ASC',
    ['guildId' => $guildId, 'userId' => $userId]
);
$roles = array_map($decorateRole, $roles);

$latestJoin = Database::fetch(
    'SELECT eventDate
       FROM tbl_raw_event
      WHERE guildId = :guildId AND userId = :userId AND eventType = "GUILD_MEMBER_ADD"' . ($asOf ? ' AND eventDate <= :asOf' : '') . '
      ORDER BY eventDate DESC, rawEventId DESC
      LIMIT 1',
    ['guildId' => $guildId, 'userId' => $userId] + $dateFilter
);
$latestLeave = Database::fetch(
    'SELECT eventDate
       FROM tbl_raw_event
      WHERE guildId = :guildId AND userId = :userId AND eventType = "GUILD_MEMBER_REMOVE"' . ($asOf ? ' AND eventDate <= :asOf' : '') . '
      ORDER BY eventDate DESC, rawEventId DESC
      LIMIT 1',
    ['guildId' => $guildId, 'userId' => $userId] + $dateFilter
);
$latestBanAdd = Database::fetch(
    'SELECT eventDate
       FROM tbl_raw_event
      WHERE guildId = :guildId AND userId = :userId AND eventType = "GUILD_BAN_ADD"' . ($asOf ? ' AND eventDate <= :asOf' : '') . '
      ORDER BY eventDate DESC, rawEventId DESC
      LIMIT 1',
    ['guildId' => $guildId, 'userId' => $userId] + $dateFilter
);
$latestBanRemove = Database::fetch(
    'SELECT eventDate
       FROM tbl_raw_event
      WHERE guildId = :guildId AND userId = :userId AND eventType = "GUILD_BAN_REMOVE"' . ($asOf ? ' AND eventDate <= :asOf' : '') . '
      ORDER BY eventDate DESC, rawEventId DESC
      LIMIT 1',
    ['guildId' => $guildId, 'userId' => $userId] + $dateFilter
);

$joinDate = $latestJoin['eventDate'] ?? null;
$leaveDate = $latestLeave['eventDate'] ?? null;
$leftAsOf = $leaveDate && (!$joinDate || strcmp((string) $leaveDate, (string) $joinDate) > 0);
$timeoutUntil = $decodeDate($member['communicationDisabledUntil'] ?? null);
$nowOrAsOf = $asOf ?? date('Y-m-d H:i:s');
$banDate = $latestBanAdd['eventDate'] ?? null;
$unbanDate = $latestBanRemove['eventDate'] ?? null;
$isBanned = $banDate && (!$unbanDate || strcmp((string) $banDate, (string) $unbanDate) > 0);

$member['walletBalances'] = $walletBalances;
$member['walletUnits'] = $walletUnits;
$member['balanceAmount'] = (int) ($walletBalances['coin'] ?? 0);
$member['currentDisplayName'] = (string) ($member['displayName'] ?? $userId);
$member['currentNickName'] = $member['nickName'] ?? null;
$member['currentAvatarHash'] = $member['avatarHash'] ?? null;
$member['currentGuildAvatarHash'] = $member['guildAvatarHash'] ?? null;
$member['currentBannerHash'] = $member['bannerHash'] ?? null;
$member['currentCommunicationDisabledUntil'] = $member['communicationDisabledUntil'] ?? null;
$member['historicalDisplayName'] = null;
$member['historicalNickName'] = null;
$member['historicalAvatarHash'] = null;
$member['historicalGuildAvatarHash'] = null;
$member['historicalBannerHash'] = null;
$member['historicalCommunicationDisabledUntil'] = null;
$member['historicalContext'] = [
    'mode' => 'current',
    'asOf' => $asOf,
    'profileConfidence' => $asOf ? 'current_filtered' : 'current',
    'profileSourceType' => 'current',
    'profileSourceDate' => $member['lastSyncDate'] ?? null,
    'roleConfidence' => 'current',
    'roleSourceType' => 'current',
    'roleSourceDate' => null,
];
$member['bannerConfidence'] = 'current';
$member['asOfStatus'] = $leftAsOf ? 'left_as_of' : 'active_as_of';
$member['asOfJoinDate'] = $joinDate;
$member['asOfLeaveDate'] = $leaveDate;
$member['asOfTimeoutUntil'] = $timeoutUntil;
$member['asOfTimeoutConfidence'] = $timeoutUntil ? 'current' : 'unavailable';
$member['asOfTimeoutActive'] = $timeoutUntil !== null && strcmp($timeoutUntil, $nowOrAsOf) >= 0;
$member['bannedAsOf'] = $isBanned ? 1 : 0;
$member['banEventDate'] = $banDate;
$member['banConfidence'] = $banDate ? 'raw_event' : 'unavailable';
$member['lastPunishEventType'] = $isBanned ? 'GUILD_BAN_ADD' : null;
$member['lastPunishEventDate'] = $banDate;
$member['lastPunishActor'] = null;
$member['lastNicknameUpdateDate'] = null;
$member['lastNicknameActor'] = null;
$member['inviteAttribution'] = null;
try {
    $inviteAttribution = Database::fetch(
        'SELECT a.*,
                COALESCE(inviterMember.nickName, inviterUser.globalName, inviterUser.userName, a.inviterName, a.inviterUserId) AS inviterDisplayName,
                inviterUser.userName AS inviterUserName,
                inviterUser.globalName AS inviterGlobalName,
                inviterUser.avatarHash AS inviterAvatarHash
           FROM tbl_member_join_invite_attribution a
      LEFT JOIN tbl_user inviterUser ON inviterUser.userId = a.inviterUserId
      LEFT JOIN tbl_member inviterMember ON inviterMember.guildId = a.guildId AND inviterMember.userId = a.inviterUserId
          WHERE a.guildId = :guildId
            AND a.joinedUserId = :userId' . ($asOf ? ' AND COALESCE(a.joinEventDate, a.sourceMessageDate) <= :asOf' : '') . '
          ORDER BY COALESCE(a.joinEventDate, a.sourceMessageDate) DESC, a.joinInviteAttributionId DESC
          LIMIT 1',
        ['guildId' => $guildId, 'userId' => $userId] + $dateFilter
    );
    if ($inviteAttribution) {
        $member['inviteAttribution'] = [
            'inviteType' => (string) ($inviteAttribution['inviteType'] ?? 'unknown'),
            'isVanity' => (string) ($inviteAttribution['inviteType'] ?? '') === 'vanity',
            'isOauth' => (string) ($inviteAttribution['inviteType'] ?? '') === 'oauth',
            'inviterUserId' => (string) ($inviteAttribution['inviterUserId'] ?? ''),
            'inviterName' => (string) ($inviteAttribution['inviterName'] ?? ''),
            'inviteCount' => $inviteAttribution['inviteCount'] !== null ? (int) $inviteAttribution['inviteCount'] : null,
            'matchStatus' => (string) ($inviteAttribution['matchStatus'] ?? ''),
            'confidence' => (string) ($inviteAttribution['confidence'] ?? ''),
            'sourceMessageId' => (string) ($inviteAttribution['sourceMessageId'] ?? ''),
            'sourceMessageDate' => (string) ($inviteAttribution['sourceMessageDate'] ?? ''),
            'joinEventDate' => (string) ($inviteAttribution['joinEventDate'] ?? ''),
            'inviter' => [
                'userId' => (string) ($inviteAttribution['inviterUserId'] ?? ''),
                'displayName' => (string) ($inviteAttribution['inviterDisplayName'] ?? ''),
                'userName' => (string) ($inviteAttribution['inviterUserName'] ?? ''),
                'globalName' => (string) ($inviteAttribution['inviterGlobalName'] ?? ''),
                'avatarUrl' => DiscordAssets::avatar($inviteAttribution['inviterUserId'] ?? null, $inviteAttribution['inviterAvatarHash'] ?? null, 64),
            ],
        ];
    }
} catch (Throwable) {
    $member['inviteAttribution'] = null;
}
$member['latestPresence'] = null;

$summary = Database::fetchAll(
    'SELECT *
       FROM tbl_user_daily_summary
      WHERE guildId = :guildId AND userId = :userId' . ($asOf ? ' AND summaryDate <= DATE(:asOf)' : '') . '
      ORDER BY summaryDate DESC
      LIMIT 30',
    ['guildId' => $guildId, 'userId' => $userId] + $dateFilter
);

$activity = Database::fetchAll(
    'SELECT re.*, c.channelName, u.userName, u.globalName, u.avatarHash,
            COALESCE(u.globalName, u.userName, re.userId) AS displayName
       FROM tbl_raw_event re
       LEFT JOIN tbl_channel c ON c.channelId = re.channelId
       LEFT JOIN tbl_user u ON u.userId = re.userId
      WHERE re.guildId = :guildId AND re.userId = :userId' . ($asOf ? ' AND re.eventDate <= :asOf' : '') . '
      ORDER BY re.eventDate DESC, re.rawEventId DESC
      LIMIT 80',
    ['guildId' => $guildId, 'userId' => $userId] + $dateFilter
);

$messages = Database::fetchAll(
    'SELECT m.messageId, m.channelId, c.channelName,
            COALESCE(NULLIF(m.contentText, ""), latestRevision.contentText) AS archiveContentText,
            m.contentText, m.attachmentCount, m.isEdited, m.isDelete, m.messageCreateDate, m.deleteDate
       FROM tbl_message m
       LEFT JOIN tbl_channel c ON c.channelId = m.channelId
       LEFT JOIN (
            SELECT mr.messageId, mr.contentText
              FROM tbl_message_revision mr
              INNER JOIN (
                    SELECT messageId, MAX(messageRevisionId) AS messageRevisionId
                      FROM tbl_message_revision
                     WHERE contentText IS NOT NULL AND contentText <> ""
                     GROUP BY messageId
              ) latest ON latest.messageRevisionId = mr.messageRevisionId
       ) latestRevision ON latestRevision.messageId = m.messageId
      WHERE m.guildId = :guildId AND m.authorUserId = :userId' . ($asOf ? ' AND m.messageCreateDate <= :asOf' : '') . '
      ORDER BY m.messageCreateDate DESC
      LIMIT 60',
    ['guildId' => $guildId, 'userId' => $userId] + $dateFilter
);

$voice = Database::fetchAll(
    'SELECT vs.*, c.channelName,
            ' . ($asOf ? 'GREATEST(0, TIMESTAMPDIFF(SECOND, vs.startDate, LEAST(COALESCE(vs.endDate, :asOfVoiceEnd), :asOfVoiceClip)))' : 'vs.durationSeconds') . ' AS clippedDurationSeconds,
            CASE WHEN ' . ($asOf ? 'vs.startDate <= :asOfVoiceActiveStart AND (vs.endDate IS NULL OR vs.endDate > :asOfVoiceActiveEnd)' : 'vs.isClosed = 0') . ' THEN 1 ELSE 0 END AS activeAsOf
       FROM tbl_voice_session vs
       LEFT JOIN tbl_channel c ON c.channelId = vs.channelId
      WHERE vs.guildId = :guildId AND vs.userId = :userId' . ($asOf ? ' AND vs.startDate <= :asOfVoiceStart' : '') . '
      ORDER BY vs.startDate DESC
      LIMIT 40',
    ['guildId' => $guildId, 'userId' => $userId] + ($asOf ? [
        'asOfVoiceEnd' => $asOf,
        'asOfVoiceClip' => $asOf,
        'asOfVoiceActiveStart' => $asOf,
        'asOfVoiceActiveEnd' => $asOf,
        'asOfVoiceStart' => $asOf,
    ] : [])
);

$systemHistory = Database::fetchAll(
    'SELECT aa.*, au.displayName, au.discordUserName
       FROM tbl_admin_action aa
       LEFT JOIN tbl_admin_user au ON au.adminUserId = aa.adminUserId
      WHERE (aa.targetType = "user" OR aa.targetType = "member") AND aa.targetId = :userId' . ($asOf ? ' AND aa.createDate <= :asOf' : '') . '
      ORDER BY aa.adminActionId DESC
      LIMIT 40',
    ['userId' => $userId] + $dateFilter
);

$accessHistory = Database::fetchAll(
    'SELECT al.*, au.displayName, au.discordUserName
       FROM tbl_access_log al
       LEFT JOIN tbl_admin_user au ON au.adminUserId = al.adminUserId
      WHERE al.targetType = "user" AND al.targetId = :userId' . ($asOf ? ' AND al.createDate <= :asOf' : '') . '
      ORDER BY al.accessLogId DESC
      LIMIT 30',
    ['userId' => $userId] + $dateFilter
);

$walletLedger = Database::fetchAll(
    'SELECT wl.shopWalletLedgerId AS walletLedgerId,
            wl.unitCode,
            wl.amountDelta,
            wl.ledgerType,
            wl.sourceType,
            wl.sourceId,
            wl.metadataJson,
            wl.createDate
       FROM tbl_shop_wallet sw
       INNER JOIN tbl_shop_wallet_ledger wl ON wl.shopWalletId = sw.shopWalletId
      WHERE sw.guildId = :guildId AND sw.userId = :userId' . ($asOf ? ' AND wl.createDate <= :asOf' : '') . '
      ORDER BY wl.shopWalletLedgerId DESC
      LIMIT 30',
    ['guildId' => $guildId, 'userId' => $userId] + $dateFilter
);
$walletUnitIndex = [];
foreach ($walletUnits as $walletUnit) {
    $walletUnitIndex[(string) ($walletUnit['unitCode'] ?? '')] = $walletUnit;
}
$walletLedger = array_map(static function (array $row) use ($walletUnitIndex): array {
    $unitCode = (string) ($row['unitCode'] ?? '');
    $unit = $walletUnitIndex[$unitCode] ?? [];
    $row['unitLabel'] = (string) ($unit['displayName'] ?? $unit['shortName'] ?? $unitCode);
    $row['unitShortName'] = (string) ($unit['shortName'] ?? $unit['displayName'] ?? $unitCode);
    return $row;
}, $walletLedger);

$member['currentAvatarUrl'] = DiscordAssets::avatar($member['userId'], $member['currentAvatarHash'] ?? null, 128);
$member['currentGuildAvatarUrl'] = DiscordAssets::guildAvatar($guildId, $member['userId'], $member['currentGuildAvatarHash'] ?? null, 128);
$member['currentProfileAvatarUrl'] = $member['currentGuildAvatarUrl'] ?: $member['currentAvatarUrl'];
$member['currentBannerUrl'] = DiscordAssets::banner($member['userId'], $member['currentBannerHash'] ?? null, 512);
$member['avatarUrl'] = DiscordAssets::avatar($member['userId'], $member['avatarHash'] ?? null, 128);
$member['guildAvatarUrl'] = DiscordAssets::guildAvatar($guildId, $member['userId'], $member['guildAvatarHash'] ?? null, 128);
$member['profileAvatarUrl'] = $member['guildAvatarUrl'] ?: $member['avatarUrl'];
$member['historicalAvatarUrl'] = null;
$member['historicalGuildAvatarUrl'] = null;
$member['historicalProfileAvatarUrl'] = null;
$member['historicalBannerUrl'] = null;
$member['bannerUrl'] = $member['currentBannerUrl'];
$member['profileColor'] = $member['accentColor'] !== null ? '#' . str_pad(dechex((int) $member['accentColor']), 6, '0', STR_PAD_LEFT) : null;
$member['badges'] = DiscordBadges::fromUserMetadata($member['userMetadataJson'] ?? null);

$openVoice = Database::fetch(
    'SELECT vs.*, c.channelName
       FROM tbl_voice_session vs
       LEFT JOIN tbl_channel c ON c.channelId = vs.channelId
      WHERE vs.guildId = :guildId AND vs.userId = :userId
        AND vs.isClosed = 0
      ORDER BY vs.voiceSessionId DESC
      LIMIT 1',
    ['guildId' => $guildId, 'userId' => $userId]
);
$member['openVoice'] = $openVoice ?: null;
if ($member['openVoice']) {
    $member['openVoice']['timeoutActive'] = $member['asOfTimeoutActive'];
    $member['openVoice']['bannedAsOf'] = $member['bannedAsOf'];
}

Response::json([
    'ok' => true,
    'asOf' => $asOf,
    'member' => $member,
    'walletUnits' => $walletUnits,
    'roles' => $roles,
    'summary' => $summary,
    'activity' => $activity,
    'messages' => $messages,
    'voice' => $voice,
    'systemHistory' => $systemHistory,
    'accessHistory' => $accessHistory,
    'walletLedger' => $walletLedger,
]);
