@extends('dashboard.layouts.main')


@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Welcome back, {{ auth()->user()->name }}</h1>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card" style="min-height: 344px;">
            <div class="card-header" style="background-color: #87CEEB; color: white;">
                <b>Top Selling Products</b>
            </div>
            <div class="card-body">
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
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header" style="background-color: #C8A2C8; color: white;">
                Chart 3
            </div>
            <div class="card-body">
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
                    {!! $chart->container() !!}
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ $chart->cdn() }}"></script>

<script>
$(document).ready(function() {
    // Find the chart container by its ID
    var chartContainer = $('#chart-container');
    
    // Find all elements with numbers that need currency formatting within the container
    chartContainer.find('.apexcharts-bar-area').each(function(index) {
        // Get the current text content
        var text = $(this).attr('val');
        
        // Convert the text to a floating-point number
        var number = parseFloat(text);
        
        // Check if the conversion was successful
        if (!isNaN(number)) {
            // Format the number as currency (adjust options as needed)
            var formattedCurrency = number.toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR'
            });

            console.log(formattedCurrency);

            // Create a separate element for each bar and position it
            var formattedCurrencyElement = $('<div class="formatted-currency"></div>');
            formattedCurrencyElement.text(formattedCurrency);
            formattedCurrencyElement.css({
                display: 'inline',
                position: 'absolute',
                top: (index * 40) + 'px', // Adjust vertical position as needed
                left: '20px', // Adjust horizontal position as needed
                fontWeight: 'bold'
            });

            // Replace the element's content with the formatted currency
            // $(this).attr('val', formattedCurrency);

            $(this).append(formattedCurrencyElement);
        }
    });
});
</script>

{{ $chart->script() }}
@endsection