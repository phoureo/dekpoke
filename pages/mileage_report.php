<div data-view="mileage-report">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Mileage Report</h3>
        <div class="orbit-muted">สรุปความคืบหน้า mileage ว่าใครเดินไปกี่ก้าว อยู่บอร์ดไหน เคลมอะไรแล้ว และยังมี reward ค้างเคลมอยู่หรือไม่</div>
      </div>
      <button class="ui button" type="button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="orbit-metric-strip" id="mileageReportMetrics"></div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-body orbit-section-body-tight">
      <div class="orbit-filter" id="mileageReportFilter">
        <div class="ui input"><input type="search" name="q" placeholder="ค้น user หรือ board"></div>
        <select class="ui dropdown" name="boardCode">
          <option value="">ทุก board</option>
        </select>
        <select class="ui dropdown" name="status">
          <option value="">ทุกสถานะ</option>
        </select>
        <div class="ui input"><input type="datetime-local" name="dateFrom"></div>
        <div class="ui input"><input type="datetime-local" name="dateTo"></div>
        <select class="ui dropdown" name="pageSize">
          <option value="50" selected>50 rows</option>
          <option value="100">100 rows</option>
          <option value="200">200 rows</option>
        </select>
        <button class="ui button" type="button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
      </div>

      <div class="orbit-table-wrap">
        <table class="ui very basic table orbit-table" id="mileageReportTable"></table>
      </div>
      <div class="orbit-section-body" id="mileageReportPager"></div>
    </div>
  </div>
</div>
