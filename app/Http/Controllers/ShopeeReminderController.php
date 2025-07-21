<?php

namespace App\Http\Controllers;

use App\Models\CashBalance;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Hamper;
use App\Models\SalesOrder;
use App\Models\ShoppingCart;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use App\Models\RunningNumber;
use App\Models\ShopeeReminder;
use Illuminate\Support\Carbon;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Facades\DB;
use App\Models\SalesOrderDetailItem;
use RealRashid\SweetAlert\Facades\Alert;

class ShopeeReminderController extends Controller
{
    private static $transaction_code = "PJ";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sortField = request('sort', 'processed_date');
        $sortOrder = request('order', 'desc');

        if(request('sort')){
            Alert::success('Success', 'Sort Reminder by ' . request('sort') . ' '. request('order'));
        }

        $whereRaw = '1 = 1';
        $currentDate = Carbon::now()->format('Y-m-d');
        if(request('processed_date_from'))
        {
            $whereRaw .= " AND date(processed_date) >= '".request('processed_date_from')."'";
        }
        else{
            $whereRaw .= " AND date(processed_date) >= '".$currentDate."'";
        }

        if(request('processed_date_to'))
        {
            $whereRaw .= " AND date(processed_date) <= '".request('processed_date_to')."'";
        }
        else{
            $whereRaw .= " AND date(processed_date) <= '".$currentDate."'";
        }

        if(request('is_processed')){
            $whereRaw .= " AND is_processed = ".request('is_processed');
        }

        $shopeeReminders = ShopeeReminder::whereRaw($whereRaw)->orderBy($sortField, $sortOrder)->filter(request(['search']))->get();
        $shopping_cart = ShoppingCart::with('hamper')->where('user_id',auth()->user()->id)->get();
        $totalAmount = 0;
        foreach($shopeeReminders as $s){
            $totalAmount += $s->total_amount;
        }
        $totalOrder = $shopeeReminders->count();
        
