<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\JobApproval;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'vehicle_id',
        'customer_id',
        'created_by',

        'job_number',
        'job_date',
        'service_type',
        'mileage',

        'complaint',
        'diagnosis',
        'notes',
        'work_done',

        'status',

        'labour_cost',
        'parts_cost',
        'estimated_cost',
        'final_cost',

        'payer_type',
        'organization_id',
    ];

    protected $casts = [
        'job_date'    => 'date',
        'labour_cost' => 'decimal:2',
        'parts_cost'  => 'decimal:2',
        'final_cost'  => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workItems()
    {
        return $this->hasMany(JobWorkItem::class);
    }

    public function partItems()
    {
        return $this->hasMany(JobPartItem::class);
    }

    /**
     * ✅ ONE invoice per job (what your blades/controllers expect: $job->invoice)
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'job_id')
            ->where('garage_id', $this->garage_id);
    }

    /**
     * Optional: keep if you want history/multiple invoices later.
     * If you don't need it, you can delete this method.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'job_id');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function jobCardPdf()
    {
        return $this->documents()
            ->where('document_type', 'job_card_pdf')
            ->latest('version');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForCurrentGarage($query)
    {
        $garageId = auth()->user()->garage_id ?? null;

        if ($garageId) {
            $query->where('garage_id', $garageId);
        }

        return $query;
    }

    // app/Models/Job.php

    public function mediaAttachments()
    {
        return $this->morphMany(\App\Models\MediaAttachment::class, 'attachable')
            ->where('garage_id', $this->garage_id);
    }

    public function mediaItems()
    {
        // Prefer using mediaAttachments->mediaItem for display.
        // This relation is still useful for querying, but we keep it constrained.
        return $this->belongsToMany(
                \App\Models\MediaItem::class,
                'media_attachments',
                'attachable_id',
                'media_item_id'
            )
            ->wherePivot('attachable_type', self::class)
            ->wherePivot('garage_id', $this->garage_id);
    }


    public function approval(): HasOne
    {
        return $this->hasOne(JobApproval::class);
    }

    public function jobRepairs()
    {
        return $this->hasMany(\App\Models\JobRepair::class, 'job_id');
    }

    public function insuranceDetail()
    {
        return $this->hasOne(JobInsuranceDetail::class, 'job_id');
    }

    public function insuranceClaim()
    {
        return $this->hasOne(\App\Models\InsuranceClaim::class, 'job_id');
    }
}
