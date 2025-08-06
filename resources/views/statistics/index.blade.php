<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Statistik & Analytics - Patria Maritim Perkasa</title>
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
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
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
                <a href="{{ route('statistics.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 font-medium">
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
                    <h1 class="text-xl font-semibold text-gray-900">Statistik & Analytics</h1>
                    <p class="text-sm text-gray-600">Analisis mendalam proses rekrutmen</p>
                </div>
                <div class="flex items-center gap-4">
                    <select name="year" id="year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                        <option value="2022">2022</option>
                    </select>
                    <select name="type" id="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="all">Semua Tipe</option>
                        <option value="organik">Organik</option>
                        <option value="non-organik">Non-Organik</option>
                    </select>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">{{ substr(Auth::user()->name, 0, 2) }}</span>
                        <button class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-blue-600">{{ substr(Auth::user()->name, 0, 2) }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 p-6">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Total Aplikasi</p>
                            <p class="text-3xl font-bold text-gray-900">1,245</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded-full font-medium">+12.5%</span>
                        <span class="text-xs text-gray-500">vs periode sebelumnya</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Tingkat Kelulusan</p>
                            <p class="text-3xl font-bold text-gray-900">28.4%</p>
                        </div>
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-bullseye text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded-full font-medium">+2.1%</span>
                        <span class="text-xs text-gray-500">dari total aplikasi</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Rata-rata Waktu Proses</p>
                            <p class="text-3xl font-bold text-gray-900">18 hari</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded-full font-medium">-3 hari</span>
                        <span class="text-xs text-gray-500">dari aplikasi ke keputusan</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Kandidat Aktif</p>
                            <p class="text-3xl font-bold text-gray-900">187</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users-cog text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="bg-purple-100 text-purple-600 text-xs px-2 py-1 rounded-full font-medium">+8.2%</span>
                        <span class="text-xs text-gray-500">sedang dalam proses</span>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Monthly Trend -->
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Tren Bulanan Rekrutmen</h3>
                    <p class="text-sm text-gray-600 mb-6">Jumlah aplikasi dan tingkat kelulusan per bulan</p>
                    <div class="h-80">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>

                <!-- Department Distribution -->
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Distribusi per Departemen</h3>
                    <p class="text-sm text-gray-600 mb-6">Jumlah kandidat berdasarkan departemen</p>
                    <div class="h-80">
                        <canvas id="departmentDistributionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Conversion Funnel -->
            <div class="bg-white rounded-xl p-6 border border-gray-200 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Funnel Konversi Rekrutmen</h3>
                <p class="text-sm text-gray-600 mb-6">Tingkat konversi di setiap tahapan proses seleksi</p>
                <div class="h-96">
                    <canvas id="conversionFunnelChart"></canvas>
                </div>
            </div>

            <!-- Performance Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Top Performing Departments -->
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Departments</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium">Information Technology</p>
                                <p class="text-sm text-gray-600">342 kandidat</p>
                            </div>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">27.5%</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium">Finance</p>
                                <p class="text-sm text-gray-600">287 kandidat</p>
                            </div>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">23.1%</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium">Marketing</p>
                                <p class="text-sm text-gray-600">198 kandidat</p>
                            </div>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">15.9%</span>
                        </div>
                    </div>
                </div>

                <!-- Average Process Time -->
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Waktu Proses Rata-rata</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <p class="font-medium">Seleksi Berkas</p>
                            <span class="border border-gray-300 text-gray-700 text-xs px-2 py-1 rounded-full">3 hari</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <p class="font-medium">Psikotes</p>
                            <span class="border border-gray-300 text-gray-700 text-xs px-2 py-1 rounded-full">5 hari</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <p class="font-medium">Interview HC</p>
                            <span class="border border-gray-300 text-gray-700 text-xs px-2 py-1 rounded-full">4 hari</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <p class="font-medium">Interview Teknis</p>
                            <span class="border border-gray-300 text-gray-700 text-xs px-2 py-1 rounded-full">6 hari</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <p class="font-medium">Interview Akhir</p>
                            <span class="border border-gray-300 text-gray-700 text-xs px-2 py-1 rounded-full">3 hari</span>
                        </div>
                    </div>
                </div>

                <!-- Best Candidate Sources -->
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Sumber Kandidat Terbaik</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex justify-between items-center mb-1">
                                <p class="font-medium">LinkedIn</p>
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">34.2%</span>
                            </div>
                            <p class="text-sm text-gray-600">425 kandidat</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex justify-between items-center mb-1">
                                <p class="font-medium">Job Portal</p>
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">28.7%</span>
                            </div>
                            <p class="text-sm text-gray-600">357 kandidat</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex justify-between items-center mb-1">
                                <p class="font-medium">Referral</p>
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">18.9%</span>
                            </div>
                            <p class="text-sm text-gray-600">235 kandidat</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Sample data for charts
        const monthlyData = [
            { month: 'Jan', aplikasi: 85, tingkatLulus: 25 },
            { month: 'Feb', aplikasi: 92, tingkatLulus: 28 },
            { month: 'Mar', aplikasi: 78, tingkatLulus: 22 },
            { month: 'Apr', aplikasi: 105, tingkatLulus: 32 },
            { month: 'May', aplikasi: 98, tingkatLulus: 29 },
            { month: 'Jun', aplikasi: 112, tingkatLulus: 35 },
            { month: 'Jul', aplikasi: 87, tingkatLulus: 26 },
            { month: 'Aug', aplikasi: 124, tingkatLulus: 38 },
            { month: 'Sep', aplikasi: 95, tingkatLulus: 30 },
            { month: 'Oct', aplikasi: 108, tingkatLulus: 33 },
            { month: 'Nov', aplikasi: 89, tingkatLulus: 27 },
            { month: 'Dec', aplikasi: 102, tingkatLulus: 31 }
        ];

        const departmentData = [
            { name: 'IT', value: 342, color: '#3b82f6' },
            { name: 'Finance', value: 287, color: '#10b981' },
            { name: 'Marketing', value: 198, color: '#f59e0b' },
            { name: 'Operations', value: 234, color: '#ef4444' },
            { name: 'HR', value: 184, color: '#8b5cf6' }
        ];

        const stageConversionData = [
            { stage: 'Aplikasi', jumlah: 1245, konversi: 100 },
            { stage: 'CV Review', jumlah: 987, konversi: 79.3 },
            { stage: 'Test', jumlah: 654, konversi: 66.3 },
            { stage: 'Interview HC', jumlah: 456, konversi: 69.7 },
            { stage: 'Interview User', jumlah: 298, konversi: 65.4 },
            { stage: 'Final Decision', jumlah: 187, konversi: 62.8 },
            { stage: 'Offering', jumlah: 154, konversi: 82.4 }
        ];

        // Monthly Trend Chart
        new Chart(document.getElementById('monthlyTrendChart'), {
            type: 'bar',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [
                    {
                        label: 'Aplikasi',
                        data: monthlyData.map(d => d.aplikasi),
                        backgroundColor: '#3b82f6',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Tingkat Lulus (%)',
                        data: monthlyData.map(d => d.tingkatLulus),
                        type: 'line',
                        borderColor: '#10b981',
                        borderWidth: 3,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Jumlah Aplikasi' },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: { display: true, text: 'Tingkat Lulus (%)' },
                        grid: { drawOnChartArea: false },
                    },
                },
            },
        });

        // Department Distribution Chart
        new Chart(document.getElementById('departmentDistributionChart'), {
            type: 'pie',
            data: {
                labels: departmentData.map(d => d.name),
                datasets: [{
                    data: departmentData.map(d => d.value),
                    backgroundColor: departmentData.map(d => d.color),
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = ((value / total) * 100).toFixed(0);
                                return `${label}: ${value} (${percentage}%)`;
                            },
                        },
                    },
                },
            },
        });

        // Conversion Funnel Chart
        new Chart(document.getElementById('conversionFunnelChart'), {
            type: 'line',
            data: {
                labels: stageConversionData.map(d => d.stage),
                datasets: [
                    {
                        label: 'Jumlah Kandidat',
                        data: stageConversionData.map(d => d.jumlah),
                        type: 'line',
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: '#3b82f6',
                        fill: true,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Tingkat Konversi (%)',
                        data: stageConversionData.map(d => d.konversi),
                        type: 'line',
                        borderColor: '#10b981',
                        borderWidth: 3,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Jumlah Kandidat' },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: { display: true, text: 'Tingkat Konversi (%)' },
                        grid: { drawOnChartArea: false },
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45,
                        },
                    },
                },
            },
        });
    </script>
</body>
</html>