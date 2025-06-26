/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/**/*.{html,ts}"],
  theme: {
    extend: {
      colors: {
        "brand-background": "#121212",
        "brand-surface": "#1E1E1E",
        "brand-text": "#F5F5F5",
        "brand-text-secondary": "#A1A1AA",
        "brand-accent": "#BEF264",
      },
      fontFamily: {
        sans: ["Inter", "sans-serif"],
      },
    },
  },
  plugins: [require("@tailwindcss/typography")],
};
