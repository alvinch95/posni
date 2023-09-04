@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Items</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="row mb-3">
  <div class="col-md-6">
      <form action="/dashboard/items">
          <div class="input-group mb-3">
              <input type="text" class="form-control" placeholder="Search items.." name="search" value="{{ request('search') }}">
              <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
            </div>
      </form>

      <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'asc']) }}" class="btn btn-dark">Sort by Name (ASC)</a>
      <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'desc']) }}" class="btn btn-outline-dark">Sort by Name (DESC)</a>

      <a href="{{ request()->fullUrlWithQuery(['sort' => 'stock', 'order' => 'asc']) }}" class="btn btn-warning">Sort by Stock (ASC)</a>
      <a href="{{ request()->fullUrlWithQuery(['sort' => 'stock', 'order' => 'desc']) }}" class="btn btn-outline-warning">Sort by Stock (DESC)</a>

  </div>
</div>

<div class="table-responsive col-lg-8">
    <a href="/dashboard/items/create" class="btn btn-primary mb-3">Add new items</a>
    @if ($items->count())
      <table class="table table-bordered border-dark table-striped table-sm">
        <caption>List of items</caption>
        <thead class="table-dark border-dark">
          <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">Purchase Price</th>
            <th scope="col">Selling Price</th>
            <th scope="col">Stock</th>
            <th scope="col">Image</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($items as $item)
              <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $item->name }}</td>
                  <td>{{ "Rp. ".number_format($item->purchase_price, 0, ',', '.') }}</td>
                  <td>{{ "Rp. ".number_format($item->selling_price, 0, ',', '.') }}</td>
                  <td>{{ $item->stock." ".$item->uom }}</td>
                  @if ($item->image)
                  <td><img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="img-fluid rounded-circle" style="max-width: 50px; max-height: 50px;"></td>
                  @else
                  <td>-</td>
                  @endif
                  
                  <td>
                      <a href="/dashboard/items/{{ $item->slug }}" class="badge bg-primary" title="View Item"><span data-feather="eye"></span></a>
                      <a href="/dashboard/items/{{ $item->slug }}/edit" class="badge bg-warning" title="Edit Item"><span data-feather="edit"></span></a>
                      <form action="/dashboard/items/{{ $item->slug }}" method="post" class="d-inline">
                        @method('delete')
                        @csrf
                        <button class="badge bg-danger border-0 hapus" title="Delete Item"><span data-feather="x-circle"></span></button>
                      </form>
                  </td>
              </tr>
          @endforeach
        </tbody>
      </table>
      <div class="d-flex justify-content-center">
        {{ $items->links() }}
      </div>
    @else
      <p class="text-center fs-4">No Product Found.</p>
    @endif
</div>

  <script>
    $(document).ready(function(){
      $('.hapus').click(function(e) {
        e.preventDefault();
        var form = $(this).parents('form');
        Swal.fire({
            title: 'Are you sure ?',
            // text: "You won't be able to revert this!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.value) {
                form.submit();
            }
        });
      });
    }); 
  </script>
@endsection