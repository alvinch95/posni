<?php

namespace App\Services;

use App\Models\Hamper;
use App\Models\HamperDetail;
use App\Models\ShoppingCart;

/**
 * Explains a hamper's stock: which items it is made of, how many of each are really available,
 * and which of them hold the number down (or at zero).
 *
 * Display only. It mirrors Hamper::getStock(), which stays the source of truth for the sales flow
 * and is never changed: per detail, item stock minus what live shopping carts have reserved of that
 * item, divided by the detail's quantity; the hamper's stock is the lowest of those. Two deliberate
 * differences, both for broken data where getStock() throws (so the public website, through
 * Website\HamperStock::of(), shows 0):
 *  - a detail whose item is soft-deleted or missing is reported (blocking, "item dihapus") and makes
 *    the stock 0;
 *  - a detail with quantity 0 is blocking too (can_make 0), as getStock() divides by zero for it
 *    when the item has stock and returns 0 when it has none.
 *
 * `components` are in detail order. `blocking` and `limiting` are ordered most telling first (see
 * culpritsFirst()); `rows` is `components` with those culprits lifted to the top, for a detail table.
 *
 * Queries are batched per call, not per hamper: forMany() costs three queries (details, items,
 * reservations) however many hampers it is given.
 */
class HamperStockBreakdown
{
    /** A hamper with 1..LOW_STOCK units left is "low". */
    public const LOW_STOCK = 2;

    /**
     * @return array{
     *     stock: int,
     *     status: 'out'|'low'|'ok',
     *     components: list<array<string, mixed>>,
     *     blocking: list<array<string, mixed>>,
     *     limiting: list<array<string, mixed>>,
     *     rows: list<array<string, mixed>>
     * }
     */
    public static function for(Hamper $hamper): array
    {
        $many = self::forMany([$hamper]);

        return $many[(int) $hamper->getKey()] ?? self::build([], []);
    }

    /**
     * Breakdowns of many hampers at once, keyed by hamper id.
     *
     * @param  iterable<Hamper>  $hampers
     * @return array<int, array<string, mixed>>
     */
    public static function forMany(iterable $hampers): array
    {
        $ids = [];
        foreach ($hampers as $hamper) {
            if ($hamper->getKey() !== null) {
                $ids[(int) $hamper->getKey()] = true;
            }
        }
        $ids = array_keys($ids);

        if ($ids === []) {
            return [];
        }

        // Same details getStock() walks: hamper_details has a soft-delete scope. The item is loaded
        // withTrashed so a deleted item can still be named.
        $details = HamperDetail::query()
            ->with(['item' => fn ($query) => $query->withTrashed()])
            ->whereIn('hamper_id', $ids)
            ->orderBy('id')
            ->get();

        $reserved = self::reservations($details->pluck('item_id')->filter()->unique()->values()->all());
        $byHamper = $details->groupBy('hamper_id');

        $result = [];
        foreach ($ids as $id) {
            $result[$id] = self::build($byHamper->get($id, collect())->all(), $reserved);
        }

        return $result;
    }

    /**
     * Units of each item held by live shopping carts: SUM(hamper_details.qty * shopping_carts.qty)
     * over live carts of live hampers that contain the item. This is the sum getStock() subtracts
     * cart by cart, with the same joins and filters (ShoppingCart's soft-delete scope covers
     * shopping_carts.deleted_at), done once for all items.
     *
     * @param  array<int, int>  $itemIds
     * @return array<int, int> item id => reserved units
     */
    private static function reservations(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        $rows = ShoppingCart::query()
            ->join('hampers', 'shopping_carts.hamper_id', '=', 'hampers.id')
            ->join('hamper_details', 'hamper_details.hamper_id', '=', 'hampers.id')
            ->join('items', 'items.id', '=', 'hamper_details.item_id')
            ->whereIn('items.id', $itemIds)
            ->whereNull('hamper_details.deleted_at')
            ->whereNull('hampers.deleted_at')
            ->whereNull('items.deleted_at')
            ->groupBy('items.id')
            ->selectRaw('items.id as item_id, SUM(hamper_details.qty * shopping_carts.qty) as reserved')
            ->get();

        $reserved = [];
        foreach ($rows as $row) {
            $reserved[(int) $row->item_id] = (int) $row->reserved;
        }

        return $reserved;
    }

