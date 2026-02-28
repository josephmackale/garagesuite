<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {

        // ✅ Register middleware aliases (Laravel 12 style)
        $middleware->alias([
            'subscription.mode' => \App\Http\Middleware\EnsureSubscriptionMode::class,
            'isSuperAdmin'      => \App\Http\Middleware\IsSuperAdmin::class,
        ]);

        // ✅ CSRF configuration (Laravel 12 style)
        // If you truly want these endpoints excluded from CSRF, keep them here.
        // (Note: routes/web.php auth endpoints usually EXPECT CSRF; only exclude if you know why.)
        $middleware->validateCsrfTokens(except: [
            // ✅ Safaricom Daraja / M-PESA callbacks (adjust to your real callback paths)
            'payments/mpesa/*',
            'mpesa/*',
            'api/mpesa/*',

            // ✅ Generic webhooks (if you use them)
            'webhooks/*',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
