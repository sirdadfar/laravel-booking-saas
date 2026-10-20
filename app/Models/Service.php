<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'duration_minutes',
        'buffer_minutes',
        'price',
        'is_active',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_service')->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function occupiesMinutes(): int
    {
        return $this->duration_minutes + $this->buffer_minutes;
    }

    public function isPerformedBy(Staff $staff): bool
    {
        return $staff->tenant_id === $this->tenant_id
            && $staff->services()->whereKey($this->getKey())->exists();
    }
}
