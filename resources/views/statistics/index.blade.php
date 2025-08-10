@extends('layouts.app')

@section('title', 'Statistik & Analytics')
@section('page-title', 'Statistik & Analytics')
@section('page-subtitle', 'Dashboard analisis data rekrutmen dan kinerja')

@push('header-filters')
<div class="flex flex-wrap gap-4">
    <div>
        <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
        <select id="year" class="border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            <option value="2025" {{ request('year', date('Y')) == '2025' ? 'selected' : '' }}>2025</option>
            <option value="2024" {{ request('year') == '2024' ? 'selected' : '' }}>2024</option>
            <option value="2023" {{ request('year') == '2023' ? 'selected' : '' }}>2023</option>
            <option value="2022" {{ request('year') == '2022' ? 'selected' : '' }}>2022</option>
        </select>
    </div>
    <div>
        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Data</label>
        <select id="type" class="border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            <option value="all">Semua</option>
            <option value="applications">Aplikasi</option>
            <option value="interviews">Interview</option>
            <option value="hired">Diterima</option>
        </select>
    </div>
    <div class="flex items-end">
        <button id="refreshData" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center gap-2">
            <i class="fas fa-sync-alt"></i>
            Refresh
        </button>
    </div>
</div>
@endpush

@push('styles')
<style>
    .stats-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .chart-container {
        position: relative;
        height: 320px;
    }
    .loading-spinner {
        display: none;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
    }
    .chart-loading .loading-spinner {
        display: block;
    }
    .chart-loading canvas {
        opacity: 0.3;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm stats-card">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Aplikasi</p>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($totalApplications ?? 0) }}</p>
                    <div class="mt-3 flex items-center">
                        @if(($applicationsTrend ?? 0) > 0)
                            <i class="fas fa-arrow-up text-green-600 text-xs mr-1"></i>
                            <span class="text-green-600 text-sm font-medium">+{{ $applicationsTrend ?? '0' }}%</span>
                        @elseif(($applicationsTrend ?? 0) < 0)
                            <i class="fas fa-arrow-down text-red-600 text-xs mr-1"></i>
                            <span class="text-red-600 text-sm font-medium">{{ $applicationsTrend ?? '0' }}%</span>
                        @else
                            <i class="fas fa-minus text-gray-600 text-xs mr-1"></i>
                            <span class="text-gray-600 text-sm font-medium">0%</span>
                        @endif
                        <span class="text-gray-500 text-sm ml-2">dari bulan lalu</span>
                    </div>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg">
                    <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm stats-card">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600 mb-1">Kandidat Aktif</p>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($activeCandidates ?? 0) }}</p>
                    <div class="mt-3 flex items-center">
                        @if(($candidatesTrend ?? 0) > 0)
                            <i class="fas fa-arrow-up text-green-600 text-xs mr-1"></i>
                            <span class="text-green-600 text-sm font-medium">+{{ $candidatesTrend ?? '0' }}%</span>
                        @elseif(($candidatesTrend ?? 0) < 0)
                            <i class="fas fa-arrow-down text-red-600 text-xs mr-1"></i>
                            <span class="text-red-600 text-sm font-medium">{{ $candidatesTrend ?? '0' }}%</span>
                        @else
                            <i class="fas fa-minus text-gray-600 text-xs mr-1"></i>
                            <span class="text-gray-600 text-sm font-medium">0%</span>
                        @endif
                        <span class="text-gray-500 text-sm ml-2">dari bulan lalu</span>
                    </div>
                </div>
                <div class="bg-green-50 p-3 rounded-lg">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm stats-card">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600 mb-1">Interview Selesai</p>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($completedInterviews ?? 0) }}</p>
                    <div class="mt-3 flex items-center">
                        @if(($interviewsTrend ?? 0) > 0)
                            <i class="fas fa-arrow-up text-green-600 text-xs mr-1"></i>
                            <span class="text-green-600 text-sm font-medium">+{{ $interviewsTrend ?? '0' }}%</span>
                        @elseif(($interviewsTrend ?? 0) < 0)
                            <i class="fas fa-arrow-down text-red-600 text-xs mr-1"></i>
                            <span class="text-red-600 text-sm font-medium">{{ $interviewsTrend ?? '0' }}%</span>
                        @else
                            <i class="fas fa-minus text-gray-600 text-xs mr-1"></i>
                            <span class="text-gray-600 text-sm font-medium">0%</span>
                        @endif
                        <span class="text-gray-500 text-sm ml-2">dari bulan lalu</span>
                    </div>
                </div>
                <div class="bg-yellow-50 p-3 rounded-lg">
                    <i class="fas fa-comments text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm stats-card">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600 mb-1">Tingkat Konversi</p>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($conversionRate ?? 0, 1) }}%</p>
                    <div class="mt-3 flex items-center">
                        @if(($conversionTrend ?? 0) > 0)
                            <i class="fas fa-arrow-up text-green-600 text-xs mr-1"></i>
                            <span class="text-green-600 text-sm font-medium">+{{ $conversionTrend ?? '0' }}%</span>
                        @elseif(($conversionTrend ?? 0) < 0)
                            <i class="fas fa-arrow-down text-red-600 text-xs mr-1"></i>
                            <span class="text-red-600 text-sm font-medium">{{ $conversionTrend ?? '0' }}%</span>
                        @else
                            <i class="fas fa-minus text-gray-600 text-xs mr-1"></i>
                            <span class="text-gray-600 text-sm font-medium">0%</span>
                        @endif
                        <span class="text-gray-500 text-sm ml-2">dari bulan lalu</span>
                    </div>
                </div>
                <div class="bg-purple-50 p-3 rounded-lg">
                    <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Trend -->
        @can('view-statistics')
        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Tren Bulanan Rekrutmen</h3>
                    <p class="text-sm text-gray-600">Jumlah aplikasi dan tingkat kelulusan per bulan</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 text-sm">
                        <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                        <span class="text-gray-600">Aplikasi</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <div class="w-3 h-3 bg-green-600 rounded-full"></div>
                        <span class="text-gray-600">Tingkat Lulus</span>
                    </div>
                </div>
            </div>
            <div class="chart-container" id="monthlyTrendContainer">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                </div>
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
        @endcan

        <!-- Department Distribution -->
        @can('view-statistics')
        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Distribusi per Departemen</h3>
                    <p class="text-sm text-gray-600">Jumlah kandidat berdasarkan departemen</p>
                </div>
                <button class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-expand-alt"></i>
                </button>
            </div>
            <div class="chart-container" id="departmentDistributionContainer">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                </div>
                <canvas id="departmentDistributionChart"></canvas>
            </div>
        </div>
        @endcan
    </div>

    <!-- Conversion Funnel -->
    <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Funnel Konversi Rekrutmen</h3>
                <p class="text-sm text-gray-600">Tingkat konversi di setiap tahapan proses seleksi</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                    <span class="text-gray-600">Jumlah Kandidat</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 bg-green-600 rounded-full"></div>
                    <span class="text-gray-600">Tingkat Konversi</span>
                </div>
                <button class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-download"></i>
                </button>
            </div>
        </div>
        <div class="chart-container" id="conversionFunnelContainer" style="height: 400px;">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
            </div>
            <canvas id="conversionFunnelChart"></canvas>
        </div>
    </div>

    <!-- Additional Analytics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Top Performing Departments -->
        @can('view-statistics')
        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Top Departemen</h4>
            <div class="space-y-3">
                @forelse($topDepartments ?? [] as $dept)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-900">{{ $dept['name'] ?? 'Unknown' }}</span>
                    </div>
                    <span class="text-sm text-gray-600">{{ $dept['count'] ?? 0 }}</span>
                </div>
                @empty
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-chart-bar text-2xl mb-2"></i>
                    <p class="text-sm">Belum ada data</p>
                </div>
                @endforelse
            </div>
        </div>
        @endcan

        <!-- Recent Activity -->
        @can('view-statistics')
        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Aktivitas Terbaru</h4>
            <div class="space-y-3">
                @forelse($recentActivities ?? [] as $activity)
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center mt-0.5">
                        <i class="fas fa-{{ $activity['icon'] ?? 'circle' }} text-blue-600 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-900">{{ $activity['message'] ?? 'No message' }}</p>
                        <p class="text-xs text-gray-500">{{ $activity['time'] ?? 'Unknown time' }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-clock text-2xl mb-2"></i>
                    <p class="text-sm">Belum ada aktivitas</p>
                </div>
                @endforelse
            </div>
        </div>
        @endcan

        <!-- Quick Actions -->
        @can('view-statistics')
        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h4>
            <div class="space-y-3">
                @can('edit-candidates')
                <a href="{{ route('candidates.create') ?? '#' }}" class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                    <i class="fas fa-user-plus text-blue-600"></i>
                    <span class="text-sm font-medium text-blue-900">Tambah Kandidat</span>
                </a>
                @endcan
                @can('import-excel')
                <a href="{{ route('import.index') ?? '#' }}" class="flex items-center gap-3 p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                    <i class="fas fa-file-excel text-green-600"></i>
                    <span class="text-sm font-medium text-green-900">Import Data</span>
                </a>
                @endcan
                @can('view-reports')
                <a href="{{ route('reports.export') ?? '#' }}" class="flex items-center gap-3 p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                    <i class="fas fa-download text-purple-600"></i>
                    <span class="text-sm font-medium text-purple-900">Export Laporan</span>
                </a>
                @endcan
            </div>
        </div>
        @endcan
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data from Laravel Controller
    const monthlyData = @json($monthlyData ?? []);
    const departmentData = @json($departmentData ?? []);
    const stageConversionData = @json($stageConversionData ?? []);

    // Chart configurations
    const chartConfig = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: true,
            }
        }
    };

    // Monthly Trend Chart
    const monthlyTrendCtx = document.getElementById('monthlyTrendChart');
    if (monthlyTrendCtx && monthlyData.length > 0) {
        new Chart(monthlyTrendCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(d => d.month || 'N/A'),
                datasets: [
                    {
                        label: 'Aplikasi',
                        data: monthlyData.map(d => d.aplikasi || 0),
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Tingkat Lulus (%)',
                        data: monthlyData.map(d => d.tingkatLulus || 0),
                        type: 'line',
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        yAxisID: 'y1',
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                    },
                ],
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Jumlah Aplikasi', font: { size: 12 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: { display: true, text: 'Tingkat Lulus (%)', font: { size: 12 } },
                        grid: { drawOnChartArea: false },
                    },
                    x: {
                        grid: { display: false },
                    }
                },
            },
        });
    }

    // Department Distribution Chart
    const departmentCtx = document.getElementById('departmentDistributionChart');
    if (departmentCtx && departmentData.length > 0) {
        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: departmentData.map(d => d.name || 'Unknown'),
                datasets: [{
                    data: departmentData.map(d => d.value || 0),
                    backgroundColor: departmentData.map(d => d.color || '#3b82f6'),
                    borderWidth: 0,
                    cutout: '70%',
                }],
            },
            options: {
                ...chartConfig,
                plugins: {
                    ...chartConfig.plugins,
                    legend: { 
                        display: true, 
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                        }
                    },
                    tooltip: {
                        ...chartConfig.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0';
                                return `${label}: ${value} (${percentage}%)`;
                            },
                        },
                    },
                },
            },
        });
    }

    // Conversion Funnel Chart
    const conversionCtx = document.getElementById('conversionFunnelChart');
    if (conversionCtx && stageConversionData.length > 0) {
        new Chart(conversionCtx, {
            type: 'line',
            data: {
                labels: stageConversionData.map(d => d.stage || 'Unknown'),
                datasets: [
                    {
                        label: 'Jumlah Kandidat',
                        data: stageConversionData.map(d => d.jumlah || 0),
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderColor: '#3b82f6',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y',
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                    },
                    {
                        label: 'Tingkat Konversi (%)',
                        data: stageConversionData.map(d => d.konversi || 0),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        yAxisID: 'y1',
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                    },
                ],
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Jumlah Kandidat', font: { size: 12 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: { display: true, text: 'Tingkat Konversi (%)', font: { size: 12 } },
                        grid: { drawOnChartArea: false },
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 0,
                        },
                    },
                },
            },
        });
    }

    // Event listeners for filters
    function updateFilters() {
        const year = document.getElementById('year').value;
        const type = document.getElementById('type').value;
        
        // Show loading state
        document.querySelectorAll('.chart-container').forEach(container => {
            container.classList.add('chart-loading');
        });
        
        // Navigate with filters
        const url = new URL(window.location.href);
        url.searchParams.set('year', year);
        url.searchParams.set('type', type);
        window.location.href = url.toString();
    }

    document.getElementById('year').addEventListener('change', updateFilters);
    document.getElementById('type').addEventListener('change', updateFilters);
    
    document.getElementById('refreshData').addEventListener('click', function() {
        this.classList.add('opacity-50');
        this.disabled = true;
        
        // Show loading on all charts
        document.querySelectorAll('.chart-container').forEach(container => {
            container.classList.add('chart-loading');
        });
        
        // Refresh page after short delay
        setTimeout(() => {
            window.location.reload();
        }, 500);
    });

    // Remove loading state after charts are loaded
    setTimeout(() => {
        document.querySelectorAll('.chart-container').forEach(container => {
            container.classList.remove('chart-loading');
        });
    }, 1000);
});
</script>
@endpush