<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $suppliers = Supplier::latest()->get();
        return view('dashboard.suppliers.index', [
            'suppliers' => Supplier::get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255|unique:suppliers',
            'phone' => 'numeric',
            'bank_account' => 'numeric'
        ]);
        $validatedData['notes'] = $request->notes; // Add notes field

        Supplier::create($validatedData);
        Alert::success('Success', 'Supplier has been added');
        return redirect('/dashboard/suppliers');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function show(Supplier $supplier)
    {
        return view('dashboard.suppliers.show',[
            'supplier' => $supplier
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function edit(Supplier $supplier)
    {
        return view('dashboard.suppliers.edit',[
            'supplier' => $supplier
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Supplier $supplier)
    {
        $rules = [
            'phone' => 'numeric',
            'bank_account' => 'numeric'
        ];
        
        if($supplier->name != $request->name)
        {
            $rules['name'] = 'required|max:255|unique:suppliers';
        }

        $validatedData = $request->validate($rules);
        $validatedData['notes'] = $request->notes; // Add notes field

        Supplier::where('id', $supplier->id)->update($validatedData);

        Alert::success('Success', 'Supplier has been updated');

        return redirect('/dashboard/suppliers');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function destroy(Supplier $supplier)
    {
        Supplier::destroy($supplier->id);
        Alert::success('Success', 'Supplier has been deleted');
        return redirect('/dashboard/suppliers');
    }
}
