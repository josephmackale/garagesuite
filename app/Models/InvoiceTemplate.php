<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceTemplate extends Model
{
    protected $fillable = [
        'garage_id',
        'key',
        'name',
        'is_active',
        'is_default',
        'body_html',
        'css',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }
}
