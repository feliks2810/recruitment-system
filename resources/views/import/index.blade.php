@extends('layouts.app')

@section('title', 'Import Excel')
@section('page-title', 'Import Data Excel')
@section('page-subtitle', 'Upload file Excel untuk import data kandidat')

@push('header-filters')
<div class="flex items-center gap-2">
    <a href="{{ route('import.template') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center gap-2 text-sm transition-colors">
        <i class="fas fa-download"></i>
        <span>Template XLSX</span>
    </a>
</div>
@endpush

@push('styles')
<style>
    .drop-zone {
        border: 2px dashed #e5e7eb;
        background-color: #f9fafb;
        transition: all 0.3s ease;
    }
    .drop-zone:hover,
    .drop-zone.dragover {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }
    .loading {
        display: none;
    }
    .loading.show {
        display: flex;
    }
</style>
@endpush

@section('content')
@can('import-excel')
<div class="space-y-6">
    <!-- Upload Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6">
            @if (session('import_summary'))
                <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Laporan Hasil Impor</h3>
                    @if (empty(session('import_summary')))
                        <p class="text-sm text-green-600 mt-2">✓ Semua baris berhasil diproses!</p>
                    @else
                        <p class="text-sm text-yellow-800 mt-2 bg-yellow-100 p-2 rounded-md">{{ count(session('import_summary')) }} baris dilewati dan tidak diimpor:</p>
                        <div class="mt-2 max-h-48 overflow-y-auto">
                            <ul class="text-sm text-gray-700 space-y-1">
                                @foreach (session('import_summary') as $skipped)
                                    <li class="border-b border-gray-200 py-1"><strong>Baris {{ $skipped['row'] }}:</strong> {{ $skipped['reason'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-excel text-blue-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Upload File Excel</h2>
                <p class="text-gray-600">Pilih file Excel (.xlsx, .xls) untuk import data</p>
            </div>

            <form action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm" class="max-w-2xl mx-auto">
                @csrf
                
                <!-- Drop Zone -->
                <div class="drop-zone rounded-lg p-8 text-center mb-6 cursor-pointer" id="dropZone">
                    <div class="space-y-4">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mx-auto">
                            <i class="fas fa-cloud-upload-alt text-gray-400 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-lg font-medium text-gray-700">Drag & drop file di sini</p>
                            <p class="text-sm text-gray-500">atau klik untuk browse file</p>
                        </div>
                        <input type="file" name="file" id="fileInput" class="hidden" accept=".xlsx,.xls,.csv" required>
                        <button type="button" onclick="document.getElementById('fileInput').click()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Pilih File
                        </button>
                    </div>
                </div>

                <!-- File Info -->
                <div id="fileInfo" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-excel text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-blue-900" id="fileName"></p>
                                <p class="text-sm text-blue-600" id="fileSize"></p>
                            </div>
                        </div>
                        <button type="button" onclick="clearFile()" class="text-blue-600 hover:text-blue-800 p-1 rounded">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mode Import</label>
                        <select name="import_mode" id="import_mode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="insert">Insert Only (Tambah Baru)</option>
                            <option value="update">Update Existing (Perbarui)</option>
                            <option value="upsert">Insert & Update (Campuran)</option>
                            <option value="update_stage">Update Stage (Perbarui Tahap)</option>
                        </select>
                    </div>

                    <div id="stage_selection_wrapper" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Tahap</label>
                        <select name="stage_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @foreach($stages as $stage)
                                <option value="{{ $stage }}">{{ ucwords(str_replace('_', ' ', $stage)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="candidate_options_wrapper">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Kandidat</label>
                                <select name="candidate_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="organic">Organik</option>
                                    <option value="non-organic">Non-Organik</option>
                                </select>
                            </div>
                            <div>
                                <label for="header_row" class="block text-sm font-medium text-gray-700 mb-2">Baris Header</label>
                                <select name="header_row" id="header_row" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="1" {{ old('header_row', '1') == '1' ? 'selected' : '' }}>Baris 1</option>
                                    <option value="2" {{ old('header_row', '1') == '2' ? 'selected' : '' }}>Baris 2</option>
                                    <option value="3" {{ old('header_row', '1') == '3' ? 'selected' : '' }}>Baris 3</option>
                                    <option value="4" {{ old('header_row', '1') == '4' ? 'selected' : '' }}>Baris 4</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar (Hidden by default) -->
                <div id="progressBar" class="hidden mb-6">
                    <div class="bg-gray-200 rounded-full h-2 mb-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p class="text-sm text-gray-600 text-center">Mengimpor data...</p>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium transition-colors" disabled>
                    <div class="flex items-center justify-center gap-2">
                        <i class="fas fa-upload" id="submitIcon"></i>
                        <span id="submitText">Import Data</span>
                        <div class="loading items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memproses...</span>
                        </div>
                    </div>
                </button>
            </form>
        </div>
    </div>

    <!-- Instructions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-600"></i>
                Panduan Import
            </h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-file-alt text-green-600 text-sm"></i>
                            Format File
                        </h4>
                        <ul class="text-sm text-gray-600 space-y-2 pl-4">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-green-500 mt-0.5 text-xs"></i>
                                File Excel (.xlsx atau .xls)
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-green-500 mt-0.5 text-xs"></i>
                                Maksimal ukuran 10MB
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-green-500 mt-0.5 text-xs"></i>
                                Sistem akan otomatis mendeteksi jenis kandidat
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-user text-blue-600 text-sm"></i>
                            Kolom Wajib (Organik)
                        </h4>
                        <ul class="text-sm text-gray-600 space-y-2 pl-4">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-dot-circle text-blue-500 mt-0.5 text-xs"></i>
                                Nama
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-dot-circle text-blue-500 mt-0.5 text-xs"></i>
                                Email
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-dot-circle text-blue-500 mt-0.5 text-xs"></i>
                                Vacancy/Posisi
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle text-gray-400 mt-0.5 text-xs"></i>
                                Applicant ID (opsional, akan digenerate otomatis)
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-building text-purple-600 text-sm"></i>
                            Kolom Wajib (Non-Organik)
                        </h4>
                        <ul class="text-sm text-gray-600 space-y-2 pl-4">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-dot-circle text-purple-500 mt-0.5 text-xs"></i>
                                Department
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-dot-circle text-purple-500 mt-0.5 text-xs"></i>
                                Nama Posisi
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-dot-circle text-purple-500 mt-0.5 text-xs"></i>
                                Quantity Target
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-dot-circle text-purple-500 mt-0.5 text-xs"></i>
                                Sourcing (Internal/Eksternal)
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-lightbulb text-yellow-600 text-sm"></i>
                            Tips
                        </h4>
                        <ul class="text-sm text-gray-600 space-y-2 pl-4">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-star text-yellow-500 mt-0.5 text-xs"></i>
                                Pastikan tidak ada baris kosong di tengah data
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-star text-yellow-500 mt-0.5 text-xs"></i>
                                Format tanggal: DD/MM/YYYY atau YYYY-MM-DD
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-star text-yellow-500 mt-0.5 text-xs"></i>
                                Email harus unik untuk setiap kandidat
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-star text-yellow-500 mt-0.5 text-xs"></i>
                                Gunakan mode "Upsert" untuk update data yang ada
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import History -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-history text-gray-600"></i>
                Riwayat Import
            </h3>
        </div>
        
        @if(isset($import_history) && $import_history->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($import_history as $history)
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 
                            @if($history->status == 'success') bg-green-50 @elseif($history->status == 'failed') bg-red-50 @else bg-blue-50 @endif 
                            rounded-lg flex items-center justify-center">
                            @if($history->status == 'success')
                                <i class="fas fa-check-circle text-green-600"></i>
                            @elseif($history->status == 'failed')
                                <i class="fas fa-times-circle text-red-600"></i>
                            @else
                                <i class="fas fa-clock text-blue-600"></i>
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $history->filename }}</p>
                            <p class="text-sm text-gray-600">
                                {{ number_format($history->total_rows) }} baris • 
                                {{ number_format($history->success_rows) }} berhasil • 
                                {{ number_format($history->failed_rows) }} gagal
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 mb-1">{{ $history->created_at->diffForHumans() }}</p>
                        @if($history->status == 'success')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Berhasil
                            </span>
                        @elseif($history->status == 'failed')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Gagal
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Proses
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">Belum ada riwayat import</p>
        </div>
        @endif
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mode switching logic
    const importModeSelect = document.getElementById('import_mode');
    const stageSelection = document.getElementById('stage_selection_wrapper');
    const candidateOptions = document.getElementById('candidate_options_wrapper');

    importModeSelect.addEventListener('change', function() {
        if (this.value === 'update_stage') {
            stageSelection.classList.remove('hidden');
            candidateOptions.classList.add('hidden');
        } else {
            stageSelection.classList.add('hidden');
            candidateOptions.classList.remove('hidden');
        }
    });

    // Trigger change event on page load to set initial state
    importModeSelect.dispatchEvent(new Event('change'));

    // Original script for file upload
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');
    const uploadForm = document.getElementById('uploadForm');
    const progressBar = document.getElementById('progressBar');
    const submitIcon = document.getElementById('submitIcon');
    const submitText = document.getElementById('submitText');
    const loading = document.querySelector('.loading');

    // Drag & Drop functionality
    if (dropZone) {
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });

        dropZone.addEventListener('click', () => {
            fileInput.click();
        });
    }

    if(fileInput) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });
    }

    // Form submission with loading state
    if (uploadForm) {
        uploadForm.addEventListener('submit', (e) => {
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Silakan pilih file terlebih dahulu');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitIcon.style.display = 'none';
            submitText.style.display = 'none';
            loading.classList.add('show');
            progressBar.classList.remove('hidden');

            // Simulate progress
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress > 90) progress = 90;
                progressBar.querySelector('.bg-blue-600').style.width = progress + '%';
            }, 500);

            // Clean up interval after 30 seconds
            setTimeout(() => {
                clearInterval(progressInterval);
            }, 30000);
        });
    }

    function handleFile(file) {
        // Validate file type
        const validTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv',
            'application/csv'
        ];
        
        const isValidType = validTypes.includes(file.type) || 
                           file.name.toLowerCase().endsWith('.xlsx') || 
                           file.name.toLowerCase().endsWith('.xls') ||
                           file.name.toLowerCase().endsWith('.csv');
        
        if (!isValidType) {
            alert('File harus berformat Excel (.xlsx, .xls) atau CSV');
            return;
        }

        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            alert('Ukuran file maksimal 10MB');
            return;
        }

        // Update UI
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.classList.remove('hidden');
        submitBtn.disabled = false;
        
        // Update file input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
    }

    window.clearFile = function() {
        fileInput.value = '';
        fileInfo.classList.add('hidden');
        submitBtn.disabled = true;
        
        // Reset loading state
        submitIcon.style.display = 'inline';
        submitText.style.display = 'inline';
        submitText.textContent = 'Import Data';
        loading.classList.remove('show');
        progressBar.classList.add('hidden');
        submitBtn.disabled = fileInput.files.length === 0;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>
@endpush

<!-- Success/Error Messages -->
@if(session('success'))
<div id="success-alert" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    <div class="flex items-center gap-2">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:text-green-200">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('warning'))
<div id="warning-alert" class="fixed top-4 right-4 bg-yellow-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    <div class="flex items-center gap-2">
        <i class="fas fa-exclamation-triangle"></i>
        <span>{{ session('warning') }}</span>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:text-yellow-200">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if(session('error'))
<div id="error-alert" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    <div class="flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ session('error') }}</span>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:text-red-200">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

@if($errors->any())
<div id="validation-alert" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 max-w-md">
    <div class="flex items-start gap-2">
        <i class="fas fa-exclamation-circle mt-0.5"></i>
        <div class="flex-1">
            @foreach($errors->all() as $error)
                <div class="text-sm">{{ $error }}</div>
            @endforeach
        </div>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:text-red-200">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif