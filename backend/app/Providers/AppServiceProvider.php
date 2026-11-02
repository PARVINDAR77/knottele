<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $host = (!app()->runningInConsole() && request()) ? request()->getHost() : parse_url(config('app.url', ''), PHP_URL_HOST);
        $isIp = !empty($host) && (filter_var($host, FILTER_VALIDATE_IP) !== false || $host === '187.127.158.24' || $host === 'localhost' || $host === '127.0.0.1');
        $serverPort = $_SERVER['SERVER_PORT'] ?? null;

        // An IP address or port 80 must NEVER force or use HTTPS (prevents net::ERR_CERT_COMMON_NAME_INVALID)
        if ($isIp || $serverPort == 80 || $serverPort == '80') {
            unset($_SERVER['HTTPS']);
            $_SERVER['SERVER_PORT'] = '80';
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
            $scheme = 'http';
            $isHttps = false;
        } else {
            $isHttps = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (request() && request()->isSecure()))
                && str_starts_with(config('app.url', ''), 'https://');
            $scheme = $isHttps ? 'https' : 'http';
        }

        // Explicitly enforce the determined scheme so Vite and asset() never use https for IP addresses
        \Illuminate\Support\Facades\URL::forceScheme($scheme);

        if (!app()->runningInConsole() && request()) {
            // Auto-detect live VPS: only the old IP (187.127.158.24) used the /knottele subfolder
            if ($host === '187.127.158.24') {
                \Illuminate\Support\Facades\URL::forceRootUrl($scheme . '://' . $host . '/knottele');
            } else {
                \Illuminate\Support\Facades\URL::forceRootUrl($scheme . '://' . request()->getHttpHost());
            }
        } elseif ($appUrl = config('app.url')) {
            if ($isIp) {
                $appUrl = preg_replace('/^https:\/\//i', 'http://', $appUrl);
            }
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
        }

        \Illuminate\Support\Facades\View::composer('customer.partials.header', function ($view) {
            $view->with('navCategories', \App\Models\Category::where('show_in_nav', true)
                ->where('status', true)
                ->orderBy('sort_order')
                ->get());

            // Inject cart count
            $cartHelper = app(\App\Helpers\CartHelper::class);
            $view->with('cartCount', $cartHelper->getCartCount());
        });
    }
}
