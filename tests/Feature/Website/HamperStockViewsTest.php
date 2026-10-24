<?php

namespace Tests\Feature\Website;

use App\Models\Hamper;
use App\Services\HamperStockBreakdown;
use Illuminate\Support\Facades\DB;

/**
 * Where the stock breakdown shows up: the shared partial, the posni Hampers list and detail page,
 * and the Produk Website list and hamper-info JSON. The service itself is HamperStockBreakdownTest.
 */
class HamperStockViewsTest extends WebsiteTestCase
{
    /** Visible text of a page: tags gone, entities decoded, whitespace collapsed. */
    private function text(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function partial(array $breakdown, array $extra = []): string
    {
        return view('dashboard.partials.stock_breakdown', ['breakdown' => $breakdown] + $extra)->render();
    }

    private function breakdownOf(int $hamperId): array
    {
        return HamperStockBreakdown::for(Hamper::findOrFail($hamperId));
    }

    /** A 0-stock hamper blocked by a negative box, plus a fine card. */
    private function outHamper(string $name = 'Libra Kitchen', ?int $serie = null): int
    {
        $box = $this->item('Box 20x15x10', -54);
        $card = $this->item('Kartu Ucapan', 9);

        return $this->hamper($name, 100000, [[$box, 1], [$card, 1]], null, $serie ?? $this->serie('Kitchen'));
    }

    // ---- the shared partial ----------------------------------------------------------------

    public function test_partial_out_shows_kurang_with_the_blocking_items_and_a_red_zero(): void
    {
        $html = $this->partial($this->breakdownOf($this->outHamper()));
        $text = $this->text($html);

        $this->assertStringContainsString('0', $text);
        $this->assertStringContainsString('Kurang:', $text);
        $this->assertStringContainsString('Box 20x15x10 — stok -54 / butuh 1', $text);
        $this->assertStringNotContainsString('Kartu Ucapan', $text); // not blocking
        $this->assertStringNotContainsString('Hampir habis:', $text);
        $this->assertStringContainsString('text-danger', $html);
    }

    public function test_partial_low_shows_hampir_habis_with_the_limiting_items(): void
    {
        $scarce = $this->item('Pita Satin', 2);
        $plenty = $this->item('Kotak', 50);
        $id = $this->hamper('Hampir', 1000, [[$scarce, 1], [$plenty, 1]]);

        $html = $this->partial($this->breakdownOf($id));
        $text = $this->text($html);

        $this->assertStringContainsString('Hampir habis:', $text);
        $this->assertStringContainsString('Pita Satin — stok 2 / butuh 1', $text);
        $this->assertStringNotContainsString('Kotak', $text);
        $this->assertStringNotContainsString('Kurang:', $text);
        $this->assertStringNotContainsString('text-danger', $html);
    }

    public function test_partial_ok_is_just_the_number(): void
    {
        $id = $this->hamper('Aman', 1000, [[$this->item('Kotak', 50), 1]]);

        $html = $this->partial($this->breakdownOf($id));

        $this->assertSame('50', $this->text($html));
    }

    public function test_partial_shows_the_cart_reservation(): void
    {
        $ribbon = $this->item('Pita', 5);
        $id = $this->hamper('Keranjang', 1000, [[$ribbon, 1]]);
        DB::table('shopping_carts')->insert(['user_id' => 1, 'hamper_id' => $id, 'qty' => 3]);

        $text = $this->text($this->partial($this->breakdownOf($id)));

        $this->assertStringContainsString('Pita — stok 5 / butuh 1 (3 di keranjang)', $text);
    }

    public function test_partial_marks_a_deleted_item_in_red_without_a_stock_figure(): void
    {
        $dead = $this->item('Item Mati', 50, 'pcs', true);
        $id = $this->hamper('Rusak', 1000, [[$dead, 1]]);

        $html = $this->partial($this->breakdownOf($id));

        $this->assertStringContainsString('Item Mati (item dihapus)', $this->text($html));
        $this->assertMatchesRegularExpression('/text-danger[^>]*>\s*Item Mati \(item dihapus\)/', $html);
    }

    public function test_compact_partial_shows_three_lines_and_counts_the_rest(): void
    {
        $items = [];
        foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
            $items[] = [$this->item("Box {$letter}", -1), 1];
        }
        $id = $this->hamper('Banyak', 1000, $items);
        $breakdown = $this->breakdownOf($id);

        $compact = $this->text($this->partial($breakdown, ['compact' => true]));
        $this->assertStringContainsString('Box A', $compact);
        $this->assertStringContainsString('Box C', $compact);
        $this->assertStringNotContainsString('Box D', $compact);
        $this->assertStringContainsString('+2 lainnya', $compact);

        $default = $this->text($this->partial($breakdown)); // compact is the default
        $this->assertStringContainsString('+2 lainnya', $default);

        $full = $this->text($this->partial($breakdown, ['compact' => false]));
        $this->assertStringContainsString('Box E', $full);
        $this->assertStringNotContainsString('lainnya', $full);
    }

