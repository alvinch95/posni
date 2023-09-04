<?php

namespace App\Http\Controllers;

use App\Models\Serie;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class SerieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard.series.index', [
            'series' => Serie::orderBy('name','asc')->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.series.create');
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
            'name' => 'required|max:255|unique:series'
        ]);

        Serie::create($validatedData);

        Alert::success('Success', 'New Serie has been added');

        return redirect('/dashboard/series');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Serie  $serie
     * @return \Illuminate\Http\Response
     */
    public function show(Serie $serie)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Serie  $serie
     * @return \Illuminate\Http\Response
     */
    public function edit(Serie $series)
    {
        return view('dashboard.series.edit',[
            'serie' => $series
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Serie  $serie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Serie $series)
    {
        $rules = [];
        if($series->name != $request->name)
        {
            $rules['name'] = 'required|max:255|unique:series';
        }

        $validatedData = $request->validate($rules);

        
        Serie::where('id', $series->id)->update($validatedData);

        Alert::success('Success', 'Serie has been updated');

        return redirect('/dashboard/series');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Serie  $serie
     * @return \Illuminate\Http\Response
     */
    public function destroy(Serie $series)
    {
        Serie::destroy($series->id);

        Alert::success('Success', 'Serie has been deleted');
        return redirect('/dashboard/series');
    }
}
