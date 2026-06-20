<?php

declare(strict_types=1);

final class ActivitySystemResetService
{
    public function resetForBotLogRebuild(): array
    {
        $guildId = (string) Bootstrap::config('discord.guildId', '');
        $result = [];
        $result['tbl_voice_session'] = Database::execute('DELETE FROM tbl_voice_session WHERE guildId = :guildId', ['guildId' => $guildId]);
        $result['tbl_user_daily_summary'] = Database::execute('DELETE FROM tbl_user_daily_summary WHERE guildId = :guildId', ['guildId' => $guildId]);
        $result['tbl_raw_event_bot_logs'] = Database::execute(
            'DELETE FROM tbl_raw_event WHERE guildId = :guildId AND sourceName LIKE "bot_log_%"',
            ['guildId' => $guildId]
        );
        $result['tbl_sync_cursor'] = Database::execute(
            'DELETE FROM tbl_sync_cursor WHERE guildId = :guildId AND cursorType IN ("bot_log_archive", "canonical_bot_logs", "voice_sessions", "earn_summary")',
            ['guildId' => $guildId]
        );
        return $result;
    }
}
