<?php

namespace Tests\Feature\Website;

use App\Models\WebCategory;
use App\Models\WebProduct;
use App\Models\WebProductImage;
use App\Models\WebProductOption;
use App\Services\Website\CatalogCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The public product and category payloads are cached for website.cache_seconds under a versioned
 * key; CMS edits bump the version so they show up at once. WebsiteTestCase turns caching off
 * (cache_seconds = 0), so every test here switches it on explicitly.
 */
class CatalogCacheTest extends WebsiteTestCase
{
    private const LIST_URL = '/api/website/v1/products';

    private const CATEGORIES_URL = '/api/website/v1/categories';

    private function cacheOn(int $seconds = 60): void
    {
        config(['website.cache_seconds' => $seconds]);
    }

    private function stockedHamper(string $name, int $price = 100000, int $stock = 5): int
    {
        return $this->hamper($name, $price, [[$this->item("Item {$name}", $stock), 1]]);
    }

    /** A published product with one in-stock option. */
    private function product(string $slug, ?string $name = null, int $price = 100000): WebProduct
    {
        return $this->webProduct(
            ['slug' => $slug, 'name_id' => $name ?? ucfirst($slug)],
            ['plain' => ['hamper_id' => $this->stockedHamper("Hamper {$slug}", $price)]]
        );
    }

