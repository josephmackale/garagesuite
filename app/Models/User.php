<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',

        // multi-tenant + roles
        'garage_id',
        'role',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // keep this cast even if you won't use email verification (harmless)
            'email_verified_at' => 'datetime',

            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Relationships
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Helpers
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
  // app/Models/User.php
    public function isSuspended(): bool
    {
        return ($this->status ?? 'active') === 'suspended' || !is_null($this->suspended_at);
    }

}
