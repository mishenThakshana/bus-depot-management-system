<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    private const TIMEOUT_MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = $request->session()->get('last_activity');

            if ($lastActivity && now()->diffInMinutes($lastActivity) >= self::TIMEOUT_MINUTES) {
                ActivityLog::record('login', 'auto_logout', 'Session timed out after 30 minutes of inactivity');

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'You were logged out due to 30 minutes of inactivity.']);
            }

            $request->session()->put('last_activity', now());
        }

        return $next($request);
    }
}
