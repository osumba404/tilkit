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
            fontFamily: {
                sans: ['Public Sans', ...defaultTheme.fontFamily.sans],
                serif: ['"Source Serif 4"', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                'tk-bg': '#eef2f5',
                'tk-surface': '#ffffff',
                'tk-ink': '#12263a',
                'tk-muted': '#506274',
                'tk-line': '#c9d3db',
                'tk-soft': '#e3ebf0',
                'tk-accent': '#0e7c86',
                'tk-accent-soft': '#d6eef0',
            },
        },
    },

    plugins: [forms],
};
