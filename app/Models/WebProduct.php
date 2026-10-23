<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebProduct extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(WebProductOption::class, 'web_product_id')->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(WebProductImage::class, 'web_product_id')->orderBy('sort_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(WebCategory::class, 'web_category_product', 'web_product_id', 'web_category_id');
    }
}
