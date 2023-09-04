@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Item</h1>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="col-lg-8">
    <form method="post" action="/dashboard/items/{{ $item->slug }}" class="mb-5" enctype="multipart/form-data">
        @method('patch')
        @csrf
        <div class="mb-3">
          <label for="name" class="form-label">Name</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" autofocus value="{{ old('name', $item->name) }}">
          @error('name')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3">
          <label for="purchase_price" class="form-label">Purchase Price</label>
          <input type="number" class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $item->purchase_price) }}">
          @error('purchase_price')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3">
          <label for="selling_price" class="form-label">Selling Price</label>
          <input type="number" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" value="{{ old('selling_price', $item->selling_price) }}">
          @error('selling_price')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3">
          <label for="stock" class="form-label">Stock</label>
          <input type="number" class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" value="{{ old('stock', $item->stock) }}" readonly>
          @error('stock')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3">
          <label for="uom" class="form-label">Unit of Measurements</label>
          {{-- <input type="text" class="form-control @error('uom') is-invalid @enderror" id="uom" name="uom" autofocus value="{{ old('uom', $item->uom) }}"> --}}
          <select class="form-select" id="uom" name="uom" required>
            <option value="{{ old('uom', $item->uom) }}" selected hidden>{{ old('uom', $item->uom) }}</option>
            <option value="pcs">pcs</option>
            <option value="cm">cm</option>
          </select>

          @error('uom')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3">
          <label for="image" class="form-label">Item Image</label>
          <input type="hidden" name="oldImage" value="{{ $item->image }}">
          @if($item->image)
            <img class="img-preview img-fluid mb-3 col-sm-5 d-block" src="{{ asset('storage/'.$item->image) }}">
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
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            @error('description')
              <p class="text-danger">{{ $message }}</p>
            @enderror
            <input id="description" type="hidden" name="description" value="{{ old('description', $item->description) }}">
            <trix-editor input="description"></trix-editor>
        </div>

        <button type="submit" class="btn btn-primary">Update Item</button>
    </form>
</div>


<script>
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