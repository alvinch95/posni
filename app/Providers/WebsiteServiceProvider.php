<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WebsiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // web_* migrations live in their own folder so they stay out of posni's top-level set.
        $this->loadMigrationsFrom(database_path('migrations/website'));

        RateLimiter::for('website', function (Request $request) {
            $apiKey = (string) config('website.api_key');
            $sent = (string) $request->header('X-Website-Key', '');

            // The Next.js server (trusted, holds the shared key) makes every SSR request from one
            // IP, so it gets a high shared bucket; anyone else is limited per IP.
            if ($apiKey !== '' && hash_equals($apiKey, $sent)) {
                return Limit::perMinute((int) config('website.server_rate_per_minute'))->by('website-server');
            }

            return Limit::perMinute((int) config('website.public_rate_per_minute'))->by($request->ip());
        });

        // Deliberately not the "api" middleware group: it carries throttle:api (60/min per IP),
        // which would stack on top of the "website" limiter.
        Route::prefix('api/website/v1')
            ->middleware(['throttle:website', SubstituteBindings::class])
            ->group(base_path('routes/website_api.php'));

        // Admin CMS: guests and staff get 403 from the "admin" middleware (IsAdmin).
        Route::prefix('dashboard/website')
            ->middleware(['web', 'admin'])
            ->name('website.')
            ->group(base_path('routes/website_admin.php'));
    }
}
