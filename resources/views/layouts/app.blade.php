{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'GarageSuite') }}</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/branding/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/branding/favicon/favicon-16x16.png') }}">

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4f46e5">

    {{-- Optional (nice on iOS) --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes"> {{-- ✅ new standard meta --}}
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="GarageSuite">

    {{-- ✅ CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" href="{{ asset('assets/branding/icon/garagesuite-icon-192.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Prevent Alpine flashes --}}
    <style>[x-cloak]{display:none !important;}</style>

    {{-- Apply stored theme BEFORE paint (prevents white flash) --}}
    <script>
        (function () {
            try {
                const theme = localStorage.getItem('gs_theme');
                const dark = theme === 'dark';
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans antialiased bg-gray-100 text-gray-900 dark:bg-slate-950 dark:text-slate-100">
    @if(session()->has('impersonator_id'))
        <div class="bg-amber-50 border-b border-amber-200 text-amber-900 px-4 py-3 text-sm flex items-center justify-between">
            <div>
                <span class="font-semibold">Impersonation active:</span>
                You are logged in as {{ auth()->user()->name }}.
            </div>

            <form method="POST" action="{{ route('admin.impersonate.stop') }}">
                @csrf
                <button type="submit" class="px-3 py-1 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700">
                    Exit Impersonation
                </button>
            </form>
        </div>
    @endif


    <div class="min-h-screen flex overflow-hidden bg-gray-100 dark:bg-slate-950">
        {{-- Sidebar: DESKTOP only (tablet/phone will use bottom nav) --}}
        @auth
            <div class="hidden lg:flex">
                @include('layouts.sidebar')
            </div>
        @endauth

        {{-- Right side: topbar + page content --}}
        <div class="flex-1 min-w-0 flex flex-col max-h-screen overflow-hidden bg-gray-100 dark:bg-slate-950">
            {{-- Topbar --}}
            @include('layouts.navigation')

            {{-- Page Heading --}}
            @if (isset($header))
                <header class="bg-gray-50 border-b border-gray-200 dark:bg-slate-950 dark:border-slate-800">
                    {{-- full-width background, boxed content --}}
                    {{-- was: py-4 --}}
                    <div class="py-2">
                        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-10">
                            {{ $header }}
                        </div>
                    </div>
                </header>
            @endif

            {{-- Page Content --}}
            <main class="flex-1 min-w-0 overflow-y-auto bg-gray-100 dark:bg-slate-950">
                {{-- boxed content + bottom padding for mobile bottom nav --}}
                <div class="pb-24 lg:pb-8">
                    {{-- was: py-6 --}}
                    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-10 py-3">
                        <x-subscription-banner />
                        {{-- was: mt-3 sm:mt-4 lg:mt-5 --}}
                        <div class="mt-2 sm:mt-2 lg:mt-3">
                            {{ $slot ?? '' }}
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    {{-- Mobile/Tablet Bottom Nav --}}
    @auth
        @include('layouts.partials.mobile-bottom-nav')
    @endauth

    {{-- ✅ THIS is what you were missing (must be BEFORE </body>) --}}
    @stack('modals')
    @stack('scripts')

    {{-- IMPORTANT:
         Do NOT register the service worker here.
         pwa.js handles registration + update events (gs-pwa-update-available).
    --}}
</body>
</html>
