<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        MaintenanceRecord::create($validated);

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
        $maintenanceRecord->delete();

        return redirect()->route('panel.fuel', ['tab' => 'maintenance'])
            ->with('success', 'Maintenance record has been removed.');
    }

    public function staffIndex(): View
    {
        $records = MaintenanceRecord::with('bus')
            ->latest('serviced_date')->latest('id')
            ->paginate(15);

        return view('panel.maintenance', compact('records'));
    }
}
