<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    public function locations(): HasMany
    {
        return $this->hasMany(BusLocation::class);
    }

    /**
     * Only live runs — cancelled ones are kept for history but never block a
     * slot, so clash checks scope to this.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Runs that are "live" at this exact moment: scheduled (not cancelled),
     * dated today, belonging to an active schedule whose departure time has
     * already passed and whose arrival time is still ahead. This is what live
     * GPS tracking treats as an active run — a bus broadcasts only while it is
     * within this window.
     */
    public function scopeActiveNow(Builder $query): Builder
    {
        $now = Carbon::now();
        $time = $now->format('H:i:s');

        return $query->scheduled()
            ->whereDate('run_date', $now->toDateString())
            ->whereHas('schedule', function (Builder $q) use ($time) {
                $q->where('is_active', true)
                    ->where('departure_time', '<=', $time)
                    ->where('arrival_time', '>=', $time);
            });
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
    public function departsAt(): Carbon
    {
        return $this->run_date->copy()
            ->setTimeFromTimeString(substr((string) $this->schedule->departure_time, 0, 8));
    }
}
