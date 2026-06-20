(function ($) {
  const App = {
    baseUrl: '',
    csrf: '',
    page: 'activity',
    state: {},
    sortState: {},
    tableCache: {},
    livePollTimer: null,
    livePollInFlight: false,
    livePollToken: '',
    durationTimer: null,
    pageLiveVersions: {},
    viewerToken: '',
    i18n: {
      th: {
        'action.add_item_prize': 'เพิ่ม Prize Item',
        'action.add_role_prize': 'เพิ่ม Prize Role',
        'action.backfill': 'Backfill',
        'action.backfill_all': 'Backfill ทั้งหมด',
        'action.cancel': 'ยกเลิก',
        'action.create_role': 'สร้างยศ',
        'action.delete': 'ลบ',
        'action.duplicate': 'ทำสำเนา',
        'action.edit': 'แก้ไข',
        'action.export': 'ส่งออก',
        'action.force': 'Force',
        'action.hide': 'ซ่อน',
        'action.logout': 'ออกจากระบบ',
        'action.open_game': 'เปิดเกม',
        'action.open_reset_confirmation': 'เปิดหน้าต่างยืนยัน Reset',
        'action.queue_backfill': 'Queue Backfill',
        'action.queue_sync': 'Queue Sync',
        'action.refresh': 'โหลดใหม่',
        'action.refresh_logs': 'โหลด Logs ใหม่',
        'action.remove': 'ลบ',
        'action.reset_bot_log_data': 'Reset Bot Log Data',
        'action.reset_cursor': 'Reset Cursor',
        'action.run': 'Run',
        'action.run_sync': 'Run Sync',
        'action.rollup': 'Rollup',
        'action.role_control': 'Role Control',
        'action.save': 'บันทึก',
        'action.save_earn_rules': 'บันทึก Earn Rules',
        'action.save_settings': 'บันทึก Settings',
        'action.sync_channels': 'Sync Channels',
        'action.sync_members': 'Sync Members',
        'action.upload': 'อัปโหลด',
        'active_as_of': 'อยู่ในเซิร์ฟ ณ เวลานั้น',
        'badge.active': 'เปิดใช้งาน',
        'badge.as_of': 'ณ เวลานั้น',
        'badge.bot': 'บอท',
        'badge.boosting': 'Boosting',
        'badge.deafened': 'ปิดเสียงรับฟัง',
        'badge.deleted': 'ลบแล้ว',
        'badge.edited': 'แก้ไขแล้ว',
        'badge.files': 'ไฟล์ {count}',
        'badge.hidden': 'ซ่อน',
        'badge.hoist': 'แสดงแยก',
        'badge.inactive': 'ไม่ใช้งาน',
        'badge.left_server': 'ออกจากเซิร์ฟ',
        'badge.managed': 'จัดการโดยระบบ',
        'badge.manual': 'จัดการเอง',
        'badge.mentionable': 'กล่าวถึงได้',
        'badge.muted': 'ปิดไมค์',
        'badge.server_muted': 'โดน server mute',
        'badge.self_muted': 'ปิดไมค์เอง',
        'badge.off': 'ปิด',
        'badge.on': 'เปิด',
        'badge.paused': 'พักไว้',
        'badge.pending': 'รอตรวจ',
        'badge.request_to_speak': 'ยกมือพูด',
        'badge.suppressed': 'ถูก suppress',
        'badge.streaming': 'สตรีม',
        'badge.timeout': 'Timeout',
        'badge.banned': 'Ban',
        'badge.video': 'วิดีโอ',
        'badge.server_deafened': 'โดน server deaf',
        'badge.self_deafened': 'ปิดหูเอง',
        'badge.visible': 'แสดง',
        'left_as_of': 'ไม่ได้อยู่ในเซิร์ฟ ณ เวลานั้น',
        'confidence.approx': 'ประมาณค่า',
        'confidence.audit_log': 'audit log',
        'confidence.bot_log_snapshot': 'bot-log snapshot',
        'confidence.current': 'ข้อมูลปัจจุบัน',
        'confidence.current_fallback': 'current fallback',
        'confidence.gateway_exact': 'gateway exact',
        'confidence.historical_exact': 'historical exact',
        'confidence.snapshot': 'snapshot',
        'confidence.snapshot_empty': 'snapshot ว่าง',
        'confidence.unavailable': 'ไม่มีข้อมูลย้อนหลัง',
        'coverage.fallback': 'Fallback',
        'coverage.historical_exact': 'Historical Exact',
        'coverage.snapshot_based': 'Snapshot Based',
        'coverage.unavailable': 'Unavailable',
        'channel.status_note': 'Active คือห้องที่ยังอยู่จริงล่าสุด',
        'channel.status_tooltip': 'Active = ห้องยังเจออยู่จาก gateway หรือ sync ล่าสุด. Inactive = ห้องถูกลบ หรือไม่พบจาก server snapshot ล่าสุด.',
        'channel.type.0': 'Text',
        'channel.type.2': 'Voice',
        'channel.type.4': 'Category',
        'channel.type.5': 'Announcement',
        'channel.type.10': 'News Thread',
        'channel.type.11': 'Public Thread',
        'channel.type.12': 'Private Thread',
        'channel.type.13': 'Stage',
        'channel.type.15': 'Forum',
        'channel.type.16': 'Media',
        'common.all': 'ทั้งหมด',
        'common.all_categories': 'ทุก category',
        'common.all_channels': 'ทุกห้อง',
        'common.all_types': 'ทุกประเภท',
        'common.author': 'ผู้เขียน',
        'common.category': 'Category',
        'common.close': 'ปิด',
        'common.content': 'เนื้อหา',
        'common.date': 'วันที่',
        'common.detail': 'รายละเอียด',
        'common.discord': 'Discord',
        'common.error': 'Error',
        'common.event': 'เหตุการณ์',
        'common.files': 'ไฟล์',
        'common.filter': 'Filter',
        'common.history': 'History',
        'common.loading': 'Loading',
        'common.logs': 'Logs',
        'common.method': 'Method',
        'common.name': 'ชื่อ',
        'common.no': 'ไม่ใช่',
        'common.none': 'ไม่มี',
        'common.no_category': 'ไม่มี Category',
        'common.off': 'ปิด',
        'common.on': 'เปิด',
        'common.open': 'เปิด',
        'common.permission': 'Permission',
        'common.position': 'ตำแหน่ง',
        'common.reason': 'เหตุผล',
        'common.ref1': 'Ref1',
        'common.ref2': 'Ref2',
        'common.search': 'ค้นหา',
        'common.source': 'Source',
        'common.stage': 'Stage',
        'common.status': 'สถานะ',
        'common.system': 'ระบบ',
        'common.target': 'เป้าหมาย',
        'common.time': 'เวลา',
        'common.type': 'ประเภท',
        'common.updated': 'อัปเดต',
        'common.user': 'ผู้ใช้',
        'common.users': 'ผู้ใช้',
        'common.yes': 'ใช่',
        'common.ticks': 'ticks',
        'common.custom': 'Custom',
        'common.as_of': 'ณ เวลา',
        'common.links': 'Links',
        'common.reactions': 'Reactions',
        'common.replies': 'Replies',
        'confirm.delete_role': 'ต้องการลบ role นี้ผ่าน Discord API ใช่ไหม?',
        'duration.hms': '{h} ชม. {m} นาที {s} วิ',
        'duration.ms': '{m} นาที {s} วิ',
        'duration.unknown': 'ไม่ทราบเวลา',
        'duration.open_unknown': 'เปิดอยู่/ไม่ทราบปลายทาง',
        'duration.over_24h': 'เกิน 24 ชั่วโมง',
        'duration.source_incomplete': 'source ไม่ครบ',
        'empty.channels': 'ยังไม่มี channel snapshot',
        'empty.channels_sub': 'กด Sync Channels เพื่อดึงห้องล่าสุดจาก Discord',
        'empty.data': 'ยังไม่มีข้อมูล',
        'empty.data_sub': 'กด sync/backfill หรือปรับ filter ใหม่',
        'empty.files': 'ไม่มีไฟล์แนบ',
        'empty.gacha_item_prize': 'ยังไม่มี Gachapon item prize',
        'empty.gacha_role_prize': 'ยังไม่มี Gachapon role prize',
        'empty.guild_snapshot': 'ยังไม่มี guild snapshot',
        'empty.guild_snapshot_sub': 'กด Run Sync เพื่อดึงข้อมูลเซิฟล่าสุด',
        'empty.no_prizes': 'ไม่มีรางวัล',
        'empty.no_timeline': 'ยังไม่มี timeline',
        'empty.no_timeline_sub': 'เพิ่มเงื่อนไขหรือเปิด tier เพื่อดู preview',
        'empty.no_zone_rooms': 'ไม่มีห้องในโซนนี้',
        'empty.not_found_filter': 'ไม่พบข้อมูลตาม filter นี้',
        'empty.not_found_filter_sub': 'ลองล้าง filter หรือ sync server snapshot ใหม่',
        'event.bot_log_skipped': 'Bot log skipped',
        'event.channel_name_update': 'เปลี่ยนชื่อห้อง',
        'event.channel_status_update': 'เปลี่ยนสถานะห้อง',
        'event.channel_user_limit_update': 'เปลี่ยนจำนวนคนสูงสุดห้อง',
        'event.invite_create': 'สร้าง invite',
        'event.invite_delete': 'ลบ invite',
        'event.member_ban': 'แบนสมาชิก',
        'event.member_guild_avatar_update': 'เปลี่ยนรูปโปรไฟล์ในเซิร์ฟ',
        'event.member_kick': 'เตะสมาชิก',
        'event.member_leave': 'ออกจากเซิร์ฟ',
        'event.member_nickname_update': 'เปลี่ยนชื่อในเซิร์ฟ',
        'event.member_profile_update': 'เปลี่ยนโปรไฟล์',
        'event.member_timeout_end': 'ยกเลิก timeout',
        'event.member_timeout_start': 'ติด timeout',
        'event.member_timeout_update': 'เปลี่ยน timeout',
        'event.member_unban': 'ปลดแบนสมาชิก',
        'event.member_voice_disconnect': 'ตัดออกจากห้องเสียง',
        'event.message_bulk_delete': 'ลบหลายข้อความ',
        'event.message_delete': 'ลบข้อความ',
        'event.message_update': 'แก้ไขข้อความ',
        'event.presence_update': 'เปลี่ยนสถานะออนไลน์',
        'event.role_color_update': 'เปลี่ยนสี role',
        'event.role_name_update': 'เปลี่ยนชื่อ role',
        'event.typing_start': 'เริ่มพิมพ์',
        'event.voice_state_update': 'เปลี่ยนสถานะ voice',
        'event.voice_users_kicked': 'เตะออกจากห้องเสียง',
        'event.webhook_created': 'สร้าง webhook',
        'event.webhook_deleted': 'ลบ webhook',
        'field.active_minutes': 'นาที active',
        'field.after_countdown_ends': 'หลังหมดเวลาเคาน์ดาวน์',
        'field.badge': 'Badge',
        'field.campaign_counter_visible': 'แสดงตัวนับ Campaign บนหน้ากาชา',
        'field.coin': 'Coin',
        'field.coin_price': 'ราคา Coin',
        'field.coin_cost': 'ปุ่ม Coin ใช้กี่เครดิต / รอบ',
        'field.coin_label': 'ชื่อปุ่ม Coin',
        'field.coin_starting': 'Coin เริ่มต้น',
        'field.color': 'สี',
        'field.default_button': 'Default Button',
        'field.description': 'รายละเอียด',
        'field.discord_role': 'Discord Role',
        'field.display_quantity': 'การแสดงจำนวน',
        'field.gacha_ticket': 'GachaTicket',
        'field.guarantee_every': 'Guarantee ทุกกี่ตา (0 = ปิด)',
        'field.guarantee_tier': 'Guarantee Tier',
        'field.image_path': 'ที่อยู่รูป',
        'field.internal_percent': 'Internal %',
        'field.internal_weight': 'น้ำหนักสุ่มจริง',
        'field.name': 'ชื่อ',
        'field.permissions_bitset': 'Permissions bitset',
        'field.position': 'ตำแหน่ง',
        'field.prize_name': 'ชื่อ Prize',
        'field.pick_date': 'วันสิ้นสุด',
        'field.public_percent': 'Public %',
        'field.public_weight': 'น้ำหนักที่โชว์',
        'field.quantity_number': 'จำนวนที่โชว์',
        'field.quota_window_days': 'Quota Window Days',
        'field.role_duration': 'ระยะเวลา Role',
        'field.sort_order': 'ลำดับ',
        'field.status_icon_path': 'ที่อยู่ไอคอนสถานะ',
        'field.summary': 'Summary',
        'field.threshold': 'Threshold',
        'field.threshold_minutes': 'Threshold minutes',
        'field.ticket_cost': 'ปุ่ม Ticket ใช้กี่เครดิต / รอบ',
        'field.ticket_label': 'ชื่อปุ่ม Ticket',
        'field.ticket_starting': 'Ticket เริ่มต้น',
        'field.tier': 'Tier',
        'field.visible': 'Visible',
        'filter.bots_only': 'เฉพาะบอท',
        'filter.humans_and_bots': 'คน + บอท',
        'filter.humans_only': 'เฉพาะคน',
        'filter.occupied_at_time': 'มีคน ณ เวลานั้น',
        'filter.people': 'มีคน',
        'filter.rows': '{count} แถว',
        'filter.search_action': 'ค้น staff/action/target/ip',
        'filter.search_activity': 'ค้น event, user, channel, target, metadata',
        'filter.search_channel': 'ค้นชื่อห้อง, channel id, topic',
        'filter.search_gacha_report': 'ค้น user, prize, draw id',
        'filter.search_reward_report': 'ค้น user, rule, source',
        'filter.all_rules': 'ทุก rule',
        'filter.all_triggers': 'ทุก trigger',
        'filter.all_rewards': 'ทุก reward',
        'filter.reward_movement_all': 'รับและใช้ทั้งหมด',
        'filter.reward_movement_in': 'ได้รับเท่านั้น',
        'filter.reward_movement_out': 'ใช้ไปเท่านั้น',
        'filter.all_units': 'ทุกหน่วย',
        'filter.all_currencies': 'ทุกสกุลเงิน',
        'filter.all_buttons': 'ทุกปุ่ม',
        'filter.all_tiers': 'ทุก tier',
        'filter.all_prizes': 'ทุกรางวัล',
        'filter.all_reward_age': 'ทุกอายุรางวัล',
        'filter.temporary_roles': 'ยศไม่ถาวร',
        'filter.permanent': 'ถาวร',
        'filter.search_member': 'ค้นชื่อ, username, user id',
        'filter.search_message': 'ค้นข้อความ',
        'filter.search_role': 'ค้น role name / role id',
        'filter.status_all': 'ทุก status',
        'filter.with_event_12h': 'มี event 12h',
        'filter.rooms_100': '100 ห้อง',
        'filter.rooms_300': '300 ห้อง',
        'filter.rooms_500': '500 ห้อง',
        'filter.days_7': '7 วัน',
        'filter.days_30': '30 วัน',
        'filter.days_90': '90 วัน',
        'filter.hours_24': '24 ชั่วโมง',
        'filter.resolution': 'ความละเอียด',
        'filter.access_logs': 'Access logs',
        'filter.all_permissions': 'ทุก permission',
        'filter.all_roles': 'ทุกยศ',
        'filter.audit_compare': 'Audit compare',
        'filter.left_inactive': 'Left / inactive',
        'filter.managed_manual': 'Managed + Manual',
        'filter.managed_only': 'Managed only',
        'filter.manual_only': 'Manual only',
        'filter.staff_access_audit': 'Staff + Access + Audit',
        'filter.staff_actions': 'Staff actions',
        'form.gacha_enabled': 'เปิดระบบกาชา',
        'form.ticket_enabled': 'เปิดปุ่ม Ticket',
        'form.coin_enabled': 'เปิดปุ่ม Coin',
        'heading.access_logs': 'Access Logs',
        'heading.audit_compare': 'Audit Compare',
        'heading.activity_archive': 'Activity Archive',
        'heading.backfill_center': 'ศูนย์ Backfill',
        'heading.condition_timeline': 'Timeline เงื่อนไข',
        'heading.dashboard_users_rate_limits': 'Dashboard Users + Rate Limits',
        'heading.danger_zone': 'โซนอันตราย',
        'heading.game_credit': 'เครดิตเกม',
        'heading.gacha_campaign_counter_display': 'การแสดงผลตัวนับบนหน้าเกม',
        'heading.gacha_campaign_settings': 'ตั้งค่า Campaign กาชาปอง',
        'heading.gacha_prize_settings': 'ตั้งค่า Prize กาชาปอง',
        'heading.gacha_report': 'รายงานการหมุนกาชาปอง',
        'heading.reward_report': 'รายงานการแจก Rewards',
        'heading.earn_manual': 'Manual Earn Grant',
        'heading.earn_manual_recent': 'รายการแจกแมนนวลล่าสุด',
        'heading.permission': 'Permission',
        'heading.permission_users': 'ผู้ใช้ Dashboard',
        'heading.live_status': 'สถานะสด',
        'heading.message_archive': 'คลังข้อความ',
        'heading.presence_event_mix': 'Presence + Event Mix',
        'heading.presence_event_mix': 'Presence + Event Mix',
        'heading.prize_items': 'Prize Item',
        'heading.prize_roles': 'Prize Role',
        'heading.raw_events_errors': 'Raw Events + Errors',
        'heading.roles_in_server': 'ยศในเซิร์ฟเวอร์',
        'heading.server_snapshot': 'Server Snapshot',
        'heading.staff_logs': 'Staff Logs',
        'heading.system_control': 'System Control',
        'heading.tier_rates': 'เรท Tier',
        'heading.top_channels_relationships': 'ห้องยอดนิยม + ความสัมพันธ์',
        'heading.top_users': 'Top Users',
        'heading.workers_jobs': 'Workers + Jobs',
        'heading.workers_recovery': 'Workers + Queue Recovery',
        'hint.active_channel': 'Active = ห้องยังเจอจาก gateway/sync ล่าสุด',
        'hint.campaign_counter_visible': 'เปิดให้ผู้เล่นเห็นกล่องตัวนับเลขด้านบน',
        'hint.gacha_cost_server': 'Server จะใช้ค่านี้เป็นต้นทางจริงตอน start spin ไม่เชื่อ cost จาก browser',
        'hint.worker_command': 'รัน worker:',
        'metric.active': 'Active',
        'metric.active_channels': 'Active channels',
        'metric.active_prizes': 'Prize ที่เปิด',
        'metric.admin_roles': 'Admin Roles',
        'metric.audit_logs': 'Audit Logs',
        'metric.catalog': 'Catalog',
        'metric.categories': 'Categories',
        'metric.channels': 'Channels',
        'metric.coins': 'Coins',
        'metric.currencies': 'Currencies',
        'metric.cursors': 'Cursors',
        'metric.deleted': 'Deleted',
        'metric.errors': 'Errors',
        'metric.events': 'Events',
        'metric.events_12h': 'Events 12h',
        'metric.files_queued': 'Files Queued',
        'metric.campaign_counter_current': 'ค่าปัจจุบัน',
        'metric.campaign_counter_max': 'เป้าหมาย',
        'metric.campaign_counter_visible': 'แสดงบนเกม',
        'metric.gacha_coin_spent': 'ยอดใช้ Coin',
        'metric.gacha_completed_spins': 'รอบที่จบแล้ว',
        'metric.gacha_highest_tier_hits': 'จำนวน Tier สูงสุด',
        'metric.gacha_refunded_spins': 'รอบที่คืนเครดิต',
        'metric.gacha_ticket_spent': 'ยอดใช้ Ticket',
        'metric.gacha_total_spins': 'รอบหมุนทั้งหมด',
        'metric.reward_coin_granted': 'เหรียญที่แจก',
        'metric.reward_free_spin_granted': 'สิทธิ์สุ่มฟรีที่แจก',
        'metric.reward_wallet_income': 'รับเข้า Ledger',
        'metric.reward_wallet_spent': 'ใช้ไป Ledger',
        'metric.reward_first_join': 'รางวัลเข้าเซิร์ฟครั้งแรก',
        'metric.reward_granted_events': 'รายการที่แจกสำเร็จ',
        'metric.reward_ticket_granted': 'ตั๋วที่แจก',
        'metric.reward_total_events': 'อีเวนต์รางวัลทั้งหมด',
        'movement.received': 'ได้รับ',
        'movement.spent': 'ใช้ไป',
        'movement.wallet_ledger': 'wallet ledger',
        'movement.reward_event': 'reward event',
        'metric.granted_today': 'Granted Today',
        'metric.inactive': 'Inactive',
        'metric.ingest_errors': 'Ingest Errors',
        'metric.item_prizes': 'Item Prizes',
        'metric.ledger': 'Ledger',
        'metric.manual': 'Manual',
        'metric.managed': 'Managed',
        'metric.recent': 'ล่าสุด',
        'metric.members': 'Members',
        'metric.members_assigned': 'Members Assigned',
        'metric.messages': 'Messages',
        'metric.messages_12h': 'Messages 12h',
        'metric.missing_raw': 'Raw Missing',
        'metric.overwrites': 'Overwrites',
        'metric.panel_actions': 'Panel Actions',
        'metric.permissions': 'Permissions',
        'metric.prizes': 'Prizes',
        'metric.queued_jobs': 'Queued Jobs',
        'metric.rate_total': 'Rate Total',
        'metric.revisions': 'Revisions',
        'metric.role_prizes': 'Role Prizes',
        'metric.roles': 'Roles',
        'metric.running': 'Running',
        'metric.summary_days': 'Summary Days',
        'metric.suspect': 'Suspect',
        'metric.tiers': 'Tiers',
        'metric.users_12h': 'Users 12h',
        'metric.users_as_of': 'Users As Of',
        'metric.visible': 'Visible',
        'metric.visible_prizes': 'Prize ที่โชว์',
        'metric.voice': 'Voice',
        'metric.voice_now': 'Voice Now',
        'metric.voice_rooms': 'Voice Rooms',
        'metric.voice_users': 'Voice Users',
        'metric.bot_highest': 'Bot Highest',
        'metric.active_viewers': 'Active Viewers',
        'metric.raw_events_24h': 'Raw Events 24h',
        'metric.running_jobs': 'Running Jobs',
        'metric.rules': 'Rules',
        'metric.voice_sessions': 'Voice Sessions',
        'message.deleted_stub': 'deleted message stub · ไม่มี content ที่ archive ทันก่อนถูกลบ',
        'message.no_text_content': '(ไม่มีข้อความ)',
        'modal.confirm_bot_log_reset': 'ยืนยันการ Reset Bot Log',
        'modal.confirmation_text': 'ข้อความยืนยัน',
        'modal.channel_detail': 'รายละเอียดห้อง',
        'modal.create_role': 'สร้างยศ',
        'modal.edit_role': 'แก้ไขยศ',
        'modal.message_detail': 'รายละเอียดข้อความ',
        'modal.role_detail': 'รายละเอียดยศ',
        'modal.type_exactly': 'พิมพ์ข้อความนี้ให้ตรงทุกตัว',
        'modal.user_detail': 'รายละเอียดผู้ใช้',
        'nav.archive': 'คลังข้อมูล',
        'nav.gachapon': 'กาชาปอง',
        'nav.monitor': 'มอนิเตอร์',
        'nav.rewards': 'Rewards',
        'nav.system': 'ระบบ',
        'page.activity': 'Activity',
        'page.admin': 'ผู้ดูแลระบบ',
        'page.backfill': 'Backfill',
        'page.earn_settings': 'ตั้งค่า Earn',
        'page.earn_manual': 'Manual Earn',
        'page.gacha_campaign': 'ตั้งค่า Campaign',
        'page.gacha_prize': 'Prize Setting',
        'page.gacha_report': 'Report',
        'page.gacha_shop': 'Shop Setting',
        'page.shop_report': 'รายงาน Shop',
        'page.shop_member_bags': 'กระเป๋าสมาชิก',
        'page.bag_transaction_report': 'ธุรกรรมรวมกระเป๋า',
        'page.logs': 'Logs',
        'page.messages': 'ข้อความ',
        'page.permission': 'Permission',
        'page.reward_report': 'รายงาน Rewards',
        'page.role_permission_descriptions': 'คำอธิบายสิทธิ์ยศ',
        'page.roles': 'ยศ',
        'pager.next': 'ถัดไป',
        'pager.prev': 'ก่อนหน้า',
        'pager.rows': '{total} แถว',
        'profile.invite_count': 'จำนวนเชิญ',
        'profile.invited_by': 'ถูกเชิญโดย',
        'profile.avatar_current_fallback': 'รูปเก่าเรียกไม่ได้ เลยใช้รูปปัจจุบันแทน',
        'profile.banner_current_fallback': 'Header ใช้รูปปัจจุบันแทน เพราะไม่มีรูปย้อนหลังที่เรียกได้',
        'profile.banner_historical': 'Header จาก snapshot ย้อนหลัง',
        'profile.current_name': 'ชื่อปัจจุบัน',
        'profile.member_active_as_of': 'อยู่ในเซิร์ฟ ณ เวลานั้น',
        'profile.member_left_as_of': 'ไม่ได้อยู่ในเซิร์ฟ ณ เวลานั้น',
        'profile.no_open_voice': 'ไม่มี voice session เปิดอยู่',
        'profile.status': 'สถานะ',
        'profile.server_state': 'สถานะในเซิร์ฟ',
        'profile.timeout_until': 'Timeout ถึง {date}',
        'profile.typing': 'กำลังพิมพ์',
        'profile.typing_live': 'สัญญาณ typing แบบสด',
        'profile.typing_until': 'สัญญาณสดถึง {date}',
        'profile.vanity_invite': 'Vanity invite',
        'profile.voice': 'Voice: {channel}',
        'profile.voice_started': 'เข้าห้อง {date}',
        'profile.voice_camera_off': 'camera off',
        'profile.voice_camera_on': 'camera on',
        'profile.voice_deafened': 'deafen',
        'profile.voice_server_deafened': 'server deafen',
        'profile.voice_listening': 'ฟังได้',
        'profile.voice_muted': 'mute',
        'profile.voice_server_muted': 'server mute',
        'profile.voice_not_in_room': 'ไม่ได้อยู่ในห้องเสียง',
        'profile.voice_room': 'ห้องเสียง',
        'profile.voice_state_as_of': 'สถานะ voice ณ เวลานั้น',
        'profile.voice_stream_off': 'share screen off',
        'profile.voice_stream_on': 'share screen on',
        'profile.voice_stayed': 'อยู่มาแล้ว {duration}',
        'profile.voice_suppressed': 'ถูก suppress',
        'profile.voice_unmuted': 'unmute',
        'profile.voice_flags': 'ไมค์ {mic} · ฟัง {listen} · กล้อง {camera} · แชร์จอ {stream}{extras}',
        'profile.punish_actor': 'โดย {name}',
        'profile.punish_date': 'เมื่อ {date}',
        'profile.timeout_until': 'timeout ถึง {date}',
        'shell.subtitle': 'คอนโซลเซิร์ฟเวอร์เดียว',
        'state.degraded': 'โหมด realtime ลดระดับ',
        'state.failed': 'ล้มเหลว',
        'state.live': 'live',
        'state.ready': 'พร้อม',
        'state.done': 'เสร็จแล้ว',
        'state.active_as_of': 'อยู่ในเซิร์ฟ ณ เวลานั้น',
        'state.left_server': 'ออกจากเซิร์ฟ',
        'state.left_as_of': 'ไม่ได้อยู่ในเซิร์ฟ ณ เวลานั้น',
        'state.offline_unknown': 'offline/unknown',
        'state.unknown': 'unknown',
        'tab.access': 'Access',
        'tab.activity': 'กิจกรรม',
        'tab.as_of': 'ณ เวลานั้น',
        'tab.channels': 'ห้อง',
        'tab.children': 'ห้องย่อย',
        'tab.economy': 'Economy',
        'tab.events': 'เหตุการณ์',
        'tab.history': 'ประวัติ',
        'tab.live_now': 'สดตอนนี้',
        'tab.members': 'สมาชิก',
        'tab.messages': 'ข้อความ',
        'tab.permissions': 'สิทธิ์',
        'tab.rooms': 'ห้อง',
        'tab.role_revisions': 'ประวัติยศ',
        'tab.suspect_sessions': 'Session ที่น่าสงสัย',
        'tab.system': 'ระบบ',
        'tab.typing_now': 'กำลังพิมพ์',
        'tab.typing_unavailable': 'ไม่มีข้อมูล typing ย้อนหลัง',
        'tab.users': 'ผู้ใช้',
        'tab.voice_users': 'ผู้ใช้ในห้องเสียง',
        'tab.view_by_room': 'ดูตามห้อง',
        'tab.view_by_users': 'ดูตามผู้ใช้',
        'tab.voice': 'เสียง',
        'table.action': 'Action',
        'table.after': 'After',
        'table.allow': 'Allow',
        'table.amount': 'Amount',
        'table.audit_logs': 'Audit Logs',
        'table.author': 'Author',
        'table.before': 'Before',
        'table.bit': 'Bit',
        'table.bulk': 'Bulk',
        'table.button': 'ปุ่ม',
        'table.category': 'Category',
        'table.channel': 'Channel',
        'table.changes': 'Changes',
        'table.content': 'Content',
        'table.created': 'Created',
        'table.cursor': 'Cursor',
        'table.date': 'Date',
        'table.deleted_by': 'Deleted By',
        'table.deny': 'Deny',
        'table.discord': 'Discord',
        'table.duration': 'Duration',
        'table.duration_as_of': 'Duration as of',
        'table.end': 'End',
        'table.error': 'Error',
        'table.event': 'Event',
        'table.files': 'Files',
        'table.flags': 'Flags',
        'table.finished': 'Finished',
        'table.heartbeat': 'Heartbeat',
        'table.in_room': 'In Room',
        'table.ip': 'IP',
        'table.joined': 'Joined',
        'table.level': 'Level',
        'table.members': 'Members',
        'table.meta': 'Meta',
        'table.method': 'Method',
        'table.name': 'Name',
        'table.off': 'Off',
        'table.on': 'On',
        'table.panel_admin': 'Panel Admin',
        'table.permission': 'Permission',
        'table.permissions': 'Permissions',
        'table.pos': 'Pos',
        'table.position': 'Position',
        'table.reason': 'Reason',
        'table.remaining': 'Remaining',
        'table.role_age': 'อายุยศ',
        'table.role_grant': 'สถานะเพิ่มยศ',
        'table.rule_source': 'Rule / Source',
        'table.spend': 'ค่าใช้จ่าย',
        'table.tier': 'Tier',
        'table.prize': 'รางวัล',
        'table.counter': 'Counter',
        'table.movement': 'ความเคลื่อนไหว',
        'table.window': 'ช่วงเวลา',
        'table.revisions': 'Revisions',
        'table.role': 'Role',
        'table.route': 'Route',
        'table.scope': 'Scope',
        'table.seen': 'Seen',
        'table.source': 'Source',
        'table.span': 'Span',
        'table.staff': 'Staff',
        'table.start': 'Start',
        'table.state': 'State',
        'table.status': 'Status',
        'table.status_reason': 'Status/Reason',
        'table.target': 'Target',
        'table.time': 'Time',
        'table.to_user': 'To User',
        'table.type': 'Type',
        'table.until': 'Until',
        'table.updated': 'Updated',
        'table.user': 'User',
        'table.voice': 'Voice',
        'table.voice_30d': 'Voice 30d',
        'table.voice_room': 'Voice Room',
        'table.actor': 'Actor',
        'table.discord_executor': 'Discord Executor',
        'table.reply_user': 'Reply User',
        'table.reset': 'Reset',
        'text.archive_notice': 'ข้อมูลจริงจาก Discord/DB จะไม่ถูกแปล เช่น ชื่อผู้ใช้ ห้อง ยศ และข้อความ',
        'text.activity_archive_subtitle': 'ค้นย้อนหลังจาก processed activity log ได้ละเอียดขึ้น ทั้ง event type, user, channel, target, parser metadata และ bot-log context',
        'text.activity_filter_hint': 'ปล่อย filter ว่าง = แสดงทุก event ที่ไม่ใช่ message',
        'text.activity_subtitle': 'canonical event timeline จาก tbl_raw_event พร้อม source/context สำหรับ monitor และ debug',
        'text.admin_subtitle': 'สถานะ worker, job queue, raw events, ingest errors และ dashboard users',
        'text.backfill_subtitle': 'เติมข้อมูลจากล่าสุดย้อนหลัง แยกตามชนิดข้อมูล พร้อม cursor, recovery status และผลลัพธ์ล่าสุด',
        'text.backfill_danger_body': 'ปุ่มนี้จะล้างข้อมูลที่ derive จาก activity ทั้งหมด แล้วค่อยให้เรา backfill/build ใหม่อีกครั้งจาก source log โดยต้องพิมพ์คำยืนยันก่อนทุกครั้ง',
        'text.bot_log_reset_warning': 'Action นี้จะล้างข้อมูล message, activity, voice session, summary และ wallet ledger ที่ derive จาก activity ก่อน rebuild ใหม่ทั้งชุด',
        'text.channels_subtitle': 'ลิสต์ห้องแบบเดียวกับ Discord แยกตาม category พร้อมหัวโซน sticky ระหว่างเลื่อน',
        'text.data_from_db': 'หน้านี้อ่านข้อมูลจาก DB ล่าสุด และจะกลับมา live เมื่อ gateway heartbeat กลับมา',
        'text.discord_action_guard': 'ทุก action จะตรวจ dashboard permission, Discord hierarchy และ bot hierarchy ก่อนยิง API',
        'text.earn_subtitle': 'ตั้งเงื่อนไขแจกของรางวัลอัตโนมัติ ระบบ worker จะตรวจซ้ำทุก 5 นาทีและกันแจกซ้ำด้วย reward event',
        'text.gacha_condition_subtitle': 'Preview เงื่อนไขสำคัญและเรทที่ active อยู่ตอนนี้',
        'text.gacha_campaign_counter_display': 'สวิตช์นี้มีผลเฉพาะการแสดงผลด้านบนของหน้ากาชา ไม่กระทบการนับรอบหรือรายงานย้อนหลัง',
        'text.gacha_campaign_subtitle': 'ควบคุมการแสดงผล campaign counter บนหน้าเกม โดยระบบยังนับค่าจริงไว้ตลอดแม้ซ่อน HUD',
        'text.gacha_item_subtitle': 'แสดงเฉพาะของรางวัลแบบ item ที่ขึ้นบนชั้นและหน้า prizes.php กดแถวเพื่อขยายแก้ไข',
        'text.gacha_prize_subtitle': 'ตั้งค่าเครดิตที่ใช้หมุน, เรทลูกกาชา, รายการรางวัล, วันสิ้นสุดแบบ pick date และรางวัลยศของเกมกาชาปอง',
        'text.gacha_report_subtitle': 'ดูย้อนหลังแต่ละรอบที่ server ล็อกผลแล้ว พร้อมสถานะ, ค่าใช้จ่าย, tier, รางวัล และจังหวะของแต่ละ phase',
        'text.gacha_role_subtitle': 'แสดงเฉพาะรางวัลยศ Discord แยกจาก item และใช้รายการเดียวบนหน้า prizes.php',
        'text.gacha_tier_subtitle': 'Internal rate ใช้สุ่มจริง, Public rate ใช้แสดงหน้ารางวัล',
        'text.logs_subtitle': 'ทุก action ในระบบ, rejected action, sensitive read, export และ Discord write audit reason',
        'text.members_subtitle': 'รายชื่อสมาชิก, role summary, activity, economy และ history ต่อ user',
        'text.messages_subtitle': 'archive หน้าเดียว ใช้ filter หนักสำหรับ message, deleted, edited, attachments, reply, reaction และ poll',
        'text.reward_report_subtitle': 'ดูย้อนหลังว่า reward rule ไหนแจกหรือใช้หน่วยเงินอะไรบ้าง รวมถึงสิทธิ์สุ่มฟรี พร้อม source และ ledger balance หลังรายการ',
        'text.earn_manual_subtitle': 'แจกหน่วยเงินให้ผู้ใช้แบบแมนนวล โดยบันทึก reward event, wallet ledger และ staff log ครบทุกครั้ง',
        'text.earn_manual_audit_header': 'ตรวจสอบย้อนหลังได้',
        'text.earn_manual_audit_body': 'ทุกการแจกจะเข้า Reward Report เป็น source Manual grant และมี balance ก่อน/หลังอยู่ใน wallet ledger',
        'text.permission_subtitle': 'ตั้ง tier ว่าใครเข้าสู่ dashboard ได้ และแต่ละ tier เห็น/จัดการหน้าไหนได้บ้าง',
        'text.permission_guard_header': 'หลังบ้านเช็คสิทธิ์จริง',
        'text.permission_guard_body': 'เมนูเป็นแค่ทางเข้า แต่ page loader และ endpoint สำคัญจะตรวจ permission จาก session/admin user ทุกครั้ง',
        'text.reward_report_storage_header': 'ตอนนี้ระบบเก็บอะไรไว้บ้าง',
        'text.reward_report_storage_body': 'tbl_reward_event เก็บประวัติการแจกต่อครั้ง และ tbl_shop_wallet_ledger เก็บการเคลื่อนไหวของทุกหน่วยเงินในระบบใหม่แบบแยกตาม unit',
        'text.reward_report_window_note': 'สำหรับ reward แบบข้อความ/เสียง report จะแสดงเป็นช่วงสะสมของ rule เช่น ช่วง 1-2 ชั่วโมง หรือ active 10-20 นาที พร้อมยอดสะสมตอนที่จ่ายจริง ไม่ใช่ clock hour จริงตามนาฬิกา เพราะระบบเก็บจาก daily summary',
        'source.earn_text_active_daily': 'Text active daily',
        'source.earn_voice_hourly': 'สะสมชั่วโมงเสียง',
        'source.earn_voice_10min_free_spin': 'เข้าห้องสนทนาครบ 10 นาที',
        'source.earn_member_first_join': 'First join',
        'source.earn_invite_member': 'Invite member',
        'source.earn_manual': 'Manual grant',
        'source.gacha_spin': 'ใช้หมุนกาชา',
        'source.gacha_spin_refund': 'คืนเครดิตกาชา',
        'source.gacha_reset_mock_credit': 'รีเซ็ตเครดิต',
        'source.voice': 'voice',
        'source.text': 'text',
        'source.first_join': 'first join',
        'source.invite_success': 'invite success',
        'reward_window.voice_segment': 'ชั่วโมงสะสมที่ {segment}',
        'reward_window.text_segment': 'ช่วง active ที่ {segment}',
        'reward_window.hour_range': 'ช่วง {start}-{end} ชั่วโมง',
        'reward_window.duration_range': 'ช่วง {start} - {end}',
        'reward_window.minute_range': 'ช่วง {start}-{end} นาที',
        'role_duration.permanent': 'ถาวร',
        'role_duration.days': '{days} วัน',
        'role_duration.left_days': 'อีก {days} วัน',
        'role_duration.ended': 'ครบกำหนดแล้ว',
        'role_duration.no_expire': 'ไม่กำหนด',
        'status.not_role_prize': 'ไม่ใช่ยศ',
        'status.no_grant_log': 'ไม่มีข้อมูล grant',
        'status.waiting_round_complete': 'รอจบรอบ',
        'text.roles_subtitle': 'จัดการยศ, hierarchy, permission, จำนวนสมาชิก และตรวจรายละเอียดของแต่ละ role',
        'text.historical_source_bot_logs': 'Historical source: bot logs',
        'text.historical_context_notice': 'กำลังดูข้อมูล ณ {asOf}. ป้าย snapshot/fallback จะบอกว่าข้อมูลส่วนไหนย้อนจริงหรือใช้ข้อมูลปัจจุบันแทน',
        'toast.backfill_failed': 'Backfill ล้มเหลว',
        'toast.earn_saved': 'บันทึก Earn settings แล้ว',
        'toast.gacha_image_uploaded': 'อัปโหลดรูปแล้ว อย่าลืมกด Save Settings',
        'toast.gacha_saved': 'บันทึก Gachapon settings แล้ว',
        'toast.earn_manual_granted': 'แจก Manual Earn สำเร็จ',
        'toast.permission_saved': 'บันทึก Permission แล้ว',
        'toast.shop_saved': 'บันทึก Shop Setting แล้ว',
        'toast.hide_session_done': 'ซ่อน voice session แล้ว',
        'toast.hide_session_failed': 'ซ่อน voice session ไม่สำเร็จ',
        'toast.page_load_failed': 'โหลดหน้าไม่สำเร็จ',
        'toast.queue_failed': 'Queue ล้มเหลว',
        'toast.role_action_failed': 'Role action ล้มเหลว',
        'toast.role_delete_failed': 'ลบ role ล้มเหลว',
        'toast.earn_save_failed': 'บันทึก Earn settings ไม่สำเร็จ',
        'toast.save_failed': 'บันทึกไม่สำเร็จ',
        'toast.upload_failed': 'อัปโหลดไม่สำเร็จ',
        'tooltip.time_step_back': 'ถอยเวลา 1 ช่องตามความละเอียดที่เลือก',
        'tooltip.time_step_forward': 'เดินเวลา 1 ช่องตามความละเอียดที่เลือก',
      },
      en: {
        'action.add_item_prize': 'Add Item Prize',
        'action.add_role_prize': 'Add Role Prize',
        'action.backfill': 'Backfill',
        'action.backfill_all': 'Backfill All',
        'action.cancel': 'Cancel',
        'action.create_role': 'Create Role',
        'action.delete': 'Delete',
        'action.duplicate': 'Duplicate',
        'action.edit': 'Edit',
        'action.export': 'Export',
        'action.force': 'Force',
        'action.hide': 'Hide',
        'action.logout': 'Logout',
        'action.open_game': 'Open Game',
        'action.open_reset_confirmation': 'Open Reset Confirmation',
        'action.queue_backfill': 'Queue Backfill',
        'action.queue_sync': 'Queue Sync',
        'action.refresh': 'Refresh',
        'action.refresh_logs': 'Refresh Logs',
        'action.remove': 'Remove',
        'action.reset_bot_log_data': 'Reset Bot Log Data',
        'action.reset_cursor': 'Reset Cursor',
        'action.run': 'Run',
        'action.run_sync': 'Run Sync',
        'action.rollup': 'Rollup',
        'action.role_control': 'Role Control',
        'action.save': 'Save',
        'action.save_earn_rules': 'Save Earn Rules',
        'action.save_settings': 'Save Settings',
        'action.sync_channels': 'Sync Channels',
        'action.sync_members': 'Sync Members',
        'action.upload': 'Upload',
        'active_as_of': 'In server as of that time',
        'badge.active': 'Active',
        'badge.as_of': 'As of',
        'badge.bot': 'Bot',
        'badge.boosting': 'Boosting',
        'badge.deafened': 'Deafened',
        'badge.deleted': 'Deleted',
        'badge.edited': 'Edited',
        'badge.files': 'Files {count}',
        'badge.hidden': 'Hidden',
        'badge.hoist': 'Hoist',
        'badge.inactive': 'Inactive',
        'badge.left_server': 'Left server',
        'badge.managed': 'Managed',
        'badge.manual': 'Manual',
        'badge.mentionable': 'Mentionable',
        'badge.muted': 'Muted',
        'badge.server_muted': 'Server muted',
        'badge.self_muted': 'Self muted',
        'badge.off': 'Off',
        'badge.on': 'On',
        'badge.paused': 'Paused',
        'badge.pending': 'Pending',
        'badge.request_to_speak': 'Requested to speak',
        'badge.suppressed': 'Suppressed',
        'badge.streaming': 'Streaming',
        'badge.timeout': 'Timeout',
        'badge.banned': 'Banned',
        'badge.video': 'Video',
        'badge.server_deafened': 'Server deafened',
        'badge.self_deafened': 'Self deafened',
        'badge.visible': 'Visible',
        'left_as_of': 'Not in server as of that time',
        'confidence.approx': 'approximate',
        'confidence.audit_log': 'audit log',
        'confidence.bot_log_snapshot': 'bot-log snapshot',
        'confidence.current': 'current',
        'confidence.current_fallback': 'current fallback',
        'confidence.gateway_exact': 'gateway exact',
        'confidence.historical_exact': 'historical exact',
        'confidence.snapshot': 'snapshot',
        'confidence.snapshot_empty': 'empty snapshot',
        'confidence.unavailable': 'unavailable',
        'coverage.fallback': 'Fallback',
        'coverage.historical_exact': 'Historical Exact',
        'coverage.snapshot_based': 'Snapshot Based',
        'coverage.unavailable': 'Unavailable',
        'channel.status_note': 'Active means the channel exists in the latest snapshot',
        'channel.status_tooltip': 'Active = found by gateway or latest sync. Inactive = deleted or missing from the latest server snapshot.',
        'channel.type.0': 'Text',
        'channel.type.2': 'Voice',
        'channel.type.4': 'Category',
        'channel.type.5': 'Announcement',
        'channel.type.10': 'News Thread',
        'channel.type.11': 'Public Thread',
        'channel.type.12': 'Private Thread',
        'channel.type.13': 'Stage',
        'channel.type.15': 'Forum',
        'channel.type.16': 'Media',
        'common.all': 'All',
        'common.all_categories': 'All categories',
        'common.all_channels': 'All channels',
        'common.all_types': 'All types',
        'common.author': 'Author',
        'common.category': 'Category',
        'common.close': 'Close',
        'common.content': 'Content',
        'common.date': 'Date',
        'common.detail': 'Detail',
        'common.discord': 'Discord',
        'common.error': 'Error',
        'common.event': 'Event',
        'common.files': 'Files',
        'common.filter': 'Filter',
        'common.history': 'History',
        'common.loading': 'Loading',
        'common.logs': 'Logs',
        'common.method': 'Method',
        'common.name': 'Name',
        'common.no': 'No',
        'common.none': 'None',
        'common.no_category': 'No Category',
        'common.off': 'Off',
        'common.on': 'On',
        'common.open': 'Open',
        'common.permission': 'Permission',
        'common.position': 'Position',
        'common.reason': 'Reason',
        'common.ref1': 'Ref1',
        'common.ref2': 'Ref2',
        'common.search': 'Search',
        'common.source': 'Source',
        'common.stage': 'Stage',
        'common.status': 'Status',
        'common.system': 'System',
        'common.target': 'Target',
        'common.time': 'Time',
        'common.type': 'Type',
        'common.updated': 'Updated',
        'common.user': 'User',
        'common.users': 'users',
        'common.yes': 'Yes',
        'common.ticks': 'ticks',
        'common.custom': 'Custom',
        'common.as_of': 'As of',
        'common.links': 'Links',
        'common.reactions': 'Reactions',
        'common.replies': 'Replies',
        'confirm.delete_role': 'Delete this role through Discord API?',
        'duration.hms': '{h}h {m}m {s}s',
        'duration.ms': '{m}m {s}s',
        'duration.unknown': 'Unknown time',
        'duration.open_unknown': 'open/unknown',
        'duration.over_24h': 'over 24h',
        'duration.source_incomplete': 'source incomplete',
        'empty.channels': 'No channel snapshot yet',
        'empty.channels_sub': 'Run Sync Channels to fetch the latest Discord rooms',
        'empty.data': 'No data yet',
        'empty.data_sub': 'Run sync/backfill or adjust filters',
        'empty.files': 'No attachments',
        'empty.gacha_item_prize': 'No Gachapon item prizes yet',
        'empty.gacha_role_prize': 'No Gachapon role prizes yet',
        'empty.guild_snapshot': 'No guild snapshot yet',
        'empty.guild_snapshot_sub': 'Run Sync to fetch the latest server data',
        'empty.no_prizes': 'No prizes',
        'empty.no_timeline': 'No timeline yet',
        'empty.no_timeline_sub': 'Add conditions or enable tiers to preview them',
        'empty.no_zone_rooms': 'No rooms in this zone',
        'empty.not_found_filter': 'No results match this filter',
        'empty.not_found_filter_sub': 'Try clearing filters or sync a fresh server snapshot',
        'event.bot_log_skipped': 'Bot log skipped',
        'event.channel_name_update': 'Channel renamed',
        'event.channel_status_update': 'Channel status updated',
        'event.channel_user_limit_update': 'Channel user limit updated',
        'event.invite_create': 'Invite created',
        'event.invite_delete': 'Invite deleted',
        'event.member_ban': 'Member banned',
        'event.member_guild_avatar_update': 'Guild avatar changed',
        'event.member_kick': 'Member kicked',
        'event.member_leave': 'Member left',
        'event.member_nickname_update': 'Nickname changed',
        'event.member_profile_update': 'Profile changed',
        'event.member_timeout_end': 'Timeout cleared',
        'event.member_timeout_start': 'Timed out',
        'event.member_timeout_update': 'Timeout updated',
        'event.member_unban': 'Member unbanned',
        'event.member_voice_disconnect': 'Voice disconnect',
        'event.message_bulk_delete': 'Messages bulk deleted',
        'event.message_delete': 'Message deleted',
        'event.message_update': 'Message edited',
        'event.presence_update': 'Presence updated',
        'event.role_color_update': 'Role color updated',
        'event.role_name_update': 'Role renamed',
        'event.typing_start': 'Typing started',
        'event.voice_state_update': 'Voice state changed',
        'event.voice_users_kicked': 'Voice users kicked',
        'event.webhook_created': 'Webhook created',
        'event.webhook_deleted': 'Webhook deleted',
        'field.active_minutes': 'Active minutes',
        'field.after_countdown_ends': 'After Countdown Ends',
        'field.badge': 'Badge',
        'field.coin': 'Coin',
        'field.coin_price': 'Coin Price',
        'field.coin_cost': 'Coin button cost / spin',
        'field.coin_label': 'Coin button label',
        'field.coin_starting': 'Starting Coin',
        'field.color': 'Color',
        'field.default_button': 'Default Button',
        'field.description': 'Description',
        'field.discord_role': 'Discord Role',
        'field.display_quantity': 'Display Quantity',
        'field.gacha_ticket': 'GachaTicket',
        'field.guarantee_every': 'Guarantee every N spins (0 = off)',
        'field.guarantee_tier': 'Guarantee Tier',
        'field.image_path': 'Image Path',
        'field.internal_percent': 'Internal %',
        'field.internal_weight': 'Internal Weight',
        'field.name': 'Name',
        'field.permissions_bitset': 'Permissions bitset',
        'field.position': 'Position',
        'field.prize_name': 'Prize Name',
        'field.pick_date': 'Pick Date',
        'field.public_percent': 'Public %',
        'field.public_weight': 'Public Weight',
        'field.quantity_number': 'Quantity Number',
        'field.quota_window_days': 'Quota Window Days',
        'field.role_duration': 'Role Duration',
        'field.sort_order': 'Sort Order',
        'field.status_icon_path': 'Status Icon Path',
        'field.summary': 'Summary',
        'field.threshold': 'Threshold',
        'field.threshold_minutes': 'Threshold minutes',
        'field.ticket_cost': 'Ticket button cost / spin',
        'field.ticket_label': 'Ticket button label',
        'field.ticket_starting': 'Starting Ticket',
        'field.tier': 'Tier',
        'field.visible': 'Visible',
        'filter.bots_only': 'Bots only',
        'filter.humans_and_bots': 'Humans + bots',
        'filter.humans_only': 'Humans only',
        'filter.occupied_at_time': 'Occupied at that time',
        'filter.people': 'Occupied',
        'filter.rows': '{count} rows',
        'filter.search_action': 'Search staff/action/target/ip',
        'filter.search_activity': 'Search event, user, channel, target, metadata',
        'filter.search_channel': 'Search channel name, channel id, topic',
        'filter.search_gacha_report': 'Search user, prize, draw id',
        'filter.search_reward_report': 'Search user, rule, source',
        'filter.all_rules': 'All rules',
        'filter.all_triggers': 'All triggers',
        'filter.all_rewards': 'All rewards',
        'filter.reward_movement_all': 'Received and spent',
        'filter.reward_movement_in': 'Received only',
        'filter.reward_movement_out': 'Spent only',
        'filter.all_units': 'All units',
        'filter.all_currencies': 'All currencies',
        'filter.all_buttons': 'All buttons',
        'filter.all_tiers': 'All tiers',
        'filter.all_prizes': 'All prizes',
        'filter.all_reward_age': 'All reward ages',
        'filter.temporary_roles': 'Temporary roles',
        'filter.permanent': 'Permanent',
        'filter.search_member': 'Search name, username, user id',
        'filter.search_message': 'Search messages',
        'filter.search_role': 'Search role name / role id',
        'filter.status_all': 'All status',
        'filter.with_event_12h': 'Has event 12h',
        'filter.rooms_100': '100 rooms',
        'filter.rooms_300': '300 rooms',
        'filter.rooms_500': '500 rooms',
        'filter.days_7': '7 days',
        'filter.days_30': '30 days',
        'filter.days_90': '90 days',
        'filter.hours_24': '24 hours',
        'filter.resolution': 'Resolution',
        'filter.access_logs': 'Access logs',
        'filter.all_permissions': 'All permissions',
        'filter.all_roles': 'All roles',
        'filter.audit_compare': 'Audit compare',
        'filter.left_inactive': 'Left / inactive',
        'filter.managed_manual': 'Managed + Manual',
        'filter.managed_only': 'Managed only',
        'filter.manual_only': 'Manual only',
        'filter.staff_access_audit': 'Staff + Access + Audit',
        'filter.staff_actions': 'Staff actions',
        'form.gacha_enabled': 'Enable Gachapon',
        'form.ticket_enabled': 'Enable Ticket button',
        'form.coin_enabled': 'Enable Coin button',
        'heading.access_logs': 'Access Logs',
        'heading.audit_compare': 'Audit Compare',
        'heading.activity_archive': 'Activity Archive',
        'heading.backfill_center': 'Backfill Center',
        'heading.condition_timeline': 'Condition Timeline',
        'heading.dashboard_users_rate_limits': 'Dashboard Users + Rate Limits',
        'heading.danger_zone': 'Danger Zone',
        'heading.game_credit': 'Game Credit',
        'heading.gacha_campaign_counter_display': 'Game Counter Display',
        'heading.gacha_campaign_settings': 'Gachapon Campaign Settings',
        'heading.gacha_prize_settings': 'Gachapon Prize Settings',
        'heading.gacha_report': 'Gachapon Spin Report',
        'heading.reward_report': 'Reward Distribution Report',
        'heading.earn_manual': 'Manual Earn Grant',
        'heading.earn_manual_recent': 'Recent Manual Grants',
        'heading.permission': 'Permission',
        'heading.permission_users': 'Dashboard Users',
        'heading.earn_manual_recent': 'Recent Manual Grants',
        'heading.live_status': 'Live Status',
        'heading.message_archive': 'Message Archive',
        'heading.presence_event_mix': 'Presence + Event Mix',
        'heading.presence_event_mix': 'Presence + Event Mix',
        'heading.prize_items': 'Prize Items',
        'heading.prize_roles': 'Prize Roles',
        'heading.raw_events_errors': 'Raw Events + Errors',
        'heading.roles_in_server': 'Roles In Server',
        'heading.server_snapshot': 'Server Snapshot',
        'heading.staff_logs': 'Staff Logs',
        'heading.system_control': 'System Control',
        'heading.tier_rates': 'Tier Rates',
        'heading.top_channels_relationships': 'Top Channels + Relationships',
        'heading.top_users': 'Top Users',
        'heading.workers_jobs': 'Workers + Jobs',
        'heading.workers_recovery': 'Workers + Queued Recovery',
        'hint.active_channel': 'Active = found by gateway or latest sync',
        'hint.campaign_counter_visible': 'Show the top campaign counter box to players.',
        'hint.gacha_cost_server': 'The server uses these values as the source of truth when starting a real spin; browser cost is not trusted.',
        'hint.worker_command': 'Worker command:',
        'metric.active': 'Active',
        'metric.active_channels': 'Active channels',
        'metric.active_prizes': 'Active Prizes',
        'metric.admin_roles': 'Admin Roles',
        'metric.audit_logs': 'Audit Logs',
        'metric.catalog': 'Catalog',
        'metric.categories': 'Categories',
        'metric.channels': 'Channels',
        'metric.coins': 'Coins',
        'metric.currencies': 'Currencies',
        'metric.cursors': 'Cursors',
        'metric.deleted': 'Deleted',
        'metric.errors': 'Errors',
        'metric.events': 'Events',
        'metric.events_12h': 'Events 12h',
        'metric.files_queued': 'Files Queued',
        'metric.campaign_counter_current': 'Current Value',
        'metric.campaign_counter_max': 'Goal',
        'metric.campaign_counter_visible': 'Visible In Game',
        'metric.gacha_coin_spent': 'Coin Spent',
        'metric.gacha_completed_spins': 'Completed Spins',
        'metric.gacha_highest_tier_hits': 'Highest Tier Hits',
        'metric.gacha_refunded_spins': 'Refunded Spins',
        'metric.gacha_ticket_spent': 'Ticket Spent',
        'metric.gacha_total_spins': 'Total Spins',
        'metric.reward_coin_granted': 'Coins Granted',
        'metric.reward_free_spin_granted': 'Free Spins Granted',
        'metric.reward_wallet_income': 'Ledger Income',
        'metric.reward_wallet_spent': 'Ledger Spent',
        'metric.reward_first_join': 'First Join Rewards',
        'metric.reward_granted_events': 'Granted Events',
        'metric.reward_ticket_granted': 'Tickets Granted',
        'metric.reward_total_events': 'Reward Events',
        'movement.received': 'Received',
        'movement.spent': 'Spent',
        'movement.wallet_ledger': 'wallet ledger',
        'movement.reward_event': 'reward event',
        'metric.granted_today': 'Granted Today',
        'metric.inactive': 'Inactive',
        'metric.ingest_errors': 'Ingest Errors',
        'metric.item_prizes': 'Item Prizes',
        'metric.ledger': 'Ledger',
        'metric.manual': 'Manual',
        'metric.managed': 'Managed',
        'metric.recent': 'Recent',
        'metric.members': 'Members',
        'metric.members_assigned': 'Members Assigned',
        'metric.messages': 'Messages',
        'metric.messages_12h': 'Messages 12h',
        'metric.missing_raw': 'Raw Missing',
        'metric.overwrites': 'Overwrites',
        'metric.panel_actions': 'Panel Actions',
        'metric.permissions': 'Permissions',
        'metric.prizes': 'Prizes',
        'metric.queued_jobs': 'Queued Jobs',
        'metric.rate_total': 'Rate Total',
        'metric.revisions': 'Revisions',
        'metric.role_prizes': 'Role Prizes',
        'metric.roles': 'Roles',
        'metric.running': 'Running',
        'metric.summary_days': 'Summary Days',
        'metric.suspect': 'Suspect',
        'metric.tiers': 'Tiers',
        'metric.users_12h': 'Users 12h',
        'metric.users_as_of': 'Users As Of',
        'metric.visible': 'Visible',
        'metric.visible_prizes': 'Visible Prizes',
        'metric.voice': 'Voice',
        'metric.voice_now': 'Voice Now',
        'metric.voice_rooms': 'Voice Rooms',
        'metric.voice_users': 'Voice Users',
        'metric.bot_highest': 'Bot Highest',
        'metric.active_viewers': 'Active Viewers',
        'metric.raw_events_24h': 'Raw Events 24h',
        'metric.running_jobs': 'Running Jobs',
        'metric.rules': 'Rules',
        'metric.voice_sessions': 'Voice Sessions',
        'message.deleted_stub': 'deleted message stub · archived content unavailable',
        'message.no_text_content': '(no text content)',
        'modal.confirm_bot_log_reset': 'Confirm Bot Log Reset',
        'modal.confirmation_text': 'Confirmation text',
        'modal.channel_detail': 'Channel Detail',
        'modal.create_role': 'Create Role',
        'modal.edit_role': 'Edit Role',
        'modal.message_detail': 'Message Detail',
        'modal.role_detail': 'Role Detail',
        'modal.type_exactly': 'Type this exactly',
        'modal.user_detail': 'User Detail',
        'shell.subtitle': 'Single-server console',
        'nav.monitor': 'Monitor',
        'nav.rewards': 'Rewards',
        'nav.archive': 'Archive',
        'nav.gachapon': 'Gachapon',
        'nav.system': 'System',
        'page.activity': 'Activity',
        'page.roles': 'Roles',
        'page.permission': 'Permission',
        'page.messages': 'Messages',
        'page.gacha_campaign': 'Campaign Setting',
        'page.gacha_prize': 'Prize Setting',
        'page.gacha_report': 'Report',
        'page.gacha_shop': 'Shop Setting',
        'page.shop_report': 'Shop Report',
        'page.shop_member_bags': 'Member Bags',
        'page.bag_transaction_report': 'Bag Transaction Report',
        'page.earn_settings': 'Earn Settings',
        'page.earn_manual': 'Manual Earn',
        'page.reward_report': 'Reward Report',
        'page.role_permission_descriptions': 'Role Permission Descriptions',
        'page.admin': 'Admin',
        'page.backfill': 'Backfill',
        'page.logs': 'Logs',
        'pager.next': 'Next',
        'pager.prev': 'Prev',
        'pager.rows': '{total} rows',
        'profile.invite_count': 'Invite count',
        'profile.invited_by': 'Invited by',
        'profile.avatar_current_fallback': 'Historical avatar was unavailable, so the current avatar is shown instead.',
        'profile.banner_current_fallback': 'Header uses the current banner because no historical banner was available.',
        'profile.banner_historical': 'Header from historical snapshot',
        'profile.current_name': 'Current name',
        'profile.member_active_as_of': 'In server as of that time',
        'profile.member_left_as_of': 'Not in server as of that time',
        'profile.no_open_voice': 'No open voice session',
        'profile.status': 'Status',
        'profile.server_state': 'Server state',
        'profile.timeout_until': 'Timeout until {date}',
        'profile.typing': 'Typing',
        'profile.typing_live': 'Live typing signal',
        'profile.typing_until': 'Signal live until {date}',
        'profile.vanity_invite': 'Vanity invite',
        'profile.voice': 'Voice: {channel}',
        'profile.voice_started': 'Joined {date}',
        'profile.voice_camera_off': 'camera off',
        'profile.voice_camera_on': 'camera on',
        'profile.voice_deafened': 'deafened',
        'profile.voice_server_deafened': 'server deafened',
        'profile.voice_listening': 'listening',
        'profile.voice_muted': 'muted',
        'profile.voice_server_muted': 'server muted',
        'profile.voice_not_in_room': 'Not in a voice room',
        'profile.voice_room': 'Voice room',
        'profile.voice_state_as_of': 'Voice state as of that time',
        'profile.voice_stream_off': 'screen share off',
        'profile.voice_stream_on': 'screen share on',
        'profile.voice_stayed': 'Stayed {duration}',
        'profile.voice_suppressed': 'suppressed',
        'profile.voice_unmuted': 'unmuted',
        'profile.voice_flags': 'Mic {mic} · Listen {listen} · Camera {camera} · Screen {stream}{extras}',
        'profile.punish_actor': 'By {name}',
        'profile.punish_date': 'At {date}',
        'profile.timeout_until': 'timeout until {date}',
        'state.degraded': 'degraded',
        'state.failed': 'failed',
        'state.live': 'live',
        'state.ready': 'ready',
        'state.done': 'done',
        'state.active_as_of': 'In server as of that time',
        'state.left_server': 'left server',
        'state.left_as_of': 'Not in server as of that time',
        'state.offline_unknown': 'offline/unknown',
        'state.unknown': 'unknown',
        'tab.access': 'Access',
        'tab.activity': 'Activity',
        'tab.as_of': 'As Of',
        'tab.channels': 'Channels',
        'tab.children': 'Children',
        'tab.economy': 'Economy',
        'tab.events': 'Events',
        'tab.history': 'History',
        'tab.live_now': 'Live Now',
        'tab.members': 'Members',
        'tab.messages': 'Messages',
        'tab.permissions': 'Permissions',
        'tab.rooms': 'Rooms',
        'tab.role_revisions': 'Role Revisions',
        'tab.suspect_sessions': 'Suspect Sessions',
        'tab.system': 'System',
        'tab.typing_now': 'Typing Now',
        'tab.typing_unavailable': 'Typing unavailable historically',
        'tab.users': 'Users',
        'tab.voice_users': 'Voice Users',
        'tab.view_by_room': 'View by room',
        'tab.view_by_users': 'View by users',
        'tab.voice': 'Voice',
        'table.action': 'Action',
        'table.after': 'After',
        'table.allow': 'Allow',
        'table.amount': 'Amount',
        'table.audit_logs': 'Audit Logs',
        'table.author': 'Author',
        'table.before': 'Before',
        'table.bit': 'Bit',
        'table.bulk': 'Bulk',
        'table.button': 'Button',
        'table.category': 'Category',
        'table.channel': 'Channel',
        'table.changes': 'Changes',
        'table.content': 'Content',
        'table.created': 'Created',
        'table.cursor': 'Cursor',
        'table.date': 'Date',
        'table.deleted_by': 'Deleted By',
        'table.deny': 'Deny',
        'table.discord': 'Discord',
        'table.duration': 'Duration',
        'table.duration_as_of': 'Duration as of',
        'table.end': 'End',
        'table.error': 'Error',
        'table.event': 'Event',
        'table.files': 'Files',
        'table.flags': 'Flags',
        'table.finished': 'Finished',
        'table.heartbeat': 'Heartbeat',
        'table.in_room': 'In Room',
        'table.ip': 'IP',
        'table.joined': 'Joined',
        'table.level': 'Level',
        'table.members': 'Members',
        'table.meta': 'Meta',
        'table.method': 'Method',
        'table.name': 'Name',
        'table.off': 'Off',
        'table.on': 'On',
        'table.panel_admin': 'Panel Admin',
        'table.permission': 'Permission',
        'table.permissions': 'Permissions',
        'table.pos': 'Pos',
        'table.position': 'Position',
        'table.reason': 'Reason',
        'table.remaining': 'Remaining',
        'table.role_age': 'Role Age',
        'table.role_grant': 'Role Grant',
        'table.rule_source': 'Rule / Source',
        'table.spend': 'Spend',
        'table.tier': 'Tier',
        'table.prize': 'Prize',
        'table.counter': 'Counter',
        'table.movement': 'Movement',
        'table.window': 'Window',
        'table.revisions': 'Revisions',
        'table.role': 'Role',
        'table.route': 'Route',
        'table.scope': 'Scope',
        'table.seen': 'Seen',
        'table.source': 'Source',
        'table.span': 'Span',
        'table.staff': 'Staff',
        'table.start': 'Start',
        'table.state': 'State',
        'table.status': 'Status',
        'table.status_reason': 'Status/Reason',
        'table.target': 'Target',
        'table.time': 'Time',
        'table.to_user': 'To User',
        'table.type': 'Type',
        'table.until': 'Until',
        'table.updated': 'Updated',
        'table.user': 'User',
        'table.voice': 'Voice',
        'table.voice_30d': 'Voice 30d',
        'table.voice_room': 'Voice Room',
        'table.actor': 'Actor',
        'table.discord_executor': 'Discord Executor',
        'table.reply_user': 'Reply User',
        'table.reset': 'Reset',
        'text.archive_notice': 'Real Discord/DB data is not translated, including users, channels, roles, and messages.',
        'text.activity_archive_subtitle': 'Search processed activity history in more detail across event types, users, channels, targets, parser metadata, and bot-log context.',
        'text.activity_filter_hint': 'Leave filters empty to show every non-message event.',
        'text.activity_subtitle': 'Canonical event timeline from tbl_raw_event with source/context for monitoring and debugging.',
        'text.admin_subtitle': 'Worker status, job queue, raw events, ingest errors, and dashboard users.',
        'text.backfill_subtitle': 'Backfill from newest to oldest by data type, including cursor, recovery status, and latest result.',
        'text.backfill_danger_body': 'This button clears activity-derived data and lets us rebuild from source logs. You must type the confirmation phrase every time.',
        'text.bot_log_reset_warning': 'This action clears message, activity, voice session, summary, and activity-derived wallet ledger data before rebuilding from bot logs.',
        'text.channels_subtitle': 'Discord-like channel inventory grouped by category with sticky section headers while scrolling.',
        'text.data_from_db': 'This page is reading the latest DB data and will return to live mode when the gateway heartbeat comes back.',
        'text.discord_action_guard': 'Every action checks dashboard permission, Discord hierarchy, and bot hierarchy before calling the Discord API.',
        'text.earn_subtitle': 'Configure automatic Coin and GachaTicket rewards. The worker checks every 5 minutes and prevents duplicate grants with reward events.',
        'text.earn_manual_subtitle': 'Grant wallet units manually while recording reward events, wallet ledgers, and staff logs every time.',
        'text.earn_manual_audit_header': 'Auditable by design',
        'text.earn_manual_audit_body': 'Every grant appears in Reward Report as Manual grant with before/after balances in the wallet ledger.',
        'text.gacha_campaign_counter_display': 'This switch only changes the game HUD. Spin counting and reports continue normally.',
        'text.gacha_campaign_subtitle': 'Control campaign counter visibility on the game page while the system keeps counting in the background.',
        'text.gacha_condition_subtitle': 'Preview active conditions and rates.',
        'text.gacha_item_subtitle': 'Item prizes shown on shelves and prizes.php. Click a row to expand and edit.',
        'text.gacha_prize_subtitle': 'Configure spin credits, gacha tier rates, prizes, pick-date endings, and Discord role prizes for the Gachapon game.',
        'text.gacha_report_subtitle': 'Review every server-locked spin with status, spend, tier, prize, and per-phase timing.',
        'text.gacha_role_subtitle': 'Discord role prizes are separate from item prizes and shown as one list on prizes.php.',
        'text.gacha_tier_subtitle': 'Internal rate is used for actual draws; Public rate is shown on the prize page.',
        'text.logs_subtitle': 'All system actions, rejected actions, sensitive reads, exports, and Discord write audit reasons.',
        'text.members_subtitle': 'Member list, role summary, activity, economy, and per-user history.',
        'text.messages_subtitle': 'One archive page with heavy filters for messages, deleted, edited, attachments, replies, reactions, and polls.',
        'text.reward_report_subtitle': 'Review every reward unit earned or spent, including free-spin entitlements, with source and post-transaction ledger balance.',
        'text.earn_manual_subtitle': 'Grant wallet units manually while recording reward events, wallet ledgers, and staff logs every time.',
        'text.earn_manual_audit_header': 'Auditable by design',
        'text.earn_manual_audit_body': 'Every grant appears in Reward Report as Manual grant with before/after balances in the wallet ledger.',
        'text.reward_report_storage_header': 'What the system stores now',
        'text.reward_report_storage_body': 'tbl_reward_event stores each reward grant, and tbl_shop_wallet_ledger stores the movement history for every unit in the new shared wallet.',
        'text.reward_report_window_note': 'For text/voice rewards, this report shows the rule accumulation window, such as hour 1-2 or active minute 10-20, plus the accumulated progress at grant time. It is not a wall-clock hour because the system stores daily summaries.',
        'source.earn_text_active_daily': 'Text active daily',
        'source.earn_voice_hourly': 'Voice hourly',
        'source.earn_voice_10min_free_spin': 'Voice 10-minute free spin',
        'source.earn_member_first_join': 'First join',
        'source.earn_invite_member': 'Invite member',
        'source.earn_manual': 'Manual grant',
        'source.gacha_spin': 'Gacha spin',
        'source.gacha_spin_refund': 'Gacha refund',
        'source.gacha_reset_mock_credit': 'Credit reset',
        'source.voice': 'voice',
        'source.text': 'text',
        'source.first_join': 'first join',
        'source.invite_success': 'invite success',
        'reward_window.voice_segment': 'Voice hour segment {segment}',
        'reward_window.text_segment': 'Active segment {segment}',
        'reward_window.hour_range': 'Hour {start}-{end}',
        'reward_window.duration_range': '{start} - {end}',
        'reward_window.minute_range': 'Minute {start}-{end}',
        'role_duration.permanent': 'Permanent',
        'role_duration.days': '{days} days',
        'role_duration.left_days': '{days} days left',
        'role_duration.ended': 'Ended',
        'role_duration.no_expire': 'No expiration',
        'status.not_role_prize': 'Not a role',
        'status.no_grant_log': 'No grant log',
        'status.waiting_round_complete': 'Waiting for round',
        'text.roles_subtitle': 'Manage roles, hierarchy, permissions, member counts, and role details.',
        'text.permission_subtitle': 'Configure who can enter the dashboard and which pages each tier can see or manage.',
        'text.permission_guard_header': 'Server-side permission guard',
        'text.permission_guard_body': 'The menu is only navigation; the page loader and sensitive endpoints check session/admin permissions every time.',
        'text.historical_source_bot_logs': 'Historical source: bot logs',
        'text.historical_context_notice': 'Viewing data as of {asOf}. Snapshot/fallback badges show which fields are truly historical and which are current fallbacks.',
        'toast.backfill_failed': 'Backfill failed',
        'toast.earn_saved': 'Earn settings saved',
        'toast.gacha_image_uploaded': 'Image uploaded. Remember to Save Settings.',
        'toast.gacha_saved': 'Gachapon settings saved',
        'toast.earn_manual_granted': 'Manual Earn granted',
        'toast.permission_saved': 'Permission saved',
        'toast.shop_saved': 'Shop setting saved',
        'toast.hide_session_done': 'Voice session hidden',
        'toast.hide_session_failed': 'Hide session failed',
        'toast.page_load_failed': 'Page failed to load',
        'toast.queue_failed': 'Queue failed',
        'toast.role_action_failed': 'Role action failed',
        'toast.role_delete_failed': 'Role delete failed',
        'toast.earn_save_failed': 'Earn settings save failed',
        'toast.save_failed': 'Save failed',
        'toast.upload_failed': 'Upload failed',
        'tooltip.time_step_back': 'Step back by one slot at the selected resolution',
        'tooltip.time_step_forward': 'Step forward by one slot at the selected resolution',
      },
    },
    init() {
      const root = $('#appRoot');
      if (!root.length) return;
      this.baseUrl = root.data('base-url');
      this.csrf = root.data('csrf');
      this.page = root.data('initial-page') || 'activity';
      this.viewerToken = this.ensureViewerToken();
      document.documentElement.dataset.theme = localStorage.getItem('orbit.theme') || 'light';
      document.documentElement.dataset.lang = localStorage.getItem('orbit.lang') || 'th';
      document.documentElement.lang = document.documentElement.dataset.lang;
      $('.ui.dropdown').dropdown();
      $('#sidebarAccordion').accordion({ exclusive: false });
      $('#navToggleAll').on('click', () => this.toggleNavGroups());
      $('[data-page]').on('click', (e) => this.go($(e.currentTarget).data('page')));
      $('[data-page-jump]').on('click', (e) => this.go($(e.currentTarget).data('page-jump')));
      $('#themeToggle').on('click', () => this.toggleTheme());
      $('#languageToggle').on('click', () => this.toggleLanguage());
      $('#logoutButton').on('click', () => this.logout());
      $('#drawerClose,#drawerBackClose').on('click', () => this.closeDrawer());
      $('#drawerModeToggle').on('click', () => this.toggleDrawerMode());
      $(document).on('keydown', (e) => {
        if (e.key === 'Escape') {
          this.closeDrawer();
          $('.ui.modal.visible').modal('hide');
          $('#profilePopover').hide();
        }
      });
      $(document).on('click', '.orbit-user', (e) => {
        e.stopPropagation();
        const id = $(e.currentTarget).data('user-id');
        if (id) this.openMember(id, this.userContextFromElement(e.currentTarget));
      });
      $(document).on('mouseenter', '.orbit-user', (e) => this.showUserCard(e.currentTarget));
      $(document).on('mouseleave', '.orbit-user', () => $('#profilePopover').hide());
      window.addEventListener('popstate', () => this.loadPage(new URLSearchParams(location.search).get('page') || 'activity', false));
      window.addEventListener('pagehide', () => this.closeLiveViewer());
      window.addEventListener('beforeunload', () => this.closeLiveViewer());
      this.durationTimer = window.setInterval(() => this.updateLiveDurations(), 1000);
      this.updateThemeIcon();
      this.applyLanguage();
      this.loadPage(this.page, false);
    },
    api(url, data, method) {
      return $.ajax({
        url: this.baseUrl + url,
        method: method || 'GET',
        data: method === 'POST' ? JSON.stringify(data || {}) : data,
        contentType: method === 'POST' ? 'application/json' : undefined,
        headers: { 'X-CSRF-Token': this.csrf },
      });
    },
    apiForm(url, formData) {
      return $.ajax({
        url: this.baseUrl + url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-Token': this.csrf },
      });
    },
    go(page) {
      this.loadPage(page, true);
    },
    loadPage(page, push) {
      if (this.livePollTimer) {
        clearTimeout(this.livePollTimer);
        this.livePollTimer = null;
      }
      this.livePollInFlight = false;
      this.livePollToken = '';
      this.closeDrawer();
      this.page = page || 'activity';
      $('#pageContainer').html(`<div class="orbit-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> ${this.t('common.loading')}</div>`);
      $('.orbit-nav-item').removeClass('active');
      $('[data-page="' + this.page + '"]').addClass('active');
      $('#pageTitle').text(this.title(this.page));
      if (push) history.pushState({ page: this.page }, '', '?page=' + encodeURIComponent(this.page));
      this.api('/api/page/load.php', { page: this.page }).done((res) => {
        $('#pageContainer').html(res.html);
        $('.ui.dropdown').dropdown();
        $('.ui.checkbox').checkbox();
        this.normalizeVisibleCheckboxes('#pageContainer');
        this.bindCommon();
        this.applyLanguage();
        this.bootView(this.page);
        this.startLiveCheck(this.page);
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || this.t('toast.page_load_failed', {}, 'Page failed to load')));
    },
    bindCommon() {
      $('[data-filter-apply]').off('click').on('click', () => {
        if (this.page === 'activity') {
          this.state.activityPage = 1;
        }
        if (this.page === 'gacha_report') {
          this.state.gachaReportPage = 1;
        }
        if (this.page === 'reward_report') {
          this.state.rewardReportPage = 1;
        }
        if (this.page === 'shop_report') {
          this.state.shopReportPage = 1;
        }
        if (this.page === 'shop_member_bags') {
          this.state.shopMemberBagsPage = 1;
        }
        if (this.page === 'bag_transaction_report') {
          this.state.bagTransactionReportPage = 1;
        }
        this.bootView(this.page);
      });
      $('#activityFilter input').off('keydown.activityFilter').on('keydown.activityFilter', (e) => {
        if (e.key !== 'Enter') return;
        this.state.activityPage = 1;
        this.loadActivity();
      });
      $('#gachaReportFilter input').off('keydown.gachaReportFilter').on('keydown.gachaReportFilter', (e) => {
        if (e.key !== 'Enter') return;
        this.state.gachaReportPage = 1;
        this.loadGachaReport();
      });
      $('#rewardReportFilter input').off('keydown.rewardReportFilter').on('keydown.rewardReportFilter', (e) => {
        if (e.key !== 'Enter') return;
        this.state.rewardReportPage = 1;
        this.loadRewardReport();
      });
      $('#shopReportFilter input').off('keydown.shopReportFilter').on('keydown.shopReportFilter', (e) => {
        if (e.key !== 'Enter') return;
        this.state.shopReportPage = 1;
        this.loadShopReport();
      });
      $('#shopMemberBagsFilter input').off('keydown.shopMemberBagsFilter').on('keydown.shopMemberBagsFilter', (e) => {
        if (e.key !== 'Enter') return;
        this.state.shopMemberBagsPage = 1;
        this.loadShopMemberBags();
      });
      $('#bagTransactionReportFilter input').off('keydown.bagTransactionReportFilter').on('keydown.bagTransactionReportFilter', (e) => {
        if (e.key !== 'Enter') return;
        this.state.bagTransactionReportPage = 1;
        this.loadBagTransactionReport();
      });
      $('[data-refresh-view]').off('click').on('click', (e) => {
        e.preventDefault();
        this.bootView(this.page);
      });
      $('[data-page-jump]').off('click').on('click', (e) => this.go($(e.currentTarget).data('page-jump')));
      $('[data-run-backfill]').off('click').on('click', (e) => this.runBackfill($(e.currentTarget).data('run-backfill'), 'run'));
      $('[data-backfill-command]').off('click').on('click', (e) => {
        const button = $(e.currentTarget);
        const type = button.data('backfill-type');
        const command = button.data('backfill-command');
        if (['reset_system', 'reset_rewards', 'reset_gachapon'].includes(command)) {
          this.confirmDangerBackfill(type, command);
          return;
        }
        this.runBackfill(type, command);
      });
      $('[data-enqueue-job]').off('click').on('click', (e) => this.enqueueJob($(e.currentTarget).data('enqueue-job')));
      $('[data-queue-maintenance]').off('click').on('click', () => this.queueMaintenance());
      $('[data-export]').off('click').on('click', (e) => window.location = this.baseUrl + '/api/export/export.php?type=' + encodeURIComponent($(e.currentTarget).data('export')));
      this.bindToggleGroups();
    },
    startLiveCheck(page) {
      if (!page) return;
      const token = `${page}:${Date.now()}:${Math.random().toString(16).slice(2)}`;
      this.livePollToken = token;
      const pollDelay = page === 'gacha_shop' ? 15000 : (String(page).includes('report') ? 10000 : 6000);
      let active = true;
      const schedule = (delay = pollDelay) => {
        if (!active || this.page !== page || this.livePollToken !== token) return;
        this.livePollTimer = window.setTimeout(() => tick(false), delay);
      };
      const tick = (isInitial) => {
        if (!active || this.page !== page || this.livePollToken !== token) return;
        if (document.hidden) {
          schedule();
          return;
        }
        if (this.livePollInFlight) {
          schedule(Math.max(2500, Math.floor(pollDelay / 2)));
          return;
        }
        this.livePollInFlight = true;
        this.api('/api/live/check.php', {
          pageKey: page,
          viewerToken: this.viewerToken,
          lastSeenLiveUpdateId: this.pageLiveVersions[page] || 0,
          path: location.pathname + location.search,
          visibility: document.visibilityState || 'visible',
        }).done((res) => {
          const state = res.state || {};
          this.updateSystemState(res.system || {});
          const current = Number(state.currentLiveUpdateId || 0);
          if (isInitial && this.pageLiveVersions[page] == null) {
            this.pageLiveVersions[page] = current;
            return;
          }
          if (state.hasUpdate) {
            this.pageLiveVersions[page] = current;
            if (this.page === page) {
              this.bootView(page);
            }
          }
        }).fail((xhr) => {
          if (xhr && xhr.status === 401 && this.livePollTimer) {
            active = false;
            clearTimeout(this.livePollTimer);
            this.livePollTimer = null;
          }
        }).always(() => {
          this.livePollInFlight = false;
          schedule();
        });
      };

      tick(true);
    },
    updateSystemState(system) {
      const banner = $('#degradedBanner');
      const gateway = system.gateway || {};
      if (system.isDegraded) {
        const lastSeen = gateway.lastSeenDate || 'never';
        const age = gateway.ageSeconds == null ? '-' : `${gateway.ageSeconds}s`;
        banner.removeAttr('hidden').html(`<i class="fa-solid fa-triangle-exclamation"></i> ${this.t('state.degraded')} · gateway ${this.esc(lastSeen)} (${this.esc(age)}) · ${this.t('text.data_from_db')}`);
        $('#syncStatus').removeClass('success').addClass('failed').html(`<i class="fa-solid fa-circle-exclamation"></i> ${this.t('state.degraded')}`);
        return;
      }

      banner.attr('hidden', true).empty();
      $('#syncStatus').removeClass('failed').addClass('success').html(`<i class="fa-solid fa-circle"></i> ${this.t('state.live')}${gateway.ageSeconds != null ? ' ' + gateway.ageSeconds + 's' : ''}`);
    },
    bootView(page) {
      const map = {
        roles: this.loadRoles,
        messages: this.loadMessages,
        activity: this.loadActivity,
        gacha_prize: this.loadGachaPrize,
        gacha_campaign: this.loadGachaCampaign,
        gacha_report: this.loadGachaReport,
        gacha_shop: this.loadGachaShop,
        earn_settings: this.loadEarnSettings,
        earn_manual: this.loadEarnManual,
        reward_report: this.loadRewardReport,
        shop_report: this.loadShopReport,
        shop_member_bags: this.loadShopMemberBags,
        bag_transaction_report: this.loadBagTransactionReport,
        admin: this.loadAdmin,
        permission: this.loadPermission,
        backfill: this.loadBackfill,
        logs: this.loadLogs,
      };
      (map[page] || map.activity).call(this);
    },
    renderEventTimelineTable(selector, rows, opts) {
      const asOf = opts?.asOf || '';
      const historicalContext = Boolean(opts?.historicalContext);
      this.table(selector, [
        ['Time', 'eventDate'],
        ['Event', 'eventType', (r) => this.badge(r.eventType, this.eventBadgeTone(r.eventType))],
        ['User', 'displayName', (r) => this.eventActorCell(r, { asOf, historicalContext })],
        ['Channel', 'channelName', (r) => r.channelName ? this.esc(r.channelName) : '-'],
        ['common.ref1', 'ref1', (r) => this.renderEventRefCell(r, 'ref1')],
        ['common.ref2', 'ref2', (r) => this.renderEventRefCell(r, 'ref2')],
        ['common.detail', 'detailSummary', (r) => this.renderEventDetailCell(r)],
      ], rows, { id: opts?.id || selector.replace('#', '') });
    },
    eventBadgeTone(eventType) {
      const value = String(eventType || '');
      if (/ban|kick|timeout|delete|disconnect|leave/.test(value)) return 'failed';
      if (/join|create|invite|video|stream|typing/.test(value)) return 'success';
      return 'partial';
    },
    eventActorCell(row, context) {
      if (row.userId) {
        return this.userCell(row, 'userId', context?.historicalContext ? { historicalContext: true, asOf: context.asOf } : null);
      }
      const label = row.eventActorLabel || row.sourceFooterText || row.sourceAuthorName || 'System';
      const display = label === 'System' ? this.t('common.system', {}, 'System') : label;
      return `<div class="orbit-table-meta"><strong>${this.esc(display)}</strong><span class="orbit-user-sub">${this.esc(row.targetType || 'system')}</span></div>`;
    },
    renderEventRefCell(row, key) {
      const label = row?.[`${key}Label`] || key;
      const value = row?.[key] || '';
      if (!value) return '-';
      return `<div class="orbit-table-meta"><strong>${this.esc(value)}</strong><span class="orbit-user-sub">${this.esc(label)}</span></div>`;
    },
    renderEventDetailCell(row) {
      const detailFields = Array.isArray(row?.detailFields) ? row.detailFields : [];
      const sourceTitle = row?.sourceTitle || '';
      const sourceDescription = row?.sourceDescription || row?.detailSummary || '';
      const footerText = row?.sourceFooterText || '';
      const extras = detailFields.slice(0, 4).map((field) => `
        <div class="orbit-event-field">
          <strong>${this.esc(field.label || '')}</strong>
          <span>${this.esc(field.value || '')}</span>
        </div>
      `).join('');
      return `
        <div class="orbit-event-detail">
          ${sourceTitle ? `<div class="orbit-event-title">${this.esc(sourceTitle)}</div>` : ''}
          ${extras}
          ${sourceDescription ? `<div class="orbit-user-sub">${this.esc(this.trunc(sourceDescription, 220))}</div>` : ''}
          ${footerText ? `<div class="orbit-user-sub">${this.esc(footerText)}</div>` : ''}
          ${row?.metadataPreview ? `<div class="orbit-user-sub">${this.esc(row.metadataPreview)}</div>` : ''}
        </div>
      `;
    },
    loadRoles() {
      this.api('/api/data/roles.php').done((res) => {
        this.state.roles = res.roles || [];
        this.state.roleCatalog = JSON.parse(JSON.stringify(res.roleCatalog || { series: [], roles: [] }));
        this.state.roleTierOptions = Array.isArray(res.tierOptions) ? res.tierOptions : ['S', 'A', 'B', 'C'];
        this.state.rolePermissionDescriptions = JSON.parse(JSON.stringify(res.permissionDescriptions?.permissions || []));
        this.state.rolePermissionDescriptionDefaults = JSON.parse(JSON.stringify(res.permissionDescriptions?.defaults || []));
        const permissionSelect = $('#rolesFilter [name="permission"]');
        if (permissionSelect.length) {
          permissionSelect.find('option:not(:first)').remove();
          const seen = {};
          (res.roles || []).forEach((r) => (r.permissionsDecoded || []).forEach((p) => {
            if (!seen[p.code]) {
              seen[p.code] = true;
              permissionSelect.append(`<option value="${this.esc(p.code)}">${this.esc(p.label)}</option>`);
            }
          }));
          permissionSelect.data('filled', true).dropdown('refresh');
        }
        const filters = this.formParams('#rolesFilter');
        let rows = res.roles || [];
        if (filters.q) {
          const q = String(filters.q).toLowerCase();
          rows = rows.filter((r) => String(r.roleName || '').toLowerCase().includes(q) || String(r.roleId || '').includes(q));
        }
        if (filters.managed === '0' || filters.managed === '1') rows = rows.filter((r) => String(r.isManaged) === filters.managed);
        if (filters.permission) rows = rows.filter((r) => (r.permissionsDecoded || []).some((p) => p.code === filters.permission));
        const managed = rows.filter((r) => Number(r.isManaged) === 1).length;
        this.metrics('#rolesMetrics', [
          ['Roles', rows.length],
          ['Managed', managed],
          ['Manual', rows.length - managed],
          ['Series', (this.state.roleCatalog?.series || []).length],
          ['Members Assigned', rows.reduce((sum, r) => sum + Number(r.memberCount || 0), 0)],
          ['Admin Roles', rows.filter((r) => (r.permissionsDecoded || []).some((p) => p.code === 'ADMINISTRATOR')).length],
          ['Bot Highest', res.hierarchy?.botHighestPosition || 0],
        ]);
        this.table('#rolesTable', [
          ['Position', 'rolePosition'],
          ['Role', 'roleName', (r) => this.roleCell(r)],
          ['Tier', 'roleTier', (r) => r.roleTier ? this.badge(`Tier ${r.roleTier}`, 'partial') : '-'],
          ['Series', 'roleSeriesName', (r) => r.roleSeriesName ? `<span class="orbit-user-sub">${this.esc(r.roleSeriesName)}${r.roleSeriesBadge ? ` · ${this.esc(r.roleSeriesBadge)}` : ''}</span>` : '-'],
          ['Members', 'memberCount'],
          ['Permissions', 'permissionCount', (r) => `${Number(r.permissionCount || 0)} <span class="orbit-user-sub">${this.esc((r.permissionsDecoded || []).slice(0, 3).map(p => p.label).join(', '))}</span>`],
          ['Managed', 'isManaged', (r) => r.isManaged == 1 ? this.t('common.yes') : this.t('common.no')],
          ['Mentionable', 'isMentionable', (r) => r.isMentionable == 1 ? this.t('common.yes') : this.t('common.no')],
          ['Hoist', 'isHoist', (r) => r.isHoist == 1 ? this.t('common.yes') : this.t('common.no')],
          ['Actions', 'roleId', (r) => `<button class="ui tiny button" data-role-catalog="${r.roleId}" title="ตั้งค่า Tier / Series"><i class="fa-solid fa-layer-group"></i></button>`]
        ], rows, { id: 'rolesTable', rowClick: (r) => this.openRole(r.roleId) });
        $('[data-role-catalog]').off('click').on('click', (e) => {
          e.stopPropagation();
          this.openRoleCatalogModal(res.roles.find(r => r.roleId == $(e.currentTarget).data('role-catalog')));
        });
        this.renderRoleSeriesTab();
        this.renderRolePermissionDescriptions(res.permissionDescriptions?.metrics || {});
        this.bindTabs('#pageContainer');
      });
    },
    renderRoles() {
      if (this.page === 'roles') this.loadRoles();
    },
    loadMessages() {
      const params = this.formParams('#messagesFilter');
      params.page = this.state.messagesPage || 1;
      const sort = this.sortState.messagesTable;
      if (sort) {
        params.sort = sort.key;
        params.dir = sort.dir;
      }
      this.api('/api/data/messages.php', params).done((res) => {
        const notice = $('#messagesNotice');
        if (res.diagnostics?.messageContentLikelyUnavailable) {
          notice.html(`
            <div class="ui warning message">
              <div class="header">Discord is withholding message content from the gateway payload</div>
              <p>Recent <code>MESSAGE_CREATE</code> events are arriving with empty <code>content</code> and empty <code>attachments</code>. Check the app's <code>Message Content Intent</code> in the Discord Developer Portal, then restart the gateway worker.</p>
            </div>
          `);
        } else {
          notice.empty();
        }
        const channelSelect = $('#messagesFilter [name="channelId"]');
        if (!channelSelect.data('filled')) {
          res.channels.forEach((c) => channelSelect.append(`<option value="${c.channelId}"># ${this.esc(c.channelName)}</option>`));
          channelSelect.data('filled', true).dropdown('refresh');
        }
        this.table('#messagesTable', [
          ['Time', 'messageCreateDate'], ['Channel', 'channelName', (r) => '#' + this.esc(r.channelName || '-')], ['Author', 'authorName', (r) => this.userCell(r, 'authorUserId')],
          ['Content', 'contentText', (r) => this.renderMessageArchiveContent(r)],
          ['Flags', 'isDelete', (r) => `${r.isDelete == 1 ? this.badge('deleted', 'failed') : ''} ${r.isEdited == 1 ? this.badge('edited', 'partial') : ''} ${r.attachmentCount > 0 ? this.badge('files ' + r.attachmentCount, '') : ''}`],
        ], res.rows, {
          id: 'messagesTable',
          rowClick: (r) => this.openMessage(r.messageId),
          onSort: () => {
            this.state.messagesPage = 1;
            this.loadMessages();
          },
        });
        this.pager('#messagesPager', res, (p) => { this.state.messagesPage = p; this.loadMessages(); });
      });
    },
    renderMessageArchiveContent(row) {
      const content = row.archiveContentText || row.contentText || '';
      const embedSummary = this.messageEmbedSummary(row);
      const attachmentPreview = this.renderAttachmentPreview(row, { compact: true });
      if (row.isDelete == 1 && !content && !attachmentPreview) {
        return `<span class="orbit-message-content deleted"><span class="orbit-muted">${this.t('message.deleted_stub', {}, 'deleted message stub · archived content unavailable')}</span></span>`;
      }
      const textHtml = content
        ? `<span class="orbit-message-content ${row.isDelete == 1 ? 'deleted' : ''}">${this.esc(this.trunc(content, 180))}</span>`
        : (embedSummary
            ? `<span class="orbit-message-content embed-only">${embedSummary}</span>`
        : (!attachmentPreview
            ? `<span class="orbit-message-content ${row.isDelete == 1 ? 'deleted' : ''}">${this.esc(this.t('message.no_text_content', {}, '(no text content)'))}</span>`
            : ''));
      return `${textHtml}${attachmentPreview}`;
    },
    messageEmbedSummary(row) {
      const metadata = this.parseJson(row.metadataJson);
      const embed = Array.isArray(metadata?.embeds) ? metadata.embeds.find((item) => item && (item.title || item.description || item.fields)) : null;
      if (!embed) return '';
      const parts = [];
      if (embed.title) parts.push(String(embed.title));
      if (embed.description) parts.push(String(embed.description).replace(/\s+/g, ' ').trim());
      if (Array.isArray(embed.fields)) {
        embed.fields.slice(0, 2).forEach((field) => {
          const name = String(field?.name || '').trim();
          const value = String(field?.value || '').replace(/\s+/g, ' ').trim();
          if (name || value) parts.push(`${name}${name && value ? ': ' : ''}${value}`);
        });
      }
      return parts.length ? `<span class="orbit-muted">embed</span> ${this.esc(this.trunc(parts.join(' · '), 180))}` : '';
    },
    renderAttachmentPreview(row, opts) {
      const attachmentId = row.firstAttachmentId || row.messageAttachmentId;
      if (!attachmentId) return '';
      const contentType = String(row.firstAttachmentContentType || row.contentType || '');
      const fileName = row.firstAttachmentFileName || row.fileName || row.attachmentId || 'attachment';
      const url = `${this.baseUrl}/api/file/attachment.php?id=${encodeURIComponent(attachmentId)}`;
      if (contentType.startsWith('image/')) {
        return `
          <a class="orbit-attachment-preview ${opts?.compact ? 'compact' : ''}" href="${url}" target="_blank" rel="noopener">
            <img src="${url}" alt="${this.escAttr(fileName)}" loading="lazy">
          </a>`;
      }
      return `<a class="ui tiny basic label" href="${url}" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip"></i> ${this.esc(fileName)}</a>`;
    },
    renderAttachmentGallery(attachments) {
      attachments = attachments || [];
      if (!attachments.length) {
        return this.empty('ไม่มีไฟล์แนบ');
      }
      return `<div class="orbit-attachment-grid">${attachments.map((a) => {
        const url = `${this.baseUrl}/api/file/attachment.php?id=${encodeURIComponent(a.messageAttachmentId)}`;
        const contentType = String(a.contentType || '');
        const fileName = a.fileName || a.attachmentId || 'attachment';
        if (contentType.startsWith('image/')) {
          return `<a class="orbit-attachment-card" href="${url}" target="_blank" rel="noopener"><img src="${url}" alt="${this.escAttr(fileName)}" loading="lazy"><span>${this.esc(fileName)}</span></a>`;
        }
        return `<a class="orbit-attachment-card file" href="${url}" target="_blank" rel="noopener"><span><i class="fa-solid fa-paperclip"></i> ${this.esc(fileName)}</span></a>`;
      }).join('')}</div>`;
    },
    loadActivity() {
      const params = this.formParams('#activityFilter');
      params.page = this.state.activityPage || 1;
      this.api('/api/data/activity.php', params).done((res) => {
        const eventTypeSelect = $('#activityFilter [name="eventType"]');
        if (!eventTypeSelect.data('filled')) {
          (res.eventTypes || []).forEach((row) => eventTypeSelect.append(`<option value="${this.escAttr(row.eventType)}">${this.esc(row.eventType)} · ${Number(row.total || 0).toLocaleString()}</option>`));
          eventTypeSelect.data('filled', true).dropdown('refresh');
        }
        this.renderEventTimelineTable('#activityTimelineTable', res.timeline || [], { id: 'activityTimelineTable' });
        this.table('#activityEventTable', [['Event', 'eventType'], ['24h', 'total']], res.events);
        this.pager('#activityTimelinePager', res, (p) => { this.state.activityPage = p; this.loadActivity(); });
      });
    },
    loadGachaPrize() {
      this.api('/api/data/gacha_prize.php').done((res) => {
        this.state.gachaConfig = res.config || {};
        this.state.gachaRoles = res.roles || [];
        this.state.gachaAssets = res.assets || [];
        this.metrics('#gachaMetrics', [
          ['Tiers', res.metrics?.tiers],
          ['Prizes', res.metrics?.prizes],
          ['Item Prizes', res.metrics?.itemPrizes],
          ['Role Prizes', res.metrics?.rolePrizes],
          ['Templates', res.metrics?.templates],
          ['Assets', (res.assets || []).length],
          ['Active', res.metrics?.activePrizes],
          ['Visible', res.metrics?.visiblePrizes],
          ['Rate Total', res.metrics?.internalRateTotal],
        ]);
        this.renderGachaPrizeEditor(res.timeline || []);
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลด Gachapon settings ไม่สำเร็จ'));
    },
    loadGachaReport() {
      const draftFilters = { ...(this.state.gachaReportFilters || {}) };
      const domFilters = $('#gachaReportFilter').length ? this.formParams('#gachaReportFilter') : {};
      const params = { ...draftFilters, ...domFilters };
      params.page = this.state.gachaReportPage || Number(params.page || 1) || 1;
      params.pageSize = Number(params.pageSize || draftFilters.pageSize || 50) || 50;
      const sort = this.sortState.gachaReportTable;
      if (sort) {
        params.sort = sort.key;
        params.dir = sort.dir;
      }
      this.state.gachaReportFilters = { ...params };

      this.api('/api/data/gacha_report.php', params).done((res) => {
        this.state.gachaReportButtons = res.filterOptions?.buttons || [];
        this.state.gachaReportTiers = res.filterOptions?.tiers || [];
        this.fillGachaReportFilterOptions();
        this.applyGachaReportFilterState(res.filters || {});
        this.state.gachaReportFilters = { ...(res.filters || {}), pageSize: res.pageSize || params.pageSize };
        this.metrics('#gachaReportMetrics', [
          ['metric.gacha_total_spins', res.metrics?.totalSpins],
          ['metric.gacha_completed_spins', res.metrics?.completedSpins],
          ['metric.gacha_refunded_spins', res.metrics?.refundedSpins],
          ['metric.gacha_ticket_spent', res.metrics?.ticketSpent],
          ['metric.gacha_coin_spent', res.metrics?.coinSpent],
          ['metric.gacha_highest_tier_hits', res.metrics?.highestTierHits],
        ]);
        this.renderGachaReport(res.rows || []);
        this.pager('#gachaReportPager', res, (page) => {
          this.state.gachaReportPage = page;
          this.loadGachaReport();
        });
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลดรายงานกาชาไม่สำเร็จ'));
    },
    loadGachaCampaign() {
      this.api('/api/data/gacha_campaign.php').done((res) => {
        const settings = res.settings || {};
        const counter = res.counter || {};
        const root = $('[data-view="gacha-campaign"]');
        const daysInMonth = (month) => {
          const match = String(month || '').match(/^(\d{4})-(\d{2})$/);
          if (!match) return 31;
          return new Date(Number(match[1]), Number(match[2]), 0).getDate();
        };
        const normalizeReward = (reward = {}, day = null, days = null) => ({
          ...(day !== null ? { day } : {}),
          ...(days !== null ? { days } : {}),
          label: String(reward.label || (day !== null ? `วันที่ ${day}` : `ครบ ${days} วัน`)),
          note: String(reward.note || ''),
          icon: String(reward.icon || reward.image || ''),
          coin: Math.max(0, Number(reward.coin || 0)),
          gem: Math.max(0, Number(reward.gem || 0)),
          ticket: Math.max(0, Number(reward.ticket || reward.gachaTicket || 0)),
          potion: Math.max(0, Number(reward.potion || 0)),
          freeSpin: Math.max(0, Number(reward.freeSpin || reward.gachaFreeSpin || 0)),
        });
        const normalizeBannerReward = (reward = {}) => ({
          label: String(reward.label || ''),
          coin: Math.max(0, Number(reward.coin || 0)),
          gem: Math.max(0, Number(reward.gem || 0)),
          ticket: Math.max(0, Number(reward.ticket || reward.gachaTicket || 0)),
          potion: Math.max(0, Number(reward.potion || 0)),
        });
        const normalizeBanner = (banner = {}, index = 0) => ({
          id: String(banner.id || `banner-${index + 1}`).trim() || `banner-${index + 1}`,
          enabled: banner.enabled !== false,
          title: String(banner.title || 'Campaign'),
          subtitle: String(banner.subtitle || ''),
          body: String(banner.body || ''),
          image: String(banner.image || banner.bannerImage || ''),
          badge: String(banner.badge || 'NEW'),
          ctaLabel: String(banner.ctaLabel || 'รับรางวัล'),
          startsAt: String(banner.startsAt || ''),
          endsAt: String(banner.endsAt || ''),
          sortOrder: Math.max(0, Number(banner.sortOrder || ((index + 1) * 10))),
          reward: normalizeBannerReward(banner.reward || banner),
        });
        const normalizeBanners = (config = {}) => {
          const rawBanners = Array.isArray(config.banners) ? config.banners : [];
          return {
            enabled: config.enabled !== false,
            title: String(config.title || 'Campaign Board'),
            subtitle: String(config.subtitle || 'ดูข่าวกิจกรรมและรับของจากแบนเนอร์'),
            banners: rawBanners.map((banner, index) => normalizeBanner(banner, index)),
          };
        };
        const normalizeCheckin = (config = {}) => {
          const month = String(config.campaignMonth || new Date().toISOString().slice(0, 7));
          const count = Number(config.daysInMonth || daysInMonth(month));
          const byDay = new Map((Array.isArray(config.dailyRewards) ? config.dailyRewards : []).map((reward) => [Number(reward.day || 0), reward]));
          const byMilestone = new Map((Array.isArray(config.milestones) ? config.milestones : []).map((reward) => [Number(reward.days || 0), reward]));
          return {
            enabled: config.enabled !== false,
            campaignMonth: month,
            daysInMonth: count,
            title: String(config.title || 'เช็คอินประจำวัน'),
            subtitle: String(config.subtitle || 'รับของเล็กๆทุกวัน และสะสมครบเพื่อรับโบนัสพิเศษ'),
            dailyRewards: Array.from({ length: count }, (_, index) => normalizeReward(byDay.get(index + 1) || { coin: 5 }, index + 1, null)),
            milestones: [3, 7, 14, 28].filter((days) => days <= count).map((days) => normalizeReward(byMilestone.get(days) || { coin: days * 5, ticket: days >= 7 ? 1 : 0 }, null, days)),
          };
        };
        const rewardCard = (reward, type) => `
          <div class="ui segment" data-checkin-${type}="${this.escAttr(reward.day || reward.days)}">
            <div class="ui tiny header">${type === 'day' ? `Day ${this.esc(reward.day)}` : `${this.esc(reward.days)} days`}</div>
            <div class="two fields">
              <div class="field"><label>Label</label><input data-checkin-reward-field="label" value="${this.escAttr(reward.label || '')}"></div>
              <div class="field"><label>Note</label><input data-checkin-reward-field="note" value="${this.escAttr(reward.note || '')}"></div>
            </div>
            <div class="field"><label>Icon URL / asset path</label><input data-checkin-reward-field="icon" value="${this.escAttr(reward.icon || '')}" placeholder="images/icon_coin.png"></div>
            <div class="four fields">
              <div class="field"><label>Coin</label><input type="number" min="0" step="1" data-checkin-reward-field="coin" value="${this.escAttr(reward.coin || 0)}"></div>
              <div class="field"><label>Gem</label><input type="number" min="0" step="1" data-checkin-reward-field="gem" value="${this.escAttr(reward.gem || 0)}"></div>
              <div class="field"><label>Ticket</label><input type="number" min="0" step="1" data-checkin-reward-field="ticket" value="${this.escAttr(reward.ticket || 0)}"></div>
              <div class="field"><label>Potion</label><input type="number" min="0" step="1" data-checkin-reward-field="potion" value="${this.escAttr(reward.potion || 0)}"></div>
            </div>
            <div class="field"><label>Free Spin</label><input type="number" min="0" step="1" data-checkin-reward-field="freeSpin" value="${this.escAttr(reward.freeSpin || 0)}"></div>
          </div>`;
        const bannerCard = (banner, index) => `
          <div class="ui segment" data-banner-card="${this.escAttr(index)}">
            <div class="two fields">
              <div class="field"><label>Banner ID</label><input data-banner-card-field="id" value="${this.escAttr(banner.id || '')}" placeholder="summer-news"></div>
              <div class="field"><label>Sort</label><input type="number" min="0" step="1" data-banner-card-field="sortOrder" value="${this.escAttr(banner.sortOrder || ((index + 1) * 10))}"></div>
            </div>
            <div class="field">
              <label>เปิดแบนเนอร์นี้</label>
              <div class="ui toggle checkbox">
                <input type="checkbox" data-banner-card-field="enabled"${banner.enabled !== false ? ' checked' : ''}>
                <label>แสดงในหน้าเกมเมื่อถึงเวลา</label>
              </div>
            </div>
            <div class="two fields">
              <div class="field"><label>Title</label><input data-banner-card-field="title" value="${this.escAttr(banner.title || '')}"></div>
              <div class="field"><label>Subtitle</label><input data-banner-card-field="subtitle" value="${this.escAttr(banner.subtitle || '')}"></div>
            </div>
            <div class="field"><label>Body</label><textarea rows="3" data-banner-card-field="body">${this.esc(banner.body || '')}</textarea></div>
            <div class="two fields">
              <div class="field">
                <label>Banner image URL / asset path</label>
                <div class="ui action input">
                  <input data-banner-card-field="image" value="${this.escAttr(banner.image || '')}" placeholder="uploads/banner_campaign.png">
                  <label class="ui tiny button">
                    <i class="fa-solid fa-upload"></i> Upload banner
                    <input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-banner-image-upload="${this.escAttr(index)}" hidden>
                  </label>
                </div>
                <div class="orbit-muted" style="margin-top:6px">แนะนำ 1200x1600 px (อัตราส่วน 3:4) เว้นข้อความสำคัญไว้กลางภาพ</div>
                ${banner.image ? `<img src="${this.escAttr(this.gachaAssetUrl(banner.image))}" alt="" style="display:block;width:96px;height:128px;object-fit:cover;border-radius:14px;margin-top:8px;border:1px solid rgba(0,0,0,.08)">` : ''}
              </div>
              <div class="field"><label>Badge</label><input data-banner-card-field="badge" value="${this.escAttr(banner.badge || 'NEW')}"></div>
            </div>
            <div class="three fields">
              <div class="field"><label>CTA label</label><input data-banner-card-field="ctaLabel" value="${this.escAttr(banner.ctaLabel || 'รับรางวัล')}"></div>
              <div class="field"><label>Starts at</label><input type="datetime-local" data-banner-card-field="startsAt" value="${this.escAttr(String(banner.startsAt || '').replace(' ', 'T').slice(0, 16))}"></div>
              <div class="field"><label>Ends at</label><input type="datetime-local" data-banner-card-field="endsAt" value="${this.escAttr(String(banner.endsAt || '').replace(' ', 'T').slice(0, 16))}"></div>
            </div>
            <h5 class="ui header">Reward</h5>
            <div class="five fields">
              <div class="field"><label>Label</label><input data-banner-reward-field="label" value="${this.escAttr(banner.reward?.label || '')}"></div>
              <div class="field"><label>Coin</label><input type="number" min="0" step="1" data-banner-reward-field="coin" value="${this.escAttr(banner.reward?.coin || 0)}"></div>
              <div class="field"><label>Gem</label><input type="number" min="0" step="1" data-banner-reward-field="gem" value="${this.escAttr(banner.reward?.gem || 0)}"></div>
              <div class="field"><label>Ticket</label><input type="number" min="0" step="1" data-banner-reward-field="ticket" value="${this.escAttr(banner.reward?.ticket || 0)}"></div>
              <div class="field"><label>Potion</label><input type="number" min="0" step="1" data-banner-reward-field="potion" value="${this.escAttr(banner.reward?.potion || 0)}"></div>
            </div>
            <button class="ui tiny negative basic button" type="button" data-banner-remove="${this.escAttr(index)}"><i class="fa-solid fa-trash"></i> Remove</button>
          </div>`;
        const renderCheckin = (config = {}) => {
          const dailyCheckin = normalizeCheckin(config);
          root.find('[data-checkin-field="enabled"]').prop('checked', dailyCheckin.enabled);
          root.find('[data-checkin-field="campaignMonth"]').val(dailyCheckin.campaignMonth);
          root.find('[data-checkin-field="title"]').val(dailyCheckin.title);
          root.find('[data-checkin-field="subtitle"]').val(dailyCheckin.subtitle);
          $('#gachaCheckinDailyGrid').html(dailyCheckin.dailyRewards.map((reward) => rewardCard(reward, 'day')).join(''));
          $('#gachaCheckinMilestoneGrid').html(dailyCheckin.milestones.map((reward) => rewardCard(reward, 'milestone')).join(''));
          root.find('.ui.checkbox').checkbox('refresh');
        };
        const readRewardCard = (card, key) => {
          const row = $(card);
          return normalizeReward({
            label: row.find('[data-checkin-reward-field="label"]').val(),
            note: row.find('[data-checkin-reward-field="note"]').val(),
            icon: row.find('[data-checkin-reward-field="icon"]').val(),
            coin: row.find('[data-checkin-reward-field="coin"]').val(),
            gem: row.find('[data-checkin-reward-field="gem"]').val(),
            ticket: row.find('[data-checkin-reward-field="ticket"]').val(),
            potion: row.find('[data-checkin-reward-field="potion"]').val(),
            freeSpin: row.find('[data-checkin-reward-field="freeSpin"]').val(),
          }, key === 'day' ? Number(row.data('checkin-day') || 0) : null, key === 'milestone' ? Number(row.data('checkin-milestone') || 0) : null);
        };
        const collectCheckin = () => ({
          enabled: root.find('[data-checkin-field="enabled"]').is(':checked'),
          campaignMonth: String(root.find('[data-checkin-field="campaignMonth"]').val() || ''),
          title: String(root.find('[data-checkin-field="title"]').val() || ''),
          subtitle: String(root.find('[data-checkin-field="subtitle"]').val() || ''),
          dailyRewards: root.find('[data-checkin-day]').map((_, card) => readRewardCard(card, 'day')).get(),
          milestones: root.find('[data-checkin-milestone]').map((_, card) => readRewardCard(card, 'milestone')).get(),
        });
        const renderBanners = (config = {}) => {
          const campaignBanners = normalizeBanners(config);
          root.find('[data-banner-field="enabled"]').prop('checked', campaignBanners.enabled);
          root.find('[data-banner-field="title"]').val(campaignBanners.title);
          root.find('[data-banner-field="subtitle"]').val(campaignBanners.subtitle);
          $('#gachaBannerCards').html(campaignBanners.banners.map((banner, index) => bannerCard(banner, index)).join(''));
          root.find('.ui.checkbox').checkbox('refresh');
        };
        const collectBanners = () => ({
          enabled: root.find('[data-banner-field="enabled"]').is(':checked'),
          title: String(root.find('[data-banner-field="title"]').val() || ''),
          subtitle: String(root.find('[data-banner-field="subtitle"]').val() || ''),
          banners: root.find('[data-banner-card]').map((index, card) => {
            const row = $(card);
            return normalizeBanner({
              id: row.find('[data-banner-card-field="id"]').val(),
              enabled: row.find('[data-banner-card-field="enabled"]').is(':checked'),
              title: row.find('[data-banner-card-field="title"]').val(),
              subtitle: row.find('[data-banner-card-field="subtitle"]').val(),
              body: row.find('[data-banner-card-field="body"]').val(),
              image: row.find('[data-banner-card-field="image"]').val(),
              badge: row.find('[data-banner-card-field="badge"]').val(),
              ctaLabel: row.find('[data-banner-card-field="ctaLabel"]').val(),
              startsAt: String(row.find('[data-banner-card-field="startsAt"]').val() || '').replace('T', ' '),
              endsAt: String(row.find('[data-banner-card-field="endsAt"]').val() || '').replace('T', ' '),
              sortOrder: row.find('[data-banner-card-field="sortOrder"]').val(),
              reward: {
                label: row.find('[data-banner-reward-field="label"]').val(),
                coin: row.find('[data-banner-reward-field="coin"]').val(),
                gem: row.find('[data-banner-reward-field="gem"]').val(),
                ticket: row.find('[data-banner-reward-field="ticket"]').val(),
                potion: row.find('[data-banner-reward-field="potion"]').val(),
              },
            }, index);
          }).get(),
        });
        this.metrics('#gachaCampaignMetrics', [
          ['metric.campaign_counter_current', counter.displayValue || counter.current || 0],
          ['metric.campaign_counter_max', counter.max || 5000],
          ['metric.campaign_counter_visible', settings.campaignCounterVisible ? this.t('common.yes') : this.t('common.no')],
        ]);
        const form = $('#gachaCampaignForm');
        form.find('[name="campaignCounterVisible"]').prop('checked', settings.campaignCounterVisible !== false);
        renderCheckin(settings.dailyCheckin || {});
        renderBanners(settings.campaignBanners || {});
        root.find('[data-gacha-campaign-tab]').off('click.gachaCampaignTab').on('click.gachaCampaignTab', (event) => {
          event.preventDefault();
          const tab = String($(event.currentTarget).data('gacha-campaign-tab') || 'counter');
          root.find('[data-gacha-campaign-tab]').removeClass('active');
          $(event.currentTarget).addClass('active');
          root.find('[data-gacha-campaign-panel]').prop('hidden', true);
          root.find(`[data-gacha-campaign-panel="${tab}"]`).prop('hidden', false);
        });
        root.find('[data-checkin-field="campaignMonth"]').off('change.gachaCheckinMonth').on('change.gachaCheckinMonth', () => {
          renderCheckin(collectCheckin());
        });
        root.find('#gachaCampaignAddBanner').off('click.gachaBannerAdd').on('click.gachaBannerAdd', () => {
          const current = collectBanners();
          current.banners.push(normalizeBanner({
            id: `banner-${current.banners.length + 1}`,
            title: 'New Campaign',
            sortOrder: (current.banners.length + 1) * 10,
          }, current.banners.length));
          renderBanners(current);
        });
        root.off('click.gachaBannerRemove', '[data-banner-remove]').on('click.gachaBannerRemove', '[data-banner-remove]', (event) => {
          const removeIndex = Number($(event.currentTarget).data('banner-remove'));
          const current = collectBanners();
          current.banners = current.banners.filter((_, index) => index !== removeIndex);
          renderBanners(current);
        });
        root.off('change.gachaBannerUpload', '[data-banner-image-upload]').on('change.gachaBannerUpload', '[data-banner-image-upload]', (event) => {
          const input = event.currentTarget;
          const file = input.files?.[0];
          const uploadIndex = Number($(input).data('banner-image-upload'));
          if (!file || !Number.isFinite(uploadIndex)) return;
          const formData = new FormData();
          formData.append('image', file);
          this.apiForm('/api/admin/gacha_upload.php', formData)
            .done((res) => {
              const uploadedPath = String(res.path || '').trim();
              const current = collectBanners();
              if (uploadedPath && current.banners[uploadIndex]) {
                current.banners[uploadIndex].image = uploadedPath;
              }
              renderBanners(current);
              this.toast('toast.gacha_image_uploaded');
            })
            .fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.upload_failed'), 'error'))
            .always(() => {
              input.value = '';
            });
        });
        form.off('submit').on('submit', (event) => {
          event.preventDefault();
          this.api('/api/admin/gacha_campaign.php', {
            campaignCounterVisible: form.find('[name="campaignCounterVisible"]').is(':checked') ? 1 : 0,
            dailyCheckin: collectCheckin(),
            campaignBanners: collectBanners(),
          }, 'POST').done(() => {
            this.toast('Saved');
            this.loadGachaCampaign();
          }).fail((xhr) => this.toast(xhr.responseJSON?.message || 'Save failed', 'error'));
        });
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลด Campaign setting ไม่สำเร็จ'));
    },
    fillGachaReportFilterOptions() {
      const buttonSelect = $('#gachaReportFilter [name="buttonId"]');
      const tierSelect = $('#gachaReportFilter [name="tierId"]');
      if (buttonSelect.length) {
        const current = String(this.state.gachaReportFilters?.buttonId || buttonSelect.val() || '');
        buttonSelect.html([
          `<option value="">${this.esc(this.t('filter.all_buttons'))}</option>`,
          ...(this.state.gachaReportButtons || []).map((button) => {
            const label = `${this.esc(button.label || ('Button ' + button.buttonId))}${button.currency ? ' · ' + this.esc(button.currency) : ''}`;
            return `<option value="${this.escAttr(button.buttonId)}">${label}</option>`;
          }),
        ].join(''));
        buttonSelect.val(current).dropdown('refresh');
      }
      if (tierSelect.length) {
        const current = String(this.state.gachaReportFilters?.tierId || tierSelect.val() || '');
        tierSelect.html([
          `<option value="">${this.esc(this.t('filter.all_tiers'))}</option>`,
          ...(this.state.gachaReportTiers || []).map((tier) => `<option value="${this.escAttr(tier.id)}">${this.esc(tier.name || tier.id)}</option>`),
        ].join(''));
        tierSelect.val(current).dropdown('refresh');
      }
    },
    applyGachaReportFilterState(filters) {
      const root = $('#gachaReportFilter');
      if (!root.length) return;
      const values = {
        q: filters.q || '',
        drawId: filters.drawId || '',
        dateFrom: filters.dateFrom || '',
        dateTo: filters.dateTo || '',
        status: filters.status || '',
        currency: filters.currency || '',
        buttonId: filters.buttonId || '',
        tierId: filters.tierId || '',
        prizeType: filters.prizeType || '',
        durationKind: filters.durationKind || '',
        pageSize: String(filters.pageSize || this.state.gachaReportFilters?.pageSize || 50),
      };
      Object.entries(values).forEach(([name, value]) => {
        const field = root.find(`[name="${name}"]`);
        if (!field.length) return;
        field.val(value);
        if (field.is('select')) field.dropdown('refresh');
      });
    },
    renderGachaReport(rows) {
      this.table('#gachaReportTable', [
        ['table.time', 'startedAt'],
        ['table.user', 'displayName', (row) => this.userCell(row)],
        ['table.status', 'drawStatus', (row) => this.gachaReportStatusBadge(row.drawStatus)],
        ['table.button', 'buttonId', (row) => this.gachaReportButtonCell(row)],
        ['table.spend', 'totalCost', (row) => this.gachaReportSpendCell(row)],
        ['table.tier', 'tierName', (row) => this.gachaReportTierCell(row)],
        ['table.prize', 'prizeName', (row) => this.gachaReportPrizeCell(row)],
        ['table.role_age', 'rewardDurationLabel', (row) => this.gachaReportDurationCell(row)],
        ['table.role_grant', 'roleGrantStatus', (row) => this.gachaReportGrantCell(row)],
        ['table.counter', 'counterCurrentValue', (row) => this.gachaReportCounterCell(row)],
        ['table.finished', 'finishedAt'],
      ], rows, {
        id: 'gachaReportTable',
        rowClick: (row) => this.openGachaReportDetail(row),
        onSort: () => {
          this.state.gachaReportPage = 1;
          this.loadGachaReport();
        },
      });
    },
    gachaReportStatusBadge(status) {
      const normalized = String(status || '').toLowerCase();
      const tone = normalized === 'completed' ? 'success'
        : normalized === 'refunded' ? 'failed'
          : normalized === 'resolved' ? 'partial'
            : normalized === 'ball_seen' ? 'pending'
              : '';
      return this.badge(normalized || 'unknown', tone);
    },
    gachaReportButtonCell(row) {
      const buttonId = Number(row?.buttonId || 0);
      const meta = (this.state.gachaReportButtons || []).find((button) => Number(button.buttonId || 0) === buttonId);
      const label = meta?.label || (buttonId > 0 ? `btn_${buttonId}` : '-');
      const currency = row?.currency ? `<div class="orbit-user-sub">${this.esc(row.currency)}</div>` : '';
      return `<div><strong>${this.esc(label)}</strong>${currency}</div>`;
    },
    gachaReportSpendCell(row) {
      const totalCost = Number(row?.totalCost || 0).toLocaleString();
      const costPerSpin = Number(row?.costPerSpin || 0).toLocaleString();
      return `<div><strong>${totalCost}</strong><div class="orbit-user-sub">${this.esc(row?.currency || '-')} · x${this.esc(String(row?.count || 1))} · ${costPerSpin}/spin</div></div>`;
    },
    gachaReportTierCell(row) {
      const tierName = row?.tierName || row?.tierId || row?.lockedType || '-';
      const tierId = String(row?.tierId || row?.lockedType || '').toLowerCase();
      const tone = tierId.includes('mythic') ? 'success'
        : tierId.includes('legendary') ? 'partial'
          : tierId.includes('epic') ? 'pending'
            : '';
      return this.badge(tierName, tone);
    },
    gachaReportPrizeCell(row) {
      const primary = row?.reportPrizeName || row?.displayPrizeName || row?.prizeName || row?.prizeId || '-';
      const meta = [];
      if (row?.prizeType) meta.push(this.esc(row.prizeType));
      if (row?.prizeType === 'role' && row?.displayPrizeName && row.displayPrizeName !== primary) {
        meta.push(`alias: ${this.esc(row.displayPrizeName)}`);
      }
      if (row?.prizeType === 'role' && row?.roleId) {
        meta.push(this.esc(row.roleId));
      }
      const type = meta.length ? `<div class="orbit-user-sub">${meta.join(' · ')}</div>` : '';
      return `<div><strong>${this.esc(primary)}</strong>${type}</div>`;
    },
    gachaReportDurationCell(row) {
      const label = this.gachaReportRoleDurationLabel(row);
      const roleMetaParts = [];
      if (row?.sourceRoleName) roleMetaParts.push(this.esc(row.sourceRoleName));
      if (row?.isTemporaryRole) {
        const relative = this.gachaReportRoleExpireRelative(row?.rewardRemoveAt);
        if (relative) roleMetaParts.push(this.esc(relative));
      }
      if (row?.rewardRemoveAt) roleMetaParts.push(this.esc(row.rewardRemoveAt));
      const roleMeta = roleMetaParts.length ? `<div class="orbit-user-sub">${roleMetaParts.join(' · ')}</div>` : '';
      return `<div><strong>${this.esc(label)}</strong>${roleMeta}</div>`;
    },
    gachaReportRoleDurationLabel(row) {
      if (row?.prizeType !== 'role') return '-';
      const days = Number(row?.rewardDurationDays || 0);
      return days > 0 ? this.t('role_duration.days', { days }) : this.t('role_duration.permanent');
    },
    gachaReportRoleExpireRelative(expireAt) {
      if (!expireAt) return this.t('role_duration.no_expire');
      const ts = Date.parse(String(expireAt).replace(' ', 'T'));
      if (!Number.isFinite(ts)) return '';
      const diff = ts - Date.now();
      if (diff <= 0) return this.t('role_duration.ended');
      return this.t('role_duration.left_days', { days: Math.max(1, Math.ceil(diff / 86400000)) });
    },
    gachaReportGrantCell(row) {
      if (row?.prizeType !== 'role') {
        return `<span class="orbit-muted">${this.esc(this.t('status.not_role_prize'))}</span>`;
      }
      const status = String(row?.roleGrantStatus || '').toLowerCase();
      const label = status || (row?.drawStatus === 'completed' ? 'status.no_grant_log' : 'status.waiting_round_complete');
      const tone = status === 'granted' ? 'success'
        : status === 'revoked' ? 'partial'
          : status.includes('failed') ? 'failed'
            : 'pending';
      const details = [
        row?.roleGrantGrantedAt ? `granted ${row.roleGrantGrantedAt}` : '',
        row?.roleGrantRevokedAt ? `revoked ${row.roleGrantRevokedAt}` : '',
        row?.roleGrantLastError || '',
      ].filter(Boolean).map((part) => this.esc(part)).join(' · ');
      return `<div>${this.badge(label, tone)}${details ? `<div class="orbit-user-sub">${details}</div>` : ''}</div>`;
    },
    gachaReportCounterCell(row) {
      if (!row?.counterDisplayValue) return '-';
      return `<div><strong>${this.esc(String(row.counterDisplayValue))}</strong><div class="orbit-user-sub">${Number(row.counterCurrentValue || 0).toLocaleString()}</div></div>`;
    },
    openGachaReportDetail(row) {
      const chips = [
        this.gachaReportStatusBadge(row.drawStatus),
        row?.currency ? `<span class="orbit-json-chip">${this.esc(row.currency)}</span>` : '',
        row?.tierName ? `<span class="orbit-json-chip">${this.esc(row.tierName)}</span>` : '',
        row?.prizeType ? `<span class="orbit-json-chip">${this.esc(row.prizeType)}</span>` : '',
        row?.rewardDurationLabel ? `<span class="orbit-json-chip">${this.esc(row.rewardDurationLabel)}</span>` : '',
        row?.roleGrantStatus ? `<span class="orbit-json-chip">${this.esc(row.roleGrantStatus)}</span>` : '',
        row?.reusedPendingReward ? `<span class="orbit-json-chip">reused pending</span>` : '',
      ].filter(Boolean).join('');
      const infoItem = (label, value) => `
        <div class="orbit-metric">
          <div class="orbit-muted">${this.esc(label)}</div>
          <div class="orbit-metric-value" style="font-size:18px">${this.esc(value || '-')}</div>
        </div>
      `;
      const jsonText = row?.snapshotPretty || JSON.stringify(row?.snapshot || {}, null, 2);
      const html = `
        <div class="orbit-json-summary" style="margin-bottom:12px">${chips}</div>
        <div class="orbit-compact-grid" style="margin-bottom:12px">
          ${infoItem('Draw ID', row?.drawId || '-')}
          ${infoItem('User ID', row?.userId || '-')}
          ${infoItem('Started', row?.startedAt || '-')}
          ${infoItem('Finished', row?.finishedAt || '-')}
          ${infoItem('Spend', `${Number(row?.totalCost || 0).toLocaleString()} ${row?.currency || ''}`.trim())}
          ${infoItem('Prize', row?.reportPrizeName || row?.displayPrizeName || row?.prizeName || row?.prizeId || '-')}
          ${infoItem('Prize Display', row?.displayPrizeName || '-')}
          ${infoItem('Source Role', row?.sourceRoleName || (row?.roleId || '-'))}
          ${infoItem('Role Duration', row?.rewardDurationLabel || '-')}
          ${infoItem('Grant Status', row?.roleGrantStatus || '-')}
          ${infoItem('Remove At', row?.rewardRemoveAt || 'ถาวร')}
          ${infoItem('Grant Error', row?.roleGrantLastError || '-')}
        </div>
        <div class="orbit-section">
          <div class="orbit-section-header"><h3 class="ui header">Snapshot</h3></div>
          <div class="orbit-section-body">
            <pre class="orbit-json">${this.esc(jsonText || '{}')}</pre>
          </div>
        </div>
      `;
      this.drawer(`Spin ${row?.drawId || ''}`, html);
    },
    loadEarnSettings() {
      this.api('/api/data/earn_settings.php').done((res) => {
        this.state.earnRules = res.rules || [];
        this.state.earnCurrencies = res.currencies || [];
        this.metrics('#earnSettingsMetrics', [
          ['Rules', this.state.earnRules.length],
          ['Active', this.state.earnRules.filter((rule) => Number(rule.isActive) === 1).length],
          ['Granted Today', this.state.earnRules.reduce((sum, rule) => sum + Number(rule.grantedToday || 0), 0)],
          ['Currencies', (res.currencies || []).length],
        ]);
        this.renderEarnSettings();
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลด Earn settings ไม่สำเร็จ'));
    },
    loadRewardReport() {
      const draftFilters = { ...(this.state.rewardReportFilters || {}) };
      const domFilters = $('#rewardReportFilter').length ? this.formParams('#rewardReportFilter') : {};
      const params = { ...draftFilters, ...domFilters };
      params.page = this.state.rewardReportPage || Number(params.page || 1) || 1;
      params.pageSize = Number(params.pageSize || draftFilters.pageSize || 50) || 50;
      const sort = this.sortState.rewardReportTable;
      if (sort) {
        params.sort = sort.key;
        params.dir = sort.dir;
      }
      this.state.rewardReportFilters = { ...params };

      this.api('/api/data/reward_report.php', params).done((res) => {
        this.state.rewardReportRules = res.filterOptions?.rules || [];
        this.state.rewardReportTriggerTypes = res.filterOptions?.triggerTypes || [];
        this.state.rewardReportUnits = res.filterOptions?.units || [];
        this.fillRewardReportFilterOptions();
        this.applyRewardReportFilterState(res.filters || {});
        this.state.rewardReportFilters = { ...(res.filters || {}), pageSize: res.pageSize || params.pageSize };
        this.metrics('#rewardReportMetrics', [
          ['metric.reward_total_events', res.metrics?.totalEvents],
          ['metric.reward_granted_events', res.metrics?.grantedEvents],
          ['metric.reward_free_spin_granted', res.metrics?.freeSpinGranted],
          ['metric.reward_first_join', res.metrics?.firstJoinRewards],
          ...this.rewardReportUnitMetricCards(res.metrics?.unitMetrics || []),
        ]);
        this.renderRewardReport(res.rows || []);
        this.pager('#rewardReportPager', res, (page) => {
          this.state.rewardReportPage = page;
          this.loadRewardReport();
        });
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลด Reward report ไม่สำเร็จ'));
    },
    fillRewardReportFilterOptions() {
      const ruleSelect = $('#rewardReportFilter [name="ruleCode"]');
      const triggerSelect = $('#rewardReportFilter [name="triggerType"]');
      const unitSelect = $('#rewardReportFilter [name="unitCode"]');
      if (ruleSelect.length) {
        const current = String(this.state.rewardReportFilters?.ruleCode || ruleSelect.val() || '');
        ruleSelect.html([
          `<option value="">${this.esc(this.t('filter.all_rules'))}</option>`,
          ...(this.state.rewardReportRules || []).map((rule) => `<option value="${this.escAttr(rule.ruleCode)}">${this.esc(rule.ruleName || rule.ruleCode)}</option>`),
        ].join(''));
        ruleSelect.val(current).dropdown('refresh');
      }
      if (triggerSelect.length) {
        const current = String(this.state.rewardReportFilters?.triggerType || triggerSelect.val() || '');
        triggerSelect.html([
          `<option value="">${this.esc(this.t('filter.all_triggers'))}</option>`,
          ...(this.state.rewardReportTriggerTypes || []).map((triggerType) => `<option value="${this.escAttr(triggerType)}">${this.esc(this.rewardReportSourceLabel(triggerType))}</option>`),
        ].join(''));
        triggerSelect.val(current).dropdown('refresh');
      }
      if (unitSelect.length) {
        const current = String(this.state.rewardReportFilters?.unitCode || unitSelect.val() || '');
        unitSelect.html([
          `<option value="">${this.esc(this.t('filter.all_units'))}</option>`,
          ...(this.state.rewardReportUnits || []).map((unit) => `<option value="${this.escAttr(unit.unitCode)}">${this.esc(unit.label || unit.unitCode)}</option>`),
        ].join(''));
        unitSelect.val(current).dropdown('refresh');
      }
    },
    rewardReportUnitMetricCards(unitMetrics) {
      const receivedLabel = this.lang() === 'th' ? 'ได้รับ' : 'Received';
      const spentLabel = this.lang() === 'th' ? 'ใช้ไป' : 'Spent';
      return (unitMetrics || []).flatMap((unit) => {
        const label = unit?.label || unit?.unitCode || '';
        return [
          [`${receivedLabel} ${label}`, unit?.income],
          [`${spentLabel} ${label}`, unit?.spent],
        ];
      });
    },
    applyRewardReportFilterState(filters) {
      const root = $('#rewardReportFilter');
      if (!root.length) return;
      const values = {
        q: filters.q || '',
        dateFrom: filters.dateFrom || '',
        dateTo: filters.dateTo || '',
        ruleCode: filters.ruleCode || '',
        triggerType: filters.triggerType || '',
        status: filters.status || '',
        rewardKind: filters.rewardKind || '',
        movementType: filters.movementType || '',
        unitCode: filters.unitCode || '',
        pageSize: String(filters.pageSize || this.state.rewardReportFilters?.pageSize || 50),
      };
      Object.entries(values).forEach(([name, value]) => {
        const field = root.find(`[name="${name}"]`);
        if (!field.length) return;
        field.val(value);
        if (field.is('select')) field.dropdown('refresh');
      });
    },
    renderRewardReport(rows) {
      this.table('#rewardReportTable', [
        ['table.time', 'createDate'],
        ['table.user', 'displayName', (row) => this.userCell(row)],
        ['table.movement', 'historyKind', (row) => this.rewardReportMovementCell(row)],
        ['table.rule_source', 'ruleName', (row) => this.rewardReportRuleCell(row)],
        ['table.source', 'sourceType', (row) => this.rewardReportSourceCell(row)],
        ['table.window', 'sourceSegment', (row) => this.rewardReportWindowCell(row)],
        ['table.amount', 'rewardCoin', (row) => this.rewardReportRewardCell(row)],
        ['table.remaining', 'walletBalanceAfter', (row) => this.rewardReportRemainingCell(row)],
        ['table.status', 'rewardStatus', (row) => this.rewardReportStatusBadge(row.rewardStatus)],
        ['table.ledger', 'walletLedgerId', (row) => this.rewardReportLedgerCell(row)],
      ], rows, {
        id: 'rewardReportTable',
        rowClick: (row) => this.openRewardReportDetail(row),
        onSort: () => {
          this.state.rewardReportPage = 1;
          this.loadRewardReport();
        },
      });
    },
    loadShopReport() {
      const draftFilters = { ...(this.state.shopReportFilters || {}) };
      const domFilters = $('#shopReportFilter').length ? this.formParams('#shopReportFilter') : {};
      const params = { ...draftFilters, ...domFilters };
      params.page = this.state.shopReportPage || Number(params.page || 1) || 1;
      params.pageSize = Number(params.pageSize || draftFilters.pageSize || 50) || 50;
      const sort = this.sortState.shopReportTable;
      if (sort) {
        params.sort = sort.key;
        params.dir = sort.dir;
      }
      this.state.shopReportFilters = { ...params };

      this.api('/api/data/shop_report.php', params).done((res) => {
        this.state.shopReportUnits = res.filterOptions?.units || [];
        this.fillShopReportFilterOptions();
        this.applyShopReportFilterState(res.filters || {});
        this.state.shopReportFilters = { ...(res.filters || {}), pageSize: res.pageSize || params.pageSize };
        this.metrics('#shopReportMetrics', [
          ['Purchases', res.metrics?.totalPurchases],
          ['Self', res.metrics?.selfPurchases],
          ['Gifts', res.metrics?.gifts],
          ['Spent', res.metrics?.totalSpent],
          ...this.shopReportUnitMetricCards(res.metrics?.unitMetrics || []),
        ]);
        this.renderShopReport(res.rows || []);
        this.pager('#shopReportPager', res, (page) => {
          this.state.shopReportPage = page;
          this.loadShopReport();
        });
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลด Shop report ไม่สำเร็จ'));
    },
    fillShopReportFilterOptions() {
      const unitSelect = $('#shopReportFilter [name="unitCode"]');
      if (!unitSelect.length) return;
      const current = String(this.state.shopReportFilters?.unitCode || unitSelect.val() || '');
      unitSelect.html([
        '<option value="">ทุกหน่วย</option>',
        ...(this.state.shopReportUnits || []).map((unit) => `<option value="${this.escAttr(unit.unitCode)}">${this.esc(unit.label || unit.unitCode)}</option>`),
      ].join(''));
      unitSelect.val(current).dropdown('refresh');
    },
    applyShopReportFilterState(filters) {
      const root = $('#shopReportFilter');
      if (!root.length) return;
      const values = {
        q: filters.q || '',
        dateFrom: filters.dateFrom || '',
        dateTo: filters.dateTo || '',
        sourceType: filters.sourceType || '',
        movementType: filters.movementType || '',
        unitCode: filters.unitCode || '',
        pageSize: String(filters.pageSize || this.state.shopReportFilters?.pageSize || 50),
      };
      Object.entries(values).forEach(([name, value]) => {
        const field = root.find(`[name="${name}"]`);
        if (!field.length) return;
        field.val(value);
        if (field.is('select')) field.dropdown('refresh');
      });
    },
    shopReportUnitMetricCards(unitMetrics) {
      return (unitMetrics || []).map((unit) => [`Spent ${unit?.label || unit?.unitCode || ''}`, unit?.spent]);
    },
    renderShopReport(rows) {
      this.table('#shopReportTable', [
        ['Time', 'createDate'],
        ['Buyer', 'displayName', (row) => this.userCell(row)],
        ['Type', 'sourceType', (row) => this.shopReportTypeCell(row)],
        ['Product', 'sourceId', (row) => this.shopReportProductCell(row)],
        ['Payment', 'amountDelta', (row) => this.shopReportPaymentCell(row)],
        ['Target', 'targetDisplayName', (row) => this.shopReportTargetCell(row)],
        ['Ledger', 'shopWalletLedgerId'],
      ], rows, {
        id: 'shopReportTable',
        rowClick: (row) => this.openShopReportDetail(row),
        onSort: () => {
          this.state.shopReportPage = 1;
          this.loadShopReport();
        },
      });
    },
    shopReportTypeCell(row) {
      const sourceType = String(row?.sourceType || '');
      if (sourceType === 'shop_role_badge_gift') return this.badge('gift', 'partial');
      if (sourceType === 'shop_role_badge_purchase') return this.badge('self', 'success');
      return this.badge(sourceType || '-', '');
    },
    shopReportProductCell(row) {
      const title = row?.productName || row?.sourceId || '-';
      const sub = [row?.optionLabel, row?.roleName].filter(Boolean).join(' · ');
      return `<div><strong>${this.esc(title)}</strong>${sub ? `<div class="orbit-user-sub">${this.esc(sub)}</div>` : ''}</div>`;
    },
    shopReportPaymentCell(row) {
      const amount = Math.abs(Number(row?.amountDelta || 0)).toLocaleString();
      const unit = row?.unitShortName || row?.unitLabel || row?.unitCode || '';
      return `<strong>${this.esc(amount)} ${this.esc(unit)}</strong>`;
    },
    shopReportTargetCell(row) {
      const target = row?.targetDisplayName || row?.targetUserId || '';
      return target ? this.esc(target) : '<span class="orbit-muted">ตัวเอง</span>';
    },
    openShopReportDetail(row) {
      this.openTransactionTrace(row, {
        fallback: () => this.openShopReportMetadataDetail(row),
      });
    },
    openShopReportMetadataDetail(row) {
      const html = `
        <div class="orbit-json-summary" style="margin-bottom:12px">
          ${this.shopReportTypeCell(row)}
          <span class="orbit-json-chip">${this.esc(row?.unitCode || '')}</span>
          <span class="orbit-json-chip">${this.esc(row?.sourceId || '')}</span>
        </div>
        <pre class="orbit-json-pre">${this.esc(row?.metadataPretty || '{}')}</pre>
      `;
      this.drawer(`Shop ledger ${row?.shopWalletLedgerId || ''}`, html);
    },
    loadShopMemberBags() {
      const draftFilters = { ...(this.state.shopMemberBagsFilters || {}) };
      const domFilters = $('#shopMemberBagsFilter').length ? this.formParams('#shopMemberBagsFilter') : {};
      const params = { ...draftFilters, ...domFilters };
      params.page = this.state.shopMemberBagsPage || Number(params.page || 1) || 1;
      params.pageSize = Number(params.pageSize || draftFilters.pageSize || 50) || 50;
      const sort = this.sortState.shopMemberBagsTable;
      if (sort) {
        params.sort = sort.key;
        params.dir = sort.dir;
      }
      this.state.shopMemberBagsFilters = { ...params };

      this.api('/api/data/shop_member_bags.php', params).done((res) => {
        this.state.shopMemberBagsUnits = res.filterOptions?.units || [];
        this.state.shopMemberBagsItemTypes = res.filterOptions?.itemTypes || [];
        this.state.shopMemberBagsWalletColumns = res.walletColumns || [];
        this.fillShopMemberBagsFilterOptions();
        this.applyShopMemberBagsFilterState(res.filters || {});
        this.state.shopMemberBagsFilters = { ...(res.filters || {}), pageSize: res.pageSize || params.pageSize };
        this.metrics('#shopMemberBagsMetrics', [
          ['Members', res.metrics?.memberCount],
          ['With Wallet', res.metrics?.membersWithWallet],
          ['With Items', res.metrics?.membersWithItems],
          ['Items', res.metrics?.totalItemQuantity],
        ]);
        this.renderShopMemberBags(res.rows || []);
        this.pager('#shopMemberBagsPager', res, (page) => {
          this.state.shopMemberBagsPage = page;
          this.loadShopMemberBags();
        });
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลดกระเป๋าสมาชิกไม่สำเร็จ'));
    },
    bagTransactionReportFormState() {
      const root = $('#bagTransactionReportFilter');
      if (!root.length) {
        return { ...(this.state.bagTransactionReportFilters || {}) };
      }
      const data = {};
      ['q', 'dateFrom', 'dateTo', 'historyKind', 'direction', 'sourceType', 'unitCode', 'itemType', 'itemCode', 'pageSize']
        .forEach((name) => {
          const field = root.find(`[name="${name}"]`);
          if (!field.length) return;
          data[name] = String(field.val() ?? '');
        });
      return data;
    },
    loadBagTransactionReport() {
      const draftFilters = { ...(this.state.bagTransactionReportFilters || {}) };
      const domFilters = this.bagTransactionReportFormState();
      const params = { ...draftFilters, ...domFilters };
      params.page = this.state.bagTransactionReportPage || Number(params.page || 1) || 1;
      params.pageSize = Number(params.pageSize || draftFilters.pageSize || 50) || 50;
      const sort = this.sortState.bagTransactionReportTable;
      if (sort) {
        params.sort = sort.key;
        params.dir = sort.dir;
      }
      this.state.bagTransactionReportFilters = { ...params };

      this.api('/api/data/bag_transaction_report.php', params).done((res) => {
        this.state.bagTransactionReportUnits = res.filterOptions?.units || [];
        this.state.bagTransactionReportItemTypes = res.filterOptions?.itemTypes || [];
        this.state.bagTransactionReportSourceTypes = res.filterOptions?.sourceTypes || [];
        this.fillBagTransactionReportFilterOptions();
        this.applyBagTransactionReportFilterState(res.filters || {});
        this.state.bagTransactionReportFilters = { ...(res.filters || {}), pageSize: res.pageSize || params.pageSize };
        if ($('#bagTransactionReportHistoryNotice').length) {
          const notice = res.historyNotice || {};
          $('#bagTransactionReportHistoryNotice').html(`
            <div class="header">${this.esc(notice.title || 'Item history notice')}</div>
            <p>${this.esc(notice.body || '')}</p>
          `);
        }
        this.metrics('#bagTransactionReportMetrics', [
          ['Rows', res.metrics?.historyRows],
          ['Members', res.metrics?.members],
          ['Item Moves', res.metrics?.itemMoves],
          ['Wallet Moves', res.metrics?.walletMoves],
          ['Reward Events', res.metrics?.rewardEvents],
          ['In', res.metrics?.inflowRows],
          ['Out', res.metrics?.outflowRows],
        ]);
        this.renderBagTransactionReport(res.rows || []);
        this.pager('#bagTransactionReportPager', res, (page) => {
          this.state.bagTransactionReportPage = page;
          this.loadBagTransactionReport();
        });
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลด transaction report ไม่สำเร็จ'));
    },
    fillBagTransactionReportFilterOptions() {
      const root = $('#bagTransactionReportFilter');
      if (!root.length) return;
      const unitSelect = root.find('[name="unitCode"]');
      const itemTypeSelect = root.find('[name="itemType"]');
      const sourceTypeSelect = root.find('[name="sourceType"]');
      if (unitSelect.length) {
        const current = String(this.state.bagTransactionReportFilters?.unitCode || unitSelect.val() || '');
        unitSelect.html([
          '<option value="">ทุกหน่วย</option>',
          ...(this.state.bagTransactionReportUnits || []).map((unit) => `<option value="${this.escAttr(unit.unitCode)}">${this.esc(unit.label || unit.unitCode)}</option>`),
        ].join(''));
        unitSelect.val(current).dropdown('refresh');
      }
      if (itemTypeSelect.length) {
        const current = String(this.state.bagTransactionReportFilters?.itemType || itemTypeSelect.val() || '');
        itemTypeSelect.html([
          '<option value="">ทุกชนิดไอเทม</option>',
          ...(this.state.bagTransactionReportItemTypes || []).map((itemType) => `<option value="${this.escAttr(itemType)}">${this.esc(itemType)}</option>`),
        ].join(''));
        itemTypeSelect.val(current).dropdown('refresh');
      }
      if (sourceTypeSelect.length) {
        const current = String(this.state.bagTransactionReportFilters?.sourceType || sourceTypeSelect.val() || '');
        sourceTypeSelect.html([
          '<option value="">ทุก source</option>',
          ...(this.state.bagTransactionReportSourceTypes || []).map((sourceType) => `<option value="${this.escAttr(sourceType)}">${this.esc(this.shopBagSourceLabel(sourceType))}</option>`),
        ].join(''));
        sourceTypeSelect.val(current).dropdown('refresh');
      }
    },
    applyBagTransactionReportFilterState(filters) {
      const root = $('#bagTransactionReportFilter');
      if (!root.length) return;
      const values = {
        q: filters.q || '',
        dateFrom: this.shopBagDateTimeValue(filters.dateFrom || ''),
        dateTo: this.shopBagDateTimeValue(filters.dateTo || ''),
        historyKind: filters.historyKind || '',
        direction: filters.direction || '',
        sourceType: filters.sourceType || '',
        unitCode: filters.unitCode || '',
        itemType: filters.itemType || '',
        itemCode: filters.itemCode || '',
        pageSize: String(filters.pageSize || this.state.bagTransactionReportFilters?.pageSize || 50),
      };
      Object.entries(values).forEach(([name, value]) => {
        const field = root.find(`[name="${name}"]`);
        if (!field.length) return;
        field.val(value);
        if (field.is('select')) field.dropdown('refresh');
      });
    },
    renderBagTransactionReport(rows) {
      this.table('#bagTransactionReportTable', [
        ['Time', 'createDate'],
        ['Member', 'displayName', (row) => this.userCell(row)],
        ['Movement', 'movementDirection', (row) => this.shopBagMovementCell(row)],
        ['Source', 'sourceType', (row) => this.shopBagSourceCell(row)],
        ['Asset', 'assetLabel', (row) => this.shopBagAssetCell(row)],
        ['Delta', 'delta', (row) => this.shopBagDeltaCell(row)],
        ['Counterparty', 'counterpartyLabel', (row) => this.shopBagCounterpartyCell(row)],
        ['Note', 'note', (row) => this.shopBagNoteCell(row)],
      ], rows, {
        id: 'bagTransactionReportTable',
        rowClick: (row) => this.openBagTransactionReportDetail(row),
        onSort: () => {
          this.state.bagTransactionReportPage = 1;
          this.loadBagTransactionReport();
        },
      });
    },
    openBagTransactionReportDetail(row) {
      this.openTransactionTrace(row, {
        fallback: () => this.openBagTransactionReportMetadataDetail(row),
      });
    },
    openBagTransactionReportMetadataDetail(row) {
      const userId = String(row?.userId || '').trim();
      const movement = this.shopBagMovementCell(row);
      const source = this.shopBagSourceCell(row);
      const asset = this.shopBagAssetCell(row);
      const delta = this.shopBagDeltaCell(row);
      const counterparty = this.shopBagCounterpartyCell(row);
      const note = this.shopBagNoteCell(row);
      const userSummary = userId ? this.userCell(row) : '<span class="orbit-muted">-</span>';
      const sourceButtons = [
        userId ? '<button class="ui button" type="button" data-bag-transaction-open-bag><i class="fa-solid fa-box-open"></i> Member Bag</button>' : '',
        userId ? '<button class="ui button" type="button" data-bag-transaction-open-member><i class="fa-solid fa-user"></i> Member Profile</button>' : '',
      ].filter(Boolean).join('');

      this.drawer(`Transaction · ${this.shopBagHistoryKindLabel(row?.historyKind || '')}`, `
        <div class="orbit-bag-header">
          <div class="orbit-bag-member">${userSummary}</div>
          <div class="orbit-section-actions">${sourceButtons}</div>
        </div>
        <div class="orbit-json-summary" style="margin-bottom:12px">
          ${movement}
          <span class="orbit-json-chip">${this.esc(this.shopBagHistoryKindLabel(row?.historyKind || ''))}</span>
          <span class="orbit-json-chip">${this.esc(row?.createDate || '-')}</span>
        </div>
        <div class="orbit-bag-summary-grid">
          <div class="orbit-drawer-section">
            <h4 class="ui header">Source</h4>
            <div>${source}</div>
          </div>
          <div class="orbit-drawer-section">
            <h4 class="ui header">Asset</h4>
            <div>${asset}</div>
          </div>
          <div class="orbit-drawer-section">
            <h4 class="ui header">Delta</h4>
            <div>${delta}</div>
          </div>
          <div class="orbit-drawer-section">
            <h4 class="ui header">Counterparty</h4>
            <div>${counterparty}</div>
          </div>
        </div>
        <div class="orbit-drawer-section">
          <h4 class="ui header">Note</h4>
          <div>${note}</div>
        </div>
        <div class="orbit-drawer-section">
          <h4 class="ui header">Metadata</h4>
          <pre class="orbit-json-pre">${this.esc(row?.metadataPretty || '{}')}</pre>
        </div>
      `);
      $('[data-bag-transaction-open-bag]').off('click').on('click', () => this.openShopMemberBagDetail(row));
      $('[data-bag-transaction-open-member]').off('click').on('click', () => {
        if (userId) this.openMember(userId);
      });
    },
    transactionTraceRequest(row) {
      const transactionGroupId = String(row?.transactionGroupId || '').trim();
      const historyKind = String(row?.traceHistoryKind || row?.historyKind || '').trim();
      const historyId = Number(
        row?.historyId
        || row?.rewardEventId
        || row?.walletLedgerId
        || row?.shopWalletLedgerId
        || row?.shopInventoryLedgerId
        || row?.gachaRoleGrantId
        || 0
      ) || 0;
      if (transactionGroupId) {
        return { transactionGroupId };
      }
      return historyKind && historyId > 0 ? { historyKind, historyId } : {};
    },
    openTransactionTrace(row, options = {}) {
      const request = this.transactionTraceRequest(row);
      if (!request.transactionGroupId && (!request.historyKind || !request.historyId)) {
        if (typeof options.fallback === 'function') {
          options.fallback();
        }
        return;
      }
      this.drawer(options.title || 'Transaction Trace', `<div class="orbit-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> ${this.t('common.loading')}</div>`);
      this.api('/api/data/bag_transaction_trace.php', request).done((res) => {
        this.renderTransactionTraceDrawer(res);
      }).fail((xhr) => {
        if (typeof options.fallback === 'function') {
          options.fallback();
          return;
        }
        this.drawer('Transaction Trace', `<div class="orbit-error"><i class="fa-solid fa-triangle-exclamation"></i> ${this.esc(xhr.responseJSON?.message || 'โหลด transaction trace ไม่สำเร็จ')}</div>`);
      });
    },
    transactionTraceConfidenceBadge(confidence) {
      const normalized = String(confidence || 'single_leg_legacy');
      const tone = normalized === 'authoritative' ? 'success'
        : normalized === 'linked_existing' ? 'partial'
          : 'pending';
      return this.badge(normalized, tone);
    },
    transactionTraceLegBadge(leg) {
      const direction = String(leg?.movementDirection || '').trim();
      const label = direction === 'out' ? 'movement.spent'
        : direction === 'in' ? 'movement.received'
          : direction === 'grant' ? 'Granted'
            : direction || 'Trace';
      const tone = direction === 'out' ? 'partial'
        : direction === 'in' || direction === 'grant' ? 'success'
          : 'pending';
      return `${this.badge(label, tone)}<div class="orbit-user-sub">${this.esc(this.shopBagHistoryKindLabel(leg?.historyKind || leg?.legKind || ''))}</div>`;
    },
    transactionTraceLegHtml(leg) {
      const counterparty = String(leg?.counterpartyLabel || '').trim()
        ? this.shopBagCounterpartyCell(leg)
        : '<span class="orbit-muted">-</span>';
      const beforeAfter = [];
      if (String(leg?.balanceBeforeText || '-').trim() !== '-') {
        beforeAfter.push(`<div><strong>Before:</strong> ${this.esc(leg.balanceBeforeText)}</div>`);
      }
      if (String(leg?.balanceAfterText || '-').trim() !== '-') {
        beforeAfter.push(`<div><strong>After:</strong> ${this.esc(leg.balanceAfterText)}</div>`);
      }
      return `
        <div class="orbit-drawer-section">
          <div class="orbit-bag-header">
            <div class="orbit-bag-member">${leg?.userId ? this.userCell(leg) : '<span class="orbit-muted">-</span>'}</div>
            <div class="orbit-json-summary">
              ${this.transactionTraceLegBadge(leg)}
              <span class="orbit-json-chip">${this.esc(leg?.createDate || '-')}</span>
              ${leg?.sourceType ? `<span class="orbit-json-chip">${this.esc(this.shopBagSourceLabel(leg.sourceType))}</span>` : ''}
            </div>
          </div>
          <div class="orbit-bag-summary-grid">
            <div class="orbit-drawer-section">
              <h4 class="ui header">Asset</h4>
              <div><strong>${this.esc(leg?.assetLabel || '-')}</strong>${leg?.assetCode ? `<div class="orbit-user-sub">${this.esc(leg.assetCode)}</div>` : ''}</div>
            </div>
            <div class="orbit-drawer-section">
              <h4 class="ui header">Delta</h4>
              <div><strong>${this.esc(leg?.deltaText || '-')}</strong></div>
            </div>
            <div class="orbit-drawer-section">
              <h4 class="ui header">Counterparty</h4>
              <div>${counterparty}</div>
            </div>
            <div class="orbit-drawer-section">
              <h4 class="ui header">Source</h4>
              <div><strong>${this.esc(this.shopBagSourceLabel(leg?.sourceType || ''))}</strong>${leg?.sourceId ? `<div class="orbit-user-sub">${this.esc(leg.sourceId)}</div>` : ''}</div>
            </div>
            <div class="orbit-drawer-section">
              <h4 class="ui header">Actor / Target</h4>
              <div>${this.esc(leg?.actorDisplayName || leg?.actorUserId || '-')}</div>
              <div class="orbit-user-sub">${this.esc(leg?.targetDisplayName || leg?.targetUserId || '')}</div>
            </div>
            <div class="orbit-drawer-section">
              <h4 class="ui header">Balances</h4>
              <div>${beforeAfter.length ? beforeAfter.join('') : '<span class="orbit-muted">-</span>'}</div>
            </div>
          </div>
          <div class="orbit-drawer-section">
            <h4 class="ui header">Note</h4>
            <div>${this.esc(leg?.note || '-')}</div>
          </div>
          <div class="orbit-drawer-section">
            <h4 class="ui header">Metadata</h4>
            <pre class="orbit-json-pre">${this.esc(leg?.metadataPretty || '{}')}</pre>
          </div>
        </div>
      `;
    },
    renderTransactionTraceDrawer(res) {
      const trace = res?.trace || {};
      const legs = Array.isArray(res?.legs) ? res.legs : [];
      const users = Array.isArray(trace?.users) ? trace.users : [];
      const primaryUser = users.length === 1 ? users[0] : null;
      const chips = [
        this.transactionTraceConfidenceBadge(trace?.confidence),
        trace?.transactionGroupId ? `<span class="orbit-json-chip">${this.esc(trace.transactionGroupId)}</span>` : '',
        trace?.traceKey ? `<span class="orbit-json-chip">${this.esc(trace.traceKey)}</span>` : '',
        ...(Array.isArray(trace?.sourceTypes) ? trace.sourceTypes.map((sourceType) => `<span class="orbit-json-chip">${this.esc(this.shopBagSourceLabel(sourceType))}</span>`) : []),
      ].filter(Boolean).join('');
      const buttons = primaryUser ? `
        <button class="ui button" type="button" data-trace-open-bag><i class="fa-solid fa-box-open"></i> Member Bag</button>
        <button class="ui button" type="button" data-trace-open-member><i class="fa-solid fa-user"></i> Member Profile</button>
      ` : '';

      this.drawer(trace?.title || 'Transaction Trace', `
        <div class="orbit-bag-header">
          <div>
            <h3 class="ui header" style="margin:0">${this.esc(trace?.title || 'Transaction Trace')}</h3>
            <div class="orbit-user-sub">${this.esc(trace?.createDateStart || '-')} ${trace?.createDateEnd && trace.createDateEnd !== trace.createDateStart ? `to ${this.esc(trace.createDateEnd)}` : ''}</div>
          </div>
          <div class="orbit-section-actions">${buttons}</div>
        </div>
        <div class="orbit-json-summary" style="margin-bottom:12px">${chips}</div>
        ${legs.map((leg) => this.transactionTraceLegHtml(leg)).join('')}
      `);

      if (primaryUser?.userId) {
        $('[data-trace-open-bag]').off('click').on('click', () => this.openShopMemberBagDetail(primaryUser));
        $('[data-trace-open-member]').off('click').on('click', () => this.openMember(primaryUser.userId));
      }
    },
    fillShopMemberBagsFilterOptions() {
      const unitSelect = $('#shopMemberBagsFilter [name="unitCode"]');
      const typeSelect = $('#shopMemberBagsFilter [name="itemType"]');
      if (unitSelect.length) {
        const current = String(this.state.shopMemberBagsFilters?.unitCode || unitSelect.val() || '');
        unitSelect.html([
          '<option value="">ทุกหน่วยเงิน</option>',
          ...(this.state.shopMemberBagsUnits || []).map((unit) => `<option value="${this.escAttr(unit.unitCode)}">${this.esc(unit.label || unit.unitCode)}</option>`),
        ].join(''));
        unitSelect.val(current).dropdown('refresh');
      }
      if (typeSelect.length) {
        const current = String(this.state.shopMemberBagsFilters?.itemType || typeSelect.val() || '');
        typeSelect.html([
          '<option value="">ทุกชนิดไอเทม</option>',
          ...(this.state.shopMemberBagsItemTypes || []).map((itemType) => `<option value="${this.escAttr(itemType)}">${this.esc(itemType)}</option>`),
        ].join(''));
        typeSelect.val(current).dropdown('refresh');
      }
    },
    applyShopMemberBagsFilterState(filters) {
      const root = $('#shopMemberBagsFilter');
      if (!root.length) return;
      const values = {
        q: filters.q || '',
        unitCode: filters.unitCode || '',
        itemType: filters.itemType || '',
        itemCode: filters.itemCode || '',
        pageSize: String(filters.pageSize || this.state.shopMemberBagsFilters?.pageSize || 50),
      };
      Object.entries(values).forEach(([name, value]) => {
        const field = root.find(`[name="${name}"]`);
        if (!field.length) return;
        field.val(value);
        if (field.is('select')) field.dropdown('refresh');
      });
      ['hideInactive', 'onlyWithWallet', 'onlyWithInventory'].forEach((name) => {
        const field = root.find(`[name="${name}"]`);
        field.prop('checked', Boolean(filters[name]));
      });
      root.find('.ui.checkbox').checkbox('refresh');
    },
    renderShopMemberBags(rows) {
      const walletColumns = this.state.shopMemberBagsWalletColumns || [];
      const columns = [
        ['Member', 'displayName', (row) => this.shopMemberBagUserCell(row)],
        ...(walletColumns.length
          ? walletColumns.map((unit) => [unit.label || unit.unitCode || 'Wallet', `wallet:${unit.unitCode || ''}`, (row) => this.shopMemberWalletUnitCell(row, unit)])
          : [['Wallet', 'walletTotal', (row) => this.shopMemberWalletCell(row)]]),
        ['Items', 'itemQuantity', (row) => this.shopMemberItemsCell(row)],
        ['Active', 'isActive', (row) => this.badge(Number(row.isActive) === 1 ? 'active' : 'left', Number(row.isActive) === 1 ? 'success' : 'failed')],
        ['Updated', 'updateDate', (row) => row.lastActivityDate || '-'],
      ];
      this.table('#shopMemberBagsTable', columns, rows, {
        id: 'shopMemberBagsTable',
        rowClick: (row) => this.openShopMemberBagDetail(row),
        onSort: () => {
          this.state.shopMemberBagsPage = 1;
          this.loadShopMemberBags();
        },
      });
    },
    shopMemberWalletCell(row) {
      const wallets = row?.wallets || [];
      if (!wallets.length) return '<span class="orbit-muted">-</span>';
      return wallets.map((wallet) => `<span class="orbit-json-chip">${this.esc(wallet.label)} ${Number(wallet.value || 0).toLocaleString()}</span>`).join(' ');
    },
    shopMemberWalletUnitCell(row, unit) {
      const unitCode = String(unit?.unitCode || '');
      const value = Number(row?.walletMap?.[unitCode] || 0);
      const formatted = value.toLocaleString();
      return value === 0 ? `<span class="orbit-muted">${formatted}</span>` : `<strong>${formatted}</strong>`;
    },
    shopMemberBagUserCell(row) {
      const userId = row?.userId || '';
      const displayName = row?.displayName || row?.globalName || row?.userName || userId || '-';
      const avatarUrl = row?.avatarUrl || '';
      return `<span class="orbit-user-static">
        ${this.imageTag('orbit-avatar', avatarUrl, avatarUrl, displayName)}
        <span class="orbit-user-text"><span class="orbit-user-name">${this.esc(displayName)}</span><span class="orbit-user-sub">${this.esc(row?.userName ? '@' + row.userName : userId)}</span></span>
      </span>`;
    },
    shopMemberItemsCell(row) {
      const items = row?.items || [];
      if (!items.length) return '<span class="orbit-muted">-</span>';
      return items.slice(0, 4).map((item) => `<span class="orbit-json-chip">${this.esc(item.label)} x${Number(item.value || 0).toLocaleString()}</span>`).join(' ')
        + (items.length > 4 ? ` <span class="orbit-muted">+${items.length - 4}</span>` : '');
    },
    openShopMemberBagDetail(row) {
      const previous = this.state.shopMemberBagDetail || {};
      const userId = String(row?.userId || previous.userId || '');
      if (!userId) return;
      const sameUser = String(previous.userId || '') === userId;
      this.state.shopMemberBagDetail = {
        userId,
        member: row || previous.member || {},
        filters: sameUser
          ? { ...(previous.filters || {}), page: Number(previous.filters?.page || 1) || 1, pageSize: Number(previous.filters?.pageSize || 25) || 25 }
          : { page: 1, pageSize: 25, historyKind: '', direction: '', sourceType: '', dateFrom: '', dateTo: '' },
      };
      this.drawer(`Bag Report · ${row?.displayName || row?.userId || userId}`, `<div class="orbit-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> ${this.t('common.loading')}</div>`);
      this.loadShopMemberBagDetail();
    },
    loadShopMemberBagDetail(options = {}) {
      const state = this.state.shopMemberBagDetail || {};
      if (!state.userId) return;
      const domFilters = $('#shopMemberBagDetailFilter').length ? this.formParams('#shopMemberBagDetailFilter') : {};
      const filters = {
        ...(state.filters || {}),
        ...domFilters,
        ...(options.filters || {}),
      };
      filters.page = Number(filters.page || 1) || 1;
      filters.pageSize = Number(filters.pageSize || 25) || 25;
      if (options.resetPage) filters.page = 1;
      state.filters = filters;

      this.api('/api/data/shop_member_bag_detail.php', { userId: state.userId, ...filters }).done((res) => {
        this.state.shopMemberBagDetail = {
          userId: state.userId,
          member: res.member || state.member || {},
          filters: { ...(res.filters || {}), page: res.page || filters.page, pageSize: res.pageSize || filters.pageSize },
        };
        this.renderShopMemberBagDetail(res);
      }).fail((xhr) => {
        this.drawer('Bag Report', `<div class="orbit-error"><i class="fa-solid fa-triangle-exclamation"></i> ${this.esc(xhr.responseJSON?.message || 'โหลด bag report ไม่สำเร็จ')}</div>`);
      });
    },
    renderShopMemberBagDetail(res) {
      const state = this.state.shopMemberBagDetail || {};
      const member = res.member || state.member || {};
      const filters = { page: 1, pageSize: 25, ...(res.filters || {}) };
      const sourceOptions = res.filterOptions?.sourceTypes || [];
      const title = `Bag Report · ${member.displayName || member.userId || state.userId || ''}`.trim();
      const notice = res.historyNotice || {};
      const historyRows = res.rows || [];
      const wallets = res.wallets || [];
      const items = res.items || [];
      const sourceOptionsHtml = [
        '<option value="">ทุก source</option>',
        ...sourceOptions.map((sourceType) => `<option value="${this.escAttr(sourceType)}">${this.esc(this.shopBagSourceLabel(sourceType))}</option>`),
      ].join('');
      const historyToggle = this.shopBagFilterToggleHtml('historyKind', String(filters.historyKind || ''), [
        ['', 'All'],
        ['item_ledger', 'Items'],
        ['wallet_ledger', 'Wallet'],
        ['reward_event', 'Rewards'],
      ]);
      const directionToggle = this.shopBagFilterToggleHtml('direction', String(filters.direction || ''), [
        ['', 'All'],
        ['in', 'In'],
        ['out', 'Out'],
      ]);

      this.drawer(title, `
        <div class="orbit-bag-header">
          <div class="orbit-bag-member">${this.userCell(member)}</div>
          <div class="orbit-section-actions">
            <button class="ui button" type="button" data-shop-bag-open-member><i class="fa-solid fa-user"></i> Member Profile</button>
            <button class="ui button" type="button" data-shop-bag-link-page="bag_transaction_report"><i class="fa-solid fa-arrow-right-arrow-left"></i> Transaction Report</button>
            <button class="ui button" type="button" data-shop-bag-link-page="reward_report"><i class="fa-solid fa-gift"></i> Reward Report</button>
            <button class="ui button" type="button" data-shop-bag-link-page="shop_report"><i class="fa-solid fa-store"></i> Shop Report</button>
          </div>
        </div>
        <div class="orbit-metric-strip" id="shopMemberBagDetailMetrics"></div>
        <div class="ui info message orbit-bag-history-note">
          <div class="header">${this.esc(notice.title || 'Item history notice')}</div>
          <p>${this.esc(notice.body || '')}</p>
        </div>
        <div class="orbit-bag-summary-grid">
          <div class="orbit-drawer-section">
            <h4 class="ui header">Current Wallet</h4>
            <div id="shopMemberBagCurrentWallets" class="orbit-bag-snapshot"></div>
          </div>
          <div class="orbit-drawer-section">
            <h4 class="ui header">Current Items</h4>
            <div id="shopMemberBagCurrentItems" class="orbit-bag-snapshot"></div>
          </div>
        </div>
        <div class="orbit-drawer-section">
          <h4 class="ui header">Bag History</h4>
          <div class="orbit-filter orbit-bag-filter" id="shopMemberBagDetailFilter">
            ${historyToggle}
            ${directionToggle}
            <select class="ui dropdown" name="sourceType">${sourceOptionsHtml}</select>
            <div class="ui input"><input type="datetime-local" name="dateFrom" value="${this.escAttr(this.shopBagDateTimeValue(filters.dateFrom))}"></div>
            <div class="ui input"><input type="datetime-local" name="dateTo" value="${this.escAttr(this.shopBagDateTimeValue(filters.dateTo))}"></div>
            <select class="ui dropdown" name="pageSize">
              <option value="25" ${Number(filters.pageSize || 25) === 25 ? 'selected' : ''}>25 rows</option>
              <option value="50" ${Number(filters.pageSize || 25) === 50 ? 'selected' : ''}>50 rows</option>
              <option value="100" ${Number(filters.pageSize || 25) === 100 ? 'selected' : ''}>100 rows</option>
            </select>
            <button class="ui button" type="button" data-shop-bag-apply><i class="fa-solid fa-filter"></i></button>
          </div>
          <div class="orbit-table-wrap">
            <table class="ui very basic table orbit-table" id="shopMemberBagHistoryTable"></table>
          </div>
          <div class="orbit-section-body" id="shopMemberBagHistoryPager"></div>
        </div>
      `);

      this.metrics('#shopMemberBagDetailMetrics', [
        ['History Rows', res.metrics?.historyRows],
        ['Item Moves', res.metrics?.itemMoves],
        ['Wallet Moves', res.metrics?.walletMoves],
        ['Reward Events', res.metrics?.rewardEvents],
        ['Wallet Units', res.metrics?.currentWalletUnits],
        ['Current Items', res.metrics?.currentItemQuantity],
      ]);
      $('#shopMemberBagCurrentWallets').html(this.shopBagWalletSnapshot(wallets));
      $('#shopMemberBagCurrentItems').html(this.shopBagItemSnapshot(items));
      $('#shopMemberBagDetailFilter [name="sourceType"]').val(String(filters.sourceType || '')).dropdown();
      $('#shopMemberBagDetailFilter [name="pageSize"]').dropdown();
      this.table('#shopMemberBagHistoryTable', [
        ['Time', 'createDate'],
        ['Movement', 'movementDirection', (entry) => this.shopBagMovementCell(entry)],
        ['Source', 'sourceType', (entry) => this.shopBagSourceCell(entry)],
        ['Asset', 'itemLabel', (entry) => this.shopBagAssetCell(entry)],
        ['Delta', 'quantityDelta', (entry) => this.shopBagDeltaCell(entry)],
        ['Counterparty', 'counterpartyLabel', (entry) => this.shopBagCounterpartyCell(entry)],
        ['Note', 'note', (entry) => this.shopBagNoteCell(entry)],
      ], historyRows, {
        id: 'shopMemberBagHistoryTable',
        rowClick: (entry) => this.openTransactionTrace(entry),
      });
      this.pager('#shopMemberBagHistoryPager', res, (page) => {
        this.loadShopMemberBagDetail({ filters: { page } });
      });
      this.bindShopMemberBagDetailDrawer();
    },
    bindShopMemberBagDetailDrawer() {
      const root = $('#shopMemberBagDetailFilter');
      if (!root.length) return;
      root.find('.ui.dropdown').dropdown();
      root.find('[data-shop-bag-toggle] .button').off('click').on('click', (event) => {
        const button = $(event.currentTarget);
        const group = button.closest('[data-shop-bag-toggle]');
        const input = group.find('input[type="hidden"]');
        input.val(String(button.data('value') || ''));
        group.find('.button').removeClass('primary active').addClass('basic');
        button.removeClass('basic').addClass('primary active');
        this.loadShopMemberBagDetail({ resetPage: true });
      });
      root.find('input').off('keydown.shopMemberBagDetail').on('keydown.shopMemberBagDetail', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        this.loadShopMemberBagDetail({ resetPage: true });
      });
      root.find('[name="sourceType"], [name="pageSize"]').off('change.shopMemberBagDetail').on('change.shopMemberBagDetail', () => {
        this.loadShopMemberBagDetail({ resetPage: true });
      });
      root.find('[data-shop-bag-apply]').off('click').on('click', () => this.loadShopMemberBagDetail({ resetPage: true }));
      $('[data-shop-bag-open-member]').off('click').on('click', () => {
        const userId = this.state.shopMemberBagDetail?.userId || '';
        if (userId) this.openMember(userId);
      });
      $('[data-shop-bag-link-page]').off('click').on('click', (event) => {
        const page = String($(event.currentTarget).data('shop-bag-link-page') || '');
        if (page) this.openShopBagLinkedReport(page);
      });
    },
    openShopBagLinkedReport(page) {
      const detailState = this.state.shopMemberBagDetail || {};
      const filters = detailState.filters || {};
      const shared = {
        q: detailState.userId || '',
        dateFrom: filters.dateFrom || '',
        dateTo: filters.dateTo || '',
        movementType: filters.direction || '',
      };
      if (page === 'reward_report') {
        this.state.rewardReportPage = 1;
        this.state.rewardReportFilters = {
          ...(this.state.rewardReportFilters || {}),
          ...shared,
        };
      } else if (page === 'bag_transaction_report') {
        this.state.bagTransactionReportPage = 1;
        this.state.bagTransactionReportFilters = {
          ...(this.state.bagTransactionReportFilters || {}),
          q: detailState.userId || '',
          dateFrom: filters.dateFrom || '',
          dateTo: filters.dateTo || '',
          direction: filters.direction || '',
          historyKind: filters.historyKind || '',
          sourceType: filters.sourceType || '',
        };
      } else if (page === 'shop_report') {
        this.state.shopReportPage = 1;
        this.state.shopReportFilters = {
          ...(this.state.shopReportFilters || {}),
          ...shared,
        };
      }
      this.closeDrawer();
      this.go(page);
    },
    shopBagFilterToggleHtml(name, current, options) {
      return `
        <div class="orbit-page-tabs orbit-bag-toggle-group" data-shop-bag-toggle="${this.escAttr(name)}">
          <input type="hidden" name="${this.escAttr(name)}" value="${this.escAttr(current)}">
          ${options.map(([value, label]) => {
            const active = String(current || '') === String(value || '');
            return `<button class="ui mini ${active ? 'primary active' : 'basic'} button" type="button" data-value="${this.escAttr(value)}">${this.esc(label)}</button>`;
          }).join('')}
        </div>`;
    },
    shopBagWalletSnapshot(wallets) {
      if (!(wallets || []).length) return '<span class="orbit-muted">No wallet balance</span>';
      return wallets.map((wallet) => `<span class="orbit-json-chip">${this.esc(wallet.label || wallet.unitLabel || wallet.unitCode || '')} ${Number(wallet.value || wallet.balanceAmount || 0).toLocaleString()}</span>`).join(' ');
    },
    shopBagItemSnapshot(items) {
      if (!(items || []).length) return '<span class="orbit-muted">No item in bag</span>';
      return items.map((item) => `<span class="orbit-json-chip">${this.esc(item.itemName || item.itemCode || 'Item')} x${Number(item.quantity || 0).toLocaleString()}</span>`).join(' ');
    },
    shopBagMovementCell(row) {
      const tone = row?.movementDirection === 'out' ? 'partial' : 'success';
      const label = row?.movementDirection === 'out' ? 'movement.spent' : 'movement.received';
      return `<div>${this.badge(label, tone)}<div class="orbit-user-sub">${this.esc(this.shopBagHistoryKindLabel(row?.historyKind || ''))}</div></div>`;
    },
    shopBagSourceCell(row) {
      const sourceType = String(row?.sourceType || '');
      const sourceLabel = this.shopBagSourceLabel(sourceType);
      const sub = row?.historyKind === 'reward_event'
        ? (row?.ruleCode || row?.triggerType || '')
        : (row?.sourceId || '');
      return `<div><strong>${this.esc(sourceLabel)}</strong>${sub ? `<div class="orbit-user-sub">${this.esc(sub)}</div>` : ''}</div>`;
    },
    shopBagAssetCell(row) {
      if (row?.historyKind === 'item_ledger') {
        const title = row?.itemLabel || row?.itemCode || 'Item';
        const sub = [row?.itemType, row?.itemCode].filter(Boolean).join(' · ');
        return `<div><strong>${this.esc(title)}</strong>${sub ? `<div class="orbit-user-sub">${this.esc(sub)}</div>` : ''}</div>`;
      }
      if (row?.historyKind === 'wallet_ledger') {
        const title = row?.unitLabel || row?.unitCode || 'Wallet';
        const sub = row?.unitCode && row?.unitCode !== row?.unitLabel ? row.unitCode : '';
        return `<div><strong>${this.esc(title)}</strong>${sub ? `<div class="orbit-user-sub">${this.esc(sub)}</div>` : ''}</div>`;
      }
      const title = row?.rewardLabel || row?.ruleName || row?.ruleCode || 'Reward';
      const sub = row?.rewardSummary || '';
      return `<div><strong>${this.esc(title)}</strong>${sub && sub !== '-' ? `<div class="orbit-user-sub">${this.esc(sub)}</div>` : ''}</div>`;
    },
    shopBagDeltaCell(row) {
      if (row?.historyKind === 'item_ledger') {
        return `<strong>${this.esc(row?.quantityText || '0')}</strong>`;
      }
      if (row?.historyKind === 'wallet_ledger') {
        return `<strong>${this.esc(row?.amountText || '-')}</strong>`;
      }
      return `<strong>${this.esc(row?.rewardSummary || '-')}</strong>`;
    },
    shopBagCounterpartyCell(row) {
      const label = String(row?.counterpartyLabel || '').trim();
      if (!label) return '<span class="orbit-muted">-</span>';
      const directionMap = { from: 'from', to: 'to', for: 'for' };
      const prefix = directionMap[String(row?.counterpartyDirection || '')] || '';
      return `<div><strong>${this.esc(label)}</strong>${prefix ? `<div class="orbit-user-sub">${this.esc(prefix)}</div>` : ''}</div>`;
    },
    shopBagNoteCell(row) {
      const note = String(row?.note || '').trim();
      if (!note) return '<span class="orbit-muted">-</span>';
      return `<div class="orbit-bag-note">${this.esc(note)}</div>`;
    },
    shopBagHistoryKindLabel(historyKind) {
      const map = {
        item_ledger: 'Item Ledger',
        wallet_ledger: 'Wallet Ledger',
        wallet_movement: 'Wallet Ledger',
        reward_event: 'Reward Event',
        role_grant: 'Role Grant',
      };
      return map[String(historyKind || '')] || String(historyKind || '-');
    },
    shopBagSourceLabel(sourceType) {
      const normalized = String(sourceType || '');
      const map = {
        shop_role_badge_purchase: 'Shop purchase',
        shop_role_badge_gift: 'Shop gift',
        shop_role_badge_consume: 'Use role badge',
        gacha_role_grant: 'Role grant',
        earn_rule: 'Earn reward',
        earn_manual: 'Manual earn',
        gacha_spin: 'Gacha spin',
        gacha_spin_refund: 'Gacha refund',
        gacha_reset_mock_credit: 'Mock credit reset',
        reward_event: 'Reward event',
      };
      if (map[normalized]) return map[normalized];
      if (normalized.startsWith('earn_')) return this.rewardReportSourceLabel(normalized);
      return normalized || '-';
    },
    shopBagDateTimeValue(value) {
      const text = String(value || '').trim();
      if (!text) return '';
      return text.replace(' ', 'T').slice(0, 16);
    },
    rewardReportSourceLabel(sourceType) {
      const map = {
        earn_text_active_daily: 'source.earn_text_active_daily',
        earn_voice_hourly: 'source.earn_voice_hourly',
        earn_voice_10min_free_spin: 'source.earn_voice_10min_free_spin',
        earn_member_first_join: 'source.earn_member_first_join',
        earn_invite_member: 'source.earn_invite_member',
        earn_manual: 'source.earn_manual',
        gacha_spin: 'source.gacha_spin',
        gacha_spin_refund: 'source.gacha_spin_refund',
        gacha_reset_mock_credit: 'source.gacha_reset_mock_credit',
      };
      const key = map[String(sourceType || '')];
      return key ? this.t(key) : String(sourceType || '-');
    },
    rewardReportStatusBadge(status) {
      const normalized = String(status || '').toLowerCase();
      const tone = normalized === 'granted' || normalized === 'received' ? 'success'
        : normalized === 'spent' || normalized === 'consumed' || normalized === 'expired' ? 'partial'
          : normalized === 'pending' ? 'pending'
            : normalized === 'failed' ? 'failed' : '';
      return this.badge(normalized || 'unknown', tone);
    },
    rewardReportMovementCell(row) {
      const direction = row?.movementDirection === 'out' ? 'movement.spent' : 'movement.received';
      const tone = row?.movementDirection === 'out' ? 'partial' : 'success';
      const sub = row?.historyKind === 'wallet_movement' ? this.t('movement.wallet_ledger') : this.t('movement.reward_event');
      return `<div>${this.badge(direction, tone)}<div class="orbit-user-sub">${this.esc(sub)}</div></div>`;
    },
    rewardReportRuleCell(row) {
      const primary = row?.historyKind === 'wallet_movement'
        ? this.rewardReportSourceLabel(row?.sourceType)
        : (row?.ruleName || row?.ruleCode || '-');
      const trigger = row?.triggerType ? `<div class="orbit-user-sub">${this.esc(this.rewardReportSourceLabel(row.triggerType))}</div>` : '';
      return `<div><strong>${this.esc(primary)}</strong>${trigger}</div>`;
    },
    rewardReportSourceCell(row) {
      const sourceType = this.rewardReportSourceLabel(row?.sourceType);
      const parts = [];
      const sourceDate = this.rewardReportSourceDate(row);
      if (sourceDate) parts.push(this.esc(sourceDate));
      if (row?.sourceId) parts.push(this.esc(row.sourceId));
      const manualMeta = this.rewardReportManualGrantMeta(row);
      if (manualMeta) parts.push(this.esc(manualMeta));
      const inviteMeta = this.rewardReportInviteMeta(row);
      if (inviteMeta) parts.push(this.esc(inviteMeta));
      const sourceMeta = parts.length ? `<div class="orbit-user-sub">${parts.join(' · ')}</div>` : '';
      return `<div><strong>${this.esc(sourceType)}</strong>${sourceMeta}</div>`;
    },
    rewardReportManualGrantMeta(row) {
      const sourceType = String(row?.sourceType || '');
      if (sourceType !== 'earn_manual') return '';
      const grant = row?.metadata?.manualGrant;
      if (!grant || typeof grant !== 'object') return '';
      const targetType = String(grant.targetType || 'user');
      const targetLabel = String(grant.targetLabel || row?.displayName || '');
      const recipientCount = Number(grant.recipientCount || 1);
      if (targetType === 'server') return `ทั้งเซิร์ฟเวอร์ ${recipientCount.toLocaleString()} คน`;
      if (targetType === 'role') return `${targetLabel} ${recipientCount.toLocaleString()} คน`;
      return targetLabel || '';
    },
    rewardReportInviteMeta(row) {
      const sourceType = String(row?.sourceType || '');
      if (sourceType !== 'earn_invite_member') return '';
      const invite = row?.metadata?.invite && typeof row.metadata.invite === 'object' ? row.metadata.invite : {};
      const joinedUserId = String(row?.sourceJoinedUserId || row?.metadata?.joinedUserId || '');
      const count = invite.invite_count != null ? `invite #${Number(invite.invite_count).toLocaleString()}` : '';
      return [joinedUserId ? `joined ${joinedUserId}` : '', count].filter(Boolean).join(' · ');
    },
    rewardReportSourceDate(row) {
      const sourceType = String(row?.sourceType || row?.triggerType || '');
      const sourceDate = String(row?.sourceDate || '');
      if (!sourceDate) return '';
      if ((sourceType === 'earn_voice_hourly' || sourceType === 'earn_text_active_daily') && sourceDate.endsWith(' 00:00:00')) {
        return sourceDate.slice(0, 10);
      }
      return sourceDate;
    },
    rewardReportWindowCell(row) {
      const primary = this.rewardReportWindowPrimary(row);
      const secondary = this.rewardReportWindowSecondary(row);
      return `<div><strong>${this.esc(primary || '-')}</strong>${secondary ? `<div class="orbit-user-sub">${this.esc(secondary)}</div>` : ''}</div>`;
    },
    rewardReportWindowPrimary(row) {
      const sourceType = String(row?.sourceType || row?.triggerType || '');
      const segment = Number(row?.sourceSegment || 0);
      if (sourceType === 'earn_voice_hourly') {
        return segment > 0 ? this.t('reward_window.voice_segment', { segment }) : this.t('source.voice');
      }
      if (sourceType === 'earn_text_active_daily') {
        return segment > 0 ? this.t('reward_window.text_segment', { segment }) : this.t('source.text');
      }
      if (sourceType === 'earn_member_first_join') {
        return this.t('source.first_join');
      }
      if (sourceType === 'earn_invite_member') {
        return this.t('source.earn_invite_member');
      }
      return row?.sourceId || '-';
    },
    rewardReportWindowSecondary(row) {
      const sourceType = String(row?.sourceType || row?.triggerType || '');
      if (sourceType === 'earn_voice_hourly') {
        const range = this.rewardReportWindowRange(row);
        const total = Number(row?.sourceVoiceSeconds || 0);
        return [range, total > 0 ? `ยอดสะสมตอนจ่าย ${this.duration(total)}` : ''].filter(Boolean).join(' · ');
      }
      if (sourceType === 'earn_text_active_daily') {
        const range = this.rewardReportWindowRange(row);
        const total = Number(row?.sourceActiveMinutes || 0);
        return [range, total > 0 ? `ยอดสะสมตอนจ่าย ${total} นาที` : ''].filter(Boolean).join(' · ');
      }
      if (sourceType === 'earn_member_first_join') {
        return row?.sourceDate || '';
      }
      if (sourceType === 'earn_invite_member') {
        return this.rewardReportInviteMeta(row) || row?.sourceDate || '';
      }
      return '';
    },
    rewardReportWindowRange(row) {
      const sourceType = String(row?.sourceType || row?.triggerType || '');
      if (sourceType === 'earn_voice_hourly') {
        const start = Number(row?.sourceWindowStartSeconds || 0);
        const end = Number(row?.sourceWindowEndSeconds || 0);
        if (end <= 0) return '';
        if (start % 3600 === 0 && end % 3600 === 0) {
          return this.t('reward_window.hour_range', { start: start / 3600, end: end / 3600 });
        }
        return this.t('reward_window.duration_range', { start: this.duration(start), end: this.duration(end) });
      }
      if (sourceType === 'earn_text_active_daily') {
        const start = Number(row?.sourceWindowStartMinutes || 0);
        const end = Number(row?.sourceWindowEndMinutes || 0);
        return end > 0 ? this.t('reward_window.minute_range', { start, end }) : '';
      }
      return '';
    },
    rewardReportProgressAtGrant(row) {
      const sourceType = String(row?.sourceType || row?.triggerType || '');
      if (sourceType === 'earn_voice_hourly') {
        const total = Number(row?.sourceVoiceSeconds || 0);
        return total > 0 ? this.duration(total) : '-';
      }
      if (sourceType === 'earn_text_active_daily') {
        const total = Number(row?.sourceActiveMinutes || 0);
        return total > 0 ? `${total} นาที` : '-';
      }
      return '-';
    },
    rewardReportConditionText(row) {
      const sourceType = String(row?.sourceType || row?.triggerType || '');
      const condition = row?.condition && typeof row.condition === 'object' ? row.condition : {};
      if (sourceType === 'earn_voice_hourly') {
        const minSeconds = Number(condition.minSeconds || 0);
        const dailyLimit = Number(condition.dailyLimit || 0);
        const threshold = minSeconds > 0
          ? (minSeconds % 3600 === 0 ? `ทุก ${minSeconds / 3600} ชั่วโมง` : `ทุก ${this.duration(minSeconds)}`)
          : 'ไม่ระบุ threshold';
        return [threshold, dailyLimit > 0 ? `สูงสุด ${dailyLimit} รอบ/วัน` : ''].filter(Boolean).join(' · ');
      }
      if (sourceType === 'earn_text_active_daily') {
        const minMinutes = Number(condition.minMinutes || 0);
        const dailyLimit = Number(condition.dailyLimit || 0);
        const messageGate = Number(condition.minMessages || 0);
        const channelGate = Number(condition.minUniqueChannels || 0);
        return [
          minMinutes > 0 ? `ทุก ${minMinutes} นาที active` : '',
          messageGate > 0 ? `อย่างน้อย ${messageGate} ข้อความ` : '',
          channelGate > 0 ? `อย่างน้อย ${channelGate} ห้อง` : '',
          dailyLimit > 0 ? `สูงสุด ${dailyLimit} รอบ/วัน` : '',
        ].filter(Boolean).join(' · ');
      }
      if (sourceType === 'earn_member_first_join') {
        return 'แจกครั้งแรกเมื่อพบ GUILD_MEMBER_ADD ของ user';
      }
      if (sourceType === 'earn_invite_member') {
        const dailyLimit = Number(condition.dailyLimit || 0);
        return ['แจกให้ผู้เชิญจาก invite attribution ที่ match กับ GUILD_MEMBER_ADD', dailyLimit > 0 ? `สูงสุด ${dailyLimit} ครั้ง/วัน/ผู้เชิญ` : ''].filter(Boolean).join(' · ');
      }
      return '-';
    },
    rewardReportUnitMap(row, fallbackKind = 'delta') {
      const directMap = row?.[fallbackKind === 'after' ? 'walletBalanceAfterMap' : fallbackKind === 'before' ? 'walletBalanceBeforeMap' : 'walletDeltaMap'];
      if (directMap && typeof directMap === 'object' && Object.keys(directMap).length) {
        return directMap;
      }
      if (fallbackKind !== 'delta') {
        const unitCode = String(row?.unitCode || '');
        const rawValue = fallbackKind === 'after' ? row?.walletBalanceAfter : row?.walletBalanceBefore;
        const numericValue = Number(rawValue || 0);
        return unitCode ? { [unitCode]: numericValue } : {};
      }
      const rewardMap = {};
      const coin = Number(row?.rewardCoin || 0);
      const ticket = Number(row?.rewardTicket || 0);
      const freeSpin = Number(row?.rewardFreeSpin || 0);
      if (coin > 0) rewardMap.coin = coin;
      if (ticket > 0) rewardMap.ticket = ticket;
      if (freeSpin > 0) rewardMap.freeSpin = freeSpin;
      return rewardMap;
    },
    rewardReportUnitSummary(unitMap, options = {}) {
      const {
        signed = false,
        joiner = ' + ',
      } = options;
      const entries = Object.entries(unitMap || {}).filter(([, value]) => Number(value || 0) !== 0);
      if (!entries.length) return '-';
      return entries.map(([unitCode, rawValue]) => {
        const value = Number(rawValue || 0);
        const sign = signed && value > 0 ? '+' : '';
        return `${sign}${value.toLocaleString()} ${unitCode}`.trim();
      }).join(joiner);
    },
    rewardReportAmountText(row) {
      const unitMap = this.rewardReportUnitMap(row, 'delta');
      if (row?.historyKind === 'wallet_movement') {
        return this.rewardReportUnitSummary(unitMap, { signed: true });
      }
      return this.rewardReportUnitSummary(unitMap);
    },
    rewardReportRemainingText(row) {
      return this.rewardReportUnitSummary(this.rewardReportUnitMap(row, 'after'), { joiner: ' · ' });
    },
    rewardReportBalanceBeforeText(row) {
      return this.rewardReportUnitSummary(this.rewardReportUnitMap(row, 'before'), { joiner: ' · ' });
    },
    rewardReportRewardCell(row) {
      return `<div><strong>${this.esc(this.rewardReportAmountText(row))}</strong></div>`;
    },
    rewardReportRemainingCell(row) {
      return `<div><strong>${this.esc(this.rewardReportRemainingText(row))}</strong></div>`;
    },
    rewardReportLedgerCell(row) {
      if (!row?.walletLedgerId) {
        return '<span class="orbit-muted">event only</span>';
      }
      const summary = this.rewardReportUnitSummary(this.rewardReportUnitMap(row, 'delta'), { signed: true, joiner: ' · ' });
      return `<div><strong>#${this.esc(String(row.walletLedgerId))}</strong><div class="orbit-user-sub">${this.esc(summary)}</div></div>`;
    },
    openRewardReportDetail(row) {
      this.openTransactionTrace(row, {
        fallback: () => this.openRewardReportMetadataDetail(row),
      });
    },
    openRewardReportMetadataDetail(row) {
      const chips = [
        this.rewardReportStatusBadge(row.rewardStatus),
        row?.ruleCode ? `<span class="orbit-json-chip">${this.esc(row.ruleCode)}</span>` : '',
        row?.sourceType ? `<span class="orbit-json-chip">${this.esc(this.rewardReportSourceLabel(row.sourceType))}</span>` : '',
      ].filter(Boolean).join('');
      const infoItem = (label, value) => `
        <div class="orbit-metric">
          <div class="orbit-muted">${this.esc(label)}</div>
          <div class="orbit-metric-value" style="font-size:18px">${this.esc(value || '-')}</div>
        </div>
      `;
      const html = `
        <div class="orbit-json-summary" style="margin-bottom:12px">${chips}</div>
        <div class="orbit-compact-grid" style="margin-bottom:12px">
          ${infoItem('Reward Event', row?.rewardEventId || '-')}
          ${infoItem('User ID', row?.userId || '-')}
          ${infoItem(row?.historyKind === 'wallet_movement' ? 'Movement Source' : 'Rule', row?.ruleName || row?.ruleCode || '-')}
          ${infoItem('Source', this.rewardReportSourceLabel(row?.sourceType))}
          ${infoItem('Manual Scope', this.rewardReportManualGrantMeta(row) || '-')}
          ${infoItem('Source Date', this.rewardReportSourceDate(row) || '-')}
          ${infoItem('Reward Segment', this.rewardReportWindowPrimary(row))}
          ${infoItem('Reward Range', this.rewardReportWindowRange(row) || '-')}
          ${infoItem('Progress At Grant', this.rewardReportProgressAtGrant(row))}
          ${infoItem('Rule Condition', this.rewardReportConditionText(row))}
          ${infoItem('Amount', this.rewardReportAmountText(row))}
          ${infoItem('Remaining', this.rewardReportRemainingText(row))}
          ${infoItem('Balance Before', this.rewardReportBalanceBeforeText(row))}
          ${infoItem('Coin', Number(row?.rewardCoin || 0).toLocaleString())}
          ${infoItem('Ticket', Number(row?.rewardTicket || 0).toLocaleString())}
          ${infoItem('Wallet Delta', this.rewardReportAmountText(row))}
          ${infoItem('Wallet Ledger', row?.walletLedgerId ? `#${row.walletLedgerId}` : 'none')}
          ${infoItem('Created', row?.createDate || '-')}
        </div>
        <pre class="orbit-json-summary">${this.esc(row?.metadataPretty || '{}')}</pre>
      `;
      this.drawer(row?.historyKind === 'wallet_movement' ? `Wallet #${row?.walletLedgerId || ''}` : `Reward ${row?.rewardEventId || ''}`, html);
    },
    roleSeriesIconUrl(path = '') {
      const normalized = String(path || '').trim();
      return normalized ? this.gachaAssetUrl(normalized) : '';
    },
    uploadRoleSeriesIcon(input) {
      const file = input.files?.[0];
      const seriesId = String($(input).data('role-series-upload') || '').trim();
      if (!file || !seriesId) return;
      this.syncRoleSeriesDrafts();
      const formData = new FormData();
      formData.append('image', file);
      this.apiForm('/api/admin/role_catalog_upload.php', formData)
        .done((res) => {
          const next = JSON.parse(JSON.stringify(this.state.roleCatalog || { series: [], roles: [] }));
          const series = (next.series || []).find((entry) => String(entry.id || '') === seriesId);
          if (!series) return;
          series.icon = String(res.path || '').trim();
          this.state.roleCatalog = next;
          this.renderRoleSeriesTab();
          this.toast('อัปโหลดไอคอนซีรีส์แล้ว');
        })
        .fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.upload_failed')));
    },
    syncRoleSeriesDrafts() {
      const config = JSON.parse(JSON.stringify(this.state.roleCatalog || { series: [], roles: [] }));
      config.series = Array.isArray(config.series) ? config.series : [];
      const roleMeta = new Map((config.roles || []).map((entry) => [String(entry.roleId), { ...entry }]));
      const selectedRoleIdsBySeries = new Map();
      $('#roleSeriesList [data-role-series-id]').each((order, element) => {
        const row = $(element);
        const seriesId = String(row.data('role-series-id') || '');
        const index = config.series.findIndex((series) => String(series.id || '') === seriesId);
        if (index === -1) return;
        config.series[index].name = String(row.find('[data-role-series-field="name"]').val() || '').trim();
        config.series[index].icon = String(row.find('[data-role-series-field="icon"]').val() || '').trim();
        config.series[index].background = String(row.find('[data-role-series-field="background"]').val() || '').trim();
        config.series[index].badge = String(row.find('[data-role-series-field="badge"]').val() || '').trim();
        config.series[index].description = String(row.find('[data-role-series-field="description"]').val() || '').trim();
        config.series[index].sortOrder = (order + 1) * 10;
        const selectedRoleIds = (row.find('[data-role-series-field="roles"]').val() || []).map((roleId) => String(roleId || ''));
        selectedRoleIdsBySeries.set(seriesId, selectedRoleIds);
        selectedRoleIds.forEach((roleId) => {
          const key = String(roleId);
          roleMeta.set(key, { ...(roleMeta.get(key) || { roleId: key, tier: '' }), roleId: key, seriesId });
        });
      });
      roleMeta.forEach((entry, key) => {
        const seriesId = String(entry.seriesId || '');
        if (!seriesId || !selectedRoleIdsBySeries.has(seriesId)) return;
        const selectedRoleIds = selectedRoleIdsBySeries.get(seriesId) || [];
        if (!selectedRoleIds.includes(key)) {
          entry.seriesId = '';
        }
      });
      config.series.sort((left, right) => Number(left.sortOrder || 0) - Number(right.sortOrder || 0));
      config.roles = Array.from(roleMeta.values()).filter((entry) => String(entry.roleId || '') !== '' && (String(entry.tier || '') !== '' || String(entry.seriesId || '') !== ''));
      this.state.roleCatalog = config;
    },
    renderRoleSeriesTab() {
      this.syncRoleSeriesDrafts();
      const config = this.state.roleCatalog || { series: [], roles: [] };
      const rows = Array.isArray(config.series) ? config.series : [];
      const roleMetaBySeries = {};
      (config.roles || []).forEach((entry) => {
        const seriesId = String(entry.seriesId || '');
        if (!seriesId) return;
        roleMetaBySeries[seriesId] = roleMetaBySeries[seriesId] || [];
        roleMetaBySeries[seriesId].push(String(entry.roleId));
      });
      const roleOptions = (this.state.roles || []).map((role) => `<option value="${this.escAttr(role.roleId)}">${this.esc(role.roleName || role.roleId)}${role.roleTier ? ` · ${this.esc(role.roleTier)}` : ''}</option>`).join('');
      $('#roleSeriesList').html(rows.length ? rows.map((series, index) => `
        <article class="orbit-earn-rule" draggable="true" data-role-series-id="${this.escAttr(series.id)}">
          <div class="orbit-earn-rule-head">
            <div>
              <strong><i class="fa-solid fa-grip-vertical orbit-drag-handle"></i> ${this.esc(series.name || `Series ${index + 1}`)}</strong>
              <span>${this.esc(series.id || `series-${index + 1}`)} · ลากขึ้น/ลงเพื่อเรียงลำดับ</span>
            </div>
            <button class="ui tiny button" type="button" data-role-series-remove="${index}"><i class="fa-solid fa-trash"></i></button>
          </div>
          <div class="ui small form orbit-earn-rule-grid">
            <div class="field">
              <label>${this.fieldLabel('ชื่อ Series', 'ชื่อที่ใช้โชว์จัดกลุ่มยศในหน้ารางวัล')}</label>
              <input data-role-series-field="name" value="${this.escAttr(series.name || '')}">
            </div>
            <div class="field">
              <label>${this.fieldLabel('Icon', 'ไอคอนวงกลมของซีรีส์ ใช้โชว์คู่กับชื่อบนหน้าร้าน')}</label>
              <input type="hidden" data-role-series-field="icon" value="${this.escAttr(series.icon || '')}">
              <div style="display:flex; align-items:center; gap:12px; min-height:38px;">
                <span style="width:42px; height:42px; border-radius:999px; overflow:hidden; border:1px solid var(--orbit-line); background:var(--orbit-panel-2); display:grid; place-items:center; flex:none;">
                  ${series.icon ? `<img src="${this.escAttr(this.roleSeriesIconUrl(series.icon || ''))}" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">` : '<i class="fa-solid fa-image" style="color:var(--orbit-muted)"></i>'}
                </span>
                <div class="ui tiny buttons">
                  <label class="ui button"><i class="fa-solid fa-upload"></i> Upload icon<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-role-series-upload="${this.escAttr(series.id)}" hidden></label>
                  <button class="ui button" type="button" data-role-series-clear-icon="${this.escAttr(series.id)}"${series.icon ? '' : ' disabled'}><i class="fa-solid fa-eraser"></i></button>
                </div>
              </div>
            </div>
            <div class="field">
              <label>${this.fieldLabel('Badge', 'คำสั้นๆที่ใช้ขึ้นข้างชื่อ series')}</label>
              <input data-role-series-field="badge" maxlength="60" value="${this.escAttr(series.badge || '')}">
            </div>
            <div class="field orbit-earn-rule-grid-wide">
              <label>${this.fieldLabel('Background', 'ใส่สีหรือ gradient ได้ เช่น linear-gradient(135deg,#ffffff,#eef7ff)')}</label>
              <input data-role-series-field="background" maxlength="240" value="${this.escAttr(series.background || '')}" placeholder="linear-gradient(135deg,#ffffff,#eef7ff)">
            </div>
            <div class="field orbit-earn-rule-grid-wide">
              <label>${this.fieldLabel('ยศใน Series', 'เลือกหลายยศให้มาอยู่ใน series นี้ ยศหนึ่งอยู่ได้ทีละ series')}</label>
              <select class="ui fluid search dropdown" multiple data-role-series-field="roles">${roleOptions}</select>
            </div>
            <div class="field orbit-earn-rule-grid-wide">
              <label>${this.fieldLabel('คำอธิบาย', 'คำอธิบายสั้นของซีรีส์ ใช้เล่า mood/คอนเซปต์เวลาจัดกลุ่มโชว์')}</label>
              <textarea rows="3" data-role-series-field="description">${this.esc(series.description || '')}</textarea>
            </div>
          </div>
        </article>
      `).join('') : this.empty('ยังไม่มี Series ยศ'));
      $('#roleSeriesList [data-role-series-id]').each((_, element) => {
        const row = $(element);
        const seriesId = String(row.data('role-series-id') || '');
        row.find('[data-role-series-field="roles"]').val(roleMetaBySeries[seriesId] || []).dropdown({
          fullTextSearch: true,
          clearable: true,
        });
      });
      $('#roleSeriesAddButton').off('click.roleSeries').on('click.roleSeries', () => {
        const next = JSON.parse(JSON.stringify(this.state.roleCatalog || { series: [], roles: [] }));
        next.series = Array.isArray(next.series) ? next.series : [];
        next.series.push({
          id: `series-${Date.now()}`,
          name: '',
          icon: '',
          background: '',
          badge: '',
          description: '',
          sortOrder: (next.series.length + 1) * 10,
        });
        this.state.roleCatalog = next;
        this.renderRoleSeriesTab();
      });
      $('#roleSeriesList [data-role-series-id]').off('dragstart.roleSeries dragover.roleSeries drop.roleSeries dragend.roleSeries')
        .on('dragstart.roleSeries', (e) => {
          this.state.roleSeriesDragId = String($(e.currentTarget).data('role-series-id') || '');
          e.originalEvent.dataTransfer.effectAllowed = 'move';
        })
        .on('dragover.roleSeries', (e) => {
          e.preventDefault();
          e.originalEvent.dataTransfer.dropEffect = 'move';
        })
        .on('drop.roleSeries', (e) => {
          e.preventDefault();
          const fromId = String(this.state.roleSeriesDragId || '');
          const toId = String($(e.currentTarget).data('role-series-id') || '');
          if (!fromId || !toId || fromId === toId) return;
          this.syncRoleSeriesDrafts();
          const next = JSON.parse(JSON.stringify(this.state.roleCatalog || { series: [], roles: [] }));
          const fromIndex = (next.series || []).findIndex((series) => String(series.id || '') === fromId);
          const toIndex = (next.series || []).findIndex((series) => String(series.id || '') === toId);
          if (fromIndex === -1 || toIndex === -1) return;
          const [moved] = next.series.splice(fromIndex, 1);
          next.series.splice(toIndex, 0, moved);
          next.series.forEach((series, seriesIndex) => { series.sortOrder = (seriesIndex + 1) * 10; });
          this.state.roleCatalog = next;
          this.renderRoleSeriesTab();
        });
      $('[data-role-series-upload]').off('change.roleSeries').on('change.roleSeries', (e) => this.uploadRoleSeriesIcon(e.currentTarget));
      $('[data-role-series-clear-icon]').off('click.roleSeries').on('click.roleSeries', (e) => {
        const seriesId = String($(e.currentTarget).data('role-series-clear-icon') || '').trim();
        if (!seriesId) return;
        this.syncRoleSeriesDrafts();
        const next = JSON.parse(JSON.stringify(this.state.roleCatalog || { series: [], roles: [] }));
        const series = (next.series || []).find((entry) => String(entry.id || '') === seriesId);
        if (!series) return;
        series.icon = '';
        this.state.roleCatalog = next;
        this.renderRoleSeriesTab();
      });
      $('[data-role-series-remove]').off('click.roleSeries').on('click.roleSeries', (e) => {
        const index = Number($(e.currentTarget).data('role-series-remove'));
        const next = JSON.parse(JSON.stringify(this.state.roleCatalog || { series: [], roles: [] }));
        next.series = (next.series || []).filter((_, rowIndex) => rowIndex !== index);
        const removedIds = new Set((this.state.roleCatalog?.series || []).filter((_, rowIndex) => rowIndex === index).map((series) => String(series.id || '')));
        next.roles = (next.roles || []).map((entry) => {
          if (removedIds.has(String(entry.seriesId || ''))) {
            return { ...entry, seriesId: '' };
          }
          return entry;
        });
        this.state.roleCatalog = next;
        this.renderRoleSeriesTab();
      });
      $('#roleSeriesSaveButton').off('click.roleSeries').on('click.roleSeries', () => this.saveRoleCatalogConfig());
    },
    upsertRoleCatalogMeta(roleId, updates) {
      const config = JSON.parse(JSON.stringify(this.state.roleCatalog || { series: [], roles: [] }));
      config.roles = Array.isArray(config.roles) ? config.roles : [];
      const currentIndex = config.roles.findIndex((entry) => String(entry.roleId) === String(roleId));
      if (currentIndex === -1) {
        config.roles.push({ roleId: String(roleId), tier: '', seriesId: '', ...updates });
      } else {
        config.roles[currentIndex] = { ...config.roles[currentIndex], ...updates, roleId: String(roleId) };
      }
      this.state.roleCatalog = config;
    },
    openRoleCatalogModal(role) {
      if (!role) return;
      const config = this.state.roleCatalog || { series: [], roles: [] };
      const current = (config.roles || []).find((entry) => String(entry.roleId) === String(role.roleId)) || {};
      const tierOptions = ['<option value="">ไม่กำหนด</option>'].concat((this.state.roleTierOptions || ['S', 'A', 'B', 'C']).map((tier) => `<option value="${this.escAttr(tier)}">${this.esc(tier)}</option>`)).join('');
      const seriesOptions = ['<option value="">ไม่อยู่ใน Series</option>'].concat((config.series || []).map((series) => `<option value="${this.escAttr(series.id)}">${this.esc(series.name || series.id)}</option>`)).join('');
      $('#roleCatalogModal').remove();
      $('body').append(`
        <div class="ui small modal" id="roleCatalogModal">
          <div class="header">ตั้งค่า Tier / Series ของยศ</div>
          <div class="content">
            <div class="orbit-muted" style="margin-bottom:12px">${this.esc(role.roleName || role.roleId)} · ${this.esc(role.roleId)}</div>
            <form class="ui form" id="roleCatalogForm">
              <div class="two fields">
                <div class="field">
                  <label>${this.fieldLabel('Tier', 'กำหนดเธียร์ S/A/B/C เพื่อใช้โชว์ต่อท้ายชื่อยศในระบบรางวัล')}</label>
                  <select class="ui dropdown" name="tier">${tierOptions}</select>
                </div>
                <div class="field">
                  <label>${this.fieldLabel('Series', 'เลือก series ที่ยศนี้จะถูกจัดกลุ่มร่วมกัน')}</label>
                  <select class="ui dropdown" name="seriesId">${seriesOptions}</select>
                </div>
              </div>
            </form>
          </div>
          <div class="actions">
            <button class="ui button deny">ยกเลิก</button>
            <button class="ui primary button" id="saveRoleCatalogButton"><i class="fa-solid fa-floppy-disk"></i> Save</button>
          </div>
        </div>`);
      $('#roleCatalogForm [name="tier"]').val(current.tier || '').dropdown();
      $('#roleCatalogForm [name="seriesId"]').val(current.seriesId || '').dropdown();
      $('#saveRoleCatalogButton').off('click.roleCatalog').on('click.roleCatalog', () => {
        const tier = String($('#roleCatalogForm [name="tier"]').val() || '').trim();
        const seriesId = String($('#roleCatalogForm [name="seriesId"]').val() || '').trim();
        this.upsertRoleCatalogMeta(role.roleId, { tier, seriesId });
        this.saveRoleCatalogConfig(() => $('#roleCatalogModal').modal('hide'));
      });
      $('#roleCatalogModal').modal({ autofocus: false }).modal('show');
    },
    saveRoleCatalogConfig(onDone) {
      this.syncRoleSeriesDrafts();
      const config = this.state.roleCatalog || { series: [], roles: [] };
      $('#roleSeriesSaveButton').addClass('loading disabled');
      this.api('/api/admin/role_catalog.php', { config }, 'POST')
        .done((res) => {
          this.state.roleCatalog = JSON.parse(JSON.stringify(res.config || config));
          this.toast('บันทึก Series / Tier ยศแล้ว');
          this.loadRoles();
          if (typeof onDone === 'function') onDone();
        })
        .fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.save_failed')))
        .always(() => $('#roleSeriesSaveButton').removeClass('loading disabled'));
    },
    syncRolePermissionDescriptionDrafts() {
      const current = new Map((this.state.rolePermissionDescriptions || []).map((entry) => [String(entry.code), entry]));
      $('#rolePermissionDescriptionsList [data-role-permission-code]').each((_, element) => {
        const row = $(element);
        const code = String(row.data('role-permission-code') || '');
        if (!code || !current.has(code)) return;
        const entry = current.get(code);
        entry.label = String(row.find('[data-role-permission-field="label"]').val() || '').trim();
        entry.badge = String(row.find('[data-role-permission-field="badge"]').val() || '').trim();
        entry.description = String(row.find('[data-role-permission-field="description"]').val() || '').trim();
      });
      this.state.rolePermissionDescriptions = Array.from(current.values());
    },
    renderRolePermissionDescriptions(serverMetrics) {
      this.syncRolePermissionDescriptionDrafts();
      const q = String($('#rolePermissionDescriptionsFilter [name="q"]').val() || '').trim().toLowerCase();
      const defaultsByCode = new Map((this.state.rolePermissionDescriptionDefaults || []).map((entry) => [String(entry.code), entry]));
      const allRows = this.state.rolePermissionDescriptions || [];
      const filteredRows = allRows.filter((entry) => {
        if (!q) return true;
        return [entry.code, entry.label, entry.badge, entry.description, entry.discordLabel]
          .some((value) => String(value || '').toLowerCase().includes(q));
      });
      const customized = allRows.filter((entry) => {
        const fallback = defaultsByCode.get(String(entry.code)) || {};
        return String(entry.label || '') !== String(fallback.label || '') || String(entry.badge || '') !== String(fallback.badge || '') || String(entry.description || '') !== String(fallback.description || '');
      }).length;
      const withDescription = allRows.filter((entry) => String(entry.description || '').trim() !== '').length;
      const withBadge = allRows.filter((entry) => String(entry.badge || '').trim() !== '').length;
      this.metrics('#rolePermissionDescriptionsMetrics', [
        ['Catalog', serverMetrics?.total ?? allRows.length],
        ['Visible', filteredRows.length],
        ['Customized', serverMetrics?.customized ?? customized],
        ['Filled', serverMetrics?.withDescription ?? withDescription],
        ['Badges', serverMetrics?.withBadge ?? withBadge],
      ]);
      $('#rolePermissionDescriptionsList').html(filteredRows.length ? filteredRows.map((entry) => {
        const fallback = defaultsByCode.get(String(entry.code)) || {};
        const rowCustomized = String(entry.label || '') !== String(fallback.label || '') || String(entry.badge || '') !== String(fallback.badge || '') || String(entry.description || '') !== String(fallback.description || '');
        return `
          <article class="orbit-earn-rule" data-role-permission-code="${this.escAttr(entry.code)}">
            <div class="orbit-earn-rule-head">
              <div>
                <strong>${this.esc(entry.label || fallback.label || entry.discordLabel || entry.code)}</strong>
                <span>${this.esc(entry.code)} · bit ${Number(entry.bit || 0)} · Discord: ${this.esc(entry.discordLabel || entry.code)}${(entry.badge || fallback.badge) ? ` · badge: ${this.esc(entry.badge || fallback.badge)}` : ''}</span>
              </div>
              ${rowCustomized ? this.badge('custom', 'partial') : this.badge('default', '')}
            </div>
            <div class="ui small form orbit-earn-rule-grid">
              <div class="field">
                <label>${this.fieldLabel('ชื่อแสดงผลภาษาไทย', 'ชื่อที่ใช้โชว์บนหน้ารางวัลยศ')}</label>
                <input data-role-permission-field="label" maxlength="120" value="${this.escAttr(entry.label || fallback.label || '')}">
              </div>
              <div class="field">
                <label>${this.fieldLabel('Badge คำขายสั้น', 'คำสั้นๆ เช่น ย้าย ปิดไมค์ ตัดเสียง สำหรับขึ้นเป็น badge ในหน้าลิสต์รางวัลยศ')}</label>
                <input data-role-permission-field="badge" maxlength="80" value="${this.escAttr(entry.badge || fallback.badge || '')}">
              </div>
              <div class="field">
                <label>${this.fieldLabel('คำอธิบายสิทธิ์', 'คำอธิบายภาษาไทยที่ใช้เล่าให้ผู้เล่นเห็นว่ายศนี้ปลดล็อกอะไร')}</label>
                <textarea rows="3" data-role-permission-field="description">${this.esc(entry.description || fallback.description || '')}</textarea>
              </div>
            </div>
          </article>
        `;
      }).join('') : this.empty('ไม่พบสิทธิ์ที่ตรงกับคำค้นหา'));
      $('#rolePermissionDescriptionsFilterApplyButton').off('click.rolePermissionDescriptions').on('click.rolePermissionDescriptions', () => this.renderRolePermissionDescriptions());
      $('#rolePermissionDescriptionsFilter [name="q"]').off('keydown.rolePermissionDescriptions').on('keydown.rolePermissionDescriptions', (e) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        this.renderRolePermissionDescriptions();
      });
      $('#rolePermissionDescriptionsResetButton').off('click.rolePermissionDescriptions').on('click.rolePermissionDescriptions', () => {
        const currentByCode = new Map((this.state.rolePermissionDescriptions || []).map((entry) => [String(entry.code), entry]));
        this.state.rolePermissionDescriptions = (this.state.rolePermissionDescriptionDefaults || []).map((fallback) => ({
          ...(currentByCode.get(String(fallback.code)) || {}),
          code: fallback.code,
          label: fallback.label || '',
          badge: fallback.badge || '',
          description: fallback.description || '',
          defaultLabel: fallback.label || '',
          defaultBadge: fallback.badge || '',
          defaultDescription: fallback.description || '',
          isCustomized: false,
        }));
        this.renderRolePermissionDescriptions();
      });
      $('#rolePermissionDescriptionsSaveButton').off('click.rolePermissionDescriptions').on('click.rolePermissionDescriptions', () => this.saveRolePermissionDescriptions());
    },
    saveRolePermissionDescriptions() {
      this.syncRolePermissionDescriptionDrafts();
      const permissions = this.state.rolePermissionDescriptions || [];
      $('#rolePermissionDescriptionsSaveButton').addClass('loading disabled');
      this.api('/api/admin/role_permission_descriptions.php', { permissions }, 'POST')
        .done((res) => {
          this.state.rolePermissionDescriptions = JSON.parse(JSON.stringify(res.permissions || permissions));
          this.toast('บันทึกคำอธิบายสิทธิ์ยศแล้ว');
          this.renderRolePermissionDescriptions(res.metrics || {});
        })
        .fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.save_failed')))
        .always(() => $('#rolePermissionDescriptionsSaveButton').removeClass('loading disabled'));
    },
    renderEarnSettings() {
      const triggerLabels = {
        earn_text_active_daily: 'Text chat active minutes',
        earn_voice_hourly: 'Voice accumulated time',
        earn_member_first_join: 'First server join',
        earn_invite_member: 'Invite member join',
      };
      const triggerHints = {
        earn_text_active_daily: 'นับนาทีที่ user มีข้อความในห้องแชทของวันนั้น ครบ threshold แล้วแจกตามจำนวน segment และ daily limit',
        earn_voice_hourly: 'นับเวลาห้องเสียงจาก daily summary ครบช่วงที่ตั้งไว้แล้วแจกซ้ำเป็นรอบๆ เช่น ทุก 1 ชั่วโมง',
        earn_member_first_join: 'แจกให้ user เมื่อเจอ GUILD_MEMBER_ADD ครั้งแรกสุดของ user นั้นในเซิร์ฟ',
        earn_invite_member: 'แจกให้ผู้เชิญจาก log ห้อง 1481271035906097164 ที่ match กับ GUILD_MEMBER_ADD ได้เท่านั้น ไม่สร้าง custom event key',
      };
      $('#earnSettingsList').html((this.state.earnRules || []).map((rule, index) => {
        const minValue = rule.triggerType === 'earn_voice_hourly'
          ? Math.max(1, Math.round(Number(rule.minSeconds || 3600) / 60))
          : Number(rule.minMinutes || 0);
        const hasThreshold = !['earn_member_first_join', 'earn_invite_member', 'earn_manual'].includes(rule.triggerType);
        const minLabel = rule.triggerType === 'earn_voice_hourly' ? 'Threshold minutes' : (hasThreshold ? 'Active minutes' : 'Threshold');
        const channelIds = Array.isArray(rule.channelIds) ? rule.channelIds.join(', ') : String(rule.channelIds || '');
        const categoryIds = Array.isArray(rule.categoryIds) ? rule.categoryIds.join(', ') : String(rule.categoryIds || '');
        const textAdvanced = rule.triggerType === 'earn_text_active_daily' ? `
          <div class="ui small form gacha-prize-fieldset orbit-earn-advanced">
            <div class="gacha-prize-fieldset-title">Advanced text conditions</div>
            <div class="orbit-earn-advanced-caption">Refine how text activity is counted with thresholds, time windows, and channel/category filters.</div>
            <div class="four fields">
              <div class="field"><label>${this.fieldLabel('Min Messages', 'จำนวนข้อความขั้นต่ำต่อวัน นอกจาก active minutes')}</label><input type="number" min="0" step="1" data-earn-field="minMessages" value="${this.escAttr(rule.minMessages || 0)}"></div>
              <div class="field"><label>${this.fieldLabel('Unique Channels', 'ต้องคุยกี่ห้องไม่ซ้ำในวันนั้น')}</label><input type="number" min="0" step="1" data-earn-field="minUniqueChannels" value="${this.escAttr(rule.minUniqueChannels || 0)}"></div>
              <div class="field"><label>${this.fieldLabel('Start Time', 'ช่วงเวลาเริ่มนับข้อความ ถ้าว่างจะนับทั้งวัน')}</label><input type="time" data-earn-field="timeStart" value="${this.escAttr(rule.timeStart || '')}"></div>
              <div class="field"><label>${this.fieldLabel('End Time', 'ช่วงเวลาสิ้นสุด รองรับช่วงข้ามคืน เช่น 22:00-02:00')}</label><input type="time" data-earn-field="timeEnd" value="${this.escAttr(rule.timeEnd || '')}"></div>
            </div>
            <div class="three fields">
              <div class="field">
                <label>${this.fieldLabel('Channel Filter', 'เลือกว่าจะใช้ทุกห้อง, เฉพาะห้องที่ระบุ, หรือยกเว้นห้องที่ระบุ')}</label>
                <select class="ui dropdown" data-earn-field="channelMode">
                  <option value="all">ทุกห้องข้อความ</option>
                  <option value="allow">เฉพาะ channel ids</option>
                  <option value="deny">ยกเว้น channel ids</option>
                </select>
              </div>
              <div class="field"><label>${this.fieldLabel('Channel IDs', 'ใส่ channel id คั่นด้วย comma หรือเว้นบรรทัด')}</label><textarea rows="4" data-earn-field="channelIds">${this.esc(channelIds)}</textarea></div>
              <div class="field"><label>${this.fieldLabel('Category IDs', 'จำกัดเฉพาะ category ids ที่ระบุ คั่นด้วย comma หรือเว้นบรรทัด')}</label><textarea rows="4" data-earn-field="categoryIds">${this.esc(categoryIds)}</textarea></div>
            </div>
          </div>
        ` : '';
        const unitRewards = rule.unitRewards || rule.reward?.unitRewards || {};
        const rewardUnitFields = (this.state.earnCurrencies || []).map((unit) => {
          const code = String(unit.code || unit.unitCode || '');
          if (!code) return '';
          const label = unit.label || unit.displayName || unit.shortName || code;
          return `<div class="field"><label>${this.fieldLabel(label, `จำนวน ${label} ที่แจกต่อครั้ง`)}</label><input type="number" min="0" step="1" data-earn-unit="${this.escAttr(code)}" value="${this.escAttr(unitRewards[code] ?? (code === 'coin' ? rule.coin : code === 'ticket' ? rule.gachaTicket : 0) ?? 0)}"></div>`;
        }).join('');
        return `
          <article class="orbit-earn-rule" data-earn-index="${index}">
            <div class="orbit-earn-rule-head">
              <div>
                <strong>${this.esc(rule.ruleName || rule.ruleCode)}</strong>
                <span>${this.esc(triggerLabels[rule.triggerType] || rule.triggerType)} ${this.infoTip(triggerHints[rule.triggerType] || 'Earn rule')}</span>
              </div>
              <label class="ui toggle checkbox"><input type="checkbox" data-earn-field="isActive" ${Number(rule.isActive) === 1 ? 'checked' : ''}><span>Active</span></label>
            </div>
            <div class="ui small form orbit-earn-rule-grid">
              <div class="field"><label>${this.fieldLabel('Name', 'ชื่อ rule ที่แสดงในระบบหลังบ้าน')}</label><input data-earn-field="ruleName" value="${this.escAttr(rule.ruleName || '')}"></div>
              <div class="field"><label>${this.fieldLabel(minLabel, 'เกณฑ์ขั้นต่ำก่อนแจก ถ้าเป็น voice ให้กรอกเป็นนาที')}</label><input type="number" min="0" step="1" data-earn-field="thresholdMinutes" value="${this.escAttr(minValue)}" ${hasThreshold ? '' : 'disabled'}></div>
              ${rewardUnitFields}
              <div class="field"><label>${this.fieldLabel('Free Spin', 'สิทธิ์สุ่มฟรีในหน้าเกม ไม่เก็บเป็นตั๋ว ใช้ได้วันต่อวัน')}</label><input type="number" min="0" max="1" step="1" data-earn-field="gachaFreeSpin" value="${this.escAttr(rule.gachaFreeSpin || 0)}"></div>
              <div class="field"><label>${this.fieldLabel('Daily Limit', 'จำนวนครั้งสูงสุดต่อ user ต่อวันสำหรับ rule นี้')}</label><input type="number" min="0" step="1" data-earn-field="dailyLimit" value="${this.escAttr(rule.dailyLimit || 1)}"></div>
              <div class="field"><label>Granted Today</label><div class="orbit-inline-summary-item"><strong>${Number(rule.grantedToday || 0).toLocaleString()}</strong><span>events</span></div></div>
            </div>
            ${textAdvanced}
          </article>
        `;
      }).join(''));
      $('#earnSettingsList [data-earn-index]').each((index, row) => {
        const rule = this.state.earnRules[index] || {};
        $(row).find('[data-earn-field="channelMode"]').val(rule.channelMode || 'all');
      });
      $('#earnSettingsList .ui.checkbox').checkbox();
      this.normalizeVisibleCheckboxes('#earnSettingsList');
      $('#earnSettingsList .ui.dropdown').dropdown();
      $('#earnSettingsSaveButton').off('click').on('click', () => this.saveEarnSettings());
    },
    collectEarnSettings() {
      return (this.state.earnRules || []).map((rule, index) => {
        const row = $(`#earnSettingsList [data-earn-index="${index}"]`);
        const thresholdMinutes = Number(row.find('[data-earn-field="thresholdMinutes"]').val() || 0);
        const unitRewards = {};
        row.find('[data-earn-unit]').each((_, input) => {
          const unitCode = String($(input).data('earn-unit') || '');
          if (!unitCode) return;
          unitRewards[unitCode] = Number($(input).val() || 0);
        });
        return {
          ruleCode: rule.ruleCode,
          ruleName: row.find('[data-earn-field="ruleName"]').val() || rule.ruleName,
          isActive: row.find('[data-earn-field="isActive"]').is(':checked'),
          minMinutes: rule.triggerType === 'earn_text_active_daily' ? thresholdMinutes : Number(rule.minMinutes || 0),
          minSeconds: rule.triggerType === 'earn_voice_hourly' ? thresholdMinutes * 60 : Number(rule.minSeconds || 0),
          dailyLimit: Number(row.find('[data-earn-field="dailyLimit"]').val() || 0),
          minMessages: Number(row.find('[data-earn-field="minMessages"]').val() || rule.minMessages || 0),
          minUniqueChannels: Number(row.find('[data-earn-field="minUniqueChannels"]').val() || rule.minUniqueChannels || 0),
          channelMode: row.find('[data-earn-field="channelMode"]').val() || rule.channelMode || 'all',
          channelIds: row.find('[data-earn-field="channelIds"]').val() || (Array.isArray(rule.channelIds) ? rule.channelIds.join(',') : ''),
          categoryIds: row.find('[data-earn-field="categoryIds"]').val() || (Array.isArray(rule.categoryIds) ? rule.categoryIds.join(',') : ''),
          timeStart: row.find('[data-earn-field="timeStart"]').val() || rule.timeStart || '',
          timeEnd: row.find('[data-earn-field="timeEnd"]').val() || rule.timeEnd || '',
          requireResolvedInviter: rule.triggerType === 'earn_invite_member' ? true : Boolean(rule.requireResolvedInviter),
          unitRewards,
          coin: Number(unitRewards.coin || 0),
          gachaTicket: Number(unitRewards.ticket || 0),
          gachaFreeSpin: Number(row.find('[data-earn-field="gachaFreeSpin"]').val() || 0),
        };
      });
    },
    saveEarnSettings() {
      this.api('/api/admin/earn_settings.php', { rules: this.collectEarnSettings() }, 'POST')
        .done((res) => {
          this.state.earnRules = res.rules || [];
        this.toast('toast.earn_saved');
          $('#earnSettingsResult').html(this.jsonSummary({ savedRules: this.state.earnRules.length }));
          this.renderEarnSettings();
        })
        .fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.earn_save_failed')));
    },
    loadEarnManual() {
      this.api('/api/data/earn_manual.php').done((res) => {
        this.state.earnManualUnits = res.units || [];
        this.state.earnManualRoles = res.roles || [];
        this.state.earnManualMemberCount = Number(res.memberCount || 0);
        this.state.earnManualSearchResults = [];
        const grantables = this.earnManualGrantables();
        const draft = this.state.earnManualDraft || {};
        this.state.earnManualDraft = {
          targetType: ['user', 'role', 'server'].includes(String(draft.targetType || '')) ? String(draft.targetType) : 'user',
          targetUser: draft.targetUser && draft.targetUser.userId ? draft.targetUser : null,
          targetRoleId: String(draft.targetRoleId || ''),
          rewardType: String(draft.rewardType || grantables[0]?.unitCode || 'coin'),
          amount: Math.max(1, Number(draft.amount || 1) || 1),
          reason: String(draft.reason || ''),
          query: String(draft.query || ''),
        };
        this.metrics('#earnManualMetrics', [
          ['metric.currencies', grantables.length],
          ['Members', this.state.earnManualMemberCount],
          ['Roles', this.state.earnManualRoles.length],
          ['metric.recent', (res.recent || []).length],
        ]);
        this.renderEarnManualForm();
        this.table('#earnManualRecentTable', [
          ['table.time', 'createDate'],
          ['table.user', 'displayName', (row) => this.userCell(row)],
          ['Target', 'targetLabel', (row) => this.esc(this.manualEarnTargetSummaryText(row))],
          ['table.amount', 'unitRewards', (row) => this.rewardReportUnitSummary(row.unitRewards || {})],
          ['table.reason', 'reason', (row) => this.esc(row.reason || '-')],
          ['table.staff', 'grantedBy', (row) => this.esc(row.grantedBy || '-')],
        ], res.recent || [], { id: 'earnManualRecentTable' });
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลด Manual Earn ไม่สำเร็จ'));
    },
    earnManualGrantables() {
      const units = (this.state.earnManualUnits || []).map((unit) => ({ ...unit, unitCode: String(unit.unitCode || '') }));
      return [
        ...units,
        {
          unitCode: 'freeSpin',
          displayName: 'Free Spin',
          shortName: 'Free Spin',
          icon: 'fa-solid fa-wand-sparkles',
        },
      ];
    },
    earnManualSelectedRole() {
      const roleId = String(this.state.earnManualDraft?.targetRoleId || '');
      return (this.state.earnManualRoles || []).find((role) => String(role.roleId || '') === roleId) || null;
    },
    renderEarnManualForm() {
      const draft = this.state.earnManualDraft || {};
      const grantables = this.earnManualGrantables();
      $('#earnManualRewardType').html(grantables.map((unit) => `<option value="${this.escAttr(unit.unitCode)}">${this.esc(unit.displayName || unit.unitCode)}</option>`).join(''));
      $('#earnManualRewardType').val(String(draft.rewardType || grantables[0]?.unitCode || 'coin'));
      $('#earnManualRoleSelect').html([
        '<option value="">เลือกยศ</option>',
        ...(this.state.earnManualRoles || []).map((role) => `<option value="${this.escAttr(role.roleId)}">${this.esc(role.roleName)} (${Number(role.memberCount || 0).toLocaleString()})</option>`),
      ].join(''));
      $('#earnManualRoleSelect').val(String(draft.targetRoleId || ''));
      $('#earnManualAmount').val(String(Math.max(1, Number(draft.amount || 1) || 1)));
      $('#earnManualForm [name="reason"]').val(String(draft.reason || ''));
      $('#earnManualForm [name="q"]').val(String(draft.query || ''));
      $('#earnManualTargetType [data-earn-target-type]').removeClass('active').each((_, node) => {
        $(node).toggleClass('active', $(node).data('earn-target-type') === draft.targetType);
      });
      $('#earnManualRoleSelect').dropdown({ fullTextSearch: true, clearable: true });
      this.syncEarnManualRewardConstraints();
      this.syncEarnManualTargetUi();
      this.renderEarnManualTargetSummary();
      this.renderEarnManualSearchResults();
      $('#earnManualTargetType [data-earn-target-type]').off('click').on('click', (event) => {
        this.state.earnManualDraft.targetType = String($(event.currentTarget).data('earn-target-type') || 'user');
        $('#earnManualTargetType [data-earn-target-type]').removeClass('active');
        $(event.currentTarget).addClass('active');
        this.syncEarnManualTargetUi();
        this.renderEarnManualTargetSummary();
      });
      $('#earnManualRewardType').off('change').on('change', (event) => {
        this.state.earnManualDraft.rewardType = String($(event.currentTarget).val() || 'coin');
        this.syncEarnManualRewardConstraints();
      });
      $('#earnManualAmount').off('input').on('input', (event) => {
        this.state.earnManualDraft.amount = Math.max(1, Number($(event.currentTarget).val() || 1) || 1);
      });
      $('#earnManualForm [name="reason"]').off('input').on('input', (event) => {
        this.state.earnManualDraft.reason = String($(event.currentTarget).val() || '');
      });
      $('#earnManualRoleSelect').off('change').on('change', (event) => {
        this.state.earnManualDraft.targetRoleId = String($(event.currentTarget).val() || '');
        this.renderEarnManualTargetSummary();
      });
      $('#earnManualSearchButton').off('click').on('click', () => this.searchEarnManualUsers());
      $('#earnManualForm [name="q"]').off('keydown').on('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          this.searchEarnManualUsers();
        }
      }).off('input').on('input', (event) => {
        this.state.earnManualDraft.query = String($(event.currentTarget).val() || '');
      });
      $('#earnManualGrantButton').off('click').on('click', () => this.grantEarnManual());
    },
    syncEarnManualTargetUi() {
      const targetType = String(this.state.earnManualDraft?.targetType || 'user');
      $('#earnManualUserLookupField').toggle(targetType === 'user');
      $('#earnManualSearchWrap').toggle(targetType === 'user');
      $('#earnManualRoleLookupField').toggle(targetType === 'role');
      if (targetType !== 'user') {
        this.state.earnManualSearchResults = [];
        this.renderEarnManualSearchResults();
      }
    },
    syncEarnManualRewardConstraints() {
      const rewardType = String(this.state.earnManualDraft?.rewardType || 'coin');
      const amount = $('#earnManualAmount');
      if (rewardType === 'freeSpin') {
        amount.attr('step', '1').attr('min', '1');
      } else {
        amount.attr('step', '1').attr('min', '1');
      }
    },
    renderEarnManualSearchResults() {
      const rows = this.state.earnManualSearchResults || [];
      if (!rows.length) {
        $('#earnManualUserResults').html('<span class="orbit-muted">ค้นหา user เพื่อเลือกเป้าหมาย</span>');
        return;
      }
      $('#earnManualUserResults').html(rows.map((row, index) => `
        <button class="orbit-picker-option" type="button" data-earn-manual-search-index="${index}">
          <img src="${this.escAttr(row.avatarUrl || '')}" alt="">
          <span><strong>${this.esc(row.displayName || row.userId)}</strong><span>${this.esc(row.userName ? '@' + row.userName : row.userId || '')}</span></span>
        </button>
      `).join(''));
      $('#earnManualUserResults [data-earn-manual-search-index]').off('click').on('click', (event) => {
        const row = rows[Number($(event.currentTarget).data('earn-manual-search-index') || 0)];
        if (!row) return;
        this.state.earnManualDraft.targetUser = row;
        this.renderEarnManualTargetSummary();
      });
    },
    renderEarnManualTargetSummary() {
      const draft = this.state.earnManualDraft || {};
      const targetType = String(draft.targetType || 'user');
      let html = '<span class="orbit-muted">ยังไม่ได้เลือกเป้าหมาย</span>';
      if (targetType === 'user' && draft.targetUser?.userId) {
        html = `
          <span class="orbit-picker-chip">
            <img src="${this.escAttr(draft.targetUser.avatarUrl || '')}" alt="">
            <span><strong>${this.esc(draft.targetUser.displayName || draft.targetUser.userId)}</strong><span>${this.esc(draft.targetUser.userId || '')}</span></span>
            <button type="button" data-earn-manual-clear-user><i class="fa-solid fa-xmark"></i></button>
          </span>
        `;
      } else if (targetType === 'role') {
        const role = this.earnManualSelectedRole();
        html = role
          ? `<span class="orbit-picker-chip"><strong>${this.esc(role.roleName || '')}</strong><span>${Number(role.memberCount || 0).toLocaleString()} members</span></span>`
          : '<span class="orbit-muted">เลือกยศที่จะจ่ายให้ทั้งยศ</span>';
      } else if (targetType === 'server') {
        html = `<span class="orbit-picker-chip"><strong>ทั้งเซิร์ฟเวอร์</strong><span>${Number(this.state.earnManualMemberCount || 0).toLocaleString()} members</span></span>`;
      }
      $('#earnManualTargetSummary').html(html);
      $('#earnManualTargetSummary [data-earn-manual-clear-user]').off('click').on('click', () => {
        this.state.earnManualDraft.targetUser = null;
        this.renderEarnManualTargetSummary();
      });
    },
    searchEarnManualUsers() {
      const q = String($('#earnManualForm [name="q"]').val() || '').trim();
      this.state.earnManualDraft.query = q;
      if (q.length < 2) {
        this.state.earnManualSearchResults = [];
        this.renderEarnManualSearchResults();
        return;
      }
      this.lookupDirectoryMembers(q).done((rows) => {
        this.state.earnManualSearchResults = rows;
        this.renderEarnManualSearchResults();
      }).fail((xhr) => this.toast(xhr.responseJSON?.message || 'ค้นหาผู้ใช้ไม่สำเร็จ'));
    },
    manualEarnTargetSummaryText(row) {
      const targetType = String(row?.targetType || 'user');
      const targetLabel = String(row?.targetLabel || row?.displayName || row?.userId || '');
      const recipientCount = Number(row?.recipientCount || 1);
      if (targetType === 'role') return `${targetLabel} (${recipientCount.toLocaleString()} คน)`;
      if (targetType === 'server') return `ทั้งเซิร์ฟเวอร์ (${recipientCount.toLocaleString()} คน)`;
      return targetLabel || '-';
    },
    collectEarnManualPayload() {
      const draft = this.state.earnManualDraft || {};
      return {
        targetType: String(draft.targetType || 'user'),
        targetUserId: String(draft.targetUser?.userId || ''),
        targetRoleId: String(draft.targetRoleId || ''),
        rewardType: String(draft.rewardType || ''),
        amount: Math.max(1, Number(draft.amount || 1) || 1),
        reason: String(draft.reason || '').trim(),
      };
    },
    grantEarnManual() {
      const payload = this.collectEarnManualPayload();
      this.api('/api/admin/earn_manual.php', payload, 'POST').done((res) => {
        $('#earnManualResult').html(this.jsonSummary({
          batchId: res.batchId,
          targetType: res.targetType,
          targetLabel: res.targetLabel,
          recipientCount: res.recipientCount,
          unitRewards: res.unitRewards,
          eventCount: res.eventCount,
        }));
        this.state.earnManualDraft = {
          ...(this.state.earnManualDraft || {}),
          amount: 1,
          reason: '',
          query: '',
        };
        if (String(payload.targetType || '') === 'user') {
          this.state.earnManualDraft.targetUser = null;
        }
        this.state.earnManualSearchResults = [];
        this.loadEarnManual();
        this.toast('toast.earn_manual_granted');
      }).fail((xhr) => this.toast(xhr.responseJSON?.message || 'Manual grant failed'));
    },
    lookupDirectoryMembers(q) {
      const deferred = $.Deferred();
      const query = String(q || '').trim();
      if (query.length < 2) {
        deferred.resolve([]);
        return deferred.promise();
      }
      this.api('/api/data/search.php', { q: query, type: 'member' }).done((res) => {
        const rows = (res.items || [])
          .filter((item) => String(item.type || '') === 'member')
          .map((item) => ({
            userId: String(item.id || ''),
            displayName: String(item.title || item.id || ''),
            userName: String((item.subtitle || '').replace(/^@/, '').split(' · ')[0] || ''),
            avatarUrl: String(item.avatarUrl || ''),
          }));
        deferred.resolve(rows);
      }).fail((xhr) => deferred.reject(xhr));
      return deferred.promise();
    },
    loadGachaShop() {
      this.api('/api/data/gacha_shop.php').done((res) => {
        this.state.shopConfig = res.config || { settings: {}, products: [], templates: [] };
        this.state.shopUnits = res.units || [];
        this.state.shopRoles = res.roles || [];
        this.state.shopRoleSeries = res.roleSeries || [];
        this.state.shopAssets = res.assets || [];
        this.metrics('#shopMetrics', [
          ['metric.currencies', res.metrics?.units],
          ['metric.active', res.metrics?.enabledUnits],
          ['metric.prizes', res.metrics?.products],
          ['metric.role_prizes', res.metrics?.roleProducts],
          ['metric.item_prizes', res.metrics?.itemProducts],
          ['metric.visible', res.metrics?.visibleProducts],
        ]);
        this.renderShopUnits();
        this.renderShopRoleSeriesConfigs();
        this.renderShopProducts();
        this.renderShopTemplates();
        this.bindTabs('#pageContainer');
        $('#shopSaveButton').off('click').on('click', (event) => {
          event.preventDefault();
          event.stopPropagation();
          this.saveGachaShop();
        });
        $('#shopAddRoleProductButton').off('click').on('click', (event) => {
          event.preventDefault();
          this.addShopProduct('role');
        });
        $('#shopAddRoleSeriesButton').off('click').on('click', (event) => {
          event.preventDefault();
          this.addShopRoleSeriesProducts(String($('#shopRoleSeriesSeed').val() || '').trim());
        });
        $('#shopAddItemProductButton').off('click').on('click', (event) => {
          event.preventDefault();
          this.addShopItemProduct();
        });
        $('#shopAddItemGroupButton').off('click').on('click', (event) => {
          event.preventDefault();
          this.addShopItemGroup();
        });
        $('#shopAddTemplateButton').off('click').on('click', (event) => {
          event.preventDefault();
          this.addShopTemplate();
        });
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลด Shop Setting ไม่สำเร็จ'));
    },
    shopAssetKind(path = '', fallback = 'image') {
      const normalized = String(path || '').trim();
      if (!normalized) return fallback;
      if (/\.(mp4|webm|mov|m4v)(?:[?#].*)?$/i.test(normalized)) return 'video';
      if (/\.(png|jpe?g|webp|gif)(?:[?#].*)?$/i.test(normalized)) return 'image';
      return fallback;
    },
    shopAssetAllowed(asset = {}, allowedKinds = []) {
      const kinds = Array.isArray(allowedKinds) ? allowedKinds.filter(Boolean) : [];
      if (!kinds.length) return true;
      const kind = String(asset.kind || this.shopAssetKind(asset.path || '', 'image'));
      if (kind === 'icon' && kinds.includes('image')) return true;
      return kinds.includes(kind);
    },
    shopAssetMediaHtml(url = '', kind = 'image', label = '', style = '', className = '') {
      if (!url) return '';
      const classAttr = className ? ` class="${this.escAttr(className)}"` : '';
      const styleAttr = style ? ` style="${this.escAttr(style)}"` : '';
      if (kind === 'video') {
        return `<video${classAttr} src="${this.escAttr(url)}" muted autoplay loop playsinline webkit-playsinline preload="metadata" disablepictureinpicture aria-label="${this.escAttr(label || 'Preview')}"${styleAttr}></video>`;
      }
      return `<img${classAttr} src="${this.escAttr(url)}" alt="${this.escAttr(label || '')}"${styleAttr}>`;
    },
    shopAssetMeta(path = '') {
      const normalized = String(path || '').trim();
      const assets = this.state.shopAssets || [];
      const found = assets.find((asset) => String(asset.path || '') === normalized);
      if (found) {
        return {
          ...found,
          kind: String(found.kind || this.shopAssetKind(normalized, 'image')),
          name: String(found.name || normalized.split('/').pop() || normalized),
          description: String(found.description || found.path || normalized),
          url: String(found.url || this.gachaAssetUrl(normalized)),
        };
      }
      const isImagePath = /^https?:\/\//i.test(normalized)
        || normalized.startsWith('/')
        || normalized.startsWith('images/')
        || normalized.startsWith('uploads/');
      if (!normalized) {
        return {
          path: '',
          name: 'ไม่ใช้',
          description: 'ไม่เลือกไฟล์',
          url: '',
          kind: 'none',
        };
      }
      return {
        path: normalized,
        name: normalized.split('/').pop() || normalized,
        description: isImagePath ? normalized : 'ค่าเดิมแบบ text/class',
        url: isImagePath ? (/^https?:\/\//i.test(normalized) || normalized.startsWith('/') ? normalized : `${this.baseUrl}/gacha/${normalized.replace(/^\/+/, '')}`) : '',
        kind: isImagePath ? this.shopAssetKind(normalized, 'image') : 'custom',
      };
    },
    shopAssetOptions(selectedPath = '', allowedKinds = []) {
      const current = this.shopAssetMeta(selectedPath);
      const rows = [{
        path: '',
        name: 'ไม่ใช้',
        description: 'ไม่เลือกไฟล์',
        url: '',
        kind: 'none',
      }];
      if (current.path) {
        rows.push(current);
      }
      (this.state.shopAssets || []).forEach((asset) => {
        const meta = this.shopAssetMeta(asset.path || '');
        if (!this.shopAssetAllowed(meta, allowedKinds) && String(meta.path || '') !== String(current.path || '')) {
          return;
        }
        if (rows.some((entry) => String(entry.path || '') === String(meta.path || ''))) {
          return;
        }
        rows.push(meta);
      });
      return rows.map((asset) => {
        const selected = String(asset.path || '') === String(selectedPath || '');
        const hasPreview = Boolean(asset.url);
        const kind = String(asset.kind || this.shopAssetKind(asset.path || '', 'image'));
        const thumb = !hasPreview
          ? '<i class="fa-solid fa-ban"></i>'
          : this.shopAssetMediaHtml(asset.url, kind, asset.name || asset.path || '', '', '');
        return `
          <div class="item${selected ? ' active selected' : ''}" data-value="${this.escAttr(asset.path || '')}" data-text="${this.escAttr(asset.name || asset.path || 'ไม่ใช้')}">
            <span class="gacha-asset-option${hasPreview ? ' is-previewable' : ''}"${hasPreview ? ` data-gacha-asset-preview-url="${this.escAttr(asset.url)}" data-gacha-asset-preview-label="${this.escAttr(asset.name || asset.path || '')}" data-gacha-asset-preview-kind="${this.escAttr(kind)}"` : ''}>
              <span class="gacha-asset-option-thumb${hasPreview ? '' : ' is-empty'}">
                ${thumb}
              </span>
              <span class="gacha-asset-option-copy">
                <strong>${this.esc(asset.name || asset.path || 'ไม่ใช้')}</strong>
                <span>${this.esc(asset.description || asset.path || 'ไม่ใช้ไฟล์นี้')}</span>
              </span>
            </span>
          </div>`;
      }).join('');
    },
    shopAssetPicker(value, field, label, hint, extraAttrs = '', allowedKinds = []) {
      const current = this.shopAssetMeta(value);
      return `
        <div class="field">
          <label>${this.fieldLabel(label, hint)}</label>
          <div class="ui fluid search selection dropdown gacha-asset-dropdown">
            <input type="hidden" ${extraAttrs} data-shop-asset-field="${this.escAttr(field)}" value="${this.escAttr(value || '')}">
            <i class="dropdown icon"></i>
            <div class="text">${this.esc(current.name || 'ไม่ใช้')}</div>
            <div class="menu">${this.shopAssetOptions(value || '', allowedKinds)}</div>
          </div>
        </div>`;
    },
    shopRoleSeriesOptions(selectedSeriesId = '') {
      const selected = String(selectedSeriesId || '');
      return ['<option value="">เลือก series ยศ</option>'].concat((this.state.shopRoleSeries || []).map((series) => {
        const seriesId = String(series.id || '');
        const suffix = [series.badge || '', series.description || ''].filter(Boolean).join(' · ');
        return `<option value="${this.escAttr(seriesId)}"${seriesId === selected ? ' selected' : ''}>${this.esc(series.name || seriesId)}${suffix ? ` · ${this.esc(suffix)}` : ''}</option>`;
      })).join('');
    },
    shopBuyThemeFallback() {
      return {
        holdSeconds: 1,
        buttonBgStart: '#4b3161',
        buttonBgEnd: '#7657b7',
        buttonTextColor: '#ffffff',
        holdSweepStart: '#f8b86f',
        holdSweepEnd: '#ffe590',
      };
    },
    shopNormalizeHexColor(value, fallback = '', allowBlank = false) {
      const text = String(value || '').trim().toLowerCase();
      if (!text) return allowBlank ? '' : fallback;
      if (!/^#?[0-9a-f]{6}$/.test(text)) return allowBlank ? '' : fallback;
      return text.startsWith('#') ? text : `#${text}`;
    },
    shopNormalizeHoldSeconds(value, fallback = 1, allowBlank = false) {
      const text = String(value ?? '').trim();
      if (!text) return allowBlank ? '' : fallback;
      const numeric = Number(text);
      if (!Number.isFinite(numeric) || numeric < 0.2 || numeric > 10) {
        return allowBlank ? '' : fallback;
      }
      return Math.round(numeric * 100) / 100;
    },
    shopNormalizeBuyTheme(theme, fallback, allowBlank = false) {
      const nextFallback = fallback || this.shopBuyThemeFallback();
      const source = theme && typeof theme === 'object' ? theme : {};
      return {
        overrideEnabled: Boolean(source.overrideEnabled),
        holdSeconds: this.shopNormalizeHoldSeconds(source.holdSeconds, Number(nextFallback.holdSeconds || 1), allowBlank),
        buttonBgStart: this.shopNormalizeHexColor(source.buttonBgStart, nextFallback.buttonBgStart || '#4b3161', allowBlank),
        buttonBgEnd: this.shopNormalizeHexColor(source.buttonBgEnd, nextFallback.buttonBgEnd || '#7657b7', allowBlank),
        buttonTextColor: this.shopNormalizeHexColor(source.buttonTextColor, nextFallback.buttonTextColor || '#ffffff', allowBlank),
        holdSweepStart: this.shopNormalizeHexColor(source.holdSweepStart, nextFallback.holdSweepStart || '#f8b86f', allowBlank),
        holdSweepEnd: this.shopNormalizeHexColor(source.holdSweepEnd, nextFallback.holdSweepEnd || '#ffe590', allowBlank),
      };
    },
    shopResolvedBuyDefaults() {
      return this.shopNormalizeBuyTheme(
        this.state.shopConfig?.settings?.shopBuyDefaults || {},
        this.shopBuyThemeFallback(),
        false
      );
    },
    shopUnitBuyThemeOverride(unit) {
      const metadata = unit?.metadata || this.parseJson(unit?.metadataJson) || {};
      return this.shopNormalizeBuyTheme(
        metadata.shopBuyTheme || unit?.shopBuyTheme || {},
        this.shopResolvedBuyDefaults(),
        true
      );
    },
    renderShopBuyDefaults() {
      const defaults = this.shopResolvedBuyDefaults();
      $('#shopBuyDefaultsPanel').html(`
        <article class="gacha-prize-row">
          <div class="gacha-prize-row-main">
            <div class="gacha-prize-row-title">Buy Interaction Defaults</div>
            <div class="orbit-user-sub">ค่าเริ่มต้นกลางของปุ่มจ่ายเงินและการกดค้างยืนยันซื้อ</div>
          </div>
          <div class="ui small form gacha-prize-row-editor" id="shopBuyDefaultsForm">
            <div class="three fields">
              <div class="field"><label>Hold Seconds</label><input type="number" min="0.2" max="10" step="0.1" data-shop-buy-default="holdSeconds" value="${this.escAttr(defaults.holdSeconds)}"></div>
              <div class="field"><label>Button Start</label><input data-shop-buy-default="buttonBgStart" value="${this.escAttr(defaults.buttonBgStart)}" placeholder="#4b3161"></div>
              <div class="field"><label>Button End</label><input data-shop-buy-default="buttonBgEnd" value="${this.escAttr(defaults.buttonBgEnd)}" placeholder="#7657b7"></div>
            </div>
            <div class="three fields">
              <div class="field"><label>Text Color</label><input data-shop-buy-default="buttonTextColor" value="${this.escAttr(defaults.buttonTextColor)}" placeholder="#ffffff"></div>
              <div class="field"><label>Hold Sweep Start</label><input data-shop-buy-default="holdSweepStart" value="${this.escAttr(defaults.holdSweepStart)}" placeholder="#f8b86f"></div>
              <div class="field"><label>Hold Sweep End</label><input data-shop-buy-default="holdSweepEnd" value="${this.escAttr(defaults.holdSweepEnd)}" placeholder="#ffe590"></div>
            </div>
          </div>
        </article>
      `);
    },
    initShopScope(scope) {
      const root = $(scope);
      root.find('.ui.dropdown').dropdown({
        fullTextSearch: true,
        forceSelection: false,
      });
      root.find('.gacha-asset-dropdown').each((_, element) => {
        const dropdown = $(element);
        const hidden = dropdown.find('input[type="hidden"]').first();
        dropdown.dropdown('set selected', String(hidden.val() || ''));
        dropdown.dropdown('setting', 'onHide', () => {
          this.hideGachaAssetPreview();
          return true;
        });
      });
      root.find('.ui.checkbox').checkbox();
      this.normalizeVisibleCheckboxes(root);
      this.bindGachaAssetPreview(scope);
    },
    renderShopUnits() {
      const units = this.state.shopUnits || [];
      const defaults = this.shopResolvedBuyDefaults();
      this.renderShopBuyDefaults();
      $('#shopRoleSeriesSeed').html(this.shopRoleSeriesOptions());
      $('#shopRoleSeriesSeed').dropdown({
        fullTextSearch: true,
        forceSelection: false,
      });
      $('#shopUnitList').html(units.map((unit, index) => `
        <article class="gacha-prize-row" data-shop-unit-index="${index}">
          <div class="gacha-prize-row-main">
            <div class="gacha-prize-row-title">${this.esc(unit.displayName || unit.unitCode)}</div>
            <div class="orbit-user-sub">${this.esc(unit.unitCode)} · ${this.esc(unit.shortName || '')}</div>
          </div>
          <div class="ui small form gacha-prize-row-editor">
            <div class="four fields">
              <div class="field"><label>Code</label><input data-shop-unit-field="unitCode" value="${this.escAttr(unit.unitCode || '')}" readonly></div>
              <div class="field"><label>Name</label><input data-shop-unit-field="displayName" value="${this.escAttr(unit.displayName || '')}"></div>
              <div class="field"><label>Short</label><input data-shop-unit-field="shortName" value="${this.escAttr(unit.shortName || '')}"></div>
              <div class="field"><label>Sort</label><input type="number" data-shop-unit-field="sortOrder" value="${this.escAttr(unit.sortOrder ?? index * 10)}"></div>
            </div>
            <div class="three fields">
              ${this.shopAssetPicker(unit.icon || '', 'icon', 'Icon Image', 'ใช้ไฟล์รูปได้ ถ้าไม่ใส่ระบบจะ fallback เป็นอักษรย่อ', 'data-shop-unit-field=\"icon\"', ['image', 'icon'])}
              <div class="field gacha-prize-upload-field"><label>&nbsp;</label><label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload icon<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-shop-upload-kind="unit" data-shop-upload-index="${index}" data-shop-upload-field="icon" hidden></label></div>
              <div class="field"><label>Icon Fallback</label><input data-shop-unit-field="iconFallback" value="${this.escAttr(unit.shortName || unit.displayName || unit.unitCode || '')}" readonly></div>
            </div>
            <div class="inline fields">
              <label class="orbit-native-checkbox"><input type="checkbox" data-shop-unit-field="isEnabled" ${Number(unit.isEnabled) !== 0 ? 'checked' : ''}><span>Enabled</span></label>
            </div>
            ${(() => {
              const theme = this.shopUnitBuyThemeOverride(unit);
              return `
                <div class="gacha-prize-fieldset">
                  <div class="gacha-prize-fieldset-title">Override buy theme for this unit</div>
                  <div class="ui tiny form">
                    <div class="inline fields">
                      <label class="orbit-native-checkbox"><input type="checkbox" data-shop-unit-theme-field="overrideEnabled" ${theme.overrideEnabled ? 'checked' : ''}><span>Enable override</span></label>
                      <div class="orbit-muted">ถ้าไม่เปิดจะใช้ค่า default ทั้งร้านทั้งหมด</div>
                    </div>
                    <div data-shop-unit-theme-body>
                      <div class="three fields">
                        <div class="field"><label>Hold Seconds</label><input type="number" min="0.2" max="10" step="0.1" data-shop-unit-theme-field="holdSeconds" value="${this.escAttr(theme.holdSeconds)}" placeholder="${this.escAttr(defaults.holdSeconds)}"></div>
                        <div class="field"><label>Button Start</label><input data-shop-unit-theme-field="buttonBgStart" value="${this.escAttr(theme.buttonBgStart)}" placeholder="${this.escAttr(defaults.buttonBgStart)}"></div>
                        <div class="field"><label>Button End</label><input data-shop-unit-theme-field="buttonBgEnd" value="${this.escAttr(theme.buttonBgEnd)}" placeholder="${this.escAttr(defaults.buttonBgEnd)}"></div>
                      </div>
                      <div class="three fields">
                        <div class="field"><label>Text Color</label><input data-shop-unit-theme-field="buttonTextColor" value="${this.escAttr(theme.buttonTextColor)}" placeholder="${this.escAttr(defaults.buttonTextColor)}"></div>
                        <div class="field"><label>Hold Sweep Start</label><input data-shop-unit-theme-field="holdSweepStart" value="${this.escAttr(theme.holdSweepStart)}" placeholder="${this.escAttr(defaults.holdSweepStart)}"></div>
                        <div class="field"><label>Hold Sweep End</label><input data-shop-unit-theme-field="holdSweepEnd" value="${this.escAttr(theme.holdSweepEnd)}" placeholder="${this.escAttr(defaults.holdSweepEnd)}"></div>
                      </div>
                    </div>
                  </div>
                </div>
              `;
            })()}
          </div>
        </article>
      `).join(''));
      this.initShopScope('#shopUnitList');
      this.initShopUnitThemeControls('#shopUnitList');
      $('[data-shop-upload-kind="unit"]').off('change.shopUpload').on('change.shopUpload', (event) => this.uploadShopAsset(event.currentTarget));
    },
    shopRoleOptions(selectedRoleId = '') {
      const selected = String(selectedRoleId || '');
      return ['<option value="">-</option>'].concat((this.state.shopRoles || []).map((role) => {
        const roleId = String(role.roleId || '');
        const meta = [role.roleTier ? `Tier ${role.roleTier}` : '', role.roleSeriesName || ''].filter(Boolean).join(' · ');
        return `<option value="${this.escAttr(roleId)}" ${roleId === selected ? 'selected' : ''}>${this.esc(role.roleName || roleId)}${meta ? ` · ${this.esc(meta)}` : ''}</option>`;
      })).join('');
    },
    shopRoleSeriesSettings() {
      this.state.shopConfig = this.state.shopConfig || { settings: {}, products: [], templates: [] };
      this.state.shopConfig.settings = this.state.shopConfig.settings || {};
      if (!Array.isArray(this.state.shopConfig.settings.roleSeriesSettings)) {
        this.state.shopConfig.settings.roleSeriesSettings = [];
      }
      return this.state.shopConfig.settings.roleSeriesSettings;
    },
    shopRoleSeriesSetting(seriesId) {
      const targetSeriesId = String(seriesId || '').trim();
      if (!targetSeriesId) {
        return { seriesId: '', headerImage: '', purchaseOptions: [] };
      }
      const found = this.shopRoleSeriesSettings().find((entry) => String(entry.seriesId || '') === targetSeriesId);
      return found || { seriesId: targetSeriesId, headerImage: '', purchaseOptions: [] };
    },
    shopClonePurchaseOptions(options = []) {
      return JSON.parse(JSON.stringify(Array.isArray(options) ? options : []));
    },
    shopRoleSeriesProductMatch(product, seriesId) {
      const targetSeriesId = String(seriesId || '').trim();
      if (!targetSeriesId || String(product?.type || '') !== 'role') {
        return false;
      }
      if (String(product?.seriesSourceId || '') === targetSeriesId) {
        return true;
      }
      const roleId = String(product?.discordRoleId || '');
      const roleMeta = (this.state.shopRoles || []).find((role) => String(role.roleId || '') === roleId);
      return String(roleMeta?.roleSeriesId || '') === targetSeriesId;
    },
    syncGachaShopDraftsToState() {
      const payload = this.collectGachaShopPayload();
      this.state.shopUnits = payload.units || [];
      this.state.shopConfig = {
        ...(this.state.shopConfig || {}),
        ...(payload.config || {}),
        products: payload.config?.products || [],
        templates: payload.config?.templates || [],
      };
    },
    shopSyncDropdownHiddenValues(scope) {
      const root = $(scope);
      root.find('.gacha-asset-dropdown').each((_, element) => {
        const dropdown = $(element);
        const hidden = dropdown.find('input[type="hidden"]').first();
        if (!hidden.length) return;
        try {
          const value = dropdown.dropdown('get value');
          hidden.val(String(value ?? hidden.val() ?? ''));
        } catch (error) {
          hidden.val(String(hidden.val() ?? ''));
        }
      });
    },
    shopFieldValue($root, selector, fallback = '') {
      const field = $root.find(selector).first();
      if (!field.length) return fallback;
      return String(field.val() ?? fallback ?? '');
    },
    shopNumberValue($root, selector, fallback = 0) {
      const value = Number(this.shopFieldValue($root, selector, fallback));
      return Number.isFinite(value) ? value : Number(fallback || 0);
    },
    shopCheckedValue($root, selector, fallback = true) {
      const field = $root.find(selector).first();
      if (!field.length) return Boolean(fallback);
      return field.prop('checked') === true;
    },
	    shopProductEditorState() {
	      this.state.shopProductEditor = {
	        rolePage: 1,
	        pageSize: 25,
	        roleQ: '',
	        ...(this.state.shopProductEditor || {}),
	      };
	      return this.state.shopProductEditor;
	    },
    shopProductRows(kind) {
      const products = this.state.shopConfig?.products || [];
      const editor = this.shopProductEditorState();
      const q = String(editor[`${kind}Q`] || '').trim().toLowerCase();
      return products
        .map((product, index) => [product, index])
        .filter(([product]) => kind === 'role' ? product.type === 'role' : product.type !== 'role')
        .filter(([product]) => {
          if (!q) return true;
          return [
            product.id,
            product.name,
            product.shortName,
            product.itemCode,
            product.discordRoleId,
            product.groupName,
            product.badge,
          ].some((value) => String(value || '').toLowerCase().includes(q));
        });
    },
	    renderShopProductToolbar(kind, total, filtered, page, totalPages) {
	      const editor = this.shopProductEditorState();
	      const selector = '#shopRoleProductToolbar';
	      const label = kind === 'role' ? 'Role products' : 'Products';
      $(selector).html(`
        <div class="ui small form shop-product-toolbar-inner">
          <div class="fields">
            <div class="six wide field">
              <div class="ui input">
                <input type="search" data-shop-product-q="${kind}" value="${this.escAttr(editor[`${kind}Q`] || '')}" placeholder="ค้นชื่อ / id / role id">
              </div>
            </div>
            <div class="three wide field">
              <select class="ui dropdown" data-shop-product-page-size>
                ${[10, 25, 50, 100].map((size) => `<option value="${size}" ${Number(editor.pageSize) === size ? 'selected' : ''}>${size} rows</option>`).join('')}
              </select>
            </div>
            <div class="seven wide field">
              <div class="ui buttons">
                <button class="ui button" type="button" data-shop-product-prev="${kind}" ${page <= 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>
                <button class="ui button disabled" type="button">${this.esc(label)} · ${filtered.toLocaleString()} / ${total.toLocaleString()} · ${page}/${totalPages}</button>
                <button class="ui button" type="button" data-shop-product-next="${kind}" ${page >= totalPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>
              </div>
            </div>
          </div>
        </div>
      `);
      this.initShopScope(selector);
    },
    shopRoleSeriesOptionHtml(option, seriesId, optionIndex, units) {
      const prices = option.prices || {};
      const gift = this.shopOptionGift(option, units);
      return `
        <div class="gacha-prize-fieldset" data-shop-role-series-option-index="${optionIndex}">
          <div class="gacha-prize-fieldset-title">Series Default Option ${optionIndex + 1}</div>
          <div class="ui tiny form">
            <div class="four fields">
              <div class="field"><label>ID</label><input data-shop-role-series-option-field="id" value="${this.escAttr(option.id || '')}"></div>
              <div class="field"><label>Label</label><input data-shop-role-series-option-field="label" value="${this.escAttr(option.label || '')}"></div>
              <div class="field"><label>Days</label><input type="number" min="0" data-shop-role-series-option-field="days" value="${this.escAttr(option.days || 0)}"></div>
              <div class="field"><label>Sort</label><input type="number" data-shop-role-series-option-field="sortOrder" value="${this.escAttr(option.sortOrder ?? optionIndex * 10)}"></div>
            </div>
            <details class="shop-config-fold" open>
              <summary><i class="fa-solid fa-wallet"></i> ราคาค่าเริ่มต้นของซีรีส์</summary>
              <div class="${Math.max(1, Math.min(4, units.length || 1))} fields">
                ${units.map((unit) => {
                  const code = String(unit.unitCode || '');
                  return `<div class="field"><label>${this.esc(unit.shortName || unit.displayName || code)}</label><input type="number" min="0" data-shop-role-series-option-price="${this.escAttr(code)}" value="${this.escAttr(prices[code] || 0)}"></div>`;
                }).join('')}
              </div>
            </details>
            <details class="shop-config-fold" ${gift.enabled ? 'open' : ''}>
              <summary><i class="fa-solid fa-gift"></i> ของขวัญค่าเริ่มต้น</summary>
              <div class="inline fields">
                <label class="orbit-native-checkbox"><input type="checkbox" data-shop-role-series-option-gift-field="enabled" ${gift.enabled ? 'checked' : ''}><span>เปิดส่งให้เพื่อนได้</span></label>
                <label class="orbit-native-checkbox"><input type="checkbox" data-shop-role-series-option-gift-field="useCustomPrices" ${gift.useCustomPrices ? 'checked' : ''}><span>แยกราคาของขวัญ</span></label>
              </div>
              <div data-shop-gift-body>
                <div class="orbit-muted" style="margin-bottom:8px">ค่าในส่วนนี้จะถูก copy ลงทุกยศในซีรีส์ตอนกด apply</div>
                <div class="shop-gift-unit-grid">
                  ${units.map((unit) => {
                    const code = String(unit.unitCode || '');
                    const label = unit.shortName || unit.displayName || code;
                    return `
                      <div class="shop-gift-unit-row">
                        <label class="orbit-native-checkbox"><input type="checkbox" data-shop-role-series-option-gift-unit="${this.escAttr(code)}" ${gift.enabledUnits[code] ? 'checked' : ''}><span>${this.esc(label)}</span></label>
                        <div data-shop-gift-custom>
                          <input type="number" min="0" data-shop-role-series-option-gift-price="${this.escAttr(code)}" value="${this.escAttr(gift.prices[code] || 0)}" placeholder="${this.escAttr(prices[code] || 0)}">
                        </div>
                      </div>`;
                  }).join('')}
                </div>
              </div>
            </details>
            <div class="inline fields">
              <label class="orbit-native-checkbox"><input type="checkbox" data-shop-role-series-option-field="active" ${option.active !== false ? 'checked' : ''}><span>Active</span></label>
              <label class="orbit-native-checkbox"><input type="checkbox" data-shop-role-series-option-field="visible" ${option.visible !== false ? 'checked' : ''}><span>Visible</span></label>
              <button class="ui mini red button" type="button" data-shop-role-series-id="${this.escAttr(seriesId)}" data-shop-role-series-option-remove="${optionIndex}"><i class="fa-solid fa-trash"></i></button>
            </div>
          </div>
        </div>
      `;
    },
    applyShopRoleSeriesDefaults(seriesId) {
      const targetSeriesId = String(seriesId || '').trim();
      if (!targetSeriesId) {
        return;
      }
      this.syncGachaShopDraftsToState();
      const setting = this.shopRoleSeriesSetting(targetSeriesId);
      const purchaseOptions = this.shopClonePurchaseOptions(setting.purchaseOptions || []);
      if (!purchaseOptions.length) {
        this.toast('ซีรีส์นี้ยังไม่มี default option ให้ apply');
        return;
      }

      let updated = 0;
      this.state.shopConfig.products = (this.state.shopConfig?.products || []).map((product) => {
        if (!this.shopRoleSeriesProductMatch(product, targetSeriesId)) {
          return product;
        }
        updated += 1;
        return {
          ...product,
          seriesSourceId: targetSeriesId,
          purchaseOptions: this.shopClonePurchaseOptions(purchaseOptions),
        };
      });

      if (!updated) {
        this.toast('ยังไม่มี role product ในซีรีส์นี้ให้ apply');
        return;
      }

      this.renderShopProducts();
      this.toast(`อัปเดตราคา default ลง role products ในซีรีส์แล้ว ${updated} รายการ`);
    },
    renderShopRoleSeriesConfigs() {
      const units = this.state.shopUnits || [];
      const roleMetaById = new Map((this.state.shopRoles || []).map((role) => [String(role.roleId || ''), role]));
      const roleCounts = {};
      (this.state.shopRoles || []).forEach((role) => {
        const seriesId = String(role.roleSeriesId || '');
        if (!seriesId) return;
        roleCounts[seriesId] = (roleCounts[seriesId] || 0) + 1;
      });
      const productCounts = {};
      (this.state.shopConfig?.products || []).forEach((product) => {
        if (String(product?.type || '') !== 'role') return;
        const roleMeta = roleMetaById.get(String(product.discordRoleId || ''));
        const seriesId = String(product.seriesSourceId || roleMeta?.roleSeriesId || '');
        if (!seriesId) return;
        productCounts[seriesId] = (productCounts[seriesId] || 0) + 1;
      });
      const settingsBySeriesId = new Map(this.shopRoleSeriesSettings().map((entry) => [String(entry.seriesId || ''), entry]));
      const rows = Array.isArray(this.state.shopRoleSeries) ? this.state.shopRoleSeries : [];
      $('#shopRoleSeriesConfigList').html(rows.length ? rows.map((series) => {
        const seriesId = String(series.id || '');
        const setting = settingsBySeriesId.get(seriesId) || { seriesId, headerImage: '', purchaseOptions: [] };
        const bannerMeta = this.shopAssetMeta(setting.headerImage || '');
        const bannerUrl = bannerMeta?.url || (setting.headerImage ? this.gachaAssetUrl(setting.headerImage) : '');
        const options = Array.isArray(setting.purchaseOptions) ? setting.purchaseOptions : [];
        return `
          <article class="orbit-earn-rule" data-shop-role-series-id="${this.escAttr(seriesId)}">
            <div class="orbit-earn-rule-head">
              <div>
                <strong>${this.esc(series.name || seriesId)}</strong>
                <span>${this.esc(seriesId)} · roles ${Number(roleCounts[seriesId] || 0).toLocaleString()} · products ${Number(productCounts[seriesId] || 0).toLocaleString()}</span>
              </div>
              <button class="ui tiny primary button" type="button" data-shop-role-series-apply="${this.escAttr(seriesId)}"${productCounts[seriesId] ? '' : ' disabled'}><i class="fa-solid fa-wand-magic-sparkles"></i> Apply To Products</button>
            </div>
            <div class="ui small form orbit-earn-rule-grid">
              <div class="field orbit-earn-rule-grid-wide">
                <label>${this.fieldLabel('Series Banner', 'แบนเนอร์แนวนอนบนหัวกล่องซีรีส์ ถ้าไม่ใส่หน้าร้านจะใช้ชื่อ text แทน')}</label>
                <div style="display:grid; gap:10px;">
                  ${bannerUrl ? `<div style="border:1px solid rgba(148,116,176,.14); border-radius:14px; overflow:hidden; background:#fff;">${this.shopAssetMediaHtml(bannerUrl, bannerMeta?.kind || this.shopAssetKind(setting.headerImage || '', 'image'), series.name || seriesId, 'display:block; width:100%; max-height:120px; object-fit:cover; object-position:center; pointer-events:none;')}</div>` : '<div class="orbit-muted" style="padding:10px 12px; border:1px dashed rgba(148,116,176,.22); border-radius:14px;">ยังไม่ได้ตั้ง banner ของซีรีส์นี้</div>'}
                  <div class="three fields" style="margin:0">
                    ${this.shopAssetPicker(setting.headerImage || '', 'headerImage', 'Media Asset', 'เลือกรูปหรือวิดีโอสำหรับแบนเนอร์ซีรีส์ หรือปล่อยว่างได้', `data-shop-role-series-field="headerImage"`, ['image', 'video'])}
                    <div class="field"><label>&nbsp;</label><label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload banner<input type="file" accept="image/png,image/jpeg,image/webp,image/gif,video/mp4,video/webm,video/quicktime,.mov,.m4v" data-shop-upload-kind="role-series" data-shop-upload-series-id="${this.escAttr(seriesId)}" data-shop-upload-field="headerImage" hidden></label></div>
                    <div class="field"><label>&nbsp;</label><button class="ui tiny button" type="button" data-shop-role-series-clear-banner="${this.escAttr(seriesId)}"><i class="fa-solid fa-eraser"></i> Clear banner</button></div>
                  </div>
                </div>
              </div>
              <div class="field orbit-earn-rule-grid-wide">
                <label>${this.fieldLabel('Series Default Prices', 'ตั้ง option หลายหน่วยครั้งเดียว แล้วกด Apply ลง role products ทุกชิ้นในซีรีส์')}</label>
                <div class="shop-setting-list">
                  ${options.length ? options.map((option, optionIndex) => this.shopRoleSeriesOptionHtml(option, seriesId, optionIndex, units)).join('') : '<div class="orbit-muted" style="padding:10px 12px; border:1px dashed rgba(148,116,176,.22); border-radius:14px;">ยังไม่มี default option ของซีรีส์นี้ กด Add Default Option เพื่อสร้างชุดราคาตั้งต้น</div>'}
                </div>
                <div class="ui tiny buttons" style="margin-top:10px">
                  <button class="ui button" type="button" data-shop-role-series-option-add="${this.escAttr(seriesId)}"><i class="fa-solid fa-plus"></i> Add Default Option</button>
                  <button class="ui primary button" type="button" data-shop-role-series-apply="${this.escAttr(seriesId)}"${productCounts[seriesId] ? '' : ' disabled'}><i class="fa-solid fa-bolt"></i> Apply Now</button>
                </div>
                ${series.description ? `<div class="orbit-muted" style="margin-top:8px">${this.esc(series.description)}</div>` : ''}
              </div>
            </div>
          </article>
        `;
      }).join('') : this.empty('ยังไม่มี Role Series'));

      this.initShopScope('#shopRoleSeriesConfigList');
      this.initShopGiftControls('#shopRoleSeriesConfigList');
      $('[data-shop-upload-kind="role-series"]').off('change.shopUpload').on('change.shopUpload', (event) => this.uploadShopAsset(event.currentTarget));
      $('[data-shop-role-series-option-add]').off('click.shopRoleSeriesOption').on('click.shopRoleSeriesOption', (event) => {
        this.syncGachaShopDraftsToState();
        const seriesId = String($(event.currentTarget).data('shop-role-series-option-add') || '').trim();
        if (!seriesId) return;
        const settings = this.shopRoleSeriesSettings();
        let setting = settings.find((entry) => String(entry.seriesId || '') === seriesId);
        if (!setting) {
          setting = { seriesId, headerImage: '', purchaseOptions: [] };
          settings.push(setting);
        }
        setting.purchaseOptions = Array.isArray(setting.purchaseOptions) ? setting.purchaseOptions : [];
        const prices = {};
        (this.state.shopUnits || []).forEach((unit) => {
          const code = String(unit.unitCode || '');
          if (code) prices[code] = 0;
        });
        if (!Object.keys(prices).length) {
          prices.coin = 0;
        }
        setting.purchaseOptions.push({
          id: `option-${Date.now().toString(36)}`,
          label: 'ถาวร',
          days: 0,
          prices,
          gift: {
            enabled: false,
            useCustomPrices: false,
            enabledUnits: {},
            prices: {},
          },
          active: true,
          visible: true,
          sortOrder: (setting.purchaseOptions.length + 1) * 10,
        });
        this.renderShopRoleSeriesConfigs();
      });
      $('[data-shop-role-series-option-remove]').off('click.shopRoleSeriesOption').on('click.shopRoleSeriesOption', (event) => {
        this.syncGachaShopDraftsToState();
        const seriesId = String($(event.currentTarget).data('shop-role-series-id') || '').trim();
        const optionIndex = Number($(event.currentTarget).data('shop-role-series-option-remove') || -1);
        const settings = this.shopRoleSeriesSettings();
        const setting = settings.find((entry) => String(entry.seriesId || '') === seriesId);
        if (!setting || optionIndex < 0) return;
        setting.purchaseOptions = (setting.purchaseOptions || []).filter((_, index) => index !== optionIndex);
        this.renderShopRoleSeriesConfigs();
      });
      $('[data-shop-role-series-apply]').off('click.shopRoleSeriesApply').on('click.shopRoleSeriesApply', (event) => {
        const seriesId = String($(event.currentTarget).data('shop-role-series-apply') || '').trim();
        this.applyShopRoleSeriesDefaults(seriesId);
      });
      $('[data-shop-role-series-clear-banner]').off('click.shopRoleSeriesBanner').on('click.shopRoleSeriesBanner', (event) => {
        this.syncGachaShopDraftsToState();
        const seriesId = String($(event.currentTarget).data('shop-role-series-clear-banner') || '').trim();
        const settings = this.shopRoleSeriesSettings();
        let setting = settings.find((entry) => String(entry.seriesId || '') === seriesId);
        if (!setting) {
          setting = { seriesId, headerImage: '', purchaseOptions: [] };
          settings.push(setting);
        }
        setting.headerImage = '';
        this.renderShopRoleSeriesConfigs();
      });
    },
	    renderShopProducts() {
	      const editor = this.shopProductEditorState();
	      editor.pageSize = Math.max(10, Math.min(100, Number(editor.pageSize || 25)));
	      this.renderShopRoleSeriesConfigs();
	      this.renderShopRoleProducts();
	      this.renderShopItemGroups();
	      $('[data-shop-product-q]').off('keydown.shopProductFilter').on('keydown.shopProductFilter', (event) => {
	        if (event.key !== 'Enter') return;
	        event.preventDefault();
	        this.syncGachaShopDraftsToState();
	        const kind = String($(event.currentTarget).data('shop-product-q') || 'role');
        const editor = this.shopProductEditorState();
        editor[`${kind}Q`] = String($(event.currentTarget).val() || '');
        editor[`${kind}Page`] = 1;
        this.renderShopProducts();
      });
	      $('[data-shop-product-page-size]').off('change.shopProductPageSize').on('change.shopProductPageSize', (event) => {
	        this.syncGachaShopDraftsToState();
	        const editor = this.shopProductEditorState();
	        editor.pageSize = Number($(event.currentTarget).val() || 25);
	        editor.rolePage = 1;
	        this.renderShopProducts();
	      });
	      $('[data-shop-product-prev]').off('click.shopProductPager').on('click.shopProductPager', (event) => {
	        event.preventDefault();
	        this.syncGachaShopDraftsToState();
        const kind = String($(event.currentTarget).data('shop-product-prev') || 'role');
        const editor = this.shopProductEditorState();
        editor[`${kind}Page`] = Math.max(1, Number(editor[`${kind}Page`] || 1) - 1);
        this.renderShopProducts();
      });
      $('[data-shop-product-next]').off('click.shopProductPager').on('click.shopProductPager', (event) => {
        event.preventDefault();
        this.syncGachaShopDraftsToState();
        const kind = String($(event.currentTarget).data('shop-product-next') || 'role');
        const editor = this.shopProductEditorState();
        editor[`${kind}Page`] = Number(editor[`${kind}Page`] || 1) + 1;
        this.renderShopProducts();
      });
	      $('[data-shop-product-remove]').off('click').on('click', (event) => {
	        this.syncGachaShopDraftsToState();
	        const index = Number($(event.currentTarget).data('shop-product-remove'));
	        this.state.shopConfig.products.splice(index, 1);
	        this.renderShopProducts();
      });
      $('[data-shop-option-add]').off('click').on('click', (event) => {
        this.syncGachaShopDraftsToState();
        const index = Number($(event.currentTarget).data('shop-option-add'));
        const product = this.state.shopConfig.products[index];
        product.purchaseOptions = product.purchaseOptions || [];
        product.purchaseOptions.push({
          id: `option-${Date.now().toString(36)}`,
          label: product.type === 'role' ? '7 วัน' : '1 ชิ้น',
          days: product.type === 'role' ? 7 : 0,
          prices: { coin: 0 },
          gift: {
            enabled: false,
            useCustomPrices: false,
            enabledUnits: {},
            prices: {},
          },
          active: true,
          visible: true,
          sortOrder: (product.purchaseOptions.length + 1) * 10,
        });
        this.renderShopProducts();
      });
	      $('[data-shop-option-remove]').off('click').on('click', (event) => {
	        this.syncGachaShopDraftsToState();
	        const productIndex = Number($(event.currentTarget).data('shop-product-index'));
	        const optionIndex = Number($(event.currentTarget).data('shop-option-remove'));
	        const product = this.state.shopConfig.products[productIndex];
	        product.purchaseOptions = (product.purchaseOptions || []).filter((_, index) => index !== optionIndex);
	        this.renderShopProducts();
	      });
	      $('[data-shop-upload-kind="product"]').off('change.shopUpload').on('change.shopUpload', (event) => this.uploadShopAsset(event.currentTarget));
	    },
	    renderShopRoleProducts() {
	      const editor = this.shopProductEditorState();
	      const products = this.state.shopConfig?.products || [];
	      const pageSize = Math.max(10, Math.min(100, Number(editor.pageSize || 25)));
	      editor.pageSize = pageSize;
	      const allCount = products.filter((product) => product.type === 'role').length;
	      const rows = this.shopProductRows('role');
	      const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
	      editor.rolePage = Math.max(1, Math.min(Number(editor.rolePage || 1), totalPages));
	      const page = editor.rolePage;
	      const visibleRows = rows.slice((page - 1) * pageSize, page * pageSize);
	      this.renderShopProductToolbar('role', allCount, rows.length, page, totalPages);
	      $('#shopRoleProductList').html(visibleRows.length
	        ? visibleRows.map(([product, index]) => this.shopProductHtml(product, index)).join('')
	        : '<div class="empty-state">ยังไม่มีสินค้ายศ</div>');
	      this.initShopScope('#shopRoleProductList');
	      this.initShopGiftControls('#shopRoleProductList');
	    },
	    shopProductTitle(product) {
	      return String(product?.shortName || product?.name || product?.id || 'สินค้าใหม่').trim() || 'สินค้าใหม่';
	    },
	    shopProductPublicState(product) {
	      const options = Array.isArray(product?.purchaseOptions) ? product.purchaseOptions : [];
	      const visibleOptions = options.filter((option) => option.active !== false && option.visible !== false);
	      const publicStateHints = [];
	      if (product?.active === false) publicStateHints.push('Active ปิด');
	      if (product?.visible === false) publicStateHints.push('Visible ปิด');
	      if (!visibleOptions.length) publicStateHints.push('ไม่มี Option ที่เปิดโชว์');
	      return {
	        visibleOptions,
	        publicStateHints,
	        publicStateLabel: publicStateHints.length ? `ไม่ขึ้นหน้าร้าน · ${publicStateHints.join(', ')}` : 'ขึ้นหน้าร้าน',
	      };
	    },
	    shopProductOptionSummary(product) {
	      const visibleOptions = this.shopProductPublicState(product).visibleOptions;
	      if (!visibleOptions.length) return 'ไม่มี Option ที่เปิดโชว์';
	      const labels = visibleOptions.slice(0, 3).map((option) => {
	        const label = String(option?.label || '').trim();
	        if (label) return label;
	        const days = Number(option?.days || 0);
	        return product?.type === 'role' ? (days > 0 ? `${days} วัน` : 'ถาวร') : '1 ชิ้น';
	      }).filter(Boolean);
	      const suffix = visibleOptions.length > labels.length ? ` +${visibleOptions.length - labels.length}` : '';
	      return `${visibleOptions.length} options${labels.length ? ` · ${labels.join(', ')}${suffix}` : ''}`;
	    },
	    shopProductImageUrl(product) {
	      const imagePath = String(product?.image || '').trim();
	      if (imagePath) {
	        const meta = this.shopAssetMeta(imagePath);
	        if (meta?.url) return String(meta.url);
	        return this.gachaAssetUrl(imagePath);
	      }
	      if (product?.type === 'role') {
	        const role = (this.state.shopRoles || []).find((entry) => String(entry.roleId || '') === String(product?.discordRoleId || ''));
	        if (role?.roleIconUrl) return String(role.roleIconUrl);
	        return this.gachaAssetUrl('images/icon_roles_blank.png');
	      }
	      return this.gachaAssetUrl('images/item-1.png');
	    },
	    shopProductThumbHtml(product, mode = 'thumb') {
	      const imageClass = mode === 'art' ? 'gacha-prize-art-media' : 'gacha-prize-thumb-media';
	      const imageUrl = this.shopProductImageUrl(product);
	      if (imageUrl) {
	        return `<img class="${imageClass}" src="${this.escAttr(imageUrl)}" alt="">`;
	      }
	      return '<span class="gacha-prize-role-fallback" aria-hidden="true"><i class="fa-solid fa-box-open"></i></span>';
	    },
	    shopSlug(value, fallback = '') {
	      const text = String(value || '').trim().toLowerCase();
	      const slug = text.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
	      return slug || String(fallback || '').trim();
	    },
	    shopItemFallbackGroupMeta(product = {}, fallbackSort = 0) {
	      const effectType = String(product?.effectType || '').trim();
	      const badge = String(product?.badge || '').trim();
	      const productSort = Math.max(0, Number(product?.sortOrder || fallbackSort || 999) || 999);
	      if (effectType.includes('private_room')) {
	        return {
	          id: 'private-room',
	          name: 'ห้องส่วนตัว',
	          badge: '',
	          description: '',
	          sortOrder: productSort,
	        };
	      }
	      if (effectType.includes('moderation')) {
	        return {
	          id: 'moderation-ticket',
	          name: 'บัตรสถานะ',
	          badge: '',
	          description: '',
	          sortOrder: productSort,
	        };
	      }
	      if (String(product?.type || '') === 'system_ticket') {
	        return {
	          id: 'system-ticket',
	          name: 'บัตรระบบ',
	          badge: '',
	          description: '',
	          sortOrder: productSort,
	        };
	      }
	      if (badge) {
	        return {
	          id: this.shopSlug(badge, 'general-item'),
	          name: badge,
	          badge: '',
	          description: '',
	          sortOrder: productSort,
	        };
	      }
	      return {
	        id: 'general-item',
	        name: 'ไอเทมทั่วไป',
	        badge: '',
	        description: '',
	        sortOrder: productSort,
	      };
	    },
	    shopResolveItemGroupMeta(source = {}, sampleProduct = {}, fallbackSort = 0) {
	      const fallback = this.shopItemFallbackGroupMeta(sampleProduct, fallbackSort);
	      const explicitName = String(source.groupName ?? '').trim();
	      const explicitBadge = String(source.groupBadge ?? '').trim();
	      const explicitDescription = String(source.groupDescription ?? '').trim();
	      const explicitId = String(source.groupId ?? '').trim();
	      const fallbackId = this.shopSlug(
	        String(sampleProduct.groupId || sampleProduct.itemCode || sampleProduct.id || fallback.id || `item-group-${Date.now().toString(36)}`),
	        `item-group-${Date.now().toString(36)}`
	      );
	      const name = explicitName || fallback.name;
	      return {
	        id: this.shopSlug(explicitId || name, fallback.id || fallbackId || 'general-item'),
	        name,
	        badge: explicitBadge || (!explicitName ? fallback.badge : ''),
	        description: explicitDescription || (!explicitName ? fallback.description : ''),
	        sortOrder: Math.max(0, Number(source.groupSortOrder || fallback.sortOrder || fallbackSort || 0) || 0),
	      };
	    },
	    shopEffectiveItemGroup(product = {}) {
	      return this.shopResolveItemGroupMeta(product, product, Number(product?.groupSortOrder || product?.sortOrder || 0));
	    },
	    shopItemGroupExpandedId() {
	      this.state.shopItemGroupExpanded = String(this.state.shopItemGroupExpanded || '');
	      return this.state.shopItemGroupExpanded;
	    },
	    shopItemEntryExpandedState() {
	      this.state.shopItemEntryExpanded = this.state.shopItemEntryExpanded || {};
	      return this.state.shopItemEntryExpanded;
	    },
	    shopItemGroupCollections(products = null) {
	      const rows = (Array.isArray(products) ? products : (this.state.shopConfig?.products || []))
	        .map((product, index) => ({ product, index }))
	        .filter(({ product }) => product.type !== 'role');
	      const grouped = new Map();
	      rows.forEach(({ product, index }) => {
	        const meta = this.shopEffectiveItemGroup(product);
	        const key = String(meta.id || this.shopSlug(product.id || `item-group-${index}`, `item-group-${index}`));
	        if (!grouped.has(key)) {
	          grouped.set(key, {
	            id: key,
	            name: meta.name,
	            badge: meta.badge,
	            description: meta.description,
	            sortOrder: Number(meta.sortOrder || product.sortOrder || 999) || 999,
	            items: [],
	          });
	        }
	        const group = grouped.get(key);
	        group.name = group.name || meta.name;
	        group.badge = group.badge || meta.badge;
	        group.description = group.description || meta.description;
	        group.sortOrder = Math.min(Number(group.sortOrder || 999), Number(meta.sortOrder || product.sortOrder || 999) || 999);
	        group.items.push({ product, index });
	      });
	      return [...grouped.values()]
	        .map((group) => ({
	          ...group,
	          items: [...group.items].sort((left, right) => Number(left.product.sortOrder || 0) - Number(right.product.sortOrder || 0)
	            || String(left.product.name || '').localeCompare(String(right.product.name || ''))),
	        }))
	        .sort((left, right) => Number(left.sortOrder || 0) - Number(right.sortOrder || 0)
	          || String(left.name || '').localeCompare(String(right.name || '')));
	    },
	    shopItemGroupOptions(selectedGroupId = '') {
	      return this.shopItemGroupCollections().map((group) => `
	        <option value="${this.escAttr(group.id)}" ${String(selectedGroupId || '') === String(group.id || '') ? 'selected' : ''}>
	          ${this.esc(group.name || group.id)} · ${group.items.length.toLocaleString()} items
	        </option>
	      `).join('');
	    },
	    shopItemGroupSummary(group) {
	      const total = group.items.length;
	      const active = group.items.filter(({ product }) => product.active !== false).length;
	      const visible = group.items.filter(({ product }) => product.visible !== false).length;
	      const description = String(group.description || '').trim();
	      return {
	        total,
	        active,
	        visible,
	        metaLine: [
	          group.badge ? `Badge: ${group.badge}` : '',
	          `${total.toLocaleString()} items`,
	          description || '',
	        ].filter(Boolean).join(' · '),
	      };
	    },
	    renderShopItemGroups() {
	      const groups = this.shopItemGroupCollections();
	      const expanded = this.shopItemGroupExpandedId();
	      this.shopItemEntryExpandedState();
	      $('#shopItemGroupList').html(groups.length ? groups.map((group) => {
	        const summary = this.shopItemGroupSummary(group);
	        return `
	          <article class="gacha-prize-row gacha-role-config-row shop-item-group-row ${expanded === group.id ? 'is-open' : ''}" draggable="true" data-shop-item-group-id="${this.escAttr(group.id)}" style="--gacha-tier-accent:var(--orbit-primary);">
	            <button class="gacha-prize-summary" type="button" data-shop-toggle-item-group="${this.escAttr(group.id)}">
	              <span class="gacha-prize-drag" title="Drag to reorder group"><i class="fa-solid fa-grip-vertical"></i></span>
	              <span class="gacha-prize-thumb">${this.shopProductThumbHtml(group.items[0]?.product || { type: 'item' }, 'thumb')}</span>
	              <span class="gacha-prize-summary-caret"><i class="fa-solid fa-chevron-${expanded === group.id ? 'down' : 'right'}"></i></span>
	              <span class="gacha-prize-copy">
	                <strong>${this.esc(group.name || group.id || 'Item Group')}</strong>
	                <span>${this.esc(summary.metaLine || 'กลุ่มสินค้า')}</span>
	              </span>
	              <span class="gacha-prize-chip-row">
	                <span class="gacha-prize-chip is-dim">${summary.total.toLocaleString()} Items</span>
	                <span class="gacha-prize-chip ${summary.active === summary.total ? 'is-live' : ''}">Active ${summary.active}/${summary.total}</span>
	                <span class="gacha-prize-chip ${summary.visible === summary.total ? 'is-live' : ''}">Visible ${summary.visible}/${summary.total}</span>
	              </span>
	            </button>
	            <div class="gacha-role-config-preview-list" ${expanded === group.id ? 'hidden' : ''}>
	              ${group.items.slice(0, 3).map(({ product, index }) => this.renderShopItemGroupPreviewRow(group.id, product, index)).join('')}
	              ${group.items.length > 3 ? `<div class="shop-item-group-preview-more">+${(group.items.length - 3).toLocaleString()} items</div>` : ''}
	            </div>
	            <div class="gacha-prize-editor ui mini form">
	              <div class="gacha-prize-editor-grid">
	                <div class="gacha-prize-editor-head">
	                  <div>
	                    <strong>Shop Item Group</strong>
	                    <div class="orbit-muted">แก้ข้อมูลกลุ่มก่อน แล้วค่อยเปิดสินค้าย่อยในกลุ่มเดียวกันเพื่อจัดลำดับและตั้งค่าแบบเดียวกับ Prize Setting</div>
	                  </div>
	                  <div class="gacha-prize-editor-actions">
	                    <button class="ui tiny primary button" type="button" data-shop-save-item-group="${this.escAttr(group.id)}"><i class="fa-solid fa-floppy-disk"></i> Save Group</button>
	                    <button class="ui tiny button" type="button" data-shop-add-item-to-group="${this.escAttr(group.id)}"><i class="fa-solid fa-plus"></i> Item</button>
	                    <button class="ui tiny negative button" type="button" data-shop-remove-item-group="${this.escAttr(group.id)}"><i class="fa-solid fa-trash"></i> Remove Group</button>
	                  </div>
	                </div>
	                <div class="gacha-prize-editor-art">
	                  <div class="gacha-prize-editor-artbox">
	                    ${this.shopProductThumbHtml(group.items[0]?.product || { type: 'item' }, 'art')}
	                    <div class="orbit-muted">${group.items.length.toLocaleString()} items share this shelf group</div>
	                  </div>
	                  <div class="gacha-prize-fields">
	                    <div class="gacha-prize-fieldset">
	                      <div class="gacha-prize-fieldset-title">ข้อมูลกลุ่มบน shelf</div>
	                      <div class="four fields">
	                        <div class="field"><label>${this.fieldLabel('Group Name', 'ชื่อหัวหมวดที่แสดงก่อนรายชื่อสินค้า')}</label><input data-shop-item-group-field="groupName" value="${this.escAttr(group.name || '')}"></div>
	                        <div class="field"><label>${this.fieldLabel('Group Badge', 'ป้ายสั้นของทั้งกลุ่ม เช่น Daily หรือ Limited')}</label><input data-shop-item-group-field="groupBadge" value="${this.escAttr(group.badge || '')}"></div>
	                        <div class="field"><label>${this.fieldLabel('Group Sort', 'ระบบคำนวณจากการลากเรียงกลุ่มและอัปเดตเลขนี้ให้อัตโนมัติ')}</label><input type="number" data-shop-item-group-field="groupSortOrder" value="${this.escAttr(group.sortOrder || 0)}" readonly></div>
	                        <div class="field"><label>${this.fieldLabel('Group ID', 'คีย์กลุ่มที่ถูกเขียนลง config.products ของสินค้าในกลุ่มนี้')}</label><input data-shop-item-group-field="groupId" value="${this.escAttr(group.id || '')}" readonly></div>
	                      </div>
	                      <div class="field"><label>${this.fieldLabel('Group Description', 'คำอธิบายสั้นของหมวดสินค้า')}</label><textarea rows="2" data-shop-item-group-field="groupDescription">${this.esc(group.description || '')}</textarea></div>
	                    </div>
	                    <div class="gacha-prize-fieldset">
	                      <div class="gacha-prize-fieldset-title">สินค้าในกลุ่มนี้</div>
	                      <div class="shop-item-group-entry-list">
	                        ${group.items.map(({ product, index }) => this.renderShopItemEntry(group, product, index)).join('')}
	                      </div>
	                    </div>
	                  </div>
	                </div>
	              </div>
	            </div>
	          </article>
	        `;
	      }).join('') : '<div class="empty-state">ยังไม่มีกลุ่มสินค้า กด Add Item Group เพื่อเริ่มจัด shelf</div>');
	      this.initShopScope('#shopItemGroupList');
	      this.initShopGiftControls('#shopItemGroupList');
	      this.bindShopItemGroupEditor();
	    },
	    renderShopItemGroupPreviewRow(groupId, product, index) {
	      const state = this.shopProductPublicState(product);
	      return `
	        <div class="gacha-role-config-preview-row shop-item-group-preview-row" data-shop-item-group-id="${this.escAttr(groupId)}" data-shop-product-id="${this.escAttr(product.id || '')}" data-shop-product-index="${index}">
	          <span class="gacha-role-config-preview-thumb">${this.shopProductThumbHtml(product, 'thumb')}</span>
	          <span class="gacha-role-config-preview-copy">
	            <strong>${this.esc(this.shopProductTitle(product))}</strong>
	            <span>${this.esc([
	              product.itemCode ? `Code: ${product.itemCode}` : '',
	              product.effectType ? `Effect: ${product.effectType}` : '',
	              this.shopProductOptionSummary(product),
	            ].filter(Boolean).join(' · '))}</span>
	          </span>
	          <span class="gacha-role-config-preview-meta">
	            <span class="gacha-prize-chip is-dim">${this.esc(String(product.type || 'item'))}</span>
	            <span class="gacha-prize-chip ${product.active !== false ? 'is-live' : ''}">${product.active !== false ? 'Active' : 'Paused'}</span>
	            <span class="gacha-prize-chip ${product.visible !== false ? 'is-live' : ''}">${product.visible !== false ? 'Visible' : 'Hidden'}</span>
	            ${state.visibleOptions.length ? `<span class="gacha-prize-chip is-dim">${state.visibleOptions.length.toLocaleString()} Options</span>` : ''}
	          </span>
	        </div>`;
	    },
	    renderShopItemEntry(group, product, index) {
	      const units = this.state.shopUnits || [];
	      const options = product.purchaseOptions || [];
	      const isOpen = Boolean(this.shopItemEntryExpandedState()[String(product.id || '')]);
	      const state = this.shopProductPublicState(product);
	      return `
	        <article class="gacha-role-config-entry shop-item-group-entry ${isOpen ? 'is-open' : ''}" draggable="true" data-shop-item-group-id="${this.escAttr(group.id)}" data-shop-product-id="${this.escAttr(product.id || '')}" data-shop-product-index="${index}" style="--gacha-tier-accent:var(--orbit-primary);">
	          <div class="gacha-role-config-entry-head">
	            <button class="gacha-role-config-entry-toggle" type="button" data-shop-toggle-item-entry="${this.escAttr(product.id || '')}">
	              <span class="gacha-prize-drag" title="Drag to reorder item"><i class="fa-solid fa-grip-vertical"></i></span>
	              <span class="gacha-prize-thumb gacha-role-config-entry-thumb">${this.shopProductThumbHtml(product, 'thumb')}</span>
	              <span class="gacha-role-config-entry-caret"><i class="fa-solid fa-chevron-${isOpen ? 'down' : 'right'}"></i></span>
	              <span class="gacha-role-config-entry-copy">
	                <strong>${this.esc(this.shopProductTitle(product))}</strong>
	                <span>${this.esc([
	                  product.itemCode ? `Code: ${product.itemCode}` : '',
	                  product.effectType ? `Effect: ${product.effectType}` : '',
	                  state.publicStateLabel,
	                ].filter(Boolean).join(' · '))}</span>
	              </span>
	            </button>
	            <button class="ui mini icon button" type="button" data-shop-item-remove="${this.escAttr(product.id || '')}" data-tooltip="ลบสินค้านี้ออกจากกลุ่ม"><i class="fa-solid fa-xmark"></i></button>
	          </div>
	          <div class="gacha-role-config-entry-preview" data-shop-item-entry-preview ${isOpen ? 'hidden' : ''}>
	            <div class="gacha-role-config-entry-preview-grid">
	              <div class="gacha-role-config-entry-preview-item"><strong>Type</strong><span>${this.esc(String(product.type || 'item'))}</span></div>
	              <div class="gacha-role-config-entry-preview-item"><strong>Item Code</strong><span>${this.esc(product.itemCode || '-')}</span></div>
	              <div class="gacha-role-config-entry-preview-item"><strong>Effect</strong><span>${this.esc(product.effectType || '-')}</span></div>
	              <div class="gacha-role-config-entry-preview-item"><strong>Options</strong><span>${this.esc(this.shopProductOptionSummary(product))}</span></div>
	            </div>
	            <div class="gacha-role-config-entry-preview-chips">
	              <span class="gacha-prize-chip is-dim">${options.length.toLocaleString()} Total Options</span>
	              <span class="gacha-prize-chip ${product.active !== false ? 'is-live' : ''}">${product.active !== false ? 'Active' : 'Paused'}</span>
	              <span class="gacha-prize-chip ${product.visible !== false ? 'is-live' : ''}">${product.visible !== false ? 'Visible' : 'Hidden'}</span>
	            </div>
	          </div>
	          <div class="gacha-role-config-entry-body" data-shop-item-entry-body ${isOpen ? '' : 'hidden'}>
	            <div class="gacha-prize-fieldset">
	              <div class="gacha-prize-fieldset-title">ข้อมูลหลักของสินค้า</div>
	              <div class="four fields">
	                <div class="field"><label>ID</label><input data-shop-product-field="id" value="${this.escAttr(product.id || '')}"></div>
	                <div class="field"><label>Type</label><select class="ui dropdown" data-shop-product-field="type">${['item', 'system_ticket'].map((type) => `<option value="${type}" ${product.type === type ? 'selected' : ''}>${type}</option>`).join('')}</select></div>
	                <div class="field"><label>Name</label><input data-shop-product-field="name" value="${this.escAttr(product.name || '')}"></div>
	                <div class="field"><label>Short</label><input data-shop-product-field="shortName" value="${this.escAttr(product.shortName || '')}"></div>
	              </div>
	              <div class="four fields">
	                <div class="field"><label>Item Code</label><input data-shop-product-field="itemCode" value="${this.escAttr(product.itemCode || '')}"></div>
	                <div class="field"><label>Effect Type</label><input data-shop-product-field="effectType" value="${this.escAttr(product.effectType || '')}" placeholder="เช่น private_room_days"></div>
	                <div class="field"><label>Badge</label><input data-shop-product-field="badge" value="${this.escAttr(product.badge || '')}"></div>
	                <div class="field"><label>Move to Group</label><select class="ui dropdown" data-shop-item-move-group="${this.escAttr(product.id || '')}">${this.shopItemGroupOptions(group.id)}</select></div>
	              </div>
	            </div>
	            <div class="gacha-prize-fieldset">
	              <div class="gacha-prize-fieldset-title">ภาพ / รายละเอียด / เงื่อนไข</div>
	              <div class="three fields">
	                ${this.shopAssetPicker(product.image || '', 'image', 'Product Image', 'รูปสินค้าที่ใช้ทั้งบน shelf และ modal', 'data-shop-product-field=\"image\"', ['image'])}
	                <div class="field gacha-prize-upload-field"><label>&nbsp;</label><label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload image<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-shop-upload-kind="product" data-shop-upload-index="${index}" data-shop-upload-field="image" hidden></label></div>
	                <div class="field"><label>Preview State</label><div class="orbit-inline-summary-item"><strong>${this.esc(this.shopProductTitle(product))}</strong><span>${this.esc(state.publicStateLabel)}</span></div></div>
	              </div>
	              <div class="two fields">
	                <div class="field"><label>Detail</label><textarea rows="2" data-shop-product-field="detailText">${this.esc(product.detailText || '')}</textarea></div>
	                <div class="field"><label>Condition</label><textarea rows="2" data-shop-product-field="conditionText">${this.esc(product.conditionText || '')}</textarea></div>
	              </div>
	              <div class="inline fields">
	                <label class="orbit-native-checkbox"><input type="checkbox" data-shop-product-field="active" ${product.active !== false ? 'checked' : ''}><span>Active</span></label>
	                <label class="orbit-native-checkbox"><input type="checkbox" data-shop-product-field="visible" ${product.visible !== false ? 'checked' : ''}><span>Visible</span></label>
	                <button class="ui mini button" type="button" data-shop-option-add="${index}"><i class="fa-solid fa-plus"></i> Option</button>
	              </div>
	            </div>
	            <div class="gacha-prize-fieldset">
	              <div class="gacha-prize-fieldset-title">ตัวเลือก ราคา และส่งของขวัญ</div>
	              <div class="shop-setting-list">
	                ${options.map((option, optionIndex) => this.shopOptionHtml(option, index, optionIndex, units)).join('')}
	              </div>
	            </div>
	          </div>
	        </article>
	      `;
	    },
	    toggleShopItemGroup(groupId) {
	      this.syncGachaShopDraftsToState();
	      const current = this.shopItemGroupExpandedId();
	      this.state.shopItemGroupExpanded = current === String(groupId) ? '' : String(groupId);
	      this.renderShopProducts();
	    },
	    toggleShopItemEntry(productId) {
	      this.syncGachaShopDraftsToState();
	      const expanded = this.shopItemEntryExpandedState();
	      expanded[String(productId)] = !expanded[String(productId)];
	      this.renderShopProducts();
	    },
	    applyShopItemGroupOrder(groups) {
	      const products = this.state.shopConfig?.products || [];
	      groups.forEach((group, groupIndex) => {
	        const sampleProduct = group.items[0]?.product || {};
	        const meta = this.shopResolveItemGroupMeta(group, sampleProduct, (groupIndex + 1) * 100);
	        const groupSortOrder = (groupIndex + 1) * 100;
	        group.items.forEach(({ product, index }, itemIndex) => {
	          const target = products[index] || products.find((row) => String(row?.id || '') === String(product?.id || ''));
	          if (!target) return;
	          Object.assign(target, {
	            groupId: meta.id,
	            groupName: meta.name,
	            groupBadge: meta.badge,
	            groupDescription: meta.description,
	            groupSortOrder,
	            sortOrder: groupSortOrder + ((itemIndex + 1) * 10),
	          });
	        });
	      });
	    },
	    addShopItemProduct(groupId = '') {
	      const groups = this.shopItemGroupCollections();
	      const currentGroupId = String(groupId || this.shopItemGroupExpandedId() || groups[0]?.id || '').trim();
	      if (!currentGroupId) {
	        this.addShopItemGroup();
	        return;
	      }
	      this.addShopItemToGroup(currentGroupId);
	    },
	    addShopItemGroup(seed = {}) {
	      this.syncGachaShopDraftsToState();
	      const groupCount = this.shopItemGroupCollections().length;
	      const baseId = this.shopSlug(seed.groupId || `item-group-${Date.now().toString(36)}`, `item-group-${Date.now().toString(36)}`);
	      this.addShopProduct(seed.type || 'item', {
	        groupId: baseId,
	        groupName: String(seed.groupName || `กลุ่มสินค้า ${groupCount + 1}`).trim(),
	        groupBadge: String(seed.groupBadge || '').trim(),
	        groupDescription: String(seed.groupDescription || '').trim(),
	        groupSortOrder: (groupCount + 1) * 100,
	        sortOrder: ((groupCount + 1) * 100) + 10,
	      }, {
	        syncDrafts: false,
	        render: false,
	      });
	      const newProduct = this.state.shopConfig?.products?.[this.state.shopConfig.products.length - 1];
	      this.state.shopItemGroupExpanded = baseId;
	      if (newProduct?.id) {
	        this.shopItemEntryExpandedState()[String(newProduct.id)] = true;
	      }
	      this.renderShopProducts();
	    },
	    addShopItemToGroup(groupId, seed = {}) {
	      this.syncGachaShopDraftsToState();
	      const groups = this.shopItemGroupCollections();
	      const group = groups.find((entry) => String(entry.id || '') === String(groupId || ''));
	      if (!group) {
	        this.addShopItemGroup(seed);
	        return;
	      }
	      const meta = this.shopResolveItemGroupMeta(group, group.items[0]?.product || {}, group.sortOrder || 0);
	      const nextSort = (group.items[group.items.length - 1]?.product?.sortOrder || (meta.sortOrder || 0)) + 10;
	      this.addShopProduct(seed.type || 'item', {
	        groupId: meta.id,
	        groupName: meta.name,
	        groupBadge: meta.badge,
	        groupDescription: meta.description,
	        groupSortOrder: Number(group.sortOrder || meta.sortOrder || 0),
	        sortOrder: nextSort,
	      }, {
	        syncDrafts: false,
	        render: false,
	      });
	      const newProduct = this.state.shopConfig?.products?.[this.state.shopConfig.products.length - 1];
	      this.state.shopItemGroupExpanded = meta.id;
	      if (newProduct?.id) {
	        this.shopItemEntryExpandedState()[String(newProduct.id)] = true;
	      }
	      this.renderShopProducts();
	    },
	    removeShopItemGroup(groupId, skipConfirm = false) {
	      if (!skipConfirm && !confirm('Remove this item group and every product inside it?')) return;
	      this.syncGachaShopDraftsToState();
	      const targetGroupId = String(groupId || '');
	      const targetIds = new Set(this.shopItemGroupCollections()
	        .find((entry) => String(entry.id || '') === targetGroupId)?.items
	        .map(({ product }) => String(product.id || '')) || []);
	      this.state.shopConfig.products = (this.state.shopConfig.products || []).filter((product) => {
	        if (product.type === 'role') return true;
	        return !targetIds.has(String(product.id || ''));
	      });
	      this.state.shopItemGroupExpanded = '';
	      const expanded = this.shopItemEntryExpandedState();
	      targetIds.forEach((productId) => { delete expanded[productId]; });
	      this.renderShopProducts();
	    },
	    removeShopItemProduct(productId) {
	      this.syncGachaShopDraftsToState();
	      const targetId = String(productId || '');
	      const groups = this.shopItemGroupCollections();
	      const group = groups.find((entry) => entry.items.some(({ product }) => String(product.id || '') === targetId));
	      if (!group) return;
	      if (group.items.length <= 1) {
	        if (!confirm('สินค้านี้เป็นตัวสุดท้ายของกลุ่ม ต้องการลบทั้งกลุ่มเลยไหม?')) return;
	        this.removeShopItemGroup(group.id, true);
	        return;
	      }
	      this.state.shopConfig.products = (this.state.shopConfig.products || []).filter((product) => String(product.id || '') !== targetId);
	      delete this.shopItemEntryExpandedState()[targetId];
	      this.state.shopItemGroupExpanded = group.id;
	      this.renderShopProducts();
	    },
	    saveShopItemGroup(groupId, trigger) {
	      this.state.shopItemGroupExpanded = String(groupId || this.shopItemGroupExpanded || '');
	      this.saveGachaShop({
	        button: trigger || null,
	        toast: 'บันทึก group แล้ว',
	      });
	    },
	    moveShopItemProduct(productId, targetGroupId) {
	      const nextGroupId = String(targetGroupId || '').trim();
	      if (!nextGroupId) return;
	      this.syncGachaShopDraftsToState();
	      const groups = this.shopItemGroupCollections();
	      const targetGroup = groups.find((entry) => String(entry.id || '') === nextGroupId);
	      const targetProduct = (this.state.shopConfig?.products || []).find((product) => String(product.id || '') === String(productId || ''));
	      if (!targetGroup || !targetProduct) return;
	      const currentGroup = groups.find((entry) => entry.items.some(({ product }) => String(product.id || '') === String(productId || '')));
	      if (currentGroup && String(currentGroup.id || '') === nextGroupId) return;
	      const meta = this.shopResolveItemGroupMeta(targetGroup, targetGroup.items[0]?.product || targetProduct, targetGroup.sortOrder || 0);
	      Object.assign(targetProduct, {
	        groupId: meta.id,
	        groupName: meta.name,
	        groupBadge: meta.badge,
	        groupDescription: meta.description,
	        groupSortOrder: Number(targetGroup.sortOrder || meta.sortOrder || 0),
	        sortOrder: (targetGroup.items[targetGroup.items.length - 1]?.product?.sortOrder || ((targetGroup.sortOrder || 0) + 10)) + 10,
	      });
	      this.state.shopItemGroupExpanded = meta.id;
	      this.shopItemEntryExpandedState()[String(productId)] = true;
	      this.renderShopProducts();
	    },
	    reorderShopItemGroups(fromGroupId, toGroupId) {
	      if (!fromGroupId || !toGroupId || fromGroupId === toGroupId) return;
	      this.syncGachaShopDraftsToState();
	      const groups = this.shopItemGroupCollections();
	      const from = groups.findIndex((group) => String(group.id || '') === String(fromGroupId));
	      const to = groups.findIndex((group) => String(group.id || '') === String(toGroupId));
	      if (from === -1 || to === -1) return;
	      const [moved] = groups.splice(from, 1);
	      groups.splice(to, 0, moved);
	      this.applyShopItemGroupOrder(groups);
	      this.state.shopItemGroupExpanded = String(fromGroupId);
	      this.renderShopProducts();
	    },
	    reorderShopItemEntries(groupId, fromProductId, toProductId) {
	      if (!groupId || !fromProductId || !toProductId || fromProductId === toProductId) return;
	      this.syncGachaShopDraftsToState();
	      const groups = this.shopItemGroupCollections();
	      const group = groups.find((entry) => String(entry.id || '') === String(groupId));
	      if (!group) return;
	      const from = group.items.findIndex(({ product }) => String(product.id || '') === String(fromProductId));
	      const to = group.items.findIndex(({ product }) => String(product.id || '') === String(toProductId));
	      if (from === -1 || to === -1) return;
	      const [moved] = group.items.splice(from, 1);
	      group.items.splice(to, 0, moved);
	      this.applyShopItemGroupOrder(groups);
	      this.state.shopItemGroupExpanded = String(groupId);
	      this.shopItemEntryExpandedState()[String(fromProductId)] = true;
	      this.renderShopProducts();
	    },
	    bindShopItemGroupEditor() {
	      $('[data-shop-toggle-item-group]').off('click.shopItemGroup').on('click.shopItemGroup', (event) => {
	        this.toggleShopItemGroup(String($(event.currentTarget).data('shop-toggle-item-group') || ''));
	      });
	      $('[data-shop-toggle-item-entry]').off('click.shopItemEntry').on('click.shopItemEntry', (event) => {
	        this.toggleShopItemEntry(String($(event.currentTarget).data('shop-toggle-item-entry') || ''));
	      });
	      $('[data-shop-add-item-to-group]').off('click.shopItemAdd').on('click.shopItemAdd', (event) => {
	        event.preventDefault();
	        this.addShopItemToGroup(String($(event.currentTarget).data('shop-add-item-to-group') || ''));
	      });
	      $('[data-shop-remove-item-group]').off('click.shopItemGroupRemove').on('click.shopItemGroupRemove', (event) => {
	        this.removeShopItemGroup(String($(event.currentTarget).data('shop-remove-item-group') || ''));
	      });
	      $('[data-shop-save-item-group]').off('click.shopItemGroupSave').on('click.shopItemGroupSave', (event) => {
	        this.saveShopItemGroup(String($(event.currentTarget).data('shop-save-item-group') || ''), event.currentTarget);
	      });
	      $('[data-shop-item-remove]').off('click.shopItemRemove').on('click.shopItemRemove', (event) => {
	        this.removeShopItemProduct(String($(event.currentTarget).data('shop-item-remove') || ''));
	      });
	      $('[data-shop-item-move-group]').off('change.shopItemMove').on('change.shopItemMove', (event) => {
	        this.moveShopItemProduct(String($(event.currentTarget).data('shop-item-move-group') || ''), String($(event.currentTarget).val() || ''));
	      });
	      $('.shop-item-group-row').off('dragstart.shopItemGroup dragover.shopItemGroup drop.shopItemGroup')
	        .on('dragstart.shopItemGroup', (e) => {
	          this.state.shopDragItemGroup = {
	            id: String($(e.currentTarget).data('shop-item-group-id') || ''),
	          };
	          e.originalEvent.dataTransfer.effectAllowed = 'move';
	        })
	        .on('dragover.shopItemGroup', (e) => {
	          e.preventDefault();
	          e.originalEvent.dataTransfer.dropEffect = 'move';
	        })
	        .on('drop.shopItemGroup', (e) => {
	          e.preventDefault();
	          const from = this.state.shopDragItemGroup;
	          const toId = String($(e.currentTarget).data('shop-item-group-id') || '');
	          if (!from?.id) return;
	          this.reorderShopItemGroups(from.id, toId);
	        });
	      $('.shop-item-group-entry').off('dragstart.shopItemEntry dragover.shopItemEntry drop.shopItemEntry')
	        .on('dragstart.shopItemEntry', (e) => {
	          this.state.shopDragItemEntry = {
	            groupId: String($(e.currentTarget).data('shop-item-group-id') || ''),
	            productId: String($(e.currentTarget).data('shop-product-id') || ''),
	          };
	          e.originalEvent.dataTransfer.effectAllowed = 'move';
	        })
	        .on('dragover.shopItemEntry', (e) => {
	          e.preventDefault();
	          e.originalEvent.dataTransfer.dropEffect = 'move';
	        })
	        .on('drop.shopItemEntry', (e) => {
	          e.preventDefault();
	          const from = this.state.shopDragItemEntry;
	          const target = $(e.currentTarget);
	          const groupId = String(target.data('shop-item-group-id') || '');
	          if (!from?.productId || from.groupId !== groupId) return;
	          this.reorderShopItemEntries(groupId, from.productId, String(target.data('shop-product-id') || ''));
	        });
	    },
	    shopProductHtml(product, index) {
	      const units = this.state.shopUnits || [];
	      const options = product.purchaseOptions || [];
	      const visibleOptions = this.shopProductPublicState(product).visibleOptions;
	      const isRole = product.type === 'role';
	      const seriesMeta = (this.state.shopRoleSeries || []).find((series) => String(series.id || '') === String(product.seriesSourceId || ''));
	      const publicStateLabel = this.shopProductPublicState(product).publicStateLabel;
	      return `
	        <article class="gacha-prize-row" data-shop-product-index="${index}">
          <div class="gacha-prize-row-main">
            <div class="gacha-prize-row-title">${this.esc(product.name || product.id)}</div>
            <div class="orbit-user-sub">${this.esc(product.type)} · ${this.esc(product.id || '')}${seriesMeta ? ` · ${this.esc(seriesMeta.name || seriesMeta.id || '')}` : ''} · ${this.esc(publicStateLabel)}</div>
          </div>
          <div class="ui small form gacha-prize-row-editor">
            <details class="shop-config-fold" open>
              <summary><i class="fa-solid fa-sliders"></i> ข้อมูลหลัก</summary>
              <div class="four fields">
                <div class="field"><label>ID</label><input data-shop-product-field="id" value="${this.escAttr(product.id || '')}"></div>
                <div class="field"><label>Type</label><select class="ui dropdown" data-shop-product-field="type">
                  ${['role', 'item', 'system_ticket'].map((type) => `<option value="${type}" ${product.type === type ? 'selected' : ''}>${type}</option>`).join('')}
                </select></div>
                <div class="field"><label>Name</label><input data-shop-product-field="name" value="${this.escAttr(product.name || '')}"></div>
                <div class="field"><label>Short</label><input data-shop-product-field="shortName" value="${this.escAttr(product.shortName || '')}"></div>
              </div>
              <div class="four fields">
                <div class="field"><label>Item Code</label><input data-shop-product-field="itemCode" value="${this.escAttr(product.itemCode || '')}"></div>
                <div class="field">
                  <label>${isRole ? 'Discord Role' : 'Effect Type'}</label>
                  ${isRole
                    ? `<select class="ui search dropdown" data-shop-product-field="discordRoleId">${this.shopRoleOptions(product.discordRoleId)}</select>`
                    : `<input data-shop-product-field="effectType" value="${this.escAttr(product.effectType || '')}" placeholder="เช่น private_room_days">`}
                </div>
                <div class="field"><label>Badge</label><input data-shop-product-field="badge" value="${this.escAttr(product.badge || '')}"></div>
                <div class="field"><label>Sort</label><input type="number" data-shop-product-field="sortOrder" value="${this.escAttr(product.sortOrder ?? index * 10)}"></div>
              </div>
            </details>
            <details class="shop-config-fold">
              <summary><i class="fa-solid fa-image"></i> ภาพ / กลุ่ม / ข้อความ</summary>
              ${isRole ? `
                <div class="three fields">
                  <div class="field"><label>Series Source</label><input data-shop-product-field="seriesSourceId" value="${this.escAttr(product.seriesSourceId || '')}" readonly></div>
                  ${this.shopAssetPicker(product.image || '', 'image', 'Image Override', 'ปล่อยว่างได้ ถ้าเป็นยศระบบจะ fallback ไปใช้ icon ของ Discord', 'data-shop-product-field=\"image\"', ['image'])}
                  <div class="field gacha-prize-upload-field"><label>&nbsp;</label><label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload image<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-shop-upload-kind="product" data-shop-upload-index="${index}" data-shop-upload-field="image" hidden></label></div>
                </div>
              ` : `
                <div class="four fields">
                  <div class="field"><label>Group Name</label><input data-shop-product-field="groupName" value="${this.escAttr(product.groupName || '')}" placeholder="เช่น ห้องส่วนตัว"></div>
                  <div class="field"><label>Group Badge</label><input data-shop-product-field="groupBadge" value="${this.escAttr(product.groupBadge || '')}" placeholder="ป้ายสั้น ๆ"></div>
                  <div class="field"><label>Group Sort</label><input type="number" data-shop-product-field="groupSortOrder" value="${this.escAttr(product.groupSortOrder ?? 0)}"></div>
                  <div class="field"><label>Group ID</label><input data-shop-product-field="groupId" value="${this.escAttr(product.groupId || '')}" placeholder="ปล่อยว่างให้ระบบสร้าง"></div>
                </div>
                <div class="three fields">
                  <div class="field"><label>Group Description</label><textarea rows="2" data-shop-product-field="groupDescription">${this.esc(product.groupDescription || '')}</textarea></div>
                  ${this.shopAssetPicker(product.image || '', 'image', 'Product Image', 'รูปสินค้าที่ใช้ทั้งบน shelf และ modal', 'data-shop-product-field=\"image\"', ['image'])}
                  <div class="field gacha-prize-upload-field"><label>&nbsp;</label><label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload image<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-shop-upload-kind="product" data-shop-upload-index="${index}" data-shop-upload-field="image" hidden></label></div>
                </div>
              `}
              <div class="two fields">
                <div class="field"><label>Detail</label><textarea rows="2" data-shop-product-field="detailText">${this.esc(product.detailText || '')}</textarea></div>
                <div class="field"><label>Condition</label><textarea rows="2" data-shop-product-field="conditionText">${this.esc(product.conditionText || '')}</textarea></div>
              </div>
            </details>
            <div class="inline fields">
              <label class="orbit-native-checkbox"><input type="checkbox" data-shop-product-field="active" ${product.active !== false ? 'checked' : ''}><span>Active</span></label>
              <label class="orbit-native-checkbox"><input type="checkbox" data-shop-product-field="visible" ${product.visible !== false ? 'checked' : ''}><span>Visible</span></label>
              <button class="ui mini button" type="button" data-shop-option-add="${index}"><i class="fa-solid fa-plus"></i> Option</button>
              <button class="ui mini red button" type="button" data-shop-product-remove="${index}"><i class="fa-solid fa-trash"></i></button>
            </div>
            <details class="shop-config-fold" open>
              <summary><i class="fa-solid fa-tags"></i> ตัวเลือก ราคา และส่งของขวัญ</summary>
              <div class="shop-setting-list">
                ${options.map((option, optionIndex) => this.shopOptionHtml(option, index, optionIndex, units)).join('')}
              </div>
            </details>
          </div>
        </article>
      `;
    },
    uploadShopAsset(input) {
      const file = input.files?.[0];
      const kind = String($(input).data('shop-upload-kind') || '');
      const index = Number($(input).data('shop-upload-index') || 0);
      const seriesId = String($(input).data('shop-upload-series-id') || '').trim();
      const field = String($(input).data('shop-upload-field') || 'image');
      if (!file) return;
      this.syncGachaShopDraftsToState();
      const formData = new FormData();
      formData.append('image', file);
      if (kind === 'role-series' && field === 'headerImage') {
        formData.append('usage', 'shop-role-series-banner');
      }
      this.apiForm('/api/admin/gacha_upload.php', formData)
        .done((res) => {
          const uploadedKind = String(res.kind || this.shopAssetKind(res.path || '', 'image'));
          this.state.shopAssets = [{
            path: res.path,
            name: String(res.path || '').split('/').pop(),
            url: res.url,
            kind: uploadedKind,
            description: String(res.path || ''),
            modifiedAt: Math.floor(Date.now() / 1000),
            isUploaded: true,
          }].concat((this.state.shopAssets || []).filter((asset) => String(asset.path || '') !== String(res.path || '')));
          if (kind === 'unit') {
            if (this.state.shopUnits[index]) {
              this.state.shopUnits[index][field] = res.path;
            }
            this.renderShopUnits();
          } else if (kind === 'role-series') {
            const settings = this.shopRoleSeriesSettings();
            let setting = settings.find((entry) => String(entry.seriesId || '') === seriesId);
            if (!setting) {
              setting = { seriesId, headerImage: '', purchaseOptions: [] };
              settings.push(setting);
            }
            setting[field] = res.path;
            this.renderShopRoleSeriesConfigs();
          } else {
            if (this.state.shopConfig?.products?.[index]) {
              this.state.shopConfig.products[index][field] = res.path;
            }
            this.renderShopProducts();
          }
          this.toast(uploadedKind === 'video' ? 'อัปโหลดวิดีโอแล้ว' : 'toast.gacha_image_uploaded');
        })
        .fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.upload_failed')));
    },
    shopOptionGift(option = {}, units = []) {
      const gift = option.gift && typeof option.gift === 'object' ? option.gift : {};
      const prices = gift.prices && typeof gift.prices === 'object' ? gift.prices : {};
      const enabledUnits = gift.enabledUnits && typeof gift.enabledUnits === 'object' ? gift.enabledUnits : {};
      const basePrices = option.prices && typeof option.prices === 'object' ? option.prices : {};
      const nextPrices = {};
      const nextEnabledUnits = {};
      (units || []).forEach((unit) => {
        const code = String(unit.unitCode || '');
        if (!code) return;
        nextEnabledUnits[code] = Boolean(enabledUnits[code]);
        nextPrices[code] = Number(prices[code] ?? basePrices[code] ?? 0);
      });
      return {
        enabled: Boolean(gift.enabled),
        useCustomPrices: Boolean(gift.useCustomPrices),
        enabledUnits: nextEnabledUnits,
        prices: nextPrices,
      };
    },
    initShopGiftControls(scope) {
      const root = $(scope);
      const sync = (row) => {
        const $row = $(row);
        const enabled = $row.find('[data-shop-option-gift-field="enabled"], [data-shop-role-series-option-gift-field="enabled"]').is(':checked');
        const custom = $row.find('[data-shop-option-gift-field="useCustomPrices"], [data-shop-role-series-option-gift-field="useCustomPrices"]').is(':checked');
        $row.find('[data-shop-gift-body]').prop('hidden', !enabled);
        $row.find('[data-shop-gift-custom]').prop('hidden', !(enabled && custom));
      };
      root.find('[data-shop-option-index]').each((_, row) => sync(row));
      root.find('[data-shop-role-series-option-index]').each((_, row) => sync(row));
      root.find('[data-shop-option-gift-field], [data-shop-role-series-option-gift-field]').off('change.shopGift').on('change.shopGift', (event) => {
        sync($(event.currentTarget).closest('[data-shop-option-index], [data-shop-role-series-option-index]'));
      });
    },
    initShopUnitThemeControls(scope) {
      const root = $(scope);
      const sync = (row) => {
        const $row = $(row);
        const enabled = $row.find('[data-shop-unit-theme-field="overrideEnabled"]').is(':checked');
        $row.find('[data-shop-unit-theme-body]').prop('hidden', !enabled);
      };
      root.find('[data-shop-unit-index]').each((_, row) => sync(row));
      root.find('[data-shop-unit-theme-field="overrideEnabled"]').off('change.shopUnitTheme').on('change.shopUnitTheme', (event) => {
        sync($(event.currentTarget).closest('[data-shop-unit-index]'));
      });
    },
    shopOptionHtml(option, productIndex, optionIndex, units) {
      const prices = option.prices || {};
      const gift = this.shopOptionGift(option, units);
      return `
        <div class="gacha-prize-fieldset" data-shop-option-index="${optionIndex}">
          <div class="gacha-prize-fieldset-title">Option ${optionIndex + 1}</div>
          <div class="ui tiny form">
            <div class="four fields">
              <div class="field"><label>ID</label><input data-shop-option-field="id" value="${this.escAttr(option.id || '')}"></div>
              <div class="field"><label>Label</label><input data-shop-option-field="label" value="${this.escAttr(option.label || '')}"></div>
              <div class="field"><label>Days</label><input type="number" min="0" data-shop-option-field="days" value="${this.escAttr(option.days || 0)}"></div>
              <div class="field"><label>Sort</label><input type="number" data-shop-option-field="sortOrder" value="${this.escAttr(option.sortOrder ?? optionIndex * 10)}"></div>
            </div>
            <details class="shop-config-fold" open>
              <summary><i class="fa-solid fa-wallet"></i> ราคาซื้อเอง</summary>
              <div class="${Math.max(1, Math.min(4, units.length || 1))} fields">
                ${units.map((unit) => {
                  const code = String(unit.unitCode || '');
                  return `<div class="field"><label>${this.esc(unit.shortName || unit.displayName || code)}</label><input type="number" min="0" data-shop-option-price="${this.escAttr(code)}" value="${this.escAttr(prices[code] || 0)}"></div>`;
                }).join('')}
              </div>
            </details>
            <details class="shop-config-fold" ${gift.enabled ? 'open' : ''}>
              <summary><i class="fa-solid fa-gift"></i> ส่งของขวัญให้เพื่อน</summary>
              <div class="inline fields">
                <label class="orbit-native-checkbox"><input type="checkbox" data-shop-option-gift-field="enabled" ${gift.enabled ? 'checked' : ''}><span>เปิดโหมดส่งให้เพื่อน</span></label>
                <label class="orbit-native-checkbox"><input type="checkbox" data-shop-option-gift-field="useCustomPrices" ${gift.useCustomPrices ? 'checked' : ''}><span>ตั้งราคาส่งแยก</span></label>
              </div>
              <div data-shop-gift-body>
                <div class="orbit-muted" style="margin-bottom:8px">ติ๊กเฉพาะหน่วยที่อนุญาตให้ใช้ส่งของขวัญ ถ้าไม่ตั้งราคาแยก ระบบจะใช้ราคาซื้อเองของหน่วยนั้น</div>
                <div class="shop-gift-unit-grid">
                  ${units.map((unit) => {
                    const code = String(unit.unitCode || '');
                    const label = unit.shortName || unit.displayName || code;
                    return `
                      <div class="shop-gift-unit-row">
                        <label class="orbit-native-checkbox"><input type="checkbox" data-shop-option-gift-unit="${this.escAttr(code)}" ${gift.enabledUnits[code] ? 'checked' : ''}><span>${this.esc(label)}</span></label>
                        <div data-shop-gift-custom>
                          <input type="number" min="0" data-shop-option-gift-price="${this.escAttr(code)}" value="${this.escAttr(gift.prices[code] || 0)}" placeholder="${this.escAttr(prices[code] || 0)}">
                        </div>
                      </div>`;
                  }).join('')}
                </div>
              </div>
            </details>
            <div class="inline fields">
              <label class="orbit-native-checkbox"><input type="checkbox" data-shop-option-field="active" ${option.active !== false ? 'checked' : ''}><span>Active</span></label>
              <label class="orbit-native-checkbox"><input type="checkbox" data-shop-option-field="visible" ${option.visible !== false ? 'checked' : ''}><span>Visible</span></label>
              <button class="ui mini red button" type="button" data-shop-product-index="${productIndex}" data-shop-option-remove="${optionIndex}"><i class="fa-solid fa-trash"></i></button>
            </div>
          </div>
        </div>
      `;
    },
    renderShopTemplates() {
      const templates = this.state.shopConfig?.templates || [];
      $('#shopTemplateList').html(templates.map((template, index) => `
        <article class="gacha-prize-row" data-shop-template-index="${index}">
          <div class="gacha-prize-row-main">
            <div class="gacha-prize-row-title">${this.esc(template.name || template.id)}</div>
            <div class="orbit-user-sub">${this.esc(template.type || 'detail')} · ${this.esc(template.id || '')}</div>
          </div>
          <div class="ui small form gacha-prize-row-editor">
            <div class="three fields">
              <div class="field"><label>ID</label><input data-shop-template-field="id" value="${this.escAttr(template.id || '')}"></div>
              <div class="field"><label>Name</label><input data-shop-template-field="name" value="${this.escAttr(template.name || '')}"></div>
              <div class="field"><label>Type</label><select class="ui dropdown" data-shop-template-field="type">
                <option value="detail" ${template.type === 'detail' ? 'selected' : ''}>detail</option>
                <option value="condition" ${template.type === 'condition' ? 'selected' : ''}>condition</option>
              </select></div>
            </div>
            <div class="field"><label>Body</label><textarea rows="3" data-shop-template-field="body">${this.esc(template.body || '')}</textarea></div>
            <button class="ui mini red button" type="button" data-shop-template-remove="${index}"><i class="fa-solid fa-trash"></i></button>
          </div>
        </article>
      `).join(''));
      this.initShopScope('#shopTemplateList');
      $('[data-shop-template-remove]').off('click').on('click', (event) => {
        this.syncGachaShopDraftsToState();
        const index = Number($(event.currentTarget).data('shop-template-remove'));
        this.state.shopConfig.templates.splice(index, 1);
        this.renderShopTemplates();
      });
    },
    collectGachaShopPayload() {
      this.shopSyncDropdownHiddenValues('#pageContainer');
      const shopBuyDefaults = this.shopNormalizeBuyTheme({
        holdSeconds: this.shopFieldValue($('#shopBuyDefaultsPanel'), '[data-shop-buy-default="holdSeconds"]'),
        buttonBgStart: this.shopFieldValue($('#shopBuyDefaultsPanel'), '[data-shop-buy-default="buttonBgStart"]'),
        buttonBgEnd: this.shopFieldValue($('#shopBuyDefaultsPanel'), '[data-shop-buy-default="buttonBgEnd"]'),
        buttonTextColor: this.shopFieldValue($('#shopBuyDefaultsPanel'), '[data-shop-buy-default="buttonTextColor"]'),
        holdSweepStart: this.shopFieldValue($('#shopBuyDefaultsPanel'), '[data-shop-buy-default="holdSweepStart"]'),
        holdSweepEnd: this.shopFieldValue($('#shopBuyDefaultsPanel'), '[data-shop-buy-default="holdSweepEnd"]'),
      }, this.shopBuyThemeFallback(), false);
      const units = [];
      $('#shopUnitList [data-shop-unit-index]').each((_, row) => {
        const $row = $(row);
        const unitIndex = Number($row.data('shop-unit-index') || 0);
        const currentUnit = (this.state.shopUnits || [])[unitIndex] || {};
        const metadata = {
          ...(currentUnit.metadata || this.parseJson(currentUnit.metadataJson) || {}),
        };
        const overrideEnabled = this.shopCheckedValue($row, '[data-shop-unit-theme-field="overrideEnabled"]', false);
        metadata.shopBuyTheme = this.shopNormalizeBuyTheme({
          overrideEnabled,
          holdSeconds: overrideEnabled ? this.shopFieldValue($row, '[data-shop-unit-theme-field="holdSeconds"]') : '',
          buttonBgStart: overrideEnabled ? this.shopFieldValue($row, '[data-shop-unit-theme-field="buttonBgStart"]') : '',
          buttonBgEnd: overrideEnabled ? this.shopFieldValue($row, '[data-shop-unit-theme-field="buttonBgEnd"]') : '',
          buttonTextColor: overrideEnabled ? this.shopFieldValue($row, '[data-shop-unit-theme-field="buttonTextColor"]') : '',
          holdSweepStart: overrideEnabled ? this.shopFieldValue($row, '[data-shop-unit-theme-field="holdSweepStart"]') : '',
          holdSweepEnd: overrideEnabled ? this.shopFieldValue($row, '[data-shop-unit-theme-field="holdSweepEnd"]') : '',
        }, this.shopResolvedBuyDefaults(), true);
        units.push({
          unitCode: this.shopFieldValue($row, '[data-shop-unit-field="unitCode"]', currentUnit.unitCode || '').trim(),
          displayName: this.shopFieldValue($row, '[data-shop-unit-field="displayName"]', currentUnit.displayName || '').trim(),
          shortName: this.shopFieldValue($row, '[data-shop-unit-field="shortName"]', currentUnit.shortName || '').trim(),
          icon: this.shopFieldValue($row, '[data-shop-unit-field="icon"]', currentUnit.icon || '').trim(),
          isEnabled: this.shopCheckedValue($row, '[data-shop-unit-field="isEnabled"]', currentUnit.isEnabled !== false && Number(currentUnit.isEnabled) !== 0),
          sortOrder: this.shopNumberValue($row, '[data-shop-unit-field="sortOrder"]', currentUnit.sortOrder || 0),
          metadata,
        });
      });

	      const products = JSON.parse(JSON.stringify(this.state.shopConfig?.products || []));
	      const collectPurchaseOptions = ($row, currentProduct) => {
	        const purchaseOptions = [];
	        $row.find('[data-shop-option-index]').each((__, optionRow) => {
	          const $option = $(optionRow);
	          const optionIndex = Number($option.data('shop-option-index') || 0);
	          const currentOption = (currentProduct.purchaseOptions || [])[optionIndex] || {};
          const prices = {};
          $option.find('[data-shop-option-price]').each((___, input) => {
            const unitCode = String($(input).data('shop-option-price') || '');
            if (unitCode) prices[unitCode] = Number($(input).val() || 0);
          });
          const giftEnabledUnits = {};
          const giftPrices = {};
          $option.find('[data-shop-option-gift-unit]').each((___, input) => {
            const unitCode = String($(input).data('shop-option-gift-unit') || '');
            if (unitCode) giftEnabledUnits[unitCode] = $(input).is(':checked');
          });
          $option.find('[data-shop-option-gift-price]').each((___, input) => {
            const unitCode = String($(input).data('shop-option-gift-price') || '');
            if (unitCode) giftPrices[unitCode] = Number($(input).val() || 0);
          });
          purchaseOptions.push({
            id: this.shopFieldValue($option, '[data-shop-option-field="id"]', currentOption.id || '').trim(),
            label: this.shopFieldValue($option, '[data-shop-option-field="label"]', currentOption.label || '').trim(),
            days: this.shopNumberValue($option, '[data-shop-option-field="days"]', currentOption.days || 0),
            sortOrder: this.shopNumberValue($option, '[data-shop-option-field="sortOrder"]', currentOption.sortOrder || 0),
            prices,
            gift: {
              enabled: this.shopCheckedValue($option, '[data-shop-option-gift-field="enabled"]', currentOption.gift?.enabled || false),
              useCustomPrices: this.shopCheckedValue($option, '[data-shop-option-gift-field="useCustomPrices"]', currentOption.gift?.useCustomPrices || false),
              enabledUnits: giftEnabledUnits,
              prices: giftPrices,
            },
	            active: this.shopCheckedValue($option, '[data-shop-option-field="active"]', currentOption.active !== false),
	            visible: this.shopCheckedValue($option, '[data-shop-option-field="visible"]', currentOption.visible !== false),
	          });
	        });
	        return purchaseOptions;
	      };
	      $('#shopRoleProductList article[data-shop-product-index]').each((_, row) => {
	        const $row = $(row);
	        const productIndex = Number($row.data('shop-product-index') || 0);
	        const currentProduct = (this.state.shopConfig?.products || [])[productIndex] || {};
	        products[productIndex] = {
	          ...currentProduct,
	          id: this.shopFieldValue($row, '[data-shop-product-field="id"]', currentProduct.id || '').trim(),
	          type: this.shopFieldValue($row, '[data-shop-product-field="type"]', currentProduct.type || 'role'),
	          name: this.shopFieldValue($row, '[data-shop-product-field="name"]', currentProduct.name || '').trim(),
	          shortName: this.shopFieldValue($row, '[data-shop-product-field="shortName"]', currentProduct.shortName || '').trim(),
	          itemCode: this.shopFieldValue($row, '[data-shop-product-field="itemCode"]', currentProduct.itemCode || '').trim(),
	          discordRoleId: this.shopFieldValue($row, '[data-shop-product-field="discordRoleId"]', currentProduct.discordRoleId || '').trim(),
	          effectType: this.shopFieldValue($row, '[data-shop-product-field="effectType"]', currentProduct.effectType || '').trim(),
	          seriesSourceId: this.shopFieldValue($row, '[data-shop-product-field="seriesSourceId"]', currentProduct.seriesSourceId || '').trim(),
	          badge: this.shopFieldValue($row, '[data-shop-product-field="badge"]', currentProduct.badge || '').trim(),
	          image: this.shopFieldValue($row, '[data-shop-product-field="image"]', currentProduct.image || '').trim(),
	          detailText: this.shopFieldValue($row, '[data-shop-product-field="detailText"]', currentProduct.detailText || '').trim(),
	          conditionText: this.shopFieldValue($row, '[data-shop-product-field="conditionText"]', currentProduct.conditionText || '').trim(),
	          sortOrder: this.shopNumberValue($row, '[data-shop-product-field="sortOrder"]', currentProduct.sortOrder || 0),
	          active: this.shopCheckedValue($row, '[data-shop-product-field="active"]', currentProduct.active !== false),
	          visible: this.shopCheckedValue($row, '[data-shop-product-field="visible"]', currentProduct.visible !== false),
	          purchaseOptions: collectPurchaseOptions($row, currentProduct),
	        };
	      });
	      $('#shopItemGroupList [data-shop-item-group-id]').each((groupOrder, groupRow) => {
	        const $groupRow = $(groupRow);
	        const firstItemRow = $groupRow.find('[data-shop-product-index]').first();
	        const firstProductIndex = Number(firstItemRow.data('shop-product-index') || 0);
	        const sampleProduct = (this.state.shopConfig?.products || [])[firstProductIndex] || {};
	        const groupDraft = {
	          groupId: this.shopFieldValue($groupRow, '[data-shop-item-group-field="groupId"]', sampleProduct.groupId || '').trim(),
	          groupName: this.shopFieldValue($groupRow, '[data-shop-item-group-field="groupName"]', sampleProduct.groupName || '').trim(),
	          groupBadge: this.shopFieldValue($groupRow, '[data-shop-item-group-field="groupBadge"]', sampleProduct.groupBadge || '').trim(),
	          groupDescription: this.shopFieldValue($groupRow, '[data-shop-item-group-field="groupDescription"]', sampleProduct.groupDescription || '').trim(),
	          groupSortOrder: this.shopNumberValue($groupRow, '[data-shop-item-group-field="groupSortOrder"]', sampleProduct.groupSortOrder || ((groupOrder + 1) * 100)),
	        };
	        const groupMeta = this.shopResolveItemGroupMeta(groupDraft, sampleProduct, (groupOrder + 1) * 100);
	        const groupSortOrder = (groupOrder + 1) * 100;
	        $groupRow.find('[data-shop-product-index]').each((itemOrder, row) => {
	          const $row = $(row);
	          const productIndex = Number($row.data('shop-product-index') || 0);
	          const currentProduct = (this.state.shopConfig?.products || [])[productIndex] || {};
	          products[productIndex] = {
	            ...currentProduct,
	            id: this.shopFieldValue($row, '[data-shop-product-field="id"]', currentProduct.id || '').trim(),
	            type: this.shopFieldValue($row, '[data-shop-product-field="type"]', currentProduct.type || 'item'),
	            name: this.shopFieldValue($row, '[data-shop-product-field="name"]', currentProduct.name || '').trim(),
	            shortName: this.shopFieldValue($row, '[data-shop-product-field="shortName"]', currentProduct.shortName || '').trim(),
	            itemCode: this.shopFieldValue($row, '[data-shop-product-field="itemCode"]', currentProduct.itemCode || '').trim(),
	            effectType: this.shopFieldValue($row, '[data-shop-product-field="effectType"]', currentProduct.effectType || '').trim(),
	            badge: this.shopFieldValue($row, '[data-shop-product-field="badge"]', currentProduct.badge || '').trim(),
	            image: this.shopFieldValue($row, '[data-shop-product-field="image"]', currentProduct.image || '').trim(),
	            detailText: this.shopFieldValue($row, '[data-shop-product-field="detailText"]', currentProduct.detailText || '').trim(),
	            conditionText: this.shopFieldValue($row, '[data-shop-product-field="conditionText"]', currentProduct.conditionText || '').trim(),
	            groupId: groupMeta.id,
	            groupName: groupMeta.name,
	            groupBadge: groupMeta.badge,
	            groupDescription: groupMeta.description,
	            groupSortOrder,
	            sortOrder: groupSortOrder + ((itemOrder + 1) * 10),
	            active: this.shopCheckedValue($row, '[data-shop-product-field="active"]', currentProduct.active !== false),
	            visible: this.shopCheckedValue($row, '[data-shop-product-field="visible"]', currentProduct.visible !== false),
	            purchaseOptions: collectPurchaseOptions($row, currentProduct),
	          };
	        });
	      });

      const roleSeriesSettings = [];
      $('#shopRoleSeriesConfigList [data-shop-role-series-id]').each((_, row) => {
        const $row = $(row);
        const seriesId = String($row.data('shop-role-series-id') || '').trim();
        const currentSetting = this.shopRoleSeriesSetting(seriesId);
        const purchaseOptions = [];
        $row.find('[data-shop-role-series-option-index]').each((__, optionRow) => {
          const $option = $(optionRow);
          const optionIndex = Number($option.data('shop-role-series-option-index') || 0);
          const currentOption = (currentSetting.purchaseOptions || [])[optionIndex] || {};
          const prices = {};
          $option.find('[data-shop-role-series-option-price]').each((___, input) => {
            const unitCode = String($(input).data('shop-role-series-option-price') || '');
            if (unitCode) prices[unitCode] = Number($(input).val() || 0);
          });
          const giftEnabledUnits = {};
          const giftPrices = {};
          $option.find('[data-shop-role-series-option-gift-unit]').each((___, input) => {
            const unitCode = String($(input).data('shop-role-series-option-gift-unit') || '');
            if (unitCode) giftEnabledUnits[unitCode] = $(input).is(':checked');
          });
          $option.find('[data-shop-role-series-option-gift-price]').each((___, input) => {
            const unitCode = String($(input).data('shop-role-series-option-gift-price') || '');
            if (unitCode) giftPrices[unitCode] = Number($(input).val() || 0);
          });
          purchaseOptions.push({
            id: this.shopFieldValue($option, '[data-shop-role-series-option-field="id"]', currentOption.id || '').trim(),
            label: this.shopFieldValue($option, '[data-shop-role-series-option-field="label"]', currentOption.label || '').trim(),
            days: this.shopNumberValue($option, '[data-shop-role-series-option-field="days"]', currentOption.days || 0),
            sortOrder: this.shopNumberValue($option, '[data-shop-role-series-option-field="sortOrder"]', currentOption.sortOrder || 0),
            prices,
            gift: {
              enabled: this.shopCheckedValue($option, '[data-shop-role-series-option-gift-field="enabled"]', currentOption.gift?.enabled || false),
              useCustomPrices: this.shopCheckedValue($option, '[data-shop-role-series-option-gift-field="useCustomPrices"]', currentOption.gift?.useCustomPrices || false),
              enabledUnits: giftEnabledUnits,
              prices: giftPrices,
            },
            active: this.shopCheckedValue($option, '[data-shop-role-series-option-field="active"]', currentOption.active !== false),
            visible: this.shopCheckedValue($option, '[data-shop-role-series-option-field="visible"]', currentOption.visible !== false),
          });
        });
        roleSeriesSettings.push({
          seriesId,
          headerImage: this.shopFieldValue($row, '[data-shop-role-series-field="headerImage"]', currentSetting.headerImage || '').trim(),
          purchaseOptions,
        });
      });

      const templates = [];
      $('#shopTemplateList [data-shop-template-index]').each((_, row) => {
        const $row = $(row);
        templates.push({
          id: String($row.find('[data-shop-template-field="id"]').val() || '').trim(),
          name: String($row.find('[data-shop-template-field="name"]').val() || '').trim(),
          type: String($row.find('[data-shop-template-field="type"]').val() || 'detail'),
          body: String($row.find('[data-shop-template-field="body"]').val() || '').trim(),
        });
      });

      return {
        units,
        config: {
          ...(this.state.shopConfig || {}),
          settings: {
            ...(this.state.shopConfig?.settings || {}),
            shopBuyDefaults,
            roleSeriesSettings,
          },
          products,
          templates,
        },
      };
    },
    addShopProduct(type, seed = {}, options = {}) {
      const { syncDrafts = true, render = true } = options;
      if (syncDrafts) {
        this.syncGachaShopDraftsToState();
      }
      const products = this.state.shopConfig.products || [];
      products.push({
        id: `${type}-${Date.now().toString(36)}`,
        type,
        name: type === 'role' ? '<roleName>' : 'สินค้าใหม่',
        shortName: '',
        itemCode: `${type}_${products.length + 1}`,
        discordRoleId: '',
        seriesSourceId: '',
        groupId: '',
        groupName: '',
        groupBadge: '',
        groupDescription: '',
        groupSortOrder: 0,
        badge: '',
        image: '',
        detailText: '',
        conditionText: '',
        active: true,
        visible: true,
        sortOrder: (products.length + 1) * 10,
        purchaseOptions: [{
          id: 'option-1',
          label: type === 'role' ? 'ถาวร' : '1 ชิ้น',
          days: 0,
          prices: { coin: 0 },
          gift: {
            enabled: false,
            useCustomPrices: false,
            enabledUnits: {},
            prices: {},
          },
          active: true,
          visible: true,
          sortOrder: 10,
        }],
        ...seed,
      });
      this.state.shopConfig.products = products;
      if (render) {
        this.renderShopProducts();
      }
    },
    addShopRoleSeriesProducts(seriesId) {
      const targetSeriesId = String(seriesId || '').trim();
      if (!targetSeriesId) {
        this.toast('เลือก Role Series ก่อน');
        return;
      }
      this.syncGachaShopDraftsToState();
      const series = (this.state.shopRoleSeries || []).find((entry) => String(entry.id || '') === targetSeriesId);
      const roles = (this.state.shopRoles || []).filter((role) => String(role.roleSeriesId || '') === targetSeriesId);
      const seriesSetting = this.shopRoleSeriesSetting(targetSeriesId);
      const defaultPurchaseOptions = this.shopClonePurchaseOptions(seriesSetting.purchaseOptions || []);
      if (!series || !roles.length) {
        this.toast('Series นี้ยังไม่มียศให้เพิ่ม');
        return;
      }
      const existingRoleIds = new Set((this.state.shopConfig?.products || [])
        .filter((product) => product.type === 'role')
        .map((product) => String(product.discordRoleId || ''))
        .filter(Boolean));
      let added = 0;
      roles.forEach((role) => {
        const roleId = String(role.roleId || '').trim();
        if (!roleId || existingRoleIds.has(roleId)) return;
        existingRoleIds.add(roleId);
        added += 1;
        this.addShopProduct('role', {
          id: `role-${roleId}`,
          name: '<roleName>',
          shortName: '',
          itemCode: `role_${roleId}`,
          discordRoleId: roleId,
          seriesSourceId: targetSeriesId,
          badge: String(series.badge || '').trim(),
          image: '',
          sortOrder: ((this.state.shopConfig.products || []).length + 1) * 10,
          purchaseOptions: defaultPurchaseOptions.length ? this.shopClonePurchaseOptions(defaultPurchaseOptions) : [{
            id: `option-${targetSeriesId}-1`,
            label: 'ถาวร',
            days: 0,
            prices: { coin: 0 },
            gift: {
              enabled: false,
              useCustomPrices: false,
              enabledUnits: {},
              prices: {},
            },
            active: true,
            visible: true,
            sortOrder: 10,
          }],
        }, {
          syncDrafts: false,
          render: false,
        });
      });
      if (!added) {
        this.toast('ยศใน series นี้ถูกเพิ่มเข้าร้านแล้วทั้งหมด');
      } else {
        this.renderShopProducts();
        this.toast(`เพิ่มยศจาก series แล้ว ${added} รายการ`);
      }
    },
    addShopTemplate() {
      this.syncGachaShopDraftsToState();
      const templates = this.state.shopConfig.templates || [];
      templates.push({
        id: `template-${Date.now().toString(36)}`,
        name: 'Template',
        type: 'detail',
        body: '',
      });
      this.state.shopConfig.templates = templates;
      this.renderShopTemplates();
    },
	    saveGachaShop(options = {}) {
	      const payload = this.collectGachaShopPayload();
	      const button = options.button ? $(options.button) : $('#shopSaveButton');
	      button.addClass('loading disabled');
	      this.api('/api/admin/gacha_shop.php', payload, 'POST').done((res) => {
	        this.state.shopConfig = res.config || payload.config;
	        this.state.shopUnits = res.units || payload.units;
	        this.toast(options.toast || 'toast.shop_saved');
	        this.loadGachaShop();
	      }).fail((xhr) => this.toast(xhr.responseJSON?.message || 'Shop save failed'))
	        .always(() => button.removeClass('loading disabled'));
	    },
    renderGachaPrizeEditor(timeline) {
      const config = this.state.gachaConfig || {};
      const settings = config.settings || {};
      const buttons = settings.buttons || {};
      $('#gachaEnabledToggle input[name="enabled"]').prop('checked', settings.enabled !== false);
      $('#gachaEnabledToggle').checkbox('refresh');
      $('#gachaSettingsForm [name="startingTicket"]').val(settings.startingBalances?.ticket ?? 0);
      $('#gachaSettingsForm [name="startingCoin"]').val(settings.startingBalances?.coin ?? 0);
      $('[data-gacha-default-icon-field="defaultRoleIcon"]').val(settings.defaultRoleIcon || '');
      $('[data-gacha-default-icon-field="defaultItemIcon"]').val(settings.defaultItemIcon || '');
      $('#gachaSettingsForm [name="ticketCost"]').val(buttons['2']?.cost ?? 1);
      $('#gachaSettingsForm [name="coinCost"]').val(buttons['1']?.cost ?? 10);
      $('#gachaSettingsForm [name="ticketLabel"]').val(buttons['2']?.label ?? 'Ticket Spin');
      $('#gachaSettingsForm [name="coinLabel"]').val(buttons['1']?.label ?? 'Coin Spin');
      $('#gachaSettingsForm [name="ticketEnabled"]').prop('checked', buttons['2']?.enabled !== false);
      $('#gachaSettingsForm [name="coinEnabled"]').prop('checked', buttons['1']?.enabled !== false);
      $('#gachaSettingsForm [name="defaultButtonId"]').val(String(settings.defaultButtonId || 1)).dropdown('refresh');
      $('#gachaSettingsForm .ui.checkbox').checkbox();
      this.normalizeVisibleCheckboxes('#gachaSettingsForm');

      const condition = config.conditions || {};
      const tierOptions = (config.tiers || []).map((tier) => `<option value="${this.escAttr(tier.id)}">${this.esc(tier.tier)} · ${this.esc(tier.name)}</option>`).join('');
      $('#gachaConditionForm [name="pityTierId"]').html(tierOptions).val(condition.pityTierId || 'mythic').dropdown('refresh');
      $('#gachaConditionForm [name="pityEvery"]').val(condition.pityEvery || 0);
      $('#gachaConditionForm [name="quotaWindowDays"]').val(condition.quotaWindowDays || 1);

      this.renderGachaTierGrid();
      this.state.gachaPrizeExpanded = this.state.gachaPrizeExpanded || { item: '', role: '' };
      this.renderGachaPrizeLists();
      this.renderGachaPrizeTemplates();
      this.renderGachaTimeline(timeline);
      this.bindGachaPrizeEditor();
      this.bindTabs('#pageContainer');
    },
    renderGachaTierGrid() {
      const tiers = this.state.gachaConfig?.tiers || [];
      $('#gachaTierGrid').html(tiers.map((tier, index) => `
        <article class="gacha-tier-card" data-tier-index="${index}" data-tier-id="${this.escAttr(tier.id || '')}" style="--tier-accent:${this.escAttr(tier.accent || '#8bd6ff')}">
          <div class="gacha-tier-card-head">
            <img src="${this.escAttr(this.gachaAssetUrl(tier.ball || 'ball_1.png'))}" alt="">
            <div>
              <strong>${this.esc(tier.tier || tier.id)}</strong>
              <span>${this.esc(tier.name || '')}</span>
            </div>
          </div>
          <div class="ui mini form">
            <div class="two fields">
              <div class="field"><label>${this.fieldLabel('Internal %', 'เรท tier ที่ใช้สุ่มจริงหลังบ้าน')}</label><input type="number" step="0.01" min="0" data-tier-field="rate" value="${this.escAttr(tier.rate ?? 0)}"></div>
              <div class="field"><label>${this.fieldLabel('Public %', 'เรท tier ที่แสดงหน้ารางวัล แยกจากเรทสุ่มจริงได้')}</label><input type="number" step="0.01" min="0" data-tier-field="displayRate" value="${this.escAttr(tier.displayRate ?? tier.rate ?? 0)}"></div>
            </div>
            <div class="field"><label>${this.fieldLabel('Summary', 'คำอธิบายสั้นของ tier ใช้แสดงในหน้ารางวัล')}</label><input data-tier-field="summary" value="${this.escAttr(tier.summary || '')}"></div>
            <div class="inline fields">
              <label class="ui checkbox"><input type="checkbox" data-tier-field="active" ${tier.active !== false ? 'checked' : ''}><span>Active</span></label>
              <label class="ui checkbox"><input type="checkbox" data-tier-field="visible" ${tier.visible !== false ? 'checked' : ''}><span>Visible</span></label>
            </div>
          </div>
        </article>
      `).join(''));
      $('#gachaTierGrid .ui.checkbox').checkbox();
      this.normalizeVisibleCheckboxes('#gachaTierGrid');
    },
    renderGachaPrizeLists() {
      this.renderGachaPrizeList('item', '#gachaItemPrizeList');
      this.renderGachaRoleConfigList('#gachaRolePrizeList');
      this.renderGachaRateOverview();
    },
    gachaRateOverviewConfig() {
      if ($('#gachaTierGrid [data-tier-id]').length || $('#gachaItemPrizeList [data-prize-id]').length || $('#gachaRolePrizeList [data-role-config-entry-id]').length) {
        return this.collectGachaPrizeConfig();
      }
      return JSON.parse(JSON.stringify(this.state.gachaConfig || {}));
    },
    gachaRoundNumber(value, digits = 4) {
      const numeric = Number(value || 0);
      if (!Number.isFinite(numeric)) return 0;
      const factor = 10 ** digits;
      return Math.round((numeric + Number.EPSILON) * factor) / factor;
    },
    gachaFormatPercent(value, digits = 2) {
      return this.gachaRoundNumber(value, digits).toFixed(digits);
    },
    gachaRateOverviewData(config = null) {
      const currentConfig = config || this.gachaRateOverviewConfig();
      const tiers = [...(currentConfig.tiers || [])]
        .sort((a, b) => this.gachaTierRank(a.id) - this.gachaTierRank(b.id) || String(a.name || '').localeCompare(String(b.name || '')));
      const activeTiers = tiers.filter((tier) => tier.active !== false);
      const visibleTiers = tiers.filter((tier) => tier.visible !== false);
      const totalActiveTierRate = activeTiers.reduce((sum, tier) => sum + Math.max(0, Number(tier.rate || 0)), 0);
      const totalVisibleTierRate = visibleTiers.reduce((sum, tier) => sum + Math.max(0, Number(tier.displayRate ?? tier.rate ?? 0)), 0);
      return tiers.map((tier) => {
        const tierId = String(tier.id || '');
        const tierInternalPercent = tier.active !== false && totalActiveTierRate > 0
          ? (Math.max(0, Number(tier.rate || 0)) / totalActiveTierRate) * 100
          : 0;
        const tierDisplayPercent = tier.visible !== false && totalVisibleTierRate > 0
          ? (Math.max(0, Number(tier.displayRate ?? tier.rate ?? 0)) / totalVisibleTierRate) * 100
          : 0;
        const prizes = (currentConfig.prizes || [])
          .filter((prize) => String(prize.tierId || '') === tierId)
          .map((prize) => {
            const type = String(prize.type || 'item') === 'role' ? 'role' : 'item';
            const resolvedName = this.gachaPrizeResolvedName(prize, type, false);
            const actualRoleName = type === 'role' ? this.gachaRoleName(prize) : '';
            const displayName = type === 'role'
              ? (actualRoleName || resolvedName || 'Role prize')
              : (resolvedName || 'Item prize');
            const detail = type === 'role'
              ? [
                  resolvedName && resolvedName !== displayName ? `Prize: ${resolvedName}` : '',
                  prize.discordRoleId ? `ID: ${prize.discordRoleId}` : 'Unassigned role',
                ].filter(Boolean).join(' · ')
              : [
                  prize.shortName ? `Short: ${prize.shortName}` : '',
                  prize.badge ? `Badge: ${prize.badge}` : '',
                ].filter(Boolean).join(' · ');
            return {
              ...prize,
              type,
              displayName,
              detail,
            };
          });
        const activePrizeRows = prizes.filter((prize) => prize.active !== false);
        const visiblePrizeRows = prizes.filter((prize) => prize.visible !== false);
        const totalActivePrizeWeight = activePrizeRows.reduce((sum, prize) => sum + Math.max(0, Number(prize.internalWeight || 0)), 0);
        const totalVisiblePrizeWeight = visiblePrizeRows.reduce((sum, prize) => sum + Math.max(0, Number(prize.displayWeight ?? prize.internalWeight ?? 0)), 0);
        const prizeRows = prizes
          .map((prize) => {
            const internalWithinTierPercent = prize.active !== false && totalActivePrizeWeight > 0
              ? (Math.max(0, Number(prize.internalWeight || 0)) / totalActivePrizeWeight) * 100
              : 0;
            const displayWithinTierPercent = prize.visible !== false && totalVisiblePrizeWeight > 0
              ? (Math.max(0, Number(prize.displayWeight ?? prize.internalWeight ?? 0)) / totalVisiblePrizeWeight) * 100
              : 0;
            return {
              ...prize,
              internalWithinTierPercent,
              displayWithinTierPercent,
              internalOverallPercent: tier.active !== false ? (tierInternalPercent * internalWithinTierPercent) / 100 : 0,
              displayOverallPercent: tier.visible !== false ? (tierDisplayPercent * displayWithinTierPercent) / 100 : 0,
              internalEditable: prize.active !== false && (activePrizeRows.length > 1 || totalActivePrizeWeight <= 0),
              displayEditable: prize.visible !== false && (visiblePrizeRows.length > 1 || totalVisiblePrizeWeight <= 0),
            };
          })
          .sort((a, b) => (Number(b.active !== false) - Number(a.active !== false))
            || (Number(b.visible !== false) - Number(a.visible !== false))
            || (b.internalWithinTierPercent - a.internalWithinTierPercent)
            || Number(a.sortOrder || 0) - Number(b.sortOrder || 0)
            || String(a.displayName || '').localeCompare(String(b.displayName || '')));
        return {
          ...tier,
          id: tierId,
          tierInternalPercent,
          tierDisplayPercent,
          tierInternalEditable: tier.active !== false && (activeTiers.length > 1 || totalActiveTierRate <= 0),
          tierDisplayEditable: tier.visible !== false && (visibleTiers.length > 1 || totalVisibleTierRate <= 0),
          internalPrizePoolPercent: totalActivePrizeWeight > 0 ? prizeRows.reduce((sum, prize) => sum + prize.internalWithinTierPercent, 0) : 0,
          displayPrizePoolPercent: totalVisiblePrizeWeight > 0 ? prizeRows.reduce((sum, prize) => sum + prize.displayWithinTierPercent, 0) : 0,
          activePrizeCount: activePrizeRows.length,
          visiblePrizeCount: visiblePrizeRows.length,
          prizeRows,
        };
      });
    },
    renderGachaRateOverview() {
      const mount = $('#gachaRateOverview');
      if (!mount.length) return;
      const tiers = this.gachaRateOverviewData();
      if (!tiers.length) {
        mount.html(this.empty('No tiers yet'));
        return;
      }
      const activeTierTotal = tiers
        .filter((tier) => tier.active !== false)
        .reduce((sum, tier) => sum + tier.tierInternalPercent, 0);
      const visibleTierTotal = tiers
        .filter((tier) => tier.visible !== false)
        .reduce((sum, tier) => sum + tier.tierDisplayPercent, 0);
      mount.html(`
        <div class="gacha-rate-overview-shell">
          <div class="gacha-rate-overview-note">
            <div>
              <strong>Quick rate editor</strong>
              <span>Real/backend percentages balance within active pools. Public/front percentages balance within visible pools.</span>
            </div>
            <div class="gacha-rate-overview-totals">
              <span class="gacha-rate-overview-total ${Math.abs(activeTierTotal - 100) < 0.01 || activeTierTotal === 0 ? '' : 'is-warning'}">Real tier pool ${this.gachaFormatPercent(activeTierTotal)}%</span>
              <span class="gacha-rate-overview-total ${Math.abs(visibleTierTotal - 100) < 0.01 || visibleTierTotal === 0 ? '' : 'is-warning'}">Public tier pool ${this.gachaFormatPercent(visibleTierTotal)}%</span>
            </div>
          </div>
          <div class="gacha-rate-overview-stack">
            ${tiers.map((tier) => `
              <article class="gacha-rate-tier-card" data-gacha-overview-tier="${this.escAttr(tier.id)}" style="--gacha-tier-accent:${this.escAttr(this.gachaTierColor(tier.id))};">
                <div class="gacha-rate-tier-head">
                  <div class="gacha-rate-tier-copy">
                    <span class="gacha-rate-tier-thumb"><img src="${this.escAttr(this.gachaAssetUrl(tier.ball || 'ball_1.png'))}" alt=""></span>
                    <div>
                      <strong>${this.esc(tier.tier || tier.id)} · ${this.esc(tier.name || '')}</strong>
                      <span>Tier rates decide the first draw step. Prize rows below then split each tier pool separately for real draw and public display.</span>
                    </div>
                  </div>
                  <div class="gacha-rate-tier-controls">
                    <div class="gacha-rate-tier-control-grid">
                      <div class="gacha-rate-tier-input">
                        <label>Real tier %</label>
                        <input type="number" step="0.01" min="0" max="100" data-gacha-overview-tier-rate="${this.escAttr(tier.id)}" value="${this.escAttr(this.gachaFormatPercent(tier.tierInternalPercent))}" ${tier.tierInternalEditable ? '' : 'disabled'}>
                      </div>
                      <div class="gacha-rate-tier-input">
                        <label>Public tier %</label>
                        <input type="number" step="0.01" min="0" max="100" data-gacha-overview-tier-display-rate="${this.escAttr(tier.id)}" value="${this.escAttr(this.gachaFormatPercent(tier.tierDisplayPercent))}" ${tier.tierDisplayEditable ? '' : 'disabled'}>
                      </div>
                    </div>
                    <div class="gacha-rate-toggle-row">
                      <label class="orbit-native-checkbox"><input type="checkbox" data-gacha-overview-tier-toggle="active" data-gacha-overview-tier-id="${this.escAttr(tier.id)}" ${tier.active !== false ? 'checked' : ''}><span>Active</span></label>
                      <label class="orbit-native-checkbox"><input type="checkbox" data-gacha-overview-tier-toggle="visible" data-gacha-overview-tier-id="${this.escAttr(tier.id)}" ${tier.visible !== false ? 'checked' : ''}><span>Visible</span></label>
                    </div>
                  </div>
                </div>
                <div class="gacha-rate-tier-meta">
                  <span class="gacha-rate-meta-pill">${tier.active !== false ? 'Active tier' : 'Paused tier'}</span>
                  <span class="gacha-rate-meta-pill">${tier.visible !== false ? 'Visible tier' : 'Hidden tier'}</span>
                  <span class="gacha-rate-meta-pill ${Math.abs(tier.internalPrizePoolPercent - 100) < 0.01 || tier.internalPrizePoolPercent === 0 ? '' : 'is-warning'}">Real pool ${this.gachaFormatPercent(tier.internalPrizePoolPercent)}%</span>
                  <span class="gacha-rate-meta-pill ${Math.abs(tier.displayPrizePoolPercent - 100) < 0.01 || tier.displayPrizePoolPercent === 0 ? '' : 'is-warning'}">Public pool ${this.gachaFormatPercent(tier.displayPrizePoolPercent)}%</span>
                  <span class="gacha-rate-meta-pill">${Number(tier.activePrizeCount || 0).toLocaleString()} active prizes</span>
                  <span class="gacha-rate-meta-pill">${Number(tier.visiblePrizeCount || 0).toLocaleString()} visible prizes</span>
                </div>
                <div class="gacha-rate-prize-list">
                  ${tier.prizeRows.length ? tier.prizeRows.map((prize) => `
                    <div class="gacha-rate-prize-row ${prize.active !== false || prize.visible !== false ? '' : 'is-paused'}" data-gacha-overview-prize="${this.escAttr(prize.id)}">
                      <span class="gacha-rate-prize-thumb">${this.gachaPrizeThumbMedia(prize, prize.type, 'thumb')}</span>
                      <span class="gacha-rate-prize-copy">
                        <strong>${this.esc(prize.displayName || 'Prize')}</strong>
                        <span>${this.esc(prize.detail || (prize.type === 'role' ? 'Role prize' : 'Item prize'))}</span>
                        <span class="gacha-rate-prize-flags">
                          ${this.badge(prize.type === 'role' ? 'Role' : 'Item', prize.type === 'role' ? 'partial' : '')}
                          ${prize.active !== false ? this.badge('Active', 'success') : this.badge('Paused', 'failed')}
                          ${prize.visible !== false ? this.badge('Visible', 'success') : this.badge('Hidden', '')}
                        </span>
                      </span>
                      <div class="gacha-rate-prize-controls">
                        <div class="gacha-rate-toggle-row">
                          <label class="orbit-native-checkbox"><input type="checkbox" data-gacha-overview-prize-toggle="active" data-gacha-overview-prize-id="${this.escAttr(prize.id)}" ${prize.active !== false ? 'checked' : ''}><span>Active</span></label>
                          <label class="orbit-native-checkbox"><input type="checkbox" data-gacha-overview-prize-toggle="visible" data-gacha-overview-prize-id="${this.escAttr(prize.id)}" ${prize.visible !== false ? 'checked' : ''}><span>Visible</span></label>
                        </div>
                        <div class="gacha-rate-prize-control-grid">
                          <span class="gacha-rate-prize-input">
                            <label>Real in tier %</label>
                            <input type="number" step="0.01" min="0" max="100" data-gacha-overview-prize-rate="${this.escAttr(prize.id)}" data-gacha-overview-tier-id="${this.escAttr(tier.id)}" value="${this.escAttr(this.gachaFormatPercent(prize.internalWithinTierPercent))}" ${prize.internalEditable ? '' : 'disabled'}>
                          </span>
                          <span class="gacha-rate-prize-input">
                            <label>Public in tier %</label>
                            <input type="number" step="0.01" min="0" max="100" data-gacha-overview-prize-display-rate="${this.escAttr(prize.id)}" data-gacha-overview-tier-id="${this.escAttr(tier.id)}" value="${this.escAttr(this.gachaFormatPercent(prize.displayWithinTierPercent))}" ${prize.displayEditable ? '' : 'disabled'}>
                          </span>
                          <span class="gacha-rate-prize-output">
                            <label>Real overall %</label>
                            <strong>${this.esc(this.gachaFormatPercent(prize.internalOverallPercent))}%</strong>
                          </span>
                          <span class="gacha-rate-prize-output">
                            <label>Public overall %</label>
                            <strong>${this.esc(this.gachaFormatPercent(prize.displayOverallPercent))}%</strong>
                          </span>
                        </div>
                      </div>
                    </div>
                  `).join('') : `<div class="gacha-rate-prize-empty">No prizes inside this tier yet.</div>`}
                </div>
              </article>
            `).join('')}
          </div>
        </div>
      `);
      this.bindGachaRateOverviewControls();
    },
    bindGachaRateOverviewControls() {
      $('[data-gacha-overview-tier-rate]').off('change.gachaOverview').on('change.gachaOverview', (e) => {
        this.applyGachaOverviewTierPercent(String($(e.currentTarget).data('gacha-overview-tier-rate') || ''), Number($(e.currentTarget).val() || 0), 'rate', 'active', 'gachaSetTierRateValue');
      });
      $('[data-gacha-overview-tier-display-rate]').off('change.gachaOverview').on('change.gachaOverview', (e) => {
        this.applyGachaOverviewTierPercent(String($(e.currentTarget).data('gacha-overview-tier-display-rate') || ''), Number($(e.currentTarget).val() || 0), 'displayRate', 'visible', 'gachaSetTierDisplayRateValue');
      });
      $('[data-gacha-overview-prize-rate]').off('change.gachaOverview').on('change.gachaOverview', (e) => {
        this.applyGachaOverviewPrizePercent(
          String($(e.currentTarget).data('gacha-overview-prize-rate') || ''),
          String($(e.currentTarget).data('gacha-overview-tier-id') || ''),
          Number($(e.currentTarget).val() || 0),
          'internal',
          'gachaSetPrizeInternalWeightValue',
        );
      });
      $('[data-gacha-overview-prize-display-rate]').off('change.gachaOverview').on('change.gachaOverview', (e) => {
        this.applyGachaOverviewPrizePercent(
          String($(e.currentTarget).data('gacha-overview-prize-display-rate') || ''),
          String($(e.currentTarget).data('gacha-overview-tier-id') || ''),
          Number($(e.currentTarget).val() || 0),
          'display',
          'gachaSetPrizeDisplayWeightValue',
        );
      });
      $('[data-gacha-overview-tier-toggle]').off('change.gachaOverview').on('change.gachaOverview', (e) => {
        const input = $(e.currentTarget);
        this.gachaSetTierBooleanValue(String(input.data('gacha-overview-tier-id') || ''), String(input.data('gacha-overview-tier-toggle') || ''), input.is(':checked'));
        this.renderGachaRateOverview();
      });
      $('[data-gacha-overview-prize-toggle]').off('change.gachaOverview').on('change.gachaOverview', (e) => {
        const input = $(e.currentTarget);
        this.gachaSetPrizeBooleanValue(String(input.data('gacha-overview-prize-id') || ''), String(input.data('gacha-overview-prize-toggle') || ''), input.is(':checked'));
        this.renderGachaRateOverview();
      });
    },
    gachaRebalancePercentages(entries, targetId, nextPercent) {
      const target = String(targetId || '');
      const currentEntries = (entries || [])
        .map((entry) => ({
          id: String(entry.id || ''),
          percent: Math.max(0, Number(entry.percent || 0)),
        }))
        .filter((entry) => entry.id !== '');
      if (!currentEntries.some((entry) => entry.id === target)) {
        return new Map(currentEntries.map((entry) => [entry.id, this.gachaRoundNumber(entry.percent, 6)]));
      }
      if (currentEntries.length <= 1) {
        return new Map(currentEntries.map((entry) => [entry.id, entry.id === target ? 100 : 0]));
      }
      const clamped = Math.max(0, Math.min(100, Number(nextPercent || 0)));
      const others = currentEntries.filter((entry) => entry.id !== target);
      const remaining = Math.max(0, 100 - clamped);
      const otherTotal = others.reduce((sum, entry) => sum + entry.percent, 0);
      const nextMap = new Map([[target, this.gachaRoundNumber(clamped, 6)]]);
      if (remaining <= 0) {
        others.forEach((entry) => nextMap.set(entry.id, 0));
        return nextMap;
      }
      if (otherTotal > 0) {
        others.forEach((entry) => {
          nextMap.set(entry.id, this.gachaRoundNumber((entry.percent / otherTotal) * remaining, 6));
        });
      } else {
        const equalShare = remaining / others.length;
        others.forEach((entry) => nextMap.set(entry.id, this.gachaRoundNumber(equalShare, 6)));
      }
      const total = Array.from(nextMap.values()).reduce((sum, value) => sum + value, 0);
      const delta = this.gachaRoundNumber(100 - total, 6);
      if (Math.abs(delta) > 0.000001) {
        const adjustId = others.length ? others[others.length - 1].id : target;
        nextMap.set(adjustId, this.gachaRoundNumber((nextMap.get(adjustId) || 0) + delta, 6));
      }
      return nextMap;
    },
    gachaSetTierRateValue(tierId, value) {
      const card = $('#gachaTierGrid [data-tier-id]').filter((_, node) => String($(node).data('tier-id') || '') === String(tierId || '')).first();
      if (!card.length) return;
      card.find('[data-tier-field="rate"]').val(this.gachaFormatPercent(value));
    },
    gachaSetTierDisplayRateValue(tierId, value) {
      const card = $('#gachaTierGrid [data-tier-id]').filter((_, node) => String($(node).data('tier-id') || '') === String(tierId || '')).first();
      if (!card.length) return;
      card.find('[data-tier-field="displayRate"]').val(this.gachaFormatPercent(value));
    },
    gachaSetCheckboxValue(input, checked) {
      const field = $(input);
      if (!field.length) return;
      const wrapper = field.closest('.ui.checkbox');
      if (wrapper.length && typeof wrapper.checkbox === 'function') {
        wrapper.checkbox(checked ? 'set checked' : 'set unchecked');
      } else {
        field.prop('checked', checked);
      }
    },
    gachaSetTierBooleanValue(tierId, field, checked) {
      const card = $('#gachaTierGrid [data-tier-id]').filter((_, node) => String($(node).data('tier-id') || '') === String(tierId || '')).first();
      if (!card.length) return;
      this.gachaSetCheckboxValue(card.find(`[data-tier-field="${field}"]`), checked);
    },
    gachaSetRoleEntryOverrideState(roleEntry, field) {
      const enabledInput = roleEntry.find('[data-role-child-enabled]');
      const enabledBox = enabledInput.closest('.ui.checkbox');
      if (enabledBox.length) enabledBox.checkbox('set checked');
      else enabledInput.prop('checked', true);
      const toggleInput = roleEntry.find(`[data-role-child-override-toggle="${field}"]`);
      const toggleBox = toggleInput.closest('.ui.checkbox');
      if (toggleBox.length) toggleBox.checkbox('set checked');
      else toggleInput.prop('checked', true);
    },
    gachaSetPrizeInternalWeightValue(prizeId, value) {
      const normalizedValue = this.gachaFormatPercent(value);
      const itemRow = $('#gachaItemPrizeList [data-prize-id]').filter((_, node) => String($(node).data('prize-id') || '') === String(prizeId || '')).first();
      if (itemRow.length) {
        itemRow.find('[data-prize-field="internalWeight"]').val(normalizedValue);
        return;
      }
      const roleEntry = $('#gachaRolePrizeList [data-role-config-entry-id]').filter((_, node) => String($(node).data('role-config-entry-id') || '') === String(prizeId || '')).first();
      if (!roleEntry.length) return;
      this.gachaSetRoleEntryOverrideState(roleEntry, 'internalWeight');
      roleEntry.find('[data-role-child-override-value="internalWeight"]').val(normalizedValue);
      this.syncGachaRoleChildPanels(roleEntry);
      this.refreshGachaRoleEntryPreview(roleEntry);
    },
    gachaSetPrizeDisplayWeightValue(prizeId, value) {
      const normalizedValue = this.gachaFormatPercent(value);
      const itemRow = $('#gachaItemPrizeList [data-prize-id]').filter((_, node) => String($(node).data('prize-id') || '') === String(prizeId || '')).first();
      if (itemRow.length) {
        itemRow.find('[data-prize-field="displayWeight"]').val(normalizedValue);
        return;
      }
      const roleEntry = $('#gachaRolePrizeList [data-role-config-entry-id]').filter((_, node) => String($(node).data('role-config-entry-id') || '') === String(prizeId || '')).first();
      if (!roleEntry.length) return;
      this.gachaSetRoleEntryOverrideState(roleEntry, 'displayWeight');
      roleEntry.find('[data-role-child-override-value="displayWeight"]').val(normalizedValue);
      this.syncGachaRoleChildPanels(roleEntry);
      this.refreshGachaRoleEntryPreview(roleEntry);
    },
    gachaSetPrizeBooleanValue(prizeId, field, checked) {
      const itemRow = $('#gachaItemPrizeList [data-prize-id]').filter((_, node) => String($(node).data('prize-id') || '') === String(prizeId || '')).first();
      if (itemRow.length) {
        this.gachaSetCheckboxValue(itemRow.find(`[data-prize-field="${field}"]`), checked);
        return;
      }
      const roleEntry = $('#gachaRolePrizeList [data-role-config-entry-id]').filter((_, node) => String($(node).data('role-config-entry-id') || '') === String(prizeId || '')).first();
      if (!roleEntry.length) return;
      this.gachaSetRoleEntryOverrideState(roleEntry, field);
      this.gachaSetCheckboxValue(roleEntry.find(`[data-role-child-override-value="${field}"]`), checked);
      this.syncGachaRoleChildPanels(roleEntry);
      this.refreshGachaRoleEntryPreview(roleEntry);
    },
    applyGachaOverviewTierPercent(tierId, nextPercent, field = 'rate', statusField = 'active', setterName = 'gachaSetTierRateValue') {
      const tiers = this.gachaRateOverviewData()
        .filter((tier) => tier[statusField] !== false)
        .map((tier) => ({
          id: tier.id,
          percent: field === 'displayRate' ? tier.tierDisplayPercent : tier.tierInternalPercent,
        }));
      const nextMap = this.gachaRebalancePercentages(tiers, tierId, nextPercent);
      nextMap.forEach((percent, id) => this[setterName](id, percent));
      this.renderGachaRateOverview();
    },
    applyGachaOverviewPrizePercent(prizeId, tierId, nextPercent, mode = 'internal', setterName = 'gachaSetPrizeInternalWeightValue') {
      const tier = this.gachaRateOverviewData().find((entry) => String(entry.id || '') === String(tierId || ''));
      if (!tier) return;
      const prizePool = (tier.prizeRows || [])
        .filter((prize) => mode === 'display' ? prize.visible !== false : prize.active !== false)
        .map((prize) => ({
          id: prize.id,
          percent: mode === 'display' ? prize.displayWithinTierPercent : prize.internalWithinTierPercent,
        }));
      const nextMap = this.gachaRebalancePercentages(prizePool, prizeId, nextPercent);
      nextMap.forEach((percent, id) => this[setterName](id, percent));
      this.renderGachaRateOverview();
    },
    renderGachaPrizeList(type, selector) {
      const prizes = (this.state.gachaConfig?.prizes || [])
        .filter((prize) => (prize.type || 'item') === type)
        .sort((a, b) => this.gachaTierRank(a.tierId) - this.gachaTierRank(b.tierId) || Number(a.sortOrder || 0) - Number(b.sortOrder || 0) || String(a.name || '').localeCompare(String(b.name || '')));
      const tiers = this.state.gachaConfig?.tiers || [];
      const templates = this.state.gachaConfig?.templates || [];
      const tierOptions = this.gachaTierOptions(tiers);
      const roleOptions = ['<option value="">ไม่แจก Role</option>'].concat((this.state.gachaRoles || []).map((role) => `<option value="${this.escAttr(role.roleId)}">${this.esc(role.roleName)}${role.roleTier ? ` · ${this.esc(role.roleTier)}` : ''}${role.roleSeriesName ? ` · ${this.esc(role.roleSeriesName)}` : ''}</option>`)).join('');
      const durationOptions = [
        [0, 'ถาวร'], [3, '3 วัน'], [7, '7 วัน'], [14, '14 วัน'], [30, '30 วัน'], [60, '60 วัน'], [90, '90 วัน']
      ].map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
      const expireActionOptions = [
        ['ended', 'หมดเวลาแล้วให้แสดง “สิ้นสุดเวลา”'],
        ['hide', 'หมดเวลาแล้วซ่อนจากหน้าบ้าน'],
      ].map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
      const displayQuantityOptions = [
        ['none', 'ไม่แสดงจำนวน'],
        ['manual', 'แสดงจำนวนแบบกรอกเอง'],
        ['quota', 'แสดงตามโควต้าจริง'],
      ].map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
      const expanded = this.state.gachaPrizeExpanded?.[type] || '';
      const sectionLabel = type === 'item' ? 'Gachapon item prize' : 'Gachapon role prize';
      const orderedTiers = [...tiers].sort((a, b) => this.gachaTierRank(a.id) - this.gachaTierRank(b.id) || String(a.name || '').localeCompare(String(b.name || '')));

      $(selector).html(orderedTiers.length ? orderedTiers.map((tier) => {
        const groupedPrizes = prizes.filter((prize) => prize.tierId === tier.id);
        return `
        <section class="gacha-prize-tier-group" data-gacha-admin-tier="${this.escAttr(tier.id)}">
          <header class="gacha-prize-tier-group-head">
            <div>
              <strong style="color:${this.escAttr(this.gachaTierColor(tier.id))}">${this.esc(tier.name || tier.tier || tier.id)}</strong>
              <span>${this.esc(tier.tier || tier.id)} · ${groupedPrizes.length.toLocaleString()} รายการ</span>
            </div>
            <button class="ui mini basic button" style="color:${this.escAttr(this.gachaTierColor(tier.id))}; box-shadow: inset 0 0 0 1px ${this.escAttr(this.gachaTierColor(tier.id))};" type="button" data-gacha-add-prize="${this.escAttr(type)}" data-gacha-add-tier="${this.escAttr(tier.id)}">
              <i class="fa-solid fa-plus"></i> ${type === 'item' ? 'Item' : 'Role'}
            </button>
          </header>
          ${groupedPrizes.length ? groupedPrizes.map((prize) => {
        const tierLabel = this.gachaTierLabel(prize.tierId);
        const roleLabel = type === 'role' ? this.gachaRoleName(prize) : '';
        const roleMeta = type === 'role' ? this.gachaRoleMeta(prize.discordRoleId) : null;
        const pickDateLabel = this.gachaDisplayPickDate(prize.pickDate);
        const shortName = prize.shortName || prize.name || (type === 'role' ? 'New Gachapon Role Prize' : 'New Gachapon Prize');
        const displayTitle = shortName || roleLabel || (type === 'role' ? 'New Gachapon Role Prize' : 'New Gachapon Prize');
        const tierAccent = this.gachaTierColor(prize.tierId);
        const metaLine = type === 'role'
          ? [
              `Tier: ${tierLabel}`,
              roleLabel ? `Role: ${roleLabel}` : '',
              roleMeta?.roleTier ? `Role Tier: ${roleMeta.roleTier}` : '',
              roleMeta?.roleSeriesName ? `Series: ${roleMeta.roleSeriesName}` : '',
              `Duration: ${this.gachaRoleDurationPoolLabel(prize)}`,
              Number(prize.coinPrice || 0) > 0 ? `Coin: ${Number(prize.coinPrice || 0).toLocaleString()} P` : '',
              this.gachaPrizeTemplateSummary(prize),
              `End: ${pickDateLabel || 'ไม่กำหนด'}`
            ].filter(Boolean).join(' · ')
          : [
              `Tier: ${tierLabel}`,
              prize.shortName ? `Short: ${prize.shortName}` : '',
              prize.badge ? `Badge: ${prize.badge}` : '',
              Number(prize.coinPrice || 0) > 0 ? `Coin: ${Number(prize.coinPrice || 0).toLocaleString()} P` : '',
              this.gachaPrizeTemplateSummary(prize),
              `End: ${pickDateLabel || 'ไม่กำหนด'}`
            ].filter(Boolean).join(' · ');
        return `
          <article class="gacha-prize-row ${expanded === prize.id ? 'is-open' : ''}" draggable="true" data-prize-id="${this.escAttr(prize.id)}" data-prize-type="${this.escAttr(type)}" style="--gacha-tier-accent:${this.escAttr(tierAccent)};">
            <button class="gacha-prize-summary" type="button" data-gacha-toggle-prize="${this.escAttr(prize.id)}" data-gacha-prize-type="${this.escAttr(type)}">
              <span class="gacha-prize-drag" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>
              <span class="gacha-prize-thumb">${this.gachaPrizeThumbMedia(prize, type, 'thumb')}</span>
              <span class="gacha-prize-summary-caret"><i class="fa-solid fa-chevron-${expanded === prize.id ? 'down' : 'right'}"></i></span>
              <span class="gacha-prize-copy">
                <strong>${this.esc(displayTitle)}</strong>
                <span>${this.esc(metaLine || sectionLabel)}</span>
              </span>
              <span class="gacha-prize-chip-row">
                <span class="gacha-prize-chip is-dim">Int ${this.esc(String(prize.internalWeight ?? 1))}</span>
                <span class="gacha-prize-chip is-dim">Pub ${this.esc(String(prize.displayWeight ?? prize.internalWeight ?? 1))}</span>
                <span class="gacha-prize-chip ${prize.active !== false ? 'is-live' : ''}">${prize.active !== false ? 'Active' : 'Paused'}</span>
              </span>
            </button>
            <div class="gacha-prize-editor ui mini form">
              <div class="gacha-prize-editor-grid">
                <div class="gacha-prize-editor-head">
                  <div>
                    <strong>${this.esc(type === 'item' ? 'Gachapon Item Prize' : 'Gachapon Role Prize')}</strong>
                    <div class="orbit-muted">${this.esc(type === 'item' ? 'ใช้กับชั้นของรางวัลและรายละเอียดบนหน้า prizes.php' : 'ใช้กับรายการยศและผลกาชาแบบ Discord role')}</div>
                  </div>
                  <div class="gacha-prize-editor-actions">
                    <button class="ui tiny primary button" type="button" data-gacha-save-prize="${this.escAttr(prize.id)}"><i class="fa-solid fa-floppy-disk"></i> Save Prize</button>
                    <button class="ui tiny button" type="button" data-gacha-duplicate="${this.escAttr(prize.id)}"><i class="fa-solid fa-copy"></i> Duplicate</button>
                    <button class="ui tiny negative button" type="button" data-gacha-remove="${this.escAttr(prize.id)}"><i class="fa-solid fa-trash"></i> Remove</button>
                  </div>
                </div>
                <div class="gacha-prize-editor-art">
                  <div class="gacha-prize-editor-artbox">
                    ${this.gachaPrizeThumbMedia(prize, type, 'art')}
                    <label class="ui tiny button" data-tooltip="อัปโหลดรูปหลัก/ไอคอนสำรองของ prize นี้">
                      <i class="fa-solid fa-upload"></i> Upload
                      <input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-prize-upload-id="${this.escAttr(prize.id)}" data-prize-upload-field="image" hidden>
                    </label>
                  </div>
                  <div class="gacha-prize-fields">
                    <div class="gacha-prize-fieldset">
                      <div class="gacha-prize-fieldset-title">ข้อมูลหลัก</div>
                    ${type === 'item' ? `
                      <div class="four fields">
                        <div class="field"><label>${this.fieldLabel('ชื่อ Prize', 'ชื่อที่แสดงบนชั้นและ modal รายละเอียด')}</label><input data-prize-field="name" value="${this.escAttr(prize.name || '')}"></div>
                        <div class="field"><label>${this.fieldLabel('ชื่อสั้น', 'ถ้าชื่อหลักยาวเกิน จะใช้ชื่อสั้นตอนโชว์หน้ารายการ')}</label><input data-prize-field="shortName" value="${this.escAttr(prize.shortName || '')}"></div>
                        <div class="field"><label>${this.fieldLabel('Tier', 'เลือกสี/ระดับลูกกาชาที่ prize นี้อยู่')}</label><select class="ui dropdown" data-prize-field="tierId">${tierOptions}</select></div>
                        <div class="field"><label>${this.fieldLabel('Badge', 'ป้ายสั้นๆ เช่น Daily, Limited, Rate Up แสดงเหนือไอเทม')}</label><input data-prize-field="badge" value="${this.escAttr(prize.badge || '')}"></div>
                      </div>
                    ` : `
                      <div class="four fields">
                        <div class="field"><label>${this.fieldLabel('ชื่อ Prize', 'ชื่อรางวัลยศที่แสดงในรายการและ modal')} <button class="ui mini icon button gacha-prize-inline-tool" type="button" data-gacha-insert-role-token="${this.escAttr(prize.id)}" data-tooltip="ใส่ <roleName> ลงในช่องชื่อ"><i class="fa-solid fa-code"></i></button></label><input data-prize-field="name" value="${this.escAttr(prize.name || '')}"></div>
                        <div class="field"><label>${this.fieldLabel('ชื่อสั้น', 'ชื่อที่ย่อสำหรับหน้ารายการ ถ้าไม่กรอกจะใช้ชื่อหลัก')}</label><input data-prize-field="shortName" value="${this.escAttr(prize.shortName || '')}"></div>
                        <div class="field"><label>${this.fieldLabel('Tier', 'เลือกสี/ระดับลูกกาชาที่ role prize นี้อยู่')}</label><select class="ui dropdown" data-prize-field="tierId">${tierOptions}</select></div>
                        <div class="field"><label>${this.fieldLabel('Discord Role', 'Role จริงใน Discord ที่จะแจกเมื่อออก prize นี้')}</label><select class="ui dropdown" data-prize-field="discordRoleId">${roleOptions}</select></div>
                      </div>
                      ${roleMeta ? `<div class="orbit-muted">Tier ยศ: ${this.esc(roleMeta.roleTier || '-')} · Series: ${this.esc(roleMeta.roleSeriesName || '-')}</div>` : ''}
                      <div class="two fields">
                        <div class="field"><label>${this.fieldLabel('Badge', 'ป้ายสั้นๆ เช่น Limited หรือ Event')}</label><input data-prize-field="badge" value="${this.escAttr(prize.badge || '')}"></div>
                      </div>
                    `}
                    </div>
                    <div class="gacha-prize-fieldset">
                      <div class="gacha-prize-fieldset-title">เรทและจำนวน</div>
                    <div class="four fields">
                      <div class="field"><label>${this.fieldLabel('Internal Weight', 'น้ำหนักสุ่มจริงภายใน tier นี้ ปิด Active แล้วจะไม่ถูกสุ่ม')}</label><input type="number" step="0.01" min="0" data-prize-field="internalWeight" value="${this.escAttr(prize.internalWeight ?? 1)}"></div>
                      <div class="field"><label>${this.fieldLabel('Public Weight', 'น้ำหนักที่แสดงหน้าบ้าน แยกจากน้ำหนักจริงได้')}</label><input type="number" step="0.01" min="0" data-prize-field="displayWeight" value="${this.escAttr(prize.displayWeight ?? prize.internalWeight ?? 1)}"></div>
                      ${type === 'role'
                        ? `<div class="field"><label>${this.fieldLabel('Role Duration', 'ระยะเวลาที่จะแจก role นี้ 0 คือถาวร')}</label><select class="ui dropdown" data-prize-field="roleDurationDays">${durationOptions}</select></div>`
                        : `<div class="field"><label>${this.fieldLabel('Display Quantity', 'เลือกวิธีแสดงจำนวนคงเหลือบนหน้ารางวัล')}</label><select class="ui dropdown" data-prize-field="displayQuantityMode">${displayQuantityOptions}</select></div>`}
                      <div class="field"><label>${this.fieldLabel(type === 'role' ? 'Display Quantity' : 'Quantity Number', type === 'role' ? 'เลือกวิธีแสดงจำนวนคงเหลือบนหน้ารางวัล' : 'จำนวนที่โชว์เมื่อเลือกแสดงแบบกรอกเอง')}</label>${type === 'role' ? `<select class="ui dropdown" data-prize-field="displayQuantityMode">${displayQuantityOptions}</select>` : `<input type="number" min="0" step="1" data-prize-field="displayQuantity" value="${this.escAttr(prize.displayQuantity ?? 0)}">`}</div>
                    </div>
                    ${type === 'role' ? `<div class="two fields"><div class="field"><label>${this.fieldLabel('Quantity Number', 'จำนวนที่โชว์เมื่อเลือกแสดงแบบกรอกเอง')}</label><input type="number" min="0" step="1" data-prize-field="displayQuantity" value="${this.escAttr(prize.displayQuantity ?? 0)}"></div></div>` : ''}
                    <div class="gacha-prize-optional-row">
                      <label class="ui checkbox"><input type="checkbox" data-gacha-optional-field="coinPrice" ${Number(prize.coinPrice || 0) > 0 ? 'checked' : ''}><span>ราคาเหรียญ</span></label>
                      <div class="field" data-gacha-optional-panel="coinPrice"><label>${this.fieldLabel('Coin Price', 'ราคาเหรียญที่ใช้แสดงบนหน้าลิสต์/รายละเอียด')}</label><input type="number" min="0" step="1" data-prize-field="coinPrice" value="${this.escAttr(prize.coinPrice ?? 0)}"></div>
                    </div>
                    </div>
                    <div class="gacha-prize-fieldset">
                      <div class="gacha-prize-fieldset-title">เวลาและไอคอน</div>
                      <div class="gacha-prize-optional-row">
                        <label class="ui checkbox"><input type="checkbox" data-gacha-optional-field="pickDate" ${String(prize.pickDate || '').trim() ? 'checked' : ''}><span>วันสิ้นสุด / เคาน์ดาวน์</span></label>
                        <div class="two fields" data-gacha-optional-panel="pickDate">
                          <div class="field"><label>${this.fieldLabel('Pick Date', 'วันเวลาสิ้นสุดสำหรับเคาน์ดาวน์บนชั้นรางวัล')}</label><input type="datetime-local" data-prize-field="pickDate" value="${this.escAttr(this.gachaPickDateInputValue(prize.pickDate))}"></div>
                          <div class="field"><label>${this.fieldLabel('After Countdown Ends', 'หมดเวลาแล้วจะซ่อน หรือโชว์ว่า สิ้นสุดเวลา')}</label><select class="ui dropdown" data-prize-field="expireAction">${expireActionOptions}</select></div>
                        </div>
                      </div>
                      <div class="two fields">
                        ${this.gachaAssetPicker(prize, 'image', type === 'role' ? 'ไอคอนสำรอง' : 'รูป Prize', type === 'role' ? 'ใช้เมื่อ role ไม่มี icon จาก Discord' : 'รูปหลักที่โชว์ในรายการและรายละเอียด')}
                        <div class="field gacha-prize-upload-field"><label>&nbsp;</label><label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload image<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-prize-upload-id="${this.escAttr(prize.id)}" data-prize-upload-field="image" hidden></label></div>
                      </div>
                      <div class="gacha-prize-optional-row">
                        <label class="ui checkbox"><input type="checkbox" data-gacha-optional-field="statusIcon" ${String(prize.statusIcon || '').trim() ? 'checked' : ''}><span>ไอคอนสถานะ</span></label>
                        <div class="two fields" data-gacha-optional-panel="statusIcon">
                          ${this.gachaAssetPicker(prize, 'statusIcon', 'Status Icon', 'ไอคอนเล็กที่วางบนมุมรางวัล')}
                          <div class="field gacha-prize-upload-field"><label>&nbsp;</label><label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload icon<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-prize-upload-id="${this.escAttr(prize.id)}" data-prize-upload-field="statusIcon" hidden></label></div>
                        </div>
                      </div>
                    </div>
                    <div class="gacha-prize-fieldset">
                      <div class="gacha-prize-fieldset-title">รายละเอียดและเงื่อนไข</div>
                      <div class="two fields">
                        <div class="field"><label>${this.fieldLabel('Detail', 'เลือกใช้เทมเพลต หรือเลือกเขียนข้อความเฉพาะ prize นี้')}</label><select class="ui dropdown" data-prize-text-mode="detail">${this.gachaTemplateModeOptions('detail', templates)}</select></div>
                        <div class="field"><label>${this.fieldLabel('Condition', 'เลือกใช้เทมเพลต หรือเลือกเขียนเงื่อนไขเฉพาะ prize นี้')}</label><select class="ui dropdown" data-prize-text-mode="condition">${this.gachaTemplateModeOptions('condition', templates)}</select></div>
                      </div>
                      <div class="field" data-gacha-text-panel="detail"><label>${this.fieldLabel('Detail Override', 'ข้อความรายละเอียดเฉพาะของรางวัลนี้')}</label><textarea rows="3" data-prize-text-field="detail">${this.esc(prize.detailText || prize.description || '')}</textarea></div>
                      <div class="field" data-gacha-text-panel="condition"><label>${this.fieldLabel('Condition Override', 'เงื่อนไขเฉพาะของรางวัลนี้')}</label><textarea rows="3" data-prize-text-field="condition">${this.esc(prize.conditionText || '')}</textarea></div>
                    </div>
                    <div class="gacha-prize-editor-actions">
                      <label class="ui checkbox"><input type="checkbox" data-prize-field="active" ${prize.active !== false ? 'checked' : ''}><span>Active / ออกได้จริง</span></label>
                      <label class="ui checkbox"><input type="checkbox" data-prize-field="visible" ${prize.visible !== false ? 'checked' : ''}><span>Visible / โชว์หน้าบ้าน</span></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </article>
        `;
      }).join('') : `<div class="gacha-prize-empty">ยังไม่มี ${this.esc(type === 'item' ? 'Item Prize' : 'Role Prize')} ในสีนี้</div>`}</section>`;
      }).join('') : `<div class="gacha-prize-empty">${this.esc(type === 'item' ? this.t('empty.gacha_item_prize') : this.t('empty.gacha_role_prize'))}</div>`);

      prizes.forEach((prize) => {
        const row = $(`${selector} [data-prize-id="${prize.id}"]`);
        row.find('[data-prize-field="tierId"]').val(prize.tierId || tiers[0]?.id || 'common');
        row.find('[data-prize-field="discordRoleId"]').val(prize.discordRoleId || '');
        row.find('[data-prize-field="roleDurationDays"]').val(String(prize.roleDurationDays ?? 0));
        row.find('[data-prize-field="coinPrice"]').val(prize.coinPrice ?? 0);
        row.find('[data-prize-field="image"]').val(prize.image || '');
        row.find('[data-prize-field="statusIcon"]').val(prize.statusIcon || '');
        row.find('[data-prize-field="expireAction"]').val(prize.expireAction || 'ended');
        row.find('[data-prize-field="displayQuantityMode"]').val(prize.displayQuantityMode || 'none');
        row.find('[data-prize-text-mode="detail"]').val(this.gachaTemplateModeValue(prize, 'detail'));
        row.find('[data-prize-text-mode="condition"]').val(this.gachaTemplateModeValue(prize, 'condition'));
      });
      this.initGachaPrizeScope(selector);
    },
    renderGachaRoleConfigList(selector) {
      const prizes = (this.state.gachaConfig?.prizes || [])
        .filter((prize) => (prize.type || 'item') === 'role')
        .sort((a, b) => this.gachaTierRank(a.tierId) - this.gachaTierRank(b.tierId) || Number(a.sortOrder || 0) - Number(b.sortOrder || 0) || String(a.name || '').localeCompare(String(b.name || '')));
      const tiers = this.state.gachaConfig?.tiers || [];
      const orderedTiers = [...tiers].sort((a, b) => this.gachaTierRank(a.id) - this.gachaTierRank(b.id) || String(a.name || '').localeCompare(String(b.name || '')));
      const tierOptions = this.gachaTierOptions(tiers);
      const durationOptions = [
        [0, 'ถาวร'], [3, '3 วัน'], [7, '7 วัน'], [14, '14 วัน'], [30, '30 วัน'], [60, '60 วัน'], [90, '90 วัน']
      ].map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
      const expireActionOptions = [
        ['ended', 'หมดเวลาแล้วให้แสดง “สิ้นสุดเวลา”'],
        ['hide', 'หมดเวลาแล้วซ่อนจากหน้าบ้าน'],
      ].map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
      const displayQuantityOptions = [
        ['none', 'ไม่แสดงจำนวน'],
        ['manual', 'แสดงจำนวนแบบกรอกเอง'],
        ['quota', 'แสดงตามโควต้าจริง'],
      ].map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
      const groups = this.gachaRoleConfigGroups(prizes);
      const expanded = this.state.gachaPrizeExpanded?.role || '';

      $(selector).html(orderedTiers.length ? orderedTiers.map((tier) => {
        const tierGroups = groups.filter((group) => group.tierId === tier.id);
        return `
        <section class="gacha-prize-tier-group" data-gacha-admin-tier="${this.escAttr(tier.id)}">
          <header class="gacha-prize-tier-group-head">
            <div>
              <strong style="color:${this.escAttr(this.gachaTierColor(tier.id))}">${this.esc(tier.name || tier.tier || tier.id)}</strong>
              <span>${this.esc(tier.tier || tier.id)} · ${tierGroups.length.toLocaleString()} Role Config</span>
            </div>
            <button class="ui mini basic button" style="color:${this.escAttr(this.gachaTierColor(tier.id))}; box-shadow: inset 0 0 0 1px ${this.escAttr(this.gachaTierColor(tier.id))};" type="button" data-gacha-add-role-config="${this.escAttr(tier.id)}">
              <i class="fa-solid fa-plus"></i> Role Config
            </button>
          </header>
          ${tierGroups.length ? tierGroups.map((group) => {
            const shared = group.sharedPrize;
            const sharedPreviewPrize = String(shared.image || '').trim() ? shared : (group.prizes[0] || shared);
            const selectedRoleIds = this.gachaRoleConfigSelectedRoleIds(group.prizes);
            const previewPrizes = group.prizes.filter((prize) => String(prize.discordRoleId || '').trim());
            const roleNames = group.prizes.map((prize) => this.gachaRoleName(prize) || 'ยังไม่เลือก Role').filter(Boolean);
            const tierAccent = this.gachaTierColor(group.tierId);
            const metaLine = [
              `Tier: ${this.gachaTierLabel(group.tierId)}`,
              `${(selectedRoleIds.length || 0).toLocaleString()} Roles`,
              this.gachaPrizeTemplateSummary(shared),
              Number(shared.coinPrice || 0) > 0 ? `Coin: ${Number(shared.coinPrice || 0).toLocaleString()} P` : '',
              roleNames.length ? roleNames.join(', ') : 'ยังไม่เลือก Role',
            ].filter(Boolean).join(' · ');
            return `
            <article class="gacha-prize-row gacha-role-config-row ${expanded === group.id ? 'is-open' : ''}" draggable="true" data-role-config-id="${this.escAttr(group.id)}" data-role-config-tier="${this.escAttr(group.tierId)}" style="--gacha-tier-accent:${this.escAttr(tierAccent)};">
              <button class="gacha-prize-summary" type="button" data-gacha-toggle-prize="${this.escAttr(group.id)}" data-gacha-prize-type="role">
                <span class="gacha-prize-drag" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>
                <span class="gacha-prize-thumb" data-role-config-preview-thumb>${this.gachaPrizeThumbMedia(sharedPreviewPrize, 'role', 'thumb')}</span>
                <span class="gacha-prize-summary-caret"><i class="fa-solid fa-chevron-${expanded === group.id ? 'down' : 'right'}"></i></span>
                <span class="gacha-prize-copy">
                  <strong>${this.esc(`Role Config · ${selectedRoleIds.length} roles`)}</strong>
                  <span>${this.esc(metaLine || 'Shared config')}</span>
                </span>
                <span class="gacha-prize-chip-row">
                  <span class="gacha-prize-chip is-dim">Int ${this.esc(String(shared.internalWeight ?? 1))}</span>
                  <span class="gacha-prize-chip is-dim">Pub ${this.esc(String(shared.displayWeight ?? shared.internalWeight ?? 1))}</span>
                  <span class="gacha-prize-chip ${shared.active !== false ? 'is-live' : ''}">${shared.active !== false ? 'Active' : 'Paused'}</span>
                </span>
              </button>
              <div class="gacha-role-config-preview-list" ${expanded === group.id ? 'hidden' : ''}>
                ${previewPrizes.length ? previewPrizes.map((prize) => this.renderGachaRoleConfigPreviewRow(prize)).join('') : '<div class="gacha-prize-empty">เลือก Discord Roles ด้านใน config นี้ก่อน แล้วระบบจะแตกเป็นรายการย่อยให้ทันที</div>'}
              </div>
              <div class="gacha-prize-editor ui mini form">
                <div class="gacha-prize-editor-grid">
                  <div class="gacha-prize-editor-head">
                    <div>
                      <strong>Gachapon Role Config</strong>
                      <div class="orbit-muted">ค่าระดับกลุ่มจะใช้กับทุก role ใน config นี้ และ role ย่อยสามารถเปิด override เฉพาะบางค่าได้</div>
                    </div>
                    <div class="gacha-prize-editor-actions">
                      <button class="ui tiny primary button" type="button" data-gacha-save-role-config="${this.escAttr(group.id)}"><i class="fa-solid fa-floppy-disk"></i> Save Config</button>
                      <button class="ui tiny button" type="button" data-gacha-add-role-entry="${this.escAttr(group.id)}"><i class="fa-solid fa-plus"></i> Roles</button>
                      <button class="ui tiny negative button" type="button" data-gacha-remove-role-config="${this.escAttr(group.id)}"><i class="fa-solid fa-trash"></i> Remove Config</button>
                    </div>
                  </div>
                  <div class="gacha-prize-editor-art">
                    <div class="gacha-prize-editor-artbox">
                      <div data-role-config-preview-art>${this.gachaPrizeThumbMedia(sharedPreviewPrize, 'role', 'art')}</div>
                      <div class="orbit-muted">${group.prizes.length.toLocaleString()} roles share this config</div>
                    </div>
                    <div class="gacha-prize-fields">
                      <div class="gacha-prize-fieldset">
                        <div class="gacha-prize-fieldset-title">คอนฟิกระดับทั้งกลุ่ม</div>
                        <div class="three fields">
                          <div class="field"><label>${this.fieldLabel('Tier', 'เลือกสี/ระดับลูกกาชาที่ role config นี้อยู่')}</label><select class="ui dropdown" data-role-config-field="tierId">${tierOptions}</select></div>
                          <div class="field"><label>${this.fieldLabel('Badge', 'ป้ายสั้นๆที่ใช้กับ role ทั้งกลุ่มนี้')}</label><input data-role-config-field="badge" value="${this.escAttr(shared.badge || '')}"></div>
                          <div class="field"><label>${this.fieldLabel('Role Duration', 'อายุยศของ role config นี้')}</label><select class="ui dropdown" data-role-config-field="roleDurationDays">${durationOptions}</select></div>
                        </div>
                        <div class="field">
                          <label>${this.fieldLabel('Discord Roles', 'เลือกได้หลาย role ในครั้งเดียว ระบบจะแตกเป็นรายการย่อยให้ทันที และยังลากเรียงลำดับรายการย่อยได้เหมือนเดิม')}</label>
                          <select class="ui fluid search dropdown" multiple data-role-config-selected-roles>
                            ${this.gachaRoleConfigRoleOptions(selectedRoleIds)}
                          </select>
                        </div>
                        <div class="gacha-prize-editor-actions">
                          <label class="ui checkbox"><input type="checkbox" data-role-config-field="active" ${shared.active !== false ? 'checked' : ''}><span>Active / ออกได้จริง</span></label>
                          <label class="ui checkbox"><input type="checkbox" data-role-config-field="visible" ${shared.visible !== false ? 'checked' : ''}><span>Visible / โชว์หน้าบ้าน</span></label>
                        </div>
                        <div class="four fields">
                          <div class="field"><label>${this.fieldLabel('Internal Weight', 'น้ำหนักสุ่มจริงภายใน tier นี้')}</label><input type="number" step="0.01" min="0" data-role-config-field="internalWeight" value="${this.escAttr(shared.internalWeight ?? 1)}"></div>
                          <div class="field"><label>${this.fieldLabel('Public Weight', 'น้ำหนักที่แสดงหน้าบ้าน')}</label><input type="number" step="0.01" min="0" data-role-config-field="displayWeight" value="${this.escAttr(shared.displayWeight ?? shared.internalWeight ?? 1)}"></div>
                          <div class="field"><label>${this.fieldLabel('Display Quantity', 'เลือกวิธีแสดงจำนวนคงเหลือบนหน้ารางวัล')}</label><select class="ui dropdown" data-role-config-field="displayQuantityMode">${displayQuantityOptions}</select></div>
                          <div class="field"><label>${this.fieldLabel('Quantity Number', 'จำนวนที่โชว์เมื่อเลือกแสดงแบบกรอกเอง')}</label><input type="number" min="0" step="1" data-role-config-field="displayQuantity" value="${this.escAttr(shared.displayQuantity ?? 0)}"></div>
                        </div>
                        <div class="gacha-prize-optional-row">
                          <label class="ui checkbox"><input type="checkbox" data-gacha-optional-field="coinPrice" ${Number(shared.coinPrice || 0) > 0 ? 'checked' : ''}><span>ราคาเหรียญ</span></label>
                          <div class="field" data-gacha-optional-panel="coinPrice"><label>${this.fieldLabel('Coin Price', 'ราคาเหรียญที่ใช้แสดงบนหน้าลิสต์/รายละเอียด')}</label><input type="number" min="0" step="1" data-role-config-field="coinPrice" value="${this.escAttr(shared.coinPrice ?? 0)}"></div>
                        </div>
                        <div class="gacha-prize-optional-row">
                          <label class="ui checkbox"><input type="checkbox" data-gacha-optional-field="pickDate" ${String(shared.pickDate || '').trim() ? 'checked' : ''}><span>วันสิ้นสุด / เคาน์ดาวน์</span></label>
                          <div class="two fields" data-gacha-optional-panel="pickDate">
                            <div class="field"><label>${this.fieldLabel('Pick Date', 'วันเวลาสิ้นสุดสำหรับเคาน์ดาวน์บนชั้นรางวัล')}</label><input type="datetime-local" data-role-config-field="pickDate" value="${this.escAttr(this.gachaPickDateInputValue(shared.pickDate))}"></div>
                            <div class="field"><label>${this.fieldLabel('After Countdown Ends', 'หมดเวลาแล้วจะซ่อน หรือโชว์ว่า สิ้นสุดเวลา')}</label><select class="ui dropdown" data-role-config-field="expireAction">${expireActionOptions}</select></div>
                          </div>
                        </div>
                        <div class="gacha-prize-optional-row">
                          <label class="ui checkbox"><input type="checkbox" data-gacha-optional-field="image" ${String(shared.image || '').trim() ? 'checked' : ''}><span>ไอคอนหลักของ Config</span></label>
                          <div class="two fields" data-gacha-optional-panel="image">
                            ${this.gachaAssetPicker(shared, 'image', 'ไอคอนหลัก', 'รูปหลักของ Role Config นี้ และเป็นค่าเริ่มต้นเมื่อ role ย่อยไม่ได้ override ไอคอน', 'data-role-config-field')}
                            <div class="field gacha-prize-upload-field"><label>&nbsp;</label><label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload image<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-gacha-role-config-upload-group="${this.escAttr(group.id)}" data-gacha-role-config-upload-field="image" hidden></label></div>
                          </div>
                        </div>
                        <div class="gacha-prize-optional-row">
                          <label class="ui checkbox"><input type="checkbox" data-gacha-optional-field="statusIcon" ${String(shared.statusIcon || '').trim() ? 'checked' : ''}><span>ไอคอนสถานะ</span></label>
                          <div class="two fields" data-gacha-optional-panel="statusIcon">
                            ${this.gachaAssetPicker(shared, 'statusIcon', 'Status Icon', 'ไอคอนเล็กที่วางบนมุมรางวัล', 'data-role-config-field')}
                            <div class="field gacha-prize-upload-field"><label>&nbsp;</label><label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload icon<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-gacha-role-config-upload-group="${this.escAttr(group.id)}" data-gacha-role-config-upload-field="statusIcon" hidden></label></div>
                          </div>
                        </div>
                        <div class="two fields">
                          <div class="field"><label>${this.fieldLabel('Detail', 'เลือกใช้เทมเพลต หรือเลือกเขียนข้อความเฉพาะ config นี้')}</label><select class="ui dropdown" data-prize-text-mode="detail">${this.gachaTemplateModeOptions('detail', this.state.gachaConfig?.templates || [])}</select></div>
                          <div class="field"><label>${this.fieldLabel('Condition', 'เลือกใช้เทมเพลต หรือเลือกเขียนเงื่อนไขเฉพาะ config นี้')}</label><select class="ui dropdown" data-prize-text-mode="condition">${this.gachaTemplateModeOptions('condition', this.state.gachaConfig?.templates || [])}</select></div>
                        </div>
                        <div class="field" data-gacha-text-panel="detail"><label>${this.fieldLabel('Detail Override', 'ข้อความรายละเอียดเฉพาะของ config นี้')}</label><textarea rows="3" data-prize-text-field="detail">${this.esc(shared.detailText || shared.description || '')}</textarea></div>
                        <div class="field" data-gacha-text-panel="condition"><label>${this.fieldLabel('Condition Override', 'เงื่อนไขเฉพาะของ config นี้')}</label><textarea rows="3" data-prize-text-field="condition">${this.esc(shared.conditionText || '')}</textarea></div>
                      </div>
                      <div class="gacha-prize-fieldset">
                        <div class="gacha-prize-fieldset-title">Role ย่อยใน Config นี้</div>
                        <div class="gacha-role-config-entry-list">
                          ${group.prizes.map((prize) => this.renderGachaRoleConfigEntry(group.id, prize, displayQuantityOptions)).join('')}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </article>`;
          }).join('') : `<div class="gacha-prize-empty">ยังไม่มี Role Config ในสีนี้</div>`}
        </section>`;
      }).join('') : `<div class="gacha-prize-empty">${this.esc(this.t('empty.gacha_role_prize'))}</div>`);

      groups.forEach((group) => {
        const row = $(`${selector} [data-role-config-id="${group.id}"]`);
        const shared = group.sharedPrize;
        const selectedRoleIds = this.gachaRoleConfigSelectedRoleIds(group.prizes);
        row.find('[data-role-config-field="tierId"]').val(group.tierId || tiers[0]?.id || 'common');
        row.find('[data-role-config-field="roleDurationDays"]').val(String(shared.roleDurationDays ?? 0));
        row.find('[data-role-config-field="image"]').val(shared.image || '');
        row.find('[data-role-config-field="statusIcon"]').val(shared.statusIcon || '');
        row.find('[data-role-config-field="expireAction"]').val(shared.expireAction || 'ended');
        row.find('[data-role-config-field="displayQuantityMode"]').val(shared.displayQuantityMode || 'none');
        row.find('[data-prize-text-mode="detail"]').val(this.gachaTemplateModeValue(shared, 'detail'));
        row.find('[data-prize-text-mode="condition"]').val(this.gachaTemplateModeValue(shared, 'condition'));
        row.find('[data-role-config-selected-roles]').val(selectedRoleIds);
        group.prizes.forEach((prize) => {
          const entry = row.find(`[data-role-config-entry-id="${prize.id}"]`);
          const child = this.gachaRoleChildConfig(prize, shared);
          entry.find('[data-role-config-entry-field="discordRoleId"]').val(prize.discordRoleId || '');
          entry.find('[data-role-entry-role-label]').val(this.gachaRoleConfigRoleLabel(prize.discordRoleId || ''));
          entry.find('[data-role-config-entry-field="image"]').val(prize.roleImageOverride || '');
          entry.find('[data-role-child-override-value="displayQuantityMode"]').val(child.overrides.displayQuantityMode?.value || 'none');
        });
      });
      this.initGachaPrizeScope(selector);
      groups.forEach((group) => {
        const row = $(`${selector} [data-role-config-id="${group.id}"]`);
        row.find('[data-role-config-selected-roles]').dropdown('set exactly', this.gachaRoleConfigSelectedRoleIds(group.prizes));
      });
      this.syncGachaRoleChildPanels(selector);
      this.syncGachaRoleEntryBodies(selector);
    },
    renderGachaRoleConfigPreviewRow(prize) {
      const actualRoleName = this.gachaDisplayDash(this.gachaRoleName(prize));
      const resolvedName = this.gachaDisplayDash(this.gachaPrizeResolvedName(prize, 'role', false));
      const resolvedShortName = this.gachaDisplayDash(String(prize.shortName || '').trim());
      const templateSummary = this.gachaDisplayDash(this.gachaPrizeTemplateSummary(prize));
      return `
        <div class="gacha-role-config-preview-row">
          <span class="gacha-role-config-preview-thumb">${this.gachaPrizeThumbMedia(prize, 'role', 'thumb')}</span>
          <span class="gacha-role-config-preview-copy">
            <strong>${this.esc(actualRoleName)}</strong>
            <span>${this.esc([
              `Prize: ${resolvedName}`,
              `Short: ${resolvedShortName}`,
              `Duration: ${this.gachaRoleDurationPoolLabel(prize)}`,
              templateSummary === '-' ? 'Template: -' : templateSummary,
            ].join(' · '))}</span>
          </span>
          <span class="gacha-role-config-preview-meta">
            <span class="gacha-prize-chip is-dim">Int ${this.esc(String(prize.internalWeight ?? 1))}</span>
            <span class="gacha-prize-chip is-dim">Pub ${this.esc(String(prize.displayWeight ?? prize.internalWeight ?? 1))}</span>
            <span class="gacha-prize-chip ${prize.active !== false ? 'is-live' : ''}">${prize.active !== false ? 'ออกได้จริง' : 'ไม่ออก'}</span>
            <span class="gacha-prize-chip ${prize.visible !== false ? 'is-live' : ''}">${prize.visible !== false ? 'โชว์หน้าบ้าน' : 'ซ่อนหน้าบ้าน'}</span>
          </span>
        </div>`;
    },
    renderGachaRoleConfigEntry(groupId, prize, displayQuantityOptions) {
      const shared = this.gachaRoleGroupConfig(prize);
      const child = this.gachaRoleChildConfig(prize, shared);
      const iconPickerPrize = { ...prize, image: String(prize.roleImageOverride || '').trim() };
      const roleMeta = this.gachaRoleMeta(prize.discordRoleId);
      const actualRoleName = this.gachaDisplayDash(this.gachaRoleName(prize));
      const resolvedName = this.gachaPrizeResolvedName(prize, 'role', false);
      const resolvedShortName = this.gachaPrizeResolvedName(prize, 'role', true);
      const roleLabel = this.gachaRoleConfigRoleLabel(prize.discordRoleId || '');
      const roleMetaLine = [
        roleMeta?.roleTier ? `Tier ยศ: ${roleMeta.roleTier}` : '',
        roleMeta?.roleSeriesName ? `Series: ${roleMeta.roleSeriesName}` : '',
        prize.discordRoleId ? `ID: ${prize.discordRoleId}` : '',
        this.gachaRoleChildOverrideSummary(child),
      ].filter(Boolean).join(' · ');
      const templateSummary = this.gachaDisplayDash(this.gachaPrizeTemplateSummary(prize));
      const isOpen = Boolean(this.state.gachaRoleEntryExpanded?.[prize.id]);
      const tierAccent = this.gachaTierColor(prize.tierId || shared.tierId);
      return `
        <article class="gacha-role-config-entry ${isOpen ? 'is-open' : ''}" draggable="true" data-role-config-id="${this.escAttr(groupId)}" data-role-config-entry-id="${this.escAttr(prize.id)}" style="--gacha-tier-accent:${this.escAttr(tierAccent)};">
          <div class="gacha-role-config-entry-head">
            <button class="gacha-role-config-entry-toggle" type="button" data-gacha-toggle-role-entry="${this.escAttr(prize.id)}">
              <span class="gacha-prize-drag" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>
              <span class="gacha-prize-thumb gacha-role-config-entry-thumb" data-role-entry-preview-thumb>${this.gachaPrizeThumbMedia(prize, 'role', 'thumb')}</span>
              <span class="gacha-role-config-entry-caret" data-role-entry-preview-caret><i class="fa-solid fa-chevron-${isOpen ? 'down' : 'right'}"></i></span>
              <span class="gacha-role-config-entry-copy">
                <strong data-role-entry-preview-name>${this.esc(actualRoleName)}</strong>
                <span data-role-entry-preview-summary>${this.esc(roleMetaLine || '-')}</span>
              </span>
            </button>
            <button class="ui mini icon button" type="button" data-gacha-remove-role-entry="${this.escAttr(prize.id)}" data-tooltip="ลบ role นี้ออกจาก config"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <div class="gacha-role-config-entry-preview" data-role-config-entry-preview ${isOpen ? 'hidden' : ''}>
            <div class="gacha-role-config-entry-preview-grid">
              <div class="gacha-role-config-entry-preview-item">
                <strong>Prize</strong>
                <span data-role-entry-preview-field="prizeName">${this.esc(this.gachaDisplayDash(resolvedName))}</span>
              </div>
              <div class="gacha-role-config-entry-preview-item">
                <strong>ชื่อสั้น</strong>
                <span data-role-entry-preview-field="shortName">${this.esc(this.gachaDisplayDash(String(prize.shortName || '').trim()))}</span>
              </div>
              <div class="gacha-role-config-entry-preview-item">
                <strong>ระยะเวลา</strong>
                <span data-role-entry-preview-field="duration">${this.esc(this.gachaRoleDurationPoolLabel(prize))}</span>
              </div>
              <div class="gacha-role-config-entry-preview-item">
                <strong>Template</strong>
                <span data-role-entry-preview-field="template">${this.esc(templateSummary)}</span>
              </div>
            </div>
            <div class="gacha-role-config-entry-preview-chips">
              <span class="gacha-prize-chip is-dim" data-role-entry-preview-chip="internalWeight">Int ${this.esc(String(prize.internalWeight ?? 1))}</span>
              <span class="gacha-prize-chip is-dim" data-role-entry-preview-chip="displayWeight">Pub ${this.esc(String(prize.displayWeight ?? prize.internalWeight ?? 1))}</span>
              <span class="gacha-prize-chip ${prize.active !== false ? 'is-live' : ''}" data-role-entry-preview-chip="active">${prize.active !== false ? 'ออกได้จริง' : 'ไม่ออก'}</span>
              <span class="gacha-prize-chip ${prize.visible !== false ? 'is-live' : ''}" data-role-entry-preview-chip="visible">${prize.visible !== false ? 'โชว์หน้าบ้าน' : 'ซ่อนหน้าบ้าน'}</span>
            </div>
          </div>
          <div class="gacha-role-config-entry-body" data-role-config-entry-body ${isOpen ? '' : 'hidden'}>
            <div class="four fields">
              <div class="field">
                <label>${this.fieldLabel('Discord Role', 'กำหนดจาก multi select ระดับ config เพื่อให้เลือกหลาย role ได้ครั้งเดียว')}</label>
                <input data-role-entry-role-label value="${this.escAttr(roleLabel)}" readonly>
                <input type="hidden" data-role-config-entry-field="discordRoleId" value="${this.escAttr(prize.discordRoleId || '')}">
              </div>
              <div class="field">
                <label>${this.fieldLabel('ชื่อ Prize', 'ชื่อที่โชว์บนหน้ารางวัลและ modal')} <button class="ui mini icon button gacha-prize-inline-tool" type="button" data-gacha-insert-role-token="${this.escAttr(prize.id)}" data-tooltip="ใส่ <roleName> ลงในช่องชื่อ"><i class="fa-solid fa-code"></i></button></label>
                <input data-role-config-entry-field="name" value="${this.escAttr(prize.name || '')}">
              </div>
              <div class="field">
                <label>${this.fieldLabel('ชื่อสั้น', 'ถ้าไม่กรอกจะใช้ชื่อหลัก')}</label>
                <input data-role-config-entry-field="shortName" value="${this.escAttr(prize.shortName || '')}">
              </div>
              ${this.gachaAssetPicker(iconPickerPrize, 'image', 'ไอคอนสำรอง', 'ใช้รูปอัปโหลดก่อน ถ้าไม่มีจะ fallback ไป icon หลักของ config, icon ของ Discord และสุดท้ายใช้ icon กลาง', 'data-role-config-entry-field')}
            </div>
            <div class="two fields">
              <div class="field">
                <label>${this.fieldLabel('ชื่อ Role จริง', 'ไว้เช็กให้ชัดว่า row นี้ผูกกับ role ไหนใน Discord')}</label>
                <div class="orbit-inline-summary-item"><strong>${this.esc(actualRoleName)}</strong><span>${this.esc(roleMetaLine || '-')}</span></div>
              </div>
              <div class="field gacha-prize-upload-field">
                <label>&nbsp;</label>
                <label class="ui tiny button"><i class="fa-solid fa-upload"></i> Upload icon<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-prize-upload-id="${this.escAttr(prize.id)}" data-prize-upload-field="image" hidden></label>
              </div>
            </div>
            <div class="gacha-role-config-entry-section">
              <div class="gacha-prize-fieldset-title">ข้อมูลหลักของ role นี้</div>
              <label class="ui checkbox"><input type="checkbox" data-role-child-enabled ${child.enabled ? 'checked' : ''}><span>เปิดโหมดใช้รายละเอียดชั้นย่อยของ Role นี้</span></label>
              <div class="gacha-role-child-panel" data-role-child-panel="enabled">
                <div class="orbit-muted gacha-role-child-hint">เปิด Override เฉพาะค่าที่อยากให้ role นี้ใช้ต่างจากค่ากลุ่ม แล้วช่องตั้งค่าของค่านั้นจะโผล่ด้านล่างทันที</div>
                <div class="two fields gacha-role-child-status-grid">
                  <div class="field gacha-role-child-status-field">
                    <label class="ui checkbox"><input type="checkbox" data-role-child-override-toggle="active" ${child.overrides.active?.enabled ? 'checked' : ''}><span>ตั้งสถานะออกได้จริงแยกจากกลุ่ม</span></label>
                    <div class="field" data-role-child-override-panel="active">
                      <label>${this.fieldLabel('ค่า Active', 'เปิดหรือปิดการออกรางวัลเฉพาะ role นี้')}</label>
                      <label class="ui checkbox"><input type="checkbox" data-role-child-override-value="active" ${child.overrides.active?.value !== false ? 'checked' : ''}><span>Active / ออกได้จริง</span></label>
                    </div>
                  </div>
                  <div class="field gacha-role-child-status-field">
                    <label class="ui checkbox"><input type="checkbox" data-role-child-override-toggle="visible" ${child.overrides.visible?.enabled ? 'checked' : ''}><span>ตั้งสถานะโชว์หน้าบ้านแยกจากกลุ่ม</span></label>
                    <div class="field" data-role-child-override-panel="visible">
                      <label>${this.fieldLabel('ค่า Visible', 'ให้ role นี้โชว์หรือซ่อนบนหน้ารางวัล')}</label>
                      <label class="ui checkbox"><input type="checkbox" data-role-child-override-value="visible" ${child.overrides.visible?.value !== false ? 'checked' : ''}><span>Visible / โชว์หน้าบ้าน</span></label>
                    </div>
                  </div>
                </div>
                <div class="gacha-role-child-grid">
                  <div class="gacha-role-child-row">
                    <label class="ui checkbox"><input type="checkbox" data-role-child-override-toggle="displayQuantityMode" ${child.overrides.displayQuantityMode?.enabled ? 'checked' : ''}><span>Override การแสดงจำนวน</span></label>
                    <div class="field" data-role-child-override-panel="displayQuantityMode">
                      <label>${this.fieldLabel('Display Quantity', 'ตั้งวิธีแสดงจำนวนเฉพาะ role นี้')}</label>
                      <select class="ui dropdown" data-role-child-override-value="displayQuantityMode">
                        ${displayQuantityOptions}
                      </select>
                    </div>
                  </div>
                  <div class="gacha-role-child-row">
                    <label class="ui checkbox"><input type="checkbox" data-role-child-override-toggle="displayQuantity" ${child.overrides.displayQuantity?.enabled ? 'checked' : ''}><span>Override จำนวนที่โชว์</span></label>
                    <div class="field" data-role-child-override-panel="displayQuantity">
                      <label>${this.fieldLabel('Quantity Number', 'จำนวนที่จะแสดงเฉพาะ role นี้')}</label>
                      <input type="number" min="0" step="1" data-role-child-override-value="displayQuantity" value="${this.escAttr(child.overrides.displayQuantity?.value ?? 0)}">
                    </div>
                  </div>
                  <div class="gacha-role-child-row">
                    <label class="ui checkbox"><input type="checkbox" data-role-child-override-toggle="coinPrice" ${child.overrides.coinPrice?.enabled ? 'checked' : ''}><span>Override ราคาเหรียญ</span></label>
                    <div class="field" data-role-child-override-panel="coinPrice">
                      <label>${this.fieldLabel('Coin Price', 'ราคาเหรียญเฉพาะ role นี้')}</label>
                      <input type="number" min="0" step="1" data-role-child-override-value="coinPrice" value="${this.escAttr(child.overrides.coinPrice?.value ?? 0)}">
                    </div>
                  </div>
                  <div class="gacha-role-child-row">
                    <label class="ui checkbox"><input type="checkbox" data-role-child-override-toggle="internalWeight" ${child.overrides.internalWeight?.enabled ? 'checked' : ''}><span>Override น้ำหนักสุ่มจริง</span></label>
                    <div class="field" data-role-child-override-panel="internalWeight">
                      <label>${this.fieldLabel('Internal Weight', 'น้ำหนักสุ่มจริงเฉพาะ role นี้')}</label>
                      <input type="number" min="0" step="0.01" data-role-child-override-value="internalWeight" value="${this.escAttr(child.overrides.internalWeight?.value ?? 1)}">
                    </div>
                  </div>
                  <div class="gacha-role-child-row">
                    <label class="ui checkbox"><input type="checkbox" data-role-child-override-toggle="displayWeight" ${child.overrides.displayWeight?.enabled ? 'checked' : ''}><span>Override น้ำหนักที่โชว์</span></label>
                    <div class="field" data-role-child-override-panel="displayWeight">
                      <label>${this.fieldLabel('Public Weight', 'น้ำหนักที่แสดงหน้าบ้านเฉพาะ role นี้')}</label>
                      <input type="number" min="0" step="0.01" data-role-child-override-value="displayWeight" value="${this.escAttr(child.overrides.displayWeight?.value ?? 1)}">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </article>`;
    },
    gachaRoleGroupConfig(prize, fallbackTierId = '') {
      const resolvedTierId = String(fallbackTierId || prize?.tierId || this.state.gachaConfig?.tiers?.[0]?.id || 'common');
      const raw = prize?.type === 'role' && prize?.roleGroupConfig && typeof prize.roleGroupConfig === 'object' ? prize.roleGroupConfig : {};
      const internalWeight = Number(raw.internalWeight ?? prize?.internalWeight ?? 1) || 0;
      return {
        tierId: String(raw.tierId || resolvedTierId || 'common'),
        badge: String(raw.badge ?? prize?.badge ?? '').trim(),
        roleDurationDays: Number(raw.roleDurationDays ?? prize?.roleDurationDays ?? 0) || 0,
        roleDurationOptions: [Number(raw.roleDurationDays ?? prize?.roleDurationDays ?? 0) || 0],
        image: String(raw.image ?? '').trim(),
        detailTemplateId: String(raw.detailTemplateId ?? prize?.detailTemplateId ?? '').trim(),
        detailText: String(raw.detailText ?? prize?.detailText ?? prize?.description ?? '').trim(),
        description: String(raw.description ?? prize?.description ?? raw.detailText ?? prize?.detailText ?? '').trim(),
        conditionTemplateId: String(raw.conditionTemplateId ?? prize?.conditionTemplateId ?? '').trim(),
        conditionText: String(raw.conditionText ?? prize?.conditionText ?? '').trim(),
        statusIcon: String(raw.statusIcon ?? prize?.statusIcon ?? '').trim(),
        pickDate: String(raw.pickDate ?? prize?.pickDate ?? '').trim(),
        expireAction: String(raw.expireAction ?? prize?.expireAction ?? 'ended') || 'ended',
        coinPrice: Number(raw.coinPrice ?? prize?.coinPrice ?? 0) || 0,
        displayQuantityMode: String(raw.displayQuantityMode ?? prize?.displayQuantityMode ?? 'none') || 'none',
        displayQuantity: Number(raw.displayQuantity ?? prize?.displayQuantity ?? 0) || 0,
        internalWeight,
        displayWeight: Number(raw.displayWeight ?? prize?.displayWeight ?? internalWeight) || 0,
        active: raw.active !== undefined ? raw.active !== false : prize?.active !== false,
        visible: raw.visible !== undefined ? raw.visible !== false : prize?.visible !== false,
      };
    },
    gachaRoleChildOverrideDefaults(shared = null) {
      return {
        active: { enabled: false, value: shared?.active !== false },
        visible: { enabled: false, value: shared?.visible !== false },
        displayQuantityMode: { enabled: false, value: String(shared?.displayQuantityMode || 'none') || 'none' },
        displayQuantity: { enabled: false, value: Number(shared?.displayQuantity ?? 0) || 0 },
        coinPrice: { enabled: false, value: Number(shared?.coinPrice ?? 0) || 0 },
        internalWeight: { enabled: false, value: Number(shared?.internalWeight ?? 1) || 0 },
        displayWeight: { enabled: false, value: Number(shared?.displayWeight ?? shared?.internalWeight ?? 1) || 0 },
      };
    },
    gachaRoleChildOverrideValue(field, value, fallback) {
      if (['active', 'visible'].includes(field)) return value !== false;
      if (field === 'displayQuantityMode') {
        return ['none', 'manual', 'quota'].includes(String(value || '')) ? String(value || '') : String(fallback || 'none');
      }
      return Number(value ?? fallback ?? 0) || 0;
    },
    gachaRoleChildConfig(prize, shared = null) {
      const resolvedShared = shared || this.gachaRoleGroupConfig(prize);
      const defaults = this.gachaRoleChildOverrideDefaults(resolvedShared);
      const raw = prize?.type === 'role' && prize?.roleChildConfig && typeof prize.roleChildConfig === 'object' ? prize.roleChildConfig : {};
      const overrides = raw.overrides && typeof raw.overrides === 'object' ? raw.overrides : {};
      const normalized = {
        enabled: raw.enabled === true,
        overrides: {},
      };
      Object.keys(defaults).forEach((field) => {
        const current = overrides[field] && typeof overrides[field] === 'object' ? overrides[field] : {};
        normalized.overrides[field] = {
          enabled: current.enabled === true,
          value: this.gachaRoleChildOverrideValue(field, current.value, defaults[field].value),
        };
      });
      return normalized;
    },
    gachaRoleChildOverrideSummary(roleChildConfig) {
      if (!roleChildConfig?.enabled) {
        return 'ใช้ค่ากลุ่มทั้งหมด';
      }
      const labels = [];
      const overrides = roleChildConfig.overrides || {};
      if (overrides.active?.enabled) labels.push(`Active:${overrides.active.value ? 'เปิด' : 'ปิด'}`);
      if (overrides.visible?.enabled) labels.push(`Visible:${overrides.visible.value ? 'โชว์' : 'ซ่อน'}`);
      if (overrides.displayQuantityMode?.enabled) {
        const modeLabel = { none: 'ไม่แสดง', manual: 'กรอกเอง', quota: 'ตามโควต้า' }[String(overrides.displayQuantityMode.value || 'none')] || 'ไม่แสดง';
        labels.push(`จำนวน:${modeLabel}`);
      }
      if (overrides.displayQuantity?.enabled) labels.push(`โชว์:${Number(overrides.displayQuantity.value || 0).toLocaleString()}`);
      if (overrides.coinPrice?.enabled) labels.push(`Coin:${Number(overrides.coinPrice.value || 0).toLocaleString()}`);
      if (overrides.internalWeight?.enabled) labels.push(`จริง:${overrides.internalWeight.value}`);
      if (overrides.displayWeight?.enabled) labels.push(`โชว์:${overrides.displayWeight.value}`);
      return labels.length ? `Override · ${labels.join(' · ')}` : 'เปิดชั้นย่อย แต่ยังไม่ตั้ง override';
    },
    gachaRoleConfigGroupId(prize) {
      const groupId = String(prize?.groupId || '').trim();
      if (groupId) return groupId;
      return String(prize?.id || '').trim() || `group-${Date.now()}`;
    },
    gachaRoleConfigGroups(prizes = null) {
      const rows = Array.isArray(prizes) ? prizes : (this.state.gachaConfig?.prizes || []).filter((prize) => (prize.type || 'item') === 'role');
      const grouped = new Map();
      rows.forEach((prize, index) => {
        const groupId = this.gachaRoleConfigGroupId(prize);
        const shared = this.gachaRoleGroupConfig(prize);
        if (!grouped.has(groupId)) {
          grouped.set(groupId, {
            id: groupId,
            tierId: shared.tierId || prize.tierId || this.state.gachaConfig?.tiers?.[0]?.id || 'common',
            sortOrder: Number(prize.sortOrder || (100000 + (index * 10))),
            prizes: [],
          });
        }
        const group = grouped.get(groupId);
        group.tierId = group.tierId || shared.tierId || prize.tierId || this.state.gachaConfig?.tiers?.[0]?.id || 'common';
        group.sortOrder = Math.min(group.sortOrder, Number(prize.sortOrder || group.sortOrder));
        group.prizes.push(prize);
      });
      return [...grouped.values()]
        .map((group) => ({
          ...group,
          prizes: [...group.prizes].sort((a, b) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0) || String(a.name || '').localeCompare(String(b.name || ''))),
        }))
        .sort((a, b) => this.gachaTierRank(a.tierId) - this.gachaTierRank(b.tierId) || Number(a.sortOrder || 0) - Number(b.sortOrder || 0) || String(a.id || '').localeCompare(String(b.id || '')))
        .map((group) => {
          const shared = this.gachaRoleGroupConfig(group.prizes[0] || {}, group.tierId);
          return {
            ...group,
            sharedPrize: JSON.parse(JSON.stringify({
              id: `group-placeholder-${group.id}`,
              groupId: group.id,
              tierId: shared.tierId,
              type: 'role',
              name: '<roleName>',
              shortName: '',
              image: '',
              discordRoleId: '',
              ...shared,
            })),
          };
        });
    },
    gachaRoleConfigSelectedRoleIds(prizes = []) {
      const seen = new Set();
      return (prizes || [])
        .map((prize) => String(prize?.discordRoleId || '').trim())
        .filter((roleId) => {
          if (!roleId || seen.has(roleId)) return false;
          seen.add(roleId);
          return true;
        });
    },
    gachaRoleConfigRoleLabel(roleId = '') {
      const resolvedRoleId = String(roleId || '').trim();
      if (!resolvedRoleId) {
        return 'ยังไม่ได้เลือก Role จากด้านบน';
      }
      const role = this.gachaRoleMeta(resolvedRoleId);
      if (!role) {
        return resolvedRoleId;
      }
      return [
        role.roleName || resolvedRoleId,
        role.roleTier ? `Tier ${role.roleTier}` : '',
        role.roleSeriesName ? `Series: ${role.roleSeriesName}` : '',
      ].filter(Boolean).join(' · ');
    },
    gachaRoleConfigRoleOptions(selectedRoleIds = []) {
      const selected = Array.from(new Set((Array.isArray(selectedRoleIds) ? selectedRoleIds : [selectedRoleIds])
        .map((roleId) => String(roleId || '').trim())
        .filter(Boolean)));
      const knownRoles = this.state.gachaRoles || [];
      const knownIds = new Set(knownRoles.map((role) => String(role.roleId || '')));
      const rows = [];
      selected.filter((roleId) => !knownIds.has(roleId)).forEach((roleId) => {
        rows.push({
          roleId,
          roleName: roleId,
          roleTier: '',
          roleSeriesName: '',
        });
      });
      rows.push(...knownRoles);
      return rows.map((role) => {
        const roleId = String(role.roleId || '');
        return `<option value="${this.escAttr(roleId)}"${selected.includes(roleId) ? ' selected' : ''}>${this.esc(role.roleName || roleId)}${role.roleTier ? ` · ${this.esc(role.roleTier)}` : ''}${role.roleSeriesName ? ` · ${this.esc(role.roleSeriesName)}` : ''}</option>`;
      }).join('');
    },
    buildGachaRoleConfigPrize(basePrize, groupId, roleId, sortOrder = 100000) {
      const shared = this.gachaRoleGroupConfig(basePrize);
      return {
        id: `prize-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
        tierId: shared.tierId || basePrize?.tierId || this.state.gachaConfig?.tiers?.[0]?.id || 'common',
        type: 'role',
        groupId: String(groupId || basePrize?.groupId || ''),
        name: '<roleName>',
        shortName: '',
        image: String(shared.image || '').trim(),
        roleImageOverride: '',
        description: String(shared.description || '').trim(),
        detailTemplateId: String(shared.detailTemplateId || '').trim(),
        detailText: String(shared.detailText || '').trim(),
        conditionTemplateId: String(shared.conditionTemplateId || '').trim(),
        conditionText: String(shared.conditionText || '').trim(),
        badge: String(shared.badge || '').trim(),
        statusIcon: String(shared.statusIcon || '').trim(),
        pickDate: String(shared.pickDate || '').trim(),
        expireAction: String(shared.expireAction || 'ended') || 'ended',
        coinPrice: Number(shared.coinPrice || 0) || 0,
        displayQuantityMode: String(shared.displayQuantityMode || 'none') || 'none',
        displayQuantity: Number(shared.displayQuantity || 0) || 0,
        internalWeight: Number(shared.internalWeight || 1) || 0,
        displayWeight: Number(shared.displayWeight ?? shared.internalWeight ?? 1) || 0,
        active: shared.active !== false,
        visible: shared.visible !== false,
        sortOrder: Number(sortOrder || 100000),
        discordRoleId: String(roleId || '').trim(),
        roleDurationDays: Number(shared.roleDurationDays || 0) || 0,
        roleDurationOptions: [Number(shared.roleDurationDays || 0) || 0],
        roleGroupConfig: { ...shared },
        roleChildConfig: {
          enabled: false,
          overrides: this.gachaRoleChildOverrideDefaults(shared),
        },
      };
    },
    openGachaRoleConfigPicker(groupId) {
      const row = $(`#gachaRolePrizeList [data-role-config-id="${this.escAttr(groupId)}"]`);
      const dropdown = row.find('[data-role-config-selected-roles]').closest('.ui.dropdown');
      if (!dropdown.length) return;
      dropdown.dropdown('show');
      const search = dropdown.find('input.search');
      if (search.length) {
        search.trigger('focus');
      }
    },
    syncGachaRoleConfigSelectedRoles(groupId, selectedRoleIds = []) {
      const normalizedRoleIds = Array.from(new Set((Array.isArray(selectedRoleIds) ? selectedRoleIds : [selectedRoleIds])
        .map((roleId) => String(roleId || '').trim())
        .filter(Boolean)));
      const config = this.collectGachaPrizeConfig();
      const groupPrizes = (config.prizes || [])
        .filter((prize) => (prize.type || 'item') === 'role' && this.gachaRoleConfigGroupId(prize) === String(groupId))
        .sort((a, b) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0));
      if (!groupPrizes.length) return;
      if (!normalizedRoleIds.length) {
        if (!confirm('Config นี้จะไม่มี Role เหลืออยู่แล้ว ต้องการลบทั้ง config เลยไหม?')) {
          this.renderGachaPrizeLists();
          this.bindGachaPrizeEditor();
          return;
        }
        config.prizes = (config.prizes || []).filter((prize) => this.gachaRoleConfigGroupId(prize) !== String(groupId));
        this.state.gachaConfig = config;
        this.state.gachaPrizeExpanded = { ...(this.state.gachaPrizeExpanded || { item: '', role: '' }), role: '' };
        this.renderGachaPrizeLists();
        this.bindGachaPrizeEditor();
        return;
      }

      const basePrize = JSON.parse(JSON.stringify(groupPrizes[0]));
      const roleBaseSort = Math.min(...groupPrizes.map((prize) => Number(prize.sortOrder || 100000)), 100000);
      const nextGroupPrizes = [];
      const nextRoleIds = new Set(normalizedRoleIds);
      const expanded = { ...(this.state.gachaRoleEntryExpanded || {}) };
      const newPrizeIds = [];

      groupPrizes.forEach((prize) => {
        const roleId = String(prize.discordRoleId || '').trim();
        if (!roleId || !nextRoleIds.has(roleId)) return;
        if (nextGroupPrizes.some((entry) => String(entry.discordRoleId || '').trim() === roleId)) return;
        nextGroupPrizes.push(prize);
      });

      normalizedRoleIds.forEach((roleId) => {
        if (nextGroupPrizes.some((prize) => String(prize.discordRoleId || '').trim() === roleId)) return;
        const nextPrize = this.buildGachaRoleConfigPrize(basePrize, groupId, roleId);
        nextGroupPrizes.push(nextPrize);
        newPrizeIds.push(nextPrize.id);
      });

      nextGroupPrizes.forEach((prize, index) => {
        prize.groupId = String(groupId);
        prize.type = 'role';
        prize.discordRoleId = String(prize.discordRoleId || '').trim();
        prize.sortOrder = roleBaseSort + (index * 10);
        prize.roleGroupConfig = { ...this.gachaRoleGroupConfig(basePrize) };
        if (!String(prize.roleImageOverride || '').trim()) {
          prize.image = String(prize.roleGroupConfig.image || '').trim();
        }
      });

      const otherPrizes = (config.prizes || []).filter((prize) => !((prize.type || 'item') === 'role' && this.gachaRoleConfigGroupId(prize) === String(groupId)));
      config.prizes = otherPrizes.concat(nextGroupPrizes);
      this.state.gachaConfig = config;
      this.state.gachaPrizeExpanded = {
        ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
        role: String(groupId),
      };
      Object.keys(expanded).forEach((prizeId) => {
        if (!config.prizes.some((prize) => String(prize.id) === String(prizeId))) {
          delete expanded[prizeId];
        }
      });
      newPrizeIds.forEach((prizeId) => {
        expanded[prizeId] = true;
      });
      this.state.gachaRoleEntryExpanded = expanded;
      this.renderGachaPrizeLists();
      this.bindGachaPrizeEditor();
    },
    gachaPrizeResolvedName(prize, type = 'item', preferShort = false) {
      if ((type || prize?.type || 'item') !== 'role') {
        return preferShort ? (prize?.shortName || prize?.name || 'Mystery Prize') : (prize?.name || 'Mystery Prize');
      }
      const roleName = this.gachaRoleName(prize);
      const template = preferShort && String(prize?.shortName || '').trim() ? String(prize.shortName || '') : String(prize?.name || '');
      return this.gachaResolveRoleNameTemplate(template, roleName);
    },
    gachaResolveRoleNameTemplate(template, roleName) {
      const resolvedTemplate = String(template || '').trim();
      const safeRoleName = String(roleName || '').trim();
      if (!resolvedTemplate || ['New Gachapon Role Prize', 'Prize Pin Role'].includes(resolvedTemplate)) {
        return safeRoleName || 'Mystery Prize';
      }
      const resolved = resolvedTemplate.replace(/<roleName>/g, safeRoleName || 'Role').trim();
      return resolved || safeRoleName || 'Mystery Prize';
    },
    gachaPrizeTemplateUsageLabel(prize, type) {
      const templates = this.state.gachaConfig?.templates || [];
      const templateId = type === 'condition' ? String(prize?.conditionTemplateId || '') : String(prize?.detailTemplateId || '');
      const text = type === 'condition' ? String(prize?.conditionText || '').trim() : String(prize?.detailText || prize?.description || '').trim();
      const label = type === 'condition' ? 'Condition' : 'Detail';
      if (text) {
        return `${label}: Custom`;
      }
      if (!templateId) {
        return `${label}: -`;
      }
      const template = templates.find((entry) => String(entry.id || '') === templateId);
      return `${label}: ${template?.name || templateId}`;
    },
    gachaPrizeTemplateSummary(prize) {
      return [
        this.gachaPrizeTemplateUsageLabel(prize, 'detail'),
        this.gachaPrizeTemplateUsageLabel(prize, 'condition'),
      ].join(' · ');
    },
    gachaDisplayDash(value, fallback = '-') {
      const normalized = String(value == null ? '' : value).trim();
      return normalized || fallback;
    },
    gachaTierColor(tierId) {
      const map = {
        mythic: '#c2185b',
        legendary: '#b7791f',
        epic: '#7c3aed',
        rare: '#0284c7',
        common: '#15803d',
      };
      return map[String(tierId || '').toLowerCase()] || 'var(--orbit-text)';
    },
    gachaTierOptions(tiers) {
      return [...(tiers || [])]
        .sort((a, b) => this.gachaTierRank(a.id) - this.gachaTierRank(b.id) || String(a.name || '').localeCompare(String(b.name || '')))
        .map((tier) => `<option value="${this.escAttr(tier.id)}" style="color:${this.escAttr(this.gachaTierColor(tier.id))}">${this.esc(tier.tier)} · ${this.esc(tier.name)}</option>`)
        .join('');
    },
    gachaAssetMeta(path = '') {
      const current = String(path || '').trim();
      if (!current) {
        return {
          path: '',
          name: 'ไม่ใช้',
          url: '',
          kind: 'none',
          isUploaded: false,
          modifiedAt: 0,
          description: 'ไม่ใช้รูปนี้',
        };
      }
      const asset = (this.state.gachaAssets || []).find((entry) => String(entry.path || '') === current);
      if (asset) {
        return {
          ...asset,
          name: String(asset.name || current),
          url: String(asset.url || this.gachaAssetUrl(current)),
          description: String(asset.path || current),
        };
      }
      return {
        path: current,
        name: current.split('/').pop() || current,
        url: this.gachaAssetUrl(current),
        kind: 'custom',
        isUploaded: false,
        modifiedAt: 0,
        description: `${current} · custom`,
      };
    },
    gachaAssetOptions(currentPath = '') {
      const current = String(currentPath || '').trim();
      const assets = this.state.gachaAssets || [];
      const known = new Set(assets.map((asset) => String(asset.path || '')));
      const rows = [this.gachaAssetMeta('')];
      if (current && !known.has(current)) {
        rows.push(this.gachaAssetMeta(current));
      }
      assets.forEach((asset) => rows.push(this.gachaAssetMeta(asset.path || '')));
      return rows.map((asset) => {
        const hasPreview = String(asset.url || '').trim() !== '';
        return `
          <div class="item" data-value="${this.escAttr(asset.path || '')}" data-text="${this.escAttr(asset.name || asset.path || 'ไม่ใช้')}">
            <span class="gacha-asset-option${hasPreview ? ' is-previewable' : ''}"${hasPreview ? ` data-gacha-asset-preview-url="${this.escAttr(asset.url)}" data-gacha-asset-preview-label="${this.escAttr(asset.name || asset.path || '')}"` : ''}>
              <span class="gacha-asset-option-thumb${hasPreview ? '' : ' is-empty'}">
                ${hasPreview ? `<img src="${this.escAttr(asset.url)}" alt="">` : '<i class="fa-solid fa-ban"></i>'}
              </span>
              <span class="gacha-asset-option-copy">
                <strong>${this.esc(asset.name || asset.path || 'ไม่ใช้')}</strong>
                <span>${this.esc(asset.description || asset.path || 'ไม่ใช้รูปนี้')}</span>
              </span>
            </span>
          </div>`;
      }).join('');
    },
    gachaAssetPicker(prize, field, label, hint, valueAttr = 'data-prize-field') {
      const current = this.gachaAssetMeta(prize?.[field] || '');
      return `
        <div class="field">
          <label>${this.fieldLabel(label, hint)}</label>
          <div class="ui fluid search selection dropdown gacha-asset-dropdown">
            <input type="hidden" ${valueAttr}="${this.escAttr(field)}" value="${this.escAttr(prize?.[field] || '')}">
            <i class="dropdown icon"></i>
            <div class="text">${this.esc(current.name || 'ไม่ใช้')}</div>
            <div class="menu">${this.gachaAssetOptions(prize?.[field] || '')}</div>
          </div>
        </div>`;
    },
    gachaTemplateModeOptions(type, templates) {
      const label = type === 'condition' ? 'ไม่แสดงเงื่อนไข' : 'ไม่แสดงรายละเอียด';
      return [`<option value="">${label}</option>`, '<option value="__custom__">เขียนเฉพาะ Prize นี้</option>']
        .concat((templates || [])
          .filter((template) => template.type === type)
          .map((template) => `<option value="${this.escAttr(template.id)}">${this.esc(template.name || template.id)}</option>`))
        .join('');
    },
    gachaTemplateModeValue(prize, type) {
      const text = type === 'condition' ? prize.conditionText : prize.detailText;
      const templateId = type === 'condition' ? prize.conditionTemplateId : prize.detailTemplateId;
      if (String(text || '').trim() !== '') return '__custom__';
      return templateId || '';
    },
    initGachaPrizeScope(scope = document) {
      const root = $(scope);
      root.find('.ui.dropdown').dropdown({
        fullTextSearch: true,
        forceSelection: false,
      });
      root.find('.gacha-asset-dropdown').each((_, element) => {
        const dropdown = $(element);
        const hidden = dropdown.find('input[type="hidden"]').first();
        dropdown.dropdown('set selected', String(hidden.val() || ''));
        dropdown.dropdown('setting', 'onHide', () => {
          this.hideGachaAssetPreview();
          return true;
        });
      });
      root.find('.ui.checkbox').checkbox();
      this.normalizeVisibleCheckboxes(root);
      this.syncGachaPrizeOptionalPanels(scope);
      this.syncGachaPrizeTextPanels(scope);
      this.bindGachaAssetPreview(scope);
    },
    syncGachaPrizeOptionalPanels(scope = document) {
      const root = $(scope);
      root.find('[data-gacha-optional-field]').each((_, input) => {
        const field = String($(input).data('gacha-optional-field') || '');
        const row = $(input).closest('.gacha-prize-row, .gacha-role-config-row');
        row.find(`[data-gacha-optional-panel="${field}"]`).prop('hidden', !$(input).is(':checked'));
      });
    },
    syncGachaPrizeTextPanels(scope = document) {
      const root = $(scope);
      root.find('[data-prize-text-mode]').each((_, select) => {
        const type = String($(select).data('prize-text-mode') || '');
        const row = $(select).closest('.gacha-prize-row, .gacha-role-config-row');
        row.find(`[data-gacha-text-panel="${type}"]`).prop('hidden', String($(select).val() || '') !== '__custom__');
      });
    },
    syncGachaRoleChildPanels(scope = document) {
      const root = $(scope);
      const entries = root.is('[data-role-config-entry-id]') ? root.add(root.find('[data-role-config-entry-id]')) : root.find('[data-role-config-entry-id]');
      entries.each((_, node) => {
        const entry = $(node);
        const enabled = entry.find('[data-role-child-enabled]').is(':checked');
        entry.find('[data-role-child-panel="enabled"]').prop('hidden', !enabled);
        entry.find('[data-role-child-override-toggle]').each((__, input) => {
          const field = String($(input).data('role-child-override-toggle') || '');
          entry.find(`[data-role-child-override-panel="${field}"]`).prop('hidden', !enabled || !$(input).is(':checked'));
        });
      });
    },
    syncGachaRoleEntryBodies(scope = document) {
      const root = $(scope);
      const entries = root.is('[data-role-config-entry-id]') ? root.add(root.find('[data-role-config-entry-id]')) : root.find('[data-role-config-entry-id]');
      entries.each((_, node) => {
        const entry = $(node);
        const prizeId = String(entry.data('role-config-entry-id') || '');
        const isOpen = Boolean(this.state.gachaRoleEntryExpanded?.[prizeId]);
        entry.toggleClass('is-open', isOpen);
        entry.find('[data-role-config-entry-body]').prop('hidden', !isOpen);
        entry.find('[data-role-config-entry-preview]').prop('hidden', isOpen);
        entry.find('[data-role-entry-preview-caret] i')
          .removeClass('fa-chevron-right fa-chevron-down')
          .addClass(isOpen ? 'fa-chevron-down' : 'fa-chevron-right');
      });
    },
    toggleGachaRoleEntryEditor(prizeId) {
      const expanded = { ...(this.state.gachaRoleEntryExpanded || {}) };
      expanded[prizeId] = !expanded[prizeId];
      this.state.gachaRoleEntryExpanded = expanded;
      this.syncGachaRoleEntryBodies(document);
    },
    gachaCollectRoleConfigShared(row, config = {}) {
      const shared = {
        type: 'role',
        groupId: String(row.data('role-config-id') || ''),
        tierId: String(row.find('[data-role-config-field="tierId"]').val() || config.tiers?.[0]?.id || 'common'),
        badge: String(row.find('[data-role-config-field="badge"]').val() || '').trim(),
        roleDurationDays: Number(row.find('[data-role-config-field="roleDurationDays"]').val() || 0),
        roleDurationOptions: [Number(row.find('[data-role-config-field="roleDurationDays"]').val() || 0) || 0],
        image: String(row.find('[data-role-config-field="image"]').val() || '').trim(),
        internalWeight: Number(row.find('[data-role-config-field="internalWeight"]').val() || 0),
        displayWeight: Number(row.find('[data-role-config-field="displayWeight"]').val() || 0),
        displayQuantityMode: String(row.find('[data-role-config-field="displayQuantityMode"]').val() || 'none'),
        displayQuantity: Number(row.find('[data-role-config-field="displayQuantity"]').val() || 0),
        coinPrice: Number(row.find('[data-role-config-field="coinPrice"]').val() || 0),
        statusIcon: String(row.find('[data-role-config-field="statusIcon"]').val() || '').trim(),
        pickDate: this.gachaPickDateStorageValue(String(row.find('[data-role-config-field="pickDate"]').val() || '')),
        expireAction: String(row.find('[data-role-config-field="expireAction"]').val() || 'ended'),
        active: row.find('[data-role-config-field="active"]').is(':checked'),
        visible: row.find('[data-role-config-field="visible"]').is(':checked'),
      };
      row.find('[data-gacha-optional-field]').each((_, input) => {
        if ($(input).is(':checked')) return;
        const field = String($(input).data('gacha-optional-field') || '');
        if (field === 'image') shared.image = '';
        if (field === 'coinPrice') shared.coinPrice = 0;
        if (field === 'pickDate') shared.pickDate = '';
        if (field === 'statusIcon') shared.statusIcon = '';
      });
      ['detail', 'condition'].forEach((textType) => {
        const mode = String(row.find(`[data-prize-text-mode="${textType}"]`).val() || '');
        const text = String(row.find(`[data-prize-text-field="${textType}"]`).val() || '').trim();
        if (textType === 'detail') {
          shared.detailTemplateId = mode === '__custom__' ? '' : mode;
          shared.detailText = mode === '__custom__' ? text : '';
          shared.description = mode === '__custom__' ? text : '';
        } else {
          shared.conditionTemplateId = mode === '__custom__' ? '' : mode;
          shared.conditionText = mode === '__custom__' ? text : '';
        }
      });
      return shared;
    },
    gachaCollectRoleChildConfig(entry, shared) {
      const defaults = this.gachaRoleChildOverrideDefaults(shared);
      const child = {
        enabled: entry.find('[data-role-child-enabled]').is(':checked'),
        overrides: {},
      };
      Object.keys(defaults).forEach((field) => {
        const valueInput = entry.find(`[data-role-child-override-value="${field}"]`);
        const rawValue = ['active', 'visible'].includes(field) ? valueInput.is(':checked') : valueInput.val();
        child.overrides[field] = {
          enabled: entry.find(`[data-role-child-override-toggle="${field}"]`).is(':checked'),
          value: this.gachaRoleChildOverrideValue(field, rawValue, defaults[field].value),
        };
      });
      return child;
    },
    gachaRoleChildEffectiveValue(shared, child, field) {
      if (child?.enabled && child?.overrides?.[field]?.enabled) {
        return child.overrides[field].value;
      }
      return shared?.[field];
    },
    gachaRoleEntryDraft(entryNode) {
      const entry = $(entryNode);
      const row = entry.closest('[data-role-config-id]');
      const shared = this.gachaCollectRoleConfigShared(row, this.state.gachaConfig || {});
      const roleImageOverride = String(entry.find('[data-role-config-entry-field="image"]').val() || '').trim();
      return {
        id: String(entry.data('role-config-entry-id') || ''),
        groupId: String(entry.data('role-config-id') || ''),
        type: 'role',
        name: String(entry.find('[data-role-config-entry-field="name"]').val() || '<roleName>').trim() || '<roleName>',
        shortName: String(entry.find('[data-role-config-entry-field="shortName"]').val() || '').trim(),
        image: roleImageOverride || String(shared.image || '').trim(),
        roleImageOverride,
        discordRoleId: String(entry.find('[data-role-config-entry-field="discordRoleId"]').val() || '').trim(),
        detailTemplateId: String(shared.detailTemplateId || '').trim(),
        detailText: String(shared.detailText || shared.description || '').trim(),
        description: String(shared.description || shared.detailText || '').trim(),
        conditionTemplateId: String(shared.conditionTemplateId || '').trim(),
        conditionText: String(shared.conditionText || '').trim(),
        roleDurationDays: Number(shared.roleDurationDays || 0) || 0,
        roleDurationOptions: [Number(shared.roleDurationDays || 0) || 0],
        roleChildConfig: this.gachaCollectRoleChildConfig(entry, shared),
      };
    },
    refreshGachaRoleEntryPreview(entryNode) {
      const entry = $(entryNode);
      const row = entry.closest('[data-role-config-id]');
      const shared = this.gachaCollectRoleConfigShared(row, this.state.gachaConfig || {});
      const draft = this.gachaRoleEntryDraft(entry);
      const roleMeta = this.gachaRoleMeta(draft.discordRoleId);
      const actualRoleName = this.gachaDisplayDash(this.gachaRoleName(draft));
      const resolvedName = this.gachaPrizeResolvedName(draft, 'role', false);
      const roleMetaLine = [
        roleMeta?.roleTier ? `Tier ยศ: ${roleMeta.roleTier}` : '',
        roleMeta?.roleSeriesName ? `Series: ${roleMeta.roleSeriesName}` : '',
        draft.discordRoleId ? `ID: ${draft.discordRoleId}` : '',
        this.gachaRoleChildOverrideSummary(draft.roleChildConfig),
      ].filter(Boolean).join(' · ');
      const templateSummary = this.gachaDisplayDash(this.gachaPrizeTemplateSummary(draft));
      const durationLabel = this.gachaRoleDurationPoolLabel(shared);
      const internalWeight = this.gachaRoleChildEffectiveValue(shared, draft.roleChildConfig, 'internalWeight');
      const displayWeight = this.gachaRoleChildEffectiveValue(shared, draft.roleChildConfig, 'displayWeight');
      const activeValue = this.gachaRoleChildEffectiveValue(shared, draft.roleChildConfig, 'active') !== false;
      const visibleValue = this.gachaRoleChildEffectiveValue(shared, draft.roleChildConfig, 'visible') !== false;
      entry.find('[data-role-entry-preview-thumb]').html(this.gachaPrizeThumbMedia(draft, 'role', 'thumb'));
      entry.find('[data-role-entry-preview-name]').text(actualRoleName);
      entry.find('[data-role-entry-preview-summary]').text(roleMetaLine || '-');
      entry.find('[data-role-entry-role-label]').val(this.gachaRoleConfigRoleLabel(draft.discordRoleId || ''));
      entry.find('[data-role-entry-preview-field="prizeName"]').text(this.gachaDisplayDash(resolvedName));
      entry.find('[data-role-entry-preview-field="shortName"]').text(this.gachaDisplayDash(String(draft.shortName || '').trim()));
      entry.find('[data-role-entry-preview-field="duration"]').text(durationLabel);
      entry.find('[data-role-entry-preview-field="template"]').text(templateSummary);
      entry.find('[data-role-entry-preview-chip="internalWeight"]').text(`Int ${this.gachaDisplayDash(internalWeight)}`);
      entry.find('[data-role-entry-preview-chip="displayWeight"]').text(`Pub ${this.gachaDisplayDash(displayWeight)}`);
      entry.find('[data-role-entry-preview-chip="active"]').text(activeValue ? 'ออกได้จริง' : 'ไม่ออก').toggleClass('is-live', activeValue);
      entry.find('[data-role-entry-preview-chip="visible"]').text(visibleValue ? 'โชว์หน้าบ้าน' : 'ซ่อนหน้าบ้าน').toggleClass('is-live', visibleValue);
      const inlineSummary = entry.find('.orbit-inline-summary-item').first();
      inlineSummary.find('strong').text(actualRoleName);
      inlineSummary.find('span').text(roleMetaLine || '-');
      this.refreshGachaRoleConfigPreview(row);
    },
    gachaRoleConfigPreviewPrize(rowNode) {
      const row = $(rowNode);
      const shared = this.gachaCollectRoleConfigShared(row, this.state.gachaConfig || {});
      if (String(shared.image || '').trim()) {
        return {
          ...shared,
          type: 'role',
          image: String(shared.image || '').trim(),
          discordRoleId: '',
        };
      }
      const firstEntry = row.find('[data-role-config-entry-id]').first();
      if (firstEntry.length) {
        return this.gachaRoleEntryDraft(firstEntry);
      }
      return {
        ...shared,
        type: 'role',
        image: '',
        discordRoleId: '',
      };
    },
    refreshGachaRoleConfigPreview(rowNode) {
      const row = $(rowNode);
      if (!row.length) return;
      const previewPrize = this.gachaRoleConfigPreviewPrize(row);
      row.find('[data-role-config-preview-thumb]').html(this.gachaPrizeThumbMedia(previewPrize, 'role', 'thumb'));
      row.find('[data-role-config-preview-art]').html(this.gachaPrizeThumbMedia(previewPrize, 'role', 'art'));
    },
    bindGachaAssetPreview(scope = document) {
      const root = $(scope);
      root.find('.gacha-asset-option.is-previewable')
        .off('mouseenter.gachaPreview mousemove.gachaPreview mouseleave.gachaPreview')
        .on('mouseenter.gachaPreview', (e) => this.showGachaAssetPreview(e))
        .on('mousemove.gachaPreview', (e) => this.moveGachaAssetPreview(e))
        .on('mouseleave.gachaPreview', () => this.hideGachaAssetPreview());
      root.find('.gacha-asset-dropdown, .gacha-asset-dropdown .menu, .gacha-asset-dropdown .item')
        .off('mouseleave.gachaPreview click.gachaPreview')
        .on('mouseleave.gachaPreview click.gachaPreview', () => this.hideGachaAssetPreview());
      $(document).off('mousedown.gachaPreview keydown.gachaPreview').on('mousedown.gachaPreview keydown.gachaPreview', () => this.hideGachaAssetPreview());
      $(window).off('blur.gachaPreview scroll.gachaPreview').on('blur.gachaPreview scroll.gachaPreview', () => this.hideGachaAssetPreview());
    },
    showGachaAssetPreview(event) {
      const target = $(event.currentTarget);
      const url = String(target.data('gacha-asset-preview-url') || '').trim();
      const kind = String(target.data('gacha-asset-preview-kind') || 'image').trim();
      const label = String(target.data('gacha-asset-preview-label') || 'Preview');
      if (!url) return;
      if (!this.state.gachaAssetPreviewEl) {
        this.state.gachaAssetPreviewEl = $('<div class="gacha-asset-preview-popup" style="display:none" hidden></div>').appendTo('body');
      }
      this.state.gachaAssetPreviewEl.html(`
        ${kind === 'video'
          ? `<video src="${this.escAttr(url)}" muted autoplay loop playsinline webkit-playsinline preload="metadata" disablepictureinpicture aria-label="${this.escAttr(label)}"></video>`
          : `<img src="${this.escAttr(url)}" alt="">`}
        <span>${this.esc(label)}</span>
      `).prop('hidden', false).css('display', 'grid');
      this.moveGachaAssetPreview(event);
    },
    moveGachaAssetPreview(event) {
      const popup = this.state.gachaAssetPreviewEl;
      if (!popup || popup.prop('hidden') || popup.css('display') === 'none') return;
      const offsetX = 20;
      const offsetY = 20;
      const left = Math.min(event.pageX + offsetX, $(window).scrollLeft() + $(window).width() - popup.outerWidth() - 16);
      const top = Math.min(event.pageY + offsetY, $(window).scrollTop() + $(window).height() - popup.outerHeight() - 16);
      popup.css({ left, top });
    },
    hideGachaAssetPreview() {
      if (this.state.gachaAssetPreviewEl) {
        this.state.gachaAssetPreviewEl.prop('hidden', true).css('display', 'none');
      }
    },
    renderGachaPrizeTemplates() {
      const templates = this.state.gachaConfig?.templates || [];
      $('#gachaPrizeTemplateList').html(templates.length ? templates.map((template, index) => `
        <article class="orbit-earn-rule" data-gacha-template-index="${index}">
          <div class="orbit-earn-rule-head">
            <div>
              <strong>${this.esc(template.name || 'Template')}</strong>
              <span>${this.esc(template.id || `template-${index + 1}`)} · ${this.esc(template.type || 'detail')}</span>
            </div>
            <button class="ui tiny button" type="button" data-gacha-template-remove="${index}"><i class="fa-solid fa-trash"></i></button>
          </div>
          <div class="ui small form orbit-earn-rule-grid">
            <div class="field">
              <label>${this.fieldLabel('Type', 'detail = รายละเอียด, condition = เงื่อนไข')}</label>
              <select class="ui dropdown" data-gacha-template-field="type">
                <option value="detail">detail</option>
                <option value="condition">condition</option>
              </select>
            </div>
            <div class="field">
              <label>${this.fieldLabel('Name', 'ชื่อเทมเพลตไว้เลือกใช้งานตอนแก้รางวัล')}</label>
              <input data-gacha-template-field="name" value="${this.escAttr(template.name || '')}">
            </div>
            <div class="field orbit-earn-rule-grid-wide">
              <label>${this.fieldLabel('Content', 'ข้อความหลักของเทมเพลต')}</label>
              <textarea rows="3" data-gacha-template-field="content">${this.esc(template.content || '')}</textarea>
            </div>
          </div>
        </article>
      `).join('') : this.empty('ยังไม่มีเทมเพลตข้อความ'));
      $('#gachaPrizeTemplateList [data-gacha-template-index]').each((_, row) => {
        const index = Number($(row).data('gacha-template-index'));
        $(row).find('[data-gacha-template-field="type"]').val(templates[index]?.type || 'detail');
      });
      $('#gachaPrizeTemplateList .ui.dropdown').dropdown();
      $('#gachaAddTemplateButton').off('click.gachaTemplate').on('click.gachaTemplate', () => this.addGachaTemplate());
      $('[data-gacha-template-remove]').off('click.gachaTemplate').on('click.gachaTemplate', (e) => {
        const index = Number($(e.currentTarget).data('gacha-template-remove'));
        const config = this.collectGachaPrizeConfig();
        config.templates = (config.templates || []).filter((_, rowIndex) => rowIndex !== index);
        this.state.gachaConfig = config;
        this.renderGachaPrizeTemplates();
        this.renderGachaPrizeLists();
        this.bindGachaPrizeEditor();
      });
    },
    gachaTemplateOptions(type, templates) {
      const filtered = (templates || []).filter((template) => template.type === type);
      return ['<option value="">ไม่ใช้เทมเพลต</option>'].concat(filtered.map((template) => `<option value="${this.escAttr(template.id)}">${this.esc(template.name || template.id)}</option>`)).join('');
    },
    gachaTierLabel(tierId) {
      const tier = (this.state.gachaConfig?.tiers || []).find((entry) => entry.id === tierId);
      return tier ? `${tier.tier} · ${tier.name}` : (tierId || 'Unknown tier');
    },
    gachaTierRank(tierId) {
      const map = { mythic: 1, legendary: 2, epic: 3, rare: 4, common: 5 };
      const key = String(tierId || '').toLowerCase();
      return map[key] || 99;
    },
    infoTip(text) {
      return `<span class="orbit-info-tip" data-tooltip="${this.escAttr(this.tl(text))}"><i class="fa-solid fa-circle-info"></i></span>`;
    },
    fieldLabel(label, text) {
      return `${this.esc(this.tl(label))} ${this.infoTip(text)}`;
    },
    gachaRoleMeta(roleId) {
      return (this.state.gachaRoles || []).find((entry) => entry.roleId === roleId) || null;
    },
    gachaRoleName(prize) {
      const role = (this.state.gachaRoles || []).find((entry) => entry.roleId === prize.discordRoleId);
      return role?.roleName || '';
    },
    gachaPrizeThumbMedia(prize, type, mode = 'thumb') {
      const imageClass = mode === 'art' ? 'gacha-prize-art-media' : 'gacha-prize-thumb-media';
      const role = type === 'role' ? this.gachaRoleMeta(prize.discordRoleId) : null;
      const roleFallbackImage = ['images/item-1.png', 'item-1.png'].includes(String(prize.image || '').trim()) ? '' : String(prize.image || '').trim();
      const settings = this.state.gachaConfig?.settings || {};
      const defaultRoleIcon = String(settings.defaultRoleIcon || '').trim();
      const defaultItemIcon = String(settings.defaultItemIcon || '').trim();
      const imagePath = type === 'role'
        ? (roleFallbackImage ? this.gachaAssetUrl(roleFallbackImage) : (role?.roleIconUrl || (defaultRoleIcon ? this.gachaAssetUrl(defaultRoleIcon) : this.gachaAssetUrl('images/icon_roles_blank.png'))))
        : this.gachaAssetUrl(prize.image || defaultItemIcon || 'images/item-1.png');
      if (imagePath) {
        return `<img class="${imageClass}" src="${this.escAttr(imagePath)}" alt="">`;
      }
      return `<span class="gacha-prize-role-fallback" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span>`;
    },
    gachaRoleDurationLabel(days) {
      const value = Number(days || 0);
      return value > 0 ? `${value} วัน` : 'ถาวร';
    },
    gachaNormalizeRoleDurationOptions(value, fallback = 0) {
      const allowed = [0, 3, 7, 14, 30, 60, 90];
      const raw = Array.isArray(value) ? value : [fallback];
      const out = [];
      raw.forEach((entry) => {
        const day = Number(entry || 0);
        if (!allowed.includes(day) || out.includes(day)) return;
        out.push(day);
      });
      if (!out.length) out.push(allowed.includes(Number(fallback || 0)) ? Number(fallback || 0) : 0);
      return out.sort((a, b) => a - b);
    },
    gachaRoleDurationPoolLabel(prize) {
      return this.gachaRoleDurationLabel(Number(prize?.roleDurationDays ?? 0));
    },
    gachaRoleDurationOptionsSelect(selectedValues = [0]) {
      const selected = this.gachaNormalizeRoleDurationOptions(selectedValues, 0).map(String);
      return [
        [0, 'ถาวร'], [3, '3 วัน'], [7, '7 วัน'], [14, '14 วัน'], [30, '30 วัน'], [60, '60 วัน'], [90, '90 วัน']
      ].map(([value, label]) => `<option value="${value}"${selected.includes(String(value)) ? ' selected' : ''}>${label}</option>`).join('');
    },
    gachaPickDateInputValue(value) {
      const raw = String(value || '').trim();
      if (!raw) return '';
      return raw.replace(' ', 'T').slice(0, 16);
    },
    gachaPickDateStorageValue(value) {
      const raw = String(value || '').trim();
      if (!raw) return '';
      const normalized = raw.replace('T', ' ');
      return normalized.length === 16 ? `${normalized}:00` : normalized;
    },
    gachaDisplayPickDate(value) {
      const normalized = this.gachaPickDateStorageValue(value);
      const match = normalized.match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/);
      return match ? `${match[3]}/${match[2]}/${match[1]} ${match[4]}:${match[5]}` : '';
    },
    normalizeVisibleCheckboxes(scope = document) {
      const root = $(scope);
      root.find('.ui.checkbox:not(.toggle) input[type="checkbox"]').each((_, input) => {
        input.classList.remove('hidden');
        input.style.display = 'block';
        input.style.visibility = 'visible';
        input.style.position = 'static';
        input.style.left = 'auto';
        input.style.top = 'auto';
        input.style.right = 'auto';
        input.style.bottom = 'auto';
        input.style.opacity = '1';
        input.style.clip = 'auto';
        input.style.clipPath = 'none';
        input.style.overflow = 'visible';
        input.style.webkitAppearance = 'checkbox';
        input.style.appearance = 'auto';
      });
    },
    renderGachaTimeline(timeline) {
      $('#gachaTimeline').html((timeline || []).map((event) => `
        <div class="gacha-timeline-row">
          <span class="gacha-timeline-dot ${this.escAttr(event.status || '')}"></span>
          <strong>${this.esc(event.label || '-')}</strong>
          <span>${this.esc(event.scope || '')}</span>
          <small>${this.esc(event.detail || '')}</small>
        </div>
      `).join('') || this.empty('empty.no_timeline', 'empty.no_timeline_sub'));
    },
    bindGachaPrizeEditor() {
      $('#gachaSaveButton').off('click').on('click', () => this.saveGachaPrizeConfig());
      $('#gachaAddTemplateButton').off('click').on('click', () => this.addGachaTemplate());
      $('[data-gacha-add-prize]').off('click').on('click', (e) => this.addGachaPrize(String($(e.currentTarget).data('gacha-add-prize') || 'item'), String($(e.currentTarget).data('gacha-add-tier') || '')));
      $('[data-gacha-add-role-config]').off('click').on('click', (e) => this.addGachaRoleConfig(String($(e.currentTarget).data('gacha-add-role-config') || '')));
      $('[data-gacha-add-role-entry]').off('click').on('click', (e) => this.addGachaRoleConfigEntry(String($(e.currentTarget).data('gacha-add-role-entry') || '')));
      $('[data-gacha-save-prize]').off('click').on('click', (e) => this.saveGachaPrizeConfig(e.currentTarget));
      $('[data-gacha-save-role-config]').off('click').on('click', (e) => this.saveGachaPrizeConfig(e.currentTarget));
      $('[data-gacha-toggle-prize]').off('click').on('click', (e) => this.toggleGachaPrizeEditor($(e.currentTarget).data('gacha-prize-type'), $(e.currentTarget).data('gacha-toggle-prize')));
      $('[data-gacha-remove]').off('click').on('click', (e) => this.removeGachaPrize(String($(e.currentTarget).data('gacha-remove'))));
      $('[data-gacha-remove-role-config]').off('click').on('click', (e) => this.removeGachaRoleConfig(String($(e.currentTarget).data('gacha-remove-role-config'))));
      $('[data-gacha-remove-role-entry]').off('click').on('click', (e) => this.removeGachaRoleConfigEntry(String($(e.currentTarget).data('gacha-remove-role-entry'))));
      $('[data-gacha-duplicate]').off('click').on('click', (e) => this.duplicateGachaPrize(String($(e.currentTarget).data('gacha-duplicate'))));
      $('[data-prize-upload-id], [data-gacha-role-config-upload-group], [data-gacha-default-icon-upload]').off('change').on('change', (e) => this.uploadGachaPrizeImage(e.currentTarget));
      $('[data-gacha-optional-field]').off('change').on('change', (e) => this.syncGachaPrizeOptionalPanels($(e.currentTarget).closest('.gacha-prize-row, .gacha-role-config-row')));
      $('[data-prize-text-mode]').off('change').on('change', (e) => this.syncGachaPrizeTextPanels($(e.currentTarget).closest('.gacha-prize-row, .gacha-role-config-row')));
      $('[data-gacha-insert-role-token]').off('click').on('click', (e) => this.insertRoleNameToken(e.currentTarget));
      $('[data-gacha-toggle-role-entry]').off('click').on('click', (e) => this.toggleGachaRoleEntryEditor(String($(e.currentTarget).data('gacha-toggle-role-entry') || '')));
      $('[data-role-config-selected-roles]').each((_, selectEl) => {
        const select = $(selectEl);
        select.dropdown('setting', 'onChange', () => {
          const row = select.closest('[data-role-config-id]');
          const raw = select.val();
          const selectedRoleIds = Array.isArray(raw) ? raw : (String(raw || '').trim() ? [String(raw || '').trim()] : []);
          const groupId = String(row.data('role-config-id') || '');
          this.syncGachaRoleConfigSelectedRoles(groupId, selectedRoleIds);
          window.requestAnimationFrame(() => this.openGachaRoleConfigPicker(groupId));
        });
      });
      $('[data-role-config-field]').off('input.gachaRoleConfigPreview change.gachaRoleConfigPreview')
        .on('input.gachaRoleConfigPreview change.gachaRoleConfigPreview', (e) => {
          const row = $(e.currentTarget).closest('[data-role-config-id]');
          this.refreshGachaRoleConfigPreview(row);
          row.find('[data-role-config-entry-id]').each((_, entryNode) => this.refreshGachaRoleEntryPreview(entryNode));
        });
      $('[data-role-child-enabled], [data-role-child-override-toggle]').off('change.gachaRoleChild click.gachaRoleChild')
        .on('change.gachaRoleChild click.gachaRoleChild', (e) => {
          const entry = $(e.currentTarget).closest('[data-role-config-entry-id]');
          window.requestAnimationFrame(() => {
            this.syncGachaRoleChildPanels(entry);
            this.refreshGachaRoleEntryPreview(entry);
          });
        });
      $('[data-role-config-entry-field], [data-role-child-override-value]').off('input.gachaRolePreview change.gachaRolePreview')
        .on('input.gachaRolePreview change.gachaRolePreview', (e) => {
          const entry = $(e.currentTarget).closest('[data-role-config-entry-id]');
          if (entry.length) {
            this.refreshGachaRoleEntryPreview(entry);
          }
        });
      this.bindGachaRateOverviewControls();
      $('#gachaTierGrid [data-tier-field], #gachaItemPrizeList [data-prize-field], #gachaRolePrizeList [data-role-config-field], #gachaRolePrizeList [data-role-config-entry-field], #gachaRolePrizeList [data-role-child-enabled], #gachaRolePrizeList [data-role-child-override-toggle], #gachaRolePrizeList [data-role-child-override-value]')
        .off('change.gachaOverviewSync')
        .on('change.gachaOverviewSync', () => this.renderGachaRateOverview());
      $('.gacha-prize-row[data-prize-id]').off('dragstart dragover drop dragend')
        .on('dragstart', (e) => {
          this.state.gachaDragPrize = {
            id: String($(e.currentTarget).data('prize-id')),
            type: String($(e.currentTarget).data('prize-type') || 'item'),
          };
          e.originalEvent.dataTransfer.effectAllowed = 'move';
        })
        .on('dragover', (e) => {
          e.preventDefault();
          e.originalEvent.dataTransfer.dropEffect = 'move';
        })
        .on('drop', (e) => {
          e.preventDefault();
          const target = $(e.currentTarget);
          const from = this.state.gachaDragPrize;
          const toId = String(target.data('prize-id'));
          const type = String(target.data('prize-type') || 'item');
          if (from?.type !== type) return;
          this.reorderGachaPrize(type, from.id, toId);
        });
      $('.gacha-role-config-row').off('dragstart.gachaRoleConfig dragover.gachaRoleConfig drop.gachaRoleConfig')
        .on('dragstart.gachaRoleConfig', (e) => {
          this.state.gachaDragRoleConfig = {
            id: String($(e.currentTarget).data('role-config-id') || ''),
            tierId: String($(e.currentTarget).data('role-config-tier') || ''),
          };
          e.originalEvent.dataTransfer.effectAllowed = 'move';
        })
        .on('dragover.gachaRoleConfig', (e) => {
          e.preventDefault();
          e.originalEvent.dataTransfer.dropEffect = 'move';
        })
        .on('drop.gachaRoleConfig', (e) => {
          e.preventDefault();
          const target = $(e.currentTarget);
          const from = this.state.gachaDragRoleConfig;
          if (!from?.id || from.tierId !== String(target.data('role-config-tier') || '')) return;
          this.reorderGachaRoleConfig(from.id, String(target.data('role-config-id') || ''));
        });
      $('.gacha-role-config-entry').off('dragstart.gachaRoleEntry dragover.gachaRoleEntry drop.gachaRoleEntry')
        .on('dragstart.gachaRoleEntry', (e) => {
          this.state.gachaDragRoleEntry = {
            prizeId: String($(e.currentTarget).data('role-config-entry-id') || ''),
            groupId: String($(e.currentTarget).data('role-config-id') || ''),
          };
          e.originalEvent.dataTransfer.effectAllowed = 'move';
        })
        .on('dragover.gachaRoleEntry', (e) => {
          e.preventDefault();
          e.originalEvent.dataTransfer.dropEffect = 'move';
        })
        .on('drop.gachaRoleEntry', (e) => {
          e.preventDefault();
          const target = $(e.currentTarget);
          const from = this.state.gachaDragRoleEntry;
          const groupId = String(target.data('role-config-id') || '');
          if (!from?.prizeId || from.groupId !== groupId) return;
          this.reorderGachaRoleEntry(groupId, from.prizeId, String(target.data('role-config-entry-id') || ''));
        });
    },
    collectGachaPrizeConfig() {
      const config = JSON.parse(JSON.stringify(this.state.gachaConfig || {}));
      config.settings = config.settings || {};
      config.settings.enabled = $('#gachaEnabledToggle input[name="enabled"]').is(':checked');
      config.settings.defaultButtonId = Number($('#gachaSettingsForm [name="defaultButtonId"]').val() || 1);
      config.settings.defaultRoleIcon = String($('[data-gacha-default-icon-field="defaultRoleIcon"]').val() || '').trim();
      config.settings.defaultItemIcon = String($('[data-gacha-default-icon-field="defaultItemIcon"]').val() || '').trim();
      config.settings.startingBalances = {
        ticket: Number($('#gachaSettingsForm [name="startingTicket"]').val() || 0),
        coin: Number($('#gachaSettingsForm [name="startingCoin"]').val() || 0),
      };
      config.settings.buttons = config.settings.buttons || {};
      config.settings.buttons['1'] = {
        label: $('#gachaSettingsForm [name="coinLabel"]').val() || 'Coin Spin',
        currency: 'coin',
        cost: Number($('#gachaSettingsForm [name="coinCost"]').val() || 10),
        enabled: $('#gachaSettingsForm [name="coinEnabled"]').is(':checked'),
      };
      config.settings.buttons['2'] = {
        label: $('#gachaSettingsForm [name="ticketLabel"]').val() || 'Ticket Spin',
        currency: 'ticket',
        cost: Number($('#gachaSettingsForm [name="ticketCost"]').val() || 1),
        enabled: $('#gachaSettingsForm [name="ticketEnabled"]').is(':checked'),
      };
      config.conditions = {
        pityEvery: Number($('#gachaConditionForm [name="pityEvery"]').val() || 0),
        pityTierId: $('#gachaConditionForm [name="pityTierId"]').val() || 'mythic',
        quotaWindowDays: Number($('#gachaConditionForm [name="quotaWindowDays"]').val() || 1),
      };

      $('#gachaTierGrid [data-tier-index]').each((_, card) => {
        const index = Number($(card).data('tier-index'));
        const tier = config.tiers[index];
        if (!tier) return;
        $(card).find('[data-tier-field]').each((__, input) => {
          const field = $(input).data('tier-field');
          tier[field] = input.type === 'checkbox' ? input.checked : (['rate', 'displayRate'].includes(field) ? Number(input.value || 0) : input.value);
        });
      });

      const prizesById = new Map((config.prizes || []).map((prize) => [String(prize.id), JSON.parse(JSON.stringify(prize))]));
      const nextPrizes = [];
      const numberFields = new Set(['internalWeight', 'displayWeight', 'roleDurationDays', 'coinPrice', 'displayQuantity']);

      $('#gachaItemPrizeList [data-prize-id]').each((order, card) => {
        const row = $(card);
        const prizeId = String(row.data('prize-id') || '');
        const prize = prizesById.get(prizeId) || { id: prizeId || `prize-${Date.now()}-${order}` };
        prize.type = 'item';
        prize.groupId = '';
        prize.sortOrder = 10 + (order * 10);
        row.find('[data-prize-field]').each((__, input) => {
          const field = String($(input).data('prize-field') || '');
          let value = input.type === 'checkbox' ? input.checked : input.value;
          if (numberFields.has(field)) {
            value = Number(value || 0);
          }
          if (field === 'pickDate') {
            value = this.gachaPickDateStorageValue(value);
          }
          prize[field] = value;
        });
        row.find('[data-gacha-optional-field]').each((__, input) => {
          if ($(input).is(':checked')) return;
          const field = String($(input).data('gacha-optional-field') || '');
          if (field === 'coinPrice') prize.coinPrice = 0;
          if (field === 'pickDate') prize.pickDate = '';
          if (field === 'statusIcon') prize.statusIcon = '';
        });
        ['detail', 'condition'].forEach((textType) => {
          const mode = String(row.find(`[data-prize-text-mode="${textType}"]`).val() || '');
          const text = String(row.find(`[data-prize-text-field="${textType}"]`).val() || '').trim();
          if (textType === 'detail') {
            prize.detailTemplateId = mode === '__custom__' ? '' : mode;
            prize.detailText = mode === '__custom__' ? text : '';
            prize.description = mode === '__custom__' ? text : '';
          } else {
            prize.conditionTemplateId = mode === '__custom__' ? '' : mode;
            prize.conditionText = mode === '__custom__' ? text : '';
          }
        });
        nextPrizes.push(prize);
      });

      $('#gachaRolePrizeList [data-role-config-id]').each((groupOrder, card) => {
        const row = $(card);
        const groupId = String(row.data('role-config-id') || `role-config-${Date.now()}-${groupOrder}`);
        const shared = this.gachaCollectRoleConfigShared(row, config);

        row.find('[data-role-config-entry-id]').each((entryOrder, entryNode) => {
          const entry = $(entryNode);
          const prizeId = String(entry.data('role-config-entry-id') || `prize-${Date.now()}-${groupOrder}-${entryOrder}`);
          const prize = prizesById.get(prizeId) || { id: prizeId };
          const roleChildConfig = this.gachaCollectRoleChildConfig(entry, shared);
          const roleImageOverride = String(entry.find('[data-role-config-entry-field="image"]').val() || '').trim();
          const discordRoleId = String(entry.find('[data-role-config-entry-field="discordRoleId"]').val() || '').trim();
          const effectiveFields = {
            active: this.gachaRoleChildEffectiveValue(shared, roleChildConfig, 'active'),
            visible: this.gachaRoleChildEffectiveValue(shared, roleChildConfig, 'visible'),
            displayQuantityMode: this.gachaRoleChildEffectiveValue(shared, roleChildConfig, 'displayQuantityMode'),
            displayQuantity: this.gachaRoleChildEffectiveValue(shared, roleChildConfig, 'displayQuantity'),
            coinPrice: this.gachaRoleChildEffectiveValue(shared, roleChildConfig, 'coinPrice'),
            internalWeight: this.gachaRoleChildEffectiveValue(shared, roleChildConfig, 'internalWeight'),
            displayWeight: this.gachaRoleChildEffectiveValue(shared, roleChildConfig, 'displayWeight'),
          };
          Object.assign(prize, {
            ...shared,
            ...effectiveFields,
            id: prizeId,
            sortOrder: 100000 + (groupOrder * 100) + (entryOrder * 10),
            name: String(entry.find('[data-role-config-entry-field="name"]').val() || "<roleName>").trim() || '<roleName>',
            shortName: String(entry.find('[data-role-config-entry-field="shortName"]').val() || '').trim(),
            image: roleImageOverride || String(shared.image || '').trim(),
            roleImageOverride,
            discordRoleId,
            roleGroupConfig: { ...shared, groupId: undefined },
            roleChildConfig,
          });
          if (!discordRoleId) {
            prize.image = '';
            prize.roleImageOverride = '';
            prize.active = false;
            prize.visible = false;
            prize.internalWeight = 0;
            prize.displayWeight = 0;
            prize.displayQuantityMode = 'none';
            prize.displayQuantity = 0;
            prize.coinPrice = 0;
          }
          delete prize.roleGroupConfig.groupId;
          delete prize.roleGroupConfig.type;
          nextPrizes.push(prize);
        });
      });

      config.prizes = nextPrizes;

      config.templates = (config.templates || []).map((template) => ({ ...template }));
      $('#gachaPrizeTemplateList [data-gacha-template-index]').each((_, card) => {
        const index = Number($(card).data('gacha-template-index'));
        const template = config.templates[index];
        if (!template) return;
        $(card).find('[data-gacha-template-field]').each((__, input) => {
          const field = $(input).data('gacha-template-field');
          template[field] = input.value;
        });
      });

      config.prizes.sort((a, b) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0));
      return config;
    },
    commitGachaPrizeConfig(config, options = {}) {
      const button = options.button ? $(options.button) : $('#gachaSaveButton');
      const savedPrizeId = String(options.prizeId || '');
      const savedRoleConfigId = String(options.roleConfigId || '');
      const nextExpanded = options.expandedState ? { ...(options.expandedState || {}) } : null;
      button.addClass('loading disabled');
      this.api('/api/admin/gacha_prize.php', { config }, 'POST')
        .done((res) => {
          this.state.gachaConfig = res.config || config;
          if (nextExpanded) {
            this.state.gachaPrizeExpanded = {
              ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
              ...nextExpanded,
            };
          } else if (savedRoleConfigId) {
            this.state.gachaPrizeExpanded = {
              ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
              role: savedRoleConfigId,
            };
          } else if (savedPrizeId) {
            const savedPrize = (this.state.gachaConfig.prizes || []).find((prize) => String(prize.id) === savedPrizeId);
            if (savedPrize) {
              this.state.gachaPrizeExpanded = {
                ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
                [savedPrize.type || 'item']: savedPrizeId,
              };
            }
          }
          this.toast(options.toast || 'toast.gacha_saved');
          this.renderGachaPrizeEditor(res.timeline || []);
        })
        .fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.save_failed')))
        .always(() => button.removeClass('loading disabled'));
    },
    saveGachaPrizeConfig(trigger) {
      const config = this.collectGachaPrizeConfig();
      this.commitGachaPrizeConfig(config, {
        button: trigger || null,
        prizeId: trigger ? String($(trigger).data('gacha-save-prize') || '') : '',
        roleConfigId: trigger ? String($(trigger).data('gacha-save-role-config') || '') : '',
      });
    },
    toggleGachaPrizeEditor(type, prizeId) {
      this.state.gachaConfig = this.collectGachaPrizeConfig();
      const expanded = this.state.gachaPrizeExpanded || { item: '', role: '' };
      expanded[type] = expanded[type] === prizeId ? '' : prizeId;
      this.state.gachaPrizeExpanded = expanded;
      this.renderGachaPrizeLists();
      this.bindGachaPrizeEditor();
    },
    addGachaPrize(type = 'item', tierId = '') {
      if (type === 'role') {
        this.addGachaRoleConfig(tierId);
        return;
      }
      const config = this.collectGachaPrizeConfig();
      tierId = tierId || config.tiers?.[0]?.id || 'common';
      const sameTypeCount = (config.prizes || []).filter((prize) => (prize.type || 'item') === type).length;
      config.prizes.push({
        id: 'prize-' + Date.now(),
        tierId,
        type,
        name: type === 'role' ? '<roleName>' : 'New Gachapon Prize',
        shortName: '',
        image: type === 'role' ? 'images/icon_roles_blank.png' : 'images/item-1.png',
        description: '',
        detailTemplateId: '',
        detailText: '',
        conditionTemplateId: '',
        conditionText: '',
        badge: '',
        statusIcon: '',
        pickDate: '',
        expireAction: 'ended',
        coinPrice: 0,
        displayQuantityMode: 'none',
        displayQuantity: 0,
        internalWeight: 1,
        displayWeight: 1,
        active: true,
        visible: true,
        sortOrder: (type === 'role' ? 100000 : 10) + ((sameTypeCount + 1) * 10),
        discordRoleId: '',
        roleDurationDays: 0,
        roleDurationOptions: [0],
      });
      this.state.gachaConfig = config;
      this.state.gachaPrizeExpanded = {
        ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
        [type]: config.prizes[config.prizes.length - 1].id,
      };
      this.renderGachaPrizeLists();
      this.bindGachaPrizeEditor();
    },
    addGachaRoleConfig(tierId = '') {
      const config = this.collectGachaPrizeConfig();
      const resolvedTierId = tierId || config.tiers?.[0]?.id || 'common';
      const groupId = `role-config-${Date.now()}`;
      const sameTypeCount = (config.prizes || []).filter((prize) => (prize.type || 'item') === 'role').length;
      const roleChildConfig = {
        enabled: false,
        overrides: this.gachaRoleChildOverrideDefaults({}),
      };
      const roleGroupConfig = {
        tierId: resolvedTierId,
        badge: '',
        roleDurationDays: 0,
        roleDurationOptions: [0],
        image: '',
        detailTemplateId: '',
        detailText: '',
        description: '',
        conditionTemplateId: '',
        conditionText: '',
        statusIcon: '',
        pickDate: '',
        expireAction: 'ended',
        coinPrice: 0,
        displayQuantityMode: 'none',
        displayQuantity: 0,
        internalWeight: 1,
        displayWeight: 1,
        active: true,
        visible: true,
      };
      config.prizes.push({
        id: `prize-${Date.now()}`,
        tierId: resolvedTierId,
        type: 'role',
        groupId,
        name: '<roleName>',
        shortName: '',
        image: '',
        roleImageOverride: '',
        description: '',
        detailTemplateId: '',
        detailText: '',
        conditionTemplateId: '',
        conditionText: '',
        badge: '',
        statusIcon: '',
        pickDate: '',
        expireAction: 'ended',
        coinPrice: 0,
        displayQuantityMode: 'none',
        displayQuantity: 0,
        internalWeight: 1,
        displayWeight: 1,
        active: true,
        visible: true,
        sortOrder: 100000 + ((sameTypeCount + 1) * 10),
        discordRoleId: '',
        roleDurationDays: 0,
        roleDurationOptions: [0],
        roleGroupConfig,
        roleChildConfig,
      });
      this.state.gachaConfig = config;
      this.state.gachaPrizeExpanded = {
        ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
        role: groupId,
      };
      const newPrize = config.prizes[config.prizes.length - 1];
      this.state.gachaRoleEntryExpanded = {
        ...(this.state.gachaRoleEntryExpanded || {}),
        [newPrize.id]: true,
      };
      this.renderGachaPrizeLists();
      this.bindGachaPrizeEditor();
    },
    addGachaRoleConfigEntry(groupId) {
      this.openGachaRoleConfigPicker(groupId);
    },
    addGachaTemplate() {
      const config = this.collectGachaPrizeConfig();
      config.templates = Array.isArray(config.templates) ? config.templates : [];
      config.templates.push({
        id: `template-${Date.now()}`,
        type: 'detail',
        name: '',
        content: '',
      });
      this.state.gachaConfig = config;
      this.renderGachaPrizeTemplates();
      this.bindGachaPrizeEditor();
    },
    removeGachaPrize(prizeId) {
      if (!confirm('Remove this Gachapon prize from the config?')) return;
      const config = this.collectGachaPrizeConfig();
      config.prizes = (config.prizes || []).filter((prize) => String(prize.id) !== String(prizeId));
      const expanded = { ...(this.state.gachaPrizeExpanded || { item: '', role: '' }) };
      if (expanded.item === String(prizeId)) {
        expanded.item = '';
      }
      if (expanded.role === String(prizeId)) {
        expanded.role = '';
      }
      this.commitGachaPrizeConfig(config, { expandedState: expanded });
    },
    removeGachaRoleConfig(groupId, skipConfirm = false) {
      if (!skipConfirm && !confirm('Remove this Role Config and all roles inside it?')) return;
      const config = this.collectGachaPrizeConfig();
      config.prizes = (config.prizes || []).filter((prize) => this.gachaRoleConfigGroupId(prize) !== String(groupId));
      this.commitGachaPrizeConfig(config, {
        expandedState: {
          ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
          role: '',
        },
      });
    },
    removeGachaRoleConfigEntry(prizeId) {
      const config = this.collectGachaPrizeConfig();
      const prize = (config.prizes || []).find((entry) => String(entry.id) === String(prizeId));
      if (!prize) return;
      const groupId = this.gachaRoleConfigGroupId(prize);
      const groupCount = (config.prizes || []).filter((entry) => (entry.type || 'item') === 'role' && this.gachaRoleConfigGroupId(entry) === groupId).length;
      if (groupCount <= 1) {
        this.removeGachaRoleConfig(groupId, true);
        return;
      }
      config.prizes = (config.prizes || []).filter((entry) => String(entry.id) !== String(prizeId));
      this.commitGachaPrizeConfig(config, {
        expandedState: {
          ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
          role: groupId,
        },
      });
    },
    duplicateGachaPrize(prizeId) {
      const config = this.collectGachaPrizeConfig();
      const clone = JSON.parse(JSON.stringify((config.prizes || []).find((prize) => String(prize.id) === String(prizeId)) || {}));
      clone.id = (clone.id || 'prize') + '-copy-' + Date.now();
      clone.name = (clone.name || 'Prize') + ' Copy';
      clone.sortOrder = (config.prizes.length + 1) * 10;
      config.prizes.push(clone);
      this.state.gachaConfig = config;
      this.state.gachaPrizeExpanded = {
        ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
        [clone.type || 'item']: clone.id,
      };
      this.renderGachaPrizeLists();
      this.bindGachaPrizeEditor();
    },
    changeGachaPrizeType(prizeId, targetType) {
      const config = this.collectGachaPrizeConfig();
      const prize = (config.prizes || []).find((entry) => String(entry.id) === String(prizeId));
      if (!prize || !['item', 'role'].includes(targetType) || prize.type === targetType) return;
      prize.type = targetType;
      prize.groupId = targetType === 'role' ? (String(prize.groupId || '').trim() || String(prize.id || '')) : '';
      if (targetType === 'role') {
        if (String(prize.image || '').trim() === 'images/item-1.png') {
          prize.image = '';
        }
        prize.roleImageOverride = String(prize.image || '').trim();
        prize.roleGroupConfig = this.gachaRoleGroupConfig(prize);
        prize.roleChildConfig = {
          enabled: false,
          overrides: this.gachaRoleChildOverrideDefaults(prize.roleGroupConfig),
        };
      } else if (targetType === 'item') {
        delete prize.roleGroupConfig;
        delete prize.roleChildConfig;
        delete prize.roleImageOverride;
      }
      this.state.gachaConfig = config;
      this.state.gachaPrizeExpanded = {
        ...(this.state.gachaPrizeExpanded || { item: '', role: '' }),
        item: targetType === 'item' ? prize.id : '',
        role: targetType === 'role' ? this.gachaRoleConfigGroupId(prize) : '',
      };
      this.renderGachaPrizeLists();
      this.bindGachaPrizeEditor();
    },
    reorderGachaPrize(type, fromId, toId) {
      if (!fromId || !toId || fromId === toId) return;
      const config = this.collectGachaPrizeConfig();
      const rows = (config.prizes || [])
        .filter((prize) => (prize.type || 'item') === type)
        .sort((a, b) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0));
      const from = rows.findIndex((prize) => String(prize.id) === String(fromId));
      const to = rows.findIndex((prize) => String(prize.id) === String(toId));
      if (from === -1 || to === -1) return;
      const [moved] = rows.splice(from, 1);
      rows.splice(to, 0, moved);
      const base = type === 'item' ? 10 : 100000;
      rows.forEach((row, index) => { row.sortOrder = base + (index * 10); });
      this.state.gachaConfig = config;
      this.renderGachaPrizeLists();
      this.bindGachaPrizeEditor();
    },
    reorderGachaRoleConfig(fromGroupId, toGroupId) {
      if (!fromGroupId || !toGroupId || fromGroupId === toGroupId) return;
      const config = this.collectGachaPrizeConfig();
      const groups = this.gachaRoleConfigGroups((config.prizes || []).filter((prize) => (prize.type || 'item') === 'role'));
      const from = groups.findIndex((group) => String(group.id) === String(fromGroupId));
      const to = groups.findIndex((group) => String(group.id) === String(toGroupId));
      if (from === -1 || to === -1) return;
      const [moved] = groups.splice(from, 1);
      groups.splice(to, 0, moved);
      const rolePrizeMap = new Map((config.prizes || []).filter((prize) => (prize.type || 'item') === 'role').map((prize) => [String(prize.id), prize]));
      let cursor = 100000;
      groups.forEach((group) => {
        group.prizes.forEach((prize, entryIndex) => {
          const target = rolePrizeMap.get(String(prize.id));
          if (target) {
            target.sortOrder = cursor + (entryIndex * 10);
          }
        });
        cursor += Math.max(group.prizes.length, 1) * 100;
      });
      this.state.gachaConfig = config;
      this.renderGachaPrizeLists();
      this.bindGachaPrizeEditor();
    },
    reorderGachaRoleEntry(groupId, fromPrizeId, toPrizeId) {
      if (!groupId || !fromPrizeId || !toPrizeId || fromPrizeId === toPrizeId) return;
      const config = this.collectGachaPrizeConfig();
      const groups = this.gachaRoleConfigGroups((config.prizes || []).filter((prize) => (prize.type || 'item') === 'role'));
      const group = groups.find((entry) => String(entry.id) === String(groupId));
      if (!group) return;
      const from = group.prizes.findIndex((prize) => String(prize.id) === String(fromPrizeId));
      const to = group.prizes.findIndex((prize) => String(prize.id) === String(toPrizeId));
      if (from === -1 || to === -1) return;
      const [moved] = group.prizes.splice(from, 1);
      group.prizes.splice(to, 0, moved);
      const rolePrizeMap = new Map((config.prizes || []).filter((prize) => (prize.type || 'item') === 'role').map((prize) => [String(prize.id), prize]));
      let cursor = 100000;
      groups.forEach((entry) => {
        entry.prizes.forEach((prize, entryIndex) => {
          const target = rolePrizeMap.get(String(prize.id));
          if (target) {
            target.sortOrder = cursor + (entryIndex * 10);
          }
        });
        cursor += Math.max(entry.prizes.length, 1) * 100;
      });
      this.state.gachaConfig = config;
      this.renderGachaPrizeLists();
      this.bindGachaPrizeEditor();
    },
    uploadGachaPrizeImage(input) {
      const file = input.files?.[0];
      const prizeId = String($(input).data('prize-upload-id'));
      const groupId = String($(input).data('gacha-role-config-upload-group') || '');
      const defaultIconField = String($(input).data('gacha-default-icon-upload') || '');
      const field = String($(input).data('prize-upload-field') || $(input).data('gacha-role-config-upload-field') || 'image');
      if (!file) return;
      const formData = new FormData();
      formData.append('image', file);
      this.apiForm('/api/admin/gacha_upload.php', formData)
        .done((res) => {
          const config = this.collectGachaPrizeConfig();
          if (defaultIconField) {
            config.settings = {
              ...(config.settings || {}),
              [defaultIconField]: res.path,
            };
            $(`[data-gacha-default-icon-field="${this.escAttr(defaultIconField)}"]`).val(res.path);
          } else if (groupId) {
            (config.prizes || [])
              .filter((entry) => (entry.type || 'item') === 'role' && this.gachaRoleConfigGroupId(entry) === groupId)
              .forEach((entry) => {
                entry.roleGroupConfig = {
                  ...(entry.roleGroupConfig || {}),
                  [field]: res.path,
                };
                if (field === 'image') {
                  if (!String(entry.roleImageOverride || '').trim()) {
                    entry.image = res.path;
                  }
                } else {
                  entry[field] = res.path;
                }
              });
          } else {
            const prize = (config.prizes || []).find((entry) => String(entry.id) === prizeId);
            if (prize) {
              if (field === 'image' && (prize.type || 'item') === 'role') {
                prize.roleImageOverride = res.path;
                prize.image = res.path;
              } else {
                prize[field] = res.path;
              }
            }
          }
          this.state.gachaAssets = [{
            path: res.path,
            name: String(res.path || '').split('/').pop(),
            url: res.url,
            kind: field === 'statusIcon' ? 'icon' : 'image',
            modifiedAt: Math.floor(Date.now() / 1000),
            isUploaded: true,
          }].concat((this.state.gachaAssets || []).filter((asset) => String(asset.path || '') !== String(res.path || '')));
          this.state.gachaConfig = config;
          this.renderGachaPrizeLists();
          this.bindGachaPrizeEditor();
          this.toast('toast.gacha_image_uploaded');
        })
        .fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.upload_failed')));
    },
    insertRoleNameToken(button) {
      const row = $(button).closest('[data-prize-id], [data-role-config-entry-id]');
      const input = row.find('[data-prize-field="name"], [data-role-config-entry-field="name"]')[0];
      if (!input) return;
      const token = '<roleName>';
      const start = input.selectionStart ?? String(input.value || '').length;
      const end = input.selectionEnd ?? start;
      input.value = `${input.value.slice(0, start)}${token}${input.value.slice(end)}`;
      input.focus();
      const cursor = start + token.length;
      if (input.setSelectionRange) input.setSelectionRange(cursor, cursor);
      if (row.is('[data-role-config-entry-id]')) {
        this.refreshGachaRoleEntryPreview(row);
      }
    },
    gachaAssetUrl(path) {
      path = String(path || 'images/item-1.png');
      if (/^https?:\/\//.test(path) || path.startsWith('/')) return path;
      if (path.startsWith('images/') || path.startsWith('uploads/')) return `${this.baseUrl}/gacha/${path}`;
      return `${this.baseUrl}/gacha/images/${path}`;
    },
    loadAdmin() {
      this.api('/api/data/admin.php').done((res) => {
        this.updateSystemState(res.system || {});
        this.metrics('#adminMetrics', [
          ['Workers', res.metrics.workers],
          ['Queued Jobs', res.metrics.queuedJobs],
          ['Running Jobs', res.metrics.runningJobs],
          ['Stale Running', res.metrics.staleRunningJobs],
          ['Raw Events 24h', res.metrics.rawEvents24h],
          ['Errors', res.metrics.errors],
          ['Active Viewers', res.metrics.activeViewers],
        ]);
        this.table('#adminWorkerTable', [
          ['Worker', 'workerName'],
          ['State', 'heartbeatDate', (r) => this.workerStateBadge(r)],
          ['Heartbeat', 'heartbeatDate', (r) => this.liveStamp(r.heartbeatDate, { freshSeconds: 20, staleSeconds: 180, pulse: true })],
          ['Meta', 'metadataJson', (r) => this.jsonSummary(r.metadataJson)],
        ], res.workers);
        this.table('#adminJobTable', [
          ['#', 'syncJobId'],
          ['Type', 'jobType'],
          ['Status', 'jobStatus', (r) => this.jobStatusBadge(r.jobStatus)],
          ['Created', 'createDate', (r) => this.liveStamp(r.createDate)],
          ['Error', 'errorMessage', (r) => this.trunc(r.errorMessage || '', 80)],
        ], res.jobs);
        this.table('#adminRawEventTable', [['#', 'rawEventId'], ['Event', 'eventType'], ['Status', 'processStatus'], ['Date', 'createDate']], res.rawEvents);
        this.table('#adminErrorTable', [['#', 'ingestErrorId'], ['Type', 'errorType'], ['Error', 'errorMessage', (r) => this.trunc(r.errorMessage || '', 100)], ['Date', 'createDate']], res.ingestErrors);
        this.table('#adminDashboardUserTable', [['#', 'adminUserId'], ['Name', 'displayName'], ['Discord', 'discordUserName'], ['Role', 'roleName'], ['Active', 'isActive']], res.dashboardUsers);
        this.table('#adminRateLimitTable', [['Route', 'routeKey'], ['Method', 'httpMethod'], ['Remaining', 'remainingCount'], ['Reset', 'resetDate'], ['Updated', 'updateDate']], res.rateLimits);
      });
    },
    loadPermission() {
      this.api('/api/data/permission.php').done((res) => {
        this.state.permissionTiers = JSON.parse(JSON.stringify(res.tiers || []));
        this.state.permissionCatalog = res.permissionCatalog || [];
        this.renderPermissionTiers();
        this.table('#permissionUsersTable', [
          ['#', 'adminUserId'],
          ['User ID', 'discordUserId'],
          ['Name', 'displayName'],
          ['Role', 'roleName'],
          ['Active', 'isActive', (row) => Number(row.isActive) === 1 ? this.badge('active', 'success') : this.badge('inactive', 'failed')],
          ['Updated', 'updateDate'],
        ], res.adminUsers || [], { id: 'permissionUsersTable' });
        $('#permissionSaveButton').off('click').on('click', () => this.savePermissionTiers());
        $('#permissionAddTierButton').off('click').on('click', () => this.addPermissionTier());
      }).fail((xhr) => this.error($('#pageContainer'), xhr.responseJSON?.message || 'โหลด Permission ไม่สำเร็จ'));
    },
    renderPermissionTiers() {
      const catalog = this.state.permissionCatalog || [];
      $('#permissionTierList').html((this.state.permissionTiers || []).map((tier, index) => {
        const permissions = new Set(tier.permissions || []);
        const permissionChecks = catalog.map((permission) => `
          <label class="ui checkbox orbit-permission-toggle">
            <input type="checkbox" data-permission-code="${this.escAttr(permission.code)}" ${permissions.has(permission.code) ? 'checked' : ''}>
            <span>${this.esc(permission.label || permission.code)}</span>
          </label>
        `).join('');
        const userChips = ((tier.users || [])).map((user) => `
          <span class="orbit-picker-chip">
            <img src="${this.escAttr(user.avatarUrl || '')}" alt="">
            <span><strong>${this.esc(user.displayName || user.userId)}</strong><span>${this.esc(user.userId || '')}</span></span>
            <button type="button" data-permission-remove-user="${this.escAttr(user.userId || '')}" data-permission-tier-index="${index}"><i class="fa-solid fa-xmark"></i></button>
          </span>
        `).join('') || '<span class="orbit-muted">ยังไม่มีผู้ใช้ใน group นี้</span>';
        const searchResults = ((tier.searchResults || [])).map((user, resultIndex) => `
          <button class="orbit-picker-option" type="button" data-permission-add-user="${index}:${resultIndex}">
            <img src="${this.escAttr(user.avatarUrl || '')}" alt="">
            <span><strong>${this.esc(user.displayName || user.userId)}</strong><span>${this.esc(user.userName ? '@' + user.userName : user.userId || '')}</span></span>
          </button>
        `).join('');
        const removable = tier.id !== 'owner';
        return `
          <article class="orbit-earn-rule" data-permission-tier-index="${index}">
            <div class="orbit-earn-rule-head">
              <div><strong>${this.esc(tier.name || tier.id)}</strong><span>${this.esc(tier.id || '')}</span></div>
              <div class="orbit-earn-rule-actions">
                ${removable ? `<button class="ui tiny basic button" type="button" data-permission-remove-tier="${index}"><i class="fa-solid fa-trash"></i> Remove</button>` : `<span class="orbit-json-chip">protected</span>`}
              </div>
            </div>
            <div class="ui small form">
              <div class="two fields">
                <div class="field"><label>Group Name</label><input data-permission-tier-field="name" value="${this.escAttr(tier.name || '')}"></div>
                <div class="field"><label>Group ID</label><input data-permission-tier-field="id" value="${this.escAttr(tier.id || '')}" ${tier.id === 'owner' ? 'readonly' : ''}></div>
              </div>
              <div class="two fields">
                <div class="field">
                  <label>Search User</label>
                  <div class="ui action input">
                    <input data-permission-user-search placeholder="Discord userId หรือชื่อ" value="${this.escAttr(tier.searchQuery || '')}">
                    <button class="ui button" type="button" data-permission-search-tier="${index}"><i class="fa-solid fa-magnifying-glass"></i></button>
                  </div>
                </div>
                <div class="field">
                  <label>Selected Users</label>
                  <div class="orbit-user-chip-list">${userChips}</div>
                </div>
              </div>
              <div class="orbit-picker-results">${searchResults || '<span class="orbit-muted">ค้นหาแล้วกดเพิ่มคนเข้า group</span>'}</div>
              <div class="orbit-compact-grid">${permissionChecks}</div>
            </div>
          </article>
        `;
      }).join(''));
      $('#permissionTierList .ui.checkbox').checkbox();
      this.normalizeVisibleCheckboxes('#permissionTierList');
      $('#permissionTierList [data-permission-search-tier]').off('click').on('click', (event) => this.searchPermissionTierUsers(Number($(event.currentTarget).data('permission-search-tier'))));
      $('#permissionTierList [data-permission-user-search]').off('keydown').on('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        this.searchPermissionTierUsers(Number($(event.currentTarget).closest('[data-permission-tier-index]').data('permission-tier-index')));
      });
      $('#permissionTierList [data-permission-add-user]').off('click').on('click', (event) => {
        const [tierIndex, resultIndex] = String($(event.currentTarget).data('permission-add-user') || '').split(':').map((value) => Number(value || 0));
        this.addPermissionTierUser(tierIndex, resultIndex);
      });
      $('#permissionTierList [data-permission-remove-user]').off('click').on('click', (event) => {
        this.removePermissionTierUser(
          Number($(event.currentTarget).data('permission-tier-index')),
          String($(event.currentTarget).data('permission-remove-user') || '')
        );
      });
      $('#permissionTierList [data-permission-remove-tier]').off('click').on('click', (event) => this.removePermissionTier(Number($(event.currentTarget).data('permission-remove-tier'))));
    },
    syncPermissionTierDrafts() {
      this.state.permissionTiers = (this.state.permissionTiers || []).map((tier, index) => {
        const row = $(`#permissionTierList [data-permission-tier-index="${index}"]`);
        if (!row.length) return tier;
        const permissions = [];
        row.find('[data-permission-code]').each((_, input) => {
          if ($(input).is(':checked')) permissions.push(String($(input).data('permission-code') || ''));
        });
        return {
          ...tier,
          id: String(row.find('[data-permission-tier-field="id"]').val() || tier.id || ''),
          name: String(row.find('[data-permission-tier-field="name"]').val() || tier.name || ''),
          permissions,
          searchQuery: String(row.find('[data-permission-user-search]').val() || tier.searchQuery || ''),
        };
      });
    },
    addPermissionTier() {
      this.syncPermissionTierDrafts();
      const stamp = Date.now().toString(36);
      this.state.permissionTiers.push({
        id: `group_${stamp}`,
        name: 'New Group',
        permissions: ['admin.view'],
        userIds: [],
        users: [],
        searchResults: [],
        searchQuery: '',
      });
      this.renderPermissionTiers();
    },
    removePermissionTier(index) {
      this.syncPermissionTierDrafts();
      const tier = this.state.permissionTiers[index];
      if (!tier || String(tier.id || '') === 'owner') return;
      this.state.permissionTiers.splice(index, 1);
      this.renderPermissionTiers();
    },
    searchPermissionTierUsers(index) {
      this.syncPermissionTierDrafts();
      const tier = this.state.permissionTiers[index];
      if (!tier) return;
      const q = String(tier.searchQuery || '').trim();
      if (q.length < 2) {
        tier.searchResults = [];
        this.renderPermissionTiers();
        return;
      }
      this.lookupDirectoryMembers(q).done((rows) => {
        tier.searchResults = rows;
        this.renderPermissionTiers();
      }).fail((xhr) => this.toast(xhr.responseJSON?.message || 'ค้นหาผู้ใช้ไม่สำเร็จ'));
    },
    addPermissionTierUser(index, resultIndex) {
      this.syncPermissionTierDrafts();
      const tier = this.state.permissionTiers[index];
      const user = tier?.searchResults?.[resultIndex];
      if (!tier || !user?.userId) return;
      (this.state.permissionTiers || []).forEach((entry) => {
        entry.userIds = (entry.userIds || []).filter((userId) => String(userId) !== String(user.userId));
        entry.users = (entry.users || []).filter((item) => String(item.userId) !== String(user.userId));
      });
      tier.userIds = Array.from(new Set([...(tier.userIds || []), String(user.userId)]));
      tier.users = [...(tier.users || []).filter((item) => String(item.userId) !== String(user.userId)), user];
      tier.searchResults = [];
      tier.searchQuery = '';
      this.renderPermissionTiers();
    },
    removePermissionTierUser(index, userId) {
      this.syncPermissionTierDrafts();
      const tier = this.state.permissionTiers[index];
      if (!tier) return;
      tier.userIds = (tier.userIds || []).filter((value) => String(value) !== String(userId));
      tier.users = (tier.users || []).filter((user) => String(user.userId) !== String(userId));
      this.renderPermissionTiers();
    },
    collectPermissionTiers() {
      this.syncPermissionTierDrafts();
      return (this.state.permissionTiers || []).map((tier) => ({
        id: String(tier.id || '').trim(),
        name: String(tier.name || '').trim(),
        userIds: Array.from(new Set((tier.userIds || []).map((userId) => String(userId)).filter(Boolean))),
        permissions: Array.from(new Set((tier.permissions || []).map((permission) => String(permission)).filter(Boolean))),
      }));
    },
    savePermissionTiers() {
      const tiers = this.collectPermissionTiers();
      this.api('/api/admin/permission.php', { tiers }, 'POST')
        .done((res) => {
          this.state.permissionTiers = JSON.parse(JSON.stringify(res.tiers || []));
          $('#permissionResult').html(this.jsonSummary({ tiers: this.state.permissionTiers.length }));
          this.loadPermission();
          this.toast('toast.permission_saved');
        })
        .fail((xhr) => this.toast(xhr.responseJSON?.message || 'Permission save failed'));
    },
    loadBackfill() {
      this.api('/api/data/backfill.php').done((res) => {
        this.updateSystemState(res.system || {});
        this.metrics('#backfillMetrics', [
          ['Catalog', res.metrics.catalogItems],
          ['Cursors', res.metrics.cursorCount],
          ['Queued Jobs', res.metrics.queuedJobs],
          ['Running', res.metrics.runningJobs],
          ['Stale Running', res.metrics.staleRunningJobs],
          ['Errors', res.metrics.ingestErrors],
          ['Files Queued', res.metrics.queuedAttachments],
        ]);
        const catalog = res.backfill.catalog || {};
        const cursors = res.backfill.cursors || [];
        const rows = Object.keys(catalog).map((key) => {
          const cursor = cursors.find(c => c.cursorType === key);
          return { key, ...catalog[key], cursorValue: cursor?.cursorValue || '-', updateDate: cursor?.updateDate || '-', metadata: cursor?.metadata || {} };
        });
        this.table('#backfillTable', [
          ['Type', 'key'],
          ['Scope', 'scope'],
          ['State', 'cursorValue', (r) => this.backfillStateBadge(r)],
          ['Cursor', 'cursorValue'],
          ['Updated', 'updateDate', (r) => this.liveStamp(r.updateDate)],
          ['Actions', 'key', (r) => `
            <button class="ui tiny primary button" data-backfill-command="run" data-backfill-type="${r.key}" data-tooltip="รันงานนี้ต่อจาก cursor ล่าสุด ถ้าข้อมูลเก่าครบแล้วจะข้ามส่วนที่ done">Run</button>
            <button class="ui tiny button" data-backfill-command="force" data-backfill-type="${r.key}" data-tooltip="บังคับรันซ้ำแม้ cursor จะบอกว่าครบแล้ว เหมาะกับการตรวจซ้ำหลังแก้ parser">Force</button>
            <button class="ui tiny button" data-backfill-command="reset" data-backfill-type="${r.key}" data-tooltip="ล้าง cursor ของงานนี้เท่านั้น ไม่ลบข้อมูล แต่รอบถัดไปจะเริ่มไล่ backfill ใหม่">Reset Cursor</button>
            ${this.infoTip(`งาน ${r.key}: ${r.scope || 'ใช้สำหรับ backfill/rebuild ข้อมูลส่วนนี้'}`)}
          `]
        ], rows, { id: 'backfillTable' });
        this.bindCommon();
        this.table('#backfillWorkerTable', [
          ['Worker', 'workerName'],
          ['State', 'heartbeatDate', (r) => this.workerStateBadge(r)],
          ['Heartbeat', 'heartbeatDate', (r) => this.liveStamp(r.heartbeatDate, { freshSeconds: 20, staleSeconds: 180, pulse: true })],
          ['Meta', 'metadataJson', (r) => this.jsonSummary(r.metadataJson)],
        ], res.workers);
        this.table('#backfillJobTable', [
          ['#', 'syncJobId'],
          ['Type', 'jobType'],
          ['Status', 'jobStatus', (r) => this.jobStatusBadge(r.jobStatus)],
          ['Created', 'createDate', (r) => this.liveStamp(r.createDate)],
          ['Started', 'startDate', (r) => this.liveStamp(r.startDate, { pulse: String(r.jobStatus || '').toLowerCase() === 'running' })],
          ['Finished', 'finishDate', (r) => this.liveStamp(r.finishDate)],
          ['Error', 'errorMessage', (r) => this.trunc(r.errorMessage || '', 90)],
        ], res.jobs);
      });
    },
    loadLogs() {
      this.api('/api/data/logs.php', this.formParams('#logsFilter')).done((res) => {
        this.updateSystemState(res.system || {});
        this.table('#logsStaffTable', [['#', 'adminActionId'], ['Staff', 'displayName'], ['Action', 'actionType'], ['Target', 'targetId'], ['Stage', 'actionStage'], ['Status', 'status', (r) => this.badge(r.status, r.status)], ['IP', 'ipAddress'], ['Date', 'createDate']], res.staffLogs, { id: 'logsStaffTable', rowClick: (row) => this.openStaffLogDetail(row) });
        this.table('#logsAccessTable', [['#', 'accessLogId'], ['Staff', 'displayName'], ['Event', 'eventType'], ['Target', 'targetId'], ['Level', 'sensitivityLevel'], ['IP', 'ipAddress'], ['Date', 'createDate']], res.accessLogs, { id: 'logsAccessTable', rowClick: (row) => this.openAccessLogDetail(row) });
        this.table('#logsAuditCompareTable', [['Action', 'adminActionId'], ['Panel Admin', 'panelAdminUserId'], ['Discord Executor', 'discordExecutorUserId'], ['Status', 'status', (r) => this.badge(r.status || 'unknown', r.status)], ['Reason', 'reason', (r) => this.trunc(r.reason || r.discordAuditReason || '', 120)]], res.auditCompare);
      });
    },
    parseJsonMaybe(value) {
      if (!value) return null;
      if (typeof value === 'object') return value;
      try { return JSON.parse(String(value)); } catch (error) { return null; }
    },
    flattenObjectDiff(value, prefix = '') {
      if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return { [prefix || 'value']: value };
      }
      return Object.entries(value).reduce((out, [key, child]) => {
        const path = prefix ? `${prefix}.${key}` : key;
        return Object.assign(out, this.flattenObjectDiff(child, path));
      }, {});
    },
    jsonDiffRows(beforeValue, afterValue) {
      const before = this.flattenObjectDiff(beforeValue || {});
      const after = this.flattenObjectDiff(afterValue || {});
      const keys = [...new Set([...Object.keys(before), ...Object.keys(after)])].sort();
      return keys.filter((key) => JSON.stringify(before[key]) !== JSON.stringify(after[key])).slice(0, 200).map((key) => ({
        key,
        before: before[key],
        after: after[key],
      }));
    },
    openStaffLogDetail(row) {
      const before = this.parseJsonMaybe(row.beforeJson);
      const after = this.parseJsonMaybe(row.afterJson);
      const request = this.parseJsonMaybe(row.requestPayloadJson);
      const response = this.parseJsonMaybe(row.responsePayloadJson);
      const diffRows = this.jsonDiffRows(before, after);
      const diffHtml = diffRows.length
        ? `<table class="ui very basic table orbit-table"><thead><tr><th>Path</th><th>Before</th><th>After</th></tr></thead><tbody>${diffRows.map((entry) => `<tr><td>${this.esc(entry.key)}</td><td><pre class="orbit-json-summary">${this.esc(JSON.stringify(entry.before, null, 2))}</pre></td><td><pre class="orbit-json-summary">${this.esc(JSON.stringify(entry.after, null, 2))}</pre></td></tr>`).join('')}</tbody></table>`
        : '<div class="orbit-muted">ไม่มี diff หรือ action นี้ไม่ได้บันทึก before/after</div>';
      this.drawer(`Staff Log #${row.adminActionId || ''}`, `
        <div class="orbit-compact-grid">
          <div class="orbit-metric"><div class="orbit-muted">Action</div><div class="orbit-metric-value" style="font-size:18px">${this.esc(row.actionType || '-')}</div></div>
          <div class="orbit-metric"><div class="orbit-muted">Status</div><div>${this.badge(row.status || 'unknown', row.status)}</div></div>
          <div class="orbit-metric"><div class="orbit-muted">Target</div><div class="orbit-metric-value" style="font-size:18px">${this.esc(row.targetType || '-')} / ${this.esc(row.targetId || '-')}</div></div>
          <div class="orbit-metric"><div class="orbit-muted">Staff</div><div class="orbit-metric-value" style="font-size:18px">${this.esc(row.displayName || row.discordUserId || '-')}</div></div>
        </div>
        <h4 class="ui header">Diff</h4>${diffHtml}
        <h4 class="ui header">Request</h4><pre class="orbit-json">${this.esc(JSON.stringify(request || {}, null, 2))}</pre>
        <h4 class="ui header">Response</h4><pre class="orbit-json">${this.esc(JSON.stringify(response || {}, null, 2))}</pre>
      `);
    },
    openAccessLogDetail(row) {
      const metadata = this.parseJsonMaybe(row.metadataJson);
      const request = this.parseJsonMaybe(row.requestPayloadJson);
      this.drawer(`Access Log #${row.accessLogId || ''}`, `
        <div class="orbit-compact-grid">
          <div class="orbit-metric"><div class="orbit-muted">Event</div><div class="orbit-metric-value" style="font-size:18px">${this.esc(row.eventType || '-')}</div></div>
          <div class="orbit-metric"><div class="orbit-muted">Target</div><div class="orbit-metric-value" style="font-size:18px">${this.esc(row.targetType || '-')} / ${this.esc(row.targetId || '-')}</div></div>
          <div class="orbit-metric"><div class="orbit-muted">Level</div><div class="orbit-metric-value" style="font-size:18px">${this.esc(row.sensitivityLevel || '-')}</div></div>
          <div class="orbit-metric"><div class="orbit-muted">Staff</div><div class="orbit-metric-value" style="font-size:18px">${this.esc(row.displayName || row.discordUserId || '-')}</div></div>
        </div>
        <h4 class="ui header">Metadata</h4><pre class="orbit-json">${this.esc(JSON.stringify(metadata || {}, null, 2))}</pre>
        <h4 class="ui header">Request</h4><pre class="orbit-json">${this.esc(JSON.stringify(request || {}, null, 2))}</pre>
      `);
    },
    openMember(userId, context) {
      this.drawer('modal.user_detail', `<div class="orbit-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> ${this.t('common.loading')}</div>`);
      const detailContext = Object.assign({}, context || {});
      const asOf = detailContext.asOf || '';
      if (asOf) {
        detailContext.asOf = asOf;
      }
      const detailParams = { userId };
      if (asOf) detailParams.asOf = asOf;
      this.api('/api/data/member_detail.php', detailParams).done((res) => {
        const m = res.member;
        const walletUnits = Array.isArray(res.walletUnits) ? res.walletUnits : (Array.isArray(m.walletUnits) ? m.walletUnits : []);
        const walletMetrics = walletUnits.map((unit) => {
          const label = unit.displayName || unit.shortName || unit.unitCode || '';
          return [label, unit.balanceAmount];
        });
        this.drawer(`${this.esc(m.displayName)}`, `
          ${this.renderHistoricalNotice(res.asOf)}
          ${this.renderUserProfileCard(res, 'drawer', detailContext)}
          <div class="orbit-metric-strip">${[
            ...walletMetrics,
            ['Messages', res.messages.length],
            ['Voice Sessions', res.voice.length],
            ['Roles', res.roles.length],
            ['Ledger', res.walletLedger.length],
            ['Summary Days', res.summary.length],
          ].map(x => `<div class="orbit-metric"><div class="orbit-muted">${x[0]}</div><div class="orbit-metric-value">${Number(x[1] || 0).toLocaleString()}</div></div>`).join('')}</div>
          <div class="orbit-drawer-tabs" data-tabs="memberProfile">
            <button class="ui small primary button active" data-tab-target="memberMessages">Messages</button>
            <button class="ui small button" data-tab-target="memberActivity">Activity</button>
            <button class="ui small button" data-tab-target="memberVoice">Voice</button>
            <button class="ui small button" data-tab-target="memberEconomy">Economy</button>
            <button class="ui small button" data-tab-target="memberSystem">System</button>
          </div>
          <div class="orbit-tab-panel active" data-tab-panel="memberMessages"><div id="drawerMessages"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="memberActivity"><div id="drawerActivity"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="memberVoice"><div id="drawerVoice"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="memberEconomy"><div id="drawerWallet"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="memberSystem"><div id="drawerSystem"></div></div>`);
        this.bindTabs('#drawerBody');
        this.table('#drawerMessages', [['Time', 'messageCreateDate'], ['Channel', 'channelName'], ['Content', 'contentText', (r) => this.renderMessageArchiveContent(r)], ['Flags', 'isDelete', (r) => r.isDelete == 1 ? this.badge('deleted', 'failed') : '']], res.messages);
        this.table('#drawerActivity', [['Time', 'eventDate'], ['Event', 'eventType'], ['Channel', 'channelName'], ['Target', 'targetId']], res.activity);
        this.table('#drawerVoice', [['Start', 'startDate'], ['End', 'endDate'], ['Channel', 'channelName'], ['Duration', 'durationSeconds', (r) => res.asOf ? this.duration(r.clippedDurationSeconds || 0) : (r.isClosed == 0 ? this.liveDuration(r.startDate) : this.duration(r.durationSeconds))]], res.voice);
        this.table('#drawerWallet', [['Time', 'createDate'], ['Unit', 'unitLabel'], ['Type', 'ledgerType'], ['Amount', 'amountDelta', (row) => `${Number(row.amountDelta || 0).toLocaleString()} ${this.esc(row.unitShortName || row.unitLabel || row.unitCode || '')}`.trim()], ['Source', 'sourceType']], res.walletLedger);
        this.table('#drawerSystem', [['Time', 'createDate'], ['Staff', 'displayName'], ['Action', 'actionType'], ['Status', 'status']], res.systemHistory.concat(res.accessHistory || []));
        this.updateLiveDurations();
      });
    },
    openMessage(messageId) {
      this.drawer('modal.message_detail', `<div class="orbit-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> ${this.t('common.loading')}</div>`);
      const asOf = '';
      const detailParams = { messageId };
      if (asOf) detailParams.asOf = asOf;
      this.api('/api/data/message_detail.php', detailParams).done((res) => {
        const m = res.message;
        this.drawer('modal.message_detail', `
          ${this.renderHistoricalNotice(res.asOf)}
          <div>${this.userCell(m, 'authorUserId', res.asOf ? { historicalContext: true, asOf: res.asOf } : null)}</div>
          <p class="orbit-muted">#${this.esc(m.channelName || '-')} · ${m.messageCreateDate} · ${m.messageId}</p>
          ${res.historicalContext ? `<div class="orbit-context-row">${this.contextBadge(res.historicalContext.authorConfidence)} ${this.contextBadge(res.historicalContext.channelConfidence)}</div>` : ''}
          <div class="ui message ${m.isDelete == 1 ? 'negative' : ''}">${this.renderMessageArchiveContent(m)}</div>
          <h4 class="ui header">Attachments</h4><div>${this.renderAttachmentGallery(res.attachments)}</div>
          <h4 class="ui header">Revisions</h4><div id="drawerRevisions"></div>
          <h4 class="ui header">Delete/System History</h4><div id="drawerDeleteHistory"></div>`);
        this.table('#drawerRevisions', [['Time', 'createDate'], ['Type', 'revisionType'], ['Content', 'contentText', (r) => this.esc(this.trunc(r.contentText || '', 140))]], res.revisions);
        this.table('#drawerDeleteHistory', [['Time', 'createDate'], ['Source', 'deleteSource'], ['Bulk', 'isBulk'], ['Deleted By', 'deletedByUserId']], res.deletes.concat(res.systemHistory || []));
      });
    },
    openChannel(channelId) {
      this.drawer('modal.channel_detail', `<div class="orbit-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> ${this.t('common.loading')}</div>`);
      const detailParams = { channelId };
      this.api('/api/data/channel_detail.php', detailParams).done((res) => {
        const c = res.channel;
        const metrics = res.metrics || {};
        const voiceTitle = this.t('metric.voice_now');
        this.drawer(`${this.esc(c.channelName)}`, `
          ${this.renderHistoricalNotice(res.asOf)}
          ${res.historicalContext ? `<div class="orbit-context-row">${this.contextBadge(res.historicalContext.channelConfidence)} ${this.contextBadge(res.historicalContext.categoryConfidence)}</div>` : ''}
          <div class="orbit-compact-grid">
            <div class="orbit-metric"><div class="orbit-muted">Type</div><div class="orbit-metric-value">${this.channelTypeLabel(c.channelType)}</div></div>
            <div class="orbit-metric"><div class="orbit-muted">Category</div><div class="orbit-metric-value">${this.esc(c.categoryName || '-')}</div></div>
            <div class="orbit-metric"><div class="orbit-muted">Position</div><div class="orbit-metric-value">${this.esc(c.channelPosition)}</div></div>
            <div class="orbit-metric"><div class="orbit-muted">Status</div><div class="orbit-metric-value" title="Active = ห้องยังเจอจาก gateway/sync ล่าสุด">${c.isActive == 1 ? 'active' : 'inactive'}</div></div>
            <div class="orbit-metric"><div class="orbit-muted">Messages</div><div class="orbit-metric-value">${Number(metrics.messagesTotal || 0).toLocaleString()}</div></div>
            <div class="orbit-metric"><div class="orbit-muted">Messages 12h</div><div class="orbit-metric-value">${Number(metrics.messages12h || 0).toLocaleString()}</div></div>
            <div class="orbit-metric"><div class="orbit-muted">Users 12h</div><div class="orbit-metric-value">${Number(metrics.activeUsers12h || 0).toLocaleString()}</div></div>
            <div class="orbit-metric"><div class="orbit-muted">Events 12h</div><div class="orbit-metric-value">${Number(metrics.events12h || 0).toLocaleString()}</div></div>
            <div class="orbit-metric"><div class="orbit-muted">Overwrites</div><div class="orbit-metric-value">${Number(metrics.overwriteCount || 0).toLocaleString()}</div></div>
          </div>
          ${c.topic ? `<div class="ui message">${this.esc(c.topic)}</div>` : ''}
          <div class="orbit-drawer-tabs" data-tabs="channelDetail">
            <button class="ui small primary button active" data-tab-target="channelVoice">${this.t('tab.live_now')}</button>
            <button class="ui small button" data-tab-target="channelMessages">${this.t('tab.messages')}</button>
            <button class="ui small button" data-tab-target="channelActivity">Activity</button>
            <button class="ui small button" data-tab-target="channelAccess">Access</button>
          </div>
          <div class="orbit-tab-panel active" data-tab-panel="channelVoice"><h4 class="ui header">${voiceTitle}</h4><div id="drawerChannelVoice"></div><h4 class="ui header">${this.t('tab.typing_now')}</h4><div id="drawerChannelTyping"></div><h4 class="ui header">${this.t('tab.children')}</h4><div id="drawerChannelChildren"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="channelMessages"><div id="drawerChannelMessages"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="channelActivity"><div id="drawerChannelActivity"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="channelAccess"><div id="drawerChannelOverwrites"></div></div>`);
        this.bindTabs('#drawerBody');
        this.table('#drawerChannelVoice', [
          ['User', 'displayName', (r) => this.userCell(r, 'userId', { voiceStartDate: r.startDate, voiceChannel: c.channelName })],
          ['Joined', 'startDate'],
          ['In Room', 'startDate', (r) => this.liveDuration(r.startDate)],
          ['State', 'isMuted', (r) => this.voiceStateBadges(r)]
        ], res.voiceNow, { id: 'drawerChannelVoice' });
        this.table('#drawerChannelTyping', [
          ['User', 'displayName', (r) => this.userCell(r, 'userId', { typingChannel: c.channelName, typingExpireDate: r.expireDate })],
          ['Seen', 'createDate'],
          ['Until', 'expireDate'],
        ], res.typingNow, { id: 'drawerChannelTyping' });
        this.table('#drawerChannelChildren', [['Name', 'channelName'], ['Type', 'channelType', (r) => this.channelTypeLabel(r.channelType)], ['Pos', 'channelPosition'], ['Status', 'isActive', (r) => r.isActive == 1 ? 'active' : 'inactive']], res.children);
        this.table('#drawerChannelMessages', [['Time', 'messageCreateDate'], ['Author', 'displayName', (r) => this.userCell(r, 'userId')], ['Content', 'contentText', (r) => this.renderMessageArchiveContent(r)]], res.recentMessages);
        this.table('#drawerChannelActivity', [['Time', 'eventDate'], ['Event', 'eventType'], ['User', 'displayName', (r) => this.userCell(r, 'userId')], ['Target', 'targetId']], res.recentActivity);
        this.table('#drawerChannelOverwrites', [
          ['Type', 'overwriteType', (r) => Number(r.overwriteType) === 0 ? 'Role' : 'Member'],
          ['Target', 'roleName', (r) => this.esc(r.roleName || r.globalName || r.userName || r.overwriteId)],
          ['Allow', 'allowDecoded', (r) => (r.allowDecoded || []).map(p => this.badge(p.label, 'success')).join(' ') || '-'],
          ['Deny', 'denyDecoded', (r) => (r.denyDecoded || []).map(p => this.badge(p.label, 'failed')).join(' ') || '-']
        ], res.overwrites);
        this.updateLiveDurations();
      });
    },
    openRole(roleId) {
      this.drawer('modal.role_detail', `<div class="orbit-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> ${this.t('common.loading')}</div>`);
      const asOf = '';
      const detailParams = { roleId };
      if (asOf) detailParams.asOf = asOf;
      this.api('/api/data/role_detail.php', detailParams).done((res) => {
        const r = res.role;
        const roleColors = this.roleColors(r);
        this.drawer(this.esc(r.roleName), `
          ${this.renderHistoricalNotice(res.asOf)}
          <div class="orbit-role-hero ${roleColors.className}" style="${roleColors.style}">
            <div class="orbit-role-icon">${r.roleIconUrl ? `<img src="${r.roleIconUrl}" alt="">` : (r.unicodeEmoji ? this.esc(r.unicodeEmoji) : '<i class="fa-solid fa-shield-halved"></i>')}</div>
            <div>
              <h3 class="ui header">${this.esc(r.roleName)}</h3>
              <div class="orbit-muted">${this.esc(r.roleId)} · position ${this.esc(r.rolePosition)} · ${Number(r.memberCount || 0).toLocaleString()} members</div>
              <div style="margin-top:8px">${this.badge(r.isManaged == 1 ? 'managed' : 'manual', r.isManaged == 1 ? 'partial' : 'success')} ${r.roleTier ? this.badge(`Tier ${r.roleTier}`, 'partial') : ''} ${r.roleSeriesName ? this.badge(r.roleSeriesName, '') : ''} ${r.isMentionable == 1 ? this.badge('mentionable', '') : ''} ${r.isHoist == 1 ? this.badge('hoist', '') : ''} ${this.contextBadge(r.contextConfidence)}</div>
              <div class="orbit-color-swatches">${roleColors.swatches}</div>
            </div>
          </div>
          <div class="orbit-metric-strip">${[
            ['Members', r.memberCount],
            ['Permissions', r.permissionCount],
            ['Overwrites', res.overwrites.length],
            ['Revisions', res.revisions.length],
            ['Panel Actions', res.systemHistory.length],
            ['Audit Logs', res.auditHistory.length],
          ].map(x => `<div class="orbit-metric"><div class="orbit-muted">${x[0]}</div><div class="orbit-metric-value">${Number(x[1] || 0).toLocaleString()}</div></div>`).join('')}</div>
          <div class="orbit-drawer-tabs" data-tabs="roleDetail">
            <button class="ui small primary button active" data-tab-target="rolePermissions">Permissions</button>
            <button class="ui small button" data-tab-target="roleMembers">Members</button>
            <button class="ui small button" data-tab-target="roleChannels">Channels</button>
            <button class="ui small button" data-tab-target="roleRevisions">Role Revisions</button>
            <button class="ui small button" data-tab-target="roleHistory">History</button>
          </div>
          <div class="orbit-tab-panel active" data-tab-panel="rolePermissions">
            <label class="ui checkbox orbit-permission-toggle"><input type="checkbox" id="rolePermissionOnlyGranted" checked><span>แสดงเฉพาะ permission ที่เปิดอยู่</span></label>
            <div id="drawerRolePermissions"></div>
          </div>
          <div class="orbit-tab-panel" data-tab-panel="roleMembers"><div class="orbit-muted">${res.membersTruncated ? 'Showing first 1000 members' : ''}</div><div id="drawerRoleMembers"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="roleChannels"><div id="drawerRoleOverwrites"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="roleRevisions"><div id="drawerRoleRevisions"></div></div>
          <div class="orbit-tab-panel" data-tab-panel="roleHistory"><div id="drawerRoleHistory"></div></div>`);
        this.bindTabs('#drawerBody');
        $('#rolePermissionOnlyGranted').checkbox({
          onChange: () => this.renderRolePermissionTable(res.permissionCatalog || [])
        });
        this.renderRolePermissionTable(res.permissionCatalog || []);
        this.table('#drawerRoleMembers', [['User', 'displayName', (m) => this.userCell(m, 'userId', res.asOf ? { historicalContext: true, asOf: res.asOf } : null)], ['Joined', 'joinedAt'], ['Status', 'asOfStatus', (m) => {
          const active = m.asOfStatus ? m.asOfStatus === 'active_as_of' : m.isActive == 1;
          return this.badge(m.asOfStatus || (active ? 'active' : 'inactive'), active ? 'success' : 'failed');
        }], ['Confidence', 'contextConfidence', (m) => this.contextBadge(m.contextConfidence)]], res.members);
        this.table('#drawerRoleOverwrites', [['Channel', 'channelName', (o) => `${this.esc(o.categoryName ? o.categoryName + ' / ' : '')}${this.esc(o.channelName)}`], ['Allow', 'allowDecoded', (o) => (o.allowDecoded || []).map(p => this.badge(p.label, 'success')).join(' ') || '-'], ['Deny', 'denyDecoded', (o) => (o.denyDecoded || []).map(p => this.badge(p.label, 'failed')).join(' ') || '-']], res.overwrites);
        this.table('#drawerRoleRevisions', [['Time', 'createDate'], ['Source', 'sourceType'], ['Changes', 'afterJson', (x) => this.renderRoleRevisionSummary(x)]], res.revisions);
        this.table('#drawerRoleHistory', [['Time', 'createDate', (x) => x.createDate || x.auditCreateDate], ['Source', 'actionType', (x) => x.actionType || 'discord_audit'], ['Actor', 'displayName', (x) => this.esc(x.displayName || x.executorUserId || '-')], ['Status/Reason', 'status', (x) => this.esc(x.status || x.reason || '-')]], (res.systemHistory || []).concat(res.auditHistory || []));
      });
    },
    confirmDangerBackfill(type, command) {
      const phraseMap = {
        reset_system: 'RESET BOT LOG DATA',
        reset_rewards: 'RESET REWARDS',
        reset_gachapon: 'RESET GACHAPON',
      };
      const phrase = phraseMap[command] || 'RESET BOT LOG DATA';
      $('#dangerResetModal').remove();
      $('body').append(`
        <div class="ui small modal" id="dangerResetModal">
          <div class="header">${this.t('modal.confirm_bot_log_reset')}</div>
          <div class="content">
            <p>${this.t('text.bot_log_reset_warning')}</p>
            <div class="ui warning message">
              <div class="header">${this.t('modal.type_exactly', {}, 'Type this exactly')}</div>
              <p><code>${phrase}</code></p>
            </div>
            <div class="ui form">
              <div class="field">
                <label>${this.t('modal.confirmation_text')}</label>
                <input type="text" id="dangerResetConfirmInput" autocomplete="off" spellcheck="false" placeholder="${phrase}">
              </div>
            </div>
          </div>
          <div class="actions">
            <button class="ui button deny">${this.t('action.cancel')}</button>
            <button class="ui red disabled button" id="dangerResetConfirmButton"><i class="fa-solid fa-triangle-exclamation"></i> ${this.t('action.reset_bot_log_data')}</button>
          </div>
        </div>`);
      const updateState = () => {
        const matches = ($('#dangerResetConfirmInput').val() || '').trim().toUpperCase() === phrase;
        $('#dangerResetConfirmButton').toggleClass('disabled', !matches);
      };
      $('#dangerResetModal').modal({
        autofocus: false,
        closable: true,
        onVisible: () => $('#dangerResetConfirmInput').trigger('focus'),
        onHidden: () => $('#dangerResetModal').remove(),
      }).modal('show');
      $('#dangerResetConfirmInput').on('input', updateState);
      $('#dangerResetConfirmButton').on('click', () => {
        if ($('#dangerResetConfirmButton').hasClass('disabled')) return;
        const confirmText = ($('#dangerResetConfirmInput').val() || '').trim().toUpperCase();
        $('#dangerResetModal').modal('hide');
        this.runBackfill(type, command, { confirmText });
      });
    },
    runBackfill(type, command, extraPayload = {}) {
      $('#syncStatus').html(`<i class="fa-solid fa-circle-notch fa-spin"></i> ${this.t('action.backfill')}`);
      const payload = Object.assign({ type, command }, extraPayload);
      if (type === 'earn_worker' && !payload.options) {
        payload.options = { date: $('#earnWorkerBackfillDate').val() || '' };
      }
      this.api('/api/admin/backfill.php', payload, 'POST').done((res) => {
        $('#syncStatus').html(`<i class="fa-solid fa-circle"></i> ${this.t('state.done')}`);
        $('#backfillResult').html(this.jsonSummary(res.result));
        if (this.page === 'backfill') this.loadBackfill();
        if (this.page === 'admin') this.loadAdmin();
      }).fail((xhr) => {
        $('#syncStatus').html(`<i class="fa-solid fa-triangle-exclamation"></i> ${this.t('state.failed')}`);
        this.toast(xhr.responseJSON?.message || this.t('toast.backfill_failed'));
      });
    },
    enqueueJob(jobType) {
      this.api('/api/admin/run_job.php', { jobType }, 'POST').done(() => {
        if (this.page === 'backfill') this.loadBackfill();
        else this.loadAdmin();
      }).fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.queue_failed')));
    },
    queueMaintenance() {
      this.api('/api/admin/queue_maintenance.php', { staleMinutes: 60 }, 'POST').done((res) => {
        const result = res.result || {};
        this.toast(`Cleaned queue: stale ${Number(result.staleRunningFailed || 0)}, duplicate ${Number(result.duplicateQueuedCancelled || 0)}`);
        if (this.page === 'backfill') this.loadBackfill();
        else this.loadAdmin();
      }).fail((xhr) => this.toast(xhr.responseJSON?.message || this.t('toast.queue_failed')));
    },
    showUserCard(el) {
      const user = $(el);
      const userId = user.data('user-id');
      if (!userId) return;
      const pop = $('#profilePopover');
      const context = this.userContextFromElement(el);
      pop.html(`<div class="orbit-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> ${this.t('common.loading')}</div>`).show();
      this.positionUserPopover(el);
      const detailParams = { userId };
      if (context?.asOf) detailParams.asOf = context.asOf;
      this.api('/api/data/member_detail.php', detailParams).done((res) => {
        pop.html(this.renderUserProfileCard(res, 'popover', context));
        this.positionUserPopover(el);
        this.updateLiveDurations();
      });
    },
    positionUserPopover(el) {
      const rect = el.getBoundingClientRect();
      const pop = $('#profilePopover');
      const margin = 10;
      const width = Math.min(pop.outerWidth() || 340, Math.max(260, window.innerWidth - margin * 2));
      const height = Math.min(pop.outerHeight() || 260, Math.max(160, window.innerHeight - margin * 2));
      let left = rect.left;
      let top = rect.bottom + 8;
      if (left + width + margin > window.innerWidth) left = window.innerWidth - width - margin;
      if (left < margin) left = margin;
      if (top + height + margin > window.innerHeight) top = rect.top - height - 8;
      if (top < margin) top = margin;
      pop.css({ left, top, maxWidth: window.innerWidth - margin * 2, maxHeight: window.innerHeight - margin * 2 });
    },
    renderUserProfileCard(res, mode, context) {
      const m = res.member || {};
      const roles = res.roles || [];
      const compact = mode === 'popover';
      const historical = m.historicalContext || res.historicalContext || {};
      const historicalMode = false;
      const color = m.profileColor || '#5865f2';
      const bannerUrl = m.bannerUrl || m.currentBannerUrl || '';
      const bannerStyle = bannerUrl
        ? `background-image:url('${this.escAttr(bannerUrl)}')`
        : `background:${color}`;
      const status = m.latestPresence?.statusText || (m.isActive == 1 ? 'state.offline_unknown' : 'state.left_server');
      const voice = m.openVoice?.channelName ? this.t('profile.voice', { channel: m.openVoice.channelName }) : this.t('profile.no_open_voice');
      const invite = m.inviteAttribution || null;
      const inviteLine = invite ? (invite.isVanity
        ? this.t('profile.vanity_invite')
        : (invite.inviter?.displayName || invite.inviterName || '-')) : '-';
      const displayName = m.displayName || m.historicalDisplayName || m.currentDisplayName || m.userName || m.userId || '-';
      const primaryAvatarUrl = m.historicalProfileAvatarUrl || m.profileAvatarUrl || m.avatarUrl || m.currentProfileAvatarUrl || m.currentAvatarUrl || '';
      const currentAvatarUrl = m.currentProfileAvatarUrl || m.currentAvatarUrl || primaryAvatarUrl;
      const showCurrentAvatarChip = Boolean(historicalMode && currentAvatarUrl && primaryAvatarUrl && currentAvatarUrl !== primaryAvatarUrl);
      const currentNameNote = historicalMode && m.currentDisplayName && m.currentDisplayName !== displayName
        ? `${this.t('profile.current_name')}: ${m.currentDisplayName}`
        : '';
      const bannerNote = historicalMode && m.currentBannerUrl
        ? this.t(m.historicalBannerUrl ? 'profile.banner_historical' : 'profile.banner_current_fallback')
        : '';
      const avatarNote = historicalMode && !m.historicalProfileAvatarUrl && currentAvatarUrl
        ? this.t('profile.avatar_current_fallback')
        : '';
      const asOfDuration = context?.asOfDurationSeconds != null && context.asOfDurationSeconds !== ''
        ? this.duration(Number(context.asOfDurationSeconds || 0))
        : '';
      const ctxVoice = context?.voiceStartDate ? `
        <div class="orbit-profile-context">
          <div class="orbit-muted">${this.esc(context.voiceChannel || this.t('profile.voice_room'))}</div>
          <strong>${this.esc(this.t('profile.voice_started', { date: context.voiceStartDate }))}</strong>
          <div class="orbit-user-sub">${this.esc(this.t('profile.voice_stayed', { duration: asOfDuration || '' }))}${asOfDuration ? '' : this.liveDuration(context.voiceStartDate)}</div>
        </div>` : '';
      const ctxTyping = context?.typingChannel ? `
        <div class="orbit-profile-context">
          <div class="orbit-muted">${this.t('profile.typing')}</div>
          <strong>${this.esc(context.typingChannel)}</strong>
          <div class="orbit-user-sub">${context.typingExpireDate ? this.esc(this.t('profile.typing_until', { date: context.typingExpireDate })) : this.t('profile.typing_live')}</div>
        </div>` : '';
      const historicalBadges = false ? `
        <div class="orbit-context-row">
          ${this.badge('badge.as_of', 'partial')}
          ${this.contextBadge(historical.profileConfidence)}
          ${this.contextBadge(historical.roleConfidence)}
          ${m.asOfStatus ? this.badge(m.asOfStatus, m.asOfStatus === 'active_as_of' ? 'success' : 'failed') : ''}
        </div>
        <div class="orbit-user-sub">${this.esc(this.t('common.as_of'))} ${this.esc(historical.asOf || res.asOf || context?.asOf || '-')}</div>
        <div class="orbit-user-sub">profile: ${this.esc(historical.profileSourceType || '-')} · ${this.esc(historical.profileSourceDate || '-')}</div>
        <div class="orbit-user-sub">roles: ${this.esc(historical.roleSourceType || '-')} · ${this.esc(historical.roleSourceDate || '-')}</div>
      ` : '';
      const metaBadges = [
        this.badge(status, status === 'online' ? 'success' : status === 'idle' ? 'partial' : ''),
        m.isBot == 1 ? this.badge('badge.bot', '') : '',
        m.premiumSince ? this.badge('badge.boosting', 'success') : '',
        historicalMode
          ? (m.asOfTimeoutActive ? this.badge('badge.timeout', 'failed') : '')
          : (m.communicationDisabledUntil ? this.badge('badge.timeout', 'failed') : ''),
      ].filter(Boolean).join(' ');
      const serverStateBadges = [];
      if (historicalMode && m.asOfStatus) serverStateBadges.push(this.badge(m.asOfStatus, m.asOfStatus === 'active_as_of' ? 'success' : 'failed'));
      if (historicalMode && m.asOfTimeoutActive) serverStateBadges.push(this.badge('badge.timeout', 'failed'));
      if (historicalMode && Number(m.bannedAsOf || 0) === 1) serverStateBadges.push(this.badge('badge.banned', 'failed'));
      const punishActor = m.lastPunishActor?.display_name || m.lastPunishActor?.user_name || '';
      const serverStateBlock = historicalMode ? `
        <div class="orbit-profile-context">
          <div class="orbit-muted">${this.t('profile.server_state')}</div>
          <div class="orbit-badge-row align-start">${serverStateBadges.join(' ') || this.badge('state.unknown', 'partial')}</div>
          ${m.asOfTimeoutActive ? `<div class="orbit-user-sub">${this.esc(this.t('profile.timeout_until', { date: m.asOfTimeoutUntil || '-' }))}</div>` : ''}
          ${m.lastPunishEventType ? `<div class="orbit-user-sub">${this.badge(m.lastPunishEventType, 'partial')} ${this.esc(m.lastPunishEventDate || '')}</div>` : ''}
          ${punishActor ? `<div class="orbit-user-sub">${this.esc(this.t('profile.punish_actor', { name: punishActor }))}</div>` : ''}
          ${bannerNote ? `<div class="orbit-profile-note">${this.esc(bannerNote)}</div>` : ''}
          ${avatarNote ? `<div class="orbit-profile-note">${this.esc(avatarNote)}</div>` : ''}
        </div>` : '';
      const voiceStateBlock = historicalMode ? `
        <div class="orbit-profile-context">
          <div class="orbit-muted">${this.t('profile.voice_state_as_of')}</div>
          <strong>${this.esc(m.openVoice?.channelName || this.t('profile.voice_not_in_room'))}</strong>
          <div class="orbit-badge-row align-start">${this.voiceStateBadges(m.openVoice || {}, { showInactive: Boolean(m.openVoice?.channelName), showConfidence: true, showUnknown: true })}</div>
          <div class="orbit-user-sub">${this.esc(this.voiceStateSummary(m.openVoice || {}))}</div>
        </div>` : '';
      return `
        <div class="orbit-discord-card ${compact ? 'compact' : ''}" style="--profile-color:${this.escAttr(color)}">
          <div class="orbit-discord-banner" style="${bannerStyle}"></div>
          <div class="orbit-discord-body">
            <div class="orbit-discord-avatar-shell">
              ${this.imageTag('orbit-discord-avatar', primaryAvatarUrl, currentAvatarUrl, displayName)}
              ${showCurrentAvatarChip ? this.imageTag('orbit-discord-avatar orbit-discord-avatar-current', currentAvatarUrl, primaryAvatarUrl, this.t('profile.current_name'), `title="${this.escAttr(this.t('profile.current_name'))}"`) : ''}
            </div>
            <div class="orbit-discord-title-row">
              <div>
                <div class="orbit-discord-name">${this.esc(displayName)}</div>
                <div class="orbit-user-sub">@${this.esc(m.userName || m.userId)} · ${this.esc(m.userId || '')}</div>
                ${currentNameNote ? `<div class="orbit-discord-name-note">${this.esc(currentNameNote)}</div>` : ''}
              </div>
              <div class="orbit-badge-row">${(m.badges || []).map(b => `<span title="${this.esc(b.label)}"><i class="${this.esc(b.icon)}"></i></span>`).join('')}</div>
            </div>
            <div class="orbit-discord-meta">${metaBadges}</div>
            ${historicalBadges}
            ${serverStateBlock}
            ${voiceStateBlock}
            <div class="orbit-role-list">${roles.slice(0, compact ? 6 : 18).map(r => this.rolePill(r)).join(' ')}</div>
            <div class="orbit-compact-grid orbit-profile-facts">
              <div><div class="orbit-muted">${this.t('table.joined')}</div><strong>${this.esc(m.joinedAt || '-')}</strong></div>
              <div><div class="orbit-muted">${this.t('metric.coins')}</div><strong>${Number(m.balanceAmount || 0).toLocaleString()}</strong></div>
              <div><div class="orbit-muted">${this.t('metric.voice')}</div><strong>${this.esc(voice)}</strong></div>
              <div><div class="orbit-muted">${this.t('metric.roles')}</div><strong>${roles.length}</strong></div>
              <div><div class="orbit-muted">${this.t('profile.invited_by')}</div><strong>${this.esc(inviteLine)}</strong></div>
              <div><div class="orbit-muted">${this.t('profile.invite_count')}</div><strong>${invite?.inviteCount != null ? Number(invite.inviteCount).toLocaleString() : '-'}</strong></div>
            </div>
            ${ctxVoice}
            ${ctxTyping}
          </div>
        </div>`;
    },
    table(selector, columns, rows, opts) {
      const target = $(selector);
      if (!target.is('table')) target.addClass('orbit-table-wrap');
      const table = target.is('table') ? target : $('<table class="ui very basic table orbit-table"></table>').appendTo(target.empty());
      const id = opts?.id || table.attr('id') || selector.replace('#', '');
      this.tableCache[id] = { selector, columns, rows: rows || [], opts };
      const sort = this.sortState[id];
      if (sort && !opts?.onSort) {
        rows = [...(rows || [])].sort((a, b) => {
          const av = a[sort.key] ?? '';
          const bv = b[sort.key] ?? '';
          const cmp = isFinite(av) && isFinite(bv) ? Number(av) - Number(bv) : String(av).localeCompare(String(bv), 'th');
          return sort.dir === 'asc' ? cmp : -cmp;
        });
      }
      if (!rows || !rows.length) return table.html(`<tbody><tr><td colspan="${columns.length}">${this.empty('empty.data', 'empty.data_sub')}</td></tr></tbody>`);
      const header = columns.map(c => {
        const active = sort && sort.key === c[1];
        const icon = active ? (sort.dir === 'asc' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short') : 'fa-sort';
        return `<th data-col="${c[1]}" class="${active ? 'sorted ' + sort.dir : ''}"><span class="orbit-th-label">${this.esc(this.tl(c[0]))}<i class="fa-solid ${icon}"></i></span><span class="orbit-resizer"></span></th>`;
      }).join('');
      table.html(`<thead><tr>${header}</tr></thead><tbody>${rows.map((r, i) => `<tr data-row="${i}">${columns.map(c => `<td>${typeof c[2] === 'function' ? c[2](r) : this.esc(r[c[1]] ?? '-')}</td>`).join('')}</tr>`).join('')}</tbody>`);
      table.find('th').on('click', (e) => {
        if ($(e.target).hasClass('orbit-resizer')) return;
        const key = $(e.currentTarget).data('col');
        const current = this.sortState[id] || {};
        this.sortState[id] = { key, dir: current.key === key && current.dir === 'asc' ? 'desc' : 'asc' };
        if (opts?.onSort) {
          opts.onSort(this.sortState[id]);
          return;
        }
        const cached = this.tableCache[id];
        this.table(cached.selector, cached.columns, cached.rows, cached.opts);
      });
      this.applyColumnPrefs(table, id);
      table.find('tbody tr').on('click', (e) => {
        if ($(e.target).closest('button,a,input,select,label,.orbit-user,.ui.dropdown,.ui.checkbox').length) return;
        if (opts?.rowClick) opts.rowClick(rows[$(e.currentTarget).data('row')]);
      });
      this.translateStaticContent(table);
      this.updateLiveDurations();
    },
    formatRulerDate(date, includeSeconds) {
      if (!date || Number.isNaN(date.getTime())) return '-';
      const pad = (n) => String(n).padStart(2, '0');
      const value = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
      return includeSeconds ? value : value;
    },
    localDateTimeInput(date) {
      const pad = (n) => String(n).padStart(2, '0');
      return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    },
    applyColumnPrefs(table, id) {
      const pref = JSON.parse(localStorage.getItem('orbit.table.' + id) || '{}');
      table.find('th').each(function (i) {
        if (pref[i]) $(this).css('width', pref[i]);
      });
      table.find('.orbit-resizer').on('mousedown', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const th = $(this).closest('th');
        const startX = e.pageX;
        const startW = th.width();
        $(document).on('mousemove.orbitResize', function (ev) {
          th.css('width', Math.max(70, startW + ev.pageX - startX));
        }).on('mouseup.orbitResize', function () {
          const out = {};
          table.find('th').each(function (idx) { out[idx] = $(this).outerWidth() + 'px'; });
          localStorage.setItem('orbit.table.' + id, JSON.stringify(out));
          $(document).off('.orbitResize');
        });
      });
    },
    formParams(selector) {
      const data = {};
      $(selector).find('input, select').each(function () {
        if (this.type === 'checkbox') data[this.name] = this.checked ? 1 : '';
        else if (this.multiple) {
          const value = $(this).val();
          if (this.name && Array.isArray(value) && value.length) data[this.name] = value;
        } else if (this.name && this.value !== '') data[this.name] = this.value;
      });
      return data;
    },
    metrics(selector, items) {
      $(selector).html(items.map(([label, value]) => `<div class="orbit-metric"><div class="orbit-muted">${this.esc(this.tl(label))}</div><div class="orbit-metric-value">${Number(value || 0).toLocaleString()}</div></div>`).join(''));
    },
    pager(selector, res, cb) {
      const pages = Math.max(1, Math.ceil(res.total / res.pageSize));
      $(selector).html(`<div class="ui small pagination menu"><a class="item ${res.page <= 1 ? 'disabled' : ''}" data-p="${res.page - 1}">${this.t('pager.prev')}</a><div class="item">${res.page} / ${pages} · ${this.t('pager.rows', { total: Number(res.total || 0).toLocaleString() })}</div><a class="item ${res.page >= pages ? 'disabled' : ''}" data-p="${res.page + 1}">${this.t('pager.next')}</a></div>`);
      $(selector).find('[data-p]').on('click', function () { if (!$(this).hasClass('disabled')) cb(Number($(this).data('p'))); });
    },
    drawer(title, html) {
      $('#drawerTitle').text(this.tl(title));
      $('#drawerBody').html(html);
      this.translateStaticContent($('#drawerBody'));
      $('#orbitDrawer').addClass('open');
      $('body').addClass('drawer-open');
      $('body').toggleClass('drawer-mode-modal', $('#orbitDrawer').hasClass('as-modal'));
      $('body').toggleClass('drawer-mode-side', !$('#orbitDrawer').hasClass('as-modal'));
    },
    closeDrawer() {
      $('#orbitDrawer').removeClass('open');
      $('body').removeClass('drawer-open drawer-mode-side drawer-mode-modal');
    },
    toggleDrawerMode() {
      const drawer = $('#orbitDrawer');
      drawer.toggleClass('as-modal');
      $('body').toggleClass('drawer-mode-modal', drawer.hasClass('as-modal'));
      $('body').toggleClass('drawer-mode-side', !drawer.hasClass('as-modal') && drawer.hasClass('open'));
      $('#drawerModeToggle i').toggleClass('fa-up-right-and-down-left-from-center fa-table-columns');
    },
    imageFallbackAttrs(primaryUrl, fallbackUrl) {
      const primary = String(primaryUrl || '').trim();
      const fallback = String(fallbackUrl || '').trim();
      if (!fallback || fallback === primary) return '';
      return ` data-fallback-src="${this.escAttr(fallback)}" onerror="if(this.dataset.fallbackSrc&&this.src!==this.dataset.fallbackSrc){this.src=this.dataset.fallbackSrc;this.dataset.fallbackSrc='';return;}this.onerror=null;this.removeAttribute('data-fallback-src');"`;
    },
    imageTag(className, primaryUrl, fallbackUrl, alt, extraAttrs) {
      const primary = String(primaryUrl || fallbackUrl || 'data:image/gif;base64,R0lGODlhAQABAAAAACw=').trim() || 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
      return `<img class="${this.escAttr(className)}" src="${this.escAttr(primary)}" alt="${this.escAttr(alt || '')}"${this.imageFallbackAttrs(primary, fallbackUrl)}${extraAttrs ? ' ' + extraAttrs : ''}>`;
    },
    memberStatusBadges(row) {
      const badges = [];
      if (row?.asOfStatus) badges.push(this.badge(row.asOfStatus, row.asOfStatus === 'active_as_of' ? 'success' : 'failed'));
      if (Number(row?.timeoutActive || 0) === 1) badges.push(this.badge('badge.timeout', 'failed'));
      if (Number(row?.bannedAsOf || 0) === 1) badges.push(this.badge('badge.banned', 'failed'));
      return badges.join(' ') || this.badge('state.unknown', 'partial');
    },
    moderationSummary(row) {
      const lines = [];
      if (row?.lastPunishEventType) lines.push(this.badge(row.lastPunishEventType, 'partial'));
      if (row?.lastPunishEventDate) lines.push(`<span class="orbit-user-sub">${this.esc(row.lastPunishEventDate)}</span>`);
      if (row?.lastPunishActor?.display_name || row?.lastPunishActor?.user_name) {
        lines.push(`<span class="orbit-user-sub">${this.esc(row.lastPunishActor.display_name || row.lastPunishActor.user_name)}</span>`);
      }
      return lines.join(' ') || '-';
    },
    voiceStateBadges(row, opts) {
      const showInactive = Boolean(opts?.showInactive);
      const showConfidence = Boolean(opts?.showConfidence);
      const showUnknown = Boolean(opts?.showUnknown);
      const badges = [];
      if (Number(row?.serverMuted || 0) === 1) badges.push(this.badge('badge.server_muted', 'failed'));
      else if (Number(row?.selfMuted || 0) === 1 || Number(row?.isMuted || 0) === 1) badges.push(this.badge('badge.self_muted', 'partial'));
      else if (showInactive) badges.push(this.badge('profile.voice_unmuted', ''));
      if (Number(row?.serverDeafened || 0) === 1) badges.push(this.badge('badge.server_deafened', 'failed'));
      else if (Number(row?.selfDeafened || 0) === 1 || Number(row?.isDeafened || 0) === 1) badges.push(this.badge('badge.self_deafened', 'partial'));
      else if (showInactive) badges.push(this.badge('profile.voice_listening', ''));
      if (Number(row?.isVideo || 0) === 1) badges.push(this.badge('badge.video', 'success'));
      else if (showInactive) badges.push(this.badge('profile.voice_camera_off', ''));
      if (Number(row?.isStreaming || 0) === 1) badges.push(this.badge('badge.streaming', 'success'));
      else if (showInactive) badges.push(this.badge('profile.voice_stream_off', ''));
      if (Number(row?.isSuppressed || 0) === 1) badges.push(this.badge('badge.suppressed', 'pending'));
      if (row?.requestToSpeakDate) badges.push(this.badge('badge.request_to_speak', 'success'));
      if (showConfidence && row?.voiceStateConfidence) badges.push(this.contextBadge(row.voiceStateConfidence));
      if (showUnknown && badges.length === 0) badges.push(this.badge('state.unknown', 'partial'));
      return badges.join(' ') || '-';
    },
    voiceStateSummary(row) {
      if (!row || (!row.channelName && !row.voiceChannelName && !row.voiceStateChannelId && !row.voiceStateConfidence)) {
        return this.t('profile.voice_not_in_room');
      }
      if (row.voiceStateConfidence && ['unknown', 'approx'].includes(String(row.voiceStateConfidence))) {
        return this.t('state.unknown');
      }
      const micKey = Number(row?.serverMuted || 0) === 1
        ? 'profile.voice_server_muted'
        : (Number(row?.selfMuted || 0) === 1 || Number(row?.isMuted || 0) === 1 ? 'profile.voice_muted' : 'profile.voice_unmuted');
      const listenKey = Number(row?.serverDeafened || 0) === 1
        ? 'profile.voice_server_deafened'
        : (Number(row?.selfDeafened || 0) === 1 || Number(row?.isDeafened || 0) === 1 ? 'profile.voice_deafened' : 'profile.voice_listening');
      const extras = [];
      if (Number(row?.isSuppressed || 0) === 1) extras.push(this.t('profile.voice_suppressed'));
      if (row?.requestToSpeakDate) extras.push(this.t('badge.request_to_speak'));
      return this.t('profile.voice_flags', {
        mic: this.t(micKey),
        listen: this.t(listenKey),
        camera: this.t(Number(row?.isVideo || 0) === 1 ? 'profile.voice_camera_on' : 'profile.voice_camera_off'),
        stream: this.t(Number(row?.isStreaming || 0) === 1 ? 'profile.voice_stream_on' : 'profile.voice_stream_off'),
        extras: extras.length ? ` · ${extras.join(' · ')}` : '',
      });
    },
    userCell(r, idKey, context) {
      const userId = r[idKey || 'userId'] || r.userId || r.authorUserId || '';
      const displayName = r.displayName || r.historicalDisplayName || r.currentDisplayName || r.globalName || r.userName || userId || '-';
      const avatarUrl = r.profileAvatarUrl || r.avatarUrl || r.historicalProfileAvatarUrl || r.currentProfileAvatarUrl || r.currentAvatarUrl || '';
      const fallbackAvatarUrl = r.currentProfileAvatarUrl || r.currentAvatarUrl || avatarUrl;
      return `<span class="orbit-user"
        data-user-id="${this.escAttr(userId)}"
        ${context?.voiceStartDate ? `data-voice-start-date="${this.escAttr(context.voiceStartDate)}"` : ''}
        ${context?.asOfDurationSeconds != null ? `data-as-of-duration-seconds="${this.escAttr(context.asOfDurationSeconds)}"` : ''}
        ${context?.asOf ? `data-as-of="${this.escAttr(context.asOf)}"` : ''}
        ${context?.voiceChannel ? `data-voice-channel="${this.escAttr(context.voiceChannel)}"` : ''}
        ${context?.historicalContext ? 'data-historical-context="1"' : ''}>
        ${this.imageTag('orbit-avatar', avatarUrl, fallbackAvatarUrl, displayName)}
        <span class="orbit-user-text"><span class="orbit-user-name">${this.esc(displayName)}</span><span class="orbit-user-sub">${this.esc(r.userName ? '@' + r.userName : userId)}</span></span>
      </span>`;
    },
    roleCell(r) {
      const colors = this.roleColors(r);
      const dotStyle = colors.className
        ? `background:linear-gradient(135deg,var(--role-color),var(--role-color-2),var(--role-color-3));${colors.style}`
        : `background:#${(Number(r.roleColor || 0)).toString(16).padStart(6, '0')}`;
      const meta = [r.roleId, r.roleTier ? `Tier ${r.roleTier}` : '', r.roleSeriesName || ''].filter(Boolean).join(' · ');
      return `<span class="orbit-role-cell">${r.roleIconUrl ? `<img class="orbit-role-img" src="${this.escAttr(r.roleIconUrl)}" alt="">` : `<span class="orbit-role-dot" style="${dotStyle}"></span>`}<span><strong>${this.esc(r.roleName)}</strong><span class="orbit-user-sub">${this.esc(meta)}</span></span></span>`;
    },
    rolePill(r) {
      const colors = this.roleColors(r);
      const color = '#' + (Number(r.roleColor || 0)).toString(16).padStart(6, '0');
      const dotStyle = colors.className
        ? `background:linear-gradient(135deg,var(--role-color),var(--role-color-2),var(--role-color-3));${colors.style}`
        : `background:${color}`;
      const icon = r.roleIconUrl ? `<img src="${this.escAttr(r.roleIconUrl)}" alt="">` : `<span class="orbit-role-dot" style="${dotStyle}"></span>`;
      return `<span class="orbit-role-pill">${icon}${this.esc(r.roleName)}</span>`;
    },
    statusPill(text, type, options = {}) {
      const icon = options.icon || '';
      const className = ['orbit-status', type || '', options.className || ''].filter(Boolean).join(' ');
      const tooltip = options.tooltip ? ` data-tooltip="${this.escAttr(this.tl(options.tooltip))}"` : '';
      return `<span class="${className}"${tooltip}>${icon}${this.esc(this.tl(text))}</span>`;
    },
    badge(text, type) {
      return this.statusPill(text, type);
    },
    jobStatusTone(status) {
      const normalized = String(status || '').toLowerCase();
      if (normalized === 'success') return 'success';
      if (normalized === 'failed') return 'failed';
      if (normalized === 'running') return 'partial';
      if (normalized === 'queued') return 'pending';
      if (normalized === 'cancelled') return '';
      return normalized;
    },
    jobStatusBadge(status) {
      const normalized = String(status || '').toLowerCase();
      const isEn = this.lang() === 'en';
      if (normalized === 'running') {
        return this.statusPill(isEn ? 'Running' : 'กำลังรัน', 'partial', {
          icon: '<i class="fa-solid fa-circle-notch fa-spin"></i>',
          className: 'is-live',
        });
      }
      if (normalized === 'queued') {
        return this.statusPill(isEn ? 'Queued' : 'รอคิว', 'pending', {
          icon: '<span class="orbit-status-dot"></span>',
          className: 'has-pulse',
        });
      }
      if (normalized === 'success') {
        return this.statusPill(isEn ? 'Success' : 'สำเร็จ', 'success', {
          icon: '<i class="fa-solid fa-check"></i>',
        });
      }
      if (normalized === 'failed') {
        return this.statusPill(isEn ? 'Failed' : 'ล้มเหลว', 'failed', {
          icon: '<i class="fa-solid fa-triangle-exclamation"></i>',
        });
      }
      if (normalized === 'cancelled') {
        return this.statusPill(isEn ? 'Cancelled' : 'ยกเลิก', '', {
          icon: '<i class="fa-solid fa-ban"></i>',
        });
      }
      return this.badge(status || '-', this.jobStatusTone(normalized));
    },
    backfillStateBadge(row) {
      const isEn = this.lang() === 'en';
      const cursorValue = String(row?.cursorValue || '').toLowerCase();
      const hasUpdate = !!(row?.updateDate && row.updateDate !== '-');
      if (!hasUpdate) {
        return this.statusPill(isEn ? 'New' : 'ยังไม่เริ่ม', 'pending', {
          icon: '<i class="fa-regular fa-circle"></i>',
        });
      }
      if (cursorValue === 'done' || cursorValue === 'recent_complete') {
        return this.statusPill(isEn ? 'Done' : 'done', 'success', {
          icon: '<i class="fa-solid fa-check"></i>',
        });
      }
      return this.statusPill(isEn ? 'In Progress' : 'คืบหน้า', 'partial', {
        icon: '<span class="orbit-status-dot"></span>',
        className: 'has-pulse',
      });
    },
    workerStateBadge(row) {
      const ageSeconds = this.ageSeconds(row?.heartbeatDate);
      const isEn = this.lang() === 'en';
      if (ageSeconds == null) {
        return this.statusPill(isEn ? 'Missing' : 'ไม่เจอ', 'failed', {
          icon: '<i class="fa-solid fa-circle-xmark"></i>',
        });
      }
      if (ageSeconds <= 20) {
        return this.statusPill(isEn ? 'Live' : 'สด', 'success', {
          icon: '<span class="orbit-status-dot"></span>',
          className: 'has-pulse',
        });
      }
      if (ageSeconds <= 180) {
        return this.statusPill(isEn ? 'Warm' : 'ยังอยู่', 'partial', {
          icon: '<span class="orbit-status-dot"></span>',
        });
      }
      return this.statusPill(isEn ? 'Stale' : 'ค้าง', 'failed', {
        icon: '<i class="fa-solid fa-triangle-exclamation"></i>',
      });
    },
    contextBadge(confidence) {
      if (!confidence) return '';
      const type = confidence === 'snapshot' || confidence === 'historical_exact' || confidence === 'gateway_exact' ? 'success'
        : confidence === 'bot_log_snapshot' || confidence === 'snapshot_empty' || confidence === 'audit_log' ? 'partial'
          : confidence === 'current_fallback' || confidence === 'unavailable' || confidence === 'approx' ? 'pending'
            : '';
      return this.badge(`confidence.${confidence}`, type);
    },
    renderHistoricalNotice(asOf) {
      if (!asOf) return '';
      return `<div class="ui message orbit-historical-note">
        <div>${this.contextBadge('historical_exact')}</div>
        <p>${this.esc(this.t('text.historical_context_notice', { asOf }))}</p>
      </div>`;
    },
    empty(title, sub) {
      return `<div class="orbit-empty"><strong>${this.esc(this.tl(title))}</strong>${sub ? `<div>${this.esc(this.tl(sub))}</div>` : ''}</div>`;
    },
    error(el, msg) {
      el.html(`<div class="orbit-error"><i class="fa-solid fa-triangle-exclamation"></i> ${this.esc(this.tl(msg))}</div>`);
    },
    toast(msg) {
      const message = this.tl(msg);
      $('body').toast ? $('body').toast({ message, class: 'error' }) : alert(message);
    },
    channelTypeLabel(type) {
      return this.t(`channel.type.${Number(type)}`, {}, 'Type ' + type);
    },
    renderChannelIngestPath(workers) {
      const gateway = workers.find(w => w.workerName === 'gateway_worker');
      const sync = workers.find(w => w.workerName === 'sync_worker');
      return `
        <div class="orbit-compact-grid">
          <div class="orbit-metric"><div class="orbit-muted">1. Discord Gateway</div><div class="orbit-user-sub">ระบบเปิด WebSocket ออกไปหา Discord แล้ว subscribe event ด้วย intents</div></div>
          <div class="orbit-metric"><div class="orbit-muted">2. gateway_worker</div><div class="orbit-user-sub">heartbeat ล่าสุด ${this.esc(gateway?.heartbeatDate || '-')}</div></div>
          <div class="orbit-metric"><div class="orbit-muted">3. tbl_raw_event</div><div class="orbit-user-sub">ทุก event ที่รับได้จะถูกเก็บ raw ก่อน แล้วค่อย transform</div></div>
          <div class="orbit-metric"><div class="orbit-muted">4. GatewayEventIngestService</div><div class="orbit-user-sub">กระจายเข้า message archive, raw activity, voice session, member, role และ channel state</div></div>
          <div class="orbit-metric"><div class="orbit-muted">5. Backfill Repair</div><div class="orbit-user-sub">sync_worker ล่าสุด ${this.esc(sync?.heartbeatDate || '-')} ใช้ bot log ที่อนุมัติซ่อมช่องว่างของ canonical event</div></div>
          <div class="orbit-metric"><div class="orbit-muted">6. Admin View</div><div class="orbit-user-sub">หน้า Activity, Messages, Roles, Earn, Shop และ Gacha ดึงจาก DB workspace</div></div>
        </div>`;
    },
    renderInlineSummary(items) {
      return (items || []).map(([label, value]) => `<span class="orbit-inline-summary-item"><span class="orbit-muted">${this.esc(this.tl(label))}</span><strong>${Number(value || 0).toLocaleString()}</strong></span>`).join('');
    },
    toggleNavGroups() {
      const button = $('#navToggleAll');
      const expanded = String(button.data('expanded')) !== '0';
      if (expanded) {
        $('#sidebarAccordion').accordion('close', 0).accordion('close', 1).accordion('close', 2);
        button.data('expanded', '0').find('i').removeClass('fa-minus').addClass('fa-plus');
      } else {
        $('#sidebarAccordion').accordion('open', 0).accordion('open', 1).accordion('open', 2);
        button.data('expanded', '1').find('i').removeClass('fa-plus').addClass('fa-minus');
      }
    },
    bindTabs(scope) {
      const root = $(scope || document);
      root.find('[data-tab-target]').off('click').on('click', (e) => {
        e.preventDefault();
        const button = $(e.currentTarget);
        const group = button.closest('[data-tabs]');
        const target = button.data('tab-target');
        group.find('[data-tab-target]').removeClass('primary active');
        button.addClass('primary active');
        group.parent().find('[data-tab-panel]').removeClass('active');
        group.parent().find(`[data-tab-panel="${target}"]`).addClass('active');
      });
    },
    bindToggleGroups(scope) {
      const root = $(scope || document);
      root.find('[data-toggle-group]').each((_, groupEl) => {
        const group = $(groupEl);
        const inputName = group.data('target-input');
        const input = group.closest('.orbit-filter').find(`[name="${inputName}"]`);
        const current = String(input.val() || group.find('.button.active').data('value') || '');
        group.find('.button').removeClass('active primary').addClass('basic');
        group.find(`.button[data-value="${current}"]`).addClass('active primary').removeClass('basic');
      });
      root.find('[data-toggle-group] .button').off('click').on('click', (e) => {
        const button = $(e.currentTarget);
        const group = button.closest('[data-toggle-group]');
        const value = String(button.data('value'));
        const inputName = group.data('target-input');
        const input = group.closest('.orbit-filter').find(`[name="${inputName}"]`);
        input.val(value);
        group.find('.button').removeClass('active primary').addClass('basic');
        button.addClass('active primary').removeClass('basic');
        if (String(group.data('auto-apply')) === '1') {
          this.bootView(this.page);
        }
      });
    },
    userContextFromElement(el) {
      const user = $(el);
      return {
        voiceStartDate: user.data('voice-start-date') || '',
        voiceChannel: user.data('voice-channel') || '',
        asOfDurationSeconds: user.data('as-of-duration-seconds'),
        asOf: user.data('as-of') || '',
        historicalContext: String(user.data('historical-context') || '') === '1',
        typingChannel: user.data('typing-channel') || '',
        typingExpireDate: user.data('typing-expire-date') || '',
      };
    },
    parseDate(value) {
      if (!value) return null;
      const normalized = String(value).replace(' ', 'T');
      const parsed = new Date(normalized);
      if (Number.isNaN(parsed.getTime())) return null;
      return parsed;
    },
    ageSeconds(value) {
      const parsed = this.parseDate(value);
      if (!parsed) return null;
      return Math.max(0, Math.floor((Date.now() - parsed.getTime()) / 1000));
    },
    liveAgoText(value) {
      const seconds = typeof value === 'number' ? Math.max(0, value) : this.ageSeconds(value);
      if (seconds == null) return '-';
      const isEn = this.lang() === 'en';
      if (seconds < 5) return isEn ? 'just now' : 'เมื่อกี้';
      if (seconds < 60) return isEn ? `${seconds}s ago` : `${seconds} วิที่แล้ว`;
      const minutes = Math.floor(seconds / 60);
      if (minutes < 60) return isEn ? `${minutes}m ago` : `${minutes} นาทีที่แล้ว`;
      const hours = Math.floor(minutes / 60);
      if (hours < 24) return isEn ? `${hours}h ago` : `${hours} ชม.ที่แล้ว`;
      const days = Math.floor(hours / 24);
      return isEn ? `${days}d ago` : `${days} วันที่แล้ว`;
    },
    liveStamp(value, options = {}) {
      if (!value || value === '-') return '<span class="orbit-muted">-</span>';
      const parsed = this.parseDate(value);
      if (!parsed) return this.esc(value);
      const attrs = [
        `data-live-ago="${this.escAttr(value)}"`,
      ];
      if (Number(options.freshSeconds || 0) > 0) attrs.push(`data-fresh-seconds="${Math.max(1, Number(options.freshSeconds))}"`);
      if (Number(options.staleSeconds || 0) > 0) attrs.push(`data-stale-seconds="${Math.max(1, Number(options.staleSeconds))}"`);
      const className = ['orbit-live-stamp', options.pulse ? 'is-pulse' : ''].filter(Boolean).join(' ');
      const ageSeconds = Math.max(0, Math.floor((Date.now() - parsed.getTime()) / 1000));
      return `<span class="${className}" ${attrs.join(' ')}>
        <span class="orbit-live-dot"></span>
        <span class="orbit-live-label">${this.esc(value)}</span>
        <span class="orbit-live-ago">${this.esc(this.liveAgoText(ageSeconds))}</span>
      </span>`;
    },
    liveDuration(startDate) {
      const parsed = this.parseDate(startDate);
      if (!parsed) return '-';
      return `<span class="orbit-live-duration" data-start-date="${this.escAttr(startDate)}">${this.duration(Math.max(0, Math.floor((Date.now() - parsed.getTime()) / 1000)))}</span>`;
    },
    voiceDurationLabel(row) {
      if (row && row.durationIsTrusted === false) {
        const reason = row.uncertaintyReason || this.t('duration.source_incomplete');
        return `<span class="orbit-status pending" data-tooltip="${this.escAttr(this.tl(reason))}"><i class="fa-solid fa-circle-info"></i> ${this.t('duration.unknown')}</span>`;
      }
      return this.duration(row?.asOfDurationSeconds || 0);
    },
    updateLiveDurations() {
      $('.orbit-live-duration[data-start-date]').each((_, el) => {
        const start = this.parseDate($(el).data('start-date'));
        if (!start) return;
        $(el).text(this.duration(Math.max(0, Math.floor((Date.now() - start.getTime()) / 1000))));
      });
      $('.orbit-live-stamp[data-live-ago]').each((_, el) => {
        const node = $(el);
        const seconds = this.ageSeconds(node.data('live-ago'));
        if (seconds == null) return;
        node.find('.orbit-live-ago').text(this.liveAgoText(seconds));
        node.removeClass('is-fresh is-warm is-stale');
        const freshSeconds = Number(node.data('fresh-seconds') || 0);
        const staleSeconds = Number(node.data('stale-seconds') || 0);
        if (freshSeconds > 0) {
          if (seconds <= freshSeconds) node.addClass('is-fresh');
          else if (staleSeconds > 0 && seconds <= staleSeconds) node.addClass('is-warm');
          else if (staleSeconds > 0) node.addClass('is-stale');
        }
      });
    },
    channelTypeIcon(type) {
      const n = Number(type);
      if (n === 2 || n === 13) return '<i class="fa-solid fa-volume-high"></i>';
      if (n === 4) return '<i class="fa-solid fa-folder"></i>';
      if (n === 5) return '<i class="fa-solid fa-bullhorn"></i>';
      if ([10, 11, 12].includes(n)) return '<i class="fa-solid fa-comments"></i>';
      if (n === 15 || n === 16) return '<i class="fa-regular fa-rectangle-list"></i>';
      return '<i class="fa-solid fa-hashtag"></i>';
    },
    roleColors(role) {
      let colors = role.roleColors || {};
      if (!colors.primary && role.metadataJson) {
        const meta = this.parseJson(role.metadataJson);
        const raw = meta?.colors || {};
        const toHex = (value) => value == null ? null : '#' + Number(value || 0).toString(16).padStart(6, '0');
        colors = {
          primary: { hex: toHex(raw.primary_color ?? role.roleColor ?? 0), value: raw.primary_color ?? role.roleColor ?? 0 },
          secondary: { hex: toHex(raw.secondary_color), value: raw.secondary_color },
          tertiary: { hex: toHex(raw.tertiary_color), value: raw.tertiary_color },
        };
      }
      const primary = colors.primary?.hex || ('#' + (Number(role.roleColor || 0)).toString(16).padStart(6, '0'));
      const secondary = colors.secondary?.hex || primary;
      const tertiary = colors.tertiary?.hex || secondary;
      const className = colors.tertiary?.hex ? 'holographic' : (colors.secondary?.hex ? 'gradient' : '');
      const swatches = [
        ['Primary', primary, colors.primary?.value],
        colors.secondary?.hex ? ['Secondary', secondary, colors.secondary?.value] : null,
        colors.tertiary?.hex ? ['Tertiary', tertiary, colors.tertiary?.value] : null,
      ].filter(Boolean).map(([label, hex, value]) => `<span class="orbit-color-swatch"><i style="--swatch-color:${this.escAttr(hex)}"></i>${this.esc(label)} ${this.esc(hex)}${value != null ? ` · ${this.esc(value)}` : ''}</span>`).join('');
      return {
        className,
        style: `--role-color:${this.escAttr(primary)};--role-color-2:${this.escAttr(secondary)};--role-color-3:${this.escAttr(tertiary)}`,
        swatches,
      };
    },
    renderRolePermissionTable(catalog) {
      const onlyGranted = $('#rolePermissionOnlyGranted').is(':checked');
      const rows = onlyGranted ? (catalog || []).filter((p) => Number(p.isAllowed) === 1) : (catalog || []);
      this.table('#drawerRolePermissions', [
        ['Permission', 'label', (p) => `<strong>${this.esc(p.label)}</strong><div class="orbit-user-sub">${this.esc([p.code, p.badge || '', p.description || ''].filter(Boolean).join(' · '))}</div>`],
        ['On', 'isAllowed', (p) => Number(p.isAllowed) === 1 ? this.badge('on', 'success') : '-'],
        ['Off', 'isAllowed', (p) => Number(p.isAllowed) === 1 ? '-' : this.badge('off', '')],
        ['Bit', 'bit'],
      ], rows, { id: 'drawerRolePermissions' });
    },
    renderRoleRevisionSummary(row) {
      const before = this.parseJson(row.beforeJson);
      const after = this.parseJson(row.afterJson);
      if (!before && !after) return '-';
      const keys = Array.from(new Set([...Object.keys(before || {}), ...Object.keys(after || {})]));
      const important = ['name', 'roleName', 'color', 'roleColor', 'colors', 'position', 'rolePosition', 'permissions', 'hoist', 'isHoist', 'mentionable', 'isMentionable', 'managed', 'isManaged'];
      const ordered = keys.filter(k => important.includes(k)).concat(keys.filter(k => !important.includes(k))).slice(0, 12);
      const changed = ordered.filter((key) => JSON.stringify(before?.[key] ?? null) !== JSON.stringify(after?.[key] ?? null));
      if (!changed.length) return '<span class="orbit-muted">snapshot ไม่มี field ที่เปลี่ยนชัดเจน</span>';
      return `<div class="orbit-change-list">${changed.slice(0, 8).map((key) => `
        <div class="orbit-change-item">
          <strong>${this.esc(key)}</strong>
          <div class="orbit-user-sub">${this.esc(this.prettyValue(before?.[key]))} -> ${this.esc(this.prettyValue(after?.[key]))}</div>
        </div>`).join('')}</div>`;
    },
    jsonSummary(json) {
      const data = this.parseJson(json);
      if (!data) return '-';
      const entries = Array.isArray(data)
        ? [['items', data.length]]
        : Object.entries(data).slice(0, 8);
      return `<span class="orbit-json-summary">${entries.map(([key, value]) => `<span class="orbit-json-chip"><strong>${this.esc(key)}</strong>: ${this.esc(this.prettyValue(value))}</span>`).join('')}</span>`;
    },
    parseJson(value) {
      if (!value) return null;
      if (typeof value === 'object') return value;
      try { return JSON.parse(String(value)); } catch (_) { return null; }
    },
    prettyValue(value) {
      if (value === null || value === undefined) return '-';
      if (Array.isArray(value)) return `${value.length} items`;
      if (typeof value === 'object') return Object.keys(value).slice(0, 4).map((key) => `${key}:${this.prettyValue(value[key])}`).join(', ');
      if (typeof value === 'boolean') return value ? 'true' : 'false';
      return this.trunc(String(value), 80);
    },
    lang() {
      return document.documentElement.dataset.lang || 'th';
    },
    t(key, params, fallback) {
      const lang = this.lang();
      const dict = this.i18n[lang] || this.i18n.th || {};
      const en = this.i18n.en || {};
      let value = dict[key] ?? en[key] ?? fallback ?? key;
      if (params && typeof value === 'string') {
        Object.keys(params).forEach((name) => {
          value = value.replace(new RegExp('\\{' + name + '\\}', 'g'), params[name]);
        });
      }
      return value;
    },
    normalizeI18nText(value) {
      return String(value == null ? '' : value).replace(/\s+/g, ' ').trim();
    },
    buildI18nReverseMap() {
      const map = {};
      Object.keys(this.i18n || {}).forEach((lang) => {
        Object.entries(this.i18n[lang] || {}).forEach(([key, value]) => {
          if (typeof value !== 'string') return;
          const normalized = this.normalizeI18nText(value);
          if (normalized && !map[normalized]) map[normalized] = key;
        });
      });
      const aliases = {
        'Activity': 'page.activity',
        'Roles': 'page.roles',
        'Messages': 'page.messages',
        'Prize': 'page.gacha_prize',
        'Earn Settings': 'page.earn_settings',
        'Role Permission Descriptions': 'page.role_permission_descriptions',
        'Admin': 'page.admin',
        'Backfill': 'page.backfill',
        'Logs': 'page.logs',
        'Backfill Center': 'heading.backfill_center',
        'Live Status': 'heading.live_status',
        'Workers + Queued Recovery': 'heading.workers_recovery',
        'Danger Zone': 'heading.danger_zone',
        'Gachapon Prize Settings': 'heading.gacha_prize_settings',
        'Game Credit': 'heading.game_credit',
        'Tier Rates': 'heading.tier_rates',
        'Prize Items': 'heading.prize_items',
        'Prize Roles': 'heading.prize_roles',
        'Condition Timeline': 'heading.condition_timeline',
        'Staff Logs': 'heading.staff_logs',
        'Access Logs': 'heading.access_logs',
        'Message Archive': 'heading.message_archive',
        'Roles In Server': 'heading.roles_in_server',
        'Top Channels + Relationships': 'heading.top_channels_relationships',
        'ทุกห้อง': 'common.all_channels',
        'ทุกประเภท': 'common.all_types',
        'ทุก category': 'common.all_categories',
        'ทั้งหมด': 'common.all',
        'เฉพาะคน': 'filter.humans_only',
        'เฉพาะบอท': 'filter.bots_only',
        'คน + บอท': 'filter.humans_and_bots',
        'มีคน': 'filter.people',
        'มีคน ณ เวลานั้น': 'filter.occupied_at_time',
        'มี event 12h': 'filter.with_event_12h',
        '300 ห้อง': 'filter.rooms_300',
        '500 ห้อง': 'filter.rooms_500',
        '100 ห้อง': 'filter.rooms_100',
        '7 วัน': 'filter.days_7',
        '30 วัน': 'filter.days_30',
        '90 วัน': 'filter.days_90',
        '24 ชั่วโมง': 'filter.hours_24',
        'ความละเอียด': 'filter.resolution',
        'All roles': 'filter.all_roles',
        'ทุกยศ': 'filter.all_roles',
        'All permissions': 'filter.all_permissions',
        'ทุก permission': 'filter.all_permissions',
        'Managed + Manual': 'filter.managed_manual',
        'Manual only': 'filter.manual_only',
        'Managed only': 'filter.managed_only',
        'Left / inactive': 'filter.left_inactive',
        'Links': 'common.links',
        'Replies': 'common.replies',
        'Reactions': 'common.reactions',
        'Staff + Access + Audit': 'filter.staff_access_audit',
        'Staff actions': 'filter.staff_actions',
        'Access logs': 'filter.access_logs',
        'Audit compare': 'filter.audit_compare',
        'Deleted': 'badge.deleted',
        'Yes': 'common.yes',
        'No': 'common.no',
        'open/unknown': 'duration.open_unknown',
        'source incomplete': 'duration.source_incomplete',
        'over 24h': 'duration.over_24h',
        'offline/unknown': 'state.offline_unknown',
        'left server': 'state.left_server',
        'unknown': 'state.unknown',
        'boosting': 'badge.boosting',
        'banned': 'badge.banned',
        'timeout': 'badge.timeout',
        'Save Settings': 'action.save_settings',
        'Save Earn Rules': 'action.save_earn_rules',
        'Add Item Prize': 'action.add_item_prize',
        'Add Role Prize': 'action.add_role_prize',
        'Open Reset Confirmation': 'action.open_reset_confirmation',
        'Run Sync': 'action.run_sync',
        'Sync Channels': 'action.sync_channels',
        'Sync Members': 'action.sync_members',
        'Refresh Logs': 'action.refresh_logs',
        'Refresh': 'action.refresh',
        'Export': 'action.export',
        'active': 'badge.active',
        'inactive': 'badge.inactive',
        'deleted': 'badge.deleted',
        'edited': 'badge.edited',
        'muted': 'badge.muted',
        'server muted': 'badge.server_muted',
        'self muted': 'badge.self_muted',
        'deafened': 'badge.deafened',
        'server deafened': 'badge.server_deafened',
        'self deafened': 'badge.self_deafened',
        'streaming': 'badge.streaming',
        'video': 'badge.video',
        'managed': 'badge.managed',
        'manual': 'badge.manual',
        'mentionable': 'badge.mentionable',
        'hoist': 'badge.hoist',
        'on': 'badge.on',
        'off': 'badge.off',
        'visible': 'badge.visible',
        'paused': 'badge.paused',
        'voice_state_update': 'event.voice_state_update',
        'voice_users_kicked': 'event.voice_users_kicked',
        'member_ban': 'event.member_ban',
        'member_kick': 'event.member_kick',
        'member_leave': 'event.member_leave',
        'member_nickname_update': 'event.member_nickname_update',
        'member_profile_update': 'event.member_profile_update',
        'member_guild_avatar_update': 'event.member_guild_avatar_update',
        'member_timeout_start': 'event.member_timeout_start',
        'member_timeout_update': 'event.member_timeout_update',
        'member_timeout_end': 'event.member_timeout_end',
        'member_unban': 'event.member_unban',
        'member_voice_disconnect': 'event.member_voice_disconnect',
        'invite_create': 'event.invite_create',
        'invite_delete': 'event.invite_delete',
        'typing_start': 'event.typing_start',
        'presence_update': 'event.presence_update',
        'channel_name_update': 'event.channel_name_update',
        'channel_user_limit_update': 'event.channel_user_limit_update',
        'channel_status_update': 'event.channel_status_update',
        'role_name_update': 'event.role_name_update',
        'role_color_update': 'event.role_color_update',
        'message_bulk_delete': 'event.message_bulk_delete',
        'message_delete': 'event.message_delete',
        'message_update': 'event.message_update',
        'webhook_created': 'event.webhook_created',
        'webhook_deleted': 'event.webhook_deleted',
        'bot_log_skipped': 'event.bot_log_skipped',
      };
      Object.entries(aliases).forEach(([text, key]) => {
        map[this.normalizeI18nText(text)] = key;
      });
      this._i18nReverseMap = map;
      return map;
    },
    i18nKeyForText(value) {
      const normalized = this.normalizeI18nText(value);
      if (!normalized) return '';
      const map = this._i18nReverseMap || this.buildI18nReverseMap();
      return map[normalized] || '';
    },
    tl(value, params) {
      if (typeof value === 'string' && ((this.i18n[this.lang()] || {})[value] || (this.i18n.en || {})[value])) {
        return this.t(value, params, value);
      }
      const key = this.i18nKeyForText(value);
      return key ? this.t(key, params, value) : String(value == null ? '' : value);
    },
    title(page) {
      return this.t(`page.${page}`, {}, this.i18n.en[`page.${page}`] || 'Overview');
    },
    ensureViewerToken() {
      const key = 'orbit.viewerToken';
      let token = window.sessionStorage.getItem(key);
      if (token) return token;
      if (window.crypto && typeof window.crypto.randomUUID === 'function') token = window.crypto.randomUUID();
      else token = 'viewer_' + Date.now() + '_' + Math.random().toString(16).slice(2);
      window.sessionStorage.setItem(key, token);
      return token;
    },
    closeLiveViewer() {
      if (!this.viewerToken || !navigator.sendBeacon && !window.fetch) return;
      const body = JSON.stringify({ viewerToken: this.viewerToken });

      if (window.fetch) {
        window.fetch(this.baseUrl + '/api/live/close.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': this.csrf,
          },
          credentials: 'same-origin',
          body,
          keepalive: true,
        }).catch(() => {});
      }
    },
    toggleTheme() {
      const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      document.documentElement.dataset.theme = next;
      localStorage.setItem('orbit.theme', next);
      this.updateThemeIcon();
    },
    updateThemeIcon() {
      const dark = document.documentElement.dataset.theme === 'dark';
      $('#themeToggle i').toggleClass('fa-moon', !dark).toggleClass('fa-sun', dark);
    },
    toggleLanguage() {
      const next = document.documentElement.dataset.lang === 'en' ? 'th' : 'en';
      document.documentElement.dataset.lang = next;
      document.documentElement.lang = next;
      localStorage.setItem('orbit.lang', next);
      this.applyLanguage();
      $('#pageTitle').text(this.title(this.page));
      this.bootView(this.page);
    },
    applyLanguage() {
      const lang = this.lang();
      const dict = this.i18n[lang] || this.i18n.th;
      $('[data-i18n]').each((_, node) => {
        const key = $(node).data('i18n');
        if (dict[key]) node.textContent = this.t(key);
      });
      $('[data-i18n-placeholder]').each((_, node) => {
        const key = $(node).data('i18n-placeholder');
        node.setAttribute('placeholder', this.t(key, {}, node.getAttribute('placeholder') || ''));
      });
      $('[data-i18n-tooltip]').each((_, node) => {
        const key = $(node).data('i18n-tooltip');
        $(node).attr('data-tooltip', this.t(key, {}, $(node).attr('data-tooltip') || ''));
      });
      $('[data-i18n-title]').each((_, node) => {
        const key = $(node).data('i18n-title');
        node.setAttribute('title', this.t(key, {}, node.getAttribute('title') || ''));
      });
      $('#languageToggle').text(lang === 'th' ? 'EN' : 'TH').attr('title', lang === 'th' ? 'เปลี่ยนเป็น English' : 'Switch to Thai');
      this.translateStaticContent($('#pageContainer'));
      this.translateStaticContent($('#drawerBody'));
      this.translateStaticContent($('#profilePopover'));
      const drawerTitle = $('#drawerTitle').text();
      if (drawerTitle) $('#drawerTitle').text(this.tl(drawerTitle));
    },
    translateStaticContent(scope) {
      const root = scope && scope.length ? scope : $('#pageContainer');
      root.find('[data-i18n]').each((_, node) => {
        const key = $(node).data('i18n');
        if (key) node.textContent = this.t(key, {}, node.textContent || '');
      });
      root.find('[data-i18n-placeholder]').each((_, node) => {
        const key = $(node).data('i18n-placeholder');
        if (key) node.setAttribute('placeholder', this.t(key, {}, node.getAttribute('placeholder') || ''));
      });
      root.find('[data-i18n-tooltip]').each((_, node) => {
        const key = $(node).data('i18n-tooltip');
        if (key) $(node).attr('data-tooltip', this.t(key, {}, $(node).attr('data-tooltip') || ''));
      });
      root.find('h1,h2,h3,h4,button,label,option,th,.orbit-muted,.orbit-filter-note,.item,.header').addBack('h1,h2,h3,h4,button,label,option,th,.orbit-muted,.orbit-filter-note,.item,.header').each((_, node) => {
        if ($(node).find('input,select,textarea').length) return;
        if (node.children.length > 0) {
          Array.from(node.childNodes).forEach((child) => {
            if (child.nodeType !== Node.TEXT_NODE) return;
            const originalText = child.__orbitI18nOriginal || child.textContent || '';
            const compact = this.normalizeI18nText(originalText);
            if (!compact) return;
            child.__orbitI18nOriginal = originalText;
            const key = this.i18nKeyForText(compact);
            if (key) child.textContent = originalText.replace(compact, this.t(key, {}, compact));
          });
          return;
        }
        const original = node.dataset.i18nOriginal || node.textContent || '';
        const text = this.normalizeI18nText(original);
        if (!text) return;
        node.dataset.i18nOriginal = original;
        const key = this.i18nKeyForText(text);
        if (key) node.textContent = this.t(key, {}, text);
      });
      root.find('input[placeholder]').each((_, node) => {
        const original = node.dataset.i18nPlaceholderOriginal || node.getAttribute('placeholder') || '';
        node.dataset.i18nPlaceholderOriginal = original;
        const key = this.i18nKeyForText(original);
        if (key) node.setAttribute('placeholder', this.t(key, {}, original));
      });
      root.find('[data-tooltip]').each((_, node) => {
        const original = node.dataset.i18nTooltipOriginal || $(node).attr('data-tooltip') || '';
        node.dataset.i18nTooltipOriginal = original;
        const key = this.i18nKeyForText(original);
        if (key) $(node).attr('data-tooltip', this.t(key, {}, original));
      });
    },
    logout() {
      this.closeLiveViewer();
      this.api('/api/auth/logout.php', {}, 'POST').always(() => location.reload());
    },
    drawChart(id, rows) {
      const canvas = document.getElementById(id);
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      const w = canvas.width = canvas.clientWidth || 800;
      const h = canvas.height = 180;
      ctx.clearRect(0, 0, w, h);
      ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--orbit-line');
      ctx.beginPath(); ctx.moveTo(32, 12); ctx.lineTo(32, h - 28); ctx.lineTo(w - 12, h - 28); ctx.stroke();
      const max = Math.max(1, ...rows.map(r => Number(r.messageCount || 0)));
      ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--orbit-primary');
      ctx.lineWidth = 2; ctx.beginPath();
      rows.forEach((r, i) => {
        const x = 32 + (w - 48) * (rows.length <= 1 ? 0 : i / (rows.length - 1));
        const y = h - 28 - (h - 48) * (Number(r.messageCount || 0) / max);
        if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
      });
      ctx.stroke();
    },
    duration(sec) {
      sec = Number(sec || 0);
      const h = Math.floor(sec / 3600);
      const m = Math.floor((sec % 3600) / 60);
      const s = Math.floor(sec % 60);
      return h ? this.t('duration.hms', { h, m, s }) : this.t('duration.ms', { m, s });
    },
    trunc(s, n) { s = String(s || ''); return s.length > n ? s.slice(0, n - 1) + '…' : s; },
    esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); },
    escAttr(s) { return this.esc(s).replace(/"/g, '&quot;'); },
    debounce(fn, wait) {
      let t; return function () { clearTimeout(t); const args = arguments; t = setTimeout(() => fn.apply(this, args), wait); };
    },
  };

  window.OrbitApp = App;
  $(function () { App.init(); });
})(jQuery);
