<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bus;
use App\Models\Driver;
use App\Models\FuelLog;
use App\Models\MaintenanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuelController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'fuel');

        // Shared filters applied per-tab to the relevant date column.
        $filters = [
            'bus_id'    => $request->query('bus_id'),
            'date_from' => $request->query('date_from'),
            'date_to'   => $request->query('date_to'),
            'type'      => $request->query('type'), // maintenance only
        ];
        $hasFilters = collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

        $fuelLogs = FuelLog::with(['bus', 'driver'])
            ->when($filters['bus_id'], fn ($q, $v) => $q->where('bus_id', $v))
            ->when($filters['date_from'], fn ($q, $v) => $q->whereDate('fuel_date', '>=', $v))
            ->when($filters['date_to'], fn ($q, $v) => $q->whereDate('fuel_date', '<=', $v))
            ->latest('fuel_date')->latest('id')
            ->paginate(10, ['*'], 'fuel_page')
            ->withQueryString();

        $maintenanceRecords = MaintenanceRecord::with('bus')
            ->when($filters['bus_id'], fn ($q, $v) => $q->where('bus_id', $v))
            ->when($filters['date_from'], fn ($q, $v) => $q->whereDate('serviced_date', '>=', $v))
            ->when($filters['date_to'], fn ($q, $v) => $q->whereDate('serviced_date', '<=', $v))
            ->when($filters['type'], fn ($q, $v) => $q->where('maintenance_type', $v))
            ->latest('serviced_date')->latest('id')
            ->paginate(10, ['*'], 'maint_page')
            ->withQueryString();

        $buses   = Bus::orderBy('registration_number')->get();
        $drivers = Driver::where('is_active', true)->orderBy('name')->get();

        return view('panel.fuel', compact(
            'tab', 'fuelLogs', 'maintenanceRecords', 'buses', 'drivers', 'filters', 'hasFilters'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bus_id'         => ['required', 'exists:buses,id'],
            'driver_id'      => ['nullable', 'exists:drivers,id'],
            'fuel_date'      => ['required', 'date'],
            'litres'         => ['required', 'numeric', 'min:0.01'],
            'cost_per_litre' => ['required', 'numeric', 'min:0.01'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $log = FuelLog::create($validated);
        $log->load('bus');

        ActivityLog::record('fuel', 'created', "Fuel log added for bus {$log->bus?->registration_number} on {$log->fuel_date->format('d M Y')}");

        return redirect()->route('panel.fuel', ['tab' => 'fuel'])
            ->with('success', 'Fuel log has been recorded.');
    }

    public function update(Request $request, FuelLog $fuelLog): RedirectResponse
    {
        $validated = $request->validate([
            'bus_id'         => ['required', 'exists:buses,id'],
            'driver_id'      => ['nullable', 'exists:drivers,id'],
            'fuel_date'      => ['required', 'date'],
            'litres'         => ['required', 'numeric', 'min:0.01'],
            'cost_per_litre' => ['required', 'numeric', 'min:0.01'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $fuelLog->update($validated);
        $fuelLog->load('bus');

        ActivityLog::record('fuel', 'updated', "Fuel log updated for bus {$fuelLog->bus?->registration_number} on {$fuelLog->fuel_date->format('d M Y')}");

        return redirect()->route('panel.fuel', ['tab' => 'fuel'])
            ->with('success', 'Fuel log has been updated.');
    }

    public function destroy(FuelLog $fuelLog): RedirectResponse
    {
        $fuelLog->load('bus');
        $label = "bus {$fuelLog->bus?->registration_number} on {$fuelLog->fuel_date->format('d M Y')}";
        $fuelLog->delete();

        ActivityLog::record('fuel', 'deleted', "Fuel log deleted for {$label}");

        return redirect()->route('panel.fuel', ['tab' => 'fuel'])
            ->with('success', 'Fuel log has been removed.');
    }

}
