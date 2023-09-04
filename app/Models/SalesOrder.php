<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = ['id'];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? false, function($query, $search) {
            return $query->where(function($query) use ($search) {
                $query->where('order_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function ($query) use ($search) {
                      $query->where('name', 'like', '%' . $search . '%');
                  });
             });
         });
    }

    //relation foreign Key
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(SalesOrderDetail::class);
    }

    //overwrite delete to implement soft delete
    public function delete()
    {
        // Set the deleted_at column value
        $this->fill(['deleted_at' => now()])->save();

        // Return null to prevent the default delete behavior
        return null;
    }
}
