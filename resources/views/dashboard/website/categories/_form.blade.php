{{-- Shared by create and edit. Params: action, method ('POST'|'PUT'), submit, category (null on create). --}}
<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="mb-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @include('dashboard.website._partials.bilingual_input', [
        'name' => 'name',
        'label' => 'Nama',
        'type' => 'text',
        'valueId' => $category ? $category->name_id : '',
        'valueEn' => $category ? $category->name_en : '',
        'required' => true,
        'maxlength' => 255,
    ])

    <div class="mb-3">
        <label for="slug" class="form-label">Slug</label>
        <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug"
               value="{{ old('slug', $category ? $category->slug : '') }}" maxlength="255" placeholder="dibuat otomatis dari nama"
               autocapitalize="none" autocomplete="off" spellcheck="false">
        @error('slug')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">
            Huruf kecil, angka, dan tanda hubung (contoh: ulang-tahun). Kosongkan untuk dibuat otomatis dari nama (ID).
            @if ($category) Mengubah slug mengubah alamat kategori ini di website. @endif
        </div>
    </div>

    @include('dashboard.website._partials.image_input', [
        'name' => 'image',
        'label' => 'Gambar kategori',
        'current' => $category ? $category->image : null,
    ])

    @include('dashboard.website._partials.active_sort', [
        'isActive' => $category ? $category->is_active : true,
        'sortOrder' => $category ? $category->sort_order : 0,
    ])

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4" style="min-height: 44px;">{{ $submit }}</button>
        <a href="{{ route('website.categories.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center" style="min-height: 44px;">Batal</a>
    </div>
</form>
