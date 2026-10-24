<?php

namespace App\Services\Website;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Short-lived cache for the public catalog payloads (product list, product detail, categories).
 *
 * Building them costs dozens of queries per product (Hamper::getStock), and the endpoints are open
 * to the internet, so every payload is kept for website.cache_seconds. Stock changes from sales are
 * left to that TTL alone; CMS edits invalidate everything at once through a version number that is
 * part of every key (website:catalog:v{n}:...). A bump leaves the old keys unreachable and they
 * expire on their own. No cache tags, because the production cache driver is `file`.
 *
 * The bump is wired to the web_* model events in WebsiteServiceProvider and called after pivot syncs.
 */
class CatalogCache
{
    private const VERSION_KEY = 'website:catalog:version';

    /** Longer slugs make oversized keys (memcached caps keys at 250 chars): built fresh instead. */
    private const MAX_CACHED_SLUG = 200;

    public static function seconds(): int
    {
        return max(0, (int) config('website.cache_seconds'));
    }

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 0);
    }

    public static function key(string $name): string
    {
        return 'website:catalog:v' . self::version() . ':' . $name;
    }

    /**
     * Cache::remember, except that null is never stored (an unknown product slug must not fill the
     * cache), and that the key is fixed before the payload is built: a version bump that lands
     * mid-build leaves a stale payload under a key nobody reads any more.
     *
     * @template T
     * @param  Closure(): T  $build
     * @return T
     */
    public static function remember(string $name, Closure $build)
    {
        $seconds = self::seconds();
        if ($seconds === 0) {
            return $build();
        }

        $key = self::key($name);

        $hit = Cache::get($key);
        if ($hit !== null) {
            return $hit;
        }

        $value = $build();
        if ($value !== null) {
            Cache::put($key, $value, $seconds);
        }

        return $value;
    }

    /** Whether a product slug is short enough to be part of a cache key. */
    public static function cacheableSlug(string $slug): bool
    {
        return strlen($slug) <= self::MAX_CACHED_SLUG;
    }

    /**
     * Invalidate every cached payload. Inside a transaction the bump waits for the commit: bumping
     * earlier would let a request that reads the not-yet-committed data cache it under the new version.
     */
    public static function bump(): void
    {
        DB::afterCommit(function () {
            $next = Cache::increment(self::VERSION_KEY);

            // Drivers that cannot increment a missing key (database, memcached) answer false.
            if ($next === false) {
                Cache::forever(self::VERSION_KEY, self::version() + 1);
            }
        });
    }
}
