/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./auth/**/*.php",
    "./admin/**/*.php",
    "./divisions/**/*.php",
    "./includes/**/*.php",
    "./assets/js/**/*.js"
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        gold: {
          50: '#fffbeb',
          100: '#fef3c7',
          500: '#f59e0b',
          600: '#d97706',
          700: '#b45309',
        }
      }
    },
  },
  plugins: [],
}

