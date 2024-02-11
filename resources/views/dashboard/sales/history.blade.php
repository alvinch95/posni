@extends('dashboard.layouts.main')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;1,100;1,500&family=REM:wght@100&display=swap" rel="stylesheet">
@endsection

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Sales Order Histories</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="row mb-3">
  <div class="col-md-8">
      <form action="/dashboard/sales/history">
          <div class="row mb-3 align-items-center">
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="text" class="form-control" placeholder="Search items.." name="search" value="{{ request('search') }}">
                <label for="search" class="form-label text-muted">Search keyword</label>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="date" class="form-control" id="order_date_from" name="order_date_from" value="{{ request('order_date_from') }}">
                <label for="order_date_from" class="form-label">Order Date From</label>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="date" class="form-control" id="order_date_to" name="order_date_to" value="{{ request('order_date_to') }}">
                <label for="order_date_to" class="form-label">Order Date To</label>
              </div>
            </div>
            <div class="col-lg-1">
              <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
            </div>
          </div>
      </form>


      <div class="d-flex align-items-center">
        {{-- <h4 class="d-inline" style="font-family: 'Montserrat', sans-serif;">Sorting</h4> --}}
        {{-- <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'asc']) }}" class="btn btn-dark mx-1 btn-sm">Item Name (ASC)</a>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'desc']) }}" class="btn btn-outline-dark mx-1 btn-sm">Item Name (DESC)</a>
  
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => 'asc']) }}" class="btn btn-warning mx-1 btn-sm">Date (ASC)</a>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => 'desc']) }}" class="btn btn-outline-warning mx-1 btn-sm">Date (DESC)</a> --}}
        <a href="" class="btn btn-sm mx-1 btn-filter-date text-white" style="background-color: #008080;" data-date-range="today">Today</a>
        <a href="" class="btn btn-sm mx-1 btn-filter-date text-white" style="background-color: #008080;" data-date-range="last7days">Last 7 days</a>
        <a href="" class="btn btn-sm mx-1 btn-filter-date text-white" style="background-color: #008080;" data-date-range="thisMonth">This Month</a>
        <a href="" class="btn btn-sm mx-1 btn-filter-date text-white" style="background-color: #008080;" data-date-range="lastMonth">Last Month</a>
      </div>
  </div>
</div>

<div class="table-responsive col-lg-8">
    @if ($sales_orders->count())
      <table class="table table-bordered border-dark table-striped table-sm">
        <caption>
          <div class="d-flex float-end">
            <label for="page-size-select" class="mx-2">Page Size:</label>
            <select id="page-size-select" class="form-select page-size-select">
                <option value="10" {{ $pageSize==10?"selected":"" }}>10</option>
                <option value="50" {{ $pageSize==50?"selected":"" }}>50</option>
                <option value="100" {{ $pageSize==100?"selected":"" }}>100</option>
                <option value="200" {{ $pageSize==200?"selected":"" }}>200</option>
            </select>
          </div>
          Showing {{ $pageSize<=$totalData?$sales_orders->count():$totalData }} of {{ $totalData }} results
        </caption>
        <thead class="table-dark border-dark">
          <tr>
            <th scope="col">#</th>
            <th scope="col">Sales Order No</th>
            <th scope="col">Date</th>
            <th scope="col">Customer</th>
            <th scope="col">Penjualan Kotor</th>
            <th scope="col">Penjualan Bersih</th>
            <th scope="col">Laba</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @php
            $totalBeforeDiscountSum = 0;
            $totalOrderSum = 0;
            $totalRevenueSum = 0;
          @endphp
          @foreach ($sales_orders as $so)
              <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $so->order_number }}</td>
                  <td>{{ $so->order_date }}</td>
                  <td>{{ $so->customer->name }}</td>
                  <td>{{ "Rp. ".number_format($so->total_before_discount, 0, ',', '.') }}</td>
                  <td>{{ "Rp. ".number_format($so->total_order, 0, ',', '.') }}</td>
                  <td>{{ "Rp. ".number_format($so->total_revenue, 0, ',', '.') }}</td>
                  <td>
                    <a href="/dashboard/sales/{{ $so->id }}" class="badge bg-primary" title="View Order Detail"><span data-feather="eye"></span></a>
                    <form action="/dashboard/sales/{{ $so->id }}" method="post" class="d-inline">
                      @method('delete')
                      @csrf
                      <button class="badge bg-danger border-0 hapus" title="Cancel Order"><span data-feather="x-circle"></span></button>
                    </form>
                </td>
              </tr>
          @php
            $totalBeforeDiscountSum += $so->total_before_discount;
            $totalOrderSum += $so->total_order;
            $totalRevenueSum += $so->total_revenue;
          @endphp
          @endforeach
        </tbody>
        <tfoot>
          <tr>
              <td colspan="4"><strong>Total:</strong></td>
              <td><strong>{{ "Rp. ".number_format($totalBeforeDiscountSum, 0, ',', '.') }}</strong></td>
              <td><strong>{{ "Rp. ".number_format($totalOrderSum, 0, ',', '.') }}</strong></td>
              <td><strong>{{ "Rp. ".number_format($totalRevenueSum, 0, ',', '.') }}</strong></td>
              <td><strong>{{ round(($totalRevenueSum/$totalBeforeDiscountSum)*100,1)."%" }}</strong></td>
          </tr>
      </tfoot>
      </table>
    @else
      <p class="text-center fs-4">No Sales Order Found.</p>
    @endif
