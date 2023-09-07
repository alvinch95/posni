@extends('dashboard.layouts.main')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;1,100;1,500&family=REM:wght@100&display=swap" rel="stylesheet">
@endsection

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ $title }}</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="row mb-3">
  <div class="col-md-6">
      <form action="/dashboard/{{ $class }}">
          <div class="input-group mb-3">
              <input type="text" class="form-control" placeholder="Search items.." name="search" value="{{ request('search') }}">
              <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
            </div>
      </form>


      <div class="d-flex align-items-center">
        <h4 class="d-inline" style="font-family: 'Montserrat', sans-serif;">Sorting</h4>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'asc']) }}" class="btn btn-dark mx-1 btn-sm mb-1">Item Name (ASC)</a>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'desc']) }}" class="btn btn-outline-dark mx-1 btn-sm mb-1">Item Name (DESC)</a>
  
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => 'asc']) }}" class="btn btn-warning mx-1 btn-sm mb-1">Date (ASC)</a>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => 'desc']) }}" class="btn btn-outline-warning mx-1 btn-sm mb-1">Date (DESC)</a>
      </div>

  </div>
</div>

<div class="table-responsive col-lg-8">
    @if ($stock_histories->count())
      <table class="table table-bordered border-dark table-striped table-sm">
        <caption>Stock History</caption>
        <thead class="table-dark border-dark">
          <tr>
            <th scope="col">#</th>
            <th scope="col">Item Name</th>
            <th scope="col">Date</th>
            <th scope="col">Type</th>
            <th scope="col">Initial Stock</th>
            <th scope="col">Qty</th>
            <th scope="col">End Stock</th>
            <th scope="col">Remark</th>
            <th scope="col">Image</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($stock_histories as $sh)
              <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $sh->item->name }}</td>
                  <td>{{ $sh->created_at }}</td>
                  <td>{{ $sh->transaction_type }}</td>
                  <td>{{ $sh->initial_stock }}</td>
                  <td>{{ $sh->qty }}</td>
                  <td>{{ $sh->end_stock }}</td>
                  <td>{{ $sh->remark }}</td>
                  @if ($sh->item->image)
                  <td><img src="{{ asset('storage/' . $sh->item->image) }}" alt="{{ $sh->item->name }}" class="img-fluid rounded-circle" style="max-width: 50px; max-height: 50px;"></td>
                  @else
                  <td>-</td>
                  @endif
              </tr>
          @endforeach
        </tbody>
      </table>
      <div class="d-flex justify-content-center">
        {{ $stock_histories->links() }}
      </div>
    @else
      <p class="text-center fs-4">No Stock History Found.</p>
    @endif
</div>

  <script>
    $(document).ready(function(){
      
    }); 
  </script>
@endsection