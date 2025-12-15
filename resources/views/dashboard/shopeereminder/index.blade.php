@extends('dashboard.layouts.main')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold">Shopee Reminders</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<!-- Stats & Filter Row -->
<div class="row g-4 mb-4">
    <!-- Filter Card -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <form id="filterForm" action="/dashboard/shopeereminder" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Search</label>
                        <input type="text" class="form-control" placeholder="Keyword..." name="search" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Start Date</label>
                        <input type="date" class="form-control" name="processed_date_from" value="{{ request('processed_date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">End Date</label>
                        <input type="date" class="form-control" name="processed_date_to" value="{{ request('processed_date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Status</label>
                        <select class="form-select" name="is_processed">
                            <option value="">All</option>
                            <option value="true" {{ request('is_processed') == 'true' ? 'selected' : '' }}>Sudah</option>
                            <option value="false" {{ request('is_processed') == 'false' ? 'selected' : '' }}>Belum</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Metrics Card -->
    <div class="col-lg-4">
        <div class="card bg-metric-blue border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-uppercase opacity-75 fw-bold mb-2">Summary</h6>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span>Jumlah Order</span>
                    <span class="fw-bold fs-5">{{ $totalOrder }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Total Gross</span>
                    <span class="fw-bold fs-5">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data List -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-lg-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 rounded-start ps-4">Date</th>
                        <th class="border-0">Order SN</th>
                        <th class="border-0">Customer</th>
                        <th class="border-0">Total</th>
                        <th class="border-0">Status</th>
                        <th class="border-0 rounded-end text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shopee_reminders as $index => $sm)
                    <tr>
                        <td class="ps-4 text-nowrap">{{ date('d M y', strtotime($sm->processed_date)) }}</td>
                        <td class="font-monospace small">{{ $sm->ordersn }}</td>
                        <td class="fw-medium">{{ $sm->customer_name }}</td>
                        <td class="fw-bold">Rp {{ number_format($sm->total_amount, 0, ',', '.') }}</td>
                        <td>
                            @if($sm->is_processed)
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Completed</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Pending</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDesktop{{ $index }}">
                                <span data-feather="eye"></span> Details
                            </button>
                            @if(!$sm->is_processed)
                                <button class="btn btn-sm btn-primary convert-btn ms-1" data-bs-toggle="modal" data-bs-target="#convertModal" 
                                    data-shopee-reminder-id="{{ $sm->id }}" 
                                    data-order-date="{{ $sm->processed_date }}" 
                                    data-remark="{{ $sm->ordersn.' || '.$sm->customer_name }}">
                                    Convert
                                </button>
                            @endif
                        </td>
                    </tr>
                    <!-- Desktop Collapse Row -->
                    <tr>
                        <td colspan="6" class="p-0 border-0">
                            <div class="collapse bg-light" id="collapseDesktop{{ $index }}">
                                <div class="p-4">
                                    <h6 class="fw-bold mb-3">Item Details</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered bg-white mb-0">
                                            <thead class="bg-secondary text-white">
                                                <tr>
                                                    <th>Item Name</th>
                                                    <th>Model</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (json_decode($sm->item_list, true) as $item)
                                                <tr>
                                                    <td class="text-wrap" style="min-width: 250px;">{{ $item['item_name'] }}</td>
                                                    <td>{{ $item['model_name'] }}</td>
                                                    <td class="text-center">{{ $item['model_quantity_purchased'] }}</td>
                                                    <td class="text-end">Rp {{ number_format($item['model_discounted_price'], 0, ',', '.') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="d-lg-none">
            @foreach ($shopee_reminders as $index => $sm)
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <small class="text-muted d-block">{{ date('d M Y', strtotime($sm->processed_date)) }}</small>
                        <span class="fw-bold d-block">{{ $sm->ordersn }}</span>
                    </div>
                    @if($sm->is_processed)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Completed</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Pending</span>
                    @endif
                </div>
                
                <div class="mb-2">
                    <small class="text-muted">Customer:</small>
                    <div class="fw-medium">{{ $sm->customer_name }}</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold fs-5">Rp {{ number_format($sm->total_amount, 0, ',', '.') }}</span>
                    <div>
                        @if(!$sm->is_processed)
                            <button class="btn btn-sm btn-primary convert-btn me-1" data-bs-toggle="modal" data-bs-target="#convertModal" 
                                data-shopee-reminder-id="{{ $sm->id }}" 
                                data-order-date="{{ $sm->processed_date }}" 
                                data-remark="{{ $sm->ordersn.' || '.$sm->customer_name }}">
                                Convert
                            </button>
                        @endif
                        <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMobile{{ $index }}">
                            <span data-feather="chevron-down"></span>
                        </button>
                    </div>
                </div>

                <!-- Mobile Collapse Details -->
                <div class="collapse" id="collapseMobile{{ $index }}">
                    <div class="bg-light p-3 rounded">
                        <h6 class="small fw-bold text-uppercase text-muted mb-2">Items</h6>
                        @foreach (json_decode($sm->item_list, true) as $item)
                        <div class="card border-0 shadow-none mb-2">
                            <div class="card-body p-2">
                                <div class="fw-medium mb-1 text-wrap">{{ $item['item_name'] }}</div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>{{ $item['model_name'] }}</span>
                                    <span>x{{ $item['model_quantity_purchased'] }}</span>
                                </div>
                                <div class="text-end fw-bold small mt-1">Rp {{ number_format($item['model_discounted_price'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>

<!-- Convert Modal (Kept functionality intact, styled) -->
<div class="modal fade" id="convertModal" tabindex="-1" aria-labelledby="convertModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow">
          <div class="modal-header">
              <h5 class="modal-title fw-bold" id="updatePriceModalLabel">Convert Order</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="convertForm" method="POST" action="{{ route('dashboard.shopeereminder.convertOrder') }}">
              @csrf
              <div class="modal-body">
                  <input type="hidden" name="shopee_reminder_id" id="shopee_reminder_id">
                  <input type="hidden" name="order_date" id="order_date">
                  <input type="hidden" name="remark" id="remark">
                  
                  <div class="alert alert-info border-0 bg-metric-blue text-dark mb-4">
                      <i class="bi bi-info-circle me-2"></i> This will create new orders in the system.
                  </div>

                  <div class="item_details">
                      <!-- Rows added via JS -->
                  </div>
                  
                  <div class="d-flex justify-content-between align-items-center mt-3">
                      <button type="button" class="btn btn-outline-primary add-row"><i class="bi bi-plus"></i> Add Item Row</button>
                      <div class="text-muted small">Please double check amounts</div>
                  </div>
              </div>
              <div class="modal-footer bg-light">
                  <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary px-4">Submit Order</button>
              </div>
          </form>
      </div>
  </div>
</div>

<script>
  // Select2 Autofocus Fix
  $(document).on('select2:open', () => {
    document.querySelector('.select2-search__field').focus();
  });

  $(document).ready(function(){
    // Initial row
    addRows();

    $(".add-row").on("click", function() {
      addRows();
    });

    // Handle Convert Button Click
    $(".convert-btn").click(function(){
      var shopeeReminderID = $(this).data('shopee-reminder-id');
      var orderDate = $(this).data('order-date');
      var remark = $(this).data('remark');
      
      // Reset Modal
      $('.item_details .row').remove();
      
      $("#loading-container").show();

      $.ajax({
        type: "POST",
        url: "{{ route('dashboard.shopeereminder.openConvert') }}",
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
        },
        error: function(xhr) {
            $("#loading-container").hide();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: xhr.responseText
            });
        }
      });
    });

    // Dynamic Form Calculations
    $(".item_details").on("change", "[name='hamper_id[]']", function() {
      var selectedOption = $(this).find(":selected");
      var unitPrice = selectedOption.data("unit-price");
      $(this).closest(".row").find(".unit_price").val(unitPrice);
      calculateRowTotal($(this).closest(".row"));
    });

    $(".item_details").on("change keyup", ".qty, .unit_price", function() {
      calculateRowTotal($(this).closest(".row"));
    });
    
    function calculateRowTotal(row) {
        var qty = row.find(".qty").val() || 0;
        var price = row.find(".unit_price").val() || 0;
        row.find(".total").val(qty * price);
    }

    function addRowsFromResponse(response) {
        if (response && response.length > 0) {
            response.forEach(function(item) {
              addRows(); 
              var newRow = $('.item_details .row').last();
              var newSelect = newRow.find('select[name="hamper_id[]"]');
              
              newSelect.val(item.id).trigger('change');
              newRow.find('.unit_price').val(item.price);
              newRow.find('.qty').val(item.qty).trigger('change');

              if (item.id == 0) {
                newRow.addClass('bg-warning-subtle p-2 rounded');
              }
            });
        } else {
            addRows(); // Fallback empty row
        }
    }
    
    function addRows(){
      var newRow = `
      <div class="row item-detail-row g-2 mb-3 align-items-end">
        <div class="col-lg-4">
          <label class="form-label small text-muted">Hampers</label>
          <select class="form-select select2" name="hamper_id[]" required>
            <option value="" disabled selected hidden>Select Hamper</option>
            @foreach ($hampers as $hamper)
              <option value="{{ $hamper->id }}" data-unit-price="{{ $hamper->selling_price }}">{{ $hamper->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-3">
          <label class="form-label small text-muted">Price</label>
          <input type="number" class="unit_price form-control" name="unit_price[]">
        </div>
        <div class="col-lg-2">
          <label class="form-label small text-muted">Qty</label>
          <input type="number" class="qty form-control" name="qty[]" step="1" required>
        </div>
        <div class="col-lg-3">
          <label class="form-label small text-muted">Total</label>
          <input type="number" class="total form-control bg-light" name="total[]" readonly>
        </div>
      </div>
      `;
      $(".item_details").append(newRow);
      initializeSelect2($('select[name="hamper_id[]"]').last());
    }

    function initializeSelect2(element) {
        element.select2({
            theme: "bootstrap-5",
            dropdownParent: $(element).closest('.modal'),
            width: '100%'
        });
    }
  });
</script>
@endsection