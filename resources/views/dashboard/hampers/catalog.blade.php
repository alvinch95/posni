@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Catalog</h1>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card catalog-card">
            @if ($hamper->image)
            <img src="{{ asset('storage/' . $hamper->image) }}" class="card-img-top" alt="{{ $hamper->name }}">
            @else
            <img src="{{ asset('storage/hampers-images/no-image-found.jpg') }}" class="card-img-top" alt="{{ $hamper->name }}">
            @endif

            <div class="card-body">
                <h5 class="card-title catalog-title">{{ $hamper->name }}</h5>
                <p class="card-text catalog-text">{{ $hamper->serie->name }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-8 mb-4">
        <div class="row catalog-details">
            @foreach ($hamper_details as $details)
            <div class="col-md-6">
                <div class="catalog-item">
                    {{ $details->item->name }}
                </div>
                <div class="catalog-qty">
                    Qty: {{ $details->qty }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .catalog-card {
        border: 1px solid #e0e0e0;
        background: #fff;
    }

    .catalog-title {
        font-size: 18px;
        font-weight: bold;
    }

    .catalog-text {
        font-size: 14px;
        color: #777;
    }

    .catalog-details {
        border: 1px solid #e0e0e0;
        background: #fff;
        max-height: 300px;
        overflow-y: auto;
    }

    .catalog-item {
        font-size: 14px;
        font-weight: bold;
    }

    .catalog-qty {
        font-size: 14px;
    }
</style>

<script>
    $(document).ready(function() {
        // JavaScript code goes here (if needed)
    });
</script>
@endsection
