<?php

namespace Tests\Feature\Website;

use App\Models\User;
use App\Models\WebProduct;
use App\Models\WebProductOption;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The .env database is a copy of production, so these tests never touch it: they switch
 * to in-memory sqlite, build the POS tables the website reads, and run only the web_*
 * migrations on top.
 */
abstract class WebsiteTestCase extends TestCase
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

        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->integer('purchase_price')->nullable();
            $table->integer('selling_price')->nullable();
            $table->integer('stock')->nullable();
            $table->string('uom')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('hampers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serie_id')->nullable();
            $table->string('name');
            $table->integer('capital_price')->nullable();
            $table->decimal('revenue_percentage')->nullable();
            $table->integer('selling_price')->nullable();
            $table->string('image')->nullable();
            $table->integer('from_item')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('hamper_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hamper_id');
            $table->foreignId('item_id');
            $table->integer('qty');
            $table->integer('unit_price')->nullable();
            $table->integer('total')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('shopping_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('hamper_id');
            $table->decimal('selling_price')->nullable();
            $table->integer('qty')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Artisan::call('migrate', [
            '--path' => 'database/migrations/website',
            '--database' => 'sqlite',
        ]);
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

    protected function serie(string $name): int
    {
        return DB::table('series')->insertGetId(['name' => $name]);
    }

    protected function item(string $name, int $stock, string $uom = 'pcs', bool $deleted = false): int
    {
        return DB::table('items')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'stock' => $stock,
            'uom' => $uom,
            'deleted_at' => $deleted ? now() : null,
        ]);
    }

    /**
     * @param  array<int, array{0:int,1:int}>  $items  list of [itemId, qty]
     */
    protected function hamper(string $name, int $sellingPrice, array $items = [], ?string $image = null, ?int $serieId = null, bool $deleted = false): int
    {
        $id = DB::table('hampers')->insertGetId([
            'serie_id' => $serieId,
            'name' => $name,
            'selling_price' => $sellingPrice,
            'image' => $image,
            'deleted_at' => $deleted ? now() : null,
        ]);

        foreach ($items as [$itemId, $qty]) {
            DB::table('hamper_details')->insert([
                'hamper_id' => $id,
                'item_id' => $itemId,
                'qty' => $qty,
            ]);
        }

        return $id;
    }

    /**
     * @param  array<string, mixed>  $attrs  overrides for the web_products row
     * @param  array<string, array<string, mixed>>  $options  ['engraved' => ['hamper_id' => .., 'price_override' => ..], 'plain' => [..]]
     */
    protected function webProduct(array $attrs = [], array $options = []): WebProduct
    {
        static $n = 0;
        $n++;

        $product = WebProduct::create($attrs + [
            'slug' => "produk-{$n}",
            'name_id' => "Produk {$n}",
            'is_published' => true,
        ]);

        $order = 0;
        foreach ($options as $kind => $option) {
            WebProductOption::create($option + [
                'web_product_id' => $product->id,
                'kind' => $kind,
                'sort_order' => $order++,
            ]);
        }

        return $product;
    }
}
