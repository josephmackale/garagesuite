<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'garage_name' => ['required', 'string', 'max:255'],
            'garage_address' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'max:8'],

            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $garage = Garage::create([
            'name' => $request->garage_name,
            'address' => $request->garage_address,
            'phone' => $request->phone,
            'garage_code' => $this->generateGarageCode(),
            'status' => 'pending',                  // becomes active after email verification
            'subscription_expires_at' => null,      // trial starts after verification
        ]);

        $user = User::create([
            'name' => $request->garage_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'garage_id' => $garage->id,
        ]);

        event(new Registered($user));              // triggers verification email (when MustVerifyEmail enabled)

        Auth::login($user);

        return redirect()->route('dashboard');     // unverified users will be redirected to /verify-email
    }

    private function generateGarageCode(): string
    {
        do {
            $code = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 8);
        } while (Garage::where('garage_code', $code)->exists());

        return $code;
    }
}
