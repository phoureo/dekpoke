<div data-view="permission">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header" data-i18n="heading.permission">Permission</h3>
        <div class="orbit-muted" data-i18n="text.permission_subtitle">ตั้ง tier ว่าใครเข้าสู่ dashboard ได้ และแต่ละ tier เห็น/จัดการหน้าไหนได้บ้าง</div>
      </div>
      <div>
        <button class="ui button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
        <button class="ui button" id="permissionAddTierButton"><i class="fa-solid fa-plus"></i> Add Group</button>
        <button class="ui primary button" id="permissionSaveButton"><i class="fa-solid fa-floppy-disk"></i> Save Permission</button>
      </div>
    </div>
    <div class="orbit-section-body">
      <div class="ui warning message">
        <div class="header" data-i18n="text.permission_guard_header">หลังบ้านเช็คสิทธิ์จริง</div>
        <p data-i18n="text.permission_guard_body">เมนูเป็นแค่ทางเข้า แต่ page loader และ endpoint สำคัญจะตรวจ permission จาก session/admin user ทุกครั้ง</p>
        <p>1 user อยู่ได้ 1 group เพื่อกันสิทธิ์ทับกัน ถ้าเพิ่มเข้า group ใหม่ ระบบจะย้ายออกจาก group เดิมให้อัตโนมัติ</p>
      </div>
      <div id="permissionTierList" class="orbit-earn-rule-list"></div>
      <div id="permissionResult" class="orbit-json-summary"></div>
    </div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-header"><h3 class="ui header" data-i18n="heading.permission_users">Dashboard Users</h3></div>
    <div class="orbit-table-wrap">
      <table class="ui very basic table orbit-table" id="permissionUsersTable"></table>
    </div>
  </div>
</div>
