/**
 * Admin Statistics: draws every [data-stats-chart] with Chart.js and every [data-stats-map] with Leaflet.
 *
 * Chart configs come from App\Statistics\Chart. Colors are role names (series-1, ord-3, other...) resolved from CSS
 * variables on .stats-root, so switching between light and dark mode redraws everything with that mode's colors.
 */
(function () {
    'use strict';

    const root = document.querySelector('.stats-root');
    if (!root) {
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const numberFormat = new Intl.NumberFormat(undefined, { maximumFractionDigits: 1 });
    const charts = [];
    const maps = [];

    function token(name) {
        return getComputedStyle(root).getPropertyValue('--' + name).trim();
    }

    function theme() {
        return {
            surface: token('stats-surface'),
            text: token('stats-text'),
            muted: token('stats-muted'),
            grid: token('stats-grid'),
            axis: token('stats-axis'),
            color: (role) => token(role === 'other' ? 'series-other' : role) || token('series-1'),
        };
    }

    function format(value, unit) {
        return value === null || value === undefined ? '–' : numberFormat.format(value) + (unit || '');
    }

    /* ------------------------------------------------------------------------------------------------------------
     * Charts
     * ---------------------------------------------------------------------------------------------------------- */

    function sparklineConfig(cfg, t) {
        const d = cfg.datasets[0];
        return {
            type: 'line',
            data: {
                labels: cfg.labels,
                datasets: [{
                    data: d.data,
                    borderColor: t.color(d.color),
                    backgroundColor: t.color(d.color),
                    borderWidth: 2,
                    pointRadius: (ctx) => (ctx.dataIndex === d.data.length - 1 ? 3 : 0),
                    pointBorderWidth: 0,
                    tension: 0,
                    fill: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                events: [],
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: {
                    x: { display: false },
                    y: { display: false, beginAtZero: false, suggestedMax: cfg.max ?? undefined },
                },
            },
        };
    }

    function chartConfig(cfg, t) {
        if (cfg.type === 'sparkline') {
            return sparklineConfig(cfg, t);
        }

        const isLine = cfg.type === 'line';
        const valueAxis = cfg.horizontal ? 'x' : 'y';
        const categoryAxis = cfg.horizontal ? 'y' : 'x';
        const last = cfg.datasets.length - 1;
        const singlePoint = cfg.labels.length === 1;

        const datasets = cfg.datasets.map((d, i) => {
            const color = t.color(d.color);
            if (isLine) {
                return {
                    label: d.label,
                    data: d.data,
                    borderColor: color,
                    backgroundColor: color,
                    borderWidth: 2,
                    borderJoinStyle: 'round',
                    borderCapStyle: 'round',
                    pointRadius: singlePoint ? 5 : 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: color,
                    pointBorderColor: t.surface,
                    pointBorderWidth: 2,
                    tension: 0,
                    spanGaps: true,
                };
            }

            // stacked: a 2px surface-colored gap between segments, and only the outermost segment gets rounded
            const gap = cfg.horizontal ? { top: 0, right: 2, bottom: 0, left: 0 } : { top: 2, right: 0, bottom: 0, left: 0 };
            return {
                label: d.label,
                data: d.data,
                links: d.links || null,
                backgroundColor: color,
                hoverBackgroundColor: color,
                borderColor: t.surface,
                borderWidth: cfg.stacked ? gap : 0,
                borderRadius: cfg.stacked && i !== last ? 0 : 4,
                borderSkipped: 'start',
                maxBarThickness: 24,
                categoryPercentage: 0.8,
                barPercentage: 0.9,
            };
        });

        const showLegend = cfg.datasets.length > 1;

        return {
            type: isLine ? 'line' : 'bar',
            data: { labels: cfg.labels, datasets: datasets },
            options: {
                indexAxis: cfg.horizontal ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,
                animation: reducedMotion ? false : { duration: 300 },
                // hovering anywhere in a bar's row/column (or at a date on a line) shows its tooltip
                interaction: { mode: 'index', intersect: false, axis: categoryAxis },
                plugins: {
                    legend: {
                        display: showLegend,
                        position: 'top',
                        align: 'start',
                        labels: {
                            color: t.text,
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            boxWidth: 12,
                            boxHeight: 12,
                            padding: 12,
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' ' + ctx.dataset.label + ': ' + format(ctx.parsed[valueAxis], cfg.unit),
                        },
                    },
                },
                scales: {
                    [valueAxis]: {
                        beginAtZero: true,
                        stacked: cfg.stacked,
                        max: cfg.max ?? undefined,
                        // headroom, so a line at its highest value doesn't sit on the chart's top edge
                        grace: cfg.max ? 0 : '5%',
                        grid: { color: t.grid, drawTicks: false },
                        border: { display: false },
                        ticks: {
                            color: t.muted,
                            padding: 6,
                            precision: cfg.unit ? undefined : 0,
                            callback: (value) => format(value, cfg.unit),
                        },
                    },
                    [categoryAxis]: {
                        stacked: cfg.stacked,
                        grid: { display: false },
                        border: { color: t.axis },
                        ticks: {
                            color: t.text,
                            autoSkip: true,
                            maxRotation: 0,
                            autoSkipPadding: 12,
                        },
                    },
                },
                onClick: (event, elements, chart) => {
                    const hit = chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, false)[0];
                    const links = hit && chart.data.datasets[hit.datasetIndex].links;
                    if (links && links[hit.index]) {
                        window.location.href = links[hit.index];
                    }
                },
                onHover: (event, elements, chart) => {
                    const hit = chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, false)[0];
                    const links = hit && chart.data.datasets[hit.datasetIndex].links;
                    event.native.target.style.cursor = links && links[hit.index] ? 'pointer' : 'default';
                },
            },
        };
    }

    function drawCharts() {
        if (typeof Chart === 'undefined') {
            return;
        }

        charts.splice(0).forEach((chart) => chart.destroy());

        const t = theme();
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.font.size = 12;
        Chart.defaults.color = t.muted;

        document.querySelectorAll('[data-stats-chart]').forEach((el) => {
            const canvas = el.querySelector('canvas');
            if (!canvas) {
                return;
            }
            try {
                const cfg = JSON.parse(el.dataset.statsChart);
                charts.push(new Chart(canvas, chartConfig(cfg, t)));
            } catch (error) {
                console.error('Statistics chart failed to draw', el, error);
            }
        });
    }

    /* ------------------------------------------------------------------------------------------------------------
     * County map
     * ---------------------------------------------------------------------------------------------------------- */

    let topology = null;

    function binIndex(mills, bins) {
        if (!mills) {
            return -1;
        }
        return bins.findIndex(([low, high]) => mills >= low && mills <= high);
    }

    function countyStyle(cfg, t, feature) {
        const county = cfg.counties[feature.id];
        const bin = binIndex(county ? county.mills : 0, cfg.bins);
        return {
            color: t.surface,
            weight: 1,
            fillColor: bin < 0 ? token('map-zero') : token('ord-' + (bin + 2)),
            fillOpacity: 1,
        };
    }

    async function drawMap(el) {
        if (typeof L === 'undefined' || typeof topojson === 'undefined') {
            el.textContent = 'The map couldn’t load. The numbers are in the table below.';
            return;
        }

        const cfg = JSON.parse(el.dataset.statsMap);
        try {
            topology = topology || (await fetch(cfg.topoUrl).then((response) => response.json()));
        } catch (error) {
            el.textContent = 'County boundaries couldn’t load. The numbers are in the table below.';
            return;
        }

        const features = topojson.feature(topology, topology.objects.counties).features
            .filter((feature) => String(feature.id).startsWith(cfg.stateFips));
        if (!features.length) {
            el.textContent = 'No county boundaries were found for this state.';
            return;
        }

        const t = theme();
        const map = L.map(el, { zoomSnap: 0.25, scrollWheelZoom: false, attributionControl: false });
        const counties = L.geoJSON({ type: 'FeatureCollection', features: features }, {
            style: (feature) => countyStyle(cfg, t, feature),
            onEachFeature: (feature, layer) => {
                const county = cfg.counties[feature.id];
                const name = county ? county.name : (feature.properties && feature.properties.name) || 'Unknown county';
                const mills = county ? county.mills : 0;
                layer.bindTooltip(name + ': ' + format(mills) + (mills === 1 ? ' mill' : ' mills'), { sticky: true });
            },
        }).addTo(map);
        map.fitBounds(counties.getBounds(), { padding: [8, 8] });

        const dots = L.layerGroup(cfg.points.map(([lat, lng, name]) => L.circleMarker([lat, lng], {
            radius: 4,
            weight: 2,
            color: t.surface,
            fillColor: t.color('series-2'),
            fillOpacity: 1,
        }).bindTooltip(name))).addTo(map);

        const toggle = el.closest('.card-body').querySelector('[data-stats-map-dots]');
        if (toggle) {
            toggle.addEventListener('change', () => (toggle.checked ? dots.addTo(map) : dots.remove()));
        }

        maps.push({ cfg: cfg, counties: counties, dots: dots });
    }

    function restyleMaps() {
        const t = theme();
        maps.forEach(({ cfg, counties, dots }) => {
            counties.setStyle((feature) => countyStyle(cfg, t, feature));
            dots.eachLayer((dot) => dot.setStyle({ color: t.surface, fillColor: t.color('series-2') }));
        });
    }

    /* ------------------------------------------------------------------------------------------------------------
     * Wiring
     * ---------------------------------------------------------------------------------------------------------- */

    drawCharts();
    document.querySelectorAll('[data-stats-map]').forEach(drawMap);

    // the theme toggle flips data-bs-theme on <html>
    new MutationObserver(() => {
        drawCharts();
        restyleMaps();
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

    // charts inside a closed <details> or a resized card need a nudge to measure themselves
    window.addEventListener('beforeprint', () => charts.forEach((chart) => chart.resize()));
})();
