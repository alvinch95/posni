<?php

namespace Tests\Feature\Website;

use App\Models\WebProduct;
use App\Models\WebProductOption;
use App\Services\Website\HamperImporter;
use App\Services\Website\ProductCatalog;
use Illuminate\Support\Facades\DB;

class HamperImportTest extends WebsiteTestCase
{
    private function importer(): HamperImporter
    {
        return new HamperImporter();
    }

    /** The importable pair "<base> Grafir" / "<base> Tanpa Grafir" in one serie. */
    private function pair(int $serieId, string $base, int $engraved = 94000, int $plain = 89000): array
    {
        return [
            $this->hamper("{$base} Grafir", $engraved, [], null, $serieId),
            $this->hamper("{$base} Tanpa Grafir", $plain, [], null, $serieId),
        ];
    }

    private function optionsOf(WebProduct $product): array
    {
        return $product->options()->get()->mapWithKeys(fn ($o) => [$o->kind => (int) $o->hamper_id])->all();
    }

    public function test_a_grafir_pair_becomes_one_product_with_two_options(): void
    {
        $serie = $this->serie('Solar');
        [$engraved, $plain] = $this->pair($serie, 'Solar F');

        $result = $this->importer()->import();

        $this->assertSame(1, $result['created']);
        $this->assertSame(['Solar F'], $result['products']);
        $this->assertSame(1, WebProduct::count());

        $product = WebProduct::firstOrFail();
        $this->assertSame('Solar F', $product->name_id);
        $this->assertNull($product->name_en);
        $this->assertSame('solar-f', $product->slug);
        $this->assertNull($product->description_id);
        $this->assertNull($product->description_en);
        $this->assertFalse($product->is_bestseller);
        $this->assertFalse($product->is_featured);
        $this->assertSame(0, (int) $product->sort_order);
        $this->assertSame(0, $product->images()->count());
        $this->assertSame(0, $product->categories()->count());

        $options = $product->options()->get();
        $this->assertCount(2, $options);
        $this->assertSame(['engraved', 'plain'], $options->pluck('kind')->all());
        $this->assertSame([0, 1], $options->pluck('sort_order')->map(fn ($n) => (int) $n)->all());
        $this->assertSame([$engraved, $plain], $options->pluck('hamper_id')->map(fn ($n) => (int) $n)->all());
        $this->assertSame([null, null], $options->pluck('price_override')->all());
    }

    public function test_the_parenthesis_variant_groups_with_its_own_pair_only(): void
    {
        $serie = $this->serie('Miranda');
        $putihEngraved = $this->hamper('Miranda E Grafir (Putih)', 100000, [], null, $serie);
        $putihPlain = $this->hamper('Miranda E Tanpa Grafir (Putih)', 95000, [], null, $serie);
        $this->hamper('Miranda E Grafir', 110000, [], null, $serie); // the plain variant is a different product

        $result = $this->importer()->import();

        $this->assertSame(2, $result['created']);

        $putih = WebProduct::where('name_id', 'Miranda E (Putih)')->firstOrFail();
        $this->assertSame(['engraved' => $putihEngraved, 'plain' => $putihPlain], $this->optionsOf($putih));
        $this->assertSame('miranda-e-putih', $putih->slug);

        $bare = WebProduct::where('name_id', 'Miranda E')->firstOrFail();
        $this->assertSame(['engraved'], array_keys($this->optionsOf($bare)));
    }

    public function test_a_single_name_hamper_becomes_one_engraved_option(): void
    {
        $serie = $this->serie('Kappa');
        $hamper = $this->hamper('Kappa A', 120000, [], null, $serie);

        $result = $this->importer()->import();

        $this->assertSame(1, $result['created']);
        $product = WebProduct::firstOrFail();
        $this->assertSame('Kappa A', $product->name_id);
        $this->assertSame(['engraved' => $hamper], $this->optionsOf($product));
    }

    public function test_the_same_base_name_in_two_series_is_two_products(): void
    {
        $a = $this->serie('Solar');
        $b = $this->serie('Lunar');
        $this->pair($a, 'Nova A');
        $this->pair($b, 'Nova A');

        $result = $this->importer()->import();

        $this->assertSame(2, $result['created']);
        $this->assertEqualsCanonicalizing(['nova-a', 'nova-a-2'], WebProduct::pluck('slug')->all());
    }