        return view('dashboard.shopeereminder.index', [
            'shopee_reminders' => $shopeeReminders,
            'hampers' => Hamper::with('serie')->orderBy('name', 'asc')->get(),
            'totalAmount' => $totalAmount,
            'totalOrder' => $totalOrder
        ]);
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
        //
    }

    public function openConvert(Request $request){
        $shopeeReminder = ShopeeReminder::find($request->shopeeReminderID);
        $itemLists = json_decode($shopeeReminder->item_list);
        $data = [];
        foreach($itemLists as $item){
            $dataname = "";
            $dataid = 0;
            $modelName = $item->model_name;
            $itemName = $item->item_name;
            $model = explode(",", $modelName);
            $barang = explode("/", $itemName);

            $firstString = strlen($model[0])>1 ? str_ireplace(":","",$model[0]) : (isset($barang[0]) ? $barang[0] : $itemName);
            // $secondString = isset($model[1]) ? str_ireplace("Tanpa Grafir","Polos",$model[1]) : (isset($barang[1]) ? $barang[1] : $itemName);
            $secondString = isset($model[1]) ? str_ireplace(":","",$model[1]) : (isset($barang[1]) ? $barang[1] : $itemName);

            $whereRaw = "name like '%".$firstString."%'";
            $hamper = Hamper::whereRaw($whereRaw)->get();
            
            if($hamper->count() > 1){
                $whereRaw .= " AND name like '%".$secondString."%'";
                $desiredHamper = Hamper::whereRaw($whereRaw)->orderBy('name', 'asc')->get();
                $dataname = $desiredHamper->first()->name ?? "";
                $dataid = $desiredHamper->first()->id ?? "";
            }
            else {
                $dataname = $hamper->first()->name ?? "";
                $dataid = $hamper->first()->id ?? "";
            }
            $data[] = [
                'id' => $dataid,
                'name' => $dataname,
                'price' => $item->model_discounted_price,
                'qty' => $item->model_quantity_purchased 
            ];
        }
        return $data;
    }

    public function convertOrder(Request $request){
        try{
            DB::beginTransaction();
            $shoppingCartsArray = json_decode($request->shopping_carts, true);
            $orderNumber = Self::getCode();
            $customer = Customer::whereRaw("name like '%Shopee%'")->first();
            
            $hamperIDs = $request->hamper_id;
            $unitPrices = $request->unit_price;
            $qtys = $request->qty;
            $totals = $request->total;
            $total_amount = 0;
            $total_capital_price = 0;
            
            //count all total
            foreach($hamperIDs as $index => $hamperid){
                $total_amount += (int)$totals[$index];
                $hamper = Hamper::find($hamperid);
                $total_capital_price += $hamper->capital_price*$qtys[$index];
            }
            $customer_fee = ($customer->fee/100 * $total_amount)+1250;//1250 Fee shopee

            //save header
            $so = new SalesOrder;
            $so->customer_id = $customer->id;
            $so->order_number = $orderNumber;
            $so->order_date = $request->order_date;
            $so->customer_fee = $customer_fee;
            $so->total_before_discount = $total_amount;
            $so->discount_amount = null;
            $so->total_capital_price = $total_capital_price;
            $so->total_order = $total_amount;
            $so->total_revenue = $total_amount - $total_capital_price - $customer_fee;
            $so->remarks = $request->remark;
            $so->save();

            $salesOrderID = $so->id;

            //save to cash balances
            $lastCash = CashBalance::orderBy('id','desc')->first();
            $currentBalance = $lastCash?$lastCash->end_balance:0;

            $cashBalance = new CashBalance;
            $cashBalance->transaction_date = $request->order_date;
            $cashBalance->cash_type = "CashIn";
            $cashBalance->related_to = "Sales";
            $cashBalance->current_balance = $currentBalance;
            $cashBalance->amount = $total_amount - $customer_fee;
            $cashBalance->end_balance = $currentBalance+($total_amount - $customer_fee);            
            $cashBalance->remark = $orderNumber;
            $cashBalance->created_by = auth()->user()->id;
            $cashBalance->save();

            foreach($hamperIDs as $index => $hamperId){
                $hamper = Hamper::find($hamperId);
                //save order detail
                $sod = new SalesOrderDetail;
                $sod->sales_order_id = $salesOrderID;
                $sod->hamper_id = $hamperId;
                $sod->capital_price = $hamper->capital_price;
                $sod->selling_price = $unitPrices[$index];
                $sod->qty = $qtys[$index];
                $sod->save();

                $salesOrderDetailID = $sod->id;

                foreach($hamper->details as $details){
                    //save order detail items
                    $sodi = new SalesOrderDetailItem;
                    $sodi->sales_order_detail_id = $salesOrderDetailID;
                    $sodi->item_id = $details->item->id; 
                    $sodi->item_name = $details->item->name;
                    $sodi->purchase_price = $details->item->purchase_price;
                    $sodi->selling_price = $details->item->selling_price;
                    $sodi->qty = $details->qty * $qtys[$index];
                    $sodi->uom = $details->item->uom;
                    $sodi->save();

                    //update item stock and insert each hampers detail to stock history
                    $sh = new StockHistory;
                    $item = Item::find($details->item_id);
                    $sh->item_id = $details->item_id;
                    $sh->transaction_date = $request->order_date;
                    $sh->transaction_type = 'Sales';
                    $sh->initial_stock = $item->stock;
                    $qtyDeduct = $details->qty * $qtys[$index];
                    $sh->qty = $qtyDeduct;
                    $endStock = $item->stock - $qtyDeduct;
                    $sh->end_stock = $endStock;
                    $sh->remark = $orderNumber;
                    $sh->save();

                    $item->stock = $endStock;
                    $item->save();
                }

                //update shopee reminder
                $shopeeReminder = ShopeeReminder::find($request->shopee_reminder_id);
                $shopeeReminder->is_processed = true;
                $shopeeReminder->remarks = $orderNumber;
                $shopeeReminder->updated_at = now();
                $shopeeReminder->save();
            }

            DB::commit();
            Alert::success('Success', 'Convert to Order Successs !');
            return redirect()->back();
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while converting to sales order.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ShopeeReminder  $shopeeReminder
     * @return \Illuminate\Http\Response
     */
    public function show(ShopeeReminder $shopeeReminder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ShopeeReminder  $shopeeReminder
     * @return \Illuminate\Http\Response
     */
    public function edit(ShopeeReminder $shopeeReminder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShopeeReminder  $shopeeReminder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ShopeeReminder $shopeeReminder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ShopeeReminder  $shopeeReminder
     * @return \Illuminate\Http\Response
     */
    public function destroy(ShopeeReminder $shopeeReminder)
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
