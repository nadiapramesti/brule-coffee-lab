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
                // Token tema: nilainya (terang/gelap) ada di resources/css/app.css.
                // Nama lama dipertahankan supaya seluruh tampilan ikut berganti
                // tanpa menulis ulang setiap kelas.
                charcoal: 'rgb(var(--c-bg) / <alpha-value>)',      // latar halaman
                labwhite: 'rgb(var(--c-fg) / <alpha-value>)',      // teks utama
                kraft:    'rgb(var(--c-muted) / <alpha-value>)',   // teks redup, garis
                surface:  'rgb(var(--c-surface) / <alpha-value>)', // kartu, menu, sidebar
                bluetext: 'rgb(var(--c-bluetext) / <alpha-value>)',
                ambertext:'rgb(var(--c-ambertext) / <alpha-value>)',
                redtext:  'rgb(var(--c-redtext) / <alpha-value>)',
                // Tetap sama di kedua mode (di atas tombol berwarna / foto):
                paper:    '#FFFBF2',
                night:    '#121212',
                kraftink: '#5C4B33',
                bluelab:  '#2A5DB0',
            },
            borderRadius: {
                DEFAULT: '10px',
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
