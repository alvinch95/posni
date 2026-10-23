<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebCategoryProductTable extends Migration
{
    public function up()
    {
        Schema::create('web_category_product', function (Blueprint $table) {
            $table->foreignId('web_category_id')->constrained('web_categories')->cascadeOnDelete();
            $table->foreignId('web_product_id')->constrained('web_products')->cascadeOnDelete();

            $table->primary(['web_category_id', 'web_product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_category_product');
    }
}
