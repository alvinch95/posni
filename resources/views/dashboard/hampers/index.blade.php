@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Hampers</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="row mb-3">
  <div class="col-md-6">
      <form action="/dashboard/hampers">
          <div class="input-group mb-3">
              <input type="text" class="form-control" placeholder="Search hampers.." name="search" value="{{ request('search') }}">
              <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
            </div>
      </form>

      <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'asc']) }}" class="btn btn-dark">Sort by Name (ASC)</a>
      <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'desc']) }}" class="btn btn-outline-dark">Sort by Name (DESC)</a>

  </div>
</div>

<div class="table-responsive col-lg-8">
    <a href="/dashboard/hampers/create" class="btn btn-primary mb-3">Add new hampers</a>
    @if ($hampers->count())
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Nama</th>
            <th scope="col">Seri</th>
            <th scope="col">Harga Modal</th>
            <th scope="col">Harga Jual</th>
            <th scope="col">Stok</th>
            <th scope="col">Image</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($hampers as $hamper)
              <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $hamper->name }}</td>
                  <td>{{ $hamper->serie->name }}</td>
                  <td>{{ "Rp. ".number_format($hamper->capital_price, 0, ',', '.') }}</td>
                  <td>{{ "Rp. ".number_format($hamper->selling_price, 0, ',', '.') }}</td>
                  <td>{{ $hamper->getStock() }}</td>
                  @if ($hamper->image)
                  <td><img src="{{ asset('storage/' . $hamper->image) }}" alt="{{ $hamper->name }}" class="img-fluid" style="max-width: 50px; max-height: 50px;"></td>
                  @else
                  <td><img src="{{ asset('storage/hampers-images/no-image-found.jpg') }}" alt="{{ $hamper->name }}" class="img-fluid" style="max-width: 50px; max-height: 50px;"></td>
                  @endif
                  <td>
                      <a href="/dashboard/hampers/{{ $hamper->id }}" class="badge bg-primary"><span data-feather="eye"></span></a>
                      <a href="/dashboard/hampers/{{ $hamper->id }}/edit" class="badge bg-warning"><span data-feather="edit"></span></a>
                      <form action="/dashboard/hampers/{{ $hamper->id }}" method="post" class="d-inline">
                        @method('delete')
                        @csrf
                        <button class="badge bg-danger border-0 hapus"><span data-feather="x-circle"></span></button>
                      </form>
                  </td>
              </tr>
          @endforeach
        </tbody>
      </table>
      <div class="d-flex justify-content-center">
        {{ $hampers->links() }}
      </div>
    @else
      <p class="text-center fs-4">No Hampers Found.</p>
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