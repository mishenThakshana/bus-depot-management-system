<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $fuelLogs = FuelLog::with(['bus', 'driver'])
            ->latest('fuel_date')->latest('id')
            ->paginate(10, ['*'], 'fuel_page')
            ->withQueryString();

        $maintenanceRecords = MaintenanceRecord::with('bus')
            ->latest('serviced_date')->latest('id')
            ->paginate(10, ['*'], 'maint_page')
            ->withQueryString();

        $buses   = Bus::orderBy('registration_number')->get();
        $drivers = Driver::where('is_active', true)->orderBy('name')->get();

        return view('panel.fuel', compact(
            'tab', 'fuelLogs', 'maintenanceRecords', 'buses', 'drivers'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bus_id'           => ['required', 'exists:buses,id'],
            'driver_id'        => ['nullable', 'exists:drivers,id'],
            'fuel_date'        => ['required', 'date'],
            'litres'           => ['required', 'numeric', 'min:0.01'],
            'cost_per_litre'   => ['required', 'numeric', 'min:0.01'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        FuelLog::create($validated);

        return redirect()->route('panel.fuel', ['tab' => 'fuel'])
            ->with('success', 'Fuel log has been recorded.');
    }

    public function update(Request $request, FuelLog $fuelLog): RedirectResponse
    {
        $validated = $request->validate([
            'bus_id'           => ['required', 'exists:buses,id'],
            'driver_id'        => ['nullable', 'exists:drivers,id'],
            'fuel_date'        => ['required', 'date'],
            'litres'           => ['required', 'numeric', 'min:0.01'],
            'cost_per_litre'   => ['required', 'numeric', 'min:0.01'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $fuelLog->update($validated);

        return redirect()->route('panel.fuel', ['tab' => 'fuel'])
            ->with('success', 'Fuel log has been updated.');
    }

    public function destroy(FuelLog $fuelLog): RedirectResponse
    {
        $fuelLog->delete();

        return redirect()->route('panel.fuel', ['tab' => 'fuel'])
            ->with('success', 'Fuel log has been removed.');
    }

    public function staffFuelLogs(): View
    {
        $fuelLogs = FuelLog::with(['bus', 'driver'])
            ->latest('fuel_date')->latest('id')
            ->paginate(15);

        $buses   = Bus::orderBy('registration_number')->get();
        $drivers = Driver::where('is_active', true)->orderBy('name')->get();

        return view('panel.fuel-logs', compact('fuelLogs', 'buses', 'drivers'));
    }
}