</div>
<div class="d-flex justify-content-center">
  {{ $sales_orders->links() }}
</div>
<style>
  /* Style the page size select */
  .page-size-select {
      padding: 4px 8px; /* Adjust padding as needed */
      font-size: 12px; /* Adjust font size as needed */
      width: 70px; /* Adjust width as needed */
  }
</style>
  <script>
    $(document).ready(function(){
      $('#page-size-select').on('change', function() {
          const selectedPageSize = $(this).val();
          const currentUrl = window.location.href;

          // Replace the "page_size" query parameter with the selected page size
          const updatedUrl = updateQueryStringParameter(currentUrl, 'page_size', selectedPageSize);

          // Redirect to the updated URL
          window.location.href = updatedUrl;
      });

      // Function to update query parameters in URL
      function updateQueryStringParameter(uri, key, value) {
          const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
          const separator = uri.indexOf('?') !== -1 ? "&" : "?";
          if (uri.match(re)) {
              return uri.replace(re, '$1' + key + "=" + value + '$2');
          }
          return uri + separator + key + "=" + value;
      }

      $('.btn-filter-date').click(function(event) {
          event.preventDefault();

          // Define the date ranges (you can modify these)
          var today = new Date().toISOString().split('T')[0];
          var last7Days = new Date(Date.now() - 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
          var thisMonthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 2).toISOString().split('T')[0];
          var lastMonthStart = new Date(new Date().getFullYear(), new Date().getMonth() - 1, 2).toISOString().split('T')[0];
          var lastMonthEnd = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];

          // Get the clicked button's data attribute to determine the date range
          var dateRange = $(this).data('date-range');
          var orderDateFrom, orderDateTo;

          // Set orderDateFrom and orderDateTo based on the selected date range
          switch (dateRange) {
              case 'today':
                  orderDateFrom = today;
                  orderDateTo = today;
                  break;
              case 'last7days':
                  orderDateFrom = last7Days;
                  orderDateTo = today;
                  break;
              case 'thisMonth':
                  orderDateFrom = thisMonthStart;
                  orderDateTo = today;
                  break;
              case 'lastMonth':
                  orderDateFrom = lastMonthStart;
                  orderDateTo = lastMonthEnd;
                  break;
              default:
                  // Default case (today)
                  orderDateFrom = today;
                  orderDateTo = today;
          }

          // Update the input fields
          $('#order_date_from').val(orderDateFrom);
          $('#order_date_to').val(orderDateTo);

          // Submit the form
          $('form').submit();
      });

      $('.hapus').click(function(e) {
        e.preventDefault();
        var form = $(this).parents('form');
        Swal.fire({
            title: 'Are you sure to cancel this transaction?',
            text: "Please double check!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.value) {
                form.submit();
            }
        });
      });
    }); 
  </script>
@endsection