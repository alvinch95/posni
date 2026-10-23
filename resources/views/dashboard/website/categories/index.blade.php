@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kategori Website</h1>
</div>

<div class="col-lg-10">
    <a href="{{ route('website.categories.create') }}" class="btn btn-primary mb-3 d-inline-flex align-items-center" style="min-height: 44px;">Tambah kategori</a>

    {{-- Phones: one card per category (a 7-column table does not fit). --}}
    <div class="d-md-none">
        @forelse ($categories as $category)
            <div class="card mb-2">
                <div class="card-body d-flex gap-3 p-3">
                    @if ($category->image)
                        <img src="{{ asset('storage/' . ltrim($category->image, '/')) }}" alt="{{ $category->name_id }}"
                             width="56" height="56" class="rounded border flex-shrink-0" style="object-fit: cover;">
                    @endif
                    <div class="flex-grow-1 text-break">
                        <div class="fw-bold">{{ $category->name_id }}</div>
                        @if (filled($category->name_en))
                            <div class="text-muted">{{ $category->name_en }}</div>
                        @endif
                        <div class="mt-1"><code style="font-size: 1em;">{{ $category->slug }}</code></div>
                        <div class="text-muted mt-1">
                            {{ $category->is_active ? 'Aktif' : 'Nonaktif' }} · {{ $category->products_count }} produk · urutan {{ $category->sort_order }}
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 px-3 pb-3">
                    <a href="{{ route('website.categories.edit', $category) }}" class="btn btn-warning flex-fill d-inline-flex align-items-center justify-content-center" style="min-height: 44px;">Edit</a>
                    <form action="{{ route('website.categories.destroy', $category) }}" method="post" class="d-flex flex-fill">
                        @method('delete')
                        @csrf
                        <button type="submit" class="btn btn-danger hapus flex-fill" style="min-height: 44px;"
                                data-name="{{ $category->name_id }}" data-products="{{ $category->products_count }}">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-center text-muted py-4">Belum ada kategori.</p>
        @endforelse
    </div>

    {{-- Tablet and desktop: the table. --}}
    <div class="table-responsive d-none d-md-block">
        <table class="table table-striped table-sm align-middle">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Gambar</th>
                    <th scope="col">Nama</th>
                    <th scope="col">Slug</th>
                    <th scope="col" class="text-end">Produk</th>
                    <th scope="col" class="text-end">Urutan</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @if ($category->image)
                                <img src="{{ asset('storage/' . ltrim($category->image, '/')) }}" alt="{{ $category->name_id }}"
                                     width="48" height="48" class="rounded border" style="object-fit: cover;">
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            {{ $category->name_id }}
                            @if (filled($category->name_en))
                                <div class="small text-muted">{{ $category->name_en }}</div>
                            @endif
                        </td>
                        <td><code>{{ $category->slug }}</code></td>
                        <td class="text-end">{{ $category->products_count }}</td>
                        <td class="text-end">{{ $category->sort_order }}</td>
                        <td>
                            @if ($category->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('website.categories.edit', $category) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('website.categories.destroy', $category) }}" method="post" class="d-inline">
                                @method('delete')
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger hapus"
                                        data-name="{{ $category->name_id }}" data-products="{{ $category->products_count }}">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada kategori.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.hapus').click(function (e) {
            e.preventDefault();
            var form = $(this).parents('form');
            var products = parseInt($(this).data('products'), 10) || 0;
            Swal.fire({
                title: 'Hapus kategori?',
                text: products > 0
                    ? 'Kategori "' + $(this).data('name') + '" akan dilepas dari ' + products + ' produk. Produknya tidak ikut terhapus.'
                    : 'Kategori "' + $(this).data('name') + '" akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
