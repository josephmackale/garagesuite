{{-- resources/views/auth/register-complete.blade.php --}}
<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Complete registration</h1>
        <p class="text-sm text-gray-600 mt-1">
            Phone verified for <span class="font-medium">{{ $pending->garage_name }}</span>
            ({{ $pending->phone }}). Now finish setting up your account.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('register.complete.store') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="garage_address" value="Garage Address (optional)" />
            <x-text-input
                id="garage_address"
                name="garage_address"
                type="text"
                class="mt-1 block w-full"
                value="{{ old('garage_address') }}"
                placeholder="e.g. Ngong Road, Nairobi"
            />
            <x-input-error :messages="$errors->get('garage_address')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email Address" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-1 block w-full"
                value="{{ old('email') }}"
                required
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                class="mt-1 block w-full"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm Password" />
            <x-text-input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="mt-1 block w-full"
                required
                autocomplete="new-password"
            />
        </div>

        <div class="flex items-center justify-end">
            <x-primary-button>
                Create Account
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
