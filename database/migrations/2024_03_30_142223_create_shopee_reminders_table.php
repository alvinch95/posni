<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopeeRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopee_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_request_id');
            $table->string("ordersn")->nullable();
            $table->timestamp('processed_date')->nullable();
            $table->string("customer_name")->nullable();
            $table->decimal("total_amount")->nullable();
            $table->text("item_list")->nullable();
            $table->boolean("is_processed")->default(false);
            $table->string("remarks")->nullable();
            $table->string("api_status")->nullable();
            $table->text("api_response")->nullable();
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
        Schema::dropIfExists('shopee_reminders');
    }
}
