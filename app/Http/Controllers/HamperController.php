<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Serie;
use App\Models\Hamper;
use App\Models\HamperDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class HamperController extends Controller
{
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
            Alert::success('Success', 'Sort Hampers by ' . request('sort') . ' '. request('order'));
        }

        return view('dashboard.hampers.index', [
            'hampers' => Hamper::with(['serie'])->orderBy($sortField, $sortOrder)->filter(request(['search']))->paginate(10)->withQueryString()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.hampers.create',[
            'series' => Serie::all(),
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
            // Step 1: Save upper section data to Hampers table
            $hampers = new Hamper;
            $hampers->name = $request->name;
            $hampers->serie_id = $request->serie_id;
            $hampers->capital_price = $request->capital_price;
            $hampers->revenue_percentage = $request->revenue_percentage;
            $hampers->selling_price = $request->selling_price;
            if($request->file('image'))
            {
                //store image ke folder
                $imagePath = $request->file('image')->store('hampers-images');
                $hampers->image = $imagePath;
            }
            $hampers->save();

            // Step 2: Retrieve the ID of the newly created Hampers record
            $hampersId = $hampers->id;

            // Step 3: Loop through the rows and save to HamperDetails table
            $itemIds = $request->item_id;
            $qtys = $request->qty;
            $unitPrices = $request->unit_price;
            $totals = $request->total;

            foreach ($itemIds as $index => $itemId) {
                $hamperDetail = new HamperDetail;
                $hamperDetail->hamper_id = $hampersId;
                $hamperDetail->item_id = $itemId;
                $hamperDetail->qty = $qtys[$index];
                $hamperDetail->unit_price = $unitPrices[$index];
                $hamperDetail->total = $totals[$index];
                $hamperDetail->save();
            }

            DB::commit();
            Alert::success('Success', 'New Hampers has been added');
            return redirect('/dashboard/hampers');
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while creating hampers.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Hamper  $hamper
     * @return \Illuminate\Http\Response
     */
    public function show(Hamper $hamper)
    {
        return view('dashboard.hampers.show',[
            'hampers' => $hamper->load('serie'),
            'hamper_details' => HamperDetail::with(['item' => function ($query){
                $query->withTrashed();
            }])->where('hamper_id',$hamper->id)->get()
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Hamper  $hamper
     * @return \Illuminate\Http\Response
     */
    public function edit(Hamper $hamper)
    {
        return view('dashboard.hampers.edit',[
            'hampers' => $hamper,
            'hamper_details' => HamperDetail::with('item')->where('hamper_id',$hamper->id)->get(),
            'series' => Serie::all(),
            'items' => Item::all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Hamper  $hamper
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Hamper $hamper)
    {
        try{
            DB::beginTransaction();
            // Step 1: Save upper section data to Hampers table
            $hamper->name = $request->name;
            $hamper->serie_id = $request->serie_id;
            $hamper->capital_price = $request->capital_price;
            $hamper->revenue_percentage = $request->revenue_percentage;
            $hamper->selling_price = $request->selling_price;
            if($request->file('image'))
            {
                //hapus dulu gambar existing
                if($request->oldImage){
                    Storage::delete($request->oldImage);
                }

                //store image ke folder
                $imagePath = $request->file('image')->store('hampers-images');
                $hamper->image = $imagePath;
            }
            $hamper->save();

            // Step 2: Retrieve the ID of the newly created Hampers record
            $hampersId = $hamper->id;

            // Step 3 : Soft delete all existing details
            foreach($hamper->details as $detail){
                $detail->delete();
            }

            // Step 4: Loop through the rows and save to HamperDetails table
            $itemIds = $request->item_id;
            $qtys = $request->qty;
            $unitPrices = $request->unit_price;
            $totals = $request->total;

            foreach ($itemIds as $index => $itemId) {
                $hamperDetail = new HamperDetail;
                $hamperDetail->hamper_id = $hampersId;
                $hamperDetail->item_id = $itemId;
                $hamperDetail->qty = $qtys[$index];
                $hamperDetail->unit_price = $unitPrices[$index];
                $hamperDetail->total = $totals[$index];
                $hamperDetail->save();
            }

            DB::commit();
            Alert::success('Success', 'Hampers has been updated');
            return redirect('/dashboard/hampers');
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while updating hampers.');
        }
    }

    public function updatePrice(Request $request){
        try{
            DB::beginTransaction();
            
            $hamper = Hamper::find($request->hamper_id);
            $hamper->selling_price = $request->newPrice;
            $hamper->revenue_percentage = round(($request->newPrice-$hamper->capital_price)/$hamper->capital_price*100,2);
            $hamper->save();
            DB::commit();
            Alert::success('Success', 'Price has been updated');
            return redirect()->back();
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while updating price.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Hamper  $hamper
     * @return \Illuminate\Http\Response
     */
    public function destroy(Hamper $hamper)
    {
        try{
            DB::beginTransaction();
            foreach($hamper->details as $detail){
                $detail->delete();
            }
            $hamper->delete();
            DB::commit();
            
            Alert::success('Success', 'Hampers has been deleted');
            return redirect('/dashboard/hampers');
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while deleting hampers.');
        }
    }
}
