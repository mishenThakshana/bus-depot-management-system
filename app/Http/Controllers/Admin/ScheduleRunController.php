<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MaintenanceRecord;
use App\Models\Schedule;
use App\Models\ScheduleRun;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleRunController extends Controller
{
    public function index(Schedule $schedule): View
    {
        $schedule->load(['route', 'bus', 'driver']);

        $runs = $schedule->runs()->orderBy('run_date')->paginate(20);

        $calendar = $schedule->runs()->orderBy('run_date')->get(['id', 'run_date', 'status'])
            ->map(fn (ScheduleRun $run) => [
                'id'        => $run->id,
                'date'      => $run->run_date->format('Y-m-d'),
                'past'      => $run->isPast(),
                'cancelled' => $run->isCancelled(),
            ])->values();

        return view('panel.schedule-runs', compact('schedule', 'runs', 'calendar'));
    }

    public function reschedule(Request $request, Schedule $schedule, ScheduleRun $run): RedirectResponse
    {
        abort_unless($run->schedule_id === $schedule->id, 404);

        if ($run->isPast()) {
            return back()->with('error', 'A run that has already passed cannot be rescheduled.');
        }

        $validated = $request->validate([
            'run_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $newDate   = $validated['run_date'];
        $formatted = Carbon::parse($newDate)->format('d M Y');

        if ($schedule->runs()->where('run_date', $newDate)->whereKeyNot($run->id)->exists()) {
            return back()->with('error', "This schedule already has a run on {$formatted}.");
        }

        if ($schedule->is_active && $clash = $schedule->firstRunConflict([$newDate], $schedule->id)) {
            return back()->with('error', $clash);
        }

        if (MaintenanceRecord::where('bus_id', $schedule->bus_id)->where('serviced_date', $newDate)->exists()) {
            return back()->with('error', "Bus {$schedule->bus?->registration_number} has a maintenance record on {$formatted} — reschedule or remove it first.");
        }

        $oldFormatted = $run->run_date->format('d M Y');
        $run->update(['run_date' => $newDate]);

        ActivityLog::record('schedule_runs', 'rescheduled', "Schedule #{$schedule->id} run moved from {$oldFormatted} to {$formatted}");

        return redirect()->route('panel.schedules.runs', $schedule)
            ->with('success', "Run moved to {$formatted}.");
    }

    public function cancel(Schedule $schedule, ScheduleRun $run): RedirectResponse
    {
        abort_unless($run->schedule_id === $schedule->id, 404);

        if ($run->isPast()) {
            return back()->with('error', 'A run that has already passed cannot be cancelled.');
        }

        $run->update(['status' => ScheduleRun::STATUS_CANCELLED]);

        ActivityLog::record('schedule_runs', 'cancelled', "Schedule #{$schedule->id} run on {$run->run_date->format('d M Y')} cancelled");

        return redirect()->route('panel.schedules.runs', $schedule)
            ->with('success', "Run on {$run->run_date->format('d M Y')} has been cancelled.");
    }

    public function reactivate(Schedule $schedule, ScheduleRun $run): RedirectResponse
    {
        abort_unless($run->schedule_id === $schedule->id, 404);

        if (! $run->isCancelled()) {
            return back()->with('error', 'This run is already active.');
        }

        if ($run->isPast()) {
            return back()->with('error', 'A run that has already passed cannot be reactivated.');
        }

        $date = $run->run_date->format('Y-m-d');

        if ($schedule->is_active && $clash = $schedule->firstRunConflict([$date], $schedule->id)) {
            return back()->with('error', $clash);
        }

        if (MaintenanceRecord::where('bus_id', $schedule->bus_id)->where('serviced_date', $date)->exists()) {
            $formatted = $run->run_date->format('d M Y');
            return back()->with('error', "Bus {$schedule->bus?->registration_number} has a maintenance record on {$formatted} — remove it first.");
        }

        $run->update(['status' => ScheduleRun::STATUS_SCHEDULED]);

        ActivityLog::record('schedule_runs', 'reactivated', "Schedule #{$schedule->id} run on {$run->run_date->format('d M Y')} reactivated");

        return redirect()->route('panel.schedules.runs', $schedule)
            ->with('success', "Run on {$run->run_date->format('d M Y')} has been reactivated.");
    }
}
