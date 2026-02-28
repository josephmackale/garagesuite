<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'vehicle_reg',
    ];

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // ✅ ADDED: polymorphic documents
    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
