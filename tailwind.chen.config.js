/** Standalone Tailwind config for the Chen platform UI (built separately from posni's Mix pipeline). */
//
// Rebuild after changing any Chen blade that uses new utility classes:
//   npx -y tailwindcss@3.4.17 -c tailwind.chen.config.js -i resources/chen/app.css -o public/chen/app.css --minify
//
module.exports = {
  content: [
    './resources/views/chen/**/*.blade.php',
    './app/Chen/**/Views/**/*.blade.php',
  ],
  theme: { extend: {} },
  plugins: [],
};
