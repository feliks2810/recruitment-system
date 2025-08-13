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

    /**
     * Bulk update status for multiple candidates
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'exists:candidates,id',
            'status' => 'required|string',
            'stage' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $candidateIds = $request->input('candidate_ids');
        $status = $request->input('status');
        $stage = $request->input('stage');
        $notes = $request->input('notes');

        $candidates = Candidate::whereIn('id', $candidateIds)->get();
        $updatedCount = 0;

        foreach ($candidates as $candidate) {
            if ($this->canUpdateStage($candidate, $stage)) {
                $fields = self::STAGE_FIELDS[$stage] ?? null;
                
                if ($fields) {
                    $candidate->{$fields['date']} = now();
                    $candidate->{$fields['result']} = $status;
                    $candidate->{$fields['notes']} = $notes;
                    
                    // Update current stage and overall status
                    $this->updateCandidateStatus($candidate);
                    
                    $candidate->save();
                    $updatedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil update status {$updatedCount} kandidat",
            'updated_count' => $updatedCount
        ]);
    }

    /**
     * Bulk move candidates to next stage
     */
    public function bulkMoveStage(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'exists:candidates,id',
            'target_stage' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $candidateIds = $request->input('candidate_ids');
        $targetStage = $request->input('target_stage');
        $notes = $request->input('notes');

        $candidates = Candidate::whereIn('id', $candidateIds)->get();
        $movedCount = 0;

        foreach ($candidates as $candidate) {
            if ($this->canUpdateStage($candidate, $targetStage)) {
                $fields = self::STAGE_FIELDS[$targetStage] ?? null;
                
                if ($fields) {
                    $candidate->{$fields['date']} = now();
                    $candidate->{$fields['result']} = 'PENDING';
                    $candidate->{$fields['notes']} = $notes ?: "Dipindahkan ke stage {$targetStage}";
                    
                    // Update current stage
                    $candidate->current_stage = $targetStage;
                    $candidate->save();
                    
                    $movedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil pindahkan {$movedCount} kandidat ke stage {$targetStage}",
            'moved_count' => $movedCount
        ]);
    }

    /**
     * Bulk delete candidates
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'exists:candidates,id',
        ]);

        $candidateIds = $request->input('candidate_ids');
        $candidates = Candidate::whereIn('id', $candidateIds)->get();
        $deletedCount = 0;

        foreach ($candidates as $candidate) {
            try {
                $this->deleteFiles($candidate);
                $candidate->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                Log::error("Failed to delete candidate {$candidate->id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil hapus {$deletedCount} kandidat",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Bulk export candidates with filters
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'format' => 'nullable|string|in:excel,csv,pdf',
        ]);

        $filters = $request->input('filters', []);
        $columns = $request->input('columns', []);
        $format = $request->input('format', 'excel');

        $query = Candidate::query();

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nama', 'like', "%{$filters['search']}%")
                  ->orWhere('alamat_email', 'like', "%{$filters['search']}%")
                  ->orWhere('vacancy', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('current_stage', $filters['status']);
        }

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $candidates = $query->get();

        // If no specific columns selected, use default
        if (empty($columns)) {
            $columns = ['nama', 'vacancy', 'department', 'current_stage', 'overall_status', 'created_at'];
        }

        $filename = 'candidates_export_' . date('Y-m-d_H-i-s');

        if ($format === 'csv') {
            return $this->exportToCsv($candidates, $columns, $filename);
        } elseif ($format === 'pdf') {
            return $this->exportToPdf($candidates, $columns, $filename);
        } else {
            return $this->exportToExcel($candidates, $columns, $filename);
        }
    }

    /**
     * Export to CSV
     */
    private function exportToCsv($candidates, $columns, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function() use ($candidates, $columns) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($file, $columns);
            
            // Write data
            foreach ($candidates as $candidate) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = $candidate->{$column} ?? '';
                }
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportToPdf($candidates, $columns, $filename)
    {
        // For now, return CSV as PDF is more complex
        // You can implement PDF generation using packages like DomPDF or Snappy
        return $this->exportToCsv($candidates, $columns, $filename);
    }

    /**
     * Export to Excel
     */
    private function exportToExcel($candidates, $columns, $filename)
    {
        // Use existing export logic
        return $this->export(request());
    }

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
            $query->where('overall_status', $status);
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

        $candidates = $query->orderByRaw("CASE WHEN overall_status = 'LULUS' THEN 2 WHEN overall_status = 'DITOLAK' THEN 3 ELSE 1 END")
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);

        // Calculate statistics
        $stats = $this->calculateStats($latestDuplicateCandidateIds);

        $statuses = Candidate::select('overall_status')->distinct()->pluck('overall_status');

        return view('candidates.index', compact(
            'candidates', 
            'stats', 
            'search', 
            'status', 
            'type', 
            'latestDuplicateCandidateIds',
            'statuses'
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
            $candidate->{$fields['date']} = now();
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
                    $candidate->overall_status = 'DITOLAK';
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
        $duplicateApplicantIds = Candidate::select('applicant_id')
            ->groupBy('applicant_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('applicant_id');

        if ($duplicateApplicantIds->isEmpty()) {
            return [];
        }

        $candidatesByAppId = Candidate::whereIn('applicant_id', $duplicateApplicantIds)
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('applicant_id');

        $latestDuplicateCandidateIds = [];
        foreach ($candidatesByAppId as $candidates) {
            for ($i = 0; $i < $candidates->count() - 1; $i++) {
                $current = $candidates[$i];
                $next = $candidates[$i + 1];
                // Check if the next candidate was created within a year of the previous one
                if ($next->created_at->lessThanOrEqualTo($current->created_at->addYear())) {
                    $latestDuplicateCandidateIds[] = $next->id;
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
            'ditolak' => Candidate::where('overall_status', 'DITOLAK')->count(),
            'duplicate' => count($duplicateIds),
        ];
    }

    private function generateTimeline(Candidate $candidate): array
    {
        $timeline = [];
        $timeline[] = [
            'stage' => 'Seleksi Berkas',
            'stage_key' => 'seleksi_berkas',
            'status' => 'completed',
            'date' => $candidate->created_at,
            'notes' => 'Berkas lengkap dan sesuai kualifikasi',
            'evaluator' => 'HR Team',
            'result' => 'LULUS',
        ];

        $stages = [
            ['Psikotes', 'psikotes', 'Psikolog'],
            ['Interview HC', 'interview_hc', 'HC Team'],
            ['Interview User', 'interview_user', 'Department Team'],
            ['Interview BOD/GM', 'interview_bod', 'BOD/GM'],
            ['Offering Letter', 'offering_letter', 'HR Team'],
            ['Medical Check Up', 'mcu', 'Medical Team'],
            ['Hiring', 'hiring', 'HR Team'],
        ];

        $failed = false;
        foreach ($stages as $stageInfo) {
            if ($failed) {
                $timeline[] = [
                    'stage' => $stageInfo[0],
                    'stage_key' => $stageInfo[1],
                    'status' => 'skipped',
                    'date' => null,
                    'notes' => 'Tidak diproses karena gagal pada tahap sebelumnya',
                    'evaluator' => $stageInfo[2],
                    'result' => null,
                    'field_date' => '',
                    'field_result' => '',
                    'field_notes' => '',
                ];
                continue;
            }

            $stageData = $this->createTimelineStage($stageInfo[0], $stageInfo[1], $candidate, $stageInfo[2]);
            $timeline[] = $stageData;

            if (isset($stageData['result']) && in_array($stageData['result'], self::FAILED_RESULTS)) {
                $failed = true;
            }
        }

        return $timeline;
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
                $overallStatus = 'DITOLAK';
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