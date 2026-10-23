@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Kategori</h1>
</div>

<div class="col-lg-8">
    @include('dashboard.website.categories._form', [
        'action' => route('website.categories.store'),
        'method' => 'POST',
        'submit' => 'Simpan Kategori',
        'category' => null,
    ])
</div>
@endsection
