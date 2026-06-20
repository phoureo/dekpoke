<?php

declare(strict_types=1);

$lang = strtolower((string) ($_GET['lang'] ?? 'th')) === 'en' ? 'en' : 'th';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    @font-face { font-family: 'GachaUi'; src: url('fonts/FCVisionRounded-Bold.woff2') format('woff2'); font-display: swap; }
    html, body { margin: 0; min-height: 100%; background: transparent; color: #fff; font-family: GachaUi, system-ui, sans-serif; }
    body { overflow: hidden; }
  </style>
</head>
<body></body>
</html>
