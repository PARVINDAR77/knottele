<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',

        // MULTIPLE API ROUTE FILES
        api: [
            __DIR__ . '/../routes/admin_api.php',
            __DIR__ . '/../routes/customer_api.php',
        ],

        then: function () {
            \Illuminate\Support\Facades\Route::middleware('api')->prefix('knottele/api')->group(function () {
                \Illuminate\Support\Facades\Route::get('/health', fn() => response()->json(['status' => 'ok']));
                require __DIR__ . '/../routes/customer_api.php';
                require __DIR__ . '/../routes/admin_api.php';
            });
        },

        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'knottele/api/*',
            'api/*',
            '*/api/*',
            'admin/signin',
            'admin/signin/*',
            'admin/login',
            'admin/login/*',
            'knottele/admin/signin',
            'knottele/admin/signin/*',
            'knottele/admin/login',
            'knottele/admin/login/*',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\TrackVisitors::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('admin*') || $request->is('*/admin*')) {
                return route('admin.signin');
            }
            return '/login';
        });

        $middleware->alias([
            'admin.auth'    => \App\Http\Middleware\AdminAuth::class,
            'customer.auth' => \App\Http\Middleware\CustomerAuth::class,
            'admin.api'     => \App\Http\Middleware\AdminApiAuth::class,
            'customer.api'  => \App\Http\Middleware\CustomerApiAuth::class,
            'sync.cart'     => \App\Http\Middleware\SyncCartAfterLogin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->is('admin*') || $request->is('*/admin*')) {
                return redirect()->route('admin.signin')->withErrors(['Your session expired. Please sign in again.']);
            }
        });
    })
    ->create();
