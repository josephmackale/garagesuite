<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobInspectionItem extends Model
{
    protected $fillable = [
        'garage_id','inspection_id','item_no','label','state','notes'
    ];

    public function inspection()
    {
        return $this->belongsTo(JobInspection::class, 'inspection_id');
    }
}
