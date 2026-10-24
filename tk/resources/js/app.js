import './bootstrap';
import './tk-details-panels';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

async function bootApp() {
    if (document.querySelector('[data-tk-form-builder]')) {
        await import('./form-builder');
    }

    Alpine.start();
}

bootApp();
