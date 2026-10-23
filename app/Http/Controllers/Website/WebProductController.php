<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Hamper;
use App\Models\WebCategory;
use App\Models\WebProduct;
use App\Models\WebProductImage;
use App\Models\WebProductOption;
use App\Services\Website\HamperStock;
use App\Services\Website\ProductCatalog;
use App\Services\Website\WebUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RealRashid\SweetAlert\Facades\Alert;

class WebProductController extends Controller
{
    private const UPLOAD_DIR = 'website/products';

    /** Option kinds in display order, with their label. A product has at most one option per kind. */
    private const KINDS = ['engraved' => 'Grafir', 'plain' => 'Tanpa Grafir'];

    /** The only columns the list may be sorted by; anything else falls back to the default. */
    private const SORTABLE = ['sort_order', 'name_id', 'created_at'];

    private const PER_PAGE = 20;

    private const PUBLISH_ERROR = 'Pilih minimal satu hamper (Grafir atau Tanpa Grafir) sebelum publish.';

    /** The auto-created serie that wraps every single item as a hamper; not a real hamper to sell. */
    private const ITEM_SERIE = 'Item';

    public function index(Request $request)
    {
        $search = is_string($request->query('search')) ? trim($request->query('search')) : '';
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'sort_order';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $products = WebProduct::query()
            ->with(['options.hamper', 'images'])
            ->when($search !== '', fn ($query) => $query->where('name_id', 'like', '%' . $search . '%'))
            ->orderBy($sort, $direction) // $sort comes from SORTABLE only
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $visibleIds = ProductCatalog::validPublishedProductIds()->all();

        $rows = [];
        foreach ($products as $product) {
            $rows[$product->id] = $this->summarize($product, $visibleIds);
        }

        return view('dashboard.website.products.index', [
            'products' => $products,
            'rows' => $rows,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        return view('dashboard.website.products.create', $this->formData(null));
    }

    public function store(Request $request)
    {
        $this->normalizeSlug($request);
        $data = $this->validated($request, null);

        $stored = [];
        try {
            DB::transaction(function () use ($request, $data, &$stored) {
                $product = WebProduct::create($this->attributes($request, $data));

                $this->syncOptions($product, $request);
                $product->categories()->sync($data['categories'] ?? []);
                $this->syncImages($product, $request, $stored);
            });
        } catch (\Throwable $e) {
            $this->discard($stored);
            throw $e;
        }

        Alert::success('Success', 'Produk berhasil ditambahkan');

        return redirect()->route('website.products.index');
    }

    public function edit(WebProduct $product)
    {
        return view('dashboard.website.products.edit', $this->formData($product));
    }

    public function update(Request $request, WebProduct $product)
    {
        $this->normalizeSlug($request);
        $data = $this->validated($request, $product);

        $stored = [];
        $removed = [];
        try {
            DB::transaction(function () use ($request, $data, $product, &$stored, &$removed) {
                $product->update($this->attributes($request, $data));

                $this->syncOptions($product, $request);
                $product->categories()->sync($data['categories'] ?? []);
                $removed = $this->syncImages($product, $request, $stored);
            });
        } catch (\Throwable $e) {
            $this->discard($stored);
            throw $e;
        }

        // Files of removed images go only after the commit, so a rolled-back save keeps them.
        $this->discard($removed);

        Alert::success('Success', 'Produk berhasil diperbarui');

        return redirect()->route('website.products.index');
    }

    /**
     * Soft delete. The slug column is unique across soft-deleted rows too, so the slug is renamed
     * to free it for a new product; the options, images and files stay, nothing else is lost.
     */
    public function destroy(WebProduct $product)
    {
        DB::transaction(function () use ($product) {
            $product->update(['slug' => Str::limit($product->slug, 200, '') . '-deleted-' . $product->id]);
            $product->delete();
        });

        Alert::success('Success', 'Produk berhasil dihapus');

        return redirect()->route('website.products.index');
    }

    /**
     * Live data for the hamper picker and the "Isi dari posni" button. Admin-only (route group).
     * A soft-deleted hamper is a 404 through route model binding.
     */
    public function hamperInfo(Hamper $hamper)
    {
        $hamper->load('serie');

        // Details and items are soft-deletable: both relations skip deleted rows, so a detail whose
        // item is gone resolves to a null item and is left out.
        $contents = $hamper->details()->with('item')->orderBy('id')->get()
            ->map(fn ($detail) => optional($detail->item)->name)
            ->filter(fn ($name) => filled($name))
            ->values()
            ->all();

        return response()->json([
            'id' => (int) $hamper->id,
            'name' => (string) $hamper->name,
            'serie' => optional($hamper->serie)->name,
            'sellingPrice' => (int) $hamper->selling_price,
            'stock' => HamperStock::of($hamper),
            'contents' => $contents,
        ]);
    }

    /**
     * What one list row shows: option lines with live price and stock, "price from", a thumbnail,
     * and whether a published product is invisible on the website.
     *
     * @param  array<int, int>  $visibleIds  ProductCatalog::validPublishedProductIds()
     */
    private function summarize(WebProduct $product, array $visibleIds): array
    {
        $options = [];
        $prices = [];
        $thumb = optional($product->images->first())->path;

        foreach (self::KINDS as $kind => $label) {
            $option = $product->options->firstWhere('kind', $kind);
            if (! $option) {
                continue;
            }

            $hamper = $option->hamper; // null when the hamper is soft-deleted or missing
            $price = $hamper ? (int) ($option->price_override ?? $hamper->selling_price ?? 0) : null;

            if ($price !== null) {
                $prices[] = $price;
            }
            if (! $thumb && $hamper && filled($hamper->image)) {
                $thumb = $hamper->image;
            }

            $options[] = [
                'label' => $label,
                'hamper' => $hamper ? $hamper->name : null,
                'price' => $price,
                'stock' => $hamper ? HamperStock::of($hamper) : null,
            ];
        }

        return [
            'options' => $options,
            'priceFrom' => $prices === [] ? null : min($prices),
            'thumb' => $thumb,
            'hidden' => $product->is_published && ! in_array((int) $product->id, $visibleIds, true),
        ];
    }

    /** Data for the create/edit form: the product (or null), categories and the grouped hamper picker. */
    private function formData(?WebProduct $product): array
    {
        if ($product) {
            $product->load(['options', 'images', 'categories']);
        }

        // Hampers grouped by serie, ordered by serie then name. A hamper without a serie is listed
        // first; the auto-created "Item" serie and soft-deleted hampers (global scope) are left out.
        $hampers = Hamper::query()
            ->leftJoin('series', 'series.id', '=', 'hampers.serie_id')
            ->where(function ($query) {
                $query->whereNull('series.name')->orWhere('series.name', '!=', self::ITEM_SERIE);
            })
            ->orderBy('series.name')
            ->orderBy('hampers.name')
            ->get(['hampers.id', 'hampers.name', 'series.name as serie_name']);

        $hamperGroups = $hampers
            ->groupBy(fn ($hamper) => $hamper->serie_name ?? 'Tanpa serie')
            ->map(fn ($group) => $group->map(fn ($hamper) => ['id' => (int) $hamper->id, 'name' => (string) $hamper->name])->all())
            ->all();

        // A hamper this product already points at that the picker no longer offers (deleted in posni,
        // or an Item-serie one): keep it selectable under its option, so saving other fields does not
        // drop the option.
        $kept = [];
        if ($product) {
            $listed = $hampers->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach ($product->options as $option) {
                $id = (int) $option->hamper_id;
                if (in_array($id, $listed, true)) {
                    continue;
                }
                $hamper = Hamper::withTrashed()->find($id);
                $name = $hamper ? $hamper->name : 'Hamper #' . $id;
                $kept[$option->kind] = [
                    'id' => $id,
                    'label' => $name . ($hamper === null || $hamper->trashed() ? ' (dihapus di posni)' : ''),
                ];
            }
        }

        return [
            'product' => $product,
            'categories' => WebCategory::query()->orderBy('sort_order')->orderBy('id')->get(),
            'hamperGroups' => $hamperGroups,
            'keptHampers' => $kept,
        ];
    }

    /**
     * Slug = Str::slug of the typed slug, or of the Indonesian name when that is empty or has no
     * usable characters. Runs before validation so "Solar F!!" is stored as "solar-f".
     */
    private function normalizeSlug(Request $request): void
    {
        $typed = $request->input('slug');
        $name = $request->input('name_id');

        $slug = Str::slug(is_string($typed) ? $typed : '');
        if ($slug === '') {
            $slug = Str::slug(is_string($name) ? $name : '');
        }

        $request->merge(['slug' => $slug]);
    }

    /** Validate the form; publishing needs at least one option. */
    private function validated(Request $request, ?WebProduct $product): array
    {
        $validator = Validator::make($request->all(), $this->rules($product), $this->messages());

        $validator->after(function ($validator) use ($request) {
            if ($request->boolean('is_published') && ! $this->hasOption($request)) {
                $validator->errors()->add('options', self::PUBLISH_ERROR);
            }
        });

        return $validator->validate();
    }

    private function hasOption(Request $request): bool
    {
        foreach (array_keys(self::KINDS) as $kind) {
            if (filled($request->input("options.{$kind}.hamper_id"))) {
                return true;
            }
        }

        return false;
    }

    private function rules(?WebProduct $product): array
    {
        $rules = [
            'name_id' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            // required_with: a blank name already has its own error, no second one for the slug.
            'slug' => [
                'required_with:name_id',
                'string',
                'max:255',
                Rule::unique('web_products', 'slug')->ignore($product ? $product->id : null),
            ],
            'categories' => 'nullable|array',
            'categories.*' => ['integer', Rule::exists('web_categories', 'id')],
            'description_id' => 'nullable|string|max:5000',
            'description_en' => 'nullable|string|max:5000',
            'contents_id' => 'nullable|string|max:5000',
            'contents_en' => 'nullable|string|max:5000',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer',
            'image_order' => 'nullable|array',
            'image_order.*' => 'nullable|integer|min:0|max:65535',
            'is_published' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ];

        foreach (array_keys(self::KINDS) as $kind) {
            $current = $product ? $product->options->firstWhere('kind', $kind) : null;

            $rules["options.{$kind}.hamper_id"] = ['bail', 'nullable', 'integer', $this->hamperExists($current ? (int) $current->hamper_id : null)];
            $rules["options.{$kind}.price_override"] = 'nullable|integer|min:1|max:100000000';
        }

        return $rules;
    }

    /**
     * The hamper must exist and not be soft-deleted, except that an option may keep the hamper it
     * already points at (it may have been deleted in posni since).
     */
    private function hamperExists(?int $keepId): \Closure
    {
        return function ($attribute, $value, $fail) use ($keepId) {
            if ($keepId !== null && (int) $value === $keepId) {
                return;
            }

            if (! Hamper::query()->whereKey($value)->exists()) {
                $fail('Hamper yang dipilih tidak ditemukan di posni.');
            }
        };
    }

    private function messages(): array
    {
        return [
            'name_id.required' => 'Nama (ID) wajib diisi.',
            'name_id.string' => 'Nama (ID) tidak valid.',
            'name_id.max' => 'Nama (ID) maksimal :max karakter.',
            'name_en.string' => 'Nama (EN) tidak valid.',
            'name_en.max' => 'Nama (EN) maksimal :max karakter.',
            'slug.required_with' => 'Slug tidak bisa dibuat dari nama ini, isi manual (huruf kecil, angka, tanda hubung).',
            'slug.string' => 'Slug tidak valid.',
            'slug.max' => 'Slug maksimal :max karakter.',
            'slug.unique' => 'Slug sudah dipakai produk lain.',
            'categories.array' => 'Kategori tidak valid.',
            'categories.*.integer' => 'Kategori tidak valid.',
            'categories.*.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'description_id.string' => 'Deskripsi (ID) tidak valid.',
            'description_id.max' => 'Deskripsi (ID) maksimal :max karakter.',
            'description_en.string' => 'Deskripsi (EN) tidak valid.',
            'description_en.max' => 'Deskripsi (EN) maksimal :max karakter.',
            'contents_id.string' => 'Isi hamper (ID) tidak valid.',
            'contents_id.max' => 'Isi hamper (ID) maksimal :max karakter.',
            'contents_en.string' => 'Isi hamper (EN) tidak valid.',
            'contents_en.max' => 'Isi hamper (EN) maksimal :max karakter.',
            'options.*.hamper_id.integer' => 'Hamper yang dipilih tidak valid.',
            'options.*.price_override.integer' => 'Harga khusus harus berupa angka bulat, tanpa titik atau koma.',
            'options.*.price_override.min' => 'Harga khusus minimal Rp 1. Kosongkan untuk memakai harga posni.',
            'options.*.price_override.max' => 'Harga khusus maksimal Rp 100.000.000.',
            'images.array' => 'Foto tidak valid.',
            'images.max' => 'Maksimal :max foto sekali unggah.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran tiap foto maksimal 2 MB.',
            'images.*.uploaded' => 'Foto gagal diunggah. Coba file yang lebih kecil dari 2 MB.',
            'remove_images.*.integer' => 'Foto yang dihapus tidak valid.',
            'image_order.*.integer' => 'Urutan foto harus berupa angka bulat.',
            'image_order.*.min' => 'Urutan foto tidak boleh negatif.',
            'image_order.*.max' => 'Urutan foto maksimal :max.',
            'is_published.boolean' => 'Status terbit tidak valid.',
            'is_bestseller.boolean' => 'Status bestseller tidak valid.',
            'is_featured.boolean' => 'Status unggulan tidak valid.',
            'sort_order.integer' => 'Urutan harus berupa angka bulat.',
            'sort_order.min' => 'Urutan tidak boleh negatif.',
            'sort_order.max' => 'Urutan maksimal :max.',
        ];
    }

    /** The web_products columns, with the optional ones defaulted and line breaks normalized to \n. */
    private function attributes(Request $request, array $data): array
    {
        $text = function (string $field) use ($data) {
            $value = $data[$field] ?? null;

            return $value === null ? null : str_replace(["\r\n", "\r"], "\n", $value);
        };

        return [
            'slug' => $data['slug'],
            'name_id' => $data['name_id'],
            'name_en' => $data['name_en'] ?? null,
            'description_id' => $text('description_id'),
            'description_en' => $text('description_en'),
            'contents_id' => $text('contents_id'),
            'contents_en' => $text('contents_en'),
            'is_published' => $request->boolean('is_published'),
            'is_bestseller' => $request->boolean('is_bestseller'),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    /**
     * One row per kind that has a hamper; a kind whose hamper is empty loses its row. An empty
     * price override is stored as null (posni's price applies), never as 0.
     */
    private function syncOptions(WebProduct $product, Request $request): void
    {
        $order = 0;

        foreach (array_keys(self::KINDS) as $kind) {
            $hamperId = $request->input("options.{$kind}.hamper_id");

            if (blank($hamperId)) {
                WebProductOption::query()
                    ->where('web_product_id', $product->id)
                    ->where('kind', $kind)
                    ->delete();
            } else {
                $price = $request->input("options.{$kind}.price_override");

                WebProductOption::updateOrCreate(
                    ['web_product_id' => $product->id, 'kind' => $kind],
                    [
                        'hamper_id' => (int) $hamperId,
                        'price_override' => blank($price) ? null : (int) $price,
                        'sort_order' => $order,
                    ]
                );
            }

            $order++;
        }
    }

    /**
     * Remove ticked images, apply the typed order to the rest, then append the new uploads after
     * them. Only rows change here. Paths of newly stored files are added to $stored and the paths of
     * removed images are returned, so the caller can delete files once the outcome is known: the new
     * ones if the save fails, the removed ones only after it commits.
     *
     * @param  array<int, string>  $stored
     * @return array<int, string>  paths of the removed images
     */
    private function syncImages(WebProduct $product, Request $request, array &$stored): array
    {
        $removed = [];
        $removeIds = array_map('intval', (array) $request->input('remove_images', []));
        $next = 0;

        // Only this product's own images are touched, whatever ids were posted.
        foreach ($product->images()->get() as $image) {
            if (in_array((int) $image->id, $removeIds, true)) {
                $removed[] = $image->path;
                $image->delete();
                continue;
            }

            $order = $request->input("image_order.{$image->id}");
            if (filled($order) && (int) $order !== (int) $image->sort_order) {
                $image->update(['sort_order' => (int) $order]);
            }

            $next = max($next, (int) $image->sort_order + 1);
        }

        foreach ((array) $request->file('images', []) as $file) {
            $path = WebUpload::store($file, self::UPLOAD_DIR);
            if ($path === '') {
                throw new \RuntimeException('Foto produk gagal disimpan.');
            }
            $stored[] = $path;

            WebProductImage::create([
                'web_product_id' => $product->id,
                'path' => $path,
                'sort_order' => $next++,
            ]);
        }

        return $removed;
    }

    /** @param  array<int, string>  $paths */
    private function discard(array $paths): void
    {
        foreach ($paths as $path) {
            WebUpload::delete($path);
        }
    }
}
