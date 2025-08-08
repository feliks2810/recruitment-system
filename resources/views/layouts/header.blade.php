<header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
    <div class="flex items-center justify-between">
        <!-- Mobile Hamburger & Title -->
        <div class="flex items-center gap-4">
            <!-- Mobile menu button -->
            <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                <i class="fas fa-bars text-lg"></i>
            </button>
            
            <!-- Page Title -->
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                <p class="text-sm text-gray-600 hidden sm:block">@yield('page-subtitle', 'Overview rekrutmen dan kandidat')</p>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="flex items-center gap-2 sm:gap-4">
            <!-- Year Filter (if needed on specific pages) -->
            @stack('header-filters')

            <!-- User Profile (Desktop) -->
            <div class="hidden lg:flex items-center gap-3">
                <div class="text-right">
                    <span class="text-sm font-medium text-gray-700 block">{{ Auth::user()->name }}</span>
                    <span class="text-xs text-gray-500">{{ Auth::user()->roles->first()->name ?? 'User' }}</span>
                </div>
                <button class="w-9 h-9 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                    <span class="text-sm font-semibold text-white">{{ substr(Auth::user()->name, 0, 2) }}</span>
                </button>
            </div>

            <!-- User Profile (Mobile) -->
            <div class="lg:hidden">
                <div class="flex items-center gap-2">
                    <button class="w-8 h-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-white">{{ substr(Auth::user()->name, 0, 2) }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>