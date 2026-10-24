<?php

use App\Http\Controllers\Website\WebCategoryController;
use App\Http\Controllers\Website\WebContactController;
use App\Http\Controllers\Website\WebContentController;
use App\Http\Controllers\Website\WebFaqController;
use App\Http\Controllers\Website\WebLinkController;
use App\Http\Controllers\Website\WebProductController;
use App\Http\Controllers\Website\WebProductImportController;
use App\Http\Controllers\Website\WebTestimonialController;
use Illuminate\Support\Facades\Route;

// Website CMS (admin only), mounted by App\Providers\WebsiteServiceProvider under
// /dashboard/website with the "web" + "admin" middleware and the "website." name prefix.
// Later tasks add their resources here.

Route::resource('categories', WebCategoryController::class)->except('show');
Route::resource('testimonials', WebTestimonialController::class)->except('show');
Route::resource('faqs', WebFaqController::class)->except('show');

// Contact settings and the marketplace/social links. The link list renders on the contact page,
// so the links resource has no index/show and every link action redirects back to the contact page.
Route::get('contact', [WebContactController::class, 'edit'])->name('contact.edit');
Route::put('contact', [WebContactController::class, 'update'])->name('contact.update');
Route::resource('links', WebLinkController::class)->except(['index', 'show']);

// Homepage copy overrides ("Konten Homepage"), one screen for every section.
Route::get('content', [WebContentController::class, 'edit'])->name('content.edit');
Route::put('content', [WebContentController::class, 'update'])->name('content.update');

// "Import dari hampers" (preview + create drafts) and bulk publish/unpublish. Declared before the
// resource so "import" and "bulk" can never be read as a {product} id.
Route::get('products/import', [WebProductImportController::class, 'create'])->name('products.import');
Route::post('products/import', [WebProductImportController::class, 'store'])->name('products.import.store');
Route::post('products/bulk', [WebProductController::class, 'bulk'])->name('products.bulk');

// Website products (the main screen). The hamper info endpoint feeds the picker's live
// price/stock line and the "Isi dari posni" button; it is admin-only like everything here.
Route::resource('products', WebProductController::class)->except('show');
Route::get('hampers/{hamper}/info', [WebProductController::class, 'hamperInfo'])->name('hampers.info');
