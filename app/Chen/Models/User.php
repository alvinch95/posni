<?php

namespace App\Chen\Models;

use Database\Factories\Chen\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'chen_users';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public function setting()
    {
        return $this->hasOne(Setting::class, 'user_id');
    }
}
