<?php

declare(strict_types=1);

final class EarnSummaryService
{
    public function rebuild(?string $date = null): array
    {
        $date ??= date('Y-m-d');
        $guildId = (string) Bootstrap::config('discord.guildId', '');
        [$dayStart, $dayEnd] = [$date . ' 00:00:00', $date . ' 23:59:59'];

        $rows = Database::fetchAll(
            'SELECT userId,
                    SUM(messageCount) AS messageCount,
                    SUM(deletedMessageCount) AS deletedMessageCount,
                    SUM(editedMessageCount) AS editedMessageCount,
                    SUM(reactionCount) AS reactionCount,
                    SUM(voiceSeconds) AS voiceSeconds
             FROM (
                SELECT m.authorUserId AS userId,
                       COUNT(*) AS messageCount,
                       SUM(CASE WHEN m.isDelete = 1 THEN 1 ELSE 0 END) AS deletedMessageCount,
                       SUM(CASE WHEN m.isEdited = 1 THEN 1 ELSE 0 END) AS editedMessageCount,
                       0 AS reactionCount,
                       0 AS voiceSeconds
                FROM tbl_message m
                LEFT JOIN tbl_user u ON u.userId = m.authorUserId
                WHERE m.guildId = :guildIdMessages
                  AND m.authorUserId IS NOT NULL
                  AND COALESCE(u.isBot, 0) = 0
                  AND m.messageCreateDate BETWEEN :messageStart AND :messageEnd
                GROUP BY m.authorUserId
                UNION ALL
                SELECT mr.userId AS userId,
                       0 AS messageCount,
                       0 AS deletedMessageCount,
                       0 AS editedMessageCount,
                       COUNT(*) AS reactionCount,
                       0 AS voiceSeconds
                FROM tbl_message_reaction mr
                INNER JOIN tbl_message m ON m.messageId = mr.messageId
                WHERE m.guildId = :guildIdReactions
                  AND mr.createDate BETWEEN :reactionStart AND :reactionEnd
                GROUP BY mr.userId
                UNION ALL
                SELECT vs.userId AS userId,
                       0 AS messageCount,
                       0 AS deletedMessageCount,
                       0 AS editedMessageCount,
                       0 AS reactionCount,
                       SUM(GREATEST(0, TIMESTAMPDIFF(
                           SECOND,
                           GREATEST(vs.startDate, :voiceStart),
                           LEAST(COALESCE(vs.endDate, NOW()), :voiceEnd)
                       ))) AS voiceSeconds
                FROM tbl_voice_session vs
                WHERE vs.guildId = :guildIdVoice
                  AND vs.startDate < :voiceWhereEnd
                  AND COALESCE(vs.endDate, NOW()) > :voiceWhereStart
                GROUP BY vs.userId
             ) summaryRows
             WHERE userId IS NOT NULL AND userId <> ""
             GROUP BY userId',
            [
                'guildIdMessages' => $guildId,
                'guildIdReactions' => $guildId,
                'guildIdVoice' => $guildId,
                'messageStart' => $dayStart,
                'messageEnd' => $dayEnd,
                'reactionStart' => $dayStart,
                'reactionEnd' => $dayEnd,
                'voiceStart' => $dayStart,
                'voiceEnd' => $dayEnd,
                'voiceWhereStart' => $dayStart,
                'voiceWhereEnd' => $dayEnd,
            ]
        );

        foreach ($rows as $row) {
            Database::execute(
                'INSERT INTO tbl_user_daily_summary
                    (guildId, userId, summaryDate, messageCount, deletedMessageCount, editedMessageCount, reactionCount, voiceSeconds, updateDate)
                 VALUES
                    (:guildId, :userId, :summaryDate, :messageCount, :deletedMessageCount, :editedMessageCount, :reactionCount, :voiceSeconds, :updateDate)
                 ON DUPLICATE KEY UPDATE
                    messageCount = VALUES(messageCount),
                    deletedMessageCount = VALUES(deletedMessageCount),
                    editedMessageCount = VALUES(editedMessageCount),
                    reactionCount = VALUES(reactionCount),
                    voiceSeconds = VALUES(voiceSeconds),
                    updateDate = VALUES(updateDate)',
                [
                    'guildId' => $guildId,
                    'userId' => (string) $row['userId'],
                    'summaryDate' => $date,
                    'messageCount' => (int) ($row['messageCount'] ?? 0),
                    'deletedMessageCount' => (int) ($row['deletedMessageCount'] ?? 0),
                    'editedMessageCount' => (int) ($row['editedMessageCount'] ?? 0),
                    'reactionCount' => (int) ($row['reactionCount'] ?? 0),
                    'voiceSeconds' => (int) ($row['voiceSeconds'] ?? 0),
                    'updateDate' => date('Y-m-d H:i:s'),
                ]
            );
        }

        return ['date' => $date, 'users' => count($rows)];
    }

    public function rebuildRange(int $days): array
    {
        $days = max(1, min(366, $days));
        $items = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $items[] = $this->rebuild(date('Y-m-d', strtotime('-' . $i . ' days')));
        }
        return ['days' => $days, 'items' => $items];
    }
}
