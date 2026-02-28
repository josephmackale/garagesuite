<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->is_super_admin) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
