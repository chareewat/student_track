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
    },
  },
  plugins: [],
};
