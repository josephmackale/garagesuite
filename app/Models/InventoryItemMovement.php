<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItemMovement extends Model
{
    protected $fillable = [
        'garage_id',
        'inventory_item_id',
        'type',
        'quantity',
        'reason',
        'job_id',
        'created_by',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

