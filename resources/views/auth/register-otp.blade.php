{{-- resources/views/auth/register-otp.blade.php --}}
<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Verify your mobile number</h1>
        <p class="text-sm text-gray-600 mt-1">
            Enter the 6-digit code sent to <span class="font-medium">{{ $pending->phone }}</span>.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('register.otp.verify') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="otp" value="OTP Code" />
            <x-text-input
                id="otp"
                name="otp"
                type="text"
                class="mt-1 block w-full"
                inputmode="numeric"
                autocomplete="one-time-code"
                placeholder="123456"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">OTP expires in 10 minutes.</p>
        </div>

        <div class="flex items-center justify-between">
            <a class="text-sm text-gray-600 underline" href="{{ route('register') }}">
                Change number
            </a>

            <x-primary-button>
                Verify & Continue
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
