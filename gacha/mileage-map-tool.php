<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

$boardCode = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim((string) ($_GET['boardCode'] ?? GachaMileageService::DEFAULT_BOARD_CODE)))) ?: GachaMileageService::DEFAULT_BOARD_CODE;
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>Mileage Map Tool</title>
  <style>
    :root {
      --bg: #080d19;
      --panel: rgba(10, 16, 38, 0.9);
      --line: rgba(137, 222, 255, 0.18);
      --ink: #edf5ff;
      --muted: rgba(225, 236, 255, 0.72);
      --accent: #8ff5ff;
      --pink: #ff9ee3;
      --warn: #ffd07a;
      --good: #8dffc2;
      --font: "FC Vision Rounded", system-ui, sans-serif;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-Regular.woff2") format("woff2");
      font-weight: 400;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-SemiBold.woff2") format("woff2");
      font-weight: 600;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-Bold.woff2") format("woff2");
      font-weight: 700;
      font-style: normal;
      font-display: swap;
    }

    * {
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
    }

    html,
    body {
      margin: 0;
      height: 100vh;
      min-height: 100vh;
      background:
        radial-gradient(circle at top, rgba(70, 98, 190, 0.16), transparent 34%),
        linear-gradient(180deg, #090d1c, #04070f 70%);
      color: var(--ink);
      font-family: var(--font);
      overflow: hidden;
    }

    body {
      display: grid;
      grid-template-columns: minmax(340px, 400px) 1fr;
      min-height: 0;
    }

    .sidebar {
      border-right: 1px solid rgba(137, 222, 255, 0.12);
      background: linear-gradient(180deg, rgba(8, 13, 31, 0.96), rgba(8, 13, 31, 0.88));
      padding: 16px;
      height: 100vh;
      min-height: 0;
      overflow-y: auto;
      overflow-x: hidden;
      overscroll-behavior: contain;
    }

    .panel {
      padding: 14px;
      border-radius: 20px;
      background: var(--panel);
      border: 1px solid var(--line);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.26);
      margin-bottom: 12px;
    }

    .panel h1,
    .panel h2 {
      margin: 0 0 8px;
    }

    .panel h1 {
      font-size: 20px;
    }

    .panel h2 {
      font-size: 15px;
    }

    .panel p,
    .panel li,
    .panel label,
    .panel small {
      color: var(--muted);
      line-height: 1.5;
      font-size: 13px;
    }

    .toolbar,
    .button-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 10px;
    }

    button,
    select,
    input,
    textarea {
      font: inherit;
      color: var(--ink);
    }

    button,
    select {
      border: 1px solid rgba(143, 245, 255, 0.16);
      background: rgba(18, 27, 61, 0.78);
      border-radius: 14px;
      padding: 10px 12px;
    }

    button {
      cursor: pointer;
      font-weight: 600;
    }

    button:disabled,
    select:disabled,
    input:disabled {
      opacity: 0.48;
      cursor: not-allowed;
    }

    button.is-active {
      background: linear-gradient(135deg, rgba(255, 155, 228, 0.92), rgba(137, 245, 255, 0.9));
      color: #0b1020;
      border-color: transparent;
    }

    input,
    textarea {
      width: 100%;
      border: 1px solid rgba(143, 245, 255, 0.14);
      background: rgba(12, 18, 43, 0.78);
      border-radius: 12px;
      padding: 10px 12px;
      margin-top: 6px;
    }

    textarea {
      min-height: 220px;
      resize: vertical;
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      font-size: 12px;
    }

    input[type="file"] {
      padding: 9px;
      cursor: pointer;
    }

    .field {
      margin-top: 10px;
    }

    .field-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 10px;
      border-radius: 999px;
      background: rgba(18, 27, 61, 0.72);
      border: 1px solid rgba(143, 245, 255, 0.14);
      color: var(--muted);
      font-size: 12px;
    }

    .status-pill strong {
      color: var(--ink);
    }

    .mini-note {
      display: block;
      margin-top: 6px;
      color: rgba(225, 236, 255, 0.56);
      font-size: 11px;
      line-height: 1.4;
    }

    .selection-size-strip {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 10px;
    }

    .anchor-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 8px;
      margin-top: 8px;
    }

    .anchor-button {
      min-height: 42px;
      padding: 8px 10px;
      font-size: 12px;
      letter-spacing: 0.01em;
    }

    .selection-bound-note {
      margin: 8px 0 0;
      color: rgba(225, 236, 255, 0.62);
      font-size: 12px;
      line-height: 1.45;
    }

    .good {
      color: var(--good);
    }

    .warn {
      color: var(--warn);
    }

    .stage {
      position: relative;
      min-width: 0;
      min-height: 0;
      height: 100vh;
      overflow: hidden;
    }

    canvas {
      width: 100%;
      height: 100%;
      display: block;
      touch-action: none;
      cursor: crosshair;
      background:
        linear-gradient(180deg, rgba(11, 19, 43, 0.4), rgba(4, 6, 14, 0.76)),
        radial-gradient(circle at top, rgba(117, 135, 255, 0.12), transparent 30%);
    }

    .hint {
      position: absolute;
      right: 16px;
      top: 16px;
      z-index: 2;
      max-width: 320px;
      padding: 12px 14px;
      border-radius: 18px;
      background: rgba(8, 14, 31, 0.84);
      border: 1px solid rgba(143, 245, 255, 0.12);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.22);
      backdrop-filter: blur(12px);
    }

    .hint strong {
      display: block;
      margin-bottom: 4px;
    }

    .hint p {
      margin: 0;
      font-size: 12px;
      line-height: 1.55;
      color: var(--muted);
    }

    .step-chooser {
      position: absolute;
      z-index: 4;
      min-width: 180px;
      max-width: min(280px, calc(100% - 24px));
      max-height: min(360px, calc(100% - 24px));
      overflow: auto;
      padding: 10px;
      border-radius: 18px;
      border: 1px solid rgba(143, 245, 255, 0.18);
      background: rgba(8, 14, 31, 0.94);
      box-shadow: 0 24px 54px rgba(0, 0, 0, 0.34);
      backdrop-filter: blur(14px);
    }

    .step-chooser.is-hidden {
      display: none;
    }

    .step-chooser-title {
      margin: 0 0 8px;
      font-size: 12px;
      color: var(--muted);
    }

    .step-chooser-list {
      display: grid;
      gap: 6px;
    }

    .step-chooser-option {
      width: 100%;
      padding: 10px 12px;
      border-radius: 14px;
      border: 1px solid rgba(143, 245, 255, 0.14);
      background: rgba(18, 27, 61, 0.84);
      text-align: left;
      color: var(--ink);
    }

    .step-chooser-option.is-active {
      background: linear-gradient(135deg, rgba(255, 155, 228, 0.92), rgba(137, 245, 255, 0.9));
      color: #0b1020;
      border-color: transparent;
    }

    .step-chooser-option strong,
    .step-chooser-option small {
      display: block;
    }

    .step-chooser-option strong {
      font-size: 13px;
      line-height: 1.2;
    }

    .step-chooser-option small {
      margin-top: 3px;
      font-size: 11px;
      color: rgba(225, 236, 255, 0.68);
    }

    .step-chooser-option.is-active small {
      color: rgba(11, 16, 32, 0.72);
    }

    @media (max-width: 1080px) {
      body {
        grid-template-columns: 1fr;
        grid-template-rows: minmax(0, 48vh) minmax(0, 1fr);
      }

      .sidebar {
        border-right: 0;
        border-bottom: 1px solid rgba(137, 222, 255, 0.12);
        height: 48vh;
      }

      .stage {
        height: 52vh;
      }
    }
  </style>
