<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\BusLocation;
use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\Schedule;
use App\Models\ScheduleRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Screenshot/demo fixture for the Live Tracking map (/live-tracking).
 *
 * Builds one clearly-labelled bus that is "active now" and has a fresh GPS
 * fix, so the live map paints exactly one marker. Re-running is safe: it wipes
 * its own demo rows first and rebuilds the run anchored to the current wall
 * clock, so the schedule is always live at the moment you seed.
 *
 * Run with:  php artisan db:seed --class=LiveTrackingDemoSeeder
 *
 * Test-case states (all observed on /live-tracking as an admin/supervisor):
 *   TC1 marker present  — run this seeder, then load the map.
 *   TC3 marker removed  — cancel the demo run; the marker drops on the next
 *                         poll (≤30s) or broadcast.
 *   TC2 no markers      — seed, then cancel the run (or seed nothing) so no
 *                         run is active; the map loads empty.
 */
class LiveTrackingDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private const BUS_REG = 'NA-9001';

    private const DRIVER_LICENCE = 'B9000001';

    private const DRIVER_EMAIL = 'livedriver@depot.com';

    private const ROUTE_NAME = 'Colombo – Kandy Express (Live Demo)';

    /**
     * A short trail of fixes along the A1 Colombo–Kandy road (last = current
     * position). Points sit on the highway so the marker lands on the road.
     */
    private const TRAIL = [
        [7.2239953, 80.1943202], // approaching Warakapola on the A1
        [7.2248101, 80.1962763], // through the junction
        [7.2254977, 80.1963920], // current position — snapped to the A1 road
    ];

    public function run(): void
    {
        $this->purgePriorDemo();

        $route = BusRoute::firstOrCreate(
            ['name' => self::ROUTE_NAME],
            [
                'origin' => 'Colombo',
                'destination' => 'Kandy',
                'stops' => ['Colombo', 'Nittambuwa', 'Kegalle', 'Kandy'],
                'distance_km' => 115.0,
                'is_active' => true,
            ]
        );

        $bus = Bus::firstOrCreate(
            ['registration_number' => self::BUS_REG],
            [
                'vehicle_type' => 'Standard Bus',
                'seat_capacity' => 52,
                'current_mileage' => 184_500,
                'is_in_service' => true,
            ]
        );

        $driver = Driver::firstOrCreate(
            ['licence_number' => self::DRIVER_LICENCE],
            [
                'name' => 'Nuwan Live-Demo',
                'email' => self::DRIVER_EMAIL,
                'nic' => '901234567V',
                'licence_expiry_date' => Carbon::today()->addYears(3),
                'phone_number' => '0771234567',
                'is_active' => true,
            ]
        );

        // Login account for the driver portal, so the "driver shares location"
        // half of the flow is testable too. Known password, no forced reset.
        User::updateOrCreate(
            ['email' => self::DRIVER_EMAIL],
            [
                'name' => $driver->name,
                'role' => 'driver',
                'driver_id' => $driver->id,
                'is_active' => true,
                'must_change_password' => false,
                'password' => 'driver1234',
            ]
        );

        // Active-now window: departed 30 min ago, arrives in 3 h. Anchored to the
        // current wall clock so ScheduleRun::activeNow() always matches today's run.
        $now = Carbon::now();
        $schedule = Schedule::create([
            'bus_route_id' => $route->id,
            'bus_id' => $bus->id,
            'driver_id' => $driver->id,
            'departure_time' => $now->copy()->subMinutes(30)->format('H:i:s'),
            'arrival_time' => $now->copy()->addHours(3)->format('H:i:s'),
            'frequency' => 'daily',
            'days_of_week' => null,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDay(),
            'is_active' => true,
        ]);

        $run = $schedule->runs()->create([
            'run_date' => Carbon::today(),
            'status' => ScheduleRun::STATUS_SCHEDULED,
        ]);

        // GPS trail, ending at the bus's current position a few seconds ago.
        foreach (self::TRAIL as $i => [$lat, $lng]) {
            $stepsFromEnd = count(self::TRAIL) - 1 - $i;
            BusLocation::create([
                'schedule_run_id' => $run->id,
                'driver_id' => $driver->id,
                'bus_id' => $bus->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'speed' => 54.0,
                'accuracy' => 8.0,
                'recorded_at' => $now->copy()->subSeconds($stepsFromEnd * 20 + 5),
            ]);
        }

        $this->command?->info('LiveTrackingDemoSeeder: bus '.self::BUS_REG.' is live now (run #'.$run->id.') with '.count(self::TRAIL).' fixes.');
        $this->command?->info('  Live map:  /live-tracking   (login admin@depot.com / admin1234)');
        $this->command?->info('  Cancel run to test marker removal: ScheduleRun #'.$run->id);
    }

    /** Remove rows from a previous run of this seeder so it stays idempotent. */
    private function purgePriorDemo(): void
    {
        $bus = Bus::where('registration_number', self::BUS_REG)->first();

        if ($bus) {
            // Schedules (and their runs + bus_locations) cascade on delete.
            Schedule::where('bus_id', $bus->id)->delete();
        }

        Driver::where('licence_number', self::DRIVER_LICENCE)->delete();
        User::where('email', self::DRIVER_EMAIL)->delete();
        BusRoute::where('name', self::ROUTE_NAME)->delete();
        $bus?->delete();
    }
}
