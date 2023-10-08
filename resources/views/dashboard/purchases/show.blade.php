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
                          <li class="list-group-item"><strong>Order Number:</strong> {{ $purchase_order->order_number }}</li>
                          <li class="list-group-item"><strong>Supplier:</strong> {{ $purchase_order->supplier->name }}</li>
                          <li class="list-group-item"><strong>Purchase Date:</strong> {{ $purchase_order->purchase_date }}</li>
                          <li class="list-group-item"><strong>Total:</strong> Rp. {{ number_format($purchase_order->total, 0, ',', '.') }}</li>
                          <li class="list-group-item"><strong>Additional Fee:</strong> Rp. {{ number_format($purchase_order->additional_fee, 0, ',', '.') }}</li>
                          <li class="list-group-item"><strong>Notes:</strong> {{ $purchase_order->notes }}</li>
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
                    <h5 class="card-title">Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="border border-3 border-secondary bg-dark text-white fs-5">
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchase_order->details as $index => $detail)
                                <tr class="border border-2 border-secondary fs-5">
                                    <td>{{ $detail->item->name }}</td>
                                    <td>{{ $detail->qty }}</td>
                                    <td>Rp. {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                                    <td>Rp. {{ number_format($detail->total, 0, ',', '.') }}</td>
                                </tr>
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
