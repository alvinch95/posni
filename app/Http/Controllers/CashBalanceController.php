<?php

namespace App\Http\Controllers;

use App\Models\CashBalance;
use App\Support\ExpenseCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RealRashid\SweetAlert\Facades\Alert;

class CashBalanceController extends Controller
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

        if(request('sort')){
            Alert::success('Success', 'Sort Order by ' . request('sort') . ' '. request('order'));
        }

        $whereRaw = '1 = 1';
        if(request('transaction_date_from'))
        {
            $whereRaw .= " AND date(transaction_date) >= '".request('transaction_date_from')."'";
        }

        if(request('transaction_date_to'))
        {
            $whereRaw .= " AND date(transaction_date) <= '".request('transaction_date_to')."'";
        }

        $lastCash = CashBalance::orderBy('id','desc')->first();
        $endBalance = $lastCash?$lastCash->end_balance:0;

        return view('dashboard.cashbalances.index', [
            'cash_balances' => CashBalance::whereRaw($whereRaw)->orderBy($sortField, $sortOrder)->filter(request(['search']))->paginate($pageSize)->withQueryString(),
            'pageSize' => $pageSize,
            'totalData' => CashBalance::whereRaw($whereRaw)->orderBy($sortField, $sortOrder)->filter(request(['search']))->count(),
            'currentCash' => $endBalance,
            'categories' => ExpenseCategories::LABELS,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.cashbalances.create', [
            'categories' => ExpenseCategories::LABELS,
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
        $request->validate([
            'cashType' => ['required', Rule::in(['CashIn', 'CashOut'])],
            'category' => ['nullable', 'required_if:cashType,CashOut', Rule::in(ExpenseCategories::slugs())],
        ]);

        try{
            DB::beginTransaction();

            $lastCash = CashBalance::orderBy('id','desc')->first();
            $currentBalance = $lastCash?$lastCash->end_balance:0;

            $cashBalance = new CashBalance;
            $cashBalance->transaction_date = $request->transaction_date;
            $cashBalance->cash_type = $request->cashType;
            $cashBalance->related_to = "Manual Input";
            if ($request->cashType == 'CashOut') {
                $cashBalance->category = $request->category;
            }
            $cashBalance->current_balance = $currentBalance;
            $cashBalance->amount = $request->amount;
            if($request->cashType == 'CashIn')
                $cashBalance->end_balance = $currentBalance+$request->amount;
            else
                $cashBalance->end_balance = $currentBalance-$request->amount;
            
            $cashBalance->remark = $request->remark;
            $cashBalance->created_by = auth()->user()->id;
            $cashBalance->save();

            DB::commit();
            Alert::success('Success', 'Transaction Submitted Successfully !');
            return redirect('/dashboard/cashbalances');
        }catch (\Exception $e) {
            DB::rollback();
            // Handle the exception (log, display error message, etc.)
            Alert::error('Error', $e->getMessage());
            return back()->with('error', 'An error occurred while submitting transactions.');
        }
    }

    /**
     * Recategorise a manual cash-out (inline edit on the cash histories page).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CashBalance  $cashBalance
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCategory(Request $request, CashBalance $cashBalance)
    {
        // Only manual cash-outs are operating costs; sales/purchase rows have no category
        if ($cashBalance->cash_type !== 'CashOut' || $cashBalance->related_to !== 'Manual Input') {
            return response()->json(['message' => 'Only manual cash-outs have a category.'], 422);
        }

        $request->validate([
            'category' => ['required', Rule::in(ExpenseCategories::slugs())],
        ]);

        $cashBalance->category = $request->category;
        $cashBalance->save();

        return response()->json([
            'category' => $cashBalance->category,
            'label' => ExpenseCategories::label($cashBalance->category),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CashBalance  $cashBalance
     * @return \Illuminate\Http\Response
     */
    public function show(CashBalance $cashBalance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CashBalance  $cashBalance
     * @return \Illuminate\Http\Response
     */
    public function edit(CashBalance $cashBalance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CashBalance  $cashBalance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CashBalance $cashBalance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CashBalance  $cashBalance
     * @return \Illuminate\Http\Response
     */
    public function destroy(CashBalance $cashBalance)
    {
        //
    }
}
