/** @type {import('tailwindcss').Config} */
export default {
    content: ['./resources/views/**/*.blade.php', './resources/js/**/*.{vue,js}'],
    theme: {
        extend: {
            colors: {
                gold: { 50: '#fdf8e7', 100: '#faefc2', 200: '#f6e08d', 300: '#f3d57a', 400: '#e5bd4f', 500: '#d4a63a', 600: '#b58626', 700: '#9a7418', 800: '#6f5212', 900: '#4a360c' },
                ink: { 50: '#f6efde', 200: '#cfc3a3', 300: '#a89a78', 700: '#3a2e17', 800: '#231c10', 900: '#1a150c', 950: '#0f0c07' },
            },
            fontFamily: { display: ['Cormorant Garamond', 'serif'], sans: ['Manrope', 'system-ui', 'sans-serif'] },
            backgroundImage: { 'gold-grad': 'linear-gradient(135deg, #f3d57a 0%, #d4a63a 50%, #9a7418 100%)' },
        },
    },
    plugins: [],
};
