@extends('dashboard.layouts.main')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;1,100;1,500&family=REM:wght@100&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
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
      <div class="col col-6">Item List</div>
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
        <div class="col col-6" data-label="Item List">
          <button class="show-btn btn btn-dark btn-sm">Show</button>
          @if(!$sm->is_processed)
            <a href="#" class="convert-btn btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#convertModal" data-shopee-reminder-id="{{ $sm->id }}" data-order-date="{{ $sm->processed_date }}" data-remark="{{ $sm->ordersn.' || '.$sm->customer_name }}">Convert to Order</a>
          @endif
        </div>
      </li>
      <div class="child-row" style="display: none;">
        <li class="table-header child-header">
                <div class="col">Item</div>
                <div class="col">Model</div>
                <div class="col">Item Qty</div>
                <div class="col">Price</div>
        </li>
        @foreach (json_decode($sm->item_list, true) as $item)
        <li class="table-row child-table-row">
            <div class="col">{{ $item['item_name'] }}</div>
            <div class="col">{{ $item['model_name'] }}</div>
            <div class="col">{{ $item['model_quantity_purchased'] }}</div>
            <div class="col">{{ "Rp. ".number_format($item['model_discounted_price'], 0, ',', '.') }}</div>
        </li>
        @endforeach
      </div>
    @endforeach
  </ul>
</div>
<div class="modal fade" id="convertModal" tabindex="-1" aria-labelledby="convertModalLabel" aria-hidden="false">
  <div class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title" id="updatePriceModalLabel">Confirmation</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="convertForm" method="POST" action="{{ route('dashboard.shopeereminder.convertOrder') }}">
              @csrf
              <input type="hidden" name="shopee_reminder_id" id="shopee_reminder_id">
              <input type="hidden" name="order_date" id="order_date">
              <input type="hidden" name="remark" id="remark">
              <div class="modal-body">
                <p class="fs-5">This will create the following orders : </p>
                <hr>
                <div class="item_details">
                  <div class="row item-detail-row mb-2">
                    <div class="col-lg-3">
                      <label for="item_id" class="form-label">Hampers</label>
                      <select class="form-select select2" name="hamper_id[]" required>
                        <option value="" disabled selected hidden>Select a hampers</option>
                        @foreach ($hampers as $hamper)
                          <option value="{{ $hamper->id }}" data-unit-price="{{ $hamper->selling_price }}">{{ $hamper->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-lg-3">
                      <label for="unit_price" class="form-label">Harga Jual</label>
                      <input type="number" class="unit_price form-control @error('unit_price') is-invalid @enderror" name="unit_price[]">
                    </div>
                    <div class="col-lg-2">
                      <label for="qty" class="form-label">Jumlah</label>
                      <input type="number" class="qty form-control @error('qty') is-invalid @enderror" name="qty[]" step="1" required>
                    </div>
                    <div class="col-lg-3">
                      <div class="row">
                          <div class="col-12 pe-0">
                              <label for="total" class="form-label">Total</label>
                              <input type="number" class="total form-control @error('total') is-invalid @enderror" name="total[]" readonly>
                          </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mt-3"><button type="button" class="btn btn-warning add-row">Add row</button></div>
                <hr>
                <p class="fs-4 text-center"><b>Please double check, Are you sure ?</b></p>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No</button>
                  <button type="submit" class="btn btn-primary">Yes, Submit !</button>
              </div>
          </form>
      </div>
  </div>
</div>

<style>
  .modal-dialog{ 
    max-width: 800px;
  }
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
      flex-basis: 30%;
    }
  }
</style>

