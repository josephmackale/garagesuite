<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after login.
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Route model binding for 'job'
        Route::model('job', \App\Models\Job::class);
        
        $this->routes(function () {

            /*
            |--------------------------------------------------------------------------
            | API Routes
            |--------------------------------------------------------------------------
            |
            | These routes are loaded with the "api" middleware group and are prefixed
            | with /api. They are stateless and ideal for integrations, mobile apps,
            | and webhooks (like M-Pesa callbacks).
            |
            */

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));


            /*
            |--------------------------------------------------------------------------
            | Web Routes
            |--------------------------------------------------------------------------
            |
            | These routes are loaded with the "web" middleware group and receive
            | session state, CSRF protection, etc.
            |
            */

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {

            // Authenticated users: higher limits
            if ($request->user()) {
                return Limit::perMinute(120)->by($request->user()->id);
            }

            // Guests: IP based
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
