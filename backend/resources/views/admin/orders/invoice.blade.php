<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>{{ $pdfFilename ?? ($order->created_at->format('d-m-Y') . '-' . sprintf('%04d', $order->id) . '.pdf') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo/Logo_1.png') }}?v=2">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            200: '#fecdd3',
                            300: '#fda4af',
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            800: '#9f1239',
                            900: '#881337',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .invoice-page {
            min-height: 100vh;
            padding: 24px 16px;
        }

        .invoice-card {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            padding: 36px 40px;
        }

        .header-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 24px;
            margin-bottom: 24px;
            gap: 16px;
        }

        .store-box {
            text-align: left;
        }

        .invoice-title-box {
            text-align: right;
        }

        .store-logo {
            height: 72px;
            width: auto;
            object-fit: contain;
            display: inline-block;
            margin-bottom: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-bottom: 28px;
        }

        .info-card {
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 16px;
        }

        .desktop-table-wrap {
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .desktop-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .desktop-table th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .desktop-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            vertical-align: middle;
        }

        .mobile-items-list {
            display: none;
        }

        .totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
        }

        .totals-box {
            width: 320px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }

        /* Mobile Responsive View */
        @media screen and (max-width: 640px) {
            .invoice-page {
                padding: 10px 8px 32px 8px;
            }

            .invoice-card {
                padding: 18px 14px;
                border-radius: 14px;
            }

            .header-wrap {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                padding-bottom: 18px;
                margin-bottom: 18px;
            }

            .store-box {
                text-align: left;
                width: 100%;
                border-bottom: 1px dashed #e2e8f0;
                padding-bottom: 14px;
            }

            .invoice-title-box {
                text-align: left;
                width: 100%;
            }

            .store-logo {
                height: 52px;
                margin-bottom: 6px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 14px;
                margin-bottom: 18px;
            }

            .info-card {
                padding: 14px;
            }

            .desktop-table-wrap {
                display: none;
            }

            .mobile-items-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin-bottom: 18px;
            }

            .mobile-item-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 12px 14px;
            }

            .totals-wrap {
                justify-content: stretch;
            }

            .totals-box {
                width: 100%;
            }

            .top-actions {
                flex-direction: row;
                width: 100%;
            }

            .top-actions .btn {
                flex: 1;
                justify-content: center;
            }
        }

        /* Print styles */
        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }

            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .invoice-page {
                padding: 0 !important;
                min-height: auto !important;
            }

            .invoice-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .header-wrap {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                border-bottom: 1px solid #cbd5e1 !important;
            }

            .store-box {
                text-align: left !important;
                width: auto !important;
                border-bottom: none !important;
                padding-bottom: 0 !important;
            }

            .invoice-title-box {
                text-align: right !important;
                width: auto !important;
            }

            .store-logo {
                height: 64px !important;
                display: inline-block !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .info-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 20px !important;
            }

            .desktop-table-wrap {
                display: block !important;
                border: 1px solid #cbd5e1 !important;
            }

            .desktop-table {
                display: table !important;
                width: 100% !important;
            }

            .mobile-items-list {
                display: none !important;
            }

            .totals-box {
                width: 280px !important;
                margin-left: auto !important;
                border: 1px solid #cbd5e1 !important;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-page">
        
        <!-- Top Action Bar (No-Print) -->
        <div class="max-w-[860px] mx-auto mb-3 flex items-center justify-between gap-2.5 no-print px-1 top-actions">
            <a href="javascript:window.close(); if(history.length > 1) history.back();" class="btn inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-stone-100 text-stone-700 text-xs sm:text-sm font-semibold rounded-xl border border-stone-200 shadow-sm transition-all">
                <i class="fas fa-arrow-left text-xs"></i> Back
            </a>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="btn inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white text-xs sm:text-sm font-semibold rounded-xl shadow-md hover:shadow-lg transition-all">
                    <i class="fas fa-print text-xs"></i> Print / Save PDF
                </button>
            </div>
        </div>

        <!-- Main Invoice Card -->
        <div class="invoice-card">
            
            <!-- Header: Store Details on LEFT, Invoice on RIGHT -->
            <div class="header-wrap">
                <!-- Left: Store Details & Logo -->
                <div class="store-box">
                    <img src="{{ asset('images/logo/knotelle-logo.png') }}?v=2" alt="KNOTELLE Logo" class="store-logo">
                    <h2 class="text-xl sm:text-2xl font-black text-red-600 tracking-wider uppercase m-0">KNOTELLE</h2>
                    <p class="text-stone-600 text-xs sm:text-sm mt-1 mb-0 leading-relaxed font-medium">
                        Handcrafted with Love India<br>
                        <span class="text-stone-700 font-semibold">support@knotelle.in</span><br>
                        <span class="text-stone-700 font-semibold">+91 9773055555</span>
                    </p>
                </div>

                <!-- Right: Invoice Info -->
                <div class="invoice-title-box">
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wider bg-red-50 text-red-700 border border-red-100 uppercase mb-2">
                        Official Invoice
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-stone-900 tracking-tight m-0">INVOICE</h1>
                    <p class="text-stone-500 font-medium text-xs sm:text-sm mt-1.5 mb-0">
                        Order #<span class="text-stone-900 font-bold select-all">{{ $order->order_number }}</span>
                    </p>
                    <p class="text-stone-500 text-xs mt-1 mb-0">
                        Date: <span class="text-stone-800 font-semibold">{{ $order->created_at->format('M d, Y') }}</span>
                    </p>
                </div>
            </div>

            <!-- Billed To & Invoice Details -->
            <div class="info-grid">
                
                <!-- Billed To -->
                <div class="info-card">
                    <h3 class="text-[11px] font-bold text-stone-400 uppercase tracking-wider mb-2 flex items-center gap-1.5 m-0">
                        <i class="fas fa-user text-red-500 text-[10px]"></i> Billed To
                    </h3>
                    <div class="text-stone-800">
                        @php
                            $shipping = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address ?? '[]', true);
                            $customerName = $order->customer->name ?? $shipping['name'] ?? $shipping['full_name'] ?? 'Customer';
                            $customerEmail = $order->customer->email ?? $shipping['email'] ?? null;
                            $customerPhone = $order->customer->mobile ?? $order->customer->phone ?? $shipping['phone'] ?? $shipping['mobile'] ?? null;
                        @endphp
                        <p class="font-bold text-sm sm:text-base text-stone-900 m-0 mb-1">{{ $customerName }}</p>
                        @if(!empty($shipping))
                            <p class="text-stone-600 text-xs sm:text-sm m-0 leading-relaxed">
                                {{ $shipping['address_line_1'] ?? $shipping['address'] ?? '' }}
                                @if(!empty($shipping['address_line_2']))
                                    <br>{{ $shipping['address_line_2'] }}
                                @endif
                                <br>
                                {{ $shipping['city'] ?? '' }}{{ !empty($shipping['state']) ? ', ' . $shipping['state'] : '' }}
                                {{ $shipping['postal_code'] ?? $shipping['pincode'] ?? '' }}
                            </p>
                        @endif
                        <div class="mt-2 pt-2 border-t border-stone-200/60 text-xs text-stone-500 space-y-1">
                            @if($customerEmail)
                                <div class="flex items-center gap-1.5 break-all">
                                    <i class="fas fa-envelope text-stone-400 text-[10px]"></i>
                                    <span>{{ $customerEmail }}</span>
                                </div>
                            @endif
                            @if($customerPhone)
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-phone text-stone-400 text-[10px]"></i>
                                    <span>{{ $customerPhone }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="info-card">
                    <h3 class="text-[11px] font-bold text-stone-400 uppercase tracking-wider mb-2 flex items-center gap-1.5 m-0">
                        <i class="fas fa-file-invoice text-red-500 text-[10px]"></i> Invoice Details
                    </h3>
                    <div class="space-y-2 text-xs sm:text-sm">
                        <div class="flex justify-between items-center py-1 border-b border-stone-200/60">
                            <span class="text-stone-500">Order Placed Date:</span>
                            <span class="font-semibold text-stone-900">{{ $order->created_at->format('M d, Y - h:i A') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-stone-200/60">
                            <span class="text-stone-500">Order Status:</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold
                                @if($order->status == 'pending') bg-amber-100 text-amber-800
                                @elseif($order->status == 'processing') bg-blue-100 text-blue-800
                                @elseif($order->status == 'shipped') bg-purple-100 text-purple-800
                                @elseif($order->status == 'delivered') bg-emerald-100 text-emerald-800
                                @elseif($order->status == 'cancelled') bg-rose-100 text-rose-800
                                @else bg-stone-100 text-stone-800 @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-stone-200/60">
                            <span class="text-stone-500">Payment Status:</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold
                                @if($order->payment_status == 'paid') bg-emerald-100 text-emerald-800
                                @elseif($order->payment_status == 'failed') bg-rose-100 text-rose-800
                                @else bg-amber-100 text-amber-800 @endif">
                                {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-stone-500">Payment Method:</span>
                            <span class="font-semibold text-stone-900">
                                {{ $order->paymentMethod->name ?? ucfirst($order->payment_method ?? 'N/A') }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Desktop Items Table (Shown on tablet, desktop & print) -->
            <div class="desktop-table-wrap">
                <table class="desktop-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Item Description</th>
                            <th style="width: 18%; text-align: right;">Price</th>
                            <th style="width: 12%; text-align: center;">Qty</th>
                            <th style="width: 20%; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <div class="font-bold text-stone-900 text-sm">{{ $item->product_name }}</div>
                                    <div class="text-xs text-stone-400 font-mono mt-0.5">SKU: {{ $item->sku }}</div>
                                    @if($item->attributes)
                                        <div class="text-[11px] text-stone-500 mt-1 flex flex-wrap gap-1">
                                            @foreach(is_array($item->attributes) ? $item->attributes : json_decode($item->attributes, true) as $key => $value)
                                                <span class="inline-block bg-stone-100 px-1.5 py-0.5 rounded text-stone-600 border border-stone-200">
                                                    {{ ucfirst($key) }}: {{ $value }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: right; color: #475569; font-weight: 500;">
                                    ₹{{ number_format($item->unit_price, 2) }}
                                </td>
                                <td style="text-align: center; color: #475569; font-weight: 600;">
                                    {{ $item->quantity }}
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                    ₹{{ number_format($item->total, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Items List (Shown only on small mobile screens <= 640px) -->
            <div class="mobile-items-list">
                <div class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                    <i class="fas fa-box-open text-red-500 text-[10px]"></i> Order Items ({{ count($order->items) }})
                </div>
                @foreach($order->items as $item)
                    <div class="mobile-item-card">
                        <div class="font-bold text-stone-900 text-sm leading-snug">{{ $item->product_name }}</div>
                        <div class="text-xs text-stone-400 font-mono mt-0.5">SKU: {{ $item->sku }}</div>
                        @if($item->attributes)
                            <div class="text-[11px] text-stone-500 mt-1.5 flex flex-wrap gap-1">
                                @foreach(is_array($item->attributes) ? $item->attributes : json_decode($item->attributes, true) as $key => $value)
                                    <span class="inline-block bg-stone-100 px-1.5 py-0.5 rounded text-stone-600 border border-stone-200">
                                        {{ ucfirst($key) }}: {{ $value }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <div class="flex justify-between items-center mt-2.5 pt-2 border-t border-dashed border-stone-200 text-xs">
                            <span class="text-stone-500 font-medium">
                                ₹{{ number_format($item->unit_price, 2) }} × {{ $item->quantity }}
                            </span>
                            <span class="font-extrabold text-stone-900 text-sm">
                                ₹{{ number_format($item->total, 2) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Totals Section -->
            <div class="totals-wrap">
                <div class="totals-box">
                    <div class="space-y-2 text-xs sm:text-sm">
                        <div class="flex justify-between text-stone-600">
                            <span>Subtotal</span>
                            <span class="font-semibold text-stone-900">₹{{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        @if($order->tax_total > 0)
                            <div class="flex justify-between text-stone-600">
                                <span>Tax</span>
                                <span class="font-semibold text-stone-900">₹{{ number_format($order->tax_total, 2) }}</span>
                            </div>
                        @endif
                        @if($order->shipping_total > 0)
                            <div class="flex justify-between text-stone-600">
                                <span>Shipping</span>
                                <span class="font-semibold text-stone-900">₹{{ number_format($order->shipping_total, 2) }}</span>
                            </div>
                        @endif
                        @if($order->discount_total > 0)
                            <div class="flex justify-between text-red-600 font-medium">
                                <span>Discount</span>
                                <span>-₹{{ number_format($order->discount_total, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center pt-2.5 mt-2 border-t-2 border-stone-200">
                            <span class="text-sm sm:text-base font-extrabold text-stone-900">Grand Total</span>
                            <span class="text-base sm:text-lg font-black text-red-600">₹{{ number_format($order->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thank You Note -->
            <div class="text-center pt-4 border-t border-stone-200/80">
                <p class="text-xs text-stone-500 font-medium m-0">
                    Thank you for choosing <span class="text-stone-800 font-bold">KNOTELLE</span>. For support, please contact <span class="font-semibold text-stone-700">support@knotelle.in</span> | <span class="font-semibold text-stone-700">+91 9773055555</span>
                </p>
            </div>

            <!-- Bottom Print Button (No-Print) -->
            <div class="mt-8 text-center no-print">
                <button onclick="window.print()" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-bold py-2.5 px-7 rounded-xl shadow-md hover:shadow-lg transition-all text-sm cursor-pointer">
                    <i class="fas fa-print"></i> Print Invoice / Save as PDF
                </button>
            </div>

        </div>

    </div>

    <script>
        // If opened with ?download=1 or ?print=1, automatically trigger print/save dialog
        if (window.location.search.includes('download=1') || window.location.search.includes('print=1')) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 450);
            });
        }
    </script>
</body>
</html>
