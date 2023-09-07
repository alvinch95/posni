@extends('dashboard.layouts.main')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;1,100;1,500&family=REM:wght@100&display=swap" rel="stylesheet">
@endsection

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

      <div class="d-flex align-items-center">
        <h4 class="d-inline" style="font-family: 'Montserrat', sans-serif;">Sorting</h4>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'asc']) }}" class="btn btn-dark mb-1 btn-sm mx-1">Name (ASC)</a>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'desc']) }}" class="btn btn-outline-dark mb-1 btn-sm mx-1">Name (DESC)</a>

        <a href="{{ request()->fullUrlWithQuery(['sort' => 'stock', 'order' => 'asc']) }}" class="btn btn-warning mb-1 btn-sm mx-1">Stock (ASC)</a>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'stock', 'order' => 'desc']) }}" class="btn btn-outline-warning mb-1 btn-sm mx-1">Stock (DESC)</a>
      </div>
  </div>
</div>

<div class="table-responsive col-lg-8">
    <a href="/dashboard/items/create" class="btn btn-primary mb-3">Add new items</a>
    @if ($items->count())
      <table id="itemsTable" class="table table-bordered border-dark table-striped table-sm">
        <caption>
          <div class="d-flex float-end">
            <label for="page-size-select" class="mx-2">Page Size:</label>
            <select id="page-size-select" class="form-select page-size-select">
                <option value="10" {{ $pageSize==10?"selected":"" }}>10</option>
                <option value="25" {{ $pageSize==25?"selected":"" }}>25</option>
                <option value="50" {{ $pageSize==50?"selected":"" }}>50</option>
            </select>
          </div>
          Showing {{ $pageSize<=$totalData?$pageSize:$totalData }} of {{ $totalData }} results
        </caption>
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
<style>
  /* Style the page size select */
  .page-size-select {
        padding: 4px 8px; /* Adjust padding as needed */
        font-size: 12px; /* Adjust font size as needed */
        width: 70px; /* Adjust width as needed */
    }
</style>
  <script>
    $(document).ready(function(){
      $('#page-size-select').on('change', function() {
          console.log("test");
          const selectedPageSize = $(this).val();
          const currentUrl = window.location.href;

          // Replace the "page_size" query parameter with the selected page size
          const updatedUrl = updateQueryStringParameter(currentUrl, 'page_size', selectedPageSize);

          // Redirect to the updated URL
          window.location.href = updatedUrl;
      });

      // Function to update query parameters in URL
      function updateQueryStringParameter(uri, key, value) {
          const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
          const separator = uri.indexOf('?') !== -1 ? "&" : "?";
          if (uri.match(re)) {
              return uri.replace(re, '$1' + key + "=" + value + '$2');
          }
          return uri + separator + key + "=" + value;
      }

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