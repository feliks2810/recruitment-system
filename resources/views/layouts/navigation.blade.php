{{-- resources/views/layouts/navigation.blade.php --}}
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('images/logo-patria.png') }}" alt="Patria Maritim Perkasa" class="h-10 w-auto">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    
                    <x-nav-link :href="route('candidates.index')" :active="request()->routeIs('candidates.*')">
                        {{ __('Kandidat') }}
                    </x-nav-link>

                    @if(auth()->user()->hasRole('team_hc'))
                        <x-nav-link :href="route('proposals.index')" :active="request()->routeIs('proposals.index')">
                            {{ __('Vacancy Proposals') }}
                            @if(session('pending_proposals_count') > 0)
                                <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                                    {{ session('pending_proposals_count') }}
                                </span>
                            @endif
                        </x-nav-link>
                    @endif

                    @if(auth()->user()->hasRole('department'))
                        <x-nav-link :href="route('proposals.create')" :active="request()->routeIs('proposals.create')">
                            {{ __('Propose Vacancy') }}
                        </x-nav-link>
                    @endif

                    

                    @if(auth()->user()->hasRole('admin'))
                        <x-nav-link :href="route('import.index')" :active="request()->routeIs('import.*')">
                            {{ __('Import Excel') }}
                        </x-nav-link>
                    @endif

                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('team_hc'))
                        <x-nav-link :href="route('statistics.index')" :active="request()->routeIs('statistics.*')">
                            {{ __('Statistik') }}
                        </x-nav-link>
                    @endif

                    @if(auth()->user()->hasRole('admin'))
                        <x-nav-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')">
                            {{ __('Manajemen Akun') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                                    <span class="text-blue-600 font-semibold text-sm">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="text-left">
                                    <div class="font-medium text-gray-800">{{ Auth::user()->name }}</div>
                                    <div class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', Auth::user()->role)) }}</div>
                                </div>
                            </div>
                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link href="#"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            
            <x-responsive-nav-link :href="route('candidates.index')" :active="request()->routeIs('candidates.*')">
                {{ __('Kandidat') }}
            </x-responsive-nav-link>

            

            @if(auth()->user()->isAdmin())
                <x-responsive-nav-link :href="route('import.index')" :active="request()->routeIs('import.*')">
                    {{ __('Import Excel') }}
                </x-responsive-nav-link>
            @endif

            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('team_hc'))
                <x-responsive-nav-link :href="route('statistics.index')" :active="request()->routeIs('statistics.*')">
                    {{ __('Statistik') }}
                </x-responsive-nav-link>
            @endif

            @if(auth()->user()->isAdmin())
                <x-responsive-nav-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')">
                    {{ __('Manajemen Akun') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link href="#"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>