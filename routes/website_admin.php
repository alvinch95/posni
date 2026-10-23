<?php

use App\Http\Controllers\Website\WebCategoryController;
use Illuminate\Support\Facades\Route;

// Website CMS (admin only), mounted by App\Providers\WebsiteServiceProvider under
// /dashboard/website with the "web" + "admin" middleware and the "website." name prefix.
// Later tasks add their resources here.

Route::resource('categories', WebCategoryController::class)->except('show');
