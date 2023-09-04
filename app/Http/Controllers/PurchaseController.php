<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\RunningNumber;
use App\Models\StockHistory;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class PurchaseController extends Controller
{
    private static $transaction_code = "PB";
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $previousSort = session('previous_sort', ''); // Retrieve previous sorting criteria from session
        // Get the current sorting criteria from the request
        $currentSort = request()->query('sort', '');
        // Store the current sorting criteria in the session for the next request
        session(['previous_sort' => $currentSort]);

        $sortField = request('sort', 'created_at');
        $sortOrder = request('order', 'desc');

        if(request('sort') && $previousSort !== $currentSort){
            Alert::success('Success', 'Sort Purchases by ' . request('sort') . ' '. request('order'));
        }

        return view('dashboard.purchases.index', [
            'purchases' => Purchase::with(['supplier'])->orderBy($sortField, $sortOrder)->filter(request(['search']))->paginate(10)->withQueryString()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.purchases.create',[
            'suppliers' => Supplier::all(),
            'items' => Item::all()
        ]);
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

            $orderNumber = self::getCode();

            // Step 1: Save upper section data to Purchases table
            $purchases = new Purchase;
            $purchases->order_number = $orderNumber;
            $purchases->supplier_id = $request->supplier_id;
            $purchases->purchase_date = $request->purchase_date;
            $purchases->total = $request->grand_total;
            $purchases->additional_fee = $request->additional_fee;
            $purchases->notes = $request->notes;
            $purchases->status = 1;
            $purchases->save();

            // Step 2: Retrieve the ID of the newly created Purchases record
            $purchaseId = $purchases->id;

            // Step 3: Loop through the rows and save to PurchaseDetails table
            $itemIds = $request->item_id;
            $qtys = $request->qty;
            $unitPrices = $request->unit_price;
            $totals = $request->total;

            foreach ($itemIds as $index => $itemId) {
                $purchaseDetail = new PurchaseDetail;
                $purchaseDetail->purchase_id = $purchaseId;
                $purchaseDetail->item_id = $itemId;
                $purchaseDetail->qty = $qtys[$index];
                $purchaseDetail->unit_price = $unitPrices[$index];
                $purchaseDetail->total = $totals[$index];
                $purchaseDetail->save();

                //Step 4 : Insert Stock history for each Item and update the stock
                $item = Item::find($itemId);
                $initialStock = $item->stock;
                $newStock = intval($initialStock)+intval($qtys[$index]);

                //update stock item
                $item->stock = $newStock;
                $item->save();

                //create new record of stock history
                $stockHistory = new StockHistory;
                $stockHistory->item_id = $itemId;
                $stockHistory->transaction_date = now();
                $stockHistory->transaction_type = "Purchase";
                $stockHistory->initial_stock = $initialStock;
                $stockHistory->qty = $qtys[$index];
                $stockHistory->end_stock = $newStock;
                $stockHistory->remark = $orderNumber;
                $stockHistory->save();
            }

            DB::commit();
            Alert::success('Success', 'Purchase Submitted Successfully !');
            return redirect('/dashboard/purchases');
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
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function show(Purchase $purchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function edit(Purchase $purchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Purchase $purchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(Purchase $purchase)
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
