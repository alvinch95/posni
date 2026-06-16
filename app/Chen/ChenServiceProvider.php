<?php

namespace App\Chen;

use App\Chen\Support\ModuleRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ChenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class, fn () => new ModuleRegistry());
    }

    public function boot(): void
    {
        // Register the Chen subdomain route group here in boot(). This provider is listed
        // BEFORE App\Providers\RouteServiceProvider in config/app.php, so its boot() runs first
        // and these domain-constrained routes are inserted into the RouteCollection ahead of
        // posni's unconstrained web routes. Route matching is insertion order, so on the chen.*
        // host the Chen "/login" wins; posni's own host is unaffected by the domain constraint.
        // (Registering routes in boot() — not register() — is what the HTTP kernel reliably picks up.)
        Route::domain('chen.' . config('chen.domain'))
            ->middleware('web')
            ->name('chen.')
            ->group(base_path('routes/chen.php'));

        // Migrations live in their own folder so they stay out of posni's top-level set.
        $this->loadMigrationsFrom(database_path('migrations/chen'));

        // Shell views under the "chen" namespace.
        $this->loadViewsFrom(resource_path('views/chen'), 'chen');

        // Each enabled module registers its own view namespace (<key>::view).
        $registry = $this->app->make(ModuleRegistry::class);
        foreach ($registry->all() as $module) {
            if (is_dir($module['path'] . '/Views')) {
                View::addNamespace($module['key'], $module['path'] . '/Views');
            }
        }

        // Console commands + daily schedule, without editing app/Console/Kernel.php.
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Chen\Console\CreateUser::class,
                \App\Chen\Modules\Finance\Console\RunRecurring::class,
            ]);
        }
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('chen:finance:run-recurring')->dailyAt('00:30');
        });
    }
}
