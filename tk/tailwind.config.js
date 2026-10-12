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
                body: ['Merriweather', 'Georgia', 'serif'],
                sans: ['Merriweather', 'Georgia', 'serif'],
            },
            colors: {
                'tk-bg': '#f8fafc',
                'tk-surface': '#ffffff',
                'tk-ink': '#0f172a',
                'tk-muted': '#64748b',
                'tk-line': '#cbd5e1',
                'tk-soft': '#f1f5f9',
                'tk-accent': '#0891b2',
                'tk-accent-soft': '#e0f2fe',
                'tk-slate': '#334155',
            },
        },
    },

    plugins: [forms],
};
