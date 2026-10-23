<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The .env database is a copy of production, so these tests never touch it: they switch
 * to in-memory sqlite and build only the tables the dashboard reads.
 */
abstract class DashboardTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('hampers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('capital_price')->nullable();
            $table->integer('selling_price')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->default(1);
            $table->string('order_number')->default('SO');
            $table->timestamp('order_date')->nullable();
            $table->decimal('customer_fee', 13, 2)->nullable();
            $table->decimal('total_capital_price', 13, 2)->nullable();
            $table->decimal('total_order', 13, 2)->nullable();
            $table->decimal('total_revenue', 13, 2)->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('sales_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id');
            $table->foreignId('hamper_id');
            $table->integer('capital_price')->nullable();
            $table->integer('selling_price')->nullable();
            $table->integer('qty')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('cash_balances', function (Blueprint $table) {
            $table->id();
            $table->timestamp('transaction_date')->nullable();
            $table->string('cash_type')->nullable();
            $table->string('related_to')->nullable();
            $table->string('category')->nullable();
            $table->decimal('current_balance', 13, 2)->nullable();
            $table->decimal('amount', 13, 2)->nullable();
            $table->decimal('end_balance', 13, 2)->nullable();
            $table->string('remark')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->integer('created_by')->default(1);
            $table->timestamps();
        });

        Schema::create('inventory_values', function (Blueprint $table) {
            $table->id();
            $table->timestamp('record_date')->nullable();
            $table->decimal('total', 13, 2)->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    protected function admin(): User
    {
        return $this->user(true);
    }

    protected function staff(): User
    {
        return $this->user(false);
    }

    private function user(bool $isAdmin): User
    {
        static $n = 0;
        $n++;

        return User::forceCreate([
            'name' => "User {$n}",
            'username' => "user{$n}",
            'email' => "user{$n}@example.test",
            'password' => bcrypt('secret'),
            'is_admin' => $isAdmin,
        ]);
    }

    protected function sale(string $date, float $total, float $revenue, array $lines = [], bool $deleted = false): int
    {
        $id = DB::table('sales_orders')->insertGetId([
            'order_date' => $date,
            'total_order' => $total,
            'total_revenue' => $revenue,
            'deleted_at' => $deleted ? $date : null,
        ]);

        foreach ($lines as $line) {
            DB::table('sales_order_details')->insert([
                'sales_order_id' => $id,
                'hamper_id' => $line['hamper_id'],
                'selling_price' => $line['selling'],
                'capital_price' => $line['capital'],
                'qty' => $line['qty'],
                'deleted_at' => $deleted ? $date : null,
            ]);
        }

        return $id;
    }

    protected function hamper(string $name): int
    {
        return DB::table('hampers')->insertGetId(['name' => $name]);
    }

    protected function cash(string $date, string $type, string $relatedTo, float $amount, ?string $category = null, ?string $remark = null, bool $deleted = false): int
    {
        return DB::table('cash_balances')->insertGetId([
            'transaction_date' => $date,
            'cash_type' => $type,
            'related_to' => $relatedTo,
            'category' => $category,
            'amount' => $amount,
            'remark' => $remark,
            'deleted_at' => $deleted ? $date : null,
        ]);
    }
}
