@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 fw-bold">Dashboard</h1>
        <p class="text-muted small">Executive Overview & Business Insights</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <select id="global-filter" class="form-select form-select-sm" style="width: 150px;">
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="this_year" selected>This Year</option>
        </select>
    </div>
</div>

<!-- Executive Metrics -->
<!-- Using 'row-cols-1 row-cols-md-2 row-cols-xl-5' for responsive 5-column layout -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-4 mb-4">
    <!-- Revenue -->
    <div class="col">
        <div class="card h-100 bg-metric-blue border-0 shadow-sm position-relative overflow-hidden">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-white bg-opacity-50 p-2 rounded-3">
                        <i class="bi bi-currency-dollar fs-5 text-primary"></i>
                    </div>
                </div>
                <h6 class="text-uppercase fw-bold opacity-75 small mb-1">Total Revenue</h6>
                <h3 class="fw-bold mb-0" id="total_revenue">-</h3>
                <!-- Optional: <small class="text-success"><i class="bi bi-arrow-up"></i> 12%</small> -->
            </div>
        </div>
    </div>

    <!-- Orders -->
    <div class="col">
        <div class="card h-100 bg-metric-green border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-white bg-opacity-50 p-2 rounded-3">
                        <i class="bi bi-cart-fill fs-5 text-success"></i>
                    </div>
                </div>
                <h6 class="text-uppercase fw-bold opacity-75 small mb-1">Total Orders</h6>
                <h3 class="fw-bold mb-0" id="total_orders">-</h3>
            </div>
        </div>
    </div>

    <!-- Avg Order Value -->
    <div class="col">
        <div class="card h-100 bg-metric-teal border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-white bg-opacity-50 p-2 rounded-3">
                        <i class="bi bi-graph-up-arrow fs-5 text-info"></i>
                    </div>
                </div>
                <h6 class="text-uppercase fw-bold opacity-75 small mb-1">Avg Order Val</h6>
                <h3 class="fw-bold mb-0" id="avg_order_value">-</h3>
            </div>
        </div>
    </div>

    <!-- Cash In -->
    <div class="col">
        <div class="card h-100 bg-metric-orange border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-white bg-opacity-50 p-2 rounded-3">
                        <i class="bi bi-credit-card fs-5 text-warning"></i>
                    </div>
                </div>
                <h6 class="text-uppercase fw-bold opacity-75 small mb-1">Cash In (Mo)</h6>
                <h4 class="fw-bold mb-0" id="cash_in">-</h4>
                <small class="opacity-75 small" id="cash_in_last">Prev: -</small>
            </div>
        </div>
    </div>

    <!-- Cash Out -->
    <div class="col">
        <div class="card h-100 bg-metric-red border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-white bg-opacity-50 p-2 rounded-3">
                        <i class="bi bi-wallet2 fs-5 text-danger"></i>
                    </div>
                </div>
                <h6 class="text-uppercase fw-bold opacity-75 small mb-1">Cash Out (Mo)</h6>
                <h4 class="fw-bold mb-0" id="cash_out">-</h4>
                <small class="opacity-75 small" id="cash_out_last">Prev: -</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1: Transactions (Value) & Order Count -->
