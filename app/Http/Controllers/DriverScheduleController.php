<?php

namespace App\Http\Controllers;

use App\Models\ScheduleRun;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only schedule portal for the driver role. Shows only the runs belonging
 * to the signed-in driver, in calendar and list views, with no edit actions.
 */
class DriverScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $driver = $request->user()->driver;

        abort_unless($driver, 403, 'No driver record is linked to your account.');

        // Whether this driver has a run live right now — drives the passive GPS
        // sharing indicator and decides if the browser starts tracking.
        $hasActiveRun = ScheduleRun::activeNow()
            ->whereHas('schedule', fn (Builder $q) => $q->where('driver_id', $driver->id))
            ->exists();

        $filters = [
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'time_from' => $request->query('time_from'),
            'time_to' => $request->query('time_to'),
        ];

        $view = $request->query('view') === 'list' ? 'list' : 'calendar';

        // ── Calendar view: one month at a time, grouped by date then timeslot ──
        $month = $this->resolveMonth($request->query('month'), $filters['date_from']);
        $mStart = $month->copy()->startOfMonth();
        $mEnd = $month->copy()->endOfMonth();

        $byDate = $this->filteredRuns($driver->id, $filters)
            ->whereBetween('schedule_runs.run_date', [$mStart->toDateString(), $mEnd->toDateString()])
            ->get()
            ->groupBy(fn (ScheduleRun $run) => $run->run_date->toDateString())
            ->map(fn ($dayRuns) => $dayRuns
                ->map(fn (ScheduleRun $run) => $this->presentRun($run))
                ->sortBy([['departure', 'asc'], ['arrival', 'asc']])
                ->values())
            ->sortKeys();

        // ── List view: paginated runs, ordered by date then departure time ──
        $runs = $this->filteredRuns($driver->id, $filters)
            ->orderBy('schedule_runs.run_date')
            ->orderBy('schedules.departure_time')
            ->orderBy('schedules.arrival_time')
            ->paginate(20)
            ->withQueryString();

        return view('panel.driver-schedule', [
            'driver' => $driver,
            'hasActiveRun' => $hasActiveRun,
            'view' => $view,
            'filters' => $filters,
            'hasFilters' => collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty(),
            'runs' => $runs,
            'byDate' => $byDate,
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'today' => Carbon::today(),
        ]);
    }

    private function filteredRuns(int $driverId, array $f): Builder
    {
        $query = ScheduleRun::query()
            ->select('schedule_runs.*')
            ->join('schedules', 'schedules.id', '=', 'schedule_runs.schedule_id')
            ->where('schedules.driver_id', $driverId)
            ->with(['schedule.route', 'schedule.bus']);

        if (! empty($f['date_from'])) {
            $query->whereDate('schedule_runs.run_date', '>=', $f['date_from']);
        }
        if (! empty($f['date_to'])) {
            $query->whereDate('schedule_runs.run_date', '<=', $f['date_to']);
        }
        if (! empty($f['time_from'])) {
            $query->whereTime('schedules.departure_time', '>=', $f['time_from']);
        }
        if (! empty($f['time_to'])) {
            $query->whereTime('schedules.departure_time', '<=', $f['time_to']);
        }

        return $query;
    }

    private function presentRun(ScheduleRun $run): array
    {
        $schedule = $run->schedule;

        return [
            'departure' => substr((string) $schedule?->departure_time, 0, 5),
            'arrival' => substr((string) $schedule?->arrival_time, 0, 5),
            'route' => $schedule?->route?->name ?? '—',
            'bus' => $schedule?->bus?->registration_number ?? '—',
            'cancelled' => $run->isCancelled(),
            'past' => $run->isPast(),
        ];
    }

    private function resolveMonth(?string $month, ?string $fallbackDate = null): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
            } catch (\Throwable) {
                // fall through
            }
        }

        if ($fallbackDate) {
            try {
                return Carbon::parse($fallbackDate)->startOfMonth();
            } catch (\Throwable) {
                // fall through
            }
        }

        return Carbon::today()->startOfMonth();
    }
}
