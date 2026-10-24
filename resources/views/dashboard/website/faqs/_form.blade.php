{{-- Shared by create and edit. Params: action, method ('POST'|'PUT'), submit, faq (null on create). --}}
<form method="post" action="{{ $action }}" class="mb-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Pertanyaan &amp; jawaban</h2>

            @include('dashboard.website._partials.bilingual_input', [
                'name' => 'question',
                'label' => 'Pertanyaan',
                'type' => 'text',
                'valueId' => $faq ? $faq->question_id : '',
                'valueEn' => $faq ? $faq->question_en : '',
                'required' => true,
                'maxlength' => 255,
            ])

            @include('dashboard.website._partials.bilingual_input', [
                'name' => 'answer',
                'label' => 'Jawaban',
                'type' => 'textarea',
                'valueId' => $faq ? $faq->answer_id : '',
                'valueEn' => $faq ? $faq->answer_en : '',
                'required' => true,
                'rows' => 5,
                'maxlength' => 2000,
            ])
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Publikasi</h2>

            @include('dashboard.website._partials.active_sort', [
                'isActive' => $faq ? $faq->is_active : true,
                'sortOrder' => $faq ? $faq->sort_order : 0,
            ])
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4" style="min-height: 44px;">{{ $submit }}</button>
        <a href="{{ route('website.faqs.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center" style="min-height: 44px;">Batal</a>
    </div>
</form>
