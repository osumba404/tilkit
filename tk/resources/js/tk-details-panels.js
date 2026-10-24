/**
 * Smooth height animation for <details class="tk-details-panel">.
 * Respects prefers-reduced-motion (native toggle only).
 */
export function initTkDetailsPanels(root = document) {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    root.querySelectorAll('details.tk-details-panel').forEach((details) => {
        if (details.dataset.tkDetailsReady === '1') {
            return;
        }
        details.dataset.tkDetailsReady = '1';

        const summary = details.querySelector(':scope > summary');
        if (! summary) {
            return;
        }

        ensureBodyWrapper(details, summary);

        const body = details.querySelector(':scope > .tk-details-panel-body');
        if (! body || prefersReduced) {
            return;
        }

        if (details.open) {
            body.style.height = 'auto';
        }

        summary.addEventListener('click', (event) => {
            if (! details.open) {
                return;
            }

            event.preventDefault();
            animateClose(details, body);
        });

        details.addEventListener('toggle', () => {
            if (! details.open) {
                return;
            }

            animateOpen(body);
        });
    });
}

function ensureBodyWrapper(details, summary) {
    if (details.querySelector(':scope > .tk-details-panel-body')) {
        return;
    }

    const body = document.createElement('div');
    body.className = 'tk-details-panel-body';
    const inner = document.createElement('div');
    inner.className = 'tk-details-panel-inner';

    let sibling = summary.nextElementSibling;
    while (sibling) {
        const next = sibling.nextElementSibling;
        inner.appendChild(sibling);
        sibling = next;
    }

    body.appendChild(inner);
    details.appendChild(body);
}

function animateOpen(body) {
    const inner = body.querySelector('.tk-details-panel-inner') || body;
    body.style.overflow = 'hidden';
    body.style.height = '0px';

    requestAnimationFrame(() => {
        body.style.transition = 'height 0.32s cubic-bezier(0.4, 0, 0.2, 1)';
        body.style.height = `${inner.scrollHeight}px`;
    });

    const finish = (event) => {
        if (event.propertyName !== 'height') {
            return;
        }
        body.style.height = 'auto';
        body.style.overflow = '';
        body.style.transition = '';
        body.removeEventListener('transitionend', finish);
    };

    body.addEventListener('transitionend', finish);
}

function animateClose(details, body) {
    const inner = body.querySelector('.tk-details-panel-inner') || body;
    body.style.overflow = 'hidden';
    body.style.height = `${inner.scrollHeight}px`;

    requestAnimationFrame(() => {
        body.style.transition = 'height 0.28s cubic-bezier(0.4, 0, 0.2, 1)';
        body.style.height = '0px';
    });

    const finish = (event) => {
        if (event.propertyName !== 'height') {
            return;
        }
        details.open = false;
        body.style.height = '';
        body.style.overflow = '';
        body.style.transition = '';
        body.removeEventListener('transitionend', finish);
    };

    body.addEventListener('transitionend', finish);
}

document.addEventListener('DOMContentLoaded', () => {
    initTkDetailsPanels();
});
