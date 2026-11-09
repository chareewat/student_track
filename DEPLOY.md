# ขึ้นระบบติดตามนักเรียนบน production

> ใช้เวลาประมาณ 30–45 นาที ควรทำหลังเลิกเรียน (ขั้นที่ 4 เพิ่ม index บนตารางเช็คชื่อ)
> ระบบใหม่**อ่านอย่างเดียว**จากตารางเดิม (เช็คชื่อ, นักเรียน, ครู) และเขียนเฉพาะตาราง `trk_*` ของตัวเอง

## 1. เตรียมไฟล์บนเครื่อง

ในโฟลเดอร์โปรเจกต์:

```
npm run build:css
cd student_affairs/student_track
php C:/ProgramData/ComposerSetup/bin/composer.phar install --no-dev
cd ../..
php student_affairs/student_track/tools/build-release.php
```

ได้ 2 ไฟล์ในโฟลเดอร์ `dist/`
- `student_track-YYYYMMDD.zip` (ประมาณ 3 MB รวม mPDF แล้ว)
- `install-YYYYMMDD.sql` (สร้างตาราง `trk_*` + index รันซ้ำได้ไม่เสียหาย)

## 2. สำรองข้อมูล

ใน phpMyAdmin → Export ฐานข้อมูล `zpqmmpsz_db` **อย่างน้อยโครงสร้าง (Structure only)** ของตาราง
`vic_attendance_teach`, `vic_attendance_subject`, `sch_attendance`, `t_teachtable`
(ขั้นที่ 4 เพิ่ม index ให้ตารางเหล่านี้ ไม่แก้ข้อมูล)

## 3. อัปโหลดไฟล์

1. cPanel → File Manager → เข้าโฟลเดอร์ `student_affairs/` (ที่เดียวกับ `checkinrow`, `advisor`)
2. อัปโหลด `student_track-YYYYMMDD.zip` แล้ว Extract → ได้โฟลเดอร์ `student_affairs/student_track/`
3. ตั้งสิทธิ์ `student_track/storage/tmp` และ `student_track/storage/uploads` เป็น **775** (ให้ PHP เขียนได้)
4. ลบไฟล์ zip ที่อัปโหลดออก

## 4. สร้างตาราง

phpMyAdmin → เลือกฐานข้อมูล `zpqmmpsz_db` → แท็บ Import → เลือก `install-YYYYMMDD.sql` → Go

ถ้าไฟล์ใหญ่หรือ timeout ให้เปิดไฟล์แล้ว copy ทีละส่วน (แบ่งด้วยบรรทัด `-- ====`) ไปวางในแท็บ SQL

## 5. ตรวจสอบระบบ

1. Login ด้วยบัญชี **Super Admin** (admin_type 99) ตามปกติ
2. เปิด `https://rwsservice.online/student_affairs/student_track/admin/system.php`
3. ต้องไม่มีรายการ **"ไม่ผ่าน"** ส่วน **"ควรแก้"** อ่านแล้วตัดสินใจ (เช่น วิชาที่ไม่มีหน่วยกิต)
4. ตรวจว่าไฟล์ภายในเปิดจากเว็บไม่ได้ (ต้องได้ 403 Forbidden):
   - `.../student_track/lib/db.php`
   - `.../student_track/vendor/autoload.php`
   - `.../student_track/storage/`

   ถ้าเปิดได้ แปลว่า host ไม่อ่าน `.htaccess` ให้แจ้งผู้ดูแล host

## 6. ตั้งค่าครั้งแรก

1. เมนู **จัดการสิทธิ์** → ตรวจ role ที่ระบบแนะนำจากสิทธิ์เดิม → **บันทึก** (ก่อนบันทึก มีแค่ Super Admin ที่เข้าระบบได้)
2. เมนู **ตั้งค่า**
   - ชื่อผู้อำนวยการ / รองผู้อำนวยการกลุ่มบริหารวิชาการ (พิมพ์ลงเอกสาร)
   - โทรศัพท์กลุ่มบริหารวิชาการ (พิมพ์ลง ขร.5)
   - เลขนำหน้าหนังสือ (ค่าเริ่มต้น `ศธ 04294.32/`)
   - ชั่วโมงเรียนของวิชาที่ไม่มีหน่วยกิต (ถ้ามี)
3. เปิด **ตรวจสอบระบบ** อีกครั้ง

## 7. ทดลองก่อนเปิดใช้จริง

