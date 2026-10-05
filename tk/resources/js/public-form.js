document.querySelectorAll('[data-multi-select]').forEach((root) => {
    const max = parseInt(root.dataset.max || '0', 10);
    const inputs = () => [...root.querySelectorAll('input[type=checkbox]')];

    root.addEventListener('change', (event) => {
        if (!(event.target instanceof HTMLInputElement) || event.target.type !== 'checkbox') {
            return;
        }
        const checked = inputs().filter((i) => i.checked);
        if (max > 0 && checked.length > max) {
            event.target.checked = false;
            root.dispatchEvent(
                new CustomEvent('multi-limit', { detail: { max }, bubbles: true }),
            );
        }
    });
});

document.addEventListener('multi-limit', (event) => {
    const max = event.detail?.max;
    const msg = document.querySelector('[data-multi-limit-msg]');
    if (msg && max) {
        msg.textContent = `You can select at most ${max} options.`;
        msg.hidden = false;
    }
});
