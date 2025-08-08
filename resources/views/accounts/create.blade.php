@extends('layouts.app')

@section('title', 'Tambah Akun')
@section('page-title', 'Tambah Akun')
@section('page-subtitle', 'Buat akun pengguna baru')

@push('header-filters')
<a href="{{ route('accounts.index') }}" class="text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center gap-2 border border-gray-300">
    <i class="fas fa-arrow-left text-sm"></i>
    <span>Kembali</span>
</a>
@endpush

@section('content')
@can('manage-users')
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Form Akun</h3>
        </div>
        
        <div class="p-6">
                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exclamation-circle text-red-600 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-red-800 font-medium">Terdapat kesalahan dalam pengisian form:</p>
                                    <ul class="list-disc pl-5 text-red-800 mt-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('candidates.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Informasi Dasar</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama *</label>
                                    <input type="text" name="nama" id="nama" value="{{ old('nama') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                </div>
                                <div>
                                    <label for="alamat_email" class="block text-sm font-medium text-gray-700">Email *</label>
                                    <input type="email" name="alamat_email" id="alamat_email" value="{{ old('alamat_email') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                </div>
                                <div>
                                    <label for="applicant_id" class="block text-sm font-medium text-gray-700">Applicant ID *</label>
                                    <input type="text" name="applicant_id" id="applicant_id" value="{{ old('applicant_id') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                </div>
                                <div>
                                    <label for="jk" class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                                    <select name="jk" id="jk" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="L" {{ old('jk') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="P" {{ old('jk') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Informasi Posisi</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="vacancy_airsys" class="block text-sm font-medium text-gray-700">Vacancy *</label>
                                    <input type="text" name="vacancy_airsys" id="vacancy_airsys" value="{{ old('vacancy_airsys') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                </div>
                                <div>
                                    <label for="airsys_internal" class="block text-sm font-medium text-gray-700">Tipe Kandidat *</label>
                                    <select name="airsys_internal" id="airsys_internal" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                                        <option value="Yes" {{ old('airsys_internal') == 'Yes' ? 'selected' : '' }}>Organik</option>
                                        <option value="No" {{ old('airsys_internal') == 'No' ? 'selected' : '' }}>Non-Organik</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="internal_position" class="block text-sm font-medium text-gray-700">Posisi Internal</label>
                                    <input type="text" name="internal_position" id="internal_position" value="{{ old('internal_position') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                                <div>
                                    <label for="source" class="block text-sm font-medium text-gray-700">Sumber</label>
                                    <input type="text" name="source" id="source" value="{{ old('source') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Informasi Pendidikan</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="jenjang_pendidikan" class="block text-sm font-medium text-gray-700">Jenjang Pendidikan</label>
                                    <input type="text" name="jenjang_pendidikan" id="jenjang_pendidikan" value="{{ old('jenjang_pendidikan') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                                <div>
                                    <label for="perguruan_tinggi" class="block text-sm font-medium text-gray-700">Perguruan Tinggi</label>
                                    <input type="text" name="perguruan_tinggi" id="perguruan_tinggi" value="{{ old('perguruan_tinggi') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                                <div>
                                    <label for="jurusan" class="block text-sm font-medium text-gray-700">Jurusan</label>
                                    <input type="text" name="jurusan" id="jurusan" value="{{ old('jurusan') }}" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                                <div>
                                    <label for="ipk" class="block text-sm font-medium text-gray-700">IPK</label>
                                    <input type="number" name="ipk" id="ipk" value="{{ old('ipk') }}" step="0.01" min="0" max="4" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Berkas</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="cv" class="block text-sm font-medium text-gray-700">CV</label>
                                    <input type="file" name="cv" id="cv" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                                <div>
                                    <label for="flk" class="block text-sm font-medium text-gray-700">FLK</label>
                                    <input type="file" name="flk" id="flk" class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                            </div>
                        </div>

                        @endcan
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const departmentField = document.getElementById('department-field');

        function toggleDepartmentField() {
            if (roleSelect.value === 'department') {
                departmentField.style.display = 'block';
            } else {
                departmentField.style.display = 'none';
            }
        }

        roleSelect.addEventListener('change', toggleDepartmentField);
        toggleDepartmentField(); // Initial check
    });
</script>
    <!-- Success Message -->
    @if(session('success'))
    <div id="success-alert" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
            <button onclick="document.getElementById('success-alert').remove()" class="ml-2 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const alert = document.getElementById('success-alert');
            if (alert) alert.remove();
        }, 5000);
    </script>
    @endif

    <!-- Error Message -->
    @if(session('error'))
    <div id="error-alert" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
            <button onclick="document.getElementById('error-alert').remove()" class="ml-2 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <script>
        setTimeout(() => {
            const alert = document.getElementById('error-alert');
            if (alert) alert.remove();
        }, 5000);
    </script>
    @endif
@endpush
                    </form>
                </div>
            </div>
        </div>
    </main>
    @endcan
</body>
</html>