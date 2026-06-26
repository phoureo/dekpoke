<?php

declare(strict_types=1);

require __DIR__ . '/core/Bootstrap.php';

$setupError = null;
$user = null;
try {
    Bootstrap::init();
    $user = Auth::currentUser();
} catch (Throwable $exception) {
    $setupError = $exception->getMessage();
}

$appName = Bootstrap::config('app.name', 'Dekpoke Workspace Console');
$baseUrl = rtrim((string) Bootstrap::config('app.baseUrl', '/discord'), '/');
$csrfToken = $setupError ? '' : Csrf::token();
$initialPage = preg_replace('/[^a-z_]/', '', (string) ($_GET['page'] ?? 'activity')) ?: 'activity';
$appCssVersion = (string) (@filemtime(Bootstrap::rootPath('assets/css/app.css')) ?: time());
$appJsVersion = (string) (@filemtime(Bootstrap::rootPath('assets/js/app.js')) ?: time());
$guild = null;
$guildIconUrl = null;
$guildBannerUrl = null;
if (!$setupError && $user) {
    $guild = Database::fetch(
        'SELECT guildId, guildName, iconHash, metadataJson
         FROM tbl_guild
         WHERE guildId = :guildId',
        ['guildId' => (string) Bootstrap::config('discord.guildId', '')]
    );
    if ($guild) {
        $guildMeta = json_decode((string) ($guild['metadataJson'] ?? ''), true);
        $guildIconUrl = DiscordAssets::guildIcon($guild['guildId'], $guild['iconHash'], 96);
        $guildBannerUrl = DiscordAssets::guildBanner($guild['guildId'], is_array($guildMeta) ? ($guildMeta['banner'] ?? null) : null, 600);
    }
}
?>
<!doctype html>
<html lang="th" data-theme="light" data-lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($appName) ?></title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/app.css?v=<?= htmlspecialchars($appCssVersion) ?>">
</head>
<body>
<?php if ($setupError): ?>
  <main class="orbit-login">
    <section class="orbit-login-panel">
      <h1 class="ui header"><?= htmlspecialchars($appName) ?></h1>
      <div class="ui negative message">
        <div class="header">Setup ยังไม่พร้อม</div>
        <p><?= htmlspecialchars($setupError) ?></p>
      </div>
      <p class="orbit-muted">นำเข้า `sql/schema.sql` และสร้าง `config/config.local.php` จากไฟล์ตัวอย่างก่อนเข้าใช้งาน</p>
    </section>
  </main>
<?php elseif (!$user): ?>
  <main class="orbit-login">
    <section class="orbit-login-panel">
      <div class="orbit-brand">
        <div class="orbit-brand-mark"><i class="fa-brands fa-discord"></i></div>
        <div>
          <div class="orbit-brand-title"><?= htmlspecialchars($appName) ?></div>
          <div class="orbit-brand-subtitle">Canonical gateway-first workspace</div>
        </div>
      </div>
      <div class="ui divider"></div>
      <p class="orbit-muted">เข้าสู่ระบบด้วย Discord เพื่อแยกสิทธิ์ทีมงาน, staff log และ audit ทุก action ในระบบ</p>
      <a class="ui primary fluid button" href="<?= htmlspecialchars($baseUrl) ?>/api/auth/login.php">
        <i class="fa-brands fa-discord"></i> Login with Discord
      </a>
    </section>
  </main>
