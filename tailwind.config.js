/** @type {import('tailwindcss').Config} */
const rgb = (name) => `rgb(var(--${name}) / <alpha-value>)`;
const scale = (prefix, keys) => Object.fromEntries(keys.map((k) => [k, rgb(`${prefix}-${k}`)]));

export default {
    content: ['./resources/views/**/*.blade.php', './resources/js/**/*.{vue,js}'],
    theme: {
        extend: {
            // The values live in resources/css/app.css (defaults) and are replaced per user by resources/js/lib/theme.js
            colors: {
                gold: scale('gold', [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]),
                ink: scale('ink', [50, 200, 300, 700, 800, 900, 950]),
            },
            fontFamily: { display: ['Cormorant Garamond', 'serif'], sans: ['Manrope', 'system-ui', 'sans-serif'] },
            backgroundImage: {
                'gold-grad': 'linear-gradient(135deg, rgb(var(--gold-300)) 0%, rgb(var(--gold-500)) 50%, rgb(var(--gold-700)) 100%)',
            },
        },
    },
    plugins: [],
};
