# DEKPOKE End Credits

หน้า End Credits แบบเลื่อนอัตโนมัติสำหรับ DEKPOKE Discord Arcade Platform

เปิดหน้าได้จาก:

```text
https://phoureo.github.io/dekpoke/credits/
```

ถ้า GitHub Pages ยังไม่เปิด ให้ไปที่ `Settings` → `Pages` → เลือก `Deploy from a branch` → branch `main` → folder `/root` แล้วกด Save

## ไฟล์สำคัญ

- `index.html` = หน้าเว็บหลักของเครดิต
- `credits-data.js` = ไฟล์สำหรับแก้รายชื่อ / เพิ่มผู้สนับสนุน / เพิ่มบอท / แก้ข้อความเครดิต
- `credits.js` = ตัว render เครดิต ปกติไม่ต้องแก้

## วิธีแก้รายชื่อ

เปิดไฟล์ `credits-data.js` แล้วแก้เฉพาะข้อมูลในเครื่องหมายคำพูด `"..."`

ตัวอย่างเพิ่ม Nitro Booster:

```js
"Nitro Boost Supporters": [
  "User A",
  "User B",
  "User C"
]
```

ตัวอย่างเพิ่มดิสพันธมิตร:

```js
"Partner Discord Servers": [
  "Server A",
  "Server B"
]
```

ตัวอย่างเพิ่มผู้สนับสนุนบริจาค:

```js
"Donation Sponsors": [
  "Sponsor A",
  "Sponsor B"
]
```

## วิธีปรับความเร็วเครดิต

เปิด `index.html` แล้วแก้บรรทัดนี้ใน CSS:

```css
--speed: 105s;
```

เลขน้อย = เลื่อนเร็วขึ้น  
เลขมาก = เลื่อนช้าลง
