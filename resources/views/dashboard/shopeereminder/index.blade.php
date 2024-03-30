@extends('dashboard.layouts.main')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;1,100;1,500&family=REM:wght@100&display=swap" rel="stylesheet">
@endsection

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Shopee Reminders</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="row mb-3">
  <div class="col-md-8">
      <form id="filterForm" action="/dashboard/shopeereminder">
          <div class="row mb-3 align-items-center">
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="text" class="form-control" placeholder="Search items.." name="search" value="{{ request('search') }}">
                <label for="search" class="form-label text-muted">Search keyword</label>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="date" class="form-control" id="processed_date_from" name="processed_date_from" value="{{ request('processed_date_from') }}">
                <label for="processed_date_from" class="form-label">Start Date</label>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="date" class="form-control" id="processed_date_to" name="processed_date_to" value="{{ request('processed_date_to') }}">
                <label for="processed_date_to" class="form-label">End Date</label>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-floating mb-1">
                <select class="form-select" id="is_processed" name="is_processed">
                  <option value="">-</option>
                  <option value="true" {{ request('is_processed') == 'true' ? 'selected' : '' }}>Sudah</option>
                  <option value="false" {{ request('is_processed') == 'false' ? 'selected' : '' }}>Belum</option>
                </select>
                <label for="is_processed" class="form-label">Status</label>
              </div>
            </div>
            <div class="col-lg-1">
              <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
            </div>
          </div>
      </form>
  </div>
</div>

<div class="container">
  <ul class="responsive-table">
    <li class="table-header">
      <div class="col col-1">Date</div>
      <div class="col col-2">OrderSN</div>
      <div class="col col-3">Customer</div>
      <div class="col col-4">Total</div>
      <div class="col col-5">Status</div>
      <div class="col col-6">Remark</div>
      <div class="col col-7">Item List</div>
    </li>
    @foreach ($shopee_reminders as $sm)
      <li class="table-row">
        <div class="col col-1" data-label="Date">{{ date('d M y', strtotime($sm->processed_date)) }}</div>
        <div class="col col-2" data-label="OrderSN">{{ $sm->ordersn }}</div>
        <div class="col col-3" data-label="Customer">{{ $sm->customer_name }}</div>
        <div class="col col-4" data-label="Total">{{ "Rp. ".number_format($sm->total_amount, 0, ',', '.') }}</div>
        <div class="col col-5" data-label="Status">
          @if($sm->is_processed)
              <span class="status status-green"></span><span class="status-label">SUDAH</span>
          @else
              <span class="status status-red"></span><span class="status-label">BELUM</span>
          @endif
        </div>
        <div class="col col-6" data-label="Remark">{{ $sm->remarks }}</div>
        <div class="col col-7" data-label="Item List">
          <button class="show-btn">Show</button>
        </div>
      </li>
      <div class="child-row" style="display: none;">
        <li class="table-header child-header">
                <div class="col">Item Name</div>
                <div class="col">Item Qty</div>
                <div class="col">Ori Price</div>
                <div class="col">Disc Price</div>
        </li>
        @foreach (json_decode($sm->item_list, true) as $item)
        <li class="table-row child-table-row">
            <div class="col">{{ $item['item_name'] }}</div>
            <div class="col">{{ $item['model_quantity_purchased'] }}</div>
            <div class="col">{{ $item['model_original_price'] }}</div>
            <div class="col">{{ $item['model_discounted_price'] }}</div>
        </li>
        @endforeach
      </div>
    @endforeach
  </ul>
</div>

<style>
  .child-row{
    margin-left: 30px;
  }
  .child-header{
    background-color: lightblue !important;
  }
  .child-table-row{
    background-color: whitesmoke !important;
  }
  .status {
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-right: 5px;
  }

  .status-green {
      background-color: #4CAF50; /* Green */
  }

  .status-red {
      background-color: #F44336; /* Red */
  }

  .status-label {
      vertical-align: middle;
  }
  .page-size-select {
      padding: 4px 8px; /* Adjust padding as needed */
      font-size: 12px; /* Adjust font size as needed */
      width: 70px; /* Adjust width as needed */
  }
  body {
    font-family: 'lato', sans-serif;
  }
  .container {
    max-width: 100%;
    margin-left: 0px;
    padding-left: 0px;
  }

  .responsive-table {
    li {
      border-radius: 5px;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .table-header {
      background-color: #966F33;
      color: white;
      font-weight: bold;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .table-row {
      background-color: white;
      box-shadow: 0px 0px 9px 0px rgba(0,0,0,0.1);
    }
    .col{
      padding : 0px 10px;
    }
    .col-1 {
      flex-basis: 10%;
    }
    .col-2 {
      flex-basis: 15%;
    }
    .col-3 {
      flex-basis: 15%;
    }
    .col-4 {
      flex-basis: 10%;
    }
    .col-5 {
      flex-basis: 10%;
    }
    .col-6 {
      flex-basis: 10%;
    }
    .col-7 {
      flex-basis: 30%;
    }
    
    @media all and (max-width: 767px) {
      .table-header {
        display: none;
      }
      .table-row{
        
      }
      li {
        display: block;
      }
      .col {
        flex-basis: 100%;
      }
      .col {
        display: flex;
        padding: 10px 0;
        &:before {
          color: #6C7A89;
          padding-right: 10px;
          content: attr(data-label);
          flex-basis: 50%;
          text-align: right;
        }
      }
    }
  }
</style>

  <script>
    $(document).ready(function(){
      $(".show-btn").click(function(){
        // Find the parent table-row element
        var tableRow = $(this).closest('.table-row');
        // Find the sibling child-row element
        var childRow = tableRow.next('.child-row');
        // Toggle the display of the child-row
        childRow.toggle();
      });
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