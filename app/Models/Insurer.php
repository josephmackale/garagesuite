<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insurer extends Model
{
    protected $fillable = [
        'garage_id',
        'name',
        'is_active',
        'phone',
        'email',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function jobInsuranceDetails(): HasMany
    {
        return $this->hasMany(JobInsuranceDetail::class);
    }
}
