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
        if (
            $request->is('admin*') ||
            $request->is('*/admin*') ||
            $request->is('knottele/admin*') ||
            $request->is('knottele/knottele/admin*')
        ) {
            config(['session.cookie' => 'knotelle_admin_session']);
        } else {
            config(['session.cookie' => 'knotelle_session']);
        }

        return $next($request);
    }
}
