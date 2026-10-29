export function bootTilkitSiteSeasonSelects(root = document) {
    root.querySelectorAll('[data-tk-site-season-form]').forEach((form) => {
        const siteSelect = form.querySelector('[data-tk-site-select]');
        const seasonSelect = form.querySelector('[data-tk-season-select]');
        if (!siteSelect || !seasonSelect) {
            return;
        }

        const seasonOptions = [...seasonSelect.querySelectorAll('option[data-site-id]')];

        const sync = () => {
            const siteId = String(siteSelect.value || '');
            seasonOptions.forEach((option) => {
                const optionSite = option.dataset.siteId ?? '';
                const show = !siteId || !optionSite || optionSite === siteId;
                option.hidden = !show;
                option.disabled = !show;
            });
            const selected = seasonSelect.selectedOptions[0];
            if (selected && selected.disabled) {
                seasonSelect.value = '';
            }
        };

        siteSelect.addEventListener('change', sync);
        sync();
    });
}
