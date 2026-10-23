<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryToCashBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash_balances', function (Blueprint $table) {
            // Operating-cost category for manual cash-outs, see App\Support\ExpenseCategories
            $table->string('category')->nullable()->after('related_to');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_balances', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
}
