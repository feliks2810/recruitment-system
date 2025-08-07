<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Manajemen Akun - Patria Maritim Perkasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
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
                    <i class="fas fa-th-large text-sm"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('candidates.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-users text-sm"></i>
                    <span>Kandidat</span>
                </a>
                @if(in_array(Auth::user()->role, ['admin', 'team_hc']))
                <a href="{{ route('import.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-upload text-sm"></i>
                    <span>Import Excel</span>
                </a>
                @endif
                <a href="{{ route('statistics.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-chart-bar text-sm"></i>
                    <span>Statistik</span>
                </a>
                @if(Auth::user()->role === 'admin')
                <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 font-medium">
                    <i class="fas fa-user-cog text-sm"></i>
                    <span>Manajemen Akun</span>
                </a>
                @endif
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
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Manajemen Akun</h1>
                    <p class="text-sm text-gray-600">Kelola akun pengguna sistem</p>
                </div>
                <a href="{{ route('accounts.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <i class="fas fa-plus text-sm"></i>
                    <span>Tambah Akun</span>
                </a>
            </div>
        </header>

        <div class="flex-1 p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                <div class="bg-white rounded-xl p-5 border border-gray-200">
                    <p class="text-sm font-medium text-gray-500">Total Akun</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl p-5 border border-gray-200">
                    <p class="text-sm font-medium text-gray-500">Admin</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['admin'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl p-5 border border-gray-200">
                    <p class="text-sm font-medium text-gray-500">Team HC</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['team_hc'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl p-5 border border-gray-200">
                    <p class="text-sm font-medium text-gray-500">User</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['user'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl p-5 border border-gray-200">
                    <p class="text-sm font-medium text-gray-500">Department</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['department'] ?? 0 }}</p>
                </div>
            </div>

            @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
                {{ session('success') }}
            </div>
            @endif

            <!-- Users Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Daftar Akun Pengguna</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Dibuat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-blue-600">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->department ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($user->status)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('accounts.edit', $user->id) }}" class="text-blue-600 hover:text-blue-800" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if(Auth::user()->id !== $user->id)
                                        <form action="{{ route('accounts.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus akun ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-12">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500">Belum ada data akun.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </main>
</body>
</html>
