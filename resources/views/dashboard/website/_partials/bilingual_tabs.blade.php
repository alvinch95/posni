{{--
  One ID | EN tab set. The Indonesian pane is open by default; it opens on English only when an *_en
  field of this set failed validation and no *_id field did. The EN tab shows a dot while an English
  field has a value (old input or the model); the script on the page keeps the dot live.

  Each field keeps the names {name}_id and {name}_en and is rendered once, so the form posts exactly
  what the stacked bilingual_input partial posted. (That partial is still used by the other screens.)

  Params:
    id      DOM id prefix of this tab set, unique on the page (e.g. "name-tabs")
    fields  list of arrays, one per field pair, with:
              name         base name; posts as {name}_id and {name}_en
              label        caption, e.g. "Nama"
              type         'text' (default) | 'textarea'
              valueId      current ID value (old() wins after a failed submit)
              valueEn      current EN value
              required     the ID field is required (default false); marks the ID tab with *
              rows         textarea rows (default 4)
              maxlength    optional, for both inputs
              placeholder  optional, for the ID input
              help         optional help text under the ID input
              helpEn       optional help text under the EN input
              addon        optional view name, rendered right of the label in the ID pane
--}}
@php
    $fields = collect($fields)->map(function ($field) {
        $field += ['type' => 'text', 'valueId' => '', 'valueEn' => '', 'required' => false, 'rows' => 4, 'maxlength' => null, 'placeholder' => null, 'help' => null, 'helpEn' => null, 'addon' => null];
        $field['fieldId'] = $field['name'] . '_id';
        $field['fieldEn'] = $field['name'] . '_en';

        return $field;
    });

    $idRequired = $fields->contains(fn ($field) => $field['required']);
    $hasIdError = $fields->contains(fn ($field) => $errors->has($field['fieldId']));
    $hasEnError = $fields->contains(fn ($field) => $errors->has($field['fieldEn']));
    $hasEnValue = $fields->contains(fn ($field) => filled(old($field['fieldEn'], $field['valueEn'])));
    $activeLang = $hasEnError && ! $hasIdError ? 'en' : 'id';

    $tabs = [
        'id' => ['title' => 'ID', 'error' => $hasIdError],
        'en' => ['title' => 'EN', 'error' => $hasEnError],
    ];
@endphp

<div id="{{ $id }}" data-bilingual-tabs>
    <ul class="nav nav-tabs mb-3" role="tablist">
        @foreach ($tabs as $lang => $tab)
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link d-inline-flex align-items-center {{ $activeLang === $lang ? 'active' : '' }} {{ $tab['error'] ? 'text-danger' : '' }}"
                        id="{{ $id }}-tab-{{ $lang }}" role="tab" aria-selected="{{ $activeLang === $lang ? 'true' : 'false' }}"
                        aria-controls="{{ $id }}-pane-{{ $lang }}" data-bs-toggle="tab" data-bs-target="#{{ $id }}-pane-{{ $lang }}"
                        style="min-height: 44px;">
                    {{ $tab['title'] }}
                    @if ($lang === 'id' && $idRequired)
                        <span class="text-danger ms-1" aria-hidden="true">*</span><span class="visually-hidden">(wajib)</span>
                    @endif
                    @if ($lang === 'en')
                        <span class="lang-dot" role="img" aria-label="sudah terisi" @if (! $hasEnValue) hidden @endif></span>
                    @endif
                    @if ($tab['error'])
                        <span class="visually-hidden">(ada kesalahan)</span>
                    @endif
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($tabs as $lang => $tab)
            <div class="tab-pane {{ $activeLang === $lang ? 'show active' : '' }}" id="{{ $id }}-pane-{{ $lang }}" data-lang="{{ $lang }}"
                 role="tabpanel" aria-labelledby="{{ $id }}-tab-{{ $lang }}">
                @foreach ($fields as $field)
                    @php
                        $isId = $lang === 'id';
                        $fieldName = $isId ? $field['fieldId'] : $field['fieldEn'];
                        $value = old($fieldName, ($isId ? $field['valueId'] : $field['valueEn']) ?? '');
                        $isRequired = $isId && $field['required'];
                        $help = $isId ? $field['help'] : $field['helpEn'];
                    @endphp
                    <div class="{{ $loop->last ? 'mb-0' : 'mb-3' }}">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-1">
                            <label for="{{ $fieldName }}" class="form-label mb-0 me-2">
                                {{ $isId ? $field['label'] : $field['label'] . ' (EN, opsional)' }}@if ($isRequired) <span class="text-danger">*</span>@endif
                            </label>
                            @if ($isId && $field['addon'])
                                @include($field['addon'])
                            @endif
                        </div>
                        @if ($field['type'] === 'textarea')
                            <textarea class="form-control @error($fieldName) is-invalid @enderror" id="{{ $fieldName }}" name="{{ $fieldName }}" rows="{{ $field['rows'] }}" @if ($field['maxlength']) maxlength="{{ $field['maxlength'] }}" @endif @if ($isId && $field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endif @if ($isRequired) required @endif>{{ $value }}</textarea>
                        @else
                            <input type="text" class="form-control @error($fieldName) is-invalid @enderror" id="{{ $fieldName }}" name="{{ $fieldName }}" value="{{ $value }}" style="min-height: 44px;" @if ($field['maxlength']) maxlength="{{ $field['maxlength'] }}" @endif @if ($isId && $field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endif @if ($isRequired) required @endif>
                        @endif
                        @error($fieldName)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if ($help)
                            <div class="form-text">{{ $help }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
