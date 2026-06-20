<div data-view="logs">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Staff Logs</h3>
        <div class="orbit-muted">ทุก action ในระบบ, rejected action, sensitive read, export และ Discord write audit reason</div>
      </div>
      <button class="ui button" data-export="staff_logs"><i class="fa-solid fa-file-export"></i> Export</button>
    </div>
    <div class="orbit-filter" id="logsFilter">
      <div class="ui input"><input type="search" name="q" placeholder="ค้น staff/action/target/ip"></div>
      <select class="ui dropdown" name="status">
        <option value="">ทุก status</option>
        <option value="success">success</option>
        <option value="failed">failed</option>
        <option value="rejected">rejected</option>
        <option value="pending">pending</option>
      </select>
      <select class="ui dropdown" name="logType">
        <option value="all">Staff + Access + Audit</option>
        <option value="staff">Staff actions</option>
        <option value="access">Access logs</option>
        <option value="audit">Audit compare</option>
      </select>
      <select class="ui dropdown" name="limit">
        <option value="80">80 แถว</option>
        <option value="150">150 แถว</option>
        <option value="250">250 แถว</option>
      </select>
      <button class="ui button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
    </div>
    <div class="orbit-table-wrap">
      <table class="ui very basic selectable table orbit-table" id="logsStaffTable"></table>
    </div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-header">
      <h3 class="ui header">Access Logs</h3>
    </div>
    <div class="orbit-table-wrap">
      <table class="ui very basic table orbit-table" id="logsAccessTable"></table>
    </div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-header">
      <h3 class="ui header">Audit Compare</h3>
    </div>
    <div class="orbit-table-wrap">
      <table class="ui very basic table orbit-table" id="logsAuditCompareTable"></table>
    </div>
  </div>
</div>
