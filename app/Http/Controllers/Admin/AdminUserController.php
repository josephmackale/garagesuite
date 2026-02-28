<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Garage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Support\Activity;

class AdminUserController extends Controller
{
    /**
     * List all users (Super Admin).
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $users = User::query()
            ->with(['garage:id,name,garage_code'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%");
                })
                ->orWhereHas('garage', function ($gq) use ($q) {
                    $gq->where('name', 'like', "%{$q}%")
                       ->orWhere('garage_code', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'q'     => $q,
        ]);
    }

    /**
     * Show a single user (Super Admin).
     */
    public function show(User $user)
    {
        $user->load('garage');

        $garages = Garage::orderBy('name')->get(['id', 'name']);

        return view('admin.users.show', [
            'user'    => $user,
            'garages' => $garages,
        ]);
    }

    /**
     * Suspend a user.
     */
    public function suspend(User $user)
    {
        $me = Auth::user();

        abort_if($user->id === $me->id, 422, "You can't suspend yourself.");
        abort_if(($user->role ?? null) === 'super_admin', 422, "You can't suspend a super admin.");

        $user->update([
            'status'       => 'suspended',
            'suspended_at' => now(),
        ]);

        Activity::log('user.suspend', $user);

        return back()->with('success', 'User suspended.');
    }

    /**
     * Activate a user.
     */
    public function activate(User $user)
    {
        $user->update([
            'status'       => 'active',
            'suspended_at' => null,
        ]);

        Activity::log('user.activate', $user);

        return back()->with('success', 'User activated.');
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, User $user)
    {
        abort_if($user->id === Auth::id(), 422, "You can't change your own role.");
        abort_if(($user->role ?? null) === 'super_admin', 422, "Can't change a super admin role.");

        $data = $request->validate([
			'role' => ['required', Rule::in(['staff', 'garage_admin', 'garage_owner', 'super_admin'])],
        ]);

        $old = $user->role;

        $user->update([
            'role' => $data['role'],
        ]);

        Activity::log('user.role_updated', $user, [
            'from' => $old,
            'to'   => $data['role'],
        ]);

        return back()->with('success', 'Role updated.');
    }

    /**
     * Move user to another garage.
     */
    public function updateGarage(Request $request, User $user)
    {
        abort_if(($user->role ?? null) === 'super_admin', 422, "Super admin doesn't belong to a garage.");
        abort_if(($user->role ?? null) === 'garage_owner', 422, "Assign a new owner before moving.");

        $data = $request->validate([
            'garage_id' => ['required', Rule::exists('garages', 'id')],
        ]);

        $from = $user->garage_id;

        $user->update([
            'garage_id' => $data['garage_id'],
        ]);

        Activity::log('user.garage_moved', $user, [
            'from' => $from,
            'to'   => $data['garage_id'],
        ]);

        return back()->with('success', 'Garage updated.');
    }
}
