<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebProductImagesTable extends Migration
{
    public function up()
    {
        Schema::create('web_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('web_product_id')->constrained('web_products')->cascadeOnDelete();
            $table->string('path'); // public disk, website/products/...
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_product_images');
    }
}
