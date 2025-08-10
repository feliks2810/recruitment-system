@extends('layouts.app')

@section('title', 'Detail Kandidat - ' . $candidate->nama)
@section('page-title', 'Detail Kandidat')
@section('page-subtitle', 'Informasi dan timeline rekrutmen')

@push('header-filters')
<div class="flex items-center space-x-4">
    <a href="{{ route('candidates.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $candidate->nama }}</h1>
        <p class="text-sm text-gray-500">{{ $candidate->applicant_id }} • {{ $candidate->alamat_email }}</p>
    </div>
</div>

<div class="flex items-center space-x-2">
    @can('import-excel')
    <a href="{{ route('candidates.edit', $candidate) }}" 
       class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 transition-colors duration-200">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
        Edit
    </a>
    @endcan

    @can('delete-candidates')
    <form method="POST" action="{{ route('candidates.destroy', $candidate) }}" class="inline" 
          onsubmit="return confirm('Yakin ingin menghapus kandidat ini? Tindakan ini tidak dapat dibatalkan.')">
        @csrf
        @method('DELETE')
        <button type="submit" 
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 transition-colors duration-200">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Hapus
        </button>
    </form>
    @endcan
</div>
@endpush

@section('content')
@can('show-candidates')
<div x-data="candidateDetail()" class="space-y-6">
    
    <!-- Update Stage Modal -->
    <div x-show="showModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
            
            <!-- Center modal -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form @submit.prevent="submitForm()" id="stageUpdateForm" method="POST" action="{{ route('candidates.updateStage', $candidate) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="stage" x-model="stageData.stage">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" x-text="'Update ' + selectedStage"></h3>
                                <div class="mt-4 space-y-4">
                                    <!-- Date Field -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                                        <input type="date" 
                                               name="date" 
                                               x-model="stageData.date"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <!-- Result Field -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Hasil <span class="text-red-500">*</span></label>
                                        <select name="result"
                                                x-model="stageData.result"
                                                required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="">Pilih Hasil</option>
                                            <template x-for="opt in availableResults" :key="opt">
                                                <option :value="opt" x-text="labelMap[opt] || opt"></option>
                                            </template>
                                        </select>
                                    </div>
                                    
                                    <!-- Notes Field -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Catatan</label>
                                        <textarea name="notes" 
                                                  x-model="stageData.notes" 
                                                  rows="3" 
                                                  maxlength="1000"
                                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                                  placeholder="Tambahkan catatan (opsional)..."></textarea>
                                        <p class="mt-1 text-xs text-gray-500">Maksimal 1000 karakter</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                :disabled="isSubmitting"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm"
                                x-text="isSubmitting ? 'Menyimpan...' : 'Simpan'">
                        </button>
                        <button type="button" 
                                @click="closeModal()" 
                                :disabled="isSubmitting"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Comment Modal -->
    <div x-show="showCommentModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Catatan</h3>
                            <div class="mt-2">
                                <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded-md max-h-40 overflow-y-auto">
                                    <p x-text="selectedComment || 'Tidak ada catatan'" class="whitespace-pre-wrap"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            @click="showCommentModal = false" 
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <!-- Left Column: Candidate Information -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Personal Information Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Pribadi</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $candidate->nama }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="mailto:{{ $candidate->alamat_email }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $candidate->alamat_email }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Applicant ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $candidate->applicant_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Jenis Kelamin</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($candidate->jk === 'L')
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 10a2 2 0 114 0 2 2 0 01-4 0z"/>
                                        </svg>
                                        Laki-laki
                                    </span>
                                @elseif($candidate->jk === 'P')
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 10a2 2 0 114 0 2 2 0 01-4 0z"/>
                                        </svg>
                                        Perempuan
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $candidate->tanggal_lahir ? \Carbon\Carbon::parse($candidate->tanggal_lahir)->format('d F Y') : '-' }}
                                @if($candidate->tanggal_lahir)
                                    <span class="text-gray-400 text-xs ml-1">
                                        ({{ \Carbon\Carbon::parse($candidate->tanggal_lahir)->age }} tahun)
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Education Information Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Pendidikan</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Jenjang Pendidikan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->jenjang_pendidikan ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Perguruan Tinggi</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->perguruan_tinggi ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Jurusan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->jurusan ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">IPK</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($candidate->ipk)
                                    <span class="font-medium">{{ $candidate->ipk }}</span>
                                    @if($candidate->ipk >= 3.5)
                                        <span class="ml-1 text-green-600 text-xs">Cumlaude</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Job Information Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Posisi</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Vacancy Airsys</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $candidate->vacancy_airsys }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Internal Position</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->internal_position ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Source</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->source ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">On Process By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->on_process_by ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Department</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->department ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Status Information Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Status Information</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tipe Kandidat</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $candidate->airsys_internal === 'Yes' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $candidate->airsys_internal === 'Yes' ? 'Organik' : 'Non-Organik' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Current Stage</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $candidate->current_stage }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status Keseluruhan</dt>
                            <dd class="mt-1">
                                @php
                                    $statusConfig = [
                                        'LULUS' => ['bg-green-100 text-green-800', '✓'],
                                        'TIDAK LULUS' => ['bg-red-100 text-red-800', '✗'],
                                        'DALAM PROSES' => ['bg-yellow-100 text-yellow-800', '⏳'],
                                        'PENDING' => ['bg-blue-100 text-blue-800', '⏸️']
                                    ];
                                    $config = $statusConfig[$candidate->overall_status] ?? ['bg-gray-100 text-gray-800', '?'];
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $config[0] }}">
                                    <span class="mr-1">{{ $config[1] }}</span>
                                    {{ $candidate->overall_status }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Apply</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $candidate->created_at->format('d F Y, H:i') }}
                                <span class="text-gray-400 text-xs ml-1">
                                    ({{ $candidate->created_at->diffForHumans() }})
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Files Card -->
            @if($candidate->cv || $candidate->flk)
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Berkas</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-3">
                            @if($candidate->cv)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-8 w-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Curriculum Vitae</p>
                                            <p class="text-xs text-gray-500">PDF Document</p>
                                        </div>
                                    </div>
                                    <a href="{{ Storage::url($candidate->cv) }}" 
                                       target="_blank" 
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-full hover:bg-blue-200 transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        Lihat
                                    </a>
                                </div>
                            @endif
                            
                            @if($candidate->flk)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-8 w-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Form Lamaran Kerja</p>
                                            <p class="text-xs text-gray-500">PDF Document</p>
                                        </div>
                                    </div>
                                    <a href="{{ Storage::url($candidate->flk) }}" 
                                       target="_blank" 
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-full hover:bg-blue-200 transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        Lihat
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Timeline -->
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Timeline Rekrutmen</h3>
                    <p class="mt-1 text-sm text-gray-500">Progress tahapan seleksi kandidat</p>
                </div>
                <div class="px-6 py-6">
                    <div class="flow-root">
                        <ul class="space-y-6">
                            @php $userCanMultiEdit = Auth::user()->hasRole('Team_HC'); @endphp
                            @foreach($timeline as $index => $stage)
                                @php
                                    // Determine if this stage can be edited
                                    $isEditable = true;
                                    if ($index > 0) {
                                        $prevStage = $timeline[$index - 1];
                                        $isEditable = isset($prevStage['result']) && 
                                                    in_array($prevStage['result'], ['LULUS', 'DITERIMA', 'HIRED', 'DISARANKAN']);
                                    }
                                    
                                    // Stage status logic
                                    $hasResult = isset($stage['result']) && !empty($stage['result']);
                                    $isCompleted = $hasResult && in_array($stage['result'], ['LULUS', 'DITERIMA', 'HIRED', 'DISARANKAN']);
                                    $isFailed = $hasResult && in_array($stage['result'], ['TIDAK LULUS', 'DITOLAK', 'CANCEL', 'TIDAK DISARANKAN', 'TIDAK DIHIRING']);
                                    $isPending = $hasResult && in_array($stage['result'], ['PENDING', 'DIPERTIMBANGKAN', 'SENT']);
                                    $isInProgress = $isEditable && !$hasResult;
                                    // If user is not Team HC and the stage already has a result, lock editing
                                    if ($hasResult && !$userCanMultiEdit) {
                                        $isEditable = false;
                                    }
                                @endphp
                                
                                <li class="relative">
                                    @if(!$loop->last)
                                        @php
                                            $lineColor = $isCompleted ? 'bg-green-400' : ($hasResult ? 'bg-yellow-400' : 'bg-gray-300');
                                        @endphp
                                        <div class="absolute left-4 mt-0.5 -ml-px h-full w-0.5 {{ $lineColor }}"></div>
                                    @endif
                                    
                                    <div class="relative flex space-x-3">
                                        <!-- Stage Icon -->
                                        <div class="flex-shrink-0">
                                            @if($isCompleted)
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500 ring-4 ring-white shadow">
                                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            @elseif($isFailed)
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-500 ring-4 ring-white shadow">
                                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </div>
                                            @elseif($isPending)
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-500 ring-4 ring-white shadow">
                                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                            @elseif($isInProgress)
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-blue-500 bg-white ring-4 ring-white shadow">
                                                    <div class="h-3 w-3 rounded-full border-2 border-blue-500 bg-transparent animate-pulse"></div>
                                                </div>
                                            @else
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-300 ring-4 ring-white shadow">
                                                    <div class="h-2.5 w-2.5 rounded-full bg-gray-500"></div>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Stage Content -->
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-sm font-medium text-gray-900">{{ $stage['stage'] }}</h4>
                                                    @if(!$isEditable && $stage['stage'] !== 'Seleksi Berkas')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                            </svg>
                                                            Locked
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                <!-- Action Buttons -->
                                                <div class="flex items-center space-x-2">
                                                    @if($stage['date'])
                                                        <span class="text-xs text-gray-500">
                                                            {{ \Carbon\Carbon::parse($stage['date'])->format('d/m/Y') }}
                                                        </span>
                                                    @endif
                                                    
                                                    @if($stage['notes'])
                                                        <button @click="showComment(`{{ addslashes($stage['notes']) }}`)" 
                                                                class="text-gray-400 hover:text-gray-600 transition-colors"
                                                                title="Lihat catatan">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                            </svg>
                                                        </button>
                                                    @endif
                                                    
                                                    @canany(['edit-candidates','edit-timeline'])
                                                        @if($stage['stage'] !== 'Seleksi Berkas' && $isEditable)
                                                            <button @click="openStageModal(
                                                                '{{ $stage['stage'] }}', 
                                                                '{{ $stage['stage_key'] ?? $stage['stage'] }}', 
                                                                '{{ $stage['date'] ? \Carbon\Carbon::parse($stage['date'])->format('Y-m-d') : '' }}', 
                                                                '{{ $stage['result'] ?? '' }}', 
                                                                {{ json_encode($stage['notes'] ?? '') }}, 
                                                                '{{ $stage['field_date'] ?? '' }}', 
                                                                '{{ $stage['field_result'] ?? '' }}', 
                                                                '{{ $stage['field_notes'] ?? '' }}'
                                                            )" 
                                                            class="text-blue-600 hover:text-blue-800 transition-colors"
                                                            title="Edit tahapan">
                                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                            </button>
                                                        @elseif($stage['stage'] !== 'Seleksi Berkas')
                                                            <span class="text-gray-300 cursor-not-allowed" title="Selesaikan tahapan sebelumnya terlebih dahulu">
                                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                            </span>
                                                        @endif
                                                    @endcanany
                                                </div>
                                            </div>
                                            
                                            <!-- Stage Details -->
                                            <div class="mt-1">
                                                @if($stage['evaluator'])
                                                    <p class="text-xs text-gray-500">Evaluator: {{ $stage['evaluator'] }}</p>
                                                @endif
                                                
                                                @if(isset($stage['result']) && $stage['result'])
                                                    <div class="mt-1">
                                                        @php
                                                            $resultConfig = [
                                                                'LULUS' => 'bg-green-100 text-green-800',
                                                                'DISARANKAN' => 'bg-green-100 text-green-800',
                                                                'DITERIMA' => 'bg-green-100 text-green-800',
                                                                'HIRED' => 'bg-green-100 text-green-800',
                                                                'PENDING' => 'bg-blue-100 text-blue-800',
                                                                'SENT' => 'bg-blue-100 text-blue-800',
                                                                'DIPERTIMBANGKAN' => 'bg-yellow-100 text-yellow-800',
                                                            ];
                                                            $resultClass = $resultConfig[$stage['result']] ?? 'bg-red-100 text-red-800';
                                                        @endphp
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $resultClass }}">
                                                            {{ $stage['result'] }}
                                                        </span>
                                                    </div>
                                                @endif
                                                
                                                @if($stage['notes'] && strlen($stage['notes']) <= 100)
                                                    <p class="mt-1 text-xs text-gray-600 italic">{{ $stage['notes'] }}</p>
                                                @elseif($stage['notes'])
                                                    <p class="mt-1 text-xs text-gray-600 italic">{{ Str::limit($stage['notes'], 100) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('candidateDetail', () => ({
        selectedStage: null,
        showModal: false,
        showCommentModal: false,
        selectedComment: '',
        isSubmitting: false,
            // Options per stage
            stageOptions: {
                psikotes: ['LULUS', 'TIDAK LULUS', 'DIPERTIMBANGKAN'],
                interview_hc: ['DISARANKAN', 'TIDAK DISARANKAN', 'DIPERTIMBANGKAN', 'CANCEL'],
                interview_user: ['DISARANKAN', 'TIDAK DISARANKAN', 'DIPERTIMBANGKAN', 'CANCEL'],
                interview_bod: ['DISARANKAN', 'TIDAK DISARANKAN', 'DIPERTIMBANGKAN', 'CANCEL'],
                offering_letter: ['DITERIMA', 'DITOLAK', 'SENT'],
                mcu: ['LULUS', 'TIDAK LULUS'],
                hiring: ['HIRED', 'TIDAK DIHIRING']
            },
            labelMap: {
                'LULUS': 'Lulus',
                'TIDAK LULUS': 'Tidak Lulus',
                'DIPERTIMBANGKAN': 'Dipertimbangkan',
                'DISARANKAN': 'Disarankan',
                'TIDAK DISARANKAN': 'Tidak Disarankan',
                'CANCEL': 'Cancel',
                'DITERIMA': 'Diterima',
                'DITOLAK': 'Ditolak',
                'SENT': 'Sent',
                'HIRED': 'Hired',
                'TIDAK DIHIRING': 'Tidak Dihiring'
            },
            availableResults: [],
        stageData: {
            stage: '',
            date: '',
            result: '',
            notes: '',
            field_date: '',
            field_result: '',
            field_notes: ''
        },

        openStageModal(stage, stageKey, currentDate, currentResult, currentNotes, fieldDate, fieldResult, fieldNotes) {
            console.log('Opening modal for stage:', stage, {
                stageKey, currentDate, currentResult, currentNotes, fieldDate, fieldResult, fieldNotes
            });
            
            this.selectedStage = stage;
            this.stageData = {
                stage: stageKey,
                date: currentDate || '',
                result: currentResult || '',
                notes: currentNotes || '',
                field_date: fieldDate || '',
                field_result: fieldResult || '',
                field_notes: fieldNotes || ''
            };
            // set available results based on stage
            this.availableResults = this.stageOptions[stageKey] || [];
            this.showModal = true;
            this.isSubmitting = false;
            
            // Focus on first input when modal opens
            this.$nextTick(() => {
                const firstInput = document.querySelector('#stageUpdateForm input[type="date"]');
                if (firstInput) firstInput.focus();
            });
        },

        showComment(comment) {
            this.selectedComment = comment;
            this.showCommentModal = true;
        },

        closeModal() {
            if (this.isSubmitting) return;
            
            this.showModal = false;
            this.selectedStage = null;
            this.resetStageData();
        },

        resetStageData() {
            this.stageData = {
                stage: '',
                date: '',
                result: '',
                notes: '',
                field_date: '',
                field_result: '',
                field_notes: ''
            };
        },

        async submitForm() {
            if (this.isSubmitting) return;

            // Validate required fields
            if (!this.stageData.result) {
                alert('Hasil harus diisi!');
                return;
            }

            this.isSubmitting = true;

            try {
                // Get form element
                const form = document.getElementById('stageUpdateForm');
                if (!form) {
                    throw new Error('Form not found');
                }

                console.log('Submitting form with data:', this.stageData);
                
                // Submit the form
                form.submit();
                
            } catch (error) {
                console.error('Error submitting form:', error);
                alert('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
                this.isSubmitting = false;
            }
        },

        // Keyboard shortcuts
        handleKeydown(event) {
            if (event.key === 'Escape') {
                if (this.showModal) {
                    this.closeModal();
                } else if (this.showCommentModal) {
                    this.showCommentModal = false;
                }
            }
        },

        init() {
            // Add keyboard event listener
            document.addEventListener('keydown', this.handleKeydown.bind(this));
            
            // Debug info
            console.log('Candidate Detail initialized');
            console.log('Update route:', '{{ route("candidates.updateStage", $candidate) }}');
            console.log('Candidate ID:', '{{ $candidate->id }}');
        }
    }))
});

// Form submission debugging
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('stageUpdateForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submission started...');
            
            // Log form data
            const formData = new FormData(form);
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
        });
    }
});

