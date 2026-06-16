@extends('chen::layout')
@section('title', 'Berulang — Finance')
@section('heading', 'Transaksi Berulang')
@section('content')
@php($fmt = fn ($v) => number_format((float) $v, 0, ',', '.'))
@php($freqLabel = ['weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'])
<div x-data="recurForm({{ \Illuminate\Support\Js::from($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'icon' => $c->icon, 'color' => $c->color])->values()) }})" class="space-y-5">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight">Transaksi Berulang</h1>
            <p class="text-sm text-slate-500">Otomatis dibuat saat jatuh tempo.</p>
        </div>
        <button type="button" @click="openCreate()" class="btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Tambah
        </button>
    </div>

    <div class="space-y-2">
        @forelse ($rules as $r)
            <div class="card p-3 flex items-center gap-3">
                <span class="icon-chip" style="background-color: {{ optional($r->category)->color ?? '#94a3b8' }}1f;">{{ optional($r->category)->icon ?: '🔁' }}</span>
                <div class="min-w-0">
                    <p class="font-medium text-slate-800 truncate">{{ optional($r->category)->name ?? '—' }}</p>
                    <p class="text-xs text-slate-400">
                        {{ $freqLabel[$r->frequency] ?? $r->frequency }} · berikutnya {{ $r->next_run_date->format('d M Y') }}
                    </p>
                </div>
                <div class="ml-auto text-right">
                    <p class="font-semibold whitespace-nowrap {{ $r->type === 'income' ? 'text-emerald-600' : 'text-slate-900' }}">
                        {{ $r->type === 'income' ? '+' : '−' }}{{ $fmt($r->amount) }}
                    </p>
                    <form method="POST" action="{{ route('chen.finance.recurring.toggle', $r->id) }}" class="mt-1">
                        @csrf @method('PATCH')
                        <button class="text-xs font-semibold rounded-full px-2.5 py-1 {{ $r->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $r->active ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </form>
                </div>
                <div class="flex flex-col gap-1">
                    <button type="button" class="btn-ghost px-3" @click='openEdit(@json($r))' aria-label="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </button>
                    <form method="POST" action="{{ route('chen.finance.recurring.destroy', $r->id) }}"
                          onsubmit="return confirm('Hapus aturan ini?')">
                        @csrf @method('DELETE')
                        <button class="btn-danger px-3" aria-label="Hapus">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card p-8 text-center text-slate-400 text-sm">Belum ada aturan berulang.</div>
        @endforelse
    </div>

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
                    <h3 class="text-lg font-bold" x-text="editing ? 'Edit aturan' : 'Aturan berulang'"></h3>
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

                    <div>
                        <label class="field-label" for="rc-amount">Jumlah</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-semibold">{{ $currency }}</span>
                            <input id="rc-amount" name="amount" x-model="amount" type="number" min="0" step="1" inputmode="numeric" required placeholder="0" class="field pl-14 text-xl font-bold">
                        </div>
                    </div>

                    <div>
                        <label class="field-label" for="rc-cat">Kategori</label>
                        <template x-if="filtered.length">
                            <select id="rc-cat" name="fin_category_id" x-model="category_id" required class="field">
                                <template x-for="c in filtered" :key="c.id">
                                    <option :value="c.id" x-text="(c.icon || '🏷️') + '  ' + c.name"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="!filtered.length">
                            <a href="{{ route('chen.finance.categories.index') }}" class="block field flex items-center text-indigo-600">Belum ada kategori — buat dulu →</a>
                        </template>
                    </div>

                    <div>
                        <label class="field-label" for="rc-freq">Frekuensi</label>
                        <select id="rc-freq" name="frequency" x-model="frequency" class="field">
                            <option value="monthly">Bulanan</option>
                            <option value="weekly">Mingguan</option>
                            <option value="yearly">Tahunan</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="field-label" for="rc-start">Mulai</label>
                            <input id="rc-start" name="start_date" x-model="start_date" type="date" required class="field">
                        </div>
                        <div>
                            <label class="field-label" for="rc-end">Berakhir <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input id="rc-end" name="end_date" x-model="end_date" type="date" class="field">
                        </div>
                    </div>

                    <div>
                        <label class="field-label" for="rc-notes">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <textarea id="rc-notes" name="notes" x-model="notes" class="field"></textarea>
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
    function recurForm(categories) {
        return {
            open: false, editing: null,
            type: 'expense', category_id: '', amount: '', frequency: 'monthly', start_date: '', end_date: '', notes: '',
            categories: categories,
            get filtered() { return this.categories.filter(c => c.type === this.type); },
            firstOf(type) { const c = this.categories.find(x => x.type === type); return c ? String(c.id) : ''; },
            onType(t) { this.type = t; if (!this.filtered.some(c => String(c.id) === String(this.category_id))) this.category_id = this.firstOf(t); },
            openCreate() {
                this.editing = null; this.type = 'expense'; this.category_id = this.firstOf('expense');
                this.amount = ''; this.frequency = 'monthly'; this.start_date = '{{ date('Y-m-d') }}'; this.end_date = ''; this.notes = ''; this.open = true;
            },
            openEdit(r) {
                this.editing = r; this.type = r.type; this.category_id = String(r.fin_category_id);
                this.amount = r.amount; this.frequency = r.frequency;
                this.start_date = (r.start_date || '').substring(0, 10);
                this.end_date = r.end_date ? r.end_date.substring(0, 10) : '';
                this.notes = r.notes || ''; this.open = true;
            },
            get action() {
                return this.editing
                    ? '{{ url('finance/recurring') }}/' + this.editing.id
                    : '{{ route('chen.finance.recurring.store') }}';
            },
        };
    }
</script>
@endpush
