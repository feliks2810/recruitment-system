@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Upload Dokumen Vacancy</h1>
                <p class="mt-2 text-gray-600">{{ $vacancy->name }} - {{ $vacancy->department->name }}</p>
            </div>
            <a href="{{ route('mpp-submissions.show', $vacancy->mppSubmission) }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                ← Kembali
            </a>
        </div>

        <!-- Info Box -->
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
                <strong>Status Vacancy:</strong> 
                <span class="px-2 py-1 rounded text-xs font-medium
                    @if($vacancy->vacancy_status === 'OSPKWT')
                        bg-blue-100 text-blue-800
                    @elseif($vacancy->vacancy_status === 'OS')
                        bg-purple-100 text-purple-800
                    @endif
                ">
                    {{ $vacancy->vacancy_status }}
                </span>
            </p>
            @php
                $requiredDocType = $vacancy->vacancy_status === 'OSPKWT' ? 'A1' : 'B1';
            @endphp
            <p class="text-sm text-blue-800 mt-2">
                <strong>Dokumen yang diperlukan:</strong> Dokumen {{ $requiredDocType }}
            </p>
        </div>

        <!-- Success/Error Messages -->
        @if ($message = Session::get('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ $message }}
        </div>
        @endif

        @if ($message = Session::get('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ $message }}
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Upload Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Upload Dokumen</h2>
                    
                    <form action="{{ route('vacancy-documents.upload', $vacancy) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Document Type (Hidden) -->
                        <input type="hidden" name="document_type" value="{{ $requiredDocType }}">

                        <!-- File Upload -->
                        <div class="mb-6">
                            <label for="document" class="block text-sm font-medium text-gray-700 mb-2">
                                Pilih File (PDF, DOC, DOCX, XLS, XLSX) <span class="text-red-500">*</span>
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition"
                                 onclick="document.getElementById('document').click()">
                                <input 
                                    type="file" 
                                    id="document" 
                                    name="document"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx"
                                    style="display: none;"
                                    onchange="updateFileName(this)"
                                    required
                                >
                                <div id="file-info" class="text-gray-600">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v24a4 4 0 004 4h24a4 4 0 004-4V20l-8-12z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="mt-2 text-sm">Klik untuk memilih file atau drag & drop</p>
                                    <p class="text-xs text-gray-500 mt-1">Maksimal ukuran: 10 MB</p>
                                </div>
                            </div>
                            @error('document')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="w-full px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium"
                        >
                            Upload Dokumen
                        </button>
                    </form>
                </div>
            </div>

            <!-- Dokumen yang Sudah Upload -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Dokumen Terupload</h2>

                    @php
                        $documents = $vacancy->vacancyDocuments;
                    @endphp

                    @if ($documents->count() > 0)
                        <div class="space-y-3">
                            @foreach ($documents as $doc)
                            <div class="p-3 bg-gray-50 rounded border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-medium text-gray-900">
                                        Dokumen {{ $doc->document_type }}
                                    </p>
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        @if($doc->status === 'approved')
                                            bg-green-100 text-green-800
                                        @elseif($doc->status === 'rejected')
                                            bg-red-100 text-red-800
                                        @else
                                            bg-yellow-100 text-yellow-800
                                        @endif
                                    ">
                                        {{ ucfirst($doc->status) }}
                                    </span>
                                </div>

                                <p class="text-xs text-gray-600 break-words mb-2">
                                    {{ $doc->original_filename }}
                                </p>

                                <p class="text-xs text-gray-500 mb-2">
                                    Upload: {{ $doc->created_at->format('d M Y H:i') }}
                                    @if($doc->reviewed_at)
                                    <br>Review: {{ $doc->reviewed_at->format('d M Y H:i') }}
                                    @endif
                                </p>

                                @if ($doc->review_notes)
                                <div class="bg-gray-100 p-2 rounded mb-2 text-xs">
                                    <p class="text-gray-700">{{ $doc->review_notes }}</p>
                                </div>
                                @endif

                                <div class="flex gap-2">
                                    <a href="{{ route('vacancy-documents.download', [$vacancy, $doc]) }}" class="flex-1 px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 text-center">
                                        Download
                                    </a>
                                    @if (auth()->user()->id === $doc->uploaded_by_user_id && $doc->status === 'pending')
                                    <form action="{{ route('vacancy-documents.destroy', [$vacancy, $doc]) }}" method="POST" style="flex: 1;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700" onclick="return confirm('Yakin ingin menghapus?')">
                                            Hapus
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-600 text-center py-6">
                            Belum ada dokumen yang diupload
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateFileName(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileInfo = document.getElementById('file-info');
        fileInfo.innerHTML = `
            <div class="text-gray-600">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v24a4 4 0 004 4h24a4 4 0 004-4V20l-8-12z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="mt-2 text-sm font-medium">${file.name}</p>
                <p class="text-xs text-gray-500 mt-1">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
            </div>
        `;
    }
}
</script>
@endsection
