import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/user-guide.css',
                'resources/js/app.js',
                'resources/js/public-form.js',
                'resources/js/public-form-wizard.js',
                'resources/js/public-geo.js',
                'resources/js/tilkit-gps.js',
                'resources/js/acc-dashboard.js',
                'resources/js/dataset-dashboard.js',
                'resources/js/tilkit-deep-analysis.js',
            ],
            refresh: true,
        }),
    ],
});
