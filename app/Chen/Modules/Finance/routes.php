<?php

use App\Chen\Modules\Finance\Controllers\CategoryController;
use App\Chen\Modules\Finance\Controllers\DashboardController;
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
