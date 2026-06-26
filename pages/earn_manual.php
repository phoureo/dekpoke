<div data-view="earn_manual">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header" data-i18n="heading.earn_manual">Manual Earn Grant</h3>
        <div class="orbit-muted" data-i18n="text.earn_manual_subtitle">แจกหน่วยเงินให้ผู้ใช้แบบแมนนวล โดยบันทึก reward event, wallet ledger และ staff log ครบทุกครั้ง</div>
      </div>
      <button class="ui button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="orbit-section-body">
      <div class="ui warning message">
        <div class="header" data-i18n="text.earn_manual_audit_header">ตรวจสอบย้อนหลังได้</div>
        <p data-i18n="text.earn_manual_audit_body">ทุกการแจกจะเข้า Reward Report เป็น source Manual grant และมี balance ก่อน/หลังอยู่ใน wallet ledger</p>
      </div>
      <div class="orbit-metric-strip" id="earnManualMetrics"></div>
      <div class="ui form orbit-form-card" id="earnManualForm">
        <div class="two fields">
          <div class="field">
            <label>Target</label>
            <div class="ui fluid three item menu orbit-toggle-menu" id="earnManualTargetType">
              <a class="item active" data-earn-target-type="user">User</a>
              <a class="item" data-earn-target-type="role">Role</a>
              <a class="item" data-earn-target-type="server">Server</a>
            </div>
          </div>
          <div class="field">
            <label>Reward</label>
            <select class="ui dropdown" name="rewardType" id="earnManualRewardType"></select>
          </div>
        </div>
        <div class="three fields">
          <div class="field" id="earnManualUserLookupField">
            <label>User Search</label>
            <div class="ui action input">
              <input name="q" placeholder="Discord userId หรือชื่อ">
              <button class="ui button" type="button" id="earnManualSearchButton"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
          </div>
          <div class="field" id="earnManualRoleLookupField" style="display:none">
            <label>Role</label>
            <select class="ui search dropdown" name="targetRoleId" id="earnManualRoleSelect"></select>
          </div>
          <div class="field">
            <label>Amount</label>
            <input type="number" min="1" step="1" name="amount" id="earnManualAmount" value="1">
          </div>
          <div class="field">
            <label>Reason <span class="orbit-required">*</span></label>
            <input name="reason" required placeholder="ต้องระบุเหตุผล เช่น campaign / ชดเชย / แก้ยอด">
          </div>
        </div>
        <div class="orbit-picker-stack">
          <div>
            <div class="orbit-picker-label">Selected Target</div>
            <div id="earnManualTargetSummary" class="orbit-picker-summary"></div>
          </div>
          <div id="earnManualSearchWrap">
            <div class="orbit-picker-label">Search Results</div>
            <div id="earnManualUserResults" class="orbit-picker-results"></div>
          </div>
        </div>
        <button class="ui primary button" id="earnManualGrantButton" type="button"><i class="fa-solid fa-hand-holding-dollar"></i> Grant</button>
      </div>
      <div id="earnManualResult" class="orbit-json-summary"></div>
    </div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-header">
      <h3 class="ui header" data-i18n="heading.earn_manual_recent">Recent Manual Grants</h3>
    </div>
    <div class="orbit-table-wrap">
      <table class="ui very basic table orbit-table" id="earnManualRecentTable"></table>
    </div>
  </div>
</div>
