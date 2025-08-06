<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Dashboard - Patria Maritim Perkasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex">
    <!-- Sidebar -->
    <aside class="bg-white w-64 min-h-screen border-r border-gray-200 flex flex-col">
        <!-- Logo -->
        <div class="p-4 flex justify-center">
            <img src="{{ asset('images/Logo Patria.png') }}" alt="Logo Patria" class="w-30 h-auto object-contain">
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-4">
            <div class="space-y-2">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 font-medium">
                    <i class="fas fa-th-large text-sm"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('candidates.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-users text-sm"></i>
                    <span>Kandidat</span>
                </a>
                @if (Auth::user()->isAdmin())
                <a href="{{ route('import.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-upload text-sm"></i>
                    <span>Import Excel</span>
                </a>
                @endif
                <a href="{{ route('statistics.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-chart-bar text-sm"></i>
                    <span>Statistik</span>
                </a>
                @if (Auth::user()->isAdmin())
                <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-user-cog text-sm"></i>
                    <span>Manajemen Akun</span>
                </a>
                @endif
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-sign-out-alt text-sm"></i>
                    <span>Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Dashboard Recruitment</h1>
                    <p class="text-sm text-gray-600">Overview rekrutmen dan kandidat</p>
                </div>
                <div class="flex items-center gap-4">
                    <select name="year" id="year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                    </select>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                        <button class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-blue-600">{{ substr(Auth::user()->name, 0, 2) }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 p-6">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Total Kandidat</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded-full font-medium">Semua kandidat</span>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Lulus</p>
                            <p class="text-3xl font-bold text-green-600">{{ $stats['lulus'] ?? 0 }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded-full font-medium">{{ $stats['total'] > 0 ? round(($stats['lulus'] / $stats['total']) * 100, 1) : 0 }}%</span>
                        <span class="text-xs text-gray-500">dari total</span>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Dalam Proses</p>
                            <p class="text-3xl font-bold text-yellow-600">{{ $stats['proses'] ?? 0 }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="bg-yellow-100 text-yellow-600 text-xs px-2 py-1 rounded-full font-medium">{{ $stats['total'] > 0 ? round(($stats['proses'] / $stats['total']) * 100, 1) : 0 }}%</span>
                        <span class="text-xs text-gray-500">sedang berjalan</span>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Tidak Lulus</p>
                            <p class="text-3xl font-bold text-red-600">{{ $stats['tidak_lulus'] ?? 0 }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full font-medium">{{ $stats['total'] > 0 ? round(($stats['tidak_lulus'] / $stats['total']) * 100, 1) : 0 }}%</span>
                        <span class="text-xs text-gray-500">dari total</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Recent Candidates -->
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Kandidat Terbaru</h3>
                        <a href="{{ route('candidates.index') }}" class="text-blue-600 text-sm hover:text-blue-700">Lihat Semua</a>
                    </div>
                    <div class="space-y-3">
                        @forelse($recent_candidates ?? [] as $candidate)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-600">{{ substr($candidate->nama, 0, 2) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium">{{ $candidate->nama }}</p>
                                    <p class="text-sm text-gray-600">{{ $candidate->vacancy_airsys }}</p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500">{{ $candidate->created_at->diffForHumans() }}</span>
                        </div>
                        @empty
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-users text-4xl mb-2"></i>
                            <p>Belum ada kandidat terbaru</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Process Distribution -->
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Tahapan Saat Ini</h3>
                    <div class="h-64">
                        <canvas id="processChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('candidates.create') }}" class="flex items-center gap-3 p-4 border-2 border-dashed border-blue-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-plus text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Tambah Kandidat Baru</p>
                            <p class="text-sm text-gray-600">Daftarkan kandidat baru</p>
                        </div>
                    </a>

                    @if (Auth::user()->isAdmin())
                    <a href="{{ route('import.index') }}" class="flex items-center gap-3 p-4 border-2 border-dashed border-green-300 rounded-lg hover:border-green-400 hover:bg-green-50 transition-colors">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-excel text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Import Data</p>
                            <p class="text-sm text-gray-600">Import dari Excel</p>
                        </div>
                    </a>
                    @endif

                    <a href="{{ route('statistics.index') }}" class="flex items-center gap-3 p-4 border-2 border-dashed border-purple-300 rounded-lg hover:border-purple-400 hover:bg-purple-50 transition-colors">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Lihat Statistik</p>
                            <p class="text-sm text-gray-600">Analisis data recruitment</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Process Distribution Chart
        const processData = @json($process_distribution ?? []);
        
        new Chart(document.getElementById('processChart'), {
            type: 'doughnut',
            data: {
                labels: processData.map(d => d.stage),
                datasets: [{
                    data: processData.map(d => d.count),
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981', 
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#06b6d4'
                    ],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                        }
                    },
                },
            },
        });
    </script>
</body>
</html>