{{--
  Shared by create and edit.
  Params: action, method ('POST'|'PUT'), submit, product (null on create).
  From the controller: categories, hamperGroups (serie => [[id, name], ...]), keptHampers (kind => [id, label]).
--}}
@php
    $kinds = ['engraved' => 'Grafir', 'plain' => 'Tanpa Grafir'];
    $currentOptions = $product ? $product->options->keyBy('kind') : collect();
    // After a failed submit the posted boxes win, even when none was ticked (old() would fall back to the saved ones).
    $selectedCategories = array_map('intval', session()->hasOldInput()
        ? (array) old('categories', [])
        : ($product ? $product->categories->pluck('id')->all() : []));
    $removedImages = array_map('intval', (array) old('remove_images', []));
    $uploadErrors = collect($errors->get('images.*'))->flatten()->unique();
    $toggles = [
        ['field' => 'is_published', 'label' => 'Terbit (tampil di website)', 'hint' => 'Perlu minimal satu hamper (Grafir atau Tanpa Grafir).', 'value' => $product ? $product->is_published : false],
        ['field' => 'is_bestseller', 'label' => 'Bestseller', 'hint' => null, 'value' => $product ? $product->is_bestseller : false],
        ['field' => 'is_featured', 'label' => 'Unggulan (tampil di "Hampers Pilihan" beranda)', 'hint' => null, 'value' => $product ? $product->is_featured : false],
    ];
@endphp

