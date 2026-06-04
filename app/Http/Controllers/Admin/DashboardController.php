<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bus;
use App\Models\Driver;
use App\Models\MaintenanceRecord;
use App\Models\ScheduleRun;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalBuses      = Bus::count();
        $inServiceBuses  = Bus::where('is_in_service', true)->count();
        $totalDrivers    = Driver::count();
        $activeDrivers   = Driver::where('is_active', true)->count();

        $today = now()->toDateString();

        $todayRuns = ScheduleRun::with(['schedule.route', 'schedule.bus', 'schedule.driver'])
            ->where('run_date', $today)
            ->orderBy('status')
            ->get();

        $todayScheduled  = $todayRuns->where('status', ScheduleRun::STATUS_SCHEDULED)->count();
        $todayCancelled  = $todayRuns->where('status', ScheduleRun::STATUS_CANCELLED)->count();

        $recentActivity = ActivityLog::with('user')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $upcomingMaintenance = MaintenanceRecord::with('bus')
            ->where('serviced_date', '>=', $today)
            ->orderBy('serviced_date')
            ->limit(10)
            ->get();

        $licenceRenewals = Driver::where('is_active', true)
            ->where('licence_expiry_date', '<=', now()->addDays(60)->toDateString())
            ->orderBy('licence_expiry_date')
            ->get();

        return view('panel.dashboard', compact(
            'totalBuses',
            'inServiceBuses',
            'totalDrivers',
            'activeDrivers',
            'todayRuns',
            'todayScheduled',
            'todayCancelled',
            'recentActivity',
            'upcomingMaintenance',
            'licenceRenewals',
        ));
    }
}
