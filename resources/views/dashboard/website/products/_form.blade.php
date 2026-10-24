{{--
  Shared by create and edit.
  Params: action, method ('POST'|'PUT'), product (null on create).
  From the controller: categories, hamperGroups (serie => [[id, name], ...]), keptHampers (kind => [id, label]).

  Layout: a main column (Hamper & harga, Info produk, Deskripsi & isi paket) and a right sidebar
  (Publikasi, Kategori, Foto). The sidebar is sticky from lg up. On a phone it is one column in the
  order Hamper & harga, Info produk, Deskripsi & isi paket, Kategori, Foto, Publikasi: the DOM order of
  the sidebar is the phone order and Publikasi is lifted to the top with order-lg-first, so every
  input exists once. The styles are in _form_styles.blade.php.
--}}
@php
    $kinds = ['engraved' => 'Dengan grafir nama', 'plain' => 'Tanpa grafir'];
    $currentOptions = $product ? $product->options->keyBy('kind') : collect();
    // After a failed submit the posted boxes win, even when none was ticked (old() would fall back to the saved ones).
    $selectedCategories = array_map('intval', session()->hasOldInput()
        ? (array) old('categories', [])
        : ($product ? $product->categories->pluck('id')->all() : []));
    $removedImages = array_map('intval', (array) old('remove_images', []));
    $uploadErrors = collect($errors->get('images.*'))->flatten()->unique();
    $toggles = [
        ['field' => 'is_published', 'label' => 'Tampil di website', 'hint' => 'Perlu minimal 1 hamper.', 'value' => $product ? $product->is_published : false],
        ['field' => 'is_bestseller', 'label' => 'Bestseller', 'hint' => 'Label Bestseller di kartu produk.', 'value' => $product ? $product->is_bestseller : false],
        ['field' => 'is_featured', 'label' => 'Unggulan homepage', 'hint' => 'Muncul di "Hampers Pilihan" beranda.', 'value' => $product ? $product->is_featured : false],
    ];

    $selectedHampers = [];
    foreach ($kinds as $kind => $kindLabel) {
        $option = $currentOptions->get($kind);
        $selectedHampers[$kind] = (string) old("options.{$kind}.hamper_id", $option ? $option->hamper_id : '');
    }
    // The warning of the Publikasi card as the page loads; the script keeps it live.
    $isPublishedNow = (bool) old('is_published', $product ? $product->is_published : false);
    $showPublishWarning = $isPublishedNow && count(array_filter($selectedHampers, fn ($hamperId) => $hamperId !== '')) === 0;

    $slugValue = (string) old('slug', $product ? $product->slug : '');
    $slugHasError = $errors->has('slug');
@endphp

