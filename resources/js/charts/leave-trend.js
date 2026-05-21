import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

function chartPalette() {
    const dark = document.documentElement.dataset.theme === 'dark';

    return {
        legend: dark ? '#94a3b8' : '#64748b',
        grid: dark ? 'rgba(30, 41, 59, 0.8)' : 'rgba(226, 232, 240, 0.9)',
        ticks: dark ? '#64748b' : '#94a3b8',
    };
}

function renderLeaveTrendChart(root) {
    const canvas = root.querySelector('canvas');

    if (! canvas) {
        return;
    }

    const labels = JSON.parse(root.dataset.labels ?? '[]');
    const approved = JSON.parse(root.dataset.approved ?? '[]');
    const pending = JSON.parse(root.dataset.pending ?? '[]');
    const colors = chartPalette();

    root._leaveTrendChart?.destroy();

    root._leaveTrendChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: root.dataset.labelApproved ?? 'Approved days',
                    data: approved,
                    backgroundColor: 'rgba(20, 184, 166, 0.75)',
                    borderRadius: 6,
                    yAxisID: 'y',
                },
                {
                    label: root.dataset.labelPending ?? 'Pending requests',
                    data: pending,
                    type: 'line',
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.15)',
                    tension: 0.35,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        usePointStyle: true,
                        color: colors.legend,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: false },
                    grid: { color: colors.grid },
                    ticks: { color: colors.ticks },
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { stepSize: 1, color: colors.ticks },
                },
                x: {
                    grid: { display: false },
                    ticks: { color: colors.ticks },
                },
            },
        },
    });
}

document.querySelectorAll('[data-leave-trend-chart]').forEach((root) => {
    renderLeaveTrendChart(root);
});

document.addEventListener('ziifra:themechange', () => {
    document.querySelectorAll('[data-leave-trend-chart]').forEach((root) => {
        renderLeaveTrendChart(root);
    });
});
