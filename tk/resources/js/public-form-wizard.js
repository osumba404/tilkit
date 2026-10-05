function initFormWizard() {
    const form = document.querySelector('[data-form-wizard]');
    if (!form) {
        return;
    }

    const pages = [...form.querySelectorAll('[data-wizard-page]')];
    const prevBtn = form.querySelector('[data-wizard-prev]');
    const nextBtn = form.querySelector('[data-wizard-next]');
    const submitBtn = form.querySelector('[data-wizard-submit]');
    const progressLabel = form.querySelector('[data-wizard-progress]');
    const progressBar = form.querySelector('[data-wizard-progress-bar]');

    let current = 0;

    const validatePage = (index) => {
        const page = pages[index];
        const fields = page.querySelectorAll('input, select, textarea');
        for (const field of fields) {
            if (field.type === 'hidden' || field.disabled) {
                continue;
            }
            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }
        return true;
    };

    const syncUi = () => {
        pages.forEach((page, idx) => {
            page.hidden = idx !== current;
        });

        if (prevBtn) {
            prevBtn.hidden = current === 0;
        }
        if (nextBtn) {
            nextBtn.hidden = current >= pages.length - 1;
        }
        if (submitBtn) {
            submitBtn.hidden = current < pages.length - 1;
        }

        const total = pages.length;
        const step = current + 1;
        if (progressLabel) {
            progressLabel.textContent = `Section ${step} of ${total}`;
        }
        if (progressBar && total > 0) {
            progressBar.style.width = `${Math.round((step / total) * 100)}%`;
        }
    };

    prevBtn?.addEventListener('click', () => {
        if (current > 0) {
            current -= 1;
            syncUi();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    nextBtn?.addEventListener('click', () => {
        if (!validatePage(current)) {
            return;
        }
        if (current < pages.length - 1) {
            current += 1;
            syncUi();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    form.addEventListener('submit', (event) => {
        if (current < pages.length - 1) {
            event.preventDefault();
            return;
        }
        for (let i = 0; i < pages.length; i += 1) {
            if (!validatePage(i)) {
                event.preventDefault();
                current = i;
                syncUi();
                return;
            }
        }
    });

    syncUi();
}

initFormWizard();
