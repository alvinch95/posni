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
    {{-- Stat cards --}}
    <div class="grid gap-3 grid-cols-2 lg:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs font-medium text-slate-500">Pemasukan</p>
            <p class="mt-1 text-lg font-bold text-emerald-600">{{ $fmt($summary['income']) }}</p>
            <p class="text-[11px] text-slate-400">{{ $cur }} · bln ini</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-slate-500">Pengeluaran</p>
            <p class="mt-1 text-lg font-bold text-rose-600">{{ $fmt($summary['expense']) }}</p>
            <p class="text-[11px] text-slate-400">{{ $cur }} · bln ini</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-slate-500">Tabungan</p>
            <p class="mt-1 text-lg font-bold {{ $summary['saving'] >= 0 ? 'text-slate-900' : 'text-rose-600' }}">{{ $fmt($summary['saving']) }}</p>
            @if (!is_null($setting->monthly_savings_target) && $setting->monthly_savings_target > 0)
                @php($pct = max(0, min(100, round($summary['saving'] / $setting->monthly_savings_target * 100))))
                <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">{{ $pct }}% dari {{ $fmt($setting->monthly_savings_target) }}</p>
            @else
                <p class="text-[11px] text-slate-400">{{ $cur }} · bln ini</p>
            @endif
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-slate-500">Rata-rata / hari</p>
            <p class="mt-1 text-lg font-bold text-slate-900">{{ $fmt($averages['per_day']) }}</p>
            <p class="text-[11px] text-slate-400">per transaksi {{ $fmt($averages['per_txn']) }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid gap-3 lg:grid-cols-2">
        <div class="card p-4">
            <h2 class="text-sm font-semibold text-slate-700 mb-2">Tabungan 6 bulan</h2>
            <div id="savingsChart"></div>
        </div>
        <div class="card p-4">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Pengeluaran per kategori</h2>
            @if (empty($byCategory))
                <p class="text-sm text-slate-400 text-center py-10">Belum ada pengeluaran bulan ini.</p>
            @else
                <div id="categoryChart"></div>
            @endif
        </div>
    </div>

    {{-- Recent --}}
    <div class="card p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-700">Transaksi terbaru</h2>
            <a href="{{ route('chen.finance.transactions.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Lihat semua</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($recent as $t)
                <li class="flex items-center gap-3 py-2.5">
                    <span class="icon-chip icon-chip-sm" style="background-color: {{ optional($t->category)->color ?? '#94a3b8' }}1f;">{{ optional($t->category)->icon ?: '🏷️' }}</span>
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-slate-800 truncate">{{ optional($t->category)->name ?? '—' }}</span>
                        <span class="block text-xs text-slate-400">{{ $t->date->format('d M Y') }}</span>
                    </span>
                    <span class="ml-auto text-sm font-semibold whitespace-nowrap {{ $t->type === 'income' ? 'text-emerald-600' : 'text-slate-900' }}">
                        {{ $t->type === 'income' ? '+' : '−' }}{{ $fmt($t->amount) }}
                    </span>
                </li>
            @empty
                <li class="py-8 text-center text-slate-400 text-sm">Belum ada transaksi.</li>
            @endforelse
        </ul>
    </div>
</div>

@push('scripts')
<script>
    const fontStack = 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif';
    const trend = @json($trend);
    new ApexCharts(document.querySelector('#savingsChart'), {
        chart: { type: 'bar', height: 260, fontFamily: fontStack, toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
        series: [
            { name: 'Masuk', data: trend.map(t => t.income) },
            { name: 'Keluar', data: trend.map(t => t.expense) },
            { name: 'Tabungan', data: trend.map(t => t.saving) },
        ],
        colors: ['#059669', '#e11d48', '#4f46e5'],
        xaxis: { categories: trend.map(t => t.month), labels: { style: { colors: '#94a3b8' } } },
        yaxis: { labels: { formatter: v => new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v), style: { colors: '#94a3b8' } } },
        legend: { position: 'top', horizontalAlign: 'left', markers: { radius: 4 } },
        grid: { borderColor: '#f1f5f9' },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => new Intl.NumberFormat('id-ID').format(v) } },
    }).render();

    const byCat = @json($byCategory);
    if (byCat.length) {
        new ApexCharts(document.querySelector('#categoryChart'), {
            chart: { type: 'donut', height: 280, fontFamily: fontStack },
            series: byCat.map(c => c.total),
            labels: byCat.map(c => c.name),
            colors: byCat.map(c => c.color),
            legend: { position: 'bottom', markers: { radius: 6 } },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '68%' } } },
            tooltip: { y: { formatter: v => new Intl.NumberFormat('id-ID').format(v) } },
        }).render();
    }
</script>
@endpush
@endsection
