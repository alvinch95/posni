@extends('dashboard.layouts.main')

@section('container')
@php
    $money = function ($amount) {
        return 'Rp ' . number_format((int) $amount, 0, ',', '.');
    };
    $sortOptions = ['sort_order' => 'Urutan', 'name_id' => 'Nama', 'created_at' => 'Terbaru'];
@endphp

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Produk Website</h1>
</div>

<div class="col-xl-11">
    <div class="d-flex flex-column flex-md-row gap-2 mb-3">
        <a href="{{ route('website.products.create') }}" class="btn btn-primary d-inline-flex align-items-center justify-content-center" style="min-height: 44px;">Tambah produk</a>

        <form action="{{ route('website.products.index') }}" method="get" class="flex-grow-1" role="search">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <div class="input-group">
                <input type="search" name="search" value="{{ $search }}" class="form-control" style="min-height: 44px;"
                       placeholder="Cari nama produk" aria-label="Cari nama produk">
                <button type="submit" class="btn btn-outline-secondary" style="min-height: 44px;">Cari</button>
            </div>
        </form>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="text-muted">Urutkan:</span>
        @foreach ($sortOptions as $column => $caption)
            @php
                $active = $sort === $column;
                $nextDirection = $active && $direction === 'asc' ? 'desc' : 'asc';
            @endphp
            <a href="{{ route('website.products.index', array_filter(['search' => $search, 'sort' => $column, 'direction' => $nextDirection])) }}"
               class="btn btn-sm {{ $active ? 'btn-dark' : 'btn-outline-secondary' }} d-inline-flex align-items-center"
               style="min-height: 44px;" @if ($active) aria-current="true" @endif>
                {{ $caption }}@if ($active) {{ $direction === 'asc' ? '▲' : '▼' }}@endif
            </a>
        @endforeach
    </div>

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
                    <span class="badge {{ $product->is_published ? 'bg-success' : 'bg-secondary' }}">{{ $product->is_published ? 'Terbit' : 'Draf' }}</span>
                    @if ($product->is_bestseller)<span class="badge bg-warning text-dark">Bestseller</span>@endif
                    @if ($product->is_featured)<span class="badge bg-info text-dark">Unggulan</span>@endif
                    @if ($row['hidden'])<span class="badge bg-danger">Tidak tampil di website</span>@endif
                </div>
                <div class="text-muted mt-1">urutan {{ $product->sort_order }}</div>
            @endcomponent
        @empty
            <p class="text-center text-muted py-4">{{ $search !== '' ? 'Tidak ada produk yang cocok.' : 'Belum ada produk.' }}</p>
        @endforelse
    </div>

    {{-- Desktop: the table. --}}
    <div class="table-responsive d-none d-lg-block">
        <table class="table table-striped table-sm align-middle">
            <thead>
                <tr>
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
                                <span class="badge {{ $product->is_published ? 'bg-success' : 'bg-secondary' }}">{{ $product->is_published ? 'Terbit' : 'Draf' }}</span>
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
                        <td colspan="8" class="text-center text-muted py-4">{{ $search !== '' ? 'Tidak ada produk yang cocok.' : 'Belum ada produk.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</div>

@include('dashboard.website._partials.delete_confirm')
@endsection
