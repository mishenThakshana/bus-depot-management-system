<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Driver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(): View
    {
        $drivers = Driver::orderBy('name')->paginate(10)->withQueryString();

        return view('panel.drivers', compact('drivers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        Driver::create($validated);

        ActivityLog::record('drivers', 'created', "Driver \"{$validated['name']}\" added");

        return redirect()->route('panel.drivers')
            ->with('success', "Driver \"{$validated['name']}\" has been added.");
    }

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $validated = $request->validate($this->rules($driver->id));

        $driver->update($validated);

        ActivityLog::record('drivers', 'updated', "Driver \"{$driver->name}\" updated");

        return redirect()->route('panel.drivers')
            ->with('success', "Driver \"{$driver->name}\" has been updated.");
    }

    public function toggleActive(Driver $driver): RedirectResponse
    {
        $driver->update(['is_active' => ! $driver->is_active]);

        $label = $driver->is_active ? 'activated' : 'deactivated';

        ActivityLog::record('drivers', $label, "Driver \"{$driver->name}\" {$label}");

        return redirect()->route('panel.drivers')
            ->with('success', "Driver \"{$driver->name}\" has been {$label}.");
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'name'                => ['required', 'string', 'max:100'],
            'nic'                 => ['required', 'string', 'max:20', Rule::unique('drivers', 'nic')->ignore($ignoreId)],
            'licence_number'      => ['required', 'string', 'max:30', Rule::unique('drivers', 'licence_number')->ignore($ignoreId)],
            'licence_expiry_date' => ['required', 'date', 'after:today'],
            'phone_number'        => ['required', 'string', 'max:20'],
        ];
    }
}
