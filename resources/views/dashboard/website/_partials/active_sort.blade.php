{{--
  Display order + on/off switch shared by every CMS list (categories, testimonials, FAQ).
  Posts sort_order and is_active (a hidden 0 comes first, so an unchecked switch still sends 0).

  Params:
    isActive     current state (default true)
    sortOrder    current order (default 0)
    activeLabel  switch caption (default 'Aktif (tampil di website)')
--}}
@php
    $isActive = $isActive ?? true;
    $sortOrder = $sortOrder ?? 0;
    $activeLabel = $activeLabel ?? 'Aktif (tampil di website)';
@endphp

<div class="mb-3">
    <label for="sort_order" class="form-label">Urutan</label>
    <input type="number" min="0" step="1" inputmode="numeric" class="form-control @error('sort_order') is-invalid @enderror"
           id="sort_order" name="sort_order" value="{{ old('sort_order', $sortOrder) }}">
    @error('sort_order')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Angka kecil tampil lebih dulu.</div>
</div>

<div class="mb-3">
    <input type="hidden" name="is_active" value="0">
    <div class="form-check form-switch">
        <input class="form-check-input @error('is_active') is-invalid @enderror" type="checkbox" role="switch"
               id="is_active" name="is_active" value="1" {{ old('is_active', $isActive) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">{{ $activeLabel }}</label>
    </div>
    @error('is_active')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>
