@extends('admin.layouts.master')

@section('title', 'OTP & SMS Tracking')

@section('content')
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-stone-800 mb-1 flex items-center gap-2">
                <i class="fas fa-sms text-red-600"></i>
                OTP & SMS Gateway Monitoring
            </h2>
            <p class="text-stone-600 text-sm">Real-time audit log and delivery statistics for customer OTPs via TRAI DLT Gateway</p>
        </div>
        <div>
            <button onclick="document.getElementById('testSmsModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition shadow-sm">
                <i class="fas fa-paper-plane"></i>
                Send Test OTP
            </button>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-2" role="alert">
        <i class="fas fa-check-circle text-emerald-600 text-lg"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="bg-rose-50 border border-rose-300 text-rose-800 px-4 py-3 rounded-xl relative mb-6 flex items-center gap-2" role="alert">
        <i class="fas fa-exclamation-circle text-rose-600 text-lg"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

<!-- Analytics Stat Cards -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <!-- Total Sent -->
    <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-stone-500">Total OTPs</span>
            <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-sm font-bold">
                <i class="fas fa-paper-plane"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-stone-900">{{ number_format($totalSent) }}</div>
        <div class="text-[11px] text-stone-500 mt-1">All time volume</div>
    </div>

    <!-- Today's Sent -->
    <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-stone-500">Sent Today</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-stone-900">{{ number_format($todaySent) }}</div>
        <div class="text-[11px] text-stone-500 mt-1">{{ now()->format('M d, Y') }}</div>
    </div>

    <!-- This Month -->
    <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-stone-500">This Month</span>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-stone-900">{{ number_format($thisMonthSent) }}</div>
        <div class="text-[11px] text-stone-500 mt-1">{{ now()->format('F Y') }}</div>
    </div>

    <!-- Verified OTPs -->
    <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Verified</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-emerald-700">{{ number_format($verifiedCount) }}</div>
        <div class="text-[11px] text-emerald-600 mt-1">Logged in users</div>
    </div>

    <!-- Failed OTPs -->
    <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-rose-600">Failed</span>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm font-bold">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-rose-600">{{ number_format($failedCount) }}</div>
        <div class="text-[11px] text-rose-500 mt-1">Delivery errors</div>
    </div>

    <!-- Gateway Success Rate -->
    <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-stone-500">Success Rate</span>
            <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center text-sm font-bold">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-stone-900">{{ $successRate }}%</div>
        <div class="text-[11px] text-stone-500 mt-1">Gateway reliability</div>
    </div>
</div>

