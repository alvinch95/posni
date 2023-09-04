@extends('dashboard.layouts.main')

@section('container')
<div class="container">
    <div class="row my-3">
        <div class="col-lg-8">
            <h1>{{ $product->title }}</h1>

            <a href="/dashboard/products" class="btn btn-success my-3"><span data-feather="arrow-left"></span> Back to all my products</a>
            <a href="/dashboard/products/{{ $product->slug }}/edit" class="btn btn-warning my-3"><span data-feather="edit"></span> Edit</a>
            <form action="/dashboard/products/{{ $product->slug }}" method="post" class="d-inline">
                @method('delete')
                @csrf
                <button class="btn btn-danger my-3" onclick="return confirm('Are you sure ?')"><span data-feather="x-circle"></span> Delete</button>
            </form>

            
            <h4>Price : {{ $product->price }}</h4>
                
            @if ($product->image)
                <div style="max-height: 350px; overflow:hidden;">
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->category->name }}" class="img-fluid">    
                </div>
            @else
                <img src="https://source.unsplash.com/random/800x400?{{ $product->category->name }}" alt="{{ $product->category->name }}" class="img-fluid">
            @endif

            <article class="my-3 fs-5">
                {!! $product->body !!}
            </article>
        </div>
    </div>
</div>
@endsection