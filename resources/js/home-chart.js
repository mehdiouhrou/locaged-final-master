import Chart from 'chart.js/auto';


const chartEl = document.getElementById('approvalChart');
const donutEl = document.getElementById('docTypesDonut');

if (chartEl) {
    const weeklyData = JSON.parse(chartEl.dataset.weekly);
    const monthlyData = JSON.parse(chartEl.dataset.monthly);
    const yearlyData = JSON.parse(chartEl.dataset.yearly);

    const ctx = chartEl.getContext('2d');
    const approvedLabel = chartEl.dataset.approvedLabel || 'Approved';
    const pendingLabel = chartEl.dataset.pendingLabel || 'Pending';
    const expiredLabel = chartEl.dataset.expiredLabel || 'Expired';
    const chartLocale = chartEl.dataset.locale || document.documentElement.lang || 'en';

    const dayTranslations = chartEl.dataset.days ? JSON.parse(chartEl.dataset.days) : null;

    const dayKeyByEnglish = {
        Monday: 'mon',
        Tuesday: 'tue',
        Wednesday: 'wed',
        Thursday: 'thu',
        Friday: 'fri',
        Saturday: 'sat',
        Sunday: 'sun',
    };

    /** Évite libellés dupliqués type "JANVIERJANVIER1" (Intl / locales). */
    function normalizeAxisLabel(s) {
        let t = String(s ?? '').trim();
        t = t.replace(/\d+/g, '').replace(/\s+/g, ' ').trim();
        if (t.length >= 6) {
            const mid = Math.floor(t.length / 2);
            if (mid >= 3) {
                const a = t.slice(0, mid).toLowerCase();
                const b = t.slice(mid).toLowerCase();
                if (a === b) {
                    return t.slice(0, mid);
                }
            }
        }
        return t;
    }

    /** Libellé jour : une seule chaîne, sans duplication (traductions DB / JSON). */
    function labelForWeeklyItem(item) {
        if (!item || !item.day) return '';
        const key = dayKeyByEnglish[item.day];
        let raw;
        if (key && dayTranslations && typeof dayTranslations[key] === 'string') {
            raw = dayTranslations[key].trim();
        } else {
            raw = String(item.day).slice(0, 3);
        }
        return normalizeAxisLabel(raw);
    }

    /** Mois : nom uniquement, sans numéro — calculé une fois depuis YYYY-MM. */
    function labelForMonthlyItem(item) {
        if (!item || !item.month) return '';
        const parts = String(item.month).split('-');
        if (parts.length < 2) return '';
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        if (Number.isNaN(y) || Number.isNaN(m)) return '';
        const d = new Date(y, m, 1);
        try {
            let s = new Intl.DateTimeFormat(chartLocale, { month: 'long' }).format(d);
            return normalizeAxisLabel(s);
        } catch (e) {
            return normalizeAxisLabel(d.toLocaleDateString('en', { month: 'long' }));
        }
    }

    function labelForYearlyItem(item) {
        if (!item || item.year == null) return '';
        return String(item.year);
    }

    const labelsWeekly = Array.isArray(weeklyData) ? weeklyData.map(labelForWeeklyItem) : [];
    const labelsMonthly = Array.isArray(monthlyData) ? monthlyData.map(labelForMonthlyItem) : [];
    const labelsYearly = Array.isArray(yearlyData) ? yearlyData.map(labelForYearlyItem) : [];

    function getData(dataArray, statusKey, length) {
        if (!Array.isArray(dataArray)) return Array(length).fill(0);
        return dataArray.map(item => item[statusKey] ?? 0);
    }

    const monthlyLen = Array.isArray(monthlyData) ? monthlyData.length : 0;

    function applyXAxisTicksForPeriod(chart, period) {
        const ticks = chart.options.scales.x.ticks;
        ticks.autoSkip = true;
        ticks.maxRotation = 45;
        ticks.minRotation = 0;
        if (period === 'weekly') {
            ticks.maxTicksLimit = 7;
        } else if (period === 'monthly') {
            ticks.maxTicksLimit = 12;
        } else if (period === 'yearly') {
            ticks.maxTicksLimit = 12;
        }
    }

    // Initial chart config - will update data dynamically
    const config = {
        type: 'bar',
        data: {
            labels: labelsMonthly,
            datasets: [
                { label: approvedLabel, data: getData(monthlyData, 'approved', monthlyLen), backgroundColor: '#cc2929', borderRadius: 6, barThickness: 12 },
                { label: pendingLabel, data: getData(monthlyData, 'pending', monthlyLen), backgroundColor: '#f59e0b', borderRadius: 6, barThickness: 12 },
                { label: expiredLabel, data: getData(monthlyData, 'expired', monthlyLen), backgroundColor: '#64748b', borderRadius: 6, barThickness: 12 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 1,
                    right: 10,
                    top: 10,
                    bottom: 10
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'center',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        font: {
                            size: 11
                        },
                        usePointStyle: true
                    }
                }
            },
            scales: {
                x: {
                    offset: true,
                    grid: {
                        display: false,
                        drawBorder: true
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        color: '#6c757d',
                        autoSkip: true,
                        maxRotation: 45,
                        minRotation: 0,
                        maxTicksLimit: 12
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 500000,
                        callback: function (value) {
                            if (value >= 1000000) return (value / 1000000) + 'M';
                            if (value >= 1000) return (value / 1000) + 'k';
                            return value;
                        },
                        font: {
                            size: 12
                        },
                        color: '#6c757d'
                    },
                    grid: {
                        color: '#e9ecef'
                    }
                }
            },
            elements: {
                bar: {
                    borderRadius: 6
                }
            }
        }
    };

    const chart = new Chart(ctx, config);
    applyXAxisTicksForPeriod(chart, 'monthly');

    // Button group handler for switching data/timeframe
    const buttons = document.querySelectorAll('.button-timeframe');
    buttons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and add to clicked
            buttons.forEach(btn => btn.classList.remove('button-active'));
            button.classList.add('button-active');

            const period = button.getAttribute('data-period');
            let labels;
            let dataSource;

            if (period === 'weekly') {
                labels = labelsWeekly;
                dataSource = weeklyData;
            } else if (period === 'monthly') {
                labels = labelsMonthly;
                dataSource = monthlyData;
            } else if (period === 'yearly') {
                labels = labelsYearly;
                dataSource = yearlyData;
            } else {
                return;
            }

            const dataLength = Array.isArray(dataSource) ? dataSource.length : 0;

            applyXAxisTicksForPeriod(chart, period);

            // Update chart data and labels
            chart.data.labels = labels;
            chart.data.datasets[0].data = getData(dataSource, 'approved', dataLength);
            chart.data.datasets[1].data = getData(dataSource, 'pending', dataLength);
            chart.data.datasets[2].data = getData(dataSource, 'expired', dataLength);

            chart.update();
        });
    });
}


