@extends('chen::layout')
@section('title', 'Transaksi — Finance')
@section('heading', 'Transaksi')
@section('content')
@php($fmt = fn ($v) => number_format((float) $v, 0, ',', '.'))
<div x-data="txForm({{ \Illuminate\Support\Js::from($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'icon' => $c->icon, 'color' => $c->color])->values()) }})" class="space-y-5">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight">Transaksi</h1>
            <p class="text-sm text-slate-500">Catat pemasukan & pengeluaran.</p>
        </div>
        <button type="button" @click="openCreate()" class="btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Tambah
        </button>
    </div>

    {{-- Totals strip --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="card p-3">
            <p class="text-xs text-slate-500">Masuk</p>
            <p class="font-bold text-emerald-600">{{ $fmt($incomeTotal) }}</p>
        </div>
        <div class="card p-3">
            <p class="text-xs text-slate-500">Keluar</p>
            <p class="font-bold text-rose-600">{{ $fmt($expenseTotal) }}</p>
        </div>
        <div class="card p-3">
            <p class="text-xs text-slate-500">Net</p>
            <p class="font-bold {{ $net >= 0 ? 'text-slate-900' : 'text-rose-600' }}">{{ $fmt($net) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="card p-3 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-end">
        <label class="block">
            <span class="field-label">Bulan</span>
            <input type="month" name="month" value="{{ $month }}" class="field">
        </label>
        <label class="block">
            <span class="field-label">Tipe</span>
            <select name="type" class="field">
                <option value="">Semua</option>
                <option value="expense" @selected($type==='expense')>Pengeluaran</option>
                <option value="income" @selected($type==='income')>Pemasukan</option>
            </select>
        </label>
        <label class="block col-span-2 sm:flex-1">
            <span class="field-label">Cari catatan</span>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="kata kunci…" class="field">
        </label>
        <button class="btn-primary col-span-2 sm:col-auto sm:px-6">Terapkan</button>
    </form>

    {{-- Mobile: card list --}}
    <div class="space-y-2 sm:hidden">
        @forelse ($transactions as $t)
            <button type="button" class="card w-full p-3 flex items-center gap-3 text-left active:bg-slate-50"
                    @click='openEdit(@json($t))'>
                <span class="icon-chip" style="background-color: {{ optional($t->category)->color ?? '#94a3b8' }}1f;">{{ optional($t->category)->icon ?: '🏷️' }}</span>
                <span class="min-w-0">
                    <span class="block font-medium text-slate-800 truncate">{{ optional($t->category)->name ?? '—' }}</span>
                    <span class="block text-xs text-slate-400">{{ $t->date->format('d M Y') }}{{ $t->notes ? ' · '.$t->notes : '' }}</span>
                </span>
                <span class="ml-auto font-semibold whitespace-nowrap {{ $t->type === 'income' ? 'text-emerald-600' : 'text-slate-900' }}">
                    {{ $t->type === 'income' ? '+' : '−' }}{{ $fmt($t->amount) }}
                </span>
            </button>
        @empty
            <div class="card p-8 text-center text-slate-400 text-sm">Belum ada transaksi.</div>
        @endforelse
    </div>

    {{-- Desktop: table --}}
    <div class="card hidden sm:block overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Kategori</th>
                    <th class="px-4 py-3 font-medium">Catatan</th>
                    <th class="px-4 py-3 font-medium text-right">Jumlah</th>
                    <th class="px-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transactions as $t)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $t->date->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2">
                                <span class="icon-chip icon-chip-sm" style="background-color: {{ optional($t->category)->color ?? '#94a3b8' }}1f;">{{ optional($t->category)->icon ?: '🏷️' }}</span>
                                <span class="font-medium text-slate-800">{{ optional($t->category)->name ?? '—' }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $t->notes }}</td>
                        <td class="px-4 py-3 text-right font-semibold whitespace-nowrap {{ $t->type === 'income' ? 'text-emerald-600' : 'text-slate-900' }}">
                            {{ $t->type === 'income' ? '+' : '−' }}{{ $fmt($t->amount) }}
                        </td>
                        <td class="px-2 py-3 text-right whitespace-nowrap">
                            <button type="button" class="btn-ghost px-3" @click='openEdit(@json($t))' aria-label="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('chen.finance.transactions.destroy', $t->id) }}" class="inline"
                                  onsubmit="return confirm('Hapus transaksi ini?')">
                                @csrf @method('DELETE')
                                <button class="btn-danger px-3" aria-label="Hapus">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $transactions->links() }}</div>

    {{-- ── Bottom-sheet form ────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak>
            <div class="sheet-backdrop" @click="open=false" x-transition.opacity></div>
            <div class="sheet p-5 sm:p-6"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full sm:translate-y-0 sm:opacity-0"
                 x-transition:enter-end="translate-y-0 sm:opacity-100">
                <div class="sheet-grip"></div>
                <div class="flex items-center mb-4">
                    <h3 class="text-lg font-bold" x-text="editing ? 'Edit transaksi' : 'Transaksi baru'"></h3>
                    <button type="button" @click="open=false" class="btn-ghost ml-auto px-3" aria-label="Tutup">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" :action="action" class="space-y-4">
                    @csrf
                    <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
                    <input type="hidden" name="type" :value="type">

                    <div class="seg" role="group" aria-label="Tipe">
                        <button type="button" class="seg-btn" :aria-pressed="type==='expense'" @click="onType('expense')">Pengeluaran</button>
                        <button type="button" class="seg-btn" :aria-pressed="type==='income'"  @click="onType('income')">Pemasukan</button>
                    </div>

                    {{-- Amount — the hero field --}}
                    <div>
                        <label class="field-label" for="tx-amount">Jumlah</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-semibold">{{ $currency }}</span>
                            <input id="tx-amount" name="amount" x-model="amount" type="number" min="0" step="1" inputmode="numeric" required
                                   placeholder="0" class="field pl-14 text-2xl font-bold" style="min-height:3.5rem">
                        </div>
                    </div>

                    <div>
                        <label class="field-label" for="tx-cat">Kategori</label>
                        <template x-if="filtered.length">
                            <select id="tx-cat" name="fin_category_id" x-model="category_id" required class="field">
                                <template x-for="c in filtered" :key="c.id">
                                    <option :value="c.id" x-text="(c.icon || '🏷️') + '  ' + c.name"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="!filtered.length">
                            <a href="{{ route('chen.finance.categories.index') }}" class="block field flex items-center text-indigo-600">
                                Belum ada kategori — buat dulu →
                            </a>
                        </template>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="field-label" for="tx-date">Tanggal</label>
                            <input id="tx-date" name="date" x-model="date" type="date" required class="field">
                        </div>
                    </div>

                    <div>
                        <label class="field-label" for="tx-notes">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <textarea id="tx-notes" name="notes" x-model="notes" placeholder="mis. makan siang" class="field"></textarea>
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="open=false" class="btn-ghost btn-block">Batal</button>
                        <button class="btn-primary btn-block" :disabled="!filtered.length">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
    function txForm(categories) {
        return {
            open: false,
            editing: null,
            type: 'expense',
            category_id: '',
            date: '',
            amount: '',
            notes: '',
            categories: categories,
            get filtered() { return this.categories.filter(c => c.type === this.type); },
            firstOf(type) { const c = this.categories.find(x => x.type === type); return c ? String(c.id) : ''; },
            onType(t) {
                this.type = t;
                if (!this.filtered.some(c => String(c.id) === String(this.category_id))) this.category_id = this.firstOf(t);
            },
            openCreate() {
                this.editing = null; this.type = 'expense';
                this.category_id = this.firstOf('expense');
                this.date = '{{ date('Y-m-d') }}'; this.amount = ''; this.notes = ''; this.open = true;
            },
            openEdit(t) {
                this.editing = t; this.type = t.type;
                this.category_id = String(t.fin_category_id);
                this.date = (t.date || '').substring(0, 10); this.amount = t.amount; this.notes = t.notes || ''; this.open = true;
            },
            get action() {
                return this.editing
                    ? '{{ url('finance/transactions') }}/' + this.editing.id
                    : '{{ route('chen.finance.transactions.store') }}';
            },
        };
    }
</script>
@endpush
