<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
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
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- PWA Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="/images/Logo Patria.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/Logo Patria.png">
    <link rel="apple-touch-icon" href="/images/Logo Patria.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            @include('layouts.header')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="p-4 sm:p-6">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>

    @stack('scripts')
    
    <script>
        // Mobile sidebar toggle functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const isHidden = sidebar.classList.contains('-translate-x-full');
            
            if (isHidden) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        // Close sidebar when clicking overlay
        document.getElementById('sidebar-overlay').addEventListener('click', toggleSidebar);

        // Close sidebar on window resize if screen becomes large
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                document.getElementById('sidebar').classList.remove('-translate-x-full');
                document.getElementById('sidebar-overlay').classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    </script>
</body>
</html>