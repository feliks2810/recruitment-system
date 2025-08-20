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
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" @click="closeModal()"></div>
            
            <!-- Center modal -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form @submit.prevent="submitForm()" id="stageUpdateForm">
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

                                    <!-- Next Test Fields -->
                                    <div x-show="isPassingResult(stageData.result) && stageData.stage !== 'hiring'" x-transition class="space-y-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                        <div>
                                            <label class="block text-sm font-medium text-blue-700">Tahap Tes Berikutnya</label>
                                            <input type="text" name="next_test_stage" x-model="stageData.next_test_stage" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm" readonly>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-blue-700">Tanggal Tes Berikutnya <span class="text-red-500">*</span></label>
                                            <input type="date" name="next_test_date" x-model="stageData.next_test_date" class="mt-1 block w-full border-blue-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
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
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Catatan
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
                            <dt class="text-sm font-medium text-gray-500">Vacancy</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $candidate->vacancy }}</dd>
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
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->department->name ?? '-' }}</dd>
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
                        <ul class="space-y-8">
                            @foreach($timeline as $index => $stage)
                                <li class="relative">
                                    <!-- Vertical line -->
                                    @if(!$loop->last)
                                        @php
                                            $lineColor = 'bg-gray-300';
                                            if ($stage['status'] === 'completed') {
                                                $lineColor = 'bg-green-500';
                                            } elseif ($stage['status'] === 'failed') {
                                                $lineColor = 'bg-red-500';
                                            }
                                        @endphp
                                        <div class="absolute left-4 top-4 -ml-px mt-0.5 h-full w-0.5 {{ $lineColor }}"></div>
                                    @endif

                                    <div class="relative flex items-start space-x-4">
                                        <!-- Stage Icon -->
                                        <div class="flex-shrink-0">
                                            @if($stage['status'] === 'completed')
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500 ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            @elseif($stage['status'] === 'failed')
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-500 ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            @elseif($stage['status'] === 'in_progress')
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-blue-500 bg-white ring-8 ring-white">
                                                    <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                                                </div>
                                            @elseif($stage['status'] === 'pending')
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-500 ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            @else {{-- locked --}}
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-400 ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Stage Content -->
                                        <div class="min-w-0 flex-1 pt-1.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-sm font-medium {{ $stage['status'] === 'locked' ? 'text-gray-500' : 'text-gray-900' }}">
                                                        {{ $stage['display_name'] }}
                                                    </h4>
                                                    @if($stage['status'] === 'locked')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500" title="Tahap ini terkunci sampai tahap sebelumnya lulus.">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                                            </svg>
                                                            Terkunci
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                <!-- Action Buttons -->
                                                <div class="flex items-center space-x-2">
                                                    @if($stage['date'])
                                                        <span class="text-xs text-gray-500">
                                                            {{ \Carbon\Carbon::parse($stage['date'])->format('d M Y') }}
                                                        </span>
                                                    @endif
                                                    
                                                    @if($stage['notes'])
                                                        <button @click="showComment(`{{ addslashes($stage['notes']) }}`)" 
                                                                class="text-gray-400 hover:text-gray-600 transition-colors"
                                                                title="Lihat catatan">
                                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.02-3.11A8.841 8.841 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM4.5 10a6.5 6.5 0 1113 0 6.5 6.5 0 01-13 0z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                    
                                                    @canany(['edit-candidates','edit-timeline'])
                                                        @if(!$stage['is_locked'])
                                                            <button @click="openStageModal(
                                                                '{{ $stage['display_name'] }}', 
                                                                '{{ $stage['stage_key'] }}', 
                                                                '{{ $stage['date'] ? \Carbon\Carbon::parse($stage['date'])->format('Y-m-d') : '' }}', 
                                                                '{{ $stage['result'] ?? '' }}', 
                                                                {{ json_encode($stage['notes'] ?? '') }}
                                                            )" 
                                                            class="text-blue-600 hover:text-blue-800 transition-colors {{ $stage['status'] === 'locked' ? 'hidden' : '' }}"
                                                            title="Update status {{ $stage['display_name'] }}">
                                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                                                </svg>
                                                            </button>
                                                        @endif
                                                    @endcanany
                                                </div>
                                            </div>
                                            
                                            <!-- Stage Details -->
                                            <div class="mt-2 text-sm">
                                                @if($stage['result'])
                                                    <div class="flex items-center space-x-2">
                                                        @php
                                                            $resultConfig = [
                                                                'LULUS' => ['bg-green-100 text-green-800', '✓'],
                                                                'DISARANKAN' => ['bg-green-100 text-green-800', '✓'],
                                                                'DITERIMA' => ['bg-green-100 text-green-800', '✓'],
                                                                'HIRED' => ['bg-green-100 text-green-800', '✓'],
                                                                'PENDING' => ['bg-blue-100 text-blue-800', '…'],
                                                                'SENT' => ['bg-blue-100 text-blue-800', '…'],
                                                                'DIPERTIMBANGKAN' => ['bg-yellow-100 text-yellow-800', '…'],
                                                                'default' => ['bg-red-100 text-red-800', '✗']
                                                            ];
                                                            $config = $resultConfig[$stage['result']] ?? $resultConfig['default'];
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $config[0] }}">
                                                            <span class="mr-1">{{ $config[1] }}</span>
                                                            {{ $stage['result'] }}
                                                        </span>
                                                        @if($stage['evaluator'])
                                                            <p class="text-gray-500">oleh {{ $stage['evaluator'] }}</p>
                                                        @endif
                                                    </div>
                                                @else
                                                    <p class="text-gray-500">Menunggu hasil...</p>
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

    <!-- Modal pop-up input tanggal tes berikutnya -->
    <div x-show="showNextTestDateModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         role="dialog"
         aria-modal="true"
         aria-labelledby="next-test-date-modal-title">
        
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" @click="closeNextTestDateModal()"></div>
            
            <!-- Center modal -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form id="nextTestDateForm" method="POST" action="{{ route('candidates.setNextTestDate', $candidate->id) }}">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="next-test-date-modal-title">Isi Tanggal Tes Berikutnya</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Kandidat telah lulus tahap ini. Silakan tentukan tanggal tes untuk tahap berikutnya.</p>
                                </div>
                                <div class="mt-4">
                                    <label for="next_test_date" class="block text-sm font-medium text-gray-700">Tanggal Tes Berikutnya <span class="text-red-500">*</span></label>
                                    <input type="date" 
                                           name="next_test_date" 
                                           id="next_test_date"
                                           x-model="nextTestDate"
                                           required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Simpan
                        </button>
                        <button type="button" 
                                @click="closeNextTestDateModal()" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan



