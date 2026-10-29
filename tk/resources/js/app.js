import './bootstrap';
import './tk-details-panels';
import { bootFormDraftPersistence } from './tk-form-draft';

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
    bootFormDraftPersistence();

    if (document.querySelector('[data-tk-site-season-form]')) {
        const siteSeasons = await import('./tilkit-site-seasons');
        siteSeasons.bootTilkitSiteSeasonSelects();
    }
    if (document.querySelector('[data-tilkit-site-month-indicators]')) {
        const siteIndicators = await import('./tilkit-site-month-indicators');
        siteIndicators.bootTilkitSiteMonthIndicators();
    }
    if (document.querySelector('[data-tk-record-indicator-cascade]')) {
        const cascade = await import('./tilkit-record-indicator-cascade');
        cascade.bootTilkitRecordIndicatorCascade();
    }

    Alpine.start();
}

bootApp();
