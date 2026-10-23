<?php

namespace Tests\Feature\Website;

use App\Models\WebCategory;
use App\Models\WebProduct;
use App\Models\WebProductImage;
use App\Models\WebProductOption;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductAdminTest extends WebsiteTestCase
{
    private const PUBLISH_ERROR = 'Pilih minimal satu hamper (Grafir atau Tanpa Grafir) sebelum publish.';

    /** A hamper that is in stock (one item, plenty of units). */
    private function stockedHamper(string $name, int $price = 100000, int $stock = 5, ?int $serieId = null): int
    {
        return $this->hamper($name, $price, [[$this->item("Item {$name}", $stock), 1]], null, $serieId);
    }

    private function category(string $slug = 'birthday', int $sort = 0, bool $active = true): WebCategory
    {
        return WebCategory::create(['slug' => $slug, 'name_id' => ucfirst($slug), 'sort_order' => $sort, 'is_active' => $active]);
    }

    /** A valid create/update body; $over replaces top-level keys. */
    private function body(array $over = []): array
    {
        return $over + [
            'name_id' => 'Solar F',
            'slug' => '',
            'sort_order' => 0,
        ];
    }

    public function test_staff_get_403(): void
    {
        $hamper = $this->stockedHamper('H');
        $product = $this->webProduct([], ['plain' => ['hamper_id' => $hamper]]);

        // guest first: no actingAs yet
        $this->get(route('website.products.index'))->assertForbidden();
        $this->get(route('website.hampers.info', $hamper))->assertForbidden();

        $staff = $this->staff();
        $this->actingAs($staff)->get(route('website.products.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('website.products.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('website.products.edit', $product))->assertForbidden();
        $this->actingAs($staff)->post(route('website.products.store'), $this->body())->assertForbidden();
        $this->actingAs($staff)->put(route('website.products.update', $product), $this->body())->assertForbidden();
        $this->actingAs($staff)->delete(route('website.products.destroy', $product))->assertForbidden();

        $this->assertSame(1, WebProduct::count());
    }

    public function test_admin_creates_product_with_two_options_categories_and_images(): void
    {
        Storage::fake('public');
        $engraved = $this->stockedHamper('Solar F Grafir', 94000);
        $plain = $this->stockedHamper('Solar F Tanpa Grafir', 89000);
        $birthday = $this->category('birthday');
        $wedding = $this->category('wedding', 1);
        $this->category('graduation', 2); // not selected

        $this->actingAs($this->admin())
            ->post(route('website.products.store'), $this->body([
                'name_en' => 'Solar F EN',
                'slug' => 'solar-f',
                'categories' => [$birthday->id, $wedding->id],
                'description_id' => 'Deskripsi',
                'description_en' => 'Description',
                'contents_id' => "Kotak kayu\nKartu ucapan",
                'contents_en' => "Wooden box\nGreeting card",
                'options' => [
                    'engraved' => ['hamper_id' => $engraved, 'price_override' => '90000'],
                    'plain' => ['hamper_id' => $plain, 'price_override' => ''],
                ],
                'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
                'is_published' => '1',
                'is_bestseller' => '1',
                'is_featured' => '0',
                'sort_order' => 5,
            ]))
            ->assertRedirect(route('website.products.index'))
            ->assertSessionHasNoErrors();

        $product = WebProduct::where('slug', 'solar-f')->firstOrFail();
        $this->assertSame('Solar F', $product->name_id);
        $this->assertSame('Solar F EN', $product->name_en);
        $this->assertSame('Deskripsi', $product->description_id);
        $this->assertSame('Description', $product->description_en);
        $this->assertSame("Kotak kayu\nKartu ucapan", $product->contents_id);
        $this->assertSame("Wooden box\nGreeting card", $product->contents_en);
        $this->assertTrue($product->is_published);
        $this->assertTrue($product->is_bestseller);
        $this->assertFalse($product->is_featured);
        $this->assertSame(5, $product->sort_order);

        $options = $product->options->keyBy('kind');
        $this->assertSame(['engraved', 'plain'], $product->options->pluck('kind')->all());
        $this->assertSame($engraved, (int) $options['engraved']->hamper_id);
        $this->assertSame(90000, (int) $options['engraved']->price_override);
        $this->assertSame($plain, (int) $options['plain']->hamper_id);
        $this->assertNull($options['plain']->price_override);

        $this->assertEqualsCanonicalizing([$birthday->id, $wedding->id], $product->categories->pluck('id')->all());

        $this->assertCount(2, $product->images);
        foreach ($product->images as $index => $image) {
            $this->assertStringStartsWith('website/products/', $image->path);
            Storage::disk('public')->assertExists($image->path);
            $this->assertSame($index, $image->sort_order);
        }
    }

    public function test_empty_price_override_is_stored_as_null(): void
    {
        $engraved = $this->stockedHamper('Solar F Grafir', 94000);

        $this->actingAs($this->admin())
            ->post(route('website.products.store'), $this->body([
                'options' => ['engraved' => ['hamper_id' => $engraved, 'price_override' => '']],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, WebProductOption::count());
        $this->assertNull(WebProductOption::first()->price_override);
    }

    public function test_price_override_must_be_a_positive_whole_number(): void
    {
        $engraved = $this->stockedHamper('Solar F Grafir');
        $admin = $this->admin();

        foreach (['0', '-5', '12.5', 'abc', '999999999999'] as $bad) {
            $this->actingAs($admin)
                ->post(route('website.products.store'), $this->body([
                    'options' => ['engraved' => ['hamper_id' => $engraved, 'price_override' => $bad]],
                ]))
                ->assertSessionHasErrors('options.engraved.price_override');
        }

        $this->assertSame(0, WebProduct::count());
    }

    public function test_option_hamper_must_exist_and_not_be_deleted(): void
    {
        $gone = $this->hamper('Gone', 1000, [], null, null, true);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body([
                'options' => ['engraved' => ['hamper_id' => 99999]],
            ]))
            ->assertSessionHasErrors('options.engraved.hamper_id');

        $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body([
                'options' => ['plain' => ['hamper_id' => $gone]],
            ]))
            ->assertSessionHasErrors('options.plain.hamper_id');

        $this->assertSame(0, WebProduct::count());
    }

    public function test_clearing_option_hamper_deletes_that_option(): void
    {
        $engraved = $this->stockedHamper('Solar F Grafir');
        $plain = $this->stockedHamper('Solar F Tanpa Grafir');
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F'], [
            'engraved' => ['hamper_id' => $engraved, 'price_override' => 90000],
            'plain' => ['hamper_id' => $plain],
        ]);

        $this->actingAs($this->admin())
            ->put(route('website.products.update', $product), $this->body([
                'slug' => 'solar-f',
                'options' => [
                    'engraved' => ['hamper_id' => '', 'price_override' => '90000'],
                    'plain' => ['hamper_id' => $plain, 'price_override' => ''],
                ],
            ]))
            ->assertRedirect(route('website.products.index'))
            ->assertSessionHasNoErrors();

        $remaining = WebProductOption::where('web_product_id', $product->id)->get();
        $this->assertSame(['plain'], $remaining->pluck('kind')->all());
        $this->assertSame($plain, (int) $remaining->first()->hamper_id);
    }

    public function test_update_changes_option_hamper_and_price_without_duplicating_rows(): void
    {
        $a = $this->stockedHamper('A');
        $b = $this->stockedHamper('B');
        $product = $this->webProduct(['slug' => 'solar-f'], ['engraved' => ['hamper_id' => $a, 'price_override' => 50000]]);

        $this->actingAs($this->admin())
            ->put(route('website.products.update', $product), $this->body([
                'slug' => 'solar-f',
                'options' => ['engraved' => ['hamper_id' => $b, 'price_override' => '']],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, WebProductOption::count());
        $option = WebProductOption::first();
        $this->assertSame($b, (int) $option->hamper_id);
        $this->assertNull($option->price_override);
    }

    public function test_cannot_publish_without_any_option(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body(['is_published' => '1']))
            ->assertSessionHasErrors(['options' => self::PUBLISH_ERROR]);

        $this->assertSame(0, WebProduct::count());

        // A draft without options is fine.
        $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body(['is_published' => '0']))
            ->assertSessionHasNoErrors();

        $product = WebProduct::firstOrFail();
        $this->assertFalse($product->is_published);

        // Publishing it later still needs an option, and clearing the last option while published fails too.
        $this->actingAs($admin)
            ->put(route('website.products.update', $product), $this->body(['slug' => $product->slug, 'is_published' => '1']))
            ->assertSessionHasErrors(['options' => self::PUBLISH_ERROR]);
        $this->assertFalse($product->fresh()->is_published);
    }

    public function test_publish_with_one_option_succeeds(): void
    {
        $plain = $this->stockedHamper('Solar F Tanpa Grafir');

        $this->actingAs($this->admin())
            ->post(route('website.products.store'), $this->body([
                'is_published' => '1',
                'options' => ['plain' => ['hamper_id' => $plain]],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertTrue(WebProduct::firstOrFail()->is_published);
    }

    public function test_slug_is_normalized_and_duplicates_fail_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body(['name_id' => 'Solar F', 'slug' => ' Solar F!! ']))
            ->assertRedirect(route('website.products.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('solar-f', WebProduct::firstOrFail()->slug);

        // Same name, empty slug: the generated slug collides.
        $response = $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body(['name_id' => 'Solar F', 'slug' => '']));

        $response->assertSessionHasErrors('slug');
        $this->assertNotSame(500, $response->getStatusCode());
        $this->assertSame(1, WebProduct::count());
    }

    public function test_slug_falls_back_to_the_name_and_survives_odd_characters(): void
    {
        $this->actingAs($this->admin())
            ->post(route('website.products.store'), $this->body(['name_id' => 'Hamper Kayu: Édisi #1 (Ulang Tahun)', 'slug' => '!!!']))
            ->assertSessionHasNoErrors();

        $this->assertSame('hamper-kayu-edisi-1-ulang-tahun', WebProduct::firstOrFail()->slug);
    }

    public function test_slug_stays_unique_on_update_but_may_keep_its_own_value(): void
    {
        $admin = $this->admin();
        $one = $this->webProduct(['slug' => 'satu', 'name_id' => 'Satu']);
        $this->webProduct(['slug' => 'dua', 'name_id' => 'Dua']);

        $this->actingAs($admin)
            ->put(route('website.products.update', $one), $this->body(['name_id' => 'Satu', 'slug' => 'satu']))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->put(route('website.products.update', $one), $this->body(['name_id' => 'Satu', 'slug' => 'Dua']))
            ->assertSessionHasErrors('slug');

        $this->assertSame('satu', $one->fresh()->slug);
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('website.products.store'), $this->body(['name_id' => '', 'slug' => '']))
            ->assertSessionHasErrors('name_id');

        $this->assertSame(0, WebProduct::count());
    }

    public function test_sort_order_is_bounded(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body(['sort_order' => 65536]))
            ->assertSessionHasErrors('sort_order');
        $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body(['sort_order' => -1, 'slug' => 'other']))
            ->assertSessionHasErrors('sort_order');

        $this->assertSame(0, WebProduct::count());
    }

    public function test_remove_images_deletes_files_and_rows(): void
    {
        Storage::fake('public');
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F']);

        $paths = [];
        foreach (['one', 'two', 'three'] as $order => $name) {
            $paths[$name] = UploadedFile::fake()->image("{$name}.jpg")->store('website/products', 'public');
            $paths[$name . '_id'] = WebProductImage::create([
                'web_product_id' => $product->id,
                'path' => $paths[$name],
                'sort_order' => $order,
            ])->id;
        }

        $other = $this->webProduct(['slug' => 'other']);
        $otherPath = UploadedFile::fake()->image('other.jpg')->store('website/products', 'public');
        $otherImage = WebProductImage::create(['web_product_id' => $other->id, 'path' => $otherPath, 'sort_order' => 0]);

        $this->actingAs($this->admin())
            ->put(route('website.products.update', $product), $this->body([
                'slug' => 'solar-f',
                // The foreign image id must be ignored, not deleted.
                'remove_images' => [$paths['one_id'], $paths['three_id'], $otherImage->id],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame([$paths['two_id']], WebProductImage::where('web_product_id', $product->id)->pluck('id')->all());
        Storage::disk('public')->assertMissing($paths['one']);
        Storage::disk('public')->assertMissing($paths['three']);
        Storage::disk('public')->assertExists($paths['two']);
        $this->assertNotNull(WebProductImage::find($otherImage->id));
        Storage::disk('public')->assertExists($otherPath);
    }

    public function test_update_reorders_images_and_appends_new_uploads(): void
    {
        Storage::fake('public');
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F']);
        $first = WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/a.jpg', 'sort_order' => 0]);
        $second = WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/b.jpg', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->put(route('website.products.update', $product), $this->body([
                'slug' => 'solar-f',
                'image_order' => [$first->id => 5, $second->id => 2],
                'images' => [UploadedFile::fake()->image('new.jpg')],
            ]))
            ->assertSessionHasNoErrors();

        $images = $product->images()->get();
        $this->assertCount(3, $images);
        $this->assertSame([$second->id, $first->id], $images->take(2)->pluck('id')->all());
        $this->assertSame(2, $images[0]->sort_order);
        $this->assertSame(5, $images[1]->sort_order);
        // The new upload comes after the existing ones.
        $this->assertGreaterThan(5, $images[2]->sort_order);
        $this->assertStringStartsWith('website/products/', $images[2]->path);
    }

    public function test_image_rules_reject_non_images(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('website.products.store'), $this->body([
                'images' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
            ]))
            ->assertSessionHasErrors('images.0');

        $this->actingAs($this->admin())
            ->post(route('website.products.store'), $this->body([
                'images' => [UploadedFile::fake()->image('huge.jpg')->size(3000)],
            ]))
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, WebProduct::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_validation_failure_stores_no_upload(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F']);

        $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body([
                'slug' => 'solar-f',
                'images' => [UploadedFile::fake()->image('a.jpg')],
            ]))
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, WebProduct::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_save_rolls_back_and_removes_uploaded_files(): void
    {
        Storage::fake('public');
        $plain = $this->stockedHamper('Solar F Tanpa Grafir');

        // Blow up while the second image row is written: the first file is already on disk by then.
        $rows = 0;
        WebProductImage::created(function () use (&$rows) {
            if (++$rows === 2) {
                throw new \RuntimeException('boom');
            }
        });

        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->admin())->post(route('website.products.store'), $this->body([
                'options' => ['plain' => ['hamper_id' => $plain]],
                'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            ]));
            $this->fail('The save should have thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame(0, WebProduct::count());
        $this->assertSame(0, WebProductOption::count());
        $this->assertSame(0, WebProductImage::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_hamper_info_returns_price_stock_and_contents_for_admin_only(): void
    {
        $serie = $this->serie('Solar');
        $box = $this->item('Kotak Kayu', 10);
        $card = $this->item('Kartu', 6);
        $hamper = $this->hamper('Solar F Grafir', 94000, [[$box, 2], [$card, 1]], null, $serie);

        // A soft-deleted detail and a detail of a soft-deleted item must not appear.
        $old = $this->item('Item Lama', 10);
        $oldDetail = DB::table('hamper_details')->insertGetId(['hamper_id' => $hamper, 'item_id' => $old, 'qty' => 1]);
        DB::table('hamper_details')->where('id', $oldDetail)->update(['deleted_at' => now()]);

        $this->actingAs($this->staff())
            ->getJson(route('website.hampers.info', $hamper))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->getJson(route('website.hampers.info', $hamper))
            ->assertOk()
            ->assertExactJson([
                'id' => $hamper,
                'name' => 'Solar F Grafir',
                'serie' => 'Solar',
                'sellingPrice' => 94000,
                'stock' => 5, // min(10 / 2, 6 / 1)
                'contents' => ['Kotak Kayu', 'Kartu'],
            ]);
    }

    public function test_hamper_info_for_a_deleted_or_missing_hamper_is_404(): void
    {
        $gone = $this->hamper('Gone', 1000, [], null, null, true);
        $admin = $this->admin();

        $this->actingAs($admin)->getJson(route('website.hampers.info', $gone))->assertNotFound();
        $this->actingAs($admin)->getJson(route('website.hampers.info', 99999))->assertNotFound();
    }

    public function test_hamper_info_survives_a_hamper_with_a_deleted_item(): void
    {
        $dead = $this->item('Item Mati', 10, 'pcs', true);
        $hamper = $this->hamper('Broken', 5000, [[$dead, 1]]);

        $this->actingAs($this->admin())
            ->getJson(route('website.hampers.info', $hamper))
            ->assertOk()
            ->assertJsonPath('stock', 0)
            ->assertJsonPath('contents', []);
    }

    public function test_picker_excludes_item_serie_and_deleted_hampers(): void
    {
        $solar = $this->serie('Solar');
        $itemSerie = $this->serie('Item');
        $this->hamper('Solar F Grafir', 94000, [], null, $solar);
        $this->hamper('Auto Item Hamper', 1000, [], null, $itemSerie);
        $this->hamper('Hamper Terhapus', 1000, [], null, $solar, true);
        $this->hamper('Hamper Tanpa Serie', 1000);

        $response = $this->actingAs($this->admin())->get(route('website.products.create'));

        $response->assertOk()
            ->assertSee('Solar F Grafir')
            ->assertSee('Hamper Tanpa Serie')
            ->assertSee('<optgroup label="Solar">', false)
            ->assertDontSee('Auto Item Hamper')
            ->assertDontSee('Hamper Terhapus')
            ->assertDontSee('<optgroup label="Item">', false);
    }

    public function test_picker_is_ordered_by_serie_then_hamper_name(): void
    {
        $zeta = $this->serie('Zeta');
        $alpha = $this->serie('Alpha');
        $this->hamper('Zeta Satu', 1, [], null, $zeta);
        $this->hamper('Alpha B', 1, [], null, $alpha);
        $this->hamper('Alpha A', 1, [], null, $alpha);

        $html = $this->actingAs($this->admin())->get(route('website.products.create'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Alpha B'), strpos($html, 'Alpha A'));
        $this->assertLessThan(strpos($html, 'Zeta Satu'), strpos($html, 'Alpha B'));
    }

    public function test_create_and_edit_forms_render(): void
    {
        $engraved = $this->stockedHamper('Solar F Grafir');
        $category = $this->category('birthday');
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F', 'contents_id' => 'Isi satu'], [
            'engraved' => ['hamper_id' => $engraved, 'price_override' => 90000],
        ]);
        $product->categories()->attach($category->id);
        WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/x.jpg', 'sort_order' => 3]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('website.products.create'))
            ->assertOk()
            ->assertSee('Isi dari posni')
            ->assertSee('name="options[engraved][hamper_id]"', false)
            ->assertSee('name="options[plain][price_override]"', false);

        $this->actingAs($admin)->get(route('website.products.edit', $product))
            ->assertOk()
            ->assertSee('value="solar-f"', false)
            ->assertSee('value="90000"', false)
            ->assertSee('website/products/x.jpg')
            ->assertSee('name="remove_images[]"', false)
            ->assertSee('Isi satu');
    }

    public function test_hamper_filter_inputs_cannot_submit_the_form_with_enter(): void
    {
        $html = $this->actingAs($this->admin())->get(route('website.products.create'))->assertOk()->getContent();

        // One search box per option row (Grafir, Tanpa Grafir) sits inside the product form ...
        $this->assertSame(2, substr_count($html, 'type="search" id="filter-'));

        // ... so the script that binds every .hamper-filter must swallow Enter (the phone keyboard's
        // Search/Go key), or typing in the filter would save the product. The browser check covers the behaviour.
        $this->assertStringContainsString("\$filter.on('keydown'", $html);
        $this->assertStringContainsString("event.key === 'Enter'", $html);
        $this->assertStringContainsString('event.preventDefault()', $html);
    }

    public function test_edit_keeps_showing_a_hamper_that_was_deleted_in_posni(): void
    {
        $gone = $this->hamper('Hamper Lama', 1000, [], null, null, true);
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F'], ['plain' => ['hamper_id' => $gone]]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('website.products.edit', $product))
            ->assertOk()
            ->assertSee('Hamper Lama')
            ->assertSee('dihapus di posni');

        // Saving the unrelated fields must not silently drop or reject the kept hamper.
        $this->actingAs($admin)
            ->put(route('website.products.update', $product), $this->body([
                'slug' => 'solar-f',
                'options' => ['plain' => ['hamper_id' => $gone]],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($gone, (int) $product->options()->first()->hamper_id);
    }

    public function test_index_lists_products_with_prices_stock_and_badges(): void
    {
        $engraved = $this->stockedHamper('Solar F Grafir', 94000, 3);
        $plain = $this->stockedHamper('Solar F Tanpa Grafir', 89000, 0);
        $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F', 'is_bestseller' => true, 'sort_order' => 7], [
            'engraved' => ['hamper_id' => $engraved],
            'plain' => ['hamper_id' => $plain, 'price_override' => 85000],
        ]);

        $this->actingAs($this->admin())->get(route('website.products.index'))
            ->assertOk()
            ->assertSee('Solar F')
            ->assertSee('Solar F Grafir')
            ->assertSee('Solar F Tanpa Grafir')
            ->assertSee('Rp 85.000') // price from = minimum option price (override wins)
            ->assertSee('Stok 3')
            ->assertSee('Habis')
            ->assertSee('Terbit')
            ->assertSee('Bestseller')
            ->assertDontSee('Tidak tampil di website');
    }

    public function test_index_warns_about_published_products_with_no_valid_option(): void
    {
        $gone = $this->hamper('Hamper Hilang', 1000, [], null, null, true);
        $this->webProduct(['slug' => 'no-options', 'name_id' => 'Tanpa Opsi', 'is_published' => true]);
        $this->webProduct(['slug' => 'dead-option', 'name_id' => 'Opsi Mati', 'is_published' => true], ['plain' => ['hamper_id' => $gone]]);
        $this->webProduct(['slug' => 'draft', 'name_id' => 'Draf Saja', 'is_published' => false]);

        $html = $this->actingAs($this->admin())->get(route('website.products.index'))->assertOk()->getContent();

        // One badge per product row, in both the phone card and the desktop table: 2 products x 2 layouts.
        $this->assertSame(4, substr_count($html, 'Tidak tampil di website'));
    }

    public function test_index_search_filters_by_indonesian_name(): void
    {
        $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F']);
        $this->webProduct(['slug' => 'black-d', 'name_id' => 'Black D']);

        $this->actingAs($this->admin())->get(route('website.products.index', ['search' => 'solar']))
            ->assertOk()
            ->assertSee('Solar F')
            ->assertDontSee('Black D');
    }

    public function test_index_sort_uses_a_whitelist(): void
    {
        $this->webProduct(['slug' => 'b', 'name_id' => 'Bravo', 'sort_order' => 1]);
        $this->webProduct(['slug' => 'a', 'name_id' => 'Alpha', 'sort_order' => 2]);
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get(route('website.products.index', ['sort' => 'name_id', 'direction' => 'asc']))->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Bravo'), strpos($html, 'Alpha'));

        $html = $this->actingAs($admin)->get(route('website.products.index', ['sort' => 'name_id', 'direction' => 'desc']))->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Alpha'), strpos($html, 'Bravo'));

        // Anything outside the whitelist falls back to the default order instead of reaching SQL.
        foreach (['id; drop table web_products', 'password', '(select 1)', 'created_at desc, id'] as $evil) {
            $this->actingAs($admin)->get(route('website.products.index', ['sort' => $evil, 'direction' => 'sideways']))->assertOk();
        }
        $html = $this->actingAs($admin)->get(route('website.products.index', ['sort' => 'password']))->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Alpha'), strpos($html, 'Bravo')); // default: sort_order asc
        $this->assertSame(2, WebProduct::count());
    }

    public function test_index_escapes_product_names(): void
    {
        $this->webProduct(['slug' => 'xss', 'name_id' => '<script>alert(1)</script>']);

        $html = $this->actingAs($this->admin())->get(route('website.products.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_destroy_soft_deletes_the_product_and_frees_its_slug(): void
    {
        $admin = $this->admin();
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F']);

        $this->actingAs($admin)
            ->delete(route('website.products.destroy', $product))
            ->assertRedirect(route('website.products.index'));

        $this->assertSoftDeleted('web_products', ['id' => $product->id]);

        // The same name can be used again; the unique index would otherwise still hold the old slug.
        $this->actingAs($admin)
            ->post(route('website.products.store'), $this->body(['name_id' => 'Solar F', 'slug' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, WebProduct::count());
        $this->assertSame('solar-f', WebProduct::firstOrFail()->slug);
    }

    public function test_flash_messages_are_in_indonesian(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->followingRedirects()
            ->post(route('website.products.store'), $this->body())
            ->assertSee('Produk berhasil ditambahkan');

        $product = WebProduct::firstOrFail();

        $this->actingAs($admin)->followingRedirects()
            ->put(route('website.products.update', $product), $this->body(['slug' => $product->slug]))
            ->assertSee('Produk berhasil diperbarui');

        $this->actingAs($admin)->followingRedirects()
            ->delete(route('website.products.destroy', $product))
            ->assertSee('Produk berhasil dihapus');
    }
}
