<?php

namespace Tests\Feature\Website;

use App\Models\WebCategory;
use App\Models\WebFaq;
use App\Models\WebLink;
use App\Models\WebSetting;
use App\Models\WebTestimonial;
use Illuminate\Support\Facades\DB;

class ContentApiTest extends WebsiteTestCase
{
    private const BASE = '/api/website/v1';

    /** A hamper that is in stock (one item, plenty of units). */
    private function stockedHamper(string $name = 'Hamper', int $price = 100000): int
    {
        static $n = 0;
        $n++;

        return $this->hamper("{$name} {$n}", $price, [[$this->item("Item {$name} {$n}", 5), 1]]);
    }

    /** A published product with one valid option, attached to the given categories. */
    private function liveProduct(array $categoryIds, array $attrs = []): void
    {
        $product = $this->webProduct($attrs, ['plain' => ['hamper_id' => $this->stockedHamper()]]);
        $product->categories()->attach($categoryIds);
    }

    private function sortedKeys(array $row): array
    {
        $keys = array_keys($row);
        sort($keys);

        return $keys;
    }

    // ---------------------------------------------------------------- categories

    public function test_categories_only_active_with_published_product_counts(): void
    {
        config(['app.url' => 'http://posni.test/']);

        $birthday = WebCategory::create(['slug' => 'birthday', 'name_id' => 'Ulang Tahun', 'name_en' => 'Birthday', 'image' => 'website/categories/b.jpg', 'sort_order' => 1]);
        $wedding = WebCategory::create(['slug' => 'wedding', 'name_id' => 'Pernikahan', 'sort_order' => 0]);
        WebCategory::create(['slug' => 'empty', 'name_id' => 'Kosong', 'sort_order' => 2]);
        $hidden = WebCategory::create(['slug' => 'hidden', 'name_id' => 'Tersembunyi', 'is_active' => false, 'sort_order' => 0]);

        // Counts: published + valid only.
        $this->liveProduct([$birthday->id, $wedding->id]);
        $this->liveProduct([$birthday->id]);
        $this->liveProduct([$hidden->id]);

        // Not counted: unpublished, soft-deleted, and published-but-no-valid-option (hamper soft-deleted).
        $this->liveProduct([$birthday->id], ['is_published' => false]);

        $deletedProduct = $this->webProduct([], ['plain' => ['hamper_id' => $this->stockedHamper()]]);
        $deletedProduct->categories()->attach($birthday->id);
        $deletedProduct->delete();

        $softDeletedHamper = $this->hamper('Gone', 90000, [[$this->item('Gone item', 5), 1]], null, null, true);
        $noValidOption = $this->webProduct([], ['plain' => ['hamper_id' => $softDeletedHamper]]);
        $noValidOption->categories()->attach($birthday->id);

        $orphan = $this->webProduct([], ['plain' => ['hamper_id' => 999999]]);
        $orphan->categories()->attach($birthday->id);

        $data = $this->getJson(self::BASE . '/categories')->assertOk()->json('data');

        // Hidden category is gone; order = sort_order asc.
        $this->assertSame(['wedding', 'birthday', 'empty'], array_column($data, 'slug'));
        $this->assertSame([1, 2, 0], array_column($data, 'productCount'));

        $byslug = collect($data)->keyBy('slug');
        $this->assertSame('Pernikahan', $byslug['wedding']['name']);
        $this->assertNull($byslug['wedding']['image']);
        $this->assertSame('Ulang Tahun', $byslug['birthday']['name']);
        $this->assertSame('http://posni.test/storage/website/categories/b.jpg', $byslug['birthday']['image']);

        $en = $this->getJson(self::BASE . '/categories?locale=en')->assertOk()->json('data');
        $this->assertSame(['Pernikahan', 'Birthday', 'Kosong'], array_column($en, 'name'));

        // productCount must match what /products actually lists for that category.
        $listed = $this->getJson(self::BASE . '/products')->assertOk()->json('data');
        foreach ($data as $category) {
            $cards = array_filter($listed, fn (array $card) => in_array($category['slug'], $card['categories'], true));
            $this->assertCount($category['productCount'], $cards, "productCount of {$category['slug']}");
        }
    }

    public function test_empty_categories_returns_empty_data_array(): void
    {
        $this->getJson(self::BASE . '/categories')->assertOk()->assertExactJson(['data' => []]);
    }

    // ---------------------------------------------------------------- testimonials