if (donutEl) {
    const donutData = JSON.parse(donutEl.dataset.donutData);
    const chartType = donutData.type || donutEl.dataset.chartType;
    const noDataLabel = donutEl.dataset.noDataLabel || 'No Data';
    const ctx2 = donutEl.getContext('2d');

    // Color palette for hierarchical donut segments
    const colorPalette = [
        '#2563eb', '#ef4444', '#22c55e', '#f59e0b', '#8b5cf6', '#06b6d4', '#e11d48', '#84cc16', '#0ea5e9',
        '#f97316', '#14b8a6', '#a855f7', '#475569', '#6366f1', '#10b981', '#60a5fa', '#3b82f6', '#0ea5a5'
    ];

    window.currentDonutChart = null;

    // Navigation state for multi-level drilldown
    let donutHistoryStack = [];
    let currentDonutState = {
        type: chartType,
        data: donutData.data || [],
        title: donutData.title,
        subtitle: donutData.subtitle,
    };

    function updateDonutHeader(state) {
        if (!state) return;
        const titleEl = document.getElementById('donutTitle');
        const subtitleEl = document.getElementById('donutSubtitle');
        if (titleEl) titleEl.textContent = state.title || '';
        if (subtitleEl) subtitleEl.textContent = state.subtitle || '';

        const backBtnWrapper = document.getElementById('donutBackButton');
        if (backBtnWrapper) {
            backBtnWrapper.style.display = donutHistoryStack.length > 0 ? 'block' : 'none';
        }
    }

    function createDonutChartFromState(state) {
        const data = state.data || [];
        const type = state.type;

        if (window.currentDonutChart) {
            window.currentDonutChart.destroy();
        }

        // Handle empty data
        if (!data || data.length === 0) {
            window.currentDonutChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: [noDataLabel],
                    datasets: [{
                        data: [1],
                        backgroundColor: ['#e9ecef'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '66%',
                    layout: { padding: 0 },
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
            return;
        }

        const labels = data.map(item => item.name);
        const values = data.map(item => item.count);
        const colors = data.map((_, index) => colorPalette[index % colorPalette.length]);

        window.currentDonutChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                layout: { padding: 0 },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const val = ctx.raw;
                                const total = values.reduce((a, b) => a + b, 0);
                                const pct = total ? (val / total * 100).toFixed(1) : 0;
                                return `${ctx.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                },
                onClick: function (event, elements) {
                    if (!elements.length) return;
                    const clickedIndex = elements[0].index;
                    const clickedItem = data[clickedIndex];
                    if (!clickedItem || !clickedItem.id) return;
                    handleDonutDrilldown(type, clickedItem.id);
                }
            }
        });
    }

    function applyDonutState(nextState) {
        currentDonutState = nextState;
        updateDonutHeader(nextState);
        createDonutChartFromState(nextState);
    }

    function pushCurrentStateToHistory() {
        if (currentDonutState) {
            donutHistoryStack.push(currentDonutState);
        }
    }

    function handleDonutDrilldown(type, id) {
        if (type === 'departments') {
            pushCurrentStateToHistory();
            loadSubDepartmentsForDepartment(id);
        } else if (type === 'sub_departments') {
            pushCurrentStateToHistory();
            loadServicesForSubDepartment(id);
        } else if (type === 'services') {
            pushCurrentStateToHistory();
            loadCategoriesForService(id);
        } else if (type === 'categories') {
            // Final level: redirect to Documents by-category page so that
            // users land on the normal documents view instead of File Audit.
            window.location.href = `/documents/by-category/${id}?show_expired=1`;
        } else {
            // subcategories or any other terminal level – no further drilldown
        }
    }

    function fetchAndApplyDonut(url) {
        fetch(url)
            .then(response => response.json())
            .then(result => {
                if (result.error) {
                    console.error('Error loading donut data:', result.error);
                    // Roll back history step on error
                    if (donutHistoryStack.length > 0) {
                        donutHistoryStack.pop();
                        updateDonutHeader(currentDonutState);
                        createDonutChartFromState(currentDonutState);
                    }
                    return;
                }

                const nextState = {
                    type: result.type,
                    data: result.data || [],
                    title: result.title,
                    subtitle: result.subtitle,
                };

                applyDonutState(nextState);
            })
            .catch(error => {
                console.error('Error loading donut data:', error);
                if (donutHistoryStack.length > 0) {
                    donutHistoryStack.pop();
                    updateDonutHeader(currentDonutState);
                    createDonutChartFromState(currentDonutState);
                }
            });
    }

    function loadSubDepartmentsForDepartment(departmentId) {
        fetchAndApplyDonut(`/departments/${departmentId}/sub-departments`);
    }

    function loadServicesForSubDepartment(subDepartmentId) {
        fetchAndApplyDonut(`/sub-departments/${subDepartmentId}/services`);
    }

    function loadCategoriesForService(serviceId) {
        fetchAndApplyDonut(`/services/${serviceId}/categories`);
    }

    function goBackToDepartments() {
        if (!donutHistoryStack.length) {
            return;
        }
        const previousState = donutHistoryStack.pop();
        applyDonutState(previousState);
    }

    // Expose back handler globally (name kept for Blade compatibility)
    window.goBackToDepartments = goBackToDepartments;

    // Initial render
    applyDonutState(currentDonutState);
}

