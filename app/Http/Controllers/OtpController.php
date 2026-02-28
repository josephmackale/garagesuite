<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class OtpController extends Controller
{
    /**
     * Send OTP to phone.
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:30'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $phone = $request->input('phone');

        // Generate a 6-digit OTP
        $code = random_int(100000, 999999);

        // Store OTP (latest one overwrites in practice)
        $otp = Otp::create([
            'phone'      => $phone,
            'code'       => $code,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // 🔔 TODO: Integrate your SMS provider here
        // Example: call HostPinnacle/your SMS API instead of just logging.
        Log::info('OTP sent', ['phone' => $phone, 'code' => $code]);

        // Reset any previous verification in session
        session()->forget(['otp_verified', 'otp_verified_phone']);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
        ]);
    }

    /**
     * Verify OTP.
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:30'],
            'otp'   => ['required', 'string', 'max:10'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'verified' => false,
                'message'  => 'Invalid data.',
                'errors'   => $validator->errors(),
            ], 422);
        }

        $phone = $request->input('phone');
        $code  = $request->input('otp');

        /** @var Otp|null $otp */
        $otp = Otp::where('phone', $phone)
            ->latest()
            ->first();

        if (! $otp) {
            return response()->json([
                'verified' => false,
                'message'  => 'No OTP found for this phone number. Please request a new one.',
            ], 404);
        }

        if ($otp->isExpired()) {
            return response()->json([
                'verified' => false,
                'message'  => 'The OTP has expired. Please request a new one.',
            ], 422);
        }

        if ($otp->attempts >= 5) {
            return response()->json([
                'verified' => false,
                'message'  => 'Too many incorrect attempts. Please request a new OTP.',
            ], 429);
        }

        // Increase attempts
        $otp->increment('attempts');

        if ($otp->code !== $code) {
            return response()->json([
                'verified' => false,
                'message'  => 'Incorrect OTP. Please try again.',
            ], 422);
        }

        // Mark verified
        $otp->update([
            'verified_at' => now(),
        ]);

        // Save into session for registration step
        session([
            'otp_verified'       => true,
            'otp_verified_phone' => $phone,
        ]);

        return response()->json([
            'verified' => true,
            'message'  => 'Phone verified successfully.',
        ]);
    }
}
