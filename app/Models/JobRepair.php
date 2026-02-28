<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobRepair extends Model
{
    protected $table = 'job_repairs';

    protected $fillable = [
        'garage_id',
        'job_id',
        'approval_pack_id',
        'status',
        'started_by',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function approvalPack(): BelongsTo
    {
        return $this->belongsTo(ApprovalPack::class);
    }

    // ✅ THIS is your execution items table
    public function items(): HasMany
    {
        return $this->hasMany(JobRepairItem::class, 'job_repair_id');
    }
}
