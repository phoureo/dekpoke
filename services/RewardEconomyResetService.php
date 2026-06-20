<?php

declare(strict_types=1);

final class RewardEconomyResetService
{
    public function resetRewardsAndWallets(string $guildId): array
    {
        $guildId = trim($guildId);
        $result = [];

        foreach ([
            'tbl_reward_event',
            'tbl_shop_wallet_ledger',
            'tbl_shop_wallet',
            'tbl_shop_inventory_ledger',
            'tbl_shop_inventory',
        ] as $table) {
            $result[$table] = $this->deleteByGuild($table, $guildId);
        }

        LiveUpdateService::markTopic('reward_report', ['scope' => 'reset_rewards_and_wallets'], 'reset', 'rewards', $guildId);
        LiveUpdateService::markTopic('shop_member_bags', ['scope' => 'reset_rewards_and_wallets'], 'reset', 'shop_member_bags', $guildId);
        return ['reset' => 'rewards_and_wallets', 'tables' => $result];
    }

    public function resetGachaponHistory(string $guildId): array
    {
        $guildId = trim($guildId);
        $result = [];

        foreach ([
            'tbl_gacha_pending_draw',
            'tbl_gacha_spin_history',
            'tbl_gacha_role_grant',
            'tbl_gacha_campaign_counter_ledger',
        ] as $table) {
            $result[$table] = $this->deleteByGuild($table, $guildId);
        }

        $result['tbl_gacha_campaign_counter'] = $this->resetCampaignCounter($guildId);
        LiveUpdateService::markTopic('gacha_report', ['scope' => 'reset_gachapon_history'], 'reset', 'gacha', $guildId);
        return ['reset' => 'gachapon_history', 'tables' => $result];
    }

    private function deleteByGuild(string $table, string $guildId): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }
        if ($this->tableHasColumn($table, 'guildId')) {
            return Database::execute('DELETE FROM `' . $table . '` WHERE guildId = :guildId', ['guildId' => $guildId]);
        }
        return Database::execute('DELETE FROM `' . $table . '`');
    }

    private function resetCampaignCounter(string $guildId): int
    {
        if (!$this->tableExists('tbl_gacha_campaign_counter')) {
            return 0;
        }
        return Database::execute(
            'UPDATE tbl_gacha_campaign_counter
                SET currentValue = 0,
                    updateDate = :updateDate
              WHERE guildId = :guildId',
            ['guildId' => $guildId, 'updateDate' => date('Y-m-d H:i:s')]
        );
    }

    private function tableExists(string $table): bool
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS total
               FROM INFORMATION_SCHEMA.TABLES
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :tableName',
            ['tableName' => $table]
        );
        return (int) ($row['total'] ?? 0) > 0;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS total
               FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :tableName
                AND COLUMN_NAME = :columnName',
            ['tableName' => $table, 'columnName' => $column]
        );
        return (int) ($row['total'] ?? 0) > 0;
    }
}
