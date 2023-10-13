<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopeeCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopee_credential', function (Blueprint $table) {
            $table->id();
            $table->string("refresh_token")->nullable();
            $table->string("access_token")->nullable();
            $table->integer("expire_in")->nullable();
            $table->string("merchant_id")->nullable();
            $table->string("shop_id")->nullable();
            $table->string("partner_id")->nullable();
            $table->string("partner_key")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopee_credential');
    }
}
