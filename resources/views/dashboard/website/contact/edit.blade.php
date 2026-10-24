@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Link &amp; Kontak Website</h1>
</div>

<div class="col-xl-10">
    {{-- ---------------------------------------------------------------- contact settings --}}
    <form method="post" action="{{ route('website.contact.update') }}">
        @csrf
        @method('PUT')

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Kontak</h2>

                {{-- The card is as wide as the links card; the fields keep a readable width. --}}
                <div style="max-width: 44rem;">
                    <div class="mb-3">
                        <label for="whatsapp_number" class="form-label">Nomor WhatsApp CS</label>
                        <input type="tel" inputmode="tel" autocomplete="off" placeholder="0812-3456-7890"
                               class="form-control @error('whatsapp_number') is-invalid @enderror"
                               id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $values['whatsapp_number']) }}">
                        @error('whatsapp_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Boleh ditulis dengan 0, +62, spasi, atau tanda hubung. Disimpan sebagai angka saja, contoh: 6281234567890. Kosongkan kalau belum ada nomor.
                        </div>
                    </div>

                    <p class="form-text mb-2">
                        <strong>Pesan pesanan</strong>: teks yang terisi otomatis di WhatsApp saat pelanggan menekan tombol pesan di halaman produk.
                        Placeholder yang diganti otomatis:
                        <code>{product}</code> nama produk, <code>{option}</code> pilihan produk, <code>{price}</code> harga, <code>{url}</code> link produk.
                        Kosongkan untuk memakai teks bawaan website.
                    </p>
                    @include('dashboard.website._partials.bilingual_input', [
                        'name' => 'whatsapp_order_template',
                        'label' => 'Template pesan pesanan',
                        'type' => 'textarea',
                        'valueId' => $values['whatsapp_order_template_id'],
                        'valueEn' => $values['whatsapp_order_template_en'],
                        'rows' => 3,
                        'maxlength' => 500,
                        'placeholder' => $defaultOrderTemplate,
                    ])

                    <p class="form-text mb-2">
                        <strong>Pesan umum</strong>: teks yang terisi otomatis saat pelanggan menekan tombol WhatsApp di luar halaman produk.
                    </p>
                    @include('dashboard.website._partials.bilingual_input', [
                        'name' => 'whatsapp_general_message',
                        'label' => 'Pesan WhatsApp umum',
                        'type' => 'textarea',
                        'valueId' => $values['whatsapp_general_message_id'],
                        'valueEn' => $values['whatsapp_general_message_en'],
                        'rows' => 3,
                        'maxlength' => 500,
                    ])

                    @include('dashboard.website._partials.bilingual_input', [
                        'name' => 'address',
                        'label' => 'Alamat',
                        'type' => 'textarea',
                        'valueId' => $values['address_id'],
                        'valueEn' => $values['address_en'],
                        'rows' => 3,
                        'maxlength' => 255,
                    ])

                    <button type="submit" class="btn btn-primary px-4" style="min-height: 44px;">Simpan Kontak</button>
                </div>
            </div>
        </div>
    </form>

    {{-- ---------------------------------------------------------------- links --}}
    {{-- One card from lg up: heading, add button and the tables. Below that the card holds only the heading and
         the add button; the per-link cards below it are cards already and must not sit inside another card. --}}
    <div class="card mb-4">
        <div class="card-body">
            {{-- Phones: heading above the button; from sm up they sit side by side. --}}
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-start align-items-sm-center gap-2">
                <h2 class="h5 mb-0">Link marketplace &amp; sosial media</h2>
                <a href="{{ route('website.links.create') }}" class="btn btn-primary d-inline-flex align-items-center flex-shrink-0" style="min-height: 44px;">Tambah link</a>
            </div>

            {{-- Desktop (lg and up): the tables. Below that they would scroll sideways beside the sidebar. --}}
            <div class="d-none d-lg-block">
                @foreach (\App\Models\WebLink::GROUPS as $group => $groupLabel)
                    @php($links = $linksByGroup->get($group, collect()))

                    <h3 class="h6 text-muted text-uppercase mt-4 mb-2">{{ $groupLabel }}</h3>

                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Platform</th>
                                    <th scope="col">Label</th>
                                    <th scope="col">URL</th>
                                    <th scope="col" class="text-end">Urutan</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($links as $link)
                                    <tr>
                                        <td>{{ \App\Models\WebLink::PLATFORMS[$link->platform] ?? $link->platform }}</td>
                                        <td class="text-break">{{ $link->label }}</td>
                                        <td class="text-break">{{ $link->url }}</td>
                                        <td class="text-end">{{ $link->sort_order }}</td>
                                        <td>
                                            @if ($link->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('website.links.edit', $link) }}" class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('website.links.destroy', $link) }}" method="post" class="d-inline">
                                                @method('delete')
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger hapus"
                                                        data-confirm-title="Hapus link?"
                                                        data-confirm-text="Link &quot;{{ $link->label }}&quot; akan dihapus.">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Belum ada link.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Phones and tablets: one card per link (a 6-column table does not fit). --}}
    <div class="d-lg-none">
        @foreach (\App\Models\WebLink::GROUPS as $group => $groupLabel)
            @php($links = $linksByGroup->get($group, collect()))

            <h3 class="h6 text-muted text-uppercase {{ $loop->first ? 'mt-0' : 'mt-4' }} mb-2">{{ $groupLabel }}</h3>

            @forelse ($links as $link)
                @component('dashboard.website._partials.list_card', [
                    'editUrl' => route('website.links.edit', $link),
                    'destroyUrl' => route('website.links.destroy', $link),
                    'deleteData' => [
                        'confirm-title' => 'Hapus link?',
                        'confirm-text' => 'Link "' . $link->label . '" akan dihapus.',
                    ],
                ])
                    <div class="fw-bold">{{ $link->label }}</div>
                    <div class="text-muted">{{ \App\Models\WebLink::PLATFORMS[$link->platform] ?? $link->platform }}</div>
                    <div class="mt-1">{{ $link->url }}</div>
                    <div class="text-muted mt-1">
                        {{ $link->is_active ? 'Aktif' : 'Nonaktif' }} · urutan {{ $link->sort_order }}
                    </div>
                @endcomponent
            @empty
                <p class="text-center text-muted py-3 mb-0">Belum ada link.</p>
            @endforelse
        @endforeach
    </div>
</div>

@include('dashboard.website._partials.delete_confirm')
@endsection
