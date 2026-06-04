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
     * A run is "past" once its date is before today. Past runs are locked:
     * they have already happened, so they can be neither cancelled, moved,
     * nor reactivated. Today's run still counts as actionable.
     */
    public function isPast(): bool
    {
        return $this->run_date->lt(now()->startOfDay());
    }
}
