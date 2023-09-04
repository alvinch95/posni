<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = ['id'];


    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? false, function($query, $search) {
            return $query->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
             });
         });
    }

    public function hamperDetails()
    {
        return $this->hasMany(HamperDetail::class);
    }

    public function stockHistories()
    {
        return $this->hasMany(StockHistory::class);
    }

    //to override route GET default parameter for show method, default is ID
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function delete()
    {
        // Set the deleted_at column value
        $this->fill(['deleted_at' => now()])->save();

        // Return null to prevent the default delete behavior
        return null;
    }
}
