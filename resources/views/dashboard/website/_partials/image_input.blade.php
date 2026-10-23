{{--
  Single image upload with a live preview.

  Params:
    name     file field name (default 'image'; e.g. 'photo' for testimonials)
    label    field label (default 'Gambar')
    current  stored path on the public disk of the existing image, or null
  Rules enforced server-side: image|mimes:jpg,jpeg,png,webp|max:2048 (2 MB).
--}}
@php
    $name = $name ?? 'image';
    $label = $label ?? 'Gambar';
    $hasCurrent = filled($current ?? null);
    // asset() follows the host the admin is browsing, so the preview works on any domain.
    $currentSrc = $hasCurrent ? asset('storage/' . ltrim($current, '/')) : '';
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <div>
        <img id="{{ $name }}-preview" class="img-fluid rounded border mb-2" alt=""
             style="max-width: 240px; max-height: 240px; object-fit: cover; {{ $hasCurrent ? '' : 'display: none;' }}"
             src="{{ $currentSrc }}">
    </div>
    <input class="form-control @error($name) is-invalid @enderror" type="file" id="{{ $name }}" name="{{ $name }}"
           accept="image/jpeg,image/png,image/webp" data-image-preview="#{{ $name }}-preview">
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">
        JPG, PNG, atau WebP, maksimal 2 MB.{{ $hasCurrent ? ' Kosongkan kalau tidak ingin mengganti gambar.' : '' }}
    </div>
</div>

@once
    @push('scripts')
        <script>
            // Live preview for every file input marked with data-image-preview.
            document.querySelectorAll('input[type="file"][data-image-preview]').forEach(function (input) {
                input.addEventListener('change', function () {
                    var preview = document.querySelector(input.dataset.imagePreview);
                    if (!preview || !input.files || !input.files[0]) {
                        return;
                    }
                    var reader = new FileReader();
                    reader.onload = function (event) {
                        preview.src = event.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(input.files[0]);
                });
            });
        </script>
    @endpush
@endonce
