<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type   = $request->query('type'); // vehicle type
        $status = $request->query('status'); // in | out (of service)

        $buses = Bus::query()
            ->when($search !== '', fn ($q) => $q->where('registration_number', 'like', "%{$search}%"))
            ->when(in_array($type, Bus::$vehicleTypes, true), fn ($q) => $q->where('vehicle_type', $type))
            ->when($status === 'in', fn ($q) => $q->where('is_in_service', true))
            ->when($status === 'out', fn ($q) => $q->where('is_in_service', false))
            ->orderBy('registration_number')
            ->paginate(10)
            ->withQueryString();

        return view('panel.buses', compact('buses', 'search', 'type', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['registration_number'] = strtoupper($validated['registration_number']);

        Bus::create($validated);

        ActivityLog::record('buses', 'created', "Bus \"{$validated['registration_number']}\" added");

        return redirect()->route('panel.buses')
            ->with('success', "Bus \"{$validated['registration_number']}\" has been added.");
    }

    public function update(Request $request, Bus $bus): RedirectResponse
    {
        $validated = $request->validate($this->rules($bus->id));
        $validated['registration_number'] = strtoupper($validated['registration_number']);

        $newInService = $request->boolean('is_in_service');

        if ($bus->is_in_service && ! $newInService && $this->hasActiveTrips($bus)) {
            return redirect()->route('panel.buses')
                ->with('error', "Bus \"{$bus->registration_number}\" cannot be taken out of service while it has active or upcoming trips.");
        }

        $validated['is_in_service'] = $newInService;
        $bus->update($validated);

        ActivityLog::record('buses', 'updated', "Bus \"{$bus->registration_number}\" updated");

        return redirect()->route('panel.buses')
            ->with('success', "Bus \"{$bus->registration_number}\" has been updated.");
    }

    public function destroy(Bus $bus): RedirectResponse
    {
        if ($this->hasActiveTrips($bus)) {
            return redirect()->route('panel.buses')
                ->with('error', "Bus \"{$bus->registration_number}\" cannot be removed while it has active or upcoming trips assigned to it.");
        }

        $regNumber = $bus->registration_number;
        $bus->delete();

        ActivityLog::record('buses', 'deleted', "Bus \"{$regNumber}\" removed");

        return redirect()->route('panel.buses')
            ->with('success', "Bus \"{$regNumber}\" has been removed from the system.");
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'registration_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('buses', 'registration_number')->ignore($ignoreId),
            ],
            'vehicle_type'    => ['required', 'string', Rule::in(Bus::$vehicleTypes)],
            'seat_capacity'   => ['required', 'integer', 'min:1', 'max:200'],
            'current_mileage' => ['required', 'integer', 'min:0'],
        ];
    }

    private function hasActiveTrips(Bus $bus): bool
    {
        return $bus->schedules()
            ->where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->exists();
    }
}
