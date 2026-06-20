<div data-view="gacha-prize">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Gachapon Prize Settings</h3>
        <div class="orbit-muted">ตั้งค่าเครดิตที่ใช้หมุน, เรทลูกกาชา, รายการรางวัล, วันสิ้นสุดแบบ pick date และรางวัลยศของเกมกาชาปอง</div>
      </div>
      <div>
        <a class="ui button" href="/discord/gacha/" target="_blank"><i class="fa-solid fa-up-right-from-square"></i> Open Game</a>
        <button class="ui button" data-refresh-view data-tooltip="โหลดค่าล่าสุดจาก server อีกครั้ง"><i class="fa-solid fa-rotate"></i></button>
        <button class="ui primary button" id="gachaSaveButton" data-tooltip="บันทึกเครดิต, prize item, prize role, template และเงื่อนไขทั้งหมด"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button>
      </div>
    </div>
    <div class="orbit-metric-strip" id="gachaMetrics"></div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-body orbit-section-body-tight">
      <div class="orbit-page-tabs" data-tabs="gachaPrizePageTabs">
        <button class="ui small primary button active" data-tab-target="gachaPrizeCatalogTab">รายการรางวัล</button>
        <button class="ui small button" data-tab-target="gachaPrizeEconomyTab">เครดิตเกม / เงื่อนไข</button>
        <button class="ui small button" data-tab-target="gachaPrizeTemplatesTab">Prize Templates</button>
      </div>

      <section class="orbit-tab-panel active" data-tab-panel="gachaPrizeCatalogTab">
        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Prize Items</h3>
              <div class="orbit-muted">แสดงเฉพาะของรางวัลแบบ item ที่ขึ้นบนชั้นและหน้า prizes.php กดแถวเพื่อขยายแก้ไข</div>
            </div>
          </div>
          <div class="orbit-section-body">
            <div class="gacha-prize-list" id="gachaItemPrizeList"></div>
          </div>
        </div>

        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Prize Roles</h3>
              <div class="orbit-muted">จัดการรางวัลยศเป็น Role Config กลาง แล้วเลือกหลาย role ย่อยเข้ามาใช้ config เดียวกันได้</div>
            </div>
          </div>
          <div class="orbit-section-body">
            <div class="gacha-prize-list" id="gachaRolePrizeList"></div>
          </div>
        </div>
      </section>

      <section class="orbit-tab-panel" data-tab-panel="gachaPrizeEconomyTab">
        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Tier Rates <span class="orbit-info-tip" data-tooltip="Internal rate ใช้สุ่มจริง ส่วน Public rate ใช้แสดงให้ผู้เล่นเห็นบนหน้ารางวัล"><i class="fa-solid fa-circle-info"></i></span></h3>
              <div class="orbit-muted">Internal rate ใช้สุ่มจริง, Public rate ใช้แสดงหน้ารางวัล</div>
            </div>
          </div>
          <div class="orbit-section-body">
            <div class="gacha-tier-grid" id="gachaTierGrid"></div>
          </div>
        </div>

        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Quick Rate Map <span class="orbit-info-tip" data-tooltip="Edit real draw percentages in one place. Tier percentages are the first draw step, then each prize row below shares 100% inside that tier."><i class="fa-solid fa-circle-info"></i></span></h3>
              <div class="orbit-muted">Tier chance on top, then every item and role inside that tier with quick percentage inputs.</div>
            </div>
          </div>
          <div class="orbit-section-body">
            <div id="gachaRateOverview"></div>
          </div>
        </div>

        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Game Credit <span class="orbit-info-tip" data-tooltip="กำหนดเครดิตเริ่มต้นและค่าใช้จ่ายต่อการ spin ฝั่ง server จะยึดค่านี้เป็นหลัก"><i class="fa-solid fa-circle-info"></i></span></h3>
              <div class="orbit-muted">Server จะใช้ค่านี้เป็นต้นทางจริงตอน start spin ไม่เชื่อ cost จาก browser</div>
            </div>
            <div class="ui toggle checkbox" id="gachaEnabledToggle">
              <input type="checkbox" name="enabled">
              <label><span data-i18n="form.gacha_enabled">เปิดระบบกาชา</span> <span class="orbit-info-tip" data-tooltip="ปิดแล้วหน้าเกมจะไม่เริ่ม flow spin จริง แม้ผู้ใช้แตะปุ่ม"><i class="fa-solid fa-circle-info"></i></span></label>
            </div>
          </div>
          <div class="orbit-section-body">
            <form class="ui form" id="gachaSettingsForm">
              <div class="four fields">
                <div class="field">
                  <label><span data-i18n="field.ticket_starting">Ticket เริ่มต้น</span> <span class="orbit-info-tip" data-tooltip="จำนวน ticket mock/default ที่ให้ profile ใหม่สำหรับทดสอบระบบกาชา"><i class="fa-solid fa-circle-info"></i></span></label>
                  <input type="number" min="0" name="startingTicket">
                </div>
                <div class="field">
                  <label><span data-i18n="field.coin_starting">Coin เริ่มต้น</span> <span class="orbit-info-tip" data-tooltip="จำนวน coin mock/default ที่ให้ profile ใหม่สำหรับทดสอบระบบกาชา"><i class="fa-solid fa-circle-info"></i></span></label>
                  <input type="number" min="0" name="startingCoin">
                </div>
                <div class="field">
                  <label><span data-i18n="field.ticket_cost">ปุ่ม Ticket ใช้กี่เครดิต / รอบ</span> <span class="orbit-info-tip" data-tooltip="จำนวน ticket ที่หักเมื่อ server อนุญาตให้เริ่ม spin จริง"><i class="fa-solid fa-circle-info"></i></span></label>
                  <input type="number" min="1" name="ticketCost">
                </div>
                <div class="field">
                  <label><span data-i18n="field.coin_cost">ปุ่ม Coin ใช้กี่เครดิต / รอบ</span> <span class="orbit-info-tip" data-tooltip="จำนวน coin ที่หักเมื่อ server อนุญาตให้เริ่ม spin จริง"><i class="fa-solid fa-circle-info"></i></span></label>
                  <input type="number" min="1" name="coinCost">
                </div>
              </div>
              <div class="three fields">
                <div class="field">
                  <label><span data-i18n="field.default_button">Default Button</span> <span class="orbit-info-tip" data-tooltip="ปุ่ม spin หลักที่หน้าเกมเลือกใช้เป็นค่าเริ่มต้น"><i class="fa-solid fa-circle-info"></i></span></label>
                  <select class="ui dropdown" name="defaultButtonId">
                    <option value="1">btn_1 Coin</option>
                    <option value="2">btn_2 Ticket</option>
                  </select>
                </div>
                <div class="field">
                  <label><span data-i18n="field.ticket_label">ชื่อปุ่ม Ticket</span> <span class="orbit-info-tip" data-tooltip="ข้อความภายในระบบสำหรับ btn_2 ยังไม่จำเป็นต้องตรงกับภาพปุ่ม"><i class="fa-solid fa-circle-info"></i></span></label>
                  <input name="ticketLabel" maxlength="80">
                </div>
                <div class="field">
                  <label><span data-i18n="field.coin_label">ชื่อปุ่ม Coin</span> <span class="orbit-info-tip" data-tooltip="ข้อความภายในระบบสำหรับ btn_1 ยังไม่จำเป็นต้องตรงกับภาพปุ่ม"><i class="fa-solid fa-circle-info"></i></span></label>
                  <input name="coinLabel" maxlength="80">
                </div>
              </div>
              <div class="inline fields">
                <label class="ui checkbox">
                  <input type="checkbox" name="ticketEnabled">
                  <span>เปิดปุ่ม Ticket <span class="orbit-info-tip" data-tooltip="เปิดให้ spin ด้วย ticket ผ่าน btn_2 ได้ ถ้าปิดปุ่มนี้จะไม่อนุญาต flow จริง"><i class="fa-solid fa-circle-info"></i></span></span>
                </label>
                <label class="ui checkbox">
                  <input type="checkbox" name="coinEnabled">
                  <span>เปิดปุ่ม Coin <span class="orbit-info-tip" data-tooltip="เปิดให้ spin ด้วย coin ผ่าน btn_1 ได้ ถ้าปิดปุ่มนี้จะไม่อนุญาต flow จริง"><i class="fa-solid fa-circle-info"></i></span></span>
                </label>
              </div>
            </form>
          </div>
        </div>

        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Condition Timeline <span class="orbit-info-tip" data-tooltip="Preview เงื่อนไขพิเศษ เช่น guarantee/pity และช่วงเวลาโควต้าที่มีผลกับการออกกาชา"><i class="fa-solid fa-circle-info"></i></span></h3>
              <div class="orbit-muted">Preview เงื่อนไขสำคัญและเรทที่ active อยู่ตอนนี้</div>
            </div>
          </div>
          <div class="orbit-section-body">
            <form class="ui form" id="gachaConditionForm">
              <div class="three fields">
                <div class="field"><label><span data-i18n="field.guarantee_every">Guarantee ทุกกี่ตา (0 = ปิด)</span> <span class="orbit-info-tip" data-tooltip="บังคับให้ออก tier ที่กำหนดเมื่อครบจำนวนตา ถ้าไม่ต้องการใช้ให้ใส่ 0"><i class="fa-solid fa-circle-info"></i></span></label><input type="number" min="0" name="pityEvery"></div>
                <div class="field"><label><span data-i18n="field.guarantee_tier">Guarantee Tier</span> <span class="orbit-info-tip" data-tooltip="tier ที่จะถูกใช้เมื่อเข้าเงื่อนไข guarantee"><i class="fa-solid fa-circle-info"></i></span></label><select class="ui dropdown" name="pityTierId"></select></div>
                <div class="field"><label><span data-i18n="field.quota_window_days">Quota Window Days</span> <span class="orbit-info-tip" data-tooltip="จำนวนวันที่ใช้คำนวณ quota/เงื่อนไขจำกัดรอบการออก"><i class="fa-solid fa-circle-info"></i></span></label><input type="number" min="1" name="quotaWindowDays"></div>
              </div>
            </form>
            <div class="gacha-timeline" id="gachaTimeline"></div>
          </div>
        </div>
      </section>

      <section class="orbit-tab-panel" data-tab-panel="gachaPrizeTemplatesTab">
        <div class="orbit-section">
          <div class="orbit-section-header">
            <div>
              <h3 class="ui header">Prize Templates</h3>
              <div class="orbit-muted">เทมเพลตใช้ซ้ำสำหรับรายละเอียด/เงื่อนไข และรูปไอคอนกลางของไอเทม/ยศที่ไม่ได้ตั้งรูปเฉพาะ</div>
            </div>
            <button class="ui button" id="gachaAddTemplateButton"><i class="fa-solid fa-plus"></i> Add Template</button>
          </div>
          <div class="orbit-section-body">
            <div class="ui small form gacha-prize-fieldset">
              <div class="gacha-prize-fieldset-title">Default Prize Icons</div>
              <div class="two fields">
                <div class="field">
                  <label>Default Role Icon</label>
                  <input data-gacha-default-icon-field="defaultRoleIcon" placeholder="uploads/prizes/...">
                  <label class="ui tiny button" style="margin-top:8px"><i class="fa-solid fa-upload"></i> Upload role icon<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-gacha-default-icon-upload="defaultRoleIcon" hidden></label>
                </div>
                <div class="field">
                  <label>Default Item Icon</label>
                  <input data-gacha-default-icon-field="defaultItemIcon" placeholder="uploads/prizes/...">
                  <label class="ui tiny button" style="margin-top:8px"><i class="fa-solid fa-upload"></i> Upload item icon<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-gacha-default-icon-upload="defaultItemIcon" hidden></label>
                </div>
              </div>
            </div>
            <div class="gacha-prize-template-list" id="gachaPrizeTemplateList"></div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>
