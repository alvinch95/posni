@extends('dashboard.layouts.main')

@section('container')
<div class="container">
    <div class="row my-3">
        <div class="col-lg-8">
            <h1>{{ $item->name }}</h1>

            <a href="/dashboard/items" class="btn btn-success my-3"><span data-feather="arrow-left"></span> Back to all my items</a>
            <a href="/dashboard/items/{{ $item->slug }}/edit" class="btn btn-warning my-3"><span data-feather="edit"></span> Edit</a>
            <form action="/dashboard/items/{{ $item->slug }}" method="post" class="d-inline">
                @method('delete')
                @csrf
                <button class="btn btn-danger my-3 hapus"><span data-feather="x-circle"></span> Delete</button>
            </form>

            <h4>Stock : {{ $item->stock." ".$item->uom }}</h4>
            <h4>Purchase Price : {{ number_format($item->purchase_price, 0, ',', '.') }}</h4>
            <h4>Selling Price : {{ number_format($item->selling_price, 0, ',', '.') }}</h4>
                
            @if ($item->image)
                <div>
                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="img-fluid" style="max-width: 400px; max-height: 400px;">
                </div>
            @else
                <p>No image</p>
            @endif

            <article class="my-3 fs-5">
                {!! $item->description !!}
            </article>
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