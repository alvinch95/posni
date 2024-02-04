@extends('dashboard.layouts.main')

@section('container')
<section class="mt-5">
    <div class="row">
        <div class="col-lg-9">
            <div class="row d-flex justify-content-left">
                <div class="row justify-content-left mb-3">
                    <div class="col-lg-4 col-sm-12">
                        <form action="/dashboard/sales">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Search products.." name="search" value="{{ request('search') }}">
                                <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
                              </div>
                        </form>
                    </div>
                    <div class="col-lg-3 col-sm-6 @desktop text-end @enddesktop">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'asc']) }}" class="btn btn-primary">Sort by Name (ASC)</a>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'desc']) }}" class="btn btn-outline-dark">Sort by Name (DESC)</a>
                    </div>
                </div>
            </div>
            <div class="row">
                @foreach ($hampers as $hamper)
                <div class="col-md-12 col-lg-3 mb-4 mb-lg-3">
                        <form id="addToCartForm" method="POST" action="{{ route('dashboard.sales.addToCart') }}">
                        @csrf
                        <div class="card">
                            <div class="card-img-container">
                                @if ($hamper->image)
                                    <img src="{{ asset('storage/' . $hamper->image) }}" class="card-img" alt="{{ $hamper->name }}" />
                                @else
                                    <img src="{{ asset('storage/hampers-images/no-image-found.jpg') }}" class="card-img" alt="{{ $hamper->name }}" />
                                @endif
                            </div>
                            <div class="card-body px-2">
                                <div class="d-flex justify-content-between">
                                    <p class="small"><a href="#!" class="text-muted">{{ $hamper->serie->name }}</a></p>
                                    <p class="small text-info">{{ "Rp. ".number_format($hamper->capital_price, 0, ',', '.') }}</p>
                                </div>
                    
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <p class="fs-5 mb-0 text-wrap">{{ $hamper->name }}</p>
                                    <p class="fs-7 text-success mb-0">{{ "Rp. ".number_format($hamper->selling_price, 0, ',', '.') }}</p>
                                </div>
                                <hr>
                    
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <p class="text-muted mb-0">Available: <span class="fw-bold">{{ $hamper->getStock() }}</span></p>
                                    <a href="#" class="update-price btn btn-warning btn-sm fw-bold py-1 px-2" data-bs-toggle="modal" data-bs-target="#updatePriceModal" data-hamper-id="{{ $hamper->id }}" data-hamper-name={{ $hamper->name }} data-hamper-price={{ $hamper->selling_price }}>Update price</a>
                                    {{-- <div>
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" id="decrement" onclick="this.parentNode.querySelector('input[type=number]').stepDown()">-</button>
                                            <input type="number" class="form-control form-control-sm" value="1" min="1" max="{{ $hamper->getStock() }}" id="quantity" name="qty">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" id="increment" onclick="this.parentNode.querySelector('input[type=number]').stepUp()">+</button>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <input type="hidden" name="hamper_id" value="{{ $hamper->id }}">
                                        <input type="hidden" name="selling_price" value="{{ $hamper->selling_price }}">
                                        <button type="submit" class="btn btn-dark add-cart" id="add-cart" @if($hamper->getStock() <= 0) disabled @endif ><i class="bi bi-cart"></i></button>
                                    </div> --}}
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex">
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" id="decrement" onclick="this.parentNode.querySelector('input[type=number]').stepDown()">-</button>
                                            <input type="number" class="form-control form-control-sm" value="1" min="1" max="{{ $hamper->getStock() }}" id="quantity" name="qty">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" id="increment" onclick="this.parentNode.querySelector('input[type=number]').stepUp()">+</button>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <input type="hidden" name="hamper_id" value="{{ $hamper->id }}">
                                        <input type="hidden" name="selling_price" value="{{ $hamper->selling_price }}">
                                        <button type="submit" class="btn btn-dark add-cart" id="add-cart" @if($hamper->getStock() <= 0) disabled @endif >Add to <i class="bi bi-cart"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                @endforeach
                <div class="d-flex justify-content-center">
                    {{ $hampers->links() }}
                </div>
          </div>
        </div>
        {{-- Shopping Cart --}}
        <nav class="col-lg-3 shopping-cart">
            <div style="position: sticky; top: 100px;" class="border bg-light d-flex flex-column cart">
                <h1 class="text-center fs-3 mt-2 mb-0">Checkout Carts</h1>
                <hr>
                <div class="h-100 mt-2" style="overflow: auto;">
                    @foreach ($shopping_carts as $shopping_cart)
                    <div class="d-flex flex-grow-1 justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center mx-4">
                            <div class="cart-img-container">
                                @if ($shopping_cart->hamper->image)
                                    <img src="{{ asset('storage/' . $shopping_cart->hamper->image) }}" alt="{{ $shopping_cart->hamper->name }}" class="img-thumbnail me-2 cart-img" style="max-width: 100px;"> 
                                @else
                                    <img src="{{ asset('storage/hampers-images/no-image-found.jpg') }}" alt="{{ $shopping_cart->hamper->name }}" class="img-thumbnail me-2 cart-img" style="max-width: 100px;"> 
                                @endif
                            </div>
                            <div>
                                <h6 class="mb-1">{{ $shopping_cart->hamper->name }}</h6>
                                <p class="mb-0 text-muted">{{ "Rp. ".number_format($shopping_cart->selling_price, 0, ',', '.') }}</p>
                                <p class="mb-0 text-muted">x{{ $shopping_cart->qty }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-dark btn-sm me-3 delete-cart" data-cart-id="{{ $shopping_cart->id }}"><i class="bi bi-trash"></i></button>
                    </div>
                    @endforeach
                </div>
                <div class="px-2">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="order_date_picker" class="form-label">Order Date</label>
                            <input type="date" class="form-control" id="order_date_picker" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="remarks" class="form-label">Keterangan</label>
                            <input type="text" class="form-control" id="remarks_input" required>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-center justify-content-center mt-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <div class="@desktop col-8 @elsedesktop w-100 @enddesktop mx-2">
                            <label for="customer_id" class="form-label">Customer</label>
                            <select class="form-select" id="customer_select" required>
                              <option value="" disabled selected hidden>Select customer</option>
                              @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" data-fee="{{ $customer->fee }}">{{ $customer->name }}</option>
                              @endforeach
                            </select>
                        </div>
                        <div class="@desktop col-3 @elsedesktop w-100 @enddesktop mx-2">
                            <label for="discount" class="form-label">Discount</label>
                            <input class="form-control" type="number" name="discount" id="discount">
                        </div>
                    </div>
                    <div>
                        <p class="fs-7 fw-bold mb-0 text-center" id="fee_customer_label">Fee Customer : </p>
                        <p class="fs-6 fw-bold mb-0 text-center" id="total_order_label">Total : {{ "Rp. ".number_format($total_cart, 0, ',', '.') }}</p>
                        <p class="fs-6 fw-bold mb-0 text-center" id="total_cuan_label">Cuan : {{ "Rp. ".number_format($total_cart-$total_modal, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <button type="submit" class="btn @if ($shopping_carts->count() <= 0)
                            btn-secondary
                        @else
                            btn-success
                        @endif checkout" @if($shopping_carts->count() <= 0) disabled @endif>Checkout</button>
                    </div>
                </div>
            </div>
        </nav>
        <form id="checkoutForm" method="POST" action="{{ route('sales.store') }}">
            @csrf
            <input type="hidden" name="total_order_original" id="total_order_original" value="{{ $total_cart }}">
            <input type="hidden" name="shopping_carts" id="shopping_carts" value="{{ json_encode($shopping_carts) }}">
            <input type="hidden" name="customer_id" id="customer_id" value="">
            <input type="hidden" name="discount_amount" id="discount_amount" value="">
            <input type="hidden" name="fee_customer" id="fee_customer" value="">
            <input type="hidden" name="total_order" id="total_order" value="{{ $total_cart }}">
            <input type="hidden" name="total_modal" id="total_modal" value="{{ $total_modal }}">
            <input type="hidden" name="total_cuan" id="total_cuan" value="{{ $total_cart - $total_modal }}">
            <input type="hidden" name="order_date" id="order_date" value="{{ now()->format('Y-m-d') }}">
            <input type="hidden" name="remarks" id="remarks">
        </form>
    </div>
</section>
<div class="modal fade" id="updatePriceModal" tabindex="-1" aria-labelledby="updatePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updatePriceModalLabel">Update Price</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updatePriceForm" method="POST" action="{{ route('dashboard.hampers.updatePrice') }}">
                @csrf
                <div class="modal-body">
                    <p class="fs-3 mb-0 text-wrap" id="hamperName"></p>
                    <input type="hidden" name="hamper_id" id="hamperId">
                    <div class="mb-3">
                        <label for="currentPrice" class="form-label">Current Price</label>
                        <input type="text" class="form-control" id="currentPrice" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="newPrice" class="form-label">New Price</label>
                        <input type="number" class="form-control" id="newPrice" name="newPrice" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    /* Media query for screens with a maximum width of 768px (typical for mobile devices) */
    @media (max-width: 768px) {
        .shopping-cart {
            height: 75vh; /* 65% of the viewport height */
        }
    }

    @media (min-width: 768px) {
    /* Styles for tablets and larger screens */
        .cart {
            height: 85vh; /* 85% of the viewport height */
        }
    }
    .card-img-container {
        height: 200px; /* Adjust the height as needed */
        overflow: hidden; /* Hide any image overflow if necessary */
    }

    .card-img {
        object-fit: contain; /* Maintain aspect ratio and cover the container */
        /* object-fit: contain;  */
        /* object-fit: cover; */
        height: 100%; /* Take up 100% of the container's height */
        width: 100%; /* Take up 100% of the container's width */
    }

    /* Custom CSS for smaller input group */
    .input-group-sm {
        font-size: 12px; /* Adjust the font size as needed */
    }

    .input-group-sm .btn {
        padding: 0.1rem 0.3rem; /* Adjust padding as needed */
    }

    .input-group-sm .form-control {
        height: 15px; /* Adjust height as needed */
        width: 30px;
        font-size: 12px; /* Adjust the font size as needed */
    }
    /* Chrome, Safari, Edge, Opera */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
    }

    /* Firefox */
    input[type=number] {
    -moz-appearance: textfield;
    }
</style>
<script>
    $(document).ready(function () {
        $('.add-cart').click(function(e){
            $("#loading-container").show();
        });

        $('.checkout').click(function(e) {
            e.preventDefault();

            var customer = $('#customer_select').find(":selected");
            var discount = $('#discount').val();
            var total_order = $('#total_order_original').val();
            var total_cuan = $('#total_cuan').val();
            var order_date = $('#order_date').val();
            
            if(order_date == "" || order_date == null || order_date == undefined){
                Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: "Tanggal order wajib diisi."
                    });
                return;
            }
            
            if(customer.val() == "" || customer.val() == null){
                Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: "Customer wajib diisi."
                    });
                return;
            }

            var form = $('#checkoutForm');
            if(total_cuan <= 0){
                Swal.fire({
                    title: 'Anda yakin ?',
                    text: "Cuan anda kurang dari 0, apakah yakin untuk checkout ?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, lanjutkan!'
                }).then((result) => {
                    if (result.value) {
                        $("#loading-container").show();
                        form.submit();
                    }
                });
            }
            else
            {
                Swal.fire({
                    title: 'Are you sure ?',
                    text: "This will update your stock",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, confirm!'
                }).then((result) => {
                    if (result.value) {
                        $("#loading-container").show();
                        form.submit();
                    }
                });
            }
        });

        $('#order_date_picker').on('change', function(){
            var order_date = $(this).val();
            $('#order_date').val(order_date);
        });
        $('#remarks_input').on('change', function(){
            var remarks = $(this).val();
            $('#remarks').val(remarks);
        });

        $('#discount').on('change', function () {
            var selectedOption = $('#customer_select').find(":selected");
            if(selectedOption == null){
                var fee_percentage = 0;
            }
            else{
                var fee_percentage = parseFloat(selectedOption.data('fee'));
            }
            var discount = $(this).val();
            var total_order = $('#total_order_original').val();
            var total_modal = $('#total_modal').val();
            var total_after_discount = total_order - discount;
            var fee_amount = fee_percentage/100*total_after_discount;
            var new_total = total_after_discount - fee_amount;
            var cuan = new_total - total_modal;
            var formattedTotal = 'Total : Rp. ' + new_total.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            var formattedFee = 'Fee Customer : Rp. ' + fee_amount.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            var formattedCuan = 'Cuan : Rp. ' + cuan.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            // Set the values of the hidden input fields
            $('#total_order_label').text(formattedTotal);
            $('#total_order').val(new_total);
            $('#discount_amount').val(discount);
            $('#fee_customer_label').text(formattedFee);
            $('#fee_customer').val(fee_amount);
            $('#total_cuan_label').text(formattedCuan);
            $('#total_cuan').val(cuan);
        });

        $('#customer_select').on('change', function () {
            var selectedOption = $(this).find(":selected");
            var fee_percentage = parseFloat(selectedOption.data('fee'));
            var total_order = $('#total_order_original').val();
            var total_modal = $('#total_modal').val();
            var fee_amount = fee_percentage/100*total_order;
            var new_total = total_order-fee_amount;
            var cuan = new_total - total_modal;
            var formattedTotal = 'Total : Rp. ' + new_total.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            var formattedFee = 'Fee Customer : Rp. ' + fee_amount.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            var formattedCuan = 'Cuan : Rp. ' + cuan.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            // Set the values of the hidden input fields
            $('#total_order_label').text(formattedTotal);
            $('#total_order').val(new_total);
            $('#fee_customer_label').text(formattedFee);
            $('#fee_customer').val(fee_amount);
            $('#customer_id').val(selectedOption.val());
            $('#total_cuan_label').text(formattedCuan);
            $('#total_cuan').val(cuan);
        });

        // Handle the "Update price" link click event
        $('.update-price').on('click', function () {
            // Get the hamper ID and some other data from the data attributes
            var hamperId = $(this).data('hamper-id');
            var hamperName = $(this).data('hamper-name');
            var hamperPrice = $(this).data('hamper-price');

            // Set the values of the hidden input fields
            $('#hamperId').val(hamperId);
            $('#hamperName').text(hamperName);
            $('#currentPrice').val(hamperPrice);
        });

        $('.delete-cart').on('click', function() {
            $("#loading-container").show();
            
            // Extract the cart ID from the data attribute
            var cartId = $(this).data('cart-id');
            
            $.ajax({
                url: "{{ route('dashboard.sales.removeCart') }}",
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    cart_id: cartId
                },
                success: function (response) {
                    $("#loading-container").hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message
                    }).then(function(){
                        location.reload();
                    });
                },
                error: function (xhr, status, error) {
                    $("#loading-container").hide();
                    // Extract the error message from the server response
                    var errorMessage = xhr.responseText;
                    // Display the error message using SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            });
        });
    });
</script>
@endsection
