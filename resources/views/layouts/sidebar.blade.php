<!-- Sidebar -->
<aside class="bg-white w-64 min-h-screen shadow-lg border-r border-gray-200 flex flex-col">
    <!-- Logo/Brand -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-anchor text-white text-lg"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-900 text-base">Patria Maritim</h2>
                <p class="text-xs text-gray-500">Recruitment System</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center gap-3 px-4 py-3 text-sm rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-50 to-blue-100 text-blue-700 font-semibold shadow-sm border border-blue-200' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
            <i class="fas fa-chart-line w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-400' }}"></i>
            <span>Dashboard</span>
        </a>

        <!-- Candidates -->
        <a href="{{ route('candidates.index') }}" 
           class="flex items-center gap-3 px-4 py-3 text-sm rounded-xl transition-all duration-200 {{ request()->routeIs('candidates.*') ? 'bg-gradient-to-r from-green-50 to-green-100 text-green-700 font-semibold shadow-sm border border-green-200' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
            <i class="fas fa-users w-5 h-5 {{ request()->routeIs('candidates.*') ? 'text-green-600' : 'text-gray-400' }}"></i>
            <span>Kandidat</span>
        </a>

        <!-- Import (hanya untuk admin dan team_hc) -->
        @if(auth()->user()->hasRole(['admin', 'team_hc']))
            <a href="{{ route('import.index') }}" 
               class="flex items-center gap-3 px-4 py-3 text-sm rounded-xl transition-all duration-200 {{ request()->routeIs('import.*') ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 text-yellow-700 font-semibold shadow-sm border border-yellow-200' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                <i class="fas fa-upload w-5 h-5 {{ request()->routeIs('import.*') ? 'text-yellow-600' : 'text-gray-400' }}"></i>
                <span>Import Data</span>
            </a>
        @endif

        <!-- Statistics (hanya untuk admin dan team_hc) -->
        @if(auth()->user()->hasRole(['admin', 'team_hc']))
            <a href="{{ route('statistics.index') }}" 
               class="flex items-center gap-3 px-4 py-3 text-sm rounded-xl transition-all duration-200 {{ request()->routeIs('statistics.*') ? 'bg-gradient-to-r from-purple-50 to-purple-100 text-purple-700 font-semibold shadow-sm border border-purple-200' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                <i class="fas fa-chart-bar w-5 h-5 {{ request()->routeIs('statistics.*') ? 'text-purple-600' : 'text-gray-400' }}"></i>
                <span>Statistik</span>
            </a>
        @endif

        <!-- Accounts (hanya untuk admin) -->
        @if(auth()->user()->hasRole('admin'))
            <a href="{{ route('accounts.index') }}" 
               class="flex items-center gap-3 px-4 py-3 text-sm rounded-xl transition-all duration-200 {{ request()->routeIs('accounts.*') ? 'bg-gradient-to-r from-red-50 to-red-100 text-red-700 font-semibold shadow-sm border border-red-200' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                <i class="fas fa-user-cog w-5 h-5 {{ request()->routeIs('accounts.*') ? 'text-red-600' : 'text-gray-400' }}"></i>
                <span>Manajemen Akun</span>
            </a>
        @endif

        <!-- Divider -->
        <div class="border-t border-gray-200 my-4"></div>

        <!-- Export (hanya untuk admin dan team_hc) -->
        @if(auth()->user()->hasRole(['admin', 'team_hc']))
            <a href="{{ route('candidates.export') }}" 
               class="flex items-center gap-3 px-4 py-3 text-sm rounded-xl text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                <i class="fas fa-download w-5 h-5 text-gray-400"></i>
                <span>Export Data</span>
            </a>
        @endif
    </nav>

    <!-- User Info & Logout -->
    <div class="border-t border-gray-200 p-4">
        <!-- User Info -->
        <div class="flex items-center gap-3 mb-4 p-3 bg-gray-50 rounded-xl">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center">
                <span class="text-sm font-bold text-blue-700">{{ Auth::user()->initials }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-500 capitalize truncate">{{ Auth::user()->role ?? 'User' }}</p>
            </div>
        </div>

        <!-- Logout Button -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" 
                    class="flex items-center gap-3 px-4 py-3 text-sm rounded-xl text-red-600 hover:bg-red-50 hover:text-red-700 w-full text-left transition-all duration-200">
                <i class="fas fa-sign-out-alt w-5 h-5"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>