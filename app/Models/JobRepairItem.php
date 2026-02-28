<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobRepairItem extends Model
{
    protected $table = 'job_repair_items';

    protected $fillable = [
        'garage_id',
        'job_repair_id',
        'approval_pack_item_id',
        'line_type',
        'name',
        'description',
        'approved_qty',
        'approved_unit_price',
        'approved_line_total',
        'execution_status',
        'started_at',
        'completed_at',
        'actual_qty',
        'actual_cost',
        'remarks',
    ];

    protected $casts = [
        'approved_qty'        => 'decimal:2',
        'approved_unit_price' => 'decimal:2',
        'approved_line_total' => 'decimal:2',
        'actual_qty'          => 'decimal:2',
        'actual_cost'         => 'decimal:2',
        'started_at'          => 'datetime',
        'completed_at'        => 'datetime',
    ];

    public function repair(): BelongsTo
    {
        return $this->belongsTo(JobRepair::class, 'job_repair_id');
    }
}