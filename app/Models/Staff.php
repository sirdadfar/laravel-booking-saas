<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'staff_service')->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function timeOffs(): HasMany
    {
        return $this->hasMany(TimeOff::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function canPerform(Service $service): bool
    {
        return $service->tenant_id === $this->tenant_id
            && $this->services()->whereKey($service->getKey())->exists();
    }
}
