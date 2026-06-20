<div data-view="earn_settings">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Reward Engine · Earn Settings <span class="orbit-info-tip" data-tooltip="ตั้งค่ารางวัลอัตโนมัติสำหรับ Coin และ GachaTicket จากข้อความ, ห้องเสียง, เข้าเซิร์ฟ และเชิญเพื่อน โดยโครงสร้างนี้ถูกเตรียมไว้ให้ขยายไปสู่ quest และ game reward ได้ต่อ"><i class="fa-solid fa-circle-info"></i></span></h3>
        <div class="orbit-muted">ตั้ง rule การแจกอัตโนมัติแบบใช้ซ้ำได้ ระบบ worker จะตรวจซ้ำทุก 5 นาที, กันแจกซ้ำด้วย reward event และใช้เป็นฐานต่อยอด quest/game condition ได้</div>
      </div>
      <div>
        <button class="ui button" data-refresh-view><i class="fa-solid fa-arrows-rotate"></i></button>
        <button class="ui primary button" id="earnSettingsSaveButton"><i class="fa-solid fa-floppy-disk"></i> Save Earn Rules</button>
      </div>
    </div>
    <div class="orbit-section-body">
      <div class="orbit-filter-note">
        <i class="fa-solid fa-terminal"></i>
        รัน worker: <code>/Applications/XAMPP/xamppfiles/bin/php workers/earn_worker.php --loop</code>
      </div>
      <div class="ui info message">
        <div class="header">Reward Engine รองรับ trigger หลัก 4 แบบ และเพิ่มเงื่อนไขย่อยตามชนิด trigger ได้</div>
        <p>Text active สามารถกำหนดจำนวนข้อความ, จำนวนห้องไม่ซ้ำ, channel/category filter และช่วงเวลาได้ ส่วน trigger อื่นยังคงเก็บ condition เพิ่มเติมแบบปลอดภัยเพื่อรอต่อยอด logic เฉพาะเคส</p>
      </div>
      <div class="orbit-metric-strip" id="earnSettingsMetrics"></div>
      <div id="earnSettingsList" class="orbit-earn-rule-list"></div>
      <div id="earnSettingsResult" class="orbit-json-summary"></div>
    </div>
  </div>
</div>
