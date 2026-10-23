<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebFaqsTable extends Migration
{
    public function up()
    {
        Schema::create('web_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question_id');
            $table->string('question_en')->nullable();
            $table->text('answer_id');
            $table->text('answer_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_faqs');
    }
}
