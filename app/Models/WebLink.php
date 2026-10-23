<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebLink extends Model
{
    /** Allowed `group` values with their admin labels, in display order. */
    public const GROUPS = [
        'marketplace' => 'Marketplace',
        'social' => 'Media sosial',
    ];

    /** Allowed `platform` values with their admin labels. */
    public const PLATFORMS = [
        'shopee' => 'Shopee',
        'tokopedia' => 'Tokopedia',
        'toco' => 'ToCo',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'other' => 'Lainnya',
    ];

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
