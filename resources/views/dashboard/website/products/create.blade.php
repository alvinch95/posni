@extends('dashboard.layouts.main')

@section('head')
    @include('dashboard.website.products._form_styles')
@endsection

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Produk Website</h1>
</div>

@include('dashboard.website.products._form', [
    'action' => route('website.products.store'),
    'method' => 'POST',
    'product' => null,
])
@endsection
