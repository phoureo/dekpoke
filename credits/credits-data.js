/*
  DEKPOKE End Credits Data
  แก้รายชื่อที่ไฟล์นี้ไฟล์เดียวได้เลย

  วิธีเพิ่มชื่อ:
  - ใส่ชื่อในเครื่องหมายคำพูด "..."
  - ถ้ามีหลายชื่อ ให้คั่นแต่ละบรรทัดด้วย comma (,)
  - ตัวอย่าง: "ชื่อคนที่ 1", "ชื่อคนที่ 2"
*/

window.DEKPOKE_CREDITS = {
  title: "DEKPOKE",
  kicker: "End Credits",
  subtitle: [
    "Discord Arcade Platform",
    "Game System / Rank System / Reward System / Community Operation"
  ],

  coreTeam: [
    { role: "Created by", names: ["DEKPOKE Team"] },
    { role: "Platform Owner", names: ["[ชื่อเจ้าของระบบ]"] },
    { role: "Discord Server Owner", names: ["[ชื่อเจ้าของเซิร์ฟเวอร์ Discord]"] },
    { role: "Project Direction", names: ["[ชื่อผู้ดูแลโปรเจกต์]"] },
    { role: "Meeting Coordinator", names: ["เรียบเรียงการประชุม / สรุปแผนงาน / จัดลำดับงานระบบ"] }
  ],

  departments: [
    {
      title: "Discord Operation",
      type: "list",
      items: [
        "Discord Guild System",
        "Member Sync System",
        "Channel Data System",
        "Message Activity Log",
        "Voice Activity Log",
        "Community Activity Summary"
      ]
    },
    {
      title: "Role Management Department",
      type: "department",
      role: "แผนกจัดการยศ",
      people: ["[ชื่อทีม / รายชื่อผู้ดูแลยศ]"],
      note: "ดูแลฐานข้อมูลยศ การผูกยศกับสมาชิก การซิงก์ยศ และประวัติการเปลี่ยนแปลงยศ",
      pills: [
        "Discord Role Database",
        "Member Role Assignment",
        "Role Permission Tracking",
        "Role Revision History",
        "Rank Role Sync",
        "Community Rank Operation"
      ]
    },
    {
      title: "Reward Management Department",
      type: "department",
      role: "แผนกจัดการของรางวัล",
      people: ["[ชื่อทีม / รายชื่อผู้ดูแลของรางวัล]"],
      note: "ดูแลเงื่อนไขรางวัล รายการของรางวัล ประวัติการแจก คลังไอเทม และขั้นตอนการรับรางวัล",
      pills: [
        "Reward Rule Management",
        "Reward Event Logging",
        "Prize Item Management",
        "User Inventory System",
        "Reward Claim Flow",
        "Shop Item Control"
      ]
    },
    {
      title: "Game Arcade System",
      type: "list",
      items: [
        "Discord Login",
        "Coin Insert Flow",
        "Arcade Play Session",
        "Run Token Verification",
        "Score Capture System",
        "Auto Save Score",
        "Rank Board Sync",
        "Leaderboard Display"
      ]
    },
    {
      title: "Coin & Wallet System",
      type: "pills",
      items: [
        "Player Wallet",
        "Coin Balance",
        "Wallet Ledger",
        "Transaction History",
        "Top-up Package Support",
        "Coin Usage Report"
      ]
    },
    {
      title: "Admin System",
      type: "pills",
      items: [
        "Admin User Management",
        "Admin Session Control",
        "Admin Action Audit",
        "Access Log System",
        "Manual Review Support",
        "Backend Report System"
      ]
    },
    {
      title: "Security & Fair Play",
      type: "list",
      items: [
        "Score Validation",
        "Session Verification",
        "Transaction Logging",
        "Anti-abuse Review",
        "Admin Audit Trail"
      ]
    }
  ],

  supporters: {
    "Nitro Boost Supporters": [
      "[ชื่อ Booster 1]",
      "[ชื่อ Booster 2]",
      "[ชื่อ Booster 3]"
    ],
    "Donation Sponsors": [
      "[ชื่อผู้สนับสนุนบริจาค 1]",
      "[ชื่อผู้สนับสนุนบริจาค 2]",
      "[ชื่อผู้สนับสนุนบริจาค 3]"
    ],
    "Partner Discord Servers": [
      "[ชื่อดิสพันธมิตร 1]",
      "[ชื่อดิสพันธมิตร 2]",
      "[ชื่อดิสพันธมิตร 3]"
    ],
    "Community Support": [
      "Moderators",
      "Early Testers",
      "Arcade Players"
    ]
  },

  botSystem: {
    mainRole: "ระบบบอทที่ใช้ในเซิร์ฟเวอร์",
    mainBot: "[ชื่อบอทหลักของเซิร์ฟเวอร์]",
    note: "ใช้สำหรับจัดการสมาชิก ยศ กิจกรรม ประกาศ การตรวจสอบ และการเชื่อมต่อระบบรางวัล",
    bots: [
      "DEKPOKE Discord Bot",
      "Role Sync Bot",
      "Reward Automation Bot",
      "Moderation Bot",
      "Log & Audit Bot",
      "Announcement Bot"
    ]
  },

  database: [
    "Discord Guild Data",
    "User Data",
    "Member Data",
    "Role Data",
    "Channel Data",
    "Message Data",
    "Reward Data",
    "Wallet Data",
    "Inventory Data",
    "Admin Action Data"
  ],

  specialThanks: [
    "To all players, testers, moderators, Discord members,",
    "Nitro Boosters, partner communities, donation sponsors,",
    "and everyone who helped test the arcade system,",
    "report issues, manage roles, verify rewards,",
    "and improve the DEKPOKE community."
  ],

  poweredBy: ["HTML5", "JavaScript", "PHP", "MySQL", "Discord API"],

  version: "DEKPOKE Arcade Platform 2026",

  legal: [
    "© 2026 DEKPOKE.",
    "DEKPOKE, POKECHIP, POKEBIT, game titles, logos, rank systems, reward systems, Discord arcade systems, and related trademarks are property of DEKPOKE. All rights reserved."
  ],

  endMark: "THANK YOU"
};
