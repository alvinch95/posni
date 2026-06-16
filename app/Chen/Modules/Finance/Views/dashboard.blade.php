@extends('chen::layout')
@section('title', 'Finance — Chen')
@section('heading', 'Finance')
@push('head')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush
@section('content')
@php
    $cur = $setting->currency ?? 'IDR';
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
@endphp
<div class="space-y-5">
    {{-- Cards --}}
    <div class="grid gap-3 grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Pemasukan (bln ini)</p>
            <p class="text-lg font-semibold text-emerald-600">{{ $cur }} {{ $fmt($summary['income']) }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Pengeluaran (bln ini)</p>
            <p class="text-lg font-semibold text-rose-600">{{ $cur }} {{ $fmt($summary['expense']) }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Tabungan (bln ini)</p>
            <p class="text-lg font-semibold {{ $summary['saving'] >= 0 ? 'text-slate-900' : 'text-rose-600' }}">
                {{ $cur }} {{ $fmt($summary['saving']) }}
            </p>
            @if (!is_null($setting->monthly_savings_target) && $setting->monthly_savings_target > 0)
                @php($pct = min(100, round($summary['saving'] / $setting->monthly_savings_target * 100)))
                <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: {{ max(0, $pct) }}%"></div>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">{{ $pct }}% dari target {{ $fmt($setting->monthly_savings_target) }}</p>
            @endif
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Rata-rata / hari</p>
            <p class="text-lg font-semibold">{{ $cur }} {{ $fmt($averages['per_day']) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Per transaksi: {{ $fmt($averages['per_txn']) }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid gap-3 lg:grid-cols-2">
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-500 mb-2">Tabungan 6 bulan</h2>
            <div id="savingsChart"></div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-500 mb-2">Pengeluaran per kategori (bln ini)</h2>
            <div id="categoryChart"></div>
            @if (empty($byCategory))
                <p class="text-sm text-slate-400 text-center py-6">Belum ada pengeluaran bulan ini.</p>
            @endif
        </div>
    </div>

    {{-- Recent --}}
    <div class="rounded-2xl bg-white border border-slate-200 p-4">
        <h2 class="text-sm font-semibold text-slate-500 mb-2">Transaksi terbaru</h2>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($recent as $t)
                <li class="flex items-center justify-between py-2">
                    <span>{{ $t->date->format('d M') }} · {{ $t->category->name ?? '—' }}</span>
                    <span class="{{ $t->type === 'income' ? 'text-emerald-600' : 'text-slate-800' }}">
                        {{ $t->type === 'income' ? '+' : '−' }} {{ $fmt($t->amount) }}
                    </span>
                </li>
            @empty
                <li class="py-4 text-center text-slate-400">Belum ada transaksi.</li>
            @endforelse
        </ul>
    </div>
</div>

@push('scripts')
<script>
    const trend = @json($trend);
    new ApexCharts(document.querySelector('#savingsChart'), {
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        series: [
            { name: 'Pemasukan', data: trend.map(t => t.income) },
            { name: 'Pengeluaran', data: trend.map(t => t.expense) },
            { name: 'Tabungan', data: trend.map(t => t.saving) },
        ],
        colors: ['#10b981', '#f43f5e', '#0f172a'],
        xaxis: { categories: trend.map(t => t.month) },
        legend: { position: 'top' },
        dataLabels: { enabled: false },
    }).render();

    const byCat = @json($byCategory);
    if (byCat.length) {
        new ApexCharts(document.querySelector('#categoryChart'), {
            chart: { type: 'donut', height: 260 },
            series: byCat.map(c => c.total),
            labels: byCat.map(c => c.name),
            colors: byCat.map(c => c.color),
            legend: { position: 'bottom' },
        }).render();
    }
</script>
@endpush
@endsection
