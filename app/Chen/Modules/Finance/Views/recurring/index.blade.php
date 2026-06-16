@extends('chen::layout')
@section('title', 'Berulang — Finance')
@section('heading', 'Transaksi Berulang')
@section('content')
<div x-data="{ open: false }" class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Transaksi Berulang</h1>
        <button @click="open = true" class="bg-slate-900 text-white text-sm rounded-lg px-3 py-2 hover:bg-slate-800">+ Tambah</button>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-4 py-2">Kategori</th><th class="px-4 py-2">Frekuensi</th>
                    <th class="px-4 py-2">Jalan Berikutnya</th><th class="px-4 py-2 text-right">Jumlah</th>
                    <th class="px-4 py-2">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rules as $r)
                    <tr>
                        <td class="px-4 py-2">{{ $r->category->name ?? '—' }} <span class="text-xs text-slate-400">({{ $r->type }})</span></td>
                        <td class="px-4 py-2">{{ ucfirst($r->frequency) }}</td>
                        <td class="px-4 py-2">{{ $r->next_run_date->format('d M Y') }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($r->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('chen.finance.recurring.toggle', $r->id) }}">
                                @csrf @method('PATCH')
                                <button class="text-xs rounded-full px-2 py-0.5 {{ $r->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">
                                    {{ $r->active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('chen.finance.recurring.destroy', $r->id) }}"
                                  onsubmit="return confirm('Hapus aturan ini?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-rose-500 hover:text-rose-700">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Belum ada aturan berulang.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Create modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-md p-5">
            <h3 class="font-semibold mb-3">Tambah Aturan Berulang</h3>
            <form method="POST" action="{{ route('chen.finance.recurring.store') }}">
                @csrf
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
                    <input type="number" name="amount" min="0" placeholder="Jumlah" required class="w-full rounded-lg border-slate-300 text-sm">
                    <select name="frequency" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="monthly">Bulanan</option>
                        <option value="weekly">Mingguan</option>
                        <option value="yearly">Tahunan</option>
                    </select>
                    <label class="block text-sm text-slate-600">Mulai
                        <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    <label class="block text-sm text-slate-600">Berakhir (opsional)
                        <input type="date" name="end_date" class="w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    <textarea name="notes" placeholder="Catatan (opsional)" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
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
