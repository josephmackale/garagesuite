<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\Organization;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminGarageController extends Controller
{
    public function index(Request $request)
    {
        $q = Garage::query()->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', (string) $request->string('status'));
        }

        if ($request->filled('search')) {
            $s = (string) $request->string('search');

            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('garage_code', 'like', "%{$s}%");
            });
        }

        $garages = $q->paginate(20)->withQueryString();

        return view('admin.garages.index', compact('garages'));
    }

    /**
     * We now create garages via a modal in index.blade.php.
     * Keep this route for backwards compatibility (no 500s).
     */
    public function create()
    {
        return redirect()
            ->route('admin.garages.index')
            ->with('open_create_modal', true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // Garage
            'garage_name'    => ['required', 'string', 'max:255'],
            'garage_phone'   => ['nullable', 'string', 'max:30'],
            'garage_email'   => ['nullable', 'email', 'max:255'],
            'garage_city'    => ['nullable', 'string', 'max:120'],
            'garage_address' => ['nullable', 'string', 'max:255'],

            // Trial bootstrap
            'trial_days' => ['required', 'integer', 'min:1', 'max:60'],

            // ✅ NEW: Garage type
            'garage_type' => ['required', 'in:standard,insurance'],

            // ✅ NEW: Insurance settings (optional; enforced by logic when type=insurance)
            'insurance_require_claim'    => ['nullable', 'boolean'],
            'insurance_require_assessor' => ['nullable', 'boolean'],
            'insurance_default_payer'    => ['nullable', 'in:insurance,customer,mixed'],
            'insurance_enable_widgets'   => ['nullable', 'boolean'],

            // Owner user
            'owner_name'     => ['required', 'string', 'max:255'],
            'owner_email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        // ✅ Build per-garage config (future-proof, doesn’t affect other garages)
        $type = $data['garage_type'] ?? 'standard';

        $garageConfig = [
            'type' => $type,
        ];

        if ($type === 'insurance') {
            $garageConfig['insurance'] = [
                // default ON for insurance partner
                'require_claim'    => (bool)($data['insurance_require_claim'] ?? true),
                'require_assessor' => (bool)($data['insurance_require_assessor'] ?? true),
                'default_payer'    => $data['insurance_default_payer'] ?? 'insurance',
                'widgets'          => (bool)($data['insurance_enable_widgets'] ?? true),
            ];
        }

        $garage = Garage::create([
            'name'         => $data['garage_name'],
            'garage_code'  => $this->generateGarageCode(),
            'phone'        => $data['garage_phone'] ?? null,
            'email'        => $data['garage_email'] ?? null,
            'city'         => $data['garage_city'] ?? null,
            'address'      => $data['garage_address'] ?? null,
            'country'      => 'Kenya',

            // ✅ NEW
            'garage_config' => $garageConfig,

            // Start as trial by default
            'status'                  => 'trial',
            'trial_ends_at'           => now()->addDays((int) $data['trial_days']),
            'subscription_expires_at' => null,
        ]);

        $owner = User::create([
            'name'           => $data['owner_name'],
            'email'          => $data['owner_email'],
            'password'       => Hash::make($data['owner_password']),
            'garage_id'      => $garage->id,
            'role'           => 'garage_owner',
            'is_super_admin' => 0,
        ]);

        // ✅ Audit log
        Activity::log('Created garage', $garage, [
            'garage_name'   => $garage->name,
            'garage_code'   => $garage->garage_code,
            'garage_type'   => $type,
            'trial_days'    => (int) $data['trial_days'],
            'trial_ends_at' => optional($garage->trial_ends_at)->toDateString(),
            'owner_user_id' => $owner->id,
            'owner_email'   => $owner->email,
        ]);

        return redirect()
            ->route('admin.garages.show', $garage)
            ->with('success', 'Garage created successfully. Owner can log in immediately.');
    }

    public function show(Garage $garage)
    {
        $owner = User::where('garage_id', $garage->id)->orderBy('id')->first();

        return view('admin.garages.show', compact('garage', 'owner'));
    }

    /**
     * Extend trial by N days.
     * - If trial_ends_at is null OR expired -> start from now
     * - If still active -> extend from trial_ends_at
     */
    public function extendTrial(Garage $garage, Request $request)
    {
        $data = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $days = (int) $data['days'];

        $base = ($garage->trial_ends_at && $garage->trial_ends_at->isFuture())
            ? $garage->trial_ends_at
            : now();

        $before = $garage->trial_ends_at ? $garage->trial_ends_at->copy() : null;

        $garage->status = 'trial';
        $garage->trial_ends_at = $base->copy()->addDays($days);
        $garage->save();

        Activity::log('Extended garage trial', $garage, [
            'days_added'        => $days,
            'trial_ends_before' => optional($before)->toDateString(),
            'trial_ends_after'  => optional($garage->trial_ends_at)->toDateString(),
        ]);

        return back()->with(
            'success',
            'Trial extended to ' . $garage->trial_ends_at->format('d M Y') . '.'
        );
    }

    /**
     * Activate (paid) by N days.
     */
    public function activate(Garage $garage, Request $request)
    {
        $data = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $days = (int) $data['days'];

        $base = ($garage->subscription_expires_at && $garage->subscription_expires_at->isFuture())
            ? $garage->subscription_expires_at
            : now();

        $before = $garage->subscription_expires_at ? $garage->subscription_expires_at->copy() : null;
        $trialBefore = $garage->trial_ends_at ? $garage->trial_ends_at->copy() : null;

        $garage->status = 'active';
        $garage->subscription_expires_at = $base->copy()->addDays($days);
        $garage->trial_ends_at = null; // clear trial once paid
        $garage->save();

        Activity::log('Activated garage subscription', $garage, [
            'days_added'               => $days,
            'expires_before'           => optional($before)->toDateString(),
            'expires_after'            => optional($garage->subscription_expires_at)->toDateString(),
            'trial_cleared_before_was' => optional($trialBefore)->toDateString(),
        ]);

        return back()->with(
            'success',
            'Garage activated until ' . $garage->subscription_expires_at->format('d M Y') . '.'
        );
    }

    public function suspend(Garage $garage)
    {
        $before = $garage->status;

        $garage->status = 'suspended';
        $garage->save();

        Activity::log('Suspended garage', $garage, [
            'status_before' => $before,
            'status_after'  => $garage->status,
        ]);

        return back()->with('success', 'Garage suspended.');
    }

    private function generateGarageCode(): string
    {
        do {
            $code = Str::random(random_int(6, 8));
        } while (Garage::where('garage_code', $code)->exists());

        return $code;
    }

    public function updateOrganizations(Request $request, Garage $garage)
    {
        $data = $request->validate([
            'organizations'   => 'nullable|array',
            'organizations.*' => 'exists:organizations,id',
        ]);

        // Sync selected partners
        $garage->organizations()->sync(
            $data['organizations'] ?? []
        );

        Activity::log('Updated garage organizations', $garage, [
            'organization_ids' => $data['organizations'] ?? [],
        ]);

        return back()->with('success', 'Partner organizations updated.');
    }

}
