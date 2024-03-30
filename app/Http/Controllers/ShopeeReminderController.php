<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopeeReminder;
use Illuminate\Support\Carbon;
use RealRashid\SweetAlert\Facades\Alert;

class ShopeeReminderController extends Controller
{
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
        
        return view('dashboard.shopeereminder.index', [
            'shopee_reminders' => $shopeeReminders
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
}
