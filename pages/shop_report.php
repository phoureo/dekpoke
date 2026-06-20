<div data-view="shop-report">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Shop Report</h3>
        <div class="orbit-muted">รายงานการซื้อสินค้าในร้านค้า แยกคนซื้อ ช่องทางจ่าย ราคา และปลายทางของของขวัญ</div>
      </div>
      <button class="ui button" type="button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="orbit-metric-strip" id="shopReportMetrics"></div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-body orbit-section-body-tight">
      <div class="orbit-filter" id="shopReportFilter">
        <div class="ui input"><input type="search" name="q" placeholder="ค้น user, product, source, role"></div>
        <div class="ui input"><input type="datetime-local" name="dateFrom"></div>
        <div class="ui input"><input type="datetime-local" name="dateTo"></div>
        <select class="ui dropdown" name="sourceType">
          <option value="">ทุกประเภทการซื้อ</option>
          <option value="shop_role_badge_purchase">ซื้อเอง</option>
          <option value="shop_role_badge_gift">ส่งของขวัญ</option>
        </select>
        <select class="ui dropdown" name="movementType">
          <option value="">ทุก movement</option>
          <option value="out">จ่ายออก</option>
          <option value="in">รับเข้า</option>
        </select>
        <select class="ui dropdown" name="unitCode">
          <option value="">ทุกหน่วย</option>
        </select>
        <select class="ui dropdown" name="pageSize">
          <option value="50" selected>50 rows</option>
          <option value="100">100 rows</option>
          <option value="200">200 rows</option>
        </select>
        <button class="ui button" type="button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
      </div>

      <div class="orbit-table-wrap">
        <table class="ui very basic table orbit-table" id="shopReportTable"></table>
      </div>
      <div class="orbit-section-body" id="shopReportPager"></div>
    </div>
  </div>
</div>
