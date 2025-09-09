<aside id="sidebar" class="bg-white h-full border-r border-gray-200 flex flex-col fixed lg:static z-50
    transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] will-change-transform shadow-lg lg:shadow-none
    w-64 -translate-x-full lg:translate-x-0
    lg:sidebar-collapsed:w-0 lg:sidebar-collapsed:translate-x-0">
    <!-- Logo -->
    <div class="p-6 flex justify-center border-b border-gray-100 bg-gradient-to-b from-blue-50 to-white">
        <div class="w-full max-w-[100px] transition-all duration-300">
            <img src="{{ asset('images/Logo Patria.png') }}" alt="Logo Patria" class="w-28 h-auto object-contain">
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 overflow-y-auto">
        <div class="space-y-2">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} active:scale-95">
                <i class="fas fa-th-large text-sm w-4 {{ request()->routeIs('dashboard') ? 'text-white' : 'group-hover:text-blue-600' }}"></i>
                <span class="font-medium sidebar-text">Dashboard</span>
            </a>

            <!-- Kandidat -->
            @canany(['view-candidates', 'view-own-department-candidates'])
            <a href="{{ route('candidates.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('candidates.*') ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} active:scale-95">
                <i class="fas fa-users text-sm w-4 {{ request()->routeIs('candidates.*') ? 'text-white' : 'group-hover:text-green-600' }}"></i>
                <span class="font-medium sidebar-text">Kandidat</span>
            </a>
            @endcanany

            <!-- Import Excel -->
            @can('import-excel')
            <a href="{{ route('import.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('import.*') ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} active:scale-95">
                <i class="fas fa-upload text-sm w-4 {{ request()->routeIs('import.*') ? 'text-white' : 'group-hover:text-purple-600' }}"></i>
                <span class="font-medium sidebar-text">Import Excel</span>
            </a>
            @endcan

            <!-- Statistik -->
            @can('view-statistics')
            <a href="{{ route('statistics.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('statistics.*') ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} active:scale-95">
                <i class="fas fa-chart-bar text-sm w-4 {{ request()->routeIs('statistics.*') ? 'text-white' : 'group-hover:text-yellow-600' }}"></i>
                <span class="font-medium sidebar-text">Statistik</span>
            </a>
            @endcan

            <!-- Manajemen Akun -->
            @can('manage-users')
            <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('accounts.*') ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/25' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} active:scale-95">
                <i class="fas fa-user-cog text-sm w-4 {{ request()->routeIs('accounts.*') ? 'text-white' : 'group-hover:text-indigo-600' }}"></i>
                <span class="font-medium sidebar-text">Manajemen Akun</span>
            </a>
            @endcan
        </div>
    </nav>

    <!-- User Profile & Logout -->
    <div class="border-t border-gray-100 p-4 bg-gray-50">
        <!-- User Info -->
        <div class="flex items-center gap-3 mb-4 px-3 py-3 bg-white rounded-xl shadow-sm user-info-container">
            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-sm font-semibold text-white">{{ substr(Auth::user()->name, 0, 2) }}</span>
            </div>
            <div class="min-w-0 flex-1 sidebar-text">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ Auth::user()->roles->first()->name ?? 'User' }}</p>
            </div>
        </div>
        
        <!-- Logout Button -->
        <button type="submit" form="logout-form" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200 w-full group active:scale-95">
            <i class="fas fa-sign-out-alt text-sm w-4 group-hover:text-red-600"></i>
            <span class="font-medium sidebar-text">Logout</span>
        </button>
        
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</aside>

