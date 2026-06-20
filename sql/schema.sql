CREATE DATABASE IF NOT EXISTS `dekpoke_workspace`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `dekpoke_workspace`;

CREATE TABLE IF NOT EXISTS `tbl_guild` (
  `guildId` varchar(32) NOT NULL,
  `guildName` varchar(190) NOT NULL,
  `iconHash` varchar(190) DEFAULT NULL,
  `ownerUserId` varchar(32) DEFAULT NULL,
  `approximateMemberCount` int DEFAULT NULL,
  `approximatePresenceCount` int DEFAULT NULL,
  `verificationLevel` int DEFAULT NULL,
  `premiumTier` int DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  `deleteDate` datetime DEFAULT NULL,
  PRIMARY KEY (`guildId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_user` (
  `userId` varchar(32) NOT NULL,
  `userName` varchar(190) NOT NULL,
  `globalName` varchar(190) DEFAULT NULL,
  `discriminator` varchar(16) DEFAULT NULL,
  `avatarHash` varchar(190) DEFAULT NULL,
  `bannerHash` varchar(190) DEFAULT NULL,
  `accentColor` int DEFAULT NULL,
  `isBot` tinyint(1) NOT NULL DEFAULT 0,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`userId`),
  KEY `idx_tbl_user_name` (`userName`),
  KEY `idx_tbl_user_globalName` (`globalName`),
  KEY `idx_tbl_user_isBot` (`isBot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_role` (
  `roleId` varchar(32) NOT NULL,
  `guildId` varchar(32) NOT NULL,
  `roleName` varchar(190) NOT NULL,
  `roleColor` int unsigned DEFAULT NULL,
  `rolePosition` int NOT NULL DEFAULT 0,
  `permissions` varchar(64) DEFAULT NULL,
  `iconHash` varchar(190) DEFAULT NULL,
  `unicodeEmoji` varchar(64) DEFAULT NULL,
  `isManaged` tinyint(1) NOT NULL DEFAULT 0,
  `isMentionable` tinyint(1) NOT NULL DEFAULT 0,
  `isHoist` tinyint(1) NOT NULL DEFAULT 0,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  `deleteDate` datetime DEFAULT NULL,
  PRIMARY KEY (`roleId`),
  KEY `idx_tbl_role_guildPosition` (`guildId`, `rolePosition`),
  KEY `idx_tbl_role_name` (`roleName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_channel` (
  `channelId` varchar(32) NOT NULL,
  `guildId` varchar(32) NOT NULL,
  `parentChannelId` varchar(32) DEFAULT NULL,
  `channelName` varchar(190) NOT NULL,
  `channelType` int NOT NULL DEFAULT 0,
  `channelPosition` int NOT NULL DEFAULT 0,
  `topic` text DEFAULT NULL,
  `bitrate` int DEFAULT NULL,
  `userLimit` int DEFAULT NULL,
  `rateLimitPerUser` int DEFAULT NULL,
  `isNsfw` tinyint(1) NOT NULL DEFAULT 0,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  `deleteDate` datetime DEFAULT NULL,
  PRIMARY KEY (`channelId`),
  KEY `idx_tbl_channel_guildPosition` (`guildId`, `channelPosition`),
  KEY `idx_tbl_channel_parent` (`parentChannelId`),
  KEY `idx_tbl_channel_type` (`channelType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_channel_overwrite` (
  `channelOverwriteId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `channelId` varchar(32) NOT NULL,
  `overwriteId` varchar(32) NOT NULL,
  `overwriteType` int NOT NULL,
  `allowPermissions` varchar(64) DEFAULT NULL,
  `denyPermissions` varchar(64) DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`channelOverwriteId`),
  UNIQUE KEY `uq_tbl_channel_overwrite` (`channelId`, `overwriteId`, `overwriteType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_member` (
  `memberId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `nickName` varchar(190) DEFAULT NULL,
  `guildAvatarHash` varchar(190) DEFAULT NULL,
  `joinedAt` datetime DEFAULT NULL,
  `premiumSince` datetime DEFAULT NULL,
  `communicationDisabledUntil` datetime DEFAULT NULL,
  `lastSyncDate` datetime DEFAULT NULL,
  `roleCount` int NOT NULL DEFAULT 0,
  `isPending` tinyint(1) NOT NULL DEFAULT 0,
  `isDelete` tinyint(1) NOT NULL DEFAULT 0,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  `deleteDate` datetime DEFAULT NULL,
  PRIMARY KEY (`memberId`),
  UNIQUE KEY `uq_tbl_member_guildUser` (`guildId`, `userId`),
  KEY `idx_tbl_member_user` (`userId`),
  KEY `idx_tbl_member_first_seen` (`joinedAt`),
  KEY `idx_tbl_member_active` (`isActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_member_role` (
  `memberRoleId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `roleId` varchar(32) NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleteDate` datetime DEFAULT NULL,
  PRIMARY KEY (`memberRoleId`),
  UNIQUE KEY `uq_tbl_member_role` (`guildId`, `userId`, `roleId`),
  KEY `idx_tbl_member_role_user` (`guildId`, `userId`),
  KEY `idx_tbl_member_role_role` (`roleId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_role_revision` (
  `roleRevisionId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `roleId` varchar(32) NOT NULL,
  `guildId` varchar(32) NOT NULL,
  `beforeJson` longtext DEFAULT NULL,
  `afterJson` longtext DEFAULT NULL,
  `sourceType` varchar(80) NOT NULL DEFAULT 'gateway',
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`roleRevisionId`),
  KEY `idx_tbl_role_revision_role` (`roleId`, `createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_raw_event` (
  `rawEventId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) DEFAULT NULL,
  `eventType` varchar(120) NOT NULL,
  `eventSequence` bigint DEFAULT NULL,
  `eventPayloadJson` longtext NOT NULL,
  `channelId` varchar(32) DEFAULT NULL,
  `userId` varchar(32) DEFAULT NULL,
  `targetType` varchar(80) DEFAULT NULL,
  `targetId` varchar(120) DEFAULT NULL,
  `sourceKey` varchar(190) DEFAULT NULL,
  `sourceName` varchar(80) NOT NULL DEFAULT 'gateway',
  `contextJson` longtext DEFAULT NULL,
  `processStatus` varchar(40) NOT NULL DEFAULT 'queued',
  `errorMessage` text DEFAULT NULL,
  `eventDate` datetime DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processDate` datetime DEFAULT NULL,
  PRIMARY KEY (`rawEventId`),
  UNIQUE KEY `uq_tbl_raw_event_source` (`sourceKey`),
  KEY `idx_tbl_raw_event_typeStatus` (`eventType`, `processStatus`),
  KEY `idx_tbl_raw_event_guildDate` (`guildId`, `eventDate`, `rawEventId`),
  KEY `idx_tbl_raw_event_userDate` (`userId`, `eventDate`),
  KEY `idx_tbl_raw_event_channelDate` (`channelId`, `eventDate`),
  KEY `idx_tbl_raw_event_sourceName` (`sourceName`, `eventDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_member_join_invite_attribution` (
  `joinInviteAttributionId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `joinedUserId` varchar(32) DEFAULT NULL,
  `rawEventId` bigint unsigned DEFAULT NULL,
  `joinEventDate` datetime DEFAULT NULL,
  `sourceMessageId` varchar(32) NOT NULL,
  `sourceChannelId` varchar(32) NOT NULL,
  `sourceMessageDate` datetime DEFAULT NULL,
  `inviteType` varchar(40) NOT NULL DEFAULT 'unknown',
  `inviterUserId` varchar(32) DEFAULT NULL,
  `inviterName` varchar(190) DEFAULT NULL,
  `inviteCount` int DEFAULT NULL,
  `matchStatus` varchar(40) NOT NULL DEFAULT 'pending_join',
  `confidence` varchar(40) NOT NULL DEFAULT 'pending',
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`joinInviteAttributionId`),
  UNIQUE KEY `uq_tbl_member_join_invite_source` (`sourceMessageId`),
  KEY `idx_tbl_member_join_invite_joined` (`guildId`, `joinedUserId`, `sourceMessageDate`),
  KEY `idx_tbl_member_join_invite_raw` (`rawEventId`),
  KEY `idx_tbl_member_join_invite_inviter` (`guildId`, `inviterUserId`, `sourceMessageDate`),
  KEY `idx_tbl_member_join_invite_status` (`matchStatus`, `inviteType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_message` (
  `messageId` varchar(32) NOT NULL,
  `guildId` varchar(32) NOT NULL,
  `channelId` varchar(32) NOT NULL,
  `authorUserId` varchar(32) DEFAULT NULL,
  `parentMessageId` varchar(32) DEFAULT NULL,
  `messageType` int DEFAULT NULL,
  `contentText` mediumtext DEFAULT NULL,
  `cleanContentText` mediumtext DEFAULT NULL,
  `attachmentCount` int NOT NULL DEFAULT 0,
  `reactionCount` int NOT NULL DEFAULT 0,
  `mentionCount` int NOT NULL DEFAULT 0,
  `isPinned` tinyint(1) NOT NULL DEFAULT 0,
  `isEdited` tinyint(1) NOT NULL DEFAULT 0,
  `isDelete` tinyint(1) NOT NULL DEFAULT 0,
  `isBackfilled` tinyint(1) NOT NULL DEFAULT 0,
  `metadataJson` longtext DEFAULT NULL,
  `messageCreateDate` datetime DEFAULT NULL,
  `messageUpdateDate` datetime DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  `deleteDate` datetime DEFAULT NULL,
  PRIMARY KEY (`messageId`),
  KEY `idx_tbl_message_guildChannelDate` (`guildId`, `channelId`, `messageCreateDate`),
  KEY `idx_tbl_message_authorDate` (`authorUserId`, `messageCreateDate`),
  KEY `idx_tbl_message_delete` (`isDelete`, `deleteDate`),
  FULLTEXT KEY `ft_tbl_message_content` (`contentText`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_message_revision` (
  `messageRevisionId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `messageId` varchar(32) NOT NULL,
  `revisionType` varchar(40) NOT NULL,
  `contentText` mediumtext DEFAULT NULL,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`messageRevisionId`),
  KEY `idx_tbl_message_revision_message` (`messageId`, `createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_message_attachment` (
  `messageAttachmentId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attachmentId` varchar(32) NOT NULL,
  `messageId` varchar(32) NOT NULL,
  `fileName` varchar(255) DEFAULT NULL,
  `contentType` varchar(190) DEFAULT NULL,
  `fileSize` bigint DEFAULT NULL,
  `sourceUrl` text DEFAULT NULL,
  `proxyUrl` text DEFAULT NULL,
  `localPath` varchar(500) DEFAULT NULL,
  `downloadStatus` varchar(40) NOT NULL DEFAULT 'queued',
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`messageAttachmentId`),
  UNIQUE KEY `uq_tbl_message_attachment` (`attachmentId`),
  KEY `idx_tbl_message_attachment_message` (`messageId`),
  KEY `idx_tbl_message_attachment_status` (`downloadStatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_message_delete` (
  `messageDeleteId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `messageId` varchar(32) NOT NULL,
  `guildId` varchar(32) NOT NULL,
  `channelId` varchar(32) NOT NULL,
  `deletedByUserId` varchar(32) DEFAULT NULL,
  `deleteSource` varchar(80) DEFAULT NULL,
  `isBulk` tinyint(1) NOT NULL DEFAULT 0,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`messageDeleteId`),
  KEY `idx_tbl_message_delete_message` (`messageId`),
  KEY `idx_tbl_message_delete_actor` (`deletedByUserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_message_reaction` (
  `messageReactionId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `messageId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `emojiKey` varchar(190) NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleteDate` datetime DEFAULT NULL,
  PRIMARY KEY (`messageReactionId`),
  UNIQUE KEY `uq_tbl_message_reaction` (`messageId`, `userId`, `emojiKey`),
  KEY `idx_tbl_message_reaction_user` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_poll_vote` (
  `pollVoteId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `messageId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `answerId` varchar(32) NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleteDate` datetime DEFAULT NULL,
  PRIMARY KEY (`pollVoteId`),
  UNIQUE KEY `uq_tbl_poll_vote` (`messageId`, `userId`, `answerId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_file_blob` (
  `fileBlobId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sourceType` varchar(80) NOT NULL,
  `sourceId` varchar(80) NOT NULL,
  `fileName` varchar(255) DEFAULT NULL,
  `contentType` varchar(190) DEFAULT NULL,
  `fileSize` bigint DEFAULT NULL,
  `localPath` varchar(500) NOT NULL,
  `sha256Hash` char(64) DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`fileBlobId`),
  KEY `idx_tbl_file_blob_source` (`sourceType`, `sourceId`),
  KEY `idx_tbl_file_blob_hash` (`sha256Hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_voice_session` (
  `voiceSessionId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `channelId` varchar(32) NOT NULL,
  `startDate` datetime NOT NULL,
  `endDate` datetime DEFAULT NULL,
  `durationSeconds` int NOT NULL DEFAULT 0,
  `isMuted` tinyint(1) NOT NULL DEFAULT 0,
  `isDeafened` tinyint(1) NOT NULL DEFAULT 0,
  `isStreaming` tinyint(1) NOT NULL DEFAULT 0,
  `isVideo` tinyint(1) NOT NULL DEFAULT 0,
  `isClosed` tinyint(1) NOT NULL DEFAULT 0,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`voiceSessionId`),
  KEY `idx_tbl_voice_active` (`guildId`, `isClosed`, `channelId`),
  KEY `idx_tbl_voice_userDate` (`guildId`, `userId`, `startDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_user_daily_summary` (
  `userDailySummaryId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `summaryDate` date NOT NULL,
  `messageCount` int NOT NULL DEFAULT 0,
  `deletedMessageCount` int NOT NULL DEFAULT 0,
  `editedMessageCount` int NOT NULL DEFAULT 0,
  `reactionCount` int NOT NULL DEFAULT 0,
  `voiceSeconds` int NOT NULL DEFAULT 0,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`userDailySummaryId`),
  UNIQUE KEY `uq_tbl_user_daily` (`guildId`, `userId`, `summaryDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_reward_rule` (
  `rewardRuleId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ruleCode` varchar(120) NOT NULL,
  `ruleName` varchar(190) NOT NULL,
  `triggerType` varchar(120) NOT NULL,
  `conditionJson` longtext DEFAULT NULL,
  `rewardJson` longtext DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`rewardRuleId`),
  UNIQUE KEY `uq_tbl_reward_rule_code` (`ruleCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_reward_event` (
  `rewardEventId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rewardRuleId` bigint unsigned NOT NULL,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `sourceType` varchar(80) DEFAULT NULL,
  `sourceId` varchar(80) DEFAULT NULL,
  `transactionGroupId` varchar(120) DEFAULT NULL,
  `rewardStatus` varchar(40) NOT NULL DEFAULT 'granted',
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rewardEventId`),
  UNIQUE KEY `uq_tbl_reward_event_source` (`rewardRuleId`, `guildId`, `userId`, `sourceType`, `sourceId`),
  KEY `idx_tbl_reward_event_user` (`guildId`, `userId`, `createDate`),
  KEY `idx_tbl_reward_event_trace` (`transactionGroupId`, `createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_shop_unit` (
  `unitCode` varchar(80) NOT NULL,
  `displayName` varchar(120) NOT NULL,
  `shortName` varchar(40) NOT NULL,
  `icon` varchar(190) DEFAULT NULL,
  `isEnabled` tinyint(1) NOT NULL DEFAULT 1,
  `sortOrder` int NOT NULL DEFAULT 0,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`unitCode`),
  KEY `idx_tbl_shop_unit_sort` (`isEnabled`, `sortOrder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_shop_wallet` (
  `shopWalletId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `unitCode` varchar(80) NOT NULL,
  `balanceAmount` bigint NOT NULL DEFAULT 0,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`shopWalletId`),
  UNIQUE KEY `uq_tbl_shop_wallet_user_unit` (`guildId`, `userId`, `unitCode`),
  KEY `idx_tbl_shop_wallet_unit` (`unitCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_shop_wallet_ledger` (
  `shopWalletLedgerId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shopWalletId` bigint unsigned NOT NULL,
  `unitCode` varchar(80) NOT NULL,
  `amountDelta` bigint NOT NULL,
  `ledgerType` varchar(80) NOT NULL,
  `sourceType` varchar(80) DEFAULT NULL,
  `sourceId` varchar(120) DEFAULT NULL,
  `transactionGroupId` varchar(120) DEFAULT NULL,
  `actorUserId` varchar(32) DEFAULT NULL,
  `targetUserId` varchar(32) DEFAULT NULL,
  `walletBalanceBefore` bigint DEFAULT NULL,
  `walletBalanceAfter` bigint DEFAULT NULL,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`shopWalletLedgerId`),
  KEY `idx_tbl_shop_wallet_ledger_wallet` (`shopWalletId`, `createDate`),
  KEY `idx_tbl_shop_wallet_ledger_unit` (`unitCode`, `createDate`),
  KEY `idx_tbl_shop_wallet_ledger_trace` (`transactionGroupId`, `createDate`),
  KEY `idx_tbl_shop_wallet_ledger_source` (`sourceType`, `sourceId`, `createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_shop_item` (
  `shopItemId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `itemCode` varchar(120) NOT NULL,
  `itemName` varchar(190) NOT NULL,
  `itemType` varchar(80) NOT NULL DEFAULT 'item',
  `image` varchar(255) DEFAULT NULL,
  `effectType` varchar(120) DEFAULT NULL,
  `effectPayloadJson` longtext DEFAULT NULL,
  `metadataJson` longtext DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`shopItemId`),
  UNIQUE KEY `uq_tbl_shop_item_code` (`itemCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_shop_inventory` (
  `shopInventoryId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `shopItemId` bigint unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT 0,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`shopInventoryId`),
  UNIQUE KEY `uq_tbl_shop_inventory_item` (`guildId`, `userId`, `shopItemId`),
  KEY `idx_tbl_shop_inventory_user` (`guildId`, `userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_shop_inventory_ledger` (
  `shopInventoryLedgerId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `shopInventoryId` bigint unsigned NOT NULL,
  `shopItemId` bigint unsigned NOT NULL,
  `quantityDelta` int NOT NULL,
  `quantityBefore` int DEFAULT NULL,
  `quantityAfter` int DEFAULT NULL,
  `ledgerType` varchar(80) NOT NULL,
  `sourceType` varchar(80) DEFAULT NULL,
  `sourceId` varchar(120) DEFAULT NULL,
  `transactionGroupId` varchar(120) DEFAULT NULL,
  `actorUserId` varchar(32) DEFAULT NULL,
  `targetUserId` varchar(32) DEFAULT NULL,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`shopInventoryLedgerId`),
  KEY `idx_tbl_shop_inventory_ledger_user` (`guildId`, `userId`, `createDate`),
  KEY `idx_tbl_shop_inventory_ledger_item` (`shopItemId`, `createDate`),
  KEY `idx_tbl_shop_inventory_ledger_inventory` (`shopInventoryId`, `createDate`),
  KEY `idx_tbl_shop_inventory_ledger_trace` (`transactionGroupId`, `createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_gacha_spin_history` (
  `gachaSpinHistoryId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `drawId` varchar(64) NOT NULL,
  `drawStatus` varchar(40) NOT NULL DEFAULT 'started',
  `count` int unsigned NOT NULL DEFAULT 1,
  `buttonId` int unsigned NOT NULL DEFAULT 0,
  `currency` varchar(80) NOT NULL DEFAULT 'ticket',
  `costPerSpin` int unsigned NOT NULL DEFAULT 0,
  `totalCost` int unsigned NOT NULL DEFAULT 0,
  `balanceBefore` bigint NOT NULL DEFAULT 0,
  `balanceAfter` bigint NOT NULL DEFAULT 0,
  `lockedType` varchar(80) DEFAULT NULL,
  `tierId` varchar(120) DEFAULT NULL,
  `tierName` varchar(190) DEFAULT NULL,
  `prizeId` varchar(190) DEFAULT NULL,
  `prizeName` varchar(255) DEFAULT NULL,
  `prizeType` varchar(80) DEFAULT NULL,
  `reusedPendingReward` tinyint(1) NOT NULL DEFAULT 0,
  `startedAt` datetime DEFAULT NULL,
  `revealedAt` datetime DEFAULT NULL,
  `ballIssuedAt` datetime DEFAULT NULL,
  `ballSeenAt` datetime DEFAULT NULL,
  `prizeResolvedAt` datetime DEFAULT NULL,
  `completedAt` datetime DEFAULT NULL,
  `refundedAt` datetime DEFAULT NULL,
  `snapshotJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`gachaSpinHistoryId`),
  UNIQUE KEY `uq_tbl_gacha_spin_history_draw` (`guildId`, `drawId`),
  KEY `idx_tbl_gacha_spin_history_started` (`startedAt`),
  KEY `idx_tbl_gacha_spin_history_user_started` (`userId`, `startedAt`),
  KEY `idx_tbl_gacha_spin_history_status_started` (`drawStatus`, `startedAt`),
  KEY `idx_tbl_gacha_spin_history_prize` (`prizeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_gacha_role_grant` (
  `gachaRoleGrantId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `drawId` varchar(64) NOT NULL,
  `transactionGroupId` varchar(120) DEFAULT NULL,
  `prizeId` varchar(190) DEFAULT NULL,
  `prizeName` varchar(255) DEFAULT NULL,
  `roleId` varchar(32) DEFAULT NULL,
  `roleName` varchar(190) DEFAULT NULL,
  `durationDays` int unsigned NOT NULL DEFAULT 0,
  `grantStatus` varchar(40) NOT NULL DEFAULT 'pending',
  `grantedAt` datetime DEFAULT NULL,
  `expireAt` datetime DEFAULT NULL,
  `revokedAt` datetime DEFAULT NULL,
  `lastAttemptAt` datetime DEFAULT NULL,
  `lastError` text DEFAULT NULL,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`gachaRoleGrantId`),
  UNIQUE KEY `uq_tbl_gacha_role_grant_draw` (`guildId`, `drawId`),
  KEY `idx_tbl_gacha_role_grant_user` (`guildId`, `userId`, `grantStatus`),
  KEY `idx_tbl_gacha_role_grant_expire` (`grantStatus`, `expireAt`),
  KEY `idx_tbl_gacha_role_grant_role` (`roleId`),
  KEY `idx_tbl_gacha_role_grant_trace` (`transactionGroupId`, `createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_gacha_campaign_counter` (
  `campaignCounterId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `campaignCode` varchar(120) NOT NULL,
  `currentValue` int unsigned NOT NULL DEFAULT 0,
  `minValue` int unsigned NOT NULL DEFAULT 0,
  `maxValue` int unsigned NOT NULL DEFAULT 5000,
  `isEnabled` tinyint(1) NOT NULL DEFAULT 1,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`campaignCounterId`),
  UNIQUE KEY `uq_tbl_gacha_campaign_counter_code` (`guildId`, `campaignCode`),
  KEY `idx_tbl_gacha_campaign_counter_enabled` (`guildId`, `isEnabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_gacha_campaign_counter_ledger` (
  `campaignCounterLedgerId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `campaignCode` varchar(120) NOT NULL,
  `drawId` varchar(64) NOT NULL,
  `amountDelta` int NOT NULL DEFAULT 0,
  `valueBefore` int unsigned NOT NULL DEFAULT 0,
  `valueAfter` int unsigned NOT NULL DEFAULT 0,
  `sourceType` varchar(80) NOT NULL DEFAULT 'gacha_spin',
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`campaignCounterLedgerId`),
  UNIQUE KEY `uq_tbl_gacha_campaign_counter_ledger_draw` (`guildId`, `campaignCode`, `drawId`),
  KEY `idx_tbl_gacha_campaign_counter_ledger_counter` (`guildId`, `campaignCode`, `createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_gacha_pending_draw` (
  `gachaPendingDrawId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guildId` varchar(32) NOT NULL,
  `userId` varchar(32) NOT NULL,
  `drawId` varchar(64) NOT NULL,
  `drawStatus` varchar(40) NOT NULL DEFAULT 'pending',
  `drawJson` longtext NOT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`gachaPendingDrawId`),
  UNIQUE KEY `uq_tbl_gacha_pending_draw_draw` (`drawId`),
  KEY `idx_tbl_gacha_pending_draw_user` (`guildId`, `userId`, `drawStatus`, `createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_setting` (
  `settingId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `settingKey` varchar(190) NOT NULL,
  `settingValueJson` longtext DEFAULT NULL,
  `isSecret` tinyint(1) NOT NULL DEFAULT 0,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`settingId`),
  UNIQUE KEY `uq_tbl_setting_key` (`settingKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_admin_user` (
  `adminUserId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `discordUserId` varchar(32) NOT NULL,
  `discordUserName` varchar(190) NOT NULL,
  `displayName` varchar(190) NOT NULL,
  `avatarHash` varchar(190) DEFAULT NULL,
  `roleName` varchar(80) NOT NULL DEFAULT 'Viewer',
  `permissionsJson` longtext DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  `deleteDate` datetime DEFAULT NULL,
  PRIMARY KEY (`adminUserId`),
  UNIQUE KEY `uq_tbl_admin_user_discord` (`discordUserId`),
  KEY `idx_tbl_admin_user_role` (`roleName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_admin_session` (
  `adminSessionId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `adminUserId` bigint unsigned NOT NULL,
  `sessionHash` char(64) NOT NULL,
  `deviceLabel` varchar(190) DEFAULT NULL,
  `ipAddress` varchar(64) DEFAULT NULL,
  `userAgent` varchar(500) DEFAULT NULL,
  `isRevoked` tinyint(1) NOT NULL DEFAULT 0,
  `lastSeenDate` datetime DEFAULT NULL,
  `revokeDate` datetime DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`adminSessionId`),
  KEY `idx_tbl_admin_session_user` (`adminUserId`),
  KEY `idx_tbl_admin_session_hash` (`sessionHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_admin_action` (
  `adminActionId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parentAdminActionId` bigint unsigned DEFAULT NULL,
  `adminUserId` bigint unsigned DEFAULT NULL,
  `discordUserId` varchar(32) DEFAULT NULL,
  `guildId` varchar(32) DEFAULT NULL,
  `actionType` varchar(120) NOT NULL,
  `targetType` varchar(80) DEFAULT NULL,
  `targetId` varchar(80) DEFAULT NULL,
  `actionStage` varchar(40) NOT NULL DEFAULT 'event',
  `status` varchar(40) NOT NULL DEFAULT 'pending',
  `permissionSnapshotJson` longtext DEFAULT NULL,
  `requestPayloadJson` longtext DEFAULT NULL,
  `responsePayloadJson` longtext DEFAULT NULL,
  `beforeJson` longtext DEFAULT NULL,
  `afterJson` longtext DEFAULT NULL,
  `errorMessage` text DEFAULT NULL,
  `discordAuditReason` varchar(512) DEFAULT NULL,
  `httpStatus` int DEFAULT NULL,
  `ipAddress` varchar(64) DEFAULT NULL,
  `userAgent` varchar(500) DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`adminActionId`),
  KEY `idx_tbl_admin_action_parent` (`parentAdminActionId`),
  KEY `idx_tbl_admin_action_user` (`adminUserId`),
  KEY `idx_tbl_admin_action_type` (`actionType`, `status`),
  KEY `idx_tbl_admin_action_target` (`targetType`, `targetId`),
  KEY `idx_tbl_admin_action_date` (`createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_admin_action_target` (
  `adminActionTargetId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `adminActionId` bigint unsigned NOT NULL,
  `targetType` varchar(80) NOT NULL,
  `targetId` varchar(80) NOT NULL,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`adminActionTargetId`),
  KEY `idx_tbl_admin_action_target_action` (`adminActionId`),
  KEY `idx_tbl_admin_action_target_lookup` (`targetType`, `targetId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_access_log` (
  `accessLogId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `adminUserId` bigint unsigned DEFAULT NULL,
  `discordUserId` varchar(32) DEFAULT NULL,
  `eventType` varchar(120) NOT NULL,
  `targetType` varchar(80) DEFAULT NULL,
  `targetId` varchar(80) DEFAULT NULL,
  `sensitivityLevel` varchar(40) NOT NULL DEFAULT 'normal',
  `requestPayloadJson` longtext DEFAULT NULL,
  `metadataJson` longtext DEFAULT NULL,
  `ipAddress` varchar(64) DEFAULT NULL,
  `userAgent` varchar(500) DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`accessLogId`),
  KEY `idx_tbl_access_log_user` (`adminUserId`),
  KEY `idx_tbl_access_log_event` (`eventType`),
  KEY `idx_tbl_access_log_target` (`targetType`, `targetId`),
  KEY `idx_tbl_access_log_date` (`createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_sync_job` (
  `syncJobId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jobType` varchar(120) NOT NULL,
  `jobStatus` varchar(40) NOT NULL DEFAULT 'queued',
  `priority` int NOT NULL DEFAULT 100,
  `attemptCount` int NOT NULL DEFAULT 0,
  `payloadJson` longtext DEFAULT NULL,
  `resultJson` longtext DEFAULT NULL,
  `errorMessage` text DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `startDate` datetime DEFAULT NULL,
  `finishDate` datetime DEFAULT NULL,
  PRIMARY KEY (`syncJobId`),
  KEY `idx_tbl_sync_job_status` (`jobStatus`, `priority`, `syncJobId`),
  KEY `idx_tbl_sync_job_type` (`jobType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_sync_cursor` (
  `syncCursorId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cursorType` varchar(120) NOT NULL,
  `guildId` varchar(32) DEFAULT NULL,
  `channelId` varchar(32) DEFAULT NULL,
  `cursorValue` varchar(190) DEFAULT NULL,
  `metadataJson` longtext DEFAULT NULL,
  `updateDate` datetime DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`syncCursorId`),
  UNIQUE KEY `uq_tbl_sync_cursor` (`cursorType`, `guildId`, `channelId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_worker_heartbeat` (
  `workerName` varchar(120) NOT NULL,
  `heartbeatDate` datetime NOT NULL,
  `metadataJson` longtext DEFAULT NULL,
  PRIMARY KEY (`workerName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_ingest_error` (
  `ingestErrorId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rawEventId` bigint unsigned DEFAULT NULL,
  `errorType` varchar(120) NOT NULL,
  `errorMessage` text NOT NULL,
  `contextJson` longtext DEFAULT NULL,
  `isResolved` tinyint(1) NOT NULL DEFAULT 0,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolveDate` datetime DEFAULT NULL,
  PRIMARY KEY (`ingestErrorId`),
  KEY `idx_tbl_ingest_error_resolved` (`isResolved`, `createDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_api_rate_limit` (
  `apiRateLimitId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `providerName` varchar(80) NOT NULL,
  `routeKey` varchar(190) NOT NULL,
  `httpMethod` varchar(12) NOT NULL,
  `bucketKey` varchar(190) DEFAULT NULL,
  `statusCode` int DEFAULT NULL,
  `remainingCount` int DEFAULT NULL,
  `resetAfterSeconds` decimal(10,3) DEFAULT NULL,
  `resetDate` datetime DEFAULT NULL,
  `metadataJson` longtext DEFAULT NULL,
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`apiRateLimitId`),
  UNIQUE KEY `uq_tbl_api_rate_limit_route` (`providerName`, `routeKey`, `httpMethod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_shop_unit` (`unitCode`, `displayName`, `shortName`, `icon`, `isEnabled`, `sortOrder`, `metadataJson`)
VALUES
  ('coin', 'เหรียญ', 'Coin', 'fa-solid fa-coins', 1, 10, '{"seed":"workspace.default_unit"}'),
  ('gem', 'เพชร', 'Gem', 'fa-solid fa-gem', 1, 20, '{"seed":"workspace.default_unit"}'),
  ('ticket', 'ตั๋ว', 'Ticket', 'fa-solid fa-ticket', 1, 30, '{"seed":"workspace.default_unit"}'),
  ('potion', 'โพชั่น', 'Potion', 'fa-solid fa-flask', 1, 40, '{"seed":"workspace.default_unit"}')
ON DUPLICATE KEY UPDATE
  `displayName` = VALUES(`displayName`),
  `shortName` = VALUES(`shortName`),
  `icon` = VALUES(`icon`),
  `isEnabled` = VALUES(`isEnabled`),
  `sortOrder` = VALUES(`sortOrder`);

INSERT INTO `tbl_reward_rule` (`ruleCode`, `ruleName`, `triggerType`, `conditionJson`, `rewardJson`, `isActive`)
VALUES
  ('earn_text_daily_ticket', 'Text active 10 minutes daily', 'earn_text_active_daily', '{"minMinutes":10,"dailyLimit":1}', '{"coin":0,"gachaTicket":1,"unitRewards":{"ticket":1}}', 1),
  ('earn_voice_hourly_coin', 'Voice 1 hour repeat', 'earn_voice_hourly', '{"minSeconds":3600,"dailyLimit":24}', '{"coin":10,"gachaTicket":0,"unitRewards":{"coin":10}}', 1),
  ('earn_voice_10min_free_spin', 'Voice 10 minutes daily free spin', 'earn_voice_hourly', '{"minSeconds":600,"dailyLimit":1}', '{"coin":0,"gachaTicket":0,"gachaFreeSpin":1,"unitRewards":[]}', 1),
  ('earn_member_first_join', 'First server join', 'earn_member_first_join', '{"dailyLimit":1}', '{"coin":10,"gachaTicket":0,"unitRewards":{"coin":10}}', 1),
  ('earn_invite_member', 'Invite member join', 'earn_invite_member', '{"dailyLimit":20,"requireResolvedInviter":true}', '{"coin":5,"gachaTicket":0,"unitRewards":{"coin":5}}', 1),
  ('earn_manual_grant', 'Manual Earn Grant', 'earn_manual', '{"manual":true}', '{"unitRewards":[]}', 1)
ON DUPLICATE KEY UPDATE
  `ruleName` = VALUES(`ruleName`),
  `triggerType` = VALUES(`triggerType`);
