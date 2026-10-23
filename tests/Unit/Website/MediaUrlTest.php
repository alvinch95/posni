<?php

namespace Tests\Unit\Website;

use App\Services\Website\MediaUrl;
use Tests\TestCase;

// Needs config() but touches no database, so it is safe to run against the real .env.
class MediaUrlTest extends TestCase
{
    public function test_null_and_blank_paths_give_null(): void
    {
        $this->assertNull(MediaUrl::to(null));
        $this->assertNull(MediaUrl::to(''));
    }

    public function test_trailing_slash_on_app_url_does_not_double_up(): void
    {
        config(['app.url' => 'http://posni.test/']);

        $this->assertSame(
            'http://posni.test/storage/website/products/a.jpg',
            MediaUrl::to('website/products/a.jpg')
        );
    }

    public function test_leading_slash_on_path_and_missing_trailing_slash_on_app_url(): void
    {
        config(['app.url' => 'http://posni.test']);

        $this->assertSame(
            'http://posni.test/storage/hampers-images/b.jpg',
            MediaUrl::to('/hampers-images/b.jpg')
        );
    }
}
