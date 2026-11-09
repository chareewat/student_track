/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './student_affairs/student_track/**/*.php',
    './student_affairs/student_track/assets/js/**/*.js',
  ],
  theme: {
    extend: {
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
