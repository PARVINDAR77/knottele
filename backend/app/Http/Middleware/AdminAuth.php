<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

class AdminAuth
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            $tokenParam = urldecode($request->query('auth_token') ?: $request->header('X-Admin-Token', ''));
            if ($tokenParam) {
                try {
                    $accessToken = Sanctum::$personalAccessTokenModel::findToken($tokenParam);
                    if ($accessToken && $accessToken->tokenable instanceof Admin) {
                        Auth::guard('admin')->login($accessToken->tokenable, true);
                        $request->session()->put('admin_api_token', $tokenParam);
                        $request->session()->save();
                        return $next($request);
                    }
                } catch (\Throwable $e) {}
            }
            if ($request->expectsJson() || $request->ajax() || $request->is('*/data*')) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('admin.signin');
        }

        // The admin UI uses the session guard, while its API uses Sanctum bearer
        // tokens. Restore a usable API token when it is missing or was revoked.
        try {
            $admin = Auth::guard('admin')->user();
            $token = $request->session()->get('admin_api_token');
            $accessToken = $token ? Sanctum::$personalAccessTokenModel::findToken($token) : null;

            if (! $accessToken || ! $accessToken->tokenable instanceof Admin || ! $accessToken->tokenable->is($admin)) {
                $request->session()->put(
                    'admin_api_token',
                    $admin->createToken('admin_api', ['admin'])->plainTextToken
                );
            }
        } catch (\Throwable $e) {
            // Ignore API token error so admin can always access web dashboard
        }

        return $next($request);
    }
}
