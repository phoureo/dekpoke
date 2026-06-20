<div data-view="roles">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">ยศ</h3>
        <div class="orbit-muted">จัดการยศในเซิร์ฟเวอร์, จับกลุ่ม Series, ตั้งเธียร์ S/A/B/C และกำหนดคำอธิบายสิทธิ์ยศชุดเดียวให้ระบบรางวัลใช้งานร่วมกัน</div>
      </div>
      <div>
        <button class="ui button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
      </div>
    </div>
    <div class="orbit-section-body orbit-section-body-tight">
      <div class="orbit-page-tabs" data-tabs="rolesPageTabs">
        <button class="ui small primary button active" data-tab-target="rolesServerTab">ยศในเซิร์ฟเวอร์</button>
        <button class="ui small button" data-tab-target="rolesSeriesTab">Series ยศ</button>
        <button class="ui small button" data-tab-target="rolesPermissionTab">คำอธิบายสิทธิ์ยศ</button>
      </div>

      <section class="orbit-tab-panel active" data-tab-panel="rolesServerTab">
        <div class="orbit-filter" id="rolesFilter">
          <div class="ui input"><input type="search" name="q" placeholder="ค้น role name / role id"></div>
          <select class="ui dropdown" name="managed">
            <option value="all">Managed + Manual</option>
            <option value="0">Manual only</option>
            <option value="1">Managed only</option>
          </select>
          <select class="ui dropdown" name="permission">
            <option value="">ทุก permission</option>
          </select>
          <button class="ui button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
        </div>
        <div class="orbit-metric-strip" id="rolesMetrics"></div>
        <div class="orbit-table-wrap">
          <table class="ui very basic selectable table orbit-table" id="rolesTable"></table>
        </div>
      </section>

      <section class="orbit-tab-panel" data-tab-panel="rolesSeriesTab">
        <div class="orbit-filter-note">
          <i class="fa-solid fa-circle-info"></i>
          เอาไว้จับกลุ่มยศตกแต่งให้เป็นซีรีส์เดียวกัน เพื่อใช้จัดกลุ่มตอนโชว์หน้ารางวัลและระบบขาย
        </div>
        <div class="orbit-section-actions">
          <button class="ui button" id="roleSeriesAddButton"><i class="fa-solid fa-plus"></i> เพิ่ม Series</button>
          <button class="ui primary button" id="roleSeriesSaveButton"><i class="fa-solid fa-floppy-disk"></i> Save Series</button>
        </div>
        <div id="roleSeriesList" class="orbit-earn-rule-list"></div>
      </section>

      <section class="orbit-tab-panel" data-tab-panel="rolesPermissionTab">
        <div class="orbit-filter-note">
          <i class="fa-solid fa-circle-info"></i>
          ใช้เฉพาะรางวัลยศ เพื่อโชว์ชื่อสิทธิ์, badge คำขายสั้นๆ และคำอธิบายภาษาไทยในหน้ารางวัล/ร้านค้า
        </div>
        <div class="orbit-metric-strip" id="rolePermissionDescriptionsMetrics"></div>
        <div class="orbit-filter" id="rolePermissionDescriptionsFilter">
          <div class="ui input"><input type="search" name="q" placeholder="ค้น code, badge, ชื่อสิทธิ์ หรือคำอธิบาย"></div>
          <button class="ui button" id="rolePermissionDescriptionsFilterApplyButton"><i class="fa-solid fa-filter"></i></button>
          <button class="ui button" id="rolePermissionDescriptionsResetButton"><i class="fa-solid fa-rotate-left"></i> คืนค่าเริ่มต้น</button>
          <button class="ui primary button" id="rolePermissionDescriptionsSaveButton"><i class="fa-solid fa-floppy-disk"></i> Save Permission Text</button>
        </div>
        <div id="rolePermissionDescriptionsList" class="orbit-earn-rule-list"></div>
      </section>
    </div>
  </div>
</div>
