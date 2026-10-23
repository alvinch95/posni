<?php

namespace Database\Seeders;

use App\Models\WebCategory;
use Illuminate\Database\Seeder;

/**
 * The six occasion categories the storefront launches with. Safe to run on any
 * environment: it inserts only when web_categories is empty, so categories that were
 * renamed, reordered or removed in the CMS are never overwritten or brought back.
 */
class WebsiteCategorySeeder extends Seeder
{
    /** slug => English name (the Indonesian name is the same). */
    private const CATEGORIES = [
        'birthday' => 'Birthday',
        'wedding' => 'Wedding',
        'graduation' => 'Graduation',
        'anniversary' => 'Anniversary',
        'farewell' => 'Farewell',
        'corporate' => 'Corporate Gift',
    ];

    public function run()
    {
        if (WebCategory::query()->exists()) {
            return;
        }

        $order = 0;
        foreach (self::CATEGORIES as $slug => $name) {
            WebCategory::create([
                'slug' => $slug,
                'name_id' => $name,
                'name_en' => $name,
                'sort_order' => $order++,
            ]);
        }
    }
}
