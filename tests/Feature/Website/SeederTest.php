<?php

namespace Tests\Feature\Website;

use App\Models\WebCategory;
use App\Models\WebLink;
use App\Models\WebProduct;
use App\Models\WebTestimonial;
use Database\Seeders\WebsiteCategorySeeder;
use Database\Seeders\WebsiteDemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * These run db:seed against the in-memory sqlite database that WebsiteTestCase switches to,
 * never the real (production copy) MySQL database.
 */
class SeederTest extends WebsiteTestCase
{
    public function test_category_seeder_inserts_once_and_never_overwrites(): void
    {
        $this->seed(WebsiteCategorySeeder::class);

        $this->assertSame(6, WebCategory::count());
        $this->assertSame(
            ['birthday', 'wedding', 'graduation', 'anniversary', 'farewell', 'corporate'],
            WebCategory::orderBy('sort_order')->pluck('slug')->all()
        );
        $this->assertSame([0, 1, 2, 3, 4, 5], WebCategory::orderBy('sort_order')->pluck('sort_order')->all());

        $corporate = WebCategory::where('slug', 'corporate')->first();
        $this->assertSame('Corporate Gift', $corporate->name_id);
        $this->assertSame('Corporate Gift', $corporate->name_en);

        WebCategory::where('slug', 'birthday')->update(['name_id' => 'Ulang Tahun']);

        $this->seed(WebsiteCategorySeeder::class);

        $this->assertSame(6, WebCategory::count());
        $this->assertSame('Ulang Tahun', WebCategory::where('slug', 'birthday')->value('name_id'));
    }

    public function test_category_seeder_does_nothing_when_a_category_already_exists(): void
    {
        WebCategory::create(['slug' => 'custom', 'name_id' => 'Custom']);

        $this->seed(WebsiteCategorySeeder::class);

        $this->assertSame(['custom'], WebCategory::pluck('slug')->all());
    }

    public function test_demo_seeder_refuses_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        // --force skips db:seed's own production prompt, so the seeder's guard is what stops it.
        Artisan::call('db:seed', ['--class' => WebsiteDemoSeeder::class, '--force' => true]);

