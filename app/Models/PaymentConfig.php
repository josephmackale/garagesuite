<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentConfig extends Model
{
    protected $table = 'payment_configs';

    protected $fillable = [
        'provider',
        'is_active',
        'environment',     // sandbox|live
        'shortcode',
        'consumer_key',
        'consumer_secret',
        'passkey',
        'callback_url',
        'account_reference',
        'transaction_desc',
        // add more fields from your migration...
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        // Encrypt credentials at rest:
        'consumer_key'    => 'encrypted',
        'consumer_secret' => 'encrypted',
        'passkey'         => 'encrypted',
        // If you store JSON config bits:
        // 'meta'          => 'array',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
