<?php

use App\Http\Controllers\CashBalanceController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SerieController;
use App\Http\Controllers\HamperController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\DashboardProductController;
use App\Http\Controllers\ShopeeReminderController;
use App\Http\Controllers\WeddingInvitationController;
use App\Http\Controllers\EmployeeCheckInController;
use App\Http\Controllers\Api\CheckInController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::post('/webhook', [WebhookController::class, 'handleWebhook'])->middleware('exempt.csrf');

Route::get('/about', function () {
    return view('about', [
        "title" => "About",
        "active" => "about",
        "name" => "Light",
        "email" => "christianto.alvin@gmail.com",
        "image" => "light.jpg"
    ]);
});

Route::get('/trial',function(){
    return view('trial', [
        "title" => "Trial"
    ]);
});


Route::get('/products', [ProductController::class, 'index']);
Route::get('product/{product:slug}', [ProductController::class, 'show']);

Route::get('categories', function(){
    return view('categories', [
        'title' => 'Categories',
        "active" => "categories",
        'categories' => Category::all()
    ]);
});

Route::get('/login', [LoginController::class, 'index'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout']);

Route::get('/register', [RegisterController::class, 'index'])->middleware('guest');
Route::post('/register', [RegisterController::class, 'store']);

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard.index');


//route resource artinya udah sepaket crud, tinggal diarahin sesuai dengan nama method di dalam controllernya
Route::get('/dashboard/products/checkSlug',[DashboardProductController::class, 'checkSlug'])->middleware('admin');
Route::resource('/dashboard/products', DashboardProductController::class)->middleware('admin');

Route::resource('/dashboard/items', ItemsController::class)->middleware('admin');

Route::resource('/dashboard/suppliers', SupplierController::class)->middleware('admin');
Route::resource('/dashboard/customers', CustomerController::class)->middleware('admin');
Route::resource('/dashboard/series', SerieController::class)->except('show')->middleware('admin');
Route::resource('/dashboard/variants', VariantController::class)->except('show')->middleware('admin');

Route::post('dashboard/hampers/copyHampers',[HamperController::class, 'copyHampers'])->middleware('auth')->name('dashboard.hampers.copy');
Route::post('dashboard/hampers/updatePrice', [HamperController::class, 'updatePrice'])->middleware('auth')->name('dashboard.hampers.updatePrice');
Route::post('dashboard/hampers/updateSellingPrice', [HamperController::class, 'updateSellingPrice'])->middleware('auth')->name('dashboard.hampers.updateSellingPrice');
Route::get('dashboard/hampers/catalog/{hamper}', [HamperController::class, 'catalog'])->middleware('auth')->name('dashboard.hampers.catalog');
Route::resource('/dashboard/hampers', HamperController::class)->middleware('admin');
Route::resource('/dashboard/purchases', PurchaseController::class)->middleware('admin');

Route::resource('/dashboard/categories', CategoryController::class)->except('show')->middleware('admin');

Route::post('dashboard/sales/addToCart', [SalesOrderController::class, 'addToCart'])->middleware('auth')->name('dashboard.sales.addToCart');
Route::post('dashboard/sales/removeCart', [SalesOrderController::class, 'removeCart'])->middleware('auth')->name('dashboard.sales.removeCart');

Route::get('dashboard/sales/history', [SalesOrderController::class, 'history'])->middleware('auth')->name('dashboard.sales.history');
Route::resource('/dashboard/sales', SalesOrderController::class)->middleware('auth');

Route::get('dashboard/stockopname', [StockOpnameController::class, 'index'])->middleware('admin')->name('dashboard.stockopname.create');
Route::post('dashboard/stockopname/submit', [StockOpnameController::class, 'store'])->middleware('admin')->name('dashboard.stockopname.submit');

Route::get('dashboard/stockin', [StockOpnameController::class, 'stockin'])->middleware('admin')->name('dashboard.stockopname.stockin');
Route::get('dashboard/stockout', [StockOpnameController::class, 'stockout'])->middleware('admin')->name('dashboard.stockopname.stockout');

Route::get('dashboard/shopeereminder', [ShopeeReminderController::class, 'index'])->middleware('admin')->name('dashboard.shopeereminder.index');
Route::post('dashboard/shopeereminder/openConvert',[ShopeeReminderController::class, 'openConvert'])->middleware('auth')->name('dashboard.shopeereminder.openConvert');
Route::post('dashboard/shopeereminder/convertOrder', [ShopeeReminderController::class, 'convertOrder'])->middleware('auth')->name('dashboard.shopeereminder.convertOrder');

Route::resource('/dashboard/cashbalances', CashBalanceController::class)->except('show')->middleware('admin');

Route::resource('/dashboard/cashbalances', CashBalanceController::class)->except('show')->middleware('admin');

Route::get('/wedding-invitation', [WeddingInvitationController::class, 'index']);
Route::post('/rsvp-submit', [WeddingInvitationController::class, 'storeRSVP'])->name('rsvp.submit');
Route::post('/wishes-submit', [WeddingInvitationController::class, 'storeWish'])->name('wishes.submit');
Route::get('/wishes-list', [WeddingInvitationController::class, 'getWishes'])->name('wishes.list');

Route::middleware(['auth'])->group(function () {
    // This route serves the check-in form page
    Route::get('/attendance/checkin', [EmployeeCheckInController::class, 'index'])
         ->name('attendance.checkin');

    Route::post('/attendance/submit', [CheckInController::class, 'store'])
         ->name('attendance.submit');

    Route::get('/attendance/status', [CheckInController::class, 'status'])
     ->name('attendance.status');

     Route::get('/attendance/records', [CheckInController::class, 'records'])
    ->name('attendance.records');

    Route::get('/attendance/my-history', [CheckInController::class, 'myHistory'])
    ->name('attendance.my-history');
});

