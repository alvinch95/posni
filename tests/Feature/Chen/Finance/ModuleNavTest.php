<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Support\ModuleRegistry;
use Tests\Chen\ChenTestCase;

class ModuleNavTest extends ChenTestCase
{
    public function test_finance_module_is_discovered(): void
    {
        $keys = array_column(app(ModuleRegistry::class)->all(), 'key');
        $this->assertContains('finance', $keys);
    }

    public function test_finance_dashboard_route_is_guarded_and_renders(): void
    {
        $this->get($this->chenUrl('/finance'))->assertRedirect($this->chenUrl('/login'));

        $this->actingAs(User::factory()->create(), 'chen')
            ->get($this->chenUrl('/finance'))
            ->assertOk()
            ->assertSee('Finance');
    }
}
