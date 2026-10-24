<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;


class AuthController extends Controller
{
    public function loginPage()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.auth.login');
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $credentials['email'])->first();

        if ($admin) {
            $info = password_get_info($admin->password);
            if ($info['algo'] === null || $info['algo'] === 0 || $info['algoName'] === 'unknown') {
                if ($admin->password === $credentials['password']) {
                    $admin->password = Hash::make($credentials['password']);
                    $admin->save();
                } else {
                    $admin->password = Hash::make($admin->password);
                    $admin->save();
                }
            }
        }

        if (!Auth::guard('admin')->attempt($credentials, true)) {
            return back()->withErrors(['Invalid credentials']);
        }

        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();

        // Update login metadata
        try {
            $admin->last_login_at = now();
            $admin->last_login_ip = $request->ip();
            $admin->save();
        } catch (\Throwable $e) {
            // Ignore if columns missing
        }

        // Safely manage API token without blocking dashboard redirect
        try {
            $admin->tokens()->delete();
            $token = $admin->createToken('admin_api', ['admin'])->plainTextToken;
            session([
                'admin_api_token' => $token,
            ]);
        } catch (\Throwable $e) {
            // Ignore token exception
        }

        session()->save();

        $response = redirect()->route('admin.dashboard');
        $cookieName = config('session.cookie', 'knotelle-session');
        $response->withCookie(cookie(
            $cookieName,
            session()->getId(),
            config('session.lifetime', 120),
            '/',
            config('session.domain'),
            false,
            true,
            false,
            'lax'
        ));

        return $response;
    }



    public function logout(Request $request)
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            $admin->tokens()->where('name', 'admin_api')->delete();
        }

        // Clear session
        session()->forget('admin_api_token');

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.signin');
    }
}
