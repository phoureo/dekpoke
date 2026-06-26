<?php
$tiers = [
    ['tier' => 'TIER 1', 'name' => 'Mini Bit',    'price' => '฿29',  'bonus' => '+300 โปกบิต',   'image' => 'icon_topup-1.png'],
    ['tier' => 'TIER 2', 'name' => 'Cute Bit',    'price' => '฿59',  'bonus' => '+650 โปกบิต',   'image' => 'icon_topup-2.png'],
    ['tier' => 'TIER 3', 'name' => 'Arcade Bit',  'price' => '฿129', 'bonus' => '+1,500 โปกบิต', 'image' => 'icon_topup-3.png'],
    ['tier' => 'TIER 4', 'name' => 'Mega Chip',   'price' => '฿249', 'bonus' => '+3,100 โปกบิต', 'image' => 'icon_topup-4.png'],
    ['tier' => 'TIER 5', 'name' => 'Galaxy Chip', 'price' => '฿499', 'bonus' => '+6,800 โปกบิต', 'image' => 'icon_topup-5.png'],
    ['tier' => 'TIER 6', 'name' => 'Legend Chip', 'price' => '฿999', 'bonus' => '+15,000 โปกบิต', 'image' => 'icon_topup-6.png'],
];
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เติมเงินเกม</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@600;700;800;900&display=swap');

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: transparent;
            font-family: 'Noto Sans Thai', system-ui, sans-serif;
            color: #56336f;
        }

        body { padding: 18px; }

        .topup-list {
            width: min(100%, 980px);
            margin: 0 auto;
            display: grid;
            gap: 12px;
        }

        .tier-card {
            min-height: 126px;
            display: grid;
            grid-template-columns: 36% 64%;
            align-items: center;
            padding: 12px 16px;
            border-radius: 28px;
            background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(250,245,255,.92));
            border: 3px solid #dfcdf6;
            box-shadow:
                inset 0 2px 0 rgba(255,255,255,.9),
                0 8px 18px rgba(116, 75, 166, .12);
        }

        .tier-image-box {
            height: 98px;
            margin-right: 16px;
            display: grid;
            place-items: center;
            border-radius: 24px;
            background:
                radial-gradient(circle at 32% 20%, rgba(255,255,255,.95), rgba(255,255,255,.12) 55%),
                linear-gradient(135deg, rgba(250,248,255,.96), rgba(238,246,255,.9));
            border: 3px solid #e4d7f8;
            overflow: hidden;
        }

        .tier-image-box img {
            width: 138px;
            max-width: 94%;
            max-height: 86px;
            object-fit: contain;
            filter: drop-shadow(0 8px 10px rgba(92, 63, 139, .22));
        }

        .tier-content {
            min-width: 0;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 14px;
        }

        .tier-text {
            min-width: 0;
            display: grid;
            gap: 7px;
        }

        .tier-title-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .tier-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 112px;
            padding: 8px 16px 7px;
            border-radius: 999px;
            color: #fff;
            font-size: 18px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: .5px;
            background: linear-gradient(180deg, #ff9ddd, #c978ff 58%, #8a73ff);
            box-shadow:
                inset 0 3px 0 rgba(255,255,255,.5),
                0 4px 0 rgba(107, 70, 181, .18);
            text-shadow: 0 1px 0 rgba(85, 39, 122, .32);
        }

        .tier-name {
            margin: 0;
            color: #503069;
            font-size: clamp(28px, 4vw, 43px);
            font-weight: 900;
            line-height: .95;
            white-space: nowrap;
        }

        .tier-bonus {
            margin: 0;
            color: #8d67a9;
            font-size: clamp(18px, 2.1vw, 25px);
            font-weight: 900;
            line-height: 1.08;
        }

        .price-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 166px;
            padding: 14px 20px 12px;
            border-radius: 999px;
            color: #7b3900;
            font-size: clamp(31px, 4.2vw, 48px);
            font-weight: 900;
            line-height: 1;
            text-decoration: none;
            white-space: nowrap;
            background: linear-gradient(180deg, #fff5aa 0%, #ffd45a 48%, #ffab36 100%);
            border: 5px solid #d47d13;
            box-shadow:
                inset 0 5px 0 rgba(255,255,255,.72),
                0 7px 0 #a75b0d,
                0 12px 16px rgba(132, 74, 22, .17);
            text-shadow: 0 2px 0 rgba(255,255,255,.35);
        }

        .price-button::before,
        .price-button::after {
            content: '✦';
            margin: 0 .28em;
            color: #fff2a2;
            font-size: .44em;
            text-shadow: 0 1px 0 #a95b0e;
        }

        @media (max-width: 760px) {
            body { padding: 10px; }
            .topup-list { gap: 10px; }
            .tier-card {
                grid-template-columns: 38% 62%;
                min-height: 112px;
                padding: 10px;
                border-radius: 22px;
            }
            .tier-image-box {
                height: 88px;
                margin-right: 10px;
                border-radius: 18px;
            }
            .tier-image-box img {
                width: 112px;
                max-height: 76px;
            }
            .tier-content {
                grid-template-columns: 1fr;
                gap: 8px;
                justify-items: start;
            }
            .tier-badge {
                min-width: 86px;
                font-size: 13px;
                padding: 7px 11px 6px;
            }
            .tier-name { font-size: 26px; }
            .tier-bonus { font-size: 15px; }
            .price-button {
                min-width: 128px;
                padding: 10px 14px 9px;
                border-width: 4px;
                font-size: 28px;
            }
        }

        @media (max-width: 430px) {
            .tier-card {
                grid-template-columns: 118px 1fr;
            }
            .tier-image-box {
                height: 82px;
            }
            .tier-title-row {
                gap: 7px;
            }
            .tier-name { font-size: 22px; }
            .tier-bonus { font-size: 13px; }
            .price-button { font-size: 24px; min-width: 112px; }
        }
    </style>
</head>
<body>
    <main class="topup-list" aria-label="รายการเติมเงินเกม">
        <?php foreach ($tiers as $tier): ?>
            <article class="tier-card">
                <div class="tier-image-box">
                    <img src="images/<?= htmlspecialchars($tier['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($tier['name'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="tier-content">
                    <div class="tier-text">
                        <div class="tier-title-row">
                            <span class="tier-badge"><?= htmlspecialchars($tier['tier'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h2 class="tier-name"><?= htmlspecialchars($tier['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                        <p class="tier-bonus">รับเพิ่ม <?= htmlspecialchars($tier['bonus'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <a class="price-button" href="#" aria-label="เติมเงิน <?= htmlspecialchars($tier['name'], ENT_QUOTES, 'UTF-8') ?> ราคา <?= htmlspecialchars($tier['price'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($tier['price'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </main>
</body>
</html>
