<?php

namespace App\Chen\Modules\Finance\Models;

use App\Chen\Models\User;
use Database\Factories\Chen\Finance\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fin_categories';
    protected $guarded = ['id'];

    protected static function newFactory()
    {
        return CategoryFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'chen_user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'fin_category_id');
    }
}
