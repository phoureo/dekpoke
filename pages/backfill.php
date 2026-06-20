<div data-view="backfill">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Backfill Center <span class="orbit-info-tip" data-tooltip="ใช้เติมข้อมูลจาก Discord API และ bot log ที่อนุมัติ เพื่อ rebuild canonical event, voice session และ earn summary ตาม cursor ปัจจุบัน"><i class="fa-solid fa-circle-info"></i></span></h3>
        <div class="orbit-muted">เติมข้อมูลจากล่าสุดย้อนหลัง แยกตามชนิดข้อมูล พร้อม cursor, recovery status และผลลัพธ์ล่าสุด</div>
      </div>
      <div>
        <button class="ui primary button" data-backfill-command="run" data-backfill-type="all" data-tooltip="รันในหน้าเว็บโดยตรง เหมาะกับงานสั้นหรือทดสอบ ถ้าไม่อยากเฝ้าหน้าจอหรือเสี่ยง refresh ให้ใช้ Queue Backfill แทน"><i class="fa-solid fa-layer-group"></i> Backfill All</button>
      </div>
    </div>
    <div class="orbit-metric-strip" id="backfillMetrics"></div>
    <div class="ui form orbit-section-body orbit-section-body-tight">
      <div class="three fields">
        <div class="field">
          <label>earn_worker.php Fillback Date</label>
          <input type="date" id="earnWorkerBackfillDate" value="<?= htmlspecialchars(date('Y-m-d')) ?>">
        </div>
        <div class="field">
          <label>&nbsp;</label>
          <button class="ui button" data-backfill-command="run" data-backfill-type="earn_worker"><i class="fa-solid fa-calendar-check"></i> Run earn_worker.php Date</button>
        </div>
      </div>
    </div>
    <div class="orbit-table-wrap">
      <table class="ui very basic table orbit-table" id="backfillTable"></table>
    </div>
    <div class="orbit-section-body">
      <div id="backfillResult" class="orbit-json-summary"></div>
    </div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Workers + Queued Recovery <span class="orbit-info-tip" data-tooltip="Queue จะให้ sync_worker ทำงานเบื้องหลัง เหมาะกับงานที่ไม่อยากรอหน้าเว็บค้าง"><i class="fa-solid fa-circle-info"></i></span></h3>
        <div class="orbit-muted">ลำดับใช้งานที่ง่ายสุด: เปิด <code>sync_worker.php --loop</code> ไว้ 1 หน้าต่าง แล้วค่อยกด <strong>Queue Sync</strong> หรือ <strong>Queue Backfill</strong> แค่ครั้งเดียว</div>
      </div>
      <div>
        <button class="ui button" data-enqueue-job="server_sync" data-tooltip="เติมสถานะปัจจุบันของ server, channel, role, member และ invite ก่อนงานอื่น"><i class="fa-solid fa-rotate"></i> Queue Sync</button>
        <button class="ui button" data-enqueue-job="backfill_all" data-tooltip="ส่งงาน backfill ทั้งหมดเข้า queue ให้ worker รันต่อ ไม่ควรกดซ้ำถ้า queue เดิมยังไม่จบ"><i class="fa-solid fa-clock-rotate-left"></i> Queue Backfill</button>
        <button class="ui basic button" data-queue-maintenance data-tooltip="ปิด running เก่าที่ไม่มี worker แล้วตัด queued ซ้ำให้เหลืองานล่าสุดอย่างละหนึ่ง"><i class="fa-solid fa-broom"></i> Clean Queue</button>
      </div>
    </div>
    <div class="orbit-section-body orbit-compact-grid">
      <table class="ui very basic table orbit-table" id="backfillWorkerTable"></table>
      <table class="ui very basic table orbit-table" id="backfillJobTable"></table>
    </div>
  </div>

  <div class="orbit-section orbit-danger-zone">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Danger Zone <span class="orbit-info-tip" data-tooltip="ล้าง message/activity/voice/summary/reward-derived ledger เพื่อเริ่ม rebuild ใหม่จาก bot log เท่านั้น ต้องพิมพ์คำยืนยันก่อน"><i class="fa-solid fa-circle-info"></i></span></h3>
        <div class="orbit-muted">ใช้เฉพาะตอนตั้งใจรีเซ็ตข้อมูล message/activity/voice/summary เพื่อ rebuild จาก bot log ใหม่ทั้งชุด</div>
      </div>
      <div>
        <button class="ui basic red button" data-backfill-command="reset_system" data-backfill-type="all" data-tooltip="ปุ่มนี้ล้างข้อมูล derived ทั้งชุด กดแล้วต้องยืนยันด้วยข้อความ RESET BOT LOG DATA"><i class="fa-solid fa-triangle-exclamation"></i> Reset Bot Log Data</button>
        <button class="ui basic red button" data-backfill-command="reset_rewards" data-backfill-type="all" data-tooltip="ล้าง reward events, wallet ledger, balance และ inventory ทุก user ต้องยืนยัน RESET REWARDS"><i class="fa-solid fa-sack-xmark"></i> Reset Rewards / Wallets</button>
        <button class="ui basic red button" data-backfill-command="reset_gachapon" data-backfill-type="all" data-tooltip="ล้างประวัติกาชา pending/history/role grant/counter ledger ต้องยืนยัน RESET GACHAPON"><i class="fa-solid fa-bomb"></i> Reset Gachapon</button>
      </div>
    </div>
    <div class="orbit-section-body">
      <div class="orbit-danger-copy">
        ปุ่มนี้จะล้างข้อมูลที่ derive จาก activity ทั้งหมด แล้วค่อยให้เรา backfill/build ใหม่อีกครั้งจาก source log โดยต้องพิมพ์คำยืนยันก่อนทุกครั้ง
      </div>
    </div>
  </div>
</div>
