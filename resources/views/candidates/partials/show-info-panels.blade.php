<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Personal Information Panel -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Personal Information</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Full Name</p>
                <p class="text-gray-800 dark:text-gray-200">{{ $candidate->nama }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                <p class="text-gray-800 dark:text-gray-200">{{ $candidate->alamat_email ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Gender</p>
                <p class="text-gray-800 dark:text-gray-200">{{ $candidate->jk ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Date of Birth</p>
                <p class="text-gray-800 dark:text-gray-200">{{ $candidate->tanggal_lahir ? \Carbon\Carbon::parse($candidate->tanggal_lahir)->format('d M Y') : 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Source</p>
                <p class="text-gray-800 dark:text-gray-200">{{ $candidate->source }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Applicant ID</p>
                <p class="text-gray-800 dark:text-gray-200">{{ $candidate->applicant_id }}</p>
            </div>
        </div>
    </div>

    <!-- Education Panel -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Education</h3>
        @if($candidate->educations->isNotEmpty())
            @foreach($candidate->educations as $education)
                <div class="mb-4">
                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $education->institution ?? 'N/A' }}</p>
                    <p class="text-gray-600 dark:text-gray-300">{{ $education->level ?? 'N/A' }} - {{ $education->major ?? 'N/A' }}</p>
                    @if($education->gpa)
                        <p class="text-sm text-gray-500 dark:text-gray-400">GPA: {{ $education->gpa }}</p>
                    @endif
                </div>
            @endforeach
        @else
            <p class="text-gray-500 dark:text-gray-400">No education information available.</p>
        @endif
    </div>
</div>
