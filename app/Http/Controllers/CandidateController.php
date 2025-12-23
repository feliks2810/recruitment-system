<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Vacancy;
use App\Services\ApplicationStageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    /**
     * =========================
     * UPDATE APPLICATION STAGE
     * =========================
     *
     * @param Request $request
     * @param Application $application
     * @param ApplicationStageService $stageService
     * @return JsonResponse
     */
    public function updateStage(Request $request, Application $application, ApplicationStageService $stageService): JsonResponse
    {
        $validated = $request->validate([
            'stage' => 'required|string',
            'result' => 'required|string',
            'notes' => 'nullable|string',
            'next_stage_date' => 'nullable|date',
        ]);

        try {
            $stageService->processStageUpdate($application, $validated);
            return response()->json(['message' => 'Stage updated successfully.']);
        } catch (\Exception $e) {
            \Log::error('Error updating stage: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error updating stage: ' . $e->getMessage()], 500);
        }
    }
    


    /**
     * =========================
     * CANDIDATE LIST
     * =========================
     */
    public function index(Request $request)
    {
        $type = $request->input('type', 'organic');

        $query = Candidate::with([
            'department',
            'latestPsikotest',
            'latestHCInterview',
            'applications.vacancy',
        ]);

        // Handle candidate type filter
        if ($type === 'duplicate') {
            $query->where('is_suspected_duplicate', true);
        } else if ($type === 'non-organic') {
            $query->where('airsys_internal', 'No');
        } else { // organic
            $query->where('airsys_internal', 'Yes');
        }

        // Filter by status (simplified)
        if ($request->filled('status')) {
            $status = strtoupper($request->status);
            // This is a simplified filter. A full implementation would require more complex queries
            // to perfectly match the computed `getFinalStatusAttribute`.
            if ($status === 'FAILED') {
                $query->whereHas('applicationStages', function ($q) {
                    $q->where('status', 'GAGAL');
                });
            } elseif ($status === 'HIRED') {
                $query->whereHas('applicationStages', function ($q) {
                    $q->whereIn('status', ['LULUS', 'DITERIMA'])->where('stage_name', 'hc_interview');
                });
            }
        }

        // Search by name, applicant_id, or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('applicant_id', 'like', "%{$search}%")
                  ->orWhere('alamat_email', 'like', "%{$search}%");
            });
        }
        
        // Filter by vacancy
        if ($request->filled('vacancy_id')) {
            $query->whereHas('applications', function ($q) use ($request) {
                $q->where('vacancy_id', $request->vacancy_id);
            });
        }

        // Order by overall_status (PROSES dulu, DITOLAK terakhir), then by created_at desc
        $candidates = $query
            ->leftJoin('applications', 'candidates.id', '=', 'applications.candidate_id')
            ->select('candidates.*')
            ->orderByRaw("CASE 
                WHEN applications.overall_status = 'PROSES' THEN 1
                WHEN applications.overall_status = 'LULUS' THEN 2
                WHEN applications.overall_status = 'CANCEL' THEN 3
                WHEN applications.overall_status = 'DITOLAK' THEN 4
                ELSE 5
            END")
            ->orderByDesc('candidates.created_at')
            ->paginate(15);

        $statuses = [
            'ON_PROCESS' => 'On Process',
            'WAITING_HC_INTERVIEW' => 'Waiting HC Interview',
            'HIRED' => 'Hired',
            'FAILED' => 'Failed',
        ];

        // Get real statistics from Application overall_status
        $stats = [
            'total_candidates' => Application::count(),
            'candidates_in_process' => Application::where('overall_status', 'PROSES')->count(),
            'candidates_passed' => Application::where('overall_status', 'LULUS')->count(),
            'candidates_failed' => Application::where('overall_status', 'DITOLAK')->count(),
            'candidates_cancelled' => Application::where('overall_status', 'CANCEL')->count(),
            'duplicate' => Candidate::where('is_suspected_duplicate', true)->count(),
        ];

        $activeVacancies = \App\Models\Vacancy::where('proposal_status', 'approved')->get();

        return view('candidates.index', compact(
            'candidates', 
            'statuses', 
            'stats',
            'activeVacancies',
            'type'
        ));
    }

    /**
     * =========================
     * CANDIDATE DETAIL
     * =========================
     */
    public function show($id)
    {
        $candidate = Candidate::with([
            'department',
            'applications.vacancy',
            'applications.stages',
            'latestPsikotest',
            'latestHCInterview',
        ])->findOrFail($id);

        // Get the most recent application to work with in the view
        $application = $candidate->applications->sortByDesc('updated_at')->first();

        // The timeline is an accessor on the Candidate model
        $timeline = $candidate->timeline;
        
        // The "Move Position" modal needs a list of other active vacancies
        $activeVacancies = \App\Models\Vacancy::where('proposal_status', 'approved')
                                               ->where('id', '!=', $application?->vacancy_id)
                                               ->get();

        return view('candidates.show', compact(
            'candidate', 
            'application',
            'timeline',
            'activeVacancies'
        ));
    }

    /**
     * =========================
     * UPDATE STATUS (OPTIONAL)
     * =========================
     * Kalau suatu saat HR update manual
     */
    public function updateStatus(Request $request, $id)
    {
        $candidate = Candidate::findOrFail($id);

        $request->validate([
            'status' => 'required|string',
        ]);

        $candidate->update([
            'status' => strtoupper($request->status),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Candidate status updated successfully.');
    }

    /**
     * Placeholder for moving candidate position.
     */
    public function movePosition(Request $request, Application $application)
    {
        $validated = $request->validate([
            'new_vacancy_id' => 'required|exists:vacancies,id',
        ]);

        $newVacancy = Vacancy::find($validated['new_vacancy_id']);
        $candidate = $application->candidate;

        // Create a new application
        $newApplication = Application::create([
            'candidate_id' => $candidate->id,
            'vacancy_id' => $newVacancy->id,
            'overall_status' => 'PROSES',
        ]);

        // Deactivate old application
        $application->update(['overall_status' => 'PINDAH']);

        // Add a note to the old application's interview_bod stage
        $application->stages()->updateOrCreate(
            ['stage_name' => 'interview_bod'],
            [
                'status' => 'PINDAH_POSISI',
                'notes' => 'Kandidat dipindahkan ke posisi baru: ' . $newVacancy->name,
                'conducted_by' => auth()->user()->name,
            ]
        );

        return response()->json(['message' => 'Candidate position moved successfully.']);
    }
}
