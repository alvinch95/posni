@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Welcome back, {{ auth()->user()->name }}</h1>
</div>

@can('admin')

<!-- Top Metrics Row -->
<div class="row mb-3">
    <!-- Total Revenue Card -->
    <div class="col-6 col-md-3 mb-3">
        <div class="card" style="background-color: #AEDFF7; color: #333; height: 140px;">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Total Revenue</h5>
                    <i class="bi bi-currency-dollar fs-3"></i>
                </div>
                <p class="card-text fs-5">Rp. {{ number_format($totalRevenueSum, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Total Orders Card -->
    <div class="col-6 col-md-3 mb-3">
        <div class="card" style="background-color: #D5E8D4; color: #333; height: 140px;">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Total Orders</h5>
                    <i class="bi bi-cart-fill fs-3"></i>
                </div>
                <p class="card-text fs-5">{{ number_format($totalOrdersSum, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Average Order Value Card -->
    <div class="col-6 col-md-3 mb-3">
        <div class="card" style="background-color: #F9E7C7; color: #333; height: 140px;">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Average Order Value</h5>
                    <i class="bi bi-graph-up-arrow fs-3"></i>
                </div>
                <p class="card-text fs-5">Rp. {{ number_format($averageOrderValue, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Cash In Card -->
    <div class="col-6 col-md-3 mb-3">
        <div class="card" style="background-color: #FFE6CC; color: #333; height: 140px;">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Cash In (This Month)</h5>
                    <i class="bi bi-credit-card fs-3"></i>
                </div>
                <p class="card-text fs-5">Rp. {{ number_format($totalCashIn, 0, ',', '.') }}</p>
                <small class="text-muted">Last Month: Rp. {{ number_format($totalCashInLastMonth, 0, ',', '.') }}</small>
            </div>
        </div>
    </div>

    <!-- Cash Out Card -->
    <div class="col-6 col-md-3 mb-3">
        <div class="card" style="background-color: #FFCCCC; color: #333; height: 140px;">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Cash Out (This Month)</h5>
                    <i class="bi bi-wallet2 fs-3"></i>
                </div>
                <p class="card-text fs-5">Rp. {{ number_format($totalCashOut, 0, ',', '.') }}</p>
                <small class="text-muted">Last Month: Rp. {{ number_format($totalCashOutLastMonth, 0, ',', '.') }}</small>
            </div>
        </div>
    </div>
</div>


<!-- Daily Report and Top Selling Products Row -->
<div class="row mb-3">
    <!-- Daily Report Chart -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header text-white" style="background-color: #C8A2C8;">
                <h5 class="mb-0">Daily Report</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('dashboard.index') }}" method="GET" class="mb-3">
                    @csrf
                    <div class="row align-items-center">
                        <div class="col-lg-5">
                            <div class="form-floating">
                                <input type="date" class="form-control" id="order_date_from" name="order_date_from" value="{{ request('order_date_from') }}">
                                <label for="order_date_from">Order Date From</label>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="form-floating">
                                <input type="date" class="form-control" id="order_date_to" name="order_date_to" value="{{ request('order_date_to') }}">
                                <label for="order_date_to">Order Date To</label>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                    </div>
                </form>
                <div id="daily-chart" style="width: 100%; height: 400px;"></div>
            </div>
        </div>
    </div>
    <!-- Top Selling Products Table -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Top Selling Products</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr class="text-center bg-light text-dark">
                            <th>Product Name</th>
                            <th>Sales Qty</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topSellingProducts as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td class="text-center">{{ number_format($product->Qty_Sold, 0, ',', '.') }}</td>
                            <td class="text-end">{{ "Rp. " . number_format($product->Total_Revenue, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-center overflow-auto">
                    {{ $topSellingProducts->links() }}
                </div>                
            </div>
        </div>

        <!-- Inventory Value Chart -->
        <div class="card mt-3">
            <div class="card-header text-white" style="background-color: #C0C0C0;">
                <h5 class="mb-0">Inventory Value</h5>
            </div>
            <div class="card-body">
                <div id="inventory-chart" style="width: 100%; height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Transactions Row -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header text-white" style="background-color: #008080;">
                <h5 class="mb-0">Monthly Transactions</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('dashboard.index') }}" method="GET" class="d-flex align-items-center mb-3">
                    @csrf
                    <div class="me-3">
                        <select class="form-select" id="year" name="year">
                            <option value="" disabled selected hidden>Select year</option>
                            @for ($y = 2023; $y <= $currentYear; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </form>
                <div id="monthly-chart" style="width: 100%; height: 400px;"></div>
            </div>
        </div>
    </div>
</div>
@endcan

<style>
    @media (max-width: 576px) {
        .card-body small {
            white-space: normal;
            font-size: 0.8rem;
            display: block;
        }
        
        .card-title {
            font-size: 1rem; /* Adjust as needed */
        }

        .card-text {
            font-size: 1rem; /* Adjust as needed */
        }
    }
</style>

<!-- ApexCharts Scripts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.36.0/dist/apexcharts.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Monthly Transactions Chart
        var monthlyOptions = {
            chart: { type: 'bar', height: 400 },
            series: [
                { name: 'Total Orders', data: @json($monthlyTotalOrders) },
                { name: 'Total Revenue', data: @json($monthlyTotalRevenue) }
            ],
            xaxis: { categories: @json($months) },
            title: { text: 'Monthly Transactions for {{ $year }}' }
        };
        var monthlyChart = new ApexCharts(document.querySelector("#monthly-chart"), monthlyOptions);
        monthlyChart.render();

        // Daily Report Chart
        var dailyOptions = {
            chart: { type: 'line', height: 400 },
            series: [
                { name: 'Total Orders', data: @json($dailyTotalOrders) },
                { name: 'Total Revenue', data: @json($dailyTotalRevenue) },
                { name: 'Count Orders', data: @json($dailyCountOrder) }
            ],
            xaxis: { categories: @json($dailyDays) },
            title: { text: 'Daily Report' }
        };
        var dailyChart = new ApexCharts(document.querySelector("#daily-chart"), dailyOptions);
        dailyChart.render();

        // Inventory Value Chart
        var inventoryOptions = {
            chart: { type: 'line', height: 400 },
            series: [{ name: 'Inventory Value', data: @json($totalInventoryValue) }],
            xaxis: { categories: @json($inventoryValueDays) },
            title: { text: 'Inventory Value' }
        };
        var inventoryChart = new ApexCharts(document.querySelector("#inventory-chart"), inventoryOptions);
        inventoryChart.render();
    });
</script>
@endsection
