{{--
  Phone card for a CMS list row (a multi-column table does not fit at 320 px). Used with @component,
  the card body goes in the slot:

    @component('dashboard.website._partials.list_card', [...])
        <div class="fw-bold">{{ $row->title }}</div>
    @endcomponent

  Params:
    editUrl     link of the Edit button
    destroyUrl  action of the delete form
    thumb       stored path on the public disk of the thumbnail, or null for no image
    thumbAlt    alt text of the thumbnail
    deleteData  data-* attributes for the Hapus button, as ['name' => value]; read by the page's
                SweetAlert confirm script (see _partials/delete_confirm)
    selectValue optional: adds a "Pilih" checkbox (name="ids[]", value = this) before the buttons, for
                a bulk-action form elsewhere on the page. selectForm is that form's id, selectLabel
                the checkbox's accessible name. The whole 44 px label is the tap target.
--}}
@php
    $thumb = $thumb ?? null;
    $thumbAlt = $thumbAlt ?? '';
    $deleteData = $deleteData ?? [];
    $selectValue = $selectValue ?? null;
    $selectForm = $selectForm ?? null;
    $selectLabel = $selectLabel ?? 'Pilih';
@endphp

<div class="card mb-2">
    <div class="card-body d-flex gap-3 p-3">
        @if (filled($thumb))
            <img src="{{ asset('storage/' . ltrim($thumb, '/')) }}" alt="{{ $thumbAlt }}"
                 width="56" height="56" loading="lazy" class="rounded border flex-shrink-0" style="object-fit: cover;">
        @endif
        <div class="flex-grow-1 text-break" style="min-width: 0;">
            {{ $slot }}
        </div>
    </div>
    <div class="d-flex gap-2 px-3 pb-3">
        @if ($selectValue !== null)
            <label class="d-inline-flex align-items-center gap-2 flex-shrink-0 m-0 pe-1" style="min-height: 44px;">
                <input type="checkbox" name="ids[]" value="{{ $selectValue }}" class="form-check-input wp-select m-0"
                       style="width: 24px; height: 24px;" @if ($selectForm) form="{{ $selectForm }}" @endif aria-label="{{ $selectLabel }}">
                <span>Pilih</span>
            </label>
        @endif
        <a href="{{ $editUrl }}" class="btn btn-warning flex-fill d-inline-flex align-items-center justify-content-center" style="min-height: 44px;">Edit</a>
        <form action="{{ $destroyUrl }}" method="post" class="d-flex flex-fill">
            @method('delete')
            @csrf
            <button type="submit" class="btn btn-danger hapus flex-fill" style="min-height: 44px;"@foreach ($deleteData as $dataName => $dataValue) data-{{ $dataName }}="{{ $dataValue }}"@endforeach>Hapus</button>
        </form>
    </div>
</div>
