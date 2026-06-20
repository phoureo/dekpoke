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

INSERT INTO `tbl_reward_rule` (`ruleCode`, `ruleName`, `triggerType`, `conditionJson`, `rewardJson`, `isActive`, `updateDate`)
VALUES (
  'earn_invite_member',
  'Invite member join',
  'earn_invite_member',
  '{"dailyLimit":20,"requireResolvedInviter":true}',
  '{"coin":5,"gachaTicket":0,"unitRewards":{"coin":5}}',
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE
  `ruleName` = VALUES(`ruleName`),
  `triggerType` = VALUES(`triggerType`),
  `conditionJson` = VALUES(`conditionJson`),
  `rewardJson` = VALUES(`rewardJson`),
  `updateDate` = VALUES(`updateDate`);
