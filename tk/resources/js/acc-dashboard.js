import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    BarController,
    BarElement,
    ArcElement,
    DoughnutController,
    ScatterController,
    Legend,
    Tooltip,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    BarController,
    BarElement,
    ArcElement,
    DoughnutController,
    ScatterController,
    Legend,
    Tooltip,
);

const palette = ['#0891b2', '#64748b', '#94a3b8', '#334155', '#0e7490', '#475569'];

export function initAccCharts({ trend, channels, statuses, baseUrl, query }) {
    const trendEl = document.getElementById('accTrendChart');
    if (trendEl && trend) {
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: trend.map((r) => r.date),
                datasets: [{
                    label: 'Submissions',
                    data: trend.map((r) => r.count),
                    borderColor: '#0891b2',
                    tension: 0.25,
                    fill: false,
                }],
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    bindFilterChart('accChannelChart', channels, 'channel', baseUrl, query);
    bindFilterChart('accStatusChart', statuses, 'status', baseUrl, query);
}

export function initDatasetAccCharts({ dimensionA, dimensionB, lineSeries, baseUrl, query, dimensionAKey, dimensionBKey }) {
    bindDatasetFilterChart('accDatasetChartA', dimensionA, dimensionAKey, baseUrl, query);
    bindDatasetFilterChart('accDatasetChartB', dimensionB, dimensionBKey, baseUrl, query);
    if (lineSeries?.labels?.length) {
        renderLineChart('accDatasetLineChart', lineSeries);
    }
}

function renderLineChart(elementId, spec) {
    const el = document.getElementById(elementId);
    if (!el || !spec?.labels?.length) {
        return;
    }

    new Chart(el, {
        type: 'line',
        data: {
            labels: spec.labels,
            datasets: [{
                label: spec.title || spec.y_column || 'Series',
                data: spec.values,
                borderColor: '#0891b2',
                backgroundColor: 'rgba(8, 145, 178, 0.15)',
                tension: 0.25,
                fill: true,
                pointRadius: 2,
            }],
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: false } },
            plugins: { legend: { display: true } },
        },
    });
}

function bindDatasetFilterChart(elementId, dataObj, columnKey, baseUrl, query) {
    if (!columnKey) {
        return;
    }
    bindFilterChart(elementId, dataObj, null, baseUrl, query, (label) => {
        const params = new URLSearchParams({
            ...query,
            filter_column: columnKey,
            filter_value: label,
        });
        window.location.href = `${baseUrl}?${params.toString()}`;
    });
}

export function initAnalysisRunCharts(root = document) {
    root.querySelectorAll('[data-chart-spec]').forEach((canvas) => {
        let spec;
        try {
            spec = JSON.parse(canvas.dataset.chartSpec || 'null');
        } catch {
            return;
        }
        if (spec.type === 'scatter' && spec.points?.length) {
            new Chart(canvas, {
                type: 'scatter',
                data: {
                    datasets: [{
                        label: spec.title || 'Scatter',
                        data: spec.points,
                        backgroundColor: '#0891b2',
                        borderColor: '#0e7490',
                    }],
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { title: { display: true, text: 'X' } },
                        y: { title: { display: true, text: 'Y' } },
                    },
                },
            });

            return;
        }

        if (!spec?.labels?.length || !spec?.values?.length) {
            return;
        }

        const isLine = spec.type === 'line' || spec.type === 'graph' || spec.type === 'area';
        const isPie = spec.type === 'pie' || spec.type === 'doughnut';
        const type = isLine ? 'line' : (isPie ? 'doughnut' : 'bar');

        const dataset = {
            data: spec.values,
            backgroundColor: isLine ? 'rgba(8, 145, 178, 0.15)' : palette,
            label: spec.title || 'Values',
        };
        if (type === 'bar') {
            dataset.borderColor = '#0891b2';
            dataset.borderWidth = 1;
        }
        if (type === 'line') {
            dataset.borderColor = '#0891b2';
            dataset.tension = 0.25;
            dataset.fill = true;
            dataset.pointRadius = 2;
        }

        new Chart(canvas, {
            type,
            data: {
                labels: spec.labels,
                datasets: [dataset],
            },
            options: {
                responsive: true,
                plugins: { legend: { position: isPie ? 'bottom' : 'top' } },
                scales: (type === 'bar' || type === 'line') ? { y: { beginAtZero: type === 'bar' } } : {},
            },
        });
    });
}

function normalizeChartData(dataObj) {
    if (!dataObj || typeof dataObj !== 'object') {
        return null;
    }
    if (Array.isArray(dataObj)) {
        return null;
    }

    return dataObj;
}

function bindFilterChart(elementId, dataObj, filterKey, baseUrl, query, onSegmentClick = null) {
    const el = document.getElementById(elementId);
    const normalized = normalizeChartData(dataObj);
    if (!el || !normalized) {
        return;
    }

    const labels = Object.keys(normalized);
    const values = Object.values(normalized);
    if (!labels.length) {
        return;
    }

    new Chart(el, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: palette }],
        },
        options: {
            responsive: true,
            onClick(_evt, elements) {
                if (!elements.length) {
                    return;
                }
                const label = labels[elements[0].index];
                if (onSegmentClick) {
                    onSegmentClick(label);
                    return;
                }
                if (!filterKey) {
                    return;
                }
                const params = new URLSearchParams({ ...query, [filterKey]: label });
                window.location.href = `${baseUrl}?${params.toString()}`;
            },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        afterLabel: () => 'Click to filter',
                    },
                },
            },
        },
    });
}

function bootstrapAccFromWindow() {
    const config = window.__tilkitAcc;
    if (!config?.type) {
        return;
    }
    if (config.type === 'dataset') {
        initDatasetAccCharts(config);
    } else if (config.type === 'platform' || config.type === 'tool') {
        initAccCharts(config);
    } else if (config.type === 'analysis-runs') {
        initAnalysisRunCharts();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapAccFromWindow);
} else {
    bootstrapAccFromWindow();
}
