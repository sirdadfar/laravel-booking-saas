<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'timezone',
        'phone',
        'address',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function timezone(): string
    {
        $timezone = $this->timezone ?: 'Asia/Tehran';

        // A bad value in the database must not take a tenant's page down with
        // an InvalidTimeZoneException; fall back to the documented default.
        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : 'Asia/Tehran';
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function slotGranularityMinutes(): int
    {
        return max(5, (int) $this->setting('slot_granularity_minutes', 30));
    }

    public function bookingHorizonDays(): int
    {
        return max(1, (int) $this->setting('booking_horizon_days', 30));
    }

    public function cancellationWindowHours(): int
    {
        return max(0, (int) $this->setting('cancellation_window_hours', 6));
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function path(): string
    {
        return '/'.$this->slug;
    }

    public function publicUrl(): string
    {
        return url($this->path());
    }
}
