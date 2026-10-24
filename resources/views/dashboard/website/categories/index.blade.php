@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kategori Website</h1>
</div>

<div class="col-xl-10">
    <a href="{{ route('website.categories.create') }}" class="btn btn-primary mb-3 d-inline-flex align-items-center" style="min-height: 44px;">Tambah kategori</a>

    {{-- Phones: one card per category (a 7-column table does not fit). --}}
    <div class="d-md-none">
        @forelse ($categories as $category)
            @component('dashboard.website._partials.list_card', [
                'editUrl' => route('website.categories.edit', $category),
                'destroyUrl' => route('website.categories.destroy', $category),
                'thumb' => $category->image,
                'thumbAlt' => $category->name_id,
                'deleteData' => ['name' => $category->name_id, 'products' => $category->products_count],
            ])
                <div class="fw-bold">{{ $category->name_id }}</div>
                @if (filled($category->name_en))
                    <div class="text-muted">{{ $category->name_en }}</div>
                @endif
                <div class="mt-1"><code style="font-size: 1em;">{{ $category->slug }}</code></div>
                <div class="text-muted mt-1">
                    {{ $category->is_active ? 'Aktif' : 'Nonaktif' }} · {{ $category->products_count }} produk · urutan {{ $category->sort_order }}
                </div>
            @endcomponent
        @empty
            <p class="text-center text-muted py-4">Belum ada kategori.</p>
        @endforelse
    </div>

    {{-- Tablet and desktop: the table. --}}
    <div class="card mb-4 d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
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
