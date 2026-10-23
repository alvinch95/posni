<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\WebFaq;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class WebFaqController extends Controller
{
    public function index()
    {
        return view('dashboard.website.faqs.index', [
            'faqs' => WebFaq::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('dashboard.website.faqs.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        WebFaq::create($this->normalize($request, $data));

        Alert::success('Success', 'FAQ berhasil ditambahkan');

        return redirect()->route('website.faqs.index');
    }

    public function edit(WebFaq $faq)
    {
        return view('dashboard.website.faqs.edit', ['faq' => $faq]);
    }

    public function update(Request $request, WebFaq $faq)
    {
        $data = $request->validate($this->rules(), $this->messages());

        $faq->update($this->normalize($request, $data));

        Alert::success('Success', 'FAQ berhasil diperbarui');

        return redirect()->route('website.faqs.index');
    }

    public function destroy(WebFaq $faq)
    {
        $faq->delete();

        Alert::success('Success', 'FAQ berhasil dihapus');

        return redirect()->route('website.faqs.index');
    }

    private function rules(): array
    {
        return [
            'question_id' => 'required|max:255',
            'question_en' => 'nullable|max:255',
            'answer_id' => 'required|max:2000',
            'answer_en' => 'nullable|max:2000',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'question_id.required' => 'Pertanyaan (ID) wajib diisi.',
            'question_id.max' => 'Pertanyaan (ID) maksimal :max karakter.',
            'question_en.max' => 'Pertanyaan (EN) maksimal :max karakter.',
            'answer_id.required' => 'Jawaban (ID) wajib diisi.',
            'answer_id.max' => 'Jawaban (ID) maksimal :max karakter.',
            'answer_en.max' => 'Jawaban (EN) maksimal :max karakter.',
            'sort_order.integer' => 'Urutan harus berupa angka bulat.',
            'sort_order.min' => 'Urutan tidak boleh negatif.',
            'sort_order.max' => 'Urutan maksimal :max.',
            'is_active.boolean' => 'Status tidak valid.',
        ];
    }

    /** Defaults for the optional fields. */
    private function normalize(Request $request, array $data): array
    {
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
