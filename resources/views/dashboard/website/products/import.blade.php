@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Import dari hampers</h1>
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

    <div class="card mb-4">
        <div class="card-body">
            <div class="alert alert-info" role="note">
                <p class="mb-2">Isi Produk Website dengan cepat dari hamper yang sudah ada di posni. Pilih serie, lalu import.</p>
                <ul class="mb-0 ps-3">
                    <li>Semua produk dibuat sebagai <strong>Draft</strong>, belum tampil di website. Setelah dicek, publish dari daftar produk: centang beberapa produk, lalu klik Publish.</li>
                    <li>Hamper "... Grafir" dan "... Tanpa Grafir" dalam satu serie digabung jadi satu produk dengan dua pilihan. Hamper lain jadi produk dengan satu pilihan (Grafir).</li>
                    <li>Hamper yang sudah dipakai produk website dilewati, supaya tidak ada duplikat. Serie Item tidak ikut.</li>
                </ul>
            </div>

            @if (count($rows) === 0)
                <p class="text-center text-muted py-4">Belum ada serie di posni.</p>
            @else
                <style>
                    .wp-import td.wp-import-check { width: 44px; padding-left: .5rem; padding-right: 0; }
                    .wp-import tr.wp-import-off { color: #6c757d; }

                    /* Phones and tablets: one card per serie (5 columns do not fit at 320 px). */
                    @media (max-width: 767.98px) {
                        .table.wp-import, .table.wp-import tbody { display: block; }
                        .table.wp-import thead { display: none; }
                        .table.wp-import tr {
                            display: flex;
                            flex-wrap: wrap;
                            align-items: center;
                            background: #fff;
                            border: 1px solid #dee2e6;
                            border-radius: .5rem;
                            margin-bottom: .5rem;
                            padding: .25rem .5rem;
                        }
                        .table.wp-import td {
                            border: 0;
                            box-shadow: none;
                            background: transparent;
                            padding: .25rem .5rem;
                        }
                        .table.wp-import td.wp-import-check { flex: 0 0 44px; width: 44px; padding: 0; }
                        .table.wp-import td.wp-import-serie { flex: 1 1 calc(100% - 44px); min-width: 0; }
                        .table.wp-import td[data-label] { flex: 1 1 30%; min-width: 0; text-align: left !important; }
                        .table.wp-import td[data-label]::before {
                            content: attr(data-label);
                            display: block;
                            font-size: .875rem;
                            color: #6c757d;
                        }
                    }
                </style>

                <form action="{{ route('website.products.import.store') }}" method="post" id="wp-import-form">
                    @csrf

                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                        <label class="d-inline-flex align-items-center gap-2 m-0" style="min-height: 44px;">
                            <input type="checkbox" id="wp-import-all" class="form-check-input m-0" style="width: 24px; height: 24px;" @if ($importable === 0) disabled @endif>
                            <span>Pilih semua</span>
                        </label>
                        <span class="text-muted">
                            @if ($importable > 0)
                                Total yang bisa dibuat: <strong>{{ $importable }}</strong> produk
                            @else
                                Tidak ada produk baru untuk diimport.
                            @endif
                        </span>
                    </div>

                    <table class="table align-middle wp-import">
                        <thead>
                            <tr>
                                <th scope="col"><span class="visually-hidden">Pilih</span></th>
                                <th scope="col">Serie</th>
                                <th scope="col" class="text-end">Hamper</th>
                                <th scope="col" class="text-end">Sudah di website</th>
                                <th scope="col" class="text-end">Akan dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $off = $row['to_create'] === 0; @endphp
                                <tr class="{{ $off ? 'wp-import-off' : '' }}">
                                    <td class="wp-import-check">
                                        <label class="d-flex align-items-center justify-content-center m-0" style="min-width: 44px; min-height: 44px;">
                                            <input type="checkbox" id="serie-{{ $row['serie_id'] }}" name="serie_ids[]" value="{{ $row['serie_id'] }}"
                                                   data-create="{{ $row['to_create'] }}" class="form-check-input wp-import-serie-box m-0"
                                                   style="width: 24px; height: 24px;" aria-label="Pilih serie {{ $row['serie'] }}" @if ($off) disabled @endif>
                                        </label>
                                    </td>
                                    <td class="wp-import-serie text-break">
                                        <label for="serie-{{ $row['serie_id'] }}" class="m-0 d-flex align-items-center" style="min-height: 44px; font-weight: 600;">{{ $row['serie'] }}</label>
                                        @if ($off)
                                            <div class="small">
                                                @if ($row['hampers'] === 0)
                                                    Belum ada hamper yang bisa diimport.
                                                @elseif ($row['partial'] > 0)
                                                    Sebagian sudah di website, cek manual.
                                                @elseif ($row['linked'] === $row['hampers'])
                                                    Semua sudah ada di website.
                                                @else
                                                    Tidak ada produk baru.
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end" data-label="Hamper">{{ $row['hampers'] }}</td>
                                    <td class="text-end" data-label="Sudah di website">{{ $row['linked'] }}</td>
                                    <td class="text-end" data-label="Akan dibuat"><strong>{{ $row['to_create'] }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                        <span class="text-muted" id="wp-import-summary" aria-live="polite">Belum ada serie dipilih</span>
                        <div class="d-flex gap-2 ms-sm-auto">
                            <a href="{{ route('website.products.index') }}" class="btn btn-outline-secondary flex-fill d-inline-flex align-items-center justify-content-center" style="min-height: 44px;">Batal</a>
                            <button type="submit" id="wp-import-submit" class="btn btn-primary flex-fill d-inline-flex align-items-center justify-content-center" style="min-height: 44px; min-width: 150px;" disabled>Import sebagai draft</button>
                        </div>
                    </div>
                </form>

                <script>
                    (function () {
                        var form = document.getElementById('wp-import-form');
                        var all = document.getElementById('wp-import-all');
                        var summary = document.getElementById('wp-import-summary');
                        var submit = document.getElementById('wp-import-submit');
                        var boxes = Array.prototype.slice.call(document.querySelectorAll('input.wp-import-serie-box:not(:disabled)'));

                        function refresh() {
                            var chosen = 0;
                            var products = 0;
                            boxes.forEach(function (box) {
                                if (box.checked) {
                                    chosen += 1;
                                    products += parseInt(box.getAttribute('data-create'), 10) || 0;
                                }
                            });

                            summary.textContent = chosen === 0
                                ? 'Belum ada serie dipilih'
                                : chosen + ' serie dipilih, ' + products + ' produk akan dibuat sebagai draft';
                            submit.disabled = chosen === 0;
                            all.checked = boxes.length > 0 && chosen === boxes.length;
                            all.indeterminate = chosen > 0 && chosen < boxes.length;
                        }

                        boxes.forEach(function (box) { box.addEventListener('change', refresh); });
                        all.addEventListener('change', function () {
                            boxes.forEach(function (box) { box.checked = all.checked; });
                            refresh();
                        });
                        form.addEventListener('submit', function () {
                            // One click, one import: a second submit while the first runs would only find everything linked.
                            submit.disabled = true;
                            submit.textContent = 'Mengimport...';
                        });

                        // Back button after an import restores the page with the button still locked.
                        window.addEventListener('pageshow', function (event) {
                            if (event.persisted) {
                                submit.textContent = 'Import sebagai draft';
                                refresh();
                            }
                        });

                        refresh();
                    })();
                </script>
            @endif
        </div>
    </div>
</div>
@endsection
