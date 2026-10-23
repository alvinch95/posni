@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Konten Homepage</h1>
</div>

<div class="col-xl-10">
    <p class="text-muted mb-2">
        Ubah teks yang tampil di halaman depan website. Tulisan abu-abu di dalam kolom adalah teks bawaan website;
        <strong>kosongkan kolom</strong> untuk memakai teks bawaan itu lagi.
    </p>
    <p class="text-muted mb-3">
        Kolom <strong>EN</strong> yang kosong akan memakai teks <strong>ID</strong> di versi English, bukan teks bawaan English.
        Karakter <code>{ }</code> <code>&lt;</code> <code>&gt;</code> tidak bisa dipakai.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            Ada isian yang belum bisa disimpan. Cek kolom yang bertanda merah di bagian yang terbuka di bawah.
        </div>
    @endif

    <form method="post" action="{{ route('website.content.update') }}" novalidate>
        @csrf
        @method('PUT')

        <div class="accordion mb-4" id="contentSections">
            @foreach ($sections as $section)
                @php
                    $sectionHasErrors = false;
                    $savedCount = 0;
                    foreach ($section['fields'] as $field) {
                        if ($errors->has("copy.{$field['name']}.id") || $errors->has("copy.{$field['name']}.en")) {
                            $sectionHasErrors = true;
                        }
                        if ($field['valueId'] !== null || $field['valueEn'] !== null) {
                            $savedCount++;
                        }
                    }
                    $isOpen = $sectionHasErrors || ($loop->first && ! $errors->any());
                    $collapseId = 'content-section-' . $section['key'];
                @endphp

                <div class="accordion-item" id="section-{{ $section['key'] }}">
                    <h2 class="accordion-header" id="heading-{{ $section['key'] }}">
                        <button class="accordion-button {{ $isOpen ? '' : 'collapsed' }}" type="button"
                                style="min-height: 56px;"
                                data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                            <span class="me-2">{{ $section['label'] }}</span>
                            @if ($savedCount > 0)
                                <span class="badge bg-primary ms-auto me-2">{{ $savedCount }} diubah</span>
                            @endif
                        </button>
                    </h2>
                    <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $isOpen ? 'show' : '' }}" aria-labelledby="heading-{{ $section['key'] }}">
                        <div class="accordion-body">
                            @foreach ($section['fields'] as $field)
                                @php
                                    $idAttr = "copy.{$field['name']}.id";
                                    $enAttr = "copy.{$field['name']}.en";
                                    $idInput = "copy_{$field['name']}_id";
                                    $enInput = "copy_{$field['name']}_en";
                                    $inputs = [
                                        ['attr' => $idAttr, 'dom' => $idInput, 'name' => "copy[{$field['name']}][id]", 'caption' => 'Indonesia (ID)', 'value' => old($idAttr, $field['valueId']), 'placeholder' => $field['defaultId']],
                                        ['attr' => $enAttr, 'dom' => $enInput, 'name' => "copy[{$field['name']}][en]", 'caption' => 'English (EN)', 'value' => old($enAttr, $field['valueEn']), 'placeholder' => $field['defaultEn']],
                                    ];
                                @endphp

                                <div class="{{ $loop->last ? '' : 'mb-4 pb-3 border-bottom' }}">
                                    <div class="fw-bold mb-2">{{ $field['label'] }}</div>
                                    <div class="row g-2">
                                        @foreach ($inputs as $input)
                                            <div class="col-12 col-md-6">
                                                <label for="{{ $input['dom'] }}" class="form-label small text-muted mb-1">{{ $input['caption'] }}</label>
                                                @if ($field['type'] === 'textarea')
                                                    <textarea class="form-control @error($input['attr']) is-invalid @enderror" id="{{ $input['dom'] }}" name="{{ $input['name'] }}" rows="3" maxlength="1000" placeholder="{{ $input['placeholder'] }}">{{ $input['value'] }}</textarea>
                                                @else
                                                    <input type="text" class="form-control @error($input['attr']) is-invalid @enderror" style="min-height: 44px;" id="{{ $input['dom'] }}" name="{{ $input['name'] }}" value="{{ $input['value'] }}" maxlength="1000" placeholder="{{ $input['placeholder'] }}" autocomplete="off">
                                                @endif
                                                @error($input['attr'])
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Stays at the bottom of the screen while scrolling, so the save button is always reachable on a phone. --}}
        <div class="d-grid d-md-block bg-white border-top py-3 mb-4" style="position: sticky; bottom: 0; z-index: 5;">
            <button type="submit" class="btn btn-primary px-4" style="min-height: 44px;">Simpan Konten</button>
        </div>
    </form>
</div>
@endsection
