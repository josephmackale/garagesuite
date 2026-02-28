<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobQuotationLine extends Model
{
    protected $fillable = [
        'quotation_id','type','category','description',
        'qty','unit_price','amount','sort_order',
    ];

    public function quotation()
    {
        return $this->belongsTo(JobQuotation::class, 'quotation_id');
    }
}
