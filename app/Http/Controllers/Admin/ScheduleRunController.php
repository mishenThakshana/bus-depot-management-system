<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleRun;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleRunController extends Controller
{
    /**
     * List every dated run for one schedule, oldest first, so an operator can
     * cancel or reschedule individual dates without touching the recurring rule.
     */
    public function index(Schedule $schedule): View
    {
        $schedule->load(['route', 'bus', 'driver']);

        $runs = $schedule->runs()->orderBy('run_date')->paginate(20);

        // A lightweight, unpaginated view of every date for the calendar.
        $calendar = $schedule->runs()->orderBy('run_date')->get(['id', 'run_date', 'status'])
            ->map(fn (ScheduleRun $run) => [
                'id'        => $run->id,
                'date'      => $run->run_date->format('Y-m-d'),
                'past'      => $run->isPast(),
                'cancelled' => $run->isCancelled(),
            ])->values();

        return view('panel.schedule-runs', compact('schedule', 'runs', 'calendar'));
    }

    /**
     * Move a single run to a new date. Only future (or today's) runs can move,
     * the new date must not already be taken by this schedule, and the bus and
     * driver must be free on that date — the same clash rule as schedule creation.
     */
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

        $run->update(['run_date' => $newDate]);

        return redirect()->route('panel.schedules.runs', $schedule)
            ->with('success', "Run moved to {$formatted}.");
    }

    /**
     * Cancel a single run. The row is kept and merely marked cancelled, so it
     * stays in the history, frees the slot for other schedules, and can be
     * reactivated later. Only future (or today's) runs may be cancelled.
     */
    public function cancel(Schedule $schedule, ScheduleRun $run): RedirectResponse
    {
        abort_unless($run->schedule_id === $schedule->id, 404);

        if ($run->isPast()) {
            return back()->with('error', 'A run that has already passed cannot be cancelled.');
        }

        $run->update(['status' => ScheduleRun::STATUS_CANCELLED]);

        return redirect()->route('panel.schedules.runs', $schedule)
            ->with('success', "Run on {$run->run_date->format('d M Y')} has been cancelled.");
    }

    /**
     * Bring a cancelled run back. The slot may have been taken by another
     * schedule while it was cancelled, so re-run the same clash check before
     * marking it live again. Only future (or today's) runs may be reactivated.
     */
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

        $run->update(['status' => ScheduleRun::STATUS_SCHEDULED]);

        return redirect()->route('panel.schedules.runs', $schedule)
            ->with('success', "Run on {$run->run_date->format('d M Y')} has been reactivated.");
    }
}
