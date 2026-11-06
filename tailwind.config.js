import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Kantumruy Pro"', '"Noto Sans Khmer"', 'Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Space Grotesk"', '"Syne"', '"Kantumruy Pro"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    black: '#111111',
                    dark: '#1A1A1A',
                    bone: '#F5F4F0',
                    offwhite: '#FAF9F5',
                    accent: '#E65100',
                    'accent-hover': '#CC4400',
                    gray: '#737373',
                    border: '#E8E7E3',
                    'card-bg': '#F9F8F5',
                },
            },
            letterSpacing: {
                tighter: '-0.04em',
                tight: '-0.02em',
                wide: '0.04em',
                widest: '0.12em',
            },
        },
    },

    plugins: [forms],
};
