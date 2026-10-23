<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebProductImage extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(WebProduct::class, 'web_product_id');
    }
}
