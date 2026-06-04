<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $schedules = Schedule::with(['route', 'bus', 'driver'])
            ->withCount('runs')
            ->latest()->paginate(10)->withQueryString();

        $routes  = BusRoute::where('is_active', true)->orderBy('name')->get();
        $buses   = Bus::where('is_in_service', true)->orderBy('registration_number')->get();
        $drivers = Driver::where('is_active', true)->orderBy('name')->get();

        return view('panel.schedules', compact('schedules', 'routes', 'buses', 'drivers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        $this->persist(new Schedule(), $validated);

        return redirect()->route('panel.schedules')
            ->with('success', 'Schedule has been created.');
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));
        $validated['is_active'] = $request->boolean('is_active');

        $this->persist($schedule, $validated);

        return redirect()->route('panel.schedules')
            ->with('success', 'Schedule has been updated.');
    }

    /**
     * Save a schedule and (re)generate its concrete dated runs. The frequency
     * is expanded into real dates, those dates are checked for a bus/driver
     * clash with other live schedules, and only then is everything written in
     * a single transaction so a schedule and its runs never drift apart.
     */
    private function persist(Schedule $schedule, array $validated): void
    {
        // Weekday selection only makes sense for weekly schedules; daily ones
        // run every day, so never carry a stale list of days.
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

        DB::transaction(function () use ($schedule, $dates) {
            $schedule->save();
            $schedule->runs()->delete();
            $schedule->runs()->createMany(
                array_map(fn ($date) => ['run_date' => $date], $dates)
            );
        });
    }

    /**
     * Shared validation rules. A schedule can only use an active route, an
     * in-service bus, and an active driver; the arrival must be after the
     * departure, and the date range must end on or after it begins.
     */
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
