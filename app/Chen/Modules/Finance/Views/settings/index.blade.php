@extends('chen::layout')
@section('title', 'Pengaturan — Finance')
@section('heading', 'Pengaturan Finance')
@section('content')
<div class="max-w-md">
    <h1 class="text-lg font-semibold mb-4">Pengaturan</h1>
    <form method="POST" action="{{ route('chen.finance.settings.update') }}"
          class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4">
        @csrf
        <label class="block text-sm text-slate-600">Mata uang
            <input name="currency" value="{{ old('currency', $setting->currency ?? 'IDR') }}" required
                   class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="block text-sm text-slate-600">Target pengeluaran / bulan
            <input type="number" min="0" name="monthly_spending_target"
                   value="{{ old('monthly_spending_target', $setting->monthly_spending_target) }}"
                   class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="block text-sm text-slate-600">Target tabungan / bulan
            <input type="number" min="0" name="monthly_savings_target"
                   value="{{ old('monthly_savings_target', $setting->monthly_savings_target) }}"
                   class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>
        <button class="inline-flex items-center justify-center w-full sm:w-auto bg-slate-900 text-white text-sm rounded-lg px-4 py-3 hover:bg-slate-800">Simpan</button>
    </form>
</div>
@endsection
