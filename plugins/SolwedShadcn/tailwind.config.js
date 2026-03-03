/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './View/**/*.twig',
    './Assets/JS/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        background: 'oklch(98% 0 0)',
        foreground: 'oklch(32.3% 0.010 207)',
        card: {
          DEFAULT: 'oklch(94% 0.003 207)',
          foreground: 'oklch(32.3% 0.010 207)',
        },
        muted: {
          DEFAULT: 'oklch(90% 0.005 207)',
          foreground: 'oklch(50% 0.008 207)',
        },
        border: 'oklch(90% 0.005 207)',
        input: 'oklch(90% 0.005 207)',
        ring: 'oklch(32.3% 0.010 207)',
        destructive: {
          DEFAULT: 'oklch(60% 0.2 25)',
          foreground: 'oklch(98% 0 0)',
        },
        accent: {
          DEFAULT: 'oklch(90.9% 0.1944 106.8)',
          content: 'oklch(32.3% 0.010 207)',
        },
        success: {
          DEFAULT: 'oklch(65% 0.17 145)',
          foreground: 'oklch(98% 0 0)',
        },
        warning: {
          DEFAULT: 'oklch(80% 0.15 80)',
          foreground: 'oklch(32.3% 0.010 207)',
        },
        info: {
          DEFAULT: 'oklch(65% 0.15 240)',
          foreground: 'oklch(98% 0 0)',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
        heading: ['Orbitron', 'Inter', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        DEFAULT: '0.5rem',
      },
    },
  },
  plugins: [],
};
