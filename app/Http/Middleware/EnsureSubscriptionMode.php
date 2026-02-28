<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;

class EnsureSubscriptionMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If no user, let other middleware handle it (auth etc.)
        if (! $user) {
            return $next($request);
        }

        /**
         * ✅ CRITICAL: Never block callbacks/webhooks/API that must still work while expired.
         * Adjust these patterns to match your actual callback routes.
         */
        if (
            $request->is('api/*') ||
            $request->is('payments/mpesa/*') ||
            $request->is('mpesa/*') ||
            $request->is('webhooks/*')
        ) {
            return $next($request);
        }

        // ✅ Super admin bypass (support both method + boolean column)
        $isSuperAdmin = (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || (bool) ($user->is_super_admin ?? false)
            || (($user->role ?? null) === 'super_admin');

        if ($isSuperAdmin) {
            app()->instance('subscription.mode', 'active');
            return $next($request);
        }

        /**
         * ✅ Always allow the locked page itself (avoid loops),
         * and allow billing/settings pages by path.
         */
        if ($request->routeIs('billing.locked')) {
            app()->instance('subscription.mode', 'read_only');
            return $next($request);
        }

        $garage = $user->garage;

        // If user has no garage, safest: read-only + allow only billing/settings GET; block writes.
        if (! $garage) {
            app()->instance('subscription.mode', 'read_only');

            // Allow only billing/settings pages even for GET, else redirect
            if (! ($request->is('billing*') || $request->is('settings*'))) {
                return $this->billingRedirect('Your account is not linked to a garage. Please contact support.');
            }

            // Block mutations anywhere if no garage
            if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                return $this->billingRedirect('Your account is not linked to a garage. Please contact support.');
            }

            return $next($request);
        }

        // ✅ Hard block for suspended garages
        if (($garage->status ?? null) === 'suspended') {
            app()->instance('subscription.mode', 'read_only');
            return $this->billingRedirect('This garage is suspended. Please contact admin.');
        }

        $subEndsAt   = $garage->subscription_expires_at;
        $trialEndsAt = $garage->trial_ends_at;

        $hasActiveSub   = $subEndsAt && now()->lt($subEndsAt);
        $hasActiveTrial = $trialEndsAt && now()->lt($trialEndsAt);

        // ✅ Active if either paid subscription OR trial is active
        $isActive = $hasActiveSub || $hasActiveTrial;

        app()->instance('subscription.mode', $isActive ? 'active' : 'read_only');

        // ✅ If active, proceed normally
        if ($isActive) {
            return $next($request);
        }

        // ✅ Phase 1 enforcement: If inactive, ONLY allow Billing + Settings
        $routeName = optional($request->route())->getName();

        $allowedWhenInactive = [
            // Billing
            'billing.index',
            'billing.locked',

            // Settings hub + branding + profile + save
            'settings.home',
            'settings.branding',
            'settings.profile',
            'settings.update',
            'garage.logo.store',
            'garage.logo.destroy',

            // Preferences
            'settings.preferences',
            'settings.preferences.update',

            // Payment settings (if you place behind subscription.mode)
            'settings.payments.index',
            'settings.payments.edit',
            'settings.payments.update',
            'settings.payments.testStk',

            // Auth utility
            'logout',
        ];

        $isAllowedRouteName = $routeName && in_array($routeName, $allowedWhenInactive, true);
        $isAllowedPath = $request->is('billing*') || $request->is('settings*');

        if (! $isAllowedRouteName && ! $isAllowedPath) {
            return $this->billingRedirect('Your subscription has expired. Please renew to continue.');
        }

        /**
         * ✅ Even inside allowed zones, block "download-like" endpoints (safety)
         * (This is extra defense in case route grouping changes later)
         */
        $blockedDownloadRouteNames = [
            'jobs.job-card.download',
            'invoices.pdf',
            'invoices.receipt-pdf',
            'documents.download',
            'documents.bulk.download',
        ];

        if ($routeName && in_array($routeName, $blockedDownloadRouteNames, true)) {
            return $this->billingRedirect('Downloads are locked. Please subscribe to unlock downloads.');
        }

        return $next($request);
    }

    private function billingRedirect(string $message)
    {
        $to = Route::has('billing.locked')
            ? route('billing.locked')
            : (Route::has('billing.index') ? route('billing.index') : url('/billing'));

        return redirect($to)->with('error', $message);
    }
}
