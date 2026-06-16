<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChenSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('chen_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('chen_users')->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->string('default_currency', 8)->default('IDR');
            $table->string('locale', 8)->default('id');
            $table->string('theme', 32)->default('light');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chen_settings');
    }
}