<form method="post" action="{{ $action }}" enctype="multipart/form-data" id="product-form" class="product-form" data-autoslug="{{ $product ? '0' : '1' }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row gx-3 gx-xl-4">
        {{-- ============ Main column ============ --}}
        <div class="col-lg-8">

            {{-- 1. Hamper & harga --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-1">Hamper &amp; harga</h2>
                    <p class="text-muted small mb-3">
                        Hubungkan ke hamper di posni. Salah satu boleh kosong, tapi minimal satu hamper supaya produk bisa tampil.
                    </p>

                    @error('options')
                        <div class="alert alert-danger py-2" role="alert">{{ $message }}</div>
                    @enderror

                    <div class="row g-3">
                        @foreach ($kinds as $kind => $label)
                            @php
                                $option = $currentOptions->get($kind);
                                $selectedHamper = $selectedHampers[$kind];
                            @endphp
                            <div class="col-md-6">
                                <div class="border rounded p-2 p-xl-3 h-100 hamper-option" data-kind="{{ $kind }}">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2" style="min-height: 1.75rem;">
                                        <h3 class="h6 mb-0">{{ $label }}</h3>
                                        <span class="badge rounded-pill bg-light text-secondary border fw-normal not-sold" style="font-size: 0.875rem;" @if ($selectedHamper !== '') hidden @endif>Tidak dijual</span>
                                    </div>

                                    <label for="filter-{{ $kind }}" class="form-label small text-muted mb-1">Cari hamper</label>
                                    <input type="search" id="filter-{{ $kind }}" class="form-control mb-1 hamper-filter" style="min-height: 44px;"
                                           placeholder="Nama atau seri" autocomplete="off">
                                    <div class="form-text text-danger mt-0 mb-2 hamper-filter-empty" style="display: none;">Tidak ada hamper yang cocok.</div>

                                    <label for="hamper-{{ $kind }}" class="form-label small text-muted mt-2 mb-1">Hamper posni</label>
                                    <select class="form-select hamper-select @error("options.{$kind}.hamper_id") is-invalid @enderror"
                                            id="hamper-{{ $kind }}" name="options[{{ $kind }}][hamper_id]" style="min-height: 44px;">
                                        <option value="">- Tidak ada -</option>
                                        @if (isset($keptHampers[$kind]))
                                            <option value="{{ $keptHampers[$kind]['id'] }}" {{ $selectedHamper === (string) $keptHampers[$kind]['id'] ? 'selected' : '' }}>{{ $keptHampers[$kind]['label'] }}</option>
                                        @endif
                                        @foreach ($hamperGroups as $serieName => $hampers)
                                            <optgroup label="{{ $serieName }}">
                                                @foreach ($hampers as $hamper)
                                                    <option value="{{ $hamper['id'] }}" {{ $selectedHamper === (string) $hamper['id'] ? 'selected' : '' }}>{{ $hamper['name'] }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error("options.{$kind}.hamper_id")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text hamper-info" aria-live="polite"></div>
                                    {{-- "Kurang: ..." (stok 0) or "Hampir habis: ..." under the info line; filled by the script below --}}
                                    <div class="form-text hamper-stock-detail" aria-live="polite"></div>

                                    <label for="price-{{ $kind }}" class="form-label mt-3 mb-1">Harga di website</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" min="1" max="100000000" step="1" inputmode="numeric"
                                               class="form-control price-input @error("options.{$kind}.price_override") is-invalid @enderror"
                                               id="price-{{ $kind }}" name="options[{{ $kind }}][price_override]" style="min-height: 44px;"
                                               value="{{ old("options.{$kind}.price_override", $option ? $option->price_override : '') }}"
                                               placeholder="Ikut harga posni">
                                        @error("options.{$kind}.price_override")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">Kosongkan = ikut harga posni</div>

                                    <div class="price-preview mt-2 pt-2 border-top" aria-live="polite">
                                        Tampil di website: <strong class="price-preview-value">—</strong>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 2. Info produk --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Info produk</h2>

                    @include('dashboard.website._partials.bilingual_tabs', [
                        'id' => 'name-tabs',
                        'fields' => [[
                            'name' => 'name',
                            'label' => 'Nama',
                            'type' => 'text',
                            'valueId' => $product ? $product->name_id : '',
                            'valueEn' => $product ? $product->name_en : '',
                            'required' => true,
                            'maxlength' => 255,
                        ]],
                    ])

                    <div class="mt-3 pt-1 border-top">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-link advanced-toggle px-0 d-inline-flex align-items-center {{ $slugHasError ? '' : 'collapsed' }}"
                                    data-bs-toggle="collapse" data-bs-target="#slug-advanced"
                                    aria-expanded="{{ $slugHasError ? 'true' : 'false' }}" aria-controls="slug-advanced">
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>Pengaturan lanjutan
                            </button>
                            <span class="text-muted small text-break">Slug: <span id="slug-summary">{{ $slugValue !== '' ? $slugValue : 'dibuat otomatis dari nama' }}</span></span>
                        </div>

                        <div class="collapse {{ $slugHasError ? 'show' : '' }}" id="slug-advanced">
                            <div class="pt-2">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug"
                                       value="{{ $slugValue }}" maxlength="255" placeholder="dibuat otomatis dari nama" style="min-height: 44px;"
                                       autocapitalize="none" autocomplete="off" spellcheck="false">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Huruf kecil, angka, dan tanda hubung (contoh: solar-f). Kosongkan untuk dibuat otomatis dari nama (ID).
                                    @if ($product) Mengubah slug mengubah alamat halaman produk ini di website. @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Deskripsi & isi paket (one tab set for both fields) --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Deskripsi &amp; isi paket</h2>

                    @include('dashboard.website._partials.bilingual_tabs', [
                        'id' => 'desc-tabs',
                        'fields' => [
                            [
                                'name' => 'description',
                                'label' => 'Deskripsi',
                                'type' => 'textarea',
                                'valueId' => $product ? $product->description_id : '',
                                'valueEn' => $product ? $product->description_en : '',
                                'rows' => 4,
                                'maxlength' => 5000,
                            ],
                            [
                                'name' => 'contents',
                                'label' => 'Isi paket',
                                'type' => 'textarea',
                                'valueId' => $product ? $product->contents_id : '',
                                'valueEn' => $product ? $product->contents_en : '',
                                'rows' => 6,
                                'maxlength' => 5000,
                                'help' => 'Satu item per baris. "Isi dari posni" mengisi dari hamper "Dengan grafir nama" (atau "Tanpa grafir" kalau kosong). Rapikan hasilnya, misalnya hapus baris kemasan seperti "Shredded Paper".',
                                'helpEn' => 'Satu item per baris.',
                                'addon' => 'dashboard.website.products._fill_contents',
                            ],
                        ],
                    ])
                </div>
            </div>
        </div>

        {{-- ============ Sidebar (sticky from lg up; DOM order = phone order) ============ --}}
        <div class="col-lg-4 product-form-side d-flex flex-column">

            {{-- 4. Kategori --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-2">Kategori</h2>

                    @if ($categories->isEmpty())
                        <p class="text-muted mb-0">Belum ada kategori. <a href="{{ route('website.categories.create') }}">Tambah kategori</a> dulu kalau perlu.</p>
                    @else
                        <div class="category-list">
                            @foreach ($categories as $category)
                                <div class="d-flex align-items-stretch border-bottom" style="min-height: 45px;">
                                    <input class="form-check-input flex-shrink-0 m-0 align-self-center" style="width: 1.25rem; height: 1.25rem;" type="checkbox"
                                           name="categories[]" value="{{ $category->id }}" id="category-{{ $category->id }}"
                                           {{ in_array((int) $category->id, $selectedCategories, true) ? 'checked' : '' }}>
                                    <label class="flex-grow-1 d-flex align-items-center py-2 ps-2 mb-0 text-break" for="category-{{ $category->id }}">
                                        <span>{{ $category->name_id }}@unless ($category->is_active) <span class="text-muted">(nonaktif)</span>@endunless</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @error('categories')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    @error('categories.*')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 5. Foto --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-1">Foto</h2>
                    <p class="text-muted small mb-3">
                        Urutan terkecil jadi foto utama. JPG, PNG, atau WebP, maksimal 2 MB per foto.
                    </p>

                    @if ($product && $product->images->isNotEmpty())
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-2 g-2 mb-3 photo-grid">
                            @foreach ($product->images as $image)
                                <div class="col">
                                    <div class="border rounded p-2 h-100 photo-tile">
                                        <img src="{{ asset('storage/' . ltrim($image->path, '/')) }}" alt="Foto {{ $loop->iteration }}"
                                             width="160" height="160" loading="lazy" class="rounded mb-2 w-100" style="aspect-ratio: 1 / 1; object-fit: cover; height: auto;">

                                        <label for="image-order-{{ $image->id }}" class="form-label small mb-1">Urutan</label>
                                        <input type="number" min="0" max="65535" step="1" inputmode="numeric"
                                               class="form-control mb-1 @error("image_order.{$image->id}") is-invalid @enderror"
                                               id="image-order-{{ $image->id }}" name="image_order[{{ $image->id }}]" style="min-height: 44px;"
                                               value="{{ old("image_order.{$image->id}", $image->sort_order) }}">
                                        @error("image_order.{$image->id}")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                        <div class="d-flex align-items-stretch" style="min-height: 44px;">
                                            <input class="form-check-input flex-shrink-0 m-0 align-self-center" style="width: 1.25rem; height: 1.25rem;" type="checkbox"
                                                   name="remove_images[]" value="{{ $image->id }}" id="remove-image-{{ $image->id }}"
                                                   {{ in_array((int) $image->id, $removedImages, true) ? 'checked' : '' }}>
                                            <label class="flex-grow-1 d-flex align-items-center py-2 ps-2 mb-0 text-danger small" for="remove-image-{{ $image->id }}">Hapus foto</label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <label for="images" class="form-label">{{ $product ? 'Tambah foto' : 'Unggah foto' }}</label>
                    <input type="file" class="form-control @if ($errors->has('images') || $uploadErrors->isNotEmpty()) is-invalid @endif"
                           id="images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple style="min-height: 44px;">
                    @error('images')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @foreach ($uploadErrors as $uploadError)
                        <div class="invalid-feedback">{{ $uploadError }}</div>
                    @endforeach
                    <div id="images-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
                    <div class="form-text">Tanpa foto → pakai foto hamper</div>
                </div>
            </div>

            {{-- 6. Publikasi (top of the sidebar on lg and up) --}}
            <div class="card mb-4 order-lg-first">
                <div class="card-body">
                    <h2 class="h5 mb-3">Publikasi</h2>

                    @foreach ($toggles as $toggle)
                        <input type="hidden" name="{{ $toggle['field'] }}" value="0">
                        <div class="d-flex align-items-stretch border rounded mb-2" style="min-height: 46px;">
                            <input class="form-check-input flex-shrink-0 m-0 ms-2 align-self-center" style="width: 1.25rem; height: 1.25rem;" type="checkbox"
                                   name="{{ $toggle['field'] }}" value="1" id="{{ $toggle['field'] }}"
                                   {{ old($toggle['field'], $toggle['value']) ? 'checked' : '' }}>
                            <label class="flex-grow-1 d-flex flex-column justify-content-center py-2 px-2 mb-0" for="{{ $toggle['field'] }}">
                                {{ $toggle['label'] }}
                                @if ($toggle['hint'])
                                    <span class="d-block small text-muted">{{ $toggle['hint'] }}</span>
                                @endif
                            </label>
                        </div>
                        @error($toggle['field'])
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                    @endforeach

                    <div id="publish-warning" class="alert alert-warning py-2 small mt-3 mb-0" role="alert" @unless ($showPublishWarning) hidden @endunless>Belum bisa tampil: pilih minimal 1 hamper</div>

                    <div class="mt-3">
                        <label for="sort_order" class="form-label">Urutan</label>
                        <input type="number" min="0" max="65535" step="1" inputmode="numeric" style="min-height: 44px;"
                               class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order"
                               value="{{ old('sort_order', $product ? $product->sort_order : 0) }}">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Angka kecil tampil lebih dulu (0 sampai 65535).</div>
                    </div>

                    {{-- Desktop buttons; on phones the fixed bar below does the saving. --}}
                    <div class="d-none d-lg-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" style="min-height: 44px;">Simpan</button>
                        <a href="{{ route('website.products.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="min-height: 44px;">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Phone and tablet: fixed Simpan bar (below lg only). --}}
    <div class="product-save-bar d-lg-none">
        <div class="d-flex gap-2">
            <a href="{{ route('website.products.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="min-height: 48px; min-width: 88px;">Batal</a>
            <button type="submit" class="btn btn-primary flex-grow-1" style="min-height: 48px;">Simpan</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    (function () {
        var infoUrl = @json(route('website.hampers.info', ['hamper' => '__ID__']));

        function rupiah(value) {
            return 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        function slugify(text) {
            var value = String(text).toLowerCase();
            if (value.normalize) {
                value = value.normalize('NFD').replace(/[̀-ͯ]/g, '');
            }
            return value.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }

        // --- Publish warning: "Tampil di website" on, but no hamper picked in either panel ------
        function updatePublishWarning() {
            var published = $('#is_published').is(':checked');
            var picked = $('.hamper-select').filter(function () { return $(this).val() !== ''; }).length > 0;
            $('#publish-warning').prop('hidden', !(published && !picked));
        }

        $('#is_published').on('change', updatePublishWarning);

        // --- Hamper pickers: text filter, live price/stock line, live "Tampil di website" preview --
        $('.hamper-option').each(function () {
            var $box = $(this);
            var $select = $box.find('.hamper-select');
            var $filter = $box.find('.hamper-filter');
            var $empty = $box.find('.hamper-filter-empty');
            var $info = $box.find('.hamper-info');
            var $stockDetail = $box.find('.hamper-stock-detail');
            var $price = $box.find('.price-input');
            var $preview = $box.find('.price-preview-value');
            var $notSold = $box.find('.not-sold');
            var master = $select.children().clone(); // placeholder, kept option, optgroups
            var token = 0;

            // What the website will show for this panel: the filled-in price, else posni's price.
            // "—" for no hamper, a missing hamper, or a price that is not above zero (the API hides those).
            function renderPreview() {
                var picked = $select.val() !== '';
                $notSold.prop('hidden', picked);

                if (!picked) {
                    $preview.text('—');
                    return;
                }

                var info = $box.data('info');
                if ($box.data('infoFailed')) {
                    $preview.text('—');
                    return;
                }

                var raw = $.trim($price.val());
                var override = raw === '' ? NaN : Number(raw);
                var price = override > 0 ? override : (info ? Number(info.sellingPrice) : null);

                if (price === null) {
                    $preview.text('Memuat...');
                } else {
                    $preview.text(price > 0 ? rupiah(price) : '—');
                }
            }

            // "Kurang: Box 20x15x10 - stok -54 / butuh 1" (status out) or "Hampir habis: ..." (low),
            // built with .text() only: item names come from the database.
            function renderStockDetail(info) {
                $stockDetail.empty().removeClass('text-danger');

                if (!info || (info.status !== 'out' && info.status !== 'low')) { return; }

                var out = info.status === 'out';
                var list = (out ? info.blocking : info.limiting) || [];
                if (!list.length) { return; }

                var $label = $('<strong>').text(out ? 'Kurang:' : 'Hampir habis:');
                if (out) { $label.addClass('text-danger'); } else { $label.css('color', '#b45309'); }
                $stockDetail.append($label);

                var $list = $('<ul class="list-unstyled mb-0">');
                if (!out) { $list.addClass('text-muted'); }

                $.each(list.slice(0, 3), function (index, line) {
                    var $line = $('<li>').css({ 'padding-left': '1em', 'text-indent': '-1em' });
                    $line.append(document.createTextNode('• '));
                    if (line.deleted) {
                        $line.append($('<span class="text-danger">').text(line.name));
                        $line.append(document.createTextNode(' — butuh ' + line.needed));
                    } else {
                        var text = line.name + ' — stok ' + line.stock + ' / butuh ' + line.needed;
                        if (line.reserved > 0) { text += ' (' + line.reserved + ' di keranjang)'; }
                        $line.append(document.createTextNode(text));
                    }
                    $list.append($line);
                });

                if (list.length > 3) {
                    $list.append($('<li class="text-muted">').text('+' + (list.length - 3) + ' lainnya'));
                }

                $stockDetail.append($list);
            }

            // Fetches the picked hamper's price/stock/contents; the request is kept in
            // $box.data('pending') so "Isi dari posni" can wait for it.
            function loadInfo() {
                var id = $select.val();
                var mine = ++token;
                $box.removeData('info').removeData('pending').removeData('infoFailed');
                $info.removeClass('text-danger');
                renderStockDetail(null);

                if (!id) {
                    $info.text('');
                    renderPreview();
                    updatePublishWarning();
                    return;
                }

                $info.text('Memuat info posni...');
                var request = $.getJSON(infoUrl.replace('__ID__', encodeURIComponent(id)));
                request.done(function (data) {
                    if (mine !== token) { return; }
                    $box.data('info', data);
                    $info.text('Harga posni: ' + rupiah(data.sellingPrice) + ' · Stok ' + data.stock);
                    renderStockDetail(data);
                    renderPreview();
                }).fail(function () {
                    if (mine !== token) { return; }
                    $box.data('infoFailed', true);
                    $info.addClass('text-danger').text('Hamper ini tidak ditemukan di posni (mungkin sudah dihapus).');
                    renderPreview();
                });
                $box.data('pending', request);
                renderPreview();
                updatePublishWarning();
            }

            $select.on('change', loadInfo);
            $price.on('input', renderPreview);
            if ($select.val()) {
                loadInfo();
            } else {
                renderPreview();
            }

            // The filter is only a search box: Enter (the Search/Go key on phone keyboards) must not
            // submit the product form. This binds every .hamper-filter, one per option row.
            $filter.on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            // <option> cannot be hidden on iOS Safari, so the list is rebuilt from the master copy.
            $filter.on('input', function () {
                var query = $.trim($filter.val()).toLowerCase();
                var selected = $select.val();
                var matches = 0;

                $select.empty();
                master.each(function () {
                    var $node = $(this).clone();

                    if ($node.is('optgroup')) {
                        var group = String($node.attr('label') || '').toLowerCase();
                        $node.children('option').each(function () {
                            var text = group + ' ' + $(this).text().toLowerCase();
                            if (query !== '' && this.value !== selected && text.indexOf(query) === -1) {
                                $(this).remove();
                            }
                        });
                        if ($node.children().length === 0) { return; }
                        matches += $node.children().length;
                    }

                    $select.append($node);
                });

                $select.val(selected);
                $empty.toggle(query !== '' && matches === 0);
            });
        });

        updatePublishWarning();

        // --- ID | EN tabs ---------------------------------------------------------------------
        // The dot on the EN tab follows the English fields while typing.
        $('[data-bilingual-tabs]').each(function () {
            var $set = $(this);
            var $dot = $set.find('.lang-dot');
            var $english = $set.find('.tab-pane[data-lang="en"]').find('input, textarea');

            $english.on('input', function () {
                var filled = false;
                $english.each(function () {
                    if ($.trim($(this).val()) !== '') { filled = true; }
                });
                $dot.prop('hidden', !filled);
            });
        });

        // A required field inside a tab that is not showing cannot take the browser's focus, and the
        // browser then drops the submit silently. Show its tab first (an invalid event does not bubble,
        // so it is caught on the way down).
        document.getElementById('product-form').addEventListener('invalid', function (event) {
            var pane = $(event.target).closest('.tab-pane')[0];
            if (!pane || pane.classList.contains('active') || !window.bootstrap) { return; }

            var tab = document.querySelector('[data-bs-target="#' + pane.id + '"]');
            if (tab) {
                (bootstrap.Tab.getInstance(tab) || new bootstrap.Tab(tab)).show();
            }
        }, true);

        // --- "Isi dari posni": contents (ID) from the Grafir hamper, else the Tanpa Grafir one --
        var $status = $('#fill-contents-status');

        function say(message, isError) {
            $status.toggleClass('text-danger', !!isError).toggleClass('text-muted', !isError).text(message);
        }

        function confirmReplace(proceed) {
            if (window.Swal) {
                Swal.fire({
                    title: 'Ganti isi paket?',
                    text: 'Isi (ID) yang sudah ada akan diganti dengan isi dari posni.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, ganti',
                    cancelButtonText: 'Batal'
                }).then(function (result) {
                    if (result.isConfirmed) { proceed(); }
                });
            } else if (window.confirm('Isi (ID) yang sudah ada akan diganti dengan isi dari posni. Lanjut?')) {
                proceed();
            }
        }

        $('#fill-contents').on('click', function () {
            var $boxes = $('.hamper-option');
            var $source = $boxes.filter('[data-kind="engraved"]');
            if (!$source.find('.hamper-select').val()) {
                $source = $boxes.filter('[data-kind="plain"]');
            }
            if (!$source.find('.hamper-select').val()) {
                say('Pilih hamper "Dengan grafir nama" atau "Tanpa grafir" dulu.', true);
                return;
            }

            say('Mengambil isi dari posni...', false);
            var pending = $source.data('pending');
            if (!pending) {
                say('Info hamper belum siap, coba lagi sebentar.', true);
                return;
            }

            pending.done(function (data) {
                var lines = data.contents || [];
                if (!lines.length) {
                    say('Hamper "' + data.name + '" belum punya isi di posni.', true);
                    return;
                }

                var apply = function () {
                    // The button lives in the ID pane, but make sure the ID tab is the one showing.
                    var idTab = document.getElementById('desc-tabs-tab-id');
                    if (idTab && window.bootstrap) { (bootstrap.Tab.getInstance(idTab) || new bootstrap.Tab(idTab)).show(); }

                    $('#contents_id').val(lines.join('\n'));
                    say('Terisi dari "' + data.name + '" (' + lines.length + ' baris). Rapikan kalau perlu.', false);
                };

                if ($.trim($('#contents_id').val()) !== '') {
                    say('', false);
                    confirmReplace(apply);
                } else {
                    apply();
                }
            }).fail(function () {
                say('Gagal mengambil isi dari posni.', true);
            });
        });

        // --- Slug follows the name until it is edited by hand (create only) --------------------
        var $slug = $('#slug');

        function showSlug() {
            $('#slug-summary').text($.trim($slug.val()) !== '' ? $slug.val() : 'dibuat otomatis dari nama');
        }

        if ($('#product-form').data('autoslug') === 1) {
            var $name = $('#name_id');
            var manual = $.trim($slug.val()) !== '' && $slug.val() !== slugify($name.val());

            $name.on('input', function () {
                if (!manual) {
                    $slug.val(slugify($name.val()));
                    showSlug();
                }
            });
            $slug.on('input', function () {
                manual = $.trim($slug.val()) !== '';
            });
        }
        $slug.on('input', showSlug);

        // --- Preview of the files picked for upload ---------------------------------------------
        $('#images').on('change', function () {
            var $preview = $('#images-preview').empty();
            Array.prototype.forEach.call(this.files, function (file) {
                var reader = new FileReader();
                reader.onload = function (event) {
                    $('<img>', { src: event.target.result, alt: '', width: 72, height: 72, 'class': 'rounded border' })
                        .css('object-fit', 'cover')
                        .appendTo($preview);
                };
                reader.readAsDataURL(file);
            });
        });
    })();
</script>
@endpush
