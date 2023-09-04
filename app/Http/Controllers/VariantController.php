<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class VariantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard.variants.index', [
            'variants' => Variant::orderBy('name','asc')->paginate(5)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.variants.create');
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
            'name' => 'required|max:255|unique:variants'
        ]);

        Variant::create($validatedData);

        Alert::success('Success', 'New Variant has been added');

        return redirect('/dashboard/variants');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Variant  $variant
     * @return \Illuminate\Http\Response
     */
    public function show(Variant $variant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Variant  $variant
     * @return \Illuminate\Http\Response
     */
    public function edit(Variant $variant)
    {
        return view('dashboard.variants.edit',[
            'variant' => $variant
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Variant  $variant
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Variant $variant)
    {
        $rules = [];
        if($variant->name != $request->name)
        {
            $rules['name'] = 'required|max:255|unique:variants';
        }

        $validatedData = $request->validate($rules);

        
        Variant::where('id', $variant->id)->update($validatedData);

        Alert::success('Success', 'Variant has been updated');

        return redirect('/dashboard/variants');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Variant  $variant
     * @return \Illuminate\Http\Response
     */
    public function destroy(Variant $variant)
    {
        Variant::destroy($variant->id);

        Alert::success('Success', 'Variant has been deleted');
        return redirect('/dashboard/variants');
    }
}
