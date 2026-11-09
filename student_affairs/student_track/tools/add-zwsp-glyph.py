"""
เพิ่ม glyph ว่างความกว้าง 0 สำหรับ U+200B (Zero Width Space) ลงในฟอนต์ Sarabun

mPDF ตัดคำภาษาไทยโดยแทรก U+200B ระหว่างคำ แล้ววาดอักขระนี้ด้วยฟอนต์ปัจจุบัน
Sarabun จาก Google Fonts ไม่มี glyph นี้ จึงแสดงเป็นกล่อง □ ใน PDF

ใช้:  python tools/add-zwsp-glyph.py assets/fonts/Sarabun-*.ttf
(ต้องติดตั้ง fonttools: python -m pip install fonttools)
Sarabun ใช้ SIL OFL 1.1 ไม่มี Reserved Font Name จึงแก้ไขได้โดยไม่ต้องเปลี่ยนชื่อ
"""
import sys

from fontTools.pens.ttGlyphPen import TTGlyphPen
from fontTools.ttLib import TTFont

ZWSP = 0x200B
GLYPH = "uni200B"

for path in sys.argv[1:]:
    font = TTFont(path)
    if any(ZWSP in table.cmap for table in font["cmap"].tables if table.isUnicode()):
        print(f"{path}: already has U+200B")
        continue

    order = font.getGlyphOrder() + [GLYPH]
    font.setGlyphOrder(order)
    font["glyf"].glyphOrder = order
    font["glyf"][GLYPH] = TTGlyphPen(None).glyph()  # ไม่มีเส้น
    font["hmtx"][GLYPH] = (0, 0)  # กว้าง 0
    for table in font["cmap"].tables:
        if table.isUnicode():
            table.cmap[ZWSP] = GLYPH
    if "DSIG" in font:  # ลายเซ็นเดิมใช้ไม่ได้หลังแก้ไฟล์
        del font["DSIG"]
    font.save(path)
    print(f"{path}: added U+200B")
