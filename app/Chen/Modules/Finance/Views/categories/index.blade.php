@extends('chen::layout')
@section('title', 'Kategori — Finance')
@section('heading', 'Kategori')
@section('content')
<div x-data="{ open: false, edit: null }" class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Kategori</h1>
        <button @click="open = true; edit = null"
                class="bg-slate-900 text-white text-sm rounded-lg px-3 py-2 hover:bg-slate-800">+ Tambah</button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach (['expense' => 'Pengeluaran', 'income' => 'Pemasukan'] as $type => $label)
            <div class="rounded-2xl bg-white border border-slate-200 p-4">
                <h2 class="text-sm font-semibold text-slate-500 mb-2">{{ $label }}</h2>
                <ul class="space-y-1">
                    @forelse ($categories->where('type', $type) as $cat)
                        <li class="flex items-center gap-2 py-1.5">
                            <span class="w-3 h-3 rounded-full" style="background: {{ $cat->color }}"></span>
                            <span class="text-sm">{{ $cat->name }}</span>
                            <span class="ml-auto flex gap-2">
                                <button class="text-xs text-slate-500 hover:text-slate-900"
                                        @click='edit = @json($cat); open = true'>Edit</button>
                                <form method="POST" action="{{ route('chen.finance.categories.destroy', $cat->id) }}"
                                      onsubmit="return confirm('Hapus kategori ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-rose-500 hover:text-rose-700">Hapus</button>
                                </form>
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400 py-1.5">Belum ada.</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>

    {{-- Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-md p-5">
            <h3 class="font-semibold mb-3" x-text="edit ? 'Edit Kategori' : 'Tambah Kategori'"></h3>
            <form method="POST"
                  :action="edit ? '{{ url('finance/categories') }}/' + edit.id : '{{ route('chen.finance.categories.store') }}'">
                @csrf
                <template x-if="edit"><input type="hidden" name="_method" value="PUT"></template>
                <div class="space-y-3">
                    <select name="type" x-bind:value="edit ? edit.type : 'expense'" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                    </select>
                    <input name="name" :value="edit ? edit.name : ''" placeholder="Nama kategori" required
                           class="w-full rounded-lg border-slate-300 text-sm">
                    <input type="color" name="color" :value="edit ? edit.color : '#64748b'"
                           class="w-16 h-9 rounded border-slate-300">
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
