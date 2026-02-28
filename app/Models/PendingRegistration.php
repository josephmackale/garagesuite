<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'garage_name',
        'phone',
        'phone_verified_at',
        'otp_code_hash',
        'otp_expires_at',
        'otp_attempts',
        'otp_last_sent_at',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'otp_last_sent_at' => 'datetime',
    ];

    public function isVerified(): bool
    {
        return (bool) $this->phone_verified_at;
    }
}
