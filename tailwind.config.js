import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                charcoal:  '#121212',
                labwhite:  '#FFFBF2',
                kraft:     '#D8C5A6',
                bluelab:   '#2A5DB0',
            },
            fontFamily: {
                display: ['Oswald', 'sans-serif'],   // buat heading tebal
                mono:    ['JetBrains Mono', 'monospace'], // vibes lab
            },
        },
    },

    plugins: [forms],
};