    public function test_testimonials_only_active_sorted_with_en_fallback(): void
    {
        config(['app.url' => 'http://posni.test/']);

        WebTestimonial::create(['name' => 'Second', 'quote_id' => 'Bagus banget', 'quote_en' => 'So good', 'occasion_id' => 'Hadiah ulang tahun', 'occasion_en' => 'Birthday gift', 'photo' => 'website/testimonials/s.jpg', 'sort_order' => 2]);
        WebTestimonial::create(['name' => 'First', 'quote_id' => 'Suka sekali', 'quote_en' => '   ', 'occasion_id' => null, 'sort_order' => 1]);
        WebTestimonial::create(['name' => 'Hidden', 'quote_id' => 'Disembunyikan', 'sort_order' => 0, 'is_active' => false]);

        $id = $this->getJson(self::BASE . '/testimonials')->assertOk()->json('data');

        $this->assertSame(['First', 'Second'], array_column($id, 'name'));
        $this->assertSame(['name' => 'First', 'quote' => 'Suka sekali', 'occasion' => '', 'photo' => null], $id[0]);
        $this->assertSame([
            'name' => 'Second',
            'quote' => 'Bagus banget',
            'occasion' => 'Hadiah ulang tahun',
            'photo' => 'http://posni.test/storage/website/testimonials/s.jpg',
        ], $id[1]);

        $en = $this->getJson(self::BASE . '/testimonials?locale=en')->assertOk()->json('data');
        // Blank EN falls back to ID; present EN is used.
        $this->assertSame('Suka sekali', $en[0]['quote']);
        $this->assertSame('', $en[0]['occasion']);
        $this->assertSame('So good', $en[1]['quote']);
        $this->assertSame('Birthday gift', $en[1]['occasion']);
    }

    public function test_empty_testimonials_returns_empty_data_array(): void
    {
        $this->getJson(self::BASE . '/testimonials')->assertOk()->assertExactJson(['data' => []]);
    }

    // ---------------------------------------------------------------- site

    public function test_site_groups_links_and_skips_inactive(): void
    {
        WebLink::create(['group' => 'social', 'platform' => 'tiktok', 'label' => 'TikTok', 'url' => 'https://tiktok.com/@kayuni', 'sort_order' => 1]);
        WebLink::create(['group' => 'social', 'platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/kayuni', 'sort_order' => 0]);
        WebLink::create(['group' => 'marketplace', 'platform' => 'tokopedia', 'label' => 'Tokopedia', 'url' => 'https://tokopedia.com/kayuni', 'sort_order' => 1]);
        WebLink::create(['group' => 'marketplace', 'platform' => 'shopee', 'label' => 'Shopee', 'url' => 'https://shopee.co.id/kayuni', 'sort_order' => 0]);
        WebLink::create(['group' => 'marketplace', 'platform' => 'toco', 'label' => 'Toco', 'url' => 'https://toco.test/kayuni', 'sort_order' => 2, 'is_active' => false]);
        WebLink::create(['group' => 'social', 'platform' => 'other', 'label' => 'Hidden social', 'url' => 'https://x.test', 'sort_order' => 3, 'is_active' => false]);
        // A group the API does not know about is never exposed.
        WebLink::create(['group' => 'internal', 'platform' => 'other', 'label' => 'Internal', 'url' => 'https://internal.test', 'sort_order' => 0]);

        WebFaq::create(['question_id' => 'Kedua?', 'question_en' => 'Second?', 'answer_id' => 'Jawab dua', 'answer_en' => 'Answer two', 'sort_order' => 2]);
        WebFaq::create(['question_id' => 'Pertama?', 'question_en' => '', 'answer_id' => 'Jawab satu', 'answer_en' => null, 'sort_order' => 1]);
        WebFaq::create(['question_id' => 'Tersembunyi?', 'answer_id' => 'x', 'sort_order' => 0, 'is_active' => false]);

        $data = $this->getJson(self::BASE . '/site')->assertOk()->json('data');

        $this->assertSame(['contact', 'copy', 'faqs', 'links'], $this->sortedKeys($data));
        $this->assertSame(['marketplace', 'social'], $this->sortedKeys($data['links']));

        $this->assertSame([
            ['platform' => 'shopee', 'label' => 'Shopee', 'url' => 'https://shopee.co.id/kayuni'],
            ['platform' => 'tokopedia', 'label' => 'Tokopedia', 'url' => 'https://tokopedia.com/kayuni'],
        ], $data['links']['marketplace']);
        $this->assertSame([
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/kayuni'],
            ['platform' => 'tiktok', 'label' => 'TikTok', 'url' => 'https://tiktok.com/@kayuni'],
        ], $data['links']['social']);

        $this->assertSame([
            ['question' => 'Pertama?', 'answer' => 'Jawab satu'],
            ['question' => 'Kedua?', 'answer' => 'Jawab dua'],
        ], $data['faqs']);

        $en = $this->getJson(self::BASE . '/site?locale=en')->assertOk()->json('data.faqs');
        $this->assertSame([
            ['question' => 'Pertama?', 'answer' => 'Jawab satu'],
            ['question' => 'Second?', 'answer' => 'Answer two'],
        ], $en);
    }

