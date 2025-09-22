@extends('layouts.app')

@section('title', 'Tambah Departemen')
@section('page-title', 'Manajemen Departemen')
@section('page-subtitle', 'Buat departemen baru.')

@section('content')
<div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-200 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Formulir Departemen Baru</h3>

    <form action="{{ route('departments.store') }}" method="POST">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nama Departemen</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-x-4 border-t border-gray-200 pt-6">
            <a href="{{ route('departments.index') }}" class="text-sm font-semibold leading-6 text-gray-900">Batal</a>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                Simpan Departemen
            </button>
        </div>
    </form>
</div>
@endsection
