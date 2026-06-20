<div data-view="bag-transaction-report">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Bag Transaction Report</h3>
        <div class="orbit-muted">ภาพรวมความเคลื่อนไหวของกระเป๋า, wallet และ reward ทั้งระบบ แบบรวมทุกสมาชิกในหน้าเดียว</div>
      </div>
      <button class="ui button" type="button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="orbit-metric-strip" id="bagTransactionReportMetrics"></div>
    <div class="ui info message" id="bagTransactionReportHistoryNotice" style="margin-top:12px">
      current balance ให้ดูที่ Member Bags ส่วนหน้านี้เน้น movement feed รวมทั้งระบบ และ item history จะ authoritative ตั้งแต่เริ่มใช้ item ledger ใหม่
    </div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-body orbit-section-body-tight">
      <div class="orbit-filter" id="bagTransactionReportFilter">
        <div class="ui input"><input type="search" name="q" placeholder="ค้น member, item, unit, source, counterparty"></div>
        <div class="ui input"><input type="datetime-local" name="dateFrom"></div>
        <div class="ui input"><input type="datetime-local" name="dateTo"></div>
        <select class="ui dropdown" name="historyKind">
          <option value="">ทุกชนิด history</option>
          <option value="item_ledger">item ledger</option>
          <option value="wallet_ledger">wallet ledger</option>
          <option value="reward_event">reward event</option>
        </select>
        <select class="ui dropdown" name="direction">
          <option value="">ทุก movement</option>
          <option value="in">รับเข้า</option>
          <option value="out">ใช้/จ่ายออก</option>
        </select>
        <select class="ui dropdown" name="sourceType">
          <option value="">ทุก source</option>
        </select>
        <select class="ui dropdown" name="unitCode">
          <option value="">ทุกหน่วย</option>
        </select>
        <select class="ui dropdown" name="itemType">
          <option value="">ทุกชนิดไอเทม</option>
        </select>
        <div class="ui input"><input type="search" name="itemCode" placeholder="item code / item name"></div>
        <select class="ui dropdown" name="pageSize">
          <option value="50" selected>50 rows</option>
          <option value="100">100 rows</option>
          <option value="200">200 rows</option>
        </select>
        <button class="ui button" type="button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
      </div>

      <div class="orbit-table-wrap">
        <table class="ui very basic table orbit-table" id="bagTransactionReportTable"></table>
      </div>
      <div class="orbit-section-body" id="bagTransactionReportPager"></div>
    </div>
  </div>
</div>