<div class="row mb-4">
    <!-- Monthly Transactions Value (Line/Area Chart) -->
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-currency-exchange me-2 text-primary"></i> Monthly Transaction Values</h6>
                <select class="form-select form-select-sm w-auto" id="transactions_year_filter">
                    @for($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="card-body">
                <div id="monthlyTransactionsChart" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>

    <!-- Monthly Order Count (New Chart) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
             <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-cart-check me-2 text-info"></i> Monthly Order Count</h6>
            </div>
            <div class="card-body">
                <div id="monthlyOrderCountChart" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2: Top Products & Inventory -->
<div class="row mb-4">
    <!-- Top Selling Products -->
    <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-warning"></i> Top Selling Products</h6>
            </div>
            <div class="card-body">
                <div id="topProductsChart" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>
    
     <!-- Inventory Value (Bar) -->
     <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-success"></i> Inventory Value Trend</h6>
            </div>
            <div class="card-body">
                 <div id="inventoryChart" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 3: Daily Activity -->
<div class="row">
    <!-- Daily Revenue (Area) -->
    <div class="col-12">

        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-info"></i> Daily Activity</h6>
                <div class="input-group input-group-sm w-auto">
                    <input type="date" class="form-control" id="daily_from" value="{{ date('Y-m-d', strtotime('-6 days')) }}">
                    <input type="date" class="form-control" id="daily_to" value="{{ date('Y-m-d') }}">
                    <button class="btn btn-outline-secondary" type="button" id="daily_filter_btn"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="card-body">
                 <div id="dailyChart" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Format Currency Helper
    const formatRupiah = (number) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
    }

    // --- Chart Instances ---
    var monthlyChart, monthlyOrderCountChart, topProductsChart, inventoryChart, dailyChart;

    // --- Fetch Data ---
    const fetchData = async (type = 'all', params = {}) => {
        try {
            const query = new URLSearchParams({ type, ...params }).toString();
            const response = await fetch(`/dashboard/data?${query}`);
            return await response.json();
        } catch (error) {
            console.error("Error fetching dashboard data:", error);
            return null;
        }
    };

    // --- Render Functions ---

    // 1. Metrics
    const renderMetrics = (metrics) => {
        if(!metrics) return;
        $('#total_revenue').text(formatRupiah(metrics.total_revenue));
        $('#total_orders').text(metrics.total_orders); // Assuming this is kept as is (Value or Count? User said "another chart for number of orders" implying this might be value, but usually metrics card is count. Let's leave as is for now)
        $('#avg_order_value').text(formatRupiah(metrics.avg_order_value));
        $('#cash_in').text(formatRupiah(metrics.cash_in));
        $('#cash_out').text(formatRupiah(metrics.cash_out));
        $('#cash_in_last').text('Prev: ' + formatRupiah(metrics.cash_in_last));
        $('#cash_out_last').text('Prev: ' + formatRupiah(metrics.cash_out_last));
    };

    // 2. Monthly Transactions Value Chart (Line/Area)
    const renderMonthlyChart = (data) => {
        const options = {
            series: [{
                name: 'Revenue',
                type: 'column',
                data: data.revenue
            }, {
                name: 'Order Value',
                type: 'line',
                data: data.orders // This is total_order value
            }],
            chart: {
                height: 350,
                type: 'line',
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false }
            },
            stroke: { width: [0, 4] },
            colors: ['#d4c5b0', '#8c9c84'], // Secondary (Beige), Primary (Sage)
            dataLabels: { enabled: true, enabledOnSeries: [1] },
            labels: data.labels,
            yaxis: [{
                title: { text: 'Revenue' },
                labels: { formatter: (val) => val >= 1000000 ? (val/1000000).toFixed(1) + 'M' : val }
            }, {
                opposite: true,
                title: { text: 'Order Value' },
                labels: { formatter: (val) => val >= 1000000 ? (val/1000000).toFixed(1) + 'M' : val }
            }],
             plotOptions: {
                bar: { borderRadius: 4, columnWidth: '50%' }
            },
            legend: { position: 'top' },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatRupiah(val);
                    }
                }
            }
        };

        if(monthlyChart) { monthlyChart.updateOptions(options); }
        else {
            monthlyChart = new ApexCharts(document.querySelector("#monthlyTransactionsChart"), options);
            monthlyChart.render();
        }
    };

    // 2.5 Monthly Order Count Chart (New)
    const renderOrderCountChart = (data) => {
        const options = {
            series: [{
                name: 'Number of Orders',
                data: data.order_counts
            }],
            chart: {
                type: 'bar',
                height: 350,
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '45%',
                    distributed: false
                }
            },
            dataLabels: { enabled: true },
            stroke: { width: 0 },
            colors: ['#e67e22'], // Accent Color
            xaxis: {
                categories: data.labels,
            },
            yaxis: {
                title: { text: 'Count' }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + " Orders";
                    }
                }
            }
        };

        if(monthlyOrderCountChart) { monthlyOrderCountChart.updateOptions(options); }
        else {
            monthlyOrderCountChart = new ApexCharts(document.querySelector("#monthlyOrderCountChart"), options);
            monthlyOrderCountChart.render();
        }
    };

    // 3. Top Products Chart (Horizontal Bar)
    const renderTopProductsChart = (data) => {
        const names = data.map(item => item.name);
        const qty = data.map(item => item.Qty_Sold);
        
        const options = {
            series: [{
                name: 'Qty Sold',
                data: qty
            }],
            chart: {
                type: 'bar',
                height: 350,
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    barHeight: '60%'
                }
            },
            dataLabels: { enabled: true },
            xaxis: {
                categories: names,
            },
            colors: ['#4a4036'], // Dark Brown
            grid: { strokeDashArray: 4 }
        };

        if(topProductsChart) { topProductsChart.updateOptions(options); }
        else {
            topProductsChart = new ApexCharts(document.querySelector("#topProductsChart"), options);
            topProductsChart.render();
        }
    };

    // 4. Inventory Chart
    const renderInventoryChart = (data) => {
        const options = {
            series: [{
                name: 'Value',
                data: data.values
            }],
            chart: {
                type: 'area',
                height: 350,
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: { categories: data.labels },
            tooltip: { y: { formatter: (val) => formatRupiah(val) } },
            colors: ['#4a4036'], // Dark brown
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.3, stops: [0, 90, 100] }
            }
        };

        if(inventoryChart) { inventoryChart.updateOptions(options); }
        else {
            inventoryChart = new ApexCharts(document.querySelector("#inventoryChart"), options);
            inventoryChart.render();
        }
    };

    // 5. Daily Chart
    const renderDailyChart = (data) => {
        const options = {
            series: [{
                name: 'Revenue',
                data: data.revenue
            }],
            chart: {
                type: 'bar',
                height: 350,
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false }
            },
            colors: ['#8c9c84'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '40%' } },
            xaxis: { categories: data.labels },
            tooltip: { y: { formatter: (val) => formatRupiah(val) } }
        };

        if(dailyChart) { dailyChart.updateOptions(options); }
        else {
            dailyChart = new ApexCharts(document.querySelector("#dailyChart"), options);
            dailyChart.render();
        }
    }


    $(document).ready(function() {
        // Initial Load
        fetchData('all').then(data => {
            renderMetrics(data.metrics);
            renderMonthlyChart(data.transactions);
            renderOrderCountChart(data.transactions); // Use same transactions data which now has order_counts
            renderTopProductsChart(data.top_products);
            renderInventoryChart(data.inventory);
            renderDailyChart(data.daily);
        });

        // Event: Transactions Year Filter
        $('#transactions_year_filter').change(function() {
            const year = $(this).val();
            fetchData('transactions', { year }).then(data => {
                renderMonthlyChart(data.transactions);
                renderOrderCountChart(data.transactions);
            });
        });

        // Event: Daily Filter
        $('#daily_filter_btn').click(function() {
            const date_from = $('#daily_from').val();
            const date_to = $('#daily_to').val();
            fetchData('daily', { date_from, date_to }).then(data => renderDailyChart(data.daily));
        });

        // Global Filter
        $('#global-filter').change(function() {
            const period = $(this).val();
            
            // Update Headers (Optional: could update text to say "Revenue (This Month)" etc)
            
            fetchData('metrics', { period }).then(data => {
                renderMetrics(data.metrics);
            });
        });
    });
</script>
@endpush