    public function test_the_item_serie_is_excluded_in_any_case(): void
    {
        $item = $this->serie('Item');
        $lower = $this->serie('item ');
        $real = $this->serie('Solar');
        $this->hamper('Batu Kayu', 5000, [], null, $item);
        $this->hamper('Gelas', 5000, [], null, $lower);
        $this->hamper('Solar F Grafir', 94000, [], null, $real);

        $result = $this->importer()->import();

        $this->assertSame(1, $result['created']);
        $this->assertSame(['Solar F'], WebProduct::pluck('name_id')->all());
        $this->assertSame(['Solar'], array_column($this->importer()->preview(), 'serie'));
    }

    public function test_passing_the_item_serie_id_still_imports_nothing_from_it(): void
    {
        $item = $this->serie('Item');
        $this->hamper('Batu Kayu', 5000, [], null, $item);

        $result = $this->importer()->import([$item]);

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, WebProduct::count());
    }

    public function test_soft_deleted_hampers_are_excluded(): void
    {
        $serie = $this->serie('Solar');
        $this->hamper('Solar F Grafir', 94000, [], null, $serie);
        $this->hamper('Solar F Tanpa Grafir', 89000, [], null, $serie, true);
        $this->hamper('Gone A', 50000, [], null, $serie, true);

        $result = $this->importer()->import();

        $this->assertSame(1, $result['created']);
        $this->assertSame(['engraved'], array_keys($this->optionsOf(WebProduct::firstOrFail())));
    }

    public function test_hampers_without_a_positive_price_are_skipped(): void
    {
        $serie = $this->serie('Solar');
        $this->hamper('Free A', 0, [], null, $serie);
        $this->hamper('Negative A', -5000, [], null, $serie);
        DB::table('hampers')->insert(['serie_id' => $serie, 'name' => 'Null A', 'selling_price' => null]);
        $this->hamper('Solar F Grafir', 94000, [], null, $serie);
        $this->hamper('Solar F Tanpa Grafir', 0, [], null, $serie); // half of a pair: the priced half still imports

        $result = $this->importer()->import();

        $this->assertSame(1, $result['created']);
        $this->assertSame(4, $result['skipped_invalid']);
        $this->assertSame(['engraved'], array_keys($this->optionsOf(WebProduct::firstOrFail())));
    }

    public function test_a_hamper_already_linked_to_a_live_product_is_skipped(): void
    {
        $serie = $this->serie('Solar');
        $linked = $this->hamper('Kappa A', 120000, [], null, $serie);
        $this->hamper('Vela B', 130000, [], null, $serie);
        $this->webProduct(['slug' => 'kappa-manual', 'name_id' => 'Kappa Manual'], ['engraved' => ['hamper_id' => $linked]]);

        $result = $this->importer()->import();

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['skipped_linked']);
        $this->assertSame(0, $result['skipped_partial']);
        $this->assertSame(['Vela B'], $result['products']);
        $this->assertSame(1, WebProductOption::where('hamper_id', $linked)->count());
    }

    public function test_a_partly_linked_group_is_skipped_as_a_whole(): void
    {
        $serie = $this->serie('Solar');
        [$engraved, $plain] = $this->pair($serie, 'Solar F');
        $this->webProduct(['slug' => 'solar-manual'], ['engraved' => ['hamper_id' => $engraved]]);

        $result = $this->importer()->import();

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, $result['skipped_linked']);
        $this->assertSame(1, $result['skipped_partial']);
        $this->assertSame(1, WebProduct::count());
        $this->assertSame(0, WebProductOption::where('hamper_id', $plain)->count());
    }

    public function test_a_hamper_of_a_soft_deleted_product_can_be_imported_again(): void
    {
        $serie = $this->serie('Solar');
        $hamper = $this->hamper('Solar F Grafir', 94000, [], null, $serie);
        $old = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F'], ['engraved' => ['hamper_id' => $hamper]]);
        $old->delete();

        $result = $this->importer()->import();

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['skipped_linked']);
        $fresh = WebProduct::firstOrFail();
        $this->assertNotSame($old->id, $fresh->id);
        $this->assertSame('solar-f-2', $fresh->slug); // the deleted row still owns "solar-f"
        $this->assertSame(['engraved' => $hamper], $this->optionsOf($fresh));
    }

    public function test_running_twice_creates_nothing_the_second_time(): void
    {
        $serie = $this->serie('Solar');
        $this->pair($serie, 'Solar F');
        $this->hamper('Kappa A', 120000, [], null, $serie);

        $first = $this->importer()->import();
        $second = $this->importer()->import();

        $this->assertSame(2, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame([], $second['products']);
        $this->assertSame(2, $second['skipped_linked']);
        $this->assertSame(2, WebProduct::count());
        $this->assertSame(3, WebProductOption::count());
    }

    public function test_a_slug_collision_gets_a_numeric_suffix(): void
    {
        $serie = $this->serie('Solar');
        $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Handmade Solar F']);
        $this->webProduct(['slug' => 'solar-f-2', 'name_id' => 'Another']);
        $this->hamper('Solar F Grafir', 94000, [], null, $serie);

        $this->importer()->import();

        $this->assertSame('solar-f-3', WebProduct::where('name_id', 'Solar F')->firstOrFail()->slug);
    }

    public function test_slugs_that_collide_inside_one_import_are_made_unique(): void
    {
        $serie = $this->serie('Solar');
        $this->hamper('Solar F', 94000, [], null, $serie);
        $this->hamper('Solar-F', 95000, [], null, $serie); // different base name, same slug

        $this->importer()->import();

        $this->assertEqualsCanonicalizing(['solar-f', 'solar-f-2'], WebProduct::pluck('slug')->all());
    }

    public function test_a_name_without_slug_characters_still_gets_a_slug(): void
    {
        $serie = $this->serie('Solar');
        $this->hamper('***', 94000, [], null, $serie);

        $this->importer()->import();

        $this->assertNotSame('', WebProduct::firstOrFail()->slug);
    }

    public function test_contents_are_prefilled_from_the_hamper_details(): void
    {
        $serie = $this->serie('Solar');
        $box = $this->item('Kotak kayu', 5);
        $card = $this->item('Kartu ucapan', 5);
        $mug = $this->item('Mug kayu', 5);
        $gone = $this->item('Item dihapus', 5, 'pcs', true);

        $engraved = $this->hamper('Solar F Grafir', 94000, [[$box, 1], [$card, 1], [$gone, 1]], null, $serie);
        $this->hamper('Solar F Tanpa Grafir', 89000, [[$mug, 1], [$box, 1]], null, $serie);
        // A detail row that was soft-deleted must not show up either.
        $deletedDetail = DB::table('hamper_details')->insertGetId(['hamper_id' => $engraved, 'item_id' => $mug, 'qty' => 1]);
        DB::table('hamper_details')->where('id', $deletedDetail)->update(['deleted_at' => now()]);

        $this->importer()->import();

        // Engraved hamper's order first, then what only the plain hamper adds; no repeats.
        $this->assertSame("Kotak kayu\nKartu ucapan\nMug kayu", WebProduct::firstOrFail()->contents_id);
    }

    public function test_contents_are_null_when_a_hamper_has_no_items(): void
    {
        $serie = $this->serie('Solar');
        $this->hamper('Kappa A', 120000, [], null, $serie);

        $this->importer()->import();

        $this->assertNull(WebProduct::firstOrFail()->contents_id);
    }

    public function test_everything_is_a_draft_unless_publish_is_set(): void
    {
        $serie = $this->serie('Solar');
        $this->pair($serie, 'Solar F');

        $this->importer()->import();
        $this->assertFalse(WebProduct::firstOrFail()->is_published);
        $this->assertSame([], ProductCatalog::validPublishedProductIds()->all());

        WebProductOption::query()->delete(); // start over with the same hampers
        WebProduct::query()->forceDelete();

        $result = $this->importer()->import(null, true);

        $this->assertSame(1, $result['created']);
        $product = WebProduct::firstOrFail();
        $this->assertTrue($product->is_published);
        $this->assertSame([(int) $product->id], ProductCatalog::validPublishedProductIds()->all());
        $this->assertCount(1, ProductCatalog::cards('id'));
    }

    public function test_two_hampers_of_the_same_kind_in_a_group_keep_the_lowest_id(): void
    {
        $serie = $this->serie('Solar');
        $first = $this->hamper('Kappa A', 120000, [], null, $serie);
        $second = $this->hamper('Kappa A Grafir', 125000, [], null, $serie); // also engraved "Kappa A"
        $plain = $this->hamper('Kappa A Tanpa Grafir', 100000, [], null, $serie);

        $result = $this->importer()->import();

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['skipped_duplicate']);
        $this->assertSame(['engraved' => $first, 'plain' => $plain], $this->optionsOf(WebProduct::firstOrFail()));
        $this->assertSame(0, WebProductOption::where('hamper_id', $second)->count());
    }

    public function test_a_linked_duplicate_blocks_its_whole_group(): void
    {
        $serie = $this->serie('Solar');
        $this->hamper('Kappa A', 120000, [], null, $serie);
        $second = $this->hamper('Kappa A Grafir', 125000, [], null, $serie);
        $this->webProduct(['slug' => 'kappa-manual'], ['engraved' => ['hamper_id' => $second]]);

        $result = $this->importer()->import();

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped_partial']);
        $this->assertSame(0, $result['skipped_duplicate']);
    }

    public function test_import_is_limited_to_the_given_series(): void
    {
        $solar = $this->serie('Solar');
        $lunar = $this->serie('Lunar');
        $this->pair($solar, 'Solar F');
        $this->pair($lunar, 'Lunar G');

        $result = $this->importer()->import([$lunar]);

        $this->assertSame(['Lunar G'], $result['products']);
        $this->assertSame(['Lunar G'], WebProduct::pluck('name_id')->all());
        $this->assertSame(['Lunar'], array_column($this->importer()->preview([$lunar]), 'serie'));
    }

    public function test_a_failure_rolls_the_whole_import_back(): void
    {
        $serie = $this->serie('Solar');
        $this->hamper('Kappa A', 120000, [], null, $serie);
        $this->hamper('Vela B', 130000, [], null, $serie);

        $created = 0;
        WebProduct::creating(function () use (&$created) {
            if (++$created === 2) {
                throw new \RuntimeException('boom');
            }
        });

        try {
            $this->importer()->import();
            $this->fail('The import should have thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame(0, WebProduct::count());
        $this->assertSame(0, WebProductOption::count());
    }

    public function test_preview_counts_per_serie(): void
    {
        $solar = $this->serie('Solar');
        $kappa = $this->serie('kappa');
        $this->serie('Item');
        $empty = $this->serie('Empty');

        $this->pair($solar, 'Solar F');   // 2 hampers -> 1 product
        $this->pair($solar, 'Solar G');   // 2 hampers -> 1 product
        $this->hamper('Solar H Grafir', 0, [], null, $solar); // not eligible
        $this->hamper('Solar Gone', 1000, [], null, $solar, true); // not eligible
        $linkedHamper = $this->hamper('Kappa A', 120000, [], null, $kappa);
        $this->hamper('Kappa B', 120000, [], null, $kappa);
        $this->webProduct(['slug' => 'kappa-manual'], ['engraved' => ['hamper_id' => $linkedHamper]]);

        $rows = $this->importer()->preview();

        // One row per serie except Item, sorted by name regardless of case.
        $this->assertSame(['Empty', 'kappa', 'Solar'], array_column($rows, 'serie'));
        $byName = array_column($rows, null, 'serie');

        $this->assertSame(['serie_id' => $solar, 'serie' => 'Solar', 'hampers' => 4, 'linked' => 0, 'to_create' => 2], $byName['Solar']);
        $this->assertSame(['serie_id' => $kappa, 'serie' => 'kappa', 'hampers' => 2, 'linked' => 1, 'to_create' => 1], $byName['kappa']);
        $this->assertSame(['serie_id' => $empty, 'serie' => 'Empty', 'hampers' => 0, 'linked' => 0, 'to_create' => 0], $byName['Empty']);
        $this->assertSame(1, WebProduct::count(), 'preview must not write anything');
    }

    public function test_preview_matches_what_import_creates(): void
    {
        $solar = $this->serie('Solar');
        $kappa = $this->serie('Kappa');
        $this->pair($solar, 'Solar F');
        $this->hamper('Kappa A', 120000, [], null, $kappa);
        $partial = $this->hamper('Kappa B', 120000, [], null, $kappa);
        $this->hamper('Kappa B Tanpa Grafir', 110000, [], null, $kappa);
        $this->webProduct(['slug' => 'x'], ['engraved' => ['hamper_id' => $partial]]);

        $expected = array_sum(array_column($this->importer()->preview(), 'to_create'));
        $result = $this->importer()->import();

        $this->assertSame(2, $expected);
        $this->assertSame($expected, $result['created']);
    }
}
