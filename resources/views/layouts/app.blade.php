<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>@yield('title', 'Dashboard') - Patria Maritim Perkasa</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Recruitment System">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#3b82f6">
    <meta name="msapplication-tap-highlight" content="no">
    
    <!-- PWA Icons -->
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon.png">
    <link rel="apple-touch-icon" href="/images/favicon.png">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Ensure content follows sidebar */
        html, body {
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        #sidebar {
            will-change: transform;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        }

        /* Desktop sidebar - Fixed positioning */
        @media (min-width: 1024px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 16rem;
                min-width: 16rem;
                max-width: 16rem;
                overflow-y: auto;
                overflow-x: hidden;
                z-index: 30;
                transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            }
            
            /* Collapsed state */
            body.sidebar-collapsed #sidebar {
                transform: translateX(-100%);
            }
            
            #main-content {
                margin-left: 16rem;
                transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1), width 0.3s cubic-bezier(0.4,0,0.2,1);
                min-height: 100vh;
                width: calc(100% - 16rem);
            }
            
            body.sidebar-collapsed #main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        /* Mobile sidebar */
        @media (max-width: 1023px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 16rem;
                overflow-y: auto;
                overflow-x: hidden;
                z-index: 50;
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            }
            
            #sidebar:not(.-translate-x-full) {
                transform: translateX(0);
            }
            
            #main-content {
                margin-left: 0;
                min-height: 100vh;
            }
        }

        /* Custom scrollbar untuk sidebar */
        #sidebar::-webkit-scrollbar {
            width: 6px;
        }

        #sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        #sidebar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 3px;
        }

        #sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.7);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    <!-- Sidebar -->
    @include('layouts.sidebar')

    <!-- Main Content Wrapper -->
    <div id="main-content" class="flex flex-col min-h-screen">
        <!-- Header -->
        @include('layouts.header')

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden w-full">
            <div class="p-4 sm:p-6 w-full max-w-full">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 transition-opacity duration-300 ease-in-out opacity-0 pointer-events-none z-40 lg:hidden"></div>

    @stack('scripts')
    
    <script>
        // Mobile sidebar toggle functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                // Close sidebar
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.classList.add('pointer-events-none');
                }, 300);
                document.body.classList.remove('overflow-hidden');
            } else {
                // Open sidebar
                overlay.classList.remove('pointer-events-none');
                overlay.style.opacity = '0.5';
                document.body.classList.add('overflow-hidden');
            }
        }

        // Close sidebar when clicking overlay
        document.getElementById('sidebar-overlay')?.addEventListener('click', toggleSidebar);

        // Desktop sidebar toggle
        function toggleDesktopSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
            
            if (document.body.classList.contains('sidebar-collapsed')) {
                localStorage.setItem('sidebarState', 'collapsed');
            } else {
                localStorage.setItem('sidebarState', 'expanded');
            }
        }

        // On page load, check sidebar state from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth >= 1024 && localStorage.getItem('sidebarState') === 'collapsed') {
                document.body.classList.add('sidebar-collapsed');
            }
        });

        // Handle sidebar visibility on window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            if (window.innerWidth >= 1024) {
                // Desktop mode
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.add('pointer-events-none');
                overlay.style.opacity = '0';
                document.body.classList.remove('overflow-hidden');
            } else {
                // Mobile mode - close sidebar
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('pointer-events-none');
                overlay.style.opacity = '0';
                document.body.classList.remove('overflow-hidden');
            }
        });
    </script>
</body>
</html>