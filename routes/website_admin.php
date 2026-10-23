<?php

use App\Http\Controllers\Website\WebCategoryController;
use App\Http\Controllers\Website\WebContactController;
use App\Http\Controllers\Website\WebContentController;
use App\Http\Controllers\Website\WebFaqController;
use App\Http\Controllers\Website\WebLinkController;
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
