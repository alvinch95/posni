<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HamperDetail extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = ['id'];

    public function hamper()
    {
        return $this->belongsTo(Hamper::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
    
    public function delete()
    {
        // Set the deleted_at column value
        $this->fill(['deleted_at' => now()])->save();

        // Return null to prevent the default delete behavior
        return null;
    }
}
