<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MaintenanceRecord;
use App\Models\ScheduleRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bus_id'           => ['required', 'exists:buses,id'],
            'maintenance_type' => ['required', Rule::in(MaintenanceRecord::$types)],
            'description'      => ['required', 'string', 'max:1000'],
            'serviced_date'    => ['required', 'date'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertNoScheduleConflict($validated['bus_id'], $validated['serviced_date']);

        $record = MaintenanceRecord::create($validated);
        $record->load('bus');

        ActivityLog::record('maintenance', 'created', "{$record->maintenance_type} maintenance logged for bus {$record->bus?->registration_number} on " . \Carbon\Carbon::parse($validated['serviced_date'])->format('d M Y'));

        return redirect()->route('panel.fuel', ['tab' => 'maintenance'])
            ->with('success', 'Maintenance record has been created.');
    }

    public function update(Request $request, MaintenanceRecord $maintenanceRecord): RedirectResponse
    {
        $validated = $request->validate([
            'bus_id'           => ['required', 'exists:buses,id'],
            'maintenance_type' => ['required', Rule::in(MaintenanceRecord::$types)],
            'description'      => ['required', 'string', 'max:1000'],
            'serviced_date'    => ['required', 'date'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertNoScheduleConflict($validated['bus_id'], $validated['serviced_date']);

        $maintenanceRecord->update($validated);
        $maintenanceRecord->load('bus');

        ActivityLog::record('maintenance', 'updated', "{$maintenanceRecord->maintenance_type} maintenance updated for bus {$maintenanceRecord->bus?->registration_number} on " . \Carbon\Carbon::parse($validated['serviced_date'])->format('d M Y'));

        return redirect()->route('panel.fuel', ['tab' => 'maintenance'])
            ->with('success', 'Maintenance record has been updated.');
    }

    private function assertNoScheduleConflict(int $busId, string $date): void
    {
        $run = ScheduleRun::scheduled()
            ->where('run_date', $date)
            ->whereHas('schedule', fn ($q) => $q->where('bus_id', $busId)->where('is_active', true))
            ->with('schedule.bus')
            ->first();

        if ($run) {
            $bus       = $run->schedule->bus?->registration_number ?? 'This bus';
            $formatted = \Carbon\Carbon::parse($date)->format('d M Y');
            throw ValidationException::withMessages([
                'serviced_date' => "{$bus} has an active scheduled run on {$formatted} — cancel it first before logging maintenance.",
            ]);
        }
    }

    public function destroy(MaintenanceRecord $maintenanceRecord): RedirectResponse
    {
        $maintenanceRecord->load('bus');
        $label = "{$maintenanceRecord->maintenance_type} maintenance for bus {$maintenanceRecord->bus?->registration_number} on {$maintenanceRecord->serviced_date->format('d M Y')}";
        $maintenanceRecord->delete();

        ActivityLog::record('maintenance', 'deleted', "{$label} deleted");

        return redirect()->route('panel.fuel', ['tab' => 'maintenance'])
            ->with('success', 'Maintenance record has been removed.');
    }

}
