<div data-view="shop-member-bags">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Shop Member Bags</h3>
        <div class="orbit-muted">ดูกระเป๋าสมาชิกทั้งหมด ทั้งยอด wallet และไอเทมในระบบ shop พร้อม filter และคลิกสมาชิกเพื่อดู movement รายละเอียด</div>
      </div>
      <button class="ui button" type="button" data-refresh-view><i class="fa-solid fa-rotate"></i></button>
    </div>
    <div class="orbit-metric-strip" id="shopMemberBagsMetrics"></div>
    <div class="ui info message" style="margin-top:12px">
      ยอดคงเหลือในกระเป๋าจะแสดงครบตามสภาพปัจจุบัน ส่วนประวัติ item เข้า/ออก จะเริ่มนับแบบ authoritative หลังเปิดใช้ item ledger ใหม่นี้
    </div>
  </div>

  <div class="orbit-section">
    <div class="orbit-section-body orbit-section-body-tight">
      <div class="orbit-filter" id="shopMemberBagsFilter">
        <div class="ui input"><input type="search" name="q" placeholder="ค้นสมาชิกหรือชื่อไอเทม"></div>
        <select class="ui dropdown" name="unitCode">
          <option value="">ทุกหน่วยเงิน</option>
        </select>
        <select class="ui dropdown" name="itemType">
          <option value="">ทุกชนิดไอเทม</option>
        </select>
        <div class="ui input"><input type="search" name="itemCode" placeholder="item code / item name"></div>
        <label class="ui checkbox"><input type="checkbox" name="hideInactive" checked><span>ซ่อนคนที่ออกแล้ว</span></label>
        <label class="ui checkbox"><input type="checkbox" name="onlyWithWallet"><span>มี wallet</span></label>
        <label class="ui checkbox"><input type="checkbox" name="onlyWithInventory"><span>มีไอเทม</span></label>
        <select class="ui dropdown" name="pageSize">
          <option value="50" selected>50 rows</option>
          <option value="100">100 rows</option>
          <option value="200">200 rows</option>
        </select>
        <button class="ui button" type="button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
      </div>

      <div class="orbit-table-wrap">
        <table class="ui very basic table orbit-table" id="shopMemberBagsTable"></table>
      </div>
      <div class="orbit-section-body" id="shopMemberBagsPager"></div>
    </div>
  </div>
</div>
