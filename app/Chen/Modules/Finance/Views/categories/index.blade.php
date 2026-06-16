@extends('chen::layout')
@section('title', 'Kategori — Finance')
@section('heading', 'Kategori')
@section('content')
<div x-data="categoryForm()" class="space-y-5">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight">Kategori</h1>
            <p class="text-sm text-slate-500">Kelompokkan transaksi kamu.</p>
        </div>
        <button type="button" @click="openCreate()" class="btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Tambah
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach (['expense' => 'Pengeluaran', 'income' => 'Pemasukan'] as $type => $label)
            <section class="card p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-slate-500">{{ $label }}</h2>
                    <span class="text-xs text-slate-400">{{ $categories->where('type', $type)->count() }}</span>
                </div>
                <ul class="space-y-1">
                    @forelse ($categories->where('type', $type) as $cat)
                        <li class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-slate-50">
                            <span class="icon-chip" style="background-color: {{ $cat->color }}1f;">{{ $cat->icon ?: '🏷️' }}</span>
                            <span class="font-medium text-slate-800 truncate">{{ $cat->name }}</span>
                            <span class="ml-auto flex items-center gap-1">
                                <button type="button" class="btn-ghost px-3" @click='openEdit(@json($cat))' aria-label="Edit {{ $cat->name }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('chen.finance.categories.destroy', $cat->id) }}"
                                      onsubmit="return confirm('Hapus kategori “{{ $cat->name }}”?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger px-3" aria-label="Hapus {{ $cat->name }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </form>
                            </span>
                        </li>
                    @empty
                        <li class="px-2 py-6 text-center text-sm text-slate-400">Belum ada kategori {{ strtolower($label) }}.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>

    {{-- ── Bottom-sheet form ────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak>
            <div class="sheet-backdrop" @click="open = false" x-transition.opacity></div>
            <div class="sheet p-5 sm:p-6"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full sm:translate-y-0 sm:opacity-0"
                 x-transition:enter-end="translate-y-0 sm:opacity-100">
                <div class="sheet-grip"></div>
                <div class="flex items-center gap-3 mb-5">
                    <span class="icon-chip" :style="`background-color: ${color}1f`" x-text="icon"></span>
                    <h3 class="text-lg font-bold" x-text="editing ? 'Edit kategori' : 'Kategori baru'"></h3>
                    <button type="button" @click="open=false" class="btn-ghost ml-auto px-3" aria-label="Tutup">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" :action="action" class="space-y-5">
                    @csrf
                    <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
                    <input type="hidden" name="type" :value="type">
                    <input type="hidden" name="icon" :value="icon">
                    <input type="hidden" name="color" :value="color">

                    {{-- Type --}}
                    <div class="seg" role="group" aria-label="Tipe">
                        <button type="button" class="seg-btn" :aria-pressed="type==='expense'" @click="type='expense'">Pengeluaran</button>
                        <button type="button" class="seg-btn" :aria-pressed="type==='income'"  @click="type='income'">Pemasukan</button>
                    </div>

                    {{-- Name --}}
                    <div>
                        <label class="field-label" for="cat-name">Nama</label>
                        <input id="cat-name" name="name" x-model="name" required maxlength="255" placeholder="mis. Makan, Transport, Gaji" class="field">
                    </div>

                    {{-- Icon chooser --}}
                    <div>
                        <span class="field-label">Ikon</span>
                        <div class="grid grid-cols-7 gap-2 sm:grid-cols-8">
                            <template x-for="ic in icons" :key="ic">
                                <button type="button" class="pick" :aria-pressed="icon===ic" @click="icon=ic" x-text="ic"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Colour --}}
                    <div>
                        <span class="field-label">Warna</span>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <template x-for="c in colors" :key="c">
                                <button type="button" class="swatch" :aria-pressed="color===c" :style="`background-color:${c}`" @click="color=c" :aria-label="c"></button>
                            </template>
                            <label class="swatch relative overflow-hidden cursor-pointer" :style="`background-color:${color}`" aria-label="Warna khusus">
                                <input type="color" x-model="color" class="absolute inset-0 h-full w-full opacity-0 cursor-pointer">
                            </label>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="open=false" class="btn-ghost btn-block">Batal</button>
                        <button class="btn-primary btn-block">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
    function categoryForm() {
        return {
            open: false,
            editing: null,
            type: 'expense',
            name: '',
            icon: '🏷️',
            color: '#6366f1',
            icons: ['🍔','🛒','🚗','⛽','🏠','💡','📱','🎬','🎮','👕','💊','🏥','🎓','✈️','🐾','🎁','☕','🍺','💇','🧾','🍜','🚌','🏋️','💰','💵','🏦','💼','📈','🎯','🪙'],
            colors: ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#ec4899','#8b5cf6','#14b8a6','#64748b','#0f172a'],
            openCreate() { this.editing = null; this.type = 'expense'; this.name = ''; this.icon = '🏷️'; this.color = '#6366f1'; this.open = true; },
            openEdit(c) { this.editing = c; this.type = c.type; this.name = c.name; this.icon = c.icon || '🏷️'; this.color = c.color || '#6366f1'; this.open = true; },
            get action() {
                return this.editing
                    ? '{{ url('finance/categories') }}/' + this.editing.id
                    : '{{ route('chen.finance.categories.store') }}';
            },
        };
    }
</script>
@endpush
