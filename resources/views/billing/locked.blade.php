<x-app-layout>
    <div class="py-4">

        <div class="max-w-3xl mx-auto">
            <div class="bg-white dark:bg-slate-900 shadow-sm rounded-lg border border-yellow-300/50">
                <div class="p-6 text-center">
                    <h2 class="text-2xl font-semibold mb-3">🔒 Account Locked</h2>

                    @if(session('error'))
                        <div class="mb-4 rounded-md bg-yellow-50 dark:bg-yellow-900/20 p-3 text-yellow-800 dark:text-yellow-200">
                            {{ session('error') }}
                        </div>
                    @else
                        <div class="mb-4 rounded-md bg-yellow-50 dark:bg-yellow-900/20 p-3 text-yellow-800 dark:text-yellow-200">
                            Your GarageSuite subscription is inactive.
                        </div>
                    @endif

                    <p class="text-slate-600 dark:text-slate-300 mb-6">
                        Please renew your subscription to continue using the system.
                    </p>

                    <div class="flex flex-wrap gap-2 justify-center">
                        <a href="{{ route('billing.index') }}"
                           class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            💳 Renew via M-PESA
                        </a>

                        <a href="https://wa.me/254706673337?text=Hello%20I%20need%20help%20with%20GarageSuite%20subscription"
                           target="_blank"
                           class="px-4 py-2 rounded-md border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                            💬 Contact Support
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 rounded-md border border-red-300 text-red-700 hover:bg-red-50">
                                🚪 Logout
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 text-sm text-slate-500">
                        Already paid? Contact support to confirm your payment.
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
