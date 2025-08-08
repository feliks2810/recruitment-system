@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Recruitment')
@section('page-subtitle', 'Overview rekrutmen dan kandidat')

@push('header-filters')
<div class="flex items-center gap-2">
    <label for="year" class="text-sm font-medium text-gray-700 hidden sm:block">Tahun:</label>
    <select name="year" id="yearFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[80px]">
        <option value="2025" selected>2025</option>
        <option value="2024">2024</option>
        <option value="2023">2023</option>
        <option value="2022">2022</option>
    </select>
</div>
@endpush

@section('content')
<!-- Quick Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
    <!-- Total Kandidat -->
    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-600">Total Kandidat</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ $stats['total_candidates'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users text-blue-600 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <span class="bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded-full font-medium">Semua kandidat</span>
        </div>
    </div>

    <!-- Lulus -->
    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-600">Lulus</p>
                <p class="text-2xl sm:text-3xl font-bold text-green-600 truncate">{{ $stats['candidates_passed'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-circle text-green-600 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded-full font-medium">{{ $stats['total_candidates'] > 0 ? round(($stats['candidates_passed'] / $stats['total_candidates']) * 100, 1) : 0 }}%</span>
            <span class="text-xs text-gray-500">dari total</span>
        </div>
    </div>

    <!-- Dalam Proses -->
    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-600">Dalam Proses</p>
                <p class="text-2xl sm:text-3xl font-bold text-yellow-600 truncate">{{ $stats['candidates_in_process'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock text-yellow-600 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <span class="bg-yellow-100 text-yellow-600 text-xs px-2 py-1 rounded-full font-medium">{{ $stats['total_candidates'] > 0 ? round(($stats['candidates_in_process'] / $stats['total_candidates']) * 100, 1) : 0 }}%</span>
            <span class="text-xs text-gray-500">sedang berjalan</span>
        </div>
    </div>

    <!-- Tidak Lulus -->
    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-600">Tidak Lulus</p>
                <p class="text-2xl sm:text-3xl font-bold text-red-600 truncate">{{ $stats['candidates_failed'] ?? 0 }}</p>
            </div>
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-times-circle text-red-600 text-lg sm:text-xl"></i>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full font-medium">{{ $stats['total_candidates'] > 0 ? round(($stats['candidates_failed'] / $stats['total_candidates']) * 100, 1) : 0 }}%</span>
            <span class="text-xs text-gray-500">dari total</span>
        </div>
    </div>
</div>

<!-- Recent Activity & Chart -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6 mb-6">
    <!-- Recent Candidates -->
    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Kandidat Terbaru</h3>
            <a href="{{ route('candidates.index') }}" class="text-blue-600 text-sm hover:text-blue-700 transition-colors">Lihat Semua</a>
        </div>
        <div class="space-y-3">
            @forelse($recent_candidates ?? [] as $candidate)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-medium text-blue-600">{{ substr($candidate->nama, 0, 2) }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-900 truncate">{{ $candidate->nama }}</p>
                        <p class="text-sm text-gray-600 truncate">{{ $candidate->vacancy_airsys }}</p>
                    </div>
                </div>
                <span class="text-xs text-gray-500 flex-shrink-0 ml-2">{{ $candidate->created_at->diffForHumans() }}</span>
            </div>
            @empty
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-users text-3xl sm:text-4xl mb-2 text-gray-300"></i>
                <p class="text-sm">Belum ada kandidat terbaru</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Process Distribution -->
    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Tahapan</h3>
        <div class="h-64">
            <canvas id="processChart"></canvas>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Tambah Kandidat -->
        <a href="{{ route('candidates.create') }}" class="group flex items-center gap-3 p-4 border-2 border-dashed border-blue-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-all duration-200">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                <i class="fas fa-user-plus text-blue-600"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-900">Tambah Kandidat</p>
                <p class="text-sm text-gray-600">Daftarkan kandidat baru</p>
            </div>
        </a>

        <!-- Import Data -->
        @can('import-excel')
        <a href="{{ route('import.index') }}" class="group flex items-center gap-3 p-4 border-2 border-dashed border-green-300 rounded-lg hover:border-green-400 hover:bg-green-50 transition-all duration-200">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                <i class="fas fa-file-excel text-green-600"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-900">Import Data</p>
                <p class="text-sm text-gray-600">Import dari Excel</p>
            </div>
        </a>
        @endcan

        <!-- Statistik -->
        <a href="{{ route('statistics.index') }}" class="group flex items-center gap-3 p-4 border-2 border-dashed border-purple-300 rounded-lg hover:border-purple-400 hover:bg-purple-50 transition-all duration-200">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                <i class="fas fa-chart-line text-purple-600"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-900">Lihat Statistik</p>
                <p class="text-sm text-gray-600">Analisis data recruitment</p>
            </div>
        </a>
    </div>
</div>
@endsection

@push('scripts')
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
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                }
            },
            cutout: '60%',
        },
    });

    // Year filter functionality
    document.getElementById('yearFilter').addEventListener('change', function() {
        const selectedYear = this.value;
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('year', selectedYear);
        
        // Show loading state
        document.body.style.cursor = 'wait';
        
        // Redirect with year parameter
        window.location.href = currentUrl.toString();
    });

    // Set current year from URL parameter
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const yearFromUrl = urlParams.get('year');
        if (yearFromUrl) {
            document.getElementById('yearFilter').value = yearFromUrl;
        }
    });
</script>
@endpush