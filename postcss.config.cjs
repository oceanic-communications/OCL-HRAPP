// Tailwind is applied by @tailwindcss/vite in vite.config.js; avoid loading
// tailwindcss here as the PostCSS entry (v4 requires @tailwindcss/postcss).
module.exports = {
  plugins: {
    autoprefixer: {},
  },
};
