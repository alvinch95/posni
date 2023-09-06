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

class StockOpnameController extends Controller
{   
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard.stockopname.create', [
            'items' => Item::orderBy('name', 'asc')->get()
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
            $sh = new StockHistory;
            $sh->item_id = $request->item_id;
            $sh->transaction_date = now();
            $sh->transaction_type = 'Stock Opname';
            $sh->initial_stock = $request->initial_stock;
            $sh->qty = abs($request->difference_stock);
            $sh->end_stock = $request->end_stock;
            $sh->remark = $request->remark;
            $sh->save();

            $item = Item::find($sh->item_id);
            $item->stock = $request->end_stock;
            $item->save();
            DB::commit();
            Alert::success('Success', 'Stock Opname Submitted Successfully !');
            return back();
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while submitting purchases.');
        }
    }

    public function stockin()
    {
        $sortField = request('sort', 'created_at');
        $sortOrder = request('order', 'desc');

        if(request('sort')){
            Alert::success('Success', 'Sort Stock In by ' . request('sort') . ' '. request('order'));
        }

        return view('dashboard.stockopname.index', [
            'stock_histories' => StockHistory::with('item')->whereRaw('end_stock > initial_stock')->orderBy($sortField, $sortOrder)->filter(request(['search']))->paginate(10)->withQueryString(),
            'title' => 'Stock In History',
            'class' => 'stockin'
        ]);
    }

    public function stockout()
    {
        $sortField = request('sort', 'created_at');
        $sortOrder = request('order', 'desc');

        if(request('sort')){
            Alert::success('Success', 'Sort Stock Out by ' . request('sort') . ' '. request('order'));
        }

        return view('dashboard.stockopname.index', [
            'stock_histories' => StockHistory::with('item')->whereRaw('end_stock < initial_stock')->orderBy($sortField, $sortOrder)->filter(request(['search']))->paginate(10)->withQueryString(),
            'title' => 'Stock Out History',
            'class' => 'stockout'
        ]);
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
}
