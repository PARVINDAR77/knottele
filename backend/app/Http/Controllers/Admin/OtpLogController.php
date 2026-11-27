<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\Request;

class OtpLogController extends Controller
{
    /**
     * Display OTP & SMS logs with analytics and search
     */
    public function index(Request $request)
    {
        // 1. Calculate Summary Metrics
        $totalSent = SmsLog::count();
        $todaySent = SmsLog::whereDate('created_at', today())->count();
        $thisMonthSent = SmsLog::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $verifiedCount = SmsLog::where('status', 'verified')
            ->orWhereNotNull('verified_at')
            ->count();
        $failedCount = SmsLog::where('status', 'failed')->count();
        $successRate = $totalSent > 0 ? round((($totalSent - $failedCount) / $totalSent) * 100, 1) : 100;

        // 2. Build Query with Filters
        $query = SmsLog::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('to', 'like', "%{$search}%")
                  ->orWhere('message_id', 'like', "%{$search}%")
                  ->orWhere('otp_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'verified') {
                $query->where(function ($q) {
                    $q->where('status', 'verified')->orWhereNotNull('verified_at');
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('date')) {
            switch ($request->date) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', today()->subDay());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->subDays(7));
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->subDays(30));
                    break;
            }
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        return view('admin.otp_logs.index', compact(
            'logs',
            'totalSent',
            'todaySent',
            'thisMonthSent',
            'verifiedCount',
            'failedCount',
            'successRate'
        ));
    }

    /**
     * Send a live test SMS from the admin panel to verify DLT gateway
     */
    public function sendTestSms(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'name' => 'nullable|string|max:50',
        ]);

        $res = SmsService::sendOtp(
            $request->mobile,
            $request->name ?: 'Admin',
            'admin_test',
            $request->ip()
        );

        if ($res['success']) {
            return back()->with('success', $res['message']);
        }

        return back()->with('error', $res['message']);
    }
}
