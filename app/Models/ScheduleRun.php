<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleRun extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'schedule_id',
        'run_date',
        'status',
    ];

    protected $casts = [
        'run_date' => 'date',
    ];

    protected $attributes = [
        'status' => self::STATUS_SCHEDULED,
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Only live runs — cancelled ones are kept for history but never block a
     * slot, so clash checks scope to this.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * A run is "past" once its departure moment has elapsed. Past runs are
     * locked: they have already departed, so they can be neither cancelled,
     * moved, nor reactivated. This is time-aware — today's run stays actionable
     * only until its scheduled departure time, after which it is past too.
     *
     * Falls back to a date-only comparison if the schedule's departure time is
     * unavailable.
     */
    public function isPast(): bool
    {
        $departure = $this->schedule?->departure_time;

        if (! $departure) {
            return $this->run_date->lt(now()->startOfDay());
        }

        return $this->departsAt()->isPast();
    }

    /**
     * The concrete departure moment: this run's date at the schedule's
     * departure time.
     */
    public function departsAt(): \Illuminate\Support\Carbon
    {
        return $this->run_date->copy()
            ->setTimeFromTimeString(substr((string) $this->schedule->departure_time, 0, 8));
    }
}
