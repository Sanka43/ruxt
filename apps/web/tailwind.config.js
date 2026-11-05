/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        background: '#0B0F19',
        surface: '#111827',
        'surface-hover': '#1F2937',
        border: '#1F2937',
        accent: '#6366F1',
        'accent-hover': '#4F46E5',
        success: '#10B981',
        warning: '#F59E0B',
      }
    },
  },
  plugins: [],
}
