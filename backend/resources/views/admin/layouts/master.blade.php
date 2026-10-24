<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'KNOTELLE Admin Panel')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo/Logo_1.png') }}?v=2">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Lucide Icons (Self-hosted) -->
    <script src="{{ asset('js/lucide.min.js') }}"></script>

    <!-- Tabulator CSS (Self-hosted) -->
    <link href="{{ asset('css/admin/tabulator.min.css') }}" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ asset('css/admin/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/datatable.css') }}">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Remove native number input spin buttons / steppers globally */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none !important;
            margin: 0 !important;
        }
        input[type="number"] {
            -moz-appearance: textfield !important;
            appearance: textfield !important;
        }

        /* Responsive Print / PDF Export Styling */
        @media print {
            #sidebar,
            header,
            .admin-header,
            .no-print,
            button,
            .tabulator-header-filter,
            .tabulator-paginator,
            #loadingSpinner,
            .btn-primary,
            .btn-secondary,
            .btn-success,
            .btn-danger {
                display: none !important;
            }

            #main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .tabulator,
            table {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
            }

            .print-only {
                display: block !important;
            }

            @page {
                size: auto;
                margin: 10mm;
            }
        }
    </style>


    <script>
        // Global variables
        const BASE_URL = '{{ url('/') }}';
        const ADMIN_URL = '{{ url(request()->is('knottele*') ? '/knottele/admin' : '/admin') }}';
        const ASSET_URL = '{{ asset('') }}';
        window.ADMIN_API_TOKEN = @json(session('admin_api_token'));
    </script>
</head>