<form method="post" action="{{ $action }}" enctype="multipart/form-data" id="product-form" class="mb-5" data-autoslug="{{ $product ? '0' : '1' }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    {{-- 1. Info --}}
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Info produk</h2>

            @include('dashboard.website._partials.bilingual_input', [
                'name' => 'name',
                'label' => 'Nama',
                'type' => 'text',
                'valueId' => $product ? $product->name_id : '',
                'valueEn' => $product ? $product->name_en : '',
                'required' => true,
                'maxlength' => 255,
            ])

            <div class="mb-3">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug"
                       value="{{ old('slug', $product ? $product->slug : '') }}" maxlength="255" placeholder="dibuat otomatis dari nama"
                       autocapitalize="none" autocomplete="off" spellcheck="false">
                @error('slug')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    Huruf kecil, angka, dan tanda hubung (contoh: solar-f). Kosongkan untuk dibuat otomatis dari nama (ID).
                    @if ($product) Mengubah slug mengubah alamat halaman produk ini di website. @endif
                </div>
            </div>

            <div class="mb-1">
                <div class="form-label">Kategori</div>
                @if ($categories->isEmpty())
                    <p class="text-muted mb-0">Belum ada kategori. <a href="{{ route('website.categories.create') }}">Tambah kategori</a> dulu kalau perlu.</p>
                @else
                    <div class="row row-cols-1 row-cols-sm-2 g-2">
                        @foreach ($categories as $category)
                            <div class="col">
                                <div class="d-flex align-items-stretch border rounded" style="min-height: 46px;">
                                    <input class="form-check-input flex-shrink-0 m-0 ms-3 align-self-center" style="width: 1.25rem; height: 1.25rem;" type="checkbox"
                                           name="categories[]" value="{{ $category->id }}" id="category-{{ $category->id }}"
                                           {{ in_array((int) $category->id, $selectedCategories, true) ? 'checked' : '' }}>
                                    <label class="flex-grow-1 d-flex align-items-center py-2 px-3 mb-0 text-break" for="category-{{ $category->id }}">
                                        <span>{{ $category->name_id }}@unless ($category->is_active) <span class="text-muted">(nonaktif)</span>@endunless</span>
                                    </label>
                                </div>
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
    </div>

    {{-- 2. Description --}}
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Deskripsi</h2>

            @include('dashboard.website._partials.bilingual_input', [
                'name' => 'description',
                'label' => 'Deskripsi',
                'type' => 'textarea',
                'valueId' => $product ? $product->description_id : '',
                'valueEn' => $product ? $product->description_en : '',
                'rows' => 4,
                'maxlength' => 5000,
            ])
        </div>
    </div>

    {{-- 3. Contents --}}
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Isi hamper</h2>

            <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center mb-2">
                <button type="button" id="fill-contents" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center" style="min-height: 44px;">Isi dari posni</button>
                <span id="fill-contents-status" class="text-muted small" role="status" aria-live="polite"></span>
            </div>
            <div class="form-text mb-3">
                Satu item per baris. "Isi dari posni" mengisi kolom ID dari hamper Grafir (atau Tanpa Grafir kalau Grafir kosong).
                Rapikan hasilnya, misalnya hapus baris kemasan seperti "Shredded Paper".
            </div>

            @include('dashboard.website._partials.bilingual_input', [
                'name' => 'contents',
                'label' => 'Isi hamper',
                'type' => 'textarea',
                'valueId' => $product ? $product->contents_id : '',
                'valueEn' => $product ? $product->contents_en : '',
                'rows' => 6,
                'maxlength' => 5000,
            ])
        </div>
    </div>

    {{-- 4. Hamper options --}}
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-1">Pilihan hamper</h2>
            <p class="text-muted small mb-3">
                Hubungkan ke hamper di posni. Harga dan stok ikut posni; harga khusus hanya kalau harga di website perlu beda.
                Salah satu boleh kosong, tapi minimal satu hamper wajib diisi supaya produk bisa terbit.
            </p>

            @error('options')
                <div class="alert alert-danger py-2" role="alert">{{ $message }}</div>
            @enderror

            <div class="row g-3">
                @foreach ($kinds as $kind => $label)
                    @php
                        $option = $currentOptions->get($kind);
                        $selectedHamper = (string) old("options.{$kind}.hamper_id", $option ? $option->hamper_id : '');
                    @endphp
                    <div class="col-lg-6">
                        <div class="border rounded p-2 p-sm-3 h-100 hamper-option" data-kind="{{ $kind }}">
                            <h3 class="h6 mb-3">{{ $label }}</h3>

                            <label for="filter-{{ $kind }}" class="form-label small text-muted">Cari hamper</label>
                            <input type="search" id="filter-{{ $kind }}" class="form-control mb-1 hamper-filter" style="min-height: 44px;"
                                   placeholder="Nama atau seri" autocomplete="off">
                            <div class="form-text text-danger mt-0 mb-2 hamper-filter-empty" style="display: none;">Tidak ada hamper yang cocok.</div>

                            <label for="hamper-{{ $kind }}" class="form-label mt-2">Hamper posni</label>
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

                            <label for="price-{{ $kind }}" class="form-label mt-3">Harga khusus (opsional)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="1" max="100000000" step="1" inputmode="numeric"
                                       class="form-control @error("options.{$kind}.price_override") is-invalid @enderror"
                                       id="price-{{ $kind }}" name="options[{{ $kind }}][price_override]" style="min-height: 44px;"
                                       value="{{ old("options.{$kind}.price_override", $option ? $option->price_override : '') }}"
                                       placeholder="Harga posni">
                                @error("options.{$kind}.price_override")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">Kosongkan untuk memakai harga posni.</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 5. Images --}}
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-1">Foto produk</h2>
            <p class="text-muted small mb-3">
                Foto dengan urutan paling kecil jadi foto utama. JPG, PNG, atau WebP, maksimal 2 MB per foto.
            </p>

            @if ($product && $product->images->isNotEmpty())
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 mb-3">
                    @foreach ($product->images as $image)
                        <div class="col">
                            <div class="border rounded p-2 h-100">
                                <img src="{{ asset('storage/' . ltrim($image->path, '/')) }}" alt="Foto {{ $loop->iteration }}"
                                     width="160" height="160" loading="lazy" class="rounded mb-2 w-100" style="aspect-ratio: 1 / 1; object-fit: cover; height: auto;">

                                <label for="image-order-{{ $image->id }}" class="form-label small mb-1">Urutan</label>
                                <input type="number" min="0" max="65535" step="1" inputmode="numeric"
                                       class="form-control mb-2 @error("image_order.{$image->id}") is-invalid @enderror"
                                       id="image-order-{{ $image->id }}" name="image_order[{{ $image->id }}]" style="min-height: 44px;"
                                       value="{{ old("image_order.{$image->id}", $image->sort_order) }}">
                                @error("image_order.{$image->id}")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div class="d-flex align-items-stretch" style="min-height: 44px;">
                                    <input class="form-check-input flex-shrink-0 m-0 align-self-center" style="width: 1.25rem; height: 1.25rem;" type="checkbox"
                                           name="remove_images[]" value="{{ $image->id }}" id="remove-image-{{ $image->id }}"
                                           {{ in_array((int) $image->id, $removedImages, true) ? 'checked' : '' }}>
                                    <label class="flex-grow-1 d-flex align-items-center py-2 ps-2 mb-0 text-danger" for="remove-image-{{ $image->id }}">Hapus foto</label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <label for="images" class="form-label">{{ $product ? 'Tambah foto' : 'Foto' }}</label>
            <input type="file" class="form-control @if ($errors->has('images') || $uploadErrors->isNotEmpty()) is-invalid @endif"
                   id="images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple style="min-height: 44px;">
            @error('images')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @foreach ($uploadErrors as $uploadError)
                <div class="invalid-feedback">{{ $uploadError }}</div>
            @endforeach
            <div id="images-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
        </div>
    </div>

    {{-- 6. Status --}}
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Status</h2>

            @foreach ($toggles as $toggle)
                <input type="hidden" name="{{ $toggle['field'] }}" value="0">
                <div class="d-flex align-items-stretch border rounded mb-2" style="min-height: 46px;">
                    <input class="form-check-input flex-shrink-0 m-0 ms-3 align-self-center" style="width: 1.25rem; height: 1.25rem;" type="checkbox"
                           name="{{ $toggle['field'] }}" value="1" id="{{ $toggle['field'] }}"
                           {{ old($toggle['field'], $toggle['value']) ? 'checked' : '' }}>
                    <label class="flex-grow-1 d-flex flex-column justify-content-center py-2 px-3 mb-0" for="{{ $toggle['field'] }}">
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
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4" style="min-height: 44px;">{{ $submit }}</button>
        <a href="{{ route('website.products.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center" style="min-height: 44px;">Batal</a>
    </div>
