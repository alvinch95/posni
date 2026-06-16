<?php

use App\Chen\Modules\Finance\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Already inside prefix "finance" and name "chen.finance." (see routes/chen.php).
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
