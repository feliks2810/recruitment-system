<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Import Excel - Patria Maritim Perkasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .drop-zone {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }
        .drop-zone.dragover {
            border-color: #3b82f6;
            background-color: #eff6ff;
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
                <a href="{{ route('import.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 font-medium">
                    <i class="fas fa-upload text-sm"></i>
                    <span>Import Excel</span>
                </a>
                <a href="{{ route('statistics.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-chart-bar text-sm"></i>
                    <span>Statistik</span>
                </a>
                <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-user-cog text-sm"></i>
                    <span>Manajemen Akun</span>
                </a>
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
                    <h1 class="text-xl font-semibold text-gray-900">Import Data Excel</h1>
                    <p class="text-sm text-gray-600">Upload file Excel untuk import data kandidat</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ asset('templates/template-import-kandidat.xlsx') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center gap-2">
                        <i class="fas fa-download text-sm"></i>
                        <span>Download Template</span>
                    </a>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                        <button class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-blue-600">{{ substr(Auth::user()->name, 0, 2) }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Upload Section -->
                <div class="bg-white rounded-xl p-8 border border-gray-200 mb-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-excel text-blue-600 text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">Upload File Excel</h2>
                        <p class="text-gray-600">Pilih file Excel (.xlsx, .xls) yang berisi data kandidat</p>
                    </div>

                    <form action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf
                        <!-- Drop Zone -->
                        <div class="drop-zone rounded-lg p-8 text-center mb-4" id="dropZone">
                            <div class="space-y-4">
                                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mx-auto">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-lg font-medium text-gray-700">Drag & drop file di sini</p>
                                    <p class="text-sm text-gray-500">atau klik untuk browse file</p>
                                </div>
                                <input type="file" name="file" id="fileInput" class="hidden" accept=".xlsx,.xls" required>
                                <button type="button" onclick="document.getElementById('fileInput').click()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                    Pilih File
                                </button>
                            </div>
                        </div>

                        <!-- File Info -->
                        <div id="fileInfo" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
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
                                <button type="button" onclick="clearFile()" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Tipe Kandidat</label>
                                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                                    <option value="">Pilih tipe kandidat</option>
                                    <option value="organic">Organik</option>
                                    <option value="non-organic">Non-Organik</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Mode Import</label>
                                <select name="import_mode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="insert">Insert Only (Tambah Baru)</option>
                                    <option value="update">Update Existing (Update yang Ada)</option>
                                    <option value="upsert">Insert & Update (Campuran)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submitBtn" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium" disabled>
                            <div class="flex items-center justify-center gap-2">
                                <i class="fas fa-upload"></i>
                                <span>Import Data</span>
                            </div>
                        </button>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Panduan Import</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Format File</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• File Excel (.xlsx atau .xls)</li>
                                <li>• Maksimal ukuran 10MB</li>
                                <li>• Gunakan template yang disediakan</li>
                                <li>• Header harus sesuai dengan template</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Kolom Wajib</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Nama</li>
                                <li>• Email</li>
                                <li>• Applicant ID</li>
                                <li>• Vacancy/Posisi</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Import History -->
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Riwayat Import</h3>
                    </div>
                    
                    @if(isset($import_history) && $import_history->count() > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach($import_history as $history)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 
                                        @if($history->status == 'success') bg-green-50 @elseif($history->status == 'failed') bg-red-50 @else bg-yellow-50 @endif 
                                        rounded-lg flex items-center justify-center">
                                        @if($history->status == 'success')
                                            <i class="fas fa-check-circle text-green-600"></i>
                                        @elseif($history->status == 'failed')
                                            <i class="fas fa-times-circle text-red-600"></i>
                                        @else
                                            <i class="fas fa-clock text-yellow-600"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $history->filename }}</p>
                                        <p class="text-sm text-gray-600">
                                            {{ $history->total_rows }} baris • {{ $history->success_rows }} berhasil • {{ $history->failed_rows }} gagal
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">{{ $history->created_at->diffForHumans() }}</p>
                                    @if($history->status == 'success')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Berhasil
                                        </span>
                                    @elseif($history->status == 'failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Gagal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
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
        </div>
    </main>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div id="success-alert" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
            <button onclick="document.getElementById('success-alert').remove()" class="ml-2">
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
            <button onclick="document.getElementById('error-alert').remove()" class="ml-2">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    @endif

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const submitBtn = document.getElementById('submitBtn');

        // Drag & Drop functionality
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

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        function handleFile(file) {
            // Validate file type
            const validTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel'
            ];
            
            if (!validTypes.includes(file.type)) {
                alert('File harus berformat Excel (.xlsx atau .xls)');
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

        function clearFile() {
            fileInput.value = '';
            fileInfo.classList.add('hidden');
            submitBtn.disabled = true;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Auto-hide alerts
        setTimeout(() => {
            const successAlert = document.getElementById('success-alert');
            const errorAlert = document.getElementById('error-alert');
            if (successAlert) successAlert.remove();
            if (errorAlert) errorAlert.remove();
        }, 5000);
    </script>
</body>
</html>