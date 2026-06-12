<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role   = $request->query('role_filter'); // admin | supervisor | driver

        $users = User::where('id', '!=', auth()->id())
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when(in_array($role, ['admin', 'supervisor', 'driver'], true), fn ($q) => $q->where('role', $role))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('panel.users', compact('users', 'search', 'role'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'      => ['required', 'in:supervisor'],
            'is_active' => ['required', 'in:1,0'],
        ]);

        $temporaryPassword = Str::password(12, symbols: false);

        $user = User::create([
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'role'                 => $validated['role'],
            'is_active'            => (bool) $validated['is_active'],
            'must_change_password' => true,
            'password'             => $temporaryPassword,
        ]);

        Mail::to($user->email)->send(new WelcomeUserMail($user, $temporaryPassword));

        ActivityLog::record('users', 'created', "User \"{$user->name}\" ({$user->role}) account created");

        return redirect()
            ->route('panel.users')
            ->with('success', "Account created for {$user->name}. Login details have been sent to {$user->email}.");
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), Response::HTTP_FORBIDDEN);

        $user->update(['is_active' => ! $user->is_active]);

        $label = $user->is_active ? 'activated' : 'deactivated';

        ActivityLog::record('users', $label, "User \"{$user->name}\" account {$label}");

        return redirect()
            ->route('panel.users')
            ->with('success', "{$user->name}'s account has been {$label}.");
    }
}
