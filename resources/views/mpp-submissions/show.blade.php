@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Detail Pengajuan MPP</h1>
                <p class="mt-2 text-gray-600">{{ $mppSubmission->department->name }}</p>
            </div>
            <a href="{{ route('mpp-submissions.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                ← Kembali
            </a>
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

        <div class="space-y-6">
            <!-- MPP Info Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Informasi Pengajuan</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Departemen</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $mppSubmission->department->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Dibuat Oleh</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $mppSubmission->createdByUser->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tanggal Dibuat</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $mppSubmission->created_at->format('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Vacancies Table -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Posisi & Dokumen</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posisi MPP</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Approval</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelengkapan Dokumen</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($mppSubmission->vacancies as $vacancy)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <strong>{{ $vacancy->name }}</strong>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        @if($vacancy->vacancy_status === 'OSPKWT')
                                            bg-blue-100 text-blue-800
                                        @elseif($vacancy->vacancy_status === 'OS')
                                            bg-purple-100 text-purple-800
                                        @endif
                                    ">
                                        {{ $vacancy->vacancy_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <span class="font-semibold">{{ $vacancy->needed_count ?? '-' }} orang</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($vacancy->proposal_status === 'approved')
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Disetujui
                                        </span>
                                    @elseif($vacancy->proposal_status === 'rejected')
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800" title="{{ $vacancy->rejection_reason }}">
                                            Ditolak
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Menunggu
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @php
                                        $requiredDocType = $vacancy->vacancy_status === 'OSPKWT' ? 'A1' : 'B1';
                                        $document = $vacancy->getDocument($requiredDocType);
                                        $isDepartmentUser = auth()->user()->hasRole('department') || auth()->user()->hasRole('department_head');
                                    @endphp
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-600">Dokumen {{ $requiredDocType }}:</span>
                                            @if ($document)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    ✓ {{ $document->status }}
                                                </span>
                                            @else
                                                @if ($isDepartmentUser)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        ⚠ Menunggu upload
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        ✗ Belum upload
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-col space-y-2">
                                        {{-- Document Actions --}}
                                        @if ($document)
                                            <div class="flex gap-2">
                                                <a href="{{ route('vacancy-documents.preview', [$vacancy, $document]) }}" class="text-blue-600 hover:text-blue-900 text-xs font-medium" target="_blank">
                                                    [Preview Dok]
                                                </a>
                                                @if ($isDepartmentUser && auth()->user()->department_id === $vacancy->department_id && $document->status === 'pending')
                                                <form action="{{ route('vacancy-documents.destroy', [$vacancy, $document]) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-medium" onclick="return confirm('Yakin ingin menghapus dokumen ini?')">
                                                        [Hapus Dok]
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        @elseif ($isDepartmentUser && auth()->user()->department_id === $vacancy->department_id)
                                            <a href="{{ route('vacancy-documents.upload', $vacancy) }}" class="text-green-600 hover:text-green-900 text-xs font-medium">
                                                [+ Upload Dok]
                                            </a>
                                        @endif

                                        {{-- Approval Actions (Team HC) --}}
                                        @can('approve-mpp-submission')
                                            @if($vacancy->proposal_status === 'pending' || $vacancy->proposal_status === null)
                                            <div class="flex gap-2 mt-2 pt-2 border-t border-gray-100">
                                                <form action="{{ route('mpp-submissions.approve-vacancy', $vacancy) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700">
                                                        Approve
                                                    </button>
                                                </form>

                                                <form action="{{ route('mpp-submissions.reject-vacancy', $vacancy) }}" method="POST" onsubmit="return handleReject(event, this)">
                                                    @csrf
                                                    <input type="hidden" name="rejection_reason" class="rejection-reason-input">
                                                    <button type="submit" class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                    Tidak ada posisi
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Approval History -->
            @if ($mppSubmission->approvalHistories->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Riwayat Aktivitas</h2>
                <div class="space-y-4">
                    @foreach ($mppSubmission->approvalHistories->sortByDesc('created_at') as $history)
                    <div class="flex gap-4 pb-4 border-b last:border-b-0">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100">
                                <span class="text-sm font-medium text-blue-800">{{ substr($history->user->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $history->user->name }}
                                <span class="text-gray-600">{{ strtoupper($history->action) }}</span>
                            </p>
                            <p class="text-xs text-gray-500">{{ $history->created_at->format('d M Y H:i') }}</p>
                            @if ($history->notes)
                            <p class="text-sm text-gray-700 mt-1">{{ $history->notes }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function handleReject(event, form) {
    event.preventDefault();
    const reason = prompt("Masukkan alasan penolakan:");
    if (reason && reason.trim() !== "") {
        form.querySelector('.rejection-reason-input').value = reason;
        form.submit();
    }
    return false;
}
</script>
@endsection
