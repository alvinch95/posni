<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebProductOption extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(WebProduct::class, 'web_product_id');
    }

    /**
     * The posni hamper behind this option. Hamper uses SoftDeletes, so a soft-deleted
     * hamper resolves to null by default.
     */
    public function hamper(): BelongsTo
    {
        return $this->belongsTo(Hamper::class, 'hamper_id');
    }
}
