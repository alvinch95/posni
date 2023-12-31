@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h3>{{ $hampers->name }}</h3>
</div>

<div class="col-lg-8">
    {{-- enctype supaya formnya bisa submit gambar --}}
    <form method="post" action="/dashboard/hampers/{{ $hampers->id }}" class="mb-5" enctype="multipart/form-data">
        @method('patch')
        @csrf
        <div class="mb-3 @desktop w-25 @elsedesktop w-100 @enddesktop">
          <label for="name" class="form-label">Nama Hampers</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" autofocus value="{{ $hampers->name }}" required>
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
              @if ($hampers->serie_id == $serie->id)
                <option value="{{ $serie->id }}" selected>{{ $serie->name }}</option>
              @else
                <option value="{{ $serie->id }}">{{ $serie->name }}</option>
              @endif
            @endforeach
          </select>
        </div>
        <div class="row">
          <div class="col-lg-3">
            <label for="capital_price" class="form-label">Harga Modal</label>
            <input type="number" class="form-control @error('capital_price') is-invalid @enderror" id="capital_price" name="capital_price" value="{{ $hampers->capital_price }}" readonly>
            @error('capital_price')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
          </div>
          <div class="col-lg-2">
            <label for="revenue" class="form-label">Keuntungan</label>
            <div class="input-group mb-3">
              <input type="number" class="form-control" id="revenue_percentage" name="revenue_percentage" aria-label="Keuntungan" aria-describedby="basic-addon2" step="0.01" value="{{ $hampers->revenue_percentage }}">
              <div class="input-group-append">
                <span class="input-group-text" id="basic-addon2">%</span>
              </div>
            </div>
          </div>
          <div class="col-lg-3">
            <label for="selling_price" class="form-label">Harga Jual</label>
            <input type="number" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" value="{{ $hampers->selling_price }}">
            @error('selling_price')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
          </div>
        </div>
        <div class="mb-3">
          <p class="lead">Keuntungan = Rp. <span id="revenue_amount">{{ number_format(($hampers->selling_price - $hampers->capital_price), 0, ',', '.') }}</span></p>
        </div>
        <div class="mb-3">
          <label for="image" class="form-label">Hampers Image</label>
          <input type="hidden" name="oldImage" value="{{ $hampers->image }}">
            @if ($hampers->image)
              <img src="{{ asset('storage/' . $hampers->image) }}" alt="{{ $hampers->name }}" class="img-fluid img-preview mb-3 d-block" style="max-width: 400px; max-height: 400px;">
            @else
              <img class="img-preview img-fluid mb-3 col-sm-5">
            @endif
            <input class="form-control @error('image') is-invalid @enderror" type="file" id="image" name="image" onchange="previewImage()">
            @error('image')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
        </div>
        <hr class="mt-4 bg-dark">
        <h1 class="display-6">Details :</h1>
        <div class="item_details">
        @foreach ($hamper_details as $details)
          <div class="row">
            <div class="col-lg-3">
              <label for="item_id" class="form-label">Item</label>
              <select class="form-select" name="item_id[]" required>
                <option value="" disabled selected hidden>Select an item</option>
                @foreach ($items as $item)
                  <option value="{{ $item->id }}" data-unit-price="{{ $item->purchase_price }}" @if ($item->id === $details->item_id)
                      selected
                  @endif >{{ $item->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-lg-3">
              <label for="unit_price" class="form-label">Harga Satuan</label>
              <input type="number" class="unit_price form-control @error('unit_price') is-invalid @enderror" name="unit_price[]" value="{{ old('unit_price',$details->unit_price) }}" readonly>
            </div>
            <div class="col-lg-2">
              <label for="qty" class="form-label">Jumlah</label>
              <input type="number" class="qty form-control @error('qty') is-invalid @enderror" name="qty[]" value="{{ old('qty',$details->qty) }}" step="1" required>
            </div>
            <div class="col-lg-3">
              <label for="total" class="form-label">Total</label>
              <input type="number" class="total form-control @error('total') is-invalid @enderror" name="total[]" value="{{ old('total',$details->total) }}" readonly>
            </div>
            <div class="col-lg-1">
              <label class="form-label" style="visibility: hidden;">Delete</label>
              <button class="btn btn-danger border-0 remove-row"><span class="bi bi-trash"></span></button>
            </div>
          </div>
        @endforeach
        </div>
        <div class="mt-3"><button type="button" class="btn btn-warning add-row">Add row</button></div>
        <hr class="my-4 bg-dark">
        <button type="submit" class="btn btn-primary">Update Hampers</button>
    </form>
</div>


<script>
  $(document).ready(function() {
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
            <input type="number" class="unit_price form-control @error('unit_price') is-invalid @enderror" name="unit_price[]" value="{{ old('unit_price') }}" readonly>
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