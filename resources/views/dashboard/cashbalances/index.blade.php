@extends('dashboard.layouts.main')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;1,100;1,500&family=REM:wght@100&display=swap" rel="stylesheet">
@endsection

@section('container')
@php
  use Carbon\Carbon; // Import Carbon for date manipulation
@endphp

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Cash Histories</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="row mb-3">
  <div class="col-md-8">
      <form id="filterForm" action="/dashboard/cashbalances">
          <div class="row mb-3 align-items-center">
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="text" class="form-control" placeholder="Search items.." name="search" value="{{ request('search') }}">
                <label for="search" class="form-label text-muted">Search keyword</label>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="date" class="form-control" id="transaction_date_from" name="transaction_date_from" value="{{ request('transaction_date_from') }}">
                <label for="transaction_date_from" class="form-label">Date From</label>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="date" class="form-control" id="transaction_date_to" name="transaction_date_to" value="{{ request('transaction_date_to') }}">
                <label for="transaction_date_to" class="form-label">Date To</label>
              </div>
            </div>
            <div class="col-lg-1">
              <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
            </div>
          </div>
      </form>
  </div>
  <div class="d-flex align-items-center">
    <a href="" class="btn btn-sm mx-1 btn-filter-date text-white" style="background-color: #008080;" data-date-range="today">Today</a>
    <a href="" class="btn btn-sm mx-1 btn-filter-date text-white" style="background-color: #008080;" data-date-range="last7days">Last 7 days</a>
    <a href="" class="btn btn-sm mx-1 btn-filter-date text-white" style="background-color: #008080;" data-date-range="thisMonth">This Month</a>
    <a href="" class="btn btn-sm mx-1 btn-filter-date text-white" style="background-color: #008080;" data-date-range="lastMonth">Last Month</a>
    <div class="d-flex align-items-center border border-dark border-2 px-5 pt-2 ml-5">
      <p class="fs-6"><b>Cash Sekarang: {{ "Rp. ".number_format($currentCash, 0, ',', '.') }}</b></p>
    </div>
  </div>
</div>

<div class="table-responsive col-lg-10">
    @if ($cash_balances->count())
      <table class="table table-bordered border-dark table-striped table-sm w-auto table-hover">
        <caption>
          <div class="d-flex float-end">
            <label for="page-size-select" class="mx-2">Page Size:</label>
            <select id="page-size-select" class="form-select page-size-select">
                <option value="10" {{ $pageSize==10?"selected":"" }}>10</option>
                <option value="50" {{ $pageSize==50?"selected":"" }}>50</option>
                <option value="100" {{ $pageSize==100?"selected":"" }}>100</option>
                <option value="200" {{ $pageSize==200?"selected":"" }}>200</option>
                <option value="500" {{ $pageSize==500?"selected":"" }}>500</option>
            </select>
          </div>
          Showing {{ $pageSize<=$totalData?$cash_balances->count():$totalData }} of {{ $totalData }} results
        </caption>
        <thead class="table-dark border-dark">
          <tr>
            <th class="p-2" scope="col">#</th>
            <th class="p-2" scope="col">Tanggal</th>
            <th class="p-2" scope="col">Debet</th>
            <th class="p-2" scope="col">Kredit</th>
            <th class="p-2" scope="col">Saldo</th>
            <th class="p-2" scope="col">Keterangan</th>
            {{-- <th scope="col">Action</th> --}}
          </tr>
        </thead>
        <tbody>
          @foreach ($cash_balances as $cb)
              <tr>
                  <td class="p-2">{{ $loop->iteration }}</td>
                  <td class="p-2">{{ Carbon::parse($cb->transaction_date)->format('d M Y H:i:s') }}</td>
                  {{-- <td>{{ $cb->cash_type=="CashIn"?"Cash In":"Cash Out" }}</td> --}}
                  <td class="p-2">{{ $cb->cash_type=="CashOut"?"- Rp. ".number_format($cb->amount, 0, ',', '.'):"" }}</td>
                  <td class="p-2">{{ $cb->cash_type=="CashIn"?"Rp. ".number_format($cb->amount, 0, ',', '.'):"" }}</td>
                  <td class="p-2">{{ "Rp. ".number_format($cb->end_balance, 0, ',', '.') }}</td>
                  <td class="p-2">{{ $cb->remark }}</td>
                  {{-- <td>
                    <form action="/dashboard/cash/{{ $cb->id }}" method="post" class="d-inline">
                      @method('delete')
                      @csrf
                      <button class="badge bg-danger border-0 hapus" title="Cancel Cash"><span data-feather="x-circle"></span></button>
                    </form>
                </td> --}}
              </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p class="text-center fs-4">No Cash History Found.</p>
    @endif
</div>
<div class="d-flex justify-content-center">
  {{ $cash_balances->links() }}
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
          $('#transaction_date_from').val(orderDateFrom);
          $('#transaction_date_to').val(orderDateTo);

          // Submit the form
          var form = $('#filterForm');
          // console.log(form);
          form.submit();
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