<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Garage;

class AdminImpersonationController extends Controller
{
    public function start(Garage $garage, Request $request)
    {
        $admin = Auth::user();
        abort_unless($admin?->is_super_admin, 403);

        // store original admin id
        $request->session()->put('impersonator_id', $admin->id);

        // pick user that belongs to this garage
        $targetUser = User::where('garage_id', $garage->id)
            ->orderBy('id')
            ->firstOrFail();

        Auth::guard('web')->loginUsingId($targetUser->id);

        // regen AFTER login so session/auth sticks
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard')
            ->with('success', 'Now impersonating: ' . $garage->name);
    }


    


    public function stop(Request $request)
    {
        \Log::error('IMPERSONATION STOP HIT', [
            'auth_id' => Auth::id(),
            'impersonator_id' => $request->session()->get('impersonator_id'),
        ]);



        $impersonatorId = $request->session()->get('impersonator_id');

        if (!$impersonatorId) {
            return redirect()->route('dashboard');
        }

        $impersonator = User::find($impersonatorId);

        if (!$impersonator || !$impersonator->is_super_admin) {
            $request->session()->forget('impersonator_id');
            abort(403);
        }

        $request->session()->forget('impersonator_id');

        Auth::loginUsingId($impersonatorId);

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.garages.index')
            ->with('success', 'Exited impersonation and returned to admin.');
    }

}
