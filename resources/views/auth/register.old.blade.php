{{-- resources/views/auth/register.blade.php --}}
<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-6" id="register-form">
        @csrf

        {{-- ========================= --}}
        {{--   GARAGE DETAILS          --}}
        {{-- ========================= --}}
        <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Garage Details</h2>

            <div class="space-y-4">
                {{-- Garage Name --}}
                <div>
                    <x-input-label for="garage_name" value="Garage Name" />
                    <x-text-input id="garage_name" class="block mt-1 w-full"
                                  type="text" name="garage_name"
                                  :value="old('garage_name')" required autofocus />
                    <x-input-error :messages="$errors->get('garage_name')" class="mt-2" />
                </div>

                {{-- Garage Address --}}
                <div>
                    <x-input-label for="garage_address" value="Garage Address" />
                    <x-text-input id="garage_address" class="block mt-1 w-full"
                                  type="text" name="garage_address"
                                  :value="old('garage_address')" />
                    <x-input-error :messages="$errors->get('garage_address')" class="mt-2" />
                </div>

                {{-- Mobile Phone (mandatory, for payments & reminders) --}}
                <div>
                    <x-input-label for="phone_input" value="Mobile Phone (for payments & reminders)" />

                    <input
                        id="phone_input"
                        type="tel"
                        class="block w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 mt-1"
                        placeholder="e.g. 0712 345 678"
                        required
                    >

                    {{-- Hidden fields that actually submit --}}
                    <input type="hidden" name="country_code" id="country_code" value="{{ old('country_code') }}">
                    <input type="hidden" name="phone" id="phone" value="{{ old('phone') }}">

                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    <x-input-error :messages="$errors->get('country_code')" class="mt-1" />

                    <p class="mt-1 text-xs text-gray-500">
                        We’ll use this for payments, reminders, and support. You’ll verify your account via email.
                    </p>
                </div>
            </div>
        </div>

        {{-- ========================= --}}
        {{--   ACCOUNT LOGIN DETAILS   --}}
        {{-- ========================= --}}
        <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Account Login</h2>

            <div class="space-y-4">
                {{-- Email Address --}}
                <div>
                    <x-input-label for="email" value="Email Address" />
                    <x-text-input id="email" class="block mt-1 w-full"
                                  type="email" name="email"
                                  :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                {{-- Password --}}
                <div>
                    <x-input-label for="password" value="Password" />
                    <x-text-input id="password" class="block mt-1 w-full"
                                  type="password" name="password" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                {{-- Confirm Password --}}
                <div>
                    <x-input-label for="password_confirmation" value="Confirm Password" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                  type="password" name="password_confirmation" required />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
            </div>
        </div>

        {{-- ========================= --}}
        {{--   SUBMIT                  --}}
        {{-- ========================= --}}
        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('login') }}"
               class="underline text-sm text-gray-600 hover:text-gray-900">
                Already registered?
            </a>

            <x-primary-button>
                Create account
            </x-primary-button>
        </div>
    </form>

    {{-- Intl Tel Input CSS/JS (keeps your nice phone UX, without OTP) --}}
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.21/css/intlTelInput.css"/>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.21/js/intlTelInput.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const phoneInput  = document.querySelector('#phone_input');
            const hiddenPhone = document.querySelector('#phone');
            const hiddenCode  = document.querySelector('#country_code');
            const form        = document.querySelector('#register-form');

            let iti = null;

            try {
                if (phoneInput && window.intlTelInput) {
                    iti = window.intlTelInput(phoneInput, {
                        initialCountry: "ke",
                        separateDialCode: true,
                        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.21/js/utils.min.js",
                    });
                }
            } catch (e) {
                console.error('intlTelInput init failed:', e);
            }

            if (form) {
                form.addEventListener('submit', function () {
                    // If intlTelInput is available, submit E.164 + dial code
                    if (iti) {
                        const number = iti.getNumber();
                        const countryData = iti.getSelectedCountryData();
                        if (hiddenPhone) hiddenPhone.value = number || '';
                        if (hiddenCode) hiddenCode.value = countryData?.dialCode ? ('+' + countryData.dialCode) : '';
                    } else {
                        // fallback: submit raw input (still captured)
                        if (hiddenPhone) hiddenPhone.value = phoneInput?.value || '';
                        if (hiddenCode) hiddenCode.value = '';
                    }
                });
            }
        });
    </script>

    <style>
        .iti { width: 100%; }
        .iti--allow-dropdown .iti__flag-container {
            border-radius: 0.375rem 0 0 0.375rem;
            border: 1px solid #d1d5db;
            border-right: 0;
        }
        .iti--allow-dropdown input[type="tel"],
        .iti--separate-dial-code input[type="tel"] {
            border-radius: 0 0.375rem 0.375rem 0;
            border-color: #d1d5db;
            height: 2.5rem;
        }
    </style>
</x-guest-layout>
