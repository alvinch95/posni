<?php

namespace Tests\Feature\Website;

use App\Models\WebSetting;
use Illuminate\Support\Facades\Schema;

class WebsiteSchemaTest extends WebsiteTestCase
{
    public function test_website_tables_exist_and_settings_round_trip(): void
    {
        foreach ([
            'web_categories',
            'web_products',
            'web_product_options',
            'web_product_images',
            'web_category_product',
            'web_testimonials',
            'web_links',
            'web_faqs',
            'web_settings',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }

        WebSetting::put('copy.Hero.eyebrow', 'Halo', null);
        $this->assertSame('Halo', WebSetting::valueFor('copy.Hero.eyebrow', 'en'));

        WebSetting::put('copy.Hero.eyebrow', '', '');
        $this->assertSame(0, WebSetting::count());
    }
}
