<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebFaq extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
