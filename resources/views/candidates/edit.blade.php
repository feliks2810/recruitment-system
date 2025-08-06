@foreach ($candidates as $candidate)
    <tr>
        <td>{{ $candidate->applicant_id }}</td>
        <td>{{ $candidate->nama }}</td>
        <td>{{ $candidate->alamat_email }}</td>
        <td>{{ $candidate->vacancy_airsys }}</td>
        <td>{{ $candidate->current_stage }}</td>
        <td>{{ $candidate->overall_status }}</td>
        @if ($candidate instanceof \App\Models\NonOrganicCandidate)
            <td>{{ $candidate->contract_type }}</td>
            <td>{{ $candidate->company }}</td>
        @else
            <td>-</td>
            <td>-</td>
        @endif
        <td>
            @if (Auth::user()->hasRole('team_hc'))
                <form action="{{ route('candidates.toggle-organic', $candidate->id) }}" method="POST">
                    @csrf
                    @method('POST')
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                        {{ $candidate->airsys_internal === 'Yes' ? 'Jadikan Non-Organik' : 'Jadikan Organik' }}
                    </button>
                </form>
            @endif
        </td>
    </tr>
@endforeach