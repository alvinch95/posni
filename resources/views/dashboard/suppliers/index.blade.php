@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Suppliers</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-6" role="alert">
  {{ session('success') }}
  </div>
@endif

{{-- @if (session()->has('alert.delete'))
<div class="alert alert-success col-lg-6" role="alert">
  {{ session('alert.delete') }}
  </div>
@endif --}}

<div class="table-responsive col-lg-6">
    <a href="/dashboard/suppliers/create" class="btn btn-primary mb-3">Create new supplier</a>
    <table class="table table-striped table-sm">
      <thead>
        <tr>
          <th scope="col">#</th>
          <th scope="col">Name</th>
          <th scope="col">Phone</th>
          <th scope="col">Bank Account No</th>
          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($suppliers as $supplier)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $supplier->name }}</td>
                <td>{{ $supplier->phone }}</td>
                <td>{{ $supplier->bank_account }}</td>
                <td>
                    <a href="/dashboard/suppliers/{{ $supplier->id }}" class="badge bg-primary"><span data-feather="eye"></span></a>
                    <a href="/dashboard/suppliers/{{ $supplier->id }}/edit" class="badge bg-warning"><span data-feather="edit"></span></a>
                    <form action="/dashboard/suppliers/{{ $supplier->id }}" method="post" class="d-inline">
                      @method('delete')
                      @csrf
                      <a href="#" class="badge bg-danger border-0 hapus"><span data-feather="x-circle"></span></a>
                    </form>
                </td>
            </tr>
        @endforeach
      </tbody>
    </table>
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