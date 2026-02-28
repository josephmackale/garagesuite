<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalPackItem extends Model
{
    protected $table = 'approval_pack_items';

    protected $guarded = [];

    public function pack()
    {
        return $this->belongsTo(ApprovalPack::class, 'approval_pack_id');
    }
}
