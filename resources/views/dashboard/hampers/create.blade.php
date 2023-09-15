@extends('dashboard.layouts.main')

@section('head')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h3>Add New Hampers</h3>
</div>

<div class="col-lg-8">
    {{-- enctype supaya formnya bisa submit gambar --}}
    <form method="post" action="/dashboard/hampers" class="mb-5" enctype="multipart/form-data"> 
        @csrf
        <div class="mb-3 @desktop w-25 @elsedesktop w-100 @enddesktop">
          <label for="name" class="form-label">Nama Hampers</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" autofocus value="{{ old('name') }}" required>
          @error('name')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3 @desktop w-25 @elsedesktop w-100 @enddesktop">
          <label for="serie" class="form-label">Series</label>
          <select class="form-select" name="serie_id" required>
            <option value="" disabled selected hidden>Select a serie</option>
            @foreach ($series as $serie)
              @if (old('serie_id') == $serie->id)
                <option value="{{ $serie->id }}" selected>{{ $serie->name }}</option>
              @else
                <option value="{{ $serie->id }}">{{ $serie->name }}</option>
              @endif
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label for="image" class="form-label">Hampers Image</label>
          <img class="img-preview img-fluid mb-3 col-sm-5">
          <input class="form-control @error('image') is-invalid @enderror" type="file" id="image" name="image" onchange="previewImage()">
          @error('image')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <hr class="mt-4 bg-dark">
        <h1 class="display-6">Details :</h1>
        <!-- Add a dropdown for selecting an existing hamper inside each row of the dynamic details section -->
        <div class="row align-items-end mb-3">
          <div class="col-lg-6 col-md-12">
            <label for="copy_hamper" class="form-label">Copy from other hampers</label>
            <select class="form-select select2" name="copy_hamper">
                <option value="" disabled selected hidden>Select an existing hamper</option>
                @foreach ($hampers as $hamper)
                    <option value="{{ $hamper->id }}">{{ $hamper->name }}</option>
                @endforeach
            </select>
          </div>
          <div class="col-lg-1 col-md-12 @mobile mt-2 @endmobile">
            <button class="btn btn-dark" id="copyButton">Copy</button>
          </div>
        </div>
        <div class="item_details">
          <div class="row">
            <div class="col-lg-3">
              <label for="item_id" class="form-label">Item</label>
              <select class="form-select select2" name="item_id[]" required>
                <option value="" disabled selected hidden>Select an item</option>
                @foreach ($items as $item)
                  <option value="{{ $item->id }}" data-unit-price="{{ $item->purchase_price }}">{{ $item->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-lg-3">
              <label for="unit_price" class="form-label">Harga Satuan</label>
              <input type="number" class="unit_price form-control @error('unit_price') is-invalid @enderror" name="unit_price[]" value="{{ old('unit_price') }}" readonly>
            </div>
            <div class="col-lg-2">
              <label for="qty" class="form-label">Jumlah</label>
              <input type="number" class="qty form-control @error('qty') is-invalid @enderror" name="qty[]" value="{{ old('qty') }}" step="1" required>
            </div>
            <div class="col-lg-4">
              <div class="row">
                  <div class="col-10">
                      <label for="total" class="form-label">Total</label>
                      <input type="number" class="total form-control @error('total') is-invalid @enderror" name="total[]" value="{{ old('total') }}" readonly>
                  </div>
                  <div class="col-2">
                      <label class="form-label" style="visibility: hidden;">Delete</label>
                      <button class="btn btn-danger border-0 remove-row"><span class="bi bi-trash"></span></button>
                  </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-3"><button type="button" class="btn btn-warning add-row">Add row</button></div>
        <hr class="my-4 bg-dark">
        <div class="row">
          <div class="col-lg-3">
            <label for="capital_price" class="form-label">Harga Modal</label>
            <input type="number" class="form-control @error('capital_price') is-invalid @enderror" id="capital_price" name="capital_price" value="{{ old('capital_price',0) }}" readonly>
            @error('capital_price')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
          </div>
          <div class="col-lg-2">
            <label for="revenue" class="form-label">Keuntungan</label>
            <div class="input-group mb-3">
              <input type="number" class="form-control" id="revenue_percentage" name="revenue_percentage" aria-label="Keuntungan" aria-describedby="basic-addon2" step="0.01">
              <div class="input-group-append">
                <span class="input-group-text" id="basic-addon2">%</span>
              </div>
            </div>
          </div>
          <div class="col-lg-3">
            <label for="selling_price" class="form-label">Harga Jual</label>
            <input type="number" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" value="{{ old('selling_price') }}">
            @error('selling_price')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
          </div>
        </div>
        <div class="mb-3">
          <p class="lead">Keuntungan = Rp. <span id="revenue_amount">0</span></p>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>


<script>
    //to make search field autofocus when selecting the dropdown
    $(document).on('select2:open', () => {
      document.querySelector('.select2-search__field').focus();
    });

    $(document).ready(function() {
      // Initialize Select2 for the default item
      initializeSelect2($('select[name="item_id[]"]'));
      initializeSelect2($('select[name="copy_hamper"]'));

      $("#copyButton").click(function(e) {
          e.preventDefault();
          // Handle the copy operation here
          var selectedHamperId = $("select[name='copy_hamper']").val();
          
          if(!selectedHamperId)
          {
            Swal.fire({
                    icon: 'warning',
                    title: '',
                    text: "Choose hampers first"
                });
            return;
          }
          Swal.fire({
              title: 'Area you sure ?',
              text: "This will reset all your hampers details and replace with copied hampers.",
              icon: 'question',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes!'
          }).then((result) => {
              if (result.value) {
                $("#loading-container").show();
                $.ajax({
                  type: "POST",
                  url: "{{ route('dashboard.hampers.copy') }}", // Replace with your actual route URL
                  data: {
                      "_token": "{{ csrf_token() }}",
                      hamperID: selectedHamperId
                  },
                  success: function(response) {
                      addRowsFromResponse(response);
                      $('select[name="copy_hamper"]').val('').trigger('change.select2');
                      $("#loading-container").hide();
                      Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: "Success copy hampers"
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
              }
          });
      });

      $(".add-row").on("click", function() {
        addRows();
      });

      $(".item_details").on("click", ".remove-row", function() {
        $(this).closest(".row").remove();
        updateHargaModal();
        updateRevenue();
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
        updateHargaModal();
        updateRevenue();
      });

      $("#revenue_percentage").on("change", function() {
        updateRevenue();
      });

      $("#selling_price").on("change", function() {
        updateKeuntunganOnly();
        updateRevenuePercentage();
      });
    });

    function addRows(){
      var newRow = `
        <div class="row mt-3">
          <div class="col-lg-3">
            <label for="item_id" class="form-label">Item</label>
            <select class="form-select select2" name="item_id[]" required>
              <option value="" disabled selected hidden>Select an item</option>
              @foreach ($items as $item)
                <option value="{{ $item->id }}" data-unit-price="{{ $item->purchase_price }}">{{ $item->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-3">
            <label for="unit_price" class="form-label">Harga Satuan</label>
            <input type="number" class="unit_price form-control @error('unit_price') is-invalid @enderror" name="unit_price[]" value="{{ old('unit_price') }}" readonly>
          </div>
          <div class="col-lg-2">
            <label for="qty" class="form-label">Jumlah</label>
            <input type="number" class="qty form-control @error('qty') is-invalid @enderror" name="qty[]" value="{{ old('qty') }}" step="1" required>
          </div>
          <div class="col-lg-4">
            <div class="row">
                <div class="col-10">
                    <label for="total" class="form-label">Total</label>
                    <input type="number" class="total form-control @error('total') is-invalid @enderror" name="total[]" value="{{ old('total') }}" readonly>
                </div>
                <div class="col-2">
                    <label class="form-label" style="visibility: hidden;">Delete</label>
                    <button class="btn btn-danger border-0 remove-row"><span class="bi bi-trash"></span></button>
                </div>
            </div>
          </div>
        </div>
      `;
      $(".item_details").append(newRow);

      // Initialize Select2 for the newly added item
      initializeSelect2($('select[name="item_id[]"]').last());
    }

    function addRowsFromResponse(response) {
        if (response && response.length > 0) {
            // Remove all existing rows
            $('.item_details .row').remove();

            response.forEach(function(item) {
              addRows(); // Add a new empty row
              // Set values for the new row
              var newSelect = $('.item_details select[name="item_id[]"]').last();
              newSelect.val(item.item_id);
              newSelect.trigger('change'); // Trigger change event to populate other fields
              var newQty = $('.item_details .qty').last();
              newQty.val(item.qty);
              newQty.trigger('change');
            });
        }
    }

    // Function to initialize Select2
    function initializeSelect2(element) {
        element.select2({
            theme: "bootstrap-5"
        });
    }

    function updateKeuntunganOnly()
    {
      var hargaModal = parseFloat($("#capital_price").val());
      var hargaJual = parseFloat($("#selling_price").val());
      var revenue = hargaJual - hargaModal;
      var formattedRevenue = revenue.toLocaleString('id-ID', {
        style: 'currency',
        currency: 'IDR'
      });
      $("#revenue_amount").text(formattedRevenue);
    }

    function updateRevenuePercentage()
    {
      var hargaModal = parseFloat($("#capital_price").val());
      var hargaJual = parseFloat($("#selling_price").val());
      var revenue = parseFloat(hargaJual - hargaModal);
      var revPercent = ((revenue/hargaModal)*100).toFixed(2);
      $("#revenue_percentage").val(revPercent);
    }

    function updateRevenue(){
      var hargaModal = parseFloat($("#capital_price").val());
      var cuan = parseFloat($("#revenue_percentage").val());
      var hargaJual = hargaModal + (hargaModal * (cuan/100));
      var revenue = hargaJual - hargaModal;
      var formattedRevenue = revenue.toLocaleString('id-ID', {
        style: 'currency',
        currency: 'IDR'
      });
      $("#selling_price").val(hargaJual);
      $("#revenue_amount").text(formattedRevenue);
    }

    function updateHargaModal() {
      var sum = 0;
      $('.total').each(function() {
        sum += parseFloat($(this).val()) || 0;
      });
      $("#capital_price").val(sum);
    }

    function resetItem(dom){
      dom.closest(".row").find(".qty").val("");
      dom.closest(".row").find(".total").val("");
    }

    function previewImage(){
      const image = document.querySelector('#image');
      const imgPreview = document.querySelector('.img-preview');

      imgPreview.style.display = 'block';

      const oFReader = new FileReader();
      oFReader.readAsDataURL(image.files[0]);

      oFReader.onload = function(oFREvent){
        imgPreview.src = oFREvent.target.result;
      }
    }
</script>
@endsection