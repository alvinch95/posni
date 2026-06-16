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

        // Subdomain route group — additive, posni's web/api routes are untouched.
        Route::domain('chen.' . config('chen.domain'))
            ->middleware('web')
            ->name('chen.')
            ->group(base_path('routes/chen.php'));

        // Console commands + daily schedule, without editing app/Console/Kernel.php.
        if ($this->app->runningInConsole()) {
            $this->commands([
                // \App\Chen\Console\CreateUser::class,                       // created in Task 4
                // \App\Chen\Modules\Finance\Console\RunRecurring::class,     // created in Task 9
            ]);
        }
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // command registered in Task 9; safe to schedule by name once it exists.
            // $schedule->command('chen:finance:run-recurring')->dailyAt('00:30');
        });
    }
}
