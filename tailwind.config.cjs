/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './app/**/*.php'
  ],
  theme: {
    extend: {
      colors: {
        primary: 'rgb(195, 207, 33)',
        accent: 'rgb(203, 45, 93)',
        muted: 'rgb(73, 74, 82)',
        background: 'rgb(255, 255, 255)',
        foreground: 'rgb(73, 74, 82)'
      }
    }
  },
  plugins: []
};
