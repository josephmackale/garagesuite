<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceClaim extends Model
{
    protected $table = 'insurance_claims';

    protected $fillable = [
        'garage_id','job_id','claim_number','status',
        'approval_pack_id','invoice_id','notes',
        'submitted_at','submitted_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}