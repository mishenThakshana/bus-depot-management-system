<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusController extends Controller
{
    public function index(): View
    {
        $buses = Bus::orderBy('registration_number')->paginate(10)->withQueryString();

        return view('panel.buses', compact('buses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        Bus::create($validated);

        return redirect()->route('panel.buses')
            ->with('success', "Bus \"{$validated['registration_number']}\" has been added.");
    }

    public function update(Request $request, Bus $bus): RedirectResponse
    {
        $validated = $request->validate($this->rules($bus->id));

        $newInService = $request->boolean('is_in_service');

        if ($bus->is_in_service && ! $newInService && $this->hasActiveTrips($bus)) {
            return redirect()->route('panel.buses')
                ->with('error', "Bus \"{$bus->registration_number}\" cannot be taken out of service while it has active or upcoming trips.");
        }

        $validated['is_in_service'] = $newInService;
        $bus->update($validated);

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
        // Wire up once Trip model exists: return $bus->trips()->whereIn('status', ['active','upcoming'])->exists();
        return isset($bus) && false;
    }
}
