<?php

// app/Http/Middleware/EnsureUserNotSuspended.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserNotSuspended
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'isSuspended') && $user->isSuspended()) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is suspended. Contact support.',
            ]);
        }

        return $next($request);
    }
}

