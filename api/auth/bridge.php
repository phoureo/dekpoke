<?php

declare(strict_types=1);

require __DIR__ . '/../init.php';

$auth = DiscordOAuth::beginAuthorization($_GET['flow'] ?? 'gacha', $_GET['return_to'] ?? null);
AuditLogger::access('oauth_bridge_open', 'auth', 'discord', [
    'flow' => $auth['flow'],
    'redirectUri' => $auth['redirectUri'],
    'returnTo' => $auth['returnTo'],
]);

$authorizeUrl = $auth['authorizeUrl'];
$returnTo = $auth['returnTo'];

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="robots" content="noindex,nofollow">
  <title>Discord Sign In</title>
  <style>
    :root {
      color-scheme: dark;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: #080812;
      color: #fff;
    }

    * {
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      margin: 0;
      display: grid;
      place-items: center;
      padding: 28px;
      background:
        radial-gradient(circle at 50% 22%, rgba(108, 120, 255, 0.28), transparent 34%),
        radial-gradient(circle at 20% 80%, rgba(255, 117, 188, 0.18), transparent 34%),
        #080812;
    }

    main {
      width: min(100%, 420px);
      display: grid;
      gap: 18px;
      text-align: center;
    }

    .logo {
      width: 74px;
      height: 74px;
      margin: 0 auto 4px;
      border-radius: 24px;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #69c8ff, #5865f2 72%);
      box-shadow: 0 18px 48px rgba(88, 101, 242, 0.34);
      font-size: 38px;
      font-weight: 800;
    }

    h1 {
      margin: 0;
      font-size: 28px;
      line-height: 1.1;
    }

    p {
      margin: 0;
      color: rgba(255, 255, 255, 0.72);
      font-size: 15px;
      line-height: 1.55;
    }

    .button {
      width: 100%;
      min-height: 58px;
      border: 0;
      border-radius: 18px;
      display: inline-grid;
      place-items: center;
      padding: 0 22px;
      background: linear-gradient(135deg, #6fd2ff, #5865f2 62%, #7658ff);
      color: #fff;
      text-decoration: none;
      font-size: 17px;
      font-weight: 800;
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.55),
        0 18px 38px rgba(88, 101, 242, 0.34);
    }

    .secondary {
      color: rgba(255, 255, 255, 0.66);
      font-size: 13px;
    }

    .secondary a {
      color: #9edfff;
    }
  </style>
</head>
<body>
  <main>
    <div class="logo" aria-hidden="true">D</div>
    <h1>Sign in with Discord</h1>
    <p>กำลังเปิดหน้าล็อกอิน Discord ถ้าแอปไม่พาไปต่อ ให้แตะปุ่มด้านล่างอีกครั้ง</p>
    <a id="continueLink" class="button" href="<?= h($authorizeUrl) ?>" target="_blank" rel="external noopener">Continue with Discord</a>
    <p class="secondary">หลังล็อกอินสำเร็จ ระบบจะพากลับไปที่ <a href="<?= h($returnTo) ?>">หน้าเกม</a></p>
  </main>
  <script>
    const continueLink = document.getElementById("continueLink");
    const isDiscordApp = /Discord/i.test(navigator.userAgent || "");
    if (!isDiscordApp) {
      window.setTimeout(() => {
        window.location.assign(continueLink.href);
      }, 420);
    }
  </script>
</body>
</html>
