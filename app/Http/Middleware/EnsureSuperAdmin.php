<?php

// app/Http/Middleware/EnsureSuperAdmin.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user || ($user->role ?? null) !== 'super_admin') {
            abort(403);
        }

        return $next($request);
    }
}
