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
        // Generate applicant_id otomatis
        $today = now()->format('dm');
        $countToday = Candidate::whereDate('created_at', now()->toDateString())->count() + 1;
        $applicantId = $today . '-' . str_pad($countToday, 3, '0', STR_PAD_LEFT);
        return view('candidates.create', compact('applicantId'));
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
            // Validasi next_test_date jika hasil tes tahap manapun = LULUS
            $nextTestRequired = false;
            $nextTestStages = [
                'psikotes_result',
                'hc_interview_status',
                'user_interview_status',
                'bod_interview_status',
                'mcu_status',
                'offering_letter_status',
                'hiring_status',
            ];
            foreach ($nextTestStages as $stage) {
                if ($request->has($stage) && strtoupper($request->input($stage)) === 'LULUS') {
                    $nextTestRequired = true;
                    break;
                }
            }
            $rules = [];
            if ($nextTestRequired) {
                $rules['next_test_date'] = 'required|date';
                $validator = \Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }
            }
            // Handle file uploads with deletion of old files
            $candidateData = $this->handleFileUploads($request, $candidateData, $candidate);
            $candidate->update($candidateData);
            $candidate->next_test_date = $request->input('next_test_date');
            $candidate->save();
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
            $passingResults = implode(',', self::PASSED_RESULTS);

            $validated = $request->validate([
                'stage' => 'required|string|in:' . implode(',', array_keys(self::STAGE_FIELDS)),
                'result' => 'required|string',
                'notes' => 'nullable|string|max:1000',
                'next_test_stage' => 'nullable|string',
                'next_test_date' => ['nullable', 'date', "required_if:result,{$passingResults}"],
            ], [
                'next_test_date.required_if' => 'Tanggal tes berikutnya wajib diisi jika hasil tes lulus.'
            ]);

            $stageKey = $validated['stage'];
            
            if (!isset(self::STAGE_FIELDS[$stageKey])) {
                return response()->json(['success' => false, 'message' => 'Tahapan tidak valid.'], 400);
            }

            if (!$this->canUpdateStage($candidate, $stageKey)) {
                return response()->json(['success' => false, 'message' => 'Selesaikan tahapan sebelumnya terlebih dahulu.'], 400);
            }

            $fields = self::STAGE_FIELDS[$stageKey];
            $resultValue = strtoupper(trim($validated['result']));

            // Update stage fields
            $candidate->{$fields['date']} = now();
            $candidate->{$fields['result']} = $resultValue;
            $candidate->{$fields['notes']} = $validated['notes'];

            // Update next test info if passed
            if (in_array($resultValue, self::PASSED_RESULTS)) {
                $candidate->next_test_stage = $validated['next_test_stage'];
                $candidate->next_test_date = $validated['next_test_date'];
            } else {
                // If failed or pending, clear any future test dates that might have been set previously
                $candidate->next_test_stage = null;
                $candidate->next_test_date = null;
            }

            $candidate->save();

            // Update overall status and current stage after saving
            $this->updateCandidateStatus($candidate);

            return response()->json([
                'success' => true, 
                'message' => 'Tahapan berhasil diperbarui.',
            ]);
                            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak valid.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating stage: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui tahapan.'], 500);
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

    /**
     * Set the next test date for a candidate
     */
    public function setNextTestDate(Request $request, $id)
    {
        $request->validate(['next_test_date' => 'required|date']);
        $candidate = Candidate::findOrFail($id);
        $candidate->next_test_date = $request->next_test_date;
        $candidate->save();
        return redirect()->route('candidates.show', $candidate->id)->with('success', 'Tanggal tes berikutnya berhasil disimpan.');
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

        $candidatesByAppId = Candidate::whereIn('applicant_id', $duplicateApplicantIds)->pluck('id')->toArray();
        return $candidatesByAppId;
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

    /**
     * Delete all files associated with a candidate
     */
    private function deleteFiles(Candidate $candidate): void
    {
        $fileFields = [
            'cv_file',
            'ijazah_file',
            'transkrip_file',
            'ktp_file',
            'foto_file',
            'skck_file',
            'surat_keterangan_sehat_file',
        ];

        foreach ($fileFields as $field) {
            if ($candidate->$field && Storage::exists($candidate->$field)) {
                Storage::delete($candidate->$field);
            }
        }
    }

    /**
     * Calculate recruitment statistics
     */
    private function calculateStats(array $duplicateIds): array
    {
        return [
            'dalam_proses' => Candidate::where('overall_status', 'DALAM PROSES')->count(),
            'hired' => Candidate::where('overall_status', 'LULUS')->count(),
            'ditolak' => Candidate::whereIn('overall_status', ['TIDAK LULUS', 'DITOLAK'])->count(),
            'duplicate' => count($duplicateIds),
        ];
    }

    /**
     * Handle file uploads for a candidate
     */
    private function handleFileUploads(Request $request, array $data, ?Candidate $candidate = null): array
    {
        $fileFields = [
            'cv_file',
            'ijazah_file',
            'transkrip_file',
            'ktp_file',
            'foto_file',
            'skck_file',
            'surat_keterangan_sehat_file',
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if exists
                if ($candidate && $candidate->$field) {
                    Storage::delete($candidate->$field);
                }

                $file = $request->file($field);
                $path = $file->store('public/candidates');
                $data[$field] = $path;
            }
        }

        return $data;
    }

    /**
     * Get display name for a stage
     */
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

    /**
     * Generate timeline array for candidate stages
     */
    private function generateTimeline(Candidate $candidate)
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
}
