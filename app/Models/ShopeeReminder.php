<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopeeReminder extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function webhookRequest()
    {
        return $this->belongsTo(WebhookRequest::class);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? false, function($query, $search) {
            return $query->where(function($query) use ($search) {
                $query->where('ordersn', 'like', '%' . $search . '%')
                ->orWhere('customer_name', 'like', '%' . $search . '%');
             });
         });
    }
}
