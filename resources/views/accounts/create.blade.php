<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Buat Akun Baru - Patria Maritim Perkasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex">
    <!-- Sidebar -->
    <aside class="bg-white w-64 min-h-screen border-r border-gray-200 flex flex-col">
        <div class="p-4 flex justify-center">
            <img src="{{ asset('images/Logo Patria.png') }}" alt="Logo Patria" class="w-30 h-auto object-contain">
        </div>
        <nav class="flex-1 p-4">
            <div class="space-y-2">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-th-large text-sm"></i><span>Dasbor</span>
                </a>
                <a href="{{ route('candidates.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-users text-sm"></i><span>Kandidat</span>
                </a>
                                @if (in_array(Auth::user()->role, ['admin', 'team_hc']))
                    <a href="{{ route('import.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                        <i class="fas fa-upload text-sm"></i><span>Impor Excel</span>
                    </a>
                    <a href="{{ route('statistics.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                        <i class="fas fa-chart-bar text-sm"></i><span>Statistik</span>
                    </a>
                @endif
                @if (Auth::user()->role === 'admin')
                    <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 font-medium">
                        <i class="fas fa-user-cog text-sm"></i><span>Manajemen Akun</span>
                    </a>
                @endif
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Buat Akun Baru</h1>
                    <p class="text-sm text-gray-600">Isi detail di bawah untuk membuat akun pengguna baru.</p>
                </div>
                <a href="{{ route('accounts.index') }}" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Manajemen Akun</span>
                </a>
            </div>
        </header>

        <div class="flex-1 p-6">
            <div class="max-w-3xl mx-auto">
                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                        <strong class="font-bold">Oops!</strong>
                        <span class="block sm:inline">Ada beberapa masalah dengan input Anda.</span>
                        <ul class="mt-3 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-white rounded-xl p-8 border border-gray-200">
                    <form action="{{ route('accounts.store') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nama Lengkap -->
                            <div class="md:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- Role -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select name="role" id="role" required onchange="toggleDepartment(this.value)"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">Pilih Role</option>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="team_hc" {{ old('role') == 'team_hc' ? 'selected' : '' }}>Team HC</option>
                                    <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                                    <option value="department" {{ old('role') == 'department' ? 'selected' : '' }}>Department</option>
                                </select>
                            </div>

                            <!-- Department (Conditional) -->
                            <div id="department-field" class="{{ old('role') == 'department' ? '' : 'hidden' }} md:col-span-2">
                                <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                                <select name="department" id="department"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">Pilih Departemen</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept }}" {{ old('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                                <input type="password" name="password" id="password" required
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- Konfirmasi Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Kata Sandi</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            
                            <!-- Status -->
                             <div class="md:col-span-2">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" id="status" required
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Non-Aktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-8 pt-5 border-t border-gray-200">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('accounts.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm font-medium">
                                    Batal
                                </a>
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium flex items-center gap-2">
                                    <i class="fas fa-save"></i>
                                    <span>Buat Akun</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleDepartment(role) {
            const departmentField = document.getElementById('department-field');
            const departmentSelect = document.getElementById('department');
            if (role === 'department') {
                departmentField.classList.remove('hidden');
                departmentSelect.required = true;
            } else {
                departmentField.classList.add('hidden');
                departmentSelect.required = false;
                // Clear the department value when not needed
                departmentSelect.value = '';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            toggleDepartment(roleSelect.value);
        });
    </script>
</body>
</html>
