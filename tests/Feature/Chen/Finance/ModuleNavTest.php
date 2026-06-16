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

    public function test_sidebar_shows_finance_sub_navigation(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'chen')
            ->get($this->chenUrl('/finance'));

        // Sub-nav labels and their hrefs must be present so every Finance page is reachable.
        foreach (['Transaksi', 'Berulang', 'Kategori', 'Pengaturan'] as $label) {
            $response->assertSee($label);
        }
        $response->assertSee($this->chenUrl('/finance/transactions'));
        $response->assertSee($this->chenUrl('/finance/categories'));
        $response->assertSee($this->chenUrl('/finance/recurring'));
        $response->assertSee($this->chenUrl('/finance/settings'));
    }
}
