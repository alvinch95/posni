@extends('dashboard.layouts.main')

@section('container')
@php
    $money = function ($amount) {
        return 'Rp ' . number_format((int) $amount, 0, ',', '.');
    };
    $sortOptions = ['sort_order' => 'Urutan', 'name_id' => 'Nama', 'created_at' => 'Terbaru'];
    $statusOptions = ['all' => 'Semua status', 'published' => 'Terbit', 'draft' => 'Draft'];
    // What every list link (sort, pagination) carries besides its own parameters.
    $filterParams = ['search' => $search, 'status' => $status !== 'all' ? $status : null, 'serie' => $serie];
    $filtered = $search !== '' || $status !== 'all' || $serie !== null;
    $emptyText = $filtered ? 'Tidak ada produk yang cocok.' : 'Belum ada produk.';
@endphp

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Produk Website</h1>
</div>

<div class="col-xl-11">
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('website.products.create') }}" class="btn btn-primary flex-fill flex-md-grow-0 d-inline-flex align-items-center justify-content-center" style="min-height: 44px;">Tambah produk</a>
        <a href="{{ route('website.products.import') }}" class="btn btn-outline-primary flex-fill flex-md-grow-0 d-inline-flex align-items-center justify-content-center" style="min-height: 44px;">Import dari hampers</a>
    </div>

    <form action="{{ route('website.products.index') }}" method="get" class="d-flex flex-column flex-md-row gap-2 mb-3" role="search">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        <div class="input-group flex-grow-1">
            <input type="search" name="search" value="{{ $search }}" class="form-control" style="min-height: 44px;"
                   placeholder="Cari nama produk" aria-label="Cari nama produk">
            <button type="submit" class="btn btn-outline-secondary" style="min-height: 44px;">Cari</button>
        </div>
        <select name="status" class="form-select wp-filter" style="min-height: 44px;" aria-label="Filter status">
            @foreach ($statusOptions as $value => $caption)
                <option value="{{ $value }}"{{ $status === $value ? " selected" : "" }}>{{ $caption }}</option>
            @endforeach
        </select>
        <select name="serie" class="form-select wp-filter" style="min-height: 44px;" aria-label="Filter serie">
            <option value="">Semua serie</option>
            @foreach ($series as $option)
                <option value="{{ $option->id }}"{{ $serie === (int) $option->id ? " selected" : "" }}>{{ $option->name }}</option>
            @endforeach
        </select>
        @if ($filtered)
            <a href="{{ route('website.products.index', ['sort' => $sort, 'direction' => $direction]) }}"
               class="btn btn-link d-inline-flex align-items-center justify-content-center" style="min-height: 44px;">Reset</a>
        @endif
    </form>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="text-muted">Urutkan:</span>
        @foreach ($sortOptions as $column => $caption)
            @php
                $active = $sort === $column;
                $nextDirection = $active && $direction === 'asc' ? 'desc' : 'asc';
            @endphp
            <a href="{{ route('website.products.index', array_filter($filterParams + ['sort' => $column, 'direction' => $nextDirection])) }}"
               class="btn btn-sm {{ $active ? 'btn-dark' : 'btn-outline-secondary' }} d-inline-flex align-items-center"
               style="min-height: 44px;" @if ($active) aria-current="true" @endif>
                {{ $caption }}@if ($active) {{ $direction === 'asc' ? '▲' : '▼' }}@endif
            </a>
        @endforeach
    </div>

    @if ($products->count() > 0)
        {{-- The checkboxes of the phone cards and of the table sit outside this form and point at it with form="wp-bulk-form". --}}
        <form id="wp-bulk-form" action="{{ route('website.products.bulk') }}" method="post" class="d-none">@csrf</form>

        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3" role="group" aria-label="Aksi massal">
            <label class="d-inline-flex align-items-center gap-2 m-0" style="min-height: 44px;">
                <input type="checkbox" id="wp-select-all" class="form-check-input m-0" style="width: 24px; height: 24px;">
                <span>Pilih semua di halaman ini</span>
            </label>
            <span class="text-muted" id="wp-selected-count" aria-live="polite">Belum ada yang dipilih</span>
            <div class="d-flex gap-2 ms-sm-auto">
                <button type="submit" form="wp-bulk-form" name="action" value="publish" class="btn btn-success flex-fill wp-bulk" style="min-height: 44px; min-width: 110px;">Publish</button>
                <button type="submit" form="wp-bulk-form" name="action" value="unpublish" class="btn btn-outline-secondary flex-fill wp-bulk" style="min-height: 44px; min-width: 110px;">Unpublish</button>
            </div>
        </div>
    @endif

    {{-- Phones and tablets: one card per product (an 8-column table does not fit). --}}
    <style>
        /* Bootstrap badges are 0.75em; keep the card text at 14px or more on phones. */
        .wp-cards .badge { font-size: .875rem; }
    </style>
    <div class="d-lg-none wp-cards">
        @forelse ($products as $product)
            @php $row = $rows[$product->id]; @endphp
            @component('dashboard.website._partials.list_card', [
                'editUrl' => route('website.products.edit', $product),
                'destroyUrl' => route('website.products.destroy', $product),
                'thumb' => $row['thumb'],
                'thumbAlt' => $product->name_id,
                'selectValue' => $product->id,
                'selectForm' => 'wp-bulk-form',
                'selectLabel' => 'Pilih ' . $product->name_id,
                'deleteData' => [
                    'confirm-title' => 'Hapus produk?',
                    'confirm-text' => 'Produk "' . $product->name_id . '" akan dihapus dari website.',
                ],
            ])
                <div class="fw-bold">{{ $product->name_id }}</div>
                @if (filled($product->name_en))
                    <div class="text-muted">{{ $product->name_en }}</div>
                @endif
                <div class="mt-1"><code style="font-size: 1em;">{{ $product->slug }}</code></div>

                @forelse ($row['options'] as $option)
                    <div class="mt-1">
                        <span class="text-muted">{{ $option['label'] }}:</span>
                        @if ($option['hamper'] !== null)
                            {{ $option['hamper'] }}
                            <span class="badge {{ $option['stock'] > 0 ? 'bg-success' : 'bg-secondary' }}">{{ $option['stock'] > 0 ? 'Stok ' . $option['stock'] : 'Habis' }}</span>
                        @else
                            <span class="text-danger">hamper dihapus di posni</span>
                        @endif
                    </div>
                @empty
                    <div class="mt-1 text-muted">Belum ada hamper.</div>
                @endforelse

                @if ($row['priceFrom'] !== null)
                    <div class="mt-1 fw-semibold">Mulai {{ $money($row['priceFrom']) }}</div>
                @endif

                <div class="mt-2 d-flex flex-wrap gap-1">
                    <span class="badge {{ $product->is_published ? 'bg-success' : 'bg-secondary' }}">{{ $product->is_published ? 'Terbit' : 'Draft' }}</span>
                    @if ($product->is_bestseller)<span class="badge bg-warning text-dark">Bestseller</span>@endif
                    @if ($product->is_featured)<span class="badge bg-info text-dark">Unggulan</span>@endif
                    @if ($row['hidden'])<span class="badge bg-danger">Tidak tampil di website</span>@endif
                </div>
                <div class="text-muted mt-1">urutan {{ $product->sort_order }}</div>
            @endcomponent
        @empty
            <p class="text-center text-muted py-4">{{ $emptyText }}</p>
        @endforelse
    </div>

    {{-- Desktop: the table. --}}
    <div class="table-responsive d-none d-lg-block">
        <table class="table table-striped table-sm align-middle">
            <thead>
                <tr>
                    <th scope="col"><span class="visually-hidden">Pilih</span></th>
                    <th scope="col">Gambar</th>
                    <th scope="col">Nama</th>
                    <th scope="col">Hamper</th>
                    <th scope="col">Harga mulai</th>
                    <th scope="col">Stok</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Urutan</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $row = $rows[$product->id]; @endphp
                    <tr>
                        <td>
                            <label class="d-flex align-items-center justify-content-center m-0" style="min-width: 44px; min-height: 44px;">
                                <input type="checkbox" name="ids[]" value="{{ $product->id }}" form="wp-bulk-form" class="form-check-input wp-select m-0"
                                       style="width: 22px; height: 22px;" aria-label="Pilih {{ $product->name_id }}">
                            </label>
                        </td>
                        <td>
                            @if (filled($row['thumb']))
                                <img src="{{ asset('storage/' . ltrim($row['thumb'], '/')) }}" alt="{{ $product->name_id }}"
                                     width="48" height="48" loading="lazy" class="rounded border" style="object-fit: cover;">
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-break">
                            {{ $product->name_id }}
                            @if (filled($product->name_en))
                                <div class="small text-muted">{{ $product->name_en }}</div>
                            @endif
                            <div class="small"><code>{{ $product->slug }}</code></div>
                        </td>
                        <td class="text-break">
                            @forelse ($row['options'] as $option)
                                <div>
                                    <span class="text-muted">{{ $option['label'] }}:</span>
                                    @if ($option['hamper'] !== null)
                                        {{ $option['hamper'] }}
                                    @else
                                        <span class="text-danger">hamper dihapus di posni</span>
                                    @endif
                                </div>
                            @empty
                                <span class="text-muted">Belum ada hamper.</span>
                            @endforelse
                        </td>
                        <td class="text-nowrap">{{ $row['priceFrom'] !== null ? $money($row['priceFrom']) : '-' }}</td>
                        <td class="text-nowrap">
                            @forelse ($row['options'] as $option)
                                <div>
                                    <span class="text-muted">{{ $option['label'] }}:</span>
                                    @if ($option['stock'] === null)
                                        -
                                    @else
                                        <span class="badge {{ $option['stock'] > 0 ? 'bg-success' : 'bg-secondary' }}">{{ $option['stock'] > 0 ? 'Stok ' . $option['stock'] : 'Habis' }}</span>
                                    @endif
                                </div>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge {{ $product->is_published ? 'bg-success' : 'bg-secondary' }}">{{ $product->is_published ? 'Terbit' : 'Draft' }}</span>
                                @if ($product->is_bestseller)<span class="badge bg-warning text-dark">Bestseller</span>@endif
                                @if ($product->is_featured)<span class="badge bg-info text-dark">Unggulan</span>@endif
                                @if ($row['hidden'])<span class="badge bg-danger">Tidak tampil di website</span>@endif
                            </div>
                        </td>
                        <td class="text-end">{{ $product->sort_order }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('website.products.edit', $product) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('website.products.destroy', $product) }}" method="post" class="d-inline">
                                @method('delete')
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger hapus"
                                        data-confirm-title="Hapus produk?"
                                        data-confirm-text="Produk &quot;{{ $product->name_id }}&quot; akan dihapus dari website.">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">{{ $emptyText }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</div>

