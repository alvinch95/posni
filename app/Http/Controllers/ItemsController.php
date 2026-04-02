<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Item;
use App\Models\Hamper;
use App\Models\HamperDetail;
use App\Models\Serie;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class ItemsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pageSize = request('page_size', 10); // Default page size is 10
        $sortField = request('sort', 'created_at');
        $sortOrder = request('order', 'desc');

        $previousSort = session('sort_items');
        session(['sort_items' => $sortField.$sortOrder]);
        if(request('sort') && ($sortField.$sortOrder) != $previousSort){
            if ($sortField == '(stock*purchase_price)')
                Alert::success('Success', 'Sort Item by value '. request('order'));
            else
                Alert::success('Success', 'Sort Item by ' . request('sort') . ' '. request('order'));
        }

        return view('dashboard.items.index', [
            'items' => Item::orderBy(DB::raw($sortField), $sortOrder)->filter(request(['search']))->paginate($pageSize)->withQueryString(),
            'pageSize' => $pageSize,
            'totalData' => Item::orderBy($sortField, $sortOrder)->filter(request(['search']))->count()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.items.create');
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
            $validatedData = $request->validate([
                'name' => ['required', 'max:255', 'unique:items', 'regex:/^[a-zA-Z0-9\s\-]+$/u'],
                'purchase_price' => 'required|numeric',
                'selling_price' => 'required|numeric|gt:purchase_price',
                'stock' => 'required|numeric',
                'uom' => 'required',
                'image' => 'image|file|max:1024'
            ]);

            if($request->file('image'))
            {
                //store image ke folder
                $validatedData['image'] = $request->file('image')->store('item-images');
            }

            $validatedData['user_id'] = auth()->user()->id;
            $validatedData['slug'] = str_replace(" ","-",strtolower($request->name));
            $validatedData['description'] = $request->description;

            $item = Item::create($validatedData);

            $serie = Serie::where('name','=','Item')->first();
            

            $hamper = new Hamper;
            $hamper->serie_id = $serie?$serie->id:1;
            $hamper->name = $item->name;
            $hamper->capital_price = $item->purchase_price;
            $hamper->revenue_percentage = round((($item->selling_price/$item->purchase_price)-1)*100,2);
            $hamper->selling_price = $item->selling_price;
            $hamper->image = $item->image;
            $hamper->from_item = $item->id;
            $hamper->save();

            $detail = new HamperDetail;
            $detail->hamper_id = $hamper->id;
            $detail->item_id = $item->id;
            $detail->unit_price = $item->purchase_price;
            $detail->qty = 1;
            $detail->total = $item->purchase_price;
            $detail->save();

            DB::commit();
            Alert::success('Success', 'New Item has been added');

            return redirect('/dashboard/items');
        }catch (\Exception $e) {
            DB::rollback();
            if($e instanceof \Illuminate\Validation\ValidationException) {
                // Handle validation errors
                return back()->withErrors($e->validator->errors())->withInput();
            }
            
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while submitting item.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        return view('dashboard.items.show',[
            'item' => $item
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function edit(Item $item)
    {
        return view('dashboard.items.edit',[
            'item' => $item
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Item $item)
    {
        $rules = [
            'purchase_price' => 'required|numeric',
            'selling_price' => 'required|numeric|gt:purchase_price',
            'stock' => 'required|numeric',
            'uom' => 'required',
            'image' => 'image|file|max:1024'
        ];
        
        if($item->name != $request->name)
        {
            $rules['name'] = 'required|max:255|unique:items';
        }

        $validatedData = $request->validate($rules);
        $validatedData['slug'] = str_replace(" ","-",strtolower($request->name));
        $validatedData['description'] = $request->description;

        if($request->file('image'))
        {
            //hapus dulu gambar existing
            if($request->oldImage){
                Storage::delete($request->oldImage);
            }

            //store image ke folder
            $validatedData['image'] = $request->file('image')->store('item-images');
        }
        
        Item::where('id', $item->id)->update($validatedData);

        if ($request->confirm_hamper_update == '1') {
            // Update hamper_details unit_price and total for this item
            HamperDetail::where('item_id', $item->id)
                ->whereNull('deleted_at')
                ->update([
                    'unit_price' => $validatedData['purchase_price'],
                    'total' => DB::raw('qty * ' . (int) $validatedData['purchase_price']),
                ]);

            // Recalculate capital_price and revenue_percentage for affected hampers
            DB::statement("
                UPDATE hampers h
                INNER JOIN (
                    SELECT
                        h.id,
                        SUM(i.purchase_price * hd.qty) AS new_capital_price,
                        ROUND((h.selling_price / SUM(i.purchase_price * hd.qty) - 1) * 100, 2) AS new_revenue_percent
                    FROM hampers h
                    LEFT JOIN hamper_details hd ON hd.hamper_id = h.id
                    LEFT JOIN items i ON i.id = hd.item_id
                    WHERE hd.deleted_at IS NULL
                      AND h.id IN (
                          SELECT DISTINCT hd2.hamper_id
                          FROM hamper_details hd2
                          WHERE hd2.item_id = ?
                            AND hd2.deleted_at IS NULL
                      )
                    GROUP BY h.id
                    HAVING SUM(i.purchase_price * hd.qty) > 0
                ) nh ON nh.id = h.id
                SET
                    h.capital_price      = nh.new_capital_price,
                    h.revenue_percentage = nh.new_revenue_percent
            ", [$item->id]);
        }

        Alert::success('Success', 'Item has been updated');

        return redirect('/dashboard/items');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy(Item $item)
    {
        //hapus dulu gambar existing
        if($item->image){
            Storage::delete($item->image);
        }

        //delete dulu Hampers yg berasal dari item ini
        Hamper::where('from_item',$item->id)->delete();

        // Item::destroy($item->id);
        Item::where('id',$item->id)->delete();

        Alert::success('Success', 'Item has been deleted');
        return redirect('/dashboard/items');
    }

    public function previewHamperUpdate(Request $request)
    {
        $itemId = $request->item_id;
        $newPrice = $request->new_purchase_price;

        $hampers = DB::select("
            SELECT
                h.id,
                h.name,
                h.capital_price,
                h.revenue_percentage,
                SUM(
                    CASE WHEN hd.item_id = ? THEN ? ELSE i.purchase_price END * hd.qty
                ) AS new_capital_price,
                ROUND((h.selling_price / SUM(
                    CASE WHEN hd.item_id = ? THEN ? ELSE i.purchase_price END * hd.qty
                ) - 1) * 100, 2) AS new_revenue_percentage
            FROM hampers h
            LEFT JOIN hamper_details hd ON hd.hamper_id = h.id
            LEFT JOIN items i ON i.id = hd.item_id
            WHERE hd.deleted_at IS NULL
              AND h.deleted_at IS NULL
              AND h.id IN (
                  SELECT DISTINCT hd2.hamper_id
                  FROM hamper_details hd2
                  WHERE hd2.item_id = ?
                    AND hd2.deleted_at IS NULL
              )
            GROUP BY h.id, h.name, h.capital_price, h.revenue_percentage, h.selling_price
            HAVING SUM(CASE WHEN hd.item_id = ? THEN ? ELSE i.purchase_price END * hd.qty) > 0
        ", [$itemId, $newPrice, $itemId, $newPrice, $itemId, $itemId, $newPrice]);

        return response()->json($hampers);
    }
}
