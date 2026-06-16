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

        // Register the Chen subdomain route group during register() so its domain-constrained
        // routes are inserted into the RouteCollection BEFORE posni's RouteServiceProvider
        // loads routes/web.php (it defers that to an app booted() callback registered at its
        // own register()). Iteration/match order is insertion order, so on the chen.* host the
        // Chen "/login" wins over posni's unconstrained "/login". posni's own host is unaffected
        // because the domain constraint stops these routes matching it.
        Route::domain('chen.' . config('chen.domain'))
            ->middleware('web')
            ->name('chen.')
            ->group(base_path('routes/chen.php'));
    }

    public function boot(): void
    {
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
                // \App\Chen\Modules\Finance\Console\RunRecurring::class,     // created in Task 9
            ]);
        }
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // command registered in Task 9; safe to schedule by name once it exists.
            // $schedule->command('chen:finance:run-recurring')->dailyAt('00:30');
        });
    }
}
