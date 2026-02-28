<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobWorkItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'description',
        'hours',
        'amount',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
