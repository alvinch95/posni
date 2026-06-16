<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinRecurringRulesTable extends Migration
{
    public function up()
    {
        Schema::create('fin_recurring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chen_user_id')->constrained('chen_users')->cascadeOnDelete();
            $table->foreignId('fin_category_id')->constrained('fin_categories');
            $table->enum('type', ['expense', 'income']);
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->enum('frequency', ['weekly', 'monthly', 'yearly']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['chen_user_id', 'active', 'next_run_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fin_recurring_rules');
    }
}
