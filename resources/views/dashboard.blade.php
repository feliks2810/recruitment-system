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

@push('styles')
<style>
.calendar-cell {
    @apply py-2 px-1 border border-gray-200 bg-white transition-all duration-200 text-gray-700 relative;
    height: 50px;
    vertical-align: middle;
    position: relative;
    cursor: default;
}

/* Current month dates - clickable */
.calendar-cell.current-month {
    @apply cursor-pointer hover:bg-gray-100;
}

.calendar-cell.today {
    @apply bg-blue-600 text-white font-bold hover:bg-blue-700;
    border-radius: 8px;
    border: 2px solid #1d4ed8;
}

.calendar-cell.past-date-current-month {
    @apply text-gray-400 bg-gray-50;
}

.calendar-cell.future-date-current-month {
    @apply text-gray-700;
}

.calendar-cell.has-event {
    @apply bg-green-100 text-green-800 font-semibold border-green-300 hover:bg-green-200;
}

.calendar-cell.has-event::before {
    content: '';
    position: absolute;
    top: 4px;
    right: 4px;
    width: 8px;
    height: 8px;
    background-color: #10b981;
    border-radius: 50%;
    border: 1px solid white;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.calendar-cell.has-event.today::before {
    background-color: #fbbf24;
    border-color: rgba(255, 255, 255, 0.8);
}

.calendar-cell.selected {
    @apply bg-blue-500 text-white font-semibold ring-2 ring-blue-300;
}

/* Other month dates - faded and not clickable */
.calendar-cell.other-month {
    @apply text-gray-300 bg-gray-50;
    cursor: default;
}

.calendar-cell.other-month:hover {
    @apply bg-gray-50;
}

.calendar-cell .date-number {
    font-size: 14px;
    font-weight: 500;
    display: block;
    margin-top: 2px;
}

.calendar-cell.today .date-number {
    font-weight: 700;
    font-size: 15px;
}

.calendar-cell .event-count {
    position: absolute;
    bottom: 4px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 9px;
    color: #059669;
    font-weight: 600;
}

.calendar-cell.today .event-count {
    color: rgba(255, 255, 255, 0.9);
}

.meeting-item {
    transition: all 0.2s ease;
}

.meeting-item.hidden {
    display: none;
}

/* Legend styles */
.calendar-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 16px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #6b7280;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}

.legend-dot.today-dot {
    background: #3b82f6;
    border-radius: 6px;
    border: 2px solid #1d4ed8;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    margin: auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .calendar-cell {
        height: 45px;
    }
    
    .calendar-cell .date-number {
        font-size: 12px;
    }
    
    .calendar-cell .event-count {
        font-size: 8px;
        bottom: 2px;
    }
}
</style>
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

