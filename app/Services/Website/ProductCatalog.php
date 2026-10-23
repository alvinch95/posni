<?php

namespace App\Services\Website;

use App\Models\WebProduct;
use App\Models\WebProductOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds the public product payloads. Every response is a literal array of allow-listed fields;
 * models are never serialized, so cost/stock/hamper-name columns cannot leak.
 */
class ProductCatalog
{
    private const KIND_ORDER = ['engraved' => 0, 'plain' => 1];

    /**
     * The single definition of "a product the public API may show": published, not soft-deleted,
     * and with at least one option whose hamper still exists (a soft-deleted or missing hamper
     * resolves to nothing). Product lists, product details and category counts all derive from it.
     *
     * @return Collection<int, int>
     */
    public static function validPublishedProductIds(): Collection
    {
        return WebProduct::query()
            ->where('is_published', true)
            ->whereHas('options.hamper')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function cards(string $locale): array
    {
        $cards = [];

        foreach (self::query()->get() as $product) {
            $options = self::validOptions($product);
            if ($options === []) { // already excluded by query(); guards min() against a concurrent hamper delete
                continue;
            }

            $cards[] = self::card($product, $options, $locale);
        }

        return $cards;
    }

    /**
     * @return array<string, mixed>|null  null when the product is unknown, unpublished or has no valid option
     */
    public static function detail(string $slug, string $locale): ?array
    {
        $product = self::query()->where('slug', $slug)->first();
        if (! $product) {
            return null;
        }

        $options = self::validOptions($product);
        if ($options === []) {
            return null;
        }

        return self::card($product, $options, $locale) + [
            'images' => $product->images
                ->map(fn ($image) => MediaUrl::to($image->path))
                ->filter()
                ->values()
                ->all(),
            'description' => Localized::pick($product->description_id, $product->description_en, $locale),
            'contents' => self::lines(Localized::pick($product->contents_id, $product->contents_en, $locale)),
            'options' => array_map(fn (array $option) => [
                'id' => $option['id'],
                'kind' => $option['kind'],
                'price' => $option['price'],
                'inStock' => $option['inStock'],
            ], $options),
        ];
    }

    /**
     * Valid published products (see validPublishedProductIds) in display order, with everything
     * the payloads need.
     */
    private static function query(): Builder
    {
        return WebProduct::query()
            ->whereIn('id', self::validPublishedProductIds())
            ->with(['options.hamper.details', 'images', 'categories'])
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }

    /**
     * Options whose hamper still exists (soft-deleted or missing hampers resolve to null),
     * ordered engraved then plain, each with its resolved price and stock flag.
     *
     * @return array<int, array{id:int, kind:string, price:int, inStock:bool, image:?string}>
     */
    private static function validOptions(WebProduct $product): array
    {
        $options = [];

        foreach ($product->options as $option) {
            /** @var WebProductOption $option */
            $hamper = $option->hamper;
            if (! $hamper) {
                continue;
            }

            $options[] = [
                'id' => (int) $option->id,
                'kind' => (string) $option->kind,
                'price' => (int) ($option->price_override ?? $hamper->selling_price ?? 0),
                'inStock' => HamperStock::of($hamper) > 0,
                'image' => $hamper->image,
            ];
        }

        // Stable: same-kind (should not happen, kind is unique per product) keeps sort_order.
        usort($options, fn (array $a, array $b) => (self::KIND_ORDER[$a['kind']] ?? 99) <=> (self::KIND_ORDER[$b['kind']] ?? 99));

        return $options;
    }

    /**
     * @param  array<int, array{id:int, kind:string, price:int, inStock:bool, image:?string}>  $options
     * @return array<string, mixed>
     */
    private static function card(WebProduct $product, array $options, string $locale): array
    {
        return [
            'slug' => $product->slug,
            'name' => Localized::pick($product->name_id, $product->name_en, $locale),
            'image' => self::image($product, $options),
            'priceFrom' => min(array_column($options, 'price')),
            'inStock' => in_array(true, array_column($options, 'inStock'), true),
            'isBestseller' => (bool) $product->is_bestseller,
            'isFeatured' => (bool) $product->is_featured,
            'categories' => $product->categories
                ->where('is_active', true)
                ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                ->pluck('slug')
                ->values()
                ->all(),
            'sortOrder' => (int) $product->sort_order,
            'createdAt' => $product->created_at ? $product->created_at->toIso8601String() : null,
        ];
    }

    /**
     * First product image, else the first valid option's hamper image, else null.
     *
     * @param  array<int, array{image:?string}>  $options
     */
    private static function image(WebProduct $product, array $options): ?string
    {
        $first = $product->images->first();
        if ($first && ($url = MediaUrl::to($first->path))) {
            return $url;
        }

        foreach ($options as $option) {
            if ($url = MediaUrl::to($option['image'])) {
                return $url;
            }
        }

        return null;
    }

    /**
     * One entry per non-empty line, trimmed.
     *
     * @return array<int, string>
     */
    private static function lines(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));
    }
}
