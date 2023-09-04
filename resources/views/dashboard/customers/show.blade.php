@extends('dashboard.layouts.main')

@section('container')
<div class="container">
    <div class="row my-3">
        <div class="col-lg-8">
            <h1>{{ $customer->title }}</h1>

            <a href="/dashboard/customers" class="btn btn-success my-3"><span data-feather="arrow-left"></span> Back to all my customers</a>
            <a href="/dashboard/customers/{{ $customer->id }}/edit" class="btn btn-warning my-3"><span data-feather="edit"></span> Edit</a>
            <form action="/dashboard/customers/{{ $customer->id }}" method="post" class="d-inline">
                @method('delete')
                @csrf
                <button class="btn btn-danger my-3 hapus"><span data-feather="x-circle"></span> Delete</button>
            </form>

            
            <h4>Customer Name : {{ $customer->name }}</h4>
            <h4>Fee : {{ $customer->fee }} %</h4>
            <h6>Notes : {{ $customer->notes }}</h6>
        </div>
    </div>
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