<?php

namespace App\Chen\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'chen_settings';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
