<?php

use App\Chen\Modules\Finance\Controllers\CategoryController;
use App\Chen\Modules\Finance\Controllers\DashboardController;
use App\Chen\Modules\Finance\Controllers\RecurringController;
use App\Chen\Modules\Finance\Controllers\SettingController;
use App\Chen\Modules\Finance\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Already inside prefix "finance" and name "chen.finance." (see routes/chen.php).
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

Route::get('/recurring', [RecurringController::class, 'index'])->name('recurring.index');
Route::post('/recurring', [RecurringController::class, 'store'])->name('recurring.store');
Route::put('/recurring/{rule}', [RecurringController::class, 'update'])->name('recurring.update');
Route::patch('/recurring/{rule}/toggle', [RecurringController::class, 'toggle'])->name('recurring.toggle');
Route::delete('/recurring/{rule}', [RecurringController::class, 'destroy'])->name('recurring.destroy');

Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
