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

        // Counts use the full set; the display lists below are capped so the
        // dashboard cards stay short instead of scrolling endlessly.
        $todayScheduled = ScheduleRun::where('run_date', $today)->where('status', ScheduleRun::STATUS_SCHEDULED)->count();
        $todayCancelled = ScheduleRun::where('run_date', $today)->where('status', ScheduleRun::STATUS_CANCELLED)->count();
        $todayRunsTotal = $todayScheduled + $todayCancelled;

        $todayRuns = ScheduleRun::with(['schedule.route', 'schedule.bus', 'schedule.driver'])
            ->where('run_date', $today)
            ->orderBy('status')
            ->limit(5)
            ->get();

        $recentActivity = ActivityLog::with('user')
            ->latest('created_at')
            ->limit(6)
            ->get();

        $upcomingMaintenanceTotal = MaintenanceRecord::where('serviced_date', '>=', $today)->count();
        $upcomingMaintenance = MaintenanceRecord::with('bus')
            ->where('serviced_date', '>=', $today)
            ->orderBy('serviced_date')
            ->limit(5)
            ->get();

        $licenceRenewalsTotal = Driver::where('is_active', true)
            ->where('licence_expiry_date', '<=', now()->addDays(60)->toDateString())
            ->count();
        $licenceRenewals = Driver::where('is_active', true)
            ->where('licence_expiry_date', '<=', now()->addDays(60)->toDateString())
            ->orderBy('licence_expiry_date')
            ->limit(5)
            ->get();

        return view('panel.dashboard', compact(
            'totalBuses',
            'inServiceBuses',
            'totalDrivers',
            'activeDrivers',
            'todayRuns',
            'todayRunsTotal',
            'todayScheduled',
            'todayCancelled',
            'recentActivity',
            'upcomingMaintenance',
            'upcomingMaintenanceTotal',
            'licenceRenewals',
            'licenceRenewalsTotal',
        ));
    }
}
