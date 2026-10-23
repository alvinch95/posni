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

    public function test_setting_put_stores_trimmed_values(): void
    {
        WebSetting::put('contact.address', "  Jl. Contoh 1  \n", "\tExample St. 1 ");

        $row = WebSetting::where('key', 'contact.address')->firstOrFail();
        $this->assertSame('Jl. Contoh 1', $row->value_id);
        $this->assertSame('Example St. 1', $row->value_en);

        // a blank side is stored as null, the other side still trimmed
        WebSetting::put('contact.address', '  Jl. Baru 2 ', '   ');
        $row = $row->fresh();
        $this->assertSame('Jl. Baru 2', $row->value_id);
        $this->assertNull($row->value_en);
    }
}
