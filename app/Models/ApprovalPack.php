<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalPack extends Model
{
    protected $table = 'approval_packs';

    protected $fillable = [
        'garage_id',
        'job_id',
        'quotation_id',
        'status',
        'version',
        'total_amount',
        'currency',
        'generated_by',
        'generated_at',
        'submitted_at',
        'decision_at',
        'decision_notes',
    ];

    protected $casts = [
        'version'      => 'integer',
        'total_amount' => 'decimal:2',
        'generated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'decision_at'  => 'datetime',
    ];

    // Relationships (keep light — no assumptions)
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeForGarage($query, int $garageId)
    {
        return $query->where('garage_id', $garageId);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function items()
    {
        return $this->hasMany(\App\Models\ApprovalPackItem::class, 'approval_pack_id');
    }
}