    /** DB queries run by $request. */
    private function queries(callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_second_products_call_runs_far_fewer_queries(): void
    {
        $this->cacheOn();
        foreach (['a', 'b', 'c', 'd', 'e', 'f'] as $slug) {
            $this->product($slug);
        }

        $first = $this->queries(fn () => $this->getJson(self::LIST_URL)->assertOk()->assertJsonCount(6, 'data'));
        $second = $this->queries(fn () => $this->getJson(self::LIST_URL)->assertOk()->assertJsonCount(6, 'data'));

        $this->assertGreaterThan(15, $first, 'the uncached build costs several queries per product (that is the point of the cache)');
        $this->assertSame(0, $second, 'a cache hit must not touch the database');
    }

    public function test_second_detail_and_categories_calls_are_served_from_cache(): void
    {
        $this->cacheOn();
        $this->product('solar-f');
        WebCategory::create(['slug' => 'birthday', 'name_id' => 'Birthday']);

        foreach ([self::LIST_URL . '/solar-f', self::CATEGORIES_URL] as $url) {
            $first = $this->queries(fn () => $this->getJson($url)->assertOk());
            $second = $this->queries(fn () => $this->getJson($url)->assertOk());

            $this->assertGreaterThan(0, $first, $url);
            $this->assertSame(0, $second, $url);
        }
    }

    public function test_locale_is_part_of_the_cache_key(): void
    {
        $this->cacheOn();
        $this->webProduct(['slug' => 'bi', 'name_id' => 'Hamper Solar', 'name_en' => 'Solar Hamper'], [
            'plain' => ['hamper_id' => $this->stockedHamper('A')],
        ]);

        $this->getJson(self::LIST_URL)->assertJsonPath('data.0.name', 'Hamper Solar');
        $this->getJson(self::LIST_URL . '?locale=en')->assertJsonPath('data.0.name', 'Solar Hamper');
        $this->getJson(self::LIST_URL)->assertJsonPath('data.0.name', 'Hamper Solar');
        $this->getJson(self::LIST_URL . '/bi?locale=en')->assertJsonPath('data.name', 'Solar Hamper');
        $this->getJson(self::LIST_URL . '/bi')->assertJsonPath('data.name', 'Hamper Solar');
    }

    public function test_cached_payload_ignores_changes_outside_the_cms_until_the_ttl_ends(): void
    {
        $this->cacheOn();
        $item = $this->item('Solo', 5);
        $hamper = $this->hamper('Solo hamper', 100000, [[$item, 1]]);
        $this->webProduct(['slug' => 'solo'], ['plain' => ['hamper_id' => $hamper]]);

        $this->getJson(self::LIST_URL)->assertJsonPath('data.0.inStock', true);

        // A sale in the POS sells the last unit: not a website model, so no version bump.
        DB::table('items')->where('id', $item)->update(['stock' => 0]);

        $this->getJson(self::LIST_URL)->assertJsonPath('data.0.inStock', true);
    }

    public function test_cms_product_save_shows_up_on_the_next_call(): void
    {
        $this->cacheOn();
        $product = $this->product('solar-f', 'Solar F');
        $admin = $this->admin();

        $this->getJson(self::LIST_URL)->assertJsonPath('data.0.name', 'Solar F');
        $this->getJson(self::LIST_URL . '/solar-f')->assertJsonPath('data.name', 'Solar F');

        $hamper = $product->options->first()->hamper_id;
        $this->actingAs($admin)
            ->put(route('website.products.update', $product), [
                'name_id' => 'Solar F Baru',
                'slug' => 'solar-f',
                'is_published' => 1,
                'options' => ['plain' => ['hamper_id' => $hamper]],
            ])
            ->assertSessionHasNoErrors();

        $this->getJson(self::LIST_URL)->assertJsonPath('data.0.name', 'Solar F Baru');
        $this->getJson(self::LIST_URL . '/solar-f')->assertJsonPath('data.name', 'Solar F Baru');
    }

    public function test_unpublishing_and_deleting_in_the_cms_hide_the_product_at_once(): void
    {
        $this->cacheOn();
        $one = $this->product('one');
        $two = $this->product('two');

        $this->getJson(self::LIST_URL)->assertJsonCount(2, 'data');
        $this->getJson(self::LIST_URL . '/one')->assertOk();
        $this->getJson(self::LIST_URL . '/two')->assertOk();

        $one->update(['is_published' => false]);
        $this->getJson(self::LIST_URL)->assertJsonCount(1, 'data');
        $this->getJson(self::LIST_URL . '/one')->assertNotFound();

        $this->actingAs($this->admin())->delete(route('website.products.destroy', $two));
        $this->getJson(self::LIST_URL)->assertJsonCount(0, 'data');
        $this->getJson(self::LIST_URL . '/two')->assertNotFound();
    }

    public function test_option_image_and_category_changes_bump_the_version(): void
    {
        $this->cacheOn();
        config(['app.url' => 'http://posni.test']);
        $product = $this->product('solar-f');
        $category = WebCategory::create(['slug' => 'birthday', 'name_id' => 'Birthday']);

        $this->getJson(self::LIST_URL . '/solar-f')->assertJsonPath('data.image', null);
        $this->getJson(self::CATEGORIES_URL)->assertJsonPath('data.0.name', 'Birthday');

        // image row
        WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/a.jpg', 'sort_order' => 0]);
        $this->getJson(self::LIST_URL . '/solar-f')->assertJsonPath('data.image', 'http://posni.test/storage/website/products/a.jpg');

        // option row (price override)
        $this->getJson(self::LIST_URL . '/solar-f')->assertJsonPath('data.priceFrom', 100000);
        WebProductOption::where('web_product_id', $product->id)->first()->update(['price_override' => 70000]);
        $this->getJson(self::LIST_URL . '/solar-f')->assertJsonPath('data.priceFrom', 70000);

        // category row
        $category->update(['name_id' => 'Ulang Tahun']);
        $this->getJson(self::CATEGORIES_URL)->assertJsonPath('data.0.name', 'Ulang Tahun');

        $category->delete();
        $this->getJson(self::CATEGORIES_URL)->assertJsonCount(0, 'data');
    }

    public function test_category_assignment_through_the_cms_shows_up_in_cards_and_counts(): void
    {
        $this->cacheOn();
        $product = $this->product('solar-f');
        $category = WebCategory::create(['slug' => 'birthday', 'name_id' => 'Birthday']);
        $hamper = $product->options->first()->hamper_id;

        $this->getJson(self::CATEGORIES_URL)->assertJsonPath('data.0.productCount', 0);
        $this->getJson(self::LIST_URL . '/solar-f')->assertJsonPath('data.categories', []);

        // The pivot sync fires no model event of its own, so the controller bumps explicitly.
        $this->actingAs($this->admin())
            ->put(route('website.products.update', $product), [
                'name_id' => 'Solar F',
                'slug' => 'solar-f',
                'is_published' => 1,
                'categories' => [$category->id],
                'options' => ['plain' => ['hamper_id' => $hamper]],
            ])
            ->assertSessionHasNoErrors();

        $this->getJson(self::CATEGORIES_URL)->assertJsonPath('data.0.productCount', 1);
        $this->getJson(self::LIST_URL . '/solar-f')->assertJsonPath('data.categories', ['birthday']);
    }

    public function test_removing_an_option_in_the_cms_shows_up_on_the_next_call(): void
    {
        $this->cacheOn();
        $product = $this->webProduct(['slug' => 'both'], [
            'engraved' => ['hamper_id' => $this->stockedHamper('E', 94000)],
            'plain' => ['hamper_id' => $this->stockedHamper('P', 89000)],
        ]);
        $engraved = $product->options->firstWhere('kind', 'engraved')->hamper_id;

        $this->getJson(self::LIST_URL . '/both')->assertJsonCount(2, 'data.options');

        $this->actingAs($this->admin())
            ->put(route('website.products.update', $product), [
                'name_id' => 'Both',
                'slug' => 'both',
                'is_published' => 1,
                'options' => ['engraved' => ['hamper_id' => $engraved], 'plain' => ['hamper_id' => '']],
            ])
            ->assertSessionHasNoErrors();

        $this->getJson(self::LIST_URL . '/both')->assertJsonCount(1, 'data.options');
    }

    public function test_the_version_only_moves_after_the_surrounding_transaction_commits(): void
    {
        $before = CatalogCache::version();

        DB::beginTransaction();
        WebCategory::create(['slug' => 'x', 'name_id' => 'X']);
        $this->assertSame($before, CatalogCache::version(), 'a rolled-back edit must not invalidate, and a request must not cache pre-commit data under the new version');
        DB::commit();

        $this->assertGreaterThan($before, CatalogCache::version());

        $mid = CatalogCache::version();
        DB::beginTransaction();
        WebCategory::create(['slug' => 'y', 'name_id' => 'Y']);
        DB::rollBack();
        $this->assertSame($mid, CatalogCache::version());
    }

    public function test_unknown_slugs_are_not_cached(): void
    {
        $this->cacheOn();

        $this->getJson(self::LIST_URL . '/later')->assertNotFound();

        // Created behind the model events' back: only an uncached 404 lets it show up immediately.
        WebProduct::withoutEvents(fn () => $this->product('later'));

        $this->getJson(self::LIST_URL . '/later')->assertOk()->assertJsonPath('data.slug', 'later');
    }

    public function test_very_long_slugs_are_served_but_never_cached(): void
    {
        $this->cacheOn();
        // The route already limits slugs to [a-z0-9-]; a slug this long would make an oversized cache
        // key (memcached caps keys at 250 characters), so it is built fresh instead.
        $long = str_repeat('a', 250);
        $this->product($long);
        $this->product('solar-f');

        $first = $this->queries(fn () => $this->getJson(self::LIST_URL . '/' . $long)->assertOk());
        $second = $this->queries(fn () => $this->getJson(self::LIST_URL . '/' . $long)->assertOk());
        $this->assertGreaterThan(0, $second);
        $this->assertSame($first, $second);

        // ... while a normal slug is cached.
        $this->getJson(self::LIST_URL . '/solar-f')->assertOk();
        $this->assertSame(0, $this->queries(fn () => $this->getJson(self::LIST_URL . '/solar-f')->assertOk()));
    }

    public function test_with_zero_seconds_nothing_is_cached(): void
    {
        $this->cacheOn(0);
        $this->product('solar-f');
        WebCategory::create(['slug' => 'birthday', 'name_id' => 'Birthday']);

        foreach ([self::LIST_URL, self::LIST_URL . '/solar-f', self::CATEGORIES_URL] as $url) {
            $first = $this->queries(fn () => $this->getJson($url)->assertOk());
            $second = $this->queries(fn () => $this->getJson($url)->assertOk());

            $this->assertGreaterThan(0, $second, $url);
            $this->assertSame($first, $second, $url);
        }

        $version = CatalogCache::version();
        foreach (['products:id', 'categories:id', 'product:id:solar-f'] as $name) {
            $this->assertFalse(Cache::has("website:catalog:v{$version}:{$name}"), $name);
        }
    }

    public function test_a_negative_value_is_treated_as_off(): void
    {
        $this->cacheOn(-5);
        $this->product('solar-f');

        $first = $this->queries(fn () => $this->getJson(self::LIST_URL)->assertOk());
        $second = $this->queries(fn () => $this->getJson(self::LIST_URL)->assertOk());

        $this->assertSame($first, $second);
    }

    public function test_default_ttl_is_sixty_seconds(): void
    {
        if (env('WEBSITE_CACHE_SECONDS') !== null) {
            $this->markTestSkipped('WEBSITE_CACHE_SECONDS is set in this environment.');
        }

        $this->assertSame(60, (require base_path('config/website.php'))['cache_seconds']);
    }
}