    /**
     * @param  array<int, HamperDetail>  $details
     * @param  array<int, int>  $reserved
     */
    private static function build(array $details, array $reserved): array
    {
        $components = [];
        foreach ($details as $detail) {
            $item = $detail->item; // null only when the row is gone for good
            $deleted = $item === null || $item->deleted_at !== null;
            $itemId = $detail->item_id !== null ? (int) $detail->item_id : null;

            $stock = $item !== null ? (int) $item->stock : 0;
            $held = $itemId !== null ? ($reserved[$itemId] ?? 0) : 0;
            $available = $stock - $held;
            $needed = abs((int) $detail->qty);

            $name = $item !== null ? (string) $item->name : '#' . $itemId;

            $components[] = [
                'item_id' => $itemId,
                'name' => $deleted ? $name . ' (item dihapus)' : $name,
                'uom' => $item !== null ? $item->uom : null,
                'stock' => $stock,
                'reserved' => $held,
                'available' => $available,
                'needed' => $needed,
                // needed 0 -> 0 as well: see the class comment.
                'can_make' => ! $deleted && $available > 0 && $needed > 0 ? intdiv($available, $needed) : 0,
                'deleted' => $deleted,
                'blocking' => false,
                'limiting' => false,
            ];
        }

        $lowest = null;
        foreach ($components as $component) {
            if ($lowest === null || $component['can_make'] < $lowest) {
                $lowest = $component['can_make'];
            }
        }

        $blockingAt = [];
        $limitingAt = [];
        foreach ($components as $position => &$component) {
            $component['blocking'] = $component['can_make'] === 0;
            $component['limiting'] = $component['can_make'] === $lowest;

            if ($component['blocking']) {
                $blockingAt[] = $position;
            }
            if ($component['limiting']) {
                $limitingAt[] = $position;
            }
        }
        unset($component);

        $stock = $lowest ?? 0;
        $status = $stock <= 0 ? 'out' : ($stock <= self::LOW_STOCK ? 'low' : 'ok');

        $blockingAt = self::culpritsFirst($components, $blockingAt);
        $limitingAt = self::culpritsFirst($components, $limitingAt);

        // Rows for a detail table: the culprits first (the blockers, and the limiters too when the
        // stock is low), everything else after them in the hamper's own order.
        $leading = $status === 'low' ? array_merge($blockingAt, array_diff($limitingAt, $blockingAt)) : $blockingAt;
        $rowsAt = array_merge($leading, array_diff(array_keys($components), $leading));

        $pick = fn (array $positions) => array_map(fn ($position) => $components[$position], $positions);

        return [
            'stock' => $stock,
            'status' => $status,
            'components' => $components,
            'blocking' => $pick($blockingAt),
            'limiting' => $pick($limitingAt),
            'rows' => $pick($rowsAt),
        ];
    }

    /**
     * The positions of some components, most telling first. The lists are cut short on screen ("+N
     * lainnya"), so the likeliest cause must lead: items held by a cart first (stock looks fine, a
     * forgotten cart is what takes it), then deleted items, then the most short (lowest
     * can_make, then most negative available), then the hamper's own detail order.
     *
     * @param  array<int, array<string, mixed>>  $components  by detail position
     * @param  array<int, int>  $positions
     * @return array<int, int>
     */
    private static function culpritsFirst(array $components, array $positions): array
    {
        $key = function (int $position) use ($components): array {
            $component = $components[$position];

            return [
                $component['reserved'] > 0 ? 0 : 1,
                $component['deleted'] ? 0 : 1,
                $component['can_make'],
                $component['available'],
                $position,
            ];
        };

        usort($positions, fn (int $a, int $b) => $key($a) <=> $key($b));

        return $positions;
    }
}