    public function test_site_with_nothing_configured_returns_empty_but_well_formed_payload(): void
    {
        $response = $this->getJson(self::BASE . '/site')->assertOk();

        $response->assertExactJson(['data' => [
            'contact' => [
                'whatsappNumber' => null,
                'whatsappOrderTemplate' => '',
                'whatsappGeneralMessage' => '',
                'address' => '',
            ],
            'links' => ['marketplace' => [], 'social' => []],
            'faqs' => [],
            'copy' => [],
        ]]);

        // "copy" is a flat map, so an empty one must be {} (not []) for the frontend's Record<string,string>.
        $this->assertStringContainsString('"copy":{}', $response->getContent());
        $this->assertStringContainsString('"links":{"marketplace":[],"social":[]}', $response->getContent());
    }

    public function test_site_copy_only_includes_known_non_empty_keys(): void
    {
        config(['website_content.sections' => [
            'hero' => [
                'label' => 'Hero',
                'keys' => [
                    'Hero.eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default_id' => 'Default', 'default_en' => 'Default EN'],
                    'Hero.sub' => ['label' => 'Sub', 'type' => 'textarea', 'default_id' => 'Sub default', 'default_en' => 'Sub default EN'],
                    'Hero.ctaPrimary' => ['label' => 'CTA', 'type' => 'text', 'default_id' => 'CTA', 'default_en' => 'CTA EN'],
                    'Hero.ctaSecondary' => ['label' => 'CTA 2', 'type' => 'text', 'default_id' => 'CTA 2', 'default_en' => 'CTA 2 EN'],
                ],
            ],
        ]]);

        WebSetting::create(['key' => 'copy.Hero.eyebrow', 'value_id' => 'Hadiah kayu', 'value_en' => 'Wooden gifts']);
        WebSetting::create(['key' => 'copy.Unknown.key', 'value_id' => 'Not in the config']);
        WebSetting::create(['key' => 'copy.Hero.sub', 'value_id' => '', 'value_en' => null]);
        WebSetting::create(['key' => 'copy.Hero.ctaPrimary', 'value_id' => '   ', 'value_en' => "\n"]);
        // A key without the copy. prefix is never copy, even if its name is in the config.
        WebSetting::create(['key' => 'Hero.ctaSecondary', 'value_id' => 'Wrong prefix']);

        $this->getJson(self::BASE . '/site')
            ->assertOk()
            ->assertExactJson(['data' => [
                'contact' => ['whatsappNumber' => null, 'whatsappOrderTemplate' => '', 'whatsappGeneralMessage' => '', 'address' => ''],
                'links' => ['marketplace' => [], 'social' => []],
                'faqs' => [],
                'copy' => ['Hero.eyebrow' => 'Hadiah kayu'],
            ]]);

