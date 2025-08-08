@extends('layouts.app')

@section('title', 'Edit Profil')
@section('page-title', 'Edit Profil')
@section('page-subtitle', 'Perbarui informasi profil Anda')

@push('header-filters')
<a href="{{ route('dashboard') }}" class="text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center gap-2 border border-gray-300">
    <i class="fas fa-arrow-left text-sm"></i>
    <span>Kembali</span>
</a>
@endpush

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Edit Profil</h1>
    <form action="{{ route('profile.update') }}" method="POST" class="bg-white p-4 shadow-md rounded">
        @csrf
        @method('PATCH')
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Nama</label>
            <input type="text" name="name" id="name" value="{{ auth()->user()->name }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
        </div>
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" value="{{ auth()->user()->email }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Simpan</button>
        <a href="{{ route('profile.destroy') }}" class="ml-4 bg-red-500 text-white px-4 py-2 rounded" onclick="return confirm('Yakin ingin menghapus akun?')">Hapus Akun</a>
    </form>
</div>
@endsection

@push('scripts')
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