@if(Auth::user()->hasRole('Team_HC'))
<!-- Jadwal Tes Akan Datang (Calendar) -->
<div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm mb-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Kalender Recruitment</h3>
        <div class="flex items-center gap-2">
            <button id="prevMonth" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fas fa-chevron-left text-gray-600"></i>
            </button>
            <span id="currentMonth" class="text-sm font-medium text-gray-700 px-3 min-w-[100px] text-center">January 2025</span>
            <button id="nextMonth" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fas fa-chevron-right text-gray-600"></i>
            </button>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Calendar - Now takes more space -->
        <div class="lg:col-span-2 bg-gray-50 rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <button id="prevMonthCal" class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-white transition-all">
                    <i class="fas fa-chevron-left text-lg"></i>
                </button>
                <h4 id="calendarMonth" class="text-xl font-bold text-gray-900">January 2025</h4>
                <button id="nextMonthCal" class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-white transition-all">
                    <i class="fas fa-chevron-right text-lg"></i>
                </button>
            </div>
            
            <!-- Calendar Table -->
            <table class="w-full text-sm text-center border-collapse rounded-lg overflow-hidden shadow-sm bg-white">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-3 px-2 font-semibold text-gray-700 border border-gray-200">Sen</th>
                        <th class="py-3 px-2 font-semibold text-gray-700 border border-gray-200">Sel</th>
                        <th class="py-3 px-2 font-semibold text-gray-700 border border-gray-200">Rab</th>
                        <th class="py-3 px-2 font-semibold text-gray-700 border border-gray-200">Kam</th>
                        <th class="py-3 px-2 font-semibold text-gray-700 border border-gray-200">Jum</th>
                        <th class="py-3 px-2 font-semibold text-gray-700 border border-gray-200">Sab</th>
                        <th class="py-3 px-2 font-semibold text-gray-700 border border-gray-200">Min</th>
                    </tr>
                </thead>
                <tbody id="calendarTableBody">
                    <!-- Days will be generated by JavaScript -->
                </tbody>
            </table>
            
            <!-- Calendar Legend -->
            <div class="calendar-legend">
                <div class="legend-item">
                    <div class="legend-dot today-dot"></div>
                    <span>Hari Ini</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot bg-green-100 border border-green-300"></div>
                    <span>Ada Kandidat</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot bg-gray-100 border border-gray-300"></div>
                    <span>Tidak Ada Jadwal</span>
                </div>
            </div>
            
            <div class="mt-6">
                <button id="addEventBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-3 px-4 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Tambah Jadwal Baru
                </button>
            </div>
        </div>

        <!-- Meeting List - Now smaller -->
        <div class="lg:col-span-1">
            <div class="mb-4 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-3 h-3 bg-blue-600 rounded-full animate-pulse"></div>
                    <p class="text-sm font-semibold text-gray-800">Jadwal Terpilih</p>
                </div>
                <p class="text-xs text-gray-600" id="selectedDateInfo">Klik tanggal untuk melihat jadwal</p>
            </div>
            
            <div id="meetingsList" class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($upcoming_tests ?? [] as $candidate)
                <div class="meeting-item flex items-start gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-all cursor-pointer" 
                     data-date="{{ \Carbon\Carbon::parse($candidate->next_test_date)->format('Y-m-d') }}"
                     onclick="window.location='{{ route('candidates.show', $candidate->id) }}'">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-medium text-white">{{ substr($candidate->nama, 0, 2) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate text-sm">{{ $candidate->nama }}</p>
                        <div class="flex items-center gap-1 text-xs text-gray-600 mt-1">
                            <i class="fas fa-calendar text-xs"></i>
                            <span>{{ \Carbon\Carbon::parse($candidate->next_test_date)->isoFormat('DD MMM') }}</span>
                        </div>
                        <div class="flex items-center gap-1 text-xs text-gray-500">
                            <i class="fas fa-map-marker-alt text-xs"></i>
                            <span>{{ $candidate->next_test_stage }}</span>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-gray-500" id="noMeetings">
                    <i class="fas fa-calendar-check text-2xl mb-2 text-gray-300"></i>
                    <p class="text-xs">Tidak ada jadwal</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah Event -->
<div id="addEventModal" class="modal">
    <div class="modal-content">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Tambah Jadwal Baru</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <form id="addEventForm" class="p-6">
            <div class="space-y-4">
                <div>
                    <label for="eventTitle" class="block text-sm font-medium text-gray-700 mb-2">Judul Event</label>
                    <input type="text" id="eventTitle" name="title" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Masukkan judul event">
                </div>
                <div>
                    <label for="eventDate" class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                    <input type="date" id="eventDate" name="date" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="eventTime" class="block text-sm font-medium text-gray-700 mb-2">Waktu</label>
                    <input type="time" id="eventTime" name="time" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="eventDescription" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                    <textarea id="eventDescription" name="description" rows="3" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                              placeholder="Deskripsi event (opsional)"></textarea>
                </div>
                <div>
                    <label for="eventLocation" class="block text-sm font-medium text-gray-700 mb-2">Lokasi</label>
                    <input type="text" id="eventLocation" name="location" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Lokasi event (opsional)">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="cancelBtn" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Simpan Event
                </button>
            </div>
        </form>
    </div>