<body class="bg-stone-50">
    @php
        $isAdminPanel = (request()->is('admin/*') || request()->is('knottele/admin/*') || request()->is('*admin/*'))
            && !request()->is('*login*') && !request()->is('*signin*');
    @endphp

    @if ($isAdminPanel)
        @include('admin.partials.sidebar')
    @endif

    <div id="main-content" class="transition-all duration-300 @if ($isAdminPanel) ml-0 md:ml-24 @endif">
        @if ($isAdminPanel)
            @include('admin.partials.header')
        @endif

        <main class="@if ($isAdminPanel) p-4 sm:p-6 md:p-8 @endif">
            <!-- Global Print Header (Appears only on Print / PDF export) -->
            <div class="print-only" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #dc2626; padding-bottom: 12px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                        <img src="{{ asset('images/logo/knotelle-logo.png') }}?v=2" style="height: 54px; width: auto; object-fit: contain;" alt="KNOTELLE">
                        <div>
                            <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #dc2626; letter-spacing: 0.03em;">KNOTELLE</h2>
                            <p style="margin: 2px 0 0 0; font-size: 11px; color: #475569; line-height: 1.4;">
                                Handcrafted with Love India<br>
                                support@knotelle.in • +91 9773055555
                            </p>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 700; background: #fee2e2; color: #dc2626; border-radius: 9999px; text-transform: uppercase;">Admin Report</span>
                        <p style="margin: 4px 0 0 0; font-size: 11px; color: #64748b;">Printed on: {{ date('M d, Y - h:i A') }}</p>
                    </div>
                </div>
            </div>

            @yield('content')
        </main>
    </div>

    <!-- Common Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Tabulator JS (Self-hosted) -->
    <script type="text/javascript" src="{{ asset('js/admin/tabulator.min.js') }}"></script>

    <!-- Export Libraries (Excel, PDF, AutoTable) -->
    <script src="{{ asset('js/admin/xlsx.full.min.js') }}"></script>
    <script src="{{ asset('js/admin/jspdf.umd.min.js') }}"></script>
    <script src="{{ asset('js/admin/jspdf.plugin.autotable.min.js') }}"></script>
    <script src="{{ asset('js/admin/html2pdf.bundle.min.js') }}"></script>

    <!-- Lodash -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="{{ asset('js/admin/custom.js') }}?v={{ filemtime(public_path('js/admin/custom.js')) }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.5.0/axios.min.js"></script>
    <script>
        if (window.axios) {
            window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
            }

            // Universal subdirectory support:
            // When hosted under /knottele, ensure axios requests starting with /admin/ or /api/ are prefixed with /knottele
            window.axios.interceptors.request.use(function (config) {
                if (config.url && typeof config.url === 'string') {
                    const isKnottele = window.location.pathname.startsWith('/knottele');
                    if (isKnottele && !config.url.startsWith('http://') && !config.url.startsWith('https://')) {
                        if (config.url.startsWith('/admin/') || config.url.startsWith('/api/')) {
                            config.url = '/knottele' + config.url;
                        } else if (config.url.startsWith('admin/') || config.url.startsWith('api/')) {
                            config.url = '/knottele/' + config.url;
                        }
                    }
                }
                return config;
            });
        }
    </script>

    <script>
        (function() {
            try {
                const params = new URLSearchParams(window.location.search);
                const urlToken = params.get('auth_token');
                const sessionToken = '{{ session("admin_api_token") }}';
                if (urlToken) {
                    localStorage.setItem('admin_auth_token', urlToken);
                } else if (sessionToken && !localStorage.getItem('admin_auth_token')) {
                    localStorage.setItem('admin_auth_token', sessionToken);
                }
                const activeToken = localStorage.getItem('admin_auth_token') || urlToken || sessionToken;

                if (activeToken) {
                    // 1. Intercept all internal admin navigation links
                    document.addEventListener('click', function(e) {
                        const link = e.target.closest('a[href]');
                        if (link && link.href && link.href.includes('/admin') && !link.href.includes('auth_token=') && !link.href.includes('/logout')) {
                            const u = new URL(link.href, window.location.origin);
                            u.searchParams.set('auth_token', activeToken);
                            link.href = u.toString();
                        }
                    }, true);

                    // 2. Intercept window.fetch for all admin AJAX requests
                    const originalFetch = window.fetch;
                    window.fetch = function(input, init) {
                        init = init || {};
                        let headers = init.headers || {};
                        if (headers instanceof Headers) {
                            if (!headers.has('X-Admin-Token')) headers.set('X-Admin-Token', activeToken);
                        } else if (Array.isArray(headers)) {
                            headers.push(['X-Admin-Token', activeToken]);
                        } else {
                            headers['X-Admin-Token'] = activeToken;
                        }
                        init.headers = headers;

                        if (typeof input === 'string') {
                            if (input.includes('/admin') && !input.includes('auth_token=')) {
                                const sep = input.includes('?') ? '&' : '?';
                                input = input + sep + 'auth_token=' + encodeURIComponent(activeToken);
                            }
                        } else if (input instanceof Request) {
                            try {
                                const u = new URL(input.url);
                                if (u.pathname.includes('/admin') && !u.searchParams.has('auth_token')) {
                                    u.searchParams.set('auth_token', activeToken);
                                    input = new Request(u.toString(), input);
                                }
                            } catch(err) {}
                        }
                        return originalFetch.call(this, input, init);
                    };

                    // 3. Intercept XMLHttpRequest (e.g. Tabulator, axios, jQuery)
                    const originalOpen = XMLHttpRequest.prototype.open;
                    XMLHttpRequest.prototype.open = function(method, url, ...rest) {
                        if (typeof url === 'string' && url.includes('/admin') && !url.includes('auth_token=')) {
                            const sep = url.includes('?') ? '&' : '?';
                            url = url + sep + 'auth_token=' + encodeURIComponent(activeToken);
                        }
                        return originalOpen.call(this, method, url, ...rest);
                    };

                    const originalSend = XMLHttpRequest.prototype.send;
                    XMLHttpRequest.prototype.send = function(...args) {
                        try {
                            this.setRequestHeader('X-Admin-Token', activeToken);
                        } catch(e) {}
                        return originalSend.apply(this, args);
                    };
                }
            } catch(e) {}
        })();
    </script>

    <script>
        function broadcastMediaUpdate() {
            const timestamp = Date.now();
            try {
                localStorage.setItem('knotelle_media_updated', timestamp.toString());
                localStorage.setItem('knotelle_media_sync', timestamp.toString());
                localStorage.setItem('knotelle_contact_updated', timestamp.toString());
                localStorage.setItem('knotelle_about_updated', timestamp.toString());
            } catch(e){}
            try {
                if ('BroadcastChannel' in window) {
                    const channel = new BroadcastChannel('knotelle_media_sync');
                    channel.postMessage({ type: 'MEDIA_UPDATED', timestamp: timestamp });
                    setTimeout(() => {
                        try { channel.close(); } catch(e) {}
                    }, 200);
                }
            } catch(e){}
        }
    </script>

    @if(session('success'))
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            toastr.success("{{ session('success') }}");
            broadcastMediaUpdate();
        });
    </script>
    @endif
    @if(session('error'))
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            toastr.error("{{ session('error') }}");
        });
    </script>
    @endif

    @stack('scripts')
</body>

</html>
