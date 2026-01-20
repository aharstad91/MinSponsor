/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./**/*.html",
    "./src/**/*.js",
    "./includes/**/*.php",
  ],
  theme: {
    extend: {
      // MinSponsor Designsystem
      colors: {
        // Primærfarger
        'korall': {
          DEFAULT: '#F6A586',
          light: '#F9C4B0',
          dark: '#D97757',
        },
        'beige': {
          DEFAULT: '#F5EFE6',
          light: '#FBF8F3', // Kremhvit
          dark: '#E8E2D9',  // Soft grå
        },
        'brun': {
          DEFAULT: '#3D3228',
          light: '#5A4D3F',
          dark: '#2A231C',
        },
        // Sekundærfarger
        'terrakotta': {
          DEFAULT: '#D97757',
          light: '#E89A7F',
          dark: '#B85D42', // Dyp terrakotta for hover
        },
        'krem': '#FBF8F3',
        'softgra': '#E8E2D9',
        // Aksentfarger
        'gul': '#F4C85E',
        // Aliaser for enkel bruk
        'primary': '#F6A586',
        'cta': '#D97757',
        'text': '#3D3228',
        'background': '#F5EFE6',
      },
      fontFamily: {
        'heading': ['Inter', 'DM Sans', 'sans-serif'],
        'body': ['Inter', 'Source Sans Pro', 'sans-serif'],
      },
      fontSize: {
        'h1': ['48px', { lineHeight: '1.2', fontWeight: '700' }],
        'h2': ['36px', { lineHeight: '1.3', fontWeight: '600' }],
        'h3': ['28px', { lineHeight: '1.4', fontWeight: '600' }],
        'h4': ['20px', { lineHeight: '1.5', fontWeight: '500' }],
        'body': ['16px', { lineHeight: '1.7' }],
        'body-lg': ['18px', { lineHeight: '1.7' }],
      },
      borderRadius: {
        'sm': '8px',
        'md': '16px',
        'lg': '24px',
      },
      spacing: {
        // Base unit: 8px
        'unit': '8px',
        'section': '80px',
        'section-lg': '120px',
      },
      boxShadow: {
        'warm': '0 4px 20px rgba(61, 50, 40, 0.08)',
        'warm-lg': '0 8px 30px rgba(61, 50, 40, 0.12)',
        'warm-sm': '0 2px 10px rgba(61, 50, 40, 0.06)',
      },
    },
  },
  plugins: [],
}