    public function test_partial_can_hide_the_number(): void
    {
        $text = $this->text($this->partial($this->breakdownOf($this->outHamper()), ['showStock' => false]));

        $this->assertStringStartsWith('Kurang:', $text);
    }

    public function test_partial_escapes_item_names(): void
    {
        $evil = $this->item('<script>alert(1)</script>', -1);
        $id = $this->hamper('Xss', 1000, [[$evil, 1]]);

        $html = $this->partial($this->breakdownOf($id));

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    // ---- posni Hampers list ------------------------------------------------------------------

    public function test_hampers_list_shows_why_a_hamper_has_zero_stock(): void
    {
        $this->outHamper('Libra Kitchen');

        $html = $this->actingAs($this->admin())->get('/dashboard/hampers')->assertOk()->getContent();
        $text = $this->text($html);

        $this->assertStringContainsString('Libra Kitchen', $text);
        $this->assertStringContainsString('Kurang:', $text);
        $this->assertStringContainsString('Box 20x15x10 — stok -54 / butuh 1', $text);
        $this->assertStringContainsString('<td class="col-stock">', $html); // the Stok column toggle still targets it
    }

    public function test_hampers_list_still_shows_a_plain_number_for_a_healthy_hamper(): void
    {
        $id = $this->hamper('Aman', 1000, [[$this->item('Kotak', 50), 1]], null, $this->serie('Kitchen'));

        $html = $this->actingAs($this->admin())->get('/dashboard/hampers')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<td class="col-stock">(\s*<[^>]+>)*\s*50\s*</', $html);
        $this->assertStringNotContainsString('Kurang:', $html);
    }

    public function test_hampers_list_does_not_fail_when_a_hamper_lists_a_deleted_item(): void
    {
        $dead = $this->item('Item Mati', 50, 'pcs', true);
        $this->hamper('Rusak', 1000, [[$dead, 1]], null, $this->serie('Kitchen'));

        $text = $this->text($this->actingAs($this->admin())->get('/dashboard/hampers')->assertOk()->getContent());

        $this->assertStringContainsString('Item Mati (item dihapus)', $text);
    }

    public function test_hampers_list_query_count_does_not_grow_with_the_rows(): void
    {
        $serie = $this->serie('Kitchen');
        $shared = $this->item('Kotak', 20);
        for ($i = 0; $i < 6; $i++) {
            $this->hamper("H{$i}", 1000, [[$shared, 1], [$this->item("Own {$i}", $i), 1]], null, $serie);
        }
        $admin = $this->admin();

        $count = function (int $pageSize) use ($admin): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($admin)->get('/dashboard/hampers?page_size=' . $pageSize)->assertOk();
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        // page_size is free-form in the controller: 2 rows vs 6 rows must cost the same.
        $this->assertSame($count(2), $count(6));
    }

    // ---- posni hamper detail -----------------------------------------------------------------

    public function test_hamper_detail_shows_the_stock_per_item_table(): void
    {
        $id = $this->outHamper('Libra Kitchen');
        DB::table('shopping_carts')->insert(['user_id' => 1, 'hamper_id' => $id, 'qty' => 2]);

        $html = $this->actingAs($this->admin())->get("/dashboard/hampers/{$id}")->assertOk()->getContent();
        $text = $this->text($html);

        $this->assertStringContainsString('Stok per item', $text);
        $this->assertStringContainsString('Stok hamper: 0', $text);
        $this->assertStringContainsString('Item Stok item Di keranjang Tersedia Butuh/hamper Bisa jadi', $text);
        // Box row: stock -54, 2 in carts, available -56, needs 1, makes 0.
        $this->assertStringContainsString('Box 20x15x10 -54 2 -56 1 0', $text);
        $this->assertStringContainsString('Kartu Ucapan 9 2 7 1 7', $text);
        $this->assertStringContainsString('table-danger', $html);
        $this->assertStringContainsString('table-responsive', $html);
        // The existing read-only details form is still there.
        $this->assertStringContainsString('Details :', $text);
    }

    public function test_hamper_detail_lists_the_blocking_items_first_and_keeps_the_rest_in_order(): void
    {
        $ids = [
            $this->item('Aaa Kartu', 9),
            $this->item('Bbb Pita', 8),
            $this->item('Ccc Box', -3),
            $this->item('Ddd Stiker', 7),
        ];
        $id = $this->hamper('Urutan', 1000, array_map(fn ($itemId) => [$itemId, 1], $ids), null, $this->serie('Kitchen'));

        $html = $this->actingAs($this->admin())->get("/dashboard/hampers/{$id}")->assertOk()->getContent();
        $table = substr($html, strpos($html, 'id="stok-per-item"'));

        $positions = array_map(fn ($name) => strpos($table, $name), ['Ccc Box', 'Aaa Kartu', 'Bbb Pita', 'Ddd Stiker']);
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
    }

    public function test_hamper_detail_marks_limiting_rows_when_stock_is_low(): void
    {
        $scarce = $this->item('Pita Satin', 2);
        $plenty = $this->item('Kotak', 50);
        $id = $this->hamper('Hampir', 1000, [[$scarce, 1], [$plenty, 1]], null, $this->serie('Kitchen'));

        $html = $this->actingAs($this->admin())->get("/dashboard/hampers/{$id}")->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'table-warning'));
        $this->assertStringNotContainsString('table-danger', $html);
        $this->assertMatchesRegularExpression('/table-warning[^>]*>\s*<td[^>]*>\s*Pita Satin/', $html);
    }

    public function test_hamper_detail_names_a_deleted_item(): void
    {
        $dead = $this->item('Item Mati', 50, 'pcs', true);
        $id = $this->hamper('Rusak', 1000, [[$dead, 1]], null, $this->serie('Kitchen'));

        $text = $this->text($this->actingAs($this->admin())->get("/dashboard/hampers/{$id}")->assertOk()->getContent());

        $this->assertStringContainsString('Stok hamper: 0', $text);
        $this->assertStringContainsString('Item Mati (item dihapus)', $text);
    }

    // ---- Produk Website list -----------------------------------------------------------------

    public function test_website_products_list_shows_kurang_for_an_out_of_stock_option(): void
    {
        $out = $this->outHamper('Libra Grafir');
        $ok = $this->hamper('Libra Polos', 90000, [[$this->item('Kotak', 50), 1]]);
        $this->webProduct(['slug' => 'libra', 'name_id' => 'Libra'], [
            'engraved' => ['hamper_id' => $out],
            'plain' => ['hamper_id' => $ok],
        ]);

        $html = $this->actingAs($this->admin())->get(route('website.products.index'))->assertOk()->getContent();
        $text = $this->text($html);

        $this->assertStringContainsString('Habis', $text);
        $this->assertStringContainsString('Box 20x15x10 — stok -54 / butuh 1', $text);
        // Phone card + desktop table: the "Kurang:" line is in both, and only for the out option.
        $this->assertSame(2, substr_count($html, 'Kurang:'));
        $this->assertStringContainsString('Stok 50', $text);
    }

    public function test_website_products_list_query_count_does_not_grow_with_the_products(): void
    {
        $shared = $this->item('Kotak', 20);
        for ($i = 0; $i < 6; $i++) {
            $hamper = $this->hamper("H{$i}", 1000, [[$shared, 1], [$this->item("Own {$i}", $i), 1]]);
            $this->webProduct(['slug' => "p{$i}", 'name_id' => "P{$i}"], ['plain' => ['hamper_id' => $hamper]]);
        }
        $admin = $this->admin();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('website.products.index'))->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $reservationQueries = array_filter($queries, fn ($q) => str_contains($q['query'], 'shopping_carts'));
        $this->assertCount(1, $reservationQueries, 'one reservation query for the whole page');
    }

    // ---- Produk Website form: hamper info JSON -------------------------------------------------

    public function test_hamper_info_json_adds_status_blocking_and_limiting_and_keeps_the_old_keys(): void
    {
        $id = $this->outHamper('Libra Kitchen');
        DB::table('shopping_carts')->insert(['user_id' => 1, 'hamper_id' => $id, 'qty' => 2]);

        $response = $this->actingAs($this->admin())->getJson(route('website.hampers.info', $id))->assertOk();

        // Existing keys, unchanged.
        $response->assertJsonPath('id', $id)
            ->assertJsonPath('name', 'Libra Kitchen')
            ->assertJsonPath('serie', 'Kitchen')
            ->assertJsonPath('sellingPrice', 100000)
            ->assertJsonPath('stock', 0)
            ->assertJsonPath('contents', ['Box 20x15x10', 'Kartu Ucapan']);

        // New keys.
        $response->assertJsonPath('status', 'out')
            ->assertJsonPath('blocking', [[
                'name' => 'Box 20x15x10',
                'stock' => -54,
                'needed' => 1,
                'reserved' => 2,
                'deleted' => false,
            ]])
            ->assertJsonPath('limiting', [[
                'name' => 'Box 20x15x10',
                'stock' => -54,
                'needed' => 1,
                'reserved' => 2,
                'deleted' => false,
            ]]);
    }

    public function test_hamper_info_json_for_a_healthy_hamper_has_no_blockers(): void
    {
        $id = $this->hamper('Aman', 1000, [[$this->item('Kotak', 50), 1]]);

        $this->actingAs($this->admin())->getJson(route('website.hampers.info', $id))
            ->assertOk()
            ->assertJsonPath('stock', 50)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('blocking', []);
    }

    public function test_hamper_info_json_reports_a_deleted_item_as_a_blocker(): void
    {
        $dead = $this->item('Item Mati', 50, 'pcs', true);
        $id = $this->hamper('Rusak', 1000, [[$dead, 1]]);

        $this->actingAs($this->admin())->getJson(route('website.hampers.info', $id))
            ->assertOk()
            ->assertJsonPath('status', 'out')
            ->assertJsonPath('blocking.0.name', 'Item Mati (item dihapus)')
            ->assertJsonPath('blocking.0.deleted', true);
    }

    public function test_product_form_renders_a_stock_detail_line_under_each_hamper_info(): void
    {
        $html = $this->actingAs($this->admin())->get(route('website.products.create'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="form-text hamper-stock-detail"')); // one per option panel
        $this->assertStringContainsString('Hampir habis:', $html);
        $this->assertStringContainsString('Kurang:', $html);
    }
}
