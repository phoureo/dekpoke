<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

$assetVersion = (string) (@filemtime(__DIR__ . '/assets/js/asset-manifest-editor.js') ?: time());
$runtimeVersion = (string) (@filemtime(__DIR__ . '/assets/js/asset-manifest-runtime.js') ?: time());
$cssVersion = (string) (@filemtime(__DIR__ . '/assets/css/editor.css') ?: time());
$csrfToken = Csrf::token();
?>
<!doctype html>
<html lang="th" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Asset Manifest Tool</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/editor.css?v=<?= htmlspecialchars($cssVersion) ?>">
</head>
<body class="editor-body">
  <div class="editor-shell is-three-column" id="assetManifestRoot">
    <header class="editor-topbar">
      <div class="editor-brand"><i class="fa-solid fa-boxes-stacked"></i><span>Asset Manifest</span></div>
      <span class="editor-pill" id="manifestModePill">กำลังโหลด</span>
      <span class="editor-pill" id="saveAllowedPill">กำลังตรวจสิทธิ์</span>
      <span class="editor-pill" id="gameVersionPill">Game v-</span>
      <span class="editor-pill" id="contentVersionPill">Content -</span>
      <div class="editor-topbar-spacer"></div>
      <button class="ui tiny button" id="refreshBootstrapButton" type="button"><i class="fa-solid fa-rotate"></i> โหลดใหม่</button>
      <button class="ui tiny button" id="saveDraftButton" type="button"><i class="fa-solid fa-floppy-disk"></i> บันทึกร่าง</button>
      <button class="ui tiny primary button" id="publishButton" type="button"><i class="fa-solid fa-upload"></i> เผยแพร่</button>
    </header>

    <aside class="editor-toolbar" aria-label="ตัวกรองหน้าใช้งาน">
      <button class="ui icon button is-active" type="button" data-page-filter="all" data-short="A" data-tip="ดู asset ทุกหน้า"><i class="fa-solid fa-layer-group"></i></button>
      <button class="ui icon button" type="button" data-page-filter="gacha-index" data-short="G" data-tip="กรองเฉพาะหน้า gacha"><i class="fa-solid fa-capsules"></i></button>
      <button class="ui icon button" type="button" data-page-filter="mileage-runtime" data-short="M" data-tip="กรองเฉพาะหน้า mileage"><i class="fa-solid fa-route"></i></button>
      <button class="ui icon button" id="addGroupButton" type="button" data-short="+" data-tip="เพิ่ม group ใหม่"><i class="fa-solid fa-folder-plus"></i></button>
      <button class="ui icon button" id="createRuleButton" type="button" data-short="R" data-tip="เพิ่มกฎให้ไฟล์ที่เลือก"><i class="fa-solid fa-plus"></i></button>
      <button class="ui icon button" id="duplicateRuleButton" type="button" data-short="D" data-tip="คัดลอกกฎของไฟล์ที่เลือก"><i class="fa-regular fa-copy"></i></button>
      <button class="ui icon button" id="removeRuleButton" type="button" data-short="-" data-tip="ลบกฎของไฟล์ที่เลือก"><i class="fa-solid fa-trash"></i></button>
    </aside>

    <main class="editor-workspace">
      <div class="asset-manifest-layout">
        <section class="editor-panel">
          <div class="editor-panel-header"><span><i class="fa-solid fa-folder-tree"></i> รายการไฟล์</span><span class="editor-muted" id="assetCountLabel">0</span></div>
          <div class="editor-panel-body">
            <div class="editor-grid-3">
              <label class="editor-field" style="grid-column: span 2;">ค้นหา
                <input id="assetSearchInput" type="search" placeholder="ค้นหา path / ชื่อไฟล์">
              </label>
              <label class="editor-field">Group
                <select id="groupFilterSelect"></select>
              </label>
            </div>
            <div class="editor-note">ไฟล์ที่ยังไม่มีกฎจะยังใช้งานได้ตามพฤติกรรมเดิมของหน้า แต่จะไม่คุม cache/version ผ่าน manifest จนกว่าจะเพิ่มกฎ.</div>
            <div class="editor-note">V1 จะยังคง preload core asset ที่ runtime ต้องใช้แน่ๆ เหมือนเดิมก่อน เพื่อไม่ให้หน้า gacha/mileage แตก แต่เราคุม `cache policy`, `version`, และ preload asset เสริมจาก manifest ได้ทันที.</div>
            <div id="assetList" class="editor-list asset-manifest-list" style="margin-top:8px"></div>
          </div>
        </section>

        <section class="editor-panel">
          <div class="editor-panel-header"><span><i class="fa-solid fa-sliders"></i> กฎของไฟล์</span><span class="editor-muted" id="selectedAssetBadge">ยังไม่เลือก</span></div>
          <div class="editor-panel-body">
            <div id="assetPreview" class="editor-asset-preview"><div class="editor-note">เลือกไฟล์จากด้านซ้ายเพื่อดูตัวอย่างและตั้งค่ากฎ cache / preload / version</div></div>
            <div id="assetInspector"></div>
          </div>
        </section>
      </div>
    </main>

    <aside class="editor-inspector">
      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-tags"></i> เวอร์ชัน</span></div>
        <div class="editor-panel-body">
          <div class="editor-grid-2">
            <label class="editor-field">Game Version
              <input id="gameVersionInput" type="text" placeholder="เช่น 1.2.0">
            </label>
            <label class="editor-field">Content Version
              <input id="contentVersionInput" type="text" placeholder="เช่น 20260626-001">
            </label>
          </div>
          <label class="editor-field">Notes
            <textarea id="manifestNotesInput" class="editor-textarea" placeholder="บันทึกว่ารอบนี้ต้องการบังคับรีโหลดไฟล์ไหน หรือ release นี้เปลี่ยนอะไร"></textarea>
          </label>
          <div class="editor-note">แนะนำให้เปลี่ยน `contentVersion` ทุกครั้งที่อยากบังคับให้ asset ที่ตั้ง `inherit-content-version` เรียกใหม่.</div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-object-group"></i> Groups</span></div>
        <div class="editor-panel-body">
          <div id="groupList" class="editor-list"></div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-clock-rotate-left"></i> Versions</span></div>
        <div class="editor-panel-body">
          <div id="versionList" class="editor-list"></div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-code"></i> JSON</span></div>
        <div class="editor-panel-body">
          <textarea id="jsonPreview" class="editor-textarea" spellcheck="false" readonly></textarea>
        </div>
      </section>
    </aside>

    <footer class="editor-statusbar">
      <span>Managed <strong id="managedCountLabel">0</strong></span>
      <span>Preload <strong id="preloadCountLabel">0</strong></span>
      <span>Groups <strong id="groupCountLabel">0</strong></span>
      <span>Versions <strong id="versionCountLabel">0</strong></span>
      <span id="selectionStatusLabel">ยังไม่เลือกไฟล์</span>
    </footer>
  </div>

  <script>
    window.ASSET_MANIFEST_EDITOR_BOOT = {
      apiUrl: "asset-manifest-api.php",
      csrfToken: <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
      runtimeScriptVersion: <?= json_encode($runtimeVersion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };
  </script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
  <script src="assets/js/editor-common.js?v=<?= htmlspecialchars($assetVersion) ?>"></script>
  <script src="assets/js/asset-manifest-runtime.js?v=<?= htmlspecialchars($runtimeVersion) ?>"></script>
  <script src="assets/js/asset-manifest-editor.js?v=<?= htmlspecialchars($assetVersion) ?>"></script>
</body>
</html>
