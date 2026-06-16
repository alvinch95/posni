<?php

namespace App\Chen\Modules\Finance\Models;

use App\Chen\Models\User;
use Database\Factories\Chen\Finance\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'fin_transactions';
    protected $guarded = ['id'];
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function newFactory()
    {
        return TransactionFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'chen_user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'fin_category_id');
    }
}