        $en = $this->getJson(self::BASE . '/site?locale=en')->assertOk()->json('data.copy');
        $this->assertSame(['Hero.eyebrow' => 'Wooden gifts'], $en);
    }

    public function test_site_copy_falls_back_to_indonesian_when_english_is_blank(): void
    {
        config(['website_content.sections' => [
            'hero' => ['label' => 'Hero', 'keys' => [
                'Hero.eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default_id' => 'D', 'default_en' => 'D EN'],
            ]],
        ]]);
        WebSetting::create(['key' => 'copy.Hero.eyebrow', 'value_id' => 'Hanya ID', 'value_en' => '  ']);

        $this->getJson(self::BASE . '/site?locale=en')->assertOk()->assertJsonPath('data.copy', ['Hero.eyebrow' => 'Hanya ID']);
    }

    public function test_site_contact_null_number_when_unset(): void
    {
        $contact = $this->getJson(self::BASE . '/site')->assertOk()->json('data.contact');

        $this->assertSame([
            'whatsappNumber' => null,
            'whatsappOrderTemplate' => '',
            'whatsappGeneralMessage' => '',
            'address' => '',
        ], $contact);

        // A blank number is the same as unset.
        WebSetting::create(['key' => 'contact.whatsapp_number', 'value_id' => '   ']);
        $this->getJson(self::BASE . '/site')->assertOk()->assertJsonPath('data.contact.whatsappNumber', null);
    }

    public function test_site_contact_values_are_localized_and_number_is_indonesian_value_only(): void
    {
        // The number has no EN variant: even an (unexpected) EN value must never replace it.
        WebSetting::create(['key' => 'contact.whatsapp_number', 'value_id' => '6281234567890', 'value_en' => '620000000000']);
        WebSetting::create(['key' => 'contact.whatsapp_order_template', 'value_id' => 'Halo, mau pesan {product}', 'value_en' => 'Hi, I want {product}']);
        WebSetting::create(['key' => 'contact.whatsapp_general_message', 'value_id' => 'Halo Kayuni', 'value_en' => null]);
        WebSetting::create(['key' => 'contact.address', 'value_id' => 'Jl. Kayu 1, Jakarta', 'value_en' => 'Kayu St 1, Jakarta']);

        $id = $this->getJson(self::BASE . '/site')->assertOk()->json('data.contact');
        $this->assertSame([
            'whatsappNumber' => '6281234567890',
            'whatsappOrderTemplate' => 'Halo, mau pesan {product}',
            'whatsappGeneralMessage' => 'Halo Kayuni',
            'address' => 'Jl. Kayu 1, Jakarta',
        ], $id);

        $en = $this->getJson(self::BASE . '/site?locale=en')->assertOk()->json('data.contact');
        $this->assertSame([
            'whatsappNumber' => '6281234567890',
            'whatsappOrderTemplate' => 'Hi, I want {product}',
            'whatsappGeneralMessage' => 'Halo Kayuni',
            'address' => 'Kayu St 1, Jakarta',
        ], $en);
    }

    // ---------------------------------------------------------------- exposure

    public function test_responses_never_expose_cost_fields(): void
    {
        config(['website_content.sections' => [
            'hero' => ['label' => 'Hero', 'keys' => [
                'Hero.eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default_id' => 'D', 'default_en' => 'D EN'],
            ]],
        ]]);

        $item = $this->item('Gelas Rahasia', 5);
        DB::table('items')->where('id', $item)->update(['purchase_price' => 11111, 'selling_price' => 22222]);
        $hamper = $this->hamper('Solar F Grafir', 94000, [[$item, 1]], 'hampers-images/solar.jpg');
        DB::table('hampers')->where('id', $hamper)->update(['capital_price' => 33333, 'revenue_percentage' => 40]);

        $category = WebCategory::create(['slug' => 'birthday', 'name_id' => 'Birthday', 'image' => 'website/categories/b.jpg']);
        $product = $this->webProduct(['slug' => 'solar-f'], ['engraved' => ['hamper_id' => $hamper]]);
        $product->categories()->attach($category->id);

        WebTestimonial::create(['name' => 'Nadia', 'quote_id' => 'Bagus', 'occasion_id' => 'Birthday gift', 'photo' => 'website/testimonials/n.jpg']);
        WebLink::create(['group' => 'marketplace', 'platform' => 'shopee', 'label' => 'Shopee', 'url' => 'https://shopee.co.id/kayuni']);
        WebFaq::create(['question_id' => 'Q?', 'answer_id' => 'A']);
        WebSetting::create(['key' => 'contact.whatsapp_number', 'value_id' => '6281234567890']);
        WebSetting::create(['key' => 'copy.Hero.eyebrow', 'value_id' => 'Hadiah']);
        // Settings outside the allow-list must never surface, whatever they hold.
        WebSetting::create(['key' => 'internal.secret', 'value_id' => 'TOP-SECRET-VALUE']);
        WebSetting::create(['key' => 'copy.Secret.key', 'value_id' => 'TOP-SECRET-COPY']);

        $responses = [
            'categories' => $this->getJson(self::BASE . '/categories')->assertOk(),
            'testimonials' => $this->getJson(self::BASE . '/testimonials')->assertOk(),
            'site' => $this->getJson(self::BASE . '/site')->assertOk(),
        ];

        $forbiddenStrings = ['capital_price', 'purchase_price', 'revenue_percentage', 'selling_price', '"stock"', 'Solar F Grafir', 'Gelas Rahasia', '33333', '22222', '11111', 'TOP-SECRET', 'internal.secret'];
        foreach ($responses as $name => $response) {
            $body = $response->getContent();
            foreach ($forbiddenStrings as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $body, "{$name} leaked {$forbidden}");
            }
        }

        // Allow-lists: exactly these keys, nothing more.
        $this->assertSame(['image', 'name', 'productCount', 'slug'], $this->sortedKeys($responses['categories']->json('data.0')));
        $this->assertSame(['name', 'occasion', 'photo', 'quote'], $this->sortedKeys($responses['testimonials']->json('data.0')));

        $site = $responses['site']->json('data');
        $this->assertSame(['contact', 'copy', 'faqs', 'links'], $this->sortedKeys($site));
        $this->assertSame(['address', 'whatsappGeneralMessage', 'whatsappNumber', 'whatsappOrderTemplate'], $this->sortedKeys($site['contact']));
        $this->assertSame(['marketplace', 'social'], $this->sortedKeys($site['links']));
        $this->assertSame(['label', 'platform', 'url'], $this->sortedKeys($site['links']['marketplace'][0]));
        $this->assertSame(['answer', 'question'], $this->sortedKeys($site['faqs'][0]));
        $this->assertSame(['Hero.eyebrow' => 'Hadiah'], $site['copy']);
    }
}
