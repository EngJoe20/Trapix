import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'

export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js'
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Raleway', ...defaultTheme.fontFamily.sans],
        mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
      },
      screens: {
        'midmd': '880px',
      },
      colors: {
        primary: '#1d4ed8',
        bg: 'rgb(var(--color-bg))',
        box: {
          bg: 'rgb(var(--color-box) / <alpha-value>)',
          border: 'rgb(var(--box-border) / <alpha-value>)',
          shadow: 'rgb(var(--box-sd) / <alpha-value>)',
        },
        heading: {
          1: 'rgb(var(--heading-1) / <alpha-value>)',
          2: 'rgb(var(--heading-2) / <alpha-value>)',
          3: 'rgb(var(--heading-3) / <alpha-value>)',
        },
      }
    },
  },
  plugins: [forms],
}