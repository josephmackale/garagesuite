<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApproval extends Model
{
    protected $fillable = [
        'garage_id',
        'job_id',
        'quotation_id',
        'status',
        'approved_by',
        'approval_ref',
        'approval_notes',
        'rejection_reason',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'created_by',
        'actioned_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
