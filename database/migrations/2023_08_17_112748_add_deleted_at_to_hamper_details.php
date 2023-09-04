<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedAtToHamperDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hamper_details', function (Blueprint $table) {
            $table->integer('unit_price')->after('item_id')->nullable();
            $table->integer('total')->after('qty')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hamper_details', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
}
