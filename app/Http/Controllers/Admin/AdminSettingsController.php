<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSettingsController extends Controller
{
    /**
     * Super Admin Settings page
     */
    public function index()
    {
        $appEnv = config('app.env');

        $garagesCount = \App\Models\Garage::count();
        $usersCount = \App\Models\User::count();

        // ✅ Ensure there's always 1 global row
        $sys = DB::table('system_settings')->first();
        if (!$sys) {
            DB::table('system_settings')->insert([
                'sms_driver'  => 'fake',
                'sms_config'  => json_encode([
                    'enabled' => 0,
                    'provider' => 'provider_x',
                    'sender_id' => null,
                    'base_url' => null,
                    'default_country_code' => '+254',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sys = DB::table('system_settings')->first();
        }

        $systemSmsDriver = $sys->sms_driver ?? 'fake';

        $systemSmsConfig = [];
        if (!empty($sys->sms_config)) {
            $systemSmsConfig = is_string($sys->sms_config)
                ? (json_decode($sys->sms_config, true) ?: [])
                : ($sys->sms_config ?? []);
        }

        // ✅ UI helper only (do NOT echo api_key)
        $systemSmsConfig['api_key_present'] = !empty($systemSmsConfig['api_key']);

        // ✅ Admin page is GLOBAL. Don't assume admin has a garage.
        // If you later add a "Select Garage" dropdown, you can pass $garage here.
        $garage = null;

        return view('admin.settings.index', compact(
            'appEnv',
            'garagesCount',
            'usersCount',
            'systemSmsDriver',
            'systemSmsConfig',
            'garage',
        ));
    }

    /**
     * Save global system-owned SMS provider settings (Super Admin only)
     */
    public function updateSms(Request $request)
    {
        $request->validate([
            'provider'              => ['nullable', 'string', 'max:255'],
            'sender_id'             => ['nullable', 'string', 'max:255'],
            'base_url'              => ['nullable', 'string', 'max:255'],
            'default_country_code'  => ['nullable', 'string', 'max:10'],
            'enabled'               => ['nullable', 'boolean'],
            'api_key'               => ['nullable', 'string', 'max:255'],
        ]);

        // ✅ Ensure 1 global row
        $sys = DB::table('system_settings')->first();
        if (!$sys) {
            DB::table('system_settings')->insert([
                'sms_driver'  => 'fake',
                'sms_config'  => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sys = DB::table('system_settings')->first();
        }

        $existing = [];
        if (!empty($sys->sms_config)) {
            $existing = is_string($sys->sms_config)
                ? (json_decode($sys->sms_config, true) ?: [])
                : ($sys->sms_config ?? []);
        }

        $new = array_merge($existing, [
            'provider' => $request->input('provider', $existing['provider'] ?? 'provider_x'),
            'sender_id' => $request->input('sender_id', $existing['sender_id'] ?? null),
            'base_url' => $request->input('base_url', $existing['base_url'] ?? null),
            'default_country_code' => $request->input('default_country_code', $existing['default_country_code'] ?? '+254'),
            'enabled' => (bool) $request->boolean('enabled'),
        ]);

        // ✅ keep old api_key if blank
        if ($request->filled('api_key')) {
            // later: encrypt (Crypt::encryptString)
            $new['api_key'] = $request->input('api_key');
        }

        DB::table('system_settings')->where('id', $sys->id)->update([
            'sms_driver' => 'system',
            'sms_config' => json_encode($new),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Global SMS settings saved.');
    }
}
