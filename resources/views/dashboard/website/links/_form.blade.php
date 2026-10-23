{{-- Shared by create and edit. Params: action, method ('POST'|'PUT'), submit, link (null on create). --}}
<form method="post" action="{{ $action }}" class="mb-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="mb-3">
        <label for="group" class="form-label">Grup <span class="text-danger">*</span></label>
        <select class="form-select @error('group') is-invalid @enderror" id="group" name="group" required>
            @foreach (\App\Models\WebLink::GROUPS as $value => $caption)
                <option value="{{ $value }}" {{ old('group', $link ? $link->group : 'marketplace') === $value ? 'selected' : '' }}>{{ $caption }}</option>
            @endforeach
        </select>
        @error('group')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="platform" class="form-label">Platform <span class="text-danger">*</span></label>
        <select class="form-select @error('platform') is-invalid @enderror" id="platform" name="platform" required>
            @foreach (\App\Models\WebLink::PLATFORMS as $value => $caption)
                <option value="{{ $value }}" {{ old('platform', $link ? $link->platform : 'shopee') === $value ? 'selected' : '' }}>{{ $caption }}</option>
            @endforeach
        </select>
        @error('platform')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="label" class="form-label">Label <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('label') is-invalid @enderror" id="label" name="label"
               value="{{ old('label', $link ? $link->label : '') }}" maxlength="50" required>
        @error('label')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Teks yang tampil di website, contoh: Shopee Kayuni atau @@kayuni.id.</div>
    </div>

    <div class="mb-3">
        <label for="url" class="form-label">URL <span class="text-danger">*</span></label>
        <input type="url" inputmode="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url"
               value="{{ old('url', $link ? $link->url : '') }}" maxlength="255" placeholder="https://shopee.co.id/kayuni" required>
        @error('url')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Alamat lengkap, diawali https://</div>
    </div>

    @include('dashboard.website._partials.active_sort', [
        'isActive' => $link ? $link->is_active : true,
        'sortOrder' => $link ? $link->sort_order : 0,
    ])

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4" style="min-height: 44px;">{{ $submit }}</button>
        <a href="{{ route('website.contact.edit') }}" class="btn btn-outline-secondary d-inline-flex align-items-center" style="min-height: 44px;">Batal</a>
    </div>
</form>
