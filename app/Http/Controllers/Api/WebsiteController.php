<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Website\ProductCatalog;
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
}
