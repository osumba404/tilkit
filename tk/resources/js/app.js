import './bootstrap';
import './tk-details-panels';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

async function bootApp() {
    if (document.querySelector('[data-tk-form-builder]')) {
        await import('./form-builder');
    }
    if (document.querySelector('[data-tilkit-program-wizard]')) {
        const wizard = await import('./tilkit-program-wizard');
        wizard.registerTilkitProgramWizard();
    }
    if (document.querySelector('[data-tk-gps-map], [data-tk-gps-view]')) {
        const gps = await import('./tilkit-gps');
        gps.bootTilkitGps();
    }
    if (document.querySelector('[data-tk-site-season-form]')) {
        const siteSeasons = await import('./tilkit-site-seasons');
        siteSeasons.bootTilkitSiteSeasonSelects();
    }

    Alpine.start();
}

bootApp();
