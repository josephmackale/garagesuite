<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobInsuranceDetail extends Model
{
    protected $table = 'job_insurance_details';

    protected $fillable = [
        'job_id',
        'garage_id', 
        // Directory link
        'insurer_id',

        // Snapshot / display
        'insurer_name',

        // Claim identifiers
        'policy_number',
        'claim_number',
        'lpo_number',
        // Financial
        'excess_amount',

        // Adjuster / contact
        'adjuster_name',
        'adjuster_phone',

        // Notes
        'notes',
    ];

    protected $casts = [
        'excess_amount' => 'float',
    ];


    /**
     * Parent job
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Linked insurer directory record
     */
    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class);
    }
}
