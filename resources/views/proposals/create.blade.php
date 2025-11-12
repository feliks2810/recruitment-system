@extends('layouts.app')

@section('title', 'Propose Vacancy')
@section('page-title', 'Propose Vacancy')
@section('page-subtitle', 'Propose a new vacancy for your department')

@push('header-filters')
<div class="flex items-center gap-2">
    <label for="year" class="text-sm font-medium text-gray-700 hidden sm:block">Tahun:</label>
    <select name="year" id="yearFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[80px]">
        @php
            $currentYear = date('Y');
            for ($year = $currentYear + 1; $year >= $currentYear - 2; $year--) {
                echo '<option value="' . $year . '"' . (request('year', $currentYear) == $year ? ' selected' : '') . '>' . $year . '</option>';
            }
        @endphp
    </select>
</div>
@endpush

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 sm:mb-8">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Proposals</p>
                <p class="text-3xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-file-alt text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Pending</p>
                <p class="text-3xl font-bold text-yellow-500">{{ $stats['pending'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-yellow-500 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Approved</p>
                <p class="text-3xl font-bold text-green-500">{{ $stats['approved'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Rejected</p>
                <p class="text-3xl font-bold text-red-500">{{ $stats['rejected'] ?? 0 }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-times-circle text-red-500 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">Propose New Vacancy</h2>

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <form action="{{ route('proposals.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="vacancy_id" class="block text-sm font-medium text-gray-700">Position</label>
                            <select name="vacancy_id" id="vacancy_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="" disabled selected>Select a position</option>
                                @foreach($vacancies as $vacancy)
                                    <option value="{{ $vacancy->id }}">{{ $vacancy->name }}</option>
                                @endforeach
                            </select>
                            @error('vacancy_id')
                                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="proposed_needed_count" class="block text-sm font-medium text-gray-700">Number of People Needed</label>
                            <input name="proposed_needed_count" type="number" id="proposed_needed_count" min="1" value="1" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            @error('proposed_needed_count')
                                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <div id="pending-proposal-info" style="display: none;" class="text-red-500 text-sm mr-4">
                                A proposal for this position is already pending.
                            </div>
                            <button id="submit-proposal-btn" type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150" disabled>
                                Submit Proposal
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">Riwayat Pengajuan</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposed Count</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submission Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HC1 Processed Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HC2 Processed Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes / Rejection Reason</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($proposalHistories as $history)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $history->vacancy->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $history->proposed_needed_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $history->created_at->format('d M Y H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @php
                                                $hc1Date = null;
                                                if ($history->hc1_approved_at) {
                                                    $hc1Date = $history->hc1_approved_at;
                                                } elseif ($history->status == 'rejected' && is_null($history->hc1_approved_at)) {
                                                    $hc1Date = $history->updated_at;
                                                }
                                            @endphp
                                            {{ $hc1Date ? \Carbon\Carbon::parse($hc1Date)->format('d M Y H:i') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @php
                                                $hc2Text = 'N/A';
                                                if ($history->hc2_approved_at) {
                                                    $hc2Text = \Carbon\Carbon::parse($history->hc2_approved_at)->format('d M Y H:i');
                                                } elseif ($history->status == 'rejected' && $history->hc1_approved_at) {
                                                    $hc2Text = \Carbon\Carbon::parse($history->updated_at)->format('d M Y H:i');
                                                } elseif ($history->status == 'rejected' && is_null($history->hc1_approved_at)) {
                                                    $hc2Text = 'Rejected at HC1';
                                                }
                                            @endphp
                                            {{ $hc2Text }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($history->status == 'pending')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                            @elseif($history->status == 'pending_hc2_approval')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Pending HC 2 Approval</span>
                                            @elseif($history->status == 'approved')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                                            @elseif($history->status == 'rejected')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $history->notes ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No proposal history.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pendingProposalVacancyIds = @json($pendingProposalVacancyIds ?? []);
        const vacancySelect = document.getElementById('vacancy_id');
        const submitButton = document.getElementById('submit-proposal-btn');
        const infoMessage = document.getElementById('pending-proposal-info');

        vacancySelect.addEventListener('change', function() {
            const selectedVacancyId = parseInt(this.value);
            if (this.value === "") {
                submitButton.disabled = true;
                infoMessage.style.display = 'none';
            } else if (pendingProposalVacancyIds.includes(selectedVacancyId)) {
                submitButton.disabled = true;
                infoMessage.style.display = 'block';
            } else {
                submitButton.disabled = false;
                infoMessage.style.display = 'none';
            }
        });

        // Initial check in case a value is pre-selected
        vacancySelect.dispatchEvent(new Event('change'));
    });
</script>
@endpush
@endsection
