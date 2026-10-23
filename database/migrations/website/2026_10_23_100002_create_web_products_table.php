<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebProductsTable extends Migration
{
    public function up()
    {
        Schema::create('web_products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_id');
            $table->string('name_en')->nullable();
            $table->text('description_id')->nullable();
            $table->text('description_en')->nullable();
            // One item per line, rendered as a list on the product page.
            $table->text('contents_id')->nullable();
            $table->text('contents_en')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_products');
    }
}
