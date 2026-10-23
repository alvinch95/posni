<?php

namespace Database\Seeders;

use App\Models\Hamper;
use App\Models\WebCategory;
use App\Models\WebLink;
use App\Models\WebProduct;
use App\Models\WebProductImage;
use App\Models\WebProductOption;
use App\Models\WebTestimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo content so the CMS and the public API can be tried locally:
 *
 *   php artisan db:seed --class=WebsiteDemoSeeder
 *
 * LOCAL ONLY. It refuses to run in production. Re-running is safe: products are
 * updateOrCreate'd by slug, everything else is created only when it is missing.
 */
class WebsiteDemoSeeder extends Seeder
{
    private const IMAGE_DIR = 'website/products';

    /**
     * One storefront product per package P. The hampers behind it are looked up by name:
     * engraved = "P Grafir" (or the exact "P"), plain = "P Tanpa Grafir".
     */
    private const PRODUCTS = [
        [
            'package' => 'Solar F',
            'slug' => 'solar-f',
            'image' => 'kayuni 1.jpg',
            'category' => 'birthday',
            'bestseller' => true,
            'featured' => true,
            'description_id' => 'Hamper kayu solid untuk kado ulang tahun. Nama penerima bisa kamu grafir langsung di kayunya.',
            'description_en' => 'A solid wood hamper made for birthday gifts. You can have the recipient\'s name engraved right into the wood.',
        ],
        [
            'package' => 'Black D',
            'slug' => 'black-d',
            'image' => 'kayuni 2.jpg',
            'category' => 'wedding',
            'bestseller' => false,
            'featured' => true,
            'description_id' => 'Hamper kayu berwarna gelap yang elegan, pas buat kado pernikahan. Tambahkan grafir nama pasangan kalau mau.',
            'description_en' => 'An elegant dark wood hamper that suits a wedding gift. Add the couple\'s names as an engraving if you like.',
        ],
        [
            'package' => 'Kappa A',
            'slug' => 'kappa-a',
            'image' => 'kayuni 3.jpg',
            'category' => 'graduation',
            'bestseller' => false,
            'featured' => true,
            'description_id' => 'Hamper kayu yang rapi dan simpel buat rayain kelulusan. Bisa dipersonalisasi dengan kartu ucapan dari kamu.',
            'description_en' => 'A neat, simple wood hamper to celebrate a graduation. Personalise it with a greeting card from you.',
        ],
        [
            'package' => 'Orion D',
            'slug' => 'orion-d',
            'image' => 'kayuni 4.jpg',
            'category' => 'anniversary',
            'bestseller' => false,
            'featured' => true,
            'description_id' => 'Hamper kayu hangat buat anniversary atau Valentine. Grafir nama kalian berdua biar makin personal.',
            'description_en' => 'A warm wood hamper for an anniversary or Valentine\'s Day. Engrave both your names to make it personal.',
        ],
        [
            'package' => 'Leo',
            'slug' => 'leo',
            'image' => 'kayuni 5.jpg',
            'category' => 'farewell',
            'bestseller' => false,
            'featured' => false,
            'description_id' => 'Hamper kayu buat kenang-kenangan farewell atau resign kantor. Siap dikirim H+1.',
            'description_en' => 'A wood hamper to remember a farewell or a colleague leaving the office. Ready to ship the next day.',
        ],
    ];

    /**
     * Copied literally from the frontend messages (Testimonials.t1* to t3*), which are
     * removed once the CMS serves testimonials.
     */
    private const TESTIMONIALS = [
        [
            'name' => 'Nadia',
            'quote_id' => 'Packaging rapi banget, grafir nama jelas. Penerima sampe screenshot buat dipajang. Worth it.',
            'quote_en' => 'Packaging was beautifully done, engraving was crisp. The recipient even screenshot it to display. Worth every rupiah.',
            'occasion_id' => 'Birthday gift',
            'occasion_en' => 'Birthday gift',
        ],
        [
            'name' => 'Reza',
            'quote_id' => 'Pesan H-1 buat farewell partner kerja, datang tepat hari H. Tim CS responsif, hasilnya rapi.',
            'quote_en' => 'Ordered the day before for a colleague\'s farewell, arrived right on time. CS was responsive, result was clean.',
            'occasion_id' => 'Office farewell',
            'occasion_en' => 'Office farewell',
        ],
        [
            'name' => 'Salwa',
            'quote_id' => 'Bahan kayu berasa premium, beda dari hampers biasa. Cocok buat kado nikahan teman dekat.',
            'quote_en' => 'The wood quality felt premium, different from typical hampers. Perfect for a close friend\'s wedding gift.',
            'occasion_id' => 'Wedding gift',
            'occasion_en' => 'Wedding gift',
        ],
    ];

