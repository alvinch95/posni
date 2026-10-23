{{-- Shared by create and edit. Params: action, method ('POST'|'PUT'), submit, testimonial (null on create). --}}
<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="mb-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="mb-3">
        <label for="name" class="form-label">Nama pelanggan <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
               value="{{ old('name', $testimonial ? $testimonial->name : '') }}" maxlength="100" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @include('dashboard.website._partials.bilingual_input', [
        'name' => 'quote',
        'label' => 'Kutipan',
        'type' => 'textarea',
        'valueId' => $testimonial ? $testimonial->quote_id : '',
        'valueEn' => $testimonial ? $testimonial->quote_en : '',
        'required' => true,
        'rows' => 4,
        'maxlength' => 1000,
    ])

    @include('dashboard.website._partials.bilingual_input', [
        'name' => 'occasion',
        'label' => 'Acara',
        'type' => 'text',
        'valueId' => $testimonial ? $testimonial->occasion_id : '',
        'valueEn' => $testimonial ? $testimonial->occasion_en : '',
        'required' => false,
        'maxlength' => 100,
    ])

    @include('dashboard.website._partials.image_input', [
        'name' => 'photo',
        'label' => 'Foto pelanggan',
        'current' => $testimonial ? $testimonial->photo : null,
    ])

    @include('dashboard.website._partials.active_sort', [
        'isActive' => $testimonial ? $testimonial->is_active : true,
        'sortOrder' => $testimonial ? $testimonial->sort_order : 0,
    ])

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4" style="min-height: 44px;">{{ $submit }}</button>
        <a href="{{ route('website.testimonials.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center" style="min-height: 44px;">Batal</a>
    </div>
</form>
