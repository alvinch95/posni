@extends('dashboard.layouts.main')

@section('container')
<div class="container-fluid">
    <div class="row mt-3">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title">Order Information</h5>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-5">
                      <ul class="list-group list-group-flush">
                          <li class="list-group-item"><strong>Order Number:</strong> {{ $sales_order->order_number }}</li>
                          <li class="list-group-item"><strong>Customer:</strong> {{ $sales_order->customer->name }}</li>
                          <li class="list-group-item"><strong>Order Date:</strong> {{ $sales_order->order_date }}</li>
                          <li class="list-group-item"><strong>Capital:</strong> Rp. {{ number_format($sales_order->total_capital_price, 0, ',', '.') }}</li>
                      </ul>
                    </div>
                    <div class="col-md-5">
                      <ul class="list-group list-group-flush">
                          <li class="list-group-item"><strong>Total Selling Price:</strong> Rp. {{ number_format($sales_order->total_before_discount, 0, ',', '.') }}</li>
                          <li class="list-group-item"><strong>Customer Fee:</strong> Rp. {{ number_format($sales_order->customer_fee, 0, ',', '.') }}</li>
                          <li class="list-group-item"><strong>Discount:</strong> Rp. {{ number_format($sales_order->discount_amount, 0, ',', '.') }}</li>
                          <li class="list-group-item"><strong>Grand Total:</strong> Rp. {{ number_format($sales_order->total_order, 0, ',', '.') }}</li>
                      </ul>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-4">
                      <ul class="list-group list-group-flush">
                        <li class="list-group-item fs-6"><strong>Revenue:</strong> Rp. {{ number_format($sales_order->total_revenue, 0, ',', '.') }}</li>
                      </ul>
                    </div>
                    <div class="col-md-1"></div>
                    <div class="col-md-4">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item fs-6"><strong>Keterangan:</strong> {{ $sales_order->remarks }}</li>
                        </ul>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title">Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="border border-3 border-secondary bg-dark text-white fs-5">
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sales_order->details as $index => $detail)
                                <tr class="border border-2 border-secondary fs-5">
                                    <td>{{ $detail->hamper->name }}</td>
                                    <td>{{ $detail->qty }}</td>
                                    <td>Rp. {{ number_format($detail->selling_price, 0, ',', '.') }}</td>
                                    <td>Rp. {{ number_format($detail->qty * $detail->selling_price, 0, ',', '.') }}</td>
                                </tr>
                                    <!-- Nested loop for SalesOrderDetailItems -->
                                    @foreach ($detail->salesOrderDetailItems as $item)
                                    <tr class="bg-light border border-1 border-secondary fs-7">
                                        <td class="px-4">{{ $item->item_name }}</td>
                                        <td>{{ $item->qty }}</td>
                                        <td>Rp. {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                                        {{-- <td>Rp. {{ number_format($item->qty*$detail->qty*$item->selling_price, 0, ',', '.') }}</td> --}}
                                        <td class="bg-secondary border border-secondary"></td>
                                    </tr>
                                    @endforeach
                                    <!-- End of nested loop -->
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
