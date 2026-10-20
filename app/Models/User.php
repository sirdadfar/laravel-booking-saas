<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * `is_platform_admin` is deliberately NOT fillable: it is a privilege
     * flag and must never be settable through request mass assignment.
     * Factories run unguarded; the seeder sets it explicitly via forceFill().
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function canAccessTenant(Tenant $tenant): bool
    {
        return $this->is_platform_admin || $this->tenant_id === $tenant->getKey();
    }
}
