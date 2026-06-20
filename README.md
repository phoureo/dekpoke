# Dekpoke Workspace Console

Lean Discord workspace rebuild for canonical gateway events, slim monitor pages, earn, shop/wallet/bag, and gachapon.

## Setup

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -uroot < sql/reset.sql
/Applications/XAMPP/xamppfiles/bin/mysql -uroot < sql/schema.sql
```

Copy `config/config.local.php.example` to `config/config.local.php` and set Discord OAuth/bot values for `/discord_workspace`.

## Workers

```bash
/Applications/XAMPP/xamppfiles/bin/php workers/gateway_worker.php
/Applications/XAMPP/xamppfiles/bin/php workers/sync_worker.php --loop
/Applications/XAMPP/xamppfiles/bin/php workers/backfill_worker.php all
/Applications/XAMPP/xamppfiles/bin/php workers/earn_worker.php
/Applications/XAMPP/xamppfiles/bin/php workers/gacha_role_worker.php
```

## Canonical Policy

`tbl_raw_event` is the canonical event timeline. Event types must be official Discord gateway event names only. Bot-log repair is limited to the approved log channels and must map back to official gateway event names.
