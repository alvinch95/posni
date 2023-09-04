@extends('layouts.main')

@section('container')

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1>{{ $product->title }}</h1>

                <p>Dijual oleh <a href="/products?author={{ $product->author->username }}" class="text-decoration-none">{{ $product->author->name }}</a> in <a href="/products?category={{ $product->category->slug }}" class="text-decoration-none">{{ $product->category->name }}</a></p>
                
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
            
                <a href="/products" class="d-block mt-3">Back to products</a>
            </div>
        </div>
    </div>
    
@endsection