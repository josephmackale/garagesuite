<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    use HasFactory;

    /**
     * Mass assignable fields.
     * Keep this list aligned with your invoices table columns.
     */
    protected $fillable = [
        'garage_id',
        'job_id',
        'customer_id',
        'vehicle_id',

        'invoice_number',
        'status',
        'payment_status',

        // Dates
        'issue_date',
        'due_date',
        'paid_at',

        // Money (DB truth)
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'paid_amount',

        // Optional metadata fields (safe if columns exist)
        'notes',
        'currency',
    ];

    /**
     * Correct casting
     */
    protected $casts = [
        'issue_date' => 'date',
        'due_date'   => 'date',
        'paid_at'    => 'datetime',

        'subtotal'     => 'decimal:2',
        'tax_rate'     => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
    ];

    /**
     * Hard safety net: total_amount MUST always equal subtotal + tax_amount.
     * This prevents drift if any controller forgets to set total_amount.
     */
    protected static function booted()
    {
        static::saving(function (self $invoice) {
            $subtotal = (float) ($invoice->subtotal ?? 0);
            $tax      = (float) ($invoice->tax_amount ?? 0);
            $invoice->total_amount = round($subtotal + $tax, 2);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function invoicePdf()
    {
        return $this->documents()
            ->where('document_type', 'invoice_pdf')
            ->latest('version');
    }

    public function receiptPdf()
    {
        return $this->documents()
            ->where('document_type', 'receipt_pdf')
            ->latest('version');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Payment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Totals (Permanent Truth)
    |--------------------------------------------------------------------------
    */

    /**
     * Recalculate totals from invoice_items and save.
     * Canonical source of truth: invoice_items.line_total.
     */
    public function recalcTotals(bool $save = true): array
    {
        $subtotal = (float) $this->items()->sum('line_total');

        $taxRate = (float) ($this->tax_rate ?? 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);

        // total_amount will also be enforced by the saving() hook
        $total = round($subtotal + $taxAmount, 2);

        $this->subtotal = round($subtotal, 2);
        $this->tax_amount = $taxAmount;
        $this->total_amount = $total;

        if ($save) {
            $this->save();
        }

        return [
            'subtotal' => $this->subtotal,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getBalanceAttribute(): float
    {
        $total = (float) ($this->total_amount ?? 0);
        $paid  = (float) ($this->paid_amount ?? 0);
        return max(0, $total - $paid);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->balance <= 0 && (float)($this->total_amount ?? 0) > 0;
    }

    public function shouldShowPaymentsUi(): bool
    {
        $status = $this->status ?? 'draft';
        if (in_array($status, ['draft', 'cancelled'], true)) return false;

        $payerType = $this->job->payer_type ?? null;

        // Insurance: only show payments at payment stage
        if ($payerType === 'insurance') {
            return in_array($status, ['partial', 'paid'], true);
            // If you want it visible earlier:
            // return in_array($status, ['sent','issued','partial','paid'], true);
        }

        return true;
    }
}