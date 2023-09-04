@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Pembelian</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="row mb-3">
  <div class="col-md-6">
      <form action="/dashboard/purchases">
          <div class="input-group mb-3">
              <input type="text" class="form-control" placeholder="Search purchases.." name="search" value="{{ request('search') }}">
              <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
            </div>
      </form>

      <a href="{{ request()->fullUrlWithQuery(['sort' => 'code', 'order' => 'asc']) }}" class="btn btn-dark">Sort by Code (ASC)</a>
      <a href="{{ request()->fullUrlWithQuery(['sort' => 'code', 'order' => 'desc']) }}" class="btn btn-outline-dark">Sort by Code (DESC)</a>

  </div>
</div>

<div class="table-responsive col-lg-8">
    <a href="/dashboard/purchases/create" class="btn btn-primary mb-3">Tambah Pembelian</a>
    @if ($purchases->count())
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Kode Pembelian</th>
            <th scope="col">Tanggal</th>
            <th scope="col">Supplier</th>
            <th scope="col">Total</th>
            <th scope="col">Catatan</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($purchases as $purchase)
              <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $purchase->order_number }}</td>
                  <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d F Y') }}</td>
                  <td>{{ $purchase->supplier->name }}</td>
                  <td>{{ "Rp. ".number_format($purchase->total, 0, ',', '.') }}</td>
                  <td>{{ $purchase->notes }}</td>
                  <td>
                      <a href="/dashboard/purchases/{{ $purchase->id }}" class="badge bg-primary"><span data-feather="eye"></span></a>
                      {{-- <form action="/dashboard/purchases/{{ $hamper->id }}" method="post" class="d-inline">
                        @method('delete')
                        @csrf
                        <button class="badge bg-danger border-0 hapus"><span data-feather="x-circle"></span></button>
                      </form> --}}
                  </td>
              </tr>
          @endforeach
        </tbody>
      </table>
      <div class="d-flex justify-content-center">
        {{ $purchases->links() }}
      </div>
    @else
      <p class="text-center fs-4">No Purchases Found.</p>
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