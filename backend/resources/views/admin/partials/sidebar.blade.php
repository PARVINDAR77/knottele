<?php
// Navigation configuration

$navItems = [
    'dashboard' => [
        'title' => 'Dashboard',
        'icon' => 'fas fa-home',
        'route' => 'admin.dashboard',
    ],

    'products' => [
        'title' => 'Products',
        'icon' => 'fas fa-cube',
        'route' => 'admin.products.index',
        'submenu' => [
            'all' => [
                'title' => 'All Products',
                'route' => 'admin.products.index',
            ],
            'create' => [
                'title' => 'Add New',
                'route' => 'admin.products.create',
            ],
        ],
    ],

    'categories' => [
        'title' => 'Categories',
        'icon' => 'fas fa-tags',
        'route' => 'admin.categories.index',
        'submenu' => [
            'all' => [
                'title' => 'All Categories',
                'route' => 'admin.categories.index',
            ],
            'create' => [
                'title' => 'Add New',
                'route' => 'admin.categories.create',
            ],
        ],
    ],

    'brands' => [
        'title' => 'Brands',
        'icon' => 'fas fa-trademark',
        'route' => 'admin.brands.index',
    ],

    'taxes' => [
        'title' => 'Taxes',
        'icon' => 'fas fa-percent',
        'route' => 'admin.taxes.index',
    ],

    // 'inventory' => [
    //     'title' => 'Inventory',
    //     'icon' => 'fas fa-boxes',
    //     'route' => 'admin.inventory.index',
    //     'submenu' => [
    //         'stock_levels' => [
    //             'title' => 'Stock Levels',
    //             'route' => 'admin.inventory.index',
    //         ],
    //         'adjust' => [
    //             'title' => 'Adjust Stock',
    //             'route' => 'admin.inventory.update',
    //             'params' => ['id' => 1],
    //         ],
    //         'history' => [
    //             'title' => 'History',
    //             'route' => 'admin.inventory.history',
    //         ],
    //     ],
    // ],

    'orders' => [
        'title' => 'Orders',
        'icon' => 'fas fa-shopping-cart',
        'route' => 'admin.orders.index',
    ],

    'custom_orders' => [
        'title' => 'Custom Orders',
        'icon' => 'fas fa-wand-magic-sparkles',
        'route' => 'admin.custom_orders.index',
    ],

    'offers' => [
        'title' => 'Offers',
        'icon' => 'fas fa-percentage',
        'route' => 'admin.offers.index',
        'submenu' => [
            'all' => [
                'title' => 'All Offers',
                'route' => 'admin.offers.index',
            ],
            'create' => [
                'title' => 'Add New',
                'route' => 'admin.offers.create',
            ],
        ],
    ],

    'users' => [
        'title' => 'Customers',
        'icon' => 'fas fa-users',
        'route' => 'admin.users.index',
    ],

    'otp_logs' => [
        'title' => 'OTP & SMS Logs',
        'icon' => 'fas fa-sms',
        'route' => 'admin.otp_logs.index',
    ],

    'media' => [
        'title' => 'Media',
        'icon' => 'fas fa-images',
        'route' => 'admin.media.index',
    ],

    // 'reports' => [
    //     'title' => 'Reports',
    //     'icon' => 'fas fa-chart-bar',
    //     'route' => 'admin.reports.index',
    //     'submenu' => [
    //         'sales' => [
    //             'title' => 'Sales',
    //             'route' => 'admin.reports.sales',
    //         ],
    //         'customers' => [
    //             'title' => 'Customers',
    //             'route' => 'admin.reports.customers',
    //         ],
    //         'products' => [
    //             'title' => 'Products',
    //             'route' => 'admin.reports.products',
    //         ],
    //     ],
    // ],

    // 'shipping' => [
    //     'title' => 'Shipping',
    //     'icon' => 'fas fa-shipping-fast',
    //     'route' => 'admin.shipping.index',
    //     'submenu' => [
    //         'zones' => [
    //             'title' => 'Zones',
    //             'route' => 'admin.shipping.index',
    //         ],
    //         'charges' => [
    //             'title' => 'Charges',
    //             'route' => 'admin.shipping.charges',
    //         ],
    //     ],
    // ],

    'crm' => [
        'title' => 'CRM',
        'icon' => 'fas fa-bullhorn',
        'route' => 'admin.crm.index',
        'submenu' => [
            'banners' => ['title' => 'Sliders', 'route' => 'admin.banners.index'],
            'home_sections' => ['title' => 'Home Page', 'route' => 'admin.home-sections.index'],
        ],
    ],

    // 'pages' => [
    //     'title' => 'Pages',
    //     'icon' => 'fas fa-file-alt',
    //     'route' => 'admin.pages.index',
    // ],

    'reviews' => [
        'title' => 'Reviews',
        'icon' => 'fas fa-star',
        'route' => 'admin.reviews.index',
    ],

    'settings' => [
        'title' => 'Settings',
        'icon' => 'fas fa-cog',
        'route' => 'admin.settings.index',
    ],
];

