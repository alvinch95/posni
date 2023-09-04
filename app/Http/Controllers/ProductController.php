<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Author;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        // dd(request(['search', 'category', 'author']));

        $title = '';
        if(request('category')){
            $category = Category::firstWhere('slug',request('category'));
            $title = ' in '. $category->name;
        }

        if(request('author')){
            $author = User::firstWhere('username',request('author'));
            $title = ' by '. $author->name;
        }
        return view('products',[
            "title" => "All Products".$title,
            "active" => "products",
            "products" => Product::latest()->filter(request(['search', 'category', 'author']))->paginate(4)->withQueryString()
        ]);
    }

    public function show(Product $product)
    {
        return view('product', [
            "title" => "Single product",
            "active" => "products",
            "product" => $product
        ]);
    }
}
