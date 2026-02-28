<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'customer_id',
        'registration_number',
        'make',
        'model',
        'year',
        'color',
        'vin',
        'engine',
        'mileage',
        'notes',
    ];

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Convenience scope: vehicles of current garage only
    public function scopeForCurrentGarage($query)
    {
        return $query->where('garage_id', auth()->user()->garage_id);
    }

    // ✅ ADDED: polymorphic documents
    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // app/Models/Vehicle.php
    public function mediaAttachments()
    {
        return $this->morphMany(\App\Models\MediaAttachment::class, 'attachable');
    }
    public function mediaItems()
    {
        return $this->belongsToMany(\App\Models\MediaItem::class, 'media_attachments', 'attachable_id', 'media_item_id')
            ->wherePivot('attachable_type', self::class);
    }

}