</head>
<body>
  <aside class="sidebar">
    <section class="panel">
      <h1>Mileage Map Tool</h1>
      <p>มาร์ค path แบบค่อย ๆ ขยายได้ และวาง reward anchor ล่วงหน้าได้แม้ยังไม่มี step ถึงตรงนั้น</p>
      <div class="toolbar">
        <span id="stepCountPill" class="status-pill"><strong>0</strong> step</span>
        <span id="rewardCountPill" class="status-pill"><strong>0</strong> reward</span>
        <span id="spriteCountPill" class="status-pill"><strong>0</strong> sprite</span>
        <span id="selectionCountPill" class="status-pill">ยังไม่ได้คลุมเลือก</span>
        <span id="savePill" class="status-pill warn">ยังไม่บันทึก</span>
      </div>
      <div class="button-row">
        <button id="modeStepAfter" class="is-active" type="button">คลิกเพิ่ม step หลังจุดที่เลือก</button>
        <button id="modeStepBefore" type="button">คลิกแทรก step ก่อนจุดที่เลือก</button>
        <button id="modeReward" type="button">คลิกวาง reward anchor</button>
        <button id="modeSprite" type="button">คลิกวาง spritesheet</button>
        <button id="previewGuideButton" class="is-active" type="button">Preview โปรไฟล์+เลข</button>
      </div>
      <div class="button-row">
        <button id="zoomFitButton" type="button">Fit</button>
        <button id="zoomInButton" type="button">ซูมเข้า</button>
        <button id="zoomOutButton" type="button">ซูมออก</button>
        <button id="centerSelectedButton" type="button">ไปที่จุดที่เลือก</button>
      </div>
      <div class="button-row">
        <button id="marqueeModeButton" type="button">โหมดคลุมเลือก</button>
        <button id="selectAllButton" type="button">เลือกทั้งหมด</button>
        <button id="clearSelectionButton" type="button">ล้างกลุ่ม</button>
      </div>
    </section>

    <section class="panel">
      <h2>Scale แบบละเอียด</h2>
      <p id="selectionScaleInfo">เลือกของก่อน แล้วจะกรอกขนาดรวมเป็น px ได้เลย</p>
      <div class="selection-size-strip">
        <span id="selectionCurrentWidthPill" class="status-pill">กว้าง - px</span>
        <span id="selectionCurrentHeightPill" class="status-pill">สูง - px</span>
      </div>
      <div class="field-grid">
        <div class="field">
          <label for="selectionScaleWidthInput">Target W px</label>
          <input id="selectionScaleWidthInput" type="number" min="1" step="1" placeholder="เช่น 900" />
        </div>
        <div class="field">
          <label for="selectionScaleHeightInput">Target H px</label>
          <input id="selectionScaleHeightInput" type="number" min="1" step="1" placeholder="เช่น 1600" />
        </div>
      </div>
      <div class="field">
        <label>Anchor / จุดยึดคงที่</label>
        <div class="anchor-grid">
          <button class="anchor-button" type="button" data-scale-anchor="nw">TL</button>
          <button class="anchor-button" type="button" data-scale-anchor="n">T</button>
          <button class="anchor-button" type="button" data-scale-anchor="ne">TR</button>
          <button class="anchor-button" type="button" data-scale-anchor="w">L</button>
          <button class="anchor-button is-active" type="button" data-scale-anchor="center">C</button>
          <button class="anchor-button" type="button" data-scale-anchor="e">R</button>
          <button class="anchor-button" type="button" data-scale-anchor="sw">BL</button>
          <button class="anchor-button" type="button" data-scale-anchor="s">B</button>
          <button class="anchor-button" type="button" data-scale-anchor="se">BR</button>
        </div>
      </div>
      <div class="button-row">
        <button id="selectionScaleLoadButton" type="button">ดึงขนาดจากที่เลือก</button>
        <button id="selectionScaleApplyButton" type="button">Apply scale px</button>
      </div>
      <p class="selection-bound-note">กรอบทองยังใช้ลากย่อ-ขยายเร็ว ๆ ได้เหมือนเดิม ส่วนชุดนี้ไว้กรอกขนาดจริงและเลือกมุมยึดให้แม่นเวลาเปลี่ยนพื้นหลังหรือเปลี่ยนสเกลทั้งแมป</p>
    </section>

    <section class="panel">
      <h2>ขนาด UI หน้าเล่น</h2>
      <div class="field-grid">
        <div class="field">
          <label for="rewardMarkerSizeInput">Reward icon px</label>
          <input id="rewardMarkerSizeInput" type="number" min="26" max="96" step="1" value="44" />
        </div>
        <div class="field">
          <label for="rewardPickupScaleInput">Coin effect scale</label>
          <input id="rewardPickupScaleInput" type="number" min="0.7" max="2.4" step="0.05" value="1.3" />
        </div>
        <div class="field">
          <label for="rewardPickupCountInput">Coin count x</label>
          <input id="rewardPickupCountInput" type="number" min="0.7" max="3.2" step="0.05" value="1.45" />
        </div>
      </div>
      <small class="mini-note">ค่านี้ถูกบันทึกใน board meta แล้วหน้า mileage กับ HUD หน้าอื่นใช้ behavior เดียวกันได้</small>
    </section>

    <section class="panel">
      <h2>Step ที่เลือก</h2>
      <p id="selectedStepInfo">ยังไม่ได้เลือก step</p>
      <div class="field">
        <label for="stepLabelInput">Label</label>
        <input id="stepLabelInput" type="text" placeholder="เช่น start / checkpoint" />
      </div>
      <div class="button-row">
        <button id="deleteStepButton" type="button">ลบ step</button>
        <button id="linkRewardButton" type="button">ผูก reward ที่เลือกกับ step นี้</button>
        <button id="unlinkRewardButton" type="button">ถอด step link ของ reward</button>
      </div>
    </section>

    <section class="panel">
      <h2>Reward ที่เลือก</h2>
      <p id="selectedRewardInfo">ยังไม่ได้เลือก reward</p>
      <div class="field-grid">
        <div class="field">
          <label for="rewardKindSelect">Kind</label>
          <select id="rewardKindSelect">
            <option value="coin">coin</option>
            <option value="ticket">ticket</option>
            <option value="gem">gem</option>
            <option value="potion">potion</option>
            <option value="item">item</option>
          </select>
        </div>
        <div class="field">
          <label for="rewardAmountInput">Amount</label>
          <input id="rewardAmountInput" type="number" min="1" step="1" value="1" />
        </div>
      </div>
      <div class="field">
        <label for="rewardLabelInput">Label</label>
        <input id="rewardLabelInput" type="text" placeholder="ข้อความโชว์บน reward" />
      </div>
      <div class="field">
        <label for="rewardItemCodeInput">Item Code</label>
        <input id="rewardItemCodeInput" type="text" placeholder="ใช้เมื่อ kind = item" />
      </div>
      <div class="button-row">
        <button id="deleteRewardButton" type="button">ลบ reward</button>
        <button id="duplicateRewardButton" type="button">คัดลอก reward</button>
      </div>
    </section>

    <section class="panel">
      <h2>Sprite ที่เลือก</h2>
      <p id="selectedSpriteInfo">ยังไม่ได้เลือก sprite</p>
      <div class="field">
        <label for="spriteLabelInput">Label</label>
        <input id="spriteLabelInput" type="text" placeholder="เช่น กล่องรางวัล / เอฟเฟกต์" />
      </div>
      <div class="field">
        <label for="spriteFileInput">Upload spritesheet</label>
        <input id="spriteFileInput" type="file" accept="image/png,image/jpeg,image/webp,image/gif" />
        <small class="mini-note">ไฟล์ที่อัปโหลดจะถูกฝังใน board JSON เป็น data URL ใช้กับไฟล์เล็ก/กลางจะเหมาะสุด</small>
      </div>
      <div class="field">
        <label for="spriteSrcInput">Sprite source/path</label>
        <input id="spriteSrcInput" type="text" placeholder="images/mileage/main/reward-sprite.png หรือ data:image/..." />
      </div>
      <div class="field-grid">
        <div class="field">
          <label for="spriteColumnsInput">Columns</label>
          <input id="spriteColumnsInput" type="number" min="1" step="1" value="1" />
        </div>
        <div class="field">
          <label for="spriteRowsInput">Rows</label>
          <input id="spriteRowsInput" type="number" min="1" step="1" value="1" />
        </div>
        <div class="field">
          <label for="spriteFrameWidthInput">Frame W</label>
          <input id="spriteFrameWidthInput" type="number" min="0" step="1" value="0" />
        </div>
        <div class="field">
          <label for="spriteFrameHeightInput">Frame H</label>
          <input id="spriteFrameHeightInput" type="number" min="0" step="1" value="0" />
        </div>
        <div class="field">
          <label for="spriteWidthInput">Show W</label>
          <input id="spriteWidthInput" type="number" min="1" step="1" value="48" />
        </div>
        <div class="field">
          <label for="spriteHeightInput">Show H</label>
          <input id="spriteHeightInput" type="number" min="1" step="1" value="48" />
        </div>
        <div class="field">
          <label for="spriteXInput">X</label>
          <input id="spriteXInput" type="number" min="0" max="1" step="0.0001" value="0" />
        </div>
        <div class="field">
          <label for="spriteYInput">Y</label>
          <input id="spriteYInput" type="number" min="0" max="1" step="0.0001" value="0" />
        </div>
        <div class="field">
          <label for="spriteFrameCountInput">Frames</label>
          <input id="spriteFrameCountInput" type="number" min="1" step="1" value="1" />
        </div>
        <div class="field">
          <label for="spriteFpsInput">Speed FPS</label>
          <input id="spriteFpsInput" type="number" min="1" max="60" step="1" value="12" />
        </div>
      </div>
      <div class="field">
        <label for="spriteModeSelect">Play mode</label>
        <select id="spriteModeSelect">
          <option value="loop">Loop</option>
          <option value="once">Once</option>
          <option value="pingpong">Loop ไป-กลับ</option>
        </select>
      </div>
      <div class="button-row">
        <button id="deleteSpriteButton" type="button">ลบ sprite</button>
        <button id="duplicateSpriteButton" type="button">คัดลอก sprite</button>
      </div>
    </section>

    <section class="panel">
      <h2>บันทึก / Export</h2>
      <p>reward anchor สามารถมี <strong>x,y</strong> อย่างเดียวก่อน แล้วค่อยกลับมาผูก <strong>stepIndex</strong> ทีหลังก็ได้</p>
      <div class="button-row">
        <button id="saveButton" type="button">Save ลงไฟล์</button>
        <button id="downloadButton" type="button">Download JSON</button>
        <button id="refreshJsonButton" type="button">Refresh JSON</button>
      </div>
      <div class="field">
        <label for="jsonPreview">JSON Preview</label>
        <textarea id="jsonPreview" spellcheck="false"></textarea>
      </div>
    </section>
  </aside>

  <main class="stage">
    <canvas id="toolCanvas" aria-label="Mileage map tool"></canvas>
    <div class="hint">
      <strong>Flow ที่แนะนำ</strong>
      <p>เริ่มจากมาร์ค path เฉพาะช่วงต้นแมปก่อนก็ได้ แล้วข้ามไปวาง reward anchor หรือ spritesheet ตกแต่งยาวทั้งแมปทีหลังได้ทันที กดค้างปุ่มขวา, ปุ่มกลาง, หรือกด Space ค้างเพื่อ pan แมป, กด Ctrl/Cmd ค้างแล้วคลิกเพื่อเอา step ช่วงท้ายมาวางซ้ำจุดเดิม, และกด Shift + ลาก หรือเปิดโหมดคลุมเลือกเพื่อเลือกหลายชิ้นแล้วลากพร้อมกันหรือจับจุดรอบกรอบเพื่อสเกลทั้งก้อน</p>
    </div>
    <section id="stepChooser" class="step-chooser is-hidden" aria-hidden="true"></section>
  </main>

  <script>
    const boardCode = <?php echo json_encode($boardCode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const canvas = document.getElementById("toolCanvas");
    const stage = document.querySelector(".stage");
    const ctx = canvas.getContext("2d");
    const apiUrl = new URL("mileage-api.php", window.location.href);
    const stepCountPill = document.getElementById("stepCountPill");
    const rewardCountPill = document.getElementById("rewardCountPill");
    const spriteCountPill = document.getElementById("spriteCountPill");
    const selectionCountPill = document.getElementById("selectionCountPill");
    const savePill = document.getElementById("savePill");
    const modeStepAfter = document.getElementById("modeStepAfter");
    const modeStepBefore = document.getElementById("modeStepBefore");
    const modeReward = document.getElementById("modeReward");
    const modeSprite = document.getElementById("modeSprite");
    const previewGuideButton = document.getElementById("previewGuideButton");
    const rewardMarkerSizeInput = document.getElementById("rewardMarkerSizeInput");
    const rewardPickupScaleInput = document.getElementById("rewardPickupScaleInput");
    const rewardPickupCountInput = document.getElementById("rewardPickupCountInput");
    const zoomFitButton = document.getElementById("zoomFitButton");
    const zoomInButton = document.getElementById("zoomInButton");
    const zoomOutButton = document.getElementById("zoomOutButton");
    const centerSelectedButton = document.getElementById("centerSelectedButton");
    const marqueeModeButton = document.getElementById("marqueeModeButton");
    const selectAllButton = document.getElementById("selectAllButton");
    const clearSelectionButton = document.getElementById("clearSelectionButton");
    const selectionScaleInfo = document.getElementById("selectionScaleInfo");
    const selectionCurrentWidthPill = document.getElementById("selectionCurrentWidthPill");
    const selectionCurrentHeightPill = document.getElementById("selectionCurrentHeightPill");
    const selectionScaleWidthInput = document.getElementById("selectionScaleWidthInput");
    const selectionScaleHeightInput = document.getElementById("selectionScaleHeightInput");
    const selectionScaleLoadButton = document.getElementById("selectionScaleLoadButton");
    const selectionScaleApplyButton = document.getElementById("selectionScaleApplyButton");
    const selectionScaleAnchorButtons = Array.from(document.querySelectorAll("[data-scale-anchor]"));
    const selectedStepInfo = document.getElementById("selectedStepInfo");
    const selectedRewardInfo = document.getElementById("selectedRewardInfo");
    const stepLabelInput = document.getElementById("stepLabelInput");
    const rewardKindSelect = document.getElementById("rewardKindSelect");
    const rewardAmountInput = document.getElementById("rewardAmountInput");
    const rewardLabelInput = document.getElementById("rewardLabelInput");
    const rewardItemCodeInput = document.getElementById("rewardItemCodeInput");
    const selectedSpriteInfo = document.getElementById("selectedSpriteInfo");
    const spriteLabelInput = document.getElementById("spriteLabelInput");
    const spriteFileInput = document.getElementById("spriteFileInput");
    const spriteSrcInput = document.getElementById("spriteSrcInput");
    const spriteColumnsInput = document.getElementById("spriteColumnsInput");
    const spriteRowsInput = document.getElementById("spriteRowsInput");
    const spriteFrameWidthInput = document.getElementById("spriteFrameWidthInput");
    const spriteFrameHeightInput = document.getElementById("spriteFrameHeightInput");
    const spriteWidthInput = document.getElementById("spriteWidthInput");
    const spriteHeightInput = document.getElementById("spriteHeightInput");
    const spriteXInput = document.getElementById("spriteXInput");
    const spriteYInput = document.getElementById("spriteYInput");
    const spriteFrameCountInput = document.getElementById("spriteFrameCountInput");
    const spriteFpsInput = document.getElementById("spriteFpsInput");
    const spriteModeSelect = document.getElementById("spriteModeSelect");
    const deleteStepButton = document.getElementById("deleteStepButton");
    const linkRewardButton = document.getElementById("linkRewardButton");
    const unlinkRewardButton = document.getElementById("unlinkRewardButton");
    const deleteRewardButton = document.getElementById("deleteRewardButton");
    const duplicateRewardButton = document.getElementById("duplicateRewardButton");
    const deleteSpriteButton = document.getElementById("deleteSpriteButton");
    const duplicateSpriteButton = document.getElementById("duplicateSpriteButton");
    const saveButton = document.getElementById("saveButton");
    const downloadButton = document.getElementById("downloadButton");
    const refreshJsonButton = document.getElementById("refreshJsonButton");
    const jsonPreview = document.getElementById("jsonPreview");
    const stepChooser = document.getElementById("stepChooser");

    const rewardColors = {
      coin: "#ffd482",
      ticket: "#90efff",
      gem: "#ffb9f5",
      potion: "#8ff7c0",
      item: "#b9a4ff"
    };
    const spriteImages = new Map();

    const viewerPreviewStyle = {
      selfSize: 44,
      selfLift: 34,
      stepBadgeSize: 24,
      stepBadgeOffset: 18
    };

    function emptyMultiSelection() {
      return {
        steps: [],
        rewards: [],
        sprites: []
      };
    }

    const state = {
      board: null,
      saveAllowed: false,
      background: null,
      camera: {
        zoom: 0.48,
        x: 470,
        y: 9500
      },
      showGuides: true,
      hoverPoint: null,
      interaction: {
        dragging: false,
        dragType: "",
        startX: 0,
        startY: 0,
        cameraX: 0,
        cameraY: 0,
        pendingPoint: null,
        pendingAnchorStepIndex: -1,
        pendingAppendToEnd: false,
        dragOriginPoint: null,
        batchSnapshot: null,
        marqueeCurrentX: 0,
        marqueeCurrentY: 0,
        transformHandle: "",
        hoverTransformHandle: "",
        hoverTransformBody: false
      },
      stepChooser: {
        visible: false,
        clientX: 0,
        clientY: 0,
        stepIndexes: []
      },
      spacePan: false,
      selectionTool: false,
      placementMode: "step-after",
      scaleAnchor: "center",
      selectedStep: -1,
      selectedReward: -1,
      selectedSprite: -1,
      multiSelection: emptyMultiSelection(),
      dirty: false
    };

    function clamp(value, min, max) {
      return Math.max(min, Math.min(max, value));
    }

    function deepClone(value) {
      return JSON.parse(JSON.stringify(value));
    }

    function plainObject(value) {
      return value && typeof value === "object" && !Array.isArray(value);
    }

    function boardUiSettings() {
      const meta = plainObject(state.board?.meta) ? state.board.meta : {};
      const ui = plainObject(meta.ui) ? meta.ui : {};
      const rewardMarker = plainObject(ui.rewardMarker) ? ui.rewardMarker : {};
      const currencyPickup = plainObject(ui.currencyPickup) ? ui.currencyPickup : {};
      return {
        rewardMarker: {
          size: clamp(Number(rewardMarker.size || 44), 26, 96)
        },
        currencyPickup: {
          scale: clamp(Number(currencyPickup.scale || 1.3), 0.7, 2.4),
          countMultiplier: clamp(Number(currencyPickup.countMultiplier || 1.45), 0.7, 3.2)
        }
      };
    }

    function isTypingTarget(target) {
      if (!(target instanceof HTMLElement)) return false;
      const tagName = target.tagName;
      return tagName === "INPUT" || tagName === "TEXTAREA" || tagName === "SELECT" || target.isContentEditable;
    }

    function rewardId(prefix = "reward") {
      return `${prefix}_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 7)}`;
    }

    function spriteId(prefix = "sprite") {
      return `${prefix}_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 7)}`;
    }

    function isDuplicatePlacementModifier(event) {
      return Boolean(event?.ctrlKey || event?.metaKey);
    }

    function normalizeSelectionIndex(index, size) {
      if (!Number.isInteger(index) || index < 0 || size <= 0) {
        return -1;
      }
      return clamp(index, 0, size - 1);
    }

    function syncSelection() {
      const stepCount = Array.isArray(state.board?.steps) ? state.board.steps.length : 0;
      const rewardCount = Array.isArray(state.board?.rewards) ? state.board.rewards.length : 0;
      const spriteCount = Array.isArray(state.board?.sprites) ? state.board.sprites.length : 0;
      state.selectedStep = normalizeSelectionIndex(state.selectedStep, stepCount);
      state.selectedReward = normalizeSelectionIndex(state.selectedReward, rewardCount);
      state.selectedSprite = normalizeSelectionIndex(state.selectedSprite, spriteCount);
    }

    function selectedStepIndex() {
      return normalizeSelectionIndex(state.selectedStep, Array.isArray(state.board?.steps) ? state.board.steps.length : 0);
    }

    function selectedRewardIndex() {
      return normalizeSelectionIndex(state.selectedReward, Array.isArray(state.board?.rewards) ? state.board.rewards.length : 0);
    }

    function selectedSpriteIndex() {
      return normalizeSelectionIndex(state.selectedSprite, Array.isArray(state.board?.sprites) ? state.board.sprites.length : 0);
    }

    function normalizeIndexList(indexes, size) {
      if (!Array.isArray(indexes) || size <= 0) {
        return [];
      }
      return [...new Set(
        indexes
          .map((value) => Number(value))
          .filter((value) => Number.isInteger(value) && value >= 0 && value < size)
      )].sort((a, b) => a - b);
    }

    function syncMultiSelection() {
      const stepCount = Array.isArray(state.board?.steps) ? state.board.steps.length : 0;
      const rewardCount = Array.isArray(state.board?.rewards) ? state.board.rewards.length : 0;
      const spriteCount = Array.isArray(state.board?.sprites) ? state.board.sprites.length : 0;
      state.multiSelection.steps = normalizeIndexList(state.multiSelection.steps, stepCount);
      state.multiSelection.rewards = normalizeIndexList(state.multiSelection.rewards, rewardCount);
      state.multiSelection.sprites = normalizeIndexList(state.multiSelection.sprites, spriteCount);
    }

    function clearMultiSelectionState() {
      state.multiSelection = emptyMultiSelection();
      state.interaction.hoverTransformHandle = "";
      state.interaction.hoverTransformBody = false;
      state.interaction.transformHandle = "";
    }

    function totalMultiSelectionCount() {
      return state.multiSelection.steps.length
        + state.multiSelection.rewards.length
        + state.multiSelection.sprites.length;
    }

    function isMultiSelected(type, index) {
      const key = `${type}s`;
      if (!Array.isArray(state.multiSelection[key])) {
        return false;
      }
      return state.multiSelection[key].includes(index);
    }

    function assignPrimaryFromMultiSelection(clearMissingTypes = true) {
      if (state.multiSelection.steps.length) {
        state.selectedStep = state.multiSelection.steps[state.multiSelection.steps.length - 1];
      } else if (clearMissingTypes) {
        state.selectedStep = -1;
      }

      if (state.multiSelection.rewards.length) {
        state.selectedReward = state.multiSelection.rewards[state.multiSelection.rewards.length - 1];
      } else if (clearMissingTypes) {
        state.selectedReward = -1;
      }

      if (state.multiSelection.sprites.length) {
        state.selectedSprite = state.multiSelection.sprites[state.multiSelection.sprites.length - 1];
      } else if (clearMissingTypes) {
        state.selectedSprite = -1;
      }

      syncSelection();
    }

    function setMultiSelection(selection, options = {}) {
      state.multiSelection = {
        steps: normalizeIndexList(selection?.steps, Array.isArray(state.board?.steps) ? state.board.steps.length : 0),
        rewards: normalizeIndexList(selection?.rewards, Array.isArray(state.board?.rewards) ? state.board.rewards.length : 0),
        sprites: normalizeIndexList(selection?.sprites, Array.isArray(state.board?.sprites) ? state.board.sprites.length : 0)
      };
      if (totalMultiSelectionCount() <= 1) {
        state.interaction.hoverTransformHandle = "";
        state.interaction.hoverTransformBody = false;
        state.interaction.transformHandle = "";
      }

      if (options.syncPrimary !== false) {
        assignPrimaryFromMultiSelection(options.clearMissingTypes !== false);
      }

      if (options.refresh !== false) {
        refreshMeta();
      }
      if (options.render !== false) {
        render();
      }
    }

    function buildSingleSelection(type, index) {
      const selection = emptyMultiSelection();
      if (type === "step") {
        selection.steps = [index];
      } else if (type === "reward") {
        selection.rewards = [index];
      } else if (type === "sprite") {
        selection.sprites = [index];
      }
      return selection;
    }

    function buildAllSelection() {
      return {
        steps: state.board.steps.map((_, index) => index),
        rewards: state.board.rewards.map((_, index) => index),
        sprites: state.board.sprites.map((_, index) => index)
      };
    }

    function currentMarqueeRect() {
      return {
        left: Math.min(state.interaction.startX, state.interaction.marqueeCurrentX),
        right: Math.max(state.interaction.startX, state.interaction.marqueeCurrentX),
        top: Math.min(state.interaction.startY, state.interaction.marqueeCurrentY),
        bottom: Math.max(state.interaction.startY, state.interaction.marqueeCurrentY)
      };
    }

    function rectContainsPoint(rect, point) {
      return point.x >= rect.left && point.x <= rect.right && point.y >= rect.top && point.y <= rect.bottom;
    }

    function rectsOverlap(a, b) {
      return !(a.right < b.left || a.left > b.right || a.bottom < b.top || a.top > b.bottom);
    }

    function selectionFromMarqueeRect(rect) {
      const selection = emptyMultiSelection();
      const rewardRadius = (boardUiSettings().rewardMarker.size * state.camera.zoom) / 2;

      state.board.steps.forEach((step, index) => {
        const screenPoint = screenPointFromBoard(stepPoint(step));
        const bounds = {
          left: screenPoint.x - (14 * state.camera.zoom),
          right: screenPoint.x + (14 * state.camera.zoom),
          top: screenPoint.y - (14 * state.camera.zoom),
          bottom: screenPoint.y + (14 * state.camera.zoom)
        };
        if (rectsOverlap(rect, bounds)) {
          selection.steps.push(index);
        }
      });

      state.board.rewards.forEach((reward, index) => {
        const point = rewardPoint(reward);
        if (!point) return;
        const screenPoint = screenPointFromBoard(point);
        const bounds = {
          left: screenPoint.x - rewardRadius,
          right: screenPoint.x + rewardRadius,
          top: screenPoint.y - rewardRadius,
          bottom: screenPoint.y + rewardRadius
        };
        if (rectsOverlap(rect, bounds)) {
          selection.rewards.push(index);
        }
      });

      state.board.sprites.forEach((sprite, index) => {
        const point = spritePoint(sprite);
        if (!point) return;
        const screenPoint = screenPointFromBoard(point);
        const halfW = (Math.max(1, Number(sprite.width || 48)) * state.camera.zoom) / 2;
        const halfH = (Math.max(1, Number(sprite.height || 48)) * state.camera.zoom) / 2;
        const bounds = {
          left: screenPoint.x - halfW,
          right: screenPoint.x + halfW,
          top: screenPoint.y - halfH,
          bottom: screenPoint.y + halfH
        };
        if (rectsOverlap(rect, bounds)) {
          selection.sprites.push(index);
        }
      });

      return selection;
    }

    function selectionContentBounds() {
      if (!state.board || totalMultiSelectionCount() <= 0) {
        return null;
      }

      let left = Infinity;
      let right = -Infinity;
      let top = Infinity;
      let bottom = -Infinity;

      const includeBounds = (x, y, padX = 0, padY = 0) => {
        left = Math.min(left, x - padX);
        right = Math.max(right, x + padX);
        top = Math.min(top, y - padY);
        bottom = Math.max(bottom, y + padY);
      };

      for (const index of state.multiSelection.steps) {
        const step = state.board.steps[index];
        if (!step) continue;
        const point = stepPoint(step);
        includeBounds(point.x, point.y);
      }

      for (const index of state.multiSelection.rewards) {
        const reward = state.board.rewards[index];
        const point = rewardPoint(reward);
        if (!point) continue;
        includeBounds(point.x, point.y);
      }

      for (const index of state.multiSelection.sprites) {
        const sprite = state.board.sprites[index];
        const point = spritePoint(sprite);
        if (!sprite || !point) continue;
        includeBounds(
          point.x,
          point.y,
          Math.max(8, Number(sprite.width || 48) / 2),
          Math.max(8, Number(sprite.height || 48) / 2)
        );
      }

      if (!Number.isFinite(left) || !Number.isFinite(right) || !Number.isFinite(top) || !Number.isFinite(bottom)) {
        return null;
      }

      return {
        left,
        right,
        top,
        bottom,
        width: right - left,
        height: bottom - top,
        centerX: (left + right) / 2,
        centerY: (top + bottom) / 2
      };
    }

    function selectionTransformBounds() {
      const contentBounds = selectionContentBounds();
      if (!contentBounds) {
        return null;
      }

      const padding = 28 / state.camera.zoom;
      const minWidth = 120 / state.camera.zoom;
      const minHeight = 120 / state.camera.zoom;
      let left = contentBounds.left;
      let right = contentBounds.right;
      let top = contentBounds.top;
      let bottom = contentBounds.bottom;
      let width = right - left;
      let height = bottom - top;
      let centerX = contentBounds.centerX;
      let centerY = contentBounds.centerY;

      if (width < minWidth) {
        width = minWidth;
        left = centerX - (width / 2);
        right = centerX + (width / 2);
      }
      if (height < minHeight) {
        height = minHeight;
        top = centerY - (height / 2);
        bottom = centerY + (height / 2);
      }

      left -= padding;
      right += padding;
      top -= padding;
      bottom += padding;
      width = right - left;
      height = bottom - top;
      centerX = (left + right) / 2;
      centerY = (top + bottom) / 2;

      return {
        left,
        right,
        top,
        bottom,
        width,
        height,
        centerX,
        centerY,
      };
    }

    function selectionHandleRadiusBoard() {
      return 10 / state.camera.zoom;
    }

    function selectionTransformHandles(bounds = selectionTransformBounds()) {
      if (!bounds) return [];
      const midX = (bounds.left + bounds.right) / 2;
      const midY = (bounds.top + bounds.bottom) / 2;
      return [
        { id: "nw", x: bounds.left, y: bounds.top, cursor: "nwse-resize" },
        { id: "n", x: midX, y: bounds.top, cursor: "ns-resize" },
        { id: "ne", x: bounds.right, y: bounds.top, cursor: "nesw-resize" },
        { id: "e", x: bounds.right, y: midY, cursor: "ew-resize" },
        { id: "se", x: bounds.right, y: bounds.bottom, cursor: "nwse-resize" },
        { id: "s", x: midX, y: bounds.bottom, cursor: "ns-resize" },
        { id: "sw", x: bounds.left, y: bounds.bottom, cursor: "nesw-resize" },
        { id: "w", x: bounds.left, y: midY, cursor: "ew-resize" }
      ];
    }

    function selectionTransformHit(point) {
      const bounds = selectionTransformBounds();
      if (!bounds) {
        return { bounds: null, handles: [], handle: null, inside: false };
      }

      const handleRadius = selectionHandleRadiusBoard() * 1.8;
      const handles = selectionTransformHandles(bounds);
      const handle = handles.find((candidate) => (
        Math.hypot(point.x - candidate.x, point.y - candidate.y) <= handleRadius
      )) || null;

      return {
        bounds,
        handles,
        handle,
        inside: rectContainsPoint(bounds, point)
      };
    }

    function selectionScaleAnchor(bounds, handleId) {
      const midX = (bounds.left + bounds.right) / 2;
      const midY = (bounds.top + bounds.bottom) / 2;
      switch (handleId) {
        case "nw": return { x: bounds.right, y: bounds.bottom };
        case "n": return { x: midX, y: bounds.bottom };
        case "ne": return { x: bounds.left, y: bounds.bottom };
        case "e": return { x: bounds.left, y: midY };
        case "se": return { x: bounds.left, y: bounds.top };
        case "s": return { x: midX, y: bounds.top };
        case "sw": return { x: bounds.right, y: bounds.top };
        case "w": return { x: bounds.right, y: midY };
        default: return { x: bounds.centerX, y: bounds.centerY };
      }
    }

    function selectionScaleHandlePoint(bounds, handleId) {
      const handle = selectionTransformHandles(bounds).find((candidate) => candidate.id === handleId);
      return handle ? { x: handle.x, y: handle.y } : { x: bounds.centerX, y: bounds.centerY };
    }

    function selectionHandleUsesAxis(handleId, axis) {
      if (axis === "x") {
        return handleId.includes("e") || handleId.includes("w");
      }
      if (axis === "y") {
        return handleId.includes("n") || handleId.includes("s");
      }
      return false;
    }

    function selectionAnchorPoint(bounds, anchorId = "center") {
      if (!bounds) {
        return null;
      }
      const midX = (bounds.left + bounds.right) / 2;
      const midY = (bounds.top + bounds.bottom) / 2;
      switch (String(anchorId || "center")) {
        case "nw": return { x: bounds.left, y: bounds.top };
        case "n": return { x: midX, y: bounds.top };
        case "ne": return { x: bounds.right, y: bounds.top };
        case "w": return { x: bounds.left, y: midY };
        case "e": return { x: bounds.right, y: midY };
        case "sw": return { x: bounds.left, y: bounds.bottom };
        case "s": return { x: midX, y: bounds.bottom };
        case "se": return { x: bounds.right, y: bounds.bottom };
        default: return { x: midX, y: midY };
      }
    }

    function createSelectionSnapshot(bounds = selectionTransformBounds(), contentBounds = selectionContentBounds()) {
      return {
        bounds,
        contentBounds,
        steps: state.multiSelection.steps.map((index) => {
          const step = state.board.steps[index];
          const point = step ? stepPoint(step) : null;
          return point ? { index, x: point.x, y: point.y } : null;
        }).filter(Boolean),
        rewards: state.multiSelection.rewards.map((index) => {
          const reward = state.board.rewards[index];
          const point = rewardPoint(reward);
          return reward && point ? { index, x: point.x, y: point.y } : null;
        }).filter(Boolean),
        sprites: state.multiSelection.sprites.map((index) => {
          const sprite = state.board.sprites[index];
          const point = spritePoint(sprite);
          return sprite && point ? {
            index,
            x: point.x,
            y: point.y,
            width: Math.max(1, Number(sprite.width || 48)),
            height: Math.max(1, Number(sprite.height || 48))
          } : null;
        }).filter(Boolean)
      };
    }

    function applySelectionScaleFactors(snapshot, scaleX = 1, scaleY = 1, anchor = null) {
      if (!snapshot?.contentBounds || !anchor || !state.board?.image) {
        return;
      }
      const width = state.board.image.width;
      const height = state.board.image.height;
      const normalizedScaleX = clamp(Number(scaleX) || 1, 0.02, 64);
      const normalizedScaleY = clamp(Number(scaleY) || 1, 0.02, 64);

      for (const entry of snapshot.steps) {
        const step = state.board.steps[entry.index];
        if (!step) continue;
        const nextX = anchor.x + ((entry.x - anchor.x) * normalizedScaleX);
        const nextY = anchor.y + ((entry.y - anchor.y) * normalizedScaleY);
        step.x = clamp(nextX / width, 0, 1);
        step.y = clamp(nextY / height, 0, 1);
      }

      for (const entry of snapshot.rewards) {
        const reward = state.board.rewards[entry.index];
        if (!reward) continue;
        const nextX = anchor.x + ((entry.x - anchor.x) * normalizedScaleX);
        const nextY = anchor.y + ((entry.y - anchor.y) * normalizedScaleY);
        reward.x = clamp(nextX / width, 0, 1);
        reward.y = clamp(nextY / height, 0, 1);
      }

      for (const entry of snapshot.sprites) {
        const sprite = state.board.sprites[entry.index];
        if (!sprite) continue;
        const nextX = anchor.x + ((entry.x - anchor.x) * normalizedScaleX);
        const nextY = anchor.y + ((entry.y - anchor.y) * normalizedScaleY);
        sprite.x = clamp(nextX / width, 0, 1);
        sprite.y = clamp(nextY / height, 0, 1);
        sprite.width = Math.max(1, Math.round(entry.width * normalizedScaleX));
        sprite.height = Math.max(1, Math.round(entry.height * normalizedScaleY));
      }
    }

    function applySelectionScale(snapshot, handleId, point) {
      if (!snapshot?.bounds || !snapshot?.contentBounds || !handleId || !point || !state.board?.image) {
        return;
      }

      const visualAnchor = selectionScaleAnchor(snapshot.bounds, handleId);
      const visualHandlePoint = selectionScaleHandlePoint(snapshot.bounds, handleId);
      const contentAnchor = selectionScaleAnchor(snapshot.contentBounds, handleId);
      let scaleX = 1;
      let scaleY = 1;

      if (selectionHandleUsesAxis(handleId, "x")) {
        const baseX = visualHandlePoint.x - visualAnchor.x;
        const nextX = point.x - visualAnchor.x;
        scaleX = Math.max(0.02, baseX === 0 ? 1 : nextX / baseX);
      }
      if (selectionHandleUsesAxis(handleId, "y")) {
        const baseY = visualHandlePoint.y - visualAnchor.y;
        const nextY = point.y - visualAnchor.y;
        scaleY = Math.max(0.02, baseY === 0 ? 1 : nextY / baseY);
      }
      applySelectionScaleFactors(snapshot, scaleX, scaleY, contentAnchor);
    }

    function loadSelectionScaleInputs() {
      const bounds = selectionContentBounds();
      if (!bounds) return;
      selectionScaleWidthInput.value = String(Math.max(1, Math.round(bounds.width)));
      selectionScaleHeightInput.value = String(Math.max(1, Math.round(bounds.height)));
    }

    function applySelectionScaleInputs() {
      const snapshot = createSelectionSnapshot();
      const bounds = snapshot?.contentBounds || null;
      if (!bounds) return;
      const requestedWidth = Number(selectionScaleWidthInput.value || 0);
      const requestedHeight = Number(selectionScaleHeightInput.value || 0);
      const hasWidth = Number.isFinite(requestedWidth) && requestedWidth > 0;
      const hasHeight = Number.isFinite(requestedHeight) && requestedHeight > 0;
      if (!hasWidth && !hasHeight) {
        return;
      }
      const scaleX = hasWidth ? requestedWidth / Math.max(1, bounds.width) : 1;
      const scaleY = hasHeight ? requestedHeight / Math.max(1, bounds.height) : 1;
      const anchor = selectionAnchorPoint(bounds, state.scaleAnchor);
      if (!anchor) return;
      applySelectionScaleFactors(snapshot, scaleX, scaleY, anchor);
      normalizeBoard();
      setDirty(true);
      refreshMeta();
      render();
    }

    function refreshSelectionScaleUi() {
      const bounds = selectionContentBounds();
      const totalSelected = totalMultiSelectionCount();
      const hasSelection = totalSelected > 0 && Boolean(bounds);
      if (selectionCurrentWidthPill) {
        selectionCurrentWidthPill.innerHTML = hasSelection
          ? `<strong>${Math.round(bounds.width)}</strong> px กว้าง`
          : "กว้าง - px";
      }
      if (selectionCurrentHeightPill) {
        selectionCurrentHeightPill.innerHTML = hasSelection
          ? `<strong>${Math.round(bounds.height)}</strong> px สูง`
          : "สูง - px";
      }
      if (selectionScaleInfo) {
        selectionScaleInfo.textContent = hasSelection
          ? `ตอนนี้เลือก ${totalSelected} ชิ้น • กรอกขนาดใหม่เป็น px แล้วเลือกมุมยึดที่ต้องการคงไว้ได้เลย`
          : "เลือกของก่อน แล้วจะกรอกขนาดรวมเป็น px ได้เลย";
      }
      if (selectionScaleLoadButton) {
        selectionScaleLoadButton.disabled = !hasSelection;
      }
      if (selectionScaleApplyButton) {
        selectionScaleApplyButton.disabled = !hasSelection;
      }
      if (selectionScaleWidthInput) {
        selectionScaleWidthInput.placeholder = hasSelection ? String(Math.max(1, Math.round(bounds.width))) : "เช่น 900";
      }
      if (selectionScaleHeightInput) {
        selectionScaleHeightInput.placeholder = hasSelection ? String(Math.max(1, Math.round(bounds.height))) : "เช่น 1600";
      }
      for (const button of selectionScaleAnchorButtons) {
        button.classList.toggle("is-active", button.dataset.scaleAnchor === state.scaleAnchor);
      }
    }

    function drawSelectionMarquee() {
      if (state.interaction.dragType !== "select-marquee") {
        return;
      }
      const rect = currentMarqueeRect();
      const width = rect.right - rect.left;
      const height = rect.bottom - rect.top;
      if (width < 1 && height < 1) {
        return;
      }

      ctx.save();
      ctx.fillStyle = "rgba(143, 245, 255, 0.12)";
      ctx.strokeStyle = "rgba(143, 245, 255, 0.92)";
      ctx.lineWidth = 1.5;
      ctx.setLineDash([8, 6]);
      ctx.fillRect(rect.left, rect.top, width, height);
      ctx.strokeRect(rect.left, rect.top, width, height);
      ctx.restore();
    }

    function drawSelectionTransform() {
      if (totalMultiSelectionCount() <= 1) {
        return;
      }
      const bounds = selectionTransformBounds();
      if (!bounds) return;

      const handleRadius = selectionHandleRadiusBoard();
      const hoverHandleId = state.interaction.hoverTransformHandle || state.interaction.transformHandle || "";

      ctx.save();
      drawRoundRectPath(bounds.left, bounds.top, bounds.width, bounds.height, 22 / state.camera.zoom);
      ctx.fillStyle = "rgba(255, 222, 148, 0.08)";
      ctx.fill();
      ctx.lineWidth = 2.4 / state.camera.zoom;
      ctx.strokeStyle = "rgba(255, 222, 148, 0.96)";
      ctx.setLineDash([12 / state.camera.zoom, 8 / state.camera.zoom]);
      ctx.stroke();
      ctx.setLineDash([]);

      for (const handle of selectionTransformHandles(bounds)) {
        ctx.beginPath();
        ctx.arc(handle.x, handle.y, handleRadius, 0, Math.PI * 2);
        ctx.fillStyle = handle.id === hoverHandleId ? "rgba(255, 158, 227, 0.98)" : "rgba(255, 222, 148, 0.98)";
        ctx.fill();
        ctx.lineWidth = 2 / state.camera.zoom;
        ctx.strokeStyle = "rgba(8, 13, 29, 0.92)";
        ctx.stroke();
      }
      ctx.restore();
    }

    function beginSelectionBatchDrag(point, clientX, clientY) {
      const batchSnapshot = createSelectionSnapshot();

      state.interaction.dragging = true;
      state.interaction.dragType = "selection-batch";
      state.interaction.startX = clientX;
      state.interaction.startY = clientY;
      state.interaction.dragOriginPoint = point;
      state.interaction.batchSnapshot = batchSnapshot;
      state.interaction.pendingPoint = null;
      state.interaction.pendingAnchorStepIndex = -1;
      state.interaction.pendingAppendToEnd = false;
    }

    function beginSelectionScale(handle, point, clientX, clientY) {
      state.interaction.dragging = true;
      state.interaction.dragType = "selection-scale";
      state.interaction.startX = clientX;
      state.interaction.startY = clientY;
      state.interaction.dragOriginPoint = point;
      state.interaction.batchSnapshot = createSelectionSnapshot();
      state.interaction.transformHandle = String(handle?.id || "");
      state.interaction.pendingPoint = null;
      state.interaction.pendingAnchorStepIndex = -1;
      state.interaction.pendingAppendToEnd = false;
    }

    function beginSelectionMarquee(clientX, clientY) {
      state.interaction.dragging = true;
      state.interaction.dragType = "select-marquee";
      state.interaction.startX = clientX;
      state.interaction.startY = clientY;
      state.interaction.marqueeCurrentX = clientX;
      state.interaction.marqueeCurrentY = clientY;
      state.interaction.dragOriginPoint = null;
      state.interaction.batchSnapshot = null;
      state.interaction.pendingPoint = null;
      state.interaction.pendingAnchorStepIndex = -1;
    }

    function hitSelectedGroupTarget(hitStep, hitReward, hitSprite) {
      if (hitReward >= 0 && isMultiSelected("reward", hitReward)) {
        return { type: "reward", index: hitReward };
      }
      if (hitSprite >= 0 && isMultiSelected("sprite", hitSprite)) {
        return { type: "sprite", index: hitSprite };
      }
      if (hitStep >= 0 && isMultiSelected("step", hitStep)) {
        return { type: "step", index: hitStep };
      }
      return null;
    }

    function pointerMoveDistance(event) {
      return Math.hypot(
        event.clientX - state.interaction.startX,
        event.clientY - state.interaction.startY
      );
    }

    function setDirty(flag = true) {
      state.dirty = flag;
      savePill.textContent = flag ? "ยังไม่บันทึก" : "บันทึกล่าสุดแล้ว";
      savePill.classList.toggle("warn", flag);
      savePill.classList.toggle("good", !flag);
    }

    function syncCanvasCursor() {
      if (state.interaction.dragging && (state.interaction.dragType === "pan" || state.interaction.dragType === "selection-batch")) {
        canvas.style.cursor = "grabbing";
        return;
      }
      if (state.interaction.dragging && state.interaction.dragType === "selection-scale") {
        const activeHandle = selectionTransformHandles(selectionTransformBounds()).find((handle) => handle.id === state.interaction.transformHandle);
        canvas.style.cursor = activeHandle?.cursor || "nwse-resize";
        return;
      }
      if (state.interaction.dragging && ["step", "reward", "sprite"].includes(state.interaction.dragType)) {
        canvas.style.cursor = "grabbing";
        return;
      }
      if (state.interaction.hoverTransformHandle) {
        const handle = selectionTransformHandles(selectionTransformBounds()).find((candidate) => candidate.id === state.interaction.hoverTransformHandle);
        canvas.style.cursor = handle?.cursor || "nwse-resize";
        return;
      }
      if (state.interaction.hoverTransformBody) {
        canvas.style.cursor = "move";
        return;
      }
      if (state.interaction.dragType === "select-marquee") {
        canvas.style.cursor = "crosshair";
        return;
      }
      if (state.spacePan) {
        canvas.style.cursor = "grab";
        return;
      }
      if (state.selectionTool) {
        canvas.style.cursor = "crosshair";
        return;
      }
      canvas.style.cursor = state.placementMode === "reward" || state.placementMode === "sprite" ? "copy" : "crosshair";
    }

    async function fetchToolJson(action, body = null) {
      const url = new URL(apiUrl.toString());
      url.searchParams.set("action", action);
      url.searchParams.set("boardCode", boardCode);
      const response = await fetch(url, {
        method: body ? "POST" : "GET",
        cache: "no-store",
        credentials: "same-origin",
        headers: body ? { "Content-Type": "application/json" } : undefined,
        body: body ? JSON.stringify(body) : null
      });
      const data = await response.json().catch(() => null);
      if (!response.ok || !data || data.ok === false) {
        const error = new Error(data?.message || data?.code || `HTTP ${response.status}`);
        error.data = data || {};
        throw error;
      }
      return data;
    }

    function resizeCanvas() {
      const rect = canvas.getBoundingClientRect();
      const dpr = Math.max(1, window.devicePixelRatio || 1);
      canvas.width = Math.round(rect.width * dpr);
      canvas.height = Math.round(rect.height * dpr);
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.scale(dpr, dpr);
      render();
    }

    function updateModeButtons() {
      modeStepAfter.classList.toggle("is-active", state.placementMode === "step-after");
      modeStepBefore.classList.toggle("is-active", state.placementMode === "step-before");
      modeReward.classList.toggle("is-active", state.placementMode === "reward");
      modeSprite.classList.toggle("is-active", state.placementMode === "sprite");
      previewGuideButton.classList.toggle("is-active", state.showGuides);
      marqueeModeButton.classList.toggle("is-active", state.selectionTool);
    }

    function fitZoom() {
      if (!state.board) return 0.35;
      const rect = canvas.getBoundingClientRect();
      return clamp((rect.width / state.board.image.width) * 0.94, 0.06, 2.4);
    }

    function fitZoomForBounds(bounds) {
      if (!bounds) {
        return fitZoom();
      }
      const rect = canvas.getBoundingClientRect();
      const widthZoom = rect.width / Math.max(1, bounds.width);
      const heightZoom = rect.height / Math.max(1, bounds.height);
      return clamp(Math.min(widthZoom, heightZoom) * 0.94, 0.06, 2.4);
    }

    function boardPointFromScreen(clientX, clientY) {
      const rect = canvas.getBoundingClientRect();
      const x = clientX - rect.left;
      const y = clientY - rect.top;
      return {
        x: (x - rect.width / 2) / state.camera.zoom + state.camera.x,
        y: (y - rect.height / 2) / state.camera.zoom + state.camera.y
      };
    }

    function screenPointFromBoard(point) {
      const rect = canvas.getBoundingClientRect();
      return {
        x: (point.x - state.camera.x) * state.camera.zoom + rect.width / 2,
        y: (point.y - state.camera.y) * state.camera.zoom + rect.height / 2
      };
    }

    function stepPoint(step) {
      return {
        x: step.x * state.board.image.width,
        y: step.y * state.board.image.height
      };
    }

    function boardEntryPoint() {
      const entry = state.board?.entry || state.board?.steps?.[0] || null;
      const normalizedX = typeof entry?.x === "number" ? entry.x : 0.4123;
      const normalizedY = typeof entry?.y === "number" ? entry.y : 0.9721;
      return {
        x: normalizedX * state.board.image.width,
        y: normalizedY * state.board.image.height
      };
    }

    function rewardPoint(reward) {
      if (typeof reward.x === "number" && typeof reward.y === "number") {
        return {
          x: reward.x * state.board.image.width,
          y: reward.y * state.board.image.height
        };
      }
      if (Number.isInteger(reward.stepIndex) && state.board.steps[reward.stepIndex]) {
        return stepPoint(state.board.steps[reward.stepIndex]);
      }
      return null;
    }

    function spritePoint(sprite) {
      if (!sprite || typeof sprite.x !== "number" || typeof sprite.y !== "number") {
        return null;
      }
      return {
        x: sprite.x * state.board.image.width,
        y: sprite.y * state.board.image.height
      };
    }

    function spriteFrameIndex(sprite, ts = performance.now()) {
      const frameCount = Math.max(1, Number(sprite?.frameCount || 1));
      const fps = Math.max(1, Number(sprite?.fps || 12));
      const frame = Math.floor((ts / 1000) * fps);
      const mode = String(sprite?.mode || "loop");
      if (mode === "once") {
        return Math.min(frameCount - 1, frame);
      }
      if (mode === "pingpong" && frameCount > 1) {
        const cycle = (frameCount * 2) - 2;
        const p = frame % cycle;
        return p < frameCount ? p : cycle - p;
      }
      return frame % frameCount;
    }

    function loadSpriteImage(sprite) {
      const src = String(sprite?.src || "").trim();
      if (!src) return null;
      const cached = spriteImages.get(src);
      if (cached) return cached;
      const image = new Image();
      image.decoding = "async";
      image.onload = () => render();
      image.onerror = () => render();
      image.src = src;
      spriteImages.set(src, image);
      return image;
    }

    function preloadSpriteImages() {
      for (const sprite of state.board?.sprites || []) {
        loadSpriteImage(sprite);
      }
    }

    function stepPoints() {
      return state.board.steps.map((step) => stepPoint(step));
    }

    function stepStackIndexes(stepIndex) {
      const target = state.board.steps?.[stepIndex] || null;
      if (!target || !state.board?.image) return [];
      const thresholdPx = 3;
      return state.board.steps.reduce((indexes, step, index) => {
        const dx = Math.abs((step.x - target.x) * state.board.image.width);
        const dy = Math.abs((step.y - target.y) * state.board.image.height);
        if (Math.hypot(dx, dy) <= thresholdPx) {
          indexes.push(index);
        }
        return indexes;
      }, []);
    }

    function closeStepChooser() {
      state.stepChooser.visible = false;
      state.stepChooser.stepIndexes = [];
      if (!stepChooser) return;
      stepChooser.classList.add("is-hidden");
      stepChooser.setAttribute("aria-hidden", "true");
      stepChooser.innerHTML = "";
    }

    function renderStepChooser() {
      if (!stepChooser || !stage) return;
      const indexes = state.stepChooser.stepIndexes || [];
      if (!state.stepChooser.visible || indexes.length <= 1) {
        closeStepChooser();
        return;
      }
      stepChooser.innerHTML = `
        <p class="step-chooser-title">จุดนี้มีหลาย step ซ้อนกัน เลือกเลขที่ต้องการ</p>
        <div class="step-chooser-list">
          ${indexes.map((index) => {
            const step = state.board.steps[index] || {};
            const label = String(step.label || "").trim();
            return `
              <button
                class="step-chooser-option${index === selectedStepIndex() ? " is-active" : ""}"
                type="button"
                data-step-chooser-index="${index}"
              >
                <strong>Step ${index + 1}</strong>
                <small>${label || "ยังไม่มี label"}</small>
              </button>
            `;
          }).join("")}
        </div>
      `;

      const stageRect = stage.getBoundingClientRect();
      const localX = state.stepChooser.clientX - stageRect.left;
      const localY = state.stepChooser.clientY - stageRect.top;
      stepChooser.classList.remove("is-hidden");
      stepChooser.setAttribute("aria-hidden", "false");

      const chooserRect = stepChooser.getBoundingClientRect();
      const maxLeft = Math.max(12, stageRect.width - chooserRect.width - 12);
      const maxTop = Math.max(12, stageRect.height - chooserRect.height - 12);
      const left = clamp(localX + 12, 12, maxLeft);
      const top = clamp(localY - 6, 12, maxTop);
      stepChooser.style.left = `${left}px`;
      stepChooser.style.top = `${top}px`;
    }

    function openStepChooser(stepIndexes, clientX, clientY) {
      state.stepChooser.visible = true;
      state.stepChooser.clientX = clientX;
      state.stepChooser.clientY = clientY;
      state.stepChooser.stepIndexes = stepIndexes.slice();
      renderStepChooser();
    }

    function pendingInsertIndex() {
      const stepIndex = selectedStepIndex();
      return state.placementMode === "step-before" && stepIndex >= 0
        ? stepIndex
        : stepIndex >= 0
          ? stepIndex + 1
          : state.board.steps.length;
    }

    function stepBadgeOffsetForList(points, index) {
      return {
        offsetX: 0,
        offsetY: 0
      };
    }

    function stepPreviewLayout(points, index) {
      const point = points[index] || null;
      if (!point) return null;
      const badgeOffset = stepBadgeOffsetForList(points, index);
      return {
        point,
        profilePoint: {
          x: point.x,
          y: point.y - viewerPreviewStyle.selfLift
        },
        badgePoint: {
          x: point.x + badgeOffset.offsetX,
          y: point.y + badgeOffset.offsetY
        },
        badgeOffset
      };
    }

    function normalizeBoard() {
      const meta = plainObject(state.board.meta) ? state.board.meta : {};
      const ui = plainObject(meta.ui) ? meta.ui : {};
      const rewardMarker = plainObject(ui.rewardMarker) ? ui.rewardMarker : {};
      const currencyPickup = plainObject(ui.currencyPickup) ? ui.currencyPickup : {};
      state.board.meta = {
        ...meta,
        ui: {
          ...ui,
          rewardMarker: {
            ...rewardMarker,
            size: Math.round(clamp(Number(rewardMarker.size || rewardMarkerSizeInput?.value || 44), 26, 96))
          },
          currencyPickup: {
            ...currencyPickup,
            scale: clamp(Number(currencyPickup.scale || rewardPickupScaleInput?.value || 1.3), 0.7, 2.4),
            countMultiplier: clamp(Number(currencyPickup.countMultiplier || rewardPickupCountInput?.value || 1.45), 0.7, 3.2)
          }
        }
      };

      state.board.steps = (state.board.steps || []).map((step, index) => ({
        i: index,
        x: clamp(Number(step.x || 0), 0, 1),
        y: clamp(Number(step.y || 0), 0, 1),
        label: String(step.label || ""),
        meta: typeof step.meta === "object" && step.meta ? step.meta : {}
      }));

      state.board.rewards = (state.board.rewards || []).map((reward, index) => ({
        id: String(reward.id || rewardId(`reward${index + 1}`)),
        kind: ["coin", "ticket", "gem", "potion", "item"].includes(String(reward.kind || "")) ? String(reward.kind) : "coin",
        amount: Math.max(1, Number(reward.amount || 1)),
        itemCode: String(reward.itemCode || ""),
        label: String(reward.label || ""),
        stepIndex: Number.isInteger(reward.stepIndex) && reward.stepIndex >= 0 && reward.stepIndex < state.board.steps.length
          ? reward.stepIndex
          : null,
        x: typeof reward.x === "number" ? clamp(Number(reward.x), 0, 1) : null,
        y: typeof reward.y === "number" ? clamp(Number(reward.y), 0, 1) : null,
        meta: typeof reward.meta === "object" && reward.meta ? reward.meta : {}
      }));

      state.board.sprites = (state.board.sprites || []).map((sprite, index) => {
        const columns = Math.max(1, Math.round(Number(sprite.columns || 1)));
        const rows = Math.max(1, Math.round(Number(sprite.rows || 1)));
        const maxFrames = Math.max(1, columns * rows);
        const mode = ["once", "loop", "pingpong"].includes(String(sprite.mode || "")) ? String(sprite.mode) : "loop";
        return {
          id: String(sprite.id || spriteId(`sprite${index + 1}`)),
          label: String(sprite.label || ""),
          src: String(sprite.src || ""),
          x: clamp(Number(sprite.x || 0), 0, 1),
          y: clamp(Number(sprite.y || 0), 0, 1),
          width: Math.max(1, Math.round(Number(sprite.width || 48))),
          height: Math.max(1, Math.round(Number(sprite.height || 48))),
          columns,
          rows,
          frameWidth: Math.max(0, Math.round(Number(sprite.frameWidth || 0))),
          frameHeight: Math.max(0, Math.round(Number(sprite.frameHeight || 0))),
          frameCount: clamp(Math.round(Number(sprite.frameCount || maxFrames)), 1, maxFrames),
          fps: clamp(Number(sprite.fps || 12), 1, 60),
          mode,
          autoplay: sprite.autoplay !== false,
          meta: typeof sprite.meta === "object" && sprite.meta ? sprite.meta : {}
        };
      });
      preloadSpriteImages();
    }

    function refreshMeta() {
      syncSelection();
      syncMultiSelection();
      const ui = boardUiSettings();
      rewardMarkerSizeInput.value = String(Math.round(ui.rewardMarker.size));
      rewardPickupScaleInput.value = String(ui.currencyPickup.scale);
      rewardPickupCountInput.value = String(ui.currencyPickup.countMultiplier);
      stepCountPill.innerHTML = `<strong>${state.board.steps.length}</strong> step`;
      rewardCountPill.innerHTML = `<strong>${state.board.rewards.length}</strong> reward`;
      spriteCountPill.innerHTML = `<strong>${state.board.sprites.length}</strong> sprite`;
      const selectionParts = [];
      if (state.multiSelection.steps.length) selectionParts.push(`step ${state.multiSelection.steps.length}`);
      if (state.multiSelection.rewards.length) selectionParts.push(`reward ${state.multiSelection.rewards.length}`);
      if (state.multiSelection.sprites.length) selectionParts.push(`sprite ${state.multiSelection.sprites.length}`);
      selectionCountPill.innerHTML = selectionParts.length
        ? `<strong>${totalMultiSelectionCount()}</strong> selected • ${selectionParts.join(" • ")}`
        : "ยังไม่ได้คลุมเลือก";

      const step = state.board.steps[selectedStepIndex()] || null;
      selectedStepInfo.textContent = step
        ? `${state.multiSelection.steps.length > 1 ? `เลือก step ${state.multiSelection.steps.length} จุด • ` : ""}step #${step.i + 1} • (${step.x.toFixed(4)}, ${step.y.toFixed(4)})`
        : "ยังไม่ได้เลือก step";
      stepLabelInput.value = step?.label || "";
      stepLabelInput.disabled = !step;
      deleteStepButton.disabled = !step;

      const reward = state.board.rewards[selectedRewardIndex()] || null;
      selectedRewardInfo.textContent = reward
        ? `${state.multiSelection.rewards.length > 1 ? `เลือก reward ${state.multiSelection.rewards.length} ชิ้น • ` : ""}${reward.id} • ${reward.kind} • ${reward.stepIndex === null ? "unlinked" : `step ${reward.stepIndex + 1}`}`
        : "ยังไม่ได้เลือก reward";
      rewardKindSelect.value = reward?.kind || "coin";
      rewardAmountInput.value = String(reward?.amount || 1);
      rewardLabelInput.value = reward?.label || "";
      rewardItemCodeInput.value = reward?.itemCode || "";
      rewardKindSelect.disabled = !reward;
      rewardAmountInput.disabled = !reward;
      rewardLabelInput.disabled = !reward;
      rewardItemCodeInput.disabled = !reward;
      deleteRewardButton.disabled = !reward;
      duplicateRewardButton.disabled = !reward;
      linkRewardButton.disabled = !step || !reward;
      unlinkRewardButton.disabled = !reward || reward.stepIndex === null;

      const sprite = state.board.sprites[selectedSpriteIndex()] || null;
      selectedSpriteInfo.textContent = sprite
        ? `${state.multiSelection.sprites.length > 1 ? `เลือก sprite ${state.multiSelection.sprites.length} ชิ้น • ` : ""}${sprite.id} • ${sprite.columns}x${sprite.rows} • ${sprite.frameCount} frame • (${sprite.x.toFixed(4)}, ${sprite.y.toFixed(4)})`
        : "ยังไม่ได้เลือก sprite";
      spriteLabelInput.value = sprite?.label || "";
      spriteSrcInput.value = sprite?.src || "";
      spriteColumnsInput.value = String(sprite?.columns || 1);
      spriteRowsInput.value = String(sprite?.rows || 1);
      spriteFrameWidthInput.value = String(sprite?.frameWidth || 0);
      spriteFrameHeightInput.value = String(sprite?.frameHeight || 0);
      spriteWidthInput.value = String(sprite?.width || 48);
      spriteHeightInput.value = String(sprite?.height || 48);
      spriteXInput.value = String(sprite?.x || 0);
      spriteYInput.value = String(sprite?.y || 0);
      spriteFrameCountInput.value = String(sprite?.frameCount || 1);
      spriteFpsInput.value = String(sprite?.fps || 12);
      spriteModeSelect.value = sprite?.mode || "loop";
      for (const input of [
        spriteLabelInput,
        spriteFileInput,
        spriteSrcInput,
        spriteColumnsInput,
        spriteRowsInput,
        spriteFrameWidthInput,
        spriteFrameHeightInput,
        spriteWidthInput,
        spriteHeightInput,
        spriteXInput,
        spriteYInput,
        spriteFrameCountInput,
        spriteFpsInput,
        spriteModeSelect,
        deleteSpriteButton,
        duplicateSpriteButton
      ]) {
        input.disabled = !sprite;
      }
      jsonPreview.value = JSON.stringify(state.board, null, 2);
      refreshSelectionScaleUi();
      updateModeButtons();
      syncCanvasCursor();
    }

    async function loadBackground() {
      if (!state.board?.image?.source) {
        state.background = null;
        return;
      }
      state.background = await new Promise((resolve, reject) => {
        const image = new Image();
        image.decoding = "async";
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error("Background load failed"));
        image.src = state.board.image.source;
      });
    }

    function setSelection(stepIndex = state.selectedStep, rewardIndex = state.selectedReward, spriteIndex = state.selectedSprite, options = {}) {
      state.selectedStep = stepIndex;
      state.selectedReward = rewardIndex;
      state.selectedSprite = spriteIndex;
      syncSelection();
      if (!options.preserveMultiSelection) {
        clearMultiSelectionState();
      }
      refreshMeta();
      render();
    }

    function findNearestStep(point, radiusPx = 18) {
      const radius = radiusPx / state.camera.zoom;
      let best = { index: -1, distance: Infinity };
      state.board.steps.forEach((step, index) => {
        const p = stepPoint(step);
        const distance = Math.hypot(point.x - p.x, point.y - p.y);
        if (distance <= radius && distance < best.distance) {
          best = { index, distance };
        }
      });
      return best.index;
    }

    function findNearestReward(point, radiusPx = 24) {
      const markerScreenRadius = (boardUiSettings().rewardMarker.size * state.camera.zoom) / 2;
      const radius = Math.max(radiusPx, markerScreenRadius + 8) / state.camera.zoom;
      let best = { index: -1, distance: Infinity };
      state.board.rewards.forEach((reward, index) => {
        const p = rewardPoint(reward);
        if (!p) return;
        const distance = Math.hypot(point.x - p.x, point.y - p.y);
        if (distance <= radius && distance < best.distance) {
          best = { index, distance };
        }
      });
      return best.index;
    }

    function findNearestSprite(point, radiusPx = 28) {
      const radius = radiusPx / state.camera.zoom;
      let best = { index: -1, distance: Infinity };
      state.board.sprites.forEach((sprite, index) => {
        const p = spritePoint(sprite);
        if (!p) return;
        const halfW = Math.max(8, Number(sprite.width || 48) / 2);
        const halfH = Math.max(8, Number(sprite.height || 48) / 2);
        const insideBox = Math.abs(point.x - p.x) <= halfW && Math.abs(point.y - p.y) <= halfH;
        const distance = Math.hypot(point.x - p.x, point.y - p.y);
        if ((insideBox || distance <= radius) && distance < best.distance) {
          best = { index, distance };
        }
      });
      return best.index;
    }

    function insertStepAt(point, options = {}) {
      const anchorStepIndex = Number.isInteger(options.anchorStepIndex)
        ? normalizeSelectionIndex(options.anchorStepIndex, state.board.steps.length)
        : selectedStepIndex();
      const appendToEnd = options.appendToEnd === true;
      const normalized = {
        i: 0,
        x: clamp(point.x / state.board.image.width, 0, 1),
        y: clamp(point.y / state.board.image.height, 0, 1),
        label: "",
        meta: {}
      };
      const insertAt = appendToEnd
        ? state.board.steps.length
        : state.placementMode === "step-before" && anchorStepIndex >= 0
        ? anchorStepIndex
        : anchorStepIndex >= 0
          ? anchorStepIndex + 1
          : state.board.steps.length;

      state.board.steps.splice(insertAt, 0, normalized);
      state.board.rewards = state.board.rewards.map((reward) => {
        if (Number.isInteger(reward.stepIndex) && reward.stepIndex >= insertAt) {
          return { ...reward, stepIndex: reward.stepIndex + 1 };
        }
        return reward;
      });
      normalizeBoard();
      setDirty(true);
      setSelection(insertAt, -1);
    }

    function addRewardAt(point) {
      const reward = {
        id: rewardId(),
        kind: "coin",
        amount: 1,
        itemCode: "",
        label: "",
        stepIndex: null,
        x: clamp(point.x / state.board.image.width, 0, 1),
        y: clamp(point.y / state.board.image.height, 0, 1),
        meta: {}
      };
      state.board.rewards.push(reward);
      normalizeBoard();
      setDirty(true);
      setSelection(state.selectedStep, state.board.rewards.length - 1);
    }

    function addSpriteAt(point) {
      const sprite = {
        id: spriteId(),
        label: "",
        src: "",
        x: clamp(point.x / state.board.image.width, 0, 1),
        y: clamp(point.y / state.board.image.height, 0, 1),
        width: 48,
        height: 48,
        columns: 1,
        rows: 1,
        frameWidth: 0,
        frameHeight: 0,
        frameCount: 1,
        fps: 12,
        mode: "loop",
        autoplay: true,
        meta: {}
      };
      state.board.sprites.push(sprite);
      normalizeBoard();
      setDirty(true);
      setSelection(state.selectedStep, state.selectedReward, state.board.sprites.length - 1);
    }

    function deleteSelectedStep() {
      const stepIndex = selectedStepIndex();
      if (stepIndex < 0) return;
      const removed = state.board.steps[stepIndex];
      state.board.steps.splice(stepIndex, 1);
      state.board.rewards = state.board.rewards.map((reward) => {
        if (reward.stepIndex === stepIndex) {
          return {
            ...reward,
            stepIndex: null,
            x: reward.x ?? removed.x,
            y: reward.y ?? removed.y
          };
        }
        if (Number.isInteger(reward.stepIndex) && reward.stepIndex > stepIndex) {
          return { ...reward, stepIndex: reward.stepIndex - 1 };
        }
        return reward;
      });
      normalizeBoard();
      setDirty(true);
      setSelection(Math.min(stepIndex, state.board.steps.length - 1), state.selectedReward);
    }

    function deleteSelectedReward() {
      const rewardIndex = selectedRewardIndex();
      if (rewardIndex < 0) return;
      state.board.rewards.splice(rewardIndex, 1);
      normalizeBoard();
      setDirty(true);
      setSelection(state.selectedStep, Math.min(rewardIndex, state.board.rewards.length - 1));
    }

    function deleteSelectedSprite() {
      const spriteIndex = selectedSpriteIndex();
      if (spriteIndex < 0) return;
      state.board.sprites.splice(spriteIndex, 1);
      normalizeBoard();
      setDirty(true);
      setSelection(state.selectedStep, state.selectedReward, Math.min(spriteIndex, state.board.sprites.length - 1));
    }

    function duplicateSelectedReward() {
      const rewardIndex = selectedRewardIndex();
      const reward = state.board.rewards[rewardIndex];
      if (!reward) return;
      const clone = deepClone(reward);
      clone.id = rewardId();
      state.board.rewards.splice(rewardIndex + 1, 0, clone);
      normalizeBoard();
      setDirty(true);
      setSelection(state.selectedStep, rewardIndex + 1);
    }

    function duplicateSelectedSprite() {
      const spriteIndex = selectedSpriteIndex();
      const sprite = state.board.sprites[spriteIndex];
      if (!sprite) return;
      const clone = deepClone(sprite);
      clone.id = spriteId();
      clone.x = clamp(Number(clone.x || 0) + (18 / state.board.image.width), 0, 1);
      clone.y = clamp(Number(clone.y || 0) + (18 / state.board.image.height), 0, 1);
      state.board.sprites.splice(spriteIndex + 1, 0, clone);
      normalizeBoard();
      setDirty(true);
      setSelection(state.selectedStep, state.selectedReward, spriteIndex + 1);
    }

    function centerOnSelected() {
      const selectionBounds = totalMultiSelectionCount() > 1 ? selectionTransformBounds() : null;
      if (selectionBounds) {
        state.camera.x = selectionBounds.centerX;
        state.camera.y = selectionBounds.centerY;
        render();
        return;
      }
      const reward = state.board.rewards[selectedRewardIndex()] || null;
      if (reward) {
        const point = rewardPoint(reward);
        if (point) {
          state.camera.x = point.x;
          state.camera.y = point.y;
          render();
          return;
        }
      }
      const step = state.board.steps[selectedStepIndex()] || null;
      if (step) {
        const point = stepPoint(step);
        state.camera.x = point.x;
        state.camera.y = point.y;
        render();
        return;
      }
      const sprite = state.board.sprites[selectedSpriteIndex()] || null;
      if (sprite) {
        const point = spritePoint(sprite);
        if (point) {
          state.camera.x = point.x;
          state.camera.y = point.y;
          render();
        }
      }
    }

    function drawRoundRectPath(x, y, width, height, radius) {
      const r = Math.min(radius, width / 2, height / 2);
      ctx.beginPath();
      ctx.moveTo(x + r, y);
      ctx.lineTo(x + width - r, y);
      ctx.quadraticCurveTo(x + width, y, x + width, y + r);
      ctx.lineTo(x + width, y + height - r);
      ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
      ctx.lineTo(x + r, y + height);
      ctx.quadraticCurveTo(x, y + height, x, y + height - r);
      ctx.lineTo(x, y + r);
      ctx.quadraticCurveTo(x, y, x + r, y);
      ctx.closePath();
    }

    function drawGuideLine(fromPoint, toPoint, color, dash = []) {
      ctx.save();
      ctx.strokeStyle = color;
      ctx.lineWidth = 1.8 / state.camera.zoom;
      ctx.setLineDash(dash.map((value) => value / state.camera.zoom));
      ctx.beginPath();
      ctx.moveTo(fromPoint.x, fromPoint.y);
      ctx.lineTo(toPoint.x, toPoint.y);
      ctx.stroke();
      ctx.restore();
    }

    function drawPreviewAvatar(point, emphasis = 0.32) {
      const radius = viewerPreviewStyle.selfSize / 2;
      ctx.save();
      ctx.translate(point.x, point.y);
      ctx.beginPath();
      ctx.arc(0, 0, radius, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(255, 158, 227, ${0.16 + (emphasis * 0.34)})`;
      ctx.fill();
      ctx.lineWidth = 2.4;
      ctx.strokeStyle = `rgba(255, 241, 248, ${0.36 + (emphasis * 0.58)})`;
      ctx.stroke();

      ctx.beginPath();
      ctx.arc(0, -radius * 0.18, radius * 0.24, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(255, 255, 255, ${0.68 + (emphasis * 0.26)})`;
      ctx.fill();

      ctx.beginPath();
      ctx.arc(0, radius * 0.26, radius * 0.34, Math.PI, Math.PI * 2);
      ctx.fillStyle = `rgba(255, 255, 255, ${0.58 + (emphasis * 0.22)})`;
      ctx.fill();
      ctx.restore();
    }

    function drawPreviewBadge(point, text, emphasis = 0.32) {
      const fontSize = viewerPreviewStyle.stepBadgeSize * 0.46;
      ctx.save();
      ctx.font = `700 ${fontSize}px 'FC Vision Rounded', sans-serif`;
      const textWidth = ctx.measureText(text).width;
      const width = Math.max(viewerPreviewStyle.stepBadgeSize, textWidth + 12);
      const height = viewerPreviewStyle.stepBadgeSize;
      drawRoundRectPath(point.x - (width / 2), point.y - (height / 2), width, height, height / 2);
      ctx.fillStyle = `rgba(7, 11, 28, ${0.24 + (emphasis * 0.16)})`;
      ctx.fill();
      ctx.lineWidth = 1.5;
      ctx.strokeStyle = `rgba(245, 251, 255, ${0.38 + (emphasis * 0.42)})`;
      ctx.stroke();
      ctx.fillStyle = `rgba(245, 251, 255, ${0.8 + (emphasis * 0.18)})`;
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText(text, point.x, point.y + 0.5);
      ctx.restore();
    }

    function drawSpriteMarker(sprite, index, ts = performance.now()) {
      const point = spritePoint(sprite);
      if (!point) return;
      const width = Math.max(1, Number(sprite.width || 48));
      const height = Math.max(1, Number(sprite.height || 48));
      const columns = Math.max(1, Number(sprite.columns || 1));
      const rows = Math.max(1, Number(sprite.rows || 1));
      const frame = spriteFrameIndex(sprite, ts);
      const col = frame % columns;
      const row = Math.floor(frame / columns) % rows;
      const image = loadSpriteImage(sprite);

      ctx.save();
      ctx.translate(point.x, point.y);
      if (image?.complete && image.naturalWidth > 0) {
        const frameWidth = Math.max(1, Number(sprite.frameWidth || 0) || Math.floor(image.naturalWidth / columns));
        const frameHeight = Math.max(1, Number(sprite.frameHeight || 0) || Math.floor(image.naturalHeight / rows));
        ctx.drawImage(
          image,
          col * frameWidth,
          row * frameHeight,
          frameWidth,
          frameHeight,
          -width / 2,
          -height / 2,
          width,
          height
        );
      } else {
        drawRoundRectPath(-width / 2, -height / 2, width, height, Math.min(14, width / 4, height / 4));
        ctx.fillStyle = "rgba(143, 245, 255, 0.16)";
        ctx.fill();
        ctx.lineWidth = 2 / state.camera.zoom;
        ctx.strokeStyle = "rgba(143, 245, 255, 0.78)";
        ctx.setLineDash([8 / state.camera.zoom, 6 / state.camera.zoom]);
        ctx.stroke();
      }

      const isPrimarySelection = index === selectedSpriteIndex();
      const isGroupedSelection = isMultiSelected("sprite", index);
      if (isPrimarySelection || isGroupedSelection) {
        ctx.lineWidth = 3 / state.camera.zoom;
        ctx.strokeStyle = isPrimarySelection ? "rgba(255, 158, 227, 0.96)" : "rgba(255, 222, 148, 0.94)";
        ctx.setLineDash([]);
        drawRoundRectPath(-width / 2 - 5, -height / 2 - 5, width + 10, height + 10, Math.min(18, width / 4, height / 4));
        ctx.stroke();
      }
      ctx.restore();
    }

    function drawSprites(ts = performance.now()) {
      (state.board?.sprites || []).forEach((sprite, index) => drawSpriteMarker(sprite, index, ts));
    }

    function drawStepGuide(layout, index, options = {}) {
      if (!layout) return;
      const emphasis = options.preview ? 1 : options.selected ? 0.86 : 0.22;
      const badgeText = String(index + 1);
      drawGuideLine(
        layout.point,
        layout.profilePoint,
        `rgba(255, 158, 227, ${0.18 + (emphasis * 0.58)})`,
        options.preview ? [9, 7] : []
      );
      drawGuideLine(
        layout.point,
        layout.badgePoint,
        `rgba(143, 245, 255, ${0.16 + (emphasis * 0.52)})`,
        options.preview ? [7, 6] : []
      );
      drawPreviewAvatar(layout.profilePoint, emphasis);
      drawPreviewBadge(layout.badgePoint, badgeText, emphasis);
    }

    function drawHoverPreview() {
      if (!state.showGuides || !state.hoverPoint || state.placementMode === "reward") {
        return;
      }

      const points = stepPoints();
      const insertAt = pendingInsertIndex();
      points.splice(insertAt, 0, state.hoverPoint);

      const layout = stepPreviewLayout(points, insertAt);
      if (!layout) return;

      const prevPoint = points[insertAt - 1] || null;
      const nextPoint = points[insertAt + 1] || null;
      if (prevPoint) {
        drawGuideLine(prevPoint, layout.point, "rgba(255, 255, 255, 0.26)", [11, 8]);
      }
      if (nextPoint) {
        drawGuideLine(layout.point, nextPoint, "rgba(255, 255, 255, 0.26)", [11, 8]);
      }

      ctx.save();
      ctx.translate(layout.point.x, layout.point.y);
      ctx.beginPath();
      ctx.arc(0, 0, 16 / state.camera.zoom, 0, Math.PI * 2);
      ctx.fillStyle = "rgba(255, 158, 227, 0.24)";
      ctx.fill();
      ctx.lineWidth = 3 / state.camera.zoom;
      ctx.strokeStyle = "rgba(255, 158, 227, 0.92)";
      ctx.setLineDash([10 / state.camera.zoom, 8 / state.camera.zoom]);
      ctx.stroke();
      ctx.restore();

      drawStepGuide(layout, insertAt, { preview: true });
    }

    function render() {
      if (!state.board) return;
      const rect = canvas.getBoundingClientRect();
      ctx.clearRect(0, 0, rect.width, rect.height);
      ctx.save();
      ctx.fillStyle = "#04070f";
      ctx.fillRect(0, 0, rect.width, rect.height);

      ctx.translate(rect.width / 2, rect.height / 2);
      ctx.scale(state.camera.zoom, state.camera.zoom);
      ctx.translate(-state.camera.x, -state.camera.y);

      if (state.background) {
        ctx.drawImage(state.background, 0, 0, state.board.image.width, state.board.image.height);
      }

      ctx.strokeStyle = "rgba(156, 208, 255, 0.18)";
      ctx.lineWidth = 6 / state.camera.zoom;
      ctx.beginPath();
      state.board.steps.forEach((step, index) => {
        const point = stepPoint(step);
        if (index === 0) {
          ctx.moveTo(point.x, point.y);
        } else {
          ctx.lineTo(point.x, point.y);
        }
      });
      ctx.stroke();

      if (state.showGuides) {
        const points = stepPoints();
        points.forEach((point, index) => {
          drawStepGuide(
            stepPreviewLayout(points, index),
            index,
            { selected: index === selectedStepIndex() || isMultiSelected("step", index) }
          );
        });
      }

      state.board.rewards.forEach((reward, index) => {
        const point = rewardPoint(reward);
        if (!point) return;
        const color = rewardColors[reward.kind] || "#ffffff";
        const markerRadius = boardUiSettings().rewardMarker.size / 2;
        const isPrimarySelection = index === selectedRewardIndex();
        const isGroupedSelection = isMultiSelected("reward", index);

        if (Number.isInteger(reward.stepIndex) && state.board.steps[reward.stepIndex]) {
          const step = stepPoint(state.board.steps[reward.stepIndex]);
          ctx.save();
          ctx.strokeStyle = "rgba(255, 255, 255, 0.24)";
          ctx.lineWidth = 2 / state.camera.zoom;
          ctx.setLineDash([10 / state.camera.zoom, 8 / state.camera.zoom]);
          ctx.beginPath();
          ctx.moveTo(point.x, point.y);
          ctx.lineTo(step.x, step.y);
          ctx.stroke();
          ctx.restore();
        }

        ctx.save();
        ctx.translate(point.x, point.y);
        ctx.beginPath();
        ctx.arc(0, 0, markerRadius, 0, Math.PI * 2);
        ctx.fillStyle = "rgba(8, 13, 29, 0.88)";
        ctx.fill();
        ctx.lineWidth = (isPrimarySelection || isGroupedSelection ? 5 : 3) / state.camera.zoom;
        ctx.strokeStyle = color;
        ctx.stroke();
        if (isPrimarySelection || isGroupedSelection) {
          ctx.beginPath();
          ctx.arc(0, 0, markerRadius + (7 / state.camera.zoom), 0, Math.PI * 2);
          ctx.lineWidth = 2.2 / state.camera.zoom;
          ctx.strokeStyle = isPrimarySelection ? "rgba(255, 158, 227, 0.96)" : "rgba(255, 222, 148, 0.94)";
          ctx.stroke();
        }
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.moveTo(0, -markerRadius * 0.55);
        ctx.lineTo(markerRadius * 0.45, 0);
        ctx.lineTo(0, markerRadius * 0.55);
        ctx.lineTo(-markerRadius * 0.45, 0);
        ctx.closePath();
        ctx.fill();
        ctx.restore();
      });

      drawSprites();

      state.board.steps.forEach((step, index) => {
        const point = stepPoint(step);
        const isPrimarySelection = index === selectedStepIndex();
        const isGroupedSelection = isMultiSelected("step", index);
        ctx.save();
        ctx.translate(point.x, point.y);
        ctx.beginPath();
        ctx.arc(0, 0, 14 / state.camera.zoom, 0, Math.PI * 2);
        ctx.fillStyle = isPrimarySelection
          ? "rgba(255, 158, 227, 0.9)"
          : isGroupedSelection
            ? "rgba(255, 222, 148, 0.92)"
            : "rgba(136, 245, 255, 0.9)";
        ctx.fill();
        ctx.lineWidth = (isPrimarySelection || isGroupedSelection ? 4 : 3) / state.camera.zoom;
        ctx.strokeStyle = "rgba(7, 12, 28, 0.9)";
        ctx.stroke();
        ctx.fillStyle = "#0a1020";
        ctx.font = `${18 / state.camera.zoom}px 'FC Vision Rounded', sans-serif`;
        ctx.textAlign = "center";
        ctx.fillText(String(index + 1), 0, 6 / state.camera.zoom);
        ctx.restore();
      });

      drawHoverPreview();
      drawSelectionTransform();

      ctx.restore();
      drawSelectionMarquee();
    }

    function saveBoardToServer() {
      return fetchToolJson("save_board", { board: state.board });
    }

    function downloadBoardJson() {
      const blob = new Blob([JSON.stringify(state.board, null, 2)], { type: "application/json" });
      const anchor = document.createElement("a");
      anchor.href = URL.createObjectURL(blob);
      anchor.download = `board-${boardCode}.v1.json`;
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();
      URL.revokeObjectURL(anchor.href);
    }

    function beginPan(clientX, clientY) {
      state.interaction.dragging = true;
      state.interaction.dragType = "pan";
      state.interaction.startX = clientX;
      state.interaction.startY = clientY;
      state.interaction.cameraX = state.camera.x;
      state.interaction.cameraY = state.camera.y;
      state.interaction.pendingPoint = null;
      state.interaction.pendingAnchorStepIndex = -1;
      state.interaction.pendingAppendToEnd = false;
      state.interaction.dragOriginPoint = null;
      state.interaction.batchSnapshot = null;
    }

    function beginObjectDrag(type, clientX, clientY) {
      state.interaction.dragging = true;
      state.interaction.dragType = type;
      state.interaction.startX = clientX;
      state.interaction.startY = clientY;
      state.interaction.pendingPoint = null;
      state.interaction.pendingAnchorStepIndex = -1;
      state.interaction.pendingAppendToEnd = false;
      state.interaction.dragOriginPoint = null;
      state.interaction.batchSnapshot = null;
    }

    function beginPendingPlacement(point, clientX, clientY, options = {}) {
      state.interaction.dragging = true;
      state.interaction.dragType = "pending-place";
      state.interaction.startX = clientX;
      state.interaction.startY = clientY;
      state.interaction.cameraX = state.camera.x;
      state.interaction.cameraY = state.camera.y;
      state.interaction.pendingPoint = point;
      state.interaction.pendingAnchorStepIndex = Number.isInteger(options.anchorStepIndex)
        ? options.anchorStepIndex
        : -1;
      state.interaction.pendingAppendToEnd = options.appendToEnd === true;
      state.interaction.dragOriginPoint = null;
      state.interaction.batchSnapshot = null;
    }

    function commitPlacement(point, options = {}) {
      if (!point) return;
      if (state.placementMode === "reward") {
        addRewardAt(point);
      } else if (state.placementMode === "sprite") {
        addSpriteAt(point);
      } else {
        insertStepAt(point, options);
      }
    }

    function updateHoverPoint(event) {
      if (!state.board) return;
      if (!state.showGuides || state.spacePan || state.selectionTool || state.placementMode === "reward" || state.placementMode === "sprite") {
        state.hoverPoint = null;
        return;
      }
      state.hoverPoint = boardPointFromScreen(event.clientX, event.clientY);
    }

    function updateTransformHover(point) {
      const transform = selectionTransformHit(point);
      state.interaction.hoverTransformHandle = transform.handle?.id || "";
      state.interaction.hoverTransformBody = !transform.handle && transform.inside && totalMultiSelectionCount() > 1;
    }

    function handlePointerDown(event) {
      if (!state.board) return;
      closeStepChooser();
      if (event.button === 1 || event.button === 2 || state.spacePan) {
        event.preventDefault();
        beginPan(event.clientX, event.clientY);
        state.hoverPoint = null;
        canvas.setPointerCapture?.(event.pointerId);
        syncCanvasCursor();
        return;
      }
      const point = boardPointFromScreen(event.clientX, event.clientY);
      const hitStep = findNearestStep(point);
      const hitReward = findNearestReward(point);
      const hitSprite = findNearestSprite(point);
      const transform = selectionTransformHit(point);
      const hitSelectedGroup = hitSelectedGroupTarget(hitStep, hitReward, hitSprite);
      const selectionModeActive = state.selectionTool || event.shiftKey;
      const allowDuplicatePlace = isDuplicatePlacementModifier(event)
        && (state.placementMode === "step-after" || state.placementMode === "step-before");

      if (allowDuplicatePlace) {
        const baseStepIndex = hitStep >= 0
          ? hitStep
          : selectedStepIndex() >= 0
            ? selectedStepIndex()
            : -1;
        const duplicatePoint = baseStepIndex >= 0 ? stepPoint(state.board.steps[baseStepIndex]) : point;
        beginPendingPlacement(duplicatePoint, event.clientX, event.clientY, {
          anchorStepIndex: baseStepIndex,
          appendToEnd: true
        });
        state.hoverPoint = duplicatePoint;
        canvas.setPointerCapture?.(event.pointerId);
        syncCanvasCursor();
        render();
        return;
      }

      if (transform.handle && totalMultiSelectionCount() > 1) {
        beginSelectionScale(transform.handle, point, event.clientX, event.clientY);
        state.hoverPoint = null;
        canvas.setPointerCapture?.(event.pointerId);
        syncCanvasCursor();
        render();
        return;
      }

      if (transform.inside && totalMultiSelectionCount() > 1) {
        beginSelectionBatchDrag(point, event.clientX, event.clientY);
        state.hoverPoint = null;
        canvas.setPointerCapture?.(event.pointerId);
        syncCanvasCursor();
        render();
        return;
      }

      if (hitSelectedGroup && totalMultiSelectionCount() > 0) {
        beginSelectionBatchDrag(point, event.clientX, event.clientY);
        state.hoverPoint = null;
        canvas.setPointerCapture?.(event.pointerId);
        syncCanvasCursor();
        render();
        return;
      }

      if (state.placementMode === "sprite") {
        if (hitSprite >= 0) {
          if (selectionModeActive) {
            setMultiSelection(buildSingleSelection("sprite", hitSprite));
          } else {
            setSelection(state.selectedStep, state.selectedReward, hitSprite);
          }
          beginObjectDrag("sprite", event.clientX, event.clientY);
        } else if (selectionModeActive) {
          beginSelectionMarquee(event.clientX, event.clientY);
        } else {
          beginPendingPlacement(point, event.clientX, event.clientY);
        }
        state.hoverPoint = null;
        canvas.setPointerCapture?.(event.pointerId);
        syncCanvasCursor();
        render();
        return;
      }

      if (hitReward >= 0) {
        if (selectionModeActive) {
          setMultiSelection(buildSingleSelection("reward", hitReward));
        } else {
          setSelection(state.selectedStep, hitReward, state.selectedSprite);
        }
        beginObjectDrag("reward", event.clientX, event.clientY);
      } else if (hitSprite >= 0) {
        if (selectionModeActive) {
          setMultiSelection(buildSingleSelection("sprite", hitSprite));
        } else {
          setSelection(state.selectedStep, state.selectedReward, hitSprite);
        }
        beginObjectDrag("sprite", event.clientX, event.clientY);
      } else if (hitStep >= 0) {
        const stackIndexes = stepStackIndexes(hitStep);
        if (stackIndexes.length > 1) {
          openStepChooser(stackIndexes, event.clientX, event.clientY);
          render();
          return;
        }
        if (selectionModeActive) {
          setMultiSelection(buildSingleSelection("step", hitStep));
        } else {
          setSelection(hitStep, state.selectedReward, state.selectedSprite);
        }
        beginObjectDrag("step", event.clientX, event.clientY);
      } else if (selectionModeActive) {
        beginSelectionMarquee(event.clientX, event.clientY);
      } else {
        beginPendingPlacement(point, event.clientX, event.clientY);
      }

      state.hoverPoint = null;
      canvas.setPointerCapture?.(event.pointerId);
      syncCanvasCursor();
      render();
    }

    function handlePointerMove(event) {
      if (!state.board) return;
      if (!state.interaction.dragging) {
        const point = boardPointFromScreen(event.clientX, event.clientY);
        updateTransformHover(point);
        updateHoverPoint(event);
        render();
        return;
      }
      const point = boardPointFromScreen(event.clientX, event.clientY);
      if (state.interaction.dragType === "step" && state.selectedStep >= 0) {
        const stepIndex = selectedStepIndex();
        if (stepIndex < 0) {
          render();
          return;
        }
        state.board.steps[stepIndex].x = clamp(point.x / state.board.image.width, 0, 1);
        state.board.steps[stepIndex].y = clamp(point.y / state.board.image.height, 0, 1);
        normalizeBoard();
        setDirty(true);
        refreshMeta();
      } else if (state.interaction.dragType === "reward" && state.selectedReward >= 0) {
        const rewardIndex = selectedRewardIndex();
        if (rewardIndex < 0) {
          render();
          return;
        }
        state.board.rewards[rewardIndex].x = clamp(point.x / state.board.image.width, 0, 1);
        state.board.rewards[rewardIndex].y = clamp(point.y / state.board.image.height, 0, 1);
        setDirty(true);
        refreshMeta();
      } else if (state.interaction.dragType === "sprite" && state.selectedSprite >= 0) {
        const spriteIndex = selectedSpriteIndex();
        if (spriteIndex < 0) {
          render();
          return;
        }
        state.board.sprites[spriteIndex].x = clamp(point.x / state.board.image.width, 0, 1);
        state.board.sprites[spriteIndex].y = clamp(point.y / state.board.image.height, 0, 1);
        setDirty(true);
        refreshMeta();
      } else if (state.interaction.dragType === "selection-batch") {
        const origin = state.interaction.dragOriginPoint;
        const snapshot = state.interaction.batchSnapshot;
        if (!origin || !snapshot) {
          render();
          return;
        }
        const deltaX = (point.x - origin.x) / state.board.image.width;
        const deltaY = (point.y - origin.y) / state.board.image.height;
        for (const entry of snapshot.steps) {
          const step = state.board.steps[entry.index];
          if (!step) continue;
          step.x = clamp((entry.x / state.board.image.width) + deltaX, 0, 1);
          step.y = clamp((entry.y / state.board.image.height) + deltaY, 0, 1);
        }
        for (const entry of snapshot.rewards) {
          const reward = state.board.rewards[entry.index];
          if (!reward) continue;
          reward.x = clamp((entry.x / state.board.image.width) + deltaX, 0, 1);
          reward.y = clamp((entry.y / state.board.image.height) + deltaY, 0, 1);
        }
        for (const entry of snapshot.sprites) {
          const sprite = state.board.sprites[entry.index];
          if (!sprite) continue;
          sprite.x = clamp((entry.x / state.board.image.width) + deltaX, 0, 1);
          sprite.y = clamp((entry.y / state.board.image.height) + deltaY, 0, 1);
        }
        normalizeBoard();
        setDirty(true);
        refreshMeta();
      } else if (state.interaction.dragType === "selection-scale") {
        const snapshot = state.interaction.batchSnapshot;
        if (!snapshot || !state.interaction.transformHandle) {
          render();
          return;
        }
        applySelectionScale(snapshot, state.interaction.transformHandle, point);
        normalizeBoard();
        setDirty(true);
        refreshMeta();
      } else if (state.interaction.dragType === "select-marquee") {
        state.interaction.marqueeCurrentX = event.clientX;
        state.interaction.marqueeCurrentY = event.clientY;
      } else if (state.interaction.dragType === "pending-place") {
        if (pointerMoveDistance(event) >= 7) {
          state.interaction.dragType = "pan";
          state.interaction.pendingPoint = null;
          syncCanvasCursor();
        }
        if (state.interaction.dragType === "pan") {
          state.camera.x = state.interaction.cameraX - ((event.clientX - state.interaction.startX) / state.camera.zoom);
          state.camera.y = state.interaction.cameraY - ((event.clientY - state.interaction.startY) / state.camera.zoom);
        }
      } else if (state.interaction.dragType === "pan") {
        state.camera.x = state.interaction.cameraX - ((event.clientX - state.interaction.startX) / state.camera.zoom);
        state.camera.y = state.interaction.cameraY - ((event.clientY - state.interaction.startY) / state.camera.zoom);
      }
      render();
    }

    function handlePointerUp(event) {
      if (!state.interaction.dragging) return;
      if (state.interaction.dragType === "pending-place") {
        const point = state.interaction.pendingPoint || boardPointFromScreen(event.clientX, event.clientY);
        commitPlacement(point, {
          anchorStepIndex: state.interaction.pendingAnchorStepIndex,
          appendToEnd: state.interaction.pendingAppendToEnd
        });
      } else if (state.interaction.dragType === "select-marquee") {
        state.interaction.marqueeCurrentX = event.clientX;
        state.interaction.marqueeCurrentY = event.clientY;
        setMultiSelection(selectionFromMarqueeRect(currentMarqueeRect()));
      }
      state.interaction.dragging = false;
      state.interaction.dragType = "";
      state.interaction.pendingPoint = null;
      state.interaction.pendingAnchorStepIndex = -1;
      state.interaction.pendingAppendToEnd = false;
      state.interaction.dragOriginPoint = null;
      state.interaction.batchSnapshot = null;
      state.interaction.transformHandle = "";
      updateTransformHover(boardPointFromScreen(event.clientX, event.clientY));
      syncCanvasCursor();
      if (canvas.releasePointerCapture && canvas.hasPointerCapture?.(event.pointerId)) {
        canvas.releasePointerCapture(event.pointerId);
      }
      updateHoverPoint(event);
      render();
    }

    canvas.addEventListener("pointerdown", handlePointerDown);
    canvas.addEventListener("pointermove", handlePointerMove);
    canvas.addEventListener("pointerup", handlePointerUp);
    canvas.addEventListener("pointercancel", handlePointerUp);
    canvas.addEventListener("pointerleave", () => {
      state.hoverPoint = null;
      state.interaction.hoverTransformHandle = "";
      state.interaction.hoverTransformBody = false;
      syncCanvasCursor();
      render();
    });
    canvas.addEventListener("contextmenu", (event) => {
      event.preventDefault();
    });
    canvas.addEventListener("wheel", (event) => {
      event.preventDefault();
      const delta = event.deltaY > 0 ? 0.92 : 1.08;
      state.camera.zoom = clamp(state.camera.zoom * delta, 0.06, 2.6);
      render();
    }, { passive: false });

    modeStepAfter.addEventListener("click", () => {
      state.placementMode = "step-after";
      state.hoverPoint = null;
      updateModeButtons();
      render();
    });
    modeStepBefore.addEventListener("click", () => {
      state.placementMode = "step-before";
      state.hoverPoint = null;
      updateModeButtons();
      render();
    });
    modeReward.addEventListener("click", () => {
      state.placementMode = "reward";
      state.hoverPoint = null;
      updateModeButtons();
      render();
    });
    modeSprite.addEventListener("click", () => {
      state.placementMode = "sprite";
      state.hoverPoint = null;
      updateModeButtons();
      render();
    });
    previewGuideButton.addEventListener("click", () => {
      state.showGuides = !state.showGuides;
      if (!state.showGuides) {
        state.hoverPoint = null;
      }
      updateModeButtons();
      render();
    });

    zoomFitButton.addEventListener("click", () => {
      const selectionBounds = totalMultiSelectionCount() > 1 ? selectionTransformBounds() : null;
      state.camera.zoom = fitZoomForBounds(selectionBounds);
      if (selectionBounds) {
        state.camera.x = selectionBounds.centerX;
        state.camera.y = selectionBounds.centerY;
      } else {
        state.camera.x = state.board.image.width / 2;
        state.camera.y = state.board.image.height * 0.84;
      }
      render();
    });
    zoomInButton.addEventListener("click", () => {
      state.camera.zoom = clamp(state.camera.zoom * 1.15, 0.06, 2.6);
      render();
    });
    zoomOutButton.addEventListener("click", () => {
      state.camera.zoom = clamp(state.camera.zoom / 1.15, 0.06, 2.6);
      render();
    });
    centerSelectedButton.addEventListener("click", centerOnSelected);
    marqueeModeButton.addEventListener("click", () => {
      state.selectionTool = !state.selectionTool;
      state.hoverPoint = null;
      refreshMeta();
      render();
    });
    selectAllButton.addEventListener("click", () => {
      setMultiSelection(buildAllSelection());
    });
    clearSelectionButton.addEventListener("click", () => {
      clearMultiSelectionState();
      refreshMeta();
      render();
    });
    selectionScaleLoadButton.addEventListener("click", () => {
      loadSelectionScaleInputs();
      refreshSelectionScaleUi();
    });
    selectionScaleApplyButton.addEventListener("click", () => {
      applySelectionScaleInputs();
    });
    for (const button of selectionScaleAnchorButtons) {
      button.addEventListener("click", () => {
        state.scaleAnchor = String(button.dataset.scaleAnchor || "center");
        refreshSelectionScaleUi();
      });
    }

    deleteStepButton.addEventListener("click", deleteSelectedStep);
    deleteRewardButton.addEventListener("click", deleteSelectedReward);
    duplicateRewardButton.addEventListener("click", duplicateSelectedReward);
    deleteSpriteButton.addEventListener("click", deleteSelectedSprite);
    duplicateSpriteButton.addEventListener("click", duplicateSelectedSprite);
    linkRewardButton.addEventListener("click", () => {
      const reward = state.board.rewards[selectedRewardIndex()];
      const stepIndex = selectedStepIndex();
      if (!reward || stepIndex < 0) return;
      reward.stepIndex = stepIndex;
      normalizeBoard();
      setDirty(true);
      refreshMeta();
      render();
    });
    unlinkRewardButton.addEventListener("click", () => {
      const reward = state.board.rewards[selectedRewardIndex()];
      if (!reward) return;
      reward.stepIndex = null;
      setDirty(true);
      refreshMeta();
      render();
    });

    stepLabelInput.addEventListener("input", () => {
      const step = state.board.steps[selectedStepIndex()];
      if (!step) return;
      step.label = stepLabelInput.value;
      setDirty(true);
      refreshMeta();
      render();
    });
    rewardKindSelect.addEventListener("change", () => {
      const reward = state.board.rewards[selectedRewardIndex()];
      if (!reward) return;
      reward.kind = rewardKindSelect.value;
      setDirty(true);
      refreshMeta();
      render();
    });
    rewardAmountInput.addEventListener("input", () => {
      const reward = state.board.rewards[selectedRewardIndex()];
      if (!reward) return;
      reward.amount = Math.max(1, Number(rewardAmountInput.value || 1));
      setDirty(true);
      refreshMeta();
    });
    rewardLabelInput.addEventListener("input", () => {
      const reward = state.board.rewards[selectedRewardIndex()];
      if (!reward) return;
      reward.label = rewardLabelInput.value;
      setDirty(true);
      refreshMeta();
    });
    rewardItemCodeInput.addEventListener("input", () => {
      const reward = state.board.rewards[selectedRewardIndex()];
      if (!reward) return;
      reward.itemCode = rewardItemCodeInput.value;
      setDirty(true);
      refreshMeta();
    });

    function selectedSprite() {
      return state.board.sprites[selectedSpriteIndex()] || null;
    }

    function updateSelectedSprite(mutator) {
      const sprite = selectedSprite();
      if (!sprite) return;
      mutator(sprite);
      normalizeBoard();
      setDirty(true);
      refreshMeta();
      render();
    }

    function updateBoardUiSettingsFromInputs() {
      if (!state.board) return;
      const meta = plainObject(state.board.meta) ? state.board.meta : {};
      const ui = plainObject(meta.ui) ? meta.ui : {};
      state.board.meta = {
        ...meta,
        ui: {
          ...ui,
          rewardMarker: {
            ...(plainObject(ui.rewardMarker) ? ui.rewardMarker : {}),
            size: Math.round(clamp(Number(rewardMarkerSizeInput.value || 44), 26, 96))
          },
          currencyPickup: {
            ...(plainObject(ui.currencyPickup) ? ui.currencyPickup : {}),
            scale: clamp(Number(rewardPickupScaleInput.value || 1.3), 0.7, 2.4),
            countMultiplier: clamp(Number(rewardPickupCountInput.value || 1.45), 0.7, 3.2)
          }
        }
      };
      normalizeBoard();
      setDirty(true);
      refreshMeta();
      render();
    }

    rewardMarkerSizeInput.addEventListener("input", updateBoardUiSettingsFromInputs);
    rewardPickupScaleInput.addEventListener("input", updateBoardUiSettingsFromInputs);
    rewardPickupCountInput.addEventListener("input", updateBoardUiSettingsFromInputs);

    spriteLabelInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.label = spriteLabelInput.value;
      });
    });
    spriteSrcInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.src = spriteSrcInput.value.trim();
      });
    });
    spriteFileInput.addEventListener("change", () => {
      const file = spriteFileInput.files?.[0] || null;
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        updateSelectedSprite((sprite) => {
          sprite.src = String(reader.result || "");
          if (!sprite.label) {
            sprite.label = file.name.replace(/\.[^.]+$/, "");
          }
        });
        spriteFileInput.value = "";
      };
      reader.readAsDataURL(file);
    });
    spriteColumnsInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.columns = Math.max(1, Math.round(Number(spriteColumnsInput.value || 1)));
        sprite.frameCount = Math.min(Math.max(1, Number(sprite.frameCount || 1)), sprite.columns * Math.max(1, Number(sprite.rows || 1)));
      });
    });
    spriteRowsInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.rows = Math.max(1, Math.round(Number(spriteRowsInput.value || 1)));
        sprite.frameCount = Math.min(Math.max(1, Number(sprite.frameCount || 1)), Math.max(1, Number(sprite.columns || 1)) * sprite.rows);
      });
    });
    spriteFrameWidthInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.frameWidth = Math.max(0, Math.round(Number(spriteFrameWidthInput.value || 0)));
      });
    });
    spriteFrameHeightInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.frameHeight = Math.max(0, Math.round(Number(spriteFrameHeightInput.value || 0)));
      });
    });
    spriteWidthInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.width = Math.max(1, Math.round(Number(spriteWidthInput.value || 48)));
      });
    });
    spriteHeightInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.height = Math.max(1, Math.round(Number(spriteHeightInput.value || 48)));
      });
    });
    spriteXInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.x = clamp(Number(spriteXInput.value || 0), 0, 1);
      });
    });
    spriteYInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.y = clamp(Number(spriteYInput.value || 0), 0, 1);
      });
    });
    spriteFrameCountInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        const maxFrames = Math.max(1, Number(sprite.columns || 1) * Number(sprite.rows || 1));
        sprite.frameCount = clamp(Math.round(Number(spriteFrameCountInput.value || 1)), 1, maxFrames);
      });
    });
    spriteFpsInput.addEventListener("input", () => {
      updateSelectedSprite((sprite) => {
        sprite.fps = clamp(Number(spriteFpsInput.value || 12), 1, 60);
      });
    });
    spriteModeSelect.addEventListener("change", () => {
      updateSelectedSprite((sprite) => {
        sprite.mode = spriteModeSelect.value;
      });
    });

    saveButton.addEventListener("click", async () => {
      try {
        if (!state.saveAllowed) {
          alert("สิทธิ์บันทึกไฟล์ยังไม่เปิดสำหรับ session นี้");
          return;
        }
        normalizeBoard();
        const data = await saveBoardToServer();
        state.board = data.board;
        normalizeBoard();
        setDirty(false);
        refreshMeta();
        render();
      } catch (error) {
        console.error(error);
        const reason = String(error?.data?.message || error?.message || "UNKNOWN_ERROR");
        alert(`บันทึกไม่สำเร็จ: ${reason}`);
      }
    });
    downloadButton.addEventListener("click", downloadBoardJson);
    refreshJsonButton.addEventListener("click", refreshMeta);

    stepChooser?.addEventListener("click", (event) => {
      const button = event.target.closest("[data-step-chooser-index]");
      if (!button) return;
      event.preventDefault();
      const index = Number(button.dataset.stepChooserIndex || -1);
      if (!Number.isInteger(index) || index < 0) return;
      if (state.selectionTool) {
        setMultiSelection(buildSingleSelection("step", index));
      } else {
        setSelection(index, state.selectedReward);
      }
      closeStepChooser();
    });

    window.addEventListener("keydown", (event) => {
      if (isTypingTarget(event.target)) return;
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "a" && !event.repeat) {
        event.preventDefault();
        setMultiSelection(buildAllSelection());
        return;
      }
      if (event.key === "Escape" && !event.repeat) {
        closeStepChooser();
        clearMultiSelectionState();
        refreshMeta();
        render();
        return;
      }
      if ((event.key === "Delete" || event.key === "Backspace") && !event.repeat) {
        if (selectedRewardIndex() >= 0) {
          event.preventDefault();
          deleteSelectedReward();
          return;
        }
        if (selectedSpriteIndex() >= 0) {
          event.preventDefault();
          deleteSelectedSprite();
          return;
        }
        if (selectedStepIndex() >= 0) {
          event.preventDefault();
          deleteSelectedStep();
          return;
        }
      }
      if (event.code !== "Space" || event.repeat) return;
      state.spacePan = true;
      syncCanvasCursor();
    });
    window.addEventListener("keyup", (event) => {
      if (isTypingTarget(event.target)) return;
      if (event.code !== "Space") return;
      state.spacePan = false;
      syncCanvasCursor();
    });

    window.addEventListener("pointerdown", (event) => {
      if (!state.stepChooser.visible) return;
      if (stepChooser?.contains(event.target)) return;
      if (event.target === canvas) return;
      closeStepChooser();
    }, { passive: true });

    window.addEventListener("resize", resizeCanvas, { passive: true });

    function toolAnimationLoop() {
      if (state.board?.sprites?.length) {
        render();
      }
      window.requestAnimationFrame(toolAnimationLoop);
    }

    async function boot() {
      const data = await fetchToolJson("tool_bootstrap");
      state.board = data.board;
      state.saveAllowed = Boolean(data.saveAllowed);
      normalizeBoard();
      await loadBackground();
      state.camera.zoom = fitZoom();
      state.camera.x = state.board.image.width / 2;
      state.camera.y = state.board.image.height * 0.84;
      setDirty(false);
      refreshMeta();
      resizeCanvas();
      syncCanvasCursor();
      render();
    }

    boot().catch((error) => {
      console.error(error);
      alert("โหลด map tool ไม่สำเร็จ");
    });
    window.requestAnimationFrame(toolAnimationLoop);
  </script>
</body>
</html>
