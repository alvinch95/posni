<?php

namespace App\Chen\Modules\Finance\Console;

use App\Chen\Modules\Finance\Services\RecurringGenerator;
use Illuminate\Console\Command;

class RunRecurring extends Command
{
    protected $signature = 'chen:finance:run-recurring';
    protected $description = 'Materialize due recurring finance transactions';

    public function handle(RecurringGenerator $generator): int
    {
        $created = $generator->run();
        $this->info("Created {$created} recurring transaction(s).");

        return 0;
    }
}
