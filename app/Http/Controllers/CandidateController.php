<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Http\Requests\CandidateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class CandidateController extends Controller
{
    // Stage field mapping for cleaner code
    private const STAGE_FIELDS = [
        'psikotes' => [
            'date' => 'psikotest_date',
            'result' => 'psikotes_result',
            'notes' => 'psikotes_notes'
        ],
        'interview_hc' => [
            'date' => 'hc_interview_date',
            'result' => 'hc_interview_status',
            'notes' => 'hc_interview_notes'
        ],
        'interview_user' => [
            'date' => 'user_interview_date',
            'result' => 'user_interview_status',
            'notes' => 'user_interview_notes'
        ],
        'interview_bod' => [
            'date' => 'bodgm_interview_date',
            'result' => 'bod_interview_status',
            'notes' => 'bod_interview_notes'
        ],
        'offering_letter' => [
            'date' => 'offering_letter_date',
            'result' => 'offering_letter_status',
            'notes' => 'offering_letter_notes'
        ],
        'mcu' => [
            'date' => 'mcu_date',
            'result' => 'mcu_status',
            'notes' => 'mcu_notes'
        ],
        'hiring' => [
            'date' => 'hiring_date',
            'result' => 'hiring_status',
            'notes' => 'hiring_notes'
        ],
    ];

    // Valid result values for each type
    private const PASSED_RESULTS = ['LULUS', 'DISARANKAN', 'DITERIMA', 'HIRED'];
    private const FAILED_RESULTS = ['TIDAK LULUS', 'TIDAK DISARANKAN', 'DITOLAK', 'TIDAK DIHIRING', 'CANCEL'];
    private const PENDING_RESULTS = ['PENDING', 'DIPERTIMBANGKAN', 'SENT'];

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $type = $request->input('type', 'organic');

        $query = Candidate::query();

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%$search%")
                  ->orWhere('alamat_email', 'like', "%$search%")
                  ->orWhere('vacancy', 'like', "%$search%")
                  ->orWhere('applicant_id', 'like', "%$search%");
            });
        }

        // Apply status filter
        if ($status) {
            $query->where('current_stage', $status);
        }

        // Get duplicate candidate IDs with improved logic
        $latestDuplicateCandidateIds = $this->getDuplicateCandidateIds();

        // Apply role-based and type-based filters
        $user = Auth::user();
        if ($user && $user->hasRole('department')) {
            $query->where('department', $user->department);
        } elseif ($type === 'duplicate') {
            $query->whereIn('id', $latestDuplicateCandidateIds);
        } else {
            $query->where('airsys_internal', $type === 'organic' ? 'Yes' : 'No');
        }

        $candidates = $query->orderBy('created_at', 'desc')->paginate(10);

        // Calculate statistics
        $stats = $this->calculateStats($latestDuplicateCandidateIds);

        return view('candidates.index', compact(
            'candidates', 
            'stats', 
            'search', 
            'status', 
            'type', 
            'latestDuplicateCandidateIds'
        ));
    }

    public function show($id)
    {
        $candidate = Candidate::findOrFail($id);
        $timeline = $this->generateTimeline($candidate);
        
        return view('candidates.show', compact('candidate', 'timeline'));
    }

    public function create()
    {
        return view('candidates.create');
    }

    public function store(CandidateRequest $request)
    {
        try {
            $candidateData = $request->validated();
            
            // Handle file uploads
            $candidateData = $this->handleFileUploads($request, $candidateData);

            // Set initial values
            $candidateData['current_stage'] = 'CV Review';
            $candidateData['overall_status'] = 'DALAM PROSES';
            $candidateData['no'] = Candidate::count() + 1;

            Candidate::create($candidateData);

            return redirect()->route('candidates.index')
                            ->with('success', 'Kandidat berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Error creating candidate: ' . $e->getMessage());
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Terjadi kesalahan saat menambah kandidat.');
        }
    }

    public function edit(Candidate $candidate)
    {
        return view('candidates.edit', compact('candidate'));
    }

    public function update(CandidateRequest $request, Candidate $candidate)
    {
        try {
            $candidateData = $request->validated();
            
            // Handle file uploads with deletion of old files
            $candidateData = $this->handleFileUploads($request, $candidateData, $candidate);

            $candidate->update($candidateData);

            return redirect()->route('candidates.show', $candidate)
                            ->with('success', 'Kandidat berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error updating candidate: ' . $e->getMessage());
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Terjadi kesalahan saat memperbarui kandidat.');
        }
    }

    public function destroy(Candidate $candidate)
    {
        try {
            // Delete associated files
            $this->deleteFiles($candidate);
            
            $candidate->delete();

            return redirect()->route('candidates.index')
                            ->with('success', 'Kandidat berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Error deleting candidate: ' . $e->getMessage());
            return redirect()->back()
                            ->with('error', 'Terjadi kesalahan saat menghapus kandidat.');
        }
    }

    public function updateStage(Request $request, Candidate $candidate)
    {
        try {
            $validated = $request->validate([
                'stage' => 'required|string|in:' . implode(',', array_keys(self::STAGE_FIELDS)),
                'date' => 'nullable|date',
                'result' => 'required|string',
                'notes' => 'nullable|string|max:1000',
            ]);

            $stageKey = $validated['stage'];
            
            if (!isset(self::STAGE_FIELDS[$stageKey])) {
                return redirect()->route('candidates.show', $candidate)
                               ->with('error', 'Tahapan tidak valid.');
            }

            // Check if previous stages are completed
            if (!$this->canUpdateStage($candidate, $stageKey)) {
                return redirect()->route('candidates.show', $candidate)
                               ->with('error', 'Selesaikan tahapan sebelumnya terlebih dahulu.');
            }

            // Validate result against stage-specific allowed values
            $stageAllowedResults = [
                'psikotes' => ['LULUS', 'TIDAK LULUS', 'DIPERTIMBANGKAN'],
                'interview_hc' => ['DISARANKAN', 'TIDAK DISARANKAN', 'DIPERTIMBANGKAN', 'CANCEL'],
                'interview_user' => ['DISARANKAN', 'TIDAK DISARANKAN', 'DIPERTIMBANGKAN', 'CANCEL'],
                'interview_bod' => ['DISARANKAN', 'TIDAK DISARANKAN', 'DIPERTIMBANGKAN', 'CANCEL'],
                'offering_letter' => ['DITERIMA', 'DITOLAK', 'SENT'],
                'mcu' => ['LULUS', 'TIDAK LULUS'],
                'hiring' => ['HIRED', 'TIDAK DIHIRING'],
            ];

            $resultValue = strtoupper(trim($validated['result']));
            if (isset($stageAllowedResults[$stageKey]) && !in_array($resultValue, $stageAllowedResults[$stageKey], true)) {
                return redirect()->route('candidates.show', $candidate)
                    ->with('error', 'Nilai hasil tidak valid untuk tahapan ini.');
            }

            $fields = self::STAGE_FIELDS[$stageKey];

            // Update fields directly to avoid mass-assignment quirks
            if (!empty($validated['date'])) {
                $candidate->{$fields['date']} = $validated['date'];
            }
            $candidate->{$fields['result']} = $resultValue;
            $candidate->{$fields['notes']} = $validated['notes'];

            $candidate->save();
            $candidate->refresh();

            // Update overall status and current stage
            $this->updateCandidateStatus($candidate);

            // Ensure hiring stage forces overall status appropriately
            if ($stageKey === 'hiring') {
                if ($resultValue === 'HIRED') {
                    $candidate->overall_status = 'LULUS';
                } elseif ($resultValue === 'TIDAK DIHIRING') {
                    $candidate->overall_status = 'TIDAK LULUS';
                }
                $candidate->current_stage = 'Hiring';
                $candidate->save();
            }

            return redirect()->route('candidates.show', $candidate)
                            ->with('success', 'Tahapan berhasil diperbarui.');
                            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                            ->withErrors($e->errors())
                            ->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating stage: ' . $e->getMessage());
            return redirect()->route('candidates.show', $candidate)
                            ->with('error', 'Terjadi kesalahan saat memperbarui tahapan.');
        }
    }

    public function switchType(Candidate $candidate)
    {
        try {
            $candidate->airsys_internal = ($candidate->airsys_internal === 'Yes') ? 'No' : 'Yes';
            $candidate->save();

            $newType = ($candidate->airsys_internal === 'Yes') ? 'organic' : 'non-organic';

            return redirect()->route('candidates.index', ['type' => $newType])
                            ->with('success', "Kandidat berhasil dipindahkan ke {$newType}.");
        } catch (\Exception $e) {
            Log::error('Error switching candidate type: ' . $e->getMessage());
            return redirect()->back()
                            ->with('error', 'Terjadi kesalahan saat memindahkan kandidat.');
        }
    }

    public function export(Request $request)
    {
        try {
            return Excel::download(new \App\Exports\CandidatesExport, 'kandidat_' . now()->format('Ymd_His') . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Error exporting candidates: ' . $e->getMessage());
            return redirect()->back()
                            ->with('error', 'Terjadi kesalahan saat mengekspor data.');
        }
    }

    // Private helper methods

    private function getDuplicateCandidateIds(): array
    {
        $latestDuplicateCandidateIds = [];
        
        $duplicateApplicantIds = Candidate::select('applicant_id')
            ->groupBy('applicant_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('applicant_id');

        foreach ($duplicateApplicantIds as $appId) {
            $candidates = Candidate::where('applicant_id', $appId)
                ->orderBy('created_at', 'asc')
                ->get();

            for ($i = 0; $i < $candidates->count(); $i++) {
                $current = $candidates[$i];
                for ($j = $i + 1; $j < $candidates->count(); $j++) {
                    $next = $candidates[$j];
                    
                    if ($next->created_at->lessThanOrEqualTo($current->created_at->addYear())) {
                        $latestDuplicateCandidateIds[] = $next->id;
                    }
                }
            }
        }

        return array_unique($latestDuplicateCandidateIds);
    }

    private function calculateStats(array $duplicateIds): array
    {
        return [
            'total' => Candidate::count(),
            'dalam_proses' => Candidate::where('overall_status', 'DALAM PROSES')->count(),
            'pending' => Candidate::where('overall_status', 'PENDING')->count(),
            'lulus' => Candidate::where('overall_status', 'LULUS')->count(),
            'hired' => Candidate::where('overall_status', 'LULUS')->count(),
            'tidak_lulus' => Candidate::where('overall_status', 'TIDAK LULUS')->count(),
            'duplicate' => count($duplicateIds),
        ];
    }

    private function generateTimeline(Candidate $candidate): array
    {
        return [
            [
                'stage' => 'Seleksi Berkas',
                'stage_key' => 'seleksi_berkas',
                'status' => 'completed',
                'date' => $candidate->created_at,
                'notes' => 'Berkas lengkap dan sesuai kualifikasi',
                'evaluator' => 'HR Team',
                'result' => 'LULUS',
            ],
            $this->createTimelineStage('Psikotes', 'psikotes', $candidate, 'Psikolog'),
            $this->createTimelineStage('Interview HC', 'interview_hc', $candidate, 'HC Team'),
            $this->createTimelineStage('Interview User', 'interview_user', $candidate, 'Department Team'),
            $this->createTimelineStage('Interview BOD/GM', 'interview_bod', $candidate, 'BOD/GM'),
            $this->createTimelineStage('Offering Letter', 'offering_letter', $candidate, 'HR Team'),
            $this->createTimelineStage('Medical Check Up', 'mcu', $candidate, 'Medical Team'),
            $this->createTimelineStage('Hiring', 'hiring', $candidate, 'HR Team'),
        ];
    }

    private function createTimelineStage(string $stageName, string $stageKey, Candidate $candidate, string $evaluator): array
    {
        $fields = self::STAGE_FIELDS[$stageKey];
        
        return [
            'stage' => $stageName,
            'stage_key' => $stageKey,
            'status' => $this->getStageStatus($candidate->{$fields['result']}),
            'date' => $candidate->{$fields['date']},
            'notes' => $candidate->{$fields['notes']},
            'evaluator' => $evaluator,
            'result' => $candidate->{$fields['result']},
            'field_date' => $fields['date'],
            'field_result' => $fields['result'],
            'field_notes' => $fields['notes'],
        ];
    }

    private function getStageStatus(?string $result): string
    {
        if (!$result) return 'pending';
        
        if (in_array($result, self::PASSED_RESULTS)) return 'completed';
        if (in_array($result, self::PENDING_RESULTS)) return 'current';
        if (in_array($result, self::FAILED_RESULTS)) return 'failed';
        
        return 'pending';
    }

    private function canUpdateStage(Candidate $candidate, string $stageKey): bool
    {
        $stageOrder = array_keys(self::STAGE_FIELDS);
        $currentIndex = array_search($stageKey, $stageOrder);
        
        if ($currentIndex === false || $currentIndex === 0) {
            return true; // First stage or invalid stage
        }

        // Check if previous stage is completed successfully
        $previousStageKey = $stageOrder[$currentIndex - 1];
        $previousFields = self::STAGE_FIELDS[$previousStageKey];
        $previousResult = $candidate->{$previousFields['result']};

        return $previousResult && in_array($previousResult, self::PASSED_RESULTS);
    }

    private function updateCandidateStatus(Candidate $candidate): void
    {
        $currentStage = 'Seleksi Berkas';
        $overallStatus = 'DALAM PROSES';

        // Check each stage for failures
        foreach (self::STAGE_FIELDS as $stageKey => $fields) {
            $result = $candidate->{$fields['result']};
            
            if ($result && in_array($result, self::FAILED_RESULTS)) {
                $overallStatus = 'TIDAK LULUS';
                $currentStage = $this->getStageDisplayName($stageKey);
                break;
            }
        }

        // If not failed, determine current stage
        if ($overallStatus !== 'TIDAK LULUS') {
            $stageNames = [
                'psikotes' => 'Psikotes',
                'interview_hc' => 'Interview HC',
                'interview_user' => 'Interview User',
                'interview_bod' => 'Interview BOD/GM',
                'offering_letter' => 'Offering Letter',
                'mcu' => 'Medical Check Up',
                'hiring' => 'Hiring',
            ];

            foreach (self::STAGE_FIELDS as $stageKey => $fields) {
                $result = $candidate->{$fields['result']};
                
                if (!$result || !in_array($result, self::PASSED_RESULTS)) {
                    $currentStage = $stageNames[$stageKey];
                    break;
                }
                $currentStage = $stageNames[$stageKey];
            }

            // Check if fully completed (hired)
            if ($candidate->hiring_status === 'HIRED') {
                $overallStatus = 'LULUS';
            }
        }

        $candidate->current_stage = $currentStage;
        $candidate->overall_status = $overallStatus;
        $candidate->save();
    }

    private function getStageDisplayName(string $stageKey): string
    {
        $displayNames = [
            'psikotes' => 'Psikotes',
            'interview_hc' => 'Interview HC',
            'interview_user' => 'Interview User',
            'interview_bod' => 'Interview BOD/GM',
            'offering_letter' => 'Offering Letter',
            'mcu' => 'Medical Check Up',
            'hiring' => 'Hiring',
        ];

        return $displayNames[$stageKey] ?? $stageKey;
    }

    private function handleFileUploads(Request $request, array $data, ?Candidate $candidate = null): array
    {
        if ($request->hasFile('cv')) {
            if ($candidate && $candidate->cv) {
                Storage::disk('public')->delete($candidate->cv);
            }
            $data['cv'] = $request->file('cv')->store('candidates/cv', 'public');
        }

        if ($request->hasFile('flk')) {
            if ($candidate && $candidate->flk) {
                Storage::disk('public')->delete($candidate->flk);
            }
            $data['flk'] = $request->file('flk')->store('candidates/flk', 'public');
        }

        return $data;
    }

    private function deleteFiles(Candidate $candidate): void
    {
        if ($candidate->cv) {
            Storage::disk('public')->delete($candidate->cv);
        }
        if ($candidate->flk) {
            Storage::disk('public')->delete($candidate->flk);
        }
    }
}