@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('candidateDetail', () => ({
        showModal: false,
        showCommentModal: false,
        showCVReviewModal: false,
        selectedStage: null,
        selectedComment: '',
        isSubmitting: false,
        
        cvReviewData: {
            status: '',
            notes: '',
            date: new Date().toISOString().split('T')[0]
        },

        stageData: {
            stage: '',
            result: '',
            notes: '',
            next_test_stage: '',
            next_test_date: ''
        },

        // Static data for stage logic
        passingResults: ['LULUS', 'DISARANKAN', 'DITERIMA', 'HIRED'],
        stageOrder: ['CV Review', 'Psikotes', 'HC Interview', 'User Interview', 'BoD Interview', 'Offering Letter', 'MCU', 'Hiring'],
        stageDisplayNames: {
            'CV Review': 'CV Review',
            'Psikotes': 'Psikotes',
            'HC Interview': 'HC Interview',
            'User Interview': 'User Interview',
            'BoD Interview': 'Interview BOD/GM',
            'Offering Letter': 'Offering Letter',
            'MCU': 'Medical Check Up',
            'Hiring': 'Hiring',
            'hiring': 'Hiring',
            'Selesai': 'Selesai'
        },
        stageOptions: {
            cv_review: ['LULUS', 'TIDAK LULUS', 'DIPERTIMBANGKAN'],
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

        // Method to check if a result is a passing one
        isPassingResult(result) {
            return this.passingResults.includes(result);
        },

        // Open the modal to update a stage
        openStageModal(stage, stageKey, date, result, notes, fieldDate, fieldResult, fieldNotes) {
            this.selectedStage = stage;
            this.availableResults = this.stageOptions[stageKey] || [];
            
            const currentIndex = this.stageOrder.indexOf(stage);
            let nextStageKey = 'Selesai';
            if (currentIndex !== -1 && currentIndex < this.stageOrder.length - 1) {
                nextStageKey = this.stageOrder[currentIndex + 1];
            }

            this.stageData = {
                stage: stageKey,
                result: result || '',
                notes: notes || '',
                next_test_stage: this.stageDisplayNames[nextStageKey] || nextStageKey,
                next_test_date: date || '',
                field_date: fieldDate,
                field_result: fieldResult,
                field_notes: fieldNotes
            };

            this.showModal = true;
        },

        // Close the main modal
        closeModal() {
            if (this.isSubmitting) return;
            this.showModal = false;
        },

        // Show the comment modal
        showComment(comment) {
            this.selectedComment = comment;
            this.showCommentModal = true;
        },

        // Open CV Review modal
        openCVReviewModal(currentStatus, currentNotes, currentDate) {
            this.cvReviewData = {
                status: currentStatus || '',
                notes: currentNotes || '',
                date: currentDate || new Date().toISOString().split('T')[0]
            };
            this.showCVReviewModal = true;
        },

        // Submit CV Review
        async submitCVReview() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;

            try {
                const response = await fetch(`/candidates/{{ $candidate->id }}/stage`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        stage: 'CV Review',
                        status: this.cvReviewData.status,
                        notes: this.cvReviewData.notes,
                        date: this.cvReviewData.date
                    })
                });

                if (!response.ok) throw new Error('Failed to update CV Review');

                window.location.reload();
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memperbarui CV Review');
            } finally {
                this.isSubmitting = false;
            }
        },

        // Handle form submission
        async submitForm() {
            if (this.isSubmitting) return;

            // Basic validation
            if (!this.stageData.result) {
                alert('Hasil harus diisi.');
                return;
            }
            if (this.isPassingResult(this.stageData.result) && this.stageData.stage !== 'hiring' && !this.stageData.next_test_date) {
                alert('Tanggal tes berikutnya harus diisi jika hasil lulus.');
                return;
            }

            this.isSubmitting = true;

            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('stage', this.stageData.stage);
            formData.append('result', this.stageData.result);
            formData.append('notes', this.stageData.notes);
            
            if (this.isPassingResult(this.stageData.result)) {
                formData.append('next_test_stage', this.stageData.next_test_stage);
                formData.append('next_test_date', this.stageData.next_test_date);
            }

            try {
                const response = await fetch("{{ route('candidates.updateStage', $candidate) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    let errorMessage = 'Gagal memperbarui data.';
                    if (errorData.errors) {
                        errorMessage = Object.values(errorData.errors).flat().join('\n');
                    }
                    throw new Error(errorMessage);
                }

                // Success, reload the page to see changes
                window.location.reload();

            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            } finally {
                this.isSubmitting = false;
            }
        }
    }))
});
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