<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('fin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chen_user_id')->constrained('chen_users')->cascadeOnDelete();
            $table->enum('type', ['expense', 'income']);
            $table->foreignId('fin_category_id')->constrained('fin_categories');
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('recurring_rule_id')->nullable()->constrained('fin_recurring_rules')->nullOnDelete();
            $table->timestamps();
            $table->index(['chen_user_id', 'date']);
            $table->index(['chen_user_id', 'type', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fin_transactions');
    }
}
