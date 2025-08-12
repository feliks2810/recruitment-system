@extends('layouts.app')

@section('title', 'Kandidat')
@section('page-title', 'Manajemen Kandidat')
@section('page-subtitle', 'Kelola dan pantau kandidat recruitment')

@push('header-filters')
<div class="flex items-center gap-4">
    @can('import-excel')
        <a href="{{ route('candidates.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2 transition-colors">
            <i class="fas fa-plus text-sm"></i>
            <span>Tambah Kandidat</span>
        </a>
    @endcan
    @can('view-candidates')
        <a href="{{ route('candidates.export') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center gap-2 transition-colors">
            <i class="fas fa-file-excel text-sm"></i>
            <span>Export Excel</span>
        </a>
    @endcan
</div>
@endpush

@section('content')
@can('view-candidates')
    <div x-data="bulkOperations()" id="candidates-scope">
    <!-- Bulk Operations Bar -->
    <div id="bulk-operations" class="bg-blue-50 border border-blue-200 px-4 sm:px-6 py-4 mb-4 rounded-lg hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-blue-900">
                    <span x-text="selectedCount"></span> kandidat dipilih
                </span>
                <button @click="clearSelection" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Bulk Update Status -->
                @can('edit-candidates')
                <button @click="showBulkUpdateModal" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    Update Status
                </button>
                
                <!-- Bulk Move Stage -->
                <button @click="showBulkMoveModal" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 text-sm flex items-center gap-2">
                    <i class="fas fa-arrow-right"></i>
                    Pindah Stage
                </button>
                @endcan
                
                <!-- Bulk Export -->
                <button @click="showBulkExportModal" class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 text-sm flex items-center gap-2">
                    <i class="fas fa-download"></i>
                    Export
                </button>
                
                <!-- Bulk Delete -->
                @can('delete-candidates')
                <button @click="confirmBulkDelete" class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 text-sm flex items-center gap-2">
                    <i class="fas fa-trash"></i>
                    Hapus
                </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 shadow-sm">
        <form method="GET" class="flex items-center gap-3 sm:gap-4 flex-wrap">
            <div class="flex-1 min-w-64">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari nama, email, atau posisi..." 
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="min-w-[150px]">
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="" {{ !request('status') ? 'selected' : '' }}>Semua Status</option>
                    <option value="psikotes" {{ request('status') == 'psikotes' ? 'selected' : '' }}>Psikotes</option>
                    <option value="interview_hc" {{ request('status') == 'interview_hc' ? 'selected' : '' }}>Interview HC</option>
                    <option value="interview_user" {{ request('status') == 'interview_user' ? 'selected' : '' }}>Interview User</option>
                    <option value="final" {{ request('status') == 'final' ? 'selected' : '' }}>Final</option>
                    <option value="hired" {{ request('status') == 'hired' ? 'selected' : '' }}>Hired</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2 transition-colors">
                <i class="fas fa-search text-sm"></i>
                <span>Cari</span>
            </button>
            <a href="{{ route('candidates.index') }}" class="text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center gap-2 transition-colors">
                <i class="fas fa-refresh text-sm"></i>
                <span>Reset</span>
            </a>
        </form>
    </div>

    <!-- Content -->
    <div class="flex-1 p-4 sm:p-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6 mb-6">
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-600">Total Kandidat</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ $candidates->total() }}</p>
                    </div>
                    <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-600">Dalam Proses</p>
                        <p class="text-xl sm:text-2xl font-bold text-yellow-600 truncate">{{ $stats['proses'] ?? 0 }}</p>
                    </div>
                    <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-600">Lulus</p>
                        <p class="text-xl sm:text-2xl font-bold text-green-600 truncate">{{ $stats['hired'] ?? 0 }}</p>
                    </div>
                    <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-600">Ditolak</p>
                        <p class="text-xl sm:text-2xl font-bold text-red-600 truncate">{{ $stats['rejected'] ?? 0 }}</p>
                    </div>
                    <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-times-circle text-red-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-600">Duplicate</p>
                        <p class="text-xl sm:text-2xl font-bold text-orange-600 truncate">{{ $stats['duplicate'] ?? 0 }}</p>
                    </div>
                    <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-copy text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex flex-wrap gap-2 sm:space-x-8 sm:gap-0" aria-label="Tabs">
                    <a href="{{ route('candidates.index', array_merge(request()->all(), ['type' => 'organic'])) }}" 
                       class="{{ $type === 'organic' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        Kandidat Organik
                    </a>
                    <a href="{{ route('candidates.index', array_merge(request()->all(), ['type' => 'non-organic'])) }}" 
                       class="{{ $type === 'non-organic' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        Kandidat Non-Organik
                    </a>
                    <a href="{{ route('candidates.index', array_merge(request()->all(), ['type' => 'duplicate'])) }}" 
                       class="{{ $type === 'duplicate' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Kandidat Duplicate ({{ $stats['duplicate'] ?? 0 }})
                    </a>
                </nav>
            </div>
        </div>

        <!-- Candidates Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    @if($type === 'duplicate')
                        Daftar Kandidat Duplicate
                        <span class="text-sm font-normal text-orange-600">(Mendaftar 2 kali dalam 1 tahun)</span>
                    @else
                        Daftar Kandidat
                        @if(request('status'))
                            <span class="text-sm font-normal text-blue-600">- Status: {{ ucfirst(str_replace('_', ' ', request('status'))) }}</span>
                        @endif
                        @if(request('search'))
                            <span class="text-sm font-normal text-gray-600">- Pencarian: "{{ request('search') }}"</span>
                        @endif
                    @endif
                </h3>
            </div>
            
            @if($candidates->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox"
                                       data-select-all
                                       @click="toggleAllCandidates($event.target.checked)"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidat</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posisi</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahapan</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            @if($type === 'duplicate')
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant ID</th>
                            @endif
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($candidates as $candidate)
                        @if(Auth::user()->hasRole('department') && $candidate->department !== Auth::user()->department)
                            @continue
                        @endif
                        <tr class="hover:bg-gray-50 transition-colors {{ in_array($candidate->id, $latestDuplicateCandidateIds ?? []) ? 'bg-red-50 border-l-4 border-red-500' : '' }}">
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                <input type="checkbox"
                                       data-candidate-checkbox
                                       value="{{ $candidate->id }}"
                                       @change="toggleCandidate({{ $candidate->id }}, $event.target.checked)"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-9 sm:w-10 h-9 sm:h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 {{ in_array($candidate->id, $latestDuplicateCandidateIds ?? []) ? 'bg-red-200' : '' }}">
                                        <span class="text-sm font-medium {{ in_array($candidate->id, $latestDuplicateCandidateIds ?? []) ? 'text-red-600' : 'text-blue-600' }}">{{ substr($candidate->nama, 0, 2) }}</span>
                                    </div>
                                    <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900 truncate {{ in_array($candidate->id, $latestDuplicateCandidateIds ?? []) ? 'text-red-900' : '' }}">
                                            {{ $candidate->nama }}
                                            @if(in_array($candidate->id, $latestDuplicateCandidateIds ?? []))
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Duplicate
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 truncate {{ in_array($candidate->id, $latestDuplicateCandidateIds ?? []) ? 'text-red-700' : '' }}">{{ $candidate->alamat_email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $candidate->vacancy_airsys }}</div>
                                @if($candidate->internal_position)
                                <div class="text-sm text-gray-500">{{ $candidate->internal_position }}</div>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                @if($candidate->hiring_status == 'HIRED')
                                    <span class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Hired</span>
                                @elseif($candidate->hiring_status == 'TIDAK DIHIRING')
                                    <span class="inline-flex px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Tidak Lulus</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Proses</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ ucfirst(str_replace('_', ' ', $candidate->hiring_status)) }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $candidate->created_at->format('d M Y') }}
                            </td>
                            @if($type === 'duplicate')
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-red-600">{{ $candidate->applicant_id }}</div>
                                <div class="text-xs text-red-500">
                                    @php
                                        $duplicateCount = \App\Models\Candidate::where('applicant_id', $candidate->applicant_id)->count();
                                    @endphp
                                    {{ $duplicateCount }} aplikasi
                                </div>
                            </td>
                            @endif
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-1 sm:gap-2">
                                    @can('import-excel')
                                    <form method="POST" action="{{ route('candidates.switchType', $candidate) }}" class="inline" onsubmit="return confirm('Yakin ingin memindahkan tipe kandidat ini?')">
                                        @csrf
                                        <button type="submit" class="text-gray-600 hover:text-gray-900 p-1" title="Pindahkan Tipe">
                                            <i class="fas fa-exchange-alt text-sm"></i>
                                        </button>
                                    </form>
                                    @endcan
                                    @can('show-candidates')
                                    <a href="{{ route('candidates.show', $candidate) }}" class="text-blue-600 hover:text-blue-900 p-1" title="Lihat Detail">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                    @endcan
                                    @can('import-excel')
                                    <a href="{{ route('candidates.edit', $candidate) }}" class="text-indigo-600 hover:text-indigo-900 p-1" title="Edit">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                    @endcan
                                    @can('delete-candidates')
                                    <form method="POST" action="{{ route('candidates.destroy', $candidate) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus kandidat ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 p-1" title="Hapus">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200">
                {{ $candidates->appends(request()->query())->links() }}
            </div>
            @else
            <div class="text-center py-12">
                @if($type === 'duplicate')
                    <i class="fas fa-copy text-5xl sm:text-6xl text-orange-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada kandidat duplicate</h3>
                    <p class="text-gray-500 mb-6">Saat ini tidak ada kandidat yang mendaftar 2 kali dalam jangka waktu 1 tahun</p>
                @else
                    <i class="fas fa-users text-5xl sm:text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        @if(request('search') || request('status'))
                            Tidak ada hasil yang ditemukan
                        @else
                            Belum ada kandidat
                        @endif
                    </h3>
                    <p class="text-gray-500 mb-6">
                        @if(request('search') || request('status'))
                            Coba ubah kriteria pencarian atau filter untuk melihat hasil lainnya
                        @else
                            Mulai dengan menambahkan kandidat baru atau import dari Excel
                        @endif
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        @if(request('search') || request('status'))
                            <a href="{{ route('candidates.index', ['type' => $type]) }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                Reset Filter
                            </a>
                        @endif
                        @can('edit-candidates')
                        <a href="{{ route('candidates.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Tambah Kandidat
                        </a>
                        @endcan
                        @can('import-excel')
                        <a href="{{ route('import.index') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                            Import Excel
                        </a>
                        @endcan
                    </div>
                @endif
            </div>
            @endif
        </div>
    </div>
@endcan
@endsection

<!-- Bulk Operations Modals -->
@can('edit-candidates')
<!-- Bulk Update Status Modal -->
<div id="bulk-update-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" x-data="bulkUpdateModal()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Update Status Kandidat</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form @submit.prevent="submitUpdate">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stage</label>
                        <select x-model="form.stage" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Stage</option>
                            <option value="psikotes">Psikotes</option>
                            <option value="interview_hc">Interview HC</option>
                            <option value="interview_user">Interview User</option>
                            <option value="interview_bod">Interview BOD</option>
                            <option value="offering_letter">Offering Letter</option>
                            <option value="mcu">Medical Check Up</option>
                            <option value="hiring">Hiring</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select x-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Status</option>
                            <option value="LULUS">LULUS</option>
                            <option value="TIDAK LULUS">TIDAK LULUS</option>
                            <option value="PENDING">PENDING</option>
                            <option value="DISARANKAN">DISARANKAN</option>
                            <option value="TIDAK DISARANKAN">TIDAK DISARANKAN</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                        <textarea x-model="form.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                    </div>
                </div>
                
                <div class="flex items-center justify-end gap-3 mt-6">
                    <button type="button" @click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Move Stage Modal -->
<div id="bulk-move-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" x-data="bulkMoveModal()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Pindah Stage Kandidat</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form @submit.prevent="submitMove">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Stage</label>
                        <select x-model="form.targetStage" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Stage</option>
                            <option value="psikotes">Psikotes</option>
                            <option value="interview_hc">Interview HC</option>
                            <option value="interview_user">Interview User</option>
                            <option value="interview_bod">Interview BOD</option>
                            <option value="offering_letter">Offering Letter</option>
                            <option value="mcu">Medical Check Up</option>
                            <option value="hiring">Hiring</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                        <textarea x-model="form.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                    </div>
                </div>
                
                <div class="flex items-center justify-end gap-3 mt-6">
                    <button type="button" @click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Pindah Stage
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- Bulk Export Modal -->
<div id="bulk-export-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" x-data="bulkExportModal()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-lg w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Export Kandidat</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form @submit.prevent="submitExport">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Format Export</label>
                        <select x-model="form.format" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kolom yang Diexport</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.columns" value="nama" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Nama</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.columns" value="vacancy" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Posisi</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.columns" value="department" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Departemen</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.columns" value="current_stage" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Stage</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.columns" value="overall_status" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Status</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.columns" value="created_at" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Tanggal Daftar</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-end gap-3 mt-6">
                    <button type="button" @click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <!-- Alpine.js for Bulk Operations -->
    <script>
        function bulkOperations() {
            return {
                selectedCandidates: new Set(),
                selectedCount: 0,
                
                toggleCandidate(id, checked) {
                    if (checked) {
                        this.selectedCandidates.add(id);
                    } else {
                        this.selectedCandidates.delete(id);
                    }
                    this.selectedCount = this.selectedCandidates.size;
                    this.updateBulkBar();
                },
                
                toggleAllCandidates(checked) {
                    const checkboxes = document.querySelectorAll('#candidates-scope input[type="checkbox"][data-candidate-checkbox]');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = checked;
                        if (checked) {
                            this.selectedCandidates.add(parseInt(checkbox.value));
                        } else {
                            this.selectedCandidates.delete(parseInt(checkbox.value));
                        }
                    });
                    this.selectedCount = this.selectedCandidates.size;
                    this.updateBulkBar();
                },
                
                updateBulkBar() {
                    const bulkBar = document.getElementById('bulk-operations');
                    if (this.selectedCount > 0) {
                        bulkBar.classList.remove('hidden');
                    } else {
                        bulkBar.classList.add('hidden');
                    }
                },
                
                clearSelection() {
                    this.selectedCandidates.clear();
                    this.selectedCount = 0;
                    this.updateBulkBar();
                    const checkboxes = document.querySelectorAll('#candidates-scope input[type="checkbox"][data-candidate-checkbox]');
                    checkboxes.forEach(checkbox => checkbox.checked = false);
                },
                
                showBulkUpdateModal() {
                    document.getElementById('bulk-update-modal').classList.remove('hidden');
                },
                
                showBulkMoveModal() {
                    document.getElementById('bulk-move-modal').classList.remove('hidden');
                },
                
                showBulkExportModal() {
                    document.getElementById('bulk-export-modal').classList.remove('hidden');
                },
                
                confirmBulkDelete() {
                    if (confirm(`Anda yakin ingin menghapus ${this.selectedCount} kandidat yang dipilih?`)) {
                        this.bulkDelete();
                    }
                },
                
                async bulkDelete() {
                    try {
                        const response = await fetch('{{ route("candidates.bulkDelete") }}', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                candidate_ids: Array.from(this.selectedCandidates)
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            alert(result.message);
                            location.reload();
                        } else {
                            alert('Gagal menghapus kandidat');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus kandidat');
                    }
                }
            }
        }
        
        function bulkUpdateModal() {
            return {
                form: {
                    stage: '',
                    status: '',
                    notes: ''
                },
                
                closeModal() {
                    document.getElementById('bulk-update-modal').classList.add('hidden');
                },
                
                async submitUpdate() {
                    if (!this.form.stage || !this.form.status) {
                        alert('Pilih stage dan status terlebih dahulu');
                        return;
                    }
                    
                    try {
                        const response = await fetch('{{ route("candidates.bulkUpdateStatus") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                candidate_ids: Array.from(window.bulkOperations().selectedCandidates),
                                stage: this.form.stage,
                                status: this.form.status,
                                notes: this.form.notes
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            alert(result.message);
                            this.closeModal();
                            location.reload();
                        } else {
                            alert('Gagal update status kandidat');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat update status kandidat');
                    }
                }
            }
        }
        
        function bulkMoveModal() {
            return {
                form: {
                    targetStage: '',
                    notes: ''
                },
                
                closeModal() {
                    document.getElementById('bulk-move-modal').classList.add('hidden');
                },
                
                async submitMove() {
                    if (!this.form.targetStage) {
                        alert('Pilih target stage terlebih dahulu');
                        return;
                    }
                    
                    try {
                        const response = await fetch('{{ route("candidates.bulkMoveStage") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                candidate_ids: Array.from(window.bulkOperations().selectedCandidates),
                                target_stage: this.form.targetStage,
                                notes: this.form.notes
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            alert(result.message);
                            this.closeModal();
                            location.reload();
                        } else {
                            alert('Gagal pindah stage kandidat');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat pindah stage kandidat');
                    }
                }
            }
        }
        
        function bulkExportModal() {
            return {
                form: {
                    format: 'excel',
                    columns: ['nama', 'vacancy', 'department', 'current_stage', 'overall_status', 'created_at']
                },
                
                closeModal() {
                    document.getElementById('bulk-export-modal').classList.add('hidden');
                },
                
                async submitExport() {
                    try {
                        const formData = new FormData();
                        formData.append('format', this.form.format);
                        formData.append('columns', JSON.stringify(this.form.columns));
                        formData.append('_token', '{{ csrf_token() }}');
                        
                        const response = await fetch('{{ route("candidates.bulkExport") }}', {
                            method: 'POST',
                            body: formData
                        });
                        
                        if (response.ok) {
                            const blob = await response.blob();
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `candidates_export_${new Date().toISOString().slice(0,10)}.${this.form.format === 'excel' ? 'xlsx' : this.form.format}`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);
                            
                            this.closeModal();
                        } else {
                            alert('Gagal export data');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat export data');
                    }
                }
            }
        }
    </script>

    <!-- Success Message -->
    @if(session('success'))
    <div id="success-alert" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 max-w-sm">
        <div class="flex items-center gap-2">
            <i class="fas fa-check-circle flex-shrink-0"></i>
            <span class="flex-1">{{ session('success') }}</span>
            <button onclick="document.getElementById('success-alert').remove()" class="ml-2 text-white hover:text-gray-200 flex-shrink-0">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const alert = document.getElementById('success-alert');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    </script>
    @endif

    <!-- Error Message -->
    @if(session('error'))
    <div id="error-alert" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 max-w-sm">
        <div class="flex items-center gap-2">
            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
            <span class="flex-1">{{ session('error') }}</span>
            <button onclick="document.getElementById('error-alert').remove()" class="ml-2 text-white hover:text-gray-200 flex-shrink-0">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const alert = document.getElementById('error-alert');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    </script>
    @endif
@endpush