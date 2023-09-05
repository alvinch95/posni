@extends('dashboard.layouts.main')

@section('head')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Stock Opname</h1>
</div>

<div class="col-lg-8">
    {{-- enctype supaya formnya bisa submit gambar --}}
    <form method="post" action="/dashboard/stockopname/submit" class="mb-5" id="stockOpnameForm"> 
        @csrf
        <div class="mb-3">
          <label for="item_id" class="form-label">Choose item</label>
          <select class="select2" name="item_id" id="item_select" required style="width:100%;">
            <option value="" disabled selected hidden>Select item</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" data-stock="{{ $item->stock }}">{{ $item->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label for="initial_stock" class="form-label">Stock System</label>
          <input type="number" class="form-control @error('stock') is-invalid @enderror" id="initial_stock" name="initial_stock" readonly>
        </div>
        <div class="mb-3">
          <label for="end_stock" class="form-label">Real Stock</label>
          <input type="number" class="form-control @error('stock') is-invalid @enderror" id="end_stock" name="end_stock" step="1" onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
        </div>
        <div class="mb-3">
          <label for="difference_stock" class="form-label">Differences</label>
          <input type="number" class="form-control @error('stock') is-invalid @enderror" id="difference_stock" name="difference_stock" readonly>
        </div>
        <div class="mb-3">
          <label for="notes" class="form-label">Remark</label>
          <input type="text" name="remark" id="remark" class="form-control">
        </div>
        
        <button type="submit" class="btn btn-primary" id="submitBtn">Submit Stock Opname</button>
    </form>
</div>

<script>
  $(document).on('select2:open', () => {
    document.querySelector('.select2-search__field').focus();
  });
  
  $(document).ready(function() {
      // Initialize Select2
      $('select[name="item_id"]').select2({
          // Optional: You can customize Select2 options here
          theme:"bootstrap-5"
      });

      $('#item_select').on('change', function () {
          var selectedOption = $(this).find(":selected");
          var initialStock = parseFloat(selectedOption.data('stock'));
          var endStock = parseFloat($('#end_stock').val());
          var differenceStock = endStock - initialStock;

          $('#initial_stock').val(initialStock);
          $('#difference_stock').val(differenceStock);
      });

      $('#end_stock').on('change', function () {
          var selectedOption = $('#item_select').find(":selected");
          var initialStock = parseFloat(selectedOption.data('stock'));
          var endStock = parseFloat($('#end_stock').val());
          var differenceStock = endStock - initialStock;

          $('#initial_stock').val(initialStock);
          $('#difference_stock').val(differenceStock);
      });

      $('#submitBtn').on('click', function (e) {
        e.preventDefault();
        var form = $('#stockOpnameForm');
        Swal.fire({
            title: 'Are you sure ?',
            text: "This will update your stock",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, confirm!'
        }).then((result) => {
            if (result.value) {
                form.submit();
            }
        });
      });
  });
</script>

@endsection