<div data-view="gacha-report">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header" data-i18n="heading.gacha_report">รายงานการหมุนกาชาปอง</h3>
        <div class="orbit-muted" data-i18n="text.gacha_report_subtitle">ดูย้อนหลังแต่ละรอบที่ server ล็อกผลแล้ว พร้อมสถานะ, ค่าใช้จ่าย, tier, รางวัล และจังหวะของแต่ละ phase</div>
      </div>
      <button class="ui button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="orbit-metric-strip" id="gachaReportMetrics"></div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-body orbit-section-body-tight">
      <div class="orbit-filter" id="gachaReportFilter">
        <div class="ui input"><input type="search" name="q" placeholder="ค้น user, prize, draw id" data-i18n-placeholder="filter.search_gacha_report"></div>
        <div class="ui input"><input type="search" name="drawId" placeholder="draw id"></div>
        <div class="ui input"><input type="datetime-local" name="dateFrom"></div>
        <div class="ui input"><input type="datetime-local" name="dateTo"></div>
        <select class="ui dropdown" name="status">
          <option value="" data-i18n="filter.status_all">ทุกสถานะ</option>
          <option value="started">started</option>
          <option value="revealed">revealed</option>
          <option value="ball_seen">ball_seen</option>
          <option value="resolved">resolved</option>
          <option value="completed">completed</option>
          <option value="refunded">refunded</option>
        </select>
        <select class="ui dropdown" name="currency">
          <option value="" data-i18n="filter.all_currencies">ทุกสกุลเงิน</option>
          <option value="ticket">ticket</option>
          <option value="coin">coin</option>
        </select>
        <select class="ui dropdown" name="buttonId">
          <option value="" data-i18n="filter.all_buttons">ทุกปุ่ม</option>
        </select>
        <select class="ui dropdown" name="tierId">
          <option value="" data-i18n="filter.all_tiers">ทุก tier</option>
        </select>
        <select class="ui dropdown" name="prizeType">
          <option value="" data-i18n="filter.all_prizes">ทุกรางวัล</option>
          <option value="item">item</option>
          <option value="role">role</option>
        </select>
        <select class="ui dropdown" name="durationKind">
          <option value="" data-i18n="filter.all_reward_age">ทุกอายุรางวัล</option>
          <option value="temporary_role" data-i18n="filter.temporary_roles">ยศไม่ถาวร</option>
          <option value="permanent" data-i18n="filter.permanent">ถาวร</option>
        </select>
        <select class="ui dropdown" name="pageSize">
          <option value="50" selected>50 rows</option>
          <option value="100">100 rows</option>
          <option value="200">200 rows</option>
        </select>
        <div class="orbit-muted">กดที่แถวเพื่อเปิด snapshot รายละเอียดของรอบนั้น</div>
        <button class="ui button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
      </div>

      <div class="orbit-table-wrap">
        <table class="ui very basic table orbit-table" id="gachaReportTable"></table>
      </div>
      <div class="orbit-section-body" id="gachaReportPager"></div>
    </div>
  </div>
</div>