<?php else: ?>
  <div id="appRoot" class="orbit-shell" data-base-url="<?= htmlspecialchars($baseUrl) ?>" data-csrf="<?= htmlspecialchars($csrfToken) ?>" data-initial-page="<?= htmlspecialchars($initialPage) ?>">
    <aside class="orbit-sidebar">
      <div class="orbit-sidebar-top">
        <div class="orbit-server-card">
          <div class="orbit-server-banner"<?= $guildBannerUrl ? ' style="background-image:url(' . htmlspecialchars($guildBannerUrl) . ')"' : '' ?>></div>
          <div class="orbit-server-body">
            <div class="orbit-server-icon">
              <?php if ($guildIconUrl): ?>
                <img src="<?= htmlspecialchars($guildIconUrl) ?>" alt="">
              <?php else: ?>
                <i class="fa-brands fa-discord"></i>
              <?php endif; ?>
            </div>
            <div class="orbit-server-copy">
              <div class="orbit-brand-title"><?= htmlspecialchars($guild['guildName'] ?? 'Dekpoke Workspace') ?></div>
              <div class="orbit-brand-subtitle" data-i18n="shell.subtitle">Single-server console</div>
            </div>
            <button class="ui icon tiny button orbit-nav-toggle" id="navToggleAll" data-expanded="1" data-tooltip="Expand / collapse menu">
              <i class="fa-solid fa-minus"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="ui accordion orbit-sidebar-scroll" id="sidebarAccordion">
        <div class="title active"><i class="dropdown icon"></i> <span data-i18n="nav.monitor">Monitor</span></div>
        <div class="content active">
          <a class="orbit-nav-item" data-page="roles"><i class="fa-solid fa-shield-halved"></i> <span data-i18n="page.roles">Roles</span></a>
          <a class="orbit-nav-item" data-page="activity"><i class="fa-solid fa-wave-square"></i> <span data-i18n="page.activity">Activity</span></a>
          <a class="orbit-nav-item" data-page="messages"><i class="fa-solid fa-message"></i> <span data-i18n="page.messages">Messages</span></a>
        </div>
        <div class="title active"><i class="dropdown icon"></i> <span data-i18n="nav.rewards">Rewards</span></div>
        <div class="content active">
          <a class="orbit-nav-item" data-page="earn_settings"><i class="fa-solid fa-coins"></i> <span data-i18n="page.earn_settings">Earn Settings</span></a>
          <a class="orbit-nav-item" data-page="earn_manual"><i class="fa-solid fa-hand-holding-dollar"></i> <span data-i18n="page.earn_manual">Manual Earn</span></a>
          <a class="orbit-nav-item" data-page="reward_report"><i class="fa-solid fa-chart-line"></i> <span data-i18n="page.reward_report">Reward Report</span></a>
          <a class="orbit-nav-item" data-page="mileage_report"><i class="fa-solid fa-road"></i> <span data-i18n="page.mileage_report">Mileage Report</span></a>
          <a class="orbit-nav-item" data-page="shop_report"><i class="fa-solid fa-receipt"></i> <span data-i18n="page.shop_report">Shop Report</span></a>
          <a class="orbit-nav-item" data-page="shop_member_bags"><i class="fa-solid fa-bag-shopping"></i> <span data-i18n="page.shop_member_bags">Member Bags</span></a>
          <a class="orbit-nav-item" data-page="bag_transaction_report"><i class="fa-solid fa-arrow-right-arrow-left"></i> <span data-i18n="page.bag_transaction_report">Bag Transactions</span></a>
          <a class="orbit-nav-item" data-page="gacha_shop"><i class="fa-solid fa-store"></i> <span data-i18n="page.gacha_shop">Shop Setting</span></a>
        </div>
        <div class="title active"><i class="dropdown icon"></i> <span data-i18n="nav.gachapon">Gachapon</span></div>
        <div class="content active">
          <a class="orbit-nav-item" data-page="gacha_prize"><i class="fa-solid fa-gift"></i> <span data-i18n="page.gacha_prize">Prize</span></a>
          <a class="orbit-nav-item" data-page="gacha_campaign"><i class="fa-solid fa-bullseye"></i> <span data-i18n="page.gacha_campaign">Campaign Setting</span></a>
          <a class="orbit-nav-item" data-page="gacha_report"><i class="fa-solid fa-chart-line"></i> <span data-i18n="page.gacha_report">Report</span></a>
        </div>
        <div class="title active"><i class="dropdown icon"></i> <span data-i18n="nav.system">System</span></div>
        <div class="content active">
          <a class="orbit-nav-item" data-page="admin"><i class="fa-solid fa-screwdriver-wrench"></i> <span data-i18n="page.admin">Admin</span></a>
          <a class="orbit-nav-item" data-page="permission"><i class="fa-solid fa-user-shield"></i> <span data-i18n="page.permission">Permission</span></a>
          <a class="orbit-nav-item" data-page="backfill"><i class="fa-solid fa-clock-rotate-left"></i> <span data-i18n="page.backfill">Backfill</span></a>
          <a class="orbit-nav-item" data-page="logs"><i class="fa-solid fa-clipboard-list"></i> <span data-i18n="page.logs">Logs</span></a>
        </div>
      </div>

      <div class="orbit-sidebar-user">
        <span class="orbit-user">
          <img class="orbit-avatar" src="<?= htmlspecialchars(DiscordAssets::avatar($user['discordUserId'], $user['avatarHash'], 64)) ?>" alt="">
          <span class="orbit-user-text">
            <span class="orbit-user-name"><?= htmlspecialchars($user['displayName']) ?></span>
            <span class="orbit-user-sub"><?= htmlspecialchars($user['discordUserName'] ?? $user['discordUserId']) ?></span>
          </span>
        </span>
        <div class="orbit-sidebar-user-actions">
          <button class="ui icon button" id="themeToggle" data-tooltip="Toggle theme"><i class="fa-solid fa-moon"></i></button>
          <button class="ui button orbit-language-button" id="languageToggle" data-tooltip="Switch language" type="button">TH</button>
          <button class="ui icon button" id="logoutButton" data-tooltip="Logout"><i class="fa-solid fa-right-from-bracket"></i></button>
        </div>
      </div>
    </aside>

    <section class="orbit-main">
      <header class="orbit-topbar">
        <div class="orbit-page-title" id="pageTitle">Activity</div>
        <span class="orbit-status" id="syncStatus"><i class="fa-solid fa-circle"></i> ready</span>
      </header>
      <div class="orbit-degraded-banner" id="degradedBanner" hidden></div>
      <main class="orbit-content" id="pageContainer">
        <div class="orbit-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading</div>
      </main>
    </section>
  </div>
  <div class="orbit-profile-popover" id="profilePopover"></div>
  <aside class="orbit-drawer" id="orbitDrawer">
    <div class="orbit-drawer-header">
      <div class="orbit-drawer-actions">
        <button class="ui icon button" id="drawerBackClose" data-tooltip="Back to menu"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="ui icon button" id="drawerClose" data-tooltip="Close"><i class="fa-solid fa-xmark"></i></button>
        <button class="ui icon button" id="drawerModeToggle" data-tooltip="Toggle drawer mode"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></button>
      </div>
      <strong id="drawerTitle">Detail</strong>
    </div>
    <div class="orbit-drawer-body" id="drawerBody"></div>
  </aside>
<?php endif; ?>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
  <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/app.js?v=<?= htmlspecialchars($appJsVersion) ?>"></script>
</body>
</html>