    /** The placeholder links from the frontend footer. The WhatsApp link lives in contact settings. */
    private const LINKS = [
        ['group' => 'social', 'platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/kayuni'],
        ['group' => 'social', 'platform' => 'tiktok', 'label' => 'TikTok', 'url' => 'https://tiktok.com/@kayuni'],
        ['group' => 'marketplace', 'platform' => 'shopee', 'label' => 'Shopee', 'url' => 'https://shopee.co.id/kayuni'],
        ['group' => 'marketplace', 'platform' => 'tokopedia', 'label' => 'Tokopedia', 'url' => 'https://tokopedia.com/kayuni'],
        ['group' => 'marketplace', 'platform' => 'toco', 'label' => 'ToCo', 'url' => 'https://toco.id/kayuni'],
    ];

    public function run()
    {
        if (app()->environment('production')) {
            if ($this->command) {
                $this->command->error('Demo seeder is local-only.');
            }

            return;
        }

        $this->call(WebsiteCategorySeeder::class);

        $this->seedTestimonials();
        $this->seedLinks();

        foreach (self::PRODUCTS as $order => $data) {
            $this->seedProduct($data, $order);
        }
    }

    /** Where the demo photos live. Overridable so tests can point it elsewhere. */
    protected function imageSourceDir(): string
    {
        return base_path('../website/frontend/public/assets/products');
    }

    private function seedTestimonials(): void
    {
        foreach (self::TESTIMONIALS as $order => $data) {
            WebTestimonial::firstOrCreate(
                ['name' => $data['name']],
                $data + ['sort_order' => $order, 'is_active' => false]
            );
        }
    }

    private function seedLinks(): void
    {
        $order = [];
        foreach (self::LINKS as $data) {
            $order[$data['group']] = $order[$data['group']] ?? 0;

            WebLink::firstOrCreate(
                ['url' => $data['url']],
                $data + ['sort_order' => $order[$data['group']]++, 'is_active' => false]
            );
        }
    }

    private function seedProduct(array $data, int $order): void
    {
        $package = $data['package'];

        $options = array_filter([
            'engraved' => $this->findHamper("{$package} Grafir") ?? $this->findHamper($package),
            'plain' => $this->findHamper("{$package} Tanpa Grafir"),
        ]);

        if ($options === []) {
            if ($this->command) {
                $this->command->warn("No hamper for {$package}, skipped");
            }

            return;
        }

        $product = WebProduct::withTrashed()->updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name_id' => $package,
                'name_en' => $package,
                'description_id' => $data['description_id'],
                'description_en' => $data['description_en'],
                'is_published' => true,
                'is_bestseller' => $data['bestseller'],
                'is_featured' => $data['featured'],
                'sort_order' => $order,
                'deleted_at' => null,
            ]
        );

        $sort = 0;
        foreach ($options as $kind => $hamperId) {
            WebProductOption::updateOrCreate(
                ['web_product_id' => $product->id, 'kind' => $kind],
                ['hamper_id' => $hamperId, 'sort_order' => $sort++]
            );
        }

        $category = WebCategory::where('slug', $data['category'])->value('id');
        $product->categories()->sync($category ? [$category] : []);

        $this->attachImage($product, $data['image']);
    }

    private function findHamper(string $name): ?int
    {
        // Hamper uses SoftDeletes, so a soft-deleted hamper is never picked.
        $id = Hamper::where('name', $name)->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    private function attachImage(WebProduct $product, string $file): void
    {
        $source = rtrim($this->imageSourceDir(), '/\\') . DIRECTORY_SEPARATOR . $file;
        if (! is_file($source)) {
            return;
        }

        // No spaces in the stored name: image URLs are built by plain concatenation.
        $path = self::IMAGE_DIR . '/' . str_replace(' ', '-', $file);

        $contents = file_get_contents($source);
        if ($contents === false) {
            return;
        }
        Storage::disk('public')->put($path, $contents);

        WebProductImage::updateOrCreate(
            ['web_product_id' => $product->id, 'path' => $path],
            ['sort_order' => 0]
        );
    }
}
