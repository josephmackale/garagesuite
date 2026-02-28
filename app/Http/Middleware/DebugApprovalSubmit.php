<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DebugApprovalSubmit
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('jobs/*/insurance/approval/submit')) {

            // ✅ Safe session read: some requests may not have session middleware
            $sessionId = null;
            try {
                if ($request->hasSession()) {
                    $sessionId = $request->session()->getId();
                }
            } catch (\Throwable $e) {
                $sessionId = null; // never crash submit because of debugging
            }

            \Log::warning('APPROVAL_POST_DEBUG', [
                'url'          => $request->fullUrl(),
                'host'         => $request->getHost(),
                'method'       => $request->method(),
                'has_token'    => $request->has('_token'),
                'token_input'  => $request->input('_token'),
                'token_header' => $request->header('X-CSRF-TOKEN'),
                'cookie_xsrf'  => $request->cookie('XSRF-TOKEN'),
                'session_id'   => $sessionId,
                'post_keys'    => array_keys($request->all()),
                'content_type' => $request->header('Content-Type'),
            ]);
        }

        return $next($request);
    }
}
