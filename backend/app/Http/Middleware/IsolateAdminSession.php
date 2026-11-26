<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsolateAdminSession
{
    /**
     * Isolate session cookie names between the Admin Panel and the public Customer Storefront.
     * Running this before StartSession ensures that Admin sessions and Website sessions
     * never collide, share cookies, or invalidate each other when used in the same browser.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $isAdmin = (
            $request->is('admin*') ||
            $request->is('*/admin*') ||
            $request->is('knottele/admin*') ||
            $request->is('knottele/knottele/admin*')
        );

        if ($isAdmin) {
            config(['session.cookie' => 'knotelle_admin_session']);
        } else {
            config(['session.cookie' => 'knotelle_session']);
        }

        $response = $next($request);

        // Enforce strict zero-cache policy on all Admin requests to prevent browser cache poisoning
        if ($isAdmin && method_exists($response, 'header')) {
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }
}
