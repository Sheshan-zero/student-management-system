/**
 * Student Management System — Main JavaScript
 * Sidebar toggle, dropdowns, Chart.js helpers
 */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // SIDEBAR TOGGLE (mobile)
    // ============================================================
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Close sidebar on ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });


    // ============================================================
    // ALERT AUTO-DISMISS
    // ============================================================
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });


    // ============================================================
    // DELETE CONFIRMATION
    // ============================================================
    document.querySelectorAll('.delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ============================================================
    // DARK MODE TOGGLE
    // ============================================================
    const darkBtn = document.getElementById('darkModeToggle');
    if (darkBtn) {
        function updateDarkIcon() {
            const icon = darkBtn.querySelector('i');
            if (document.body.classList.contains('dark-mode')) {
                icon.className = 'fas fa-sun';
            } else {
                icon.className = 'fas fa-moon';
            }
        }
        updateDarkIcon();
        darkBtn.addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
            updateDarkIcon();
        });
    }


});

// ============================================================
// CHART.JS HELPERS — consistent styling
// ============================================================
const SMS_CHARTS = {

    // Default color palette for charts
    colors: {
        primary: '#4361ee',
        secondary: '#7209b7',
        success: '#2ec4b6',
        warning: '#ff9f1c',
        danger: '#e63946',
        info: '#4895ef',
        accent: '#4cc9f0'
    },

    // Transparent versions for area fills
    alphaColors: {
        primary: 'rgba(67, 97, 238, 0.12)',
        secondary: 'rgba(114, 9, 183, 0.12)',
        success: 'rgba(46, 196, 182, 0.12)',
        warning: 'rgba(255, 159, 28, 0.12)',
        danger: 'rgba(230, 57, 70, 0.12)',
        info: 'rgba(72, 149, 239, 0.12)'
    },

    /**
     * Create a line/area chart
     * @param {string} canvasId - Canvas element ID
     * @param {object} config   - { labels, datasets: [{ label, data, color }] }
     */
    lineChart: function (canvasId, config) {
        var ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        var datasets = config.datasets.map(function (ds) {
            var c = SMS_CHARTS.colors[ds.color] || ds.color;
            var ac = SMS_CHARTS.alphaColors[ds.color] || 'rgba(67,97,238,0.12)';
            return {
                label: ds.label,
                data: ds.data,
                borderColor: c,
                backgroundColor: ac,
                tension: 0.4,
                fill: true,
                borderWidth: 2.5,
                pointRadius: 3,
                pointBackgroundColor: c
            };
        });

        return new Chart(ctx, {
            type: 'line',
            data: { labels: config.labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: config.datasets.length > 1,
                        position: 'top',
                        labels: { usePointStyle: true, padding: 20, font: { size: 12, family: 'Inter' } }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, family: 'Inter' }, color: '#6c757d' }
                    },
                    y: {
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 11, family: 'Inter' }, color: '#6c757d' },
                        beginAtZero: true
                    }
                }
            }
        });
    },

    /**
     * Create a doughnut chart
     * @param {string} canvasId
     * @param {object} config - { labels, data, colors (array of keys or hex) }
     */
    doughnutChart: function (canvasId, config) {
        var ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        var bgColors = config.colors.map(function (c) {
            return SMS_CHARTS.colors[c] || c;
        });

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: config.labels,
                datasets: [{
                    data: config.data,
                    backgroundColor: bgColors,
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 16, font: { size: 12, family: 'Inter' } }
                    }
                }
            }
        });
    },

    /**
     * Create a bar chart
     * @param {string} canvasId
     * @param {object} config - { labels, datasets: [{ label, data, color }] }
     */
    barChart: function (canvasId, config) {
        var ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        var datasets = config.datasets.map(function (ds) {
            return {
                label: ds.label,
                data: ds.data,
                backgroundColor: SMS_CHARTS.colors[ds.color] || ds.color,
                borderRadius: 6,
                borderSkipped: false,
                barThickness: 32
            };
        });

        return new Chart(ctx, {
            type: 'bar',
            data: { labels: config.labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: config.datasets.length > 1,
                        position: 'top',
                        labels: { usePointStyle: true, padding: 20, font: { size: 12, family: 'Inter' } }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11, family: 'Inter' } } },
                    y: { grid: { color: 'rgba(0,0,0,0.04)' }, beginAtZero: true, ticks: { font: { size: 11, family: 'Inter' } } }
                }
            }
        });
    }
};