        $this->assertStringContainsString('Demo seeder is local-only.', Artisan::output());
        $this->assertSame(0, WebTestimonial::count());
        $this->assertSame(0, WebLink::count());
        $this->assertSame(0, WebProduct::count());
        $this->assertSame(0, WebCategory::count());
    }

    public function test_demo_seeder_links_grafir_and_tanpa_grafir_hampers(): void
    {
        Storage::fake('public');
        $engraved = $this->hamper('Solar F Grafir', 150000);
        $plain = $this->hamper('Solar F Tanpa Grafir', 130000);

        $this->seed(WebsiteDemoSeeder::class);

        $product = WebProduct::where('slug', 'solar-f')->firstOrFail();
        $this->assertCount(2, $product->options);
        $this->assertSame($engraved, (int) $product->options->firstWhere('kind', 'engraved')->hamper_id);
        $this->assertSame($plain, (int) $product->options->firstWhere('kind', 'plain')->hamper_id);

        $this->assertSame(3, WebTestimonial::count());
        $this->assertSame(0, WebTestimonial::where('is_active', true)->count());
    }

    public function test_demo_seeder_falls_back_to_the_exact_hamper_name_when_there_is_no_grafir_variant(): void
    {
        Storage::fake('public');
        $exact = $this->hamper('Kappa A', 99000);

        $this->seed(WebsiteDemoSeeder::class);

        $product = WebProduct::where('slug', 'kappa-a')->firstOrFail();
        $this->assertCount(1, $product->options);
        $this->assertSame('engraved', $product->options->first()->kind);
        $this->assertSame($exact, (int) $product->options->first()->hamper_id);
    }

    public function test_demo_seeder_prefers_grafir_over_the_exact_name(): void
    {
        Storage::fake('public');
        $this->hamper('Kappa A', 90000);
        $grafir = $this->hamper('Kappa A Grafir', 99000);

        $this->seed(WebsiteDemoSeeder::class);

        $options = WebProduct::where('slug', 'kappa-a')->firstOrFail()->options;
        $this->assertCount(1, $options);
        $this->assertSame($grafir, (int) $options->first()->hamper_id);
    }

    public function test_demo_seeder_ignores_soft_deleted_hampers(): void
    {
        Storage::fake('public');
        $this->hamper('Solar F Grafir', 150000, [], null, null, true);
        $plain = $this->hamper('Solar F Tanpa Grafir', 130000);

        $this->seed(WebsiteDemoSeeder::class);

        $options = WebProduct::where('slug', 'solar-f')->firstOrFail()->options;
        $this->assertCount(1, $options);
        $this->assertSame('plain', $options->first()->kind);
        $this->assertSame($plain, (int) $options->first()->hamper_id);
    }

    public function test_demo_seeder_skips_a_package_without_hampers_and_warns(): void
    {
        Storage::fake('public');
        $this->hamper('Solar F Grafir', 150000);

        Artisan::call('db:seed', ['--class' => WebsiteDemoSeeder::class]);
        $output = Artisan::output(); // reading it empties the buffer, so read once

        $this->assertNotNull(WebProduct::where('slug', 'solar-f')->first());
        $this->assertNull(WebProduct::where('slug', 'leo')->first());
        $this->assertStringContainsString('No hamper for Leo, skipped', $output);
        $this->assertStringContainsString('No hamper for Kappa A, skipped', $output);
        $this->assertStringNotContainsString('No hamper for Solar F, skipped', $output);
    }

    public function test_demo_seeder_sets_publish_flags_and_categories(): void
    {
        Storage::fake('public');
        foreach (['Solar F', 'Black D', 'Kappa A', 'Orion D', 'Leo'] as $package) {
            $this->hamper("{$package} Grafir", 150000);
            $this->hamper("{$package} Tanpa Grafir", 130000);
        }

        $this->seed(WebsiteDemoSeeder::class);

        $expected = [
            // slug => [category, bestseller, featured]
            'solar-f' => ['birthday', true, true],
            'black-d' => ['wedding', false, true],
            'kappa-a' => ['graduation', false, true],
            'orion-d' => ['anniversary', false, true],
            'leo' => ['farewell', false, false],
        ];

        $this->assertSame(5, WebProduct::count());
        foreach ($expected as $slug => [$category, $bestseller, $featured]) {
            $product = WebProduct::where('slug', $slug)->firstOrFail();
            $this->assertTrue($product->is_published, "{$slug} published");
            $this->assertSame($bestseller, $product->is_bestseller, "{$slug} bestseller");
            $this->assertSame($featured, $product->is_featured, "{$slug} featured");
            $this->assertSame([$category], $product->categories->pluck('slug')->all(), "{$slug} category");
            $this->assertNotSame('', trim($product->name_id));
            $this->assertNotSame('', trim($product->description_id));
        }
    }

    public function test_demo_seeder_seeds_literal_testimonials_and_inactive_placeholder_links(): void
    {
        Storage::fake('public');

        $this->seed(WebsiteDemoSeeder::class);

        $nadia = WebTestimonial::where('name', 'Nadia')->firstOrFail();
        $this->assertSame('Packaging rapi banget, grafir nama jelas. Penerima sampe screenshot buat dipajang. Worth it.', $nadia->quote_id);
        $this->assertSame('Packaging was beautifully done, engraving was crisp. The recipient even screenshot it to display. Worth every rupiah.', $nadia->quote_en);
        $this->assertSame('Birthday gift', $nadia->occasion_id);
        $this->assertSame('Birthday gift', $nadia->occasion_en);
        $this->assertSame(['Nadia', 'Reza', 'Salwa'], WebTestimonial::orderBy('sort_order')->pluck('name')->all());
        $this->assertSame('Office farewell', WebTestimonial::where('name', 'Reza')->value('occasion_en'));
        $this->assertSame('Wedding gift', WebTestimonial::where('name', 'Salwa')->value('occasion_id'));

        $this->assertSame(5, WebLink::count());
        $this->assertSame(0, WebLink::where('is_active', true)->count());
        $this->assertEqualsCanonicalizing([
            'https://instagram.com/kayuni',
            'https://tiktok.com/@kayuni',
            'https://shopee.co.id/kayuni',
            'https://tokopedia.com/kayuni',
            'https://toco.id/kayuni',
        ], WebLink::pluck('url')->all());
        $this->assertSame('instagram', WebLink::where('url', 'https://instagram.com/kayuni')->value('platform'));
        $this->assertSame('social', WebLink::where('url', 'https://tiktok.com/@kayuni')->value('group'));
        $this->assertSame('marketplace', WebLink::where('url', 'https://toco.id/kayuni')->value('group'));
        $this->assertSame(['shopee', 'tokopedia', 'toco'], WebLink::where('group', 'marketplace')->orderBy('sort_order')->pluck('platform')->all());
        $this->assertSame(0, WebLink::where('url', 'like', '%wa.me%')->count());
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        Storage::fake('public');
        $this->hamper('Solar F Grafir', 150000);
        $this->hamper('Solar F Tanpa Grafir', 130000);

        $this->seed(WebsiteDemoSeeder::class);
        WebProduct::where('slug', 'solar-f')->update(['name_id' => 'Diubah']);
        $this->seed(WebsiteDemoSeeder::class);

        $this->assertSame(1, WebProduct::count());
        $this->assertSame('Solar F', WebProduct::where('slug', 'solar-f')->value('name_id'));
        $this->assertSame(2, WebProduct::first()->options()->count());
        $this->assertSame(1, WebProduct::first()->categories()->count());
        $this->assertSame(1, WebProduct::first()->images()->count());
        $this->assertSame(6, WebCategory::count());
        $this->assertSame(3, WebTestimonial::count());
        $this->assertSame(5, WebLink::count());
    }

    public function test_demo_seeder_copies_the_product_image_to_the_public_disk(): void
    {
        $source = base_path('../website/frontend/public/assets/products/kayuni 1.jpg');
        if (! is_file($source)) {
            $this->markTestSkipped('website/frontend assets are not checked out next to posni.');
        }

        Storage::fake('public');
        $this->hamper('Solar F Grafir', 150000);

        $this->seed(WebsiteDemoSeeder::class);

        $product = WebProduct::where('slug', 'solar-f')->firstOrFail();
        $this->assertCount(1, $product->images);
        $path = $product->images->first()->path;
        $this->assertStringStartsWith('website/products/', $path);
        $this->assertStringNotContainsString(' ', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(filesize($source), Storage::disk('public')->size($path));
    }

    public function test_demo_seeder_skips_the_image_when_the_source_file_is_missing(): void
    {
        Storage::fake('public');
        $this->hamper('Solar F Grafir', 150000);

        $seeder = new class extends WebsiteDemoSeeder {
            protected function imageSourceDir(): string
            {
                return base_path('storage/does-not-exist');
            }
        };
        $seeder->run();

        $product = WebProduct::where('slug', 'solar-f')->firstOrFail();
        $this->assertSame(0, $product->images()->count());
        $this->assertSame([], Storage::disk('public')->allFiles('website/products'));
    }
}
