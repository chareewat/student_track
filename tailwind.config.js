/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './student_affairs/student_track/**/*.php',
    './student_affairs/student_track/assets/js/**/*.js',
    './demo/**/*.php', // โหมดสาธิต (เฉพาะเครื่องพัฒนา)
  ],
  theme: {
    extend: {
      // ตัวอักษร UI ใหญ่กว่าค่าเริ่มต้นของ Tailwind 10% (เฉพาะขนาดตัวอักษร ระยะห่าง/ไอคอนคงเดิม)
      fontSize: {
        xs: ['0.825rem', { lineHeight: '1.1rem' }],
        sm: ['0.9625rem', { lineHeight: '1.375rem' }],
        base: ['1.1rem', { lineHeight: '1.65rem' }],
        lg: ['1.2375rem', { lineHeight: '1.925rem' }],
        xl: ['1.375rem', { lineHeight: '1.925rem' }],
        '2xl': ['1.65rem', { lineHeight: '2.2rem' }],
        '3xl': ['2.0625rem', { lineHeight: '2.475rem' }],
        '4xl': ['2.475rem', { lineHeight: '2.75rem' }],
      },
      fontFamily: {
        sans: ['"Noto Sans Thai"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      // โทนสีหลักของระบบ (ดู dev/README.md "UI")
      colors: {
        canvas: '#F1EFEC', // พื้นหลังหน้า
        sand: '#D4C9BE',   // เส้นขอบ หัวตาราง ป้าย ปุ่มรอง
        navy: {
          DEFAULT: '#123458', // แบรนด์ หัวข้อ เมนูที่เลือก ปุ่มนำทาง
          600: '#0E2A47',
          50: '#E8EDF3',
        },
        ink: '#030303',    // ตัวอักษร
        accent: {
          DEFAULT: '#EE6200', // สีเน้นตัดกับ navy: ไอคอนหัวหน้า แถบเมนูที่เลือก ตัวนับ รายการใหม่/เกินกำหนด
          600: '#C24E00',     // พื้นปุ่ม/ตัวอักษรสีส้ม (ตัดกับขาวผ่าน AA)
          100: '#FFE0C7',
          50: '#FFF3EA',
        },
      },
      boxShadow: {
        soft: '0 1px 2px rgba(18, 52, 88, 0.05), 0 8px 24px -6px rgba(18, 52, 88, 0.10)',
        lift: '0 2px 4px rgba(18, 52, 88, 0.06), 0 14px 32px -8px rgba(18, 52, 88, 0.18)',
        btn: '0 1px 2px rgba(3, 3, 3, 0.08), 0 4px 10px -4px rgba(3, 3, 3, 0.25)',
      },
      borderRadius: {
        '4xl': '1.75rem',
      },
    },
  },
  plugins: [],
};
