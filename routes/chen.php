<?php

use App\Chen\Http\Controllers\Auth\LoginController;
use App\Chen\Support\ModuleRegistry;
use Illuminate\Support\Facades\Route;

// Guest auth routes
Route::middleware('guest:chen')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated shell + modules
Route::middleware('auth:chen')->group(function () {
    Route::get('/', fn () => view('chen::home'))->name('home');

    // Load each enabled module's routes under its key prefix.
    foreach (app(ModuleRegistry::class)->all() as $module) {
        $routes = $module['path'] . '/routes.php';
        if (file_exists($routes)) {
            Route::prefix($module['key'])
                ->name($module['key'] . '.')
                ->group($routes);
        }
    }
});
