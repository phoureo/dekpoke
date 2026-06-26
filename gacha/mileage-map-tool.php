<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

$boardCode = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim((string) ($_GET['boardCode'] ?? GachaMileageService::DEFAULT_BOARD_CODE)))) ?: GachaMileageService::DEFAULT_BOARD_CODE;
$assetVersion = (string) (@filemtime(__DIR__ . '/assets/js/mileage-editor.js') ?: time());
$cssVersion = (string) (@filemtime(__DIR__ . '/assets/css/editor.css') ?: time());
?>
<!doctype html>
<html lang="th" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>ตัวแก้ไขแผนที่ Mileage</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/editor.css?v=<?= htmlspecialchars($cssVersion) ?>">
</head>
<body class="editor-body">
  <div class="editor-shell" id="mileageEditorRoot">
    <header class="editor-topbar">
      <div class="editor-brand"><i class="fa-solid fa-route"></i><span>ตัวแก้ไขแผนที่ Mileage</span></div>
      <span class="editor-pill" id="boardCodePill"><?= htmlspecialchars($boardCode) ?></span>
      <span class="editor-pill is-warn" id="draftStatePill">กำลังโหลด</span>
      <span class="editor-pill" id="selectionPill">ยังไม่เลือก</span>
      <div class="editor-mode-tabs" role="tablist" aria-label="โหมดทำงาน">
        <button class="ui tiny icon button is-active" type="button" data-view-mode="design" data-tip="ออกแบบบน canvas ด้วยเครื่องมือจับ ลาก วาง"><i class="fa-solid fa-pen-ruler"></i> ออกแบบ</button>
        <button class="ui tiny icon button" type="button" data-view-mode="preview" data-tip="ดูการทำงานจริงด้วย runtime เดียวกับหน้าจริง"><i class="fa-solid fa-play"></i> พรีวิว</button>
        <button class="ui tiny icon button" type="button" data-view-mode="split" data-tip="ออกแบบและดูพรีวิวควบคู่กัน"><i class="fa-solid fa-table-columns"></i> เทียบคู่</button>
      </div>
      <div class="editor-topbar-spacer"></div>
      <button class="ui tiny button" id="saveDraftButton" type="button" data-tip="บันทึกเป็นฉบับร่าง ไม่กระทบหน้าจริง"><i class="fa-solid fa-floppy-disk"></i> บันทึกร่าง</button>
      <button class="ui tiny button" id="stickyToolButton" type="button" data-tip="เปิดแล้วเครื่องมือวางจะไม่เด้งกลับไปเลือกหลังวาง"><i class="fa-solid fa-thumbtack"></i> วางต่อเนื่อง</button>
      <button class="ui tiny primary button" id="publishButton" type="button" data-tip="เผยแพร่ draft เป็น board จริงและเก็บ version ก่อนหน้า"><i class="fa-solid fa-upload"></i> เผยแพร่</button>
      <button class="ui tiny button" id="refreshButton" type="button" data-tip="โหลดข้อมูลใหม่"><i class="fa-solid fa-rotate"></i></button>
    </header>

    <aside class="editor-toolbar" aria-label="เครื่องมือ">
      <button class="ui icon button is-active" type="button" data-tool="select" data-short="V" data-tip="เลือก ย้าย ปรับขนาด และคลุมหลายชิ้น"><i class="fa-solid fa-arrow-pointer"></i></button>
      <button class="ui icon button" type="button" data-tool="pan" data-short="H" data-tip="เลื่อนมุมมอง กด Space ค้างก็ใช้ได้"><i class="fa-solid fa-hand"></i></button>
      <button class="ui icon button" type="button" data-tool="marquee" data-short="M" data-tip="คลุมเลือกหลายชิ้น"><i class="fa-regular fa-square"></i></button>
      <button class="ui icon button" type="button" data-tool="step" data-short="S" data-tip="เพิ่มช่องเดินใหม่ วางแล้วกลับไปโหมดเลือก"><i class="fa-solid fa-location-dot"></i></button>
      <button class="ui icon button" type="button" data-tool="reward-node" data-short="R" data-tip="วางจุดรางวัลที่ผูกกับ template"><i class="fa-solid fa-gift"></i></button>
      <button class="ui icon button" type="button" data-tool="sprite" data-short="I" data-tip="วาง sprite จาก config ล่าสุดหรือจาก palette"><i class="fa-regular fa-image"></i></button>
      <button class="ui icon button" type="button" data-tool="segment" data-short="G" data-tip="เลือก/แก้ส่วนภาพพื้นหลังของแผนที่"><i class="fa-solid fa-layer-group"></i></button>
      <button class="ui icon button" type="button" id="fitButton" data-short="F" data-tip="ปรับมุมมองให้เห็นทั้งหมด"><i class="fa-solid fa-expand"></i></button>
      <button class="ui icon button" type="button" id="undoButton" data-short="Z" data-tip="ย้อนกลับ"><i class="fa-solid fa-rotate-left"></i></button>
      <button class="ui icon button" type="button" id="redoButton" data-short="Y" data-tip="ทำซ้ำ"><i class="fa-solid fa-rotate-right"></i></button>
    </aside>

    <main class="editor-workspace is-design-only" id="mileageWorkspace">
      <div class="editor-stage-wrap">
        <div id="mapStage" class="editor-stage" aria-label="canvas ออกแบบแผนที่"></div>
        <div class="editor-floating-hint" id="stageHint">โหมดเริ่มต้นคือเลือก กด Space ค้างเพื่อเลื่อนมุมมอง ใช้ Shift คลิกหรือคลุมเพื่อเลือกหลายชิ้น</div>
      </div>
      <div class="editor-preview-wrap">
        <iframe
          id="runtimePreviewFrame"
          class="editor-runtime-frame"
          src="mileage.php?boardCode=<?= urlencode($boardCode) ?>&preview=1"
          title="พรีวิวการทำงานจริง"
          loading="eager"
        ></iframe>
        <div class="editor-preview-toolbar">
          <button class="ui mini icon button" id="previewPlayButton" type="button" data-tip="เล่น/หยุดการเดินจำลอง"><i class="fa-solid fa-play"></i></button>
          <button class="ui mini icon button" id="previewZoomOutButton" type="button" data-tip="ซูมออกในพรีวิว"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
          <button class="ui mini icon button" id="previewZoomInButton" type="button" data-tip="ซูมเข้าในพรีวิว"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
          <button class="ui mini icon button" id="previewFocusButton" type="button" data-tip="โฟกัสตัวอย่างผู้เล่น"><i class="fa-solid fa-crosshairs"></i></button>
          <label class="editor-field" style="margin:0">ก้าว
            <input id="previewStepRange" type="range" min="-1" max="0" value="-1" step="0.05">
          </label>
          <label class="editor-field" style="margin:0">สปีด
            <input id="previewSpeedRange" type="range" min="0.25" max="3" value="1" step="0.25">
          </label>
          <span class="editor-pill" id="previewStepLabel">เริ่มต้น</span>
          <button class="ui mini button" id="previewFriendsButton" type="button" data-tip="เปิด/ปิดตัวอย่างเพื่อน"><i class="fa-solid fa-user-group"></i> เพื่อน</button>
          <button class="ui mini button" id="previewFitButton" type="button" data-tip="จัดมุมมองพรีวิว"><i class="fa-solid fa-expand"></i></button>
        </div>
      </div>
    </main>

    <aside class="editor-inspector">
      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-sliders"></i> ตัวเลือก</span><span id="activeToolLabel">เลือก</span></div>
        <div class="editor-panel-body" id="selectionInspector"></div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-map"></i> แผนที่</span></div>
        <div class="editor-panel-body">
          <label class="editor-field">ชื่อแผนที่
            <input id="boardTitleInput" type="text">
          </label>
          <div class="editor-grid-3">
            <label class="editor-field">ขนาดรางวัล
              <input id="rewardMarkerSizeInput" type="number" min="26" max="96" step="1">
              <input type="range" min="26" max="96" step="1" data-range-target="rewardMarkerSizeInput">
            </label>
            <label class="editor-field">สเกลเหรียญ
              <input id="pickupScaleInput" type="number" min="0.7" max="2.4" step="0.05">
              <input type="range" min="0.7" max="2.4" step="0.05" data-range-target="pickupScaleInput">
            </label>
            <label class="editor-field">จำนวนเหรียญ
              <input id="pickupCountInput" type="number" min="0.7" max="3.2" step="0.05">
              <input type="range" min="0.7" max="3.2" step="0.05" data-range-target="pickupCountInput">
            </label>
          </div>
          <div class="editor-grid-3">
            <label class="editor-field">แสงทางเดิน <input id="fxPathGlowInput" type="number" min="0" max="2" step="0.05"><input type="range" min="0" max="2" step="0.05" data-range-target="fxPathGlowInput"></label>
            <label class="editor-field">เมฆ <input id="fxCloudInput" type="number" min="0" max="2" step="0.05"><input type="range" min="0" max="2" step="0.05" data-range-target="fxCloudInput"></label>
            <label class="editor-field">เพื่อน <input id="fxFriendCountInput" type="number" min="0" max="12" step="1"><input type="range" min="0" max="12" step="1" data-range-target="fxFriendCountInput"></label>
          </div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-hand-pointer"></i> ลากไปวาง</span></div>
        <div class="editor-panel-body is-compact">
          <div class="editor-list" id="quickPalette">
            <div class="editor-drop-token" draggable="true" data-drag-kind="step"><span><i class="fa-solid fa-location-dot"></i> เพิ่มช่องเดิน</span><strong>#</strong></div>
            <div class="editor-drop-token" draggable="true" data-drag-kind="reward-node"><span><i class="fa-solid fa-gift"></i> เพิ่มจุดรางวัล</span><strong>R</strong></div>
            <div class="editor-drop-token" draggable="true" data-drag-kind="sprite"><span><i class="fa-regular fa-image"></i> วาง Sprite</span><strong>IMG</strong></div>
          </div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-layer-group"></i> พื้นหลัง</span></div>
        <div class="editor-panel-body">
          <div id="segmentList" class="editor-list"></div>
          <div class="editor-actions" style="margin-top:8px">
            <button class="ui tiny button" id="addTopSegmentButton" type="button"><i class="fa-solid fa-arrow-up"></i> ต่อด้านบน</button>
            <button class="ui tiny button" id="addBottomSegmentButton" type="button"><i class="fa-solid fa-arrow-down"></i> ต่อด้านล่าง</button>
            <button class="ui tiny button" id="deleteSegmentButton" type="button"><i class="fa-solid fa-trash"></i></button>
          </div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-icons"></i> ไอคอนรางวัล</span></div>
        <div class="editor-panel-body">
          <div class="editor-actions">
            <label class="ui tiny button" for="iconUploadInput"><i class="fa-solid fa-upload"></i> อัปโหลด</label>
            <input id="iconUploadInput" class="editor-hidden" type="file" accept="image/png,image/jpeg,image/webp,image/gif">
            <button class="ui tiny button" id="addIconTemplateButton" type="button"><i class="fa-solid fa-plus"></i> เปล่า</button>
          </div>
          <div id="iconTemplateList" class="editor-list" style="margin-top:8px"></div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-box-open"></i> Template รางวัล</span></div>
        <div class="editor-panel-body">
          <div class="editor-actions">
            <button class="ui tiny button" id="addRewardTemplateButton" type="button"><i class="fa-solid fa-plus"></i> เพิ่ม</button>
          </div>
          <div id="rewardTemplateList" class="editor-list" style="margin-top:8px"></div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-folder-open"></i> คลังไฟล์</span><button class="ui mini icon button" id="refreshAssetButton" type="button" data-tip="โหลดรายการไฟล์ใหม่"><i class="fa-solid fa-rotate"></i></button></div>
        <div class="editor-panel-body">
          <div class="editor-asset-toolbar">
            <label class="editor-field">
              <input id="assetSearchInput" type="search" placeholder="ค้นหาชื่อไฟล์ / path">
            </label>
            <div class="editor-mode-tabs" aria-label="รูปแบบการดูคลังไฟล์">
              <button class="ui mini icon button is-active" type="button" data-asset-view="grid" data-tip="ดูแบบตารางรูป"><i class="fa-solid fa-grip"></i></button>
              <button class="ui mini icon button" type="button" data-asset-view="list" data-tip="ดูแบบรายการ"><i class="fa-solid fa-list"></i></button>
            </div>
          </div>
          <div id="assetPreview" class="editor-asset-preview"><div class="editor-note">คลิกไฟล์เพื่อดูตัวอย่าง ลากการ์ดไปวางบนแคนวาสได้ และถ้าระบบกำลังรอรูปอยู่จะมีปุ่มใช้ไฟล์นี้ให้เลย</div></div>
          <div id="assetBrowserList" class="editor-list editor-asset-list is-grid"></div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-clock-rotate-left"></i> เวอร์ชัน</span></div>
        <div class="editor-panel-body">
          <div id="versionList" class="editor-list"></div>
        </div>
      </section>

      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-code"></i> JSON</span></div>
        <div class="editor-panel-body">
          <textarea id="jsonPreview" class="editor-textarea" spellcheck="false"></textarea>
        </div>
      </section>
    </aside>

    <aside class="editor-layers">
      <section class="editor-panel">
        <div class="editor-panel-header"><span><i class="fa-solid fa-layer-group"></i> ลำดับชั้น</span><span class="editor-muted" id="layerCountLabel">0</span></div>
        <div class="editor-panel-body">
          <div id="layerList" class="editor-list"></div>
        </div>
      </section>
    </aside>

    <footer class="editor-statusbar">
      <span>ซูม <strong id="zoomLabel">100%</strong></span>
      <span>ช่องเดิน <strong id="stepCountLabel">0</strong></span>
      <span>รางวัลเดิม <strong id="rewardCountLabel">0</strong></span>
      <span>จุดรางวัล <strong id="rewardNodeCountLabel">0</strong></span>
      <span>Sprite <strong id="spriteCountLabel">0</strong></span>
      <span id="assetWarningLabel"></span>
    </footer>
  </div>

  <script>
    window.MILEAGE_EDITOR_BOOT = {
      boardCode: <?= json_encode($boardCode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
      apiUrl: "mileage-api.php"
    };
  </script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
  <script src="vendor/konva/konva.min.js"></script>
  <script src="assets/js/editor-common.js?v=<?= htmlspecialchars($assetVersion) ?>"></script>
  <script src="assets/js/mileage-editor.js?v=<?= htmlspecialchars($assetVersion) ?>"></script>
</body>
</html>
