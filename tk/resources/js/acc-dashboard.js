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

export function initDatasetAccCharts({ dimensionA, dimensionB, baseUrl, query, dimensionAKey, dimensionBKey }) {
    bindDatasetFilterChart('accDatasetChartA', dimensionA, dimensionAKey, baseUrl, query);
    bindDatasetFilterChart('accDatasetChartB', dimensionB, dimensionBKey, baseUrl, query);
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

function bindFilterChart(elementId, dataObj, filterKey, baseUrl, query, onSegmentClick = null) {
    const el = document.getElementById(elementId);
    if (!el || !dataObj) {
        return;
    }

    const labels = Object.keys(dataObj);
    const values = Object.values(dataObj);

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
