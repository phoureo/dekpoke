<div data-view="gacha-campaign">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header" data-i18n="heading.gacha_campaign_settings">ตั้งค่า Campaign กาชาปอง</h3>
        <div class="orbit-muted" data-i18n="text.gacha_campaign_subtitle">ควบคุมการแสดงผล campaign counter บนหน้าเกม โดยระบบยังนับค่าจริงไว้ตลอดแม้ซ่อน HUD</div>
      </div>
      <button class="ui button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="orbit-metric-strip" id="gachaCampaignMetrics"></div>
  </div>

  <div class="ui top attached tabular menu gacha-campaign-tabs">
    <a class="active item" data-gacha-campaign-tab="counter">Counter</a>
    <a class="item" data-gacha-campaign-tab="checkin">Daily Check-in</a>
    <a class="item" data-gacha-campaign-tab="banners">Banners</a>
  </div>

  <div class="orbit-section" data-gacha-campaign-panel="counter">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header" data-i18n="heading.gacha_campaign_counter_display">การแสดงผลตัวนับบนหน้าเกม</h3>
        <div class="orbit-muted" data-i18n="text.gacha_campaign_counter_display">สวิตช์นี้มีผลเฉพาะการแสดงผลด้านบนของหน้ากาชา ไม่กระทบการนับรอบหรือรายงานย้อนหลัง</div>
      </div>
    </div>
    <div class="orbit-section-body">
      <form class="ui form" id="gachaCampaignForm">
        <div class="orbit-form-grid">
          <div class="field">
            <label data-i18n="field.campaign_counter_visible">แสดงตัวนับ Campaign บนหน้ากาชา</label>
            <div class="ui toggle checkbox">
              <input type="checkbox" name="campaignCounterVisible">
              <label data-i18n="hint.campaign_counter_visible">เปิดให้ผู้เล่นเห็นกล่องตัวนับเลขด้านบน</label>
            </div>
          </div>
        </div>
        <button class="ui primary button" type="submit">
          <i class="fa-solid fa-floppy-disk"></i>
          <span data-i18n="action.save_settings">บันทึก Settings</span>
        </button>
      </form>
    </div>
  </div>

  <div class="orbit-section" data-gacha-campaign-panel="checkin" hidden>
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Daily Check-in</h3>
        <div class="orbit-muted">ตั้งค่าปฏิทินเช็คอินรายวันและโบนัสไมล์สโตนในกล่องกิจกรรมหน้ากาชา</div>
      </div>
    </div>
    <div class="orbit-section-body">
      <div class="ui form" id="gachaCheckinForm">
        <div class="orbit-form-grid">
          <div class="field">
            <label>เปิดกิจกรรม Check-in</label>
            <div class="ui toggle checkbox">
              <input type="checkbox" data-checkin-field="enabled">
              <label>แสดงปุ่มกิจกรรมและให้ผู้เล่นกดรับได้</label>
            </div>
          </div>
          <div class="field">
            <label>เดือน Campaign</label>
            <input type="month" data-checkin-field="campaignMonth">
          </div>
          <div class="field">
            <label>ชื่อกิจกรรม</label>
            <input data-checkin-field="title" placeholder="เช็คอินประจำวัน">
          </div>
          <div class="field">
            <label>คำอธิบายสั้น</label>
            <input data-checkin-field="subtitle" placeholder="รับของทุกวันและสะสมครบเพื่อรับโบนัส">
          </div>
        </div>

        <h4 class="ui header">รางวัลรายวัน</h4>
        <div class="orbit-muted">ใส่จำนวน Coin / Ticket / Free Spin ของแต่ละวันได้เลย ช่องว่าง = ไม่แจกหน่วยนั้น</div>
        <div class="orbit-compact-grid" id="gachaCheckinDailyGrid"></div>

        <h4 class="ui header">โบนัสไมล์สโตน</h4>
        <div class="orbit-muted">ผู้เล่นกดรับโบนัสซ้อนอีกชั้นเมื่อเช็คอินครบตามจำนวนวัน</div>
        <div class="orbit-compact-grid" id="gachaCheckinMilestoneGrid"></div>

        <button class="ui primary button" type="submit" form="gachaCampaignForm">
          <i class="fa-solid fa-floppy-disk"></i>
          <span>บันทึก Check-in</span>
        </button>
      </div>
    </div>
  </div>

  <div class="orbit-section" data-gacha-campaign-panel="banners" hidden>
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Campaign Banners</h3>
        <div class="orbit-muted">ตั้งค่าแบนเนอร์แนวตั้งหน้าเกม พร้อมรางวัล coin / gem / ticket / potion ต่อแบนเนอร์</div>
      </div>
      <button class="ui button" type="button" id="gachaCampaignAddBanner"><i class="fa-solid fa-plus"></i> Add banner</button>
    </div>
    <div class="orbit-section-body">
      <div class="ui form" id="gachaBannerForm">
        <div class="orbit-form-grid">
          <div class="field">
            <label>เปิด Campaign Banner</label>
            <div class="ui toggle checkbox">
              <input type="checkbox" data-banner-field="enabled">
              <label>แสดงปุ่มโฆษณา/แบนเนอร์บนหน้ากาชา</label>
            </div>
          </div>
          <div class="field">
            <label>หัวข้อหน้า Banner</label>
            <input data-banner-field="title" placeholder="Campaign Board">
          </div>
          <div class="field">
            <label>คำอธิบายหน้า Banner</label>
            <input data-banner-field="subtitle" placeholder="ดูข่าวกิจกรรมและรับของจากแบนเนอร์">
          </div>
        </div>
        <div id="gachaBannerCards" class="orbit-compact-grid"></div>
        <button class="ui primary button" type="submit" form="gachaCampaignForm">
          <i class="fa-solid fa-floppy-disk"></i>
          <span>บันทึก Banners</span>
        </button>
      </div>
    </div>
  </div>
</div>
