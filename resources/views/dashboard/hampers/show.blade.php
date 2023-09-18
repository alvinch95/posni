@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h3>{{ $hampers->name }}</h3>
</div>

<div class="col-lg-8">
    {{-- enctype supaya formnya bisa submit gambar --}}
    <form method="post" action="/dashboard/hampers" class="mb-5" enctype="multipart/form-data"> 
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
          <input type="text" class="form-control @error('name') is-invalid @enderror" id="serie_id" name="serie_id" autofocus value="{{ $hampers->serie->name }}" required>
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
          <div class="col-lg-3">
            <label for="revenue" class="form-label">Keuntungan</label>
            <div class="input-group mb-3">
              <input type="number" class="form-control" id="revenue_percentage" aria-label="Keuntungan" aria-describedby="basic-addon2" value="{{ $hampers->revenue_percentage }}">
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
            @if ($hampers->image)
                <div>
                    <img src="{{ asset('storage/' . $hampers->image) }}" alt="{{ $hampers->name }}" class="img-fluid" style="max-width: 400px; max-height: 400px;">
                </div>
            @else
                <p>No image</p>
            @endif
        </div>
        <hr class="mt-4 bg-dark">
        <h1 class="display-6">Details :</h1>
        <div class="item_details">
        @foreach ($hamper_details as $details)
          <div class="row">
            <div class="col-lg-3">
              <label for="item_id" class="form-label">Item</label>
              <input class="form-control @if ($details->item->deleted_at) text-danger @endif" type="text" name="" id="" value="{{ $details->item->name }}@if($details->item->deleted_at)(deleted)@endif">
            </div>
            <div class="col-lg-3">
              <label for="unit_price" class="form-label">Harga Satuan</label>
              <input class="form-control @if ($details->item->deleted_at) text-danger @endif" type="text" name="" id="" value="{{ $details->item->purchase_price }}">
            </div>
            <div class="col-lg-2">
              <label for="qty" class="form-label">Jumlah</label>
              <input class="form-control @if ($details->item->deleted_at) text-danger @endif" type="text" name="" id="" value="{{ $details->qty }}">
            </div>
            <div class="col-lg-3">
              <label for="total" class="form-label">Total</label>
              <input class="form-control @if ($details->item->deleted_at) text-danger @endif" type="text" name="" id="" value="{{ number_format(($details->item->purchase_price*$details->qty), 0, ',', '.') }}">
            </div>
          </div>
        @endforeach
        </div>
        <hr class="my-4 bg-dark">
    </form>
</div>


<script>
    $(document).ready(function() {
      // Add readonly attribute to all input fields within the form
      $("form :input").prop("readonly", true);
    });
</script>
@endsection