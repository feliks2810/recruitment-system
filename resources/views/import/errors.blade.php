@extends('layouts.app')

@section('title', 'Error Import')
@section('page-title', 'Detail Error Import')
@section('page-subtitle', 'Berikut adalah baris yang gagal diimport beserta alasannya')

@push('header-filters')
<div class="flex items-center gap-3">
    <a href="{{ route('import.index') }}" class="flex items-center gap-2 px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
        <i class="fas fa-arrow-left text-sm"></i>
        <span>Kembali ke Import</span>
    </a>
    <a href="{{ route('candidates.index') }}" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        <i class="fas fa-users text-sm"></i>
        <span>Lihat Kandidat</span>
    </a>
</div>
@endpush

@section('content')

        <!-- Content -->
        <div class="flex-1 p-6">
            @if(empty($errors))
            <!-- No Errors -->
            <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-green-900 mb-2">Import Berhasil!</h3>
                <p class="text-green-700 mb-4">Semua data berhasil diimport tanpa error.</p>
                <a href="{{ route('candidates.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-users"></i>
                    <span>Lihat Data Kandidat</span>
                </a>
            </div>
            @else
            <!-- Errors Summary -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-red-900">Ditemukan {{ count($errors) }} Error</h3>
                        <p class="text-sm text-red-700">Beberapa baris gagal diimport. Silakan perbaiki dan coba lagi.</p>
                    </div>
                </div>
                
                <!-- Error Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div class="bg-white rounded-lg p-4 border border-red-200">
                        <div class="text-2xl font-bold text-red-600">{{ count($errors) }}</div>
                        <div class="text-sm text-red-700">Baris Gagal</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-red-200">
                        <div class="text-2xl font-bold text-orange-600">
                            {{ collect($errors)->pluck('errors')->flatten()->groupBy('code')->count() }}
                        </div>
                        <div class="text-sm text-orange-700">Jenis Error</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-red-200">
                        <div class="text-2xl font-bold text-blue-600">
                            {{ collect($errors)->max('row') ?? 0 }}
                        </div>
                        <div class="text-sm text-blue-700">Baris Terakhir</div>
                    </div>
                </div>
            </div>

            <!-- Error Details -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Detail Error Import</h3>
                    <p class="text-sm text-gray-600 mt-1">Perbaiki error berikut sebelum mengimport ulang</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Baris</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solusi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($errors as $error)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Baris {{ $error->row() ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        @if(method_exists($error, 'errors') && is_array($error->errors()))
                                            @foreach($error->errors() as $err)
                                                <div class="mb-1">
                                                    <span class="inline-block w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                                    {{ $err['message'] ?? $err }}
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="inline-block w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                            Unknown error
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600 max-w-xs">
                                        @if(method_exists($error, 'values') && is_array($error->values()))
                                            <div class="space-y-1">
                                                @foreach(array_slice($error->values(), 0, 3, true) as $key => $value)
                                                    @if(!empty($value))
                                                    <div>
                                                        <span class="font-medium">{{ ucfirst($key) }}:</span> 
                                                        {{ Str::limit($value, 30) }}
                                                    </div>
                                                    @endif
                                                @endforeach
                                                @if(count($error->values()) > 3)
                                                <div class="text-xs text-gray-400">... dan {{ count($error->values()) - 3 }} lainnya</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400">No data available</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-blue-600">
                                        @php
                                            $errorMessage = '';
                                            if(method_exists($error, 'errors') && is_array($error->errors())) {
                                                $errorMessage = $error->errors()[0] ?? '';
                                            }
                                        @endphp
                                        
                                        @if(str_contains($errorMessage, 'email'))
                                            <i class="fas fa-lightbulb mr-1"></i>
                                            Pastikan format email valid
                                        @elseif(str_contains($errorMessage, 'required'))
                                            <i class="fas fa-lightbulb mr-1"></i>
                                            Lengkapi field yang wajib diisi
                                        @elseif(str_contains($errorMessage, 'unique'))
                                            <i class="fas fa-lightbulb mr-1"></i>
                                            Data sudah ada, hapus duplikat
                                        @elseif(str_contains($errorMessage, 'date'))
                                            <i class="fas fa-lightbulb mr-1"></i>
                                            Gunakan format DD/MM/YYYY
                                        @else
                                            <i class="fas fa-lightbulb mr-1"></i>
                                            Periksa format data
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Actions -->
                <div class="p-6 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            Total {{ count($errors) }} baris gagal diimport
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('import.template') }}" class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-download"></i>
                                <span>Download Template</span>
                            </a>
                            <a href="{{ route('import.index') }}" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fas fa-upload"></i>
                                <span>Import Ulang</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    @endsection