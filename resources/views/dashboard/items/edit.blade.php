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
    <form method="post" action="/dashboard/items/{{ $item->slug }}" class="mb-5" enctype="multipart/form-data" id="editItemForm">
        @method('patch')
        @csrf
        <input type="hidden" name="confirm_hamper_update" id="confirm_hamper_update" value="0">
        <input type="hidden" id="original_purchase_price" value="{{ $item->purchase_price }}">
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
          <input type="number" class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $item->purchase_price) }}" min="1">
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
            <option value="gram">gram</option>
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
    document.getElementById('editItemForm').addEventListener('submit', function(e) {
        if (this.dataset.confirmed) return;

        const originalPrice = Number(document.getElementById('original_purchase_price').value);
        const newPrice = Number(document.getElementById('purchase_price').value);

        if (originalPrice !== newPrice) {
            e.preventDefault();
            const form = this;

            $.ajax({
                url: '/dashboard/items/previewHamperUpdate',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    item_id: {{ $item->id }},
                    new_purchase_price: newPrice
                },
                success: function(data) {
                    if (data.length > 0) {
                        let tableHtml = '<div style="max-height:300px;overflow-y:auto;">' +
                            '<table class="table table-sm table-bordered text-nowrap" style="font-size:13px;">' +
                            '<thead class="table-dark"><tr><th>Hamper</th><th>Modal</th><th style="background-color:#198754;color:#fff;">Modal Baru</th><th>Revenue %</th><th style="background-color:#198754;color:#fff;">Revenue % Baru</th></tr></thead><tbody>';
                        data.forEach(function(h) {
                            tableHtml += '<tr>' +
                                '<td>' + h.name + '</td>' +
                                '<td>' + Number(h.capital_price).toLocaleString() + '</td>' +
                                '<td style="background-color:#d1e7dd;color:#0f5132;font-weight:600;">' + Number(h.new_capital_price).toLocaleString() + '</td>' +
                                '<td>' + h.revenue_percentage + '%</td>' +
                                '<td style="background-color:#d1e7dd;color:#0f5132;font-weight:600;">' + h.new_revenue_percentage + '%</td>' +
                                '</tr>';
                        });
                        tableHtml += '</tbody></table></div>';

                        Swal.fire({
                            title: 'Update affected hampers?',
                            html: tableHtml,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, update all',
                            cancelButtonText: 'Cancel',
                            width: '600px'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('confirm_hamper_update').value = '1';
                                form.dataset.confirmed = '1';
                                form.submit();
                            }
                        });
                    } else {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                },
                error: function(xhr) {
                    console.error('Preview hamper update failed:', xhr.responseText);
                    form.dataset.confirmed = '1';
                    form.submit();
                }
            });
        }
    });

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