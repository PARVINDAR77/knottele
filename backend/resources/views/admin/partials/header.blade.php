<!-- Top Navigation -->
<nav class="bg-gradient-to-r from-white/95 via-red-50/95 to-stone-50/95 backdrop-blur-lg border-b border-red-100/50 shadow-lg px-4 py-3 z-30 sticky top-0"
    style="box-shadow: 0 4px 20px rgba(14, 165, 233, 0.08), 0 2px 8px rgba(28, 25, 23, 0.04);">
    <div class="flex justify-between items-center gap-2 sm:gap-4">
        <div class="flex items-center">
            <button id="sidebarToggle" class="text-stone-500 hover:text-red-600 focus:outline-none md:hidden transition-colors duration-200 hover:bg-red-50/50 p-2 rounded-lg">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-lg sm:text-xl font-bold bg-gradient-to-r from-stone-800 to-stone-600 bg-clip-text text-transparent ml-2 sm:ml-4 capitalize truncate max-w-[150px] sm:max-w-none">
                {{ str_replace('_', ' ', Request::segment(Request::segment(1) === 'knottele' ? 3 : 2) ?? 'Dashboard') }}
            </h1>
        </div>
        <div class="flex items-center space-x-2 sm:space-x-4">
            <!-- <div class="relative hidden sm:block">
                <input type="text" placeholder="Search..."
                    class="pl-10 pr-4 py-2 rounded-xl border-2 border-red-100/80 bg-white/80 text-stone-900 placeholder-stone-400 
                           focus:outline-none focus:ring-4 focus:ring-red-500/20 focus:border-red-500 focus:bg-white
                           transition-all duration-200 font-medium"
                    style="box-shadow: 0 2px 8px rgba(14, 165, 233, 0.06);">
                <i class="fas fa-search absolute left-3 top-3 text-stone-400"></i>
            </div> -->
            <!-- <div class="relative block">
                <a href="{{ route('admin.notifications.index') }}"
                    class="text-gray-500 hover:text-gray-700 relative">
                    <i class="fas fa-bell text-xl"></i>
                    <span
                        class="absolute -top-1 -right-1 bg-rose-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">3</span>
                </a>
            </div> -->
            <div class="relative block">
                <div class="relative group">
                    <button
                        class="flex items-center space-x-2 text-stone-700 hover:text-red-600 admin-menu-toggle 
                               px-3 py-2 rounded-xl hover:bg-white/60 transition-all duration-200">
                        <div
                            class="w-8 h-8 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center text-white font-bold shadow-md"
                            style="box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);">
                            {{ strtoupper(substr(Auth::guard('admin')->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <span class="font-semibold">{{ Auth::guard('admin')->user()->name ?? 'Admin' }}</span>
                        <i class="fas fa-chevron-down text-sm transition-transform duration-200 group-hover:rotate-180"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        class="absolute right-0 mt-2 w-48 bg-white/95 backdrop-blur-lg rounded-xl shadow-lg border border-red-100/50 py-2 z-50 hidden admin-menu"
                        style="box-shadow: 0 8px 24px rgba(14, 165, 233, 0.12), 0 4px 12px rgba(28, 25, 23, 0.06);">
                        <a href="{{ route('admin.settings.index') }}"
                            class="block px-4 py-2.5 text-stone-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-stone-50 hover:text-red-600 
                                   transition-all duration-200 font-medium rounded-lg mx-2">
                            <i class="fas fa-user mr-2 text-red-500"></i> Profile
                        </a>
                        <a href="{{ route('admin.settings.index') }}"
                            class="block px-4 py-2.5 text-stone-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-stone-50 hover:text-red-600 
                                   transition-all duration-200 font-medium rounded-lg mx-2">
                            <i class="fas fa-cog mr-2 text-red-500"></i> Settings
                        </a>
                        <div class="border-t border-red-100/50 my-2"></div>
                        <form method="POST" action="{{ route('admin.logout') }}" id="logoutForm" onsubmit="try{localStorage.removeItem('admin_auth_token');sessionStorage.removeItem('admin_auth_token');}catch(e){}">
                            @csrf
                            <button type="submit"
                                class="block w-full text-left px-4 py-2.5 text-rose-600 hover:bg-gradient-to-r hover:from-rose-50 hover:to-red-50 
                                       transition-all duration-200 font-bold rounded-lg mx-2">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
