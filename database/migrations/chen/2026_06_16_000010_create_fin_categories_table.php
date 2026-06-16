<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('fin_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chen_user_id')->constrained('chen_users')->cascadeOnDelete();
            $table->enum('type', ['expense', 'income']);
            $table->string('name');
            $table->string('color', 9)->default('#64748b');
            $table->string('icon', 16)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['chen_user_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fin_categories');
    }
}
