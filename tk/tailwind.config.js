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
                ui: ['Merriweather', 'Georgia', ...defaultTheme.fontFamily.serif],
                display: ['Merriweather', 'Georgia', ...defaultTheme.fontFamily.serif],
                body: ['Merriweather', 'Georgia', ...defaultTheme.fontFamily.serif],
                sans: ['Merriweather', 'Georgia', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                'tk-bg': 'rgb(var(--tk-bg) / <alpha-value>)',
                'tk-surface': 'rgb(var(--tk-surface) / <alpha-value>)',
                'tk-ink': 'rgb(var(--tk-ink) / <alpha-value>)',
                'tk-muted': 'rgb(var(--tk-muted) / <alpha-value>)',
                'tk-line': 'rgb(var(--tk-line) / <alpha-value>)',
                'tk-soft': 'rgb(var(--tk-soft) / <alpha-value>)',
                'tk-accent': '#0a7c94',
                'tk-accent-soft': 'rgb(var(--tk-accent-soft) / <alpha-value>)',
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
            keyframes: {
                'tk-fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'tk-fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'tk-fade-in-down': {
                    '0%': { opacity: '0', transform: 'translateY(-6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'tk-scale-in': {
                    '0%': { opacity: '0', transform: 'scale(0.97)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                'tk-slide-in-right': {
                    '0%': { opacity: '0', transform: 'translateX(8px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
            },
            animation: {
                'tk-fade-in': 'tk-fade-in 0.35s ease-out both',
                'tk-fade-in-up': 'tk-fade-in-up 0.45s cubic-bezier(0.22, 1, 0.36, 1) both',
                'tk-fade-in-down': 'tk-fade-in-down 0.4s ease-out both',
                'tk-scale-in': 'tk-scale-in 0.35s ease-out both',
                'tk-slide-in-right': 'tk-slide-in-right 0.4s ease-out both',
            },
        },
    },

    plugins: [forms],
};
