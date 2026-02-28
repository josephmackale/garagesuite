<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'actor_id','action','target_type','target_id','meta','ip','user_agent'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target()
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }
}
