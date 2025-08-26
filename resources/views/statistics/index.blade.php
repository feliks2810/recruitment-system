@extends('layouts.app')

@section('title', 'Statistik & Analytics')
@section('page-title', 'Statistik & Analytics')
@section('page-subtitle', 'Analisis mendalam tentang proses dan efektivitas rekrutmen')

@push('header-filters')
<form method="GET" id="filterForm" class="flex flex-wrap items-end gap-4">
    <div>
        <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
        <select name="year" id="year" class="border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            @for ($y = date('Y'); $y >= 2022; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <div>
        <label for="source" class="block text-sm font-medium text-gray-700 mb-1">Sumber</label>
        <select name="source" id="source" class="border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            <option value="">Semua Sumber</option>
            @foreach($sources as $src)
                <option value="{{ $src }}" {{ $source == $src ? 'selected' : '' }}>{{ $src }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center gap-2">
        <i class="fas fa-filter"></i>
        Terapkan
    </button>
</form>
@endpush

@section('content')
<div class="space-y-6">
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Total Aplikasi</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($kpiData['total_applications']) }}</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Total Diterima</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ number_format($kpiData['total_hired']) }}</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Rata-rata Waktu Rekrut (Hari)</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($kpiData['avg_time_to_hire']) }}</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Tingkat Konversi Global</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ $kpiData['conversion_rate'] }}%</p>
        </div>
    </div>

    <!-- Charts Row 1: Funnel and Source -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="lg:col-span-3 bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Funnel Rekrutmen</h3>
            <div style="height: 350px;"><canvas id="funnelChart"></canvas></div>
        </div>
        <div class="lg:col-span-2 bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Efektivitas Sumber</h3>
            <div style="height: 350px;"><canvas id="sourceChart"></canvas></div>
        </div>
    </div>

    <!-- Charts Row 2: Monthly and Gender -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="lg:col-span-3 bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tren Aplikasi Bulanan</h3>
            <div style="height: 300px;"><canvas id="monthlyChart"></canvas></div>
        </div>
        <div class="lg:col-span-2 bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Gender</h3>
            <div style="height: 300px;"><canvas id="genderChart"></canvas></div>
        </div>
    </div>

    <!-- Stage Analysis -->
    <div class="bg-white rounded-xl p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Analisis Per Tahapan</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($stageAnalysis as $stage)
                <div class="bg-gray-50 rounded-lg p-5 border border-gray-200">
                    <p class="font-bold text-gray-800">{{ $stage['name'] }}</p>
                    <p class="text-sm text-gray-500 mb-3">{{ $stage['total'] }} kandidat dievaluasi</p>
                    
                    <div class="space-y-2">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-green-600">Lulus</span>
                                <span class="text-gray-600">{{ $stage['passed'] }} ({{ $stage['pass_rate'] }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-green-500 h-2.5 rounded-full" style="width: {{ $stage['pass_rate'] }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-red-600">Gagal</span>
                                <span class="text-gray-600">{{ $stage['failed'] }} ({{ $stage['fail_rate'] }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-red-500 h-2.5 rounded-full" style="width: {{ $stage['fail_rate'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const funnelData = @json($funnelData);
    const sourceData = @json($sourceData);
    const monthlyData = @json($monthlyData);
    const genderData = @json($genderData);

    // 1. Funnel Chart
    new Chart(document.getElementById('funnelChart'), {
        type: 'bar',
        data: {
            labels: funnelData.map(d => d.stage),
            datasets: [{
                label: 'Jumlah Kandidat',
                data: funnelData.map(d => d.count),
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y;
                            }
                            const conversion = funnelData[context.dataIndex].conversion;
                            return [label, `Konversi: ${conversion}%`];
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 2. Source Effectiveness Chart
    new Chart(document.getElementById('sourceChart'), {
        type: 'bar',
        data: {
            labels: sourceData.map(d => d.source),
            datasets: [
                {
                    label: 'Total Aplikasi',
                    data: sourceData.map(d => d.total_applications),
                    backgroundColor: 'rgba(200, 200, 200, 0.6)',
                },
                {
                    label: 'Diterima',
                    data: sourceData.map(d => d.hired_count),
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                 tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.x !== null) {
                                label += context.parsed.x;
                            }
                            if(context.dataset.label === 'Diterima'){
                                const hireRate = sourceData[context.dataIndex].hire_rate;
                                return [label, `Tingkat Sukses: ${hireRate}%`];
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: { beginAtZero: true },
                y: { grid: { display: false } }
            }
        }
    });

    // 3. Monthly Applications Chart
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [{
                label: 'Aplikasi Masuk',
                data: monthlyData.map(d => d.count),
                borderColor: 'rgba(239, 68, 68, 1)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // 4. Gender Distribution Chart
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(genderData),
            datasets: [{
                data: Object.values(genderData),
                backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(236, 72, 153, 0.8)'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
@endpush
