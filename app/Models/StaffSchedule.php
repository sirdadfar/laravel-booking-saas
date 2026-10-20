<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\StaffScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSchedule extends Model
{
    /** @use HasFactory<StaffScheduleFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * Iranian week. Carbon's dayOfWeek is converted with:
     * (carbonDayOfWeek + 1) % 7  =>  Saturday = 0, ..., Friday = 6.
     */
    public const WEEKDAYS = [
        0 => 'شنبه',
        1 => 'یکشنبه',
        2 => 'دوشنبه',
        3 => 'سه‌شنبه',
        4 => 'چهارشنبه',
        5 => 'پنجشنبه',
        6 => 'جمعه',
    ];

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'weekday',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public static function weekdayLabel(int $weekday): string
    {
        return self::WEEKDAYS[$weekday] ?? '—';
    }

    public function label(): string
    {
        return self::weekdayLabel($this->weekday).' '.$this->start_time.' تا '.$this->end_time;
    }

    public function startMinutes(): int
    {
        return self::toMinutes($this->start_time);
    }

    public function endMinutes(): int
    {
        return self::toMinutes($this->end_time);
    }

    private static function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
