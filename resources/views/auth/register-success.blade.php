<x-guest-layout>

    <div class="bg-white shadow-md rounded-2xl p-10 text-center">

        <h1 class="text-xl font-semibold text-gray-900">
            Garage registration received
        </h1>

        <!-- Blue Tick Icon -->
        <div class="flex justify-center mt-2 mb-6">
            <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <p class="text-gray-600 text-sm leading-relaxed mb-4">
            Thank you for submitting your details. Your garage account is being set up.
        </p>

        <p class="text-gray-600 text-sm leading-relaxed">
            Once everything is ready, login details will be sent to the email address you provided.
            You'll then be able to sign in and start using GarageSuite.
        </p>

        <a href="{{ route('login') }}"
           class="inline-flex items-center justify-center mt-6 px-5 py-2 rounded-full bg-gray-900 text-white text-sm font-medium hover:bg-black">
            Go to login page
        </a>
    </div>
</x-guest-layout>

