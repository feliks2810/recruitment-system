@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('candidates.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $candidate->nama }}</h1>
                <p class="text-sm text-gray-500">{{ $candidate->applicant_id }} • {{ $candidate->alamat_email }}</p>
            </div>
        </div>
        
        @if(Auth::user()->canModifyData())
            <div class="flex space-x-3">
                <a href="{{ route('candidates.edit', $candidate) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
                
                <form method="POST" action="{{ route('candidates.destroy', $candidate) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus kandidat ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Hapus
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Candidate Information -->
        <div class="lg:col-span-1">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Kandidat</h3>
                </div>
                <div class="border-t border-gray-200 px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->nama }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->alamat_email }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Applicant ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->applicant_id }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Jenis Kelamin</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->jk ?? '-' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $candidate->tanggal_lahir ? \Carbon\Carbon::parse($candidate->tanggal_lahir)->format('d/m/Y') : '-' }}
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Pendidikan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->jenjang_pendidikan ?? '-' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Perguruan Tinggi</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->perguruan_tinggi ?? '-' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Jurusan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->jurusan ?? '-' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">IPK</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->ipk ?? '-' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tipe Kandidat</dt>
                            <dd class="mt-1">
                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $candidate->airsys_internal === 'Yes' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $candidate->airsys_internal === 'Yes' ? 'Organik' : 'Non-Organik' }}
                                </span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status Keseluruhan</dt>
                            <dd class="mt-1">
                                @php
                                    $statusColors = [
                                        'LULUS' => 'bg-green-100 text-green-800',
                                        'TIDAK LULUS' => 'bg-red-100 text-red-800',
                                        'DALAM PROSES' => 'bg-yellow-100 text-yellow-800',
                                        'PENDING' => 'bg-blue-100 text-blue-800'
                                    ];
                                @endphp
                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $statusColors[$candidate->overall_status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $candidate->overall_status }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Job Information -->
            <div class="mt-6 overflow-hidden rounded-lg bg-white shadow">
                <div class="px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Posisi</h3>
                </div>
                <div class="border-t border-gray-200 px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Vacancy Airsys</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->vacancy_airsys }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Internal Position</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->internal_position ?? '-' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Source</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->source ?? '-' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">On Process By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $candidate->on_process_by ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Files -->
            @if($candidate->cv || $candidate->flk)
                <div class="mt-6 overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900">Berkas</h3>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-4">
                        <div class="space-y-3">
                            @if($candidate->cv)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="ml-2 text-sm text-gray-900">CV</span>
                                    </div>
                                    <a href="{{ Storage::url($candidate->cv) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                            @endif
                            
                            @if($candidate->flk)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="ml-2 text-sm text-gray-900">FLK</span>
                                    </div>
                                    <a href="{{ Storage::url($candidate->flk) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Timeline -->
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Timeline Rekrutmen</h3>
                    <p class="mt-1 text-sm text-gray-500">Progress tahapan seleksi kandidat</p>
                </div>
                <div class="border-t border-gray-200 px-6 py-4">
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($timeline as $index => $stage)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 {{ $stage['status'] === 'completed' ? 'bg-green-500' : ($stage['status'] === 'current' ? 'bg-indigo-500' : 'bg-gray-300') }}" aria-hidden="true"></span>
                                        @endif
                                        
                                        <div class="relative flex space-x-3">
                                            <div>
                                                @if($stage['status'] === 'completed')
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500 ring-8 ring-white">
                                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </span>
                                                @elseif($stage['status'] === 'current')
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-500 ring-8 ring-white">
                                                        <span class="h-2.5 w-2.5 rounded-full bg-white"></span>
                                                    </span>
                                                @elseif($stage['status'] === 'failed')
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-500 ring-8 ring-white">
                                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-400 ring-8 ring-white">
                                                        <span class="h-2.5 w-2.5 rounded-full bg-white"></span>
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ $stage['stage'] }}</p>
                                                    @if($stage['notes'])
                                                        <p class="mt-0.5 text-sm text-gray-500">{{ $stage['notes'] }}</p>
                                                    @endif
                                                    @if($stage['evaluator'])
                                                        <p class="mt-0.5 text-xs text-gray-400">Evaluator: {{ $stage['evaluator'] }}</p>
                                                    @endif
                                                    @if(isset($stage['result']) && $stage['result'])
                                                        <span class="mt-1 inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $stage['result'] === 'LULUS' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                            {{ $stage['result'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                    @if($stage['date'])
                                                        {{ \Carbon\Carbon::parse($stage['date'])->format('d/m/Y') }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if(Auth::user()->canModifyData() && $stage['status'] === 'current')
                                            <!-- Update Stage Form -->
                                            <div class="mt-4 ml-11 rounded-lg border border-gray-200 bg-gray-50 p-4" x-data="{ showForm: false }">
                                                <button @click="showForm = !showForm" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                                    Update Tahapan
                                                </button>
                                                
                                                <div x-show="showForm" x-transition class="mt-4">
                                                    <form method="POST" action="{{ route('candidates.updateStage', $candidate) }}">
                                                        @csrf
                                                        <input type="hidden" name="stage" value="{{ $stage['stage'] }}">
                                                        
                                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700">Hasil</label>
                                                                <select name="result" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                                    <option value="">Pilih Hasil</option>
                                                                    <option value="lulus">Lulus</option>
                                                                    <option value="tidak-lulus">Tidak Lulus</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-4">
                                                            <label class="block text-sm font-medium text-gray-700">Catatan</label>
                                                            <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Tambahkan catatan evaluasi..."></textarea>
                                                        </div>
                                                        
                                                        <div class="mt-4 flex justify-end space-x-3">
                                                            <button type="button" @click="showForm = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                                Batal
                                                            </button>
                                                            <button type="submit" class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                                Update
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection