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
     * and with at least one valid option. An option is valid when its hamper still exists (a
     * soft-deleted or missing hamper resolves to nothing) and its effective price, the override
     * or else the hamper's selling price, is above zero (never show "Rp 0"). Product lists,
     * product details and category counts all derive from it; validOptions() is its PHP twin.
     *
     * @return Collection<int, int>
     */
    public static function validPublishedProductIds(): Collection
    {
        return self::withValidOption(WebProduct::query()->where('is_published', true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * Which of these products have at least one valid option, published or not: the same option rule
     * as validPublishedProductIds() (a draft must pass it before it may be published). Soft-deleted
     * products never match.
     *
     * @param  array<int, int>  $productIds
     * @return Collection<int, int>
     */
    public static function validProductIds(array $productIds): Collection
    {
        return self::withValidOption(WebProduct::query()->whereIn('id', $productIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    /** The one definition of "has a valid option" (see validPublishedProductIds). */
    private static function withValidOption(Builder $products): Builder
    {
        return $products->whereHas('options', function ($options) {
            $options->whereHas('hamper', function ($hamper) use ($options) {
                $override = $options->getModel()->qualifyColumn('price_override');
                $selling = $hamper->getModel()->qualifyColumn('selling_price');

                $hamper->whereRaw("COALESCE({$override}, {$selling}) > 0");
            });
        });
    }

    /**
     * The product list. Cached for website.cache_seconds (see CatalogCache).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function cards(string $locale): array
    {
        return CatalogCache::remember("products:{$locale}", fn () => self::buildCards($locale));
    }

    /**
     * One product. Cached like the list, but only when it exists: unknown or hidden slugs are
     * rebuilt every time, so they can never fill the cache.
     *
     * @return array<string, mixed>|null  null when the product is unknown, unpublished or has no valid option
     */
    public static function detail(string $slug, string $locale): ?array
    {
        if (! CatalogCache::cacheableSlug($slug)) {
            return self::buildDetail($slug, $locale);
        }

        return CatalogCache::remember("product:{$locale}:{$slug}", fn () => self::buildDetail($slug, $locale));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildCards(string $locale): array
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
     * @return array<string, mixed>|null
     */
    private static function buildDetail(string $slug, string $locale): ?array
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
     * Valid options only (see validPublishedProductIds): the hamper still exists (soft-deleted or
     * missing hampers resolve to null) and the effective price is above zero. Ordered engraved
     * then plain, each with its resolved price and stock flag.
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

            $price = (int) ($option->price_override ?? $hamper->selling_price ?? 0);
            if ($price <= 0) {
                continue;
            }

            $options[] = [
                'id' => (int) $option->id,
                'kind' => (string) $option->kind,
                'price' => $price,
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
