<?php

namespace App\Http\Controllers;

use App\Events\BusLocationUpdated;
use App\Models\BusLocation;
use App\Models\ScheduleRun;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Live GPS tracking. Drivers post fixes from their browser while a run is in
 * progress; admins and supervisors watch those fixes move on a map in real time.
 */
class LocationController extends Controller
{
    /**
     * Ingest a single GPS fix from the signed-in driver's browser. Accepted only
     * while the driver actually has a run live right now — otherwise rejected
     * with 403 so the client knows to stop tracking.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $driver = $request->user()->driver;

        abort_unless($driver, 403, 'No driver record is linked to your account.');

        $run = ScheduleRun::activeNow()
            ->whereHas('schedule', fn (Builder $q) => $q->where('driver_id', $driver->id))
            ->with(['schedule.route', 'schedule.bus', 'schedule.driver'])
            ->first();

        abort_unless($run, 403, 'You have no active run right now.');

        $location = BusLocation::create([
            'schedule_run_id' => $run->id,
            'driver_id' => $driver->id,
            'bus_id' => $run->schedule->bus_id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'speed' => $validated['speed'] ?? null,
            'accuracy' => $validated['accuracy'] ?? null,
            'recorded_at' => Carbon::now(),
        ]);

        // Reuse the relations already loaded on the run so broadcasting the
        // event doesn't trigger a second round of queries.
        $location->setRelation('scheduleRun', $run);
        $location->setRelation('bus', $run->schedule->bus);
        $location->setRelation('driver', $run->schedule->driver);

        broadcast(new BusLocationUpdated($location));

        return response()->json(['status' => 'ok'], 201);
    }

    /**
     * The admin/supervisor live map: every currently active bus that has at
     * least one fix, with its most recent position and run details.
     */
    public function liveMap(): View
    {
        $runs = ScheduleRun::activeNow()
            ->with(['schedule.route', 'schedule.bus', 'schedule.driver'])
            ->get();

        $buses = $runs->map(function (ScheduleRun $run) {
            // The two most recent fixes: the latest is the marker position, and
            // the one before it gives the heading so the bus points the right way.
            $recent = $run->locations()->latest('recorded_at')->take(2)->get();
            $location = $recent->first();

            if (! $location) {
                return null;
            }

            $previous = $recent->get(1);
            $schedule = $run->schedule;

            return [
                'bus_id' => $schedule->bus_id,
                'bus_registration' => $schedule->bus?->registration_number,
                'driver_name' => $schedule->driver?->name,
                'route_name' => $schedule->route?->name,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'heading' => $this->bearing($previous, $location),
                'departure_time' => substr((string) $schedule->departure_time, 0, 5),
                'arrival_time' => substr((string) $schedule->arrival_time, 0, 5),
                'recorded_at' => $location->recorded_at?->toIso8601String(),
            ];
        })->filter()->values();

        return view('panel.live-tracking', [
            'buses' => $buses,
            'apiKey' => config('services.google_maps.key'),
        ]);
    }

    /**
     * The bus ids of every run live right now. The map polls this to spot buses
     * whose run has ended or been cancelled and remove their markers.
     */
    public function activeRunIds(): JsonResponse
    {
        $busIds = ScheduleRun::activeNow()
            ->with('schedule:id,bus_id')
            ->get()
            ->pluck('schedule.bus_id')
            ->filter()
            ->unique()
            ->values();

        return response()->json($busIds);
    }

    /**
     * Compass bearing in degrees (0 = north, clockwise) from a previous fix to
     * the current one, used to rotate the bus marker toward its direction of
     * travel. Null when there is no previous fix to measure against.
     */
    private function bearing(?BusLocation $from, BusLocation $to): ?float
    {
        if (! $from) {
            return null;
        }

        $lat1 = deg2rad((float) $from->latitude);
        $lat2 = deg2rad((float) $to->latitude);
        $dLng = deg2rad((float) $to->longitude - (float) $from->longitude);

        $y = sin($dLng) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dLng);

        return round(fmod(rad2deg(atan2($y, $x)) + 360, 360), 1);
    }
}
