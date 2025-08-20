{{-- /resources/views/candidates/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Kandidat')
@section('page-title', 'Manajemen Kandidat')
@section('page-subtitle', 'Kelola dan pantau kandidat recruitment')

{{-- Header Action Buttons --}}
@push('header-filters')
    <div class="flex items-center gap-4">
        @can('import-excel')
            <button @click="showImportModal = true" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 flex items-center gap-2 transition-colors">
                <i class="fas fa-upload text-sm"></i>
                <span>Import Excel</span>
            </button>
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
    {{-- This scope initializes and contains all Alpine.js logic for this page --}}
    <div x-data="candidatesPage()" x-init="init()" id="candidates-scope">

        {{-- Import Modal --}}
        @can('import-excel')
            <div x-show="showImportModal" @keydown.escape.window="showImportModal = false" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" x-cloak>
                <div @click.away="showImportModal = false" class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto" x-show="showImportModal" x-transition>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900">Import Data Kandidat</h3>
                            <button @click="showImportModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                        </div>
                        
                        <form action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="candidate_type" class="block text-sm font-medium text-gray-700 mb-2">Tipe Kandidat <span class="text-red-500">*</span></label>
                                    <select name="candidate_type" id="candidate_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('candidate_type') border-red-300 @enderror" required>
                                        <option value="">Pilih Tipe Kandidat</option>
                                        <option value="organic" {{ old('candidate_type') == 'organic' ? 'selected' : '' }}>
                                            Organic (Internal Process)
                                        </option>
                                        <option value="non-organic" {{ old('candidate_type') == 'non-organic' ? 'selected' : '' }}>
                                            Non-Organic (External)
                                        </option>
                                    </select>
                                    @error('candidate_type')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="import_mode" class="block text-sm font-medium text-gray-700 mb-2">Mode Import <span class="text-red-500">*</span></label>
                                    <select name="import_mode" id="import_mode" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('import_mode') border-red-300 @enderror" required>
                                        <option value="">Pilih Mode Import</option>
                                        <option value="insert" {{ old('import_mode') == 'insert' ? 'selected' : '' }}>
                                            Insert Only (Skip jika ada duplikat)
                                        </option>
                                        <option value="update" {{ old('import_mode') == 'update' ? 'selected' : '' }}>
                                            Update Only (Update jika ada)
                                        </option>
                                        <option value="upsert" {{ old('import_mode') == 'upsert' ? 'selected' : '' }}>
                                            Insert & Update (Insert baru, update yang ada)
                                        </option>
                                    </select>
                                    @error('import_mode')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="header_row" class="block text-sm font-medium text-gray-700 mb-2">Header Row <span class="text-red-500">*</span></label>
                                    <select name="header_row" id="header_row" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('header_row') border-red-300 @enderror" required>
                                        <option value="1" {{ old('header_row') == '1' ? 'selected' : '' }}>Baris ke-1</option>
                                        <option value="2" {{ old('header_row') == '2' ? 'selected' : '' }}>Baris ke-2</option>
                                        <option value="3" {{ old('header_row') == '3' ? 'selected' : '' }}>Baris ke-3</option>
                                        <option value="4" {{ old('header_row') == '4' ? 'selected' : '' }}>Baris ke-4</option>
                                    </select>
                                    @error('header_row')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-2">File Excel <span class="text-red-500">*</span></label>
                                    <input type="file" name="excel_file" id="excel_file" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('excel_file') border-red-300 @enderror" 
                                           accept=".xlsx,.xls,.csv" required>
                                    @error('excel_file')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    <p class="text-gray-500 text-sm mt-1">
                                        Format yang didukung: Excel (.xlsx, .xls) dan CSV. Maksimal 10MB.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                                <button type="button" onclick="downloadTemplate()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center gap-2 transition-colors">
                                    <i class="fas fa-download"></i>
                                    <span>Download Template</span>
                                </button>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="showImportModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Batal</button>
                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2 transition-colors">
                                        <i class="fas fa-upload"></i>
                                        <span>Import Data</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        <div x-show="selectedCount > 0" id="bulk-operations" class="bg-blue-50 border border-blue-200 px-4 sm:px-6 py-4 mb-4 rounded-lg" x-transition>
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
                    @can('edit-candidates')
                        <button @click="showBulkUpdateModal = true" class="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition-colors hover:bg-blue-700">
                            <i class="fas fa-edit"></i>
                            <span>Update Status</span>
                        </button>
                        <button @click="showBulkMoveModal = true" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 text-sm flex items-center gap-2">
                            <i class="fas fa-arrow-right"></i>
                            <span>Pindah Stage</span>
                        </button>
                        <button @click="confirmBulkSwitchType" class="bg-teal-600 text-white px-3 py-2 rounded-lg hover:bg-teal-700 text-sm flex items-center gap-2">
                            <i class="fas fa-exchange-alt"></i>
                            <span>Ubah Tipe</span>
                        </button>
                    @endcan
                    
                    <button @click="showBulkExportModal = true" class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 text-sm flex items-center gap-2">
                        <i class="fas fa-download"></i>
                        <span>Export</span>
                    </button>
                    
                    @can('delete-candidates')
                        <button @click="confirmBulkDelete" class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 text-sm flex items-center gap-2">
                            <i class="fas fa-trash"></i>
                            <span>Hapus</span>
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 shadow-sm">
            <form method="GET" x-ref="filterForm" class="flex items-center gap-3 sm:gap-4 flex-wrap">
                <div class="flex-1 min-w-64">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari nama, email, atau posisi..." 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           @input.debounce.500ms="$refs.filterForm.submit()">
                </div>
                <div class="min-w-[150px]">
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @change="$refs.filterForm.submit()">
                        <option value="" {{ !request('status') ? 'selected' : '' }}>Semua Status</option>
                        @foreach($statuses as $statusOption)
                            <option value="{{ $statusOption }}" {{ request('status') == $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Preserve type filter on form submission --}}
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
            </form>
        </div>

        <div class="flex-1 p-4 sm:p-6">
            
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
                            <p class="text-xl sm:text-2xl font-bold text-yellow-600 truncate">{{ $stats['dalam_proses'] ?? 0 }}</p>
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
                            <p class="text-xl sm:text-2xl font-bold text-red-600 truncate">{{ $stats['ditolak'] ?? 0 }}</p>
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
                                               @click="toggleAll($event.target.checked)"
                                               :checked="allVisibleSelected"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidat</th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posisi</th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahapan</th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    @if($type === 'duplicate')
                                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant ID</th>
                                    @endif
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky right-0 bg-gray-50">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($candidates as $candidate)
                                    @if(Auth::user()->hasRole('department') && $candidate->department_id !== Auth::user()->department_id)
                                        @continue
                                    @endif
                                    <tr class="hover:bg-gray-50 transition-colors {{ in_array($candidate->id, $latestDuplicateCandidateIds ?? []) ? 'bg-red-50 border-l-4 border-red-500' : '' }}">
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox"
                                                   :value="{{ $candidate->id }}"
                                                   x-model="$store.candidates.selectedIds"
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-9 sm:w-10 h-9 sm:h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 {{ in_array($candidate->id, $latestDuplicateCandidateIds ?? []) ? 'bg-red-200' : '' }} {{ $candidate->is_inactive ? 'ring-2 ring-red-500' : '' }}">
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
                                            <div class="text-sm text-gray-900">{{ $candidate->vacancy }}</div>
                                            @if($candidate->internal_position)
                                                <div class="text-sm text-gray-500">{{ $candidate->internal_position }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $candidate->department->name ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                            @if($candidate->overall_status == 'LULUS')
                                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Lulus</span>
                                            @elseif($candidate->overall_status == 'DITOLAK')
                                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Ditolak</span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Proses</span>
                                            @endif
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $candidate->current_stage }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $candidate->created_at->format('d M Y') }}
                                        </td>
                                        @if($type === 'duplicate')
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-red-600">{{ $candidate->applicant_id }}</div>
                                                <div class="text-xs text-red-500">
                                                    {{ \App\Models\Candidate::where('applicant_id', $candidate->applicant_id)->count() }} aplikasi
                                                </div>
                                            </td>
                                        @endif
                                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium sticky right-0 bg-white hover:bg-gray-50">
                                            <div class="flex items-center gap-1 sm:gap-2">
                                                @if(in_array($candidate->id, $latestDuplicateCandidateIds ?? []))
                                                    <button onclick="promptDuplicateAction({{ $candidate->id }})" class="text-gray-600 hover:text-gray-900 p-1" title="Ubah Status Duplikat">
                                                        <i class="fas fa-exclamation-triangle {{ $candidate->is_suspected_duplicate ? 'text-red-500' : 'text-gray-400' }}"></i>
                                                    </button>
                                                @endif
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

                    <div class="px-4 sm:px-6 py-4 border-t border-gray-200">
                        {{ $candidates->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        @if($type === 'duplicate')
                            <i class="fas fa-copy text-5xl sm:text-6xl text-orange-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada kandidat duplicate</h3>
                            <p class="text-gray-500 mb-6">Saat ini tidak ada kandidat yang mendaftar 2 kali dalam jangka waktu 1 tahun.</p>
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
                                    Coba ubah kriteria pencarian atau filter untuk melihat hasil lainnya.
                                @else
                                    Mulai dengan menambahkan kandidat baru atau import dari Excel.
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
                                    <button @click="showImportModal = true" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                        Import Excel
                                    </button>
                                @endcan
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @can('edit-candidates')
            <div x-show="showBulkUpdateModal" @keydown.escape.window="showBulkUpdateModal = false" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" x-cloak>
                <div @click.away="showBulkUpdateModal = false" class="bg-white rounded-xl max-w-md w-full p-6" x-show="showBulkUpdateModal" x-transition>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Update Status Kandidat</h3>
                        <button @click="showBulkUpdateModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>
                    <form @submit.prevent="submitBulkUpdate">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Stage</label>
                                <select x-model="updateForm.stage" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                                <select x-model="updateForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Pilih Status</option>
                                    <option value="LULUS">LULUS</option>
                                    <option value="TIDAK LULUS">TIDAK LULUS</option>
                                    <option value="PENDING">PENDING</option>
                                    <option value="DISARANKAN">DISARANKAN</option>
                                    <option value="TIDAK DISARANKAN">TIDAK DISARANKAN</option>
                                    <option value="DITERIMA">DITERIMA</option>
                                    <option value="DITOLAK">DITOLAK</option>
                                    <option value="HIRED">HIRED</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                                <textarea x-model="updateForm.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 mt-6">
                            <button type="button" @click="showBulkUpdateModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="showBulkMoveModal" @keydown.escape.window="showBulkMoveModal = false" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" x-cloak>
                <div @click.away="showBulkMoveModal = false" class="bg-white rounded-xl max-w-md w-full p-6" x-show="showBulkMoveModal" x-transition>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Pindah Stage Kandidat</h3>
                        <button @click="showBulkMoveModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>
                    <form @submit.prevent="submitBulkMove">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Target Stage</label>
                                <select x-model="moveForm.targetStage" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                                <textarea x-model="moveForm.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 mt-6">
                            <button type="button" @click="showBulkMoveModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Pindah Stage</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        <div x-show="showBulkExportModal" @keydown.escape.window="showBulkExportModal = false" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" x-cloak>
            <div @click.away="showBulkExportModal = false" class="bg-white rounded-xl max-w-lg w-full p-6" x-show="showBulkExportModal" x-transition>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Export Kandidat</h3>
                    <button @click="showBulkExportModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form @submit.prevent="submitBulkExport">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Format Export</label>
                            <select x-model="exportForm.format" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV (.csv)</option>
                                <option value="pdf">PDF (.pdf)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kolom yang Diexport</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center"><input type="checkbox" x-model="exportForm.columns" value="nama" class="rounded"><span class="ml-2 text-sm">Nama</span></label>
                                <label class="flex items-center"><input type="checkbox" x-model="exportForm.columns" value="vacancy" class="rounded"><span class="ml-2 text-sm">Posisi</span></label>
                                <label class="flex items-center"><input type="checkbox" x-model="exportForm.columns" value="department" class="rounded"><span class="ml-2 text-sm">Departemen</span></label>
                                <label class="flex items-center"><input type="checkbox" x-model="exportForm.columns" value="current_stage" class="rounded"><span class="ml-2 text-sm">Stage</span></label>
                                <label class="flex items-center"><input type="checkbox" x-model="exportForm.columns" value="overall_status" class="rounded"><span class="ml-2 text-sm">Status</span></label>
                                <label class="flex items-center"><input type="checkbox" x-model="exportForm.columns" value="created_at" class="rounded"><span class="ml-2 text-sm">Tanggal Daftar</span></label>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button type="button" @click="showBulkExportModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">Export</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        // Shared state for selected candidates
        Alpine.store('candidates', {
            selectedIds: [],
        });
    });

    function candidatesPage() {
        return {
            // Modal visibility state
            showImportModal: false,
            showBulkUpdateModal: false,
            showBulkMoveModal: false,
            showBulkExportModal: false,

            // Form data for modals
            updateForm: { stage: '', status: '', notes: '' },
            moveForm: { targetStage: '', notes: '' },
            exportForm: {
                format: 'excel',
                columns: ['nama', 'vacancy', 'department', 'current_stage', 'overall_status', 'created_at']
            },
            
            // An array of all candidate IDs visible on the current page
            visibleCandidateIds: [],

            init() {
                // Populate the array of visible IDs when the component initializes
                document.querySelectorAll('input[type="checkbox"][value]').forEach(el => {
                    this.visibleCandidateIds.push(parseInt(el.value));
                });
            },

            // Computed properties for convenience
            get selectedCount() {
                return this.$store.candidates.selectedIds.length;
            },
            get allVisibleSelected() {
                return this.visibleCandidateIds.length > 0 && this.visibleCandidateIds.every(id => this.$store.candidates.selectedIds.includes(id));
            },

            // --- Selection Methods ---
            toggleAll(checked) {
                if (checked) {
                    this.$store.candidates.selectedIds = [...new Set([...this.$store.candidates.selectedIds, ...this.visibleCandidateIds])];
                } else {
                    this.$store.candidates.selectedIds = this.$store.candidates.selectedIds.filter(id => !this.visibleCandidateIds.includes(id));
                }
            },
            clearSelection() {
                this.$store.candidates.selectedIds = [];
            },

            // --- Bulk Action Handlers ---
            async handleBulkAction(url, payload, method = 'POST') {
                if (this.selectedCount === 0) {
                    alert('Pilih kandidat terlebih dahulu.');
                    return;
                }
                
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();
                    
                    if (response.ok && result.success) {
                        alert(result.message);
                        location.reload();
                    } else {
                        let errorMessage = result.message || 'Terjadi kesalahan.';
                        if (result.errors) {
                            errorMessage += '\n' + Object.values(result.errors).flat().join('\n');
                        }
                        alert('Gagal: ' + errorMessage);
                    }
                } catch (error) {
                    console.error('Fetch Error:', error);
                    alert('Terjadi kesalahan jaringan atau server tidak merespons.');
                }
            },

            // Specific submit handlers for modals
            submitBulkUpdate() {
                if (!this.updateForm.stage || !this.updateForm.status) {
                    alert('Pilih stage dan status terlebih dahulu.');
                    return;
                }
                const payload = { ...this.updateForm, candidate_ids: this.$store.candidates.selectedIds };
                this.handleBulkAction('{{ route("candidates.bulkUpdateStatus") }}', payload);
            },
            submitBulkMove() {
                if (!this.moveForm.targetStage) {
                    alert('Pilih target stage terlebih dahulu.');
                    return;
                }
                const payload = { target_stage: this.moveForm.targetStage, notes: this.moveForm.notes, candidate_ids: this.$store.candidates.selectedIds };
                this.handleBulkAction('{{ route("candidates.bulkMoveStage") }}', payload);
            },
            confirmBulkSwitchType() {
                if (this.selectedCount > 0 && confirm(`Anda yakin ingin mengubah tipe untuk ${this.selectedCount} kandidat yang dipilih?`)) {
                    this.handleBulkAction('{{ route("candidates.bulkSwitchType") }}', { candidate_ids: this.$store.candidates.selectedIds });
                }
            },
            confirmBulkDelete() {
                if (this.selectedCount > 0 && confirm(`Anda yakin ingin menghapus ${this.selectedCount} kandidat yang dipilih?`)) {
                    this.handleBulkAction('{{ route("candidates.bulkDelete") }}', { candidate_ids: this.$store.candidates.selectedIds }, 'DELETE');
                }
            },
            async submitBulkExport() {
                 if (this.selectedCount === 0) {
                    alert('Pilih kandidat terlebih dahulu.');
                    return;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('format', this.exportForm.format);
                    formData.append('columns', JSON.stringify(this.exportForm.columns));
                    formData.append('candidate_ids', JSON.stringify(this.$store.candidates.selectedIds));
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                    const response = await fetch('{{ route("candidates.bulkExport") }}', {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `candidates_export_${new Date().toISOString().slice(0,10)}.${this.exportForm.format === 'excel' ? 'xlsx' : this.exportForm.format}`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                        this.showBulkExportModal = false;
                    } else {
                        alert('Gagal export data.');
                    }
                } catch (error) {
                    console.error('Export Error:', error);
                    alert('Terjadi kesalahan saat export data.');
                }
            },
        }
    }

    // Standalone function for handling duplicate status toggling on individual candidates
    function promptDuplicateAction(candidateId) {
        if (!confirm('Apakah Anda ingin mengubah status duplikat kandidat ini?')) return;

        const markAsDuplicate = confirm('Apakah Anda ingin menandai kandidat ini sebagai duplikat?\n\n- Klik "OK" untuk menandai sebagai DUPLIKAT.\n- Klik "Cancel" untuk menandai sebagai BUKAN duplikat (akan diberi Applicant ID baru).');

        fetch(`/candidates/${candidateId}/toggle-duplicate`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ mark_as_duplicate: markAsDuplicate })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message || 'Aksi selesai.');
            if (data.success) location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengubah status duplikat.');
        });
    }

    // Function for download template (called from import modal)
    function downloadTemplate() {
        const candidateType = document.getElementById('candidate_type').value;
        
        if (!candidateType) {
            alert('Silakan pilih tipe kandidat terlebih dahulu');
            return;
        }
        
        window.location.href = `{{ route('import.template', ['type' => '__TYPE__']) }}`.replace('__TYPE__', candidateType);
    }
</script>

@if(session('success') || session('error'))
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
         class="fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 max-w-sm {{ session('success') ? 'bg-green-500' : 'bg-red-500' }} text-white">
        <div class="flex items-center gap-2">
            <i class="fas {{ session('success') ? 'fa-check-circle' : 'fa-exclamation-circle' }} flex-shrink-0"></i>
            <span class="flex-1">{{ session('success') ?? session('error') }}</span>
            <button @click="show = false" class="ml-2 text-white hover:text-gray-200 flex-shrink-0">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
@endif

@endpush