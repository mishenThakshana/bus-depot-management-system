<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function index(): View
    {
        $routes = BusRoute::orderBy('name')->paginate(10)->withQueryString();

        return view('panel.routes', compact('routes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        BusRoute::create([
            'name'            => $validated['name'],
            'origin'          => $validated['origin'],
            'origin_lat'      => $validated['origin_lat'] ?? null,
            'origin_lng'      => $validated['origin_lng'] ?? null,
            'destination'     => $validated['destination'],
            'destination_lat' => $validated['destination_lat'] ?? null,
            'destination_lng' => $validated['destination_lng'] ?? null,
            'stops'           => $this->normalizeStops($validated['stops'] ?? []),
            'distance_km'     => $validated['distance_km'],
        ]);

        return redirect()->route('panel.routes')->with('success', "Route \"{$validated['name']}\" has been created.");
    }

    public function update(Request $request, BusRoute $route): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $newIsActive = $request->boolean('is_active');

        if ($route->is_active && ! $newIsActive && $this->hasActiveSchedules($route)) {
            return redirect()->route('panel.routes')
                ->with('error', "Route \"{$route->name}\" cannot be deactivated while it is assigned to a schedule.");
        }

        $route->update([
            'name'            => $validated['name'],
            'origin'          => $validated['origin'],
            'origin_lat'      => $validated['origin_lat'] ?? null,
            'origin_lng'      => $validated['origin_lng'] ?? null,
            'destination'     => $validated['destination'],
            'destination_lat' => $validated['destination_lat'] ?? null,
            'destination_lng' => $validated['destination_lng'] ?? null,
            'stops'           => $this->normalizeStops($validated['stops'] ?? []),
            'distance_km'     => $validated['distance_km'],
            'is_active'       => $newIsActive,
        ]);

        return redirect()->route('panel.routes')->with('success', "Route \"{$route->name}\" has been updated.");
    }

    /**
     * Shared validation rules for storing/updating a route. Coordinates are
     * optional so a manually-typed location still works, but when present they
     * must fall within valid lat/lng ranges.
     */
    private function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'origin'          => ['required', 'string', 'max:255'],
            'origin_lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'origin_lng'      => ['nullable', 'numeric', 'between:-180,180'],
            'destination'     => ['required', 'string', 'max:255'],
            'destination_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'stops'           => ['nullable', 'array'],
            'stops.*.name'    => ['nullable', 'string', 'max:255'],
            'stops.*.lat'     => ['nullable', 'numeric', 'between:-90,90'],
            'stops.*.lng'     => ['nullable', 'numeric', 'between:-180,180'],
            'distance_km'     => ['required', 'numeric', 'min:0.1', 'max:99999'],
        ];
    }

    /**
     * Drop empty stop rows and keep each one as {name, lat, lng}. Coordinates
     * are cast to float (or null) so the map can plot the exact picked point.
     */
    private function normalizeStops(array $stops): ?array
    {
        $clean = [];

        foreach ($stops as $stop) {
            $name = trim($stop['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $clean[] = [
                'name' => $name,
                'lat'  => isset($stop['lat']) && $stop['lat'] !== '' ? (float) $stop['lat'] : null,
                'lng'  => isset($stop['lng']) && $stop['lng'] !== '' ? (float) $stop['lng'] : null,
            ];
        }

        return $clean ?: null;
    }

    public function destroy(BusRoute $route): RedirectResponse
    {
        if ($this->hasActiveSchedules($route)) {
            return redirect()->route('panel.routes')
                ->with('error', "Route \"{$route->name}\" cannot be deleted while it is assigned to a schedule.");
        }

        $route->delete();

        return redirect()->route('panel.routes')->with('success', "Route \"{$route->name}\" has been deleted.");
    }

    private function hasActiveSchedules(BusRoute $route): bool
    {
        return $route->schedules()->where('is_active', true)->exists();
    }
}
