<?php

namespace App\Chen\Modules\Finance\Models;

use App\Chen\Models\User;
use Illuminate\Database\Eloquent\Model;

class FinanceSetting extends Model
{
    protected $table = 'fin_settings';
    protected $guarded = ['id'];
    protected $casts = [
        'monthly_spending_target' => 'decimal:2',
        'monthly_savings_target' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'chen_user_id');
    }
}
