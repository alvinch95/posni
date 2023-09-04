<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRevenuePercentageToHampers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hampers', function (Blueprint $table) {
            $table->decimal('revenue_percentage')->after('capital_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hampers', function (Blueprint $table) {
            $table->dropColumn('revenue_percentage');
        });
    }
}
