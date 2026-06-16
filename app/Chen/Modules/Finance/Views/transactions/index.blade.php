@extends('chen::layout')
@section('title', 'Transaksi — Finance')
@section('heading', 'Transaksi')
@section('content')
<div x-data="{ open: false, edit: null }" class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <h1 class="text-lg font-semibold">Transaksi</h1>
        <button @click="open = true; edit = null"
                class="bg-slate-900 text-white text-sm rounded-lg px-3 py-2 hover:bg-slate-800">+ Tambah</button>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-2 text-sm">
        <input type="month" name="month" value="{{ $month }}" class="rounded-lg border-slate-300">
        <select name="type" class="rounded-lg border-slate-300">
            <option value="">Semua</option>
            <option value="expense" @selected($type==='expense')>Pengeluaran</option>
            <option value="income" @selected($type==='income')>Pemasukan</option>
        </select>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari catatan…" class="rounded-lg border-slate-300">
        <button class="bg-slate-200 rounded-lg px-3 hover:bg-slate-300">Filter</button>
    </form>

    <div class="rounded-2xl bg-white border border-slate-200">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Kategori</th>
                    <th class="px-4 py-2">Catatan</th><th class="px-4 py-2 text-right">Jumlah</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transactions as $t)
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $t->date->format('d M Y') }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $t->category->color ?? '#999' }}"></span>
                                {{ $t->category->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-slate-500">{{ $t->notes }}</td>
                        <td class="px-4 py-2 text-right font-medium {{ $t->type === 'income' ? 'text-emerald-600' : 'text-slate-800' }}">
                            {{ $t->type === 'income' ? '+' : '−' }} {{ number_format($t->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button class="text-xs text-slate-500 hover:text-slate-900" @click='edit = @json($t); open = true'>Edit</button>
                            <form method="POST" action="{{ route('chen.finance.transactions.destroy', $t->id) }}" class="inline"
                                  onsubmit="return confirm('Hapus transaksi ini?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-rose-500 hover:text-rose-700 ml-1">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 font-semibold">
                <tr>
                    <td colspan="3" class="px-4 py-2 text-right text-slate-500">Pemasukan / Pengeluaran / Net</td>
                    <td class="px-4 py-2 text-right whitespace-nowrap">
                        <span class="text-emerald-600">+{{ number_format($incomeTotal, 0, ',', '.') }}</span>
                        <span class="text-rose-600 ml-2">−{{ number_format($expenseTotal, 0, ',', '.') }}</span>
                        <span class="ml-2 {{ $net >= 0 ? 'text-slate-900' : 'text-rose-600' }}">= {{ number_format($net, 0, ',', '.') }}</span>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
    <div>{{ $transactions->links() }}</div>

    {{-- Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-md p-5">
            <h3 class="font-semibold mb-3" x-text="edit ? 'Edit Transaksi' : 'Tambah Transaksi'"></h3>
            <form method="POST"
                  :action="edit ? '{{ url('finance/transactions') }}/' + edit.id : '{{ route('chen.finance.transactions.store') }}'">
                @csrf
                <template x-if="edit"><input type="hidden" name="_method" value="PUT"></template>
                <div class="space-y-3">
                    <select name="type" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                    </select>
                    <select name="fin_category_id" class="w-full rounded-lg border-slate-300 text-sm" required>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ ucfirst($c->type) }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date" :value="edit ? edit.date.substring(0,10) : '{{ date('Y-m-d') }}'" required
                           class="w-full rounded-lg border-slate-300 text-sm">
                    <input type="number" step="1" min="0" name="amount" :value="edit ? edit.amount : ''" placeholder="Jumlah" required
                           class="w-full rounded-lg border-slate-300 text-sm">
                    <textarea name="notes" placeholder="Catatan (opsional)" x-text="edit ? edit.notes : ''"
                              class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="open = false" class="text-sm px-3 py-2">Batal</button>
                    <button class="bg-slate-900 text-white text-sm rounded-lg px-4 py-2">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
