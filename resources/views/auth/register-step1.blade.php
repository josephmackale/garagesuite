{{-- resources/views/auth/register-step1.blade.php --}}
<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Create your Garage</h1>
        <p class="text-sm text-gray-600 mt-1">
            Start with your garage name and mobile number. We’ll verify via SMS OTP.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800 border border-green-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 border border-red-200">
            Please fix the errors below and try again.
        </div>
    @endif

    <form method="POST" action="{{ route('register.step1') }}" class="space-y-5">
        @csrf

        {{-- Garage Name --}}
        <div>
            <x-input-label for="garage_name" value="Garage Name" />
            <x-text-input
                id="garage_name"
                name="garage_name"
                type="text"
                class="mt-1 block w-full"
                value="{{ old('garage_name') }}"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('garage_name')" class="mt-2" />
        </div>

        {{-- Mobile Number (intl-tel-input) --}}
        <div>
            <x-input-label value="Mobile Number" />

            <div class="mt-1">
                <input
                    id="phone_input"
                    type="tel"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="712345678"
                    autocomplete="tel"
                    inputmode="tel"
                    required
                />
            </div>

            {{-- Hidden value submitted to Laravel --}}
            <input type="hidden" name="phone_e164" id="phone_e164" value="{{ old('phone_e164') }}">

            <p class="mt-2 text-xs text-gray-500">
                We’ll send a 6-digit OTP to this number.
            </p>

            <x-input-error :messages="$errors->get('phone_e164')" class="mt-2" />
        </div>

        {{-- Submit --}}
        <div class="pt-2 flex items-center justify-end">
            <x-primary-button class="px-6">
                Send OTP
            </x-primary-button>
        </div>

        <div class="pt-4 border-t border-gray-100 text-sm text-gray-600">
            Already have an account?
            <a class="underline" href="{{ route('login') }}">Log in</a>
        </div>
    </form>

    {{-- Full-width intl-tel-input polish --}}
    <style>
        /* Ensure intl-tel-input spans full width */
        .iti {
            width: 100%;
        }

        /* Proper padding so text does not collide with flag */
        .iti input {
            width: 100%;
            padding-left: 3.75rem !important; /* space for flag + arrow */
            height: 2.75rem;                 /* match Breeze input height */
        }

        /* Position flag nicely inside the input */
        .iti__flag-container {
            left: 0.75rem;
        }

        /* Center flag vertically */
        .iti__selected-flag {
            display: flex;
            align-items: center;
        }
    </style>

</x-guest-layout>
