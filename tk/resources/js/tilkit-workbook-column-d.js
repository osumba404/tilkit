import { composeColumnD, fillSelect, parseColumnD } from './tilkit-indicator-picker-utils';

export function bootTilkitWorkbookColumnD(root = document) {
    root.querySelectorAll('[data-tk-workbook-column-d]').forEach((panel) => {
        const hidden = panel.querySelector('[data-tk-wb-d-value]');
        const behSelect = panel.querySelector('[data-tk-wb-d-behaviour]');
        const intensitySelect = panel.querySelector('[data-tk-wb-d-intensity]');
        if (!hidden || !behSelect) {
            return;
        }

        let intensityOptions = [];
        try {
            intensityOptions = JSON.parse(panel.dataset.intensityOptions || '[]');
        } catch {
            intensityOptions = [];
        }

        fillSelect(
            intensitySelect,
            intensityOptions.map((o) => ({
                value: o.value,
                label: o.label || o.value,
            })),
            intensityOptions.length ? 'Choose intensity' : '-',
        );

        const sync = () => {
            hidden.value = composeColumnD(behSelect.value, intensitySelect?.value);
        };

        const parsed = parseColumnD(hidden.value);
        if (parsed.behaviour) {
            behSelect.value = parsed.behaviour;
        }
        if (parsed.intensity && intensitySelect) {
            intensitySelect.value = parsed.intensity;
        }

        behSelect.addEventListener('change', sync);
        intensitySelect?.addEventListener('change', sync);
    });
}
