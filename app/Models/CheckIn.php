<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'check_ins';

    // Define which fields can be mass assigned
    protected $fillable = [
        'user_id',
        'action_type',
        'action_time',
        'latitude',
        'longitude',
        'photo_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
