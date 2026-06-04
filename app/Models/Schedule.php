<?php

namespace App\Models;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = [
        'bus_route_id',
        'bus_id',
        'driver_id',
        'departure_time',
        'arrival_time',
        'frequency',
        'days_of_week',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_date'   => 'date',
        'end_date'     => 'date',
        'is_active'    => 'boolean',
    ];

    public static array $frequencies = [
        'daily',
        'weekly',
    ];

    /**
     * Weekday options for weekly schedules, keyed by Carbon dayOfWeek
     * (0 = Sunday … 6 = Saturday) and ordered Monday-first for display.
     */
    public static array $weekdays = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class, 'bus_route_id');
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ScheduleRun::class);
    }

    /**
     * Expand this schedule's frequency over its date range into the concrete
     * dates it actually runs. Returns an array of 'Y-m-d' strings.
     *
     *   daily   — every day in the range.
     *   weekly  — every day in the range whose weekday is one of the selected
     *             days_of_week (e.g. Monday + Thursday). If no days are
     *             selected it falls back to the start date's own weekday.
     */
    public function runDatesBetween(): array
    {
        $start = $this->start_date->copy()->startOfDay();
        $end   = $this->end_date->copy()->startOfDay();

        if ($end->lt($start)) {
            return [];
        }

        return $this->frequency === 'weekly'
            ? $this->weeklyDates($start, $end)
            : $this->datesFromPeriod(CarbonPeriod::create($start, '1 day', $end));
    }

    private function datesFromPeriod(CarbonPeriod $period): array
    {
        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    private function weeklyDates(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        $days = array_map('intval', $this->days_of_week ?: [$start->dayOfWeek]);
        $dates = [];

        foreach (CarbonPeriod::create($start, '1 day', $end) as $date) {
            if (in_array($date->dayOfWeek, $days, true)) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }
}
