<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'garage_id',
        'documentable_type',
        'documentable_id',
        'document_type',
        'name',
        'disk',
        'path',
        'file_name',
        'mime_type',
        'file_size',
        'version',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    public function getUrlAttribute(): string
    {
        // e.g. returns a full URL like https://app.com/storage/...
        return \Storage::disk($this->disk)->url($this->path);
    }

    public function isInvoicePdf(): bool
    {
        return $this->document_type === 'invoice_pdf';
    }

    public function isJobCardPdf(): bool
    {
        return $this->document_type === 'job_card_pdf';
    }

    public function isReceiptPdf(): bool
    {
        return $this->document_type === 'receipt_pdf';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForGarage($query, int $garageId)
    {
        return $query->where('garage_id', $garageId);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    // ✅ ADDED: safe polymorphic filters (replaces any old customer_id/vehicle_id queries)
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('documentable_type', Customer::class)
                     ->where('documentable_id', $customerId);
    }

    public function scopeForVehicle($query, int $vehicleId)
    {
        return $query->where('documentable_type', Vehicle::class)
                     ->where('documentable_id', $vehicleId);
    }
}
