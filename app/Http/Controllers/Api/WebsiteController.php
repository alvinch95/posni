<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Website\ProductCatalog;
use App\Services\Website\SitePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only public API for the website (routes/website_api.php, prefix /api/website/v1).
 */
class WebsiteController extends Controller
{
    /**
     * "en" only for ?locale=en; anything else (missing, "id", "xx", arrays, ...) is "id".
     */
    public static function locale(Request $request): string
    {
        return $request->query('locale') === 'en' ? 'en' : 'id';
    }

    public function products(Request $request): JsonResponse
    {
        return response()->json(['data' => ProductCatalog::cards(self::locale($request))]);
    }

    public function product(Request $request, string $slug): JsonResponse
    {
        $product = ProductCatalog::detail($slug, self::locale($request));

        if ($product === null) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $product]);
    }

    public function categories(Request $request): JsonResponse
    {
        return response()->json(['data' => SitePayload::categories(self::locale($request))]);
    }

    public function testimonials(Request $request): JsonResponse
    {
        return response()->json(['data' => SitePayload::testimonials(self::locale($request))]);
    }

    public function site(Request $request): JsonResponse
    {
        $site = SitePayload::build(self::locale($request));

        // "copy" is a flat map: when empty it must encode as {} (a PHP [] would become a JSON array).
        if ($site['copy'] === []) {
            $site['copy'] = new \stdClass();
        }

        return response()->json(['data' => $site]);
    }
}
