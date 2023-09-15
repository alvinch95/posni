<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardProductController;
use App\Http\Controllers\ItemsController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SerieController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HamperController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockOpnameController;

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

Route::get('/', function () {
    // return view('home', [
    //     "title" => "Home",
    //     "active" => "home"
    // ]);
    return view('dashboard.index',[
        'title' => 'Dashboard',
        'active' => 'dashboard'
    ]);
})->middleware('auth');

Route::get('/about', function () {
    return view('about', [
        "title" => "About",
        "active" => "about",
        "name" => "Light",
        "email" => "christianto.alvin@gmail.com",
        "image" => "light.jpg"
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

Route::get('/dashboard', function(){
    return view('dashboard.index',[
        'title' => 'Dashboard',
        'active' => 'dashboard'
    ]);
})->middleware('auth');


//route resource artinya udah sepaket crud, tinggal diarahin sesuai dengan nama method di dalam controllernya
Route::get('/dashboard/products/checkSlug',[DashboardProductController::class, 'checkSlug'])->middleware('auth');
Route::resource('/dashboard/products', DashboardProductController::class)->middleware('auth');

Route::resource('/dashboard/items', ItemsController::class)->middleware('auth');

Route::resource('/dashboard/suppliers', SupplierController::class)->middleware('auth');
Route::resource('/dashboard/customers', CustomerController::class)->middleware('auth');
Route::resource('/dashboard/series', SerieController::class)->except('show')->middleware('auth');
Route::resource('/dashboard/variants', VariantController::class)->except('show')->middleware('auth');

Route::post('dashboard/hampers/copyHampers',[HamperController::class, 'copyHampers'])->middleware('auth')->name('dashboard.hampers.copy');
Route::post('dashboard/hampers/updatePrice', [HamperController::class, 'updatePrice'])->middleware('auth')->name('dashboard.hampers.updatePrice');
Route::resource('/dashboard/hampers', HamperController::class)->middleware('auth');
Route::resource('/dashboard/purchases', PurchaseController::class)->middleware('auth');

Route::resource('/dashboard/categories', CategoryController::class)->except('show')->middleware('admin');

Route::post('dashboard/sales/addToCart', [SalesOrderController::class, 'addToCart'])->middleware('auth')->name('dashboard.sales.addToCart');
Route::post('dashboard/sales/removeCart', [SalesOrderController::class, 'removeCart'])->middleware('auth')->name('dashboard.sales.removeCart');

Route::get('dashboard/sales/history', [SalesOrderController::class, 'history'])->middleware('auth')->name('dashboard.sales.history');
Route::resource('/dashboard/sales', SalesOrderController::class)->middleware('auth');

Route::get('dashboard/stockopname', [StockOpnameController::class, 'index'])->middleware('auth')->name('dashboard.stockopname.create');
Route::post('dashboard/stockopname/submit', [StockOpnameController::class, 'store'])->middleware('auth')->name('dashboard.stockopname.submit');

Route::get('dashboard/stockin', [StockOpnameController::class, 'stockin'])->middleware('auth')->name('dashboard.stockopname.stockin');
Route::get('dashboard/stockout', [StockOpnameController::class, 'stockout'])->middleware('auth')->name('dashboard.stockopname.stockout');


// Route::get('/categories/{category:slug}', function(Category $category){
//     return view('products', [
//         'title' => "Products By Category : $category->name",
//         "active" => "categories",
//         'products' => $category->products->load('author','category')
//     ]);
// });

// Route::get('/authors/{author:username}',function(User $author){
//     return view('products', [
//         'title' => "Products By Author : $author->name",
//         "active" => "products",
//         'products' => $author->products->load('category','author')
//     ]);
// });