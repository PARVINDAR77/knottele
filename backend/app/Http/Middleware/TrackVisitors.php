<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $ip = $request->ip();
            $today = now()->format('Y-m-d');

            // We use firstOrCreate to ensure we only count once per IP per day
            \App\Models\Visitor::firstOrCreate([
                'ip_address' => $ip,
                'visit_date' => $today
            ]);
        } catch (\Throwable $e) {
            // Silently ignore visitor logging issues to never interrupt requests
        }

        return $next($request);
    }
}
