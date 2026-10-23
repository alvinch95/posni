@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah FAQ</h1>
</div>

<div class="col-lg-8">
    @include('dashboard.website.faqs._form', [
        'action' => route('website.faqs.store'),
        'method' => 'POST',
        'submit' => 'Simpan FAQ',
        'faq' => null,
    ])
</div>
@endsection
