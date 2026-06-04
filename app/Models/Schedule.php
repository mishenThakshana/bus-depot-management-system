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
     * Returns a message if the bus has a maintenance record on any of the given
     * dates, or null when every date is clear.
     */
    public function firstMaintenanceConflict(array $dates): ?string
    {
        $record = MaintenanceRecord::where('bus_id', $this->bus_id)
            ->whereIn('serviced_date', $dates)
            ->orderBy('serviced_date')
            ->first();

        if (! $record) {
            return null;
        }

        return "Bus {$this->bus?->registration_number} has a maintenance record on {$record->serviced_date->format('d M Y')} — remove it first or choose a different date range.";
    }

    /**
     * Find the first of the given dates on which this schedule's bus or driver
     * is already committed to an overlapping run on another active schedule.
     * Returns a human-readable clash message, or null when every date is free.
     *
     * Two runs overlap when each starts before the other one ends. Times are
     * normalised to 'HH:MM:00' so a window that merely touches another at a
     * boundary (e.g. 09:00 arrival vs 09:00 departure) is not treated as a clash.
     *
     * $excludeId skips a schedule (its own id on update / reschedule) so a
     * schedule is never reported as clashing with itself. Cancelled runs are
     * ignored — a cancelled slot is free for any schedule to take.
     */
    public function firstRunConflict(array $dates, ?int $excludeId = null): ?string
    {
        $departure = substr($this->departure_time, 0, 5) . ':00';
        $arrival   = substr($this->arrival_time, 0, 5) . ':00';
        $busId     = $this->bus_id;
        $driverId  = $this->driver_id;

        $run = ScheduleRun::query()
            ->scheduled()
            ->whereIn('run_date', $dates)
            ->whereHas('schedule', function ($q) use ($busId, $driverId, $departure, $arrival, $excludeId) {
                $q->where('is_active', true)
                    ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                    ->where(fn ($q) => $q->where('bus_id', $busId)->orWhere('driver_id', $driverId))
                    ->where('departure_time', '<', $arrival)
                    ->where('arrival_time', '>', $departure);
            })
            ->with('schedule.bus', 'schedule.driver')
            ->orderBy('run_date')
            ->first();

        if (! $run) {
            return null;
        }

        $other = $run->schedule;
        $date  = $run->run_date->format('d M Y');

        if ($other->bus_id === $busId) {
            return "Bus {$other->bus?->registration_number} is already allocated to an overlapping run on {$date}.";
        }

        return "Driver {$other->driver?->name} is already allocated to an overlapping run on {$date}.";
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
