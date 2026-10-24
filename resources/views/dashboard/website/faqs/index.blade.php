@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">FAQ Website</h1>
</div>

<div class="col-xl-10">
    <a href="{{ route('website.faqs.create') }}" class="btn btn-primary mb-3 d-inline-flex align-items-center" style="min-height: 44px;">Tambah FAQ</a>

    {{-- Phones and tablets: one card per question (a 6-column table does not fit). --}}
    <div class="d-lg-none">
        @forelse ($faqs as $faq)
            @component('dashboard.website._partials.list_card', [
                'editUrl' => route('website.faqs.edit', $faq),
                'destroyUrl' => route('website.faqs.destroy', $faq),
                'deleteData' => [
                    'confirm-title' => 'Hapus FAQ?',
                    'confirm-text' => 'FAQ "' . \Illuminate\Support\Str::limit($faq->question_id, 80) . '" akan dihapus.',
                ],
            ])
                <div class="fw-bold">{{ $faq->question_id }}</div>
                <div class="mt-1">{{ \Illuminate\Support\Str::limit($faq->answer_id, 120) }}</div>
                <div class="text-muted mt-1">
                    {{ $faq->is_active ? 'Aktif' : 'Nonaktif' }} · urutan {{ $faq->sort_order }}
                </div>
            @endcomponent
        @empty
            <p class="text-center text-muted py-4">Belum ada FAQ.</p>
        @endforelse
    </div>

    {{-- Desktop (lg and up): the table. Below that it would scroll sideways beside the sidebar. --}}
    <div class="card mb-4 d-none d-lg-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Pertanyaan</th>
                            <th scope="col">Jawaban</th>
                            <th scope="col" class="text-end">Urutan</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($faqs as $faq)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="text-break">
                                    {{ $faq->question_id }}
                                    @if (filled($faq->question_en))
                                        <div class="small text-muted">{{ $faq->question_en }}</div>
                                    @endif
                                </td>
                                <td class="text-break">{{ \Illuminate\Support\Str::limit($faq->answer_id, 100) }}</td>
                                <td class="text-end">{{ $faq->sort_order }}</td>
                                <td>
                                    @if ($faq->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('website.faqs.edit', $faq) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('website.faqs.destroy', $faq) }}" method="post" class="d-inline">
                                        @method('delete')
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger hapus"
                                                data-confirm-title="Hapus FAQ?"
                                                data-confirm-text="FAQ &quot;{{ \Illuminate\Support\Str::limit($faq->question_id, 80) }}&quot; akan dihapus.">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada FAQ.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('dashboard.website._partials.delete_confirm')
@endsection
