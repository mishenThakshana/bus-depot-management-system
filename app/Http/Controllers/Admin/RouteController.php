<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BusRoute;
use App\Models\ScheduleRun;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status'); // active | inactive

        $routes = BusRoute::query()
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('origin', 'like', $term)
                    ->orWhere('destination', 'like', $term));
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('panel.routes', compact('routes', 'search', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        BusRoute::create([
            'name'            => $validated['name'],
            'origin'          => $validated['origin'],
            'origin_lat'      => $validated['origin_lat'] ?? null,
            'origin_lng'      => $validated['origin_lng'] ?? null,
            'destination'     => $validated['destination'],
            'destination_lat' => $validated['destination_lat'] ?? null,
            'destination_lng' => $validated['destination_lng'] ?? null,
            'stops'           => $this->normalizeStops($validated['stops'] ?? []),
            'distance_km'     => $validated['distance_km'],
        ]);

        ActivityLog::record('routes', 'created', "Route \"{$validated['name']}\" created");

        return redirect()->route('panel.routes')->with('success', "Route \"{$validated['name']}\" has been created.");
    }

    public function update(Request $request, BusRoute $route): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $newIsActive = $request->boolean('is_active');

        if ($route->is_active && ! $newIsActive && $this->hasFutureRuns($route)) {
            return redirect()->route('panel.routes')
                ->with('error', "Route \"{$route->name}\" cannot be deactivated while it has upcoming runs scheduled.");
        }

        $route->update([
            'name'            => $validated['name'],
            'origin'          => $validated['origin'],
            'origin_lat'      => $validated['origin_lat'] ?? null,
            'origin_lng'      => $validated['origin_lng'] ?? null,
            'destination'     => $validated['destination'],
            'destination_lat' => $validated['destination_lat'] ?? null,
            'destination_lng' => $validated['destination_lng'] ?? null,
            'stops'           => $this->normalizeStops($validated['stops'] ?? []),
            'distance_km'     => $validated['distance_km'],
            'is_active'       => $newIsActive,
        ]);

        ActivityLog::record('routes', 'updated', "Route \"{$route->name}\" updated");

        return redirect()->route('panel.routes')->with('success', "Route \"{$route->name}\" has been updated.");
    }

    private function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'origin'          => ['required', 'string', 'max:255'],
            'origin_lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'origin_lng'      => ['nullable', 'numeric', 'between:-180,180'],
            'destination'     => ['required', 'string', 'max:255'],
            'destination_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'stops'           => ['nullable', 'array'],
            'stops.*.name'    => ['nullable', 'string', 'max:255'],
            'stops.*.lat'     => ['nullable', 'numeric', 'between:-90,90'],
            'stops.*.lng'     => ['nullable', 'numeric', 'between:-180,180'],
            'distance_km'     => ['required', 'numeric', 'min:0.1', 'max:99999'],
        ];
    }

    private function normalizeStops(array $stops): ?array
    {
        $clean = [];

        foreach ($stops as $stop) {
            $name = trim($stop['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $clean[] = [
                'name' => $name,
                'lat'  => isset($stop['lat']) && $stop['lat'] !== '' ? (float) $stop['lat'] : null,
                'lng'  => isset($stop['lng']) && $stop['lng'] !== '' ? (float) $stop['lng'] : null,
            ];
        }

        return $clean ?: null;
    }

    public function toggleActive(BusRoute $route): RedirectResponse
    {
        $newIsActive = ! $route->is_active;

        if (! $newIsActive && $this->hasFutureRuns($route)) {
            return redirect()->route('panel.routes')
                ->with('error', "Route \"{$route->name}\" cannot be deactivated while it has upcoming runs scheduled. Cancel or reschedule them first.");
        }

        $route->update(['is_active' => $newIsActive]);

        $label = $newIsActive ? 'activated' : 'deactivated';

        ActivityLog::record('routes', $label, "Route \"{$route->name}\" {$label}");

        return redirect()->route('panel.routes')
            ->with('success', "Route \"{$route->name}\" has been {$label}.");
    }

    /**
     * Whether the route has any upcoming (future-dated, or today-but-not-yet-
     * departed) scheduled runs on an active schedule. Such routes must stay
     * active so the trips they back remain valid.
     */
    private function hasFutureRuns(BusRoute $route): bool
    {
        $today   = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');

        return ScheduleRun::query()
            ->scheduled()
            ->join('schedules', 'schedules.id', '=', 'schedule_runs.schedule_id')
            ->where('schedules.bus_route_id', $route->id)
            ->where('schedules.is_active', true)
            ->where(function ($q) use ($today, $nowTime) {
                $q->whereDate('schedule_runs.run_date', '>', $today)
                    ->orWhere(function ($q2) use ($today, $nowTime) {
                        $q2->whereDate('schedule_runs.run_date', '=', $today)
                            ->whereTime('schedules.departure_time', '>', $nowTime);
                    });
            })
            ->exists();
    }
}
