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

<div class="col-lg-12">
    <a href="/dashboard/products/create" class="btn btn-primary mb-4 shadow-sm">
        <span data-feather="plus-circle" class="me-1"></span> Create New Product
    </a>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th scope="col" class="border-0 rounded-start">#</th>
                      <th scope="col" class="border-0">Title</th>
                      <th scope="col" class="border-0">Category</th>
                      <th scope="col" class="border-0">Price</th>
                      <th scope="col" class="border-0 rounded-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td class="ps-3">{{ $loop->iteration }}</td>
                            <td class="fw-medium">{{ $product->title }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $product->category->name }}</span></td>
                            <td class="font-monospace">Rp. {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td>
                                <a href="/dashboard/products/{{ $product->slug }}" class="btn btn-sm btn-outline-primary"><span data-feather="eye"></span></a>
                                <a href="/dashboard/products/{{ $product->slug }}/edit" class="btn btn-sm btn-outline-warning mx-1"><span data-feather="edit"></span></a>
                                <form action="/dashboard/products/{{ $product->slug }}" method="post" class="d-inline">
                                  @method('delete')
                                  @csrf
                                  <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure ?')"><span data-feather="trash-2"></span></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                  </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection