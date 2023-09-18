@extends('dashboard.layouts.main')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;1,100;1,500&family=REM:wght@100&display=swap" rel="stylesheet">
@endsection

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Sales Order Histories</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="row mb-3">
  <div class="col-md-8">
      <form action="/dashboard/sales/history">
          <div class="row mb-3 align-items-center">
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="text" class="form-control" placeholder="Search items.." name="search" value="{{ request('search') }}">
                <label for="search" class="form-label text-muted">Search keyword</label>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="date" class="form-control" id="order_date_from" name="order_date_from" value="{{ request('order_date_from') }}">
                <label for="order_date_from" class="form-label">Order Date From</label>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-floating mb-1">
                <input type="date" class="form-control" id="order_date_to" name="order_date_to" value="{{ request('order_date_to') }}">
                <label for="order_date_to" class="form-label">Order Date To</label>
              </div>
            </div>
            <div class="col-lg-1">
              <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
            </div>
          </div>
      </form>


      <div class="d-flex align-items-center">
        <h4 class="d-inline" style="font-family: 'Montserrat', sans-serif;">Sorting</h4>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'asc']) }}" class="btn btn-dark mx-1 btn-sm">Item Name (ASC)</a>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'desc']) }}" class="btn btn-outline-dark mx-1 btn-sm">Item Name (DESC)</a>
  
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => 'asc']) }}" class="btn btn-warning mx-1 btn-sm">Date (ASC)</a>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => 'desc']) }}" class="btn btn-outline-warning mx-1 btn-sm">Date (DESC)</a>
      </div>
  </div>
</div>



<div class="table-responsive col-lg-8">
    @if ($sales_orders->count())
      <table class="table table-bordered border-dark table-striped table-sm">
        <caption>Sales Order History</caption>
        <thead class="table-dark border-dark">
          <tr>
            <th scope="col">#</th>
            <th scope="col">Sales Order No</th>
            <th scope="col">Date</th>
            <th scope="col">Customer</th>
            <th scope="col">Remarks</th>
            <th scope="col">Total Order</th>
            <th scope="col">Revenue</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @php
            $totalOrderSum = 0;
            $totalRevenueSum = 0;
          @endphp
          @foreach ($sales_orders as $so)
              <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $so->order_number }}</td>
                  <td>{{ $so->order_date }}</td>
                  <td>{{ $so->customer->name }}</td>
                  <td>{{ $so->remarks }}</td>
                  <td>{{ "Rp. ".number_format($so->total_order, 0, ',', '.') }}</td>
                  <td>{{ "Rp. ".number_format($so->total_revenue, 0, ',', '.') }}</td>
                  <td>
                    <a href="/dashboard/sales/{{ $so->id }}" class="badge bg-primary" title="View Order Detail"><span data-feather="eye"></span></a>
                    {{-- <form action="/dashboard/sales/{{ $so->id }}" method="post" class="d-inline">
                      @method('delete')
                      @csrf
                      <button class="badge bg-danger border-0 hapus" title="Cancel Order"><span data-feather="x-circle"></span></button>
                    </form> --}}
                </td>
              </tr>
          @php
            $totalOrderSum += $so->total_order;
            $totalRevenueSum += $so->total_revenue;
          @endphp
          @endforeach
        </tbody>
        <tfoot>
          <tr>
              <td colspan="5"><strong>Total:</strong></td>
              <td><strong>{{ "Rp. ".number_format($totalOrderSum, 0, ',', '.') }}</strong></td>
              <td><strong>{{ "Rp. ".number_format($totalRevenueSum, 0, ',', '.') }}</strong></td>
              <td></td>
          </tr>
      </tfoot>
      </table>
      <div class="d-flex justify-content-center">
        {{ $sales_orders->links() }}
      </div>
    @else
      <p class="text-center fs-4">No Sales Order Found.</p>
    @endif
</div>
<style>
  
</style>

  <script>
    $(document).ready(function(){
      
    }); 
  </script>
@endsection