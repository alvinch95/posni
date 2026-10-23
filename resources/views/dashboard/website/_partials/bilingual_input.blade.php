{{--
  Indonesian (required or optional) + English (always optional) pair of fields.

  Params:
    name      base field name; posts as {name}_id and {name}_en
    label     field label, e.g. "Nama"
    type      'text' (default) | 'textarea'
    valueId   current ID value (the old() input wins after a failed submit)
    valueEn   current EN value
    required  whether the ID field is required (default false)
    rows      textarea rows (default 4)
    maxlength optional maxlength for both inputs
    placeholder optional placeholder text for the ID input (the one the EN input falls back to)
--}}
@php
    $type = $type ?? 'text';
    $required = $required ?? false;
    $rows = $rows ?? 4;
    $maxlength = $maxlength ?? null;
    $placeholder = $placeholder ?? null;

    $fieldId = $name . '_id';
    $fieldEn = $name . '_en';
    $inputs = [
        ['field' => $fieldId, 'value' => old($fieldId, $valueId ?? ''), 'caption' => $label . ' (ID)', 'isRequired' => $required, 'placeholder' => $placeholder],
        ['field' => $fieldEn, 'value' => old($fieldEn, $valueEn ?? ''), 'caption' => $label . ' (EN, opsional)', 'isRequired' => false, 'placeholder' => null],
    ];
@endphp

@foreach ($inputs as $input)
    <div class="mb-3">
        <label for="{{ $input['field'] }}" class="form-label">
            {{ $input['caption'] }}@if ($input['isRequired']) <span class="text-danger">*</span>@endif
        </label>
        @if ($type === 'textarea')
            <textarea class="form-control @error($input['field']) is-invalid @enderror" id="{{ $input['field'] }}" name="{{ $input['field'] }}" rows="{{ $rows }}" @if ($maxlength) maxlength="{{ $maxlength }}" @endif @if ($input['placeholder']) placeholder="{{ $input['placeholder'] }}" @endif @if ($input['isRequired']) required @endif>{{ $input['value'] }}</textarea>
        @else
            <input type="text" class="form-control @error($input['field']) is-invalid @enderror" id="{{ $input['field'] }}" name="{{ $input['field'] }}" value="{{ $input['value'] }}" @if ($maxlength) maxlength="{{ $maxlength }}" @endif @if ($input['placeholder']) placeholder="{{ $input['placeholder'] }}" @endif @if ($input['isRequired']) required @endif>
        @endif
        @error($input['field'])
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
@endforeach