// Get current route
$currentRoute = Route::currentRouteName();
$isActive = function ($route, $params = []) use ($currentRoute) {
    if ($currentRoute === $route) return true;
    if ($currentRoute === 'knottele.' . $route) return true;
    if ($currentRoute && str_ends_with($currentRoute, $route)) return true;
    return false;
};
?>

<aside id="sidebar"
    class="fixed left-0 top-0 z-40 h-screen bg-gradient-to-br from-white/95 via-red-50/95 to-stone-50/95 backdrop-blur-lg
              border-r border-red-100/50 shadow-lg group transition-all duration-300
              overflow-hidden sidebar-collapsed
              -translate-x-full md:translate-x-0"
    style="box-shadow: 0 8px 32px rgba(14, 165, 233, 0.08), 0 4px 16px rgba(28, 25, 23, 0.05);">

    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="logo-container p-6 border-b border-stone-200/50 relative">
            <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center rounded-xl bg-[#FFF9F6] p-1 shadow-sm" style="height: 48px; width: auto;">
                    <img src="{{ asset('images/logo/Logo_1.png') }}" class="h-full w-auto object-contain" alt="KNOTELLE Logo">
                </div>
                <span
                    class="text-xl font-bold bg-gradient-to-r from-[#913638] to-[#74292B] bg-clip-text text-transparent transition-all duration-300
                             text-expandable whitespace-nowrap tracking-wide">
                    KNOTELLE
                </span>
            </div>
            <button id="sidebarClose" class="text-stone-500 hover:text-[#913638] md:hidden transition-colors duration-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            @foreach ($navItems as $key => $item)
                @php
                    $hasSubmenu = isset($item['submenu']);
                    $active = $isActive($item['route'] ?? '');
                    $submenuOpen = $active;
                @endphp

                <div class="relative {{ $submenuOpen ? 'submenu-open' : '' }}">
                    <!-- Parent Menu -->
                    <a href="{{ $hasSubmenu ? '#' : route($item['route']) }}"
                        class="parent-link flex items-center gap-3 px-4 py-3 rounded-xl
                               text-stone-600 hover:bg-white/60 hover:text-red-600
                               transition-all duration-200 group-hover:pr-6
                               {{ $active ? 'bg-gradient-to-r from-red-50/80 to-red-100/80 border-l-4 border-red-500 text-red-600 font-bold shadow-sm' : '' }}"
                        style="{{ $active ? 'box-shadow: 0 4px 12px rgba(14, 165, 233, 0.12);' : '' }}">
                        <i
                            class="{{ $item['icon'] }} text-xl min-w-6 text-center transition-colors duration-200
                                  {{ $active ? 'text-red-500' : 'text-stone-400 group-hover:text-red-500' }}">
                        </i>
                        <span class="font-semibold transition-all duration-300 text-expandable whitespace-nowrap">
                            {{ $item['title'] }}
                        </span>
                        @if ($hasSubmenu)
                            <i
                                class="fas fa-chevron-down text-xs ml-auto transition-all duration-300
                                      {{ $submenuOpen ? 'transform rotate-180 text-red-500' : 'text-stone-400' }}"></i>
                        @endif
                    </a>

                    <!-- Submenu -->
                    @if ($hasSubmenu)
                        <div class="submenu ml-8 mt-2 space-y-1 {{ $submenuOpen ? 'submenu-open' : '' }}">
                            @foreach ($item['submenu'] as $subKey => $subItem)
                                @php
                                    $subActive = $isActive($subItem['route']);
                                @endphp
                                <a href="{{ route($subItem['route'], $subItem['params'] ?? []) }}"
                                    class="submenu-link block px-4 py-2 text-sm rounded-lg
                                          {{ $subActive ? 'bg-gradient-to-r from-white/70 to-red-50/70 text-red-600 font-bold border-l-2 border-red-500' : 'text-stone-500 hover:bg-white/50 hover:text-red-600' }}
                                          transition-all duration-200 text-expandable whitespace-nowrap">
                                    {{ $subItem['title'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>

        <!-- Sidebar Footer -->
        <button id="sidebarToggleMode"
            class="w-full mt-4 bg-gradient-to-r from-red-100 to-red-200 text-red-700 py-3 rounded-xl text-sm font-bold hover:from-red-200 hover:to-red-300 transition-all duration-200 shadow-sm"
            style="box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15);">
            <i class="fas fa-expand-arrows-alt mr-2"></i>Expand
        </button>
    </div>
</aside>

<!-- Overlay for mobile -->
<div id="sidebarOverlay" class="fixed inset-0 bg-stone-900/50 z-30 md:hidden hidden backdrop-blur-sm"></div>
