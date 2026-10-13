import html2canvas from 'html2canvas';
import { jsPDF } from 'jspdf';
import { destroyDatasetCharts, initDatasetAccCharts } from './acc-dashboard.js';

function jsonHeaders() {
    return {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
}

async function submitPanelForm(form, methodOverride = null) {
    const action = form.getAttribute('action');
    if (!action) {
        return;
    }

    const formData = new FormData(form);
    let method = (form.getAttribute('method') || 'POST').toUpperCase();
    if (methodOverride) {
        method = methodOverride;
    }
    if (method === 'PATCH' || method === 'DELETE') {
        formData.append('_method', method);
    }

    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
    }

    try {
        const response = await fetch(action, {
            method: 'POST',
            body: formData,
            headers: {
                ...jsonHeaders(),
                'X-CSRF-TOKEN': window.__tilkitAcc?.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            showFormError(data.errors?.panel || data.message || 'Could not update panel.');
            return;
        }

        hideFormError();
        applyDashboardPayload(data);
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }
}

function showFormError(message) {
    const el = document.querySelector('.dataset-panel-form-error');
    if (!el) {
        return;
    }
    el.textContent = message;
    el.classList.remove('hidden');
}

function hideFormError() {
    const el = document.querySelector('.dataset-panel-form-error');
    if (!el) {
        return;
    }
    el.textContent = '';
    el.classList.add('hidden');
}

function applyDashboardPayload(data) {
    const panelsRoot = document.getElementById('dataset-panels-root');
    const addRoot = document.getElementById('dataset-add-panel-root');
    if (!panelsRoot || !addRoot || !window.__tilkitAcc) {
        return;
    }

    destroyDatasetCharts(panelsRoot);
    panelsRoot.innerHTML = data.panels_html ?? '';
    addRoot.innerHTML = data.add_panel_html ?? '';
    window.__tilkitAcc.charts = data.charts ?? [];

    initDatasetAccCharts(window.__tilkitAcc);

    if (window.Alpine) {
        window.Alpine.initTree(panelsRoot);
        window.Alpine.initTree(addRoot);
    }

    const countEl = document.getElementById('dataset-kpi-panel-count');
    if (countEl && typeof data.panel_count === 'number') {
        countEl.textContent = new Intl.NumberFormat().format(data.panel_count);
    }

    bindDatasetDashboardInteractions();

    const focusId = data.focus_panel_id;
    if (focusId) {
        const target = document.getElementById(`panel-${focusId}`) || document.getElementById('dataset-add-panel');
        target?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        document.getElementById('dataset-add-panel')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

async function exportPanelPng(panelId) {
    const section = document.getElementById(`panel-${panelId}`);
    if (!section) {
        return;
    }

    const canvas = section.querySelector('canvas');
    if (canvas) {
        const link = document.createElement('a');
        link.download = `panel-${panelId}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        return;
    }

    const body = section.querySelector('[data-panel-body]') || section.querySelector('.dataset-panel-body');
    if (!body) {
        return;
    }

    const shot = await html2canvas(body, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
    const link = document.createElement('a');
    link.download = `panel-${panelId}.png`;
    link.href = shot.toDataURL('image/png');
    link.click();
}

async function exportDashboardPdf() {
    const list = document.getElementById('dataset-panels-list');
    if (!list) {
        return;
    }

    const panels = list.querySelectorAll('.dataset-panel-card');
    if (!panels.length) {
        return;
    }

    const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
    const pageWidth = pdf.internal.pageSize.getWidth();
    const margin = 40;
    let y = margin;
    const title = window.__tilkitAcc?.datasetName || 'Dataset dashboard';
    pdf.setFontSize(14);
    pdf.text(title, margin, y);
    y += 24;

    for (let i = 0; i < panels.length; i += 1) {
        const panel = panels[i];
        const shot = await html2canvas(panel, { scale: 1.5, useCORS: true, backgroundColor: '#ffffff' });
        const imgData = shot.toDataURL('image/png');
        const imgWidth = pageWidth - margin * 2;
        const imgHeight = (shot.height * imgWidth) / shot.width;

        if (y + imgHeight > pdf.internal.pageSize.getHeight() - margin) {
            pdf.addPage();
            y = margin;
        }

        pdf.addImage(imgData, 'PNG', margin, y, imgWidth, imgHeight);
        y += imgHeight + 16;
    }

    pdf.save(`${title.replace(/[^\w\-]+/g, '-').slice(0, 80) || 'dashboard'}.pdf`);
}

function bindDatasetDashboardInteractions() {
    document.querySelectorAll('.dataset-panel-store-form, .dataset-panel-update-form').forEach((form) => {
        if (form.dataset.ajaxBound === '1') {
            return;
        }
        form.dataset.ajaxBound = '1';
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitPanelForm(form);
        });
    });

    document.querySelectorAll('.dataset-panel-remove-form').forEach((form) => {
        if (form.dataset.ajaxBound === '1') {
            return;
        }
        form.dataset.ajaxBound = '1';
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!window.confirm('Remove this panel?')) {
                return;
            }
            submitPanelForm(form, 'DELETE');
        });
    });

    document.querySelectorAll('.dataset-panels-clear-form').forEach((form) => {
        if (form.dataset.ajaxBound === '1') {
            return;
        }
        form.dataset.ajaxBound = '1';
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!window.confirm('Remove all panels?')) {
                return;
            }
            submitPanelForm(form);
        });
    });

    document.querySelectorAll('.dataset-panel-export-png').forEach((btn) => {
        if (btn.dataset.exportBound === '1') {
            return;
        }
        btn.dataset.exportBound = '1';
        btn.addEventListener('click', () => {
            const panelId = btn.getAttribute('data-panel-id');
            if (panelId) {
                exportPanelPng(panelId);
            }
        });
    });

    const pdfBtn = document.getElementById('dataset-export-pdf');
    if (pdfBtn && pdfBtn.dataset.exportBound !== '1') {
        pdfBtn.dataset.exportBound = '1';
        pdfBtn.addEventListener('click', () => {
            exportDashboardPdf();
        });
    }
}

function bootstrapDatasetDashboard() {
    if (window.__tilkitAcc?.type !== 'dataset') {
        return;
    }
    bindDatasetDashboardInteractions();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapDatasetDashboard);
} else {
    bootstrapDatasetDashboard();
}
