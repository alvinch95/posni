<?php

namespace Tests\Chen;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

abstract class ChenTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolate from MySQL: run only Chen migrations on an in-memory sqlite db.
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        Artisan::call('migrate', [
            '--path' => 'database/migrations/chen',
            '--database' => 'sqlite',
        ]);
    }

    /** Base URL for the Chen subdomain in tests. */
    protected function chenUrl(string $path = '/'): string
    {
        return 'http://chen.' . config('chen.domain') . $path;
    }
}
