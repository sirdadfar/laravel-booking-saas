<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\BookingStatus;
use App\Scopes\TenantScope;
use Carbon\CarbonImmutable;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'service_id',
        'staff_id',
        'customer_id',
        'code',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'price',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => BookingStatus::class,
            'price' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if (blank($booking->code)) {
                $booking->code = self::generateCode((int) $booking->tenant_id);
            }
        });
    }

    public static function generateCode(int $tenantId): string
    {
        do {
            $code = 'NB-'.Str::upper(Str::random(6));
        } while (self::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->exists());

        return $code;
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::blockingValues());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatus::Cancelled;
    }

    public function canTransitionTo(BookingStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }

    public function startsAtIn(string $timezone): CarbonImmutable
    {
        return CarbonImmutable::instance($this->starts_at)->setTimezone($timezone);
    }

    public function endsAtIn(string $timezone): CarbonImmutable
    {
        return CarbonImmutable::instance($this->ends_at)->setTimezone($timezone);
    }

    /**
     * Buffer is NOT part of ends_at: ends_at is the actual service end. The
     * buffer only expands the occupied interval inside the availability engine.
     */
    public function durationMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }
}
