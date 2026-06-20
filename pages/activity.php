<div data-view="activity">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header" data-i18n="heading.activity_archive">Activity Archive</h3>
        <div class="orbit-muted">Canonical timeline จาก tbl_raw_event ใช้ official Discord gateway keyword เท่านั้น พร้อม source/context จาก backfill ที่อนุมัติ</div>
      </div>
      <button class="ui button" data-refresh-view><i class="fa-solid fa-arrows-rotate"></i></button>
    </div>
    <div class="orbit-filter" id="activityFilter">
      <div class="ui input"><input type="search" name="q" placeholder="ค้น event, user, channel, target, metadata" data-i18n-placeholder="filter.search_activity"></div>
      <select class="ui fluid search dropdown" name="type" multiple>
        <option value="voice">voice</option>
        <option value="member">members</option>
        <option value="role">roles</option>
        <option value="channel">channels</option>
        <option value="guild">guild / system</option>
        <option value="audit">audit</option>
        <option value="invite">invites</option>
        <option value="message">messages</option>
      </select>
      <select class="ui fluid search dropdown" name="eventType" multiple>
        <option value="">ทุก event type</option>
      </select>
      <select class="ui dropdown" name="pageSize">
        <option value="50">50 rows</option>
        <option value="100" selected>100 rows</option>
        <option value="200">200 rows</option>
        <option value="300">300 rows</option>
      </select>
      <div class="orbit-muted" data-i18n="text.activity_filter_hint">ปล่อย filter ว่าง = แสดงทุก event ที่ไม่ใช่ message</div>
      <button class="ui button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
    </div>
    <div class="orbit-table-wrap">
      <table class="ui very basic table orbit-table" id="activityTimelineTable"></table>
    </div>
    <div class="orbit-section-body" id="activityTimelinePager"></div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-header">
      <h3 class="ui header">Official Event Mix</h3>
    </div>
    <div class="orbit-section-body">
      <table class="ui very basic table orbit-table" id="activityEventTable"></table>
    </div>
  </div>
</div>