@include('dashboard.website._partials.delete_confirm')

@if ($products->count() > 0)
<script>
    // Bulk selection. A product has a checkbox in the phone card list and one in the table (only one
    // of them is visible), so ticking one ticks its twin; the server ignores duplicate ids.
    (function () {
        var form = document.getElementById('wp-bulk-form');
        var all = document.getElementById('wp-select-all');
        var counter = document.getElementById('wp-selected-count');
        if (!form || !all || !counter) { return; }

        var boxes = Array.prototype.slice.call(document.querySelectorAll('input.wp-select'));
        var buttons = Array.prototype.slice.call(document.querySelectorAll('.wp-bulk'));

        function unique(onlyChecked) {
            var seen = {};
            boxes.forEach(function (box) {
                if (!onlyChecked || box.checked) { seen[box.value] = true; }
            });
            return Object.keys(seen).length;
        }

        function refresh() {
            var chosen = unique(true);
            var total = unique(false);
            counter.textContent = chosen === 0 ? 'Belum ada yang dipilih' : chosen + ' produk dipilih';
            buttons.forEach(function (button) { button.disabled = chosen === 0; });
            all.checked = chosen > 0 && chosen === total;
            all.indeterminate = chosen > 0 && chosen < total;
        }

        boxes.forEach(function (box) {
            box.addEventListener('change', function () {
                boxes.forEach(function (other) {
                    if (other.value === box.value) { other.checked = box.checked; }
                });
                refresh();
            });
        });

        all.addEventListener('change', function () {
            boxes.forEach(function (box) { box.checked = all.checked; });
            refresh();
        });

        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var chosen = unique(true);
                if (chosen === 0) { return; }

                var publish = button.value === 'publish';
                var send = function () {
                    var action = document.createElement('input');
                    action.type = 'hidden';
                    action.name = 'action';
                    action.value = button.value;
                    form.appendChild(action);
                    form.submit();
                };
                var title = (publish ? 'Publish ' : 'Unpublish ') + chosen + ' produk?';
                var text = publish
                    ? 'Produk yang punya hamper valid akan tampil di website. Yang belum valid dilewati.'
                    : 'Produk akan disembunyikan dari website dan jadi draft.';

                if (window.Swal) {
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: publish ? 'Ya, publish' : 'Ya, unpublish',
                        cancelButtonText: 'Batal'
                    }).then(function (result) { if (result.isConfirmed) { send(); } });
                } else if (window.confirm(title + ' ' + text)) {
                    send();
                }
            });
        });

        refresh();
    })();
</script>
@endif

<script>
    // The status and serie selects apply as soon as they change (the Cari button still works).
    Array.prototype.forEach.call(document.querySelectorAll('select.wp-filter'), function (select) {
        select.addEventListener('change', function () { select.form.submit(); });
    });
</script>
@endsection
