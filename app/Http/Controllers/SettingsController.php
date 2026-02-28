<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Insurer;
use App\Models\GarageLegalDocument;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * ✅ Settings Home (Hub)
     * GET /settings  (route: settings.home)
     */
    public function home()
    {
        $user = Auth::user();

        // ✅ SUPER ADMIN REDIRECT (keep consistent with your app)
        $isSuperAdmin =
            (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) ||
            (property_exists($user, 'is_super_admin') && (bool) $user->is_super_admin) ||
            (property_exists($user, 'role') && $user->role === 'super_admin');

        if ($isSuperAdmin) {
            return redirect()->route('admin.settings.index');
        }

        return view('settings.home');
    }

    /**
     * ✅ Branding Settings (your existing settings.home blade for now)
     * GET /settings/branding (route: settings.branding)
     * supports ?tab=branding|garage|billing (etc)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $tab = $request->query('tab', 'branding');
        $garage = $user?->garage;

        return view('settings.home', compact('tab', 'garage', 'user'));
    }



    /**
     * ✅ Preferences (includes Insurance settings under Preferences)
     * GET /settings/preferences  (route: settings.preferences)
     */
    public function preferences(Request $request)
    {
        $user = $request->user();

        $isSuperAdmin =
            (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) ||
            (property_exists($user, 'is_super_admin') && (bool) $user->is_super_admin) ||
            (property_exists($user, 'role') && $user->role === 'super_admin');

        if ($isSuperAdmin) {
            return redirect()->route('admin.settings.index');
        }

        $garage = $user?->garage;
        if (! $garage) {
            abort(403, 'No garage workspace assigned.');
        }

        // --------------------------------------------
        // Preferences row (safe if table doesn't exist)
        // --------------------------------------------
        $prefsRow = null;
        try {
            $prefsRow = DB::table('garage_preferences')
                ->where('garage_id', $garage->id)
                ->first();
        } catch (\Throwable $e) {
            $prefsRow = null;
        }

        // Default prefs (safe)
        $prefs = (object) [
            'currency'                     => $prefsRow->currency ?? 'KES',
            'tax_rate'                     => $prefsRow->tax_rate ?? 0,

            // Document prefs (if you later store them)
            'invoice_numbering'            => $prefsRow->invoice_numbering ?? 'auto',
            'date_format'                  => $prefsRow->date_format ?? 'd/m/Y',

            // Insurance settings under Preferences
            'insurance_require_inspection' => (int) ($prefsRow->insurance_require_inspection ?? 1),
            'insurance_require_approval'   => (int) ($prefsRow->insurance_require_approval ?? 1),
            'insurance_default_payer'      => $prefsRow->insurance_default_payer ?? 'insurer',
            'insurance_lock_repair'        => (int) ($prefsRow->insurance_lock_repair ?? 1),
        ];

        // --------------------------------------------
        // Insurers list (uses your existing model)
        // IMPORTANT: your model uses is_active, not active
        // --------------------------------------------
        try {
            $insurers = \App\Models\Insurer::query()
                ->where('garage_id', $garage->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } catch (\Throwable $e) {
            // If table/model isn't ready yet, don't crash preferences page
            $insurers = collect();
        }

        return view('settings.preferences', compact('user', 'garage', 'prefs', 'insurers'));
    }


    /**
     * ✅ Save Preferences
     * POST /settings/preferences (route: settings.preferences.update)
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $isSuperAdmin =
            (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) ||
            (property_exists($user, 'is_super_admin') && (bool) $user->is_super_admin) ||
            (property_exists($user, 'role') && $user->role === 'super_admin');

        if ($isSuperAdmin) {
            return redirect()->route('admin.settings.index');
        }

        $garage = $user?->garage;
        if (! $garage) {
            abort(403, 'No garage workspace assigned.');
        }

        $validated = $request->validate([
            // core prefs (keep minimal now)
            'currency' => ['nullable', 'string', 'max:10'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // insurance prefs
            'insurance_require_inspection' => ['nullable', Rule::in(['0', '1', 0, 1])],
            'insurance_require_approval'   => ['nullable', Rule::in(['0', '1', 0, 1])],
            'insurance_default_payer'      => ['nullable', Rule::in(['insurer', 'customer', 'split'])],
            'insurance_lock_repair'        => ['nullable', Rule::in(['0', '1', 0, 1])],
        ]);

        // Normalize booleans
        $data = [
            'garage_id'                    => $garage->id,
            'currency'                     => $validated['currency'] ?? 'KES',
            'tax_rate'                     => $validated['tax_rate'] ?? 0,

            'insurance_require_inspection' => (int)($validated['insurance_require_inspection'] ?? 1),
            'insurance_require_approval'   => (int)($validated['insurance_require_approval'] ?? 1),
            'insurance_default_payer'      => $validated['insurance_default_payer'] ?? 'insurer',
            'insurance_lock_repair'        => (int)($validated['insurance_lock_repair'] ?? 1),

            'updated_at'                   => now(),
        ];

        // Upsert (safe)
        try {
            DB::table('garage_preferences')->updateOrInsert(
                ['garage_id' => $garage->id],
                array_merge($data, ['created_at' => now()])
            );
        } catch (\Throwable $e) {
            // If table doesn’t exist yet, don’t crash the app
            return redirect()
                ->route('settings.preferences')
                ->with('error', 'Preferences table not found yet. Create garage_preferences migration first.');
        }

        return redirect()
            ->route('settings.preferences')
            ->with('success', 'Preferences updated successfully.');
    }

    /**
     * ✅ Update Garage Profile + Payment Methods
     * POST /settings (route: settings.update)
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // ✅ SUPER ADMIN REDIRECT (avoid super admin posting here)
        $isSuperAdmin =
            (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) ||
            (property_exists($user, 'is_super_admin') && (bool) $user->is_super_admin) ||
            (property_exists($user, 'role') && $user->role === 'super_admin');

        if ($isSuperAdmin) {
            return redirect()->route('admin.settings.index');
        }

        $garage = $user->garage;

        if (! $garage) {
            abort(403, 'No garage workspace assigned.');
        }

        $validated = $request->validate([
            // Garage profile
            'name'    => ['required', 'string', 'max:120'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'email'   => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city'    => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'status'  => ['nullable', Rule::in(['active', 'inactive'])],

            // Payment methods (JSON)
            'payment_methods.mpesa.type'    => ['nullable', Rule::in(['paybill', 'till'])],
            'payment_methods.mpesa.number'  => ['nullable', 'string', 'max:30'],
            'payment_methods.mpesa.account' => ['nullable', 'string', 'max:60'],

            'payment_methods.bank.bank_name'      => ['nullable', 'string', 'max:120'],
            'payment_methods.bank.account_name'   => ['nullable', 'string', 'max:120'],
            'payment_methods.bank.account_number' => ['nullable', 'string', 'max:60'],
        ]);

        $garage->update([
            'name'            => $validated['name'],
            'phone'           => $validated['phone']   ?? null,
            'email'           => $validated['email']   ?? null,
            'address'         => $validated['address'] ?? null,
            'city'            => $validated['city']    ?? null,
            'country'         => $validated['country'] ?? null,
            'status'          => $validated['status']  ?? $garage->status,

            // Persist payment methods cleanly
            'payment_methods' => $validated['payment_methods'] ?? [],
        ]);

        return redirect()
            ->route('settings.branding', ['tab' => 'garage'])
            ->with('success', 'Garage profile updated successfully.');
    }

    private function allowedLegalDocTypes(): array
    {
        return [
            'certificate_of_incorporation',
            'company_registration_certificate',
            'kra_pin_certificate',
            'tax_compliance_certificate', // optional
        ];
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        // super admin guard (keep yours if you have it)
        if (($user->is_super_admin ?? false) || (($user->role ?? null) === 'super_admin')) {
            return redirect()->route('admin.settings.index');
        }

        $garageId = (int) ($user->garage_id ?? 0);
        abort_unless($garageId, 403, 'No garage workspace assigned.');

        $documents = GarageLegalDocument::query()
            ->where('garage_id', $garageId)
            ->get()
            ->keyBy('doc_type');

        return view('settings.profile', [
            'user' => $user,
            'garageId' => $garageId,
            'documents' => $documents,
            'docTypes' => $this->allowedLegalDocTypes(),
        ]);
    }

    public function uploadLegalDocument(Request $request, string $docType)
    {
        $user = $request->user();
        $garageId = (int) ($user->garage_id ?? 0);
        abort_unless($garageId, 403, 'No garage workspace assigned.');

        abort_unless(in_array($docType, $this->allowedLegalDocTypes(), true), 404);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'], // 10MB v1
        ]);

        $file = $data['file'];

        $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');

        // deterministic name per doc type
        $filename = $docType . '.' . $ext;
        $path = "garages/{$garageId}/legal/{$filename}";

        // delete old file if exists in DB
        $existing = GarageLegalDocument::query()
            ->where('garage_id', $garageId)
            ->where('doc_type', $docType)
            ->first();

        if ($existing && $existing->path && Storage::disk('local')->exists($existing->path)) {
            Storage::disk('local')->delete($existing->path);
        }

        // store new
        Storage::disk('local')->putFileAs("garages/{$garageId}/legal", $file, $filename);

        GarageLegalDocument::updateOrCreate(
            ['garage_id' => $garageId, 'doc_type' => $docType],
            [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => (int) $file->getSize(),
                'uploaded_by' => $user->id,
                'uploaded_at' => now(),
            ]
        );

        return redirect()->route('settings.profile')->with('success', 'Document uploaded.');
    }

    public function deleteLegalDocument(Request $request, string $docType)
    {
        $user = $request->user();
        $garageId = (int) ($user->garage_id ?? 0);
        abort_unless($garageId, 403, 'No garage workspace assigned.');

        abort_unless(in_array($docType, $this->allowedLegalDocTypes(), true), 404);

        $existing = GarageLegalDocument::query()
            ->where('garage_id', $garageId)
            ->where('doc_type', $docType)
            ->first();

        if ($existing) {
            if ($existing->path && Storage::disk('local')->exists($existing->path)) {
                Storage::disk('local')->delete($existing->path);
            }
            $existing->delete();
        }

        return redirect()->route('settings.profile')->with('success', 'Document removed.');
    }
}
