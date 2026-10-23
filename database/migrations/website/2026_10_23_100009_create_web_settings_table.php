<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('web_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value_id')->nullable();
            $table->text('value_en')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('web_settings');
    }
}
