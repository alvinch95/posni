@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My products</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="table-responsive col-lg-8">
    <a href="/dashboard/products/create" class="btn btn-primary mb-3">Create new product</a>
    <table class="table table-striped table-sm">
      <thead>
        <tr>
          <th scope="col">#</th>
          <th scope="col">Title</th>
          <th scope="col">Category</th>
          <th scope="col">Price</th>
          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($products as $product)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $product->title }}</td>
                <td>{{ $product->category->name }}</td>
                <td>Rp. {{ number_format($product->price, 0, ',', '.') }}</td>
                <td>
                    <a href="/dashboard/products/{{ $product->slug }}" class="badge bg-primary"><span data-feather="eye"></span></a>
                    <a href="/dashboard/products/{{ $product->slug }}/edit" class="badge bg-warning"><span data-feather="edit"></span></a>
                    <form action="/dashboard/products/{{ $product->slug }}" method="post" class="d-inline">
                      @method('delete')
                      @csrf
                      <button class="badge bg-danger border-0" onclick="return confirm('Are you sure ?')"><span data-feather="x-circle"></span></button>
                    </form>
                </td>
            </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection