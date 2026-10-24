<?php

namespace Tests\Feature\Website;

use App\Models\WebCategory;
use App\Models\WebProductImage;
use Illuminate\Support\Facades\DB;

class ProductApiTest extends WebsiteTestCase
{
    private const LIST_URL = '/api/website/v1/products';

    /** A hamper that is in stock (one item, plenty of units). */
    private function stockedHamper(string $name = 'Hamper', int $price = 100000, int $stock = 5, ?string $image = null): int
    {
        return $this->hamper($name, $price, [[$this->item("Item {$name}", $stock), 1]], $image);
    }

    private function slugs(array $cards): array
    {
        return array_column($cards, 'slug');
    }

    public function test_lists_only_published_products_with_valid_options(): void
    {
        $this->webProduct(['slug' => 'live'], ['plain' => ['hamper_id' => $this->stockedHamper('A')]]);
        $this->webProduct(['slug' => 'draft', 'is_published' => false], ['plain' => ['hamper_id' => $this->stockedHamper('B')]]);
        $this->webProduct(['slug' => 'optionless']);

        $this->getJson(self::LIST_URL)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'live');
    }

    public function test_soft_deleted_products_are_not_listed(): void
    {
        $product = $this->webProduct(['slug' => 'gone'], ['plain' => ['hamper_id' => $this->stockedHamper('A')]]);
        $product->delete();

        $this->getJson(self::LIST_URL)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(self::LIST_URL . '/gone')->assertNotFound();
    }

    public function test_price_override_wins_and_price_from_is_minimum(): void
    {
        $engraved = $this->stockedHamper('Engraved', 94000);
        $plain = $this->stockedHamper('Plain', 89000);

        $this->webProduct(['slug' => 'solar-f'], [
            'engraved' => ['hamper_id' => $engraved],
            'plain' => ['hamper_id' => $plain, 'price_override' => 85000],
        ]);

        $this->getJson(self::LIST_URL)
            ->assertOk()
            ->assertJsonPath('data.0.priceFrom', 85000);

        $options = $this->getJson(self::LIST_URL . '/solar-f')->assertOk()->json('data.options');
        $this->assertSame([94000, 85000], array_column($options, 'price'));
        $this->assertSame(85000, $this->getJson(self::LIST_URL . '/solar-f')->json('data.priceFrom'));
    }

    public function test_options_are_ordered_engraved_then_plain_regardless_of_sort_order(): void
    {
        // "plain" is created first, so it gets the lower sort_order.
        $this->webProduct(['slug' => 'solar-f'], [
            'plain' => ['hamper_id' => $this->stockedHamper('Plain', 89000)],
            'engraved' => ['hamper_id' => $this->stockedHamper('Engraved', 94000)],
        ]);

        $options = $this->getJson(self::LIST_URL . '/solar-f')->assertOk()->json('data.options');

        $this->assertSame(['engraved', 'plain'], array_column($options, 'kind'));
        $this->assertSame([94000, 89000], array_column($options, 'price'));
    }

    public function test_stock_flags_follow_hamper_stock(): void
    {
        $engravedItem = $this->item('Engraved item', 0);
        $plainItem = $this->item('Plain item', 5);
        $engraved = $this->hamper('Engraved', 94000, [[$engravedItem, 1]]);
        $plain = $this->hamper('Plain', 89000, [[$plainItem, 1]]);

        $this->webProduct(['slug' => 'solar-f'], [
            'engraved' => ['hamper_id' => $engraved],
            'plain' => ['hamper_id' => $plain],
        ]);

        $options = $this->getJson(self::LIST_URL . '/solar-f')->assertOk()->json('data.options');
        $this->assertSame([false, true], array_column($options, 'inStock'));
        $this->getJson(self::LIST_URL)->assertOk()->assertJsonPath('data.0.inStock', true);

        DB::table('items')->where('id', $plainItem)->update(['stock' => 0]);

        $this->getJson(self::LIST_URL)->assertOk()->assertJsonPath('data.0.inStock', false);
        $this->getJson(self::LIST_URL . '/solar-f')->assertOk()->assertJsonPath('data.inStock', false);
    }

    public function test_option_with_soft_deleted_hamper_is_dropped_and_lone_product_hidden(): void
    {
        $deleted = $this->hamper('Deleted', 90000, [[$this->item('Deleted item', 5), 1]], null, null, true);

        // Mixed product: the live option survives, the soft-deleted and the missing hamper are dropped.
        $this->webProduct(['slug' => 'mixed'], [
            'engraved' => ['hamper_id' => $this->stockedHamper('Live', 94000)],
            'plain' => ['hamper_id' => $deleted],
        ]);
        // Lone product: its only option points at a soft-deleted hamper.
        $this->webProduct(['slug' => 'lone'], ['plain' => ['hamper_id' => $deleted]]);
        // Lone product whose hamper row does not exist at all.
        $this->webProduct(['slug' => 'orphan'], ['plain' => ['hamper_id' => 999999]]);

        $cards = $this->getJson(self::LIST_URL)->assertOk()->json('data');
        $this->assertSame(['mixed'], $this->slugs($cards));

        $options = $this->getJson(self::LIST_URL . '/mixed')->assertOk()->json('data.options');
        $this->assertCount(1, $options);
        $this->assertSame('engraved', $options[0]['kind']);

        $this->getJson(self::LIST_URL . '/lone')->assertNotFound();
        $this->getJson(self::LIST_URL . '/orphan')->assertNotFound();
    }

    public function test_option_without_a_positive_price_is_dropped_like_a_deleted_hamper(): void
    {
        $noPrice = $this->stockedHamper('No price', 90000);
        DB::table('hampers')->where('id', $noPrice)->update(['selling_price' => null]);
        $freeHamper = $this->stockedHamper('Free', 0);
        $good = $this->stockedHamper('Good', 94000);

        // No override: the hamper's own price decides, and null or 0 is not a price.
        $this->webProduct(['slug' => 'null-price'], [
            'engraved' => ['hamper_id' => $good],
            'plain' => ['hamper_id' => $noPrice],
        ]);
        $this->webProduct(['slug' => 'zero-price'], [
            'engraved' => ['hamper_id' => $good],
            'plain' => ['hamper_id' => $freeHamper],
        ]);

        foreach (['null-price', 'zero-price'] as $slug) {
            $options = $this->getJson(self::LIST_URL . '/' . $slug)->assertOk()->json('data.options');
            $this->assertSame(['engraved'], array_column($options, 'kind'), $slug);
            $this->assertSame([94000], array_column($options, 'price'), $slug);
            $this->getJson(self::LIST_URL . '/' . $slug)->assertJsonPath('data.priceFrom', 94000);
        }

        $cards = collect($this->getJson(self::LIST_URL)->assertOk()->json('data'))->keyBy('slug');
        $this->assertSame(94000, $cards['null-price']['priceFrom']);
        $this->assertSame(94000, $cards['zero-price']['priceFrom']);
    }

    public function test_override_decides_validity_over_the_hamper_price(): void
    {
        $free = $this->stockedHamper('Free', 0);
        $priced = $this->stockedHamper('Priced', 80000);

        // A positive override rescues a hamper without a price.
        $this->webProduct(['slug' => 'rescued'], ['plain' => ['hamper_id' => $free, 'price_override' => 50000]]);
        // A stored 0 override (validation rejects it, but a hand-made row could carry it) is not a price,
        // even though the hamper itself has one.
        $this->webProduct(['slug' => 'zero-override'], ['plain' => ['hamper_id' => $priced, 'price_override' => 0]]);

        $this->getJson(self::LIST_URL . '/rescued')->assertOk()->assertJsonPath('data.priceFrom', 50000);
        $this->getJson(self::LIST_URL . '/zero-override')->assertNotFound();
        $this->assertSame(['rescued'], $this->slugs($this->getJson(self::LIST_URL)->assertOk()->json('data')));
    }

    public function test_product_whose_only_option_has_no_price_is_hidden_everywhere(): void
    {
        $category = WebCategory::create(['slug' => 'birthday', 'name_id' => 'Birthday']);
        $free = $this->stockedHamper('Free', 0);
        $noPrice = $this->stockedHamper('No price', 90000);
        DB::table('hampers')->where('id', $noPrice)->update(['selling_price' => null]);

        $ok = $this->webProduct(['slug' => 'ok'], ['plain' => ['hamper_id' => $this->stockedHamper('Ok', 60000)]]);
        $ok->categories()->attach($category->id);
        foreach (['free' => $free, 'no-price' => $noPrice] as $slug => $hamper) {
            $this->webProduct(['slug' => $slug], ['engraved' => ['hamper_id' => $hamper]])->categories()->attach($category->id);
        }

        $this->assertSame(['ok'], $this->slugs($this->getJson(self::LIST_URL)->assertOk()->json('data')));
        $this->getJson(self::LIST_URL . '/free')->assertNotFound();
        $this->getJson(self::LIST_URL . '/no-price')->assertNotFound();

        // productCount follows the same rule as the list.
        $this->getJson('/api/website/v1/categories')->assertOk()->assertJsonPath('data.0.productCount', 1);
        $this->assertSame(3, WebCategory::first()->products()->count(), 'sanity: all three are attached');
    }

    public function test_hamper_with_soft_deleted_item_counts_as_out_of_stock(): void
    {
        // Hamper::getStock() dereferences the (soft-deleted, so null) item and can throw.
        $hamper = $this->hamper('Broken', 90000, [[$this->item('Gone item', 5, 'pcs', true), 1]]);
        $this->webProduct(['slug' => 'broken'], ['plain' => ['hamper_id' => $hamper]]);

        $this->getJson(self::LIST_URL . '/broken')
            ->assertOk()
            ->assertJsonPath('data.options.0.inStock', false)
            ->assertJsonPath('data.inStock', false);

        $this->getJson(self::LIST_URL)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.inStock', false);
    }

    public function test_english_falls_back_to_indonesian(): void
    {
        $hamper = $this->stockedHamper('A');
        $this->webProduct(['slug' => 'no-en', 'name_id' => 'Hampers Solar', 'name_en' => null], ['plain' => ['hamper_id' => $hamper]]);

        $this->getJson(self::LIST_URL . '?locale=en')->assertOk()->assertJsonPath('data.0.name', 'Hampers Solar');
        $this->getJson(self::LIST_URL . '?locale=xx')->assertOk()->assertJsonPath('data.0.name', 'Hampers Solar');
        $this->getJson(self::LIST_URL)->assertOk()->assertJsonPath('data.0.name', 'Hampers Solar');
    }

    public function test_english_is_used_when_present_and_only_for_locale_en(): void
    {
        $this->webProduct([
            'slug' => 'bilingual',
            'name_id' => 'Hampers Solar',
            'name_en' => 'Solar Hamper',
            'description_id' => 'Deskripsi',
            'description_en' => 'Description',
            'contents_id' => "Gelas kayu\nCoaster",
            'contents_en' => "Wooden cup\nCoaster set",
        ], ['plain' => ['hamper_id' => $this->stockedHamper('A')]]);

        $en = $this->getJson(self::LIST_URL . '/bilingual?locale=en')->assertOk()->json('data');
        $this->assertSame('Solar Hamper', $en['name']);
        $this->assertSame('Description', $en['description']);
        $this->assertSame(['Wooden cup', 'Coaster set'], $en['contents']);

        $id = $this->getJson(self::LIST_URL . '/bilingual?locale=EN')->assertOk()->json('data');
        $this->assertSame('Hampers Solar', $id['name']);
        $this->assertSame(['Gelas kayu', 'Coaster'], $id['contents']);
    }

    public function test_image_urls_are_absolute_without_double_slash(): void
    {
        config(['app.url' => 'http://posni.test/']);

        $product = $this->webProduct(['slug' => 'pic'], ['plain' => ['hamper_id' => $this->stockedHamper('A')]]);
        WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/b.jpg', 'sort_order' => 1]);
        WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/a.jpg', 'sort_order' => 0]);

        $this->getJson(self::LIST_URL)
            ->assertOk()
            ->assertJsonPath('data.0.image', 'http://posni.test/storage/website/products/a.jpg');

        $this->getJson(self::LIST_URL . '/pic')
            ->assertOk()
            ->assertJsonPath('data.image', 'http://posni.test/storage/website/products/a.jpg')
            ->assertJsonPath('data.images', [
                'http://posni.test/storage/website/products/a.jpg',
                'http://posni.test/storage/website/products/b.jpg',
            ]);
    }

    public function test_image_falls_back_to_hamper_image(): void
    {
        config(['app.url' => 'http://posni.test/']);

        $this->webProduct(['slug' => 'from-hamper'], [
            'engraved' => ['hamper_id' => $this->stockedHamper('Engraved', 94000, 5, 'hampers-images/solar.jpg')],
            'plain' => ['hamper_id' => $this->stockedHamper('Plain', 89000, 5, 'hampers-images/other.jpg')],
        ]);
        $this->webProduct(['slug' => 'no-image'], ['plain' => ['hamper_id' => $this->stockedHamper('Bare')]]);

        $cards = collect($this->getJson(self::LIST_URL)->assertOk()->json('data'))->keyBy('slug');

        $this->assertSame('http://posni.test/storage/hampers-images/solar.jpg', $cards['from-hamper']['image']);
        $this->assertNull($cards['no-image']['image']);
    }

    public function test_product_image_wins_over_hamper_image(): void
    {
        config(['app.url' => 'http://posni.test']);

        $product = $this->webProduct(['slug' => 'both'], [
            'plain' => ['hamper_id' => $this->stockedHamper('A', 89000, 5, 'hampers-images/solar.jpg')],
        ]);
        WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/own.jpg', 'sort_order' => 0]);

        $this->getJson(self::LIST_URL . '/both')
            ->assertOk()
            ->assertJsonPath('data.image', 'http://posni.test/storage/website/products/own.jpg');
    }

    public function test_detail_returns_images_contents_options(): void
    {
        config(['app.url' => 'http://posni.test']);

        $category = WebCategory::create(['slug' => 'birthday', 'name_id' => 'Birthday']);
        WebCategory::create(['slug' => 'hidden', 'name_id' => 'Hidden', 'is_active' => false]);

        $product = $this->webProduct([
            'slug' => 'solar-f',
            'name_id' => 'Solar F',
            'description_id' => "Hampers kayu\nuntuk kamu",
            'contents_id' => "Gelas kayu\n  Coaster  \r\n\n\nKartu ucapan\n",
            'is_bestseller' => true,
            'is_featured' => true,
            'sort_order' => 3,
        ], [
            'engraved' => ['hamper_id' => $this->stockedHamper('Solar F Grafir', 94000)],
        ]);
        $product->categories()->attach($category->id);
        $product->categories()->attach(WebCategory::where('slug', 'hidden')->value('id'));
        WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/a.jpg', 'sort_order' => 0]);

        $response = $this->getJson(self::LIST_URL . '/solar-f')->assertOk();
        $data = $response->json('data');

        $this->assertSame('solar-f', $data['slug']);
        $this->assertSame('Solar F', $data['name']);
        $this->assertSame(94000, $data['priceFrom']);
        $this->assertTrue($data['inStock']);
        $this->assertTrue($data['isBestseller']);
        $this->assertTrue($data['isFeatured']);
        $this->assertSame(['birthday'], $data['categories']);
        $this->assertSame(3, $data['sortOrder']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/', $data['createdAt']);
        $this->assertSame(['http://posni.test/storage/website/products/a.jpg'], $data['images']);
        $this->assertSame("Hampers kayu\nuntuk kamu", $data['description']);
        $this->assertSame(['Gelas kayu', 'Coaster', 'Kartu ucapan'], $data['contents']);
        $this->assertCount(1, $data['options']);
        $this->assertSame('engraved', $data['options'][0]['kind']);
        $this->assertSame(94000, $data['options'][0]['price']);
        $this->assertTrue($data['options'][0]['inStock']);
        $this->assertIsInt($data['options'][0]['id']);
    }

    public function test_detail_is_404_for_unpublished_or_unknown_slug(): void
    {
        $this->webProduct(['slug' => 'draft', 'is_published' => false], ['plain' => ['hamper_id' => $this->stockedHamper('A')]]);
        $this->webProduct(['slug' => 'optionless']);

        foreach (['draft', 'optionless', 'does-not-exist'] as $slug) {
            $response = $this->getJson(self::LIST_URL . '/' . $slug)->assertNotFound();
            $this->assertSame('{"message":"Not found"}', $response->getContent(), "slug {$slug}");
        }
    }

    public function test_responses_never_expose_cost_fields(): void
    {
        $item = $this->item('Gelas Rahasia', 5);
        DB::table('items')->where('id', $item)->update(['purchase_price' => 11111, 'selling_price' => 22222]);
        $hamper = $this->hamper('Solar F Grafir', 94000, [[$item, 1]], 'hampers-images/solar.jpg');
        DB::table('hampers')->where('id', $hamper)->update(['capital_price' => 33333, 'revenue_percentage' => 40]);

        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F'], [
            'engraved' => ['hamper_id' => $hamper],
        ]);
        WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/a.jpg', 'sort_order' => 0]);

        $list = $this->getJson(self::LIST_URL)->assertOk();
        $detail = $this->getJson(self::LIST_URL . '/solar-f')->assertOk();

        foreach ([$list, $detail] as $response) {
            $body = $response->getContent();
            foreach (['capital_price', 'purchase_price', 'revenue_percentage', 'selling_price', '"stock"', 'Solar F Grafir', 'Gelas Rahasia', '33333', '22222', '11111'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $body, "leaked {$forbidden}");
            }
        }

        // Allow-list: exactly these keys, nothing more.
        $cardKeys = ['categories', 'createdAt', 'image', 'inStock', 'isBestseller', 'isFeatured', 'name', 'priceFrom', 'slug', 'sortOrder'];
        sort($cardKeys);
        $card = $list->json('data.0');
        $this->assertSame($cardKeys, $this->sortedKeys($card));

        $detailKeys = array_merge($cardKeys, ['contents', 'description', 'images', 'options']);
        sort($detailKeys);
        $data = $detail->json('data');
        $this->assertSame($detailKeys, $this->sortedKeys($data));
        $this->assertSame(['id', 'inStock', 'kind', 'price'], $this->sortedKeys($data['options'][0]));
    }

    public function test_products_sorted_by_sort_order_then_newest(): void
    {
        $make = fn (string $slug, int $order) => $this->webProduct(
            ['slug' => $slug, 'sort_order' => $order],
            ['plain' => ['hamper_id' => $this->stockedHamper($slug)]]
        );

        $make('late', 1);
        $make('first-old', 0);
        $make('first-new', 0);

        $cards = $this->getJson(self::LIST_URL)->assertOk()->json('data');

        $this->assertSame(['first-new', 'first-old', 'late'], $this->slugs($cards));
    }

    public function test_empty_catalog_returns_empty_data_array_without_auth(): void
    {
        $this->getJson(self::LIST_URL)->assertOk()->assertExactJson(['data' => []]);
    }

    private function sortedKeys(array $row): array
    {
        $keys = array_keys($row);
        sort($keys);

        return $keys;
    }
}
