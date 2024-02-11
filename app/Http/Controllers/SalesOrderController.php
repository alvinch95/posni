<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Hamper;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\ShoppingCart;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use App\Models\RunningNumber;
use App\Models\SalesOrderDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\SalesOrderDetailItem;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class SalesOrderController extends Controller
{
    private static $transaction_code = "PJ";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $shopping_cart = ShoppingCart::with('hamper')->where('user_id',auth()->user()->id)->get();
        $total_cart = 0;
        $total_modal = 0;
        foreach($shopping_cart as $cart){
            $total_cart += $cart->selling_price*$cart->qty;
            $total_modal += $cart->hamper->capital_price*$cart->qty;
        }
        $sortField = request('sort', 'created_at');
        $sortOrder = request('order', 'desc');
        return view('dashboard.sales.index',[
            'hampers' => Hamper::with('serie')->orderBy($sortField, $sortOrder)->filter(request(['search']))->paginate(12)->withQueryString(),
            'shopping_carts' => ShoppingCart::with('hamper')->where('user_id',auth()->user()->id)->get(),
            'customers' => Customer::all(),
            'total_cart' => $total_cart,
            'total_modal' => $total_modal
        ]);
    }

    public function addToCart(Request $request){
        try{
            DB::beginTransaction();
            $shopping_cart = new ShoppingCart;
            $shopping_cart->user_id = auth()->user()->id;
            $shopping_cart->hamper_id = $request->hamper_id;
            $shopping_cart->selling_price = $request->selling_price;
            $shopping_cart->qty = $request->qty;
            $shopping_cart->save();
            DB::commit();
            Alert::success('Success', 'Add to cart');
            return redirect()->back();
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while add to cart.');
        }
    }

    public function removeCart(Request $request){
        try{
            DB::beginTransaction();
            $shopping_cart = ShoppingCart::find($request->cart_id);
            $shopping_cart->delete();
            DB::commit();
            return new JsonResponse(['message' => 'Item removed from cart'], 200);
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            DB::beginTransaction();
            $shoppingCartsArray = json_decode($request->shopping_carts, true);
            $orderNumber = Self::getCode();

            //save header
            $so = new SalesOrder;
            $so->customer_id = $request->customer_id;
            $so->order_number = $orderNumber;
            $so->order_date = $request->order_date;
            $so->customer_fee = $request->fee_customer;
            $so->total_before_discount = $request->total_order_original;
            $so->discount_amount = $request->discount_amount;
            $so->total_capital_price = $request->total_modal;
            $so->total_order = $request->total_order;
            $so->total_revenue = $request->total_cuan;
            $so->remarks = $request->remarks;
            $so->save();

            $salesOrderID = $so->id;

            foreach($shoppingCartsArray as $cart){
                $sc = ShoppingCart::find($cart['id']);
                //save order detail
                $sod = new SalesOrderDetail;
                $sod->sales_order_id = $salesOrderID;
                $sod->hamper_id = $cart['hamper_id'];
                $sod->capital_price = $sc->hamper->capital_price;
                $sod->selling_price = $cart['selling_price'];
                $sod->qty = $cart['qty'];
                $sod->save();

                $salesOrderDetailID = $sod->id;

                $hamper = Hamper::find($cart['hamper_id']);
                foreach($hamper->details as $details){
                    //save order detail items
                    $sodi = new SalesOrderDetailItem;
                    $sodi->sales_order_detail_id = $salesOrderDetailID;
                    $sodi->item_id = $details->item->id; 
                    $sodi->item_name = $details->item->name;
                    $sodi->purchase_price = $details->item->purchase_price;
                    $sodi->selling_price = $details->item->selling_price;
                    $sodi->qty = $details->qty;
                    $sodi->uom = $details->item->uom;
                    $sodi->save();

                    //update item stock and insert each hampers detail to stock history
                    $sh = new StockHistory;
                    $item = Item::find($details->item_id);
                    $sh->item_id = $details->item_id;
                    $sh->transaction_date = $request->order_date;
                    $sh->transaction_type = 'Sales';
                    $sh->initial_stock = $item->stock;
                    $qtyDeduct = $details->qty * $cart['qty'];
                    $sh->qty = $qtyDeduct;
                    $endStock = $item->stock - $qtyDeduct;
                    $sh->end_stock = $endStock;
                    $sh->remark = $orderNumber;
                    $sh->save();

                    $item->stock = $endStock;
                    $item->save();
                }

                //delete shopping cart after saving order is done
                $sc->order_number = $orderNumber;
                $sc->delete();
            }

            DB::commit();
            Alert::success('Success', 'Sales Order Submitted Successfully !');
            return redirect()->back();
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while submitting purchases.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\Response
     */

    public function history()
    {
        $pageSize = request('page_size', 10); // Default page size is 10
        $sortField = request('sort', 'created_at');
        $sortOrder = request('order', 'desc');

        if(request('sort')){
            Alert::success('Success', 'Sort Stock In by ' . request('sort') . ' '. request('order'));
        }

        $whereRaw = '1 = 1';
        if(request('order_date_from'))
        {
            $whereRaw .= " AND order_date >= '".request('order_date_from')."'";
        }

        if(request('order_date_to'))
        {
            $whereRaw .= " AND order_date <= '".request('order_date_to')."'";
        }

        return view('dashboard.sales.history', [
            'sales_orders' => SalesOrder::with('customer')->whereRaw($whereRaw)->orderBy($sortField, $sortOrder)->filter(request(['search']))->paginate($pageSize)->withQueryString(),
            'pageSize' => $pageSize,
            'totalData' => SalesOrder::with('customer')->whereRaw($whereRaw)->orderBy($sortField, $sortOrder)->filter(request(['search']))->count()
        ]);
    }
    public function show(SalesOrder $sale)
    {
        $so = SalesOrder::with(['details.salesOrderDetailItems','customer','details.hamper'])->find($sale->id);

        return view('dashboard.sales.show',[
            'sales_order' => $so
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\Response
     */
    public function edit(SalesOrder $salesOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SalesOrder $salesOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(SalesOrder $salesOrder)
    {
        //
    }

    private static function getCode()
    {
        $code = "";
        $month = now()->month;
        $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
        $year = now()->year;
        $day = now()->day;
        $dayFormatted = str_pad($day, 2, '0', STR_PAD_LEFT);
        $iteration = 0;
        $running_number = RunningNumber::where([['bulan',$month],['tahun',$year],['code',self::$transaction_code]])->first();

        if($running_number){
            $iteration = $running_number->next_number;
            $running_number->next_number = $iteration+1;
        }else{
            //insert the first one
            $running_number = new RunningNumber;
            $running_number->code = self::$transaction_code;
            $running_number->bulan = $month;
            $running_number->tahun = $year;
            $running_number->next_number = 2;
            $iteration = 1;
        }
        $running_number->save();

        $iterationFormatted = str_pad($iteration, 4, '0', STR_PAD_LEFT);
        $code = self::$transaction_code."/".$dayFormatted.$monthFormatted.$year."/".$iterationFormatted;
        return $code;
    }
}
