<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebTestimonialsTable extends Migration
{
    public function up()
    {
        Schema::create('web_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('quote_id');
            $table->text('quote_en')->nullable();
            $table->string('occasion_id')->nullable();
            $table->string('occasion_en')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_testimonials');
    }
}
