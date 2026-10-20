<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Database\Factories\TimeOffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOff extends Model
{
    /** @use HasFactory<TimeOffFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'time_offs';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'starts_at',
        'ends_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function localRange(string $timezone): string
    {
        $start = CarbonImmutable::instance($this->starts_at)->setTimezone($timezone);
        $end = CarbonImmutable::instance($this->ends_at)->setTimezone($timezone);

        return $start->format('Y-m-d H:i').' تا '.$end->format('Y-m-d H:i');
    }
}
