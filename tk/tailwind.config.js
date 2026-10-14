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
                ui: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Merriweather', 'Georgia', 'serif'],
                body: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
                sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'tk-bg': '#f3f5f7',
                'tk-surface': '#ffffff',
                'tk-ink': '#0f1419',
                'tk-muted': '#5b6573',
                'tk-line': '#dce2ea',
                'tk-soft': '#eef1f5',
                'tk-accent': '#0a7c94',
                'tk-accent-soft': '#e6f4f7',
                'tk-sidebar': '#151c26',
                'tk-slate': '#2a3441',
            },
            boxShadow: {
                'tk-xs': '0 1px 2px 0 rgb(15 20 25 / 0.04)',
                'tk-sm': '0 1px 3px 0 rgb(15 20 25 / 0.06), 0 1px 2px -1px rgb(15 20 25 / 0.04)',
                'tk-md': '0 8px 24px -8px rgb(15 20 25 / 0.12)',
                'tk-lg': '0 16px 48px -16px rgb(15 20 25 / 0.16)',
            },
            borderRadius: {
                tk: '0.75rem',
            },
            letterSpacing: {
                'tk-label': '0.08em',
            },
        },
    },

    plugins: [forms],
};
