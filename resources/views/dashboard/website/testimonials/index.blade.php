@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Testimoni Website</h1>
</div>

<div class="col-lg-10">
    <a href="{{ route('website.testimonials.create') }}" class="btn btn-primary mb-3 d-inline-flex align-items-center" style="min-height: 44px;">Tambah testimoni</a>

    {{-- Phones: one card per testimonial (a 7-column table does not fit). --}}
    <div class="d-md-none">
        @forelse ($testimonials as $testimonial)
            @component('dashboard.website._partials.list_card', [
                'editUrl' => route('website.testimonials.edit', $testimonial),
                'destroyUrl' => route('website.testimonials.destroy', $testimonial),
                'thumb' => $testimonial->photo,
                'thumbAlt' => $testimonial->name,
                'deleteData' => [
                    'confirm-title' => 'Hapus testimoni?',
                    'confirm-text' => 'Testimoni dari "' . $testimonial->name . '" akan dihapus.',
                ],
            ])
                <div class="fw-bold">{{ $testimonial->name }}</div>
                @if (filled($testimonial->occasion_id))
                    <div class="text-muted">{{ $testimonial->occasion_id }}</div>
                @endif
                <div class="mt-1">{{ \Illuminate\Support\Str::limit($testimonial->quote_id, 120) }}</div>
                <div class="text-muted mt-1">
                    {{ $testimonial->is_active ? 'Aktif' : 'Nonaktif' }} · urutan {{ $testimonial->sort_order }}
                </div>
            @endcomponent
        @empty
            <p class="text-center text-muted py-4">Belum ada testimoni.</p>
        @endforelse
    </div>

    {{-- Tablet and desktop: the table. --}}
    <div class="table-responsive d-none d-md-block">
        <table class="table table-striped table-sm align-middle">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Foto</th>
                    <th scope="col">Nama</th>
                    <th scope="col">Kutipan</th>
                    <th scope="col" class="text-end">Urutan</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($testimonials as $testimonial)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @if ($testimonial->photo)
                                <img src="{{ asset('storage/' . ltrim($testimonial->photo, '/')) }}" alt="{{ $testimonial->name }}"
                                     width="48" height="48" loading="lazy" class="rounded border" style="object-fit: cover;">
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-break">
                            {{ $testimonial->name }}
                            @if (filled($testimonial->occasion_id))
                                <div class="small text-muted">{{ $testimonial->occasion_id }}</div>
                            @endif
                        </td>
                        <td class="text-break">{{ \Illuminate\Support\Str::limit($testimonial->quote_id, 100) }}</td>
                        <td class="text-end">{{ $testimonial->sort_order }}</td>
                        <td>
                            @if ($testimonial->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('website.testimonials.edit', $testimonial) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('website.testimonials.destroy', $testimonial) }}" method="post" class="d-inline">
                                @method('delete')
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger hapus"
                                        data-confirm-title="Hapus testimoni?"
                                        data-confirm-text="Testimoni dari &quot;{{ $testimonial->name }}&quot; akan dihapus.">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada testimoni.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('dashboard.website._partials.delete_confirm')
@endsection
