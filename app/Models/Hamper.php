<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isNull;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hamper extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = ['id'];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? false, function($query, $search) {
            return $query->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                  ->orWhereHas('serie', function ($query) use ($search) {
                      $query->where('name', 'like', '%' . $search . '%');
                  });
             });
         });
    }

    //relation foreign Key
    public function serie()
    {
        return $this->belongsTo(Serie::class);
    }

    public function details()
    {
        return $this->hasMany(HamperDetail::class);
    }

    //overwrite delete to implement soft delete
    public function delete()
    {
        // Set the deleted_at column value
        $this->fill(['deleted_at' => now()])->save();

        // Return null to prevent the default delete behavior
        return null;
    }

    //custom function
    public function getStock()
    {
        $hamperDetails = $this->details;
        $lowestStock = null;
        foreach($hamperDetails as $detail){
            $item = $detail->item()->first();
            if($item->uom != 'pcs' && !is_null($item->deleted_at)){
                continue;
            }
            $itemStock = $item ? $item->stock : 0;

            //kurangi stock dari shopping cart
            $shopping_cart = ShoppingCart::join('hampers', 'shopping_carts.hamper_id', '=', 'hampers.id')
            ->join('hamper_details', 'hamper_details.hamper_id', '=', 'hampers.id')
            ->join('items','items.id','=','hamper_details.item_id')
            ->select('shopping_carts.*','hamper_details.qty as hampers_qty')
            ->where('items.id', $detail->item_id)
            ->whereNull('hamper_details.deleted_at')
            ->whereNull('hampers.deleted_at')
            ->whereNull('items.deleted_at')
            ->get();
            foreach($shopping_cart as $cart){
                $itemStock = $itemStock - ($cart->hampers_qty*$cart->qty);
            }

            $available = $itemStock > 0 ? intdiv($itemStock, abs($detail->qty)) : 0;
            if(is_null($lowestStock) || $available < $lowestStock){
                $lowestStock = $available;
            }
        }
        return is_null($lowestStock) ? 0 : $lowestStock;
    }

}
