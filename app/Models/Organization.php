<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'type',
        'contact_person',
        'phone',
        'email',
        'billing_terms',
        'status',
    ];

    public function garages(): BelongsToMany
    {
        return $this->belongsToMany(
            Garage::class,
            'garage_organizations'
        )->withTimestamps();
    }


    /* Helpers */

    public function isInsurance(): bool
    {
        return $this->type === 'insurance';
    }

    public function isCorporate(): bool
    {
        return $this->type === 'corporate';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
