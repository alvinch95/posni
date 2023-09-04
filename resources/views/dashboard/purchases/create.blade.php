@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h3>Transaksi Pembelian</h3>
</div>

<div class="col-lg-8">
    {{-- enctype supaya formnya bisa submit gambar --}}
    <form method="post" action="/dashboard/purchases" class="mb-5" enctype="multipart/form-data"> 
        @csrf
        <div class="mb-3 @desktop w-25 @elsedesktop w-100 @enddesktop">
          <label for="purchase_date" class="form-label">Tanggal Pembelian</label>
          <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" id="purchase_date" name="purchase_date" autofocus value="{{ old('purchase_date') }}" required>
          @error('purchase_date')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3 @desktop w-25 @elsedesktop w-100 @enddesktop">
          <label for="supplier" class="form-label">Supplier</label>
          <select class="form-select" name="supplier_id" required>
            <option value="" disabled selected hidden>Select supplier</option>
            @foreach ($suppliers as $supplier)
              @if (old('supplier_id') == $supplier->id)
                <option value="{{ $supplier->id }}" selected>{{ $supplier->name }}</option>
              @else
                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
              @endif
            @endforeach
          </select>
        </div>
        <div class="mb-3 @desktop w-25 @elsedesktop w-100 @enddesktop">
          <label for="grand_total" class="form-label">Total</label>
          <input type="number" class="form-control @error('grand_total') is-invalid @enderror" id="grand_total" name="grand_total" autofocus value="{{ old('grand_total') }}" readonly>
          @error('grand_total')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3 @desktop w-50 @elsedesktop w-100 @enddesktop">
          <label for="notes" class="form-label">Notes</label>
          <input type="text" class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" autofocus value="{{ old('notes') }}">
          @error('notes')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <hr class="mt-4 bg-dark">
        <h1 class="display-6">Details :</h1>
        <div class="item_details">
          <div class="row">
            <div class="col-lg-3">
              <label for="item_id" class="form-label">Item</label>
              <select class="form-select" name="item_id[]" required>
                <option value="" disabled selected hidden>Select an item</option>
                @foreach ($items as $item)
                  <option value="{{ $item->id }}" data-unit-price="{{ $item->purchase_price }}">{{ $item->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-lg-3">
              <label for="unit_price" class="form-label">Harga Satuan</label>
              <input type="number" class="unit_price form-control @error('unit_price') is-invalid @enderror" name="unit_price[]" value="{{ old('unit_price') }}">
            </div>
            <div class="col-lg-2">
              <label for="qty" class="form-label">Jumlah</label>
              <input type="number" class="qty form-control @error('qty') is-invalid @enderror" name="qty[]" value="{{ old('qty') }}" step="1" required>
            </div>
            <div class="col-lg-3">
              <label for="total" class="form-label">Total</label>
              <input type="number" class="total form-control @error('total') is-invalid @enderror" name="total[]" value="{{ old('total') }}" readonly>
            </div>
            <div class="col-lg-1">
              <label class="form-label" style="visibility: hidden;">Delete</label>
              <button class="btn btn-danger border-0 remove-row"><span class="bi bi-trash"></span></button>
            </div>
          </div>
        </div>
        <div class="mt-3"><button type="button" class="btn btn-warning add-row">Add row</button></div>
        <hr class="mt-4 mb-3 bg-dark">
        <div class="mb-3 @desktop w-25 @elsedesktop w-100 @enddesktop">
          <label for="additional_fee" class="form-label">Additional Fee</label>
          <input type="number" class="form-control @error('additional_fee') is-invalid @enderror" id="additional_fee" name="additional_fee" autofocus value="{{ old('additional_fee') }}">
          @error('additional_fee')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <button type="submit" class="btn btn-primary submitbtn">Submit</button>
    </form>
</div>


<script>
    $(document).ready(function() {
      $('.submitbtn').click(function(e) {
        e.preventDefault();
        var form = $(this).parents('form');
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

      $(".add-row").on("click", function() {
        var newRow = `
          <div class="row mt-3">
            <div class="col-lg-3">
              <label for="item_id" class="form-label">Item</label>
              <select class="form-select" name="item_id[]" required>
                <option value="" disabled selected hidden>Select an item</option>
                @foreach ($items as $item)
                  <option value="{{ $item->id }}" data-unit-price="{{ $item->purchase_price }}">{{ $item->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-lg-3">
              <label for="unit_price" class="form-label">Harga Satuan</label>
              <input type="number" class="unit_price form-control @error('unit_price') is-invalid @enderror" name="unit_price[]" value="{{ old('unit_price') }}">
            </div>
            <div class="col-lg-2">
              <label for="qty" class="form-label">Jumlah</label>
              <input type="number" class="qty form-control @error('qty') is-invalid @enderror" name="qty[]" value="{{ old('qty') }}" step="1" required>
            </div>
            <div class="col-lg-3">
              <label for="total" class="form-label">Total</label>
              <input type="number" class="total form-control @error('total') is-invalid @enderror" name="total[]" value="{{ old('total') }}" readonly>
            </div>
            <div class="col-lg-1">
              <label class="form-label" style="visibility: hidden;">Delete</label>
              <button class="btn btn-danger border-0 remove-row"><span class="bi bi-trash"></span></button>
            </div>
          </div>
        `;
        $(".item_details").append(newRow);
      });

      $(".item_details").on("click", ".remove-row", function() {
        $(this).closest(".row").remove();
        updateTotal();
      });

      $(".item_details").on("change", "[name='item_id[]']", function() {
        var selectedOption = $(this).find(":selected");
        var unitPrice = selectedOption.data("unit-price");
        $(this).closest(".row").find(".unit_price").val(unitPrice);
        resetItem($(this));
      });

      $(".item_details").on("change", ".qty", function() {
        var qty = $(this).val();
        var unitPrice = $(this).closest(".row").find(".unit_price").val();
        $(this).closest(".row").find(".total").val(unitPrice*qty);
        updateTotal();
      });

      $(".item_details").on("change", ".unit_price", function() {
        var unitPrice = $(this).val();
        var qty = $(this).closest(".row").find(".qty").val();
        $(this).closest(".row").find(".total").val(unitPrice*qty);
        updateTotal();
      });

      $(".item_details").on("change", ".unit_price", function() {
        var unitPrice = $(this).val();
        var qty = $(this).closest(".row").find(".qty").val();
        $(this).closest(".row").find(".total").val(unitPrice*qty);
        updateTotal();
      });

      $("#additional_fee").on("change", function() {
        updateTotal();
      });
    });

    function updateTotal() {
      var sum = 0;
      $('.total').each(function() {
        sum += parseFloat($(this).val()) || 0;
      });

      var additional_fee = parseFloat($("#additional_fee").val()) || 0;
      sum += additional_fee;
      $("#grand_total").val(sum);
    }

    function resetItem(dom){
      dom.closest(".row").find(".qty").val("");
      dom.closest(".row").find(".total").val("");
    }
</script>
@endsection