<!-- Filters & Search -->
<div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-sm mb-6">
    <form method="GET" action="{{ route('admin.otp_logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-center">
        <!-- Search Input -->
        <div class="relative">
            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone, message ID, OTP..." class="w-full pl-9 pr-3 py-2 text-sm border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
        </div>

        <!-- Status Filter -->
        <div>
            <select name="status" class="w-full px-3 py-2 text-sm border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent / Queued</option>
                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified (Success)</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed / Error</option>
            </select>
        </div>

        <!-- Date Range Filter -->
        <div>
            <select name="date" class="w-full px-3 py-2 text-sm border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                <option value="" {{ request('date') == '' ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ request('date') == 'today' ? 'selected' : '' }}>Today Only</option>
                <option value="yesterday" {{ request('date') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="week" {{ request('date') == 'week' ? 'selected' : '' }}>Last 7 Days</option>
                <option value="month" {{ request('date') == 'month' ? 'selected' : '' }}>Last 30 Days</option>
            </select>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-stone-800 text-white text-sm font-semibold rounded-xl hover:bg-stone-900 transition flex items-center justify-center gap-2">
                <i class="fas fa-filter text-xs"></i>
                Filter
            </button>
            @if(request()->anyFilled(['search', 'status', 'date']))
                <a href="{{ route('admin.otp_logs.index') }}" class="px-3 py-2 bg-stone-100 text-stone-600 text-sm font-medium rounded-xl hover:bg-stone-200 transition" title="Clear Filters">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Logs Data Table -->
<div class="bg-white rounded-2xl shadow-sm border border-stone-200/80 overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-stone-700">
            <thead class="bg-stone-50 border-b border-stone-200 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <tr>
                    <th class="py-3.5 px-4">ID</th>
                    <th class="py-3.5 px-4">Mobile Number</th>
                    <th class="py-3.5 px-4">OTP Code</th>
                    <th class="py-3.5 px-4">Purpose</th>
                    <th class="py-3.5 px-4">Delivery Status</th>
                    <th class="py-3.5 px-4">Gateway Msg ID</th>
                    <th class="py-3.5 px-4">Latency</th>
                    <th class="py-3.5 px-4">Sent Time</th>
                    <th class="py-3.5 px-4">Verified At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-stone-50/70 transition">
                        <td class="py-3.5 px-4 font-mono text-xs text-stone-500">#{{ $log->id }}</td>
                        <td class="py-3.5 px-4 font-medium text-stone-900">
                            <span class="inline-flex items-center gap-1.5 font-mono text-stone-800">
                                <i class="fas fa-phone text-stone-400 text-xs"></i>
                                +91 {{ $log->to }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            @if ($log->otp_code)
                                <span class="font-mono font-bold bg-amber-50 text-amber-800 px-2 py-0.5 rounded border border-amber-200 text-xs tracking-wider">
                                    {{ $log->otp_code }}
                                </span>
                            @else
                                <span class="text-stone-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-700 capitalize">
                                {{ $log->purpose ?? 'Login' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            @if ($log->verified_at || $log->status === 'verified')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    <i class="fas fa-check-circle text-[10px]"></i> Verified
                                </span>
                            @elseif ($log->status === 'sent')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    <i class="fas fa-paper-plane text-[10px]"></i> Sent
                                </span>
                            @elseif ($log->status === 'failed')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800" title="{{ $log->error_message }}">
                                    <i class="fas fa-times-circle text-[10px]"></i> Failed
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-stone-100 text-stone-800">
                                    {{ ucfirst($log->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-mono text-xs text-stone-600">
                            {{ $log->message_id ?? '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-xs text-stone-500 font-mono">
                            {{ $log->duration_ms ? $log->duration_ms . 'ms' : '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-xs text-stone-600 whitespace-nowrap">
                            <div>{{ $log->created_at->format('d M Y, h:i A') }}</div>
                            <div class="text-[10px] text-stone-400">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-stone-600 whitespace-nowrap">
                            @if ($log->verified_at)
                                <div class="text-emerald-700 font-medium">{{ $log->verified_at->format('h:i A') }}</div>
                                <div class="text-[10px] text-emerald-600">{{ $log->verified_at->diffForHumans() }}</div>
                            @else
                                <span class="text-stone-300">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-stone-500">
                            <div class="w-12 h-12 rounded-full bg-stone-100 text-stone-400 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-inbox text-xl"></i>
                            </div>
                            <p class="font-medium text-stone-700">No OTP logs recorded yet</p>
                            <p class="text-xs text-stone-500 mt-1">Logs will appear automatically whenever users generate OTPs on login or checkout.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div class="p-4 border-t border-stone-200">
            {{ $logs->links() }}
        </div>
    @endif
</div>

<!-- Test SMS Modal -->
<div id="testSmsModal" class="hidden fixed inset-0 z-50 bg-stone-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 relative">
        <div class="flex items-center justify-between pb-4 border-b border-stone-100">
            <h3 class="text-lg font-bold text-stone-800 flex items-center gap-2">
                <i class="fas fa-paper-plane text-red-600"></i>
                Send Test OTP SMS
            </h3>
            <button onclick="document.getElementById('testSmsModal').classList.add('hidden')" class="text-stone-400 hover:text-stone-600">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form action="{{ route('admin.otp_logs.test') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-1">Mobile Number (10 Digits)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-stone-500">+91</span>
                    <input type="tel" name="mobile" maxlength="10" placeholder="9876543210" required class="w-full pl-12 pr-4 py-2.5 text-sm border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                <p class="text-[11px] text-stone-500 mt-1">Real SMS will be sent using Sender ID <strong class="text-stone-700">TXXTOO</strong></p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-1">Recipient Name (Optional)</label>
                <input type="text" name="name" value="Admin" placeholder="Customer Name" class="w-full px-4 py-2.5 text-sm border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('testSmsModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-100 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i>
                    Send Live Test
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
