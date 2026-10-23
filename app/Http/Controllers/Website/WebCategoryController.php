<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\WebCategory;
use App\Services\Website\WebUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class WebCategoryController extends Controller
{
    private const UPLOAD_DIR = 'website/categories';

    public function index()
    {
        return view('dashboard.website.categories.index', [
            'categories' => WebCategory::withCount('products')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('dashboard.website.categories.create');
    }

    public function store(Request $request)
    {
        $this->fillEmptySlug($request);

        $data = $request->validate($this->rules(), $this->messages());
        $data = $this->normalize($request, $data);

        if ($request->hasFile('image')) {
            $data['image'] = WebUpload::store($request->file('image'), self::UPLOAD_DIR);
        }

        WebCategory::create($data);

        Alert::success('Success', 'Kategori berhasil ditambahkan');

        return redirect()->route('website.categories.index');
    }

    public function edit(WebCategory $category)
    {
        return view('dashboard.website.categories.edit', ['category' => $category]);
    }

    public function update(Request $request, WebCategory $category)
    {
        $this->fillEmptySlug($request);

        $data = $request->validate($this->rules($category->id), $this->messages());
        $data = $this->normalize($request, $data);

        $oldImage = $category->image;
        if ($request->hasFile('image')) {
            $data['image'] = WebUpload::store($request->file('image'), self::UPLOAD_DIR);
        }

        $category->update($data);

        // Delete the old file only after the row points at the new one.
        if (isset($data['image'])) {
            WebUpload::delete($oldImage);
        }

        Alert::success('Success', 'Kategori berhasil diperbarui');

        return redirect()->route('website.categories.index');
    }

    public function destroy(WebCategory $category)
    {
        $image = $category->image;

        // The pivot rows go with it (cascade); the products themselves stay.
        $category->delete();
        WebUpload::delete($image);

        Alert::success('Success', 'Kategori berhasil dihapus');

        return redirect()->route('website.categories.index');
    }

    /** An empty slug falls back to the slugified Indonesian name, before validation runs. */
    private function fillEmptySlug(Request $request): void
    {
        if (trim((string) $request->input('slug')) === '') {
            $request->merge(['slug' => Str::slug((string) $request->input('name_id'))]);
        }
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'name_id' => 'required|max:255',
            'name_en' => 'nullable|max:255',
            // required_with: a blank name already has its own error, no second one for the slug.
            'slug' => [
                'required_with:name_id',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:web_categories,slug' . ($ignoreId ? ",{$ignoreId}" : ''),
            ],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'name_id.required' => 'Nama (ID) wajib diisi.',
            'name_id.max' => 'Nama (ID) maksimal :max karakter.',
            'name_en.max' => 'Nama (EN) maksimal :max karakter.',
            'slug.required_with' => 'Slug tidak bisa dibuat dari nama ini, isi manual (huruf kecil, angka, tanda hubung).',
            'slug.max' => 'Slug maksimal :max karakter.',
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung, contoh: ulang-tahun.',
            'slug.unique' => 'Slug sudah dipakai kategori lain.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
            'image.uploaded' => 'Gambar gagal diunggah. Coba file yang lebih kecil dari 2 MB.',
            'sort_order.integer' => 'Urutan harus berupa angka bulat.',
            'sort_order.min' => 'Urutan tidak boleh negatif.',
            'is_active.boolean' => 'Status tidak valid.',
        ];
    }

    /** Defaults for the optional fields; `image` is set separately, only when a file was sent. */
    private function normalize(Request $request, array $data): array
    {
        unset($data['image']);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
