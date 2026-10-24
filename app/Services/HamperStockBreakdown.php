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
 * differences, both for broken data where getStock() throws or divides by zero:
 *  - a detail whose item is soft-deleted or missing is reported (blocking, "item dihapus") and makes
 *    the stock 0, the same as Website\HamperStock::of() does when getStock() throws;
 *  - a detail with quantity 0 is ignored, so it can never block.
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
     *     limiting: list<array<string, mixed>>
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
                'can_make' => ! $deleted && $available > 0 && $needed > 0 ? intdiv($available, $needed) : 0,
                'deleted' => $deleted,
                'blocking' => false,
                'limiting' => false,
            ];
        }

        // A zero-quantity detail takes no part in the minimum, so it neither blocks nor limits.
        $counts = fn (array $component) => $component['deleted'] || $component['needed'] > 0;

        $lowest = null;
        foreach ($components as $component) {
            if ($counts($component) && ($lowest === null || $component['can_make'] < $lowest)) {
                $lowest = $component['can_make'];
            }
        }

        foreach ($components as &$component) {
            $component['blocking'] = $counts($component) && $component['can_make'] === 0;
            $component['limiting'] = $counts($component) && $component['can_make'] === $lowest;
        }
        unset($component);

        $stock = $lowest ?? 0;

        return [
            'stock' => $stock,
            'status' => $stock <= 0 ? 'out' : ($stock <= self::LOW_STOCK ? 'low' : 'ok'),
            'components' => $components,
            'blocking' => array_values(array_filter($components, fn ($component) => $component['blocking'])),
            'limiting' => array_values(array_filter($components, fn ($component) => $component['limiting'])),
        ];
    }
}
