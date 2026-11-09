# การพัฒนาบนเครื่อง

## โครงสร้าง

```
/                              ไฟล์ระบบเดิมระดับ root (login, lobby, auth, dbconfig)
/student_affairs/student_track/ โมดูลระบบติดตามนักเรียน (ตำแหน่งเดียวกับบน server)
/dev/sql/01_legacy_schema.sql  โครงสร้างตารางเดิมที่ใช้ (ไม่มีข้อมูล)
/dev/sql/02_lookup_data.sql    ข้อมูล lookup ที่ไม่ใช่ข้อมูลส่วนบุคคล (คำนำหน้า, สถานะ, ภาคเรียน ฯลฯ)
/dev/seed.php                  สร้างข้อมูลจำลอง
```

## ตั้งค่าครั้งแรก

1. คัดลอก `global_config.example.php` เป็น `global_config.php` (ไฟล์นี้ไม่ขึ้น git)
2. ทำ junction ให้ XAMPP มองเห็นโฟลเดอร์โปรเจกต์ (PowerShell):
   ```
   New-Item -ItemType Junction -Path C:\Users\Jay\Documents\xampp\htdocs\student_track -Target "C:\Users\Jay\Documents\ระบบติดตามนักเรียน"
   ```
3. เปิด `extension=gd` ใน `xampp\php\php.ini` (mPDF ต้องใช้)
4. เปิด MySQL และ Apache จาก XAMPP Control Panel
5. สร้างฐานข้อมูลจำลอง:
   ```
   mysql -u root -e "DROP DATABASE IF EXISTS zpqmmpsz_db; CREATE DATABASE zpqmmpsz_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   (echo SET FOREIGN_KEY_CHECKS=0; cat dev/sql/01_legacy_schema.sql dev/sql/02_lookup_data.sql) | mysql -u root --default-character-set=utf8mb4 zpqmmpsz_db
   php dev/seed.php
   php student_affairs/student_track/migrations/migrate.php
   ```
   เมื่อรัน `dev/seed.php` ใหม่ ตาราง `trk_*` (role, ตั้งค่า) จะไม่ถูกล้าง
6. ติดตั้งและ build CSS: `npm install` แล้ว `npm run build:css` (ระหว่างพัฒนาใช้ `npm run watch:css`)
7. ติดตั้ง mPDF (ไลบรารีสร้าง PDF): ในโฟลเดอร์ `student_affairs/student_track` สั่ง
   `php C:/ProgramData/ComposerSetup/bin/composer.phar install --no-dev`
   (composer.json ล็อกไว้ที่ PHP 7.4 และจะลบฟอนต์ของ mPDF ที่ไม่ได้ใช้ให้อัตโนมัติ)

## ฟอนต์ใน PDF

`assets/fonts/Sarabun-*.ttf` มาจาก Google Fonts (SIL OFL 1.1) **และถูกแก้ไขแล้ว** โดยเพิ่ม glyph ว่างสำหรับ U+200B
เพราะ mPDF แทรกอักขระนี้ระหว่างคำภาษาไทยเพื่อตัดบรรทัด ถ้าฟอนต์ไม่มี glyph นี้จะเห็นเป็นกล่อง □ ใน PDF
ถ้าดาวน์โหลดฟอนต์ใหม่ ต้องรัน `python tools/add-zwsp-glyph.py assets/fonts/Sarabun-*.ttf` อีกครั้ง (ต้องติดตั้ง fonttools)

เข้าใช้งานที่ http://localhost/student_track/login.php แล้วไปที่ http://localhost/student_track/student_affairs/student_track/

ครั้งแรก ให้ login เป็น `admin` แล้วไปที่ "จัดการสิทธิ์" กดบันทึกค่าที่ระบบแนะนำ จากนั้นบัญชีอื่นจึงจะเข้าใช้งานได้

## บัญชีจำลอง (รหัสผ่าน 1234 ทุกบัญชี)

| username | บทบาท | หมายเหตุ |
|---|---|---|
| admin | admin_type 99 (Super Admin) | |
| exec | admin_type 98 (ฝ่ายบริหาร) | |
| acad | admin_type 1 (วิชาการ) | |
| affairs | admin_type 5 (กิจการนักเรียน) | |
| t01–t10 | ครู | เป็นทั้งครูประจำวิชาและครูที่ปรึกษา (ห้อง 6/1 มีครูที่ปรึกษา 3 คน ห้อง 2/1 มีคนเดียว) |

## สถานการณ์ในข้อมูลจำลอง (ภาคเรียน 2/2569)

| นักเรียน | ห้อง | สถานการณ์ |
|---|---|---|
| 900001 | 1/1 | เคส 1: ขาดคณิตบ่อย (ขาด/ลา/หนี ปนกัน) |
| 900002 | 1/1 | เคส 1: ขาดหลายวิชาพร้อมกัน |
| 900015 | 1/1 | ลาออกแล้ว แต่มีประวัติขาด ต้องไม่แสดงในระบบ |
| 900003 | 1/1 | เคส 2: ขาด 5 วัน → กลับมาเรียน → ขาดใหม่อีก 5 วัน (ครั้งที่ 2) |
| 900016 | 1/2 | เคส 2: ขาดคร่อมวันหยุด 23 ต.ค. |
| 900017 | 1/2 | ไม่เข้าเคส 2: ขาด 4 วัน → ลา → ขาด 3 วัน |
| 900031 | 2/1 | เคส 2: มีวันที่ครูลืมเช็คชื่อ (4 พ.ย.) อยู่กลางช่วงขาด |
| 900047 | 4/1 | เคส 1 ระดับ ม.ปลาย (ไม่ต้องออก บค.14) |
| 900048 | 4/1 | ขาดยาวตั้งแต่ 27 ต.ค. ไม่กลับมาเลย |

หากต้องการให้นักเรียนข้ามเกณฑ์ครั้งที่ 2/3 ให้สร้างเช็คชื่อล่วงหน้า: `php dev/seed.php --until=2027-01-29`
ล้างเอกสาร/รอบติดตาม/แจ้งเตือนที่สร้างระหว่างทดสอบ: `php dev/seed.php --reset-tracking`

ตัวอย่างที่ใช้ทดสอบเคส 1: login เป็น `t01` → เมนู "นักเรียนขาดรายวิชา" (ภาษาไทย 1 ม.1/1 และสุขศึกษา 4 ม.4/1 มีนักเรียนถึงเกณฑ์)
ฝั่งครูที่ปรึกษา: `t01`/`t02` เป็นครูที่ปรึกษา ม.1/1 (ออก ขร.2 + บค.14), `t06`/`t07` เป็นครูที่ปรึกษา ม.4/1 (ออก ขร.2 อย่างเดียว) → เมนู "งานครูที่ปรึกษา"

หมายเหตุการทดสอบด้วย curl บน Git Bash: ข้อความภาษาไทยที่พิมพ์ในคำสั่งจะไม่ถูกส่งเป็น UTF-8 ให้เขียนค่าลงไฟล์ UTF-8 แล้วส่งด้วย `-F "field=<file.txt"`

## ข้อกำหนดของโค้ด

- **PHP 7.4:** production ใช้ PHP 7.4 ห้ามใช้ `match`, named arguments, nullsafe `?->`, `str_contains`, union types, enum, readonly (XAMPP บนเครื่องเป็น PHP 8.0 จึงไม่เตือน)
- **CSS:** host ไม่มี Node.js ให้ build `app.css` บนเครื่องแล้ว commit ไฟล์ที่ build แล้วด้วย
