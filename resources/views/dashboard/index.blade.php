@extends('dashboard.layouts.main')

@section('head')
<style>
    /* Page-scoped: tighter gutters on phones (layout default is 2rem on every side) */
    @media (max-width: 575.98px) {
        .dashboard-main { padding: 1rem !important; }
        .kpi:not(.kpi-hero) .delta-vs { display: none; }
    }

    .dash { --dash-gap: 0.75rem; font-variant-numeric: tabular-nums; }
    .dash .card { margin-bottom: 0; }
    .dash.is-loading .dash-body { opacity: .55; transition: opacity .15s; }

    .dash-header { display: flex; flex-direction: column; gap: .75rem; padding-bottom: 1rem; margin-bottom: 1rem; border-bottom: 1px solid #e9e4dc; }
    .dash-header h1 { margin: 0; }
    .dash-filter { display: flex; flex-direction: column; gap: .5rem; }
    .dash-filter .form-select, .dash-filter .form-control, .dash-filter .btn { min-height: 44px; }
    .dash-custom { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .dash-custom .form-control { min-width: 0; padding-left: .5rem; padding-right: .5rem; }
    .dash-custom .btn { grid-column: 1 / -1; }
    .dash-range { font-size: .875rem; color: var(--text-muted); margin: 0; }

    .kpi-grid { display: grid; grid-template-columns: 1fr; gap: var(--dash-gap); margin-bottom: 1rem; }
    .kpi { padding: 1rem; display: grid; grid-template-columns: 1fr auto; grid-template-areas: "label value" "delta value"; align-items: center; column-gap: .75rem; }
    .kpi-label { grid-area: label; font-size: .8125rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .02em; }
    .kpi-value { grid-area: value; font-size: 1.125rem; font-weight: 700; color: var(--text-dark); text-align: right; white-space: nowrap; }
    .kpi-delta { grid-area: delta; font-size: .8125rem; margin-top: .125rem; }
    .kpi-sub { font-size: .875rem; }

    .kpi-hero { background: var(--text-dark) !important; color: #fff; display: block; padding: 1.25rem; }
    .kpi-hero .kpi-label { color: rgba(255,255,255,.75); }
    .kpi-hero .kpi-value { color: #fff; font-size: 2rem; text-align: left; margin: .25rem 0; white-space: normal; overflow-wrap: anywhere; }
    .kpi-hero .kpi-value.is-negative { color: #ffb4a8; }
    .kpi-hero .kpi-sub { color: rgba(255,255,255,.8); }
    .kpi-hero .delta-up { color: #a8e6b8; }
    .kpi-hero .delta-down { color: #ffb4a8; }
    .kpi-hero .delta-none { color: rgba(255,255,255,.65); }

    .delta-up { color: #157347; }
    .delta-down { color: #b02a37; }
    .delta-none { color: var(--text-muted); }

    .dash-card { padding: 1rem; margin-bottom: 1rem !important; }
    .dash-card-head { display: flex; justify-content: space-between; align-items: center; gap: .75rem; margin-bottom: .75rem; flex-wrap: wrap; }
    .dash-card-head h2 { font-size: 1rem; font-weight: 700; margin: 0; }
    .dash-card-head small { color: var(--text-muted); }
    .chart-caption { font-size: .8125rem; font-weight: 600; color: var(--text-muted); margin: .5rem 0 0; }
    .empty-state { padding: 2rem 1rem; text-align: center; color: var(--text-muted); font-size: .9375rem; }

    .rank-list { list-style: none; margin: 0; padding: 0; }
    .rank-list li { display: grid; grid-template-columns: 1.75rem 1fr auto; column-gap: .75rem; align-items: start; padding: .75rem 0; border-top: 1px solid #f1ede7; }
    .rank-list li:first-child { border-top: 0; }
    .rank-no { font-weight: 700; color: var(--text-muted); text-align: right; }
    .rank-name { font-weight: 600; color: var(--text-dark); overflow-wrap: anywhere; }
    .rank-meta { font-size: .8125rem; color: var(--text-muted); }
    .rank-value { font-weight: 700; text-align: right; white-space: nowrap; }
    .rank-value small { display: block; font-weight: 400; color: var(--text-muted); }
    .rank-bar { height: 6px; border-radius: 3px; background: #f1ede7; margin-top: .375rem; overflow: hidden; }
    .rank-bar span { display: block; height: 100%; border-radius: 3px; }

    .seg-toggle .btn { min-height: 44px; min-width: 72px; }
    .seg-toggle .btn.active { background: var(--text-dark); border-color: var(--text-dark); color: #fff; }

    /* Tren Penjualan grouping: full width under the title on phones, so four buttons fit at 320px */
    .group-toggle { flex: 1 0 100%; }
    .group-toggle .btn { flex: 1 1 auto; min-width: 0; min-height: 44px; padding-left: .25rem; padding-right: .25rem; }
    .group-toggle .btn.active { background: var(--text-dark); border-color: var(--text-dark); color: #fff; }
    .group-toggle .btn:disabled { cursor: not-allowed; }
    .is-busy .trend-body { opacity: .55; transition: opacity .15s; }

    @media (min-width: 576px) {
        .group-toggle { flex: 0 0 auto; }
        .group-toggle .btn { flex: 0 0 auto; min-width: 72px; padding-left: .75rem; padding-right: .75rem; }
        .dash-header { flex-direction: row; justify-content: space-between; align-items: flex-end; }
        .dash-filter { align-items: flex-end; }
        .dash-filter .form-select { width: 220px; }
        .dash-custom { grid-template-columns: 150px 150px auto; }
        .dash-custom .btn { grid-column: auto; }
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .kpi-hero { grid-column: 1 / -1; }
        .kpi { display: block; }
        .kpi-value { text-align: left; font-size: 1.375rem; margin: .25rem 0; }
        .dash-card { padding: 1.25rem; }
    }

    @media (min-width: 1200px) {
        .kpi-grid { grid-template-columns: repeat(4, 1fr); }
    }

    @media (min-width: 1600px) {
        .kpi-grid { grid-template-columns: 1.5fr repeat(4, 1fr); }
        .kpi-hero { grid-column: auto; }
    }
</style>
@endsection

@section('container')
<div class="dash is-loading" id="dash">
    <header class="dash-header pt-3">
        <div>
            <h1 class="h2 fw-bold">Dashboard</h1>
            <p class="dash-range" id="period-label">Memuat…</p>
        </div>
        <div class="dash-filter">
            <select id="period" class="form-select" aria-label="Periode">
                @foreach ($presets as $key => $label)
                    <option value="{{ $key }}" @if ($key === $defaultPreset) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
            <div id="custom-range" class="dash-custom d-none">
                <input type="date" id="from" class="form-control" aria-label="Dari tanggal">
                <input type="date" id="to" class="form-control" aria-label="Sampai tanggal">
                <button type="button" class="btn btn-primary" id="apply-custom">Terapkan</button>
            </div>
        </div>
    </header>

    <div class="dash-body">
        <!-- KPIs -->
        <section class="kpi-grid" aria-label="Ringkasan">
            <div class="card kpi kpi-hero">
                <div class="kpi-label">Laba Bersih</div>
                <div class="kpi-value" id="kpi-laba_bersih">—</div>
                <div class="kpi-sub" id="kpi-laba-sub">Cuan − Biaya ops</div>
                <div class="kpi-delta" id="delta-laba_bersih"></div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Omzet</div>
                <div class="kpi-value" id="kpi-omzet">—</div>
                <div class="kpi-delta" id="delta-omzet"></div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Cuan <span class="text-lowercase fw-normal" id="kpi-margin"></span></div>
                <div class="kpi-value" id="kpi-cuan">—</div>
                <div class="kpi-delta" id="delta-cuan"></div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Jumlah Order</div>
                <div class="kpi-value" id="kpi-orders">—</div>
                <div class="kpi-delta" id="delta-orders"></div>
            </div>
            <div class="card kpi">
                <div class="kpi-label">Rata-rata / Order</div>
                <div class="kpi-value" id="kpi-aov">—</div>
                <div class="kpi-delta" id="delta-aov"></div>
            </div>
        </section>

        <!-- Trend -->
        <section class="card dash-card" id="trend-card">
            <div class="dash-card-head">
                <h2>Tren Penjualan</h2>
                <div class="btn-group group-toggle" id="trend-group" role="group" aria-label="Kelompokkan tren per">
                    <button type="button" class="btn btn-outline-secondary" data-group="day" aria-pressed="false">Hari</button>
                    <button type="button" class="btn btn-outline-secondary" data-group="week" aria-pressed="false">Minggu</button>
                    <button type="button" class="btn btn-outline-secondary" data-group="month" aria-pressed="false">Bulan</button>
                    <button type="button" class="btn btn-outline-secondary" data-group="year" aria-pressed="false">Tahun</button>
                </div>
            </div>
            <div class="trend-body">
                <div id="trend-empty" class="empty-state d-none">Belum ada penjualan di periode ini.</div>
                <div id="trend-charts">
                    <div id="trendChart"></div>
                    <p class="chart-caption">Jumlah order</p>
                    <div id="ordersChart"></div>
                </div>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <!-- Top products -->
            <div class="col-lg-7">
                <section class="card dash-card h-100 mb-0">
                    <div class="dash-card-head">
                        <h2>Produk Terlaris</h2>
                        <div class="btn-group seg-toggle" role="group" aria-label="Urutkan berdasarkan">
                            <button type="button" class="btn btn-outline-secondary active" data-sort="qty">Qty</button>
                            <button type="button" class="btn btn-outline-secondary" data-sort="margin">Margin</button>
                        </div>
                    </div>
                    <ol class="rank-list" id="top-products"></ol>
                    <div id="top-empty" class="empty-state d-none">Belum ada produk terjual di periode ini.</div>
                    <p class="rank-meta mb-0 mt-2">Margin = (harga jual − modal) × qty, sebelum diskon order &amp; fee marketplace.</p>
                </section>
            </div>

            <!-- Cost breakdown -->
            <div class="col-lg-5">
                <section class="card dash-card h-100 mb-0">
                    <div class="dash-card-head">
                        <h2>Biaya Operasional</h2>
                        <strong id="costs-total">—</strong>
                    </div>
                    <ul class="rank-list" id="costs"></ul>
                    <div id="costs-empty" class="empty-state d-none">Belum ada biaya di periode ini.</div>
                    <p class="rank-meta mb-0 mt-2">Cash-out manual. Pembelian stok tidak dihitung (sudah masuk modal).</p>
                    <a href="/dashboard/cashbalances" class="btn btn-link px-0 d-inline-flex align-items-center" style="min-height: 44px">Ubah kategori biaya <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                </section>
            </div>
        </div>

        <!-- Inventory -->
        <section class="card dash-card">
            <div class="dash-card-head">
                <h2>Nilai Inventory</h2>
                <small id="inventory-granularity"></small>
            </div>
            <div id="inventory-empty" class="empty-state d-none">Belum ada catatan nilai inventory di periode ini.</div>
            <div id="inventoryChart"></div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const COLORS = { omzet: '#c88432', cuan: '#23693a', neutral: '#4a4036', bar: '#8c9c84' };

    const rupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);
    const compact = (n) => new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(n);
    const number = (n) => new Intl.NumberFormat('id-ID').format(n);
    const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const isNarrow = () => window.matchMedia('(max-width: 575.98px)').matches;

    let state = { period: '{{ $defaultPreset }}', from: '', to: '' };
    let lastData = null;
    let topSort = 'qty';
    let requestId = 0;
    let trendRequestId = 0; // trend-only refetches (grouping buttons) are superseded separately from full loads
    let fullLoading = false;
    let trendShown = null; // the grouping the trend charts currently show
    const charts = {};

    // --- Period state <-> URL ---
    const readUrl = () => {
        const q = new URLSearchParams(window.location.search);
        const period = q.get('period');
        const known = $('#period option').map((i, o) => o.value).get();
        if (period && known.includes(period)) {
            state = { period, from: q.get('from') || '', to: q.get('to') || '' };
        }
    };

    const writeUrl = () => {
        const q = new URLSearchParams({ period: state.period });
        if (state.period === 'custom') { q.set('from', state.from); q.set('to', state.to); }
        const url = window.location.pathname + '?' + q.toString();
        if (url !== window.location.pathname + window.location.search) {
            history.pushState(state, '', url);
        }
    };

    const syncControls = () => {
        $('#period').val(state.period);
        $('#custom-range').toggleClass('d-none', state.period !== 'custom');
        if (state.from) $('#from').val(state.from);
        if (state.to) $('#to').val(state.to);
    };

    // --- Fetch ---
    const TOO_MANY_POINTS = 'Terlalu banyak titik untuk rentang ini';

    // Resolves to the payload, or null when the request was superseded or failed (already reported)
    const fetchDashboard = async (extra, isCurrent) => {
        const q = new URLSearchParams({ period: state.period });
        if (state.period === 'custom') { q.set('from', state.from); q.set('to', state.to); }
        Object.keys(extra).forEach((k) => q.set(k, extra[k]));

        const res = await fetch('/dashboard/data?' + q.toString(), { headers: { 'Accept': 'application/json' } });
        const body = await res.json();
        if (!isCurrent()) return null; // a newer request superseded this one

        if (res.status === 401 || res.status === 419) {
            Swal.fire({ icon: 'info', title: 'Sesi berakhir', text: 'Silakan login ulang.' }).then(() => window.location.reload());
            return null;
        }
        if (!res.ok) {
            const errors = body.errors ? Object.values(body.errors).flat().join(' ') : (body.message || 'Gagal memuat data.');
            Swal.fire({ icon: 'warning', title: 'Periode tidak valid', text: errors });
            return null;
        }
        return body;
    };

    // A full load always starts from the automatic trend grouping (no trend_group sent)
    const load = async () => {
        const id = ++requestId;
        trendRequestId++;
        fullLoading = true;
        $('#trend-card').removeClass('is-busy');
        $('#dash').addClass('is-loading');

        try {
            const body = await fetchDashboard({}, () => id === requestId);
            if (!body) return;

            lastData = body;
            render(body);
        } catch (e) {
            if (id === requestId) {
                Swal.fire({ icon: 'error', title: 'Gagal memuat dashboard', text: 'Coba muat ulang halaman.' });
            }
        } finally {
            if (id === requestId) {
                fullLoading = false;
                $('#dash').removeClass('is-loading');
            }
        }
    };

    // Only the Tren Penjualan and Jumlah order charts change; the rest of the page keeps what it has
    const loadTrend = async (group) => {
        const id = ++trendRequestId;
        $('#trend-card').addClass('is-busy');

        try {
            const body = await fetchDashboard({ trend_group: group }, () => id === trendRequestId);
            if (body) renderTrend(body.trend);
        } catch (e) {
            if (id === trendRequestId) {
                Swal.fire({ icon: 'error', title: 'Gagal memuat tren', text: 'Coba lagi sebentar.' });
            }
        } finally {
            if (id === trendRequestId) $('#trend-card').removeClass('is-busy');
        }
    };

    // --- Render ---
    const renderDelta = (el, change) => {
        if (change === null || change === undefined) {
            el.html('<span class="delta-none" title="Tidak ada data pembanding">—<span class="delta-vs"> vs periode lalu</span></span>');
            return;
        }
        const up = change >= 0;
        const icon = up ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
        const text = (up ? '+' : '') + change.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%';
        el.html('<span class="' + (up ? 'delta-up' : 'delta-down') + '"><i class="bi ' + icon + '" aria-hidden="true"></i> ' + text + '</span> <span class="delta-none delta-vs">vs periode lalu</span>');
    };

    const renderSummary = (summary) => {
        const c = summary.current;
        $('#kpi-laba_bersih').text(rupiah(c.laba_bersih)).toggleClass('is-negative', c.laba_bersih < 0);
        $('#kpi-laba-sub').text('Cuan ' + rupiah(c.cuan) + ' − Biaya ops ' + rupiah(c.biaya_ops));
        $('#kpi-omzet').text(rupiah(c.omzet));
        $('#kpi-cuan').text(rupiah(c.cuan));
        $('#kpi-margin').text(c.margin === null ? '' : '· margin ' + c.margin.toLocaleString('id-ID') + '%');
        $('#kpi-orders').text(number(c.orders));
        $('#kpi-aov').text(rupiah(c.aov));
        ['laba_bersih', 'omzet', 'cuan', 'orders', 'aov'].forEach((m) => renderDelta($('#delta-' + m), summary.changes[m]));
    };

    const upsertChart = (key, selector, options) => {
        if (charts[key]) {
            charts[key].updateOptions(options, true, false);
        } else {
            charts[key] = new ApexCharts(document.querySelector(selector), options);
            charts[key].render();
        }
    };

    // Apex doesn't thin category labels reliably, so show every Nth label and keep the
    // full label for the tooltip (looked up by index)
    const xaxisFor = (labels, maxLabels) => {
        const step = Math.ceil(labels.length / (maxLabels || (isNarrow() ? 4 : 10)));
        return {
            categories: labels.map((l, i) => (i % step === 0 ? l : '')),
            labels: { rotate: 0, hideOverlappingLabels: false, trim: false, style: { colors: '#8b7e74', fontSize: '12px' } },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false },
        };
    };
    const tooltipTitle = (labels) => ({ formatter: (v, opts) => labels[opts.dataPointIndex] });

    const baseChart = (type, height) => ({
        type, height,
        fontFamily: 'inherit',
        toolbar: { show: false },
        zoom: { enabled: false },
        animations: { enabled: false },
    });

    // Week labels ("26 Feb–3 Mar 2024") are long: show only as many as fit the chart width
    const weekLabelBudget = (labels) => {
        const plotWidth = $('#trendChart').width() - 56; // minus the y-axis gutter
        const longest = Math.max(...labels.map((l) => l.length));
        return Math.max(1, Math.min(isNarrow() ? 4 : 10, Math.floor(plotWidth / (longest * 6.2 + 10))));
    };

    // Mark the grouping in use; values with too many points for the range are disabled
    const renderTrendGroups = (trend) => {
        trendShown = trend.granularity;
        $('#trend-group .btn').each(function () {
            const group = $(this).data('group');
            const allowed = trend.allowed_groups.includes(group);
            const selected = group === trend.granularity;
            $(this)
                .toggleClass('active', selected)
                .attr('aria-pressed', selected ? 'true' : 'false')
                .prop('disabled', !allowed);
            if (allowed) $(this).removeAttr('title'); else $(this).attr('title', TOO_MANY_POINTS);
        });
    };

    const renderTrend = (trend) => {
        renderTrendGroups(trend);
        const maxLabels = trend.granularity === 'week' ? weekLabelBudget(trend.labels) : undefined;
        const empty = trend.orders.every((n) => n === 0);
        $('#trend-empty').toggleClass('d-none', !empty);
        $('#trend-charts').toggleClass('d-none', empty);
        if (empty) return;

        upsertChart('trend', '#trendChart', {
            chart: baseChart('line', isNarrow() ? 240 : 300),
            series: [
                { name: 'Omzet', type: 'column', data: trend.omzet },
                { name: 'Cuan', type: 'line', data: trend.cuan },
            ],
            colors: [COLORS.omzet, COLORS.cuan],
            stroke: { width: [0, 2], curve: 'straight' },
            // A line of one point (e.g. Tahun on a single month) draws nothing, so give it a dot
            markers: { size: [0, trend.cuan.length === 1 ? 5 : 0], hover: { size: 5 } },
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 4, borderRadiusApplication: 'end' } },
            dataLabels: { enabled: false },
            legend: { position: 'top', horizontalAlign: 'left', fontSize: '13px', markers: { width: 10, height: 10, radius: 2 } },
            grid: { borderColor: '#efebe5', strokeDashArray: 3, xaxis: { lines: { show: false } } },
            xaxis: xaxisFor(trend.labels, maxLabels),
            yaxis: { labels: { minWidth: 40, formatter: (v) => compact(v), style: { colors: '#8b7e74' } } },
            tooltip: { shared: true, intersect: false, x: tooltipTitle(trend.labels), y: { formatter: (v) => rupiah(v) } },
        });

        upsertChart('orders', '#ordersChart', {
            chart: baseChart('bar', 140),
            series: [{ name: 'Jumlah order', data: trend.orders }],
            colors: [COLORS.neutral],
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 3, borderRadiusApplication: 'end' } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#efebe5', strokeDashArray: 3, xaxis: { lines: { show: false } } },
            xaxis: xaxisFor(trend.labels, maxLabels),
            yaxis: { tickAmount: 2, labels: { minWidth: 40, formatter: (v) => number(Math.round(v)), style: { colors: '#8b7e74' } } },
            tooltip: { x: tooltipTitle(trend.labels), y: { formatter: (v) => number(v) + ' order' } },
        });
    };

    const renderTopProducts = () => {
        if (!lastData) return;
        const items = topSort === 'margin' ? lastData.top_products.by_margin : lastData.top_products.by_qty;
        $('#top-empty').toggleClass('d-none', items.length > 0);

        const max = Math.max(1, ...items.map((p) => Math.max(0, p[topSort])));
        $('#top-products').html(items.map((p, i) => {
            const value = topSort === 'margin' ? rupiah(p.margin) : number(p.qty) + ' pcs';
            const meta = topSort === 'margin'
                ? number(p.qty) + ' pcs · Omzet ' + rupiah(p.omzet)
                : 'Omzet ' + rupiah(p.omzet) + ' · Margin ' + rupiah(p.margin);
            const width = Math.max(0, p[topSort]) / max * 100;
            return '<li>'
                + '<span class="rank-no">' + (i + 1) + '</span>'
                + '<div><div class="rank-name">' + escapeHtml(p.name) + '</div>'
                + '<div class="rank-meta">' + meta + '</div>'
                + '<div class="rank-bar"><span style="width:' + width + '%;background:' + COLORS.bar + '"></span></div></div>'
                + '<span class="rank-value">' + value + '</span>'
                + '</li>';
        }).join(''));
    };

    const renderCosts = (costs) => {
        $('#costs-total').text(rupiah(costs.total));
        $('#costs-empty').toggleClass('d-none', costs.items.length > 0);

        const max = Math.max(1, ...costs.items.map((c) => c.total));
        $('#costs').html(costs.items.map((c) => {
            return '<li style="grid-template-columns: 1fr auto">'
                + '<div><div class="rank-name">' + escapeHtml(c.label) + '</div>'
                + '<div class="rank-bar"><span style="width:' + (c.total / max * 100) + '%;background:' + COLORS.neutral + '"></span></div></div>'
                + '<span class="rank-value">' + rupiah(c.total) + '<small>' + c.share.toLocaleString('id-ID') + '%</small></span>'
                + '</li>';
        }).join(''));
    };

    const renderInventory = (inv) => {
        $('#inventory-granularity').text(inv.granularity === 'day' ? 'snapshot harian' : 'nilai akhir bulan');
        const empty = inv.values.every((v) => v === null);
        $('#inventory-empty').toggleClass('d-none', !empty);
        $('#inventoryChart').toggleClass('d-none', empty);
        if (empty) return;

        upsertChart('inventory', '#inventoryChart', {
            chart: baseChart('area', isNarrow() ? 200 : 260),
            series: [{ name: 'Nilai inventory', data: inv.values }],
            colors: [COLORS.neutral],
            stroke: { width: 2, curve: 'straight' },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0.02 } },
            markers: { size: inv.values.filter((v) => v !== null).length <= 2 ? 4 : 0, hover: { size: 5 } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#efebe5', strokeDashArray: 3, xaxis: { lines: { show: false } } },
            xaxis: xaxisFor(inv.labels),
            yaxis: { labels: { formatter: (v) => compact(v), style: { colors: '#8b7e74' } } },
            tooltip: { x: tooltipTitle(inv.labels), y: { formatter: (v) => (v === null ? 'Tidak ada catatan' : rupiah(v)) } },
        });
    };

    const render = (data) => {
        $('#period-label').text(data.period.label + ' · dibanding ' + data.period.prev_label);
        if (data.period.key === 'custom') {
            state.from = data.period.from;
            state.to = data.period.to;
            syncControls();
        }
        renderSummary(data.summary);
        renderTrend(data.trend);
        renderTopProducts();
        renderCosts(data.costs);
        renderInventory(data.inventory);
    };

    // --- Events ---
    $(document).ready(function () {
        readUrl();
        syncControls();
        load();

        $('#period').on('change', function () {
            const period = $(this).val();
            if (period === 'custom') {
                // Pre-fill with the range currently shown so "Terapkan" is one tap away
                if (lastData) {
                    $('#from').val(state.from || lastData.period.from);
                    $('#to').val(state.to || lastData.period.to);
                }
                $('#custom-range').removeClass('d-none');
                return;
            }
            state = { period, from: '', to: '' };
            syncControls();
            writeUrl();
            load();
        });

        $('#apply-custom').on('click', function () {
            const from = $('#from').val();
            const to = $('#to').val();
            if (!from || !to) {
                Swal.fire({ icon: 'info', title: 'Pilih tanggal', text: 'Isi tanggal mulai dan akhir.' });
                return;
            }
            if (from > to) {
                Swal.fire({ icon: 'warning', title: 'Tanggal terbalik', text: 'Tanggal mulai harus sebelum tanggal akhir.' });
                return;
            }
            state = { period: 'custom', from, to };
            writeUrl();
            load();
        });

        $('#trend-group .btn').on('click', function () {
            const group = $(this).data('group');
            if (this.disabled || fullLoading || !lastData || group === trendShown) return;
            loadTrend(group);
        });

        $('.seg-toggle .btn').on('click', function () {
            topSort = $(this).data('sort');
            $('.seg-toggle .btn').removeClass('active');
            $(this).addClass('active');
            renderTopProducts();
        });

        window.addEventListener('popstate', function () {
            readUrl();
            if (!new URLSearchParams(window.location.search).get('period')) {
                state = { period: '{{ $defaultPreset }}', from: '', to: '' };
            }
            syncControls();
            load();
        });
    });
</script>
@endpush
