<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'name',
        'category',
        'brand',
        'part_number',
        'unit',
        'cost_price',
        'selling_price',
        'current_stock',
        'reorder_level',
        'status',
    ];

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }
    public function movements()
    {
        return $this->hasMany(\App\Models\InventoryItemMovement::class);
    }

}
