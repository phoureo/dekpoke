<div data-view="gacha-shop">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Shop Setting</h3>
        <div class="orbit-muted">ระบบร้านค้า, หน่วยกลาง, กระเป๋า และสินค้าแยกจากกาชา ตอนนี้เปิดเป็นโหมดตั้งค่าและแสดงผลก่อน ยังไม่ซื้อจริง</div>
      </div>
      <div>
        <a class="ui button" href="/discord/gacha/shop.php" target="_blank"><i class="fa-solid fa-up-right-from-square"></i> Open Shop</a>
        <button class="ui button" type="button" data-refresh-view data-tooltip="โหลดค่าล่าสุดจาก server อีกครั้ง"><i class="fa-solid fa-rotate"></i></button>
        <button class="ui primary button" type="button" id="shopSaveButton" data-tooltip="บันทึก unit, role shop, item shop และ template ทั้งหมด"><i class="fa-solid fa-floppy-disk"></i> Save Shop</button>
      </div>
    </div>
    <div class="orbit-metric-strip" id="shopMetrics"></div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-body orbit-section-body-tight">
      <div class="orbit-page-tabs" data-tabs="shopSettingTabs">
        <button class="ui small primary button active" type="button" data-tab-target="shopUnitTab">Unit Setting</button>
        <button class="ui small button" type="button" data-tab-target="shopRoleTab">Role Shop</button>
        <button class="ui small button" type="button" data-tab-target="shopItemTab">Item Shop</button>
        <button class="ui small button" type="button" data-tab-target="shopTemplateTab">Text Templates</button>
      </div>

      <section class="orbit-tab-panel active" data-tab-panel="shopUnitTab">
        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Shop Units</h3>
              <div class="orbit-muted">code เป็นค่าระบบถาวร ส่วนชื่อ/ชื่อย่อ/ไอคอน เปลี่ยนได้ทันทีและถูกใช้บนหน้าร้าน/เกม</div>
            </div>
          </div>
          <div class="orbit-section-body">
            <div class="shop-setting-list" id="shopBuyDefaultsPanel" style="margin-bottom:14px"></div>
            <div class="shop-setting-list" id="shopUnitList"></div>
          </div>
        </div>
      </section>

      <section class="orbit-tab-panel" data-tab-panel="shopRoleTab">
        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Role Shop</h3>
              <div class="orbit-muted">ขายทีละยศ หรือดึงทั้ง series เข้ามาทีเดียวแล้วค่อยไล่ตั้งราคาแต่ละยศภายหลังได้</div>
            </div>
            <div class="ui buttons">
              <button class="ui button" type="button" id="shopAddRoleProductButton"><i class="fa-solid fa-plus"></i> Add Role Product</button>
              <button class="ui button" type="button" id="shopAddRoleSeriesButton"><i class="fa-solid fa-layer-group"></i> Add Role Series</button>
            </div>
          </div>
          <div class="orbit-section-body">
            <div class="ui form" style="margin-bottom:14px">
              <div class="three fields">
                <div class="ten wide field">
                  <label>Role Series</label>
                  <select class="ui fluid search dropdown" id="shopRoleSeriesSeed"></select>
                </div>
                <div class="six wide field">
                  <label>&nbsp;</label>
                  <div class="orbit-muted">เลือก series แล้วกด Add Role Series เพื่อแตกสินค้าเป็นยศย่อยทั้งหมดในชุดนั้น</div>
                </div>
              </div>
            </div>
            <div class="shop-setting-list" id="shopRoleSeriesConfigList" style="margin-bottom:14px"></div>
            <div class="shop-product-toolbar" id="shopRoleProductToolbar"></div>
            <div class="shop-setting-list" id="shopRoleProductList"></div>
          </div>
        </div>
      </section>

      <section class="orbit-tab-panel" data-tab-panel="shopItemTab">
        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Item Shop</h3>
              <div class="orbit-muted">ตั้งกลุ่มก่อน แล้วค่อยเรียงสินค้าย่อยในกลุ่มเดียวกันให้ตรงกับ shelf หน้าร้านแบบเดียวกับ flow ของ Prize Setting</div>
            </div>
            <div class="ui buttons">
              <button class="ui button" type="button" id="shopAddItemGroupButton"><i class="fa-solid fa-layer-group"></i> Add Item Group</button>
              <button class="ui button" type="button" id="shopAddItemProductButton"><i class="fa-solid fa-plus"></i> Add Item Product</button>
            </div>
          </div>
          <div class="orbit-section-body">
            <div class="shop-setting-list" id="shopItemGroupList"></div>
          </div>
        </div>
      </section>

      <section class="orbit-tab-panel" data-tab-panel="shopTemplateTab">
        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Shop Text Templates</h3>
              <div class="orbit-muted">เทมเพลตข้อความรายละเอียดและเงื่อนไขสำหรับสินค้าร้านค้า</div>
            </div>
            <button class="ui button" type="button" id="shopAddTemplateButton"><i class="fa-solid fa-plus"></i> Add Template</button>
          </div>
          <div class="orbit-section-body">
            <div class="shop-setting-list" id="shopTemplateList"></div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>