// Auto-hide alerts
function initializeAlerts() {
    // Success alerts
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(() => successAlert.remove(), 5000);
    }
    
    // Error alerts
    const errorAlert = document.getElementById('error-alert');
    if (errorAlert) {
        setTimeout(() => errorAlert.remove(), 8000);
    }
    
    // Validation errors
    const validationErrors = document.getElementById('validation-errors');
    if (validationErrors) {
        setTimeout(() => validationErrors.remove(), 10000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeAlerts);
</script>

<!-- Alert Messages -->
@if ($errors->any())
<div id="validation-errors" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-md animate-slide-in-right">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        </div>
        <div class="flex-1">
            <h4 class="font-medium mb-2">Terjadi kesalahan:</h4>
            <ul class="text-sm space-y-1 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button onclick="document.getElementById('validation-errors').remove()" 
                class="flex-shrink-0 text-white hover:text-gray-200 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
@endif

@if(session('success'))
<div id="success-alert" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-slide-in-right">
    <div class="flex items-center gap-3">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="font-medium">Berhasil!</p>
            <p class="text-sm">{{ session('success') }}</p>
        </div>
        <button onclick="document.getElementById('success-alert').remove()" 
                class="flex-shrink-0 text-white hover:text-gray-200 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
@endif

@if(session('error'))
<div id="error-alert" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-slide-in-right">
    <div class="flex items-center gap-3">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="font-medium">Terjadi kesalahan!</p>
            <p class="text-sm">{{ session('error') }}</p>
        </div>
        <button onclick="document.getElementById('error-alert').remove()" 
                class="flex-shrink-0 text-white hover:text-gray-200 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
@endif

<style>
@keyframes slide-in-right {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-slide-in-right {
    animation: slide-in-right 0.3s ease-out;
}

/* Custom scrollbar for comment modal */
.max-h-40::-webkit-scrollbar {
    width: 4px;
}

.max-h-40::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.max-h-40::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.max-h-40::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Loading state for buttons */
.disabled\:opacity-50:disabled {
    opacity: 0.5;
}

.disabled\:cursor-not-allowed:disabled {
    cursor: not-allowed;
}

/* Focus styles */
.focus\:ring-2:focus {
    --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
    --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
    box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

.focus\:ring-blue-500:focus {
    --tw-ring-color: rgb(59 130 246);
}

.focus\:ring-offset-2:focus {
    --tw-ring-offset-width: 2px;
}

/* Transition improvements */
.transition-colors {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
@endpush

@endsection