@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Produk Website</h1>
</div>

<div class="col-lg-10">
    @include('dashboard.website.products._form', [
        'action' => route('website.products.update', $product),
        'method' => 'PUT',
        'submit' => 'Simpan Perubahan',
        'product' => $product,
    ])
</div>
@endsection
