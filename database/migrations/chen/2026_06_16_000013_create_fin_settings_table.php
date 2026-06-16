<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('fin_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chen_user_id')->unique()->constrained('chen_users')->cascadeOnDelete();
            $table->string('currency', 8)->default('IDR');
            $table->decimal('monthly_spending_target', 15, 2)->nullable();
            $table->decimal('monthly_savings_target', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fin_settings');
    }
}
