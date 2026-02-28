<x-guest-layout>

    <div class="mb-4 text-sm text-gray-700 leading-relaxed">
        <strong>Welcome to GarageSuite!</strong> 🎉 <br><br>

        To activate your <span class="font-semibold">7-day free trial</span> and start using your new garage account,
        please verify your email address by clicking the link we just sent to:

        <span class="block mt-2 font-medium text-gray-900">
            {{ auth()->user()->email }}
        </span>

        If the email hasn’t arrived within a minute, you can request another one below.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-emerald-600">
            A fresh verification link has been sent. Please check your inbox or spam folder.
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button>
                Resend Verification Email
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Log Out
            </button>
        </form>
    </div>

</x-guest-layout>
