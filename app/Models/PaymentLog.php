<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $table = 'payment_logs';

    protected $fillable = [
        'provider',
        'direction', // request|response|callback
        'event',     // stk_push|stk_result|...
        'invoice_id',
        'payment_id',
        'phone',
        'amount',
        'merchant_request_id',
        'checkout_request_id',
        'result_code',
        'result_desc',
        'payload',   // JSON
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'payload' => 'array',
    ];
}
