import forms from '@tailwindcss/forms';

export default {
  content: ['./resources/**/*.blade.php', './resources/**/*.js', './resources/**/*.vue'],
  theme: {
    extend: {
      fontFamily: { sans: ['Inter', 'sans-serif'] },
      colors: {
        brand: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 900: '#1e3a8a' },
        surface: '#f8fafc',
      },
      boxShadow: { 'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)' }
    }
  },
  plugins: [ forms ],
};