ทำกับนักเรียนจริง 1 คน แล้ว**ยกเลิกเอกสาร**ทิ้งเมื่อเสร็จ (ยกเลิกไม่ลบข้อมูล นักเรียนจะกลับไปอยู่ในรายชื่อ)

- [ ] ครูประจำวิชา: เมนู "นักเรียนขาดรายวิชา" แสดงตัวเลขตรงกับระบบเช็คชื่อเดิม → ออก ขร.1 → พิมพ์ PDF ภาษาไทยถูกต้อง
- [ ] ดาวน์โหลด Word ของ ขร.1 แล้วเปิดใน Word บนเครื่องของโรงเรียน ตรวจว่าใช้ฟอนต์ TH SarabunIT๙ และบรรทัดไม่ล้น
- [ ] ครูที่ปรึกษา: ได้รับแจ้งเตือน → ออก ขร.2 (+ บค.14 ถ้า ม.1–3) → บันทึกผลติดตามพร้อมรูปจากมือถือ → ขร.3
- [ ] ครูที่ปรึกษา: เมนู "นักเรียนขาดติดต่อกัน" ตรงกับการเช็คชื่อรายวัน
- [ ] เจ้าหน้าที่วิชาการ / ผู้บริหาร: เมนู "ภาพรวม" และดาวน์โหลด CSV เปิดใน Excel ได้
- [ ] ยกเลิกเอกสารที่ทดลองทั้งหมด (ยกเลิกจากขั้นล่าสุดย้อนขึ้นไป)

## 8. เปิดให้ใช้งาน

- อัปโหลด `lobby.php` (จาก root ของโปรเจกต์) ทับไฟล์เดิมที่ root ของเว็บ **สำรองไฟล์เดิมก่อน**
  - การ์ด "บันทึกติดตามนักเรียน" เดิมถูกแทนด้วย "ระบบติดตามนักเรียน" (ใช้ id เดิม จึงอยู่ตำแหน่งเดิมตามที่ admin จัดไว้)
  - การ์ด "รายงานการติดตาม" เดิมถูกเอาออก (ใช้เมนู "ภาพรวม" ในระบบใหม่แทน)
  - การ์ดมีตัวเลขแจ้งเตือนที่ยังไม่ได้อ่านของระบบใหม่
  - ระบบเดิม (`student_affairs/advisor/studenttrack*.php`) ยังอยู่บน server เพียงแต่ไม่มีลิงก์จาก lobby ข้อมูลเก่าในตาราง `std_pursuance` ยังอยู่ครบ
- แจ้งฝ่ายวิชาการ: ขร.1 ในระบบใช้เกณฑ์ **10 / 20 / 30%** (ฟอร์มกระดาษเดิมเขียน 40%)
- แจ้งครู: ต้องออกเอกสารเรียงครั้ง (1 → 2 → 3) และเลข ขร.2 / ขร.5 ต้องขอจากงานสารบรรณก่อน

## ถ้าต้องถอนระบบออก

ระบบไม่แก้ข้อมูลเดิม ถอนได้โดยลบโฟลเดอร์ `student_affairs/student_track/` และรันใน phpMyAdmin:

```sql
DROP TABLE IF EXISTS trk_followup_photos, trk_followups, trk_notifications, trk_document_rounds,
  trk_documents, trk_rounds, trk_audit_log, trk_subject_hours, trk_settings, trk_user_roles, trk_migrations;
ALTER TABLE vic_attendance_teach DROP INDEX IF EXISTS trk_idx_sem_subject_room, DROP INDEX IF EXISTS trk_idx_teacher_sem;
ALTER TABLE vic_attendance_subject DROP INDEX IF EXISTS trk_idx_student_teach;
ALTER TABLE sch_attendance DROP INDEX IF EXISTS trk_idx_sem_student_day;
ALTER TABLE t_teachtable DROP INDEX IF EXISTS trk_idx_teacher_sem;
```

(index ที่เพิ่มไม่มีผลเสียต่อระบบเดิม จะเก็บไว้ก็ได้)

## อัปเดตเวอร์ชันถัดไป

1. สร้างชุดไฟล์ใหม่ด้วย `build-release.php`
2. Extract ทับโฟลเดอร์เดิม (`storage/uploads` ในไฟล์ zip ว่าง จึงไม่ทับรูปที่มีอยู่)
3. รัน `install-*.sql` ใหม่ (สร้างเฉพาะที่ยังไม่มี)
4. เปิด **ตรวจสอบระบบ**
