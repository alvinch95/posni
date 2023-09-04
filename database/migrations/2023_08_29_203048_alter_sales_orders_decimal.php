<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterSalesOrdersDecimal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('customer_fee', 13, 2)->change();
            $table->decimal('total_before_discount', 13, 2)->change();
            $table->decimal('discount_amount', 13, 2)->change();
            $table->decimal('total', 13, 2)->change();
            $table->decimal('total_capital_price',13,2)->after('discount_amount')->nullable();
            $table->decimal('total_revenue',13,2)->after('total')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('customer_fee', 8, 2)->change();
            $table->decimal('total_before_discount', 8, 2)->change();
            $table->decimal('discount_amount', 8, 2)->change();
            $table->decimal('total', 8, 2)->change();
            $table->dropColumn('total_capital_price');
            $table->dropColumn('total_revenue');
        });
    }
}
