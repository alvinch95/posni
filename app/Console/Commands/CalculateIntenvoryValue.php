<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\InventoryValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateIntenvoryValue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory-value:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to calculate inventory value. Will be run daily by cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Fetch items from the database
        $items = Item::all();
        $this->info('Fetching all items.');

        $this->info('Calculating...');
         $balance = DB::table('items as i')
        ->select(DB::raw('SUM(i.purchase_price * i.stock) as total_value'))
        ->value('total_value');

        $inventoryValue = new InventoryValue;
        $inventoryValue->record_date = now();
        $inventoryValue->total = $balance;
        $inventoryValue->save();

        $this->info('Inventory values calculated and stored successfully.');
    }
}
