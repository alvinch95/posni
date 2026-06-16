@extends('chen::layout')
@section('title', 'Pengaturan — Finance')
@section('heading', 'Pengaturan Finance')
@section('content')
<div class="max-w-md space-y-5">
    <div>
        <h1 class="text-xl font-bold tracking-tight">Pengaturan</h1>
        <p class="text-sm text-slate-500">Mata uang & target bulanan.</p>
    </div>
    <form method="POST" action="{{ route('chen.finance.settings.update') }}" class="card p-5 sm:p-6 space-y-5">
        @csrf
        <div>
            <label class="field-label" for="set-currency">Mata uang</label>
            <input id="set-currency" name="currency" value="{{ old('currency', $setting->currency ?? 'IDR') }}" required maxlength="8" class="field">
        </div>
        <div>
            <label class="field-label" for="set-spend">Target pengeluaran / bulan</label>
            <input id="set-spend" type="number" min="0" inputmode="numeric" name="monthly_spending_target"
                   value="{{ old('monthly_spending_target', $setting->monthly_spending_target) }}" placeholder="mis. 5.000.000" class="field">
        </div>
        <div>
            <label class="field-label" for="set-save">Target tabungan / bulan</label>
            <input id="set-save" type="number" min="0" inputmode="numeric" name="monthly_savings_target"
                   value="{{ old('monthly_savings_target', $setting->monthly_savings_target) }}" placeholder="mis. 2.000.000" class="field">
        </div>
        <button class="btn-primary btn-block sm:w-auto sm:px-8">Simpan</button>
    </form>
</div>
@endsection
