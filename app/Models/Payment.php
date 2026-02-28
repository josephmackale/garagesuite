<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    /**
     * Mass assignable fields (match your actual DB columns).
     * NOTE: do NOT include 'method' unless you add it to the payments table.
     */
    protected $fillable = [
        'garage_id',
        'invoice_id',

        // Payment details
        'provider',
        'phone',
        'amount',
        'currency',
        'status', // pending|paid|failed|cancelled|partial

        // Gateway refs (optional columns if you have them)
        'merchant_request_id',
        'checkout_request_id',
        'mpesa_receipt',
        'result_code',
        'result_desc',

        // General reference + payload
        'reference',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
        'meta'    => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    protected static function booted()
    {
        /**
         * Ensure tenant (garage_id) is always set.
         */
        static::creating(function (Payment $payment) {

            // Best source: invoice->garage_id
            if (empty($payment->garage_id) && !empty($payment->invoice_id)) {
                $invoice = Invoice::select('id', 'garage_id')->find($payment->invoice_id);
                if ($invoice) {
                    $payment->garage_id = $invoice->garage_id;
                }
            }

            // Fallback: logged-in user (not available in tinker unless you fake auth)
            if (empty($payment->garage_id) && auth()->check()) {
                $payment->garage_id = auth()->user()->garage_id;
            }
        });

        /**
         * After a payment is created, sync the invoice totals/status.
         * This makes dashboard numbers correct from payments table.
         */
        static::created(function (Payment $payment) {

            $invoice = Invoice::find($payment->invoice_id);
            if (!$invoice) {
                return;
            }

            // Sum payments for THIS invoice (keep it deterministic)
            $totalPaid = static::where('invoice_id', $invoice->id)->sum('amount');

            $invoice->paid_amount = $totalPaid;

            // Decide payment_status
            $grandTotal = (float) ($invoice->total_amount ?? 0);
            $paidFloat  = (float) $totalPaid;

            if ($paidFloat <= 0) {
                $invoice->payment_status = 'unpaid';
                $invoice->paid_at = null;
            } elseif ($grandTotal > 0 && $paidFloat >= $grandTotal) {
                $invoice->payment_status = 'paid';
                $invoice->paid_at = $payment->paid_at ?? now();
            } else {
                $invoice->payment_status = 'partial';
                $invoice->paid_at = $payment->paid_at ?? now();
            }

            $invoice->save();
        });
    }
}
