<?php

namespace Tests\Feature\Website;

use App\Models\WebProduct;
use App\Services\Website\ProductCatalog;

class ProductImportAdminTest extends WebsiteTestCase
{
    /** A hamper the website may show: priced, in a serie. */
    private function liveHamper(string $name, ?int $serieId = null, int $price = 100000): int
    {
        return $this->hamper($name, $price, [], null, $serieId);
    }

    private function names($response): array
    {
        return collect($response->viewData('products')->items())->pluck('name_id')->all();
    }

    // --- access -----------------------------------------------------------------------------

    public function test_staff_and_guests_get_403_on_import_and_bulk(): void
    {
        $serie = $this->serie('Solar');
        $hamper = $this->liveHamper('Solar F Grafir', $serie);
        $product = $this->webProduct(['is_published' => false], ['engraved' => ['hamper_id' => $hamper]]);

        $this->get(route('website.products.import'))->assertForbidden();
        $this->post(route('website.products.import.store'), ['serie_ids' => [$serie]])->assertForbidden();
        $this->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => [$product->id]])->assertForbidden();

        $staff = $this->staff();
        $this->actingAs($staff)->get(route('website.products.import'))->assertForbidden();
        $this->actingAs($staff)->post(route('website.products.import.store'), ['serie_ids' => [$serie]])->assertForbidden();
        $this->actingAs($staff)->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => [$product->id]])->assertForbidden();

        $this->assertSame(1, WebProduct::count());
        $this->assertFalse($product->fresh()->is_published);
    }

    // --- import page ------------------------------------------------------------------------

    public function test_the_import_route_is_not_swallowed_by_the_resource_routes(): void
    {
        $this->assertSame('/dashboard/website/products/import', route('website.products.import', [], false));
        $this->actingAs($this->admin())->get(route('website.products.import'))->assertOk();
    }

    public function test_the_preview_page_lists_the_series_with_their_counts(): void
    {
        $solar = $this->serie('Solar');
        $kappa = $this->serie('Kappa');
        $item = $this->serie('Item');
        $this->liveHamper('Solar F Grafir', $solar);
        $this->liveHamper('Solar F Tanpa Grafir', $solar);
        $this->liveHamper('Solar G', $solar);
        $linked = $this->liveHamper('Kappa A', $kappa);
        $this->liveHamper('Batu Kayu', $item);
        $this->webProduct(['slug' => 'kappa-manual'], ['engraved' => ['hamper_id' => $linked]]);

        $response = $this->actingAs($this->admin())->get(route('website.products.import'));

        $response->assertOk()
            ->assertSee('Import dari hampers')
            ->assertSee('Draft')
            ->assertSee('Solar')
            ->assertSee('Kappa')
            ->assertDontSee('Batu Kayu');

        $rows = collect($response->viewData('rows'))->keyBy('serie');
        $this->assertSame(['hampers' => 3, 'linked' => 0, 'to_create' => 2], collect($rows['Solar'])->only(['hampers', 'linked', 'to_create'])->all());
        $this->assertSame(['hampers' => 1, 'linked' => 1, 'to_create' => 0], collect($rows['Kappa'])->only(['hampers', 'linked', 'to_create'])->all());
        $this->assertFalse($rows->has('Item'));

        $html = $response->getContent();
        // The serie with something to create is selectable, the fully-linked one is disabled.
        $this->assertMatchesRegularExpression('/<input[^>]*id="serie-' . $solar . '"(?![^>]*disabled)[^>]*>/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="serie-' . $kappa . '"[^>]*disabled/', $html);
        $this->assertStringContainsString('name="serie_ids[]"', $html);
    }

    // --- import submit ----------------------------------------------------------------------

    public function test_posting_the_import_creates_drafts_flashes_the_summary_and_redirects(): void
    {
        $solar = $this->serie('Solar');
        $kappa = $this->serie('Kappa');
        $this->liveHamper('Solar F Grafir', $solar);
        $this->liveHamper('Solar F Tanpa Grafir', $solar);
        $this->liveHamper('Kappa A', $kappa); // not selected
        $already = $this->liveHamper('Solar G', $solar);
        $this->webProduct(['slug' => 'solar-g-manual'], ['engraved' => ['hamper_id' => $already]]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.products.import.store'), ['serie_ids' => [$solar]])
            ->assertRedirect(route('website.products.index', ['status' => 'draft']));

        $this->assertSame(['Solar F'], WebProduct::where('slug', 'solar-f')->pluck('name_id')->all());
        $this->assertSame(0, WebProduct::where('name_id', 'Kappa A')->count());
        $this->assertFalse(WebProduct::where('slug', 'solar-f')->firstOrFail()->is_published);

        // A second run flashes the skip. Both messages are in Indonesian on the redirected page.
        $this->actingAs($admin)->followingRedirects()
            ->post(route('website.products.import.store'), ['serie_ids' => [$solar]])
            ->assertSee('Tidak ada produk baru yang dibuat')
            ->assertSee('2 dilewati (sudah ada di website)');
    }

    public function test_the_flash_after_a_first_import_names_the_drafts(): void
    {
        $solar = $this->serie('Solar');
        $this->liveHamper('Solar F Grafir', $solar);
        $this->liveHamper('Kappa A', $solar);

        $this->actingAs($this->admin())->followingRedirects()
            ->post(route('website.products.import.store'), ['serie_ids' => [(string) $solar]])
            ->assertOk()
            ->assertSee('2 produk dibuat sebagai draft');
    }

    public function test_the_import_needs_at_least_one_existing_serie(): void
    {
        $admin = $this->admin();
        $this->serie('Solar');

        $this->actingAs($admin)->post(route('website.products.import.store'), [])
            ->assertSessionHasErrors('serie_ids');
        $this->actingAs($admin)->post(route('website.products.import.store'), ['serie_ids' => []])
            ->assertSessionHasErrors('serie_ids');
        $this->actingAs($admin)->post(route('website.products.import.store'), ['serie_ids' => [999]])
            ->assertSessionHasErrors('serie_ids.0');
        $this->actingAs($admin)->post(route('website.products.import.store'), ['serie_ids' => ['abc']])
            ->assertSessionHasErrors('serie_ids.0');

        $this->assertSame(0, WebProduct::count());
    }

    // --- bulk publish / unpublish -----------------------------------------------------------

    public function test_bulk_publish_publishes_valid_products_and_skips_invalid_ones(): void
    {
        $serie = $this->serie('Solar');
        $good = $this->webProduct(['is_published' => false, 'name_id' => 'Good'], ['engraved' => ['hamper_id' => $this->liveHamper('Good H', $serie)]]);
        $overridden = $this->webProduct(['is_published' => false, 'name_id' => 'Overridden'], ['plain' => ['hamper_id' => $this->hamper('Free H', 0, [], null, $serie), 'price_override' => 50000]]);
        $noOption = $this->webProduct(['is_published' => false, 'name_id' => 'NoOption']);
        $freeHamper = $this->webProduct(['is_published' => false, 'name_id' => 'Free'], ['engraved' => ['hamper_id' => $this->hamper('Zero H', 0, [], null, $serie)]]);
        $deletedHamper = $this->webProduct(['is_published' => false, 'name_id' => 'Deleted'], ['engraved' => ['hamper_id' => $this->hamper('Gone H', 90000, [], null, $serie, true)]]);
        $untouched = $this->webProduct(['is_published' => false, 'name_id' => 'Untouched'], ['engraved' => ['hamper_id' => $this->liveHamper('Other H', $serie)]]);

        $ids = [$good->id, $overridden->id, $noOption->id, $freeHamper->id, $deletedHamper->id];

        $this->actingAs($this->admin())
            ->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => $ids])
            ->assertRedirect(route('website.products.index'));

        $this->assertTrue($good->fresh()->is_published);
        $this->assertTrue($overridden->fresh()->is_published);
        $this->assertFalse($noOption->fresh()->is_published);
        $this->assertFalse($freeHamper->fresh()->is_published);
        $this->assertFalse($deletedHamper->fresh()->is_published);
        $this->assertFalse($untouched->fresh()->is_published); // not selected

        $this->assertEqualsCanonicalizing([(int) $good->id, (int) $overridden->id], ProductCatalog::validPublishedProductIds()->all());
    }

    public function test_bulk_publish_flash_reports_published_and_skipped(): void
    {
        $serie = $this->serie('Solar');
        $good = $this->webProduct(['is_published' => false], ['engraved' => ['hamper_id' => $this->liveHamper('Good H', $serie)]]);
        $bad = $this->webProduct(['is_published' => false]);
        $live = $this->webProduct(['is_published' => true], ['engraved' => ['hamper_id' => $this->liveHamper('Live H', $serie)]]);

        $this->actingAs($this->admin())->followingRedirects()
            ->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => [$good->id, $bad->id, $live->id]])
            ->assertSee('1 produk dipublish')
            ->assertSee('1 sudah terbit')
            ->assertSee('1 dilewati (belum punya hamper yang valid)');
    }

    public function test_bulk_publish_makes_the_products_visible_in_the_public_catalog(): void
    {
        $serie = $this->serie('Solar');
        $product = $this->webProduct(['is_published' => false, 'slug' => 'solar-f', 'name_id' => 'Solar F'], ['engraved' => ['hamper_id' => $this->liveHamper('Solar F Grafir', $serie)]]);
        $this->assertSame([], ProductCatalog::cards('id'));

        $this->actingAs($this->admin())->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => [$product->id]]);

        $this->assertSame(['solar-f'], array_column(ProductCatalog::cards('id'), 'slug'));
    }

    public function test_bulk_unpublish_works(): void
    {
        $serie = $this->serie('Solar');
        $a = $this->webProduct(['is_published' => true], ['engraved' => ['hamper_id' => $this->liveHamper('A H', $serie)]]);
        $b = $this->webProduct(['is_published' => true], ['engraved' => ['hamper_id' => $this->liveHamper('B H', $serie)]]);
        $draft = $this->webProduct(['is_published' => false]);
        $kept = $this->webProduct(['is_published' => true], ['engraved' => ['hamper_id' => $this->liveHamper('K H', $serie)]]);

        $this->actingAs($this->admin()) // an invalid product can always be unpublished
            ->post(route('website.products.bulk'), ['action' => 'unpublish', 'ids' => [$a->id, $b->id, $draft->id]])
            ->assertRedirect(route('website.products.index'));

        $this->assertFalse($a->fresh()->is_published);
        $this->assertFalse($b->fresh()->is_published);
        $this->assertFalse($draft->fresh()->is_published);
        $this->assertTrue($kept->fresh()->is_published);
        $this->assertSame([(int) $kept->id], ProductCatalog::validPublishedProductIds()->all());
    }

    public function test_bulk_unpublish_flash_is_in_indonesian(): void
    {
        $serie = $this->serie('Solar');
        $a = $this->webProduct(['is_published' => true], ['engraved' => ['hamper_id' => $this->liveHamper('A H', $serie)]]);

        $this->actingAs($this->admin())->followingRedirects()
            ->post(route('website.products.bulk'), ['action' => 'unpublish', 'ids' => [$a->id]])
            ->assertSee('1 produk di-unpublish (jadi draft)');
    }

    public function test_bulk_ignores_deleted_and_unknown_products(): void
    {
        $serie = $this->serie('Solar');
        $deleted = $this->webProduct(['is_published' => false], ['engraved' => ['hamper_id' => $this->liveHamper('D H', $serie)]]);
        $deleted->delete();

        $this->actingAs($this->admin())
            ->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => [$deleted->id, 9999]])
            ->assertSessionHasNoErrors();

        $this->assertFalse(WebProduct::withTrashed()->findOrFail($deleted->id)->is_published);
    }

    public function test_bulk_validates_its_input(): void
    {
        $admin = $this->admin();
        $product = $this->webProduct(['is_published' => false]);

        $this->actingAs($admin)->post(route('website.products.bulk'), ['action' => 'delete', 'ids' => [$product->id]])
            ->assertSessionHasErrors('action');
        $this->actingAs($admin)->post(route('website.products.bulk'), ['action' => 'publish'])
            ->assertSessionHasErrors('ids');
        $this->actingAs($admin)->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => []])
            ->assertSessionHasErrors('ids');
        $this->actingAs($admin)->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => ['x']])
            ->assertSessionHasErrors('ids.0');
        $this->actingAs($admin)->post(route('website.products.bulk'), ['ids' => [$product->id]])
            ->assertSessionHasErrors('action');
    }

    public function test_duplicate_ids_are_harmless(): void
    {
        $serie = $this->serie('Solar');
        $product = $this->webProduct(['is_published' => false], ['engraved' => ['hamper_id' => $this->liveHamper('A H', $serie)]]);

        // The page keeps a phone-card and a table checkbox per product; both may be posted.
        $this->actingAs($this->admin())->followingRedirects()
            ->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => [$product->id, (string) $product->id]])
            ->assertSee('1 produk dipublish');
    }

    // --- index filters and controls ---------------------------------------------------------

    public function test_the_status_filter_splits_published_and_draft(): void
    {
        $admin = $this->admin();
        $this->webProduct(['name_id' => 'Live One', 'is_published' => true]);
        $this->webProduct(['name_id' => 'Draft One', 'is_published' => false]);

        $this->assertEqualsCanonicalizing(['Live One', 'Draft One'], $this->names($this->actingAs($admin)->get(route('website.products.index'))));
        $this->assertEqualsCanonicalizing(['Live One', 'Draft One'], $this->names($this->actingAs($admin)->get(route('website.products.index', ['status' => 'all']))));
        $this->assertSame(['Live One'], $this->names($this->actingAs($admin)->get(route('website.products.index', ['status' => 'published']))));
        $this->assertSame(['Draft One'], $this->names($this->actingAs($admin)->get(route('website.products.index', ['status' => 'draft']))));
        // Anything else falls back to all.
        $this->assertCount(2, $this->names($this->actingAs($admin)->get(route('website.products.index', ['status' => 'bogus']))));
        $this->assertCount(2, $this->names($this->actingAs($admin)->get(route('website.products.index', ['status' => ['x']]))));
    }

    public function test_the_serie_filter_matches_any_option_of_that_serie(): void
    {
        $admin = $this->admin();
        $solar = $this->serie('Solar');
        $kappa = $this->serie('Kappa');
        $this->webProduct(['name_id' => 'Solar Only'], ['engraved' => ['hamper_id' => $this->liveHamper('S1', $solar)]]);
        $this->webProduct(['name_id' => 'Mixed'], [
            'engraved' => ['hamper_id' => $this->liveHamper('K1', $kappa)],
            'plain' => ['hamper_id' => $this->liveHamper('S2', $solar)],
        ]);
        $this->webProduct(['name_id' => 'Kappa Only'], ['engraved' => ['hamper_id' => $this->liveHamper('K2', $kappa)]]);
        $this->webProduct(['name_id' => 'Deleted Hamper'], ['engraved' => ['hamper_id' => $this->hamper('S3', 1000, [], null, $solar, true)]]);
        $this->webProduct(['name_id' => 'No Option']);

        $this->assertEqualsCanonicalizing(['Solar Only', 'Mixed'], $this->names($this->actingAs($admin)->get(route('website.products.index', ['serie' => $solar]))));
        $this->assertEqualsCanonicalizing(['Mixed', 'Kappa Only'], $this->names($this->actingAs($admin)->get(route('website.products.index', ['serie' => $kappa]))));
        $this->assertCount(5, $this->names($this->actingAs($admin)->get(route('website.products.index', ['serie' => 'abc']))));
        $this->assertCount(5, $this->names($this->actingAs($admin)->get(route('website.products.index', ['serie' => ['1']]))));
    }

    public function test_filters_combine_with_search_sort_and_pagination(): void
    {
        $admin = $this->admin();
        $solar = $this->serie('Solar');
        $this->webProduct(['name_id' => 'Solar Draft', 'is_published' => false], ['engraved' => ['hamper_id' => $this->liveHamper('S1', $solar)]]);
        $this->webProduct(['name_id' => 'Solar Live', 'is_published' => true], ['engraved' => ['hamper_id' => $this->liveHamper('S2', $solar)]]);
        $this->webProduct(['name_id' => 'Other Draft', 'is_published' => false], ['engraved' => ['hamper_id' => $this->liveHamper('O1', $solar)]]);

        $response = $this->actingAs($admin)->get(route('website.products.index', [
            'status' => 'draft', 'serie' => $solar, 'search' => 'Solar', 'sort' => 'name_id', 'direction' => 'desc',
        ]));

        $this->assertSame(['Solar Draft'], $this->names($response));

        // Page 2 links keep every filter.
        for ($i = 1; $i <= 21; $i++) {
            $this->webProduct(['name_id' => "Bulk {$i}", 'is_published' => false]);
        }
        $page = $this->actingAs($admin)->get(route('website.products.index', ['status' => 'draft']));
        $this->assertStringContainsString('status=draft', $page->getContent());
        $this->assertStringContainsString('page=2', $page->getContent());
    }

    public function test_the_index_shows_the_import_button_filters_and_bulk_controls(): void
    {
        $serie = $this->serie('Solar');
        $product = $this->webProduct(['name_id' => 'Solar F'], ['engraved' => ['hamper_id' => $this->liveHamper('S1', $serie)]]);

        $html = $this->actingAs($this->admin())->get(route('website.products.index'))
            ->assertOk()
            ->assertSee('Import dari hampers')
            ->assertSee(route('website.products.import'), false)
            ->assertSee('name="status"', false)
            ->assertSee('name="serie"', false)
            ->assertSee('Solar') // the serie select lists series that have products
            ->assertSee(route('website.products.bulk'), false)
            ->assertSee('Publish')
            ->assertSee('Unpublish')
            ->getContent();

        // One checkbox per product in the phone card list and one in the table.
        $this->assertSame(2, substr_count($html, 'name="ids[]" value="' . $product->id . '"'));
    }

    public function test_the_selects_keep_the_chosen_status_and_serie(): void
    {
        $solar = $this->serie('Solar');
        $kappa = $this->serie('Kappa');
        $this->webProduct(['name_id' => 'P1'], ['engraved' => ['hamper_id' => $this->liveHamper('H1', $solar)]]);
        $this->webProduct(['name_id' => 'P2'], ['engraved' => ['hamper_id' => $this->liveHamper('H2', $kappa)]]);

        $html = $this->actingAs($this->admin())
            ->get(route('website.products.index', ['status' => 'draft', 'serie' => $kappa]))
            ->assertOk()
            ->getContent();

        // Real attributes, not unrendered Blade directives (this Laravel has no @selected).
        $this->assertStringNotContainsString('@selected', $html);
        $this->assertMatchesRegularExpression('/<option value="draft" selected>/', $html);
        $this->assertMatchesRegularExpression('/<option value="' . $kappa . '" selected>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="all" selected>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="' . $solar . '" selected>/', $html);
    }

    public function test_the_serie_select_only_lists_series_with_products_and_keeps_the_chosen_one(): void
    {
        $used = $this->serie('Used Serie');
        $unused = $this->serie('Unused Serie');
        $this->webProduct(['name_id' => 'P'], ['engraved' => ['hamper_id' => $this->liveHamper('H', $used)]]);

        $response = $this->actingAs($this->admin())->get(route('website.products.index'));
        $response->assertSee('Used Serie')->assertDontSee('Unused Serie');

        // A serie picked by URL stays visible in the select even without products.
        $this->actingAs($this->admin())->get(route('website.products.index', ['serie' => $unused]))
            ->assertSee('Unused Serie');
    }

    public function test_the_index_lists_a_published_but_invalid_product_as_hidden_and_shows_validation_errors(): void
    {
        $admin = $this->admin();
        $this->webProduct(['name_id' => 'Stuck', 'is_published' => true]);

        $this->actingAs($admin)->get(route('website.products.index'))->assertSee('Tidak tampil di website');

        $this->actingAs($admin)->followingRedirects()->from(route('website.products.index'))
            ->post(route('website.products.bulk'), ['action' => 'publish', 'ids' => []])
            ->assertSee('Pilih minimal satu produk');
    }
}
