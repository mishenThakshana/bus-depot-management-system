<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bus;
use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\Schedule;
use App\Models\ScheduleRun;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Unified schedules page. Both the calendar and list views render the same
     * filtered set of runs (every schedule's runs across the depot), grouped by
     * day then by timeslot. The Add/Edit schedule CRUD lives alongside the views.
     */
    public function index(Request $request): View
    {
        // ── CRUD form options (active records only — these are assignable) ──
        $routes  = BusRoute::where('is_active', true)->orderBy('name')->get();
        $buses   = Bus::where('is_in_service', true)->orderBy('registration_number')->get();
        $drivers = Driver::where('is_active', true)->orderBy('name')->get();
        $canAddSchedule = $routes->isNotEmpty() && $buses->isNotEmpty() && $drivers->isNotEmpty();

        // Filter dropdowns list everything so runs of now-inactive buses/drivers
        // stay filterable.
        $filterBuses   = Bus::orderBy('registration_number')->get(['id', 'registration_number']);
        $filterDrivers = Driver::orderBy('name')->get(['id', 'name']);

        // Edit prefill data for every schedule, keyed by id — the Edit modal is
        // opened from a run row, so we need each parent schedule on hand.
        $scheduleData = Schedule::orderBy('id')->get()->mapWithKeys(fn (Schedule $s) => [
            $s->id => [
                'id'             => $s->id,
                'bus_route_id'   => $s->bus_route_id,
                'bus_id'         => $s->bus_id,
                'driver_id'      => $s->driver_id,
                'departure_time' => substr((string) $s->departure_time, 0, 5),
                'arrival_time'   => substr((string) $s->arrival_time, 0, 5),
                'frequency'      => $s->frequency,
                'days_of_week'   => $s->days_of_week ?? [],
                'start_date'     => $s->start_date->format('Y-m-d'),
                'end_date'       => $s->end_date->format('Y-m-d'),
                'is_active'      => $s->is_active,
            ],
        ]);

        $filters = [
            'driver_id' => $request->query('driver_id'),
            'bus_id'    => $request->query('bus_id'),
            'date_from' => $request->query('date_from'),
            'date_to'   => $request->query('date_to'),
            'time_from' => $request->query('time_from'),
            'time_to'   => $request->query('time_to'),
        ];

        $view = $request->query('view') === 'list' ? 'list' : 'calendar';

        // ── Calendar view: one month at a time, grouped by date then timeslot ──
        $month  = $this->resolveMonth($request->query('month'), $filters['date_from']);
        $mStart = $month->copy()->startOfMonth();
        $mEnd   = $month->copy()->endOfMonth();

        $byDate = $this->filteredRuns($filters)
            ->whereBetween('schedule_runs.run_date', [$mStart->toDateString(), $mEnd->toDateString()])
            ->get()
            ->groupBy(fn (ScheduleRun $run) => $run->run_date->toDateString())
            ->map(fn ($dayRuns) => $dayRuns
                ->map(fn (ScheduleRun $run) => $this->presentRun($run))
                ->sortBy([['departure', 'asc'], ['arrival', 'asc']])
                ->values())
            ->sortKeys();

        // ── List view: paginated runs, ordered by date then departure time ──
        $runs = $this->filteredRuns($filters)
            ->orderBy('schedule_runs.run_date')
            ->orderBy('schedules.departure_time')
            ->orderBy('schedules.arrival_time')
            ->paginate(20)
            ->withQueryString();

        return view('panel.schedules', [
            'view'           => $view,
            'filters'        => $filters,
            'hasFilters'     => collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty(),
            'routes'         => $routes,
            'buses'          => $buses,
            'drivers'        => $drivers,
            'canAddSchedule' => $canAddSchedule,
            'filterBuses'    => $filterBuses,
            'filterDrivers'  => $filterDrivers,
            'scheduleData'   => $scheduleData,
            'runs'           => $runs,
            'byDate'         => $byDate,
            'month'          => $month,
            'prevMonth'      => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth'      => $month->copy()->addMonth()->format('Y-m'),
            'today'          => Carbon::today(),
        ]);
    }

    /**
     * Base runs query with the shared filters applied. The schedules table is
     * joined so we can both filter and order by its columns (departure time,
     * assigned bus/driver).
     */
    private function filteredRuns(array $f): Builder
    {
        $query = ScheduleRun::query()
            ->select('schedule_runs.*')
            ->join('schedules', 'schedules.id', '=', 'schedule_runs.schedule_id')
            ->with(['schedule.route', 'schedule.bus', 'schedule.driver']);

        if (! empty($f['driver_id'])) {
            $query->where('schedules.driver_id', $f['driver_id']);
        }
        if (! empty($f['bus_id'])) {
            $query->where('schedules.bus_id', $f['bus_id']);
        }
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
            'id'         => $run->id,
            'scheduleId' => $schedule?->id,
            'departure'  => substr((string) $schedule?->departure_time, 0, 5),
            'arrival'    => substr((string) $schedule?->arrival_time, 0, 5),
            'route'      => $schedule?->route?->name ?? '—',
            'bus'        => $schedule?->bus?->registration_number ?? '—',
            'driver'     => $schedule?->driver?->name ?? '—',
            'cancelled'  => $run->isCancelled(),
            'past'       => $run->isPast(),
            // Absolute departure instant so the browser can re-check "past"
            // against the live clock, not just page-load time.
            'departsAt'  => $schedule?->departure_time ? $run->departsAt()->toIso8601String() : null,
        ];
    }

    /**
     * Parse a YYYY-MM query value into a Carbon at the first of that month,
     * falling back to the month of the date-from filter, then the current month.
     */
    private function resolveMonth(?string $month, ?string $fallbackDate = null): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        $this->persist(new Schedule(), $validated);

        ActivityLog::record('schedules', 'created', 'New schedule created');

        return redirect()->route('panel.schedules')
            ->with('success', 'Schedule has been created.');
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));
        $validated['is_active'] = $request->boolean('is_active');

        $label = "Schedule #{$schedule->id} ({$schedule->route?->name})";
        $this->persist($schedule, $validated);

        ActivityLog::record('schedules', 'updated', "{$label} updated");

        return redirect()->route('panel.schedules')
            ->with('success', 'Schedule has been updated.');
    }

    private function persist(Schedule $schedule, array $validated): void
    {
        if (($validated['frequency'] ?? null) !== 'weekly') {
            $validated['days_of_week'] = null;
        } else {
            $validated['days_of_week'] = array_values(array_map('intval', $validated['days_of_week']));
        }

        $schedule->fill($validated);

        $dates = $schedule->runDatesBetween();

        if (empty($dates)) {
            throw ValidationException::withMessages([
                'frequency' => 'This schedule produces no run dates for the chosen frequency and date range.',
            ]);
        }

        if ($clash = $schedule->firstRunConflict($dates, $schedule->exists ? $schedule->id : null)) {
            throw ValidationException::withMessages(['bus_id' => $clash]);
        }

        if ($maintClash = $schedule->firstMaintenanceConflict($dates)) {
            throw ValidationException::withMessages(['bus_id' => $maintClash]);
        }

        DB::transaction(function () use ($schedule, $dates) {
            $schedule->save();
            $schedule->runs()->delete();
            $schedule->runs()->createMany(
                array_map(fn ($date) => ['run_date' => $date], $dates)
            );
        });
    }

    private function rules(Request $request): array
    {
        $isWeekly = $request->input('frequency') === 'weekly';

        return [
            'bus_route_id'   => ['required', Rule::exists('bus_routes', 'id')->where('is_active', true)],
            'bus_id'         => ['required', Rule::exists('buses', 'id')->where('is_in_service', true)],
            'driver_id'      => ['required', Rule::exists('drivers', 'id')->where('is_active', true)],
            'departure_time' => ['required', 'date_format:H:i'],
            'arrival_time'   => ['required', 'date_format:H:i', 'after:departure_time'],
            'frequency'      => ['required', Rule::in(Schedule::$frequencies)],
            'days_of_week'   => [Rule::requiredIf($isWeekly), 'array'],
            'days_of_week.*' => ['integer', Rule::in(array_keys(Schedule::$weekdays))],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
