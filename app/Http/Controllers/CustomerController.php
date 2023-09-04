<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customers = Customer::latest()->get();
        return view('dashboard.customers.index', [
            'customers' => Customer::get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.customers.create');
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
            'name' => 'required|max:255|unique:customers',
            'fee' => 'required|numeric|between:0,99.99'
        ]);
        $validatedData['notes'] = $request->notes; // Add notes field

        Customer::create($validatedData);
        Alert::success('Success', 'Customer has been added');
        return redirect('/dashboard/customers');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        return view('dashboard.customers.show',[
            'customer' => $customer
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        return view('dashboard.customers.edit',[
            'customer' => $customer
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        // dd($request);
        $rules = [
            'fee' => 'required|numeric|between:0,99.99'
        ];
        
        if($customer->name != $request->name)
        {
            $rules['name'] = 'required|max:255|unique:customers';
        }

        $validatedData = $request->validate($rules);
        $validatedData['notes'] = $request->notes; // Add notes field

        Customer::where('id', $customer->id)->update($validatedData);

        Alert::success('Success', 'Customer has been updated');

        return redirect('/dashboard/customers');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        Customer::destroy($customer->id);
        Alert::success('Success', 'Customer has been deleted');
        return redirect('/dashboard/customers');
    }
}