</form>

@push('scripts')
<script>
    (function () {
        var infoUrl = @json(route('website.hampers.info', ['hamper' => '__ID__']));

        function rupiah(value) {
            return 'Rp ' + Number(value).toLocaleString('id-ID');
        }

        function slugify(text) {
            var value = String(text).toLowerCase();
            if (value.normalize) {
                value = value.normalize('NFD').replace(/[̀-ͯ]/g, '');
            }
            return value.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }

        // --- Hamper pickers: text filter, live price/stock line --------------------------------
        $('.hamper-option').each(function () {
            var $box = $(this);
            var $select = $box.find('.hamper-select');
            var $filter = $box.find('.hamper-filter');
            var $empty = $box.find('.hamper-filter-empty');
            var $info = $box.find('.hamper-info');
            var master = $select.children().clone(); // placeholder, kept option, optgroups
            var token = 0;

            // Fetches the picked hamper's price/stock/contents; the request is kept in
            // $box.data('pending') so "Isi dari posni" can wait for it.
            function loadInfo() {
                var id = $select.val();
                var mine = ++token;
                $box.removeData('info').removeData('pending');
                $info.removeClass('text-danger');

                if (!id) {
                    $info.text('');
                    return;
                }

                $info.text('Memuat info posni...');
                var request = $.getJSON(infoUrl.replace('__ID__', encodeURIComponent(id)));
                request.done(function (data) {
                    if (mine !== token) { return; }
                    $box.data('info', data);
                    $info.text('Harga posni: ' + rupiah(data.sellingPrice) + ', Stok: ' + data.stock);
                }).fail(function () {
                    if (mine !== token) { return; }
                    $info.addClass('text-danger').text('Hamper ini tidak ditemukan di posni (mungkin sudah dihapus).');
                });
                $box.data('pending', request);
            }

            $select.on('change', loadInfo);
            if ($select.val()) {
                loadInfo();
            }

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

        // --- "Isi dari posni": contents (ID) from the Grafir hamper, else the Tanpa Grafir one --
        var $status = $('#fill-contents-status');

        function say(message, isError) {
            $status.toggleClass('text-danger', !!isError).toggleClass('text-muted', !isError).text(message);
        }

        function confirmReplace(proceed) {
            if (window.Swal) {
                Swal.fire({
                    title: 'Ganti isi hamper?',
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
                say('Pilih hamper Grafir atau Tanpa Grafir dulu.', true);
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
        if ($('#product-form').data('autoslug') === 1) {
            var $name = $('#name_id');
            var $slug = $('#slug');
            var manual = $.trim($slug.val()) !== '' && $slug.val() !== slugify($name.val());

            $name.on('input', function () {
                if (!manual) { $slug.val(slugify($name.val())); }
            });
            $slug.on('input', function () {
                manual = $.trim($slug.val()) !== '';
            });
        }

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
