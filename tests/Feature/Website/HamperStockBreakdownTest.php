<?php

namespace Tests\Feature\Website;

use App\Models\Hamper;
use App\Services\HamperStockBreakdown;
use App\Services\Website\HamperStock;
use Illuminate\Support\Facades\DB;

/**
 * HamperStockBreakdown explains Hamper::getStock() (which stays untouched): same number, plus the
 * items behind it. The first group of tests pins the two together.
 */
class HamperStockBreakdownTest extends WebsiteTestCase
{
    private function cart(int $hamperId, int $qty, bool $deleted = false): void
    {
        DB::table('shopping_carts')->insert([
            'user_id' => 1,
            'hamper_id' => $hamperId,
            'qty' => $qty,
            'deleted_at' => $deleted ? now() : null,
        ]);
    }

    private function model(int $id): Hamper
    {
        return Hamper::findOrFail($id);
    }

    /** @return array<string, mixed> the component of an item, by its name */
    private function named(array $breakdown, string $name): array
    {
        foreach ($breakdown['components'] as $component) {
            if ($component['name'] === $name) {
                return $component;
            }
        }

        $this->fail("No component named {$name}");
    }

    // ---- stock equals getStock() ---------------------------------------------------------

    public function test_stock_equals_get_stock_for_single_and_multi_item_hampers(): void
    {
        $a = $this->item('Kotak', 10);
        $b = $this->item('Kartu', 7);
        $c = $this->item('Pita', 3);

        $single = $this->hamper('Single', 1000, [[$a, 1]]);
        $multi = $this->hamper('Multi', 1000, [[$a, 2], [$b, 1], [$c, 1]]);
        $multiQty = $this->hamper('Qty', 1000, [[$b, 2]]); // 7 / 2 = 3, remainder dropped
        $negativeQty = $this->hamper('NegQty', 1000, [[$a, -2]]); // getStock uses abs(qty)

        foreach ([$single => 10, $multi => 3, $multiQty => 3, $negativeQty => 5] as $id => $expected) {
            $hamper = $this->model($id);
            $breakdown = HamperStockBreakdown::for($hamper);

            $this->assertSame($hamper->getStock(), $breakdown['stock'], "hamper {$hamper->name}");
            $this->assertSame($expected, $breakdown['stock'], "hamper {$hamper->name}");
        }
    }

    public function test_stock_equals_get_stock_with_cart_reservations(): void
    {
        $a = $this->item('Kotak', 10);
        $b = $this->item('Kartu', 20);

        $h1 = $this->hamper('H1', 1000, [[$a, 1], [$b, 1]]);
        $h2 = $this->hamper('H2', 1000, [[$a, 2]]); // shares Kotak with H1
        $gone = $this->hamper('Gone', 1000, [[$a, 1]], null, null, true); // deleted hamper: its carts do not count

        $this->cart($h1, 3);              // holds 3 x 1 Kotak and 3 x 1 Kartu
        $this->cart($h2, 2);              // holds 2 x 2 = 4 Kotak
        $this->cart($h1, 5, true);        // soft-deleted cart: ignored
        $this->cart($gone, 4);            // cart of a deleted hamper: ignored

        // A soft-deleted detail does not reserve anything either.
        $dead = DB::table('hamper_details')->insertGetId(['hamper_id' => $h2, 'item_id' => $b, 'qty' => 9]);
        DB::table('hamper_details')->where('id', $dead)->update(['deleted_at' => now()]);

        foreach ([$h1, $h2] as $id) {
            $hamper = $this->model($id);
            $this->assertSame($hamper->getStock(), HamperStockBreakdown::for($hamper)['stock'], "hamper {$hamper->name}");
        }

        // Kotak: 10 - 3 (H1 carts) - 4 (H2 carts) = 3; H1 makes 3, H2 makes 1 (3 / 2).
        $this->assertSame(3, HamperStockBreakdown::for($this->model($h1))['stock']);
        $this->assertSame(1, HamperStockBreakdown::for($this->model($h2))['stock']);
    }

