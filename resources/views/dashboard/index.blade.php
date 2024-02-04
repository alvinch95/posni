@extends('dashboard.layouts.main')


@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Welcome back, {{ auth()->user()->name }}</h1>
</div>

@can('admin')
    
<div class="row mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header" style="background-color: #C8A2C8; color: white;">
                Daily Report
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <form action="{{ route('dashboard.index') }}" method="GET">
                        @csrf
                        <div class="row mb-3 align-items-center">
                          <div class="col-lg-5">
                            <div class="form-floating mb-1">
                              <input type="date" class="form-control" id="order_date_from" name="order_date_from" value="{{ request('order_date_from') }}">
                              <label for="order_date_from" class="form-label">Order Date From</label>
                            </div>
                          </div>
                          <div class="col-lg-5">
                            <div class="form-floating mb-1">
                              <input type="date" class="form-control" id="order_date_to" name="order_date_to" value="{{ request('order_date_to') }}">
                              <label for="order_date_to" class="form-label">Order Date To</label>
                            </div>
                          </div>
                          <div class="col-lg-1">
                            <button type="submit" class="btn btn-primary ms-2">Apply</button>
                          </div>
                        </div>
                    </form>
                </div>
            {!! $daily_chart->container() !!}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="min-height: 344px;">
            <div class="card-header" style="background-color: #87CEEB; color: white;">
                <b>Top Selling Products</b>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr style="background-color: #87CEEB; color: white;">
                            <th>Product Name</th>
                            <th>Sales Quantity</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topSellingProducts as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ number_format($product->Qty_Sold, 0, ',', '.') }}</td>
                            <td>{{ "Rp. ".number_format($product->Total_Revenue, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                                <div class="d-flex justify-content-center">
                                    {{ $topSellingProducts->links() }}
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Inventory Value Chart --}}
        <div class="card mt-3">
            <div class="card-header" style="background-color: #C0C0C0; color: white;">
                <b>Inventory Value</b>
            </div>
            <div class="card-body">
                {!! $inventory_value_chart->container() !!}
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header" style="background-color: #008080; color: white;">
                Monthly Transactions
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <form action="{{ route('dashboard.index') }}" method="GET" class="d-flex align-items-center">
                        @csrf
                        <div>
                            <select class="form-select" id="year" name="year">
                                <option value="" disabled selected hidden>Select year</option>
                                @for ($year = 2023; $year <= $currentYear; $year++)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary ms-2">Apply</button>
                    </form>
                </div>
                <div id="chart-container">
                    {!! $monthly_chart->container() !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endcan

<script src="{{ $monthly_chart->cdn() }}"></script>
<script src="{{ $daily_chart->cdn() }}"></script>
<script src="{{ $inventory_value_chart->cdn() }}"></script>

<script>
$(document).ready(function() {
    
});
</script>

{{ $monthly_chart->script() }}
{{ $daily_chart->script() }}
{{ $inventory_value_chart->script() }}
@endsection