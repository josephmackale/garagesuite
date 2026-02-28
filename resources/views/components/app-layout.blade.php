{{-- resources/views/components/app-layout.blade.php --}}
<div class="min-h-screen bg-gray-100 flex overflow-hidden">
    <div class="bg-red-600 text-white text-center py-1">APP LAYOUT LOADED</div>

    {{-- Sidebar: DESKTOP only (phone + tablet use bottom nav) --}}
    @auth
        <div class="hidden lg:flex">
            @include('layouts.sidebar')
        </div>
    @endauth

    {{-- Right side: topbar + page content --}}
    <div class="flex-1 flex flex-col max-h-screen overflow-hidden">

        {{-- Top navigation --}}
        @include('layouts.navigation')

        {{-- Page Heading --}}
        @if (isset($header))
            <header class="bg-gray-50 border-b">
                {{-- ✅ Keep header full-width background, but box the content (match page content width) --}}
                <div class="py-4">
                    <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-10">
                        {{ $header }}
                    </div>
                </div>
            </header>
        @endif

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto">
            {{-- ✅ bottom padding on phone+tablet so content doesn’t sit under bottom nav --}}
            <div class="pb-24 lg:pb-8">
                {{-- ✅ THIS is what stops full-width --}}
                <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-10 py-6">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    {{-- Mobile/Tablet Bottom Nav --}}
    @auth
        @include('layouts.partials.mobile-bottom-nav')
    @endauth

    {{-- Global Back-to-Top Button --}}
    <button
        id="backToTopBtn"
        type="button"
        class="fixed z-50 rounded-full shadow-lg border bg-white
            bottom-6 right-6 h-11 w-11 hidden
            flex items-center justify-center
            text-gray-700 hover:text-indigo-600
            dark:bg-slate-900 dark:border-slate-700"
        aria-label="Back to top"
    >
        <x-lucide-chevron-up class="w-5 h-5" />
    </button>

    <script>
    (function () {
        const btn = document.getElementById('backToTopBtn');
        if (!btn) return;

        function toggle() {
            if (window.scrollY > 320) {
                btn.classList.remove('hidden');
            } else {
                btn.classList.add('hidden');
            }
        }

        toggle();
        window.addEventListener('scroll', toggle, { passive: true });

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    })();
    </script>

</div>
