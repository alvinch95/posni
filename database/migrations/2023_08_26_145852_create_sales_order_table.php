<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->string('order_number');
            $table->timestamp('order_date')->nullable();
            $table->decimal('customer_fee')->nullable();
            $table->decimal('total_before_discount')->nullable();
            $table->decimal('discount_rate')->nullable();
            $table->decimal('discount_amount')->nullable();
            $table->decimal('total')->nullable();
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('sales_orders');
    }
}
