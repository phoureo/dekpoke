<div data-view="messages">
  <div class="orbit-section">
    <div class="orbit-section-header">
      <div>
        <h3 class="ui header">Message Archive</h3>
        <div class="orbit-muted">archive หน้าเดียว ใช้ filter หนักสำหรับ message, deleted, edited, attachments, reply, reaction และ poll</div>
      </div>
      <div>
        <button class="ui button" data-export="messages"><i class="fa-solid fa-file-export"></i> Export</button>
        <button class="ui primary button" data-run-backfill="message_archive"><i class="fa-solid fa-clock-rotate-left"></i> Backfill</button>
      </div>
    </div>
    <div class="orbit-filter" id="messagesFilter">
      <div class="ui input"><input type="search" name="q" placeholder="ค้นข้อความ / message id / url / user id"></div>
      <select class="ui dropdown" name="channelId"><option value="">ทุกห้อง</option></select>
      <select class="ui dropdown" name="authorKind">
        <option value="human" selected>เฉพาะคน</option>
        <option value="bot">เฉพาะบอท</option>
        <option value="all">คน + บอท</option>
      </select>
      <div class="ui input"><input type="text" name="userId" placeholder="user id"></div>
      <div class="ui input"><input type="date" name="dateFrom"></div>
      <div class="ui input"><input type="date" name="dateTo"></div>
      <div class="ui checkbox orbit-filter-checkbox"><input type="checkbox" name="deleted"><label>Deleted</label></div>
      <div class="ui checkbox orbit-filter-checkbox"><input type="checkbox" name="edited"><label>Edited</label></div>
      <div class="ui checkbox orbit-filter-checkbox"><input type="checkbox" name="attachment"><label>Files</label></div>
      <div class="ui checkbox orbit-filter-checkbox"><input type="checkbox" name="link"><label>Links</label></div>
      <div class="ui checkbox orbit-filter-checkbox"><input type="checkbox" name="reply"><label>Replies</label></div>
      <div class="ui checkbox orbit-filter-checkbox"><input type="checkbox" name="reaction"><label>Reactions</label></div>
      <select class="ui dropdown" name="pageSize">
        <option value="50">50 แถว</option>
        <option value="100">100 แถว</option>
      </select>
      <button class="ui button" data-filter-apply><i class="fa-solid fa-filter"></i></button>
    </div>
    <div id="messagesNotice"></div>
    <div class="orbit-table-wrap">
      <table class="ui very basic selectable table orbit-table" id="messagesTable"></table>
    </div>
    <div class="orbit-section-body" id="messagesPager"></div>
  </div>
</div>
