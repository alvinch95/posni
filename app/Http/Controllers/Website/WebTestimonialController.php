<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\WebTestimonial;
use App\Services\Website\WebUpload;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class WebTestimonialController extends Controller
{
    private const UPLOAD_DIR = 'website/testimonials';

    public function index()
    {
        return view('dashboard.website.testimonials.index', [
            'testimonials' => WebTestimonial::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('dashboard.website.testimonials.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());
        $data = $this->normalize($request, $data);

        if ($request->hasFile('photo')) {
            $data['photo'] = WebUpload::store($request->file('photo'), self::UPLOAD_DIR);
        }

        WebTestimonial::create($data);

        Alert::success('Success', 'Testimoni berhasil ditambahkan');

        return redirect()->route('website.testimonials.index');
    }

    public function edit(WebTestimonial $testimonial)
    {
        return view('dashboard.website.testimonials.edit', ['testimonial' => $testimonial]);
    }

    public function update(Request $request, WebTestimonial $testimonial)
    {
        $data = $request->validate($this->rules(), $this->messages());
        $data = $this->normalize($request, $data);

        $oldPhoto = $testimonial->photo;
        if ($request->hasFile('photo')) {
            $data['photo'] = WebUpload::store($request->file('photo'), self::UPLOAD_DIR);
        }

        $testimonial->update($data);

        // Delete the old file only after the row points at the new one.
        if (isset($data['photo'])) {
            WebUpload::delete($oldPhoto);
        }

        Alert::success('Success', 'Testimoni berhasil diperbarui');

        return redirect()->route('website.testimonials.index');
    }

    public function destroy(WebTestimonial $testimonial)
    {
        $photo = $testimonial->photo;

        $testimonial->delete();
        WebUpload::delete($photo);

        Alert::success('Success', 'Testimoni berhasil dihapus');

        return redirect()->route('website.testimonials.index');
    }

    private function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'quote_id' => 'required|max:1000',
            'quote_en' => 'nullable|max:1000',
            'occasion_id' => 'nullable|max:100',
            'occasion_en' => 'nullable|max:100',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal :max karakter.',
            'quote_id.required' => 'Kutipan (ID) wajib diisi.',
            'quote_id.max' => 'Kutipan (ID) maksimal :max karakter.',
            'quote_en.max' => 'Kutipan (EN) maksimal :max karakter.',
            'occasion_id.max' => 'Acara (ID) maksimal :max karakter.',
            'occasion_en.max' => 'Acara (EN) maksimal :max karakter.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'photo.uploaded' => 'Foto gagal diunggah. Coba file yang lebih kecil dari 2 MB.',
            'sort_order.integer' => 'Urutan harus berupa angka bulat.',
            'sort_order.min' => 'Urutan tidak boleh negatif.',
            'sort_order.max' => 'Urutan maksimal :max.',
            'is_active.boolean' => 'Status tidak valid.',
        ];
    }

    /** Defaults for the optional fields; `photo` is set separately, only when a file was sent. */
    private function normalize(Request $request, array $data): array
    {
        unset($data['photo']);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
