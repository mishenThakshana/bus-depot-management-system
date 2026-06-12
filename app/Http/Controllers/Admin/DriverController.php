<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\ActivityLog;
use App\Models\Driver;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $search  = trim((string) $request->query('search', ''));
        $licence = $request->query('licence'); // expired | soon | valid

        $drivers = Driver::query()
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('nic', 'like', $term)
                    ->orWhere('licence_number', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone_number', 'like', $term));
            })
            ->when($licence === 'expired', fn ($q) => $q->whereDate('licence_expiry_date', '<', Carbon::today()))
            ->when($licence === 'soon', fn ($q) => $q->whereDate('licence_expiry_date', '>=', Carbon::today())
                ->whereDate('licence_expiry_date', '<=', Carbon::today()->addDays(60)))
            ->when($licence === 'valid', fn ($q) => $q->whereDate('licence_expiry_date', '>', Carbon::today()->addDays(60)))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('panel.drivers', compact('drivers', 'search', 'licence'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $temporaryPassword = Str::password(12, symbols: false);

        $user = DB::transaction(function () use ($validated, $temporaryPassword) {
            $driver = Driver::create($validated);

            // Provision a login account so the driver can view their schedules.
            return User::create([
                'name'                 => $driver->name,
                'email'                => $driver->email,
                'role'                 => 'driver',
                'driver_id'            => $driver->id,
                'is_active'            => true,
                'must_change_password' => true,
                'password'             => $temporaryPassword,
            ]);
        });

        Mail::to($user->email)->send(new WelcomeUserMail($user, $temporaryPassword));

        ActivityLog::record('drivers', 'created', "Driver \"{$validated['name']}\" added with a login account");

        return redirect()->route('panel.drivers')
            ->with('success', "Driver \"{$validated['name']}\" has been added. Login details have been sent to {$validated['email']}.");
    }

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $validated = $request->validate($this->rules($driver->id, $driver->user?->id));

        DB::transaction(function () use ($driver, $validated) {
            $driver->update($validated);

            // Keep the linked login account's name/email in step with the record.
            $driver->user?->update([
                'name'  => $validated['name'],
                'email' => $validated['email'],
            ]);
        });

        ActivityLog::record('drivers', 'updated', "Driver \"{$driver->name}\" updated");

        return redirect()->route('panel.drivers')
            ->with('success', "Driver \"{$driver->name}\" has been updated.");
    }

    public function toggleActive(Driver $driver): RedirectResponse
    {
        $newActive = ! $driver->is_active;

        DB::transaction(function () use ($driver, $newActive) {
            $driver->update(['is_active' => $newActive]);
            // A deactivated driver must not be able to log in.
            $driver->user?->update(['is_active' => $newActive]);
        });

        $label = $newActive ? 'activated' : 'deactivated';

        ActivityLog::record('drivers', $label, "Driver \"{$driver->name}\" {$label}");

        return redirect()->route('panel.drivers')
            ->with('success', "Driver \"{$driver->name}\" has been {$label}.");
    }

    private function rules(?int $ignoreDriverId = null, ?int $ignoreUserId = null): array
    {
        return [
            'name'                => ['required', 'string', 'max:100'],
            'email'               => [
                'required', 'email', 'max:255',
                Rule::unique('drivers', 'email')->ignore($ignoreDriverId),
                Rule::unique('users', 'email')->ignore($ignoreUserId),
            ],
            'nic'                 => ['required', 'string', 'max:20', Rule::unique('drivers', 'nic')->ignore($ignoreDriverId)],
            'licence_number'      => ['required', 'string', 'max:30', Rule::unique('drivers', 'licence_number')->ignore($ignoreDriverId)],
            'licence_expiry_date' => ['required', 'date', 'after:today'],
            'phone_number'        => ['required', 'string', 'max:20'],
        ];
    }
}
