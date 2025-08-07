<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Edit Akun - Patria Maritim Perkasa</title>
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
    <main class="flex-1 p-6">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-2xl font-semibold text-gray-900 mb-6">Edit Akun</h1>

            @if ($errors->any())
                <div class="bg-red-50 text-red-800 p-4 rounded-lg mb-4">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="bg-green-50 text-green-800 p-4 rounded-lg mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('accounts.update', $account->id) }}" method="POST" class="bg-white shadow-md rounded-lg p-6">
                @csrf
                @method('PUT')
                <div class="grid gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $account->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $account->email) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Kata Sandi Baru (opsional)</label>
                        <input type="password" name="password" id="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                                                <select name="role" id="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="toggleDepartment(this.value)">
                            <option value="admin" {{ $account->role == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="team_hc" {{ $account->role == 'team_hc' ? 'selected' : '' }}>Team HC</option>
                            <option value="user" {{ $account->role == 'user' ? 'selected' : '' }}>User</option>
                            <option value="department" {{ $account->role == 'department' ? 'selected' : '' }}>Department</option>
                        </select>
                    </div>
                    <div id="department-field" style="display: {{ $account->role === 'department' ? 'block' : 'none' }};">
                        <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                        <select name="department" id="department" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ $account->department == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="1" {{ $account->status ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ !$account->status ? 'selected' : '' }}>Non-Aktif</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        function toggleDepartment(role) {
            const departmentField = document.getElementById('department-field');
            if (role === 'department') {
                departmentField.style.display = 'block';
            } else {
                departmentField.style.display = 'none';
            }
        }
    </script>
</body>
</html>