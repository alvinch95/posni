<?php

use App\Http\Controllers\Api\WebsiteController;
use Illuminate\Support\Facades\Route;

// Public website API, mounted by App\Providers\WebsiteServiceProvider under /api/website/v1.
// GET only; responses are allow-listed arrays (never serialized models).

Route::get('products', [WebsiteController::class, 'products']);
Route::get('products/{slug}', [WebsiteController::class, 'product'])->where('slug', '[a-z0-9-]+');
Route::get('categories', [WebsiteController::class, 'categories']);
Route::get('testimonials', [WebsiteController::class, 'testimonials']);
Route::get('site', [WebsiteController::class, 'site']);