<script>
  //to make search field autofocus when selecting the dropdown
  $(document).on('select2:open', () => {
    document.querySelector('.select2-search__field').focus();
  });

  $(document).ready(function(){
    $(".add-row").on("click", function() {
      addRows();
    });

    $(".show-btn").click(function(){
      var tableRow = $(this).closest('.table-row');
      var childRow = tableRow.next('.child-row');
      childRow.slideToggle("fast");
    });

    $(".convert-btn").click(function(){
      var shopeeReminderID = $(this).data('shopee-reminder-id');
      var orderDate = $(this).data('order-date');
      var remark = $(this).data('remark');
      initializeSelect2($('select[name="hamper_id[]"]'));

      $("#loading-container").show();

      $.ajax({
        type: "POST",
        url: "{{ route('dashboard.shopeereminder.openConvert') }}", // Replace with your actual route URL
        data: {
            "_token": "{{ csrf_token() }}",
            shopeeReminderID: shopeeReminderID
        },
        success: function(response) {
            addRowsFromResponse(response);
            $("#shopee_reminder_id").val(shopeeReminderID);
            $("#order_date").val(orderDate);
            $("#remark").val(remark);
            $("#loading-container").hide();
            Swal.fire({
              icon: 'success',
              title: 'Success',
              text: "Generate form success"
            })
        },
        error: function(xhr, textStatus, errorThrown) {
            $("#loading-container").hide();
            // Extract the error message from the server response
            var errorMessage = xhr.responseText;
            // Display the error message using SweetAlert
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage
            });
        }
      });

      var form = $('#convertForm');
      // $("#loading-container").show();
      // form.submit();
    });

    $(".item_details").on("change", "[name='hamper_id[]']", function() {
      var selectedOption = $(this).find(":selected");
      var unitPrice = selectedOption.data("unit-price");
      $(this).closest(".row").find(".unit_price").val(unitPrice);
    });

    $(".item_details").on("change", ".qty", function() {
      var qty = $(this).val();
      var unitPrice = $(this).closest(".row").find(".unit_price").val();
      $(this).closest(".row").find(".total").val(unitPrice*qty);
    });

    $(".item_details").on("change", ".unit_price", function() {
      var unitPrice = $(this).val();
      var qty = $(this).closest(".row").find(".qty").val();
      $(this).closest(".row").find(".total").val(unitPrice*qty);
    });
    
    function addRowsFromResponse(response) {
        if (response && response.length > 0) {
            // Remove all existing rows
            $('.item_details .row').remove();

            response.forEach(function(item) {
              addRows(); // Add a new empty row
              // Set values for the new row
              var newSelect = $('.item_details select[name="hamper_id[]"]').last();
              newSelect.val(item.id);
              newSelect.trigger('change');
              var newPrice = $('.item_details .unit_price').last();
              newPrice.val(item.price);
              var newQty = $('.item_details .qty').last();
              newQty.val(item.qty);
              newQty.trigger('change');

              if (item.id == 0) {
                // Change row color to light red
                var newRow = newSelect.closest('.row');
                newRow.css('background-color', '#ffcccc'); // Light red color
            }
            });
        }
    }
    
    function addRows(){
      var newRow = `
      <div class="row item-detail-row mb-2">
        <div class="col-lg-3">
          <label for="item_id" class="form-label">Hampers</label>
          <select class="form-select select2" name="hamper_id[]" required>
            <option value="" disabled selected hidden>Select a hampers</option>
            @foreach ($hampers as $hamper)
              <option value="{{ $hamper->id }}" data-unit-price="{{ $hamper->selling_price }}">{{ $hamper->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-3">
          <label for="unit_price" class="form-label">Harga Jual</label>
          <input type="number" class="unit_price form-control @error('unit_price') is-invalid @enderror" name="unit_price[]">
        </div>
        <div class="col-lg-2">
          <label for="qty" class="form-label">Jumlah</label>
          <input type="number" class="qty form-control @error('qty') is-invalid @enderror" name="qty[]" step="1" required>
        </div>
        <div class="col-lg-3">
          <div class="row">
              <div class="col-12 pe-0">
                  <label for="total" class="form-label">Total</label>
                  <input type="number" class="total form-control @error('total') is-invalid @enderror" name="total[]" readonly>
              </div>
          </div>
        </div>
      </div>
      `;
      $(".item_details").append(newRow);

      // Initialize Select2 for the newly added item
      initializeSelect2($('select[name="hamper_id[]"]').last());
    }

    function initializeSelect2(element) {
        element.select2({
            theme: "bootstrap-5",
            dropdownParent: $(element).closest('.modal')
        });
    }
  });
</script>
@endsection