    public function test_stock_equals_get_stock_for_an_item_with_negative_stock(): void
    {
        $box = $this->item('Box 20x15x10', -54);
        $card = $this->item('Kartu', 5);
        $id = $this->hamper('Negatif', 1000, [[$box, 1], [$card, 1]]);

        $hamper = $this->model($id);
        $breakdown = HamperStockBreakdown::for($hamper);

        $this->assertSame(0, $hamper->getStock());
        $this->assertSame(0, $breakdown['stock']);
        $this->assertSame('out', $breakdown['status']);
    }

    // ---- blocking / limiting / reserved ----------------------------------------------------

    public function test_a_negative_item_is_reported_as_the_blocking_component(): void
    {
        $box = $this->item('Box 20x15x10', -54);
        $card = $this->item('Kartu', 5);
        $id = $this->hamper('Negatif', 1000, [[$box, 1], [$card, 1]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertCount(2, $breakdown['components']);
        $this->assertSame(['Box 20x15x10'], array_column($breakdown['blocking'], 'name'));

        $blocking = $this->named($breakdown, 'Box 20x15x10');
        $this->assertSame(-54, $blocking['stock']);
        $this->assertSame(0, $blocking['reserved']);
        $this->assertSame(-54, $blocking['available']);
        $this->assertSame(1, $blocking['needed']);
        $this->assertSame(0, $blocking['can_make']);
        $this->assertTrue($blocking['blocking']);
        $this->assertTrue($blocking['limiting']);
        $this->assertFalse($blocking['deleted']);

        $other = $this->named($breakdown, 'Kartu');
        $this->assertSame(5, $other['can_make']);
        $this->assertFalse($other['blocking']);
        $this->assertFalse($other['limiting']);
    }

    public function test_a_zero_stock_item_blocks_and_every_blocker_is_listed(): void
    {
        $cookies = $this->item('Cookies Soes - Chocochip', 0, 'pcs');
        $strip = $this->item('Strip Photo - Glossy', -23, 'pcs');
        $card = $this->item('Kartu', 5);
        $id = $this->hamper('Dua Kurang', 1000, [[$cookies, 1], [$strip, 1], [$card, 1]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(0, $breakdown['stock']);
        $this->assertEqualsCanonicalizing(
            ['Cookies Soes - Chocochip', 'Strip Photo - Glossy'],
            array_column($breakdown['blocking'], 'name'),
        );
    }

    public function test_low_stock_lists_the_limiting_item(): void
    {
        $scarce = $this->item('Pita Satin', 2);
        $plenty = $this->item('Kotak', 50);
        $id = $this->hamper('Hampir Habis', 1000, [[$scarce, 1], [$plenty, 1]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(2, $breakdown['stock']);
        $this->assertSame('low', $breakdown['status']);
        $this->assertSame([], $breakdown['blocking']);
        $this->assertSame(['Pita Satin'], array_column($breakdown['limiting'], 'name'));
        $this->assertTrue($this->named($breakdown, 'Pita Satin')['limiting']);
        $this->assertFalse($this->named($breakdown, 'Kotak')['limiting']);
    }

    public function test_status_thresholds(): void
    {
        $this->assertSame(2, HamperStockBreakdown::LOW_STOCK);

        $make = function (int $stock): string {
            $item = $this->item("Item {$stock}", $stock);
            $id = $this->hamper("H {$stock}", 1000, [[$item, 1]]);

            return HamperStockBreakdown::for($this->model($id))['status'];
        };

        $this->assertSame('out', $make(0));
        $this->assertSame('low', $make(1));
        $this->assertSame('low', $make(2));
        $this->assertSame('ok', $make(3));
    }

    public function test_every_item_tied_for_the_minimum_is_limiting(): void
    {
        $a = $this->item('A', 4);
        $b = $this->item('B', 4);
        $c = $this->item('C', 9);
        $id = $this->hamper('Seri', 1000, [[$a, 1], [$b, 1], [$c, 1]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(4, $breakdown['stock']);
        $this->assertSame('ok', $breakdown['status']);
        $this->assertEqualsCanonicalizing(['A', 'B'], array_column($breakdown['limiting'], 'name'));
    }

    public function test_reserved_quantity_is_reported_and_counts_against_stock(): void
    {
        $ribbon = $this->item('Pita', 5);
        $card = $this->item('Kartu', 50);
        $id = $this->hamper('Di Keranjang', 1000, [[$ribbon, 1], [$card, 1]]);
        $this->cart($id, 3);

        $breakdown = HamperStockBreakdown::for($this->model($id));
        $component = $this->named($breakdown, 'Pita');

        $this->assertSame(5, $component['stock']);
        $this->assertSame(3, $component['reserved']);
        $this->assertSame(2, $component['available']);
        $this->assertSame(2, $component['can_make']);
        $this->assertSame(2, $breakdown['stock']);
        $this->assertSame('low', $breakdown['status']);
        $this->assertSame(3, $this->named($breakdown, 'Kartu')['reserved']);
    }

    public function test_a_detail_with_zero_quantity_blocks_like_the_website_shows_it(): void
    {
        // getStock() throws (stock > 0) or returns 0 (stock <= 0) for a qty-0 detail, and the public
        // website maps both to 0, so the breakdown reports the detail as blocking and the stock as 0.
        $stiker = $this->item('Stiker', 4);
        $card = $this->item('Kartu', 5);
        $id = $this->hamper('Nol', 1000, [[$stiker, 0], [$card, 1]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(0, $breakdown['stock']);
        $this->assertSame('out', $breakdown['status']);
        $this->assertSame(['Stiker'], array_column($breakdown['blocking'], 'name'));

        $component = $this->named($breakdown, 'Stiker');
        $this->assertSame(0, $component['needed']);
        $this->assertSame(0, $component['can_make']);
        $this->assertTrue($component['blocking']);
        $this->assertTrue($component['limiting']);
        $this->assertFalse($this->named($breakdown, 'Kartu')['blocking']);
    }

    public function test_stock_equals_the_website_stock_for_a_zero_quantity_detail(): void
    {
        $card = $this->item('Kartu', 5);
        $positive = $this->item('Stiker Ada', 4);   // getStock() divides by zero here, the website shows 0
        $empty = $this->item('Stiker Habis', 0);    // getStock() returns 0 here
        $negative = $this->item('Stiker Minus', -3);

        foreach ([$positive, $empty, $negative] as $itemId) {
            $id = $this->hamper("Nol {$itemId}", 1000, [[$card, 1], [$itemId, 0]]);
            $hamper = $this->model($id);

            $stock = HamperStockBreakdown::for($hamper)['stock'];

            $this->assertSame(HamperStock::of($hamper), $stock, "item {$itemId}");
            $this->assertSame(0, $stock, "item {$itemId}");
        }

        // Where getStock() itself does not throw, it agrees too.
        $hamper = $this->model($this->hamper('Nol Habis', 1000, [[$empty, 0]]));
        $this->assertSame($hamper->getStock(), HamperStockBreakdown::for($hamper)['stock']);
    }

    public function test_the_same_item_in_two_details_of_a_cart_hamper_matches_get_stock(): void
    {
        $pinus = $this->item('Pinus Kering', 10);
        $card = $this->item('Kartu', 40);

        // The cart hamper lists Pinus twice; its cart reserves 1 x 3 for each of the two joined rows.
        $cartHamper = $this->hamper('Solar F Grafir', 1000, [[$pinus, 1], [$pinus, 1], [$card, 1]]);
        $target = $this->hamper('Target', 1000, [[$pinus, 1], [$card, 2]]);
        $this->cart($cartHamper, 3);

        foreach ([$cartHamper, $target] as $id) {
            $hamper = $this->model($id);
            $breakdown = HamperStockBreakdown::for($hamper);

            $this->assertSame($hamper->getStock(), $breakdown['stock'], "hamper {$hamper->name}");
            $this->assertSame(6, $this->named($breakdown, 'Pinus Kering')['reserved'], "hamper {$hamper->name}");
        }

        // 10 - 6 reserved = 4 for the one-Pinus target.
        $this->assertSame(4, HamperStockBreakdown::for($this->model($target))['stock']);
    }

    // ---- which blockers come first ---------------------------------------------------------------

    public function test_cart_held_items_come_first_among_the_blockers(): void
    {
        $a = $this->item('A Minus Lima', -5);
        $b = $this->item('B Minus Sembilan', -9);
        $c = $this->item('C Minus Satu', -1);
        $pinus = $this->item('Pinus Kering', 34);
        $cartHamper = $this->hamper('Solar F Grafir', 1000, [[$pinus, 1]]);
        $this->cart($cartHamper, 50); // reserves 50 of the 34 Pinus: available -16

        // Pinus is the last detail of the target hamper, yet it must lead the blocking list.
        $id = $this->hamper('Target', 1000, [[$a, 1], [$b, 1], [$c, 1], [$pinus, 1]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(
            ['Pinus Kering', 'B Minus Sembilan', 'A Minus Lima', 'C Minus Satu'],
            array_column($breakdown['blocking'], 'name'),
        );
        $pinusComponent = $this->named($breakdown, 'Pinus Kering');
        $this->assertSame(34, $pinusComponent['stock']);
        $this->assertSame(50, $pinusComponent['reserved']);
        $this->assertSame(-16, $pinusComponent['available']);
    }

    public function test_blockers_are_ordered_reserved_then_deleted_then_most_short_then_detail_order(): void
    {
        $shortFirst = $this->item('Zeta Nol Pertama', 0);
        $shortSecond = $this->item('Alpha Nol Kedua', 0);
        $veryShort = $this->item('Minus Tujuh', -7);
        $dead = $this->item('Item Mati', 10, 'pcs', true);
        $held = $this->item('Ditahan', 3);
        $cartHamper = $this->hamper('Penahan', 1000, [[$held, 1]]);
        $this->cart($cartHamper, 5); // Ditahan: 3 - 5 = -2, held by a cart

        $id = $this->hamper('Urutan', 1000, [[$shortFirst, 1], [$shortSecond, 1], [$veryShort, 1], [$dead, 1], [$held, 1]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(
            ['Ditahan', 'Item Mati (item dihapus)', 'Minus Tujuh', 'Zeta Nol Pertama', 'Alpha Nol Kedua'],
            array_column($breakdown['blocking'], 'name'),
        );
        // components keep the hamper's own detail order.
        $this->assertSame(
            ['Zeta Nol Pertama', 'Alpha Nol Kedua', 'Minus Tujuh', 'Item Mati (item dihapus)', 'Ditahan'],
            array_column($breakdown['components'], 'name'),
        );
    }

    public function test_limiting_items_use_the_same_ordering(): void
    {
        $plain = $this->item('Polos', 2);
        $held = $this->item('Ditahan', 5);
        $cartHamper = $this->hamper('Penahan', 1000, [[$held, 1]]);
        $this->cart($cartHamper, 3); // Ditahan: 5 - 3 = 2, the same as Polos
        $other = $this->item('Banyak', 50);

        $id = $this->hamper('Seri', 1000, [[$plain, 1], [$held, 1], [$other, 1]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(2, $breakdown['stock']);
        $this->assertSame(['Ditahan', 'Polos'], array_column($breakdown['limiting'], 'name'));
    }

    public function test_rows_put_blocking_first_then_limiting_when_low_then_the_rest_in_order(): void
    {
        $a = $this->item('Aaa Banyak', 50);
        $b = $this->item('Bbb Minus', -3);
        $c = $this->item('Ccc Banyak', 40);
        $id = $this->hamper('Baris', 1000, [[$a, 1], [$b, 1], [$c, 1]]);
        $this->assertSame(
            ['Bbb Minus', 'Aaa Banyak', 'Ccc Banyak'],
            array_column(HamperStockBreakdown::for($this->model($id))['rows'], 'name'),
        );

        $low = $this->item('Ddd Dua', 2);
        $e = $this->item('Eee Banyak', 30);
        $f = $this->item('Fff Banyak', 20);
        $lowId = $this->hamper('Baris Rendah', 1000, [[$e, 1], [$low, 1], [$f, 1]]);
        $this->assertSame(
            ['Ddd Dua', 'Eee Banyak', 'Fff Banyak'],
            array_column(HamperStockBreakdown::for($this->model($lowId))['rows'], 'name'),
        );

        // An ok hamper has no culprits to lift: its own order.
        $okId = $this->hamper('Baris Aman', 1000, [[$f, 1], [$e, 1]]);
        $this->assertSame(
            ['Fff Banyak', 'Eee Banyak'],
            array_column(HamperStockBreakdown::for($this->model($okId))['rows'], 'name'),
        );
    }

    // ---- edge cases ----------------------------------------------------------------------

    public function test_a_deleted_item_gives_zero_stock_and_a_deleted_blocking_component(): void
    {
        $dead = $this->item('Item Mati', 50, 'pcs', true);
        $card = $this->item('Kartu', 5);
        $id = $this->hamper('Rusak', 1000, [[$dead, 1], [$card, 1]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(0, $breakdown['stock']);
        $this->assertSame('out', $breakdown['status']);

        $component = $this->named($breakdown, 'Item Mati (item dihapus)');
        $this->assertTrue($component['deleted']);
        $this->assertTrue($component['blocking']);
        $this->assertSame(0, $component['can_make']);
        $this->assertSame($dead, $component['item_id']);
        $this->assertSame(['Item Mati (item dihapus)'], array_column($breakdown['blocking'], 'name'));
    }

    public function test_a_detail_whose_item_row_is_missing_is_named_by_its_id(): void
    {
        $id = $this->hamper('Yatim', 1000, [[999, 2]]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(0, $breakdown['stock']);
        $this->assertSame('#999 (item dihapus)', $breakdown['components'][0]['name']);
        $this->assertTrue($breakdown['components'][0]['deleted']);
        $this->assertTrue($breakdown['components'][0]['blocking']);
    }

    public function test_a_hamper_without_details_is_out_with_no_components(): void
    {
        $id = $this->hamper('Kosong', 1000, []);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(0, $breakdown['stock']);
        $this->assertSame('out', $breakdown['status']);
        $this->assertSame([], $breakdown['components']);
        $this->assertSame([], $breakdown['blocking']);
        $this->assertSame([], $breakdown['limiting']);
    }

    public function test_soft_deleted_details_are_not_components(): void
    {
        $old = $this->item('Item Lama', -5);
        $card = $this->item('Kartu', 5);
        $id = $this->hamper('Ganti', 1000, [[$card, 1]]);
        $detail = DB::table('hamper_details')->insertGetId(['hamper_id' => $id, 'item_id' => $old, 'qty' => 1]);
        DB::table('hamper_details')->where('id', $detail)->update(['deleted_at' => now()]);

        $breakdown = HamperStockBreakdown::for($this->model($id));

        $this->assertSame(5, $breakdown['stock']);
        $this->assertSame(['Kartu'], array_column($breakdown['components'], 'name'));
    }

    public function test_the_service_reads_the_item_uom(): void
    {
        $kg = $this->item('Kayu', 6, 'kg');
        $id = $this->hamper('Berat', 1000, [[$kg, 2]]);

        $this->assertSame('kg', HamperStockBreakdown::for($this->model($id))['components'][0]['uom']);
    }

    // ---- forMany -------------------------------------------------------------------------

    public function test_for_many_equals_for_per_hamper_and_is_keyed_by_hamper_id(): void
    {
        $a = $this->item('Kotak', 10);
        $b = $this->item('Kartu', -3);
        $dead = $this->item('Item Mati', 4, 'pcs', true);

        $ids = [
            $this->hamper('Satu', 1000, [[$a, 1]]),
            $this->hamper('Dua', 1000, [[$a, 2], [$b, 1]]),
            $this->hamper('Tiga', 1000, [[$dead, 1]]),
            $this->hamper('Empat', 1000, []),
        ];
        $this->cart($ids[0], 2);

        $hampers = Hamper::whereIn('id', $ids)->get();
        $many = HamperStockBreakdown::forMany($hampers);

        $this->assertEqualsCanonicalizing($ids, array_keys($many));
        foreach ($hampers as $hamper) {
            $this->assertSame(HamperStockBreakdown::for($hamper), $many[$hamper->id], "hamper {$hamper->name}");
        }
    }

    public function test_for_many_query_count_is_bounded_and_does_not_grow_with_the_hampers(): void
    {
        $shared = $this->item('Kotak', 20);
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = $this->hamper("H{$i}", 1000, [[$shared, 1], [$this->item("Own {$i}", $i), 1]]);
        }
        $this->cart($ids[0], 1);
        $hampers = Hamper::whereIn('id', $ids)->get();

        $count = function ($subset): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            HamperStockBreakdown::forMany($subset);
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $three = $count($hampers->take(3));
        $ten = $count($hampers);

        $this->assertLessThanOrEqual(5, $ten);
        $this->assertSame($three, $ten);
    }

    public function test_for_many_with_no_hampers_runs_no_queries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $result = HamperStockBreakdown::forMany([]);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame([], $result);
        $this->assertSame(0, $queries);
    }
}
