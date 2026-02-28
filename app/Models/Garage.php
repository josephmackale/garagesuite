<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Garage extends Model
{
    use HasFactory;

    /**
     * Mass assignable fields.
     */
    protected $fillable = [
        'invoice_sequence',
        'name',
        'logo_path',
        'garage_code',
        'phone',
        'email',
        'payment_details',
        'address',
        'city',
        'country',
        'status',
        'subscription_expires_at',
        'trial_ends_at',
        'sms_driver',
        'sms_config',
        'use_global_sms',
        'payment_methods',

        // ✅ NEW (Insurance + future configs)
        'garage_config',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'subscription_expires_at' => 'datetime',
        'trial_ends_at'           => 'datetime',
        'sms_config'              => 'array',
        'payment_methods'         => 'array',

        // ✅ NEW
        'garage_config'           => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'garage_organizations'
        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get full garage config or specific key.
     *
     * Example:
     *   $garage->config()
     *   $garage->config('type')
     *   $garage->config('insurance.require_claim')
     */
    public function config(?string $key = null, $default = null)
    {
        $config = $this->garage_config ?? [];

        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default);
    }

    /**
     * Check if this garage is an insurance partner.
     */
    public function isInsurance(): bool
    {
        return $this->config('type', 'standard') === 'insurance';
    }

    /**
     * Insurance: require claim number?
     */
    public function requiresClaimNumber(): bool
    {
        return (bool) $this->config('insurance.require_claim', false);
    }

    /**
     * Insurance: require assessor name?
     */
    public function requiresAssessor(): bool
    {
        return (bool) $this->config('insurance.require_assessor', false);
    }

    /**
     * Insurance: default payer (insurance|customer|mixed)
     */
    public function insuranceDefaultPayer(): string
    {
        return (string) $this->config('insurance.default_payer', 'insurance');
    }

    /**
     * Insurance: dashboard widgets enabled?
     */
    public function insuranceWidgetsEnabled(): bool
    {
        return (bool) $this->config('insurance.widgets', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Licensing Helpers (Bonus: makes code cleaner everywhere)
    |--------------------------------------------------------------------------
    */

    /**
     * Check if garage is currently on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is active.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->status === 'active'
            && $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }

    /**
     * Check if garage is allowed to operate.
     */
    public function isOperational(): bool
    {
        if ($this->status === 'suspended') {
            return false;
        }

        return $this->isOnTrial() || $this->hasActiveSubscription();
    }
}
