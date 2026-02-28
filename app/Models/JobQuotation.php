<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobQuotation extends Model
{
    protected $fillable = [
        'garage_id','job_id','status','version',
        'subtotal','tax','discount','total',
        'submitted_at','submitted_by','approved_at','approved_by',
    ];

    public function lines()
    {
        return $this->hasMany(JobQuotationLine::class, 'quotation_id')->orderBy('sort_order');
    }
}
