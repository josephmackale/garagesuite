<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Models\Garage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PhoneRegisterController extends Controller
{
    public function step1Form()
    {
        return view('auth.register-step1');
    }

    public function step1SendOtp(Request $request)
    {
        $data = $request->validate([
            'garage_name'  => ['required', 'string', 'max:120'],
            'country_code' => ['required', 'digits_between:1,4'],
            'phone'        => ['required', 'digits_between:6,12'],
        ]);

        $phone = $this->normalizeInternationalPhone(
            $data['country_code'],
            $data['phone']
        );

        // throttle resend: 60s
        $last = PendingRegistration::where('phone', $phone)->latest()->first();
        if ($last && $last->otp_last_sent_at && $last->otp_last_sent_at->gt(now()->subSeconds(60))) {
            return back()->withErrors(['phone' => 'Please wait 1 minute before requesting another code.'])->withInput();
        }

        $otp = random_int(100000, 999999);

        $pending = PendingRegistration::create([
            'garage_name'      => $data['garage_name'],
            'phone'            => $phone,
            'otp_code_hash'    => Hash::make((string) $otp),
            'otp_expires_at'   => now()->addMinutes(10),
            'otp_attempts'     => 0,
            'otp_last_sent_at' => now(),
        ]);

        session(['pending_registration_id' => $pending->id]);

        // TODO: Replace with your real SMS provider call
        $this->sendSms($phone, "GarageSuite OTP: {$otp}. Expires in 10 minutes.");

        return redirect()
            ->route('register.otp.form')
            ->with('status', 'OTP sent. Enter the code to continue.');
    }

    public function otpForm()
    {
        $pending = $this->getPendingOrAbort();

        if ($pending->isVerified()) {
            return redirect()->route('register.complete.form');
        }

        return view('auth.register-otp', compact('pending'));
    }

    public function verifyOtp(Request $request)
    {
        $pending = $this->getPendingOrAbort();

        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        if (! $pending->otp_expires_at || now()->gt($pending->otp_expires_at)) {
            return back()->withErrors(['otp' => 'OTP expired. Please go back and request a new one.']);
        }

        if ($pending->otp_attempts >= 5) {
            return back()->withErrors(['otp' => 'Too many attempts. Please request a new OTP.']);
        }

        $pending->increment('otp_attempts');

        if (! Hash::check($request->otp, (string) $pending->otp_code_hash)) {
            return back()->withErrors(['otp' => 'Invalid OTP code.']);
        }

        $pending->update([
            'phone_verified_at' => now(),
            'otp_code_hash'     => null,
            'otp_expires_at'    => null,
        ]);

        return redirect()
            ->route('register.complete.form')
            ->with('status', 'Phone verified. Continue registration.');
    }

    public function completeForm()
    {
        $pending = $this->getPendingOrAbort();

        if (! $pending->isVerified()) {
            return redirect()->route('register.otp.form');
        }

        return view('auth.register-complete', compact('pending'));
    }

    public function completeStore(Request $request)
    {
        $pending = $this->getPendingOrAbort();

        if (! $pending->isVerified()) {
            return redirect()->route('register.otp.form');
        }

        $data = $request->validate([
            'garage_address' => ['nullable', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'       => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = null;

        DB::transaction(function () use ($pending, $data, &$user) {

            $trialDays = 7; // ✅ change to 14 if you want

            $garage = Garage::create([
                'name'       => $pending->garage_name,
                'garage_code'=> $this->generateGarageCode(),
                'phone'      => $pending->phone,
                'email'      => null,
                'address'    => $data['garage_address'] ?? null,
                'city'       => null,
                'country'    => 'Kenya',

                // ✅ Trial / subscription baseline
                'status'                 => 'pending', // pending = trial
                'trial_ends_at'          => now()->addDays($trialDays),
                'subscription_expires_at'=> null,
                'payment_methods'        => null,
            ]);

            $user = User::create([
                'name'              => $pending->garage_name,
                'email'             => $data['email'],
                'phone'             => $pending->phone,
                'phone_verified_at' => $pending->phone_verified_at,
                'password'          => Hash::make($data['password']),
                'garage_id'         => $garage->id,

                // ✅ keep roles consistent with your admin-created garages
                'role'              => 'garage_owner',
                'is_super_admin'    => false,
            ]);

            $pending->delete();
            session()->forget('pending_registration_id');
        });

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    private function getPendingOrAbort(): PendingRegistration
    {
        $id = session('pending_registration_id');

        abort_if(! $id, 419, 'Registration session expired. Please start again.');

        $pending = PendingRegistration::find($id);
        abort_if(! $pending, 419, 'Registration session expired. Please start again.');

        return $pending;
    }

    private function sendSms(string $phone, string $message): void
    {
        // TEMP: log the OTP so you can test immediately
        logger()->info("SMS to {$phone}: {$message}");

        // TODO: Replace with real API call
    }

    private function normalizeInternationalPhone(string $countryCode, string $local): string
    {
        $local = preg_replace('/\D+/', '', $local);

        // Remove leading zero if user typed it
        if (str_starts_with($local, '0')) {
            $local = substr($local, 1);
        }

        return $countryCode . $local; // e.g. 254712345678
    }

    private function generateGarageCode(): string
    {
        do {
            $code = Str::random(random_int(6, 8));
        } while (Garage::where('garage_code', $code)->exists());

        return $code;
    }
}
