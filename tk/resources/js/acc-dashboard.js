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
    BubbleController,
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
    BubbleController,
    Legend,
    Tooltip,
);

const palette = ['#0891b2', '#64748b', '#94a3b8', '#334155', '#0e7490', '#475569'];

function specPalette(spec) {
    if (Array.isArray(spec?.colors) && spec.colors.length) {
        return spec.colors;
    }

    return palette;
}

function hexToRgba(hex, alpha) {
    const normalized = (hex || '#0891b2').replace('#', '');
    if (normalized.length !== 6) {
        return `rgba(8, 145, 178, ${alpha})`;
    }
    const r = parseInt(normalized.slice(0, 2), 16);
    const g = parseInt(normalized.slice(2, 4), 16);
    const b = parseInt(normalized.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function cycleColors(colors, count) {
    const out = [];
    for (let i = 0; i < count; i += 1) {
        out.push(colors[i % colors.length]);
    }

    return out;
}

export function destroyDatasetCharts(root = document) {
    root.querySelectorAll('canvas').forEach((canvas) => {
        const existing = Chart.getChart(canvas);
        if (existing) {
            existing.destroy();
        }
    });
}

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

export function initDatasetAccCharts({ charts, baseUrl, query }) {
    if (!Array.isArray(charts)) {
        return;
    }
    charts.forEach((spec) => {
        if (spec.empty || (spec.render && spec.render !== 'canvas')) {
            return;
        }
        renderDatasetChart(spec, baseUrl, query);
    });
}

function renderDatasetChart(spec, baseUrl, query) {
    const el = document.getElementById(spec.id);
    if (!el) {
        return;
    }

    const onSegmentClick = spec.filter_column
        ? (label) => {
            const params = new URLSearchParams({
                ...query,
                filter_column: spec.filter_column,
                filter_value: label,
            });
            window.location.href = `${baseUrl}?${params.toString()}`;
        }
        : null;

    const colors = specPalette(spec);
    const primary = colors[0] || '#0891b2';

    if ((spec.type === 'scatter' || spec.type === '3d_bubble') && spec.points?.length) {
        const isBubble = spec.type === '3d_bubble';
        new Chart(el, {
            type: isBubble ? 'bubble' : 'scatter',
            data: {
                datasets: [{
                    label: spec.title || (isBubble ? '3D bubble' : 'Scatter'),
                    data: spec.points,
                    backgroundColor: hexToRgba(primary, 0.55),
                    borderColor: colors[1] || primary,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: spec.axis_labels?.x || 'X' } },
                    y: { title: { display: true, text: spec.axis_labels?.y || 'Y' } },
                },
                plugins: isBubble ? {
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const p = ctx.raw;
                                return `X: ${p.x}, Y: ${p.y} (bubble size ∝ Z)`;
                            },
                        },
                    },
                } : {},
            },
        });

        return;
    }

    if (!spec.labels?.length || !spec.values?.length) {
        return;
    }

    const isLine = spec.type === 'line' || spec.type === 'area';
    const isPie = spec.type === 'pie' || spec.type === 'doughnut';
    const chartType = isLine ? 'line' : (isPie ? (spec.type === 'pie' ? 'pie' : 'doughnut') : 'bar');
    const segmentColors = cycleColors(colors, spec.values.length);

    const dataset = {
        data: spec.values,
        label: spec.title || 'Values',
        backgroundColor: isLine ? hexToRgba(primary, 0.15) : segmentColors,
    };
    if (chartType === 'bar') {
        dataset.borderColor = segmentColors.map((c) => c);
        dataset.borderWidth = 1;
    }
    if (chartType === 'line') {
        dataset.borderColor = primary;
        dataset.tension = 0.25;
        dataset.fill = true;
        dataset.pointRadius = 2;
        dataset.backgroundColor = hexToRgba(primary, 0.15);
    }

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: isPie ? 'bottom' : 'top' },
            tooltip: onSegmentClick ? { callbacks: { afterLabel: () => 'Click to filter' } } : {},
        },
        scales: (chartType === 'bar' || chartType === 'line') ? { y: { beginAtZero: chartType === 'bar' } } : {},
    };

    if (onSegmentClick && chartType === 'doughnut') {
        options.onClick = (_evt, elements) => {
            if (!elements.length) {
                return;
            }
            const label = spec.labels[elements[0].index];
            onSegmentClick(label);
        };
    }

    if (onSegmentClick && chartType === 'bar') {
        options.onClick = (_evt, elements) => {
            if (!elements.length) {
                return;
            }
            const label = spec.labels[elements[0].index];
            onSegmentClick(label);
        };
    }

    new Chart(el, {
        type: chartType,
        data: { labels: spec.labels, datasets: [dataset] },
        options,
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
        if ((spec.type === 'scatter' || spec.type === '3d_bubble') && spec.points?.length) {
            const isBubble = spec.type === '3d_bubble';
            new Chart(canvas, {
                type: isBubble ? 'bubble' : 'scatter',
                data: {
                    datasets: [{
                        label: spec.title || (isBubble ? '3D bubble' : 'Scatter'),
                        data: spec.points,
                        backgroundColor: 'rgba(8, 145, 178, 0.55)',
                        borderColor: '#0e7490',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: spec.axis_labels?.x || 'X',
                            },
                        },
                        y: {
                            title: {
                                display: true,
                                text: spec.axis_labels?.y || 'Y',
                            },
                        },
                    },
                    plugins: {
                        tooltip: isBubble ? {
                            callbacks: {
                                label(ctx) {
                                    const p = ctx.raw;
                                    return `X: ${p.x}, Y: ${p.y}, size ∝ Z`;
                                },
                            },
                        } : {},
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
