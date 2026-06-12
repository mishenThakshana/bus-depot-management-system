<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\FuelLog;
use App\Models\MaintenanceRecord;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-time demo data for the Schedules page (calendar + list views).
 *
 * Run with:  php artisan db:seed --class=DemoDataSeeder
 *
 * Wipes existing operational data, then creates routes, buses, drivers and a
 * compact set of 30 schedules whose runs span the weeks around today. The
 * schedules use a fixed daily / weekly / monthly mix and share a small pool of
 * timeslots, so a single day holds several runs and a single timeslot holds
 * multiple buses + drivers — exactly what the day view is built to show.
 *
 * Not wired into DatabaseSeeder so production seeding stays clean.
 */
class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Departure / arrival windows shared across schedules. */
    private array $timeslots = [
        ['05:30', '07:00'],
        ['07:00', '08:30'],
        ['08:30', '10:00'],
        ['10:00', '12:00'],
        ['12:00', '14:00'],
        ['14:00', '16:00'],
        ['16:00', '18:00'],
        ['18:00', '20:00'],
    ];

    public function run(): void
    {
        // Clear existing operational data so the demo set is rebuilt from
        // scratch (and stays small). Deleted in FK-safe order; users, audit and
        // login logs are left untouched. Schedule runs cascade with schedules.
        FuelLog::query()->delete();
        MaintenanceRecord::query()->delete();
        Schedule::query()->delete();
        User::where('role', 'driver')->delete();
        Driver::query()->delete();
        Bus::query()->delete();
        BusRoute::query()->delete();

        $routes  = $this->seedRoutes();
        $buses   = $this->seedBuses(12);
        $drivers = $this->seedDrivers(12);

        $this->seedSchedules(30, $routes, $buses, $drivers);

        $this->command?->info('DemoDataSeeder: '.Schedule::count().' schedules and '
            .DB::table('schedule_runs')->count().' runs available.');
    }

    private function seedRoutes(): array
    {
        $defs = [
            ['Colombo – Kandy Express', 'Colombo', 'Kandy', 115.0],
            ['Colombo – Galle Coastal', 'Colombo', 'Galle', 119.0],
            ['Colombo – Jaffna Mainline', 'Colombo', 'Jaffna', 396.0],
            ['Kandy – Nuwara Eliya Hills', 'Kandy', 'Nuwara Eliya', 77.0],
            ['Colombo – Negombo Shuttle', 'Colombo', 'Negombo', 38.0],
            ['Galle – Matara Southern', 'Galle', 'Matara', 45.0],
            ['Colombo – Anuradhapura', 'Colombo', 'Anuradhapura', 205.0],
            ['Kurunegala – Colombo', 'Kurunegala', 'Colombo', 94.0],
            ['Colombo – Badulla Highland', 'Colombo', 'Badulla', 230.0],
            ['Colombo – Trincomalee', 'Colombo', 'Trincomalee', 257.0],
            ['Ratnapura – Colombo', 'Ratnapura', 'Colombo', 101.0],
            ['Colombo – Batticaloa', 'Colombo', 'Batticaloa', 314.0],
        ];

        $routes = [];

        foreach ($defs as [$name, $origin, $destination, $distance]) {
            $routes[] = BusRoute::firstOrCreate(
                ['name' => $name],
                [
                    'origin'      => $origin,
                    'destination' => $destination,
                    'stops'       => [$origin, 'Midway Halt', $destination],
                    'distance_km' => $distance,
                    'is_active'   => true,
                ]
            );
        }

        return $routes;
    }

    private function seedBuses(int $count): array
    {
        $types   = Bus::$vehicleTypes;
        $letters = ['NA', 'NB', 'NC', 'ND', 'NW', 'PA', 'PB', 'PC'];
        $buses   = [];

        for ($i = 1; $i <= $count; $i++) {
            $reg = sprintf('%s-%04d', $letters[($i - 1) % count($letters)], 1000 + $i);

            $buses[] = Bus::firstOrCreate(
                ['registration_number' => $reg],
                [
                    'vehicle_type'    => $types[($i - 1) % count($types)],
                    'seat_capacity'   => [28, 45, 52, 60][($i - 1) % 4],
                    'current_mileage' => random_int(20_000, 250_000),
                    'is_in_service'   => true,
                ]
            );
        }

        return $buses;
    }

    private function seedDrivers(int $count): array
    {
        $first = ['Saman', 'Kasun', 'Nimal', 'Pradeep', 'Ruwan', 'Chaminda', 'Lakmal', 'Sunil',
                  'Tharindu', 'Dinesh', 'Roshan', 'Asanka', 'Gayan', 'Mahesh', 'Buddhika'];
        $last  = ['Perera', 'Fernando', 'Silva', 'Bandara', 'Jayawardena', 'Wickramasinghe',
                  'Rajapaksa', 'Gunawardena', 'Dissanayake', 'Senanayake'];

        $drivers = [];

        for ($i = 1; $i <= $count; $i++) {
            $name  = $first[($i - 1) % count($first)].' '.$last[intdiv($i - 1, count($first)) % count($last)];
            $email = sprintf('driver%02d@depot.com', $i);

            $driver = Driver::firstOrCreate(
                ['licence_number' => sprintf('B%07d', 1_000_000 + $i)],
                [
                    'name'                => $name,
                    'email'               => $email,
                    'nic'                 => sprintf('%09dV', 850_000_000 + $i),
                    'licence_expiry_date' => Carbon::today()->addMonths(random_int(6, 60)),
                    'phone_number'        => '07'.random_int(0, 9).sprintf('%07d', random_int(0, 9_999_999)),
                    'is_active'           => true,
                ]
            );

            // Demo login account so the driver portal is testable. Password is a
            // known value and no forced reset, unlike real auto-provisioned ones.
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name'                 => $name,
                    'role'                 => 'driver',
                    'driver_id'            => $driver->id,
                    'is_active'            => true,
                    'must_change_password' => false,
                    'password'             => 'driver1234',
                ]
            );

            $drivers[] = $driver;
        }

        return $drivers;
    }

    private function seedSchedules(int $count, array $routes, array $buses, array $drivers): void
    {
        $rangeStart = Carbon::today()->subWeeks(2);
        $rangeEnd   = Carbon::today()->addWeeks(6);

        // Fixed cycle so the demo always carries a predictable mix — per five
        // schedules: 2 weekly, 2 monthly, 1 daily. Daily runs dominate the run
        // count, so keeping them sparse is what keeps the dataset small.
        $cycle = ['weekly', 'monthly', 'weekly', 'monthly', 'daily'];

        for ($i = 0; $i < $count; $i++) {
            [$departure, $arrival] = $this->timeslots[$i % count($this->timeslots)];
            $frequency = $cycle[$i % count($cycle)];

            if ($frequency === 'monthly') {
                // Vary the day-of-month so monthly schedules don't all land on
                // the same day, and span a few months so each yields several
                // runs (some past, some upcoming).
                $dayOfMonth = 1 + (($i * 7) % 27);
                $start      = Carbon::today()->subMonth()->day($dayOfMonth);
                $end        = Carbon::today()->addMonths(2);
            } else {
                $start = $rangeStart->copy();
                $end   = $rangeEnd->copy();
            }

            $schedule = Schedule::create([
                'bus_route_id'   => $routes[array_rand($routes)]->id,
                'bus_id'         => $buses[array_rand($buses)]->id,
                'driver_id'      => $drivers[array_rand($drivers)]->id,
                'departure_time' => $departure,
                'arrival_time'   => $arrival,
                'frequency'      => $frequency,
                'days_of_week'   => $frequency === 'weekly' ? $this->randomWeekdays() : null,
                'start_date'     => $start,
                'end_date'       => $end,
                'is_active'      => true,
            ]);

            $dates = $schedule->runDatesBetween();

            if (! empty($dates)) {
                $schedule->runs()->createMany(
                    array_map(fn ($date) => ['run_date' => $date], $dates)
                );
            }
        }
    }

    /** Pick 2–3 distinct weekdays (Carbon dayOfWeek integers, 0=Sun…6=Sat). */
    private function randomWeekdays(): array
    {
        $all = array_keys(Schedule::$weekdays);
        shuffle($all);

        return array_values(array_slice($all, 0, random_int(2, 3)));
    }
}
