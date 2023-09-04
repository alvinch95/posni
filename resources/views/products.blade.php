@extends('layouts.main')

@section('container')
    <h1 class="mb-3 text-center">{{ $title }}</h1>

    <div class="row justify-content-center mb-3">
        <div class="col-md-6">
            <form action="/products">
                @if (request('category'))
                    <input type="hidden" name='category' value="{{ request('category') }}">
                @endif
                @if (request('author'))
                    <input type="hidden" name='author' value="{{ request('author') }}">
                @endif
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Search products.." name="search" value="{{ request('search') }}">
                    <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
                  </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-center">
        {{ $products->links() }}
    </div>
    

    @if ($products->count())
        <div class="card mb-3">
            @if ($products[0]->image)
                <div style="max-height: 400px; overflow:hidden;">
                    <img src="{{ asset('storage/' . $products[0]->image) }}" alt="{{ $products[0]->category->name }}" class="img-fluid">    
                </div>
            @else
                <img src="https://source.unsplash.com/random/1200x400?{{ $products[0]->category->name }}" class="card-img-top position-relative" alt="{{ $products[0]->category->name }}">
            @endif
            
            <div class="card-body text-center">
                <h3 class="card-title"><a href="/product/{{ $products[0]->slug }}" class="text-decoration-none text-dark">{{ $products[0]->title }}</a></h3>
                <p>
                    <small class="text-muted">
                        Dijual oleh <a href="/products?author={{ $products[0]->author->username }}" class="text-decoration-none">{{ $products[0]->author->name }}</a> in <a href="/products?category={{ $products[0]->category->slug }}" class="text-decoration-none">{{ $products[0]->category->name }}</a> {{ $products[0]->created_at->diffForHumans() }}
                    </small>
                </p>
                <p class="card-text">{{ $products[0]->excerpt }}</p>

                <a href="/product/{{ $products[0]->slug }}" class="text-decoration-none btn btn-dark">Read more</a>
            </div>
        </div>
        <div class="container">
            <div class="row">
                @foreach ($products->skip(1) as $product)
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="position-absolute px-3 py-2" style="background-color: rgba(0, 0, 0, 0.7)"><a href="/products?category={{ $product->category->slug }}" class="text-decoration-none text-white">{{ $product->category->name }}</a></div>
                        @if ($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->category->name }}" class="img-fluid">
                        @else
                            <img src="https://source.unsplash.com/random/500x400?{{ $product->category->name }}" class="card-img-top" alt="{{ $product->category->name }}">
                        @endif
                        <div class="card-body">
                        <h5 class="card-title">{{ $product->title }}</h5>
                            <p>
                                <small class="text-muted">
                                    Dijual oleh <a href="/products?author={{ $product->author->username }}" class="text-decoration-none">{{ $product->author->name }}</a> {{ $product->created_at->diffForHumans() }}
                                </small>
                            </p>
                        <p class="card-text">{{ $product->excerpt }}</p>
                        <a href="/product/{{ $product->slug }}" class="btn btn-primary">Read more</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    @else
        <p class="text-center fs-4">No Product Found.</p>
    @endif

@endsection

