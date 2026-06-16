<?php

namespace App\Chen\Modules\Finance\Models;

use App\Chen\Models\User;
use Database\Factories\Chen\Finance\RecurringRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringRule extends Model
{
    use HasFactory;

    protected $table = 'fin_recurring_rules';
    protected $guarded = ['id'];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_date' => 'date',
        'active' => 'boolean',
        'amount' => 'decimal:2',
    ];

    protected static function newFactory()
    {
        return RecurringRuleFactory::new();
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
