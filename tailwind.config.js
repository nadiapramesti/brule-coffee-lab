import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                charcoal: '#121212',
                labwhite: '#FFFBF2',
                kraft:    '#D8C5A6',
                kraftink: '#5C4B33',
                bluelab:  '#2A5DB0',
            },
            borderRadius: {
                DEFAULT: '3px',
            },
            fontFamily: {
                display: ['IBM Plex Sans Condensed', 'system-ui', 'sans-serif'],
                sans:    ['IBM Plex Sans', 'system-ui', 'sans-serif'],
                mono:    ['IBM Plex Mono', 'ui-monospace', 'monospace'],
            },
        },
    },

    plugins: [forms],
};
