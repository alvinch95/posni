<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_balances', function (Blueprint $table) {
            $table->id();
            $table->timestamp('transaction_date')->nullable();
            $table->string('cash_type')->nullable();
            $table->string('related_to')->nullable();
            $table->decimal('current_balance', 13, 2)->nullable();
            $table->decimal('amount', 13, 2)->nullable();
            $table->decimal('end_balance', 13, 2)->nullable();
            $table->string('remark')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->integer('created_by');
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
        Schema::dropIfExists('cash_balances');
    }
}
