<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * List all users (paginated, excluding the currently signed-in admin).
     */
    public function index(): View
    {
        $users = User::where('id', '!=', auth()->id())
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('panel.users', compact('users'));
    }

    /**
     * Create a new user account (Supervisor or Staff only).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'      => ['required', 'in:supervisor,staff'],
            'is_active' => ['required', 'in:1,0'],
        ]);

        // Generate a human-readable temporary password and keep a plain-text copy for the email.
        $temporaryPassword = Str::password(12, symbols: false);

        $user = User::create([
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'role'                 => $validated['role'],
            'is_active'            => (bool) $validated['is_active'],
            'must_change_password' => true,
            'password'             => Hash::make($temporaryPassword),
        ]);

        // Send the welcome e-mail with login credentials.
        Mail::to($user->email)->send(new WelcomeUserMail($user, $temporaryPassword));

        return redirect()
            ->route('panel.users')
            ->with('success', "Account created for {$user->name}. Login details have been sent to {$user->email}.");
    }

    /**
     * Toggle a user's active / inactive status.
     * Admin cannot deactivate their own account.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), Response::HTTP_FORBIDDEN);

        $user->update(['is_active' => ! $user->is_active]);

        $label = $user->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('panel.users')
            ->with('success', "{$user->name}'s account has been {$label}.");
    }
}
