<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'GarageSuite') }}</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/branding/favicon/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/branding/favicon/favicon-16x16.png') }}">


        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
            <div class="w-full max-w-md">

                <!-- Logo -->
                <div class="mb-8 text-center">
                    <a href="/">
                        <img
                            src="{{ asset('assets/branding/icon/garagesuite-icon-128.png') }}"
                            alt="GarageSuite"
                            class="mx-auto h-24 w-24"
                        >
                    </a>
                </div>

                <!-- Auth Card -->
                <div class="bg-white shadow-md overflow-hidden sm:rounded-lg px-6 py-6">
                    {{ $slot }}
                </div>

            </div>
        </div>
    </body>
</html>
