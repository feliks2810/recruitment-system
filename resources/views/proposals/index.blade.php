@extends('layouts.app')

@section('title', 'Vacancy Proposals')
@section('page-title', 'Vacancy Proposals')
@section('page-subtitle', 'Review and manage vacancy proposals')

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

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">Pending Proposals</h2>

                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposed By</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Needed</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documents</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if($proposals->isEmpty())
                                <tr>
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No pending proposals.</td>
                                </tr>
                            @else
                                @foreach($proposals as $proposal)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $proposal->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $proposal->department->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $proposal->proposedByUser->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $proposal->proposed_needed_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @forelse($proposal->manpowerRequestFiles as $file)
                                                <a href="{{ route('proposals.download', $file->id) }}" class="text-indigo-600 hover:text-indigo-900 underline">
                                                    @if($file->stage === 'download_file') Download File @elseif($file->stage === 'hc1_approved') HC1 Approved @elseif($file->stage === 'rejected') Rejected @else {{ ucfirst(str_replace('_', ' ', $file->stage)) }} @endif
                                                </a>
                                            @empty
                                                <span>No documents.</span>
                                            @endforelse
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div x-data="{ showHc1Modal: false, showRejectModal: false, hc1Action: 'approve' }">
                                                @if($proposal->proposal_status == \App\Models\Vacancy::STATUS_PENDING && Auth::user()->can('review-vacancy-proposals-step-1'))
                                                    <button @click="showHc1Modal = true" type="button" class="text-blue-600 hover:text-blue-900">Process (HC1)</button>

                                                    <!-- HC1 Process Modal -->
                                                    <div x-show="showHc1Modal" class="fixed z-10 inset-0 overflow-y-auto" style="display: none;">
                                                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                            <div x-show="showHc1Modal" @click="showHc1Modal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                                                            <div x-show="showHc1Modal" @click.stop class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                                <div class="border-b border-gray-200">
                                                                    <nav class="-mb-px flex" aria-label="Tabs">
                                                                        <button @click="hc1Action = 'approve'" :class="{ 'border-blue-500 text-blue-600': hc1Action === 'approve', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': hc1Action !== 'approve' }" class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm">
                                                                            Approve
                                                                        </button>
                                                                        <button @click="hc1Action = 'reject'" :class="{ 'border-red-500 text-red-600': hc1Action === 'reject', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': hc1Action !== 'reject' }" class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm">
                                                                            Reject
                                                                        </button>
                                                                    </nav>
                                                                </div>

                                                                <!-- HC1 Approve Form -->
                                                                <div x-show="hc1Action === 'approve'">
                                                                    <form action="{{ route('proposals.hc1-upload', $proposal->id) }}" method="POST" enctype="multipart/form-data">
                                                                        @csrf
                                                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                                            <h3 class="text-lg leading-6 font-medium text-gray-900">Approve and Submit to HC2</h3>
                                                                            <div class="mt-4">
                                                                                <label for="document_{{ $proposal->id }}" class="block text-sm font-medium text-gray-700">Upload Revised Document <span class="text-red-500">*</span></label>
                                                                                <input type="file" name="document" id="document_{{ $proposal->id }}" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Approve and Submit</button>
                                                                            <button @click="showHc1Modal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                                                                        </div>
                                                                    </form>
                                                                </div>

                                                                <!-- HC1 Reject Form -->
                                                                <div x-show="hc1Action === 'reject'" style="display: none;">
                                                                    <form action="{{ route('proposals.reject', $proposal->id) }}" method="POST">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                                            <h3 class="text-lg leading-6 font-medium text-gray-900">Reject Proposal</h3>
                                                                            <div class="mt-2">
                                                                                <label for="rejection_reason_hc1_{{ $proposal->id }}" class="block text-sm font-medium text-gray-700">Reason for rejection <span class="text-red-500">*</span></label>
                                                                                <textarea name="rejection_reason" id="rejection_reason_hc1_{{ $proposal->id }}" rows="3" class="shadow-sm mt-1 block w-full sm:text-sm border-gray-300 rounded-md" required></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Submit Rejection</button>
                                                                            <button @click="showHc1Modal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @elseif($proposal->proposal_status == \App\Models\Vacancy::STATUS_PENDING_HC2_APPROVAL && Auth::user()->can('review-vacancy-proposals-step-2'))
                                                    <div class="flex items-center justify-end">
                                                        <form action="{{ route('proposals.approve', $proposal->id) }}" method="POST" class="inline-block">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="text-green-600 hover:text-green-900">Approve (HC2)</button>
                                                        </form>
                                                        <button @click="showRejectModal = true" type="button" class="text-red-600 hover:text-red-900 ml-4">Reject</button>
                                                    </div>
                                                @else
                                                    <span class="text-gray-400 italic">No action available</span>
                                                @endif

                                                <!-- HC2 Reject Modal -->
                                                <div x-show="showRejectModal" class="fixed z-10 inset-0 overflow-y-auto" style="display: none;">
                                                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                        <div x-show="showRejectModal" @click="showRejectModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                                                        <div x-show="showRejectModal" @click.stop class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                            <form action="{{ route('proposals.reject', $proposal->id) }}" method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                                    <h3 class="text-lg leading-6 font-medium text-gray-900">Reject Proposal</h3>
                                                                    <div class="mt-2">
                                                                        <label for="rejection_reason_{{ $proposal->id }}" class="block text-sm font-medium text-gray-700">Reason for rejection <span class="text-red-500">*</span></label>
                                                                        <textarea name="rejection_reason" id="rejection_reason_{{ $proposal->id }}" rows="3" class="shadow-sm mt-1 block w-full sm:text-sm border-gray-300 rounded-md" required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Submit Rejection</button>
                                                                    <button @click="showRejectModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">Proposal History</h2>
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
                                @forelse($histories as $history)
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
@endsection