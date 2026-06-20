<div data-view="admin">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">System Control</h3>
        <div class="orbit-muted">สถานะ worker, job queue, raw events, ingest errors และ admin users</div>
      </div>
      <div>
        <button class="ui button" data-enqueue-job="server_sync"><i class="fa-solid fa-rotate"></i> Queue Sync</button>
        <button class="ui basic button" data-queue-maintenance><i class="fa-solid fa-broom"></i> Clean Queue</button>
        <button class="ui button" data-page-jump="backfill"><i class="fa-solid fa-clock-rotate-left"></i> Backfill</button>
        <button class="ui button" data-page-jump="logs"><i class="fa-solid fa-clipboard-list"></i> Logs</button>
      </div>
    </div>
    <div class="orbit-metric-strip" id="adminMetrics"></div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-header">
      <h3 class="ui header">Workers + Jobs</h3>
    </div>
    <div class="orbit-section-body orbit-compact-grid">
      <table class="ui very basic table orbit-table" id="adminWorkerTable"></table>
      <table class="ui very basic table orbit-table" id="adminJobTable"></table>
    </div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-header">
      <h3 class="ui header">Raw Events + Errors</h3>
    </div>
    <div class="orbit-section-body orbit-compact-grid">
      <table class="ui very basic table orbit-table" id="adminRawEventTable"></table>
      <table class="ui very basic table orbit-table" id="adminErrorTable"></table>
    </div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-header">
      <h3 class="ui header">Admin Users + Rate Limits</h3>
    </div>
    <div class="orbit-section-body orbit-compact-grid">
      <table class="ui very basic table orbit-table" id="adminDashboardUserTable"></table>
      <table class="ui very basic table orbit-table" id="adminRateLimitTable"></table>
    </div>
  </div>
</div>