</div>
@endif

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
    document.addEventListener('DOMContentLoaded', function() {
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
                }
            },
            cutout: '60%',
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
        const urlParams = new URLSearchParams(window.location.search);
        const yearFromUrl = urlParams.get('year');
        if (yearFromUrl) {
            document.getElementById('yearFilter').value = yearFromUrl;
        }

        // Calendar functionality
        const months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        let currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();
        let selectedDate = null;

        // Get upcoming tests data for calendar highlighting
        const upcomingTests = @json($upcoming_tests ?? []);
        const testsByDate = {};

        // Group tests by date
        upcomingTests.forEach(test => {
            const date = new Date(test.next_test_date);
            const dateKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            if (!testsByDate[dateKey]) {
                testsByDate[dateKey] = [];
            }
            testsByDate[dateKey].push(test);
        });

        function updateCalendar() {
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const firstDayOfWeek = firstDay.getDay();
            const daysInMonth = lastDay.getDate();

            // Adjust for Monday start (0 = Sunday, 1 = Monday, etc.)
            const startDay = firstDayOfWeek === 0 ? 6 : firstDayOfWeek - 1;

            // Update month display
            const monthYear = `${months[currentMonth]} ${currentYear}`;
            document.getElementById('currentMonth').textContent = monthYear;
            document.getElementById('calendarMonth').textContent = monthYear;

            const calendarTableBody = document.getElementById('calendarTableBody');
            calendarTableBody.innerHTML = '';

            // Calculate total days needed (6 rows × 7 days = 42 days)
            let dayCount = 0;

            // Previous month's trailing days
            const prevMonth = new Date(currentYear, currentMonth - 1, 0);
            const prevMonthDays = prevMonth.getDate();

            // Today's date for comparison
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Generate 6 rows of calendar
            for (let week = 0; week < 6; week++) {
                const row = document.createElement('tr');

                for (let day = 0; day < 7; day++) {
                    const cell = document.createElement('td');
                    cell.className = 'calendar-cell';

                    let cellDay, cellMonth, cellYear, dateKey;

                    if (dayCount < startDay) {
                        // Previous month days
                        cellDay = prevMonthDays - (startDay - dayCount - 1);
                        cellMonth = currentMonth === 0 ? 11 : currentMonth - 1;
                        cellYear = currentMonth === 0 ? currentYear - 1 : currentYear;
                        cell.classList.add('other-month');
                    } else if (dayCount < startDay + daysInMonth) {
                        // Current month days
                        cellDay = dayCount - startDay + 1;
                        cellMonth = currentMonth;
                        cellYear = currentYear;
                        cell.classList.add('current-month'); // Add this class for current month dates
                    } else {
                        // Next month days
                        cellDay = dayCount - startDay - daysInMonth + 1;
                        cellMonth = currentMonth === 11 ? 0 : currentMonth + 1;
                        cellYear = currentMonth === 11 ? currentYear + 1 : currentYear;
                        cell.classList.add('other-month');
                    }

                    dateKey = `${cellYear}-${String(cellMonth + 1).padStart(2, '0')}-${String(cellDay).padStart(2, '0')}`;
                    
                    // Create date number element
                    const dateNumber = document.createElement('span');
                    dateNumber.className = 'date-number';
                    dateNumber.textContent = cellDay;
                    cell.appendChild(dateNumber);

                    // Only add interactions for current month days
                    if (dayCount >= startDay && dayCount < startDay + daysInMonth) {
                        const cellDate = new Date(cellYear, cellMonth, cellDay);
                        cellDate.setHours(0, 0, 0, 0);

                        // Check if it's today
                        if (cellDate.getTime() === today.getTime()) {
                            cell.classList.add('today');
                        } else if (cellDate.getTime() < today.getTime()) {
                            cell.classList.add('past-date-current-month');
                        } else {
                            cell.classList.add('future-date-current-month');
                        }

                        // Check if there are events on this date
                        if (testsByDate[dateKey]) {
                            cell.classList.add('has-event');
                            
                            // Add event count
                            const eventCount = document.createElement('div');
                            eventCount.className = 'event-count';
                            const count = testsByDate[dateKey].length;
                            eventCount.textContent = `${count} kandidat`;
                            cell.appendChild(eventCount);
                        }

                        // Add click event only for current month dates
                        cell.addEventListener('click', function(e) {
                            e.preventDefault();
                            selectDate(dateKey, cellDay, cellMonth, cellYear, this);
                        });
                    }

                    row.appendChild(cell);
                    dayCount++;
                }

                calendarTableBody.appendChild(row);
            }
        }

        function selectDate(dateKey, day, month, year, cellElement) {
            // Remove previous selection
            document.querySelectorAll('.calendar-cell.selected').forEach(el => {
                el.classList.remove('selected');
            });

            // Add selection to clicked date
            cellElement.classList.add('selected');
            selectedDate = dateKey;

            // Update selected date info
            const monthName = months[month];
            const selectedDateObj = new Date(year, month, day);
            const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const dayName = dayNames[selectedDateObj.getDay()];

            const candidateCount = testsByDate[dateKey] ? testsByDate[dateKey].length : 0;
            document.getElementById('selectedDateInfo').innerHTML =
                `<strong>${dayName}, ${day} ${monthName} ${year}</strong><br>
                <span class="text-xs">${candidateCount} kandidat terjadwal</span>`;

            // Filter meetings by selected date
            filterMeetingsByDate(dateKey);
        }

        function filterMeetingsByDate(dateKey) {
            const meetingItems = document.querySelectorAll('.meeting-item');
            const noMeetings = document.getElementById('noMeetings');
            let hasVisibleMeetings = false;

            meetingItems.forEach(item => {
                const itemDate = item.getAttribute('data-date');
                if (itemDate === dateKey) {
                    item.classList.remove('hidden');
                    hasVisibleMeetings = true;
                } else {
                    item.classList.add('hidden');
                }
            });

            // Show/hide no meetings message
            if (hasVisibleMeetings) {
                noMeetings.classList.add('hidden');
            } else {
                noMeetings.classList.remove('hidden');
            }
        }

        // Filter meetings for today on initial load
        function showTodaysMeetings() {
            const today = new Date();
            const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
            
            // Auto-select today's date
            setTimeout(() => {
                const todayCell = document.querySelector('.calendar-cell.today');
                if (todayCell) {
                    selectDate(todayKey, today.getDate(), today.getMonth(), today.getFullYear(), todayCell);
                }
            }, 100);
        }

        // Navigation buttons (both sets)
        document.getElementById('prevMonth').addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateCalendar();
            showAllMeetings();
        });

        document.getElementById('nextMonth').addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            updateCalendar();
            showAllMeetings();
        });

        // Calendar navigation buttons
        document.getElementById('prevMonthCal').addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateCalendar();
            showAllMeetings();
        });

        document.getElementById('nextMonthCal').addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            updateCalendar();
            showAllMeetings();
        });

        function showAllMeetings() {
            const meetingItems = document.querySelectorAll('.meeting-item');
            const noMeetings = document.getElementById('noMeetings');

            meetingItems.forEach(item => {
                item.classList.remove('hidden');
            });

            if (meetingItems.length > 0) {
                noMeetings.classList.add('hidden');
            } else {
                noMeetings.classList.remove('hidden');
            }

            document.getElementById('selectedDateInfo').innerHTML = 'Klik tanggal untuk melihat jadwal';

            // Remove selection
            document.querySelectorAll('.calendar-cell.selected').forEach(el => {
                el.classList.remove('selected');
            });
        }

        // Modal functionality
        const modal = document.getElementById('addEventModal');
        const addEventBtn = document.getElementById('addEventBtn');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const addEventForm = document.getElementById('addEventForm');

        // Open modal
        addEventBtn.addEventListener('click', function() {
            modal.classList.add('show');

            // Set default date to today or selected date
            const eventDateInput = document.getElementById('eventDate');
            if (selectedDate) {
                eventDateInput.value = selectedDate;
            } else {
                const today = new Date();
                eventDateInput.value = today.toISOString().split('T')[0];
            }
        });

        // Close modal
        function closeModalFunc() {
            modal.classList.remove('show');
            addEventForm.reset();
        }

        closeModal.addEventListener('click', closeModalFunc);
        cancelBtn.addEventListener('click', closeModalFunc);

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModalFunc();
            }
        });

        // Handle form submission
        addEventForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(addEventForm);
            const eventData = {
                title: formData.get('title'),
                date: formData.get('date'),
                time: formData.get('time'),
                description: formData.get('description'),
                location: formData.get('location')
            };

            // Show loading state
            const submitBtn = addEventForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('{{ route("events.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(eventData)
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    showNotification('success', result.message);

                    // Reset form and close modal
                    closeModalFunc();

                    // Refresh calendar display
                    updateCalendar();

                    // If event is today, update today's meetings
                    const today = new Date();
                    const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                    if (eventData.date === todayKey) {
                        showTodaysMeetings();
                    }

                } else {
                    throw new Error(result.message || 'Terjadi kesalahan');
                }

            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'Terjadi kesalahan saat menyimpan event: ' + error.message);
            } finally {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Notification function
        function showNotification(type, message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all transform translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            // Slide in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }, 5000);
        }

        // Update today's date in header periodically
        function updateTodayDate() {
            // This function is no longer needed since we removed the today date header
            // Just keeping it for backward compatibility
        }

        // Initialize calendar and show today's meetings
        updateCalendar();
        showTodaysMeetings();
    });
</script>   