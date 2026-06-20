<div data-view="reward-report">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header" data-i18n="heading.reward_report">รายงานการแจก Rewards</h3>
        <div class="orbit-muted" data-i18n="text.reward_report_subtitle">ดูย้อนหลังว่า reward rule ไหนแจก coin หรือตั๋วให้ใคร จาก source อะไร และมี coin ledger ผูกไว้หรือไม่</div>
      </div>
      <button class="ui button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="ui info message">
      <div class="header" data-i18n="text.reward_report_storage_header">ตอนนี้ระบบเก็บอะไรไว้บ้าง</div>
      <p data-i18n="text.reward_report_storage_body">tbl_reward_event เก็บประวัติการแจกต่อครั้ง และ tbl_shop_wallet_ledger เก็บการเคลื่อนไหวของทุกหน่วยเงินในระบบใหม่แบบแยกตาม unit</p>
      <p data-i18n="text.reward_report_window_note">สำหรับ reward แบบข้อความ/เสียง report จะแสดงเป็นช่วงสะสมของ rule เช่น ช่วง 1-2 ชั่วโมง หรือ active 10-20 นาที พร้อมยอดสะสมตอนที่จ่ายจริง ไม่ใช่ clock hour จริงตามนาฬิกา เพราะระบบเก็บจาก daily summary</p>
    </div>
    <div class="orbit-metric-strip" id="rewardReportMetrics"></div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-body orbit-section-body-tight">
      <div class="orbit-filter" id="rewardReportFilter">
        <div class="ui input"><input type="search" name="q" placeholder="ค้น user, rule, source" data-i18n-placeholder="filter.search_reward_report"></div>
        <div class="ui input"><input type="datetime-local" name="dateFrom"></div>
        <div class="ui input"><input type="datetime-local" name="dateTo"></div>
        <select class="ui dropdown" name="ruleCode">
          <option value="" data-i18n="filter.all_rules">ทุก rule</option>
        </select>
        <select class="ui dropdown" name="triggerType">
          <option value="" data-i18n="filter.all_triggers">ทุก trigger</option>
        </select>
        <select class="ui dropdown" name="status">
          <option value="" data-i18n="filter.status_all">ทุกสถานะ</option>
          <option value="granted">granted</option>
          <option value="received">received</option>
          <option value="spent">spent</option>
        </select>
        <select class="ui dropdown" name="rewardKind">
          <option value="" data-i18n="filter.all_rewards">ทุก reward</option>
          <option value="coin">coin</option>
          <option value="ticket">ticket</option>
          <option value="freeSpin">free spin</option>
          <option value="mixed">mixed</option>
        </select>
        <select class="ui dropdown" name="movementType">
          <option value="" data-i18n="filter.reward_movement_all">รับและใช้ทั้งหมด</option>
          <option value="in" data-i18n="filter.reward_movement_in">ได้รับเท่านั้น</option>
          <option value="out" data-i18n="filter.reward_movement_out">ใช้ไปเท่านั้น</option>
        </select>
        <select class="ui dropdown" name="unitCode">
          <option value="" data-i18n="filter.all_units">ทุกหน่วย</option>
        </select>
        <select class="ui dropdown" name="pageSize">
          <option value="50" selected>50 rows</option>
          <option value="100">100 rows</option>
          <option value="200">200 rows</option>
        </select>
        <div class="orbit-muted">กดที่แถวเพื่อเปิด metadata และดูว่าเหรียญผูกกับ wallet ledger แถวไหน</div>
        <button class="ui button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
      </div>

      <div class="orbit-table-wrap">
        <table class="ui very basic table orbit-table" id="rewardReportTable"></table>
      </div>
      <div class="orbit-section-body" id="rewardReportPager"></div>
    </div>
  </div>
</div>
