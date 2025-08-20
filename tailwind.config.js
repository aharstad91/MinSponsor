/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./**/*.html",
    "./src/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        // Legg til egne farger her hvis ønskelig
        'spons-blue': '#1e40af',
        'spons-gray': '#6b7280',
      },
      fontFamily: {
        // Legg til egne fonter her hvis ønskelig
      },
    },
  },
  plugins: [
    // Du kan legge til Tailwind plugins her, f.eks:
    // require('@tailwindcss/typography'),
    // require('@tailwindcss/forms'),
  ],
}
