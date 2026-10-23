<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebProductOptionsTable extends Migration
{
    public function up()
    {
        Schema::create('web_product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('web_product_id')->constrained('web_products')->cascadeOnDelete();
            // Deliberately no FK constraint: hampers are soft-deleted by posni, and a missing or
            // deleted hamper just drops the option from the public API.
            $table->unsignedBigInteger('hamper_id')->index();
            $table->string('kind', 20); // engraved | plain
            $table->integer('price_override')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['web_product_id', 'kind']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_product_options');
    }
}
