<?php

namespace Tests\Feature\Chen;

use App\Chen\Support\ModuleRegistry;
use Tests\Chen\ChenTestCase;

class ProviderBootTest extends ChenTestCase
{
    public function test_module_registry_is_bound(): void
    {
        $this->assertInstanceOf(ModuleRegistry::class, app(ModuleRegistry::class));
    }

    public function test_chen_domain_config_has_a_value(): void
    {
        $this->assertNotEmpty(config('chen.domain'));
    }
}
