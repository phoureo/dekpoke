<?php

declare(strict_types=1);

require __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

PlayerAuth::logout();
Auth::logout();

$defaultRedirect = './index.html?signed_out=1';
$redirect = trim((string) ($_GET['redirect'] ?? $defaultRedirect));
if ($redirect === '' || preg_match('/^(?:https?:)?\/\//i', $redirect)) {
    $redirect = $defaultRedirect;
}

?><!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Signing out...</title>
</head>
<body>
  <script>
    (function () {
      const redirectUrl = <?= json_encode($redirect, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?> || "./index.html";
      try {
        window.localStorage.removeItem("dekpoke:gacha:player-auth:v1");
      } catch (error) {
        // Ignore storage cleanup failures.
      }
      try {
        document.cookie = "gachaPlayerToken=; Max-Age=0; Path=/; SameSite=Lax";
      } catch (error) {
        // Ignore cookie cleanup failures.
      }
      window.location.replace(redirectUrl);
    }());
  </script>
</body>
</html>
